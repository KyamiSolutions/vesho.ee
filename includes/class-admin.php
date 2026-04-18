<?php
/**
 * Vesho CRM – Admin class
 *
 * @package Vesho_CRM
 */
defined( 'ABSPATH' ) || exit;

class Vesho_CRM_Admin {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menus' ) );
        add_action( 'admin_post_vesho_save_client',      array( __CLASS__, 'handle_save_client' ) );
        add_action( 'admin_post_vesho_delete_client',    array( __CLASS__, 'handle_delete_client' ) );
        add_action( 'admin_post_vesho_save_service',     array( __CLASS__, 'handle_save_service' ) );
        add_action( 'admin_post_vesho_delete_service',   array( __CLASS__, 'handle_delete_service' ) );
        add_action( 'admin_post_vesho_save_worker',      array( __CLASS__, 'handle_save_worker' ) );
        add_action( 'admin_post_vesho_delete_worker',    array( __CLASS__, 'handle_delete_worker' ) );
        add_action( 'admin_post_vesho_save_device',      array( __CLASS__, 'handle_save_device' ) );
        add_action( 'admin_post_vesho_delete_device',    array( __CLASS__, 'handle_delete_device' ) );
        add_action( 'admin_post_vesho_save_maintenance', array( __CLASS__, 'handle_save_maintenance' ) );
        add_action( 'admin_post_vesho_delete_maintenance', array( __CLASS__, 'handle_delete_maintenance' ) );
        add_action( 'admin_post_vesho_save_invoice',     array( __CLASS__, 'handle_save_invoice' ) );
        add_action( 'admin_post_vesho_delete_invoice',   array( __CLASS__, 'handle_delete_invoice' ) );
        add_action( 'admin_post_vesho_save_inventory',     array( __CLASS__, 'handle_save_inventory' ) );
        add_action( 'admin_post_vesho_delete_inventory',  array( __CLASS__, 'handle_delete_inventory' ) );
        add_action( 'admin_post_vesho_restore_inventory', array( __CLASS__, 'handle_restore_inventory' ) );
        add_action( 'admin_post_vesho_writeoff_inventory',array( __CLASS__, 'handle_writeoff_inventory' ) );
        add_action( 'admin_post_vesho_import_inventory',  array( __CLASS__, 'handle_import_inventory' ) );
        add_action( 'admin_post_vesho_save_workorder',   array( __CLASS__, 'handle_save_workorder' ) );
        add_action( 'admin_post_vesho_delete_workorder', array( __CLASS__, 'handle_delete_workorder' ) );
        add_action( 'admin_post_vesho_save_pricelist',   array( __CLASS__, 'handle_save_pricelist' ) );
        add_action( 'admin_post_vesho_delete_pricelist', array( __CLASS__, 'handle_delete_pricelist' ) );
        add_action( 'admin_post_vesho_save_settings',        array( __CLASS__, 'handle_save_settings' ) );
        add_action( 'admin_post_vesho_update_request',        array( __CLASS__, 'handle_update_request' ) );
        add_action( 'admin_post_vesho_update_request_notes',  array( __CLASS__, 'handle_update_request_notes' ) );
        add_action( 'admin_post_vesho_reply_ticket',          array( __CLASS__, 'handle_reply_ticket' ) );
        add_action( 'admin_post_vesho_update_ticket_status',  array( __CLASS__, 'handle_update_ticket_status' ) );
        add_action( 'admin_post_vesho_invoice_mark_paid',     array( __CLASS__, 'handle_invoice_mark_paid' ) );
        add_action( 'admin_post_vesho_save_workhours',   array( __CLASS__, 'handle_save_workhours' ) );
        add_action( 'admin_post_vesho_delete_workhours', array( __CLASS__, 'handle_delete_workhours' ) );
        add_action( 'admin_post_vesho_save_receipt',         array( __CLASS__, 'handle_save_receipt' ) );
        add_action( 'admin_post_vesho_save_campaign',        array( __CLASS__, 'handle_save_campaign' ) );
        add_action( 'admin_post_vesho_pause_campaign',       array( __CLASS__, 'handle_pause_campaign' ) );
        add_action( 'admin_post_vesho_resume_campaign',      array( __CLASS__, 'handle_resume_campaign' ) );
        add_action( 'admin_post_vesho_client_send_access',   array( __CLASS__, 'handle_client_send_access' ) );
        add_action( 'admin_post_vesho_send_worker_pin',      array( __CLASS__, 'handle_send_worker_pin' ) );
        add_action( 'admin_post_vesho_send_invoice_email',   array( __CLASS__, 'handle_send_invoice_email' ) );
        add_action( 'admin_post_vesho_save_notice',          array( __CLASS__, 'handle_save_notice' ) );
        add_action( 'admin_post_vesho_delete_notice',        array( __CLASS__, 'handle_delete_notice' ) );
        add_action( 'admin_post_vesho_save_shop_order',      array( __CLASS__, 'handle_save_shop_order' ) );
        add_action( 'admin_post_vesho_delete_shop_order',    array( __CLASS__, 'handle_delete_shop_order' ) );
        add_action( 'admin_post_vesho_shop_order_status',    array( __CLASS__, 'handle_shop_order_status' ) );
        add_action( 'admin_post_vesho_bulk_shop_orders',     array( __CLASS__, 'handle_bulk_shop_orders' ) );
        add_action( 'admin_post_vesho_pick_item',            array( __CLASS__, 'handle_pick_item' ) );
        add_action( 'admin_post_vesho_confirm_maintenance', array( __CLASS__, 'handle_confirm_maintenance' ) );
        add_action( 'admin_post_vesho_reject_maintenance',  array( __CLASS__, 'handle_reject_maintenance' ) );
        add_action( 'admin_post_vesho_cancel_maintenance',  array( __CLASS__, 'handle_cancel_maintenance' ) );
        add_action( 'wp_ajax_vesho_search_wp_users',        array( __CLASS__, 'ajax_search_wp_users' ) );
        add_action( 'wp_ajax_vesho_add_maintenance_ajax',   array( __CLASS__, 'ajax_add_maintenance' ) );
        add_action( 'wp_ajax_vesho_get_client_devices',     array( __CLASS__, 'ajax_get_client_devices' ) );
        add_action( 'wp_ajax_vesho_postpone_maintenance',   array( __CLASS__, 'ajax_postpone_maintenance' ) );
        add_action( 'wp_ajax_vesho_create_credit_note',      array( __CLASS__, 'ajax_create_credit_note' ) );
        add_action( 'wp_ajax_vesho_order_issue_refund',      array( __CLASS__, 'ajax_order_issue_refund' ) );
        add_action( 'wp_ajax_vesho_order_manual_refund',     array( __CLASS__, 'ajax_order_manual_refund' ) );
        add_action( 'admin_post_vesho_generate_worker_barcode', array( __CLASS__, 'handle_generate_worker_barcode' ) );
        add_action( 'admin_post_vesho_approve_return',           array( __CLASS__, 'handle_approve_return' ) );
        add_action( 'admin_post_vesho_reject_return',            array( __CLASS__, 'handle_reject_return' ) );
        add_action( 'admin_post_vesho_export_invoices_csv',     array( __CLASS__, 'handle_export_invoices_csv' ) );
        add_action( 'admin_post_vesho_export_maintenances_csv', array( __CLASS__, 'handle_export_maintenances_csv' ) );
        // Feature: Admin user management
        add_action( 'admin_post_vesho_save_admin_user',   array( __CLASS__, 'handle_save_admin_user' ) );
        add_action( 'admin_post_vesho_delete_admin_user', array( __CLASS__, 'handle_delete_admin_user' ) );
        // Feature: TOTP 2FA
        add_action( 'admin_post_vesho_save_2fa',          array( __CLASS__, 'handle_save_2fa' ) );
        add_action( 'admin_post_vesho_disable_2fa',       array( __CLASS__, 'handle_disable_2fa' ) );
        add_action( 'admin_menu',                         array( __CLASS__, 'register_my_account_page' ) );
        add_action( 'wp_login',                           array( __CLASS__, 'after_wp_login' ), 10, 2 );
        add_action( 'login_form_vesho_2fa',               array( __CLASS__, 'render_2fa_login_page' ) );
        add_action( 'admin_bar_menu',                     array( __CLASS__, 'add_my_account_admin_bar_link' ), 999 );
        // Feature: Force password change
        add_action( 'admin_init',                         array( __CLASS__, 'check_force_password_change' ) );
        add_action( 'admin_post_vesho_change_password',   array( __CLASS__, 'handle_change_password' ) );
        // Admin barcode scanner assets
        add_action( 'admin_enqueue_scripts',              array( __CLASS__, 'enqueue_scanner_assets' ) );
    }

    // ── Scanner assets ─────────────────────────────────────────────────────────

    public static function enqueue_scanner_assets( $hook ) {
        // Load on Vesho CRM pages that use barcode scanning
        $screen = get_current_screen();
        if ( ! $screen ) return;
        $page = sanitize_text_field( $_GET['page'] ?? '' );
        $scanner_pages = [
            'vesho-crm-orders',
            'vesho-crm-inventory',
            'vesho-crm-receipts',
        ];
        if ( ! in_array( $page, $scanner_pages, true ) ) return;

        wp_enqueue_script(
            'zxing-library',
            'https://unpkg.com/@zxing/library@0.21.3/umd/index.min.js',
            [],
            '0.21.3',
            true
        );
        wp_enqueue_script(
            'vesho-ean-scanner',
            VESHO_CRM_URL . 'admin/js/ean-scanner.js',
            [ 'zxing-library' ],
            VESHO_CRM_VERSION,
            true
        );
    }

    // ── Menu registration ──────────────────────────────────────────────────────

    public static function register_menus() {
        $icon = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="currentColor">'
            . '<path d="M16 4C16 4 6 14 6 20C6 25.5 10.5 30 16 30C21.5 30 26 25.5 26 20C26 14 16 4 16 4Z" fill="#00b4c8"/></svg>'
        );

        $cap = current_user_can('manage_options') ? 'manage_options' : 'vesho_crm_admin';
        add_menu_page( 'Vesho CRM', 'Vesho CRM', $cap, 'vesho-crm',
            array( __CLASS__, 'page_dashboard' ), $icon, 25 );

        // Dashboard (overrides duplicate parent label)
        add_submenu_page( 'vesho-crm', 'Töölaud', 'Töölaud', $cap, 'vesho-crm', array( __CLASS__, 'page_dashboard' ) );
        add_submenu_page( 'vesho-crm', 'Meeldetuletused', 'Meeldetuletused', $cap, 'vesho-crm-reminders', array( __CLASS__, 'page_reminders' ) );

        // ── KLIENDID ──
        add_submenu_page( 'vesho-crm', 'Kliendid', 'Kliendid', $cap, 'vesho-crm-clients', array( __CLASS__, 'page_clients' ) );
        add_submenu_page( 'vesho-crm', 'Seadmed', 'Seadmed', $cap, 'vesho-crm-devices', array( __CLASS__, 'page_devices' ) );
        add_submenu_page( 'vesho-crm', 'Päringud', 'Päringud', $cap, 'vesho-crm-requests', array( __CLASS__, 'page_requests' ) );
        add_submenu_page( 'vesho-crm', 'Tugipiletid', 'Tugipiletid', $cap, 'vesho-crm-tickets', array( __CLASS__, 'page_tickets' ) );

        // ── HOOLDUSED ──
        add_submenu_page( 'vesho-crm', 'Kalender', 'Kalender', $cap, 'vesho-crm-calendar', array( __CLASS__, 'page_calendar' ) );
        add_submenu_page( 'vesho-crm', 'Marsruut', 'Marsruut', $cap, 'vesho-crm-route', array( __CLASS__, 'page_route' ) );
        add_submenu_page( 'vesho-crm', 'Hooldused', 'Hooldused', $cap, 'vesho-crm-maintenances', array( __CLASS__, 'page_maintenances' ) );

        // ── TÖÖ ──
        add_submenu_page( 'vesho-crm', 'Töötajad', 'Töötajad', $cap, 'vesho-crm-workers', array( __CLASS__, 'page_workers' ) );
        add_submenu_page( 'vesho-crm', 'Töötunnid', 'Töötunnid', $cap, 'vesho-crm-workhours', array( __CLASS__, 'page_workhours' ) );
        add_submenu_page( 'vesho-crm', 'Töökäsud', 'Töökäsud', $cap, 'vesho-crm-workorders', array( __CLASS__, 'page_workorders' ) );

        // ── ARVELDUS ──
        add_submenu_page( 'vesho-crm', 'Arved', 'Arved', $cap, 'vesho-crm-invoices', array( __CLASS__, 'page_invoices' ) );
        add_submenu_page( 'vesho-crm', 'Müügiraport', 'Müügiraport', $cap, 'vesho-crm-sales', array( __CLASS__, 'page_sales' ) );
        add_submenu_page( 'vesho-crm', 'Tellimused', 'Tellimused', $cap, 'vesho-crm-orders', array( __CLASS__, 'page_orders' ) );
        add_submenu_page( 'vesho-crm', 'Kampaaniad', 'Kampaaniad', $cap, 'vesho-crm-campaigns', array( __CLASS__, 'page_campaigns' ) );

        // ── LADU ──
        add_submenu_page( 'vesho-crm', 'Ladu', 'Ladu', $cap, 'vesho-crm-inventory', array( __CLASS__, 'page_inventory' ) );
        add_submenu_page( 'vesho-crm', 'Vastuvõtt', 'Vastuvõtt', $cap, 'vesho-crm-receipts', array( __CLASS__, 'page_receipts' ) );
        add_submenu_page( 'vesho-crm', 'Hinnakiri', 'Hinnakiri', $cap, 'vesho-crm-pricelist', array( __CLASS__, 'page_pricelist' ) );

        // ── MUU ──
        add_submenu_page( 'vesho-crm', 'Teenused', 'Teenused', $cap, 'vesho-crm-services', array( __CLASS__, 'page_services' ) );
        add_submenu_page( 'vesho-crm', 'Tegevuslogi', 'Tegevuslogi', $cap, 'vesho-crm-activity', array( __CLASS__, 'page_activity_log' ) );
        add_submenu_page( 'vesho-crm', 'Seaded', 'Seaded', 'manage_options', 'vesho-crm-settings', array( __CLASS__, 'page_settings' ) );
        add_submenu_page( 'vesho-crm', 'Administraatorid', '👥 Administraatorid', 'manage_options', 'vesho-crm-admins',
            function() { include VESHO_CRM_PATH . 'admin/views/admins.php'; } );
        if ( ( defined('WP_DEBUG') && WP_DEBUG ) || in_array( $_SERVER['SERVER_NAME'] ?? '', ['localhost','127.0.0.1','::1'] ) || str_contains( $_SERVER['HTTP_HOST'] ?? '', 'localhost' ) ) {
            add_submenu_page( 'vesho-crm', 'Demo Seeder', '🧪 Demo Seeder', 'manage_options', 'vesho-crm-demo-seeder',
                function() { include VESHO_CRM_PATH . 'admin/views/demo-seeder.php'; } );
        }
        add_submenu_page(
            'vesho-crm',
            'Uuendused',
            '🚀 Uuendused',
            'manage_options',
            'vesho-crm-releases',
            function() {
                include VESHO_CRM_PATH . 'admin/views/releases.php';
            }
        );
    }

    public static function register_my_account_page() {
        add_submenu_page( null, 'Minu konto', 'Minu konto', 'manage_options', 'vesho-my-account', array( __CLASS__, 'page_my_account' ) );
    }

    public static function add_my_account_admin_bar_link( $wp_admin_bar ) {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $wp_admin_bar->add_node( array(
            'id'    => 'vesho-my-account',
            'title' => '🔐 Minu konto (2FA)',
            'href'  => admin_url( 'admin.php?page=vesho-my-account' ),
            'parent'=> 'top-secondary',
        ) );
    }

    public static function page_my_account() {
        $user         = wp_get_current_user();
        $totp_enabled = get_user_meta( $user->ID, 'vesho_totp_enabled', true );
        $msg          = sanitize_text_field( $_GET['msg'] ?? '' );
        $force_pw     = isset( $_GET['force_pw'] );
        $must_change  = (bool) get_user_meta( $user->ID, 'vesho_force_password_change', true );
        ?>
        <div class="wrap">
            <h1>🔐 Minu konto</h1>

            <?php
            // Messages
            if ( $msg === 'enabled' )     echo '<div class="notice notice-success is-dismissible"><p>2FA aktiveeritud!</p></div>';
            if ( $msg === 'disabled' )    echo '<div class="notice notice-success is-dismissible"><p>2FA keelatud.</p></div>';
            if ( $msg === 'bad_code' )    echo '<div class="notice notice-error is-dismissible"><p>Vale kood. Proovi uuesti.</p></div>';
            if ( $msg === 'pw_changed' )  echo '<div class="notice notice-success is-dismissible"><p>✅ Parool muudetud!</p></div>';
            if ( $msg === 'short_pw' )    echo '<div class="notice notice-error is-dismissible"><p>Parool peab olema vähemalt 8 tähemärki.</p></div>';
            if ( $msg === 'pw_mismatch' ) echo '<div class="notice notice-error is-dismissible"><p>Paroolid ei ühti.</p></div>';
            ?>

            <div style="max-width:520px;margin-top:20px">

            <?php if ( $must_change || $force_pw ) : ?>
            <!-- ── Forced password change ──────────────────────────────────── -->
            <div style="background:#fef3c7;border:2px solid #f59e0b;border-radius:10px;padding:20px 24px;margin-bottom:24px">
                <h3 style="margin:0 0 8px;color:#92400e">🔑 Paroolimuse vahetus nõutav</h3>
                <p style="margin:0 0 16px;font-size:13px;color:#78350f">
                    Administraator on loonud sinu konto ajutise parooliga. Palun vali uus parool enne jätkamist.
                </p>
                <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('vesho_change_password'); ?>
                    <input type="hidden" name="action" value="vesho_change_password">
                    <div style="margin-bottom:12px">
                        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:5px;color:#92400e">Uus parool *</label>
                        <input type="password" name="new_password" required minlength="8"
                               style="width:100%;padding:9px 12px;border:1px solid #fcd34d;border-radius:6px;font-size:14px;box-sizing:border-box"
                               placeholder="Minimaalselt 8 tähemärki">
                    </div>
                    <div style="margin-bottom:16px">
                        <label style="display:block;font-size:12px;font-weight:600;margin-bottom:5px;color:#92400e">Kinnita uus parool *</label>
                        <input type="password" name="confirm_password" required
                               style="width:100%;padding:9px 12px;border:1px solid #fcd34d;border-radius:6px;font-size:14px;box-sizing:border-box"
                               placeholder="Korda parooli">
                    </div>
                    <button type="submit" class="button button-primary">🔑 Muuda parool</button>
                </form>
            </div>
            <?php endif; ?>

            <!-- ── 2FA section ──────────────────────────────────────────────── -->
            <?php if ( $totp_enabled ) : ?>
                <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:8px;padding:20px;margin-bottom:20px">
                    <strong style="color:#166534">✅ 2FA on aktiivne</strong>
                    <p style="margin:8px 0 0">Sisselogimisel nõutakse Google Authenticatori koodi.</p>
                </div>
                <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('vesho_disable_2fa'); ?>
                    <input type="hidden" name="action" value="vesho_disable_2fa">
                    <button type="submit" class="button button-secondary" onclick="return confirm('Keela 2FA?')">🔓 Keela 2FA</button>
                </form>
            <?php else :
                $setup_secret = get_user_meta( $user->ID, 'vesho_totp_pending_secret', true );
                if ( empty($setup_secret) ) {
                    $setup_secret = self::totp_generate_secret();
                    update_user_meta( $user->ID, 'vesho_totp_pending_secret', $setup_secret );
                }
                $qr_label = rawurlencode( 'Vesho:' . $user->user_email );
                $qr_url = 'otpauth://totp/' . $qr_label . '?secret=' . $setup_secret . '&issuer=Vesho';
            ?>
                <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:20px;margin-bottom:20px">
                    <h3 style="margin-top:0">Seadista Google Authenticator</h3>
                    <p>1. Laadi alla <strong>Google Authenticator</strong> või <strong>Authy</strong> rakendus.</p>
                    <p>2. Skaneeri QR-kood:</p>
                    <div id="vesho-2fa-qr" style="margin:12px 0;width:200px;height:200px;border:1px solid #e2e8f0"></div>
                    <script>
                    (function(){
                        var url = <?php echo wp_json_encode($qr_url); ?>;
                        var el  = document.getElementById('vesho-2fa-qr');
                        if(typeof QRCode !== 'undefined'){
                            new QRCode(el,{text:url,width:200,height:200,correctLevel:QRCode.CorrectLevel.M});
                        } else {
                            var s=document.createElement('script');
                            s.src='https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js';
                            s.onload=function(){ new QRCode(el,{text:url,width:200,height:200,correctLevel:QRCode.CorrectLevel.M}); };
                            document.head.appendChild(s);
                        }
                    })();
                    </script>
                    <p style="font-size:12px;color:#6b8599">Või sisesta käsitsi: <code style="user-select:all;padding:4px 8px;background:#f1f5f9;border-radius:4px"><?php echo esc_html($setup_secret); ?></code></p>
                    <p>3. Sisesta rakenduse 6-kohaline kood kinnituseks:</p>
                    <form method="POST" action="<?php echo admin_url('admin-post.php'); ?>">
                        <?php wp_nonce_field('vesho_save_2fa'); ?>
                        <input type="hidden" name="action" value="vesho_save_2fa">
                        <input type="hidden" name="setup_secret" value="<?php echo esc_attr($setup_secret); ?>">
                        <div style="display:flex;gap:8px;align-items:center;margin-top:12px">
                            <input type="text" name="totp_code" maxlength="6" minlength="6" pattern="[0-9]{6}"
                                   placeholder="000000" required autocomplete="one-time-code"
                                   style="width:120px;font-size:20px;letter-spacing:4px;text-align:center;padding:8px;border:1px solid #ccc;border-radius:6px">
                            <button type="submit" class="button button-primary">✅ Aktiveeri 2FA</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
            </div>
        </div>
        <?php
    }

    // ── Page renderers ─────────────────────────────────────────────────────────

    public static function page_dashboard()   { self::load_view('dashboard'); }
    public static function page_clients()     { self::load_view('clients'); }
    public static function page_devices()     { self::load_view('devices'); }
    public static function page_maintenances(){ self::load_view('maintenances'); }
    public static function page_requests()    { self::load_view('requests'); }
    public static function page_tickets()     { self::load_view('tickets'); }
    public static function page_workers()     { self::load_view('workers'); }
    public static function page_workorders()  { self::load_view('workorders'); }
    public static function page_workhours()   { self::load_view('workhours'); }
    public static function page_invoices()    { self::load_view('invoices'); }
    public static function page_orders()      { self::load_view('orders'); }
    public static function page_sales()       { self::load_view('sales'); }
    public static function page_inventory()   { self::load_view('inventory'); }
    public static function page_receipts()    { self::load_view('receipts'); }
    public static function page_pricelist()   { self::load_view('pricelist'); }
    public static function page_services()    { self::load_view('services'); }
    public static function page_campaigns()   { self::load_view('campaigns'); }
    public static function page_activity_log() { self::load_view('activity-log'); }
    public static function page_settings()    { self::load_view('settings'); }
    public static function page_reminders()    { self::load_view('reminders'); }
    public static function page_calendar()     { self::load_view('calendar'); }
    public static function page_route()        { self::load_view('route'); }
    public static function page_stockcount()   { self::load_view('stockcount'); }
    public static function page_warehouseloc() { self::load_view('warehouseloc'); }



private static function load_view( $name ) {
        $file = VESHO_CRM_PATH . 'admin/views/' . $name . '.php';
        if ( file_exists( $file ) ) {
            include $file;
        } else {
            echo '<div class="wrap"><h1>' . esc_html( $name ) . '</h1><p>Vaade puudub.</p></div>';
        }
    }

    // ══════════════════════════════════════════════════════════════════════════
    // POST handlers
    // ══════════════════════════════════════════════════════════════════════════

    // ── Client ────────────────────────────────────────────────────────────────
    public static function handle_save_client() {
        check_admin_referer( 'vesho_save_client' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $table    = $wpdb->prefix . 'vesho_clients';
        $id       = absint( $_POST['client_id'] ?? 0 );
        $name     = sanitize_text_field( $_POST['name'] ?? '' );
        $email    = sanitize_email( $_POST['email'] ?? '' );
        $password = $_POST['password'] ?? '';
        $data = array(
            'name'        => $name,
            'email'       => $email,
            'phone'       => sanitize_text_field( $_POST['phone'] ?? '' ),
            'address'     => sanitize_textarea_field( $_POST['address'] ?? '' ),
            'client_type' => sanitize_text_field( $_POST['client_type'] ?? 'eraisik' ),
            'company'     => sanitize_text_field( $_POST['company'] ?? '' ),
            'reg_code'    => sanitize_text_field( $_POST['reg_code'] ?? '' ),
            'vat_number'  => sanitize_text_field( $_POST['vat_number'] ?? '' ),
            'notes'       => sanitize_textarea_field( $_POST['notes'] ?? '' ),
        );
        if ( ! empty( $password ) ) $data['password'] = wp_hash_password( $password );

        if ( $id ) {
            $wpdb->update( $table, $data, array( 'id' => $id ) );
            // Update WP user if exists
            $existing = $wpdb->get_row( $wpdb->prepare( "SELECT user_id FROM $table WHERE id=%d", $id ) );
            if ( $existing && $existing->user_id ) {
                wp_update_user( array( 'ID' => (int) $existing->user_id, 'display_name' => $name ) );
                if ( ! empty( $password ) ) wp_set_password( $password, (int) $existing->user_id );
            }
            $msg = 'updated';
        } else {
            $data['created_at'] = current_time( 'mysql' );
            // Create WP user if email not taken
            $wp_user_id = null;
            if ( $email && ! email_exists( $email ) ) {
                $wp_pass    = ! empty( $password ) ? $password : wp_generate_password( 12, false );
                $wp_user_id = wp_create_user( $email, $wp_pass, $email );
                if ( ! is_wp_error( $wp_user_id ) ) {
                    $u = new WP_User( $wp_user_id );
                    $u->set_role( 'vesho_client' );
                    wp_update_user( array( 'ID' => $wp_user_id, 'display_name' => $name, 'first_name' => $name ) );
                    $data['user_id'] = $wp_user_id;
                } else {
                    $wp_user_id = null;
                }
            } elseif ( $email && ( $existing_user = get_user_by( 'email', $email ) ) ) {
                // Link existing WP user
                $existing_user->add_role( 'vesho_client' );
                $data['user_id'] = $existing_user->ID;
            }
            $wpdb->insert( $table, $data );
            $msg = 'added';
        }
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-clients','msg'=>$msg), admin_url('admin.php') ) );
        exit;
    }

    public static function handle_delete_client() {
        check_admin_referer( 'vesho_delete_client' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_GET['client_id'] ?? 0 );
        if ( $id ) $wpdb->delete( $wpdb->prefix . 'vesho_clients', array('id'=>$id) );
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-clients','msg'=>'deleted'), admin_url('admin.php') ) );
        exit;
    }

    // ── Device ────────────────────────────────────────────────────────────────
    public static function handle_save_device() {
        check_admin_referer( 'vesho_save_device' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_POST['device_id'] ?? 0 );
        $interval = absint( $_POST['maintenance_interval'] ?? 0 );
        $data = array(
            'client_id'            => absint( $_POST['client_id'] ?? 0 ),
            'name'                 => sanitize_text_field( $_POST['name'] ?? '' ),
            'model'                => sanitize_text_field( $_POST['model'] ?? '' ),
            'serial_number'        => sanitize_text_field( $_POST['serial_number'] ?? '' ),
            'install_date'         => sanitize_text_field( $_POST['install_date'] ?? '' ) ?: null,
            'location'             => sanitize_text_field( $_POST['location'] ?? '' ),
            'notes'                => sanitize_textarea_field( $_POST['notes'] ?? '' ),
            'maintenance_interval' => $interval ?: null,
        );
        $redirect_client = absint( $_POST['redirect_client'] ?? 0 );
        if ( $id ) { $wpdb->update( $wpdb->prefix.'vesho_devices', $data, array('id'=>$id) ); $msg='updated'; }
        else { $data['created_at']=current_time('mysql'); $wpdb->insert($wpdb->prefix.'vesho_devices',$data); $msg='added'; }
        $page = $redirect_client ? 'vesho-crm-devices&client_id='.$redirect_client : 'vesho-crm-devices';
        wp_redirect( add_query_arg( array('page'=>$page,'msg'=>$msg), admin_url('admin.php') ) );
        exit;
    }

    public static function handle_delete_device() {
        check_admin_referer( 'vesho_delete_device' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_GET['device_id'] ?? 0 );
        if ( $id ) $wpdb->delete( $wpdb->prefix.'vesho_devices', array('id'=>$id) );
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-devices','msg'=>'deleted'), admin_url('admin.php') ) );
        exit;
    }

    // ── Maintenance ───────────────────────────────────────────────────────────
    public static function handle_save_maintenance() {
        check_admin_referer( 'vesho_save_maintenance' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_POST['maintenance_id'] ?? 0 );
        $data = array(
            'device_id'      => absint( $_POST['device_id'] ?? 0 ),
            'scheduled_date' => sanitize_text_field( $_POST['scheduled_date'] ?? '' ) ?: null,
            'completed_date' => sanitize_text_field( $_POST['completed_date'] ?? '' ) ?: null,
            'status'         => sanitize_text_field( $_POST['status'] ?? 'scheduled' ),
            'description'    => sanitize_textarea_field( $_POST['description'] ?? '' ),
            'locked_price'   => strlen($_POST['locked_price'] ?? '') ? (float)$_POST['locked_price'] : null,
            'worker_id'      => absint( $_POST['worker_id'] ?? 0 ) ?: null,
            'notes'          => sanitize_textarea_field( $_POST['notes'] ?? '' ),
        );
        if ( $id ) {
            // Detect status change for email notification
            $old = $wpdb->get_row( $wpdb->prepare(
                "SELECT m.status, d.client_id, c.email as client_email, c.name as client_name, d.name as device_name
                 FROM {$wpdb->prefix}vesho_maintenances m
                 LEFT JOIN {$wpdb->prefix}vesho_devices d ON m.device_id=d.id
                 LEFT JOIN {$wpdb->prefix}vesho_clients c ON d.client_id=c.id
                 WHERE m.id=%d", $id
            ) );
            $wpdb->update($wpdb->prefix.'vesho_maintenances',$data,array('id'=>$id));
            $new_status = $data['status'];
            if ( $old && $old->status !== $new_status && $old->client_email ) {
                $co = get_option('vesho_company_name','Vesho OÜ');
                $date_str = $data['scheduled_date'] ? date('d.m.Y', strtotime($data['scheduled_date'])) : '';
                if ( $new_status === 'scheduled' && $old->status === 'pending' ) {
                    wp_mail(
                        $old->client_email,
                        $co . ' — Broneeringul kinnitatud',
                        "Tere, {$old->client_name}!\n\nTeie broneering on kinnitatud.\n\nSeade: {$old->device_name}\nKuupäev: {$date_str}\n\nLugupidamisega,\n{$co}"
                    );
                } elseif ( $new_status === 'cancelled' ) {
                    wp_mail(
                        $old->client_email,
                        $co . ' — Broneering tühistatud',
                        "Tere, {$old->client_name}!\n\nKahjuks tühistasime teie broneeringu seadmele {$old->device_name}.\n\nUue aja broneerimiseks palun võtke meiega ühendust.\n\nLugupidamisega,\n{$co}"
                    );
                }
            }
            // Auto-create next maintenance if interval set and status just became completed
            if ( $new_status === 'completed' && $old && $old->status !== 'completed' ) {
                $device = $wpdb->get_row( $wpdb->prepare(
                    "SELECT maintenance_interval FROM {$wpdb->prefix}vesho_devices WHERE id=%d",
                    $data['device_id']
                ) );
                if ( $device && $device->maintenance_interval > 0 ) {
                    $base = $data['completed_date'] ?: current_time('Y-m-d');
                    $next = date('Y-m-d', strtotime("+{$device->maintenance_interval} months", strtotime($base)));
                    $wpdb->insert( $wpdb->prefix . 'vesho_maintenances', array(
                        'device_id'      => $data['device_id'],
                        'scheduled_date' => $next,
                        'status'         => 'scheduled',
                        'description'    => $data['description'],
                        'created_at'     => current_time('mysql'),
                    ) );
                }

                // Auto-create draft invoice if locked_price set and no invoice exists yet
                $locked_price = $data['locked_price'];
                if ( $locked_price > 0 ) {
                    $inv_exists = $wpdb->get_var( $wpdb->prepare(
                        "SELECT id FROM {$wpdb->prefix}vesho_invoices WHERE maintenance_id=%d LIMIT 1",
                        $id
                    ) );
                    if ( ! $inv_exists ) {
                        $dev_client = $wpdb->get_row( $wpdb->prepare(
                            "SELECT d.client_id FROM {$wpdb->prefix}vesho_devices d WHERE d.id=%d",
                            $data['device_id']
                        ) );
                        if ( $dev_client && $dev_client->client_id ) {
                            // Apply campaign discount locked at booking time
                            $maint_row   = $wpdb->get_row( $wpdb->prepare(
                                "SELECT campaign_discount, campaign_name FROM {$wpdb->prefix}vesho_maintenances WHERE id=%d",
                                $id
                            ) );
                            $camp_disc   = $maint_row ? (float) $maint_row->campaign_discount : 0;
                            $camp_name   = $maint_row ? (string) $maint_row->campaign_name : '';
                            $inv_amount  = $camp_disc > 0
                                ? round( $locked_price * ( 1 - $camp_disc / 100 ), 2 )
                                : $locked_price;
                            $inv_desc    = 'Automaatselt loodud hooldusest #' . $id;
                            if ( $camp_disc > 0 ) {
                                $inv_desc .= ' (kampaania "' . $camp_name . '" -' . (int) $camp_disc . '%)';
                            }
                            $inv_number = Vesho_CRM_Database::get_next_invoice_number();
                            $wpdb->insert( $wpdb->prefix . 'vesho_invoices', array(
                                'client_id'      => $dev_client->client_id,
                                'invoice_number' => $inv_number,
                                'status'         => 'draft',
                                'invoice_date'   => current_time('Y-m-d'),
                                'due_date'       => date('Y-m-d', strtotime('+14 days')),
                                'amount'         => $inv_amount,
                                'maintenance_id' => $id,
                                'description'    => $inv_desc,
                                'created_at'     => current_time('mysql'),
                            ) );
                        }
                    }
                }
            }
            $msg='updated';
        } else {
            $data['created_at']=current_time('mysql');
            $wpdb->insert($wpdb->prefix.'vesho_maintenances',$data);
            $msg='added';
        }
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-maintenances','msg'=>$msg), admin_url('admin.php') ) );
        exit;
    }

    public static function handle_delete_maintenance() {
        check_admin_referer( 'vesho_delete_maintenance' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_GET['maintenance_id'] ?? 0 );
        if ( $id ) $wpdb->delete( $wpdb->prefix.'vesho_maintenances', array('id'=>$id) );
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-maintenances','msg'=>'deleted'), admin_url('admin.php') ) );
        exit;
    }

    // ── Invoice ───────────────────────────────────────────────────────────────
    public static function handle_save_invoice() {
        check_admin_referer( 'vesho_save_invoice' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_POST['invoice_id'] ?? 0 );

        // Calculate total from line items
        $items_desc  = array_map('sanitize_text_field',  (array)( $_POST['items']['desc']  ?? [] ));
        $items_qty   = array_map('floatval',             (array)( $_POST['items']['qty']   ?? [] ));
        $items_price = array_map('floatval',             (array)( $_POST['items']['price'] ?? [] ));
        $items_vat   = array_map('floatval',             (array)( $_POST['items']['vat']   ?? [] ));
        $grand_total = 0;
        foreach ( $items_desc as $i => $desc ) {
            if ( empty($desc) ) continue;
            $net          = ($items_qty[$i] ?? 1) * ($items_price[$i] ?? 0);
            $vat_rate     = $items_vat[$i] ?? 0;
            $grand_total += $net * (1 + $vat_rate / 100);
        }
        // Fallback to posted amount if no line items
        if ( $grand_total <= 0 ) $grand_total = (float)( $_POST['amount'] ?? 0 );

        $data = array(
            'client_id'      => absint( $_POST['client_id'] ?? 0 ),
            'invoice_number' => $id ? sanitize_text_field($_POST['invoice_number']??'') : Vesho_CRM_Database::get_next_invoice_number(),
            'invoice_date'   => sanitize_text_field( $_POST['invoice_date'] ?? '' ) ?: date('Y-m-d'),
            'due_date'       => sanitize_text_field( $_POST['due_date'] ?? '' ) ?: null,
            'amount'         => round( $grand_total, 2 ),
            'status'         => sanitize_text_field( $_POST['status'] ?? 'unpaid' ),
            'description'    => sanitize_textarea_field( $_POST['description'] ?? '' ),
        );

        if ( $id ) {
            $wpdb->update( $wpdb->prefix.'vesho_invoices', $data, array('id'=>$id) );
            $wpdb->delete( $wpdb->prefix.'vesho_invoice_items', array('invoice_id'=>$id) );
            $msg = 'updated';
        } else {
            $data['created_at'] = current_time('mysql');
            $wpdb->insert( $wpdb->prefix.'vesho_invoices', $data );
            $id = $wpdb->insert_id;
            $msg = 'added';
        }

        // Save line items
        foreach ( $items_desc as $i => $desc ) {
            if ( empty($desc) ) continue;
            $qty      = $items_qty[$i]   ?? 1;
            $price    = $items_price[$i] ?? 0;
            $vat      = $items_vat[$i]   ?? 0;
            $net      = $qty * $price;
            $total    = $net * (1 + $vat / 100);
            $wpdb->insert( $wpdb->prefix.'vesho_invoice_items', array(
                'invoice_id'  => $id,
                'description' => $desc,
                'quantity'    => $qty,
                'unit_price'  => $price,
                'vat_rate'    => $vat,
                'total'       => round($total,2),
            ) );
        }

        wp_redirect( add_query_arg( array('page'=>'vesho-crm-invoices','msg'=>$msg), admin_url('admin.php') ) );
        exit;
    }

    public static function handle_delete_invoice() {
        check_admin_referer( 'vesho_delete_invoice' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_GET['invoice_id'] ?? 0 );
        if ( $id ) {
            $wpdb->delete( $wpdb->prefix.'vesho_invoice_items', array('invoice_id'=>$id) );
            $wpdb->delete( $wpdb->prefix.'vesho_invoices', array('id'=>$id) );
        }
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-invoices','msg'=>'deleted'), admin_url('admin.php') ) );
        exit;
    }

    public static function handle_invoice_mark_paid() {
        check_admin_referer( 'vesho_invoice_mark_paid' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_GET['invoice_id'] ?? 0 );
        if ( $id ) {
            $inv = $wpdb->get_row( $wpdb->prepare(
                "SELECT i.invoice_number, c.name as client_name, i.amount
                 FROM {$wpdb->prefix}vesho_invoices i
                 LEFT JOIN {$wpdb->prefix}vesho_clients c ON i.client_id=c.id
                 WHERE i.id=%d", $id
            ) );
            $wpdb->update( $wpdb->prefix.'vesho_invoices', array('status'=>'paid'), array('id'=>$id) );
            if ( $inv ) {
                self::send_admin_notification( 'invoice_paid',
                    "Arve {$inv->invoice_number} märgiti makstud",
                    "Klient: {$inv->client_name}\nArve: {$inv->invoice_number}\nSumma: " . number_format($inv->amount,2,',','.') . " €"
                );
                if ( function_exists('vesho_crm_log_activity') ) {
                    vesho_crm_log_activity('invoice_paid', "Arve {$inv->invoice_number} märgiti makstud. Klient: {$inv->client_name}, summa: " . number_format($inv->amount,2,',','.') . " €", 'invoice', $id);
                }
            }
        }
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-invoices','msg'=>'updated'), admin_url('admin.php') ) );
        exit;
    }

    // ── Worker ────────────────────────────────────────────────────────────────
    public static function handle_save_worker() {
        check_admin_referer( 'vesho_save_worker' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id          = absint( $_POST['worker_id'] ?? 0 );
        $worker_name = sanitize_text_field( $_POST['name'] ?? '' );
        $pin         = $_POST['password'] ?? '';
        $data = array(
            'name'       => $worker_name,
            'email'      => sanitize_email( $_POST['email'] ?? '' ),
            'work_email' => sanitize_email( $_POST['work_email'] ?? '' ),
            'phone'      => sanitize_text_field( $_POST['phone'] ?? '' ),
            'role'       => sanitize_text_field( $_POST['role'] ?? 'technician' ),
            'active'          => isset($_POST['active']) ? 1 : 0,
            'show_on_website' => isset($_POST['show_on_website']) ? 1 : 0,
            'can_inventory'   => isset($_POST['can_inventory']) ? 1 : 0,
        );
        if ( ! empty($pin) ) {
            $data['password'] = wp_hash_password($pin);
            $data['pin']      = $pin;
        }

        if ( $id ) {
            $existing = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vesho_workers WHERE id=%d", $id
            ) );
            // Generate barcode token if missing
            if ( $existing && empty( $existing->barcode_token ) ) {
                $data['barcode_token'] = bin2hex( random_bytes(8) );
            }
            $wpdb->update( $wpdb->prefix . 'vesho_workers', $data, array( 'id' => $id ) );
            // Update WP user password if PIN changed
            if ( ! empty($pin) && $existing && $existing->user_id ) {
                wp_set_password( $pin, (int) $existing->user_id );
            }
            $msg = 'updated';
        } else {
            // New worker — create WP user too
            $data['created_at']    = current_time( 'mysql' );
            $data['barcode_token'] = bin2hex( random_bytes(8) );
            $email = ! empty( $data['email'] ) ? $data['email'] : strtolower( str_replace( ' ', '.', $worker_name ) ) . '@worker.local';
            $wp_user_id = username_exists( $worker_name ) ? false : wp_create_user( $worker_name, $pin ?: wp_generate_password(), $email );
            if ( $wp_user_id && ! is_wp_error( $wp_user_id ) ) {
                $wp_user = new WP_User( $wp_user_id );
                $wp_user->set_role( 'vesho_worker' );
                wp_update_user( array( 'ID' => $wp_user_id, 'display_name' => $worker_name ) );
                $data['user_id'] = $wp_user_id;
            }
            $wpdb->insert( $wpdb->prefix . 'vesho_workers', $data );
            $msg = 'added';
        }
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-workers','msg'=>$msg), admin_url('admin.php') ) );
        exit;
    }

    public static function handle_delete_worker() {
        check_admin_referer( 'vesho_delete_worker' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_GET['worker_id'] ?? 0 );
        if ( $id ) $wpdb->delete($wpdb->prefix.'vesho_workers', array('id'=>$id));
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-workers','msg'=>'deleted'), admin_url('admin.php') ) );
        exit;
    }

    // ── Service ───────────────────────────────────────────────────────────────
    public static function handle_save_service() {
        check_admin_referer( 'vesho_save_service' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_POST['service_id'] ?? 0 );
        $data = array(
            'name'        => sanitize_text_field( $_POST['name'] ?? '' ),
            'description' => sanitize_textarea_field( $_POST['description'] ?? '' ),
            'price'       => (float)( $_POST['price'] ?? 0 ),
            'price_unit'  => sanitize_text_field( $_POST['price_unit'] ?? '' ),
            'icon'        => sanitize_text_field( $_POST['icon'] ?? '💧' ),
            'active'      => isset($_POST['active']) ? 1 : 0,
            'sort_order'  => absint( $_POST['sort_order'] ?? 0 ),
        );
        if ( $id ) { $wpdb->update($wpdb->prefix.'vesho_services',$data,array('id'=>$id)); }
        else { $data['created_at']=current_time('mysql'); $wpdb->insert($wpdb->prefix.'vesho_services',$data); }
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-services','msg'=>'saved'), admin_url('admin.php') ) );
        exit;
    }

    public static function handle_delete_service() {
        check_admin_referer( 'vesho_delete_service' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_GET['service_id'] ?? 0 );
        if ( $id ) $wpdb->delete($wpdb->prefix.'vesho_services', array('id'=>$id));
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-services','msg'=>'deleted'), admin_url('admin.php') ) );
        exit;
    }

    // ── Inventory ─────────────────────────────────────────────────────────────
    public static function handle_save_inventory() {
        check_admin_referer( 'vesho_save_inventory' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_POST['inventory_id'] ?? 0 );
        $data = array(
            'name'           => sanitize_text_field( $_POST['name'] ?? '' ),
            'sku'            => sanitize_text_field( $_POST['sku'] ?? '' ),
            'ean'            => sanitize_text_field( $_POST['ean'] ?? '' ),
            'category'       => sanitize_text_field( $_POST['category'] ?? '' ),
            'unit'           => sanitize_text_field( $_POST['unit'] ?? 'tk' ),
            'quantity'       => (float)( $_POST['quantity'] ?? 0 ),
            'min_quantity'   => strlen($_POST['min_quantity']??'') ? (float)$_POST['min_quantity'] : null,
            'purchase_price' => strlen($_POST['purchase_price']??'') ? (float)$_POST['purchase_price'] : 0,
            'sell_price'     => strlen($_POST['sell_price']??'') ? (float)$_POST['sell_price'] : 0,
            'shop_price'     => strlen($_POST['shop_price']??'') ? (float)$_POST['shop_price'] : null,
            'location'       => sanitize_text_field( $_POST['location'] ?? '' ),
            'supplier'       => sanitize_text_field( $_POST['supplier'] ?? '' ),
            'description'    => sanitize_textarea_field( $_POST['description'] ?? '' ),
            'archived'       => isset($_POST['archived']) ? 1 : 0,
        );
        if ( $id ) { $wpdb->update($wpdb->prefix.'vesho_inventory',$data,array('id'=>$id)); $msg='updated'; }
        else { $data['created_at']=current_time('mysql'); $wpdb->insert($wpdb->prefix.'vesho_inventory',$data); $msg='added'; }
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-inventory','msg'=>$msg), admin_url('admin.php') ) );
        exit;
    }

    public static function handle_delete_inventory() {
        check_admin_referer( 'vesho_delete_inventory' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_GET['inventory_id'] ?? 0 );
        if ( $id ) $wpdb->update($wpdb->prefix.'vesho_inventory', array('archived'=>1), array('id'=>$id));
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-inventory','msg'=>'deleted'), admin_url('admin.php') ) );
        exit;
    }

    public static function handle_restore_inventory() {
        check_admin_referer( 'vesho_restore_inventory' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_GET['inventory_id'] ?? 0 );
        if ( $id ) $wpdb->update( $wpdb->prefix.'vesho_inventory', array('archived'=>0), array('id'=>$id) );
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-inventory','tab'=>'archived','msg'=>'restored'), admin_url('admin.php') ) );
        exit;
    }

    public static function handle_writeoff_inventory() {
        check_admin_referer( 'vesho_writeoff_inventory' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id     = absint( $_POST['inventory_id'] ?? 0 );
        $qty    = abs( (float)( $_POST['qty'] ?? 0 ) );
        $reason = sanitize_text_field( $_POST['reason'] ?? '' );
        $type   = sanitize_text_field( $_POST['type'] ?? 'writeoff' );
        if ( ! $id || $qty <= 0 ) {
            wp_redirect( add_query_arg( array('page'=>'vesho-crm-inventory','msg'=>'error'), admin_url('admin.php') ) );
            exit;
        }
        $user = wp_get_current_user();
        // Log to write-offs table
        $wpdb->insert( $wpdb->prefix.'vesho_inventory_writeoffs', array(
            'inventory_id' => $id,
            'qty'          => $qty,
            'reason'       => $reason,
            'type'         => $type,
            'user_name'    => $user ? $user->display_name : 'admin',
            'created_at'   => current_time('mysql'),
        ) );
        // Deduct from inventory
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}vesho_inventory SET quantity = GREATEST(0, quantity - %f) WHERE id=%d",
            $qty, $id
        ) );
        // Track used_quantity separately
        if ( $type === 'used' ) {
            $wpdb->query( $wpdb->prepare(
                "UPDATE {$wpdb->prefix}vesho_inventory SET used_quantity = COALESCE(used_quantity,0) + %f WHERE id=%d",
                $qty, $id
            ) );
        }
        $msg = $type === 'used' ? 'used' : 'writeoff';
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-inventory','msg'=>$msg), admin_url('admin.php') ) );
        exit;
    }

    public static function handle_import_inventory() {
        check_admin_referer( 'vesho_import_inventory' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        if ( empty( $_FILES['csv_file']['tmp_name'] ) ) {
            wp_redirect( add_query_arg( array('page'=>'vesho-crm-inventory','tab'=>'import','msg'=>'error'), admin_url('admin.php') ) );
            exit;
        }
        global $wpdb;
        $table   = $wpdb->prefix . 'vesho_inventory';
        $file    = $_FILES['csv_file']['tmp_name'];
        $handle  = fopen( $file, 'r' );
        if ( ! $handle ) {
            wp_redirect( add_query_arg( array('page'=>'vesho-crm-inventory','tab'=>'import','msg'=>'error'), admin_url('admin.php') ) );
            exit;
        }
        // Read header row
        $header = fgetcsv( $handle );
        if ( ! $header ) { fclose($handle); wp_redirect( add_query_arg( array('page'=>'vesho-crm-inventory','tab'=>'import','msg'=>'error'), admin_url('admin.php') ) ); exit; }
        // Normalise header (lowercase, trim)
        $header = array_map( fn($h) => strtolower(trim($h)), $header );
        $col    = array_flip( $header );
        $count  = 0;
        while ( ($row = fgetcsv($handle)) !== false ) {
            $get = fn($key,$default='') => isset($col[$key]) && isset($row[$col[$key]]) ? trim($row[$col[$key]]) : $default;
            $name = $get('name');
            if ( ! $name ) continue;
            $sku  = $get('sku');
            // Check if existing by SKU
            $existing_id = $sku ? $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE sku=%s AND archived=0 LIMIT 1", $sku)) : null;
            $data = array(
                'name'           => sanitize_text_field($name),
                'sku'            => sanitize_text_field($sku),
                'ean'            => sanitize_text_field($get('ean')),
                'unit'           => sanitize_text_field($get('unit','tk')),
                'quantity'       => (float) $get('quantity', 0),
                'purchase_price' => $get('purchase_price') !== '' ? (float)$get('purchase_price') : 0,
                'sell_price'     => $get('sell_price') !== '' ? (float)$get('sell_price') : 0,
                'shop_price'     => $get('shop_price') !== '' ? (float)$get('shop_price') : null,
                'min_quantity'   => $get('min_quantity') !== '' ? (float)$get('min_quantity') : null,
                'category'       => sanitize_text_field($get('category')),
                'location'       => sanitize_text_field($get('location')),
                'supplier'       => sanitize_text_field($get('supplier')),
                'description'    => sanitize_textarea_field($get('notes', $get('description'))),
                'archived'       => 0,
            );
            if ( $existing_id ) {
                $wpdb->update( $table, $data, array('id'=>(int)$existing_id) );
            } else {
                $data['created_at'] = current_time('mysql');
                $wpdb->insert( $table, $data );
            }
            $count++;
        }
        fclose($handle);
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-inventory','msg'=>'imported','count'=>$count), admin_url('admin.php') ) );
        exit;
    }

    // ── Work order ────────────────────────────────────────────────────────────
    public static function handle_save_workorder() {
        check_admin_referer( 'vesho_save_workorder' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_POST['workorder_id'] ?? 0 );
        $data = array(
            'client_id'      => absint($_POST['client_id']??0),
            'device_id'      => absint($_POST['device_id']??0) ?: null,
            'worker_id'      => absint($_POST['worker_id']??0) ?: null,
            'title'          => sanitize_text_field($_POST['title']??''),
            'description'    => sanitize_textarea_field($_POST['description']??''),
            'status'         => sanitize_text_field($_POST['status']??'open'),
            'priority'       => sanitize_text_field($_POST['priority']??'normal'),
            'scheduled_date' => sanitize_text_field($_POST['scheduled_date']??'') ?: null,
            'price'          => strlen($_POST['price']??'') ? (float)$_POST['price'] : null,
            'notes'          => sanitize_textarea_field($_POST['notes']??''),
            'work_type'      => sanitize_text_field($_POST['work_type']??''),
        );
        $prev_worker_id = 0;
        if ( $id ) {
            $prev = $wpdb->get_row($wpdb->prepare("SELECT worker_id FROM {$wpdb->prefix}vesho_workorders WHERE id=%d", $id));
            $prev_worker_id = (int)($prev->worker_id ?? 0);
            $wpdb->update($wpdb->prefix.'vesho_workorders',$data,array('id'=>$id));
            $msg='updated';
            $new_id = $id;
        } else {
            $data['created_at']=current_time('mysql');
            $wpdb->insert($wpdb->prefix.'vesho_workorders',$data);
            $msg='added';
            $new_id = $wpdb->insert_id;
        }
        // Send email to worker if assigned (new or changed)
        $new_worker_id = (int)($data['worker_id'] ?? 0);
        if ( $new_worker_id && $new_worker_id !== $prev_worker_id ) {
            $worker = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vesho_workers WHERE id=%d", $new_worker_id
            ));
            $email_to = !empty($worker->work_email) ? $worker->work_email : (!empty($worker->email) ? $worker->email : '');
            if ( $email_to ) {
                $co      = get_option('vesho_company_name', 'Vesho OÜ');
                $wo      = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vesho_workorders WHERE id=%d", $new_id));
                $subject = $co . ' — Uus töökäsk: ' . ($wo->title ?: 'Töökäsk #' . $new_id);
                $body    = "Tere, {$worker->name}!\n\n";
                $body   .= "Sulle on määratud uus töökäsk.\n\n";
                $body   .= "Töökäsk: " . ($wo->title ?: '#' . $new_id) . "\n";
                if (!empty($wo->description)) $body .= "Kirjeldus: {$wo->description}\n";
                if (!empty($wo->scheduled_date)) $body .= "Planeeritud kuupäev: " . date('d.m.Y', strtotime($wo->scheduled_date)) . "\n";
                $body   .= "\nLogi portaali sisse et töökäsuga alustada.\n\nLugupidamisega,\n{$co}";
                wp_mail($email_to, $subject, $body);
            }
        }
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-workorders','msg'=>$msg), admin_url('admin.php') ) );
        exit;
    }

    public static function handle_delete_workorder() {
        check_admin_referer( 'vesho_delete_workorder' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_GET['workorder_id'] ?? 0 );
        if ( $id ) $wpdb->delete($wpdb->prefix.'vesho_workorders', array('id'=>$id));
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-workorders','msg'=>'deleted'), admin_url('admin.php') ) );
        exit;
    }

    // ── Price list ────────────────────────────────────────────────────────────
    public static function handle_save_pricelist() {
        check_admin_referer( 'vesho_save_pricelist' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_POST['pricelist_id'] ?? 0 );
        $data = array(
            'name'           => sanitize_text_field($_POST['name']??''),
            'category'       => sanitize_text_field($_POST['category']??''),
            'unit'           => sanitize_text_field($_POST['unit']??'tk'),
            'price'          => (float)($_POST['price']??0),
            'vat_rate'       => (float)($_POST['vat_rate']??22),
            'description'    => sanitize_textarea_field($_POST['description']??''),
            'active'         => isset($_POST['active']) ? 1 : 0,
            'sort_order'     => absint($_POST['sort_order']??0),
            'visible_public' => isset($_POST['visible_public']) ? 1 : 0,
            'work_type'      => sanitize_text_field($_POST['work_type']??''),
        );
        if ( $id ) { $wpdb->update($wpdb->prefix.'vesho_price_list',$data,array('id'=>$id)); }
        else { $data['created_at']=current_time('mysql'); $wpdb->insert($wpdb->prefix.'vesho_price_list',$data); }
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-pricelist','msg'=>'saved'), admin_url('admin.php') ) );
        exit;
    }

    public static function handle_delete_pricelist() {
        check_admin_referer( 'vesho_delete_pricelist' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_GET['pricelist_id'] ?? 0 );
        if ( $id ) $wpdb->delete($wpdb->prefix.'vesho_price_list', array('id'=>$id));
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-pricelist','msg'=>'deleted'), admin_url('admin.php') ) );
        exit;
    }

    // ── Request ───────────────────────────────────────────────────────────────
    public static function handle_update_request() {
        check_admin_referer( 'vesho_update_request' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_GET['request_id'] ?? $_POST['request_id'] ?? 0 );
        $status = sanitize_text_field( $_GET['status'] ?? $_POST['status'] ?? 'open' );
        if ( $id ) {
            $wpdb->update( $wpdb->prefix.'vesho_guest_requests', array( 'status' => $status ), array('id'=>$id) );
        }
        $redirect_id = $id;
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-requests','action'=>'view','request_id'=>$redirect_id,'msg'=>'updated'), admin_url('admin.php') ) );
        exit;
    }

    public static function handle_update_request_notes() {
        check_admin_referer( 'vesho_update_request' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id    = absint( $_POST['request_id'] ?? 0 );
        $notes = sanitize_textarea_field( $_POST['admin_notes'] ?? '' );
        if ( $id ) {
            $wpdb->update( $wpdb->prefix.'vesho_guest_requests', array('admin_notes'=>$notes), array('id'=>$id) );
        }
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-requests','action'=>'view','request_id'=>$id,'msg'=>'updated'), admin_url('admin.php') ) );
        exit;
    }

    // ── Support Tickets ───────────────────────────────────────────────────────
    public static function handle_reply_ticket() {
        check_admin_referer( 'vesho_ticket_action' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id    = absint( $_POST['ticket_id'] ?? 0 );
        $reply = sanitize_textarea_field( $_POST['reply'] ?? '' );
        if ( $id ) {
            $wpdb->update( $wpdb->prefix.'vesho_support_tickets', [
                'reply'      => $reply,
                'status'     => 'closed',
                'updated_at' => current_time('mysql'),
            ], ['id' => $id] );
        }
        wp_redirect( add_query_arg( ['page'=>'vesho-crm-tickets','action'=>'view','ticket_id'=>$id,'msg'=>'replied'], admin_url('admin.php') ) );
        exit;
    }

    public static function handle_update_ticket_status() {
        check_admin_referer( 'vesho_ticket_action' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id     = absint( $_GET['ticket_id'] ?? 0 );
        $status = sanitize_text_field( $_GET['status'] ?? 'open' );
        if ( $id ) {
            $wpdb->update( $wpdb->prefix.'vesho_support_tickets', ['status'=>$status,'updated_at'=>current_time('mysql')], ['id'=>$id] );
        }
        wp_redirect( add_query_arg( ['page'=>'vesho-crm-tickets','action'=>'view','ticket_id'=>$id,'msg'=>'updated'], admin_url('admin.php') ) );
        exit;
    }

    // ── Settings ──────────────────────────────────────────────────────────────
    public static function handle_save_settings() {
        check_admin_referer( 'vesho_save_settings' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        $text_fields = array(
            'company_name','company_reg','company_vat','company_address',
            'company_phone','company_email','company_bank','company_iban',
            'invoice_prefix','invoice_start','portal_page','login_page','worker_page',
            'portal_title','portal_login_sub','notify_email','maintenance_reminder_days',
            'vat_rate','invoice_due_days','ga_id',
            'services_page_title','services_page_subtitle','maintenance_message',
            'coming_soon_title','coming_soon_message',
            // payment gateways
            'stripe_pub_key','mc_shop_id',
            'montonio_access_key',
            // shop
            'shop_loyalty_discount',
            'shop_ship_pickup_price','shop_ship_courier_price','shop_ship_parcelshop_price',
            // appearance
            'company_logo','primary_color',
            // cookie banner
            'cookie_banner_title','cookie_accept_text','cookie_reject_text',
            // geofence
            'office_lat','office_lng','geofence_radius',
        );
        foreach ( $text_fields as $f ) {
            if ( isset($_POST[$f]) ) update_option( 'vesho_'.$f, sanitize_text_field($_POST[$f]) );
        }
        // Sensitive keys — store but don't overwrite if empty (keep existing secret)
        foreach ( array('stripe_secret_key','stripe_webhook_secret','mc_secret_key','montonio_secret_key') as $f ) {
            if ( !empty($_POST[$f]) ) update_option( 'vesho_'.$f, sanitize_text_field($_POST[$f]) );
        }
        if ( isset($_POST['portal_welcome']) ) {
            update_option( 'vesho_portal_welcome', sanitize_textarea_field($_POST['portal_welcome']) );
        }
        if ( isset($_POST['shop_terms']) ) {
            update_option( 'vesho_shop_terms', wp_kses_post($_POST['shop_terms']) );
        }
        if ( isset($_POST['privacy_policy']) ) {
            update_option( 'vesho_privacy_policy', wp_kses_post($_POST['privacy_policy']) );
        }
        if ( isset($_POST['vesho_contract_terms']) ) {
            update_option( 'vesho_contract_terms', wp_kses_post($_POST['vesho_contract_terms']) );
        }
        if ( isset($_POST['cookie_banner_text']) ) {
            update_option( 'vesho_cookie_banner_text', sanitize_textarea_field($_POST['cookie_banner_text']) );
        }
        // Checkboxes — unchecked = 0, checked = 1
        foreach ( array(
            'portal_registration','portal_show_devices','portal_show_maintenances',
            'portal_show_services','portal_show_invoices','portal_show_support','show_contract_terms',
            'notify_new_request','notify_new_ticket','notify_invoice_paid','notify_new_client',
            'notify_maintenance_reminder','notify_low_stock','notify_worker_shift',
            'low_stock_alert','worker_reminder','cookie_banner','maintenance_mode','coming_soon_mode','geofence_warn_only',
            'stripe_enabled','mc_enabled','mc_sandbox','montonio_enabled','montonio_sandbox',
            'shop_ship_pickup_enabled','shop_ship_courier_enabled','shop_ship_parcelshop_enabled',
            'cookie_banner_enabled',
        ) as $f ) {
            update_option( 'vesho_'.$f, isset($_POST[$f]) ? '1' : '0' );
        }
        if ( function_exists('vesho_crm_log_activity') ) {
            vesho_crm_log_activity('settings_saved', 'Seaded salvestatud');
        }
        // Purge all caches when maintenance/coming-soon mode changes
        do_action( 'litespeed_purge_all' );
        do_action( 'w3tc_flush_all' );
        if ( function_exists( 'wp_cache_flush' ) ) wp_cache_flush();
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-settings','msg'=>'saved'), admin_url('admin.php') ) );
        exit;
    }

    // ── Work Hours ────────────────────────────────────────────────────────────
    public static function handle_save_workhours() {
        check_admin_referer( 'vesho_save_workhours' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_POST['workhour_id'] ?? 0 );
        $data = array(
            'worker_id'    => absint($_POST['worker_id']??0),
            'workorder_id' => absint($_POST['workorder_id']??0) ?: null,
            'date'         => sanitize_text_field($_POST['work_date']??date('Y-m-d')),
            'hours'        => (float)($_POST['hours']??0),
            'description'  => sanitize_textarea_field($_POST['description']??''),
        );
        if ( $id ) { $wpdb->update($wpdb->prefix.'vesho_work_hours',$data,array('id'=>$id)); $msg='updated'; }
        else { $data['created_at']=current_time('mysql'); $wpdb->insert($wpdb->prefix.'vesho_work_hours',$data); $msg='added'; }
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-workhours','msg'=>$msg), admin_url('admin.php') ) );
        exit;
    }

    public static function handle_delete_workhours() {
        check_admin_referer( 'vesho_delete_workhours' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_GET['workhour_id'] ?? 0 );
        if ( $id ) $wpdb->delete($wpdb->prefix.'vesho_work_hours', array('id'=>$id));
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-workhours','msg'=>'deleted'), admin_url('admin.php') ) );
        exit;
    }

    // ── Stock Receipt ─────────────────────────────────────────────────────────
    public static function handle_save_receipt() {
        check_admin_referer( 'vesho_save_receipt' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $receipt = array(
            'receipt_date'     => sanitize_text_field($_POST['receipt_date']??date('Y-m-d')),
            'reference_number' => sanitize_text_field($_POST['reference_number']??''),
            'supplier'         => sanitize_text_field($_POST['supplier']??''),
            'notes'            => sanitize_textarea_field($_POST['notes']??''),
            'created_at'       => current_time('mysql'),
        );
        $wpdb->insert($wpdb->prefix.'vesho_stock_receipts', $receipt);
        $receipt_id = $wpdb->insert_id;
        if ( $receipt_id && ! empty($_POST['lines']) && is_array($_POST['lines']) ) {
            foreach ( $_POST['lines'] as $line ) {
                $inv_id  = absint($line['inventory_id']??0);
                $qty     = (float)($line['quantity']??0);
                $price   = (float)($line['unit_price']??0);
                $batch   = sanitize_text_field($line['batch_number']??'');
                if (!$inv_id || $qty <= 0) continue;
                $wpdb->insert($wpdb->prefix.'vesho_stock_receipt_items', array(
                    'receipt_id'   => $receipt_id,
                    'inventory_id' => $inv_id,
                    'quantity'     => $qty,
                    'unit_price'   => $price,
                    'batch_number' => $batch,
                ));
                // Update stock quantity
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->prefix}vesho_inventory SET quantity = quantity + %f WHERE id = %d",
                    $qty, $inv_id
                ));
            }
        }
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-receipts','msg'=>'added'), admin_url('admin.php') ) );
        exit;
    }

    // ── Email notification helper ─────────────────────────────────────────────
    private static function send_admin_notification( $type, $subject, $body ) {
        if ( get_option( 'vesho_notify_' . $type, '1' ) !== '1' ) return;
        $to = get_option( 'vesho_notify_email', get_option('admin_email') );
        if ( ! $to ) return;
        wp_mail( $to, '[Vesho CRM] ' . $subject, $body );
    }

    // ── Client: send portal access ────────────────────────────────────────────
    public static function handle_client_send_access() {
        check_admin_referer( 'vesho_client_send_access' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id     = absint( $_GET['client_id'] ?? 0 );
        $client = $id ? Vesho_CRM_Database::get_client( $id ) : null;
        if ( ! $client ) {
            wp_redirect( add_query_arg( ['page'=>'vesho-crm-clients','msg'=>'error'], admin_url('admin.php') ) );
            exit;
        }
        $new_pass = wp_generate_password( 10, false );

        // Create or update WP user
        if ( $client->user_id ) {
            wp_set_password( $new_pass, (int) $client->user_id );
        } else {
            $user_id = get_user_by( 'email', $client->email );
            if ( $user_id ) {
                $user_id = $user_id->ID;
                wp_set_password( $new_pass, $user_id );
                ( new WP_User( $user_id ) )->add_role( 'vesho_client' );
            } else {
                $user_id = wp_create_user( $client->email, $new_pass, $client->email );
                if ( ! is_wp_error( $user_id ) ) {
                    ( new WP_User( $user_id ) )->set_role( 'vesho_client' );
                    wp_update_user( ['ID'=>$user_id,'display_name'=>$client->name] );
                } else {
                    $user_id = null;
                }
            }
            if ( $user_id ) {
                $wpdb->update( $wpdb->prefix.'vesho_clients', ['user_id'=>$user_id], ['id'=>$id] );
                $wpdb->update( $wpdb->prefix.'vesho_clients', ['password'=>wp_hash_password($new_pass)], ['id'=>$id] );
            }
        }

        // Send email
        $portal_url = home_url('/klient/');
        $co = get_option('vesho_company_name','Vesho OÜ');
        $subject = $co . ' — portaali ligipääs';
        $body  = "Tere, {$client->name}!\n\n";
        $body .= "Siin on teie portaali sisselogimisandmed:\n\n";
        $body .= "Portaal: {$portal_url}\n";
        $body .= "Kasutajanimi: {$client->email}\n";
        $body .= "Parool: {$new_pass}\n\n";
        $body .= "Lugupidamisega,\n{$co}";
        wp_mail( $client->email, $subject, $body );

        if ( function_exists('vesho_crm_log_activity') ) {
            vesho_crm_log_activity('access_sent', "Portaali ligipääs saadetud: {$client->name} ({$client->email})", 'client', $id);
        }

        wp_redirect( add_query_arg( ['page'=>'vesho-crm-clients','msg'=>'access_sent'], admin_url('admin.php') ) );
        exit;
    }

    // ── Worker: send PIN email ────────────────────────────────────────────────
    public static function handle_send_worker_pin() {
        check_admin_referer( 'vesho_send_worker_pin' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id     = absint( $_GET['worker_id'] ?? 0 );
        $worker = $id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}vesho_workers WHERE id=%d", $id ) ) : null;
        if ( ! $worker || empty( $worker->work_email ) ) {
            wp_redirect( add_query_arg( ['page'=>'vesho-crm-workers','msg'=>'no_email'], admin_url('admin.php') ) );
            exit;
        }
        $new_pin = (string) rand(100000, 999999);
        $update_data = ['password'=>wp_hash_password($new_pin),'pin'=>$new_pin];
        // Generate barcode token if missing (like Node.js send-pin behavior)
        $barcode_token = $worker->barcode_token;
        if ( empty( $barcode_token ) ) {
            $barcode_token = bin2hex( random_bytes( 8 ) );
            $update_data['barcode_token'] = $barcode_token;
        }
        $wpdb->update( $wpdb->prefix.'vesho_workers', $update_data, ['id'=>$id] );
        if ( $worker->user_id ) {
            wp_set_password( $new_pin, (int) $worker->user_id );
        }
        $co = get_option('vesho_company_name','Vesho OÜ');
        $portal_url = home_url('/worker/');
        $subject = $co . ' — portaali PIN';
        $body  = "Tere, {$worker->name}!\n\n";
        $body .= "Uus portaali PIN:\n\n";
        $body .= "Portaal: {$portal_url}\n";
        $body .= "Kasutajanimi: {$worker->name}\n";
        $body .= "PIN: {$new_pin}\n\n";
        $body .= "QR kood tööaja registreerimiseks: {$barcode_token}\n\n";
        $body .= "Lugupidamisega,\n{$co}";
        wp_mail( $worker->work_email, $subject, $body );

        wp_redirect( add_query_arg( ['page'=>'vesho-crm-workers','action'=>'edit','worker_id'=>$id,'msg'=>'pin_sent'], admin_url('admin.php') ) );
        exit;
    }

    // ── Invoice: send email ───────────────────────────────────────────────────
    public static function handle_send_invoice_email() {
        check_admin_referer( 'vesho_send_invoice_email' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id  = absint( $_GET['invoice_id'] ?? 0 );
        $inv = $id ? $wpdb->get_row( $wpdb->prepare(
            "SELECT i.*, c.name as client_name, c.email as client_email
             FROM {$wpdb->prefix}vesho_invoices i
             LEFT JOIN {$wpdb->prefix}vesho_clients c ON i.client_id=c.id
             WHERE i.id=%d", $id
        ) ) : null;
        if ( ! $inv || ! $inv->client_email ) {
            wp_redirect( add_query_arg( ['page'=>'vesho-crm-invoices','msg'=>'no_email'], admin_url('admin.php') ) );
            exit;
        }
        $items = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_invoice_items WHERE invoice_id=%d ORDER BY id ASC", $id
        ) );
        $co = get_option('vesho_company_name','Vesho OÜ');
        $subject = $co . ' — Arve ' . $inv->invoice_number;
        $body  = "Tere, {$inv->client_name}!\n\n";
        $body .= "Saadame Teile arve {$inv->invoice_number}.\n\n";
        if ( ! empty($items) ) {
            $body .= "Arve read:\n";
            foreach ( $items as $item ) {
                $body .= "  - {$item->description}: " . number_format($item->quantity,2,',','.') . ' × ' . number_format($item->unit_price,2,',','.') . " € = " . number_format($item->total,2,',','.') . " €\n";
            }
            $body .= "\n";
        }
        $body .= "Kokku: " . number_format($inv->amount,2,',','.') . " €\n";
        if ( $inv->due_date ) $body .= "Tähtaeg: " . date('d.m.Y', strtotime($inv->due_date)) . "\n";
        $iban = get_option('vesho_company_iban','');
        if ( $iban ) $body .= "IBAN: {$iban}\n";
        $body .= "\nLugupidamisega,\n{$co}";
        wp_mail( $inv->client_email, $subject, $body );
        // Mark as sent
        if ( $inv->status === 'unpaid' || $inv->status === 'draft' ) {
            $wpdb->update( $wpdb->prefix.'vesho_invoices', ['status'=>'sent'], ['id'=>$id] );
        }
        wp_redirect( add_query_arg( ['page'=>'vesho-crm-invoices','msg'=>'email_sent'], admin_url('admin.php') ) );
        exit;
    }

    // ── Portal notices ────────────────────────────────────────────────────────
    public static function handle_save_notice() {
        check_admin_referer( 'vesho_save_notice' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $data = [
            'title'     => sanitize_text_field( $_POST['notice_title'] ?? '' ),
            'message'   => sanitize_textarea_field( $_POST['notice_message'] ?? '' ),
            'target'    => sanitize_text_field( $_POST['notice_target'] ?? 'both' ),
            'starts_at' => sanitize_text_field( $_POST['notice_starts'] ?? '' ) ?: null,
            'ends_at'   => sanitize_text_field( $_POST['notice_ends'] ?? '' ) ?: null,
            'active'    => 1,
            'created_at'=> current_time('mysql'),
        ];
        $wpdb->insert( $wpdb->prefix.'vesho_portal_notices', $data );
        wp_redirect( add_query_arg( ['page'=>'vesho-crm-settings','msg'=>'saved'], admin_url('admin.php') ) );
        exit;
    }

    public static function handle_delete_notice() {
        check_admin_referer( 'vesho_delete_notice' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_GET['notice_id'] ?? 0 );
        if ( $id ) $wpdb->delete( $wpdb->prefix.'vesho_portal_notices', ['id'=>$id] );
        wp_redirect( add_query_arg( ['page'=>'vesho-crm-settings','msg'=>'saved'], admin_url('admin.php') ) );
        exit;
    }

    // ── Campaign ──────────────────────────────────────────────────────────────
    public static function handle_save_campaign() {
        check_admin_referer( 'vesho_save_campaign' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_POST['campaign_id'] ?? 0 );
        $data = array(
            'name'             => sanitize_text_field($_POST['name']??''),
            'discount_percent'             => strlen($_POST['discount_percent']??'') ? (float)$_POST['discount_percent'] : 0,
            'maintenance_discount_percent' => strlen($_POST['maintenance_discount_percent']??'') ? (float)$_POST['maintenance_discount_percent'] : 0,
            'free_shipping'    => isset($_POST['free_shipping']) ? 1 : 0,
            'target'           => sanitize_text_field($_POST['target']??'both'),
            'valid_from'       => sanitize_text_field($_POST['valid_from']??'') ?: null,
            'valid_until'      => sanitize_text_field($_POST['valid_until']??'') ?: null,
            'visible_to_guests'=> isset($_POST['visible_to_guests']) ? 1 : 0,
            'paused'           => isset($_POST['paused']) ? 1 : 0,
            'notes'            => sanitize_textarea_field($_POST['notes']??''),
        );
        if ( $id ) { $wpdb->update($wpdb->prefix.'vesho_campaigns',$data,array('id'=>$id)); }
        else { $data['created_at']=current_time('mysql'); $wpdb->insert($wpdb->prefix.'vesho_campaigns',$data); }
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-campaigns','msg'=>'saved'), admin_url('admin.php') ) );
        exit;
    }

    public static function handle_pause_campaign() {
        check_admin_referer( 'vesho_pause_campaign' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_GET['campaign_id'] ?? 0 );
        if ( $id ) $wpdb->update( $wpdb->prefix.'vesho_campaigns', array('paused'=>1), array('id'=>$id) );
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-campaigns','msg'=>'saved'), admin_url('admin.php') ) );
        exit;
    }

    public static function handle_resume_campaign() {
        check_admin_referer( 'vesho_resume_campaign' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_GET['campaign_id'] ?? 0 );
        if ( $id ) $wpdb->update( $wpdb->prefix.'vesho_campaigns', array('paused'=>0), array('id'=>$id) );
        wp_redirect( add_query_arg( array('page'=>'vesho-crm-campaigns','msg'=>'saved'), admin_url('admin.php') ) );
        exit;
    }

    // ── Shop Orders ───────────────────────────────────────────────────────────

    public static function handle_save_shop_order() {
        check_admin_referer( 'vesho_save_shop_order' );
        global $wpdb;

        $order_id   = absint( $_POST['order_id'] ?? 0 );
        $client_id  = absint( $_POST['client_id'] ?? 0 ) ?: null;
        $status     = sanitize_text_field( $_POST['status'] ?? 'new' );
        $ship_method= sanitize_text_field( $_POST['shipping_method'] ?? 'pickup' );
        $ship_price = (float) ( $_POST['shipping_price'] ?? 0 );
        $track      = sanitize_text_field( $_POST['tracking_number'] ?? '' );
        $notes      = sanitize_textarea_field( $_POST['notes'] ?? '' );
        $guest_name = sanitize_text_field( $_POST['guest_name'] ?? '' );
        $guest_email= sanitize_email( $_POST['guest_email'] ?? '' );
        $guest_phone= sanitize_text_field( $_POST['guest_phone'] ?? '' );
        $ship_addr  = sanitize_text_field( $_POST['shipping_address'] ?? '' );

        // Build items
        $inv_ids  = array_map('absint', (array)( $_POST['item_inventory_id'] ?? [] ));
        $names    = array_map('sanitize_text_field', (array)( $_POST['item_name'] ?? [] ));
        $qtys     = array_map('floatval', (array)( $_POST['item_qty'] ?? [] ));
        $prices   = array_map('floatval', (array)( $_POST['item_price'] ?? [] ));

        $items = [];
        foreach ( $inv_ids as $i => $inv_id ) {
            $qty   = $qtys[$i] ?? 0;
            $price = $prices[$i] ?? 0;
            $name  = $names[$i] ?? '';
            if ( $qty <= 0 && ! $name ) continue;
            $sku = '';
            if ( $inv_id ) {
                $inv = $wpdb->get_row( $wpdb->prepare( "SELECT name, sku FROM {$wpdb->prefix}vesho_inventory WHERE id=%d", $inv_id ) );
                if ( $inv ) {
                    $sku  = $inv->sku;
                    if ( ! $name ) $name = $inv->name;
                }
            }
            $items[] = [
                'inventory_id' => $inv_id ?: null,
                'name'         => $name,
                'sku'          => $sku,
                'quantity'     => $qty,
                'unit_price'   => $price,
                'total'        => round( $qty * $price, 2 ),
            ];
        }

        $subtotal = array_sum( array_column( $items, 'total' ) );
        $total    = round( $subtotal + $ship_price, 2 );

        $data = [
            'client_id'       => $client_id,
            'guest_name'      => $guest_name,
            'guest_email'     => $guest_email,
            'guest_phone'     => $guest_phone,
            'shipping_address'=> $ship_addr,
            'shipping_method' => $ship_method,
            'shipping_price'  => $ship_price,
            'subtotal'        => $subtotal,
            'total'           => $total,
            'status'          => $status,
            'tracking_number' => $track,
            'notes'           => $notes,
        ];

        if ( $order_id ) {
            $wpdb->update( $wpdb->prefix . 'vesho_shop_orders', $data, array( 'id' => $order_id ) );
            // Rebuild items
            $wpdb->delete( $wpdb->prefix . 'vesho_shop_order_items', array( 'order_id' => $order_id ) );
            $msg = 'updated';
        } else {
            // Generate order number
            $num  = (int) $wpdb->get_var( "SELECT COUNT(*)+1 FROM {$wpdb->prefix}vesho_shop_orders" );
            $data['order_number'] = 'ORD-' . str_pad( $num, 4, '0', STR_PAD_LEFT );
            $wpdb->insert( $wpdb->prefix . 'vesho_shop_orders', $data );
            $order_id = $wpdb->insert_id;
            $msg = 'added';
        }

        foreach ( $items as $it ) {
            $it['order_id'] = $order_id;
            $wpdb->insert( $wpdb->prefix . 'vesho_shop_order_items', $it );
        }

        wp_redirect( add_query_arg( array( 'page' => 'vesho-crm-orders', 'msg' => $msg ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function handle_delete_shop_order() {
        check_admin_referer( 'vesho_delete_shop_order' );
        global $wpdb;
        $order_id = absint( $_GET['order_id'] ?? 0 );
        if ( $order_id ) {
            // Restore stock for cancelled orders
            $items = $wpdb->get_results( $wpdb->prepare(
                "SELECT inventory_id, quantity FROM {$wpdb->prefix}vesho_shop_order_items WHERE order_id=%d AND inventory_id IS NOT NULL",
                $order_id
            ) );
            foreach ( $items as $it ) {
                $wpdb->query( $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}vesho_inventory SET quantity = quantity + %f WHERE id = %d",
                    $it->quantity, $it->inventory_id
                ) );
            }
            $wpdb->delete( $wpdb->prefix . 'vesho_shop_order_items', array( 'order_id' => $order_id ) );
            $wpdb->delete( $wpdb->prefix . 'vesho_shop_orders',      array( 'id'       => $order_id ) );
        }
        wp_redirect( add_query_arg( array( 'page' => 'vesho-crm-orders', 'msg' => 'deleted' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function handle_shop_order_status() {
        check_admin_referer( 'vesho_shop_order_status' );
        global $wpdb;
        $order_id = absint( $_POST['order_id'] ?? 0 );
        $status   = sanitize_text_field( $_POST['status'] ?? '' );
        $tracking = sanitize_text_field( $_POST['tracking_number'] ?? '' );

        if ( ! $order_id || ! $status ) {
            wp_redirect( admin_url( 'admin.php?page=vesho-crm-orders' ) );
            exit;
        }

        $upd = array( 'status' => $status );
        if ( $tracking ) $upd['tracking_number'] = $tracking;

        // Restore stock on cancel
        if ( $status === 'cancelled' ) {
            $items = $wpdb->get_results( $wpdb->prepare(
                "SELECT inventory_id, quantity FROM {$wpdb->prefix}vesho_shop_order_items WHERE order_id=%d AND inventory_id IS NOT NULL",
                $order_id
            ) );
            foreach ( $items as $it ) {
                $wpdb->query( $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}vesho_inventory SET quantity = quantity + %f WHERE id = %d",
                    $it->quantity, $it->inventory_id
                ) );
            }
        }
        // Restore stock on returned
        if ( $status === 'returned' ) {
            $items = $wpdb->get_results( $wpdb->prepare(
                "SELECT inventory_id, COALESCE(picked_qty, quantity) as qty FROM {$wpdb->prefix}vesho_shop_order_items WHERE order_id=%d AND inventory_id IS NOT NULL",
                $order_id
            ) );
            foreach ( $items as $it ) {
                $wpdb->query( $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}vesho_inventory SET quantity = quantity + %f WHERE id = %d",
                    $it->qty, $it->inventory_id
                ) );
            }
        }
        // Deduct stock when moving to picking
        if ( $status === 'picking' ) {
            $order = $wpdb->get_row( $wpdb->prepare( "SELECT status FROM {$wpdb->prefix}vesho_shop_orders WHERE id=%d", $order_id ) );
            if ( $order && $order->status === 'new' ) {
                $items = $wpdb->get_results( $wpdb->prepare(
                    "SELECT inventory_id, quantity FROM {$wpdb->prefix}vesho_shop_order_items WHERE order_id=%d AND inventory_id IS NOT NULL",
                    $order_id
                ) );
                foreach ( $items as $it ) {
                    $wpdb->query( $wpdb->prepare(
                        "UPDATE {$wpdb->prefix}vesho_inventory SET quantity = GREATEST(0, quantity - %f) WHERE id = %d",
                        $it->quantity, $it->inventory_id
                    ) );
                }
            }
        }

        // ── Partial picking: calculate refund_pending_amount ──────────────────
        if ( $status === 'ready' ) {
            $items = $wpdb->get_results( $wpdb->prepare(
                "SELECT quantity, COALESCE(picked_qty, quantity) as pq, unit_price
                 FROM {$wpdb->prefix}vesho_shop_order_items WHERE order_id=%d",
                $order_id
            ) );
            $diff = 0.0;
            foreach ( $items as $it ) {
                if ( (float)$it->pq < (float)$it->quantity ) {
                    $diff += ( (float)$it->quantity - (float)$it->pq ) * (float)$it->unit_price;
                }
            }
            if ( $diff > 0.005 ) {
                $upd['refund_pending_amount'] = round( $diff, 2 );
            }
        }
        // Clear pending refund when order is completed or refunded
        if ( in_array( $status, ['fulfilled', 'returned', 'cancelled'] ) ) {
            $upd['refund_pending_amount'] = 0.00;
        }

        $wpdb->update( $wpdb->prefix . 'vesho_shop_orders', $upd, array( 'id' => $order_id ) );

        // Return to view page if came from there
        $ref = wp_get_referer();
        if ( $ref && strpos( $ref, 'action=view' ) !== false ) {
            wp_redirect( add_query_arg( 'msg', 'status', $ref ) );
        } else {
            wp_redirect( add_query_arg( array( 'page' => 'vesho-crm-orders', 'msg' => 'status' ), admin_url( 'admin.php' ) ) );
        }
        exit;
    }

    public static function handle_bulk_shop_orders() {
        check_admin_referer( 'vesho_bulk_shop_orders' );
        global $wpdb;
        $bulk_action = sanitize_text_field( $_POST['bulk_action'] ?? '' );
        $order_ids   = array_map( 'absint', (array)( $_POST['order_ids'] ?? [] ) );

        if ( $bulk_action === 'send_to_workers' && ! empty( $order_ids ) ) {
            foreach ( $order_ids as $oid ) {
                $order = $wpdb->get_row( $wpdb->prepare( "SELECT status FROM {$wpdb->prefix}vesho_shop_orders WHERE id=%d", $oid ) );
                if ( $order && $order->status === 'new' ) {
                    $wpdb->update( $wpdb->prefix . 'vesho_shop_orders', array( 'status' => 'picking' ), array( 'id' => $oid ) );
                    // Deduct stock
                    $items = $wpdb->get_results( $wpdb->prepare(
                        "SELECT inventory_id, quantity FROM {$wpdb->prefix}vesho_shop_order_items WHERE order_id=%d AND inventory_id IS NOT NULL",
                        $oid
                    ) );
                    foreach ( $items as $it ) {
                        $wpdb->query( $wpdb->prepare(
                            "UPDATE {$wpdb->prefix}vesho_inventory SET quantity = GREATEST(0, quantity - %f) WHERE id = %d",
                            $it->quantity, $it->inventory_id
                        ) );
                    }
                }
            }
        }

        wp_redirect( add_query_arg( array( 'page' => 'vesho-crm-orders', 'msg' => 'sent' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public static function handle_pick_item() {
        check_admin_referer( 'vesho_pick_item' );
        global $wpdb;
        $item_id   = absint( $_POST['item_id']   ?? 0 );
        $order_id  = absint( $_POST['order_id']  ?? 0 );
        $picked_qty= (float)( $_POST['picked_qty'] ?? 0 );

        if ( $item_id ) {
            $item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}vesho_shop_order_items WHERE id=%d", $item_id ) );
            if ( $item ) {
                $picked = ( $picked_qty >= $item->quantity ) ? 1 : 0;
                $wpdb->update( $wpdb->prefix . 'vesho_shop_order_items', array(
                    'picked'     => $picked,
                    'picked_qty' => $picked_qty,
                ), array( 'id' => $item_id ) );
            }
        }

        $ref = wp_get_referer() ?: admin_url( 'admin.php?page=vesho-crm-orders&action=view&order_id=' . $order_id );
        wp_redirect( $ref );
        exit;
    }

    // ── Reminder: confirm maintenance ────────────────────────────────────────
    public static function handle_confirm_maintenance() {
        check_admin_referer( 'vesho_confirm_maintenance' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id   = absint( $_POST['maintenance_id'] ?? 0 );
        $date = sanitize_text_field( $_POST['scheduled_date'] ?? '' );
        if ( $id ) {
            $wpdb->update(
                $wpdb->prefix . 'vesho_maintenances',
                array( 'status' => 'scheduled', 'scheduled_date' => $date ?: null ),
                array( 'id' => $id )
            );
        }
        wp_redirect( add_query_arg( array( 'page' => 'vesho-crm-reminders', 'msg' => 'confirmed' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    // ── Reminder: reject maintenance ─────────────────────────────────────────
    public static function handle_reject_maintenance() {
        $id = absint( $_GET['maintenance_id'] ?? 0 );
        check_admin_referer( 'vesho_reject_maintenance_' . $id );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        if ( $id ) {
            $wpdb->update(
                $wpdb->prefix . 'vesho_maintenances',
                array( 'status' => 'cancelled' ),
                array( 'id' => $id )
            );
        }
        wp_redirect( add_query_arg( array( 'page' => 'vesho-crm-reminders', 'msg' => 'rejected' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    // ── Reminder: cancel maintenance ─────────────────────────────────────────
    public static function handle_cancel_maintenance() {
        $id = absint( $_GET['maintenance_id'] ?? 0 );
        check_admin_referer( 'vesho_cancel_maintenance_' . $id );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        if ( $id ) {
            $wpdb->update(
                $wpdb->prefix . 'vesho_maintenances',
                array( 'status' => 'cancelled' ),
                array( 'id' => $id )
            );
        }
        wp_redirect( add_query_arg( array( 'page' => 'vesho-crm-reminders', 'msg' => 'cancelled' ), admin_url( 'admin.php' ) ) );
        exit;
    }

    // ── AJAX: search WP users ─────────────────────────────────────────────────
    public static function ajax_search_wp_users() {
        check_ajax_referer( 'vesho_admin_nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
        $q = sanitize_text_field( $_GET['q'] ?? '' );
        if ( strlen( $q ) < 2 ) wp_send_json_error();

        $users = get_users([
            'search'         => '*' . $q . '*',
            'search_columns' => ['user_login','user_email','display_name'],
            'number'         => 10,
            'orderby'        => 'display_name',
        ]);

        $already = array_map(
            fn($u) => $u->ID,
            get_users(['role__in' => ['administrator','vesho_crm_admin'], 'fields' => 'ID', 'number' => 500])
        );

        $result = [];
        foreach ( $users as $u ) {
            if ( in_array( $u->ID, $already ) ) continue;
            $result[] = [
                'id'      => $u->ID,
                'display' => esc_html( $u->display_name ),
                'login'   => esc_html( $u->user_login ),
                'email'   => esc_html( $u->user_email ),
                'label'   => esc_attr( $u->display_name . ' (' . $u->user_email . ')' ),
            ];
        }
        wp_send_json_success( $result );
    }

    // ── AJAX: add maintenance ─────────────────────────────────────────────────
    public static function ajax_add_maintenance() {
        check_ajax_referer( 'vesho_admin_nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        global $wpdb;
        $device_id  = absint( $_POST['device_id'] ?? 0 );
        $date       = sanitize_text_field( $_POST['scheduled_date'] ?? '' );
        $worker_id  = absint( $_POST['worker_id'] ?? 0 );
        $desc       = sanitize_textarea_field( $_POST['description'] ?? '' );
        if ( ! $device_id || ! $date ) {
            wp_send_json_error( 'Puuduvad kohustuslikud väljad.' );
        }
        $wpdb->insert( $wpdb->prefix . 'vesho_maintenances', array(
            'device_id'      => $device_id,
            'scheduled_date' => $date,
            'status'         => 'scheduled',
            'description'    => $desc,
            'worker_id'      => $worker_id ?: null,
            'created_at'     => current_time( 'mysql' ),
        ) );
        wp_send_json_success( array( 'id' => $wpdb->insert_id ) );
    }

    // ── AJAX: get client devices ──────────────────────────────────────────────
    public static function ajax_get_client_devices() {
        check_ajax_referer( 'vesho_admin_nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        global $wpdb;
        $client_id = absint( $_POST['client_id'] ?? 0 );
        if ( ! $client_id ) wp_send_json_error( 'Puudub client_id.' );
        $devices = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, name FROM {$wpdb->prefix}vesho_devices WHERE client_id=%d ORDER BY name ASC",
            $client_id
        ) );
        $result = array();
        foreach ( $devices as $d ) {
            $result[] = array( 'id' => (int)$d->id, 'name' => $d->name );
        }
        wp_send_json_success( $result );
    }

    // ── AJAX: postpone maintenance ────────────────────────────────────────────
    public static function ajax_postpone_maintenance() {
        check_ajax_referer( 'vesho_admin_nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        global $wpdb;
        $id      = absint( $_POST['maintenance_id'] ?? 0 );
        $new_date = sanitize_text_field( $_POST['new_date'] ?? '' );
        if ( ! $id || ! $new_date ) wp_send_json_error( 'Puuduvad andmed.' );
        $wpdb->update(
            $wpdb->prefix . 'vesho_maintenances',
            array( 'scheduled_date' => $new_date ),
            array( 'id' => $id )
        );
        wp_send_json_success();
    }

    // ── AJAX: create credit note ──────────────────────────────────────────────
    public static function ajax_create_credit_note() {
        check_ajax_referer( 'vesho_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );
        global $wpdb;
        $invoice_id = absint( $_POST['invoice_id'] ?? 0 );
        $amount     = (float) ( $_POST['amount'] ?? 0 );
        $reason     = sanitize_text_field( $_POST['reason'] ?? '' );

        $inv = $invoice_id ? $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_invoices WHERE id=%d", $invoice_id
        ) ) : null;
        if ( ! $inv ) wp_send_json_error( 'Arvet ei leitud' );

        // Generate credit note number: INV-2026-001 → KARV-2026-001, or prepend KARV-
        $cn_number = preg_match( '/^ARV-/i', $inv->invoice_number )
            ? preg_replace( '/^ARV-/i', 'KARV-', $inv->invoice_number )
            : 'KARV-' . $inv->invoice_number;

        // Make unique if already exists
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_credit_notes WHERE credit_note_number=%s", $cn_number
        ) );
        if ( $existing ) {
            $cn_number .= '-' . date( 'His' );
        }

        $today = current_time( 'Y-m-d' );
        $wpdb->insert( $wpdb->prefix . 'vesho_credit_notes', array(
            'credit_note_number' => $cn_number,
            'invoice_id'         => $invoice_id,
            'client_id'          => $inv->client_id,
            'amount'             => $amount > 0 ? $amount : abs( (float) $inv->amount ),
            'reason'             => $reason,
            'status'             => 'issued',
            'issued_date'        => $today,
            'created_at'         => current_time( 'mysql' ),
        ) );

        wp_send_json_success( array( 'number' => $cn_number ) );
    }

    // ── Generate worker barcode token ─────────────────────────────────────────
    public static function handle_generate_worker_barcode() {
        check_admin_referer( 'vesho_generate_worker_barcode' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;
        $id = absint( $_GET['worker_id'] ?? 0 );
        if ( ! $id ) wp_die( 'Puudub töötaja ID' );
        $token = bin2hex( random_bytes( 8 ) );
        $wpdb->update( $wpdb->prefix . 'vesho_workers', array( 'barcode_token' => $token ), array( 'id' => $id ) );
        wp_redirect( add_query_arg( array(
            'page'      => 'vesho-crm-workers',
            'action'    => 'edit',
            'worker_id' => $id,
            'msg'       => 'barcode_generated',
        ), admin_url( 'admin.php' ) ) );
        exit;
    }

    // ── Approve return request ────────────────────────────────────────────────

    public static function handle_approve_return() {
        check_admin_referer( 'vesho_approve_return' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;

        $order_id    = absint( $_POST['order_id'] ?? 0 );
        $disposition = sanitize_text_field( $_POST['disposition'] ?? 'stock' );
        if ( ! in_array( $disposition, ['stock','used','writeoff'], true ) ) $disposition = 'stock';

        $order = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_shop_orders WHERE id=%d AND status='return_requested'", $order_id
        ) );
        if ( ! $order ) {
            wp_redirect( add_query_arg( ['page'=>'vesho-crm-orders','msg'=>'err'], admin_url('admin.php') ) );
            exit;
        }

        $items = $wpdb->get_results( $wpdb->prepare(
            "SELECT soi.inventory_id, soi.quantity, soi.name FROM {$wpdb->prefix}vesho_shop_order_items soi
             WHERE soi.order_id=%d AND soi.inventory_id IS NOT NULL AND soi.inventory_id > 0", $order_id
        ) );

        foreach ( $items as $item ) {
            if ( $disposition === 'stock' ) {
                $wpdb->query( $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}vesho_inventory SET quantity = quantity + %f WHERE id=%d",
                    $item->quantity, $item->inventory_id
                ) );
            } elseif ( $disposition === 'used' ) {
                $wpdb->query( $wpdb->prepare(
                    "UPDATE {$wpdb->prefix}vesho_inventory SET quantity = quantity + %f, used_quantity = used_quantity + %f WHERE id=%d",
                    $item->quantity, $item->quantity, $item->inventory_id
                ) );
            } elseif ( $disposition === 'writeoff' ) {
                $wpdb->insert( $wpdb->prefix . 'vesho_inventory_writeoffs', [
                    'inventory_id' => $item->inventory_id,
                    'product_name' => $item->name,
                    'quantity'     => $item->quantity,
                    'reason'       => 'Tellimuse tagastus #' . $order->order_number,
                    'written_off_at' => current_time('mysql'),
                ] );
            }
        }

        // Issue refund if paid
        if ( $order->paid_at && $order->payment_method ) {
            self::do_payment_refund( $order, (float) $order->total );
        }

        $wpdb->update( $wpdb->prefix . 'vesho_shop_orders', ['status' => 'returned'], ['id' => $order_id] );

        wp_redirect( add_query_arg( ['page'=>'vesho-crm-orders','action'=>'view','order_id'=>$order_id,'msg'=>'return_approved'], admin_url('admin.php') ) );
        exit;
    }

    // ── Reject return request ─────────────────────────────────────────────────

    public static function handle_reject_return() {
        check_admin_referer( 'vesho_reject_return' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;

        $order_id = absint( $_POST['order_id'] ?? 0 );
        $order = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_shop_orders WHERE id=%d AND status='return_requested'", $order_id
        ) );
        if ( ! $order ) {
            wp_redirect( add_query_arg( ['page'=>'vesho-crm-orders','msg'=>'err'], admin_url('admin.php') ) );
            exit;
        }

        // Revert to fulfilled (order was fulfilled before return request)
        $wpdb->update( $wpdb->prefix . 'vesho_shop_orders',
            ['status' => 'fulfilled', 'return_reason' => '', 'return_description' => ''],
            ['id' => $order_id]
        );

        wp_redirect( add_query_arg( ['page'=>'vesho-crm-orders','action'=>'view','order_id'=>$order_id,'msg'=>'return_rejected'], admin_url('admin.php') ) );
        exit;
    }

    // ── Export invoices as CSV ────────────────────────────────────────────────
    public static function handle_export_invoices_csv() {
        check_admin_referer( 'vesho_export_invoices_csv' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT i.invoice_number, c.name as client_name, i.invoice_date, i.due_date,
                    i.amount, i.status, i.description
             FROM {$wpdb->prefix}vesho_invoices i
             LEFT JOIN {$wpdb->prefix}vesho_clients c ON i.client_id=c.id
             ORDER BY i.invoice_date DESC",
            ARRAY_A
        );

        $status_labels = array( 'draft'=>'Mustand','sent'=>'Saadetud','paid'=>'Makstud','unpaid'=>'Maksmata','overdue'=>'Tähtaeg ületatud' );
        $filename = 'arved-' . date( 'Y-m-d' ) . '.csv';

        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );
        echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
        $out = fopen( 'php://output', 'w' );
        fputcsv( $out, array( 'Arve nr', 'Klient', 'Arve kuupäev', 'Tähtaeg', 'Summa (€)', 'Staatus', 'Kirjeldus' ), ';' );
        foreach ( $rows as $r ) {
            fputcsv( $out, array(
                $r['invoice_number'],
                $r['client_name'],
                $r['invoice_date'] ? date( 'd.m.Y', strtotime( $r['invoice_date'] ) ) : '',
                $r['due_date']     ? date( 'd.m.Y', strtotime( $r['due_date'] ) )     : '',
                number_format( (float) $r['amount'], 2, ',', '' ),
                $status_labels[ $r['status'] ] ?? $r['status'],
                $r['description'] ?? '',
            ), ';' );
        }
        fclose( $out );
        exit;
    }

    // ── Export maintenances as CSV ────────────────────────────────────────────
    public static function handle_export_maintenances_csv() {
        check_admin_referer( 'vesho_export_maintenances_csv' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );
        global $wpdb;

        $rows = $wpdb->get_results(
            "SELECT c.name as client_name, c.address as client_address,
                    d.name as device_name, d.model as device_model, d.serial_number,
                    m.scheduled_date, m.completed_date, m.status, m.description
             FROM {$wpdb->prefix}vesho_maintenances m
             JOIN {$wpdb->prefix}vesho_devices d ON d.id = m.device_id
             JOIN {$wpdb->prefix}vesho_clients c ON c.id = d.client_id
             ORDER BY m.scheduled_date DESC",
            ARRAY_A
        );

        $status_labels = array( 'scheduled'=>'Planeeritud','completed'=>'Tehtud','overdue'=>'Hilines','pending'=>'Ootel','cancelled'=>'Tühistatud' );
        $filename = 'hooldused-' . date( 'Y-m-d' ) . '.csv';

        header( 'Content-Type: text/csv; charset=UTF-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );
        echo "\xEF\xBB\xBF";
        $out = fopen( 'php://output', 'w' );
        fputcsv( $out, array( 'Klient', 'Aadress', 'Seade', 'Mudel', 'Seerianumber', 'Planeeritud kuupäev', 'Tehtud kuupäev', 'Staatus', 'Kirjeldus' ), ';' );
        foreach ( $rows as $r ) {
            fputcsv( $out, array(
                $r['client_name'],
                $r['client_address'] ?? '',
                $r['device_name'],
                $r['device_model'] ?? '',
                $r['serial_number'] ?? '',
                $r['scheduled_date'] ? date( 'd.m.Y', strtotime( $r['scheduled_date'] ) ) : '',
                $r['completed_date'] ? date( 'd.m.Y', strtotime( $r['completed_date'] ) ) : '',
                $status_labels[ $r['status'] ] ?? $r['status'],
                $r['description'] ?? '',
            ), ';' );
        }
        fclose( $out );
        exit;
    }

    // ── Partial refund helpers ─────────────────────────────────────────────────

    /**
     * Issue the pending partial refund for an order (called via AJAX).
     * Handles Stripe and Maksekeskus automatically; Montonio requires manual action.
     */
    public static function ajax_order_issue_refund() {
        check_ajax_referer( 'vesho_portal_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( ['message' => 'Pole lubatud'] );
        }
        global $wpdb;
        $order_id = absint( $_POST['order_id'] ?? 0 );
        if ( ! $order_id ) wp_send_json_error( ['message' => 'Vale tellimus'] );

        $order = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_shop_orders WHERE id=%d", $order_id
        ) );
        if ( ! $order ) wp_send_json_error( ['message' => 'Tellimust ei leitud'] );

        $amount = round( (float)( $order->refund_pending_amount ?? 0 ), 2 );
        if ( $amount <= 0 ) wp_send_json_error( ['message' => 'Tagasimakset pole ootel'] );

        $result = self::do_payment_refund( $order, $amount );

        if ( $result['done'] ) {
            $wpdb->update( $wpdb->prefix . 'vesho_shop_orders', [
                'refund_pending_amount' => 0.00,
                'refund_amount'         => (float)$order->refund_amount + $amount,
            ], ['id' => $order_id] );
            wp_send_json_success( ['message' => $result['message']] );
        } else {
            wp_send_json_error( ['message' => $result['message']] );
        }
    }

    /**
     * Manual partial refund — admin specifies amount (called via AJAX).
     */
    public static function ajax_order_manual_refund() {
        check_ajax_referer( 'vesho_portal_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( ['message' => 'Pole lubatud'] );
        }
        global $wpdb;
        $order_id = absint( $_POST['order_id'] ?? 0 );
        $amount   = round( (float)( $_POST['amount'] ?? 0 ), 2 );
        if ( ! $order_id || $amount <= 0 ) wp_send_json_error( ['message' => 'Vale sisend'] );

        $order = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_shop_orders WHERE id=%d", $order_id
        ) );
        if ( ! $order ) wp_send_json_error( ['message' => 'Tellimust ei leitud'] );

        $result = self::do_payment_refund( $order, $amount );

        if ( $result['done'] ) {
            $wpdb->update( $wpdb->prefix . 'vesho_shop_orders', [
                'refund_amount' => (float)$order->refund_amount + $amount,
            ], ['id' => $order_id] );
            // Also clear pending if amount matches
            if ( abs( $amount - (float)$order->refund_pending_amount ) < 0.01 ) {
                $wpdb->update( $wpdb->prefix . 'vesho_shop_orders', ['refund_pending_amount' => 0.00], ['id' => $order_id] );
            }
            wp_send_json_success( ['message' => $result['message']] );
        } else {
            wp_send_json_error( ['message' => $result['message']] );
        }
    }

    /**
     * Dispatch a payment refund based on the order's payment method.
     * Returns ['done' => bool, 'message' => string].
     */
    private static function do_payment_refund( $order, float $amount ): array {
        $method = $order->payment_method ?? '';

        // ── Stripe ─────────────────────────────────────────────────────────────
        if ( $method === 'stripe' ) {
            $secret     = get_option( 'vesho_stripe_secret_key', '' );
            $payment_id = $order->stripe_payment_id ?? '';
            if ( ! $secret || ! $payment_id ) {
                return ['done' => false, 'message' => 'Stripe seadistus puudub'];
            }
            $resp = wp_remote_post( 'https://api.stripe.com/v1/refunds', [
                'headers' => ['Authorization' => 'Basic ' . base64_encode( $secret . ':' )],
                'body'    => [
                    'payment_intent' => $payment_id,
                    'amount'         => (int)round( $amount * 100 ),
                ],
            ] );
            if ( is_wp_error( $resp ) ) {
                return ['done' => false, 'message' => 'Stripe ühenduse viga: ' . $resp->get_error_message()];
            }
            $data = json_decode( wp_remote_retrieve_body( $resp ), true );
            if ( ! empty( $data['id'] ) ) {
                return ['done' => true, 'message' => "Stripe tagastus {$amount}€ tehtud ✓"];
            }
            $err = $data['error']['message'] ?? 'Teadmata viga';
            return ['done' => false, 'message' => "Stripe viga: $err"];
        }

        // ── Maksekeskus ────────────────────────────────────────────────────────
        if ( $method === 'mc' || $method === 'maksekeskus' ) {
            $shop_id    = get_option( 'vesho_mc_shop_id', '' );
            $secret     = get_option( 'vesho_mc_secret_key', '' );
            $sandbox    = get_option( 'vesho_mc_sandbox', '1' ) === '1';
            $tx_id      = $order->mc_transaction_id ?? '';
            if ( ! $shop_id || ! $secret || ! $tx_id ) {
                return ['done' => false, 'message' => 'Maksekeskus seadistus puudub'];
            }
            $base = $sandbox ? 'https://api.test.maksekeskus.ee/v1' : 'https://api.maksekeskus.ee/v1';
            $resp = wp_remote_post( "$base/transactions/$tx_id/refunds", [
                'headers' => [
                    'Authorization' => 'Basic ' . base64_encode( "$shop_id:$secret" ),
                    'Content-Type'  => 'application/json',
                ],
                'body'    => wp_json_encode( ['amount' => $amount] ),
            ] );
            if ( is_wp_error( $resp ) ) {
                return ['done' => false, 'message' => 'Maksekeskus ühenduse viga'];
            }
            $data = json_decode( wp_remote_retrieve_body( $resp ), true );
            if ( ! empty( $data['id'] ) || ! empty( $data['status'] ) ) {
                return ['done' => true, 'message' => "Maksekeskus tagastus {$amount}€ tehtud ✓"];
            }
            $err = $data['message'] ?? 'Teadmata viga';
            return ['done' => false, 'message' => "Maksekeskus viga: $err"];
        }

        // ── Montonio — manual ──────────────────────────────────────────────────
        if ( $method === 'montonio' ) {
            return ['done' => false, 'message' => 'Montonio tagastused tee käsitsi Montonio halduspaneelil (merchants.montonio.com)'];
        }

        return ['done' => false, 'message' => "Makse meetodit '$method' ei toetata automaatseks tagastuseks"];
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Feature: Admin kasutajahaldus
    // ══════════════════════════════════════════════════════════════════════════

    public static function handle_save_admin_user() {
        check_admin_referer( 'vesho_save_admin_user' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

        $id           = absint( $_POST['admin_user_id'] ?? 0 );
        $username     = sanitize_user( $_POST['username'] ?? '' );
        $email        = sanitize_email( $_POST['email'] ?? '' );
        $display_name = sanitize_text_field( $_POST['display_name'] ?? '' );
        $password     = $_POST['password'] ?? '';

        if ( $id ) {
            // Update existing
            $data = [];
            if ( $email ) $data['user_email'] = $email;
            if ( $display_name ) $data['display_name'] = $display_name;
            if ( ! empty($password) ) $data['user_pass'] = $password;
            if ( ! empty($data) ) {
                $data['ID'] = $id;
                $result = wp_update_user( $data );
                if ( is_wp_error($result) ) {
                    wp_redirect( add_query_arg( ['page' => 'vesho-crm-settings', 'tab_hash' => 'adminid', 'msg' => 'err'], admin_url('admin.php') ) );
                    exit;
                }
            }
            $msg = 'admin_updated';
        } else {
            // Create new admin
            if ( ! $username || ! $email || ! $password ) {
                wp_redirect( add_query_arg( ['page' => 'vesho-crm-settings', 'tab_hash' => 'adminid', 'msg' => 'missing_fields'], admin_url('admin.php') ) );
                exit;
            }
            if ( username_exists($username) || email_exists($email) ) {
                wp_redirect( add_query_arg( ['page' => 'vesho-crm-settings', 'tab_hash' => 'adminid', 'msg' => 'user_exists'], admin_url('admin.php') ) );
                exit;
            }
            $new_user_id = wp_create_user( $username, $password, $email );
            if ( is_wp_error($new_user_id) ) {
                wp_redirect( add_query_arg( ['page' => 'vesho-crm-settings', 'tab_hash' => 'adminid', 'msg' => 'err'], admin_url('admin.php') ) );
                exit;
            }
            $u = new WP_User( $new_user_id );
            $u->set_role( 'administrator' );
            if ( $display_name ) wp_update_user( ['ID' => $new_user_id, 'display_name' => $display_name] );
            // Force password change on first login
            update_user_meta( $new_user_id, 'vesho_force_password_change', 1 );
            $msg = 'admin_added';
        }

        wp_redirect( add_query_arg( ['page' => 'vesho-crm-settings', 'tab_hash' => 'adminid', 'msg' => $msg], admin_url('admin.php') ) );
        exit;
    }

    public static function handle_delete_admin_user() {
        check_admin_referer( 'vesho_delete_admin_user' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

        $id = absint( $_GET['admin_user_id'] ?? 0 );
        $current_user_id = get_current_user_id();
        if ( ! $id ) wp_die( 'Vigane kasutaja ID' );
        if ( $id === $current_user_id ) wp_die( 'Ei saa iseennast kustutada' );

        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user( $id );

        wp_redirect( add_query_arg( ['page' => 'vesho-crm-settings', 'tab_hash' => 'adminid', 'msg' => 'admin_deleted'], admin_url('admin.php') ) );
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Feature: TOTP 2FA — pure PHP, no external libraries
    // ══════════════════════════════════════════════════════════════════════════

    public static function totp_generate_secret( $length = 16 ) {
        $chars  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ( $i = 0; $i < $length; $i++ ) {
            $secret .= $chars[ random_int(0, 31) ];
        }
        return $secret;
    }

    private static function base32_decode( $secret ) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $output   = '';
        $v        = 0;
        $b        = 0;
        foreach ( str_split( strtoupper($secret) ) as $c ) {
            $pos = strpos( $alphabet, $c );
            if ( $pos === false ) continue;
            $v  = ( $v << 5 ) | $pos;
            $b += 5;
            if ( $b >= 8 ) {
                $output .= chr( ($v >> ($b -= 8)) & 255 );
            }
        }
        return $output;
    }

    private static function totp_get_code( $secret, $time = null ) {
        $time = $time ?? floor( time() / 30 );
        $msg  = pack( 'J', $time );
        $hash = hash_hmac( 'sha1', $msg, self::base32_decode($secret), true );
        $off  = ord( $hash[19] ) & 0xf;
        $code = (
            ( ( ord($hash[$off+0]) & 0x7f ) << 24 ) |
            ( ( ord($hash[$off+1]) & 0xff ) << 16 ) |
            ( ( ord($hash[$off+2]) & 0xff ) <<  8 ) |
            ( ( ord($hash[$off+3]) & 0xff ) )
        ) % 1000000;
        return str_pad( $code, 6, '0', STR_PAD_LEFT );
    }

    public static function totp_verify( $secret, $code, $window = 1 ) {
        $time = floor( time() / 30 );
        for ( $i = -$window; $i <= $window; $i++ ) {
            if ( self::totp_get_code($secret, $time + $i) === $code ) return true;
        }
        return false;
    }

    public static function handle_save_2fa() {
        check_admin_referer( 'vesho_save_2fa' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

        $user_id      = get_current_user_id();
        $setup_secret = sanitize_text_field( $_POST['setup_secret'] ?? '' );
        $totp_code    = sanitize_text_field( $_POST['totp_code'] ?? '' );

        if ( ! self::totp_verify($setup_secret, $totp_code) ) {
            wp_redirect( add_query_arg( ['page' => 'vesho-my-account', 'msg' => 'bad_code'], admin_url('admin.php') ) );
            exit;
        }

        update_user_meta( $user_id, 'vesho_totp_secret',  $setup_secret );
        update_user_meta( $user_id, 'vesho_totp_enabled', 1 );
        delete_user_meta( $user_id, 'vesho_totp_pending_secret' );

        wp_redirect( add_query_arg( ['page' => 'vesho-my-account', 'msg' => 'enabled'], admin_url('admin.php') ) );
        exit;
    }

    public static function handle_disable_2fa() {
        check_admin_referer( 'vesho_disable_2fa' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

        $user_id = get_current_user_id();
        update_user_meta( $user_id, 'vesho_totp_enabled', 0 );
        delete_user_meta( $user_id, 'vesho_totp_secret' );
        delete_user_meta( $user_id, 'vesho_totp_pending_secret' );

        wp_redirect( add_query_arg( ['page' => 'vesho-my-account', 'msg' => 'disabled'], admin_url('admin.php') ) );
        exit;
    }

    /**
     * After WP login: if user has 2FA enabled, log them out and redirect to 2FA page.
     */
    public static function after_wp_login( $user_login, $user ) {
        if ( ! in_array('administrator', (array) $user->roles) ) return;
        if ( ! get_user_meta( $user->ID, 'vesho_totp_enabled', true ) ) return;

        // Store token in transient
        $token = wp_generate_password( 32, false );
        set_transient( 'vesho_2fa_' . $token, $user->ID, 300 ); // 5 min

        // Log them out
        wp_logout();

        // Redirect to 2FA page
        $redirect = add_query_arg( ['action' => 'vesho_2fa', 'token' => $token], wp_login_url() );
        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Render the 2FA verification page on wp-login.php?action=vesho_2fa
     */
    public static function render_2fa_login_page() {
        $token = sanitize_text_field( $_REQUEST['token'] ?? '' );
        $code  = sanitize_text_field( $_POST['totp_code'] ?? '' );
        $error = '';

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
            $user_id = get_transient( 'vesho_2fa_' . $token );
            if ( ! $user_id ) {
                $error = 'Sessioon aegus. Palun logi uuesti sisse.';
            } else {
                $secret = get_user_meta( $user_id, 'vesho_totp_secret', true );
                if ( self::totp_verify($secret, $code) ) {
                    delete_transient( 'vesho_2fa_' . $token );
                    // Log user in (no wp_login action — avoids re-triggering 2FA hook)
                    wp_set_current_user( $user_id );
                    wp_set_auth_cookie( $user_id, false );
                    wp_safe_redirect( admin_url() );
                    exit;
                } else {
                    $error = 'Vale kood. Proovi uuesti.';
                }
            }
        }

        // Render the 2FA form (WP's login_header outputs HTML head + body open)
        login_header( '2FA kinnitus', '', null );
        ?>
        <form method="POST" action="<?php echo esc_url( add_query_arg(['action' => 'vesho_2fa', 'token' => $token], wp_login_url()) ); ?>">
            <p><?php esc_html_e('Sisesta Google Authenticatori 6-kohaline kood:', 'vesho-crm'); ?></p>
            <?php if ($error) echo '<div class="error" style="padding:8px;color:#d63638">' . esc_html($error) . '</div>'; ?>
            <p>
                <label for="totp_code">Autentimiskood</label>
                <input type="text" name="totp_code" id="totp_code" class="input" maxlength="6" minlength="6"
                       pattern="[0-9]{6}" autocomplete="one-time-code" autofocus required
                       style="font-size:24px;letter-spacing:6px;text-align:center;width:100%;padding:10px">
            </p>
            <p class="submit">
                <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="Kinnita">
            </p>
        </form>
        <?php
        login_footer();
        exit;
    }

    // ══════════════════════════════════════════════════════════════════════════
    // Feature: Force password change on first login
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * On every admin page load: if the current user must change their password,
     * redirect them to the Minu konto page until they do.
     */
    public static function check_force_password_change() {
        if ( ! is_user_logged_in() ) return;
        if ( ! current_user_can( 'manage_options' ) ) return;
        $user_id = get_current_user_id();
        if ( ! get_user_meta( $user_id, 'vesho_force_password_change', true ) ) return;

        // Allow the password-change POST to go through
        if ( isset($_POST['action']) && $_POST['action'] === 'vesho_change_password' ) return;

        // Allow already being on the account page with force_pw flag
        $page = sanitize_text_field( $_GET['page'] ?? '' );
        if ( $page === 'vesho-my-account' && isset($_GET['force_pw']) ) return;

        wp_safe_redirect( admin_url( 'admin.php?page=vesho-my-account&force_pw=1' ) );
        exit;
    }

    /**
     * Handle forced password change form submission.
     */
    public static function handle_change_password() {
        check_admin_referer( 'vesho_change_password' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Unauthorized' );

        $user_id  = get_current_user_id();
        $new_pass = $_POST['new_password']     ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if ( strlen( $new_pass ) < 8 ) {
            wp_safe_redirect( admin_url( 'admin.php?page=vesho-my-account&force_pw=1&msg=short_pw' ) );
            exit;
        }
        if ( $new_pass !== $confirm ) {
            wp_safe_redirect( admin_url( 'admin.php?page=vesho-my-account&force_pw=1&msg=pw_mismatch' ) );
            exit;
        }

        wp_set_password( $new_pass, $user_id );
        delete_user_meta( $user_id, 'vesho_force_password_change' );

        // Re-authenticate (wp_set_password logs the user out)
        wp_set_auth_cookie( $user_id, false );

        wp_safe_redirect( admin_url( 'admin.php?page=vesho-my-account&msg=pw_changed' ) );
        exit;
    }
}
