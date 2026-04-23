<?php defined('ABSPATH') || exit;

class Vesho_CRM_Client_Portal {

    public static function init() {
        if (!session_id() && !headers_sent() && !is_admin()) {
            session_start();
        }
        // Hide WP admin bar on portal pages
        add_filter('show_admin_bar', [__CLASS__, 'maybe_hide_admin_bar']);
        add_shortcode('vesho_client_portal', [__CLASS__, 'shortcode_portal']);
        add_shortcode('vesho_client_login',  [__CLASS__, 'shortcode_login']);
        add_shortcode('vesho_price_list',    [__CLASS__, 'shortcode_price_list']);
        // Moved to includes/shortcodes.php (server-side PHP rebuild)
        // add_shortcode('vesho_shop',          [__CLASS__, 'shortcode_shop']);
        // add_shortcode('vesho_shop_grid',     [__CLASS__, 'shortcode_shop_grid']);
        // Invoice print — fires before WordPress outputs the theme, so no header appears
        add_action('template_redirect', [__CLASS__, 'maybe_print_invoice']);

        $nopriv_actions = ['vesho_client_login', 'vesho_client_register', 'vesho_client_logout'];
        add_action('wp_ajax_vesho_resend_verification',        [__CLASS__, 'ajax_resend_verification']);
        add_action('wp_ajax_nopriv_vesho_resend_verification', [__CLASS__, 'ajax_resend_verification']);
        add_action('wp_ajax_nopriv_vesho_forgot_password', [__CLASS__, 'ajax_forgot_password']);
        add_action('wp_ajax_vesho_forgot_password',        [__CLASS__, 'ajax_forgot_password']);
        $auth_actions   = [
            'vesho_client_update_profile',
            'vesho_client_change_password',
            'vesho_client_book_service',
            'vesho_submit_ticket',
            'vesho_client_cancel_booking',
            'vesho_client_upgrade_to_firma',
            'vesho_client_delete_account',
            'vesho_client_cancel_order',
        ];
        add_action('wp_ajax_vesho_client_reply_ticket', [__CLASS__, 'ajax_client_reply_ticket']);
        add_action('wp_ajax_vesho_client_return_request', [__CLASS__, 'ajax_client_return_request']);

        foreach ($nopriv_actions as $action) {
            add_action('wp_ajax_nopriv_' . $action, [__CLASS__, 'ajax_' . str_replace('vesho_client_', '', $action)]);
            add_action('wp_ajax_' . $action,        [__CLASS__, 'ajax_' . str_replace('vesho_client_', '', $action)]);
        }
        foreach ($auth_actions as $action) {
            $method = str_replace('vesho_client_', '', str_replace('vesho_submit_', 'submit_', $action));
            add_action('wp_ajax_' . $action, [__CLASS__, 'ajax_' . $method]);
        }

        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
        add_action('wp_footer', [__CLASS__, 'register_form_fix_script']);
        add_filter('template_include',  [__CLASS__, 'force_portal_template']);
    }

    // ── Force portal template (site header+footer) ────────────────────────────
    public static function force_portal_template( $template ) {
        global $post;
        if ( $post && (
            has_shortcode( $post->post_content, 'vesho_client_portal' ) ||
            has_shortcode( $post->post_content, 'vesho_client_login' )
        )) {
            $pt = locate_template( 'page-portal.php' );
            if ( $pt ) return $pt;
        }
        return $template;
    }

    public static function maybe_hide_admin_bar($show) {
        global $post;
        if ( $post && (
            has_shortcode($post->post_content, 'vesho_client_portal') ||
            has_shortcode($post->post_content, 'vesho_client_login')
        )) {
            return false;
        }
        return $show;
    }

    // ── Auth ──────────────────────────────────────────────────────────────────

    private static function get_client() {
        return self::get_current_client();
    }

    public static function get_current_client() {
        if (!is_user_logged_in()) return null;
        $user = wp_get_current_user();
        global $wpdb;
        // Admin can also be a client — auto-create record if missing
        if (current_user_can('manage_options')) {
            $client = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vesho_clients WHERE user_id=%d LIMIT 1",
                $user->ID
            ));
            if (!$client) {
                $wpdb->insert($wpdb->prefix . 'vesho_clients', [
                    'user_id'     => $user->ID,
                    'name'        => $user->display_name ?: $user->user_login,
                    'email'       => $user->user_email,
                    'phone'       => '',
                    'client_type' => 'eraisik',
                    'created_at'  => current_time('mysql'),
                ]);
                $user->add_role('vesho_client');
                $client = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}vesho_clients WHERE user_id=%d LIMIT 1",
                    $user->ID
                ));
            }
            return $client;
        }
        if (!in_array('vesho_client', (array) $user->roles)) return null;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_clients WHERE user_id=%d LIMIT 1",
            $user->ID
        ));
    }

    // ── Assets ────────────────────────────────────────────────────────────────

    public static function enqueue_assets() {
        global $post;
        if (!$post) return;
        if (has_shortcode($post->post_content, 'vesho_client_portal') ||
            has_shortcode($post->post_content, 'vesho_client_login')) {
            // CSS on teemas (assets/css/portal.css), teema enqueue_assets laeb selle.
            // Fallback kui teema ei ole vesho (nt child theme mis ei laadi portal.css)
            if (!wp_style_is('vesho-portal', 'enqueued')) {
                $theme_css = get_template_directory_uri() . '/assets/css/portal.css';
                wp_enqueue_style('vesho-portal', $theme_css, array(), filemtime(get_template_directory() . '/assets/css/portal.css'));
            }
        }
    }

    // ── Shortcodes ────────────────────────────────────────────────────────────

    public static function shortcode_login() {
        $client = self::get_client();
        if ($client) return '<p>Olete sisse logitud. <a href="' . esc_url(home_url('/klient/')) . '">Mine portaali &rarr;</a></p>';
        ob_start();
        self::render_login_form();
        return ob_get_clean();
    }

    // ── Invoice print — runs before WordPress theme, clean standalone page ─────
    public static function maybe_print_invoice() {
        if ( ! isset($_GET['cpaction']) || $_GET['cpaction'] !== 'print_invoice' ) return;
        $invoice_id = absint($_GET['invoice_id'] ?? 0);
        $nonce      = sanitize_text_field($_GET['nonce'] ?? '');
        if ( ! $invoice_id || ! wp_verify_nonce($nonce, 'cp_print_' . $invoice_id) ) {
            wp_safe_redirect( home_url('/') );
            exit;
        }
        $client = self::get_current_client();
        if ( ! $client ) {
            wp_safe_redirect( home_url('/') );
            exit;
        }
        global $wpdb;
        $inv = $wpdb->get_row($wpdb->prepare(
            "SELECT i.*, c.name as client_name, c.email as client_email, c.address as client_address, c.company as client_company
             FROM {$wpdb->prefix}vesho_invoices i
             LEFT JOIN {$wpdb->prefix}vesho_clients c ON i.client_id = c.id
             WHERE i.id = %d AND i.client_id = %d",
            $invoice_id, $client->id
        ));
        if ( ! $inv ) {
            wp_safe_redirect( home_url('/') );
            exit;
        }
        $items    = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_invoice_items WHERE invoice_id=%d ORDER BY id ASC",
            $invoice_id
        ));
        $co_name  = get_option('vesho_company_name', 'Vesho OÜ');
        $co_reg   = get_option('vesho_company_reg', '');
        $co_vat   = get_option('vesho_company_vat', '');
        $co_addr  = get_option('vesho_company_address', '');
        $co_bank  = get_option('vesho_company_bank', '');
        $co_iban  = get_option('vesho_company_iban', '');
        $co_email = get_option('vesho_company_email', '');
        $logo_url = get_option('vesho_company_logo', '');
        self::render_invoice_print($inv, $items, compact('co_name','co_reg','co_vat','co_addr','co_bank','co_iban','co_email','logo_url'));
        exit;
    }

    private static function render_invoice_print( $inv, $items, $co ) {
        extract($co);
        ?><!DOCTYPE html>
<html lang="et">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Arve <?php echo esc_html($inv->invoice_number); ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Helvetica Neue',Arial,sans-serif;font-size:13px;color:#111;background:#fff;padding:32px 40px}
.inv-wrap{max-width:760px;margin:0 auto}
.inv-top{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:32px;padding-bottom:20px;border-bottom:2px solid #e2e8f0}
.inv-logo img{max-height:48px;max-width:180px;object-fit:contain}
.inv-logo-text{font-size:22px;font-weight:800;color:#0d1f2d;letter-spacing:-0.5px}
.inv-logo-text span{color:#00b4c8}
.inv-co-info{text-align:right;font-size:12px;color:#555;line-height:1.6}
.inv-co-name{font-size:15px;font-weight:700;color:#0d1f2d;margin-bottom:4px}
.inv-meta{display:flex;justify-content:space-between;margin-bottom:28px;gap:24px}
.inv-title-block .inv-title{font-size:32px;font-weight:800;color:#0d1f2d;letter-spacing:-1px}
.inv-number{font-size:18px;font-weight:600;color:#00b4c8;margin-top:4px}
.inv-dates{margin-top:8px;font-size:12px;color:#555;line-height:1.8}
.inv-client{text-align:right;font-size:13px;color:#333;line-height:1.7}
.inv-client-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:4px}
table{width:100%;border-collapse:collapse;margin:0 0 20px}
thead tr{background:#f8fafc;border-bottom:2px solid #e2e8f0}
th{padding:10px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#64748b}
td{padding:10px 12px;border-bottom:1px solid #f1f5f9;font-size:13px}
tbody tr:last-child td{border-bottom:none}
.inv-totals{margin-left:auto;width:280px;margin-top:8px}
.inv-totals table{margin:0}
.inv-totals td{padding:5px 12px;border:none;font-size:13px}
.inv-totals tr.total-row td{font-size:16px;font-weight:800;border-top:2px solid #0d1f2d;padding-top:10px}
.inv-bank{margin-top:24px;padding:14px 16px;background:#f8fafc;border-radius:8px;font-size:12px;color:#555;line-height:1.8}
.inv-bank strong{color:#0d1f2d}
.inv-print-btn{margin-top:28px;text-align:right}
.inv-print-btn button{padding:10px 24px;background:#00b4c8;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:14px;font-weight:600}
@media print{
    .inv-print-btn{display:none}
    body{padding:16px}
}
</style>
</head>
<body>
<div class="inv-wrap">

  <div class="inv-top">
    <div class="inv-logo">
      <?php if (!empty($logo_url)): ?>
        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($co_name); ?>">
      <?php else: ?>
        <div class="inv-logo-text"><?php
          $parts = explode(' ', $co_name, 2);
          echo esc_html($parts[0]);
          if (!empty($parts[1])) echo ' <span>'.esc_html($parts[1]).'</span>';
        ?></div>
      <?php endif; ?>
    </div>
    <div class="inv-co-info">
      <div class="inv-co-name"><?php echo esc_html($co_name); ?></div>
      <?php if ($co_addr)  echo '<div>'.esc_html($co_addr).'</div>'; ?>
      <?php if ($co_reg)   echo '<div>Reg: '.esc_html($co_reg).'</div>'; ?>
      <?php if ($co_vat)   echo '<div>KMKR: '.esc_html($co_vat).'</div>'; ?>
      <?php if ($co_email) echo '<div>'.esc_html($co_email).'</div>'; ?>
    </div>
  </div>

  <div class="inv-meta">
    <div class="inv-title-block">
      <div class="inv-title">ARVE</div>
      <div class="inv-number"><?php echo esc_html($inv->invoice_number); ?></div>
      <div class="inv-dates">
        Kuupäev: <?php echo $inv->invoice_date ? date('d.m.Y', strtotime($inv->invoice_date)) : '—'; ?><br>
        <?php if ($inv->due_date): ?>Tähtaeg: <?php echo date('d.m.Y', strtotime($inv->due_date)); ?><?php endif; ?>
      </div>
    </div>
    <div class="inv-client">
      <div class="inv-client-label">Klient</div>
      <strong><?php echo esc_html($inv->client_name); ?></strong><br>
      <?php if ($inv->client_company) echo esc_html($inv->client_company).'<br>'; ?>
      <?php if ($inv->client_address) echo nl2br(esc_html($inv->client_address)).'<br>'; ?>
      <?php echo esc_html($inv->client_email); ?>
    </div>
  </div>

  <table>
    <thead><tr>
      <th>#</th><th>Kirjeldus</th><th style="text-align:right">Kogus</th>
      <th style="text-align:right">Ühikuhind</th><th style="text-align:right">KM %</th>
      <th style="text-align:right">Kokku</th>
    </tr></thead>
    <tbody>
    <?php
    $subtotal = 0; $vat_total = 0;
    foreach ($items as $i => $item):
        $line = (float)$item->total;
        $vat  = round($line * ((float)$item->vat_rate / 100), 2);
        $subtotal  += $line;
        $vat_total += $vat;
    ?>
    <tr>
      <td style="color:#94a3b8"><?php echo $i+1; ?></td>
      <td><?php echo esc_html($item->description); ?></td>
      <td style="text-align:right"><?php echo number_format((float)$item->quantity, 2, ',', ''); ?></td>
      <td style="text-align:right"><?php echo number_format((float)$item->unit_price, 2, ',', ' '); ?> €</td>
      <td style="text-align:right"><?php echo (int)$item->vat_rate; ?>%</td>
      <td style="text-align:right"><strong><?php echo number_format($line, 2, ',', ' '); ?> €</strong></td>
    </tr>
    <?php endforeach; ?>
    <?php if (empty($items)): ?>
    <tr><td colspan="6" style="text-align:center;padding:20px;color:#94a3b8">Arveread puuduvad</td></tr>
    <?php endif; ?>
    </tbody>
  </table>

  <div class="inv-totals">
    <table>
      <tr><td style="color:#555">Summa ilma KM-ta:</td><td style="text-align:right"><?php echo number_format($subtotal, 2, ',', ' '); ?> €</td></tr>
      <tr><td style="color:#555">KM:</td><td style="text-align:right"><?php echo number_format($vat_total, 2, ',', ' '); ?> €</td></tr>
      <tr class="total-row"><td>KOKKU:</td><td style="text-align:right"><?php echo number_format($subtotal + $vat_total, 2, ',', ' '); ?> €</td></tr>
    </table>
  </div>

  <?php if ($co_bank || $co_iban): ?>
  <div class="inv-bank">
    <strong>Pangaandmed:</strong><?php
    if ($co_bank) echo ' '  . esc_html($co_bank);
    if ($co_iban) echo ' | IBAN: ' . esc_html($co_iban);
    ?>
  </div>
  <?php endif; ?>

  <?php if ($inv->description): ?>
  <div style="margin-top:16px;color:#555;font-size:12px"><?php echo esc_html($inv->description); ?></div>
  <?php endif; ?>

  <div class="inv-print-btn">
    <button onclick="window.print()">📄 Laadi PDF</button>
  </div>

</div>
<script>window.onload=function(){window.print();};</script>
</body>
</html>
<?php
    }

    public static function shortcode_portal() {

        // ── Email verification (must run before maintenance mode check) ───────
        $verify_token = isset($_GET['verify_email']) ? sanitize_text_field($_GET['verify_email']) : '';
        if ($verify_token) {
            global $wpdb;
            $client_id = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}vesho_clients WHERE email_verify_token=%s AND email_verified=0 LIMIT 1",
                $verify_token
            ));
            ob_start();
            if ($client_id) {
                $wpdb->update($wpdb->prefix.'vesho_clients',
                    ['email_verified' => 1, 'email_verify_token' => ''],
                    ['id' => $client_id]
                );
                echo '<div style="padding:40px;text-align:center;font-family:sans-serif"><div style="font-size:48px;margin-bottom:16px">✅</div><h2>E-post kinnitatud!</h2><p>Sinu e-posti aadress on edukalt kinnitatud. Nüüd saad sisse logida.</p><p><a href="'.esc_url(remove_query_arg('verify_email')).'" style="color:#00b4c8;font-weight:600">Logi sisse &rarr;</a></p></div>';
            } else {
                echo '<div style="padding:40px;text-align:center;font-family:sans-serif"><div style="font-size:48px;margin-bottom:16px">❌</div><h2>Kinnituslehel viga</h2><p>Link on aegunud või juba kasutatud. Proovi uuesti sisse logida.</p></div>';
            }
            return ob_get_clean();
        }

        // ── Maintenance mode ──────────────────────────────────────────────────
        if (get_option('vesho_maintenance_mode','0') === '1' && !current_user_can('manage_options')) {
            $msg = esc_html(get_option('vesho_maintenance_message','Hooldus käib. Palume vabandust ebamugavuse pärast!'));
            return '<div style="min-height:60vh;display:flex;align-items:center;justify-content:center;padding:40px 20px;text-align:center">
                <div>
                    <div style="font-size:64px;margin-bottom:20px">🔧</div>
                    <h2 style="font-size:24px;font-weight:700;color:#0d1f2d;margin-bottom:12px">Hooldus käib</h2>
                    <p style="color:#6b8599;font-size:15px;max-width:400px;margin:0 auto">' . $msg . '</p>
                </div>
            </div>';
        }



        // ── Payment return handlers ───────────────────────────────────────────
        if (!empty($_GET['vesho_mc_return']) && !empty($_GET['invoice_id'])) {
            $inv_id     = absint($_GET['invoice_id']);
            $shop_id    = get_option('vesho_mc_shop_id','');
            $secret_key = get_option('vesho_mc_secret_key','');
            $sandbox    = get_option('vesho_mc_sandbox','1') === '1';
            if ($shop_id && $secret_key) {
                $base_url = $sandbox ? 'https://api.test.maksekeskus.ee/v1' : 'https://api.maksekeskus.ee/v1';
                global $wpdb;
                $tx_id = $wpdb->get_var($wpdb->prepare("SELECT mc_transaction_id FROM {$wpdb->prefix}vesho_invoices WHERE id=%d", $inv_id));
                if ($tx_id) {
                    $ver = wp_remote_get("$base_url/transactions/$tx_id/", [
                        'headers' => ['Authorization' => 'Basic ' . base64_encode("$shop_id:$secret_key")],
                    ]);
                    $ver_data = json_decode(wp_remote_retrieve_body($ver), true);
                    if (($ver_data['status']??'') === 'completed') {
                        vesho_mark_invoice_paid($inv_id);
                        wp_redirect(add_query_arg(['tab'=>'invoices','mc_paid'=>'1'], get_permalink()));
                        exit;
                    }
                }
            }
            wp_redirect(add_query_arg(['tab'=>'invoices','mc_pending'=>'1'], get_permalink()));
            exit;
        }
        if (!empty($_GET['vesho_montonio_return']) && !empty($_GET['invoice_id'])) {
            $inv_id      = absint($_GET['invoice_id']);
            $secret_key  = get_option('vesho_montonio_secret_key','');
            $order_token = sanitize_text_field($_GET['order_token'] ?? '');
            if ($secret_key && $order_token) {
                $decoded = vesho_jwt_decode($order_token, $secret_key);
                if ($decoded && ($decoded['paymentStatus']??'') === 'PAID') {
                    $ref = $decoded['merchantReference'] ?? '';
                    if (str_starts_with($ref, 'invoice-')) {
                        $id = intval(explode('-', $ref, 2)[1]);
                        if ($id === $inv_id) { vesho_mark_invoice_paid($inv_id); }
                    }
                    wp_redirect(add_query_arg(['tab'=>'invoices','montonio_paid'=>'1'], get_permalink()));
                    exit;
                }
            }
            wp_redirect(add_query_arg(['tab'=>'invoices','montonio_pending'=>'1'], get_permalink()));
            exit;
        }

        $client = self::get_current_client();
        if ( !$client ) {
            ob_start();
            self::render_login_form();
            return ob_get_clean();
        }
        // ── Email not verified — show blocked screen ──────────────────────────
        // Localhost bypass: auto-verify so development doesn't need real email
        $is_local = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost','127.0.0.1'], true)
                    || str_ends_with($_SERVER['HTTP_HOST'] ?? '', '.local');
        if ( isset($client->email_verified) && (int)$client->email_verified === 0 ) {
            if ( $is_local ) {
                global $wpdb;
                $wpdb->update(
                    $wpdb->prefix.'vesho_clients',
                    ['email_verified' => 1, 'email_verify_token' => ''],
                    ['id' => $client->id]
                );
                $client->email_verified = 1;
                // fall through to portal render below
            } else {
                ob_start();
                self::render_verify_blocked($client);
                return ob_get_clean();
            }
        }
        ob_start();
        self::render_portal($client);
        return ob_get_clean();
    }

    // ── Email verify blocked screen ───────────────────────────────────────────

    private static function render_verify_blocked($client) {
        $ajax  = esc_url(admin_url('admin-ajax.php'));
        $nonce = wp_create_nonce('vesho_portal_nonce');
        $email = esc_html($client->email ?? '');
        $logout_url = esc_url(wp_logout_url(home_url('/klient/')));
        ?>
<style>
@keyframes drip{0%,100%{transform:scaleY(1) translateY(0);opacity:1}50%{transform:scaleY(1.3) translateY(4px);opacity:.7}80%{transform:scaleY(.8) translateY(8px);opacity:.4}85%{opacity:0;transform:scaleY(.5) translateY(14px)}86%{opacity:0;transform:scaleY(1) translateY(0)}100%{opacity:1}}
@keyframes drop-fall{0%{opacity:0;transform:translateY(0) scaleY(1)}20%{opacity:1}80%{opacity:1}100%{opacity:0;transform:translateY(36px) scaleY(1.6)}}
.vverify-wrap{min-height:80vh;display:flex;align-items:center;justify-content:center;padding:32px 20px;font-family:'Barlow',sans-serif,Arial}
.vverify-card{background:#fff;border-radius:16px;padding:48px 40px;box-shadow:0 20px 60px rgba(0,0,0,.1);max-width:440px;width:100%;text-align:center;position:relative}
.vverify-card::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#00b4c8,#0891a8);border-radius:16px 16px 0 0}
.vverify-faucet{margin:0 auto 28px;width:72px;height:72px;position:relative}
.vverify-faucet svg{width:72px;height:72px}
.vverify-drop{position:absolute;left:50%;transform:translateX(-50%);bottom:-8px;width:10px;height:14px;background:#00b4c8;border-radius:50% 50% 60% 60%;animation:drop-fall 1.8s ease-in infinite;opacity:0}
.vverify-drop:nth-child(2){animation-delay:.6s}
.vverify-drop:nth-child(3){animation-delay:1.2s}
.vverify-h{font-size:1.4rem;font-weight:800;color:#0d1f2d;margin:0 0 10px;font-family:'Barlow Condensed',sans-serif,Arial;text-transform:uppercase;letter-spacing:.5px}
.vverify-sub{color:#6b8599;font-size:14px;line-height:1.6;margin:0 0 8px}
.vverify-email{display:inline-block;background:#f0fbfc;color:#0891a8;border-radius:6px;padding:4px 12px;font-size:13px;font-weight:600;margin-bottom:24px}
.vverify-btn{display:block;width:100%;padding:13px;border:none;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer;margin-bottom:10px;transition:.2s;font-family:inherit;letter-spacing:.3px}
.vverify-btn-primary{background:#00b4c8;color:#fff}
.vverify-btn-primary:hover{background:#0891a8}
.vverify-btn-primary:disabled{background:#a0c8d0;cursor:not-allowed}
.vverify-btn-ghost{background:transparent;color:#6b8599;border:1.5px solid #e0e6eb}
.vverify-btn-ghost:hover{border-color:#00b4c8;color:#0891a8}
.vverify-msg{font-size:13px;padding:8px 14px;border-radius:6px;margin-bottom:12px;display:none}
.vverify-msg.ok{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.vverify-msg.err{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca}
.vverify-divider{border:none;border-top:1px solid #e8edf0;margin:20px 0}
</style>
<div class="vverify-wrap">
  <div class="vverify-card">
    <div class="vverify-faucet">
      <svg viewBox="0 0 72 72" fill="none" xmlns="http://www.w3.org/2000/svg">
        <!-- faucet body -->
        <rect x="8" y="20" width="32" height="10" rx="5" fill="#0d1f2d"/>
        <rect x="28" y="28" width="10" height="18" rx="4" fill="#0d1f2d"/>
        <rect x="22" y="44" width="22" height="6" rx="3" fill="#1a3a4a"/>
        <!-- handle -->
        <rect x="36" y="16" width="22" height="7" rx="3.5" fill="#00b4c8"/>
        <rect x="54" y="12" width="7" height="15" rx="3.5" fill="#00b4c8"/>
        <!-- spout tip -->
        <path d="M28 50 Q33 56 38 50" stroke="#00b4c8" stroke-width="2" fill="none"/>
      </svg>
      <div class="vverify-drop"></div>
      <div class="vverify-drop"></div>
      <div class="vverify-drop"></div>
    </div>
    <h2 class="vverify-h">Kinnita e-posti aadress</h2>
    <p class="vverify-sub">Saatsime kinnituslingi aadressile</p>
    <span class="vverify-email"><?php echo $email; ?></span>
    <div class="vverify-msg" id="vverify-msg"></div>
    <button class="vverify-btn vverify-btn-primary" id="vverify-resend-btn" onclick="veshoResendVerify()">
      📧 Saada kinnituslink uuesti
    </button>
    <hr class="vverify-divider">
    <p class="vverify-sub" style="font-size:12px;margin-bottom:14px">Kui oled kinnituslingil klikkinud, värskenda lehte:</p>
    <button class="vverify-btn vverify-btn-ghost" onclick="window.location.reload()">🔄 Värskenda lehte</button>
    <hr class="vverify-divider">
    <a href="<?php echo $logout_url; ?>" style="font-size:12px;color:#a0b0bc;text-decoration:none">← Logi välja</a>
  </div>
</div>
<script>
function veshoResendVerify(){
  var btn=document.getElementById('vverify-resend-btn');
  var msg=document.getElementById('vverify-msg');
  btn.disabled=true; btn.textContent='Saadan...';
  msg.style.display='none';
  var fd=new FormData();
  fd.append('action','vesho_resend_verification');
  fd.append('nonce','<?php echo $nonce; ?>');
  fetch('<?php echo $ajax; ?>',{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){
      msg.style.display='block';
      msg.className='vverify-msg '+(d.success?'ok':'err');
      msg.textContent=(d.data&&d.data.message)||(d.success?'Saadetud!':'Viga');
      btn.disabled=false;
      btn.textContent='📧 Saada kinnituslink uuesti';
    })
    .catch(function(){
      msg.style.display='block';msg.className='vverify-msg err';msg.textContent='Ühenduse viga';
      btn.disabled=false;btn.textContent='📧 Saada kinnituslink uuesti';
    });
}
</script>
        <?php
    }

    // ── Login / Register form ─────────────────────────────────────────────────

    private static function render_login_form() {
        $ajax    = esc_url(admin_url('admin-ajax.php'));
        $nonce   = wp_create_nonce('vesho_portal_nonce');
        $reg     = get_option('vesho_portal_registration','1') === '1';
        $logo_id = get_theme_mod('custom_logo');
        $site    = esc_html(get_theme_mod('vesho_company_display_name', get_option('blogname', 'VESHO')));
        ?>
<style>
@import url('https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800&family=Barlow:wght@400;500;600&display=swap');
.vcl-wrap{min-height:100vh;background:#f0f4f8;display:flex;align-items:center;justify-content:center;padding:32px 20px;font-family:'Barlow',sans-serif}
.vcl-inner{width:100%;max-width:460px}
.vcl-logo{text-align:center;margin-bottom:24px}
.vcl-logo a{display:inline-flex;align-items:center;gap:10px;text-decoration:none}
.vcl-logo img{max-height:48px;max-width:180px;object-fit:contain}
.vcl-logo-text{font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:1.8rem;color:#0d1f2d;letter-spacing:1px;text-transform:uppercase}
.vcl-logo-text span{color:#00b4c8}
.vcl-card{background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(13,31,45,.1);overflow:hidden}
.vcl-card-head{background:linear-gradient(135deg,#0d1f2d,#1a3a4a);padding:28px 36px 24px;text-align:center}
.vcl-card-head h2{font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:1.4rem;color:#fff;text-transform:uppercase;letter-spacing:1px;margin:0}
.vcl-card-head p{color:rgba(255,255,255,.55);font-size:13px;margin:6px 0 0}
.vcl-card-body{padding:32px 36px}
.vcl-tabs{display:flex;gap:0;border-bottom:2px solid #edf2f7;margin-bottom:24px}
.vcl-tab{flex:1;background:none;border:none;border-bottom:3px solid transparent;padding:9px 6px;font-family:'Barlow',sans-serif;font-size:13px;font-weight:700;color:#94a3b8;cursor:pointer;margin-bottom:-2px;transition:.15s;text-transform:uppercase;letter-spacing:.5px}
.vcl-tab.active{color:#00b4c8;border-bottom-color:#00b4c8}
.vcl-panel{display:none}.vcl-panel.active{display:block}
.vcl-group{margin-bottom:16px}
.vcl-group label{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#64748b;margin-bottom:6px}
.vcl-group input{width:100%;padding:11px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-family:'Barlow',sans-serif;font-size:14px;color:#0d1f2d;background:#f8fafc;box-sizing:border-box;transition:.2s;outline:none}
.vcl-group input:focus{border-color:#00b4c8;background:#fff;box-shadow:0 0 0 3px rgba(0,180,200,.1)}
.vcl-btn{width:100%;padding:13px;background:#00b4c8;color:#fff;border:none;border-radius:8px;font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:1rem;text-transform:uppercase;letter-spacing:1px;cursor:pointer;transition:.2s;margin-top:4px}
.vcl-btn:hover{background:#0891a8}
.vcl-btn:disabled{background:#a0c8d0;cursor:not-allowed}
.vcl-msg{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px;display:none}
.vcl-msg.success{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0}
.vcl-msg.error{background:#fef2f2;color:#b91c1c;border:1px solid #fecaca}
.vcl-links{text-align:center;margin-top:16px;font-size:13px;color:#94a3b8;display:flex;flex-direction:column;gap:6px}
.vcl-links a,.vcl-back-link{color:#00b4c8;font-weight:600;text-decoration:none;cursor:pointer;background:none;border:none;font-size:13px;font-family:'Barlow',sans-serif;padding:0}
.vcl-links a:hover,.vcl-back-link:hover{text-decoration:underline}
.vcl-type-wrap{display:flex;gap:8px;margin-bottom:16px}
.vcl-type-btn{flex:1;border:1.5px solid #e2e8f0;border-radius:8px;padding:10px 6px;cursor:pointer;text-align:center;font-size:13px;font-weight:600;color:#5a7080;transition:.15s;background:#f8fafc;user-select:none;font-family:'Barlow',sans-serif}
.vcl-type-btn.selected{border-color:#00b4c8;color:#00b4c8;background:#f0fbfc}
.vcl-hint{font-size:13px;color:#6b8599;margin-bottom:16px;line-height:1.5}
</style>
<div class="vcl-wrap">
  <div class="vcl-inner">
    <div class="vcl-logo">
      <a href="<?php echo esc_url(home_url('/')); ?>">
        <?php if ($logo_id): ?>
          <img src="<?php echo esc_url(wp_get_attachment_image_url($logo_id, 'full')); ?>" alt="<?php echo esc_attr($site); ?>">
        <?php else: ?>
          <svg viewBox="0 0 32 32" fill="none" width="30" height="30"><path d="M16 4C16 4 6 13 6 20a10 10 0 0020 0C26 13 16 4 16 4Z" fill="#00b4c8"/><path d="M16 12C16 12 10 18 10 22a6 6 0 0012 0C22 18 16 12 16 12Z" fill="white" opacity=".5"/></svg>
          <span class="vcl-logo-text"><?php echo $site; ?><span>.</span></span>
        <?php endif; ?>
      </a>
    </div>
    <div class="vcl-card">
      <div class="vcl-card-head">
        <h2>Klientide Portaal</h2>
        <p>Halda seadmeid, arveid ja broneeringuid</p>
      </div>
      <div class="vcl-card-body">
        <?php if ($reg): ?>
        <div class="vcl-tabs">
          <button class="vcl-tab active" data-panel="login">Logi sisse</button>
          <button class="vcl-tab" data-panel="register">Registreeru</button>
        </div>
        <?php endif; ?>

        <!-- Login -->
        <div class="vcl-panel active" id="vcl-panel-login">
          <div class="vcl-msg" id="vcl-login-msg"></div>
          <form id="vcl-login-form" autocomplete="on">
            <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
            <div class="vcl-group"><label>E-post</label><input type="email" name="email" required placeholder="nimi@ettevote.ee" autocomplete="email"></div>
            <div class="vcl-group"><label>Parool</label><input type="password" name="password" required placeholder="••••••••" autocomplete="current-password"></div>
            <button type="submit" class="vcl-btn">Logi sisse</button>
          </form>
          <div class="vcl-links">
            <a href="#" data-panel="forgot">Unustasid parooli?</a>
            <?php if ($reg): ?><span>Pole kontot? <a href="#" data-panel="register">Registreeru</a></span><?php endif; ?>
          </div>
        </div>

        <!-- Forgot -->
        <div class="vcl-panel" id="vcl-panel-forgot">
          <div class="vcl-msg" id="vcl-forgot-msg"></div>
          <p class="vcl-hint">Sisesta oma e-post ja saadame sulle parooli lähtestamise lingi.</p>
          <form id="vcl-forgot-form">
            <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
            <div class="vcl-group"><label>E-post</label><input type="email" name="email" required placeholder="nimi@ettevote.ee" autocomplete="email"></div>
            <button type="submit" class="vcl-btn">Saada link</button>
          </form>
          <div class="vcl-links"><button class="vcl-back-link" data-panel="login">← Tagasi sisselogimisele</button></div>
        </div>

        <?php if ($reg): ?>
        <!-- Register -->
        <div class="vcl-panel" id="vcl-panel-register">
          <div class="vcl-msg" id="vcl-reg-msg"></div>
          <form id="vcl-reg-form" autocomplete="on">
            <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
            <div class="vcl-type-wrap">
              <label class="vcl-type-btn selected" id="vcl-type-eraisik"><input type="radio" name="account_type" value="eraisik" checked style="display:none"> 👤 Eraisik</label>
              <label class="vcl-type-btn" id="vcl-type-firma"><input type="radio" name="account_type" value="firma" style="display:none"> 🏢 Ettevõte</label>
            </div>
            <div id="vcl-firma-fields" style="display:none">
              <div class="vcl-group"><label>Ettevõtte nimi *</label><input type="text" name="reg_company" placeholder="OÜ Näidis"></div>
              <div class="vcl-group"><label>Registrikood</label><input type="text" name="reg_number" placeholder="12345678"></div>
              <div class="vcl-group"><label>KMKR number</label><input type="text" name="vat_number" placeholder="EE123456789"></div>
            </div>
            <div class="vcl-group"><label>Täisnimi *</label><input type="text" name="name" required placeholder="Nimi Perekonnanimi" autocomplete="name"></div>
            <div class="vcl-group"><label>E-post *</label><input type="email" name="email" required placeholder="nimi@ettevote.ee" autocomplete="email"></div>
            <div class="vcl-group"><label>Telefon</label><input type="tel" name="phone" placeholder="+372 5XXX XXXX" autocomplete="tel"></div>
            <div class="vcl-group"><label>Parool * <small style="font-weight:400;text-transform:none">(min 8 märki)</small></label><input type="password" name="password" required minlength="8" placeholder="••••••••" autocomplete="new-password"></div>
            <button type="submit" class="vcl-btn">Loo konto</button>
          </form>
          <div class="vcl-links"><span>On juba konto? <a href="#" data-panel="login">Logi sisse</a></span></div>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<script>
(function(){
  var AJAX='<?php echo $ajax; ?>';
  document.addEventListener('click',function(e){
    var el=e.target.closest('[data-panel]');
    if(!el) return;
    e.preventDefault();
    var p=el.dataset.panel;
    document.querySelectorAll('.vcl-tab').forEach(function(t){t.classList.toggle('active',t.dataset.panel===p);});
    document.querySelectorAll('.vcl-panel').forEach(function(x){x.classList.remove('active');});
    var t=document.getElementById('vcl-panel-'+p);
    if(t) t.classList.add('active');
  });
  document.querySelectorAll('.vcl-type-btn').forEach(function(l){
    l.addEventListener('click',function(){
      document.querySelectorAll('.vcl-type-btn').forEach(function(x){x.classList.remove('selected');});
      l.classList.add('selected');
      var f=document.getElementById('vcl-firma-fields');
      if(f) f.style.display=l.querySelector('input').value==='firma'?'block':'none';
    });
  });
  function hf(fid,action,mid,lbl){
    var form=document.getElementById(fid);
    if(!form) return;
    form.addEventListener('submit',function(e){
      e.preventDefault();
      var msg=document.getElementById(mid),btn=form.querySelector('button[type=submit]');
      var fd=new FormData(form); fd.append('action',action);
      btn.disabled=true; btn.textContent='...'; msg.style.display='none';
      fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        if(d.success){
          if(d.data&&d.data.redirect){
            msg.style.display='block';msg.className='vcl-msg success';msg.textContent=d.data.message||'Õnnestus!';
            setTimeout(function(){window.location.href=d.data.redirect;},600);
          } else if(d.data&&d.data.verify){
            var card=form.closest('.vcl-card-body');
            if(card) card.innerHTML='<div style="text-align:center;padding:32px 0"><div style="font-size:52px;margin-bottom:16px">💧</div><h3 style="font-family:Barlow Condensed,sans-serif;font-size:1.4rem;font-weight:800;text-transform:uppercase;color:#0d1f2d;margin:0 0 12px">Konto loodud!</h3><p style="font-size:14px;color:#5a7080;margin:0 0 8px">Saatsime Sulle kinnitusmeili.</p><p style="font-size:14px;color:#5a7080;margin:0 0 20px">Klõpsa kirjas oleval lingil et aktiveerida konto.</p><p style="font-size:12px;color:#a0b0bc">Ei leidnud? Kontrolli rämpsposti.</p></div>';
          } else {
            msg.style.display='block';msg.className='vcl-msg success';msg.textContent=(d.data&&d.data.message)||'Õnnestus!';
          }
        } else {
          msg.style.display='block';msg.className='vcl-msg error';msg.textContent=(d.data&&d.data.message)||'Viga!';
          btn.disabled=false;btn.textContent=lbl;
        }
      }).catch(function(){msg.style.display='block';msg.className='vcl-msg error';msg.textContent='Ühenduse viga';btn.disabled=false;btn.textContent=lbl;});
    });
  }
  hf('vcl-login-form','vesho_client_login','vcl-login-msg','Logi sisse');
  hf('vcl-reg-form','vesho_client_register','vcl-reg-msg','Loo konto');
  hf('vcl-forgot-form','vesho_forgot_password','vcl-forgot-msg','Saada link');
})();
</script>
        <?php
    }

    // ── Portal shell ──────────────────────────────────────────────────────────

    private static function render_portal($client) {
        global $wpdb;
        $tab = isset($_GET['ptab']) ? sanitize_text_field($_GET['ptab']) : 'dashboard';
        $base = get_permalink();
        $logout_url = wp_logout_url(home_url('/klient/'));
        $nonce = wp_create_nonce('vesho_portal_nonce');
        $ajax  = esc_url(admin_url('admin-ajax.php'));
        $cid   = (int) $client->id;

        // Read portal settings from admin
        $show_devices      = get_option('vesho_portal_show_devices',      '1') === '1';
        $show_maintenances = get_option('vesho_portal_show_maintenances', '1') === '1';
        $show_services     = get_option('vesho_portal_show_services',     '1') === '1';
        $show_invoices     = get_option('vesho_portal_show_invoices',     '1') === '1';
        $show_support      = get_option('vesho_portal_show_support',      '1') === '1';
        $show_orders       = get_option('vesho_portal_show_orders',       '1') === '1';

        $show_contract = get_option('vesho_show_contract_terms', '0') === '1';

        $nav_items = ['dashboard' => ['icon' => '&#9634;', 'label' => 'Ülevaade']];
        if ($show_devices)      $nav_items['devices']      = ['icon' => '&#128297;', 'label' => 'Seadmed'];
        // Maintenance = unified tab (booking + list + photos), replaces old "maintenances" + "services"
        if ($show_maintenances || $show_services) $nav_items['maintenances'] = ['icon' => '&#128295;', 'label' => 'Hooldus'];
        if ($show_invoices)     $nav_items['invoices']     = ['icon' => '&#128196;', 'label' => 'Arved'];
        if ($show_support)      $nav_items['support']      = ['icon' => '&#127881;', 'label' => 'Tugi'];
        if ($show_orders)       $nav_items['orders']       = ['icon' => '&#128722;', 'label' => 'Tellimused'];
        if ($show_contract)     $nav_items['contract']     = ['icon' => '&#128221;', 'label' => 'Lepingutingimused'];
        $nav_items['profile'] = ['icon' => '&#128100;', 'label' => 'Profiil'];

        $avatar   = strtoupper(mb_substr($client->name ?? 'K', 0, 1));
        $logo_id  = get_theme_mod('custom_logo');
        $site_name = esc_html(get_theme_mod('vesho_company_display_name', get_option('blogname', 'VESHO')));
        ?>
<div class="vcp-wrap">
  <aside class="vcp-sidebar" id="vcpSidebar">
    <div class="vcp-sidebar-logo">
      <?php if ($logo_id): ?>
        <a href="<?php echo esc_url(home_url('/')); ?>">
          <img src="<?php echo esc_url(wp_get_attachment_image_url($logo_id, 'full')); ?>"
               alt="<?php echo esc_attr(get_option('blogname')); ?>">
        </a>
      <?php else: ?>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="vcp-sidebar-logo-text">
          <?php echo $site_name; ?><span>.</span>
        </a>
      <?php endif; ?>
    </div>
    <div class="vcp-sidebar-label">Menüü</div>
    <button class="vcp-nav-arrow vcp-nav-arrow--left" id="vcpNavLeft" aria-hidden="true">&#8249;</button>
    <ul class="vcp-nav" id="vcpNavScroll">
      <?php foreach ($nav_items as $tid => $item): ?>
      <li><a href="<?php echo esc_url(add_query_arg('ptab', $tid, $base)); ?>"
             class="vcp-nav-link <?php echo $tab === $tid ? 'active' : ''; ?>">
        <span class="vcp-nav-icon"><?php echo $item['icon']; ?></span>
        <?php echo esc_html($item['label']); ?>
      </a></li>
      <?php endforeach; ?>
    </ul>
    <button class="vcp-nav-arrow vcp-nav-arrow--right" id="vcpNavRight" aria-hidden="true">&#8250;</button>
    <div class="vcp-sidebar-footer">
      <div class="vcp-sidebar-user">
        <div class="vcp-avatar"><?php echo esc_html($avatar); ?></div>
        <div style="min-width:0">
          <div class="vcp-sidebar-name"><?php echo esc_html($client->name ?? ''); ?></div>
          <div class="vcp-sidebar-email"><?php echo esc_html($client->email ?? ''); ?></div>
        </div>
      </div>
      <a href="<?php echo esc_url($logout_url); ?>" class="vcp-logout-link">↩ Logi välja</a>
    </div>
  </aside>

  <div class="vcp-main">
    <header class="vcp-topbar">
      <button class="vcp-hamburger" id="vcpHamburger" aria-label="Menüü">&#9776;</button>
      <span class="vcp-topbar-title"><?php echo esc_html($nav_items[$tab]['label'] ?? 'Portaal'); ?></span>
      <div class="vcp-topbar-right">Tere, <strong><?php echo esc_html(explode(' ', $client->name)[0]); ?></strong></div>
    </header>
    <div class="vcp-content">
      <?php
      // ── Global announcement (admin seab kohe, kõigile) ───────────────────────
      $g_ann = get_option('vesho_global_announcement', '');
      $g_type = get_option('vesho_global_announcement_type', 'info');
      if ($g_ann):
          $g_bg  = $g_type==='warning' ? '#fff7ed' : ($g_type==='success' ? '#f0fdf4' : '#eef2ff');
          $g_brd = $g_type==='warning' ? '#fcd34d' : ($g_type==='success' ? '#86efac' : '#c7d2fe');
          $g_col = $g_type==='warning' ? '#92400e' : ($g_type==='success' ? '#166534' : '#3730a3');
          $g_icon = $g_type==='warning' ? '⚠️' : ($g_type==='success' ? '✅' : '📣');
      ?>
      <div style="background:<?php echo $g_bg; ?>;border-bottom:2px solid <?php echo $g_brd; ?>;padding:10px 16px;display:flex;align-items:flex-start;gap:8px;font-size:13px;font-weight:600;color:<?php echo $g_col; ?>;flex-wrap:wrap">
        <span style="flex-shrink:0;line-height:1.4"><?php echo $g_icon; ?></span>
        <span style="flex:1;min-width:0;word-break:break-word;line-height:1.4"><?php echo esc_html($g_ann); ?></span>
      </div>
      <?php endif; ?>
      <?php
      // ── Portal notices (layout-tasemel, igas tab-is, 3006 stiil) ─────────────
      $today_n = current_time('Y-m-d');
      $client_notices = $wpdb->get_results($wpdb->prepare(
          "SELECT * FROM {$wpdb->prefix}vesho_portal_notices
           WHERE active=1 AND target IN ('client','both')
           AND (starts_at IS NULL OR starts_at <= %s)
           AND (ends_at IS NULL OR ends_at >= %s)
           ORDER BY created_at DESC LIMIT 5",
          $today_n, $today_n
      ));
      if ( $client_notices ) : ?>
      <script>
      var _vcp_dismissed = (function(){try{return JSON.parse(sessionStorage.getItem('vcp_dismissed')||'[]');}catch(e){return [];}})();
      function vcpDismiss(id){_vcp_dismissed.push(id);sessionStorage.setItem('vcp_dismissed',JSON.stringify(_vcp_dismissed));var el=document.getElementById('vcpn-'+id);if(el)el.style.display='none';}
      </script>
      <?php foreach ($client_notices as $cn) :
          $type   = $cn->type ?? 'info';
          $bg     = $type==='warning' ? 'rgba(245,158,11,0.12)' : ($type==='success' ? 'rgba(16,185,129,0.12)' : 'rgba(99,102,241,0.12)');
          $border = $type==='warning' ? 'rgba(245,158,11,0.3)'  : ($type==='success' ? 'rgba(16,185,129,0.3)'  : 'rgba(99,102,241,0.3)');
          $color  = $type==='warning' ? '#f59e0b' : ($type==='success' ? '#10b981' : '#818cf8');
          $icon   = $type==='warning' ? '⚠️' : ($type==='success' ? '✅' : 'ℹ️');
      ?>
      <div id="vcpn-<?php echo (int)$cn->id; ?>" style="background:<?php echo $bg; ?>;border-bottom:1px solid <?php echo $border; ?>;padding:10px 20px;display:flex;align-items:center;gap:10px;font-size:13px;margin-bottom:8px;border-radius:6px">
        <span style="font-size:16px"><?php echo $icon; ?></span>
        <span style="color:<?php echo $color; ?>;font-weight:600"><?php echo esc_html($cn->title); ?></span>
        <span style="color:#9ca3af">— <?php echo esc_html($cn->message); ?></span>
        <button onclick="vcpDismiss(<?php echo (int)$cn->id; ?>)" style="margin-left:auto;background:none;border:none;cursor:pointer;color:#9ca3af;font-size:18px;line-height:1;padding:0 4px">×</button>
      </div>
      <?php endforeach; ?>
      <script>
      (function(){_vcp_dismissed.forEach(function(id){var el=document.getElementById('vcpn-'+id);if(el)el.style.display='none';});})();
      </script>
      <?php endif; ?>
      <?php
      switch ($tab) {
          case 'dashboard':   self::tab_dashboard($client, $cid, $base, $nonce); break;
          case 'devices':     self::tab_devices($cid); break;
          case 'maintenances':self::tab_maintenances($cid); break;
          case 'services':    self::tab_services($cid, $nonce, $ajax); break;
          case 'invoices':    self::tab_invoices($cid); break;
          case 'support':     self::tab_support($cid, $nonce, $ajax); break;
          case 'orders':      self::tab_orders($client, $cid); break;
          case 'contract':    self::tab_contract(); break;
          case 'profile':     self::tab_profile($client, $nonce, $ajax); break;
          default:            self::tab_dashboard($client, $cid, $base, $nonce);
      }
      ?>
    </div>
  </div>
</div>
<script>
(function(){
  var sidebar=document.getElementById('vcpSidebar');
  var ham=document.getElementById('vcpHamburger');
  var backdrop=document.createElement('div');
  backdrop.className='vcp-backdrop';
  document.body.appendChild(backdrop);
  function openSidebar(){
    sidebar.classList.add('open');
    backdrop.classList.add('active');
    document.body.classList.add('vcp-sidebar-open');
  }
  function closeSidebar(){
    sidebar.classList.remove('open');
    backdrop.classList.remove('active');
    document.body.classList.remove('vcp-sidebar-open');
  }
  if(ham&&sidebar){
    ham.addEventListener('click',function(e){
      e.stopPropagation();
      sidebar.classList.contains('open')?closeSidebar():openSidebar();
    });
  }
  backdrop.addEventListener('click',closeSidebar);
  // Nav scroll arrows
  (function(){
    var nav=document.getElementById('vcpNavScroll');
    var btnL=document.getElementById('vcpNavLeft');
    var btnR=document.getElementById('vcpNavRight');
    if(!nav||!btnL||!btnR) return;
    function upd(){
      btnL.disabled=nav.scrollLeft<=1;
      btnR.disabled=nav.scrollLeft>=nav.scrollWidth-nav.clientWidth-1;
    }
    nav.addEventListener('scroll',upd,{passive:true});
    btnL.addEventListener('click',function(){nav.scrollBy({left:-120,behavior:'smooth'});});
    btnR.addEventListener('click',function(){nav.scrollBy({left:120,behavior:'smooth'});});
    // Scroll active item into view
    var active=nav.querySelector('.vcp-nav-link.active');
    if(active) active.scrollIntoView({inline:'nearest',block:'nearest'});
    upd();
  })();
})();
</script>
        <?php
    }

    // ── Tab: Dashboard ────────────────────────────────────────────────────────

    private static function tab_dashboard($client, $cid, $base, $nonce) {
        global $wpdb;

        $devices_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_devices WHERE client_id=%d", $cid
        ));
        $unpaid_count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_invoices WHERE client_id=%d AND status IN ('sent','overdue')", $cid
        ));
        $next_maint = $wpdb->get_row($wpdb->prepare(
            "SELECT m.id, m.scheduled_date, d.name as device_name, d.model as device_model
             FROM {$wpdb->prefix}vesho_maintenances m
             JOIN {$wpdb->prefix}vesho_devices d ON d.id=m.device_id
             WHERE d.client_id=%d AND m.status='scheduled' AND m.scheduled_date>=CURDATE()
             ORDER BY m.scheduled_date ASC LIMIT 1", $cid
        ));
        $last_maint = $wpdb->get_row($wpdb->prepare(
            "SELECT m.completed_date, d.name as device_name, d.model as device_model
             FROM {$wpdb->prefix}vesho_maintenances m
             JOIN {$wpdb->prefix}vesho_devices d ON d.id=m.device_id
             WHERE d.client_id=%d AND m.status='completed'
             ORDER BY m.completed_date DESC LIMIT 1", $cid
        ));

        $recent_invoices = $wpdb->get_results($wpdb->prepare(
            "SELECT id, invoice_number, invoice_date, amount, status FROM {$wpdb->prefix}vesho_invoices
             WHERE client_id=%d ORDER BY invoice_date DESC LIMIT 5", $cid
        ));
        $upcoming_maints = $wpdb->get_results($wpdb->prepare(
            "SELECT m.scheduled_date, m.description, m.status, d.name as device_name
             FROM {$wpdb->prefix}vesho_maintenances m
             JOIN {$wpdb->prefix}vesho_devices d ON d.id=m.device_id
             WHERE d.client_id=%d AND m.status IN ('scheduled','pending') AND m.scheduled_date>=CURDATE()
             ORDER BY m.scheduled_date ASC LIMIT 5", $cid
        ));

        // Company contact info
        $co_name    = get_option('vesho_company_name', get_bloginfo('name'));
        $co_phone   = get_option('vesho_company_phone', '');
        $co_email   = get_option('vesho_company_email', '');
        $co_address = get_option('vesho_company_address', '');

        // Loyalty discount
        $loyalty_pct = (float) get_option('vesho_shop_loyalty_discount', 0);

        $maints_url  = esc_url(add_query_arg('ptab', 'maintenances', $base));
        $invoices_url = esc_url(add_query_arg('ptab', 'invoices', $base));

        $next_date = $next_maint ? esc_html(date_i18n('d.m.Y', strtotime($next_maint->scheduled_date))) : '—';
        $days_until = '';
        if ($next_maint) {
            $diff = (strtotime($next_maint->scheduled_date) - time()) / 86400;
            $days_until = $diff <= 1 ? 'Homme' : (int)ceil($diff) . ' päeva';
        }
        ?>
<div class="vcp-page-header">
  <h1>Tere tulemast, <?php echo esc_html(explode(' ', $client->name)[0]); ?>!</h1>
  <?php if (!empty($client->company)) echo '<p>' . esc_html($client->company) . '</p>'; ?>
</div>

<?php if ($loyalty_pct > 0): ?>
<div style="display:flex;align-items:center;gap:10px;padding:10px 16px;background:rgba(16,185,129,0.07);border:1px solid rgba(16,185,129,0.2);border-radius:12px;margin-bottom:16px">
  <span style="font-size:20px">⭐</span>
  <div>
    <div style="font-size:13px;font-weight:700;color:#059669">Kliendi lojaalsussoodustus: <?php echo (int)$loyalty_pct; ?>%</div>
    <div style="font-size:12px;color:#6b7280">Soodustus rakendub automaatselt e-poe tellimustele</div>
  </div>
</div>
<?php endif; ?>

<div class="vcp-stat-grid">
  <div class="vcp-stat-card vcp-stat-blue" style="cursor:pointer" onclick="location.href='<?php echo $maints_url; ?>'">
    <div class="vcp-stat-label">Järgmine hooldus</div>
    <div class="vcp-stat-value" style="font-size:<?php echo $next_maint ? '1rem' : '1.5rem'; ?>"><?php echo $next_date; ?></div>
    <?php if ($days_until): ?><div style="font-size:11px;color:#60a5fa;margin-top:4px;font-weight:600"><?php echo esc_html($days_until); ?></div><?php endif; ?>
  </div>
  <div class="vcp-stat-card vcp-stat-orange" style="cursor:pointer" onclick="location.href='<?php echo $invoices_url; ?>'">
    <div class="vcp-stat-label">Tasumata arved</div>
    <div class="vcp-stat-value"><?php echo $unpaid_count; ?></div>
  </div>
  <div class="vcp-stat-card vcp-stat-green">
    <div class="vcp-stat-label">Aktiivseid seadmeid</div>
    <div class="vcp-stat-value"><?php echo $devices_count; ?></div>
  </div>
  <?php if ($last_maint): ?>
  <div class="vcp-stat-card vcp-stat-teal">
    <div class="vcp-stat-label">Viimane hooldus</div>
    <div class="vcp-stat-value" style="font-size:1rem"><?php echo esc_html(date_i18n('d.m.Y', strtotime($last_maint->completed_date))); ?></div>
    <div style="font-size:11px;color:#2dd4bf;margin-top:4px"><?php echo esc_html($last_maint->device_name); ?></div>
  </div>
  <?php else: ?>
  <div class="vcp-stat-card vcp-stat-teal">
    <div class="vcp-stat-label">Hooldusi tehtud</div>
    <div class="vcp-stat-value">—</div>
  </div>
  <?php endif; ?>
</div>

<div class="vcp-two-col">
  <div>
    <h2 class="vcp-section-title">Viimased arved</h2>
    <?php if (empty($recent_invoices)): ?>
      <div class="vcp-empty">Arveid pole.</div>
    <?php else: ?>
      <table class="vcp-table">
        <thead><tr><th>Number</th><th>Kuupäev</th><th>Summa</th><th>Staatus</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recent_invoices as $inv): ?>
        <tr>
          <td data-label="Number"><?php echo esc_html($inv->invoice_number); ?></td>
          <td data-label="Kuupäev"><?php echo esc_html($inv->invoice_date ? date('d.m.Y', strtotime($inv->invoice_date)) : '—'); ?></td>
          <td data-label="Summa"><?php echo number_format((float)$inv->amount, 2, ',', ' '); ?> €</td>
          <td data-label="Staatus"><?php echo self::status_badge($inv->status, 'invoice'); ?></td>
          <td data-label=""><?php if ($inv->status !== 'paid'): ?>
            <button onclick="vwpOpenPayment(<?php echo (int)$inv->id; ?>,<?php echo (float)$inv->amount; ?>)"
                    style="padding:4px 12px;background:#f59e0b;border:none;border-radius:6px;color:#fff;font-size:12px;font-weight:700;cursor:pointer;white-space:nowrap">
                💳 Maksa
            </button>
          <?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php self::render_payment_modal(); ?>
    <?php endif; ?>
  </div>
  <div>
    <h2 class="vcp-section-title">Tulevased hooldused</h2>
    <?php if (empty($upcoming_maints)): ?>
      <div class="vcp-empty">Planeeritud hooldusi pole.</div>
    <?php else: ?>
      <div style="display:flex;flex-direction:column;gap:8px">
        <?php foreach ($upcoming_maints as $m): ?>
        <div class="vcp-maint-item">
          <div class="vcp-maint-date"><?php echo esc_html(date('d.m.Y', strtotime($m->scheduled_date))); ?></div>
          <div class="vcp-maint-info">
            <strong><?php echo esc_html($m->device_name); ?></strong>
            <?php if ($m->description) echo '<br><span style="color:#6b7280;font-size:12px">' . esc_html($m->description) . '</span>'; ?>
          </div>
          <div><?php echo self::status_badge($m->status, 'maintenance'); ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="vcp-quick-actions">
  <a href="<?php echo $maints_url; ?>" class="vcp-btn-primary">🔧 Broneeri hooldus</a>
  <a href="<?php echo $invoices_url; ?>" class="vcp-btn-outline">📋 Vaata arveid</a>
</div>

<?php if ($co_phone || $co_email || $co_address): ?>
<div class="vcp-card" style="margin-top:16px">
  <h2 class="vcp-section-title" style="margin-bottom:12px">📞 Kontakt</h2>
  <div style="display:flex;flex-wrap:wrap;gap:16px">
    <?php if ($co_name): ?>
    <div style="min-width:140px">
      <div style="font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px">Ettevõte</div>
      <div style="font-size:14px;font-weight:600"><?php echo esc_html($co_name); ?></div>
    </div>
    <?php endif; ?>
    <?php if ($co_phone): ?>
    <div style="min-width:140px">
      <div style="font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px">Telefon</div>
      <a href="tel:<?php echo esc_attr(preg_replace('/\s+/', '', $co_phone)); ?>" style="font-size:14px;font-weight:600;color:inherit;text-decoration:none">📞 <?php echo esc_html($co_phone); ?></a>
    </div>
    <?php endif; ?>
    <?php if ($co_email): ?>
    <div style="min-width:140px">
      <div style="font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px">E-post</div>
      <a href="mailto:<?php echo esc_attr($co_email); ?>" style="font-size:14px;font-weight:600;color:inherit;text-decoration:none">✉️ <?php echo esc_html($co_email); ?></a>
    </div>
    <?php endif; ?>
    <?php if ($co_address): ?>
    <div style="min-width:140px">
      <div style="font-size:11px;font-weight:600;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:3px">Aadress</div>
      <div style="font-size:14px;font-weight:600">📍 <?php echo esc_html($co_address); ?></div>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>
        <?php
    }

    // ── Tab: Devices ──────────────────────────────────────────────────────────

    private static function tab_devices($cid) {
        global $wpdb;
        $devices = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_devices WHERE client_id=%d ORDER BY name", $cid
        ));
        ?>
<h2 class="vcp-section-title">Minu seadmed</h2>
<?php if (empty($devices)): ?>
  <div class="vcp-empty">Seadmeid pole registreeritud.</div>
<?php else: ?>
  <table class="vcp-table">
    <thead><tr><th>Nimi</th><th>Mudel</th><th>Seerianumber</th><th>Paigaldus</th><th>Asukoht</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($devices as $d): ?>
    <tr>
      <td data-label="Seade"><strong><?php echo esc_html($d->name); ?></strong></td>
      <td data-label="Mudel"><?php echo esc_html($d->model ?? '—'); ?></td>
      <td data-label="Seeria"><code><?php echo esc_html($d->serial_number ?? '—'); ?></code></td>
      <td data-label="Paigaldus"><?php echo esc_html($d->install_date ? date('d.m.Y', strtotime($d->install_date)) : '—'); ?></td>
      <td data-label="Asukoht"><?php echo esc_html($d->location ?? '—'); ?></td>
      <td data-label="">
        <button type="button"
                class="vcp-btn-outline vcp-dev-history-toggle"
                style="font-size:12px;padding:4px 10px"
                data-device-id="<?php echo (int)$d->id; ?>"
                aria-expanded="false">
          📋 Hooldusajalugu
        </button>
      </td>
    </tr>
    <tr class="vcp-dev-history-row" id="vcp-dev-hist-<?php echo (int)$d->id; ?>" style="display:none">
      <td colspan="6" style="padding:0">
        <div style="padding:12px 16px;background:#f8fafc;border-top:1px solid #e2e8f0">
          <?php
          $maints = $wpdb->get_results($wpdb->prepare(
              "SELECT m.*, w.name as worker_name
               FROM {$wpdb->prefix}vesho_maintenances m
               LEFT JOIN {$wpdb->prefix}vesho_workers w ON m.worker_id = w.id
               WHERE m.device_id = %d
               ORDER BY m.scheduled_date DESC
               LIMIT 10",
              $d->id
          ));
          if (empty($maints)): ?>
          <p style="margin:0;color:#94a3b8;font-size:13px">Hooldusajalugu puudub.</p>
          <?php else: ?>
          <table style="width:100%;font-size:12px;border-collapse:collapse">
            <thead>
              <tr style="color:#64748b;text-align:left">
                <th style="padding:4px 8px;font-weight:600">Kuupäev</th>
                <th style="padding:4px 8px;font-weight:600">Kirjeldus</th>
                <th style="padding:4px 8px;font-weight:600">Staatus</th>
                <th style="padding:4px 8px;font-weight:600">Töötaja</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($maints as $m): ?>
            <tr style="border-top:1px solid #e2e8f0">
              <td style="padding:6px 8px;white-space:nowrap"><?php echo esc_html($m->scheduled_date ? date('d.m.Y', strtotime($m->scheduled_date)) : '—'); ?></td>
              <td style="padding:6px 8px"><?php echo esc_html($m->maintenance_type ?? $m->description ?? '—'); ?></td>
              <td style="padding:6px 8px"><?php echo self::status_badge($m->status, 'maintenance'); ?></td>
              <td style="padding:6px 8px"><?php echo esc_html($m->worker_name ?: '—'); ?></td>
            </tr>
            <?php if (!empty($m->worker_notes)): ?>
            <tr style="border-top:none">
              <td colspan="4" style="padding:0 8px 8px">
                <div style="background:#f0f9ff;border-left:3px solid #00b4c8;padding:8px 10px;border-radius:0 4px 4px 0;font-size:12px;color:#0d1f2d;line-height:1.5">
                  💬 <?php echo esc_html($m->worker_notes); ?>
                </div>
              </td>
            </tr>
            <?php endif; ?>
            <?php
            // Photos for this maintenance
            $maint_photos = $wpdb->get_results($wpdb->prepare(
                "SELECT filename FROM {$wpdb->prefix}vesho_workorder_photos WHERE maintenance_id=%d ORDER BY created_at ASC",
                $m->id
            ));
            if ($maint_photos): ?>
            <tr style="border-top:none">
              <td colspan="4" style="padding:0 8px 10px">
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                  <?php foreach ($maint_photos as $mp): ?>
                  <a href="<?php echo esc_url($mp->filename); ?>" target="_blank"
                     style="display:block;width:60px;height:60px;border-radius:6px;overflow:hidden;border:1.5px solid #e2e8f0">
                    <img src="<?php echo esc_url($mp->filename); ?>" style="width:100%;height:100%;object-fit:cover" alt="foto">
                  </a>
                  <?php endforeach; ?>
                </div>
              </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<script>
(function(){
  document.querySelectorAll('.vcp-dev-history-toggle').forEach(function(btn){
    btn.addEventListener('click', function(){
      var did = btn.dataset.deviceId;
      var row = document.getElementById('vcp-dev-hist-' + did);
      if (!row) return;
      var isOpen = row.style.display !== 'none';
      row.style.display = isOpen ? 'none' : 'table-row';
      btn.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
      btn.textContent = isOpen ? '📋 Hooldusajalugu' : '▲ Peida ajalugu';
    });
  });
})();
</script>
<?php endif; ?>
        <?php
    }

    // ── Tab: Maintenances ─────────────────────────────────────────────────────

    private static function tab_maintenances($cid) {
        global $wpdb;
        $nonce = wp_create_nonce('vesho_portal_nonce');
        $ajax  = esc_url(admin_url('admin-ajax.php'));
        $today = current_time('Y-m-d');

        // All maintenances for this client
        $all = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, d.name as device_name, d.model as device_model,
                    w.name as worker_name
             FROM {$wpdb->prefix}vesho_maintenances m
             JOIN {$wpdb->prefix}vesho_devices d ON d.id=m.device_id
             LEFT JOIN {$wpdb->prefix}vesho_workers w ON w.id=m.worker_id
             WHERE d.client_id=%d
             ORDER BY m.scheduled_date DESC LIMIT 100", $cid
        ));

        $pending   = array_filter($all, fn($m) => $m->status === 'pending');
        $scheduled = array_filter($all, fn($m) => $m->status === 'scheduled' && $m->scheduled_date >= $today);
        $completed = array_filter($all, fn($m) => $m->status === 'completed');

        // Services for booking form
        $services = $wpdb->get_results(
            "SELECT id, name, description, price FROM {$wpdb->prefix}vesho_services WHERE active=1 ORDER BY name"
        );
        // Devices for booking form
        $devices = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name FROM {$wpdb->prefix}vesho_devices WHERE client_id=%d ORDER BY name", $cid
        ));
        // Active campaign for maintenance
        $campaign = $wpdb->get_row($wpdb->prepare(
            "SELECT name, maintenance_discount_percent FROM {$wpdb->prefix}vesho_campaigns
             WHERE paused=0 AND valid_from<=%s AND valid_until>=%s
               AND (target='hooldus' OR target='both') AND maintenance_discount_percent>0
             ORDER BY maintenance_discount_percent DESC LIMIT 1",
            $today, $today
        ));
        $min_date = date('Y-m-d', strtotime('+1 day'));
        ?>

<!-- Cancel modal -->
<div id="vcp-cancel-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:24px;max-width:400px;width:90%;box-shadow:0 20px 60px rgba(0,0,0,0.2)">
    <div style="font-size:16px;font-weight:700;color:#1e293b;margin-bottom:4px">✕ Tühista hooldus</div>
    <div style="font-size:12px;color:#64748b;margin-bottom:16px" id="vcp-cancel-modal-info"></div>
    <div style="font-size:12px;color:#64748b;margin-bottom:8px">Palun lisa põhjus (valikuline)</div>
    <textarea id="vcp-cancel-reason" rows="3" placeholder="Põhjus..."
              style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;resize:vertical;box-sizing:border-box;font-family:inherit"></textarea>
    <div style="display:flex;gap:8px;margin-top:16px">
      <button id="vcp-cancel-back" style="flex:1;padding:11px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;cursor:pointer;font-size:13px;font-weight:600;color:#64748b">Tagasi</button>
      <button id="vcp-cancel-confirm" style="flex:2;padding:11px;border:none;border-radius:10px;background:#dc2626;color:#fff;cursor:pointer;font-size:13px;font-weight:700">✕ Tühistan</button>
    </div>
  </div>
</div>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <h2 class="vcp-section-title" style="margin:0">Hooldus</h2>
  <button id="vcp-maint-form-toggle"
          style="background:rgba(0,180,200,0.12);color:#00b4c8;border:1px solid rgba(0,180,200,0.3);border-radius:8px;padding:8px 16px;font-size:13px;font-weight:700;cursor:pointer">
    + Uus taotlus
  </button>
</div>

<div id="vcp-maint-msg" style="display:none;margin-bottom:12px"></div>

<!-- Booking form (hidden by default) -->
<div id="vcp-maint-form-wrap" style="display:none;margin-bottom:20px">
  <div class="vcp-card" style="border:1px solid rgba(0,180,200,0.2)">
    <div style="font-size:14px;font-weight:700;color:#1e293b;margin-bottom:16px">Telli uus hooldus</div>
    <form id="vcp-maint-form" style="display:flex;flex-direction:column;gap:14px">
      <?php if (!empty($devices)): ?>
      <div>
        <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.06em">Seade</label>
        <select name="device_id" id="vcp-maint-device" class="vcp-input">
          <option value="">— Vali seade —</option>
          <?php foreach ($devices as $d): ?>
          <option value="<?php echo (int)$d->id; ?>"><?php echo esc_html($d->name); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div>
        <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.06em">Soovitud kuupäev *</label>
        <input type="date" name="preferred_date" required min="<?php echo $min_date; ?>" class="vcp-input">
      </div>
      <?php if (!empty($services)): ?>
      <div>
        <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.06em">Teenuse tüüp</label>
        <?php if ($campaign): ?>
        <div style="background:rgba(245,158,11,0.1);border:1px solid rgba(245,158,11,0.3);border-radius:8px;padding:8px 12px;margin-bottom:8px;font-size:12px">
          🎉 <strong><?php echo esc_html($campaign->name); ?></strong> — <?php echo (int)$campaign->maintenance_discount_percent; ?>% soodustust
        </div>
        <?php endif; ?>
        <select name="service_id" class="vcp-input">
          <option value="">— Vali teenus (valikuline) —</option>
          <?php foreach ($services as $svc): ?>
          <option value="<?php echo (int)$svc->id; ?>">
            <?php echo esc_html($svc->name); ?>
            <?php if ($svc->price): ?>
              <?php if ($campaign && $campaign->maintenance_discount_percent > 0): ?>
                — <?php echo number_format($svc->price * (1 - $campaign->maintenance_discount_percent / 100), 2, ',', ' '); ?> €
                <span style="text-decoration:line-through;color:#94a3b8"><?php echo number_format((float)$svc->price, 2, ',', ' '); ?> €</span>
              <?php else: ?>
                — <?php echo number_format((float)$svc->price, 2, ',', ' '); ?> €
              <?php endif; ?>
            <?php endif; ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div>
        <label style="font-size:12px;font-weight:600;color:#64748b;display:block;margin-bottom:6px;text-transform:uppercase;letter-spacing:0.06em">Kirjeldus / märkused</label>
        <textarea name="description" rows="3" class="vcp-input" placeholder="Kirjelda probleemi või soovi..."></textarea>
      </div>
      <div style="display:flex;gap:8px">
        <button type="submit" class="vcp-btn-primary" id="vcp-maint-submit">📤 Esita taotlus</button>
        <button type="button" id="vcp-maint-cancel-form" class="vcp-btn-outline">Tühista</button>
      </div>
    </form>
  </div>
</div>

<!-- Pending confirmations -->
<?php if (!empty($pending)): ?>
<div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.3);border-radius:12px;padding:14px 16px;margin-bottom:16px">
  <div style="font-size:12px;font-weight:700;color:#d97706;margin-bottom:10px;text-transform:uppercase;letter-spacing:0.06em">⏳ Ootab kinnitust</div>
  <?php foreach ($pending as $m): ?>
  <div id="maint-row-<?php echo $m->id; ?>" style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid rgba(245,158,11,0.15)">
    <div style="flex:1">
      <div style="font-weight:600;font-size:13px;color:#1e293b"><?php echo esc_html($m->device_name); ?><?php if ($m->device_model): ?> <span style="color:#64748b;font-size:12px">(<?php echo esc_html($m->device_model); ?>)</span><?php endif; ?></div>
      <div style="font-size:12px;color:#64748b"><?php echo $m->scheduled_date ? date('d.m.Y', strtotime($m->scheduled_date)) : '—'; ?></div>
    </div>
    <span style="font-size:11px;font-weight:700;color:#d97706;background:rgba(245,158,11,0.15);padding:2px 8px;border-radius:999px">Ootel</span>
    <button class="vcp-cancel-maint" data-id="<?php echo $m->id; ?>" data-device="<?php echo esc_attr($m->device_name); ?>" data-date="<?php echo esc_attr($m->scheduled_date ? date('d.m.Y', strtotime($m->scheduled_date)) : ''); ?>"
            style="background:none;border:1px solid rgba(239,68,68,0.3);border-radius:6px;padding:4px 10px;color:#dc2626;font-size:12px;cursor:pointer;font-weight:600">✕ Tühista</button>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Scheduled maintenances -->
<?php if (!empty($scheduled)): ?>
<div style="background:rgba(59,130,246,0.06);border:1px solid rgba(59,130,246,0.2);border-radius:12px;padding:14px 16px;margin-bottom:16px">
  <div style="font-size:12px;font-weight:700;color:#2563eb;margin-bottom:10px;text-transform:uppercase;letter-spacing:0.06em">📅 Planeeritud</div>
  <?php foreach ($scheduled as $m):
    $diff = $m->scheduled_date ? (int)ceil((strtotime($m->scheduled_date) - time()) / 86400) : null;
    $diff_label = $diff === null ? '' : ($diff === 0 ? 'Täna' : ($diff === 1 ? 'Homme' : $diff . ' p'));
    $diff_color = $diff === null ? '#64748b' : ($diff <= 1 ? '#dc2626' : ($diff <= 3 ? '#d97706' : '#2563eb'));
  ?>
  <div id="maint-row-<?php echo $m->id; ?>" style="display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid rgba(59,130,246,0.1)">
    <div style="flex:1">
      <div style="font-weight:600;font-size:13px;color:#1e293b"><?php echo esc_html($m->device_name); ?><?php if ($m->device_model): ?> <span style="color:#64748b;font-size:12px">(<?php echo esc_html($m->device_model); ?>)</span><?php endif; ?></div>
      <div style="font-size:12px;color:#64748b"><?php echo $m->scheduled_date ? date('d.m.Y', strtotime($m->scheduled_date)) : '—'; ?></div>
    </div>
    <?php if ($diff_label): ?>
    <span style="font-size:11px;font-weight:700;color:<?php echo $diff_color; ?>;background:<?php echo $diff_color; ?>20;padding:2px 8px;border-radius:999px"><?php echo $diff_label; ?></span>
    <?php endif; ?>
    <?php if ($m->worker_name): ?><span style="font-size:12px;color:#64748b">👷 <?php echo esc_html($m->worker_name); ?></span><?php endif; ?>
    <button class="vcp-cancel-maint" data-id="<?php echo $m->id; ?>" data-device="<?php echo esc_attr($m->device_name); ?>" data-date="<?php echo esc_attr($m->scheduled_date ? date('d.m.Y', strtotime($m->scheduled_date)) : ''); ?>"
            style="background:none;border:1px solid rgba(239,68,68,0.3);border-radius:6px;padding:4px 10px;color:#dc2626;font-size:12px;cursor:pointer;font-weight:600">✕ Tühista</button>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (empty($pending) && empty($scheduled)): ?>
<div style="font-size:13px;color:#94a3b8;margin-bottom:16px">Planeeritud hooldusi pole.</div>
<?php endif; ?>

<!-- Completed maintenances with photos -->
<?php if (!empty($completed)): ?>
<div style="margin-top:8px">
  <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:10px">📸 Tehtud tööde ajalugu</div>
  <div style="display:flex;flex-direction:column;gap:10px">
  <?php foreach ($completed as $m):
    $photos = $wpdb->get_results($wpdb->prepare(
        "SELECT filename, photo_type FROM {$wpdb->prefix}vesho_workorder_photos WHERE maintenance_id=%d ORDER BY created_at ASC",
        $m->id
    ));
    $photo_count = count($photos);
    $before_photos = array_filter($photos, fn($p) => ($p->photo_type ?? '') === 'before');
    $after_photos  = array_filter($photos, fn($p) => ($p->photo_type ?? '') !== 'before');
  ?>
  <div class="vcp-card" style="padding:0;overflow:hidden">
    <div onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display==='none'?'block':'none'"
         style="display:flex;align-items:center;gap:10px;padding:12px 16px;cursor:pointer;justify-content:space-between">
      <div>
        <div style="font-weight:600;font-size:13px;color:#1e293b"><?php echo esc_html($m->device_name); ?><?php if ($m->device_model): ?> <span style="color:#64748b;font-size:12px">(<?php echo esc_html($m->device_model); ?>)</span><?php endif; ?></div>
        <div style="font-size:12px;color:#64748b">✅ <?php echo $m->scheduled_date ? date('d.m.Y', strtotime($m->scheduled_date)) : '—'; ?><?php if ($m->worker_name): ?> · <?php echo esc_html($m->worker_name); ?><?php endif; ?></div>
      </div>
      <div style="display:flex;align-items:center;gap:8px">
        <?php if ($photo_count > 0): ?><span style="font-size:11px;color:#64748b">📷 <?php echo $photo_count; ?></span><?php endif; ?>
        <span style="color:#94a3b8;font-size:11px">▼</span>
      </div>
    </div>
    <div style="display:none;border-top:1px solid #f1f5f9;padding:14px 16px">
      <?php if (!empty($m->worker_notes)): ?>
      <div style="background:#f0f9ff;border-left:3px solid #00b4c8;padding:10px 12px;border-radius:0 6px 6px 0;font-size:13px;color:#0d1f2d;margin-bottom:12px;line-height:1.5">
        💬 <?php echo esc_html($m->worker_notes); ?>
      </div>
      <?php endif; ?>
      <?php if ($photo_count > 0): ?>
      <?php if (!empty($before_photos)): ?>
      <div style="font-size:11px;font-weight:600;color:#64748b;margin-bottom:6px">📷 Enne</div>
      <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:12px">
        <?php foreach ($before_photos as $p): ?>
        <a href="<?php echo esc_url($p->filename); ?>" target="_blank" style="display:block;width:72px;height:72px;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0">
          <img src="<?php echo esc_url($p->filename); ?>" style="width:100%;height:100%;object-fit:cover" alt="enne">
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($after_photos)): ?>
      <div style="font-size:11px;font-weight:600;color:#64748b;margin-bottom:6px">✅ Pärast</div>
      <div style="display:flex;flex-wrap:wrap;gap:6px">
        <?php foreach ($after_photos as $p): ?>
        <a href="<?php echo esc_url($p->filename); ?>" target="_blank" style="display:block;width:72px;height:72px;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0">
          <img src="<?php echo esc_url($p->filename); ?>" style="width:100%;height:100%;object-fit:cover" alt="pärast">
        </a>
        <?php endforeach; ?>
      </div>
      <?php elseif (empty($before_photos)): ?>
      <div style="display:flex;flex-wrap:wrap;gap:6px">
        <?php foreach ($photos as $p): ?>
        <a href="<?php echo esc_url($p->filename); ?>" target="_blank" style="display:block;width:72px;height:72px;border-radius:8px;overflow:hidden;border:1px solid #e2e8f0">
          <img src="<?php echo esc_url($p->filename); ?>" style="width:100%;height:100%;object-fit:cover" alt="foto">
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php elseif (!$m->worker_notes): ?>
      <div style="font-size:13px;color:#94a3b8">Täiendavat infot pole.</div>
      <?php endif; ?>
      <?php if ($m->description): ?>
      <div style="font-size:12px;color:#64748b;margin-top:8px"><?php echo esc_html($m->description); ?></div>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php if (empty($all)): ?>
<div class="vcp-empty">Hooldusi pole. Telli uus hooldus ülaloleva nupu abil.</div>
<?php endif; ?>

<script>
(function(){
  var AJAX='<?php echo esc_js($ajax); ?>', NONCE='<?php echo esc_js($nonce); ?>';
  var cancelId=null;

  // Form toggle
  var toggleBtn=document.getElementById('vcp-maint-form-toggle');
  var formWrap=document.getElementById('vcp-maint-form-wrap');
  if(toggleBtn&&formWrap){
    toggleBtn.addEventListener('click',function(){
      var open=formWrap.style.display!=='none';
      formWrap.style.display=open?'none':'block';
      toggleBtn.textContent=open?'+ Uus taotlus':'✕ Sulge';
    });
  }
  var cancelFormBtn=document.getElementById('vcp-maint-cancel-form');
  if(cancelFormBtn) cancelFormBtn.addEventListener('click',function(){
    formWrap.style.display='none';
    toggleBtn.textContent='+ Uus taotlus';
  });

  // Booking form submit
  var form=document.getElementById('vcp-maint-form');
  var msgEl=document.getElementById('vcp-maint-msg');
  if(form) form.addEventListener('submit',function(e){
    e.preventDefault();
    var btn=document.getElementById('vcp-maint-submit');
    btn.disabled=true;btn.textContent='⏳ Saadan...';
    var fd=new FormData(form);
    fd.append('action','vesho_client_book_service');
    fd.append('nonce',NONCE);
    fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(function(d){
      msgEl.style.display='block';
      msgEl.className='vcp-msg '+(d.success?'success':'error');
      msgEl.textContent=(d.data&&d.data.message)||(d.success?'Taotlus esitatud!':'Viga');
      if(d.success){
        form.reset();
        formWrap.style.display='none';
        toggleBtn.textContent='+ Uus taotlus';
        setTimeout(function(){location.reload();},1800);
      }
      btn.disabled=false;btn.textContent='📤 Esita taotlus';
    }).catch(function(){
      msgEl.style.display='block';msgEl.className='vcp-msg error';msgEl.textContent='Ühenduse viga';
      btn.disabled=false;btn.textContent='📤 Esita taotlus';
    });
  });

  // Cancel modal
  var modal=document.getElementById('vcp-cancel-modal');
  var backBtn=document.getElementById('vcp-cancel-back');
  var confirmBtn=document.getElementById('vcp-cancel-confirm');
  var reasonInp=document.getElementById('vcp-cancel-reason');
  var infoEl=document.getElementById('vcp-cancel-modal-info');

  function openCancelModal(id, device, date){
    cancelId=id;
    if(infoEl) infoEl.textContent=device+(date?' · '+date:'');
    if(reasonInp) reasonInp.value='';
    modal.style.display='flex';
  }
  if(backBtn) backBtn.addEventListener('click',function(){modal.style.display='none';});
  modal.addEventListener('click',function(e){if(e.target===modal)modal.style.display='none';});

  if(confirmBtn) confirmBtn.addEventListener('click',function(){
    if(!cancelId) return;
    confirmBtn.disabled=true;confirmBtn.textContent='⏳...';
    var fd=new FormData();
    fd.append('action','vesho_client_cancel_booking');
    fd.append('nonce',NONCE);
    fd.append('maintenance_id',cancelId);
    fd.append('reason',reasonInp?reasonInp.value:'');
    fetch(AJAX,{method:'POST',body:fd}).then(r=>r.json()).then(function(d){
      modal.style.display='none';
      msgEl.style.display='block';
      msgEl.className='vcp-msg '+(d.success?'success':'error');
      msgEl.textContent=(d.data&&d.data.message)||(d.success?'Tühistatud!':'Viga');
      if(d.success){
        var row=document.getElementById('maint-row-'+cancelId);
        if(row){row.style.opacity='.4';var btn=row.querySelector('.vcp-cancel-maint');if(btn)btn.remove();}
        setTimeout(function(){location.reload();},1500);
      }
      confirmBtn.disabled=false;confirmBtn.textContent='✕ Tühistan';
    }).catch(function(){confirmBtn.disabled=false;confirmBtn.textContent='✕ Tühistan';});
  });

  document.querySelectorAll('.vcp-cancel-maint').forEach(function(btn){
    btn.addEventListener('click',function(){
      openCancelModal(btn.dataset.id,btn.dataset.device||'',btn.dataset.date||'');
    });
  });
})();
</script>
        <?php
    }

    // ── Tab: Services (booking) ───────────────────────────────────────────────

    private static function tab_services($cid, $nonce, $ajax) {
        global $wpdb;
        $services = $wpdb->get_results(
            "SELECT id, name, description, price FROM {$wpdb->prefix}vesho_services WHERE active=1 ORDER BY name"
        );
        $devices = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name FROM {$wpdb->prefix}vesho_devices WHERE client_id=%d ORDER BY name", $cid
        ));
        ?>
<h2 class="vcp-section-title">Broneeri teenus</h2>
<div class="vcp-card">
  <div id="vcp-service-msg" class="vcp-msg" style="display:none"></div>
  <form id="vcp-service-form" style="display:flex;flex-direction:column;gap:14px">
    <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
    <div class="vcp-field">
      <label>Teenus *</label>
      <select name="service_id" required class="vcp-input">
        <option value="">— Vali teenus —</option>
        <?php foreach ($services as $svc): ?>
          <option value="<?php echo $svc->id; ?>">
            <?php echo esc_html($svc->name); ?>
            <?php if ($svc->price) echo ' — ' . number_format((float)$svc->price, 2, ',', ' ') . ' €'; ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if (!empty($devices)): ?>
    <div class="vcp-field">
      <label>Seade (valikuline)</label>
      <select name="device_id" class="vcp-input">
        <option value="">— Vali seade —</option>
        <?php foreach ($devices as $d): ?>
          <option value="<?php echo $d->id; ?>"><?php echo esc_html($d->name); ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php endif; ?>
    <div class="vcp-field">
      <label>Eelistatud kuupäev *</label>
      <input type="date" name="preferred_date" required class="vcp-input" min="<?php echo date('Y-m-d'); ?>">
    </div>
    <div class="vcp-field">
      <label>Kirjeldus / märkused</label>
      <textarea name="description" rows="3" class="vcp-input" placeholder="Kirjelda probleemi või soovi..."></textarea>
    </div>
    <button type="submit" class="vcp-btn-primary" style="align-self:flex-start">Esita taotlus</button>
  </form>
</div>
<script>
(function(){
  var form=document.getElementById('vcp-service-form');
  if(!form) return;
  form.addEventListener('submit',function(e){
    e.preventDefault();
    var msg=document.getElementById('vcp-service-msg');
    var fd=new FormData(form); fd.append('action','vesho_client_book_service');
    var btn=form.querySelector('button[type=submit]'); btn.disabled=true; btn.textContent='...';
    fetch('<?php echo $ajax; ?>',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
      msg.style.display='block';
      msg.className='vcp-msg '+(d.success?'success':'error');
      msg.textContent=(d.data&&d.data.message)||(d.success?'Taotlus esitatud!':'Viga');
      if(d.success){ form.reset(); }
      btn.disabled=false; btn.textContent='Esita taotlus';
    }).catch(function(){ msg.style.display='block'; msg.className='vcp-msg error'; msg.textContent='Ühenduse viga'; btn.disabled=false; btn.textContent='Esita taotlus'; });
  });
})();
</script>
        <?php
    }

    // ── Tab: Invoices ─────────────────────────────────────────────────────────

    private static function tab_invoices($cid) {
        global $wpdb;
        $show_vat = ($_GET['vat'] ?? '1') === '1';
        $invoices = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_invoices WHERE client_id=%d ORDER BY invoice_date DESC LIMIT 50", $cid
        ));
        ?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
  <h2 class="vcp-section-title" style="margin-bottom:0">Arved</h2>
  <div>
    <a href="?ptab=invoices&vat=1" style="padding:5px 12px;border-radius:6px 0 0 6px;border:1px solid #e2e8f0;font-size:12px;font-weight:600;text-decoration:none;<?php echo $show_vat ? 'background:#00b4c8;color:#fff;border-color:#00b4c8' : 'background:#fff;color:#64748b'; ?>">KM-ga</a><a href="?ptab=invoices&vat=0" style="padding:5px 12px;border-radius:0 6px 6px 0;border:1px solid #e2e8f0;border-left:none;font-size:12px;font-weight:600;text-decoration:none;<?php echo !$show_vat ? 'background:#00b4c8;color:#fff;border-color:#00b4c8' : 'background:#fff;color:#64748b'; ?>">KM-ta</a>
  </div>
</div>
<?php if (!empty($_GET['mc_paid']) || !empty($_GET['montonio_paid'])) : ?>
<div class="vcp-alert-success" style="background:#dcfce7;border:1px solid #bbf7d0;color:#16a34a;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-weight:600">
    ✅ Makse õnnestus! Arve on tasutud.
</div>
<?php elseif (!empty($_GET['mc_pending']) || !empty($_GET['montonio_pending'])) : ?>
<div class="vcp-alert-info" style="background:#dbeafe;border:1px solid #bfdbfe;color:#1d4ed8;padding:12px 16px;border-radius:10px;margin-bottom:16px">
    ℹ️ Makse töödeldakse. Arve uuendatakse automaatselt.
</div>
<?php endif; ?>
<?php if (empty($invoices)): ?>
  <div class="vcp-empty">Arveid pole.</div>
<?php else: ?>
  <div style="display:flex;flex-direction:column;gap:12px">
  <?php foreach ($invoices as $inv):
    $items = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vesho_invoice_items WHERE invoice_id=%d ORDER BY id ASC",
        $inv->id
    ));
    $overdue = $inv->due_date && $inv->due_date < date('Y-m-d') && !in_array($inv->status, ['paid','draft']);
  ?>
  <div class="vcp-card" style="padding:0;overflow:hidden">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 16px;cursor:pointer;gap:12px"
         onclick="this.nextElementSibling.style.display=this.nextElementSibling.style.display==='none'?'block':'none'">
      <div>
        <strong><?php echo esc_html($inv->invoice_number ?? '#'.$inv->id); ?></strong>
        <span style="color:#888;font-size:12px;margin-left:8px"><?php echo $inv->invoice_date ? date('d.m.Y', strtotime($inv->invoice_date)) : '—'; ?></span>
      </div>
      <div style="display:flex;align-items:center;gap:10px">
        <?php
        if (!$show_vat) {
            $net = $wpdb->get_var($wpdb->prepare("SELECT SUM(quantity*unit_price) FROM {$wpdb->prefix}vesho_invoice_items WHERE invoice_id=%d", $inv->id));
            $display_amount = $net ? (float)$net : (float)($inv->amount ?? 0);
        } else {
            $display_amount = (float)($inv->amount ?? 0);
        }
        ?>
        <strong style="font-size:15px<?php echo $inv->status==='paid'?';color:#22c55e':($overdue?';color:#ef4444':''); ?>"><?php echo number_format($display_amount, 2, ',', ' '); ?> €<?php if (!$show_vat): ?> <span style="font-size:11px;font-weight:400;color:#94a3b8">(KM-ta)</span><?php endif; ?></strong>
        <?php echo self::status_badge($overdue ? 'overdue' : $inv->status, 'invoice'); ?>
        <?php
        $print_url = add_query_arg([
            'cpaction'   => 'print_invoice',
            'invoice_id' => $inv->id,
            'nonce'      => wp_create_nonce('cp_print_' . $inv->id),
        ], get_permalink());
        ?>
        <a href="<?php echo esc_url($print_url); ?>" target="_blank"
           onclick="event.stopPropagation()"
           style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:#f0f9ff;border:1px solid #00b4c8;border-radius:6px;color:#00b4c8;font-size:12px;font-weight:600;text-decoration:none;white-space:nowrap">🖨️ PDF</a>
        <?php if (!in_array($inv->status, ['paid'])) : ?>
        <button onclick="event.stopPropagation();vwpOpenPayment(<?php echo $inv->id; ?>,<?php echo $inv->amount; ?>)"
                style="display:inline-flex;align-items:center;gap:4px;padding:4px 10px;background:#f59e0b;border:none;border-radius:6px;color:#fff;font-size:12px;font-weight:600;cursor:pointer;white-space:nowrap">
            💳 Maksa
        </button>
        <?php endif; ?>
        <span style="color:#aaa;font-size:11px">▼</span>
      </div>
    </div>
    <div style="display:none;border-top:1px solid #f0f0f0;padding:16px">
      <?php if ($inv->due_date): ?>
      <div style="font-size:12px;color:<?php echo $overdue?'#ef4444':'#888'; ?>;margin-bottom:12px">
        Tähtaeg: <?php echo date('d.m.Y', strtotime($inv->due_date)); ?>
        <?php if ($overdue) echo ' <strong>— TÄHTAEG ÜLETATUD</strong>'; ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($items)): ?>
      <table class="vcp-table" style="font-size:13px">
        <thead><tr><th>Kirjeldus</th><th style="text-align:right">Kogus</th><th style="text-align:right">Hind</th><th style="text-align:right">KM%</th><th style="text-align:right">Kokku</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
          <td><?php echo esc_html($item->description); ?></td>
          <td style="text-align:right"><?php echo number_format($item->quantity, 2, ',', ''); ?></td>
          <td style="text-align:right"><?php echo number_format($item->unit_price, 2, ',', ' '); ?> €</td>
          <td style="text-align:right"><?php echo number_format($item->vat_rate, 0); ?>%</td>
          <td style="text-align:right"><strong><?php echo number_format($item->total, 2, ',', ' '); ?> €</strong></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr><td colspan="4" style="text-align:right;font-weight:700">Kokku:</td>
          <td style="text-align:right;font-weight:700"><?php echo number_format((float)$inv->amount, 2, ',', ' '); ?> €</td></tr>
        </tfoot>
      </table>
      <?php elseif ($inv->description): ?>
      <p style="font-size:13px;color:#555"><?php echo esc_html($inv->description); ?></p>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php
    // ── Credit notes ───────────────────────────────────────────────────────────
    $credit_notes = $wpdb->get_results($wpdb->prepare(
        "SELECT cn.*, i.invoice_number FROM {$wpdb->prefix}vesho_credit_notes cn
         LEFT JOIN {$wpdb->prefix}vesho_invoices i ON i.id=cn.invoice_id
         WHERE cn.client_id=%d ORDER BY cn.issued_date DESC",
        $cid
    ));
    if (!empty($credit_notes)) :
    ?>
<h4 style="margin-top:24px;font-size:15px;font-weight:700;color:#0d1f2d">Kreeditarved</h4>
<div class="vcp-card" style="overflow:hidden;padding:0">
<table class="vcp-table" style="font-size:13px">
  <thead><tr><th>Number</th><th>Seotud arve</th><th>Summa</th><th>Põhjus</th><th>Kuupäev</th></tr></thead>
  <tbody>
  <?php foreach ($credit_notes as $cn) : ?>
  <tr>
    <td data-label="Number"><strong><?php echo esc_html($cn->credit_note_number); ?></strong></td>
    <td data-label="Arve"><?php echo esc_html($cn->invoice_number ?? '—'); ?></td>
    <td data-label="Summa"><strong><?php echo number_format((float)$cn->amount, 2, ',', ' '); ?> €</strong></td>
    <td data-label="Põhjus"><?php echo esc_html($cn->reason ?: '—'); ?></td>
    <td data-label="Kuupäev"><?php echo $cn->issued_date ? date('d.m.Y', strtotime($cn->issued_date)) : '—'; ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php endif; ?>
<?php self::render_payment_modal(); ?>
        <?php
    }

    // ── Shared: Payment modal + JS ────────────────────────────────────────────
    private static function render_payment_modal() {
        static $rendered = false;
        if ( $rendered ) return; // only once per page
        $rendered = true;
        ?>
<div id="vcp-pay-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;align-items:center;justify-content:center">
  <div style="background:#fff;border-radius:16px;padding:28px;width:90%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,.2)">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
      <h3 style="margin:0;font-size:18px;font-weight:700">💳 Maksa arve</h3>
      <button onclick="document.getElementById('vcp-pay-modal').style.display='none'" style="background:none;border:none;font-size:20px;cursor:pointer;color:#888">✕</button>
    </div>
    <div id="vcp-pay-amount" style="font-size:28px;font-weight:800;color:#1e293b;margin-bottom:20px;text-align:center"></div>
    <div id="vcp-pay-methods" style="display:flex;flex-direction:column;gap:10px"></div>
    <div id="vcp-pay-stripe-form" style="display:none;margin-top:16px">
      <div id="vcp-stripe-element" style="border:1px solid #e2e8f0;border-radius:8px;padding:12px;min-height:44px"></div>
      <button onclick="vwpSubmitStripe()" style="width:100%;margin-top:12px;padding:12px;background:#f59e0b;color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer">
        Maksa Stripe&apos;iga
      </button>
    </div>
    <div id="vcp-pay-msg" style="margin-top:12px;font-size:13px;text-align:center;color:#888"></div>
  </div>
</div>
<script>
(function(){
var _invId=0,_stripe=null,_cardEl=null;
var nonce='<?php echo wp_create_nonce('vesho_portal_nonce'); ?>';
var ajaxUrl='<?php echo admin_url('admin-ajax.php'); ?>';

window.vwpOpenPayment=function(id,amount){
    _invId=id;
    document.getElementById('vcp-pay-amount').textContent=parseFloat(amount).toFixed(2).replace('.',',')+' €';
    document.getElementById('vcp-pay-msg').textContent='';
    document.getElementById('vcp-pay-stripe-form').style.display='none';
    document.getElementById('vcp-pay-modal').style.display='flex';
    fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=vesho_get_payment_config&nonce='+nonce
    }).then(r=>r.json()).then(d=>{
        if(!d.success)return;
        var cfg=d.data,methods=document.getElementById('vcp-pay-methods');
        methods.innerHTML='';
        if(cfg.mc_enabled){var b=document.createElement('button');b.textContent='🏦 Panka / kaardiga (Maksekeskus)';b.style='width:100%;padding:13px;background:#fff;border:2px solid #00b4c8;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;color:#00b4c8;margin-bottom:6px';b.onclick=function(){vwpShowBankPicker()};methods.appendChild(b);}
        if(cfg.montonio_enabled){var b2=document.createElement('button');b2.textContent='🏦 Pangalink / järelmaks (Montonio)';b2.style='width:100%;padding:13px;background:#fff;border:2px solid #6366f1;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;color:#6366f1;margin-bottom:6px';b2.onclick=function(){vwpPayMontonio()};methods.appendChild(b2);}
        if(cfg.stripe_enabled&&cfg.stripe_pub_key){var b3=document.createElement('button');b3.textContent='💳 Kaardiga (Stripe)';b3.style='width:100%;padding:13px;background:#fff;border:2px solid #635bff;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;color:#635bff';b3.onclick=function(){vwpInitStripe(cfg.stripe_pub_key)};methods.appendChild(b3);}
        if(!cfg.mc_enabled&&!cfg.montonio_enabled&&!cfg.stripe_enabled)methods.innerHTML='<p style="text-align:center;color:#888;font-size:13px">Makselahendus pole seadistatud.</p>';
    });
};
var MC_BANKS=[
  {id:'swedbank',name:'Swedbank',color:'#f37229'},
  {id:'seb',name:'SEB',color:'#007b40'},
  {id:'lhv',name:'LHV',color:'#e8b100'},
  {id:'luminor',name:'Luminor',color:'#ff523f'},
  {id:'coop',name:'Coop Pank',color:'#00b050'},
  {id:'bigbank',name:'Bigbank',color:'#ee3124'},
  {id:'inbank',name:'Inbank',color:'#6b21a8'},
  {id:'revolut',name:'Revolut',color:'#1a1b25'},
];
function vwpShowBankPicker(){
  var existing=document.getElementById('vcp-bank-picker-overlay');
  if(existing) existing.remove();
  var overlay=document.createElement('div');
  overlay.id='vcp-bank-picker-overlay';
  overlay.style='position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:10002;display:flex;align-items:center;justify-content:center;padding:16px';
  var box=document.createElement('div');
  box.style='background:#fff;border-radius:16px;padding:24px;max-width:380px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,0.25)';
  box.innerHTML='<div style="font-size:16px;font-weight:700;color:#1e293b;margin-bottom:4px">Vali pank</div><div style="font-size:12px;color:#94a3b8;margin-bottom:18px">Suunatakse valitud panga lehele</div>';
  var grid=document.createElement('div');
  grid.style='display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:14px';
  MC_BANKS.forEach(function(bank){
    var btn=document.createElement('button');
    btn.textContent=bank.name;
    btn.style='padding:12px;border-radius:10px;border:2px solid '+bank.color+';background:#fff;color:'+bank.color+';font-size:13px;font-weight:700;cursor:pointer;transition:background 0.15s';
    btn.onmouseenter=function(){btn.style.background=bank.color+'18'};
    btn.onmouseleave=function(){btn.style.background='#fff'};
    btn.onclick=function(){overlay.remove();vwpPayMC(bank.id);};
    grid.appendChild(btn);
  });
  box.appendChild(grid);
  var skipBtn=document.createElement('button');
  skipBtn.textContent='Jätka ilma pangavalikuta';
  skipBtn.style='width:100%;padding:10px;border-radius:10px;border:1px solid #e2e8f0;background:#f8fafc;color:#64748b;font-size:13px;cursor:pointer;margin-bottom:8px';
  skipBtn.onclick=function(){overlay.remove();vwpPayMC('');};
  var cancelBtn=document.createElement('button');
  cancelBtn.textContent='Tühista';
  cancelBtn.style='width:100%;padding:8px;border:none;background:transparent;color:#94a3b8;font-size:13px;cursor:pointer';
  cancelBtn.onclick=function(){overlay.remove();};
  box.appendChild(skipBtn);box.appendChild(cancelBtn);
  overlay.appendChild(box);
  overlay.onclick=function(e){if(e.target===overlay)overlay.remove();};
  document.body.appendChild(overlay);
}
function vwpPayMC(bankId){setMsg('Suunamine Maksekeskusesse...');var body='action=vesho_pay_invoice_mc&nonce='+nonce+'&invoice_id='+_invId;if(bankId)body+='&bank_id='+encodeURIComponent(bankId);fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body}).then(r=>r.json()).then(d=>{if(d.success&&d.data.redirect_url)window.location.href=d.data.redirect_url;else setMsg('❌ '+(d.data&&d.data.message?d.data.message:'Viga'));});}
function vwpPayMontonio(){setMsg('Suunamine Montoniole...');fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=vesho_pay_invoice_montonio&nonce='+nonce+'&invoice_id='+_invId}).then(r=>r.json()).then(d=>{if(d.success&&d.data.redirect_url)window.location.href=d.data.redirect_url;else setMsg('❌ '+(d.data&&d.data.message?d.data.message:'Viga'));});}
function vwpInitStripe(pubKey){document.getElementById('vcp-pay-stripe-form').style.display='block';if(_stripe)return;if(!window.Stripe){var s=document.createElement('script');s.src='https://js.stripe.com/v3/';s.onload=function(){vwpInitStripe(pubKey)};document.head.appendChild(s);return;}_stripe=Stripe(pubKey);var el=_stripe.elements();_cardEl=el.create('card',{style:{base:{fontSize:'16px',color:'#1e293b'}}});_cardEl.mount('#vcp-stripe-element');}
window.vwpSubmitStripe=function(){setMsg('Töötleme makset...');fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=vesho_pay_invoice_stripe&nonce='+nonce+'&invoice_id='+_invId}).then(r=>r.json()).then(d=>{if(!d.success){setMsg('❌ '+(d.data&&d.data.message?d.data.message:'Viga'));return;}_stripe.confirmCardPayment(d.data.client_secret,{payment_method:{card:_cardEl}}).then(function(result){if(result.error)setMsg('❌ '+result.error.message);else fetch(ajaxUrl,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=vesho_confirm_stripe_payment&nonce='+nonce+'&invoice_id='+_invId}).then(r=>r.json()).then(d2=>{if(d2.success&&d2.data.message){setMsg('✅ '+d2.data.message);setTimeout(function(){location.reload()},2000);}});});});};
function setMsg(m){document.getElementById('vcp-pay-msg').textContent=m;}
})();
</script>
        <?php
    }

    // ── Tab: Orders ───────────────────────────────────────────────────────────

    private static function tab_orders($client, $cid) {
        global $wpdb;
        $email = $client->email ?? '';
        $nonce = wp_create_nonce('vesho_portal_nonce');
        $ajax  = esc_url(admin_url('admin-ajax.php'));

        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_shop_orders
             WHERE client_id=%d OR guest_email=%s
             ORDER BY created_at DESC LIMIT 100",
            $cid, $email
        ));

        $delivery_label = [
            'pickup'  => '🏪 Kaupluses',
            'courier' => '🚚 Kuller',
            'parcel'  => '📦 Pakiautomaat',
        ];
        $status_map = [
            'new'              => ['Uus',              '#fee2e2', '#dc2626'],
            'pending_payment'  => ['Makse ootel',      '#fef9c3', '#b45309'],
            'picking'          => ['Komplekteerimisel', '#fef9c3', '#b45309'],
            'ready'            => ['Valmis',            '#dbeafe', '#1d4ed8'],
            'shipped'          => ['Saadetud',          '#ede9fe', '#7c3aed'],
            'fulfilled'        => ['Täidetud',          '#dcfce7', '#16a34a'],
            'cancelled'        => ['Tühistatud',        '#f3f4f6', '#6b7280'],
            'returned'         => ['Tagastatud',        '#f3f4f6', '#6b7280'],
            'return_requested' => ['Tagastus ootel',    '#fef9c3', '#b45309'],
        ];
        ?>
<div class="vcp-page-header"><h1>Tellimused</h1></div>
<div id="vcp-order-msg" class="vcp-msg" style="display:none;margin-bottom:12px"></div>
<?php if (empty($orders)): ?>
  <div class="vcp-empty">Teil ei ole ühtegi tellimust.</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:10px">
<?php foreach ($orders as $o):
    $status_info = $status_map[$o->status] ?? [$o->status, '#f3f4f6', '#6b7280'];
    $items = $wpdb->get_results($wpdb->prepare(
        "SELECT name, quantity, shop_price, unit_price FROM {$wpdb->prefix}vesho_shop_order_items WHERE order_id=%d", $o->id
    ));
    $items_summary = implode(', ', array_map(fn($i) => esc_html($i->name) . ' ×' . (int)$i->quantity, $items));
    $is_return   = $o->status === 'returned';
    $total_color = $is_return ? '#dc2626' : 'inherit';
    $total_str   = ($is_return ? '−' : '') . number_format(abs((float)$o->total), 2, ',', ' ') . ' €';
    $days_old    = $o->created_at ? (time() - strtotime($o->created_at)) / 86400 : 99;
    $return_eligible  = in_array($o->status, ['fulfilled', 'shipped'], true) && $days_old <= 14;
    $cancel_eligible  = in_array($o->status, ['new', 'pending_payment'], true);
    $dl_label = $delivery_label[$o->delivery_method ?? ''] ?? esc_html($o->delivery_method ?? '—');
    $card_id  = 'vcp-order-card-' . (int)$o->id;
?>
<div class="vcp-card" style="padding:0;overflow:hidden">
  <!-- Header row — always visible, clickable to expand -->
  <div class="vcp-order-header" data-card="<?php echo $card_id; ?>"
       style="display:flex;align-items:center;gap:12px;padding:14px 18px;cursor:pointer;user-select:none">
    <div style="flex:1;min-width:0">
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <strong style="font-size:14px"><?php echo esc_html($o->order_number); ?></strong>
        <span style="display:inline-block;padding:2px 10px;border-radius:999px;font-size:11px;font-weight:600;background:<?php echo esc_attr($status_info[1]); ?>;color:<?php echo esc_attr($status_info[2]); ?>"><?php echo esc_html($status_info[0]); ?></span>
      </div>
      <div style="font-size:12px;color:#6b7280;margin-top:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo $items_summary ?: '—'; ?></div>
    </div>
    <div style="text-align:right;flex-shrink:0">
      <div style="font-weight:700;color:<?php echo $total_color; ?>"><?php echo $total_str; ?></div>
      <div style="font-size:11px;color:#94a3b8;margin-top:2px"><?php echo esc_html($o->created_at ? date('d.m.Y', strtotime($o->created_at)) : '—'); ?></div>
    </div>
    <div class="vcp-order-chevron" style="font-size:12px;color:#94a3b8;transition:transform 0.2s;flex-shrink:0">▼</div>
  </div>

  <!-- Expanded body -->
  <div class="vcp-order-body" id="<?php echo $card_id; ?>" style="display:none;border-top:1px solid #e5e7eb;padding:16px 18px">

    <!-- Items list -->
    <?php if ($items): ?>
    <div style="margin-bottom:14px">
      <div style="font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px">Tooted</div>
      <div style="display:flex;flex-direction:column;gap:4px">
        <?php foreach ($items as $it):
            $price = (float)($it->shop_price ?: $it->unit_price);
            $line_total = $price * (int)$it->quantity;
        ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 10px;background:#f9fafb;border-radius:8px;font-size:13px">
          <span><?php echo esc_html($it->name); ?> <span style="color:#94a3b8">×<?php echo (int)$it->quantity; ?></span></span>
          <span style="font-weight:600;white-space:nowrap"><?php echo number_format($line_total, 2, ',', ' '); ?> €</span>
        </div>
        <?php endforeach; ?>
        <div style="display:flex;justify-content:space-between;padding:6px 10px;font-size:13px;font-weight:700;border-top:1px solid #e5e7eb;margin-top:4px">
          <span>Kokku</span>
          <span style="color:<?php echo $total_color; ?>"><?php echo $total_str; ?></span>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Delivery + tracking -->
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px">
      <div style="padding:10px 12px;background:#f9fafb;border-radius:8px">
        <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:3px">Tarne</div>
        <div style="font-size:13px;font-weight:600"><?php echo $dl_label; ?></div>
        <?php if ($o->delivery_address): ?>
        <div style="font-size:12px;color:#6b7280;margin-top:2px;line-height:1.4"><?php echo esc_html($o->delivery_address); ?></div>
        <?php endif; ?>
      </div>
      <div style="padding:10px 12px;background:#f9fafb;border-radius:8px">
        <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:3px">Jälgimine</div>
        <?php if ($o->tracking_number): ?>
        <a href="https://www.omniva.ee/private/track?barcode=<?php echo esc_attr($o->tracking_number); ?>"
           target="_blank" rel="noopener noreferrer"
           style="font-size:12px;font-family:monospace;color:#2563eb"><?php echo esc_html($o->tracking_number); ?> ↗</a>
        <?php else: ?>
        <div style="font-size:13px;color:#94a3b8">—</div>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($o->notes): ?>
    <div style="margin-bottom:14px;padding:8px 12px;background:#fffbeb;border-radius:8px;border-left:3px solid #f59e0b;font-size:12px;color:#555">📋 <?php echo esc_html($o->notes); ?></div>
    <?php endif; ?>

    <!-- Actions -->
    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <?php if ($cancel_eligible): ?>
      <button type="button" class="vcp-order-cancel-btn"
              style="font-size:12px;padding:6px 14px;border-radius:8px;border:1px solid rgba(239,68,68,0.3);background:rgba(239,68,68,0.07);color:#dc2626;cursor:pointer;font-weight:600"
              data-order-id="<?php echo (int)$o->id; ?>"
              data-order-nr="<?php echo esc_attr($o->order_number); ?>"
              data-nonce="<?php echo esc_attr($nonce); ?>">
        ✕ Tühista tellimus
      </button>
      <?php endif; ?>

      <?php if ($return_eligible): ?>
      <button type="button" class="vcp-return-toggle-btn"
              style="font-size:12px;padding:6px 14px;border-radius:8px;border:1px solid #e5e7eb;background:#f9fafb;cursor:pointer;font-weight:600"
              data-order-id="<?php echo (int)$o->id; ?>">
        ↩️ Taotle tagastust
      </button>
      <?php endif; ?>
    </div>

    <!-- Return form -->
    <?php if ($return_eligible): ?>
    <div class="vcp-return-form-wrap" id="vcp-return-form-<?php echo (int)$o->id; ?>"
         style="display:none;margin-top:14px;padding:14px;background:#fefce8;border-radius:10px;border:1px solid #fde047">
      <div style="font-size:13px;font-weight:600;margin-bottom:10px">Tagastustaotlus — <?php echo esc_html($o->order_number); ?></div>
      <div style="display:flex;flex-direction:column;gap:10px">
        <div>
          <label style="display:block;font-size:12px;font-weight:500;margin-bottom:4px;color:#374151">Tagastuse põhjus *</label>
          <select class="vcp-return-reason-select vcp-input" data-order-id="<?php echo (int)$o->id; ?>" style="font-size:13px;width:100%">
            <option value="">— Vali põhjus —</option>
            <option value="Toode ei vasta kirjeldusele">Toode ei vasta kirjeldusele</option>
            <option value="Defektne toode">Defektne toode</option>
            <option value="Muutsin meelt">Muutsin meelt</option>
            <option value="Vale toode saadetud">Vale toode saadetud</option>
            <option value="Muu">Muu</option>
          </select>
        </div>
        <div>
          <label style="display:block;font-size:12px;font-weight:500;margin-bottom:4px;color:#374151">Kirjelda lähemalt</label>
          <textarea class="vcp-return-description vcp-input" data-order-id="<?php echo (int)$o->id; ?>" rows="3" style="width:100%;font-size:13px" placeholder="Kirjelda lähemalt..."></textarea>
        </div>
        <div>
          <label style="display:block;font-size:12px;font-weight:500;margin-bottom:4px;color:#374151">Lisa foto (valikuline)</label>
          <input type="file" class="vcp-return-photo" data-order-id="<?php echo (int)$o->id; ?>" accept="image/*" style="font-size:13px">
        </div>
        <div style="display:flex;gap:8px">
          <button type="button" class="vcp-btn-primary vcp-return-submit" style="font-size:13px"
                  data-order-id="<?php echo (int)$o->id; ?>" data-nonce="<?php echo esc_attr($nonce); ?>">Esita taotlus</button>
          <button type="button" class="vcp-return-toggle-btn vcp-btn-outline" style="font-size:13px"
                  data-order-id="<?php echo (int)$o->id; ?>">Tühista</button>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /.vcp-order-body -->
</div><!-- /.vcp-card -->
<?php endforeach; ?>
</div><!-- orders list -->

<script>
(function(){
  var AJAX='<?php echo $ajax; ?>';
  var msgEl = document.getElementById('vcp-order-msg');
  function showMsg(txt, ok) {
    if (!msgEl) return;
    msgEl.style.display='block';
    msgEl.className='vcp-msg '+(ok?'success':'error');
    msgEl.textContent=txt;
    setTimeout(function(){ msgEl.style.display='none'; }, 4000);
  }

  // Expand/collapse cards
  document.querySelectorAll('.vcp-order-header').forEach(function(hdr){
    hdr.addEventListener('click', function(){
      var id = hdr.dataset.card;
      var body = document.getElementById(id);
      var ch = hdr.querySelector('.vcp-order-chevron');
      if (!body) return;
      var open = body.style.display !== 'none';
      body.style.display = open ? 'none' : 'block';
      if (ch) ch.style.transform = open ? '' : 'rotate(180deg)';
    });
  });

  // Cancel order
  document.querySelectorAll('.vcp-order-cancel-btn').forEach(function(btn){
    btn.addEventListener('click', function(e){
      e.stopPropagation();
      if (!confirm('Oled kindel, et soovid tellimuse ' + btn.dataset.orderNr + ' tühistada?')) return;
      btn.disabled=true; btn.textContent='...';
      var fd=new FormData();
      fd.append('action','vesho_client_cancel_order');
      fd.append('nonce',btn.dataset.nonce);
      fd.append('order_id',btn.dataset.orderId);
      fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        showMsg((d.data&&d.data.message)||(d.success?'Tühistatud!':'Viga'), d.success);
        if (d.success) setTimeout(function(){ window.location.reload(); }, 1000);
        else { btn.disabled=false; btn.textContent='✕ Tühista tellimus'; }
      }).catch(function(){ btn.disabled=false; btn.textContent='✕ Tühista tellimus'; showMsg('Ühenduse viga',false); });
    });
  });

  // Toggle return form
  document.querySelectorAll('.vcp-return-toggle-btn').forEach(function(btn){
    btn.addEventListener('click', function(e){
      e.stopPropagation();
      var wrap = document.getElementById('vcp-return-form-'+btn.dataset.orderId);
      if (wrap) wrap.style.display = wrap.style.display==='none' ? 'block' : 'none';
    });
  });

  // Submit return request
  document.querySelectorAll('.vcp-return-submit').forEach(function(btn){
    btn.addEventListener('click', function(e){
      e.stopPropagation();
      var oid=btn.dataset.orderId, nonce=btn.dataset.nonce;
      var reasonSel = document.querySelector('.vcp-return-reason-select[data-order-id="'+oid+'"]');
      var descTa    = document.querySelector('.vcp-return-description[data-order-id="'+oid+'"]');
      var photoInp  = document.querySelector('.vcp-return-photo[data-order-id="'+oid+'"]');
      var reason    = reasonSel ? reasonSel.value.trim() : '';
      if (!reason) { alert('Palun vali tagastuse põhjus'); return; }
      btn.disabled=true; btn.textContent='...';
      var fd=new FormData();
      fd.append('action','vesho_client_return_request');
      fd.append('nonce',nonce);
      fd.append('order_id',oid);
      fd.append('reason',reason);
      fd.append('description',descTa?descTa.value.trim():'');
      if (photoInp&&photoInp.files&&photoInp.files[0]) fd.append('return_photo',photoInp.files[0]);
      fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        showMsg((d.data&&d.data.message)||(d.success?'Esitatud!':'Viga'), d.success);
        if (d.success) setTimeout(function(){ window.location.reload(); }, 1200);
        else { btn.disabled=false; btn.textContent='Esita taotlus'; }
      }).catch(function(){ btn.disabled=false; btn.textContent='Esita taotlus'; showMsg('Ühenduse viga',false); });
    });
  });
})();
</script>
<?php endif; ?>
        <?php
    }

    // ── Tab: Support ──────────────────────────────────────────────────────────

    private static function tab_support($cid, $nonce, $ajax) {
        global $wpdb;
        $tickets = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_support_tickets WHERE client_id=%d ORDER BY created_at DESC LIMIT 30", $cid
        ));
        // Load reply threads for all tickets
        $ticket_ids = array_column((array)$tickets, 'id');
        $replies_by_ticket = [];
        if (!empty($ticket_ids)) {
            $placeholders = implode(',', array_fill(0, count($ticket_ids), '%d'));
            $all_replies  = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vesho_ticket_replies WHERE ticket_id IN ($placeholders) ORDER BY created_at ASC",
                ...$ticket_ids
            ));
            foreach ($all_replies as $rep) {
                $replies_by_ticket[$rep->ticket_id][] = $rep;
            }
        }
        ?>
<h2 class="vcp-section-title">Tugipiletid</h2>
<div class="vcp-card" style="margin-bottom:24px">
  <h3 style="margin:0 0 16px;font-size:15px;font-weight:600">Esita uus pilet</h3>
  <div id="vcp-ticket-msg" class="vcp-msg" style="display:none"></div>
  <form id="vcp-ticket-form" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:12px">
    <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
    <div class="vcp-field">
      <label>Teema *</label>
      <input type="text" name="subject" required class="vcp-input" placeholder="Kirjelda probleemi lühidalt">
    </div>
    <div class="vcp-field">
      <label>Sõnum *</label>
      <textarea name="message" required rows="4" class="vcp-input" placeholder="Kirjelda probleemi detailsemalt..."></textarea>
    </div>
    <div style="margin-bottom:4px">
      <label style="display:block;margin-bottom:4px;font-size:13px;font-weight:500;color:#374151">Lisa fail (foto, PDF) — valikuline</label>
      <input type="file" name="ticket_attachment" accept="image/*,.pdf" style="margin-top:4px;font-size:13px">
    </div>
    <button type="submit" class="vcp-btn-primary" style="align-self:flex-start">Saada pilet</button>
  </form>
</div>

<?php if (!empty($tickets)): ?>
  <div style="display:flex;flex-direction:column;gap:16px">
  <?php foreach ($tickets as $t):
    $border_color = $t->status==='closed' ? '#22c55e' : ($t->status==='in_progress' ? '#3b82f6' : '#f59e0b');
    $thread = $replies_by_ticket[$t->id] ?? [];
    $has_thread = !empty($thread);
    $card_id = 'ticket-card-'.$t->id;
  ?>
  <div class="vcp-card" id="<?php echo $card_id; ?>" style="padding:0;border-left:4px solid <?php echo $border_color; ?>;overflow:hidden">

    <!-- Pileti päis — klikitav avamiseks -->
    <div onclick="vshToggleTicket(<?php echo $t->id; ?>)"
         style="display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px;cursor:pointer;user-select:none">
      <div style="flex:1;min-width:0">
        <div style="font-weight:600;font-size:14px;color:#1a2a38"><?php echo esc_html($t->subject); ?></div>
        <div style="font-size:12px;color:#888;margin-top:2px">
          #<?php echo $t->id; ?> &middot; <?php echo $t->created_at ? date('d.m.Y H:i', strtotime($t->created_at)) : '—'; ?>
          <?php if ($has_thread): ?>&middot; <span style="color:#00b4c8"><?php echo count($thread); ?> vastus<?php echo count($thread)>1?'t':''; ?></span><?php endif; ?>
        </div>
      </div>
      <div style="display:flex;align-items:center;gap:8px;flex-shrink:0">
        <?php echo self::status_badge($t->status ?? 'open', 'ticket'); ?>
        <span id="vsh-arrow-<?php echo $t->id; ?>" style="font-size:12px;color:#6b8599;transition:transform .2s">▼</span>
      </div>
    </div>

    <!-- Sisu — peidetud vaikimisi -->
    <div id="vsh-body-<?php echo $t->id; ?>" style="display:none;border-top:1px solid #f0f4f7">

      <!-- Sõnumite ahel -->
      <div style="padding:16px;display:flex;flex-direction:column;gap:10px;max-height:420px;overflow-y:auto;scroll-behavior:smooth" id="vsh-thread-<?php echo $t->id; ?>">

        <!-- Algne sõnum kliendilt -->
        <div style="display:flex;gap:10px;align-items:flex-start">
          <div style="width:28px;height:28px;border-radius:50%;background:#e0f2fe;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0">👤</div>
          <div style="flex:1">
            <div style="font-size:11px;color:#888;margin-bottom:4px">
              <strong style="color:#374151">Sina</strong> &middot; <?php echo $t->created_at ? date('d.m.Y H:i', strtotime($t->created_at)) : '—'; ?>
            </div>
            <div style="background:#f4f7f9;border-radius:0 8px 8px 8px;padding:10px 14px;font-size:13px;line-height:1.6;white-space:pre-wrap"><?php echo esc_html($t->message ?? ''); ?></div>
            <?php if (!empty($t->attachment_url)): ?>
            <div style="margin-top:6px">
              <?php $ext = strtolower(pathinfo($t->attachment_url, PATHINFO_EXTENSION));
                    $is_image = in_array($ext, ['jpg','jpeg','png','gif','webp']); ?>
              <?php if ($is_image): ?>
                <a href="<?php echo esc_url($t->attachment_url); ?>" target="_blank" rel="noopener">
                  <img src="<?php echo esc_url($t->attachment_url); ?>" style="max-width:200px;max-height:120px;border-radius:6px;border:1px solid #e2e8f0;object-fit:cover" alt="manus">
                </a>
              <?php else: ?>
                <a href="<?php echo esc_url($t->attachment_url); ?>" target="_blank" rel="noopener" style="font-size:12px;color:#00b4c8">📎 Vaata manust</a>
              <?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($has_thread): ?>
          <?php foreach ($thread as $rep): ?>
          <div style="display:flex;gap:10px;align-items:flex-start;flex-direction:row-reverse">
            <div style="width:28px;height:28px;border-radius:50%;background:#d1fae5;display:flex;align-items:center;justify-content:middle;font-size:12px;flex-shrink:0;align-items:center;justify-content:center">🛠</div>
            <div style="flex:1">
              <div style="font-size:11px;color:#888;margin-bottom:4px;text-align:right">
                <strong style="color:#374151">Vesho tugi</strong> &middot; <?php echo $rep->created_at ? date('d.m.Y H:i', strtotime($rep->created_at)) : '—'; ?>
              </div>
              <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px 0 8px 8px;padding:10px 14px;font-size:13px;line-height:1.6;white-space:pre-wrap;text-align:left"><?php echo esc_html($rep->message); ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php elseif (!empty($t->reply)): ?>
          <!-- Legacy single reply -->
          <div style="display:flex;gap:10px;align-items:flex-start;flex-direction:row-reverse">
            <div style="width:28px;height:28px;border-radius:50%;background:#d1fae5;display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0">🛠</div>
            <div style="flex:1">
              <div style="font-size:11px;color:#888;margin-bottom:4px;text-align:right"><strong style="color:#374151">Vesho tugi</strong></div>
              <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px 0 8px 8px;padding:10px 14px;font-size:13px;line-height:1.6;white-space:pre-wrap;text-align:left"><?php echo esc_html($t->reply); ?></div>
            </div>
          </div>
        <?php endif; ?>

      </div><!-- /sõnumid -->

      <?php if ($t->status !== 'closed'): ?>
      <!-- Vastuse vorm — threadi all, eraldi ribana -->
      <div style="border-top:1px solid #f0f4f7;padding:12px 16px;background:#fafbfc">
        <div id="vsh-reply-msg-<?php echo $t->id; ?>" class="vcp-msg" style="display:none;margin-bottom:8px"></div>
        <form id="vsh-reply-form-<?php echo $t->id; ?>" style="display:flex;gap:8px;align-items:flex-end">
          <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
          <input type="hidden" name="ticket_id" value="<?php echo $t->id; ?>">
          <textarea name="reply_message" rows="2"
            style="flex:1;box-sizing:border-box;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;font-size:13px;resize:none;font-family:inherit;line-height:1.5"
            placeholder="Kirjuta vastus..."></textarea>
          <button type="submit" class="vcp-btn-primary" style="font-size:13px;padding:9px 16px;white-space:nowrap;flex-shrink:0">Saada</button>
        </form>
      </div>
      <?php else: ?>
      <div style="border-top:1px solid #f0f4f7;padding:10px 16px;background:#f8fafb;font-size:12px;color:#9ca3af;text-align:center">Pilet on suletud</div>
      <?php endif; ?>

    </div><!-- /body -->

  </div>
  <?php endforeach; ?>
  </div>
<?php else: ?>
  <div class="vcp-empty">Tugipileteid pole.</div>
<?php endif; ?>
<script>
(function(){
  // ── Uue pileti vorm ───────────────────────────────────────────────────────
  var form=document.getElementById('vcp-ticket-form');
  if(form){
    form.addEventListener('submit',function(e){
      e.preventDefault();
      var msg=document.getElementById('vcp-ticket-msg');
      var fd=new FormData(form); fd.append('action','vesho_submit_ticket');
      var btn=form.querySelector('button[type=submit]'); btn.disabled=true; btn.textContent='...';
      fetch('<?php echo $ajax; ?>',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        msg.style.display='block';
        msg.className='vcp-msg '+(d.success?'success':'error');
        msg.textContent=(d.data&&d.data.message)||(d.success?'Pilet saadetud!':'Viga');
        if(d.success){ form.reset(); setTimeout(function(){window.location.reload();},1200); }
        btn.disabled=false; btn.textContent='Saada pilet';
      }).catch(function(){ msg.style.display='block'; msg.className='vcp-msg error'; msg.textContent='Ühenduse viga'; btn.disabled=false; btn.textContent='Saada pilet'; });
    });
  }

  // ── Pileti avamine/sulgemine ──────────────────────────────────────────────
  window.vshToggleTicket = function(id){
    var body   = document.getElementById('vsh-body-'+id);
    var arrow  = document.getElementById('vsh-arrow-'+id);
    var thread = document.getElementById('vsh-thread-'+id);
    if(!body) return;
    var open = body.style.display !== 'none';
    body.style.display = open ? 'none' : 'block';
    if(arrow) arrow.style.transform = open ? '' : 'rotate(180deg)';
    if(!open && thread) setTimeout(function(){ thread.scrollTop = thread.scrollHeight; }, 50);
  };

  // ── Kliendi vastuse vorm igale piletile ───────────────────────────────────
  document.querySelectorAll('[id^="vsh-reply-form-"]').forEach(function(replyForm){
    replyForm.addEventListener('submit', function(e){
      e.preventDefault();
      var tid = replyForm.querySelector('[name="ticket_id"]').value;
      var msg = document.getElementById('vsh-reply-msg-'+tid);
      var fd  = new FormData(replyForm);
      fd.append('action', 'vesho_client_reply_ticket');
      var btn = replyForm.querySelector('button[type=submit]');
      btn.disabled=true; btn.textContent='...';
      fetch('<?php echo $ajax; ?>',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        if(msg){ msg.style.display='block'; msg.className='vcp-msg '+(d.success?'success':'error'); msg.textContent=(d.data&&d.data.message)||(d.success?'Vastus saadetud!':'Viga'); }
        if(d.success){ setTimeout(function(){window.location.reload();},800); }
        btn.disabled=false; btn.textContent='Saada vastus';
      }).catch(function(){
        if(msg){ msg.style.display='block'; msg.className='vcp-msg error'; msg.textContent='Ühenduse viga'; }
        btn.disabled=false; btn.textContent='Saada vastus';
      });
    });
  });
})();
</script>
        <?php
    }

    // ── Tab: Profile ──────────────────────────────────────────────────────────

    private static function tab_profile($client, $nonce, $ajax) {
        ?>
<h2 class="vcp-section-title">Minu profiil</h2>
<div class="vcp-two-col">
  <div class="vcp-card">
    <h3 style="margin:0 0 16px;font-size:15px;font-weight:600">Isikuandmed</h3>
    <div id="vcp-profile-msg" class="vcp-msg" style="display:none"></div>
    <form id="vcp-profile-form" style="display:flex;flex-direction:column;gap:12px">
      <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
      <div class="vcp-field">
        <label>Täisnimi *</label>
        <input type="text" name="name" required class="vcp-input" value="<?php echo esc_attr($client->name); ?>">
      </div>
      <div class="vcp-field">
        <label>E-post</label>
        <input type="email" class="vcp-input" value="<?php echo esc_attr($client->email); ?>" disabled style="opacity:.6">
      </div>
      <div class="vcp-field">
        <label>Telefon</label>
        <input type="tel" name="phone" class="vcp-input" value="<?php echo esc_attr($client->phone ?? ''); ?>">
      </div>
      <div class="vcp-field">
        <label>Ettevõte</label>
        <input type="text" name="company" class="vcp-input" value="<?php echo esc_attr($client->company ?? ''); ?>">
      </div>
      <button type="submit" class="vcp-btn-primary" style="align-self:flex-start">Salvesta muutused</button>
    </form>
  </div>
  <div class="vcp-card">
    <h3 style="margin:0 0 16px;font-size:15px;font-weight:600">Muuda parool</h3>
    <div id="vcp-pw-msg" class="vcp-msg" style="display:none"></div>
    <form id="vcp-pw-form" style="display:flex;flex-direction:column;gap:12px">
      <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
      <div class="vcp-field">
        <label>Praegune parool *</label>
        <input type="password" name="current_password" required class="vcp-input" placeholder="••••••••">
      </div>
      <div class="vcp-field">
        <label>Uus parool * (min. 8 märki)</label>
        <input type="password" name="new_password" required minlength="8" class="vcp-input" placeholder="••••••••">
      </div>
      <button type="submit" class="vcp-btn-outline" style="align-self:flex-start">Muuda parool</button>
    </form>
  </div>
</div>

<?php if (($client->client_type ?? 'eraisik') === 'eraisik'): ?>
<div class="vcp-card" style="margin-top:20px">
  <h3 style="margin:0 0 12px;font-size:15px;font-weight:600">🏢 Uuenda ettevõtteks</h3>
  <p style="font-size:13px;color:#6b7280;margin-bottom:14px">Eraisikust konto saab üks kord muuta ettevõtte kontoks. Tagasipööramine ei ole võimalik.</p>
  <div id="vcp-firma-msg" class="vcp-msg" style="display:none"></div>
  <form id="vcp-firma-form" style="display:flex;flex-direction:column;gap:12px">
    <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
    <div class="vcp-field"><label>Ettevõtte nimi *</label><input type="text" name="company" required class="vcp-input" placeholder="OÜ Näide"></div>
    <div class="vcp-field"><label>Registrikood *</label><input type="text" name="reg_code" required class="vcp-input" placeholder="12345678"></div>
    <div class="vcp-field"><label>KMKR number</label><input type="text" name="vat_number" class="vcp-input" placeholder="EE123456789"></div>
    <button type="submit" class="vcp-btn-outline" style="align-self:flex-start">🏢 Muuda ettevõtteks</button>
  </form>
</div>
<?php endif; ?>

<div class="vcp-card" style="margin-top:20px;border-top:2px solid #fee2e2">
  <h3 style="margin:0 0 12px;font-size:15px;font-weight:600;color:#dc2626">🗑️ Kustuta konto (GDPR)</h3>
  <p style="font-size:13px;color:#6b7280;margin-bottom:14px">Isikuandmed anonümiseeritakse. Arved säilitatakse 7 aastat (raamatupidamiskohustus). Maksmata arvete korral kustutamine blokeeritakse.</p>
  <div id="vcp-delete-msg" class="vcp-msg" style="display:none"></div>
  <form id="vcp-delete-form" style="display:flex;flex-direction:column;gap:12px;max-width:360px">
    <input type="hidden" name="nonce" value="<?php echo $nonce; ?>">
    <div class="vcp-field"><label>Sisesta parool kinnituseks *</label><input type="password" name="confirm_password" required class="vcp-input" placeholder="••••••••"></div>
    <button type="submit" class="vcp-btn-outline" style="align-self:flex-start;color:#dc2626;border-color:#dc2626">🗑️ Kustuta konto</button>
  </form>
</div>

<script>
(function(){
  var AJAX='<?php echo $ajax; ?>';
  function ajaxForm(id,action,msgId,label){
    var form=document.getElementById(id); if(!form) return;
    form.addEventListener('submit',function(e){
      e.preventDefault();
      var msg=document.getElementById(msgId);
      var fd=new FormData(form); fd.append('action',action);
      var btn=form.querySelector('button[type=submit]'); btn.disabled=true; btn.textContent='...';
      fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        msg.style.display='block'; msg.className='vcp-msg '+(d.success?'success':'error');
        msg.textContent=(d.data&&d.data.message)||(d.success?'Salvestatud!':'Viga');
        btn.disabled=false; btn.textContent=label;
        if(d.success&&d.data&&d.data.redirect) setTimeout(function(){window.location.href=d.data.redirect;},1500);
      }).catch(function(){ msg.style.display='block'; msg.className='vcp-msg error'; msg.textContent='Viga'; btn.disabled=false; btn.textContent=label; });
    });
  }
  ajaxForm('vcp-profile-form','vesho_client_update_profile','vcp-profile-msg','Salvesta muutused');
  ajaxForm('vcp-pw-form','vesho_client_change_password','vcp-pw-msg','Muuda parool');
  ajaxForm('vcp-firma-form','vesho_client_upgrade_to_firma','vcp-firma-msg','🏢 Muuda ettevõtteks');
  var delForm=document.getElementById('vcp-delete-form');
  if(delForm){
    delForm.addEventListener('submit',function(e){
      e.preventDefault();
      if(!confirm('Oled kindel? See toiming on pöördumatu!')) return;
      var msg=document.getElementById('vcp-delete-msg');
      var fd=new FormData(delForm); fd.append('action','vesho_client_delete_account');
      var btn=delForm.querySelector('button[type=submit]'); btn.disabled=true; btn.textContent='...';
      fetch(AJAX,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){
        msg.style.display='block'; msg.className='vcp-msg '+(d.success?'success':'error');
        msg.textContent=(d.data&&d.data.message)||(d.success?'Konto kustutatud.':'Viga');
        btn.disabled=false; btn.textContent='🗑️ Kustuta konto';
        if(d.success) setTimeout(function(){window.location.href=(d.data&&d.data.redirect)||'/';},2000);
      }).catch(function(){ btn.disabled=false; btn.textContent='🗑️ Kustuta konto'; });
    });
  }
})();
</script>
        <?php
    }

    // ── Tab: Contract terms ───────────────────────────────────────────────────

    private static function tab_contract() {
        $terms = get_option('vesho_contract_terms', '');
        ?>
<h2 class="vcp-section-title">Lepingutingimused</h2>
<?php if (empty($terms)): ?>
  <div class="vcp-empty">Lepingutingimused pole veel lisatud.</div>
<?php else: ?>
  <div class="vcp-card" style="line-height:1.7;font-size:14px;color:#334155;max-width:800px">
    <?php echo wp_kses_post(wpautop($terms)); ?>
  </div>
<?php endif; ?>
        <?php
    }

    // ── AJAX: Login ───────────────────────────────────────────────────────────

    public static function ajax_login() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $email    = sanitize_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $creds = ['user_login' => $email, 'user_password' => $password, 'remember' => true];
        $user = wp_signon($creds, is_ssl());
        if (is_wp_error($user)) {
            wp_send_json_error(['message' => 'Vale e-post või parool']);
        }
        if (!in_array('vesho_client', (array) $user->roles)) {
            wp_logout();
            wp_send_json_error(['message' => 'See konto ei ole kliendi konto']);
        }
        $portal = get_page_by_path('klient');
        wp_send_json_success(['message' => 'Sisselogimine õnnestus!', 'redirect' => $portal ? get_permalink($portal) : home_url('/klient/')]);
    }

    // ── AJAX: Register ────────────────────────────────────────────────────────

    public static function ajax_register() {
        $nonce = $_POST['nonce'] ?? $_POST['lm_nonce_reg'] ?? $_POST['lm_reg_nonce'] ?? '';
        if ( ! wp_verify_nonce( $nonce, 'vesho_portal_nonce' ) ) {
            wp_send_json_error( [ 'message' => 'Turvakontroll ebaõnnestus. Uuenda leht ja proovi uuesti.' ] );
        }
        $name     = sanitize_text_field($_POST['name'] ?? '');
        $email    = sanitize_email($_POST['email'] ?? '');
        $phone    = sanitize_text_field($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$name || !$email || strlen($password) < 8) {
            wp_send_json_error(['message' => 'Täida kõik kohustuslikud väljad (parool min 8 märki)']);
        }
        if (get_option('vesho_portal_registration', '1') !== '1') {
            wp_send_json_error(['message' => 'Registreerimine on keelatud']);
        }
        if (email_exists($email)) {
            wp_send_json_error(['message' => 'See e-posti aadress on juba registreeritud']);
        }
        global $wpdb;
        // Generate username from full name (e.g. "Sandro Mägi" → "sandro.magi")
        $username_base = sanitize_user( str_replace( ' ', '.', strtolower( $name ) ), true );
        if ( empty( $username_base ) ) $username_base = strtolower( strstr( $email, '@', true ) );
        $username = $username_base;
        $i = 1;
        while ( username_exists( $username ) ) {
            $username = $username_base . $i;
            $i++;
        }
        $user_id = wp_create_user($username, $password, $email);
        if (is_wp_error($user_id)) {
            wp_send_json_error(['message' => $user_id->get_error_message()]);
        }
        $user = new WP_User($user_id);
        $user->set_role('vesho_client');
        wp_update_user(['ID' => $user_id, 'display_name' => $name, 'first_name' => $name]);
        $account_type = sanitize_text_field($_POST['account_type'] ?? $_POST['client_type'] ?? 'eraisik');
        $reg_company  = sanitize_text_field($_POST['reg_company'] ?? $_POST['company'] ?? '');
        $reg_number   = sanitize_text_field($_POST['reg_number']  ?? $_POST['reg_code'] ?? '');
        $vat_number   = sanitize_text_field($_POST['vat_number']  ?? '');
        if ($account_type === 'firma' && $reg_company === '') {
            wp_delete_user($user_id);
            wp_send_json_error(['message' => 'Ettevõtte nimi on kohustuslik ettevõtte konto puhul']);
        }
        $verify_token = bin2hex(random_bytes(24));
        $wpdb->insert($wpdb->prefix . 'vesho_clients', [
            'user_id'            => $user_id,
            'name'               => $name,
            'email'              => $email,
            'phone'              => $phone ?: '',
            'client_type'        => $account_type,
            'company'            => $reg_company,
            'reg_code'           => $reg_number,
            'vat_number'         => $vat_number,
            'email_verified'     => 0,
            'email_verify_token' => $verify_token,
            'created_at'         => current_time('mysql'),
        ]);
        // Send verification email
        $portal   = get_page_by_path('klient');
        $base_url = $portal ? get_permalink($portal) : home_url('/klient/');
        $verify_url = add_query_arg('verify_email', $verify_token, $base_url);
        $co = get_option('vesho_company_name', 'Vesho OÜ');
        wp_mail(
            $email,
            $co . ' — Kinnita e-posti aadress',
            "Tere, {$name}!\n\nKinnitamaks oma e-posti aadressi, palun klõpsa alloleval lingil (kehtib 24h):\n\n{$verify_url}\n\nLugupidamisega,\n{$co}"
        );
        wp_send_json_success(['message' => 'Konto loodud! Saatsime Sulle kinnitusmeili — palun kontrolli postkasti ja klõpsa kinnituslingil.', 'verify' => true]);
    }

    // ── AJAX: Logout ──────────────────────────────────────────────────────────

    public static function ajax_logout() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        wp_logout();
        wp_send_json_success(['message' => 'Väljalogimine õnnestus', 'redirect' => home_url('/klient/')]);
    }

    // ── AJAX: Forgot password ─────────────────────────────────────────────────

    public static function ajax_forgot_password() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $email = sanitize_email($_POST['email'] ?? '');
        if (!$email) wp_send_json_error(['message' => 'E-posti aadress on kohustuslik.']);

        $user = get_user_by('email', $email);
        // Always return success to prevent user enumeration
        if (!$user) {
            wp_send_json_success(['message' => 'Kui see e-posti aadress on registreeritud, saatsime parooli lähtestamise lingi.']);
        }

        // Use WordPress built-in password reset
        $key = get_password_reset_key($user);
        if (is_wp_error($key)) {
            wp_send_json_error(['message' => 'Lingi genereerimisel tekkis viga. Proovi uuesti.']);
        }

        $reset_url = add_query_arg([
            'action' => 'rp',
            'key'    => $key,
            'login'  => rawurlencode($user->user_login),
        ], wp_login_url());

        $co = get_option('vesho_company_name', 'Vesho OÜ');
        wp_mail(
            $email,
            $co . ' — Parooli lähtestamine',
            "Tere!\n\nSaime parooli lähtestamise taotluse.\n\nParooli lähtestamiseks klõpsa alloleval lingil (kehtib 24h):\n{$reset_url}\n\nKui Sa ei teinud seda taotlust, eira seda meili — Sinu parool ei muutu.\n\nLugupidamisega,\n{$co}"
        );

        wp_send_json_success(['message' => 'Kui see e-posti aadress on registreeritud, saatsime parooli lähtestamise lingi.']);
    }

    // ── AJAX: Resend verification email ──────────────────────────────────────

    public static function ajax_resend_verification() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        if ( ! is_user_logged_in() ) wp_send_json_error(['message' => 'Pole sisse logitud']);
        global $wpdb;
        $user   = wp_get_current_user();
        $client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_clients WHERE user_id=%d LIMIT 1", $user->ID
        ));
        if ( ! $client ) wp_send_json_error(['message' => 'Kliendi konto ei leitud']);
        if ( (int)$client->email_verified === 1 ) {
            wp_send_json_success(['message' => 'E-post on juba kinnitatud! Värskenda lehte.']);
        }
        // Generate fresh token
        $token = wp_generate_password(32, false);
        $wpdb->update(
            $wpdb->prefix . 'vesho_clients',
            ['email_verify_token' => $token],
            ['id' => $client->id]
        );
        $portal     = get_page_by_path('klient');
        $base_url   = $portal ? get_permalink($portal) : home_url('/klient/');
        $verify_url = add_query_arg('verify_email', $token, $base_url);
        $co         = get_option('vesho_company_name', 'Vesho OÜ');
        $name       = $client->name ?? 'klient';
        wp_mail(
            $client->email,
            $co . ' — Kinnita e-posti aadress',
            "Tere, {$name}!\n\nKinnitamaks oma e-posti aadressi, palun klõpsa alloleval lingil (kehtib 24h):\n\n{$verify_url}\n\nLugupidamisega,\n{$co}"
        );
        wp_send_json_success(['message' => 'Kinnituslink saadetud! Kontrolli postkasti.']);
    }

    // ── AJAX: Update profile ──────────────────────────────────────────────────

    public static function ajax_update_profile() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $client = self::get_current_client();
        if (!$client) wp_send_json_error(['message' => 'Pole sisse logitud']);
        global $wpdb;
        $name    = sanitize_text_field($_POST['name'] ?? '');
        $phone   = sanitize_text_field($_POST['phone'] ?? '');
        $company = sanitize_text_field($_POST['company'] ?? '');
        if (!$name) wp_send_json_error(['message' => 'Nimi on kohustuslik']);

        $wpdb->update(
            $wpdb->prefix . 'vesho_clients',
            ['name' => $name, 'phone' => $phone ?: '', 'company' => $company ?: ''],
            ['id' => $client->id]
        );
        $user_id = get_current_user_id();
        wp_update_user(['ID' => $user_id, 'display_name' => $name, 'first_name' => $name]);
        wp_send_json_success(['message' => 'Profiil uuendatud!']);
    }

    // ── AJAX: Change password ─────────────────────────────────────────────────

    public static function ajax_change_password() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'Pole sisse logitud']);
        $current_pw = $_POST['current_password'] ?? '';
        $new_pw     = $_POST['new_password'] ?? '';
        if (!$current_pw || !$new_pw) wp_send_json_error(['message' => 'Mõlemad paroolid on kohustuslikud']);
        if (strlen($new_pw) < 8) wp_send_json_error(['message' => 'Uus parool peab olema vähemalt 8 märki']);

        $user = wp_get_current_user();
        if (!wp_check_password($current_pw, $user->user_pass, $user->ID)) {
            wp_send_json_error(['message' => 'Vale praegune parool']);
        }
        wp_set_password($new_pw, $user->ID);
        // Re-login after password change so the session stays valid
        wp_signon(['user_login' => $user->user_email, 'user_password' => $new_pw, 'remember' => true], is_ssl());
        wp_send_json_success(['message' => 'Parool muudetud!']);
    }

    // ── AJAX: Book service ────────────────────────────────────────────────────

    public static function ajax_book_service() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $client = self::get_current_client();
        if (!$client) wp_send_json_error(['message' => 'Pole sisse logitud']);
        global $wpdb;
        $cid            = (int) $client->id;
        $service_id     = absint($_POST['service_id'] ?? 0);
        $preferred_date = sanitize_text_field($_POST['preferred_date'] ?? '');
        $description    = sanitize_textarea_field($_POST['description'] ?? '');
        $device_id      = absint($_POST['device_id'] ?? 0);
        if (!$service_id || !$preferred_date) wp_send_json_error(['message' => 'Teenus ja kuupäev on kohustuslikud']);

        $service = $wpdb->get_row($wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}vesho_services WHERE id=%d", $service_id
        ));
        $service_name = $service ? $service->name : 'Teenus #' . $service_id;

        // Verify device ownership if provided
        if ($device_id) {
            $owns = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}vesho_devices WHERE id=%d AND client_id=%d", $device_id, $cid
            ));
            if (!$owns) $device_id = 0;
        }

        // If no device, create a placeholder for this client or use first device
        if (!$device_id) {
            $device_id = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}vesho_devices WHERE client_id=%d LIMIT 1", $cid
            ));
        }

        $desc = $description ? $service_name . ' — ' . $description : $service_name;

        // Lock active maintenance campaign discount at booking time
        $today = current_time('Y-m-d');
        $campaign = $wpdb->get_row( $wpdb->prepare(
            "SELECT name, maintenance_discount_percent FROM {$wpdb->prefix}vesho_campaigns
             WHERE paused=0 AND valid_from<=%s AND valid_until>=%s
               AND (target='hooldus' OR target='both')
             ORDER BY maintenance_discount_percent DESC LIMIT 1",
            $today, $today
        ) );
        $campaign_discount = $campaign ? (float) $campaign->maintenance_discount_percent : 0;
        $campaign_name     = $campaign ? (string) $campaign->name : '';

        if ($device_id) {
            $wpdb->insert($wpdb->prefix . 'vesho_maintenances', [
                'device_id'         => $device_id,
                'client_id'         => $cid,
                'scheduled_date'    => $preferred_date,
                'description'       => $desc,
                'status'            => 'pending',
                'service_id'        => $service_id,
                'campaign_discount' => $campaign_discount,
                'campaign_name'     => $campaign_name,
                'created_at'        => current_time('mysql'),
            ]);
        } else {
            // No device on account — insert into work_orders table
            $wpdb->insert($wpdb->prefix . 'vesho_workorders', [
                'client_id'      => $cid,
                'title'          => $service_name,
                'description'    => $desc,
                'scheduled_date' => $preferred_date,
                'status'         => 'pending',
                'created_at'     => current_time('mysql'),
            ]);
        }
        wp_send_json_success(['message' => 'Broneeringusoov esitatud! Võtame teiega ühendust.']);
    }

    // ── AJAX: Cancel booking ─────────────────────────────────────────────────

    public static function ajax_cancel_booking() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $client = self::get_current_client();
        if (!$client) wp_send_json_error(['message' => 'Pole sisse logitud']);
        global $wpdb;
        $cid   = (int) $client->id;
        $mid   = absint($_POST['maintenance_id'] ?? 0);
        $reason= sanitize_textarea_field($_POST['reason'] ?? '');
        if (!$mid) wp_send_json_error(['message' => 'Vigane ID']);

        // Verify ownership + cancellable status
        $maint = $wpdb->get_row($wpdb->prepare(
            "SELECT m.id, m.status, m.scheduled_date, d.name as device_name
             FROM {$wpdb->prefix}vesho_maintenances m
             JOIN {$wpdb->prefix}vesho_devices d ON d.id=m.device_id
             WHERE m.id=%d AND d.client_id=%d LIMIT 1",
            $mid, $cid
        ));
        if (!$maint) wp_send_json_error(['message' => 'Hooldust ei leitud']);
        if (!in_array($maint->status, ['scheduled','pending'], true)) {
            wp_send_json_error(['message' => 'Seda hooldust ei saa enam tühistada']);
        }

        $notes = $reason ? "Klient tühistas. Põhjus: {$reason}" : 'Klient tühistas.';
        $wpdb->update(
            $wpdb->prefix . 'vesho_maintenances',
            ['status' => 'cancelled', 'notes' => $notes],
            ['id' => $mid]
        );

        // Notify admin
        $notify = get_option('vesho_notify_email', get_option('admin_email'));
        if ($notify) {
            $co   = get_option('vesho_company_name', 'Vesho CRM');
            $date = $maint->scheduled_date ? date('d.m.Y', strtotime($maint->scheduled_date)) : '—';
            wp_mail($notify, "[{$co}] Hooldus tühistatud kliendi poolt",
                "Klient {$client->name} ({$client->email}) tühistas hoolduse:\n" .
                "Seade: {$maint->device_name}\nKuupäev: {$date}\n" .
                ($reason ? "Põhjus: {$reason}" : '')
            );
        }

        if (function_exists('vesho_crm_log_activity')) {
            vesho_crm_log_activity('maintenance_cancelled', "Klient tühistas hoolduse #{$mid}", 'maintenance', $mid);
        }

        wp_send_json_success(['message' => 'Hooldus tühistatud.']);
    }

    // ── AJAX: Upgrade eraisik → firma ─────────────────────────────────────────

    public static function ajax_upgrade_to_firma() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $client = self::get_current_client();
        if (!$client) wp_send_json_error(['message' => 'Pole sisse logitud']);
        if (($client->client_type ?? 'eraisik') !== 'eraisik') {
            wp_send_json_error(['message' => 'Konto on juba ettevõtte tüüpi']);
        }
        global $wpdb;
        $company  = sanitize_text_field($_POST['company'] ?? '');
        $reg_code = sanitize_text_field($_POST['reg_code'] ?? '');
        $vat_num  = sanitize_text_field($_POST['vat_number'] ?? '');
        if (!$company || !$reg_code) wp_send_json_error(['message' => 'Ettevõtte nimi ja registrikood on kohustuslikud']);

        $wpdb->update(
            $wpdb->prefix . 'vesho_clients',
            ['client_type' => 'firma', 'company' => $company, 'reg_code' => $reg_code, 'vat_number' => $vat_num],
            ['id' => $client->id]
        );
        wp_send_json_success(['message' => 'Konto uuendatud ettevõtte kontoks!']);
    }

    // ── AJAX: Delete account (GDPR) ───────────────────────────────────────────

    public static function ajax_delete_account() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $client = self::get_current_client();
        if (!$client) wp_send_json_error(['message' => 'Pole sisse logitud']);

        $confirm_pw = $_POST['confirm_password'] ?? '';
        $user = wp_get_current_user();
        if (!wp_check_password($confirm_pw, $user->user_pass, $user->ID)) {
            wp_send_json_error(['message' => 'Vale parool']);
        }

        global $wpdb;
        $cid = (int) $client->id;

        // Block if unpaid invoices exist
        $unpaid = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_invoices WHERE client_id=%d AND status IN ('unpaid','overdue','sent')",
            $cid
        ));
        if ($unpaid > 0) {
            wp_send_json_error(['message' => "Kontol on {$unpaid} tasumata arvet. Palun tasuge enne konto kustutamist."]);
        }

        // Anonymize personal data — keep invoices (7-year accounting requirement)
        $anon_name  = 'Kustutatud kasutaja #' . $cid;
        $anon_email = 'deleted_' . $cid . '@deleted.local';
        $wpdb->update(
            $wpdb->prefix . 'vesho_clients',
            [
                'name'           => $anon_name,
                'email'          => $anon_email,
                'phone'          => '',
                'address'        => '',
                'company'        => '',
                'reg_number'     => '',
                'vat_number'     => '',
                'notes'          => '',
                'portal_token'   => '',
                'session_token'  => '',
            ],
            ['id' => $cid]
        );

        // Clear session cookie
        setcookie('vesho_client_token', '', time() - 3600, '/');

        // Delete WP user
        $wp_uid = (int) ($client->user_id ?? $user->ID);
        wp_logout();
        if ($wp_uid) {
            wp_delete_user($wp_uid);
        }

        wp_send_json_success(['message' => 'Konto kustutatud. Andmed anonümiseeritud.', 'redirect' => home_url('/')]);
    }

    // ── AJAX: Submit ticket ───────────────────────────────────────────────────

    public static function ajax_submit_ticket() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $client = self::get_current_client();
        if (!$client) wp_send_json_error(['message' => 'Pole sisse logitud']);
        global $wpdb;
        $cid     = (int) $client->id;
        $subject = sanitize_text_field($_POST['subject'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        if (!$subject || !$message) wp_send_json_error(['message' => 'Teema ja sõnum on kohustuslikud']);

        $wpdb->insert($wpdb->prefix . 'vesho_support_tickets', [
            'client_id'  => $cid,
            'subject'    => $subject,
            'message'    => $message,
            'status'     => 'open',
            'created_at' => current_time('mysql'),
        ]);
        $ticket_id = (int) $wpdb->insert_id;

        // Handle optional file attachment
        if ($ticket_id && !empty($_FILES['ticket_attachment']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $upload = wp_handle_upload($_FILES['ticket_attachment'], ['test_form' => false]);
            if (!empty($upload['url'])) {
                $wpdb->update(
                    $wpdb->prefix . 'vesho_support_tickets',
                    ['attachment_url' => $upload['url']],
                    ['id' => $ticket_id]
                );
            }
        }

        // Admin notification
        if ( get_option('vesho_notify_new_ticket', '1') === '1' ) {
            $notify_email = get_option('vesho_notify_email', get_option('admin_email'));
            if ( $notify_email ) {
                $co = get_option('vesho_company_name', 'Vesho CRM');
                wp_mail( $notify_email,
                    "[{$co}] Uus tugipilet: {$subject}",
                    "Klient: {$client->name} ({$client->email})\nTeema: {$subject}\n\n{$message}"
                );
            }
        }
        wp_send_json_success(['message' => 'Pilet edukalt saadetud! Vastame esimesel võimalusel.']);
    }

    // ── AJAX: Client reply to existing ticket ─────────────────────────────────

    public static function ajax_client_reply_ticket() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $client = self::get_current_client();
        if (!$client) wp_send_json_error(['message' => 'Pole sisse logitud']);
        global $wpdb;
        $tid     = absint($_POST['ticket_id'] ?? 0);
        $message = sanitize_textarea_field($_POST['reply_message'] ?? '');
        if (!$tid || !$message) wp_send_json_error(['message' => 'Vastus ei tohi olla tühi']);

        // Make sure ticket belongs to this client
        $ticket = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_support_tickets WHERE id=%d AND client_id=%d LIMIT 1",
            $tid, $client->id
        ));
        if (!$ticket) wp_send_json_error(['message' => 'Pilet ei leitud']);
        if ($ticket->status === 'closed') wp_send_json_error(['message' => 'Pilet on suletud']);

        $wpdb->insert($wpdb->prefix . 'vesho_ticket_replies', [
            'ticket_id'  => $tid,
            'author'     => 'client',
            'message'    => $message,
            'created_at' => current_time('mysql'),
        ]);

        // Re-open ticket to in_progress if it was closed
        $wpdb->update($wpdb->prefix . 'vesho_support_tickets', [
            'status'     => 'in_progress',
            'updated_at' => current_time('mysql'),
        ], ['id' => $tid]);

        // Notify admin
        $notify_email = get_option('vesho_notify_email', get_option('admin_email'));
        if ($notify_email) {
            $co = get_option('vesho_company_name', 'Vesho CRM');
            wp_mail($notify_email,
                "[{$co}] Klient vastas piletile #{$tid}: {$ticket->subject}",
                "Klient: {$client->name} ({$client->email})\n\n{$message}"
            );
        }

        wp_send_json_success(['message' => 'Vastus saadetud!']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static function status_badge($status, $type = '') {
        $map = [
            // invoice
            'paid'        => ['Tasutud',       '#dcfce7', '#16a34a'],
            'sent'        => ['Saadetud',      '#fee2e2', '#dc2626'],
            'overdue'     => ['Tähtaeg möödas','#fee2e2', '#dc2626'],
            'draft'       => ['Mustand',       '#f3f4f6', '#6b7280'],
            // maintenance / order
            'scheduled'   => ['Planeeritud',   '#dbeafe', '#1d4ed8'],
            'pending'     => ['Ootel',         '#fef9c3', '#b45309'],
            'in_progress' => ['Töös',          '#e0f2fe', '#0369a1'],
            'completed'   => ['Lõpetatud',     '#dcfce7', '#16a34a'],
            'cancelled'   => ['Tühistatud',    '#f3f4f6', '#6b7280'],
            // ticket
            'open'        => ['Avatud',        '#dbeafe', '#1d4ed8'],
            'closed'      => ['Suletud',       '#f3f4f6', '#6b7280'],
            'resolved'    => ['Lahendatud',    '#dcfce7', '#16a34a'],
        ];
        $info = $map[$status] ?? [esc_html($status), '#f3f4f6', '#6b7280'];
        return sprintf(
            '<span style="display:inline-block;padding:2px 10px;border-radius:999px;font-size:11px;font-weight:600;background:%s;color:%s">%s</span>',
            esc_attr($info[1]), esc_attr($info[2]), esc_html($info[0])
        );
    }

    // ── CSS ───────────────────────────────────────────────────────────────────

    private static function portal_css() {
        return '
/* === Vesho Client Portal CSS === */
.vcp-wrap{display:flex;min-height:100vh;font-family:Barlow,system-ui,sans-serif;font-size:14px;color:#1e293b;background:#f8fafc}
.vcp-sidebar{width:240px;min-height:100vh;background:#1e293b;display:flex;flex-direction:column;flex-shrink:0;position:sticky;top:0}
@media(min-width:769px){.vcp-nav-arrow{display:none !important;pointer-events:none !important}}
.vcp-sidebar-logo{padding:24px 20px 12px;font-size:22px;font-weight:800;color:#fff;letter-spacing:-.5px}
.vcp-sidebar-logo span{color:#00b4c8}
.vcp-sidebar-label{padding:4px 20px 8px;font-size:10px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:rgba(255,255,255,.35)}
.vcp-nav{list-style:none;margin:0;padding:0 8px}
.vcp-nav li{margin-bottom:2px}
.vcp-nav-link{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:8px;color:rgba(255,255,255,.65);text-decoration:none;font-size:13px;font-weight:500;transition:background .15s,color .15s}
.vcp-nav-link:hover,.vcp-nav-link.active{background:rgba(0,180,200,.18);color:#fff}
.vcp-nav-link.active{color:#00b4c8}
.vcp-nav-icon{width:18px;text-align:center;font-size:15px}
.vcp-sidebar-footer{margin-top:auto;padding:16px 16px 20px;border-top:1px solid rgba(255,255,255,.08)}
.vcp-sidebar-user{display:flex;gap:10px;align-items:center;margin-bottom:12px}
.vcp-avatar{width:34px;height:34px;border-radius:50%;background:#00b4c8;color:#fff;font-size:14px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.vcp-sidebar-name{color:#fff;font-size:13px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px}
.vcp-sidebar-email{color:rgba(255,255,255,.4);font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px}
.vcp-logout-link{display:block;color:rgba(255,255,255,.4);font-size:12px;text-decoration:none;padding:4px 0;transition:color .15s}
.vcp-logout-link:hover{color:#fff}
.vcp-main{flex:1;display:flex;flex-direction:column;min-width:0}
.vcp-topbar{height:56px;background:#fff;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;padding:0 24px;gap:12px;position:sticky;top:0;z-index:50}
.vcp-topbar-title{font-size:16px;font-weight:600;flex:1}
.vcp-topbar-right{font-size:13px;color:#64748b}
.vcp-hamburger{display:none;background:none;border:none;font-size:20px;cursor:pointer;color:#1e293b;padding:4px 8px}
.vcp-content{padding:28px 28px;max-width:1100px;width:100%}
.vcp-page-header{margin-bottom:24px}
.vcp-page-header h1{font-size:22px;font-weight:700;margin:0 0 4px}
.vcp-page-header p{margin:0;color:#64748b}
.vcp-section-title{font-size:16px;font-weight:600;margin:0 0 12px}
.vcp-stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:16px;margin-bottom:28px}
.vcp-stat-card{background:#fff;border-radius:12px;padding:20px;border-left:4px solid #e2e8f0;box-shadow:0 1px 3px rgba(0,0,0,.07)}
.vcp-stat-blue{border-left-color:#3b82f6}
.vcp-stat-green{border-left-color:#10b981}
.vcp-stat-orange{border-left-color:#f59e0b}
.vcp-stat-teal{border-left-color:#00b4c8}
.vcp-stat-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;margin-bottom:6px}
.vcp-stat-value{font-size:1.6rem;font-weight:700;color:#1e293b}
.vcp-two-col{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px}
.vcp-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.07);margin-bottom:16px}
.vcp-table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.07)}
.vcp-table thead tr{background:#f8fafc}
.vcp-table th{padding:10px 14px;text-align:left;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#64748b;border-bottom:1px solid #e2e8f0}
.vcp-table td{padding:10px 14px;border-bottom:1px solid #f1f5f9;font-size:13px}
.vcp-table tbody tr:last-child td{border-bottom:none}
.vcp-table tbody tr:nth-child(even){background:#fafafa}
.vcp-table tbody tr:hover{background:#f1f5f9}
.vcp-empty{padding:32px;text-align:center;color:#94a3b8;background:#fff;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.07)}
.vcp-maint-item{display:flex;align-items:center;gap:12px;background:#fff;padding:12px 16px;border-radius:10px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
.vcp-maint-date{font-size:12px;font-weight:600;color:#3b82f6;min-width:72px}
.vcp-maint-info{flex:1;font-size:13px}
.vcp-quick-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:20px}
.vcp-btn-primary{display:inline-block;padding:10px 20px;background:#00b4c8;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;transition:background .15s}
.vcp-btn-primary:hover{background:#0099ad}
.vcp-btn-outline{display:inline-block;padding:10px 20px;background:#fff;color:#1e293b;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;transition:border-color .15s}
.vcp-btn-outline:hover{border-color:#94a3b8}
.vcp-field{display:flex;flex-direction:column;gap:4px}
.vcp-field label{font-size:12px;font-weight:600;color:#64748b}
.vcp-input{width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;font-family:inherit;transition:border-color .15s;outline:none}
.vcp-input:focus{border-color:#00b4c8;box-shadow:0 0 0 3px rgba(0,180,200,.12)}
.vcp-msg{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:12px}
.vcp-msg.success{background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0}
.vcp-msg.error{background:#fee2e2;color:#dc2626;border:1px solid #fecaca}
/* Login card */
.vcp-login-wrap{display:flex;align-items:center;justify-content:center;min-height:60vh;padding:24px}
.vcp-login-card{background:#fff;border-radius:16px;padding:36px;width:100%;max-width:400px;box-shadow:0 4px 24px rgba(0,0,0,.1)}
.vcp-login-logo{font-size:24px;font-weight:800;color:#1e293b;margin-bottom:4px;letter-spacing:-.5px}
.vcp-login-logo span{color:#00b4c8}
.vcp-login-title{font-size:18px;font-weight:600;color:#1e293b;margin:0 0 20px}
.vcp-tabs{display:flex;gap:0;border-bottom:2px solid #f1f5f9;margin-bottom:20px}
.vcp-tab{padding:8px 18px;background:none;border:none;font-size:13px;font-weight:600;color:#94a3b8;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px}
.vcp-tab.active{color:#00b4c8;border-bottom-color:#00b4c8}
.vcp-panel{display:none}
.vcp-panel.active{display:block}
/* ════════════════════════════════════════════════════════════
   MOBIIL — Vesho Client Portal v2.9.64
   ════════════════════════════════════════════════════════════ */

/* Globaalsed alused */
html:has(.vcp-wrap),body:has(.vcp-wrap){overscroll-behavior-x:none;touch-action:pan-y;overflow-x:hidden}
*{box-sizing:border-box}
.vcp-wrap,.vcp-main,.vcp-content{box-sizing:border-box;max-width:100%}
.vcp-table-wrap{touch-action:pan-x pan-y;-webkit-overflow-scrolling:touch}
.vcp-wrap a,.vcp-wrap button{-webkit-tap-highlight-color:transparent}

/* ── 1024px: tahvelarvuti ── */
@media(max-width:1024px){
  .vcp-content{padding:24px 20px}
  .vcp-stat-grid{grid-template-columns:repeat(2,1fr)}
  .vcp-two-col{gap:16px}
}

/* ── 768px: mobiil ── */
@media(max-width:768px){

  /* Avaliku saidi päis: portaalil ainult logo */
  body:has(.vcp-wrap) .site-topbar,
  body:has(.vcp-wrap) .header-nav,
  body:has(.vcp-wrap) .header-phone,
  body:has(.vcp-wrap) .header-cta,
  body:has(.vcp-wrap) .hamburger{display:none !important}
  body:has(.vcp-wrap) .site-header .header-inner{height:52px !important;padding:0 16px !important;min-height:unset !important}
  body:has(.vcp-wrap) #page-content{padding-top:52px !important}
  body:has(.vcp-wrap) #page,
  body:has(.vcp-wrap) #primary,
  body:has(.vcp-wrap) #main,
  body:has(.vcp-wrap) .entry-content,
  body:has(.vcp-wrap) article{padding:0 !important;margin:0 !important}

  /* ── Bottom navigation bar ── */
  .vcp-sidebar{
    position:fixed !important;
    bottom:0 !important;left:0 !important;right:0 !important;top:auto !important;
    width:100% !important;min-height:unset !important;
    height:calc(64px + env(safe-area-inset-bottom)) !important;
    flex-direction:row !important;
    z-index:900 !important;
    overflow:hidden !important;
    border-top:1px solid rgba(0,180,200,.15) !important;
    padding:0 0 env(safe-area-inset-bottom) !important;
    background:#0d1f2d !important;
    box-shadow:0 -2px 20px rgba(0,0,0,.35) !important;
  }
  .vcp-sidebar-logo,.vcp-sidebar-label,.vcp-sidebar-footer{display:none !important}

  /* Nav: flex rida */
  .vcp-nav{
    display:flex !important;flex-direction:row !important;flex:1 !important;
    padding:0 !important;margin:0 !important;min-width:0 !important;
    overflow-x:auto !important;overflow-y:hidden !important;
    scroll-behavior:smooth !important;-webkit-overflow-scrolling:touch !important;
    scrollbar-width:none !important;align-items:stretch !important;
  }
  .vcp-nav::-webkit-scrollbar{display:none !important}
  .vcp-nav li{flex:1 1 0 !important;min-width:54px !important;margin:0 !important;display:flex !important}

  /* Nav link */
  .vcp-nav-link{
    flex-direction:column !important;
    justify-content:center !important;align-items:center !important;
    gap:4px !important;padding:6px 4px 8px !important;
    font-size:10px !important;font-weight:700 !important;letter-spacing:.3px !important;
    border-radius:0 !important;
    min-height:64px !important;height:64px !important;
    text-align:center !important;white-space:nowrap !important;
    color:rgba(255,255,255,.4) !important;
    transition:color .15s,background .15s !important;
    flex:1 !important;position:relative !important;
    text-decoration:none !important;
  }
  .vcp-nav-link:hover{
    color:rgba(255,255,255,.7) !important;
    background:rgba(255,255,255,.05) !important;
  }
  .vcp-nav-link.active{
    color:#00b4c8 !important;
    background:rgba(0,180,200,.07) !important;
  }
  .vcp-nav-link.active::after{
    content:"" !important;
    position:absolute !important;top:0 !important;left:20% !important;right:20% !important;
    height:2px !important;background:#00b4c8 !important;
    border-radius:0 0 3px 3px !important;
  }
  .vcp-nav-icon{font-size:21px !important;width:auto !important;line-height:1 !important}

  /* Nav noolenupud */
  .vcp-nav-arrow{
    display:flex !important;align-items:center !important;justify-content:center !important;
    width:32px !important;height:64px !important;background:#0d1f2d !important;
    border:none !important;padding:0 !important;cursor:pointer !important;
    color:rgba(255,255,255,.5) !important;font-size:16px !important;
    flex-shrink:0 !important;transition:opacity .2s !important;
  }
  .vcp-nav-arrow--left{box-shadow:6px 0 12px rgba(0,0,0,.3) !important}
  .vcp-nav-arrow--right{box-shadow:-6px 0 12px rgba(0,0,0,.3) !important}
  .vcp-nav-arrow:disabled{opacity:0 !important;pointer-events:none !important}

  /* Main area */
  .vcp-main{width:100% !important;min-width:0 !important}
  .vcp-hamburger{display:none !important}

  /* Topbar */
  .vcp-topbar{
    position:sticky !important;top:0 !important;
    padding:0 16px !important;height:52px !important;
    z-index:200 !important;
    background:#fff !important;
    border-bottom:2px solid #f1f5f9 !important;
    box-shadow:0 1px 6px rgba(0,0,0,.06) !important;
  }
  .vcp-topbar-title{font-size:15px !important;font-weight:700 !important;color:#0d1f2d !important}
  .vcp-topbar-right{display:none !important}

  /* Sisu */
  .vcp-content{
    padding:16px 14px calc(80px + env(safe-area-inset-bottom)) !important;
    max-width:100% !important;
  }

  /* Stat kaardid: 2 veergu */
  .vcp-stat-grid{grid-template-columns:1fr 1fr !important;gap:10px !important;margin-bottom:16px !important}
  .vcp-stat-card{
    padding:14px 12px 12px !important;
    border-radius:12px !important;
    border-left-width:3px !important;
    box-shadow:0 1px 4px rgba(0,0,0,.08) !important;
  }
  .vcp-stat-label{font-size:10px !important;letter-spacing:.04em !important;margin-bottom:6px !important}
  .vcp-stat-value{font-size:1.5rem !important;font-weight:800 !important;line-height:1.1 !important}

  /* 2-veergu → 1 veerg */
  .vcp-two-col{grid-template-columns:1fr !important;gap:12px !important}

  /* Kaardid */
  .vcp-card{padding:14px !important;border-radius:12px !important;margin-bottom:12px !important}
  .vcp-maint-item{flex-wrap:wrap !important;gap:6px !important;padding:10px 12px !important}
  .vcp-maint-date{min-width:unset !important;font-size:12px !important}
  .vcp-maint-info{font-size:12px !important}

  /* Lehepealkiri */
  .vcp-page-header{margin-bottom:14px !important}
  .vcp-page-header h1{font-size:20px !important;font-weight:800 !important}
  .vcp-page-header p{font-size:13px !important}
  .vcp-section-title{font-size:14px !important;font-weight:700 !important;margin-bottom:10px !important}

  /* Tabelid: horisontaalselt keritav 768-641px vahemikus */
  .vcp-table-wrap{border-radius:10px !important;overflow:hidden !important}
  .vcp-table{font-size:12.5px !important;display:block !important;overflow-x:auto !important;-webkit-overflow-scrolling:touch !important}
  .vcp-table thead,.vcp-table tbody{display:table !important;width:100% !important}
  .vcp-table th{font-size:10px !important;padding:8px 10px !important;white-space:nowrap !important}
  .vcp-table td{font-size:12.5px !important;padding:9px 10px !important}

  /* Nupud */
  .vcp-quick-actions{gap:8px !important;flex-wrap:wrap !important}
  .vcp-btn-primary,.vcp-btn-outline,.vcp-btn-danger{
    padding:11px 16px !important;font-size:13px !important;border-radius:8px !important;
    display:inline-flex !important;align-items:center !important;
  }

  /* Login */
  .vcp-login-card{padding:28px 20px !important}
  .vcp-login-title{font-size:16px !important}
  .vcp-input{font-size:16px !important}

  /* Tühi olek */
  .vcp-empty{padding:28px 16px !important;font-size:13px !important}
}

/* ── 640px: tabelid → kaartideks ── */
@media(max-width:640px){
  .vcp-table{overflow-x:visible !important;display:block !important}
  .vcp-table thead{display:none !important}
  .vcp-table-wrap{background:transparent !important;box-shadow:none !important;border-radius:0 !important}
  .vcp-table tbody{display:flex !important;flex-direction:column !important;gap:8px !important}
  .vcp-table tbody tr{
    display:block !important;
    background:#fff !important;
    border-radius:12px !important;
    box-shadow:0 1px 4px rgba(0,0,0,.08) !important;
    overflow:hidden !important;
    border-bottom:none !important;
  }
  .vcp-table tbody tr:nth-child(even){background:#fff !important}
  .vcp-table tbody tr:hover{background:#f8faff !important}
  .vcp-table tbody td{
    display:flex !important;
    align-items:center !important;
    justify-content:space-between !important;
    gap:10px !important;
    padding:10px 14px !important;
    border-bottom:1px solid #f1f5f9 !important;
    font-size:13px !important;
    min-height:42px !important;
  }
  .vcp-table tbody td:last-child{border-bottom:none !important}
  .vcp-table tbody td::before{
    content:attr(data-label) !important;
    font-size:10px !important;font-weight:700 !important;
    color:#94a3b8 !important;
    text-transform:uppercase !important;letter-spacing:.07em !important;
    flex-shrink:0 !important;min-width:70px !important;
  }
  .vcp-table tbody td[data-label=""]::before{display:none !important}
  .vcp-table tbody td[data-label=""]{justify-content:flex-end !important}
  /* Peidame toimingute veergu mobiilil */
  .vcp-dev-history-row{display:none !important}
}

/* ── 480px ── */
@media(max-width:480px){
  .vcp-content{padding:12px 12px calc(76px + env(safe-area-inset-bottom)) !important}
  .vcp-stat-card{padding:12px 10px !important}
  .vcp-stat-value{font-size:1.35rem !important}
  .vcp-page-header h1{font-size:18px !important}
  .vcp-btn-primary,.vcp-btn-outline,.vcp-btn-danger{
    width:100% !important;justify-content:center !important;
  }
  .vcp-quick-actions{flex-direction:column !important}
  .vcp-card{padding:12px !important}
  .vcp-login-card{padding:24px 16px !important}
  .vcp-empty{padding:20px 14px !important}
}

/* ── 360px ── */
@media(max-width:360px){
  .vcp-content{padding:10px 10px calc(72px + env(safe-area-inset-bottom)) !important}
  .vcp-stat-value{font-size:1.2rem !important}
  .vcp-stat-card{padding:10px 8px !important}
  .vcp-page-header h1{font-size:16px !important}
  .vcp-login-card{padding:20px 12px !important}
  .vcp-nav-link{font-size:9px !important;gap:3px !important}
  .vcp-nav-icon{font-size:19px !important}
}
';
    }

    // ── Public price list shortcode ───────────────────────────────────────────

    public static function shortcode_price_list( $atts ) {
        global $wpdb;
        $atts = shortcode_atts(['category' => ''], $atts);
        $vat_rate = (float) get_option('vesho_vat_rate', '24');

        $where = "visible_public=1 AND active=1";
        if ($atts['category']) {
            $where .= $wpdb->prepare(' AND category=%s', $atts['category']);
        }
        $items = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}vesho_price_list WHERE $where ORDER BY sort_order ASC, category ASC, name ASC"
        );

        // Active campaign discount
        $today = date('Y-m-d');
        $campaign = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}vesho_campaigns
             WHERE paused=0
               AND (valid_from IS NULL OR valid_from <= '$today')
               AND (valid_until IS NULL OR valid_until >= '$today')
               AND (target='hooldus' OR target='both')
             ORDER BY discount_percent DESC LIMIT 1"
        );
        $camp_discount = $campaign ? (float)$campaign->discount_percent : 0;

        ob_start();
        ?>
        <div id="vesho-pricelist" style="font-family:inherit">
        <?php if ($vat_rate > 0) : ?>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:20px;flex-wrap:wrap">
            <span style="font-size:13px;color:#6b7280">Näita hinnad:</span>
            <div style="display:flex;gap:0;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden">
                <button onclick="veshoPLToggle(0)" id="pl-btn-excl" class="pl-toggle-btn active"
                        style="padding:6px 14px;border:none;background:#00b4c8;color:#fff;font-size:13px;font-weight:600;cursor:pointer">
                    KM-ta
                </button>
                <button onclick="veshoPLToggle(1)" id="pl-btn-incl"
                        style="padding:6px 14px;border:none;background:#f1f5f9;color:#64748b;font-size:13px;font-weight:600;cursor:pointer">
                    KM-ga
                </button>
            </div>
            <span style="font-size:12px;color:#94a3b8">KM <?php echo $vat_rate; ?>%</span>
        </div>
        <?php endif; ?>

        <?php if ($camp_discount > 0 && $campaign) : ?>
        <div style="background:linear-gradient(135deg,#10b981,#059669);color:#fff;border-radius:10px;padding:12px 18px;margin-bottom:20px;display:flex;align-items:center;gap:10px">
            <span style="font-size:22px">🎉</span>
            <div>
                <strong><?php echo esc_html($campaign->name); ?></strong> — <?php echo floatval($camp_discount); ?>% allahindlus kõigil teenustel
                <?php if ($campaign->valid_until) echo '<br><small style="opacity:.8">Kehtib kuni '.date('d.m.Y',strtotime($campaign->valid_until)).'</small>'; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php
        $categories = [];
        foreach ($items as $it) {
            $cat = $it->category ?: 'Teenused';
            $categories[$cat][] = $it;
        }
        foreach ($categories as $cat => $cat_items) :
        ?>
        <?php if (count($categories) > 1) : ?>
        <h3 style="font-size:15px;font-weight:700;color:#1e293b;border-bottom:2px solid #e2e8f0;padding-bottom:8px;margin:20px 0 12px"><?php echo esc_html($cat); ?></h3>
        <?php endif; ?>
        <div style="display:grid;gap:10px">
        <?php foreach ($cat_items as $it) :
            $price_excl = (float)$it->price;
            $price_incl = $price_excl * (1 + $it->vat_rate / 100);
            $camp_excl  = $camp_discount > 0 ? $price_excl * (1 - $camp_discount/100) : 0;
            $camp_incl  = $camp_excl > 0 ? $camp_excl * (1 + $it->vat_rate / 100) : 0;
        ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;gap:12px">
            <div style="flex:1">
                <div style="font-weight:600;color:#1e293b;font-size:14px"><?php echo esc_html($it->name); ?></div>
                <?php if ($it->description) : ?>
                <div style="font-size:12px;color:#6b7280;margin-top:2px"><?php echo esc_html($it->description); ?></div>
                <?php endif; ?>
            </div>
            <div style="text-align:right;white-space:nowrap">
                <?php if ($camp_discount > 0 && $camp_excl > 0) : ?>
                <div class="pl-price-excl" style="font-size:12px;color:#9ca3af;text-decoration:line-through">
                    <?php echo number_format($price_excl, 2, ',', ' '); ?> €
                </div>
                <div class="pl-price-incl" style="font-size:12px;color:#9ca3af;text-decoration:line-through;display:none">
                    <?php echo number_format($price_incl, 2, ',', ' '); ?> €
                </div>
                <div class="pl-price-excl" style="font-weight:700;color:#10b981;font-size:15px">
                    <?php echo number_format($camp_excl, 2, ',', ' '); ?> € / <?php echo esc_html($it->unit); ?>
                </div>
                <div class="pl-price-incl" style="font-weight:700;color:#10b981;font-size:15px;display:none">
                    <?php echo number_format($camp_incl, 2, ',', ' '); ?> € / <?php echo esc_html($it->unit); ?>
                </div>
                <div style="font-size:11px;color:#10b981;font-weight:600">-<?php echo floatval($camp_discount); ?>%</div>
                <?php else : ?>
                <div class="pl-price-excl" style="font-weight:700;color:#1e293b;font-size:15px">
                    <?php echo number_format($price_excl, 2, ',', ' '); ?> € / <?php echo esc_html($it->unit); ?>
                </div>
                <div class="pl-price-incl" style="font-weight:700;color:#1e293b;font-size:15px;display:none">
                    <?php echo number_format($price_incl, 2, ',', ' '); ?> € / <?php echo esc_html($it->unit); ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        </div>
        <?php if ($vat_rate > 0) : ?>
        <script>
        function veshoPLToggle(incl) {
            document.querySelectorAll('.pl-price-excl').forEach(function(el){el.style.display=incl?'none':'';});
            document.querySelectorAll('.pl-price-incl').forEach(function(el){el.style.display=incl?'':'none';});
            document.getElementById('pl-btn-excl').style.background=incl?'#f1f5f9':'#00b4c8';
            document.getElementById('pl-btn-excl').style.color=incl?'#64748b':'#fff';
            document.getElementById('pl-btn-incl').style.background=incl?'#00b4c8':'#f1f5f9';
            document.getElementById('pl-btn-incl').style.color=incl?'#fff':'#64748b';
        }
        </script>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    // ── AJAX: Return request ──────────────────────────────────────────────────

    public static function ajax_client_return_request() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $client = self::get_current_client();
        if (!$client) wp_send_json_error(['message' => 'Pole sisse logitud']);

        global $wpdb;
        $order_id    = absint($_POST['order_id'] ?? 0);
        $reason      = sanitize_text_field($_POST['reason'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');

        if (!$reason) wp_send_json_error(['message' => 'Tagastuse põhjus on kohustuslik']);

        // Verify order belongs to client and is within 14 days
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_shop_orders WHERE id=%d AND client_id=%d",
            $order_id, $client->id
        ));
        if (!$order) wp_send_json_error(['message' => 'Tellimust ei leitud']);

        $days_ago = $order->created_at ? (time() - strtotime($order->created_at)) / 86400 : 99;
        if ($days_ago > 14) wp_send_json_error(['message' => '14-päeva tagastusperiood on lõppenud']);

        if (!in_array($order->status, ['fulfilled', 'shipped'], true)) {
            wp_send_json_error(['message' => 'Selle tellimuse staatus ei luba tagastust']);
        }

        $update_data = [
            'return_reason'      => $reason,
            'return_description' => $description,
            'status'             => 'return_requested',
        ];

        // Handle optional photo upload
        if (!empty($_FILES['return_photo']['name'])) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $upload = wp_handle_upload($_FILES['return_photo'], ['test_form' => false]);
            if (!empty($upload['url'])) {
                $update_data['return_photo_url'] = $upload['url'];
            }
        }

        $wpdb->update(
            $wpdb->prefix . 'vesho_shop_orders',
            $update_data,
            ['id' => $order_id]
        );
        wp_send_json_success(['message' => 'Tagastustaotlus esitatud']);
    }

    // ── Cancel order ──────────────────────────────────────────────────────────

    public static function ajax_cancel_order() {
        check_ajax_referer('vesho_portal_nonce', 'nonce');
        $client = self::get_current_client();
        if (!$client) wp_send_json_error(['message' => 'Pole sisse logitud']);

        global $wpdb;
        $order_id = absint($_POST['order_id'] ?? 0);

        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_shop_orders WHERE id=%d AND (client_id=%d OR guest_email=%s)",
            $order_id, $client->id, $client->email
        ));

        if (!$order) wp_send_json_error(['message' => 'Tellimust ei leitud']);

        if (!in_array($order->status, ['new', 'pending_payment'], true)) {
            wp_send_json_error(['message' => 'Seda tellimust ei saa enam tühistada (komplekteerimine on alanud)']);
        }

        $wpdb->update(
            $wpdb->prefix . 'vesho_shop_orders',
            ['status' => 'cancelled'],
            ['id' => $order_id]
        );

        // Notify admin
        $co    = get_option('vesho_company_name', get_bloginfo('name'));
        $admin = get_option('vesho_notify_email', get_option('admin_email'));
        if ($admin) {
            wp_mail(
                $admin,
                "[{$co}] Tellimus tühistatud kliendi poolt",
                "Klient {$client->name} tühistas tellimuse #{$order->order_number}."
            );
        }

        wp_send_json_success(['message' => 'Tellimus tühistatud']);
    }

    // ── [vesho_shop] shortcode ────────────────────────────────────────────────
    public static function shortcode_shop_OLD_REMOVED($atts) { // placeholder — replaced below
        global $wpdb;
        // Handle payment return URL params
        $shop_view       = 'shop';
        $return_order_id = absint($_GET['order_id'] ?? 0);
        if (!empty($_GET['vesho_shop_mc_return']))       $shop_view = 'success';
        elseif (!empty($_GET['vesho_shop_montonio_return'])) $shop_view = 'success';
        elseif (!empty($_GET['shop_view']) && $_GET['shop_view'] === 'cancel') $shop_view = 'cancelled';

        // Products
        $products = $wpdb->get_results(
            "SELECT id, name, category, shop_price, shop_description, quantity, unit
             FROM {$wpdb->prefix}vesho_inventory
             WHERE shop_enabled=1 AND shop_price>0 AND archived=0
             ORDER BY category ASC, name ASC"
        );

        // Categories
        $categories = array_values(array_unique(array_filter(array_column($products, 'category'))));

        // Active shop campaign
        $today    = date('Y-m-d');
        $campaign = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}vesho_campaigns
             WHERE paused=0
               AND (valid_from IS NULL OR valid_from <= '$today')
               AND (valid_until IS NULL OR valid_until >= '$today')
               AND (target='epood' OR target='both')
             ORDER BY discount_percent DESC LIMIT 1"
        );

        // Client
        $client          = self::get_current_client();
        $client_discount = $client ? (float)($client->loyalty_pct ?? 0) : 0;

        // Payment settings
        $stripe_enabled  = get_option('vesho_stripe_enabled', '0') === '1' && get_option('vesho_stripe_pub_key', '') && get_option('vesho_stripe_secret_key', '');
        $stripe_pub_key  = get_option('vesho_stripe_pub_key', '');
        $mc_enabled      = get_option('vesho_mc_enabled', '0') === '1' && get_option('vesho_mc_shop_id', '') && get_option('vesho_mc_secret_key', '');
        $montonio_enabled= get_option('vesho_montonio_enabled', '0') === '1' && get_option('vesho_montonio_access_key', '') && get_option('vesho_montonio_secret_key', '');

        // Delivery options
        $del_opts = [];
        if (get_option('vesho_shop_ship_pickup_enabled',  '1') !== '0')
            $del_opts[] = ['id'=>'pickup',  'label'=>'Kaupluses pealekorjamine', 'icon'=>'🏪', 'price'=>(float)get_option('vesho_shop_ship_pickup_price',      '0')];
        if (get_option('vesho_shop_ship_courier_enabled', '1') !== '0')
            $del_opts[] = ['id'=>'courier', 'label'=>'Kuller',                   'icon'=>'🚚', 'price'=>(float)get_option('vesho_shop_ship_courier_price',     '0')];
        if (get_option('vesho_shop_ship_parcelshop_enabled', '1') !== '0')
            $del_opts[] = ['id'=>'omniva',  'label'=>'Pakiautomaat (Omniva/DPD)','icon'=>'📦', 'price'=>(float)get_option('vesho_shop_ship_parcelshop_price', '0')];
        if (empty($del_opts))
            $del_opts[] = ['id'=>'pickup',  'label'=>'Pealekorjamine',           'icon'=>'🏪', 'price'=>0.0];

        // Return-URL order for success display
        $return_order = null;
        if ($return_order_id && $shop_view === 'success') {
            $return_order = $wpdb->get_row($wpdb->prepare(
                "SELECT order_number, total FROM {$wpdb->prefix}vesho_shop_orders WHERE id=%d",
                $return_order_id
            ));
        }

        $nonce = wp_create_nonce('vesho_cart_nonce');

        // JS config object
        $cfg = wp_json_encode([
            'ajaxurl'          => admin_url('admin-ajax.php'),
            'nonce'            => $nonce,
            'stripeEnabled'    => (bool)$stripe_enabled,
            'stripePubKey'     => (string)$stripe_pub_key,
            'mcEnabled'        => $mc_enabled,
            'montonioEnabled'  => $montonio_enabled,
            'clientDiscount'   => $client_discount,
            'clientLoggedIn'   => (bool)$client,
            'clientName'       => $client ? $client->name : '',
            'clientEmail'      => $client ? $client->email : '',
            'clientPhone'      => $client ? ($client->phone ?? '') : '',
            'campDiscount'     => $campaign ? (float)$campaign->discount_percent : 0,
            'campName'         => $campaign ? $campaign->name : '',
            'campFreeShip'     => $campaign ? (bool)$campaign->free_shipping : false,
            'campGuests'       => $campaign ? (bool)$campaign->visible_to_guests : false,
            'deliveryOptions'  => $del_opts,
            'initialView'      => $shop_view,
            'returnOrder'      => $return_order,
        ]);

        ob_start();
        ?>
<style>
#vesho-shop-root{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#1e293b;line-height:1.5}
#vesho-shop-root *{box-sizing:border-box}
.vshop-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px;padding:24px}
.vshop-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;display:flex;flex-direction:column;transition:all .2s;cursor:default}
.vshop-card:hover{transform:translateY(-3px);box-shadow:0 12px 32px rgba(0,0,0,.1);border-color:#cbd5e1}
.vshop-card-img{height:140px;display:flex;align-items:center;justify-content:center;font-size:64px;background:linear-gradient(135deg,#f1f5f9,#e2e8f0)}
.vshop-card-body{padding:16px;flex:1;display:flex;flex-direction:column;gap:8px}
.vshop-card-name{font-weight:700;font-size:14px;color:#1e293b}
.vshop-card-desc{font-size:12px;color:#6b7280;flex:1}
.vshop-card-price{font-size:20px;font-weight:800;color:#1e293b}
.vshop-card-footer{padding:0 16px 16px;display:flex;align-items:center;justify-content:space-between;gap:8px}
.vshop-add-btn{background:#00b4c8;color:#fff;border:none;border-radius:10px;padding:9px 18px;font-size:13px;font-weight:700;cursor:pointer;flex:1;transition:background .15s}
.vshop-add-btn:hover{background:#009ab2}
.vshop-add-btn:disabled{background:#94a3b8;cursor:not-allowed}
.vshop-qty-ctrl{display:flex;align-items:center;gap:6px;flex:1;justify-content:space-between}
.vshop-qty-ctrl button{width:32px;height:32px;border:1px solid #e2e8f0;border-radius:8px;background:#f8fafc;cursor:pointer;font-size:16px;font-weight:700;color:#475569;transition:all .15s;display:flex;align-items:center;justify-content:center}
.vshop-qty-ctrl button:hover{background:#00b4c8;color:#fff;border-color:#00b4c8}
.vshop-qty-ctrl span{font-size:16px;font-weight:700;color:#1e293b;min-width:24px;text-align:center}
.vshop-fab{position:fixed;bottom:28px;right:28px;width:60px;height:60px;border-radius:50%;background:#00b4c8;color:#fff;border:none;cursor:pointer;font-size:24px;box-shadow:0 4px 20px rgba(0,180,200,.4);z-index:9990;display:flex;align-items:center;justify-content:center;transition:transform .15s}
.vshop-fab:hover{transform:scale(1.08)}
.vshop-fab-badge{position:absolute;top:-4px;right:-4px;background:#ef4444;color:#fff;border-radius:50%;width:22px;height:22px;font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center;border:2px solid #fff}
.vshop-sidebar-bg{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:9991;opacity:0;transition:opacity .25s}
.vshop-sidebar{position:fixed;top:0;right:0;bottom:0;width:min(420px,100vw);background:#fff;z-index:9992;display:flex;flex-direction:column;transform:translateX(100%);transition:transform .3s cubic-bezier(.4,0,.2,1);box-shadow:-4px 0 40px rgba(0,0,0,.15)}
.vshop-overlay{position:fixed;inset:0;background:#f8fafc;z-index:9993;overflow-y:auto;display:flex;align-items:flex-start;justify-content:center;padding:32px 16px}
.vshop-overlay-inner{width:100%;max-width:480px}
.vshop-inp{width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;color:#1e293b;outline:none;background:#f8fafc;transition:border .15s}
.vshop-inp:focus{border-color:#00b4c8;background:#fff}
.vshop-btn-acc{background:#00b4c8;color:#fff;border:none;border-radius:12px;padding:13px 20px;font-size:15px;font-weight:700;cursor:pointer;transition:background .15s;display:flex;align-items:center;justify-content:center;gap:8px}
.vshop-btn-acc:hover:not(:disabled){background:#009ab2}
.vshop-btn-acc:disabled{opacity:.6;cursor:not-allowed}
.vshop-btn-gray{background:#f1f5f9;color:#475569;border:none;border-radius:12px;padding:13px 18px;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s}
.vshop-btn-gray:hover{background:#e2e8f0}
.vshop-step-bar{display:flex;align-items:center;justify-content:center;gap:0;margin-bottom:28px}
.vshop-err{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);border-radius:10px;padding:10px 14px;color:#dc2626;font-size:13px;margin-bottom:14px}
.vshop-cat-btn{padding:7px 16px;border-radius:20px;border:1px solid #e2e8f0;background:#fff;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s;white-space:nowrap}
.vshop-cat-btn.active,.vshop-cat-btn:hover{background:#00b4c8;color:#fff;border-color:#00b4c8}
@media(max-width:600px){.vshop-grid{grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;padding:16px}.vshop-card-img{height:100px;font-size:48px}.vshop-overlay{padding:16px 8px}}
</style>
<div id="vesho-shop-root">

<?php if ($campaign && ($campaign->target === 'epood' || $campaign->target === 'both')) : ?>
<div style="background:linear-gradient(135deg,rgba(0,180,200,.12),rgba(168,85,247,.1));border-bottom:1px solid rgba(0,180,200,.25);padding:12px 24px;text-align:center">
  <span style="font-size:18px;margin-right:8px">🎉</span>
  <strong style="font-size:14px"><?php echo esc_html($campaign->name); ?></strong>
  <?php if ($campaign->description) : ?><span style="color:#6b7280;font-size:13px;margin-left:8px"><?php echo esc_html($campaign->description); ?></span><?php endif; ?>
  <?php if ($campaign->discount_percent > 0) : ?>
  <span style="margin-left:10px;background:rgba(0,180,200,.2);color:#007a8c;font-weight:700;font-size:13px;padding:2px 10px;border-radius:20px">-<?php echo (float)$campaign->discount_percent; ?>%</span>
  <?php elseif ($campaign->free_shipping) : ?>
  <span style="margin-left:10px;background:rgba(16,185,129,.15);color:#059669;font-weight:700;font-size:13px;padding:2px 10px;border-radius:20px">Tasuta tarne</span>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Hero -->
<div style="background:#fff;border-bottom:1px solid #e2e8f0;padding:40px 24px 32px">
  <div style="max-width:1160px;margin:0 auto">
    <div style="font-size:11px;font-weight:700;color:#00b4c8;letter-spacing:.15em;text-transform:uppercase;margin-bottom:10px"><?php echo esc_html(get_option('vesho_shop_hero_badge','Vesho E-pood')); ?></div>
    <h1 style="font-size:clamp(1.6rem,3.5vw,2.4rem);font-weight:900;color:#0f172a;letter-spacing:-.03em;margin:0 0 10px"><?php echo esc_html(get_option('vesho_shop_hero_title','Varuosad ja materjalid')); ?></h1>
    <p style="color:#6b7280;font-size:14px;margin:0 0 20px;max-width:480px"><?php echo esc_html(get_option('vesho_shop_hero_desc','Kvaliteetsed tooted kiire tarnega. Registreeritud klientidele eripakkumised.')); ?></p>
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
      <div style="position:relative;max-width:340px;width:100%">
        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px;pointer-events:none">🔍</span>
        <input id="vshop-search" type="text" placeholder="Otsi tooteid..." style="width:100%;padding:10px 12px 10px 36px;border:1px solid #e2e8f0;border-radius:10px;font-size:14px;outline:none;background:#f8fafc" oninput="veshoShop.onSearch(this.value)">
      </div>
      <?php if ($client_discount > 0) : ?>
      <div style="display:inline-flex;align-items:center;gap:6px;background:rgba(0,180,200,.08);border:1px solid rgba(0,180,200,.2);border-radius:8px;padding:7px 14px;font-size:12px;color:#007a8c;font-weight:600">
        🎁 Kliendikaardi allahindlus: <strong><?php echo $client_discount; ?>%</strong>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Category tabs -->
<?php if (!empty($categories)) : ?>
<div style="background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:12px 24px;overflow-x:auto">
  <div style="display:flex;gap:8px;max-width:1160px;margin:0 auto;min-width:max-content">
    <button class="vshop-cat-btn active" onclick="veshoShop.onCategory(null,this)" data-cat="">Kõik</button>
    <?php foreach ($categories as $cat) : ?>
    <button class="vshop-cat-btn" onclick="veshoShop.onCategory('<?php echo esc_js($cat); ?>',this)" data-cat="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></button>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Product grid -->
<div class="vshop-grid" id="vshop-grid">
<?php
$icons = ['🔧','⚙️','🔩','🛠️','💡','🔌','❄️','🌡️','🔑','🪛'];
foreach ($products as $i => $p) :
    $icon = $icons[$i % count($icons)];
    $in_stock = (int)$p->quantity > 0;
?>
<div class="vshop-card" data-pid="<?php echo $p->id; ?>" data-name="<?php echo esc_attr(strtolower($p->name . ' ' . ($p->shop_description ?? ''))); ?>" data-cat="<?php echo esc_attr($p->category); ?>">
  <div class="vshop-card-img"><?php echo $icon; ?></div>
  <div class="vshop-card-body">
    <?php if ($p->category) : ?><div style="font-size:11px;color:#00b4c8;font-weight:700;letter-spacing:.05em;text-transform:uppercase;margin-bottom:2px"><?php echo esc_html($p->category); ?></div><?php endif; ?>
    <div class="vshop-card-name"><?php echo esc_html($p->name); ?></div>
    <?php if ($p->shop_description) : ?><div class="vshop-card-desc"><?php echo esc_html($p->shop_description); ?></div><?php endif; ?>
    <?php if (!$in_stock) : ?><div style="font-size:11px;color:#ef4444;font-weight:600">Laost otsas</div><?php endif; ?>
    <div class="vshop-card-price"><?php echo number_format((float)$p->shop_price, 2, '.', ''); ?> €</div>
  </div>
  <div class="vshop-card-footer">
    <div id="vshop-ctrl-<?php echo $p->id; ?>" style="flex:1">
      <button class="vshop-add-btn" <?php if (!$in_stock) echo 'disabled'; ?> onclick="veshoShop.addToCart(<?php echo $p->id; ?>,'<?php echo esc_js($p->name); ?>',<?php echo (float)$p->shop_price; ?>,<?php echo (int)$p->quantity; ?>)"><?php echo $in_stock ? '+ Lisa korvi' : 'Otsas'; ?></button>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php if (empty($products)) : ?>
<div style="grid-column:1/-1;text-align:center;padding:60px 20px;color:#94a3b8">
  <div style="font-size:48px;margin-bottom:12px">🛒</div>
  <div style="font-size:16px;font-weight:600">Poodi pole veel tooteid lisatud</div>
</div>
<?php endif; ?>
</div>
<div id="vshop-no-results" style="display:none;text-align:center;padding:60px 20px;color:#94a3b8">
  <div style="font-size:48px;margin-bottom:12px">🔍</div>
  <div style="font-size:16px;font-weight:600">Otsingule vastavaid tooteid ei leitud</div>
</div>

<!-- Cart FAB -->
<button class="vshop-fab" id="vshop-fab" onclick="veshoShop.openCart()" style="display:none">
  🛒
  <span class="vshop-fab-badge" id="vshop-fab-badge">0</span>
</button>

<!-- Overlays container -->
<div id="vshop-overlay-root"></div>

</div><!-- #vesho-shop-root -->

<script>
(function(){
var CFG = <?php echo $cfg; ?>;
var ICONS=['🔧','⚙️','🔩','🛠️','💡','🔌','❄️','🌡️','🔑','🪛'];
var MC_BANKS=[
  {id:'swedbank',  name:'Swedbank',  color:'#f36b00'},
  {id:'seb',       name:'SEB',       color:'#60b33c'},
  {id:'lhv',       name:'LHV',       color:'#f5a800'},
  {id:'luminor',   name:'Luminor',   color:'#c00000'},
  {id:'coop',      name:'Coop',      color:'#24a148'},
  {id:'bigbank',   name:'Bigbank',   color:'#c0392b'},
  {id:'inbank',    name:'Inbank',    color:'#7c3aed'},
  {id:'revolut',   name:'Revolut',   color:'#191c20'},
];

// ── State ──────────────────────────────────────────────────────────────────
var cart = [];
var cartSidebarOpen = false;
var deliveryMethod = CFG.deliveryOptions[0] ? CFG.deliveryOptions[0].id : 'pickup';
var deliveryAddress = '';
var deliveryPhone = CFG.clientPhone || '';
var guestName = '', guestEmail = '', guestPhone = '';
var pendingOrder = null;
var paymentError = '';
var paymentLoading = false;
var checkoutMode = CFG.stripeEnabled ? 'stripe' : (CFG.mcEnabled ? 'mc' : (CFG.montonioEnabled ? 'montonio' : 'stripe'));
var stripeInst = null, stripeElems = null, stripeCard = null;
var mcBanks = [];
var view = 'shop'; // shop | cart | guest | delivery | payment | mc-banks | success | cancelled

// Load cart from localStorage
function loadCart(){
  try{ var s=JSON.parse(localStorage.getItem('vesho_cart')||'[]'); if(Array.isArray(s)) cart=s; }catch(e){}
}
function saveCart(){
  localStorage.setItem('vesho_cart',JSON.stringify(cart));
  updateFab();
}
function cartTotal(){
  var disc = effectiveDiscount();
  return cart.reduce(function(s,c){ return s + applyDisc(c.price,disc)*c.qty; },0);
}
function cartOrig(){
  return cart.reduce(function(s,c){ return s + c.price*c.qty; },0);
}
function effectiveDiscount(){
  return Math.max(CFG.clientDiscount || 0, CFG.campGuests || CFG.clientLoggedIn ? CFG.campDiscount : 0);
}
function applyDisc(p,d){ return d>0 ? p*(1-d/100) : p; }
function cartCount(){ return cart.reduce(function(s,c){ return s+c.qty; },0); }

function updateFab(){
  var fab=document.getElementById('vshop-fab');
  var badge=document.getElementById('vshop-fab-badge');
  if(!fab) return;
  var n=cartCount();
  fab.style.display = n>0 ? 'flex' : 'none';
  if(badge) badge.textContent=n;
}

function updateCardCtrls(){
  cart.forEach(function(item){
    var el=document.getElementById('vshop-ctrl-'+item.pid);
    if(!el) return;
    el.innerHTML='<div class="vshop-qty-ctrl">'+
      '<button onclick="veshoShop.setQty('+item.pid+','+(item.qty-1)+')">−</button>'+
      '<span>'+item.qty+'</span>'+
      '<button onclick="veshoShop.setQty('+item.pid+','+(item.qty+1)+')">+</button>'+
      '</div>';
  });
  // Reset cards not in cart
  var cards=document.querySelectorAll('#vshop-grid .vshop-card');
  cards.forEach(function(card){
    var pid=parseInt(card.dataset.pid);
    var inCart=cart.find(function(c){ return c.pid===pid; });
    if(!inCart){
      var ctrl=document.getElementById('vshop-ctrl-'+pid);
      if(ctrl && ctrl.querySelector('.vshop-qty-ctrl')){
        var btn=ctrl.querySelector('.vshop-add-btn');
        if(!btn){
          ctrl.innerHTML='<button class="vshop-add-btn" onclick="veshoShop.restoreAdd('+pid+')">+ Lisa korvi</button>';
        }
      }
    }
  });
}

// ── Search / filter ────────────────────────────────────────────────────────
var currentSearch='', currentCat='';
function filterProducts(){
  var cards=document.querySelectorAll('#vshop-grid .vshop-card');
  var visible=0;
  cards.forEach(function(card){
    var name=card.dataset.name||'';
    var cat=card.dataset.cat||'';
    var matchS=!currentSearch||name.includes(currentSearch.toLowerCase());
    var matchC=!currentCat||cat===currentCat;
    card.style.display=(matchS&&matchC)?'':'none';
    if(matchS&&matchC) visible++;
  });
  var noRes=document.getElementById('vshop-no-results');
  if(noRes) noRes.style.display=visible===0?'block':'none';
}

// ── Cart sidebar ───────────────────────────────────────────────────────────
function renderCartSidebar(){
  var root=document.getElementById('vshop-overlay-root');
  if(!root) return;
  var disc=effectiveDiscount();
  var total=cartTotal();
  var orig=cartOrig();
  var saved=orig-total;
  var delivOption=CFG.deliveryOptions.find(function(d){ return d.id===deliveryMethod; })||{price:0};
  var shipCost=CFG.campFreeShip?0:delivOption.price;
  var grandTotal=total+shipCost;

  var html='<div class="vshop-sidebar-bg" id="vsb-bg" onclick="veshoShop.closeCart()"></div>'+
    '<div class="vshop-sidebar" id="vsb">'+
      '<div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid #e2e8f0">'+
        '<div style="font-size:18px;font-weight:800;color:#0f172a">🛒 Ostukorv</div>'+
        '<button onclick="veshoShop.closeCart()" style="border:none;background:none;font-size:20px;cursor:pointer;color:#94a3b8;padding:4px">✕</button>'+
      '</div>'+
      '<div style="flex:1;overflow-y:auto;padding:16px 24px">';

  if(cart.length===0){
    html+='<div style="text-align:center;padding:48px 0;color:#94a3b8"><div style="font-size:48px;margin-bottom:12px">🛒</div><div style="font-size:15px;font-weight:600">Ostukorv on tühi</div></div>';
  } else {
    cart.forEach(function(item){
      var dp=applyDisc(item.price,disc);
      html+='<div style="display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid #f1f5f9">'+
        '<div style="width:44px;height:44px;border-radius:10px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">'+ICONS[item.pid%ICONS.length]+'</div>'+
        '<div style="flex:1;min-width:0">'+
          '<div style="font-size:13px;font-weight:700;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'+escHtml(item.name)+'</div>'+
          (disc>0?'<div style="font-size:11px;color:#10b981">−'+disc+'% allahindlus</div>':'')+
          '<div style="font-size:13px;color:#00b4c8;font-weight:700;margin-top:2px">'+(dp*item.qty).toFixed(2)+' €</div>'+
        '</div>'+
        '<div class="vshop-qty-ctrl" style="flex-shrink:0">'+
          '<button onclick="veshoShop.setQty('+item.pid+','+(item.qty-1)+')">−</button>'+
          '<span>'+item.qty+'</span>'+
          '<button onclick="veshoShop.setQty('+item.pid+','+(item.qty+1)+')">+</button>'+
        '</div>'+
      '</div>';
    });
  }

  html+='</div>';

  if(cart.length>0){
    html+='<div style="padding:16px 24px;border-top:1px solid #e2e8f0">'+
      (disc>0?'<div style="display:flex;justify-content:space-between;font-size:12px;color:#10b981;margin-bottom:6px"><span>Säästsite</span><span>−'+saved.toFixed(2)+' €</span></div>':'')+
      (CFG.campFreeShip?'<div style="font-size:12px;color:#10b981;margin-bottom:6px">✓ Tasuta tarne ('+escHtml(CFG.campName)+')</div>':
        (shipCost>0?'<div style="display:flex;justify-content:space-between;font-size:12px;color:#6b7280;margin-bottom:6px"><span>Tarne</span><span>'+shipCost.toFixed(2)+' €</span></div>':''))+
      '<div style="display:flex;justify-content:space-between;font-size:17px;font-weight:800;color:#0f172a;margin-bottom:16px"><span>Kokku</span><span>'+grandTotal.toFixed(2)+' €</span></div>'+

      // Payment method selector
      '<div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap">'+
        (CFG.stripeEnabled?'<button onclick="veshoShop.setCheckoutMode(\'stripe\')" id="vsb-mode-stripe" style="flex:1;padding:9px;border:2px solid '+(checkoutMode==='stripe'?'#00b4c8':'#e2e8f0')+';border-radius:10px;background:'+(checkoutMode==='stripe'?'rgba(0,180,200,.08)':'#fff')+';cursor:pointer;font-size:12px;font-weight:700;color:#0f172a">💳 Kaart</button>':'')+
        (CFG.mcEnabled?'<button onclick="veshoShop.setCheckoutMode(\'mc\')" id="vsb-mode-mc" style="flex:1;padding:9px;border:2px solid '+(checkoutMode==='mc'?'#00b4c8':'#e2e8f0')+';border-radius:10px;background:'+(checkoutMode==='mc'?'rgba(0,180,200,.08)':'#fff')+';cursor:pointer;font-size:12px;font-weight:700;color:#0f172a">🏦 Pank</button>':'')+
        (CFG.montonioEnabled?'<button onclick="veshoShop.setCheckoutMode(\'montonio\')" id="vsb-mode-montonio" style="flex:1;padding:9px;border:2px solid '+(checkoutMode==='montonio'?'#00b4c8':'#e2e8f0')+';border-radius:10px;background:'+(checkoutMode==='montonio'?'rgba(0,180,200,.08)':'#fff')+';cursor:pointer;font-size:12px;font-weight:700;color:#0f172a">🏦 Montonio</button>':'')+
      '</div>'+

      '<button class="vshop-btn-acc" style="width:100%" onclick="veshoShop.startCheckout()">Vormista tellimus →</button>'+
    '</div>';
  }

  html+='</div>';

  root.innerHTML=html;
  // Animate in
  setTimeout(function(){
    var bg=document.getElementById('vsb-bg');
    var sb=document.getElementById('vsb');
    if(bg) bg.style.opacity='1';
    if(sb) sb.style.transform='translateX(0)';
  },10);
}

// ── Checkout steps ─────────────────────────────────────────────────────────
function renderStepBar(steps,activeIdx){
  var html='<div class="vshop-step-bar">';
  steps.forEach(function(s,i){
    var done=i<activeIdx, active=i===activeIdx;
    html+='<div style="display:flex;align-items:center">'+
      '<div style="display:flex;flex-direction:column;align-items:center;gap:4px">'+
        '<div style="width:32px;height:32px;border-radius:50%;background:'+(done?'#10b981':active?'#00b4c8':'#f1f5f9')+';border:2px solid '+(done?'#10b981':active?'#00b4c8':'#e2e8f0')+';display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:'+(done||active?'#fff':'#94a3b8')+';transition:all .3s">'+
          (done?'✓':(i+1))+
        '</div>'+
        '<span style="font-size:11px;color:'+(active?'#0f172a':'#94a3b8')+';font-weight:'+(active?700:400)+'">'+s+'</span>'+
      '</div>'+
      (i<steps.length-1?'<div style="width:40px;height:2px;background:'+(done?'#10b981':'#e2e8f0')+';margin:0 4px;margin-bottom:18px;transition:background .3s"></div>':'')+
    '</div>';
  });
  return html+'</div>';
}

function getSteps(){
  return CFG.clientLoggedIn ? ['Tarne','Makse','Kinnitus'] : ['Kontakt','Tarne','Makse','Kinnitus'];
}
function getStepIdx(v){
  if(CFG.clientLoggedIn){
    return {delivery:0,payment:1,'mc-banks':1,success:2}[v]??0;
  }
  return {guest:0,delivery:1,payment:2,'mc-banks':2,success:3}[v]??0;
}

function renderOverlay(contentHtml){
  var root=document.getElementById('vshop-overlay-root');
  if(!root) return;
  root.innerHTML='<div class="vshop-overlay"><div class="vshop-overlay-inner">'+contentHtml+'</div></div>';
}

function renderGuestStep(){
  var steps=getSteps(), idx=getStepIdx('guest');
  var html=renderStepBar(steps,idx)+
    '<div style="text-align:center;margin-bottom:24px"><h2 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0 0 6px">Kontaktandmed</h2><p style="color:#6b7280;font-size:14px;margin:0">Sisesta oma andmed tellimuse kinnitamiseks</p></div>'+
    '<div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:24px;margin-bottom:14px">'+
      '<label style="display:block;font-size:13px;color:#6b7280;margin-bottom:14px">Nimi *<input id="vg-name" class="vshop-inp" style="margin-top:4px;display:block" placeholder="Sinu nimi" value="'+escAttr(guestName)+'" oninput="veshoShop.guestName=this.value"></label>'+
      '<label style="display:block;font-size:13px;color:#6b7280;margin-bottom:14px">E-post *<input id="vg-email" class="vshop-inp" type="email" style="margin-top:4px;display:block" placeholder="sinu@email.ee" value="'+escAttr(guestEmail)+'" oninput="veshoShop.guestEmail=this.value"></label>'+
      '<label style="display:block;font-size:13px;color:#6b7280">Telefon<input id="vg-phone" class="vshop-inp" style="margin-top:4px;display:block" placeholder="+372 5xxx xxxx" value="'+escAttr(guestPhone)+'" oninput="veshoShop.guestPhone=this.value"></label>'+
    '</div>'+
    (paymentError?'<div class="vshop-err">⚠️ '+escHtml(paymentError)+'</div>':'')+
    '<div style="display:flex;gap:10px">'+
      '<button class="vshop-btn-acc" style="flex:1" onclick="veshoShop.nextFromGuest()">Jätka →</button>'+
      '<button class="vshop-btn-gray" onclick="veshoShop.backToCart()">← Tagasi</button>'+
    '</div>';
  renderOverlay(html);
}

function renderDeliveryStep(){
  var steps=getSteps(), idx=getStepIdx('delivery');
  var html=renderStepBar(steps,idx)+
    '<div style="text-align:center;margin-bottom:24px"><h2 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0 0 6px">Tarneinfo</h2><p style="color:#6b7280;font-size:14px;margin:0">Vali tarnimisviis</p></div>'+
    '<div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:24px;margin-bottom:14px">';

  if(CFG.clientLoggedIn){
    html+='<label style="display:block;font-size:13px;color:#6b7280;margin-bottom:16px">Telefoninumber<input id="vd-phone" class="vshop-inp" style="margin-top:4px;display:block" placeholder="+372 5xxx xxxx" value="'+escAttr(deliveryPhone)+'" oninput="veshoShop.deliveryPhone=this.value"></label>';
  }

  html+='<div style="font-size:13px;color:#6b7280;margin-bottom:8px">Tarnimisviis *</div><div style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px">';
  CFG.deliveryOptions.forEach(function(opt){
    var sel=deliveryMethod===opt.id;
    var priceStr=opt.price===0?'<span style="color:#10b981;font-weight:700">Tasuta</span>':opt.price.toFixed(2)+' €';
    html+='<button onclick="veshoShop.setDelivery(\''+opt.id+'\')" style="display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:12px;border:2px solid '+(sel?'#00b4c8':'#e2e8f0')+';background:'+(sel?'rgba(0,180,200,.06)':'#f8fafc')+';cursor:pointer;text-align:left;transition:all .15s">'+
      '<span style="font-size:22px">'+opt.icon+'</span>'+
      '<div style="flex:1"><div style="font-size:14px;font-weight:600;color:#0f172a">'+escHtml(opt.label)+'</div></div>'+
      '<div style="font-size:13px;font-weight:700;flex-shrink:0">'+priceStr+'</div>'+
      (sel?'<span style="color:#00b4c8;font-size:18px;margin-left:4px">✓</span>':'')+
    '</button>';
  });
  html+='</div>';

  if(deliveryMethod!=='pickup'){
    html+='<label style="display:block;font-size:13px;color:#6b7280">'+(deliveryMethod==='omniva'?'Pakiautomaadi nimi *':'Tarne aadress *')+'<input id="vd-addr" class="vshop-inp" style="margin-top:4px;display:block" placeholder="'+(deliveryMethod==='omniva'?'nt Omniva Tallinn Viru':'Tänav, maja, linn')+'" value="'+escAttr(deliveryAddress)+'" oninput="veshoShop.deliveryAddress=this.value"></label>';
  }

  html+='</div>'+
    (paymentError?'<div class="vshop-err">⚠️ '+escHtml(paymentError)+'</div>':'')+
    '<div style="display:flex;gap:10px">'+
      '<button class="vshop-btn-acc" style="flex:1" '+(paymentLoading?'disabled':'')+' onclick="veshoShop.proceedToPayment()">'+(paymentLoading?'⏳ Laadin...':'Jätka →')+'</button>'+
      '<button class="vshop-btn-gray" onclick="veshoShop.'+(CFG.clientLoggedIn?'backToCart':'gotoGuest')+'()">← Tagasi</button>'+
    '</div>';
  renderOverlay(html);
}

function renderPaymentStep(){
  var steps=getSteps(), idx=getStepIdx('payment');
  var disc=effectiveDiscount();
  var html=renderStepBar(steps,idx)+
    '<div style="text-align:center;margin-bottom:24px"><h2 style="font-size:1.4rem;font-weight:800;color:#0f172a;margin:0 0 6px">Makseandmed</h2><p style="color:#6b7280;font-size:14px;margin:0">Tellimus <strong>#'+escHtml(pendingOrder.order_number)+'</strong></p></div>'+
    // Order summary
    '<div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px 20px;margin-bottom:16px">'+
      '<div style="font-size:11px;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;font-weight:700;margin-bottom:10px">Tellimuse kokkuvõte</div>';

  cart.forEach(function(c){
    var dp=applyDisc(c.price,disc);
    html+='<div style="display:flex;justify-content:space-between;font-size:13px;padding:5px 0;border-bottom:1px solid #f1f5f9;color:#475569">'+
      '<span style="color:#0f172a">'+escHtml(c.name)+' <span style="color:#94a3b8">×'+c.qty+'</span></span>'+
      '<span style="font-weight:600">'+(dp*c.qty).toFixed(2)+' €</span>'+
    '</div>';
  });

  if(pendingOrder.discount_amount > 0.01){
    html+='<div style="display:flex;justify-content:space-between;font-size:12px;color:#10b981;padding:6px 0">'+
      '<span>'+(pendingOrder.campaign_name?'🎉 '+pendingOrder.campaign_name:'Kliendikaardi soodustus ('+disc+'%)')+'</span>'+
      '<span>−'+Number(pendingOrder.discount_amount).toFixed(2)+' €</span>'+
    '</div>';
  }
  if(pendingOrder.shipping_price > 0){
    html+='<div style="display:flex;justify-content:space-between;font-size:13px;color:#6b7280;padding:6px 0">'+
      '<span>Tarne</span><span>'+Number(pendingOrder.shipping_price).toFixed(2)+' €</span>'+
    '</div>';
  }
  html+='<div style="display:flex;justify-content:space-between;padding-top:10px;border-top:1px solid #e2e8f0;margin-top:4px">'+
    '<span style="font-size:15px;font-weight:700;color:#0f172a">Kokku</span>'+
    '<span style="font-size:20px;font-weight:800;color:#0f172a">'+Number(pendingOrder.total).toFixed(2)+' €</span>'+
  '</div></div>'+
  // Stripe mount
  '<div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:20px;margin-bottom:14px">'+
    '<div style="font-size:11px;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;font-weight:700;margin-bottom:14px">Makseandmed</div>'+
    '<div id="vshop-stripe-mount" style="min-height:44px"></div>'+
  '</div>'+
  (paymentError?'<div class="vshop-err">⚠️ '+escHtml(paymentError)+'</div>':'')+
  '<div style="display:flex;gap:10px">'+
    '<button class="vshop-btn-acc" style="flex:1;box-shadow:0 4px 14px rgba(0,180,200,.3)" '+(paymentLoading?'disabled':'')+' onclick="veshoShop.confirmStripe()">'+(paymentLoading?'⏳ Töötlen...':'🔒 Maksa '+Number(pendingOrder.total).toFixed(2)+' €')+'</button>'+
    '<button class="vshop-btn-gray" '+(paymentLoading?'disabled':'')+' onclick="veshoShop.cancelCheckout()">Tühista</button>'+
  '</div>'+
  '<div style="text-align:center;font-size:11px;color:#94a3b8;margin-top:10px">🔒 Makse on krüpteeritud ja turvaline · Stripe</div>';

  renderOverlay(html);

  // Mount Stripe
  setTimeout(function(){
    loadStripeAndMount();
  },120);
}

function renderMcBanksStep(){
  var steps=getSteps(), idx=getStepIdx('mc-banks');
  var html=renderStepBar(steps,idx)+
    '<div style="text-align:center;margin-bottom:24px">'+
      '<div style="font-size:40px;margin-bottom:10px">🏦</div>'+
      '<div style="font-size:20px;font-weight:800;color:#0f172a">Vali pank</div>'+
      '<div style="font-size:13px;color:#6b7280;margin-top:6px">Sind suunatakse panga veebilehele</div>'+
    '</div>';

  if(pendingOrder){
    html+='<div style="background:#f8fafc;border-radius:10px;padding:12px 16px;margin-bottom:18px;display:flex;justify-content:space-between">'+
      '<span style="font-size:13px;color:#6b7280">Tellimus '+escHtml(pendingOrder.order_number||'')+'</span>'+
      '<span style="font-size:15px;font-weight:700;color:#0f172a">'+Number(pendingOrder.total||0).toFixed(2)+' €</span>'+
    '</div>';
  }

  if(mcBanks.length>0){
    html+='<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:18px">';
    mcBanks.forEach(function(bank){
      html+='<a href="#" onclick="veshoShop.redirectToBank(event,\''+escAttr(bank.url||'')+'\',\''+escAttr(bank.name||'')+'\',\''+escAttr(bank.id||bank.country||'')+'\');return false" style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:16px 10px;border-radius:14px;border:1px solid #e2e8f0;background:#f8fafc;text-decoration:none;transition:all .15s" onmouseover="this.style.borderColor=\'#00b4c8\';this.style.background=\'rgba(0,180,200,.06)\'" onmouseout="this.style.borderColor=\'#e2e8f0\';this.style.background=\'#f8fafc\'">'+
        (bank.logo_url?'<img src="'+escAttr(bank.logo_url)+'" alt="'+escAttr(bank.name)+'" style="height:34px;object-fit:contain">':
          '<div style="width:44px;height:44px;border-radius:10px;background:'+escAttr(getStaticBankColor(bank))+';display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:16px">'+escHtml((bank.name||'?')[0])+'</div>')+
        '<span style="font-size:12px;font-weight:600;color:#0f172a;text-align:center">'+escHtml(bank.name||bank.id||'Pank')+'</span>'+
      '</a>';
    });
    html+='</div>';
  } else {
    // No MC bank data — show static bank list with direct MC redirect
    html+='<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px;margin-bottom:18px">';
    MC_BANKS.forEach(function(bank){
      html+='<button onclick="veshoShop.redirectMCDirect(\''+escAttr(bank.id)+'\')" style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:16px 10px;border-radius:14px;border:1px solid #e2e8f0;background:#f8fafc;cursor:pointer;transition:all .15s" onmouseover="this.style.borderColor=\'#00b4c8\';this.style.background=\'rgba(0,180,200,.06)\'" onmouseout="this.style.borderColor=\'#e2e8f0\';this.style.background=\'#f8fafc\'">'+
        '<div style="width:44px;height:44px;border-radius:10px;background:'+bank.color+';display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:16px">'+bank.name[0]+'</div>'+
        '<span style="font-size:12px;font-weight:600;color:#0f172a">'+bank.name+'</span>'+
      '</button>';
    });
    html+='</div>';
  }

  if(paymentError) html+='<div class="vshop-err">⚠️ '+escHtml(paymentError)+'</div>';
  html+='<button class="vshop-btn-gray" style="width:100%" onclick="veshoShop.cancelCheckout()">← Tagasi</button>';
  renderOverlay(html);
}

function renderSuccessStep(order){
  var html='<div style="text-align:center;padding:32px 0">'+
    '<div style="width:80px;height:80px;border-radius:50%;background:rgba(16,185,129,.15);border:2px solid rgba(16,185,129,.3);display:flex;align-items:center;justify-content:center;font-size:36px;margin:0 auto 20px">✅</div>'+
    '<h2 style="font-size:1.7rem;font-weight:800;color:#0f172a;margin:0 0 8px">Makse õnnestus!</h2>'+
    (order?'<p style="color:#6b7280;font-size:15px;margin:0 0 6px">Tellimus <strong>#'+escHtml(order.order_number||order.order_number||'')+'</strong> on kinnitatud.</p>'+
      '<p style="color:#10b981;font-size:22px;font-weight:800;margin:0 0 24px">'+Number(order.total||0).toFixed(2)+' €</p>':'')+
    '<p style="color:#6b7280;font-size:14px;margin:0 0 24px">Saatsime kinnituse teie e-postile. Võtame teiega tarne osas ühendust.</p>'+
    '<button class="vshop-btn-acc" onclick="veshoShop.backToShop()">Tagasi poodi</button>'+
  '</div>';
  renderOverlay(html);
}

function renderCancelledStep(){
  var html='<div style="text-align:center;padding:32px 0">'+
    '<div style="font-size:48px;margin-bottom:16px">❌</div>'+
    '<h2 style="font-size:1.5rem;font-weight:800;color:#0f172a;margin:0 0 10px">Makse tühistati</h2>'+
    '<p style="color:#6b7280;font-size:14px;margin:0 0 24px">Tellimust ei loodud. Proovi uuesti.</p>'+
    '<button class="vshop-btn-acc" onclick="veshoShop.backToShop()">Tagasi poodi</button>'+
  '</div>';
  renderOverlay(html);
}

// ── Stripe ─────────────────────────────────────────────────────────────────
function loadStripeAndMount(){
  if(!CFG.stripeEnabled||!CFG.stripePubKey) return;
  if(window.Stripe){
    mountStripeElements();
    return;
  }
  var s=document.createElement('script');
  s.src='https://js.stripe.com/v3/';
  s.onload=function(){ mountStripeElements(); };
  s.onerror=function(){ setPayError('Stripe laadimine ebaõnnestus'); };
  document.head.appendChild(s);
}
function mountStripeElements(){
  if(!pendingOrder||!pendingOrder.client_secret) return;
  try{
    stripeInst=window.Stripe(CFG.stripePubKey);
    stripeElems=stripeInst.elements({
      clientSecret:pendingOrder.client_secret,
      appearance:{theme:'stripe',variables:{colorPrimary:'#00b4c8',borderRadius:'10px',fontFamily:'inherit'}}
    });
    stripeCard=stripeElems.create('payment');
    var mount=document.getElementById('vshop-stripe-mount');
    if(mount) stripeCard.mount(mount);
  }catch(e){ setPayError('Stripe: '+e.message); }
}

// ── AJAX helpers ───────────────────────────────────────────────────────────
function ajaxPost(action,data,cb){
  var fd=new FormData();
  fd.append('action',action);
  fd.append('nonce',CFG.nonce);
  for(var k in data) fd.append(k,data[k]);
  fetch(CFG.ajaxurl,{method:'POST',body:fd})
    .then(function(r){ return r.json(); })
    .then(function(d){ cb(null,d); })
    .catch(function(e){ cb(e,null); });
}

function setPayError(msg){ paymentError=msg; paymentLoading=false; redrawCurrentStep(); }
function setPayLoading(v){ paymentLoading=v; redrawCurrentStep(); }
function redrawCurrentStep(){
  if(view==='guest') renderGuestStep();
  else if(view==='delivery') renderDeliveryStep();
  else if(view==='payment') renderPaymentStep();
  else if(view==='mc-banks') renderMcBanksStep();
}

function placeOrder(paymentMethod,onSuccess){
  paymentLoading=true;
  var data={
    payment_method: paymentMethod,
    shipping_method: deliveryMethod,
    address: deliveryAddress,
    phone: deliveryPhone||(CFG.clientLoggedIn?CFG.clientPhone:guestPhone)||'',
  };
  if(!CFG.clientLoggedIn){
    data.name=guestName||'';
    data.email=guestEmail||'';
  }
  // Send cart as JSON so server can use it even if session differs
  data.items_json=JSON.stringify(cart.map(function(c){ return {pid:c.pid, qty:c.qty}; }));

  ajaxPost('vesho_shop_place_order',data,function(err,resp){
    paymentLoading=false;
    if(err||!resp||!resp.success){
      setPayError((resp&&resp.data)||'Viga tellimuse loomisel');
      return;
    }
    onSuccess(resp.data);
  });
}

function getStaticBankColor(bank){
  var map={'swedbank':'#f36b00','seb':'#60b33c','lhv':'#f5a800','luminor':'#c00000','coop':'#24a148','bigbank':'#c0392b','inbank':'#7c3aed','revolut':'#191c20'};
  var id=(bank.id||'').toLowerCase();
  return map[id]||'#64748b';
}

// ── Public API ─────────────────────────────────────────────────────────────
window.veshoShop = {
  // State refs (for inline oninput handlers)
  get guestName(){ return guestName; },  set guestName(v){ guestName=v; },
  get guestEmail(){ return guestEmail; }, set guestEmail(v){ guestEmail=v; },
  get guestPhone(){ return guestPhone; }, set guestPhone(v){ guestPhone=v; },
  get deliveryPhone(){ return deliveryPhone; }, set deliveryPhone(v){ deliveryPhone=v; },
  get deliveryAddress(){ return deliveryAddress; }, set deliveryAddress(v){ deliveryAddress=v; },

  addToCart: function(pid,name,price,maxQty){
    var ex=cart.find(function(c){ return c.pid===pid; });
    if(ex) ex.qty=Math.min(ex.qty+1,maxQty);
    else cart.push({pid:pid,name:name,price:price,qty:1,maxQty:maxQty});
    saveCart();
    updateCardCtrls();
    this.openCart();
  },

  restoreAdd: function(pid){
    var card=document.querySelector('.vshop-card[data-pid="'+pid+'"]');
    if(card){
      var ctrl=document.getElementById('vshop-ctrl-'+pid);
      if(ctrl) ctrl.innerHTML='<button class="vshop-add-btn" onclick="veshoShop.addToCart('+pid+',\'\',0,999)">+ Lisa korvi</button>';
    }
  },

  setQty: function(pid,qty){
    if(qty<=0){ cart=cart.filter(function(c){ return c.pid!==pid; }); }
    else{
      var item=cart.find(function(c){ return c.pid===pid; });
      if(item) item.qty=Math.min(qty,item.maxQty||999);
    }
    saveCart();
    updateCardCtrls();
    if(cartSidebarOpen) renderCartSidebar();
  },

  openCart: function(){
    cartSidebarOpen=true;
    renderCartSidebar();
  },

  closeCart: function(){
    cartSidebarOpen=false;
    var bg=document.getElementById('vsb-bg');
    var sb=document.getElementById('vsb');
    if(bg){ bg.style.opacity='0'; }
    if(sb){ sb.style.transform='translateX(100%)'; }
    setTimeout(function(){
      var root=document.getElementById('vshop-overlay-root');
      if(root&&!root.querySelector('.vshop-overlay')) root.innerHTML='';
    },300);
  },

  onSearch: function(val){
    currentSearch=val;
    filterProducts();
  },

  onCategory: function(cat,btn){
    currentCat=cat||'';
    document.querySelectorAll('.vshop-cat-btn').forEach(function(b){ b.classList.remove('active'); });
    if(btn) btn.classList.add('active');
    filterProducts();
  },

  setCheckoutMode: function(mode){
    checkoutMode=mode;
    renderCartSidebar();
  },

  setDelivery: function(method){
    deliveryMethod=method;
    renderDeliveryStep();
  },

  startCheckout: function(){
    this.closeCart();
    paymentError='';
    if(!CFG.clientLoggedIn){
      view='guest'; guestName=CFG.clientName||''; guestEmail=CFG.clientEmail||''; guestPhone=CFG.clientPhone||'';
      setTimeout(renderGuestStep,150);
    } else {
      view='delivery';
      setTimeout(renderDeliveryStep,150);
    }
  },

  nextFromGuest: function(){
    guestName=document.getElementById('vg-name')?document.getElementById('vg-name').value:guestName;
    guestEmail=document.getElementById('vg-email')?document.getElementById('vg-email').value:guestEmail;
    guestPhone=document.getElementById('vg-phone')?document.getElementById('vg-phone').value:guestPhone;
    if(!guestName.trim()||!guestEmail.trim()){ setPayError('Nimi ja e-post on kohustuslikud'); return; }
    paymentError=''; view='delivery'; renderDeliveryStep();
  },

  backToCart: function(){
    view='shop';
    var root=document.getElementById('vshop-overlay-root');
    if(root) root.innerHTML='';
    this.openCart();
  },

  gotoGuest: function(){
    view='guest'; renderGuestStep();
  },

  proceedToPayment: function(){
    deliveryAddress=document.getElementById('vd-addr')?document.getElementById('vd-addr').value:deliveryAddress;
    deliveryPhone=document.getElementById('vd-phone')?document.getElementById('vd-phone').value:deliveryPhone;
    if(deliveryMethod!=='pickup'&&!deliveryAddress.trim()){
      setPayError(deliveryMethod==='omniva'?'Sisesta pakiautomaadi nimi':'Sisesta tarne aadress'); return;
    }
    paymentError='';

    if(checkoutMode==='mc'){
      placeOrder('mc',function(data){
        pendingOrder=data;
        if(data.redirect_url){
          // Show bank picker with static banks (redirect_url is the MC payment link)
          mcBanks=[]; // No individual bank URLs from this endpoint
          view='mc-banks';
          renderMcBanksStep();
        } else {
          setPayError('MC: ei saanud makselinki');
        }
      });
      paymentLoading=true; renderDeliveryStep();
    } else if(checkoutMode==='montonio'){
      placeOrder('montonio',function(data){
        pendingOrder=data;
        if(data.redirect_url){
          window.location.href=data.redirect_url;
        } else {
          setPayError('Montonio: ei saanud makselinki');
        }
      });
      paymentLoading=true; renderDeliveryStep();
    } else {
      // Stripe
      placeOrder('stripe',function(data){
        pendingOrder=data;
        if(!data.client_secret){
          // No Stripe (disabled or order confirmed directly)
          cart=[]; saveCart(); updateCardCtrls();
          view='success'; renderSuccessStep(data);
          return;
        }
        view='payment';
        renderPaymentStep();
      });
      paymentLoading=true; renderDeliveryStep();
    }
  },

  confirmStripe: function(){
    if(!stripeInst||!stripeElems){ setPayError('Stripe pole laetud'); return; }
    paymentLoading=true; renderPaymentStep();
    stripeInst.confirmPayment({elements:stripeElems,redirect:'if_required'})
      .then(function(result){
        if(result.error){ setPayError(result.error.message||'Makse ebaõnnestus'); return; }
        // Notify server
        ajaxPost('vesho_shop_stripe_confirm',{order_id:pendingOrder.order_id},function(err,resp){
          if(err||!resp||!resp.success){ setPayError((resp&&resp.data)||'Serveri kinnitus ebaõnnestus'); return; }
          cart=[]; saveCart(); updateCardCtrls();
          var confirmedOrder=resp.data||pendingOrder;
          view='success'; renderSuccessStep(confirmedOrder);
        });
      });
  },

  redirectToBank: function(e,url,name,id){
    if(e) e.preventDefault();
    if(!url){ setPayError('Pangalink pole saadaval'); return; }
    try{
      var u=new URL(url);
      var trusted=['payment.maksekeskus.ee','payment.test.maksekeskus.ee','sandbox-payment.maksekeskus.ee'];
      if(!trusted.includes(u.hostname)){ setPayError('Keeldutud redirect: '+u.hostname); return; }
      window.location.href=url;
    }catch(err){ setPayError('Vigane URL'); }
  },

  redirectMCDirect: function(bankId){
    // pendingOrder.redirect_url is the MC payment link — append bank if possible
    if(pendingOrder&&pendingOrder.redirect_url){
      window.location.href=pendingOrder.redirect_url;
    } else {
      setPayError('MC makselink pole saadaval');
    }
  },

  cancelCheckout: function(){
    view='shop';
    pendingOrder=null; paymentError=''; paymentLoading=false;
    stripeInst=null; stripeElems=null; stripeCard=null;
    var root=document.getElementById('vshop-overlay-root');
    if(root) root.innerHTML='';
  },

  backToShop: function(){
    // Remove URL params and reload clean
    var url=window.location.pathname;
    history.replaceState({},'',url);
    view='shop';
    pendingOrder=null; paymentError='';
    var root=document.getElementById('vshop-overlay-root');
    if(root) root.innerHTML='';
  },
};

// ── Utils ───────────────────────────────────────────────────────────────────
function escHtml(s){
  return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escAttr(s){
  return String(s||'').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// ── Init ────────────────────────────────────────────────────────────────────
loadCart();
updateFab();
updateCardCtrls();

// Handle initial view from PHP (MC/Montonio return)
if(CFG.initialView==='success'&&CFG.returnOrder){
  setTimeout(function(){ renderSuccessStep(CFG.returnOrder); },100);
} else if(CFG.initialView==='cancelled'){
  setTimeout(function(){ renderCancelledStep(); },100);
}

})();
</script>
        <?php
        return ob_get_clean();
    } // end shortcode_shop_OLD_REMOVED

    // ── [vesho_shop] shortcode — proper design ────────────────────────────────
    // [vesho_shop_grid] — sama mis vesho_shop aga ilma hero sektsioonita (Elementor lehel kasuta)
    public static function shortcode_shop_grid($atts) {
        $atts = (array)($atts ?: []);
        $atts['hero'] = '0';
        return self::shortcode_shop($atts);
    }

    public static function shortcode_shop($atts) {
        $atts = shortcode_atts(['hero' => '1'], $atts, 'vesho_shop');
        $show_hero = $atts['hero'] !== '0';
        global $wpdb;

        // Handle payment return URL params
        $shop_view       = 'shop';
        $return_order_id = absint($_GET['order_id'] ?? 0);
        if (!empty($_GET['vesho_shop_mc_return']))            $shop_view = 'success';
        elseif (!empty($_GET['vesho_shop_montonio_return']))  $shop_view = 'success';
        elseif (!empty($_GET['shop_view']) && $_GET['shop_view'] === 'cancel') $shop_view = 'cancelled';

        // Products
        $products = $wpdb->get_results(
            "SELECT i.id, i.name, i.category, i.shop_price, i.shop_description, i.quantity, i.unit,
                    COALESCE(c.color,'#00b4c8') as cat_color
             FROM {$wpdb->prefix}vesho_inventory i
             LEFT JOIN {$wpdb->prefix}vesho_inventory_categories c ON c.name = i.category
             WHERE i.shop_enabled=1 AND i.shop_price>0 AND i.archived=0
             ORDER BY i.category ASC, i.name ASC"
        );

        // Categories with counts and colors (from categories table)
        $db_cats = $wpdb->get_results(
            "SELECT c.name, c.color,
                    COUNT(i.id) as cnt
             FROM {$wpdb->prefix}vesho_inventory_categories c
             LEFT JOIN {$wpdb->prefix}vesho_inventory i
               ON i.category=c.name AND i.shop_enabled=1 AND i.shop_price>0 AND i.archived=0
             GROUP BY c.id, c.name, c.color
             ORDER BY c.sort_order ASC, c.name ASC"
        );
        $total_count = count($products);

        // Active shop campaign
        $today    = date('Y-m-d');
        $campaign = $wpdb->get_row(
            "SELECT * FROM {$wpdb->prefix}vesho_campaigns
             WHERE paused=0
               AND (valid_from IS NULL OR valid_from <= '$today')
               AND (valid_until IS NULL OR valid_until >= '$today')
               AND (target='epood' OR target='both')
             ORDER BY discount_percent DESC LIMIT 1"
        );

        // Client
        $client          = self::get_current_client();
        $client_discount = $client ? (float)($client->loyalty_pct ?? 0) : 0;

        // Payment settings
        $stripe_enabled   = get_option('vesho_stripe_enabled', '0') === '1' && get_option('vesho_stripe_pub_key', '') && get_option('vesho_stripe_secret_key', '');
        $stripe_pub_key   = get_option('vesho_stripe_pub_key', '');
        $mc_enabled       = get_option('vesho_mc_enabled', '0') === '1' && get_option('vesho_mc_shop_id', '') && get_option('vesho_mc_secret_key', '');
        $montonio_enabled = get_option('vesho_montonio_enabled', '0') === '1' && get_option('vesho_montonio_access_key', '') && get_option('vesho_montonio_secret_key', '');

        // Delivery options
        $del_opts = [];
        if (get_option('vesho_shop_ship_pickup_enabled',     '1') !== '0')
            $del_opts[] = ['id'=>'pickup',  'label'=>'Kaupluses pealekorjamine', 'icon'=>'🏪', 'price'=>(float)get_option('vesho_shop_ship_pickup_price',      '0')];
        if (get_option('vesho_shop_ship_courier_enabled',    '1') !== '0')
            $del_opts[] = ['id'=>'courier', 'label'=>'Kuller',                   'icon'=>'🚚', 'price'=>(float)get_option('vesho_shop_ship_courier_price',     '0')];
        if (get_option('vesho_shop_ship_parcelshop_enabled', '1') !== '0')
            $del_opts[] = ['id'=>'omniva',  'label'=>'Pakiautomaat (Omniva/DPD)','icon'=>'📦', 'price'=>(float)get_option('vesho_shop_ship_parcelshop_price', '0')];
        if (empty($del_opts))
            $del_opts[] = ['id'=>'pickup', 'label'=>'Pealekorjamine', 'icon'=>'🏪', 'price'=>0.0];

        // Return order for success screen
        $return_order = null;
        if ($return_order_id && $shop_view === 'success') {
            $return_order = $wpdb->get_row($wpdb->prepare(
                "SELECT order_number, total FROM {$wpdb->prefix}vesho_shop_orders WHERE id=%d",
                $return_order_id
            ));
        }

        $nonce = wp_create_nonce('vesho_cart_nonce');
        $cfg   = wp_json_encode([
            'ajaxurl'         => admin_url('admin-ajax.php'),
            'nonce'           => $nonce,
            'stripeEnabled'   => (bool)$stripe_enabled,
            'stripePubKey'    => (string)$stripe_pub_key,
            'mcEnabled'       => $mc_enabled,
            'montonioEnabled' => $montonio_enabled,
            'clientDiscount'  => $client_discount,
            'clientLoggedIn'  => (bool)$client,
            'clientName'      => $client ? $client->name  : '',
            'clientEmail'     => $client ? $client->email : '',
            'clientPhone'     => $client ? ($client->phone ?? '') : '',
            'campDiscount'    => $campaign ? (float)$campaign->discount_percent : 0,
            'campName'        => $campaign ? $campaign->name : '',
            'campFreeShip'    => $campaign ? (bool)$campaign->free_shipping : false,
            'campGuests'      => $campaign ? (bool)$campaign->visible_to_guests : false,
            'deliveryOptions' => $del_opts,
            'initialView'     => $shop_view,
            'returnOrder'     => $return_order,
        ]);

        ob_start();
        ?>
<style>
#vshop{all:revert;font-family:'Barlow',system-ui,sans-serif;color:#1a2a38;line-height:1.5;margin-top:-1px}
#vshop *{box-sizing:border-box;font-family:inherit}

/* Hero */
#vshop .vs-hero{background:linear-gradient(135deg,#0b1c2b 0%,#0d3347 100%);padding:80px 32px 72px;text-align:center;margin-bottom:0}
#vshop .vs-hero-eye{display:block;color:#00b4c8;font-size:11px;font-weight:800;letter-spacing:.2em;text-transform:uppercase;margin-bottom:20px}
#vshop .vs-hero-ttl{display:block;color:#fff;font-size:52px;font-weight:900;line-height:1;margin-bottom:16px}
#vshop .vs-hero-sub{display:block;color:#7a9eb0;font-size:16px}

/* Body + wrap */
#vshop .vs-body{background:#f4f7f9;padding:32px 0;min-height:400px}
#vshop .vs-wrap{max-width:1200px;margin:0 auto;padding:0 24px}

/* Campaign banner */
#vshop .vs-camp{display:flex;align-items:center;gap:16px;background:#fff;border-left:4px solid #00b4c8;border-radius:8px;padding:14px 20px;margin-bottom:20px;box-shadow:0 2px 12px rgba(0,0,0,.07)}
#vshop .vs-camp-badge{background:#00b4c8;color:#fff;font-size:13px;font-weight:700;padding:5px 12px;border-radius:4px;white-space:nowrap;flex-shrink:0}
#vshop .vs-camp-name{font-weight:700;font-size:14px;color:#1a2a38;margin:0}
#vshop .vs-camp-date{font-size:12px;color:#5a7080;margin:2px 0 0}

/* Layout */
#vshop .vs-layout{display:flex;gap:16px;align-items:flex-start}

/* Sidebar — Oomipood dark header stiil */
#vshop .vs-sidebar{flex:0 0 240px;border-radius:8px;overflow:hidden;position:sticky;top:80px;box-shadow:0 2px 12px rgba(0,0,0,.07)}
#vshop .vs-sidebar-hdr{padding:13px 16px;font-size:13px;font-weight:700;color:#fff;background:#0d1f2d;margin:0;display:flex;align-items:center;gap:6px}
#vshop .vs-cat{display:flex;align-items:center;gap:10px;padding:11px 16px;cursor:pointer;border:none;border-bottom:1px solid #dce8ef;background:#fff;width:100%;text-align:left;font-size:13px;color:#1a2a38;font-weight:500;transition:background .12s}
#vshop .vs-cat:hover{background:#f4f7f9}
#vshop .vs-cat.active{background:#e0f7fa;color:#007a8c;font-weight:700}
#vshop .vs-cat.active .vs-dot{box-shadow:0 0 0 3px rgba(0,180,200,.22)}
#vshop .vs-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;display:inline-block}
#vshop .vs-cnt{margin-left:auto;background:#e8eef2;color:#5a7080;font-size:11px;font-weight:700;padding:2px 7px;border-radius:20px;min-width:22px;text-align:center}
#vshop .vs-cat.active .vs-cnt{background:#00b4c8;color:#fff}

/* Main */
#vshop .vs-main{flex:1;min-width:0}
#vshop .vs-toolbar{display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;background:#fff;border-radius:8px;padding:10px 14px;box-shadow:0 2px 12px rgba(0,0,0,.07)}
#vshop .vs-search-wrap{position:relative;flex:1;min-width:180px}
#vshop .vs-search-wrap input{width:100%;padding:9px 12px 9px 36px;border:1px solid #dce8ef;border-radius:4px;font-size:13px;color:#1a2a38;outline:none;background:#f4f7f9;transition:border .15s}
#vshop .vs-search-wrap input:focus{border-color:#00b4c8;background:#fff}
#vshop .vs-search-ico{position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#8a9bb0;font-size:14px;pointer-events:none}
#vshop .vs-search-btn{background:#00b4c8;color:#fff;border:none;border-radius:4px;padding:9px 16px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;transition:background .15s}
#vshop .vs-search-btn:hover{background:#008fa0}
#vshop .vs-sort-wrap{display:flex;align-items:center;gap:6px;margin-left:auto}
#vshop .vs-sort-lbl{font-size:13px;color:#5a7080;white-space:nowrap}
#vshop .vs-sort{border:1px solid #dce8ef;border-radius:4px;padding:9px 10px;font-size:13px;color:#1a2a38;background:#f4f7f9;cursor:pointer;outline:none}
#vshop .vs-views{display:flex;border:1px solid #dce8ef;border-radius:4px;overflow:hidden}
#vshop .vs-view-btn{background:#f4f7f9;border:none;padding:9px 12px;cursor:pointer;color:#8a9bb0;font-size:16px;line-height:1;transition:all .12s}
#vshop .vs-view-btn.active,#vshop .vs-view-btn:hover{background:#00b4c8;color:#fff}
#vshop .vs-tcount{font-size:13px;color:#5a7080;margin-bottom:14px;padding-left:2px}
#vshop .vs-tcount strong{color:#1a2a38;font-weight:700}

/* Grid */
#vshop .vs-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(185px,1fr));gap:12px}
#vshop .vs-list{display:flex;flex-direction:column;gap:10px}

/* Grid card — Oomipood stiil */
#vshop .vs-card{background:#fff;border-radius:8px;overflow:hidden;display:flex;flex-direction:column;transition:box-shadow .18s;box-shadow:0 2px 12px rgba(0,0,0,.07);border:1px solid #dce8ef}
#vshop .vs-card:hover{box-shadow:0 4px 20px rgba(0,50,80,.10);border-color:#b2d8e0}
#vshop .vs-card-img{height:160px;display:flex;align-items:center;justify-content:center;font-size:64px;background:#fff;border-bottom:1px solid #dce8ef;padding:12px}
#vshop .vs-card-body{padding:12px 14px;flex:1;display:flex;flex-direction:column;gap:4px}
#vshop .vs-card-cat{font-size:10px;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:#00b4c8;margin:0}
#vshop .vs-card-name{font-weight:700;font-size:14px;color:#1a2a38;line-height:1.35;margin:2px 0 0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
#vshop .vs-card-stock{font-size:11px;font-weight:600;margin:4px 0 0;display:flex;align-items:center;gap:5px}
#vshop .vs-card-stock.in{color:#16a34a}
#vshop .vs-card-stock.out{color:#dc2626}
#vshop .vs-stock-dot{width:7px;height:7px;border-radius:50%;display:inline-block;flex-shrink:0}
#vshop .vs-card-price{font-size:22px;font-weight:800;color:#00b4c8;margin:6px 0 0;letter-spacing:-.3px}
#vshop .vs-card-foot{padding:0 14px 14px}

/* List card */
#vshop .vs-list-card{background:#fff;border-radius:8px;display:flex;align-items:center;gap:14px;padding:12px 16px;box-shadow:0 2px 12px rgba(0,0,0,.07);border:1px solid #dce8ef;transition:box-shadow .15s}
#vshop .vs-list-card:hover{box-shadow:0 4px 20px rgba(0,50,80,.10);border-color:#b2d8e0}
#vshop .vs-list-ico{width:60px;height:60px;border-radius:8px;background:#f4f7f9;border:1px solid #dce8ef;display:flex;align-items:center;justify-content:center;font-size:28px;flex-shrink:0}
#vshop .vs-list-info{flex:1;min-width:0}
#vshop .vs-list-name{font-weight:700;font-size:14px;color:#1a2a38;margin:0}
#vshop .vs-list-desc{font-size:12px;color:#5a7080;margin:2px 0 0}
#vshop .vs-list-price{font-size:20px;font-weight:800;color:#00b4c8;flex-shrink:0;white-space:nowrap;margin:0 8px}

/* Buttons */
#vshop .vs-add-btn{background:#00b4c8;color:#fff;border:none;border-radius:4px;padding:9px 0;font-size:13px;font-weight:700;cursor:pointer;width:100%;transition:background .15s;display:flex;align-items:center;justify-content:center;gap:6px}
#vshop .vs-add-btn:hover{background:#008fa0}
#vshop .vs-add-btn:disabled{background:#c8d6de;cursor:not-allowed}
#vshop .vs-qty{display:flex;align-items:center;border:1px solid #dce8ef;border-radius:4px;overflow:hidden;width:100%}
#vshop .vs-qty button{width:36px;height:36px;border:none;border-right:1px solid #dce8ef;background:#f4f7f9;cursor:pointer;font-size:18px;font-weight:700;color:#1a2a38;transition:all .12s;display:flex;align-items:center;justify-content:center;padding:0;flex-shrink:0}
#vshop .vs-qty button:last-child{border-right:none;border-left:1px solid #dce8ef}
#vshop .vs-qty button:hover{background:#00b4c8;color:#fff}
#vshop .vs-qty span{font-size:14px;font-weight:700;color:#1a2a38;flex:1;text-align:center}

/* Empty */
#vshop .vs-empty{text-align:center;padding:60px 20px;background:#fff;border-radius:8px;box-shadow:0 2px 12px rgba(0,0,0,.07)}
#vshop .vs-empty-ico{font-size:48px;margin-bottom:12px;display:block}
#vshop .vs-empty-txt{font-size:14px;font-weight:600;margin:0;color:#5a7080}

/* FAB */
#vshop .vs-fab{position:fixed;bottom:28px;right:28px;width:56px;height:56px;border-radius:50%;background:#00b4c8;color:#fff;border:none;cursor:pointer;font-size:22px;box-shadow:0 4px 20px rgba(0,50,80,.18);z-index:9990;display:none;align-items:center;justify-content:center;transition:transform .15s}
#vshop .vs-fab:hover{transform:scale(1.08)}
#vshop .vs-fab-badge{position:absolute;top:-4px;right:-4px;background:#ef4444;color:#fff;border-radius:50%;width:20px;height:20px;font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center;border:2px solid #fff}

/* Overlays */
#vshop .vs-bg{position:fixed;inset:0;background:rgba(0,0,0,.48);z-index:9991;opacity:0;pointer-events:none;transition:opacity .25s}
#vshop .vs-drawer{position:fixed;top:0;right:0;bottom:0;width:min(420px,100vw);background:#fff;z-index:9992;display:flex;flex-direction:column;transform:translateX(100%);transition:transform .3s cubic-bezier(.4,0,.2,1);box-shadow:-4px 0 40px rgba(0,0,0,.13)}
#vshop .vs-ov{position:fixed;inset:0;background:#f4f7f9;z-index:9993;overflow-y:auto;display:flex;align-items:flex-start;justify-content:center;padding:32px 16px}
#vshop .vs-ov-inner{width:100%;max-width:500px}

/* Form */
#vshop .vs-inp{width:100%;padding:10px 14px;border:1px solid #dce8ef;border-radius:4px;font-size:14px;color:#1a2a38;outline:none;background:#f4f7f9;transition:border .15s;display:block}
#vshop .vs-inp:focus{border-color:#00b4c8;background:#fff}
#vshop .vs-btn-teal{background:#00b4c8;color:#fff;border:none;border-radius:8px;padding:13px 20px;font-size:15px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:background .15s;width:100%}
#vshop .vs-btn-teal:hover:not(:disabled){background:#008fa0}
#vshop .vs-btn-teal:disabled{opacity:.6;cursor:not-allowed}
#vshop .vs-btn-gray{background:#e8eef2;color:#1a2a38;border:1px solid #dce8ef;border-radius:8px;padding:13px 18px;font-size:14px;font-weight:600;cursor:pointer;transition:all .15s}
#vshop .vs-btn-gray:hover{background:#dce8ef}
#vshop .vs-err{background:#fff5f5;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;color:#dc2626;font-size:13px;margin-bottom:14px}
#vshop .vs-steps{display:flex;align-items:center;justify-content:center;gap:0;margin-bottom:28px}
#vshop .vs-deliv-btn{display:flex;align-items:center;gap:12px;padding:13px 16px;border-radius:8px;border:2px solid #dce8ef;background:#f4f7f9;cursor:pointer;text-align:left;transition:all .15s;width:100%}
#vshop .vs-deliv-btn.sel{border-color:#00b4c8;background:#e0f7fa}

/* Mobile */
@media(max-width:768px){
  #vshop .vs-sidebar{display:none}
  #vshop .vs-layout{display:block}
  #vshop .vs-grid{grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px}
  #vshop .vs-ov{padding:16px 8px}
  #vshop .vs-hero-ttl{font-size:34px}
  #vshop .vs-body{padding:20px 0}
  #vshop .vs-fab{bottom:20px;right:20px}
}
</style>

<div id="vshop">

<?php if($show_hero): ?>
<div class="vs-hero">
  <span class="vs-hero-eye">VESHO OÜ</span>
  <span class="vs-hero-ttl">Pood</span>
  <span class="vs-hero-sub">Veesüsteemide materjalid ja komponendid.</span>
</div>
<?php endif; ?>

<div class="vs-body">
<div class="vs-wrap">

<?php if($campaign && in_array($campaign->target,['epood','both'],true)): ?>
<div class="vs-camp">
  <?php if($campaign->discount_percent>0): ?>
  <span class="vs-camp-badge">-<?=(float)$campaign->discount_percent?>%</span>
  <?php elseif($campaign->free_shipping): ?>
  <span class="vs-camp-badge" style="background:#10b981">Tasuta tarne</span>
  <?php else: ?>
  <span style="font-size:22px">🎉</span>
  <?php endif; ?>
  <div>
    <p class="vs-camp-name"><?=esc_html($campaign->name)?><?php if($campaign->description): ?> <span style="font-weight:400;color:#64748b"> — <?=esc_html($campaign->description)?></span><?php endif; ?></p>
    <?php if($campaign->valid_until): ?><p class="vs-camp-date">Kehtib kuni <?=date('d.m.Y',strtotime($campaign->valid_until))?></p><?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div class="vs-layout">
  <aside class="vs-sidebar">
    <p class="vs-sidebar-hdr">▼ &nbsp;Kategooriad</p>
    <button class="vs-cat active" id="vscat-all" onclick="vsShop.setCat('',this)">
      <span class="vs-dot" style="background:#00b4c8"></span>
      Kõik tooted
      <span class="vs-cnt" id="vscat-n-all"><?=$total_count?></span>
    </button>
    <?php foreach($db_cats as $c): ?>
    <button class="vs-cat" id="vscat-<?=esc_attr(sanitize_title($c->name))?>" onclick="vsShop.setCat('<?=esc_js($c->name)?>',this)" data-cat="<?=esc_attr($c->name)?>">
      <span class="vs-dot" style="background:<?=esc_attr($c->color?:'#00b4c8')?>"></span>
      <?=esc_html($c->name)?>
      <span class="vs-cnt" id="vscat-n-<?=esc_attr(sanitize_title($c->name))?>"><?=(int)$c->cnt?></span>
    </button>
    <?php endforeach; ?>
  </aside>

  <div class="vs-main">
    <div class="vs-toolbar">
      <div class="vs-search-wrap">
        <span class="vs-search-ico">🔍</span>
        <input type="text" id="vshop-q" placeholder="Otsi tooteid..." oninput="vsShop.onSearch(this.value)">
      </div>
      <button class="vs-search-btn" onclick="vsShop.onSearch(document.getElementById('vshop-q').value)">Otsi</button>
      <div class="vs-sort-wrap">
        <span class="vs-sort-lbl">Sorteeri:</span>
        <select class="vs-sort" id="vshop-sort" onchange="vsShop.onSort(this.value)">
          <option value="az">Nimi A–Z</option>
          <option value="za">Nimi Z–A</option>
          <option value="asc">Hind ↑</option>
          <option value="desc">Hind ↓</option>
        </select>
      </div>
      <div class="vs-views">
        <button class="vs-view-btn active" id="vshop-vg" onclick="vsShop.setView('grid')" title="Ruudustik">⊞</button>
        <button class="vs-view-btn" id="vshop-vl" onclick="vsShop.setView('list')" title="Nimekiri">☰</button>
      </div>
    </div>
    <p class="vs-tcount" id="vshop-count"><strong><?=$total_count?></strong> toodet</p>
    <div id="vshop-products"></div>
  </div>
</div>

</div><!-- .vs-wrap -->
</div><!-- .vs-body -->

<button class="vs-fab" id="vs-fab" onclick="vsShop.openCart()">🛒<span class="vs-fab-badge" id="vs-fab-badge">0</span></button>
<div id="vs-overlays"></div>
</div><!-- #vshop -->

<script>
(function(){
var CFG=<?php echo $cfg; ?>;
var PRODS=<?php echo wp_json_encode(array_map(function($p){
    return ['id'=>(int)$p->id,'name'=>$p->name,'category'=>$p->category??'','price'=>(float)$p->shop_price,'desc'=>$p->shop_description??'','qty'=>(int)$p->quantity,'color'=>$p->cat_color??'#00b4c8'];
},$products)); ?>;
var ICONS=['🔧','⚙️','🔩','🛠️','💡','🔌','❄️','🌡️','🔑','🪛','🧪','💧','⚗️','🔋','🩺'];

/* ── State ─────────────────────────────────────────────────────────── */
var cart=loadCart();
var currentCat='',currentSearch='',currentSort='az',currentView='grid';
var cartOpen=false;
// checkout
var view='shop';
var deliveryMethod=CFG.deliveryOptions[0]?CFG.deliveryOptions[0].id:'pickup';
var deliveryAddress='',deliveryPhone=CFG.clientPhone||'';
var guestName=CFG.clientName||'',guestEmail=CFG.clientEmail||'',guestPhone=CFG.clientPhone||'';
var pendingOrder=null,paymentError='',paymentLoading=false;
var checkoutMode=CFG.stripeEnabled?'stripe':CFG.mcEnabled?'mc':CFG.montonioEnabled?'montonio':'stripe';
var stripeInst=null,stripeElems=null,stripeCard=null;
var mcBanks=[];

function loadCart(){
  try{var s=JSON.parse(localStorage.getItem('vesho_cart')||'[]');return Array.isArray(s)?s:[];}catch(e){return[];}
}
function saveCart(){
  localStorage.setItem('vesho_cart',JSON.stringify(cart));
  updateFab();
  updateCardCtrls();
  if(cartOpen)renderCart();
}
function cartTotal(){
  var d=effectiveDisc();
  return cart.reduce(function(s,c){return s+applyDisc(c.price,d)*c.qty;},0);
}
function cartCount(){return cart.reduce(function(s,c){return s+c.qty;},0);}
function effectiveDisc(){return Math.max(CFG.clientDiscount||0,CFG.campGuests||CFG.clientLoggedIn?CFG.campDiscount:0);}
function applyDisc(p,d){return d>0?p*(1-d/100):p;}
function updateFab(){
  var fab=document.getElementById('vs-fab');
  var b=document.getElementById('vs-fab-badge');
  if(!fab)return;
  var n=cartCount();
  fab.style.display=n>0?'flex':'none';
  if(b)b.textContent=n;
}
function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');}
function escA(s){return String(s||'').replace(/"/g,'&quot;').replace(/'/g,'&#39;');}

/* ── Products rendering ─────────────────────────────────────────────── */
function getFiltered(){
  var list=PRODS.filter(function(p){
    var ms=!currentSearch||p.name.toLowerCase().includes(currentSearch.toLowerCase())||(p.desc||'').toLowerCase().includes(currentSearch.toLowerCase());
    var mc=!currentCat||p.category===currentCat;
    return ms&&mc;
  });
  list.sort(function(a,b){
    if(currentSort==='az')return a.name.localeCompare(b.name,'et');
    if(currentSort==='za')return b.name.localeCompare(a.name,'et');
    if(currentSort==='asc')return a.price-b.price;
    if(currentSort==='desc')return b.price-a.price;
    return 0;
  });
  return list;
}
function renderProducts(){
  var list=getFiltered();
  var el=document.getElementById('vshop-products');
  var cnt=document.getElementById('vshop-count');
  if(cnt)cnt.innerHTML='<strong>'+list.length+'</strong> toodet'+(currentSearch||currentCat?' (filtreeritud)':'');

  if(list.length===0){
    el.innerHTML='<div class="vs-empty"><div class="vs-empty-ico">🔍</div><p class="vs-empty-txt">'+(PRODS.length===0?'Pood on varsti avatud.':'Otsingule vastavaid tooteid ei leitud')+'</p></div>';
    return;
  }

  var html='<div class="'+(currentView==='grid'?'vs-grid':'vs-list')+'">';
  list.forEach(function(p,i){
    var icon=ICONS[p.id%ICONS.length];
    var inStock=p.qty>0;
    var inCart=cart.find(function(c){return c.pid===p.id;});
    var disc=effectiveDisc();
    var dp=applyDisc(p.price,disc);

    if(currentView==='grid'){
      html+='<div class="vs-card" id="vc-'+p.id+'">';
      html+='<div class="vs-card-img">'+icon+'</div>';
      html+='<div class="vs-card-body">';
      if(p.category)html+='<p class="vs-card-cat" style="color:'+esc(p.color)+'">'+esc(p.category)+'</p>';
      html+='<p class="vs-card-name">'+esc(p.name)+'</p>';
      if(p.desc)html+='<p class="vs-card-desc">'+esc(p.desc)+'</p>';
      html+='<p class="vs-card-stock '+(inStock?'in':'out')+'"><span class="vs-stock-dot" style="background:'+(inStock?'#16a34a':'#dc2626')+'"></span>'+(inStock?'Laos':'Otsas')+'</p>';
      html+='<div class="vs-card-price">'+(disc>0?'<span style="font-size:12px;color:#8a9bb0;text-decoration:line-through;margin-right:4px">'+p.price.toFixed(2)+' €</span>':'')+dp.toFixed(2)+' €</div>';
      html+='</div>';
      html+='<div class="vs-card-foot"><div id="vcc-'+p.id+'" style="flex:1">'+cardCtrlHtml(p,inCart,inStock)+'</div></div>';
      html+='</div>';
    } else {
      html+='<div class="vs-list-card" id="vc-'+p.id+'">';
      html+='<div class="vs-list-ico">'+icon+'</div>';
      html+='<div class="vs-list-info"><p class="vs-list-name">'+esc(p.name)+'</p>';
      if(p.desc)html+='<p class="vs-list-desc">'+esc(p.desc)+'</p>';
      html+='<p class="vs-card-stock '+(inStock?'in':'out')+'" style="margin-top:3px"><span class="vs-stock-dot" style="background:'+(inStock?'#16a34a':'#dc2626')+'"></span>'+(inStock?'Laos':'Otsas')+'</p>';
      html+='</div>';
      html+='<div class="vs-list-price">'+(disc>0?'<div style="font-size:11px;color:#94a3b8;text-decoration:line-through">'+p.price.toFixed(2)+' €</div>':'')+dp.toFixed(2)+' €</div>';
      html+='<div id="vcc-'+p.id+'">'+cardCtrlHtml(p,inCart,inStock)+'</div>';
      html+='</div>';
    }
  });
  html+='</div>';
  el.innerHTML=html;
}
function cardCtrlHtml(p,inCart,inStock){
  if(!inStock)return '<button class="vs-add-btn" disabled>Otsas</button>';
  if(inCart)return '<div class="vs-qty"><button onclick="vsShop.setQty('+p.id+','+(inCart.qty-1)+')">−</button><span>'+inCart.qty+'</span><button onclick="vsShop.setQty('+p.id+','+(inCart.qty+1)+','+p.qty+')">+</button></div>';
  return '<button class="vs-add-btn" onclick="vsShop.addToCart('+p.id+','+escA(JSON.stringify(p.name))+','+p.price+','+p.qty+')">🛒 Lisa korvi</button>';
}
function updateCardCtrls(){
  PRODS.forEach(function(p){
    var el=document.getElementById('vcc-'+p.id);
    if(!el)return;
    var inCart=cart.find(function(c){return c.pid===p.id;});
    el.innerHTML=cardCtrlHtml(p,inCart,p.qty>0);
  });
}

/* ── Cart drawer ─────────────────────────────────────────────────────── */
function renderCart(){
  var root=document.getElementById('vs-overlays');
  if(!root)return;
  var disc=effectiveDisc(),total=cartTotal(),orig=cart.reduce(function(s,c){return s+c.price*c.qty;},0),saved=orig-total;
  var delv=CFG.deliveryOptions.find(function(d){return d.id===deliveryMethod;})||{price:0};
  var shipC=CFG.campFreeShip?0:delv.price;
  var grand=total+shipC;

  var html='<div class="vs-bg" id="vsb-bg" onclick="vsShop.closeCart()" style="pointer-events:auto"></div>'+
    '<div class="vs-drawer" id="vsb">';

  html+='<div style="display:flex;align-items:center;justify-content:space-between;padding:18px 20px;border-bottom:1px solid #e2e8f0">'+
    '<div style="font-size:17px;font-weight:800;color:#0d3d4f">🛒 Ostukorv</div>'+
    '<button onclick="vsShop.closeCart()" style="border:none;background:none;font-size:20px;cursor:pointer;color:#94a3b8;padding:4px">✕</button>'+
  '</div>';

  html+='<div style="flex:1;overflow-y:auto;padding:14px 20px">';
  if(cart.length===0){
    html+='<div style="text-align:center;padding:48px 0;color:#94a3b8"><div style="font-size:44px;margin-bottom:10px">🛒</div><div style="font-size:14px;font-weight:600">Ostukorv on tühi</div></div>';
  } else {
    cart.forEach(function(item){
      var dp=applyDisc(item.price,disc);
      html+='<div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid #f1f5f9">'+
        '<div style="width:42px;height:42px;border-radius:9px;background:linear-gradient(135deg,#f0f7ff,#e0f7fa);display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0">'+ICONS[item.pid%ICONS.length]+'</div>'+
        '<div style="flex:1;min-width:0"><div style="font-size:13px;font-weight:700;color:#1a2a38;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'+esc(item.name)+'</div>'+
        (disc>0?'<div style="font-size:10px;color:#10b981">−'+disc+'%</div>':'')+
        '<div style="font-size:12px;color:#00b4c8;font-weight:700">'+(dp*item.qty).toFixed(2)+' €</div></div>'+
        '<div class="vs-qty"><button onclick="vsShop.setQty('+item.pid+','+(item.qty-1)+')">−</button><span>'+item.qty+'</span><button onclick="vsShop.setQty('+item.pid+','+(item.qty+1)+','+item.maxQty+')">+</button></div>'+
      '</div>';
    });
  }
  html+='</div>';

  if(cart.length>0){
    html+='<div style="padding:14px 20px;border-top:1px solid #e2e8f0">';
    if(disc>0)html+='<div style="display:flex;justify-content:space-between;font-size:12px;color:#10b981;margin-bottom:5px"><span>Säästsite</span><span>−'+saved.toFixed(2)+' €</span></div>';
    if(CFG.campFreeShip)html+='<div style="font-size:12px;color:#10b981;margin-bottom:5px">✓ Tasuta tarne ('+esc(CFG.campName)+')</div>';
    else if(shipC>0)html+='<div style="display:flex;justify-content:space-between;font-size:12px;color:#6b7280;margin-bottom:5px"><span>Tarne</span><span>'+shipC.toFixed(2)+' €</span></div>';
    html+='<div style="display:flex;justify-content:space-between;font-size:16px;font-weight:800;color:#0d3d4f;margin-bottom:14px"><span>Kokku</span><span>'+grand.toFixed(2)+' €</span></div>';

    // Payment selector
    var pmBtns='';
    function pmBtn(id,label){var s=checkoutMode===id;return '<button onclick="vsShop.setCheckoutMode(\''+id+'\')" style="flex:1;padding:8px;border:2px solid '+(s?'#00b4c8':'#e2e8f0')+';border-radius:9px;background:'+(s?'rgba(0,180,200,.08)':'#f8fafc')+';cursor:pointer;font-size:12px;font-weight:700;color:#0d3d4f">'+label+'</button>';}
    if(CFG.stripeEnabled||CFG.mcEnabled||CFG.montonioEnabled){
      pmBtns='<div style="display:flex;gap:6px;margin-bottom:10px">';
      if(CFG.stripeEnabled)pmBtns+=pmBtn('stripe','💳 Kaart');
      if(CFG.mcEnabled)pmBtns+=pmBtn('mc','🏦 Pank');
      if(CFG.montonioEnabled)pmBtns+=pmBtn('montonio','🏦 Montonio');
      pmBtns+='</div>';
    }
    html+=pmBtns;
    html+='<button class="vs-btn-teal" style="width:100%" onclick="vsShop.startCheckout()">Vormista tellimus →</button>';
    html+='</div>';
  }

  html+='</div>';
  root.innerHTML=html;
  setTimeout(function(){
    var bg=document.getElementById('vsb-bg');var sb=document.getElementById('vsb');
    if(bg)bg.style.opacity='1';if(sb)sb.style.transform='translateX(0)';
  },10);
}

/* ── Checkout overlay helpers ─────────────────────────────────────────── */
function stepBar(steps,idx){
  var h='<div class="vs-steps">';
  steps.forEach(function(s,i){
    var done=i<idx,act=i===idx;
    h+='<div style="display:flex;align-items:center"><div style="display:flex;flex-direction:column;align-items:center;gap:3px">'+
      '<div style="width:30px;height:30px;border-radius:50%;background:'+(done?'#10b981':act?'#00b4c8':'#f1f5f9')+';border:2px solid '+(done?'#10b981':act?'#00b4c8':'#e2e8f0')+';display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;color:'+(done||act?'#fff':'#94a3b8')+'">'+(done?'✓':(i+1))+'</div>'+
      '<span style="font-size:10px;color:'+(act?'#0d3d4f':'#94a3b8')+';font-weight:'+(act?700:400)+'">'+s+'</span></div>'+
      (i<steps.length-1?'<div style="width:36px;height:2px;background:'+(done?'#10b981':'#e2e8f0')+';margin:0 3px;margin-bottom:16px"></div>':'')+
    '</div>';
  });
  return h+'</div>';
}
function getSteps(){return CFG.clientLoggedIn?['Tarne','Makse','Kinnitus']:['Kontakt','Tarne','Makse','Kinnitus'];}
function getStepIdx(v){return CFG.clientLoggedIn?({delivery:0,payment:1,'mc-banks':1,success:2}[v]||0):({guest:0,delivery:1,payment:2,'mc-banks':2,success:3}[v]||0);}
function showOverlay(html){document.getElementById('vs-overlays').innerHTML='<div class="vs-ov"><div class="vs-ov-inner">'+html+'</div></div>';}
function redrawCheckout(){
  if(view==='guest')renderGuest();
  else if(view==='delivery')renderDelivery();
  else if(view==='payment')renderPayment();
  else if(view==='mc-banks')renderMcBanks();
}
function setPayErr(m){paymentError=m;paymentLoading=false;redrawCheckout();}

function renderGuest(){
  view='guest';
  var h=stepBar(getSteps(),getStepIdx('guest'))+
    '<div style="text-align:center;margin-bottom:22px"><h2 style="font-size:1.4rem;font-weight:800;color:#0d3d4f;margin:0 0 5px">Kontaktandmed</h2><p style="color:#6b7280;font-size:13px;margin:0">Sisesta oma andmed tellimuse kinnitamiseks</p></div>'+
    '<div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;margin-bottom:14px">'+
      '<label style="display:block;font-size:12px;color:#6b7280;margin-bottom:13px">Nimi *<input id="vg-name" class="vs-inp" style="margin-top:4px" placeholder="Sinu nimi" value="'+escA(guestName)+'" oninput="vsShop.guestName=this.value"></label>'+
      '<label style="display:block;font-size:12px;color:#6b7280;margin-bottom:13px">E-post *<input id="vg-email" class="vs-inp" type="email" style="margin-top:4px" placeholder="sinu@email.ee" value="'+escA(guestEmail)+'" oninput="vsShop.guestEmail=this.value"></label>'+
      '<label style="display:block;font-size:12px;color:#6b7280">Telefon<input id="vg-phone" class="vs-inp" style="margin-top:4px" placeholder="+372 5xxx xxxx" value="'+escA(guestPhone)+'" oninput="vsShop.guestPhone=this.value"></label>'+
    '</div>'+
    (paymentError?'<div class="vs-err">⚠️ '+esc(paymentError)+'</div>':'')+
    '<div style="display:flex;gap:10px"><button class="vs-btn-teal" style="flex:1" onclick="vsShop.nextFromGuest()">Jätka →</button><button class="vs-btn-gray" onclick="vsShop.backToCart()">← Tagasi</button></div>';
  showOverlay(h);
}

function renderDelivery(){
  view='delivery';
  var h=stepBar(getSteps(),getStepIdx('delivery'))+
    '<div style="text-align:center;margin-bottom:22px"><h2 style="font-size:1.4rem;font-weight:800;color:#0d3d4f;margin:0 0 5px">Tarneinfo</h2><p style="color:#6b7280;font-size:13px;margin:0">Vali tarnimisviis ja sisesta kontakt</p></div>'+
    '<div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:22px;margin-bottom:14px">';

  if(CFG.clientLoggedIn){
    h+='<label style="display:block;font-size:12px;color:#6b7280;margin-bottom:14px">Telefoninumber<input id="vd-phone" class="vs-inp" style="margin-top:4px" placeholder="+372 5xxx xxxx" value="'+escA(deliveryPhone)+'" oninput="vsShop.deliveryPhone=this.value"></label>';
  }

  h+='<div style="font-size:12px;color:#6b7280;margin-bottom:8px;font-weight:600">Tarnimisviis *</div><div style="display:flex;flex-direction:column;gap:8px;margin-bottom:14px">';
  CFG.deliveryOptions.forEach(function(opt){
    var s=deliveryMethod===opt.id;
    h+='<button onclick="vsShop.setDelivery(\''+opt.id+'\')" class="vs-deliv-btn'+(s?' sel':'')+'">'+
      '<span style="font-size:20px">'+opt.icon+'</span>'+
      '<div style="flex:1"><div style="font-size:13px;font-weight:600;color:#0d3d4f">'+esc(opt.label)+'</div></div>'+
      '<div style="font-size:13px;font-weight:700;flex-shrink:0">'+(opt.price===0?'<span style="color:#10b981">Tasuta</span>':opt.price.toFixed(2)+' €')+'</div>'+
      (s?'<span style="color:#00b4c8;font-size:17px;margin-left:4px">✓</span>':'')+
    '</button>';
  });
  h+='</div>';

  if(deliveryMethod!=='pickup'){
    h+='<label style="display:block;font-size:12px;color:#6b7280">'+(deliveryMethod==='omniva'?'Pakiautomaadi nimi *':'Tarne aadress *')+'<input id="vd-addr" class="vs-inp" style="margin-top:4px" placeholder="'+(deliveryMethod==='omniva'?'nt Omniva Tallinn Viru':'Tänav, maja, linn')+'" value="'+escA(deliveryAddress)+'" oninput="vsShop.deliveryAddress=this.value"></label>';
  }

  h+='</div>'+(paymentError?'<div class="vs-err">⚠️ '+esc(paymentError)+'</div>':'')+
    '<div style="display:flex;gap:10px"><button class="vs-btn-teal" style="flex:1" '+(paymentLoading?'disabled':'')+' onclick="vsShop.proceedToPayment()">'+(paymentLoading?'⏳ Laadin...':'Jätka →')+'</button>'+
    '<button class="vs-btn-gray" onclick="vsShop.'+(CFG.clientLoggedIn?'backToCart':'showGuest')+'()">← Tagasi</button></div>';
  showOverlay(h);
}

function renderPayment(){
  view='payment';
  var disc=effectiveDisc();
  var h=stepBar(getSteps(),getStepIdx('payment'))+
    '<div style="text-align:center;margin-bottom:22px"><h2 style="font-size:1.4rem;font-weight:800;color:#0d3d4f;margin:0 0 5px">Makseandmed</h2><p style="color:#6b7280;font-size:13px;margin:0">Tellimus <strong>#'+esc(pendingOrder.order_number)+'</strong></p></div>'+
    '<div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:16px 18px;margin-bottom:14px">'+
      '<div style="font-size:10px;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;font-weight:700;margin-bottom:10px">Kokkuvõte</div>';

  cart.forEach(function(c){
    var dp=applyDisc(c.price,disc);
    h+='<div style="display:flex;justify-content:space-between;font-size:12px;padding:4px 0;border-bottom:1px solid #f8fafc;color:#475569"><span style="color:#0d3d4f">'+esc(c.name)+' <span style="color:#94a3b8">×'+c.qty+'</span></span><span style="font-weight:600">'+(dp*c.qty).toFixed(2)+' €</span></div>';
  });

  if(pendingOrder.discount_amount>0.01)h+='<div style="display:flex;justify-content:space-between;font-size:11px;color:#10b981;padding:5px 0"><span>'+(pendingOrder.campaign_name?'🎉 '+pendingOrder.campaign_name:'Soodustus ('+disc+'%)')+'</span><span>−'+Number(pendingOrder.discount_amount).toFixed(2)+' €</span></div>';
  if(pendingOrder.shipping_price>0)h+='<div style="display:flex;justify-content:space-between;font-size:12px;color:#6b7280;padding:5px 0"><span>Tarne</span><span>'+Number(pendingOrder.shipping_price).toFixed(2)+' €</span></div>';
  h+='<div style="display:flex;justify-content:space-between;padding-top:8px;border-top:1px solid #e2e8f0;margin-top:3px"><span style="font-size:14px;font-weight:700;color:#0d3d4f">Kokku</span><span style="font-size:19px;font-weight:800;color:#0d3d4f">'+Number(pendingOrder.total).toFixed(2)+' €</span></div></div>'+
    '<div style="background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:18px;margin-bottom:14px"><div style="font-size:10px;text-transform:uppercase;letter-spacing:.1em;color:#94a3b8;font-weight:700;margin-bottom:12px">Makseandmed</div><div id="vs-stripe-mount" style="min-height:44px"></div></div>'+
    (paymentError?'<div class="vs-err">⚠️ '+esc(paymentError)+'</div>':'')+
    '<div style="display:flex;gap:10px"><button class="vs-btn-teal" style="flex:1;box-shadow:0 4px 14px rgba(0,180,200,.3)" '+(paymentLoading?'disabled':'')+' onclick="vsShop.confirmStripe()">'+(paymentLoading?'⏳ Töötlen...':'🔒 Maksa '+Number(pendingOrder.total).toFixed(2)+' €')+'</button><button class="vs-btn-gray" '+(paymentLoading?'disabled':'')+' onclick="vsShop.cancelCheckout()">Tühista</button></div>'+
    '<div style="text-align:center;font-size:10px;color:#94a3b8;margin-top:8px">🔒 Makse on krüpteeritud · Stripe</div>';
  showOverlay(h);
  setTimeout(mountStripe,120);
}

function renderMcBanks(){
  view='mc-banks';
  var MC_BANKS=[{id:'swedbank',name:'Swedbank',color:'#f36b00'},{id:'seb',name:'SEB',color:'#60b33c'},{id:'lhv',name:'LHV',color:'#f5a800'},{id:'luminor',name:'Luminor',color:'#c00000'},{id:'coop',name:'Coop',color:'#24a148'},{id:'bigbank',name:'Bigbank',color:'#c0392b'},{id:'inbank',name:'Inbank',color:'#7c3aed'},{id:'revolut',name:'Revolut',color:'#191c20'}];
  var h=stepBar(getSteps(),getStepIdx('mc-banks'))+
    '<div style="text-align:center;margin-bottom:22px"><div style="font-size:36px;margin-bottom:8px">🏦</div><div style="font-size:18px;font-weight:800;color:#0d3d4f">Vali pank</div><div style="font-size:12px;color:#6b7280;margin-top:4px">Sind suunatakse panga veebilehele</div></div>';
  if(pendingOrder)h+='<div style="background:#f8fafc;border-radius:10px;padding:10px 14px;margin-bottom:14px;display:flex;justify-content:space-between"><span style="font-size:12px;color:#6b7280">Tellimus '+esc(pendingOrder.order_number||'')+'</span><span style="font-size:14px;font-weight:700;color:#0d3d4f">'+Number(pendingOrder.total||0).toFixed(2)+' €</span></div>';
  if(paymentError)h+='<div class="vs-err">⚠️ '+esc(paymentError)+'</div>';
  h+='<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:9px;margin-bottom:14px">';
  var banksToShow=mcBanks.length>0?mcBanks:MC_BANKS;
  banksToShow.forEach(function(bank){
    var url=bank.url||pendingOrder&&pendingOrder.redirect_url||'';
    h+='<button onclick="vsShop.goBank(\''+escA(url)+'\',\''+escA(bank.name||bank.id||'')+'\',\''+escA(bank.id||'')+'\')" style="display:flex;flex-direction:column;align-items:center;gap:7px;padding:14px 8px;border-radius:12px;border:1px solid #e2e8f0;background:#f8fafc;cursor:pointer;transition:all .13s" onmouseover="this.style.borderColor=\'#00b4c8\';this.style.background=\'rgba(0,180,200,.06)\'" onmouseout="this.style.borderColor=\'#e2e8f0\';this.style.background=\'#f8fafc\'">'+
      (bank.logo_url?'<img src="'+escA(bank.logo_url)+'" alt="'+escA(bank.name)+'" style="height:30px;object-fit:contain">':
        '<div style="width:40px;height:40px;border-radius:9px;background:'+escA(bank.color||'#64748b')+';display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:15px">'+esc((bank.name||bank.id||'?')[0])+'</div>')+
      '<span style="font-size:11px;font-weight:600;color:#0d3d4f;text-align:center">'+esc(bank.name||bank.id||'Pank')+'</span>'+
    '</button>';
  });
  h+='</div><button class="vs-btn-gray" style="width:100%" onclick="vsShop.cancelCheckout()">← Tagasi</button>';
  showOverlay(h);
}

function renderSuccess(order){
  view='success';
  var h='<div style="text-align:center;padding:32px 0">'+
    '<div style="width:76px;height:76px;border-radius:50%;background:rgba(16,185,129,.15);border:2px solid rgba(16,185,129,.3);display:flex;align-items:center;justify-content:center;font-size:32px;margin:0 auto 18px">✅</div>'+
    '<h2 style="font-size:1.6rem;font-weight:800;color:#0d3d4f;margin:0 0 8px">Makse õnnestus!</h2>'+
    (order?'<p style="color:#6b7280;font-size:14px;margin:0 0 6px">Tellimus <strong>#'+esc(order.order_number||'')+'</strong> on kinnitatud.</p>'+
      '<p style="color:#10b981;font-size:20px;font-weight:800;margin:0 0 20px">'+Number(order.total||0).toFixed(2)+' €</p>':'')+
    '<p style="color:#6b7280;font-size:13px;margin:0 0 22px">Saatsime kinnituse teie e-postile.</p>'+
    '<button class="vs-btn-teal" onclick="vsShop.backToShop()">Tagasi poodi</button></div>';
  showOverlay(h);
}

function renderCancelled(){
  view='cancelled';
  showOverlay('<div style="text-align:center;padding:32px 0"><div style="font-size:44px;margin-bottom:14px">❌</div><h2 style="font-size:1.5rem;font-weight:800;color:#0d3d4f;margin:0 0 10px">Makse tühistati</h2><p style="color:#6b7280;font-size:13px;margin:0 0 22px">Tellimust ei loodud. Proovi uuesti.</p><button class="vs-btn-teal" onclick="vsShop.backToShop()">Tagasi poodi</button></div>');
}

/* ── Stripe ─────────────────────────────────────────────────────────── */
function mountStripe(){
  if(!CFG.stripeEnabled||!CFG.stripePubKey)return;
  if(window.Stripe){doMount();return;}
  var s=document.createElement('script');s.src='https://js.stripe.com/v3/';
  s.onload=doMount;s.onerror=function(){setPayErr('Stripe laadimine ebaõnnestus');};
  document.head.appendChild(s);
}
function doMount(){
  if(!pendingOrder||!pendingOrder.client_secret)return;
  try{
    stripeInst=window.Stripe(CFG.stripePubKey);
    stripeElems=stripeInst.elements({clientSecret:pendingOrder.client_secret,appearance:{theme:'stripe',variables:{colorPrimary:'#00b4c8',borderRadius:'10px',fontFamily:'inherit'}}});
    stripeCard=stripeElems.create('payment');
    var m=document.getElementById('vs-stripe-mount');if(m)stripeCard.mount(m);
  }catch(e){setPayErr('Stripe: '+e.message);}
}

/* ── AJAX ───────────────────────────────────────────────────────────── */
function ajax(action,data,cb){
  var fd=new FormData();fd.append('action',action);fd.append('nonce',CFG.nonce);
  for(var k in data)fd.append(k,data[k]);
  fetch(CFG.ajaxurl,{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){cb(null,d);}).catch(function(e){cb(e,null);});
}

function placeOrder(method,cb){
  paymentLoading=true;
  var data={payment_method:method,shipping_method:deliveryMethod,address:deliveryAddress,phone:deliveryPhone||(CFG.clientLoggedIn?CFG.clientPhone:guestPhone)||''};
  if(!CFG.clientLoggedIn){data.name=guestName;data.email=guestEmail;}
  data.items_json=JSON.stringify(cart.map(function(c){return{pid:c.pid,qty:c.qty};}));
  ajax('vesho_shop_place_order',data,function(err,resp){
    paymentLoading=false;
    if(err||!resp||!resp.success){setPayErr((resp&&resp.data)||'Viga tellimuse loomisel');return;}
    cb(resp.data);
  });
}

/* ── Public API ─────────────────────────────────────────────────────── */
window.vsShop={
  get guestName(){return guestName;},set guestName(v){guestName=v;},
  get guestEmail(){return guestEmail;},set guestEmail(v){guestEmail=v;},
  get guestPhone(){return guestPhone;},set guestPhone(v){guestPhone=v;},
  get deliveryPhone(){return deliveryPhone;},set deliveryPhone(v){deliveryPhone=v;},
  get deliveryAddress(){return deliveryAddress;},set deliveryAddress(v){deliveryAddress=v;},

  setCat:function(cat,btn){
    currentCat=cat;
    document.querySelectorAll('.vs-cat').forEach(function(b){b.classList.remove('active');});
    if(btn)btn.classList.add('active');
    renderProducts();
  },
  onSearch:function(val){currentSearch=val||'';renderProducts();},
  onSort:function(val){currentSort=val;renderProducts();},
  setView:function(v){
    currentView=v;
    document.getElementById('vshop-vg').classList.toggle('active',v==='grid');
    document.getElementById('vshop-vl').classList.toggle('active',v==='list');
    renderProducts();
  },

  addToCart:function(pid,name,price,maxQty){
    // name might be a JSON-encoded string from PHP
    if(typeof name==='string'&&name.charAt(0)==='"'){try{name=JSON.parse(name);}catch(e){}}
    var ex=cart.find(function(c){return c.pid===pid;});
    if(ex)ex.qty=Math.min(ex.qty+1,maxQty);
    else cart.push({pid:pid,name:name,price:price,qty:1,maxQty:maxQty});
    saveCart();
    this.openCart();
  },
  setQty:function(pid,qty,maxQty){
    if(qty<=0){cart=cart.filter(function(c){return c.pid!==pid;});}
    else{var item=cart.find(function(c){return c.pid===pid;});if(item)item.qty=Math.min(qty,maxQty||item.maxQty||999);}
    saveCart();
  },

  openCart:function(){cartOpen=true;renderCart();},
  closeCart:function(){
    cartOpen=false;
    var bg=document.getElementById('vsb-bg');var sb=document.getElementById('vsb');
    if(bg){bg.style.opacity='0';bg.style.pointerEvents='none';}
    if(sb)sb.style.transform='translateX(100%)';
    setTimeout(function(){var r=document.getElementById('vs-overlays');if(r&&r.querySelector('.vs-drawer'))r.innerHTML='';},300);
  },

  setCheckoutMode:function(m){checkoutMode=m;renderCart();},
  setDelivery:function(m){
    deliveryMethod=m;
    deliveryAddress=document.getElementById('vd-addr')?document.getElementById('vd-addr').value:deliveryAddress;
    renderDelivery();
  },

  startCheckout:function(){
    this.closeCart();paymentError='';
    if(!CFG.clientLoggedIn){setTimeout(renderGuest,150);}else{setTimeout(renderDelivery,150);}
  },
  nextFromGuest:function(){
    guestName=document.getElementById('vg-name')?document.getElementById('vg-name').value:guestName;
    guestEmail=document.getElementById('vg-email')?document.getElementById('vg-email').value:guestEmail;
    guestPhone=document.getElementById('vg-phone')?document.getElementById('vg-phone').value:guestPhone;
    if(!guestName.trim()||!guestEmail.trim()){setPayErr('Nimi ja e-post on kohustuslikud');return;}
    paymentError='';renderDelivery();
  },
  showGuest:function(){renderGuest();},
  backToCart:function(){
    view='shop';document.getElementById('vs-overlays').innerHTML='';
    this.openCart();
  },

  proceedToPayment:function(){
    deliveryAddress=document.getElementById('vd-addr')?document.getElementById('vd-addr').value:deliveryAddress;
    deliveryPhone=document.getElementById('vd-phone')?document.getElementById('vd-phone').value:deliveryPhone;
    if(deliveryMethod!=='pickup'&&!deliveryAddress.trim()){setPayErr(deliveryMethod==='omniva'?'Sisesta pakiautomaadi nimi':'Sisesta tarne aadress');return;}
    paymentError='';
    if(checkoutMode==='mc'){
      paymentLoading=true;renderDelivery();
      placeOrder('mc',function(data){
        pendingOrder=data;
        if(data.redirect_url){mcBanks=[];renderMcBanks();}
        else setPayErr('MC: ei saanud makselinki');
      });
    } else if(checkoutMode==='montonio'){
      paymentLoading=true;renderDelivery();
      placeOrder('montonio',function(data){
        pendingOrder=data;
        if(data.redirect_url)window.location.href=data.redirect_url;
        else setPayErr('Montonio: ei saanud makselinki');
      });
    } else {
      paymentLoading=true;renderDelivery();
      placeOrder('stripe',function(data){
        pendingOrder=data;
        if(!data.client_secret){cart=[];saveCart();renderSuccess(data);}
        else renderPayment();
      });
    }
  },

  confirmStripe:function(){
    if(!stripeInst||!stripeElems){setPayErr('Stripe pole laetud');return;}
    paymentLoading=true;renderPayment();
    stripeInst.confirmPayment({elements:stripeElems,redirect:'if_required'}).then(function(result){
      if(result.error){setPayErr(result.error.message||'Makse ebaõnnestus');return;}
      ajax('vesho_shop_stripe_confirm',{order_id:pendingOrder.order_id},function(err,resp){
        if(err||!resp||!resp.success){setPayErr((resp&&resp.data)||'Serveri kinnitus ebaõnnestus');return;}
        cart=[];saveCart();renderSuccess(resp.data||pendingOrder);
      });
    });
  },

  goBank:function(url,name,id){
    if(!url){
      if(pendingOrder&&pendingOrder.redirect_url)url=pendingOrder.redirect_url;
      else{setPayErr('Pangalink pole saadaval');return;}
    }
    try{var u=new URL(url);var trusted=['payment.maksekeskus.ee','payment.test.maksekeskus.ee','sandbox-payment.maksekeskus.ee'];
      if(!trusted.includes(u.hostname)){setPayErr('Keeldutud: '+u.hostname);return;}
      window.location.href=url;
    }catch(e){
      if(pendingOrder&&pendingOrder.redirect_url)window.location.href=pendingOrder.redirect_url;
      else setPayErr('Vigane URL');
    }
  },

  cancelCheckout:function(){
    view='shop';pendingOrder=null;paymentError='';paymentLoading=false;stripeInst=null;stripeElems=null;stripeCard=null;
    document.getElementById('vs-overlays').innerHTML='';
  },
  backToShop:function(){
    history.replaceState({},'',window.location.pathname);
    view='shop';pendingOrder=null;paymentError='';
    document.getElementById('vs-overlays').innerHTML='';
  },
};

/* ── Init ─────────────────────────────────────────────────────────────── */
renderProducts();
updateFab();

if(CFG.initialView==='success'&&CFG.returnOrder)setTimeout(function(){renderSuccess(CFG.returnOrder);},80);
else if(CFG.initialView==='cancelled')setTimeout(renderCancelled,80);

})();
</script>
        <?php
        return ob_get_clean();
    }

    // ── Fix: hide firma fields on register form load ──────────────────────────
    public static function register_form_fix_script() {
        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Hide firma fields initially
            document.querySelectorAll('#lm-register-form .lm-firma-field').forEach(function(el) {
                el.style.display = 'none';
            });
            document.querySelectorAll('#lm-register-form input[name="client_type"]').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    var isFirma = this.value === 'firma';
                    document.querySelectorAll('#lm-register-form .lm-firma-field').forEach(function(el) {
                        el.style.display = isFirma ? (el.dataset.display || 'block') : 'none';
                    });
                });
            });

            // (unverified handling moved to server-side portal blocked screen)
        });
        </script>
        <?php
    }
}
