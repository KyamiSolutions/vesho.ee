<?php
/**
 * Plugin Name: Vesho CRM
 * Plugin URI:  https://vesho.ee
 * Description: CRM ja klientide portaal Vesho OÜ-le. Haldab kliente, seadmeid, hooldusi, arveid ja teenuseid.
 * Version:     2.5.3
 * Author:      Vesho OÜ
 * Author URI:  https://vesho.ee
 * Text Domain: vesho-crm
 * License:     Proprietary
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

defined( 'ABSPATH' ) || exit;

// ── Constants ──────────────────────────────────────────────────────────────────
define('VESHO_CRM_VERSION', '2.5.3');
define( 'VESHO_CRM_FILE',     __FILE__ );
define( 'VESHO_CRM_PATH',     plugin_dir_path( __FILE__ ) );
define( 'VESHO_CRM_URL',      plugin_dir_url( __FILE__ ) );
define( 'VESHO_CRM_BASENAME', plugin_basename( __FILE__ ) );

// ── Autoload includes ─────────────────────────────────────────────────────────
function vesho_crm_load_includes() {
    $files = array(
        'includes/class-database.php',
        'includes/class-admin.php',
        'includes/class-updater.php',
        'includes/class-client-portal.php',
        'includes/class-worker-portal.php',
        'includes/class-api.php',
        'includes/class-guest-request.php',
        'includes/shortcodes.php',
        'includes/class-pdf.php',
    );
    foreach ( $files as $file ) {
        $path = VESHO_CRM_PATH . $file;
        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
}

// ── Activation hook ───────────────────────────────────────────────────────────
register_activation_hook( __FILE__, 'vesho_crm_activate' );
function vesho_crm_activate() {
    vesho_crm_load_includes();
    Vesho_CRM_Database::install();
    vesho_crm_seed_default_services();

    // Register custom roles
    add_role( 'vesho_client', 'CRM Klient', array( 'read' => true ) );
    add_role( 'vesho_worker', 'CRM Töötaja', array( 'read' => true ) );

    // Create default pages if they don't exist
    vesho_crm_create_pages();

    // Set version
    update_option( 'vesho_crm_version', VESHO_CRM_VERSION );
    update_option( 'vesho_crm_activated', current_time( 'mysql' ) );

    // Flush rewrite rules
    flush_rewrite_rules();
}

// ── Deactivation hook ─────────────────────────────────────────────────────────
register_deactivation_hook( __FILE__, 'vesho_crm_deactivate' );
function vesho_crm_deactivate() {
    remove_role( 'vesho_client' );
    remove_role( 'vesho_worker' );
    wp_clear_scheduled_hook( 'vesho_crm_daily_cron' );
    flush_rewrite_rules();
}

// ── Seed default services ─────────────────────────────────────────────────────
function vesho_crm_seed_default_services() {
    global $wpdb;
    $table = $wpdb->prefix . 'vesho_services';

    if ( $wpdb->get_var( "SELECT COUNT(*) FROM $table" ) > 0 ) return;

    $defaults = array(
        array( 'name' => 'Hooldus & Remont',        'description' => 'Regulaarne veesüsteemide hooldus ja kiirete rikete kõrvaldamine.',            'price' => 49.00, 'icon' => '🔧', 'active' => 1 ),
        array( 'name' => 'Veepuhastus',              'description' => 'Vee kvaliteedi analüüs ja filtreerimissüsteemide paigaldus.',                   'price' => 89.00, 'icon' => '💧', 'active' => 1 ),
        array( 'name' => 'Kaevupumbad',              'description' => 'Kaevupumpade paigaldus, remont ja hooldus.',                                    'price' => 129.00,'icon' => '⛏️', 'active' => 1 ),
        array( 'name' => 'Paigaldus',                'description' => 'Uute veesüsteemide ja seadmete professionaalne paigaldus.',                    'price' => 199.00,'icon' => '⚙️', 'active' => 1 ),
        array( 'name' => 'Vee analüüs',              'description' => 'Professionaalne vee kvaliteedi analüüs laboratooriumis.',                       'price' => 59.00, 'icon' => '🧪', 'active' => 1 ),
        array( 'name' => 'Veesoojendid',             'description' => 'Veesoojendite paigaldus, hooldus ja asendamine.',                               'price' => 79.00, 'icon' => '♻️', 'active' => 1 ),
    );

    foreach ( $defaults as $svc ) {
        $wpdb->insert( $table, array_merge( $svc, array( 'created_at' => current_time( 'mysql' ) ) ) );
    }
}

// ── Create default pages ──────────────────────────────────────────────────────
function vesho_crm_create_pages() {
    $pages = array(
        'klient'         => array( 'title' => 'Klientide Portaal', 'content' => '[vesho_client_portal]' ),
        'klient-login'   => array( 'title' => 'Logi Sisse',        'content' => '[vesho_client_login]'  ),
        'worker'         => array( 'title' => 'Töötaja Portaal',   'content' => '[vesho_worker_portal]' ),
    );

    foreach ( $pages as $slug => $page ) {
        if ( ! get_page_by_path( $slug ) ) {
            wp_insert_post( array(
                'post_title'     => $page['title'],
                'post_name'      => $slug,
                'post_content'   => $page['content'],
                'post_status'    => 'publish',
                'post_type'      => 'page',
                'comment_status' => 'closed',
            ) );
        }
    }
}

// ── Init ──────────────────────────────────────────────────────────────────────
// ── Coming Soon / Site-wide maintenance mode ──────────────────────────────────
add_action( 'template_redirect', 'vesho_coming_soon_redirect' );
function vesho_coming_soon_redirect() {
    $active = get_option('vesho_coming_soon_mode','0') === '1'
           || get_option('vesho_maintenance_mode','0') === '1';
    if ( ! $active ) return;
    if ( current_user_can('manage_options') ) return;
    if ( is_admin() ) return;
    // Allow wp-login, wp-admin, REST API, AJAX
    if ( defined('DOING_AJAX') && DOING_AJAX ) return;
    if ( defined('REST_REQUEST') && REST_REQUEST ) return;
    // Allow email verification links — also tell LiteSpeed not to cache these
    if ( ! empty( $_GET['verify_email'] ) ) {
        do_action( 'litespeed_control_set_nocache', 'vesho_verify' );
        header( 'X-LiteSpeed-Cache-Control: no-cache' );
        return;
    }
    // Tell LiteSpeed Cache not to serve a cached version of this response
    do_action( 'litespeed_control_set_nocache', 'vesho_maintenance' );
    header( 'X-LiteSpeed-Cache-Control: no-cache' );

    $is_maintenance = get_option('vesho_maintenance_mode','0') === '1';
    $logo    = get_option('vesho_company_logo', '');
    $name    = get_option('vesho_company_name', get_bloginfo('name'));
    $email   = get_option('vesho_company_email', get_option('admin_email'));

    if ( $is_maintenance ) {
        $title   = 'Hooldus käib';
        $message = esc_html( get_option('vesho_coming_soon_message_maintenance', get_option('vesho_maintenance_message', 'Hooldus käib. Palume vabandust ebamugavuse pärast!') ) );
    } else {
        $title   = esc_html( get_option('vesho_coming_soon_title', 'Varsti tulekul') );
        $message = esc_html( get_option('vesho_coming_soon_message', 'Töötame uue veebisaidi kallal. Peagi tagasi!') );
    }

    // Dripping faucet SVG icon
    $faucet_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 100" width="80" height="100" style="margin:0 auto 24px;display:block">
  <!-- Pipe body -->
  <rect x="28" y="4" width="24" height="14" rx="4" fill="#00b4c8"/>
  <!-- Handle -->
  <rect x="16" y="8" width="48" height="8" rx="4" fill="#00d4e8"/>
  <!-- Faucet neck -->
  <rect x="34" y="18" width="12" height="22" rx="3" fill="#00b4c8"/>
  <!-- Spout -->
  <path d="M34 38 Q34 52 22 54 L22 60 Q22 64 26 64 L54 64 Q58 64 58 60 L58 54 Q46 52 46 38 Z" fill="#00b4c8"/>
  <!-- Spout tip -->
  <rect x="30" y="62" width="20" height="6" rx="3" fill="#008fa0"/>
  <!-- Drop 1 -->
  <ellipse cx="40" cy="76" rx="4" ry="5" fill="#00b4c8" opacity="0.9">
    <animate attributeName="cy" values="72;88" dur="1.4s" repeatCount="indefinite"/>
    <animate attributeName="opacity" values="0.9;0" dur="1.4s" repeatCount="indefinite"/>
  </ellipse>
  <!-- Drop 2 -->
  <ellipse cx="40" cy="76" rx="3" ry="4" fill="#00d4e8" opacity="0.7">
    <animate attributeName="cy" values="72;88" dur="1.4s" begin="0.7s" repeatCount="indefinite"/>
    <animate attributeName="opacity" values="0.7;0" dur="1.4s" begin="0.7s" repeatCount="indefinite"/>
  </ellipse>
</svg>';

    http_response_code(503);
    header('Retry-After: 3600');
    echo '<!DOCTYPE html><html lang="et"><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>' . esc_html($title) . ' — ' . esc_html($name) . '</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{min-height:100vh;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#0d1f2d 0%,#1a3a50 100%);font-family:\'Segoe UI\',Arial,sans-serif;color:#fff;padding:20px}
.cs-box{text-align:center;max-width:520px}
.cs-logo{margin-bottom:32px}
.cs-logo img{max-height:70px;filter:brightness(0) invert(1)}
h1{font-size:clamp(28px,5vw,48px);font-weight:800;margin-bottom:16px;letter-spacing:-1px}
p{font-size:17px;opacity:.75;line-height:1.6;margin-bottom:32px}
.cs-divider{width:60px;height:3px;background:#00b4c8;border-radius:2px;margin:0 auto 28px}
.cs-contact{font-size:14px;opacity:.55}
.cs-contact a{color:#00b4c8;text-decoration:none}
.cs-bar{width:100%;background:rgba(255,255,255,.1);border-radius:20px;height:4px;margin:32px 0 20px;overflow:hidden}
.cs-bar-fill{height:100%;background:linear-gradient(90deg,#00b4c8,#0ae);border-radius:20px;animation:progress 2s ease-in-out infinite alternate}
@keyframes progress{from{width:45%}to{width:80%}}
</style>
</head><body>
<div class="cs-box">
  ' . ($logo ? '<div class="cs-logo"><img src="' . esc_url($logo) . '" alt="' . esc_attr($name) . '"></div>' : '<div class="cs-logo" style="font-size:28px;font-weight:800;color:#00b4c8">' . esc_html($name) . '</div>') . '
  ' . $faucet_svg . '
  <h1>' . esc_html($title) . '</h1>
  <div class="cs-divider"></div>
  <p>' . $message . '</p>
  ' . ( $is_maintenance ? '' : '<div class="cs-bar"><div class="cs-bar-fill"></div></div>' ) . '
  <div class="cs-contact">Küsimused? <a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></div>
</div>
</body></html>';
    exit;
}

add_action( 'plugins_loaded', 'vesho_crm_init', 10 );
function vesho_crm_init() {
    // Load text domain
    load_plugin_textdomain( 'vesho-crm', false, VESHO_CRM_PATH . 'languages/' );

    // Load includes
    vesho_crm_load_includes();

    // Run DB update check if version changed
    if ( get_option( 'vesho_crm_version' ) !== VESHO_CRM_VERSION ) {
        Vesho_CRM_Database::install();
        vesho_crm_create_pages();
        // ── Migrate old shop order statuses to 3006-compatible names ──────────
        global $wpdb;
        $t = $wpdb->prefix . 'vesho_shop_orders';
        $wpdb->query("UPDATE {$t} SET status='pending'    WHERE status IN ('new')");
        $wpdb->query("UPDATE {$t} SET status='processing' WHERE status IN ('picking')");
        $wpdb->query("UPDATE {$t} SET status='confirmed'  WHERE status IN ('ready')");
        $wpdb->query("UPDATE {$t} SET status='completed'  WHERE status IN ('fulfilled')");
        // ─────────────────────────────────────────────────────────────────────
        update_option( 'vesho_crm_version', VESHO_CRM_VERSION );
    }

    // Ensure portal pages exist (safe to run always)
    vesho_crm_create_pages();

    // Ensure custom roles exist (safe to run always — add_role() is a no-op if role exists)
    if ( ! get_role('vesho_client') )    add_role( 'vesho_client',    'CRM Klient',   ['read' => true] );
    if ( ! get_role('vesho_worker') )    add_role( 'vesho_worker',    'CRM Töötaja',  ['read' => true] );
    if ( ! get_role('vesho_crm_admin') ) add_role( 'vesho_crm_admin', 'CRM Admin',    ['read' => true, 'vesho_crm_admin' => true] );

    // Init classes
    if ( class_exists( 'Vesho_CRM_Admin' ) ) {
        Vesho_CRM_Admin::init();
    }
    if ( class_exists( 'Vesho_CRM_Client_Portal' ) ) {
        Vesho_CRM_Client_Portal::init();
    }
    if ( class_exists( 'Vesho_CRM_API' ) ) {
        Vesho_CRM_API::init();
    }
    if ( class_exists( 'Vesho_CRM_Worker_Portal' ) ) {
        Vesho_CRM_Worker_Portal::init();
    }
    if ( class_exists( 'Vesho_CRM_Guest_Request' ) ) {
        Vesho_CRM_Guest_Request::init();
    }
    if ( class_exists( 'Vesho_CRM_Updater' ) ) {
        Vesho_CRM_Updater::init();
    }
}

// ── PDF download endpoint ─────────────────────────────────────────────────────
add_action( 'init', function() {
    if ( isset( $_GET['vesho_pdf'] ) && $_GET['vesho_pdf'] === 'invoice' ) {
        if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Puuduvad õigused.' );
        }
        $id = absint( $_GET['id'] ?? 0 );
        if ( $id && function_exists( 'vesho_build_invoice_pdf' ) ) {
            vesho_build_invoice_pdf( $id );
        }
        exit;
    }
} );

// ── Admin permission helper ───────────────────────────────────────────────────
/**
 * Check if current WP user can access a specific CRM module.
 * Super-admins (manage_options) always pass. vesho_crm_admin users
 * are checked against their stored permissions array.
 */
function vesho_can( $module ) {
    if ( current_user_can('manage_options') ) return true;
    if ( ! current_user_can('vesho_crm_admin') ) return false;
    $perms = get_user_meta( get_current_user_id(), 'vesho_crm_perms', true );
    return is_array($perms) && in_array( $module, $perms, true );
}

// Grant vesho_crm_admin users access to WP admin area
add_filter( 'user_has_cap', function( $allcaps, $caps, $args ) {
    if ( isset($allcaps['vesho_crm_admin']) && $allcaps['vesho_crm_admin'] ) {
        $allcaps['read']          = true;
        $allcaps['edit_posts']    = false;
        // Allow them to see WP admin bar but not full dashboard
    }
    return $allcaps;
}, 10, 3 );

// Redirect vesho_crm_admin users to CRM on WP login
add_action( 'admin_init', function() {
    if ( current_user_can('manage_options') ) return;
    if ( ! current_user_can('vesho_crm_admin') ) return;
    $screen = get_current_screen();
    if ( $screen && strpos($screen->id, 'vesho') === false ) {
        wp_redirect( admin_url('admin.php?page=vesho-crm') );
        exit;
    }
} );

// ── Save update server URL (AJAX) ─────────────────────────────────────────────
add_action( 'wp_ajax_vesho_save_update_server', function() {
    check_ajax_referer( 'vesho_admin_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
    update_option( 'vesho_update_server_url', esc_url_raw( $_POST['url'] ?? '' ) );
    wp_send_json_success();
} );

// ── Public site notices (otse PHP output, navbar alla) ───────────────────────
add_action( 'vesho_after_header', 'vesho_public_notices' );
function vesho_public_notices() {
    if ( is_admin() ) return;
    global $wpdb;
    $today = current_time('Y-m-d');
    $cols = $wpdb->get_col("DESCRIBE `{$wpdb->prefix}vesho_portal_notices`") ?: [];
    $type_sel = in_array('type',$cols) ? "COALESCE(type,'info')" : "'info'";
    $notices = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, title, message, {$type_sel} as type FROM {$wpdb->prefix}vesho_portal_notices
         WHERE active=1 AND target IN ('client','both')
         AND (starts_at IS NULL OR starts_at <= %s)
         AND (ends_at IS NULL OR ends_at >= %s)
         ORDER BY created_at DESC LIMIT 5",
        $today, $today
    ) );
    if ( empty($notices) ) return;
    echo '<script>var _vpub_dm=(function(){try{return JSON.parse(sessionStorage.getItem("vpub_dm")||"[]");}catch(e){return [];}})();</script>';
    foreach ( $notices as $n ) {
        $type   = $n->type ?? 'info';
        $bg     = $type==='warning' ? 'rgba(245,158,11,0.12)' : ($type==='success' ? 'rgba(16,185,129,0.12)' : 'rgba(99,102,241,0.12)');
        $border = $type==='warning' ? 'rgba(245,158,11,0.3)'  : ($type==='success' ? 'rgba(16,185,129,0.3)'  : 'rgba(99,102,241,0.3)');
        $color  = $type==='warning' ? '#f59e0b' : ($type==='success' ? '#10b981' : '#818cf8');
        $icon   = $type==='warning' ? '⚠️' : ($type==='success' ? '✅' : 'ℹ️');
        $id     = (int)$n->id;
        printf(
            '<div id="vpubn-%1$d" style="background:%2$s;border-bottom:1px solid %3$s;padding:10px 20px;display:flex;align-items:center;gap:10px;font-size:13px">'
            . '<span style="font-size:16px">%4$s</span>'
            . '<span style="color:%5$s;font-weight:600">%6$s</span>'
            . '<span style="color:#9ca3af"> — %7$s</span>'
            . '<button onclick="var dm=(function(){try{return JSON.parse(sessionStorage.getItem(\'vpub_dm\')||\'[]\');}catch(e){return [];}})();dm.push(%1$d);sessionStorage.setItem(\'vpub_dm\',JSON.stringify(dm));this.closest(\'[id^=vpubn-]\').remove()" style="margin-left:auto;background:none;border:none;cursor:pointer;color:#9ca3af;font-size:20px;line-height:1;padding:0 4px">×</button>'
            . '</div>',
            $id, $bg, $border, $icon, $color,
            esc_html($n->title), esc_html($n->message)
        );
    }
    echo '<script>(function(){_vpub_dm.forEach(function(id){var el=document.getElementById("vpubn-"+id);if(el)el.remove();});})();</script>';
}

// ── Plugin action links ───────────────────────────────────────────────────────
add_filter( 'plugin_action_links_' . VESHO_CRM_BASENAME, 'vesho_crm_action_links' );
function vesho_crm_action_links( $links ) {
    $crm_link = '<a href="' . admin_url( 'admin.php?page=vesho-crm' ) . '">CRM Paneel</a>';
    array_unshift( $links, $crm_link );
    return $links;
}

// ── Enqueue admin assets ──────────────────────────────────────────────────────
add_action( 'admin_enqueue_scripts', 'vesho_crm_admin_assets' );
function vesho_crm_admin_assets( $hook ) {
    // Only load on our admin pages
    if ( strpos( $hook, 'vesho-crm' ) === false && strpos( $hook, 'vesho_crm' ) === false ) return;

    wp_enqueue_style(
        'vesho-crm-admin',
        VESHO_CRM_URL . 'admin/css/admin.css',
        array(),
        VESHO_CRM_VERSION
    );

    // Select2 — searchable dropdowns for all CRM selects
    wp_enqueue_style( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css', array(), '4.1.0' );
    wp_enqueue_script( 'select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', array('jquery'), '4.1.0', true );

    // ZXing barcode scanning library (CDN)
    wp_enqueue_script(
        'zxing-library',
        'https://unpkg.com/@zxing/library@0.21.3/umd/index.min.js',
        array(),
        '0.21.3',
        true
    );

    // QR Code generator (for worker cards)
    wp_enqueue_script(
        'qrcodejs',
        'https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js',
        array(),
        '1.0.0',
        true
    );

    // EAN Scanner module
    wp_enqueue_script(
        'vesho-ean-scanner',
        VESHO_CRM_URL . 'admin/js/ean-scanner.js',
        array( 'zxing-library' ),
        VESHO_CRM_VERSION,
        true
    );

    wp_enqueue_script(
        'vesho-crm-admin-js',
        VESHO_CRM_URL . 'admin/js/admin.js',
        array( 'jquery', 'vesho-ean-scanner' ),
        VESHO_CRM_VERSION,
        true
    );

    wp_localize_script( 'vesho-crm-admin-js', 'VeshoCRM', array(
        'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
        'restUrl'  => rest_url( 'vesho/v1/' ),
        'nonce'    => wp_create_nonce( 'vesho_crm_nonce' ),
        'restNonce'=> wp_create_nonce( 'wp_rest' ),
        'i18n'     => array(
            'confirm_delete' => __( 'Kas olete kindel, et soovite kustutada?', 'vesho-crm' ),
            'saving'         => __( 'Salvestamine...', 'vesho-crm' ),
            'saved'          => __( 'Salvestatud!', 'vesho-crm' ),
            'error'          => __( 'Viga!', 'vesho-crm' ),
        ),
    ) );

    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script( 'wp-color-picker' );
}

// ── Enqueue frontend assets (cart nonce) ─────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'vesho_crm_frontend_assets' );
function vesho_crm_frontend_assets() {
    // Inline script to expose ajaxurl and cart nonce globally on all frontend pages
    wp_add_inline_script(
        'jquery',
        'var ajaxurl = ' . wp_json_encode( admin_url( 'admin-ajax.php' ) ) . ';' .
        'var veshoCartNonce = ' . wp_json_encode( wp_create_nonce( 'vesho_cart_nonce' ) ) . ';',
        'after'
    );
}

// ── Force portal page template ────────────────────────────────────────────────
add_filter( 'template_include', 'vesho_crm_portal_template', 99 );
function vesho_crm_portal_template( $template ) {
    if ( ! is_page() ) return $template;
    global $post;
    $portal_slugs = array( 'klient', 'klient-login', 'worker' );
    if ( in_array( $post->post_name, $portal_slugs ) ) {
        $portal_tpl = get_theme_file_path( 'page-portal.php' );
        if ( file_exists( $portal_tpl ) ) return $portal_tpl;
    }
    return $template;
}

// ── Helper: format Estonian date ─────────────────────────────────────────────
function vesho_crm_format_date( $date_str, $format = 'd.m.Y' ) {
    if ( empty( $date_str ) ) return '–';
    $ts = strtotime( $date_str );
    if ( ! $ts ) return esc_html( $date_str );
    return date( $format, $ts );
}

// ── Helper: format money ──────────────────────────────────────────────────────
function vesho_crm_format_money( $amount, $currency = '€' ) {
    if ( $amount === null || $amount === '' ) return '–';
    return number_format( (float) $amount, 2, ',', ' ' ) . ' ' . $currency;
}

// ── Helper: status badge HTML ─────────────────────────────────────────────────
function vesho_crm_status_badge( $status ) {
    $map = array(
        'active'    => array( 'label' => 'Aktiivne',    'class' => 'badge-success' ),
        'inactive'  => array( 'label' => 'Mitteaktiivne', 'class' => 'badge-gray' ),
        'pending'   => array( 'label' => 'Ootel',       'class' => 'badge-warning' ),
        'scheduled' => array( 'label' => 'Planeeritud',  'class' => 'badge-info' ),
        'completed' => array( 'label' => 'Lõpetatud',   'class' => 'badge-success' ),
        'cancelled' => array( 'label' => 'Tühistatud',  'class' => 'badge-danger' ),
        'paid'      => array( 'label' => 'Makstud',     'class' => 'badge-success' ),
        'unpaid'    => array( 'label' => 'Maksmata',    'class' => 'badge-danger' ),
        'overdue'   => array( 'label' => 'Tähtaeg ületatud', 'class' => 'badge-danger' ),
        'draft'     => array( 'label' => 'Mustand',     'class' => 'badge-gray' ),
        'sent'      => array( 'label' => 'Saadetud',    'class' => 'badge-info' ),
        'eraisik'   => array( 'label' => 'Eraisik',     'class' => 'badge-info' ),
        'ettevote'  => array( 'label' => 'Ettevõte',    'class' => 'badge-cyan' ),
        'open'        => array( 'label' => 'Ootel',       'class' => 'badge-warning' ),
        'assigned'    => array( 'label' => 'Määratud',   'class' => 'badge-info' ),
        'in_progress' => array( 'label' => 'Töös',       'class' => 'badge-cyan' ),
        'resolved'    => array( 'label' => 'Lahendatud', 'class' => 'badge-success' ),
    );
    $status_lower = strtolower( $status );
    $cfg = isset( $map[ $status_lower ] ) ? $map[ $status_lower ] : array( 'label' => esc_html( $status ), 'class' => 'badge-gray' );
    return '<span class="crm-badge ' . esc_attr( $cfg['class'] ) . '">' . esc_html( $cfg['label'] ) . '</span>';
}

// ── Helper: log activity ──────────────────────────────────────────────────────
function vesho_crm_log_activity( $action, $description, $object_type = '', $object_id = null ) {
    global $wpdb;
    $user    = wp_get_current_user();
    $user_id = $user ? $user->ID : 0;
    $name    = $user ? ( $user->display_name ?: $user->user_login ) : 'System';
    $wpdb->insert( $wpdb->prefix . 'vesho_activity_log', array(
        'user_id'     => $user_id ?: null,
        'user_name'   => $name,
        'action'      => $action,
        'description' => $description,
        'object_type' => $object_type,
        'object_id'   => $object_id,
        'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? '',
        'created_at'  => current_time( 'mysql' ),
    ) );
}

// ── AJAX: Activity log (admin) ────────────────────────────────────────────────
add_action('wp_ajax_vesho_get_activity_log', 'vesho_ajax_get_activity_log');
function vesho_ajax_get_activity_log() {
    if (!current_user_can('manage_options')) wp_send_json_error('Pole lubatud');
    global $wpdb;
    $limit       = min(500, max(1, absint($_POST['limit'] ?? 50)));
    $offset      = absint($_POST['offset'] ?? 0);
    $action_f    = sanitize_text_field($_POST['action_filter'] ?? '');
    $type_f      = sanitize_text_field($_POST['object_type'] ?? '');
    $where       = '1=1';
    $args        = [];
    if ($action_f) { $where .= ' AND action=%s'; $args[] = $action_f; }
    if ($type_f)   { $where .= ' AND object_type=%s'; $args[] = $type_f; }
    $total = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vesho_activity_log WHERE $where" . ($args ? call_user_func_array([$wpdb,'prepare'],array_merge([' '],array_slice($args,0))) : ''));
    $sql   = "SELECT * FROM {$wpdb->prefix}vesho_activity_log WHERE $where ORDER BY created_at DESC LIMIT %d OFFSET %d";
    $rows  = $wpdb->get_results($wpdb->prepare($sql, ...array_merge($args, [$limit, $offset])));
    wp_send_json_success(['rows' => $rows, 'total' => $total]);
}

// ── WP-Cron: schedule daily job ──────────────────────────────────────────────
add_action( 'plugins_loaded', 'vesho_crm_schedule_cron' );
function vesho_crm_schedule_cron() {
    if ( ! wp_next_scheduled( 'vesho_crm_daily_cron' ) ) {
        // Schedule to run at 08:00 server time daily
        $next_8am = strtotime( 'today 08:00:00' );
        if ( $next_8am <= time() ) $next_8am = strtotime( 'tomorrow 08:00:00' );
        wp_schedule_event( $next_8am, 'daily', 'vesho_crm_daily_cron' );
    }
}

add_action( 'vesho_crm_daily_cron', 'vesho_crm_run_daily_jobs' );
function vesho_crm_run_daily_jobs() {
    vesho_crm_mark_overdue_invoices();
    vesho_crm_check_low_stock();
    vesho_crm_send_maintenance_reminders();
    vesho_crm_send_worker_reminders();
}

// ── Simple JWT helper (HS256 only, for Montonio) ──────────────────────────────
function vesho_jwt_encode( array $payload, string $secret ): string {
    $header  = rtrim( strtr( base64_encode( json_encode(['typ'=>'JWT','alg'=>'HS256']) ), '+/', '-_' ), '=' );
    $body    = rtrim( strtr( base64_encode( json_encode($payload) ), '+/', '-_' ), '=' );
    $sig     = rtrim( strtr( base64_encode( hash_hmac('sha256', "$header.$body", $secret, true) ), '+/', '-_' ), '=' );
    return "$header.$body.$sig";
}
function vesho_jwt_decode( string $token, string $secret ): ?array {
    $parts = explode('.', $token);
    if ( count($parts) !== 3 ) return null;
    [$header, $body, $sig] = $parts;
    $expected = rtrim( strtr( base64_encode( hash_hmac('sha256', "$header.$body", $secret, true) ), '+/', '-_' ), '=' );
    if ( ! hash_equals($expected, $sig) ) return null;
    return json_decode( base64_decode( strtr($body, '-_', '+/') ), true );
}

// ── Payment helpers ───────────────────────────────────────────────────────────
function vesho_mark_invoice_paid( int $invoice_id ): bool {
    global $wpdb;
    $updated = $wpdb->query( $wpdb->prepare(
        "UPDATE {$wpdb->prefix}vesho_invoices SET status='paid' WHERE id=%d AND status!='paid'",
        $invoice_id
    ));
    return $updated > 0;
}
function vesho_validate_paid_amount( string $type, int $id, $paid_amount ): bool {
    if ( $paid_amount === null ) return true;
    global $wpdb;
    $paid = (float) $paid_amount;
    if ( $type === 'invoice' ) {
        $expected = (float) $wpdb->get_var( $wpdb->prepare(
            "SELECT amount FROM {$wpdb->prefix}vesho_invoices WHERE id=%d", $id
        ));
    } elseif ( $type === 'shop_order' ) {
        $expected = (float) $wpdb->get_var( $wpdb->prepare(
            "SELECT total FROM {$wpdb->prefix}vesho_shop_orders WHERE id=%d", $id
        ));
    } else { return true; }
    return abs($paid - $expected) <= 0.02;
}

// ── Shop order payment confirmation (idempotent) ──────────────────────────────
function vesho_confirm_shop_order( int $order_id ): bool {
    global $wpdb;
    $updated = $wpdb->query( $wpdb->prepare(
        "UPDATE {$wpdb->prefix}vesho_shop_orders SET status='pending' WHERE id=%d AND status IN ('new','pending_payment')",
        $order_id
    ));
    if ( $updated > 0 ) {
        // Send confirmation email
        $o = $wpdb->get_row( $wpdb->prepare(
            "SELECT order_number, total, client_name, client_email FROM {$wpdb->prefix}vesho_shop_orders WHERE id=%d", $order_id
        ));
        if ( $o && $o->client_email ) {
            $co = get_option( 'vesho_company_name', get_bloginfo('name') );
            wp_mail(
                $o->client_email,
                "[{$co}] Tellimus #{$o->order_number} kinnitatud",
                "Tere {$o->client_name},\n\nTeie tellimus #{$o->order_number} ({$o->total} €) on kinnitatud.\nVõtame varsti ühendust tarne osas.\n\nAitäh!\n{$co}"
            );
        }
        // Notify admin
        $admin_email = get_option('vesho_notify_email', get_option('admin_email'));
        if ( $admin_email && $o ) {
            $co = get_option( 'vesho_company_name', get_bloginfo('name') );
            wp_mail( $admin_email, "[{$co}] Uus tellimus #{$o->order_number}", "Uus kinnitatud tellimus #{$o->order_number} ({$o->total} €) kliendilt {$o->client_name}." );
        }
        vesho_crm_log_activity( 'confirm_order', "Tellimus kinnitatud maksega", 'shop_order', $order_id );
    }
    return $updated > 0;
}

// ── AJAX: Payment method config ───────────────────────────────────────────────
add_action('wp_ajax_vesho_get_payment_config', 'vesho_ajax_get_payment_config');
function vesho_ajax_get_payment_config() {
    check_ajax_referer('vesho_portal_nonce', 'nonce');
    wp_send_json_success([
        'stripe_enabled'   => get_option('vesho_stripe_enabled','0') === '1' && get_option('vesho_stripe_pub_key',''),
        'stripe_pub_key'   => get_option('vesho_stripe_pub_key',''),
        'mc_enabled'       => get_option('vesho_mc_enabled','0') === '1',
        'montonio_enabled' => get_option('vesho_montonio_enabled','0') === '1',
    ]);
}

// ── AJAX: Stripe — create payment intent ─────────────────────────────────────
add_action('wp_ajax_vesho_pay_invoice_stripe', 'vesho_ajax_pay_invoice_stripe');
function vesho_ajax_pay_invoice_stripe() {
    check_ajax_referer('vesho_portal_nonce', 'nonce');
    $client = Vesho_CRM_Client_Portal::get_current_client();
    if (!$client) wp_send_json_error(['message'=>'Pole sisse logitud']);
    global $wpdb;
    $invoice_id = absint($_POST['invoice_id'] ?? 0);
    $inv = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vesho_invoices WHERE id=%d AND client_id=%d", $invoice_id, $client->id
    ));
    if (!$inv) wp_send_json_error(['message'=>'Arvet ei leitud']);
    if ($inv->status === 'paid') wp_send_json_error(['message'=>'Arve on juba makstud']);

    $secret_key = get_option('vesho_stripe_secret_key','');
    if (!$secret_key) wp_send_json_error(['message'=>'Stripe pole seadistatud']);

    $amount_cents = (int) round((float)$inv->amount * 100);
    $response = wp_remote_post('https://api.stripe.com/v1/payment_intents', [
        'headers' => ['Authorization' => 'Bearer ' . $secret_key, 'Content-Type' => 'application/x-www-form-urlencoded'],
        'body'    => http_build_query([
            'amount'                              => $amount_cents,
            'currency'                            => 'eur',
            'automatic_payment_methods[enabled]'  => 'true',
            'metadata[invoice_id]'                => $inv->id,
            'metadata[invoice_number]'            => $inv->invoice_number,
            'metadata[client_id]'                 => $client->id,
        ]),
    ]);
    if (is_wp_error($response)) wp_send_json_error(['message'=>'Stripe ühenduse viga']);
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (!empty($data['error'])) wp_send_json_error(['message'=>$data['error']['message']??'Stripe viga']);
    $wpdb->update($wpdb->prefix.'vesho_invoices', ['stripe_payment_id'=>$data['id']], ['id'=>$inv->id]);
    wp_send_json_success(['client_secret'=>$data['client_secret']]);
}

// ── AJAX: Stripe — confirm payment ───────────────────────────────────────────
add_action('wp_ajax_vesho_confirm_stripe_payment', 'vesho_ajax_confirm_stripe_payment');
function vesho_ajax_confirm_stripe_payment() {
    check_ajax_referer('vesho_portal_nonce', 'nonce');
    $client = Vesho_CRM_Client_Portal::get_current_client();
    if (!$client) wp_send_json_error(['message'=>'Pole sisse logitud']);
    global $wpdb;
    $invoice_id = absint($_POST['invoice_id'] ?? 0);
    $inv = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vesho_invoices WHERE id=%d AND client_id=%d", $invoice_id, $client->id
    ));
    if (!$inv || !$inv->stripe_payment_id) wp_send_json_error(['message'=>'Makset pole alustatud']);
    $secret_key = get_option('vesho_stripe_secret_key','');
    $response = wp_remote_get("https://api.stripe.com/v1/payment_intents/{$inv->stripe_payment_id}", [
        'headers' => ['Authorization' => 'Bearer ' . $secret_key],
    ]);
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (($data['status'] ?? '') === 'succeeded') {
        vesho_mark_invoice_paid($inv->id);
        wp_send_json_success(['message'=>'Makse õnnestus!']);
    }
    wp_send_json_success(['success'=>false, 'status'=>$data['status']??'unknown']);
}

// ── AJAX: Maksekeskus — create transaction ────────────────────────────────────
add_action('wp_ajax_vesho_pay_invoice_mc', 'vesho_ajax_pay_invoice_mc');
function vesho_ajax_pay_invoice_mc() {
    check_ajax_referer('vesho_portal_nonce', 'nonce');
    $client = Vesho_CRM_Client_Portal::get_current_client();
    if (!$client) wp_send_json_error(['message'=>'Pole sisse logitud']);
    global $wpdb;
    $invoice_id = absint($_POST['invoice_id'] ?? 0);
    $inv = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vesho_invoices WHERE id=%d AND client_id=%d", $invoice_id, $client->id
    ));
    if (!$inv) wp_send_json_error(['message'=>'Arvet ei leitud']);
    if ($inv->status === 'paid') wp_send_json_error(['message'=>'Arve on juba makstud']);

    $shop_id    = get_option('vesho_mc_shop_id','');
    $secret_key = get_option('vesho_mc_secret_key','');
    $sandbox    = get_option('vesho_mc_sandbox','1') === '1';
    if (!$shop_id || !$secret_key) wp_send_json_error(['message'=>'Maksekeskus pole seadistatud']);

    $base_url  = $sandbox ? 'https://api.test.maksekeskus.ee/v1' : 'https://api.maksekeskus.ee/v1';
    $pay_base  = $sandbox ? 'https://payment.test.maksekeskus.ee' : 'https://payment.maksekeskus.ee';
    $return_url = add_query_arg([
        'vesho_mc_return' => '1',
        'type'            => 'invoice',
        'invoice_id'      => $inv->id,
    ], home_url('/'));

    $body = [
        'transaction' => [
            'amount'           => number_format((float)$inv->amount, 2, '.', ''),
            'currency'         => 'EUR',
            'reference'        => $inv->invoice_number,
            'merchant_data'    => 'invoice:' . $inv->id,
            'transaction_type' => 'charge',
            'customer_url'     => $return_url,
            'notification_url' => rest_url('vesho/v1/mc-notify'),
            'cancel_url'       => add_query_arg('mc_cancel','1', home_url('/')),
        ],
        'customer' => [
            'email'   => $client->email,
            'ip'      => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'country' => 'EE',
            'locale'  => 'et',
        ],
    ];

    $response = wp_remote_post("$base_url/transactions/", [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode("$shop_id:$secret_key"),
            'Content-Type'  => 'application/json',
        ],
        'body'    => json_encode($body),
        'timeout' => 15,
    ]);
    if (is_wp_error($response)) wp_send_json_error(['message'=>'Maksekeskuse ühenduse viga']);
    $data = json_decode(wp_remote_retrieve_body($response), true);
    $tx_id = $data['transaction']['id'] ?? $data['id'] ?? null;
    if ($tx_id) {
        $wpdb->update($wpdb->prefix.'vesho_invoices', ['mc_transaction_id'=>$tx_id], ['id'=>$inv->id]);
        wp_send_json_success(['redirect_url' => "$pay_base/pay.html?trx=$tx_id"]);
    }
    wp_send_json_error(['message' => $data['message'] ?? $data['error'] ?? 'Maksekeskuse viga']);
}

// ── AJAX: Montonio — create order ─────────────────────────────────────────────
add_action('wp_ajax_vesho_pay_invoice_montonio', 'vesho_ajax_pay_invoice_montonio');
function vesho_ajax_pay_invoice_montonio() {
    check_ajax_referer('vesho_portal_nonce', 'nonce');
    $client = Vesho_CRM_Client_Portal::get_current_client();
    if (!$client) wp_send_json_error(['message'=>'Pole sisse logitud']);
    global $wpdb;
    $invoice_id = absint($_POST['invoice_id'] ?? 0);
    $inv = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vesho_invoices WHERE id=%d AND client_id=%d", $invoice_id, $client->id
    ));
    if (!$inv) wp_send_json_error(['message'=>'Arvet ei leitud']);
    if ($inv->status === 'paid') wp_send_json_error(['message'=>'Arve on juba makstud']);

    $access_key = get_option('vesho_montonio_access_key','');
    $secret_key = get_option('vesho_montonio_secret_key','');
    $sandbox    = get_option('vesho_montonio_sandbox','1') === '1';
    if (!$access_key || !$secret_key) wp_send_json_error(['message'=>'Montonio pole seadistatud']);

    $base_url  = $sandbox ? 'https://sandbox-stargate.montonio.com/api' : 'https://stargate.montonio.com/api';
    $reference = 'invoice-' . $inv->id;
    $return_url = add_query_arg([
        'vesho_montonio_return' => '1',
        'type'       => 'invoice',
        'invoice_id' => $inv->id,
    ], home_url('/'));

    $payload = [
        'accessKey'          => $access_key,
        'merchantReference'  => $reference,
        'returnUrl'          => $return_url,
        'notificationUrl'    => rest_url('vesho/v1/montonio-webhook'),
        'grandTotal'         => (float) $inv->amount,
        'currency'           => 'EUR',
        'locale'             => 'et',
        'payment'            => ['amount' => (float)$inv->amount, 'currency' => 'EUR', 'methodCode' => 'paymentInitiation'],
        'exp'                => time() + 3600,
    ];

    $token = vesho_jwt_encode($payload, $secret_key);
    $response = wp_remote_post("$base_url/orders", [
        'headers' => ['Content-Type' => 'application/json'],
        'body'    => json_encode(['data' => $token]),
        'timeout' => 15,
    ]);
    if (is_wp_error($response)) wp_send_json_error(['message'=>'Montonio ühenduse viga']);
    $data = json_decode(wp_remote_retrieve_body($response), true);
    if (!empty($data['paymentUrl'])) {
        $wpdb->update($wpdb->prefix.'vesho_invoices', ['montonio_payment_reference'=>$reference], ['id'=>$inv->id]);
        wp_send_json_success(['redirect_url' => $data['paymentUrl']]);
    }
    wp_send_json_error(['message' => $data['message'] ?? $data['error'] ?? 'Montonio viga']);
}

// ── AJAX: Stripe refund ───────────────────────────────────────────────────────
add_action( 'wp_ajax_vesho_refund_stripe', 'vesho_ajax_refund_stripe' );
function vesho_ajax_refund_stripe() {
    check_ajax_referer( 'vesho_admin_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Puudub luba' ], 403 );
    }

    global $wpdb;

    $order_id   = absint( $_POST['order_id']   ?? 0 );
    $invoice_id = absint( $_POST['invoice_id'] ?? 0 );

    if ( $order_id ) {
        $record = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_shop_orders WHERE id = %d LIMIT 1",
            $order_id
        ) );
        $table        = $wpdb->prefix . 'vesho_shop_orders';
        $amount_field = 'total';
        $status_value = 'returned';
    } elseif ( $invoice_id ) {
        $record = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_invoices WHERE id = %d LIMIT 1",
            $invoice_id
        ) );
        $table        = $wpdb->prefix . 'vesho_invoices';
        $amount_field = 'amount';
        $status_value = 'paid';
    } else {
        wp_send_json_error( [ 'message' => 'order_id või invoice_id puudub' ] );
        return;
    }

    if ( ! $record ) {
        wp_send_json_error( [ 'message' => 'Kirjet ei leitud' ] );
    }

    $stripe_payment_id = $record->stripe_payment_id ?? '';
    if ( ! $stripe_payment_id ) {
        wp_send_json_error( [ 'message' => 'Stripe makse ID puudub' ] );
    }

    $secret_key = get_option( 'vesho_stripe_secret_key', '' );
    if ( ! $secret_key ) {
        wp_send_json_error( [ 'message' => 'Stripe pole seadistatud' ] );
    }

    $response = wp_remote_post( 'https://api.stripe.com/v1/refunds', [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode( $secret_key . ':' ),
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ],
        'body'    => http_build_query( [ 'payment_intent' => $stripe_payment_id ] ),
        'timeout' => 20,
    ] );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( [ 'message' => 'Stripe ühenduse viga: ' . $response->get_error_message() ] );
    }

    $data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( ! empty( $data['error'] ) ) {
        wp_send_json_error( [ 'message' => $data['error']['message'] ?? 'Stripe tagasimakse viga' ] );
    }

    $amount = (float) ( $record->$amount_field ?? 0 );
    $wpdb->update( $table, [
        'refund_amount' => $amount,
        'status'        => $status_value,
    ], [ 'id' => $record->id ] );

    vesho_crm_log_activity( 'refund_stripe', "Stripe tagasimakse: {$stripe_payment_id}, summa: {$amount}", $order_id ? 'shop_order' : 'invoice', $record->id );
    wp_send_json_success( [ 'message' => 'Stripe tagasimakse õnnestus', 'refund_id' => $data['id'] ?? '' ] );
}

// ── AJAX: Maksekeskus refund ───────────────────────────────────────────────────
add_action( 'wp_ajax_vesho_refund_mc', 'vesho_ajax_refund_mc' );
function vesho_ajax_refund_mc() {
    check_ajax_referer( 'vesho_admin_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Puudub luba' ], 403 );
    }

    global $wpdb;

    $order_id = absint( $_POST['order_id'] ?? 0 );
    if ( ! $order_id ) {
        wp_send_json_error( [ 'message' => 'order_id puudub' ] );
    }

    $order = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vesho_shop_orders WHERE id = %d LIMIT 1",
        $order_id
    ) );
    if ( ! $order ) {
        wp_send_json_error( [ 'message' => 'Tellimust ei leitud' ] );
    }

    $transaction_id = $order->mc_transaction_id ?? '';
    if ( ! $transaction_id ) {
        wp_send_json_error( [ 'message' => 'Maksekeskuse tehingu ID puudub' ] );
    }

    $shop_id    = get_option( 'vesho_mc_shop_id', '' );
    $secret_key = get_option( 'vesho_mc_secret_key', '' );
    $sandbox    = get_option( 'vesho_mc_sandbox', '1' ) === '1';
    if ( ! $shop_id || ! $secret_key ) {
        wp_send_json_error( [ 'message' => 'Maksekeskus pole seadistatud' ] );
    }

    $base_url = $sandbox
        ? 'https://api.test.maksekeskus.ee/v1'
        : 'https://api.maksekeskus.ee/v1';

    $amount = (float) ( $order->total ?? 0 );

    $response = wp_remote_post( "{$base_url}/transactions/{$transaction_id}/refunds", [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode( "{$shop_id}:{$secret_key}" ),
            'Content-Type'  => 'application/json',
        ],
        'body'    => json_encode( [
            'amount'  => number_format( $amount, 2, '.', '' ),
            'comment' => 'Tagastus',
        ] ),
        'timeout' => 20,
    ] );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( [ 'message' => 'Maksekeskuse ühenduse viga: ' . $response->get_error_message() ] );
    }

    $http_code = wp_remote_retrieve_response_code( $response );
    $data      = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $http_code < 200 || $http_code >= 300 ) {
        wp_send_json_error( [ 'message' => $data['message'] ?? $data['error'] ?? 'Maksekeskuse tagasimakse viga' ] );
    }

    $wpdb->update( $wpdb->prefix . 'vesho_shop_orders', [
        'refund_amount' => $amount,
        'status'        => 'returned',
    ], [ 'id' => $order->id ] );

    vesho_crm_log_activity( 'refund_mc', "Maksekeskus tagasimakse: tehingu ID {$transaction_id}, summa: {$amount}", 'shop_order', $order->id );
    wp_send_json_success( [ 'message' => 'Maksekeskuse tagasimakse õnnestus' ] );
}

// ── AJAX: Montonio refund ──────────────────────────────────────────────────────
add_action( 'wp_ajax_vesho_refund_montonio', 'vesho_ajax_refund_montonio' );
function vesho_ajax_refund_montonio() {
    check_ajax_referer( 'vesho_admin_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Puudub luba' ], 403 );
    }

    global $wpdb;

    $order_id = absint( $_POST['order_id'] ?? 0 );
    if ( ! $order_id ) {
        wp_send_json_error( [ 'message' => 'order_id puudub' ] );
    }

    $order = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vesho_shop_orders WHERE id = %d LIMIT 1",
        $order_id
    ) );
    if ( ! $order ) {
        wp_send_json_error( [ 'message' => 'Tellimust ei leitud' ] );
    }

    $reference = $order->montonio_payment_reference ?? '';
    if ( ! $reference ) {
        wp_send_json_error( [ 'message' => 'Montonio makse viide puudub' ] );
    }

    $access_key = get_option( 'vesho_montonio_access_key', '' );
    $secret_key = get_option( 'vesho_montonio_secret_key', '' );
    $sandbox    = get_option( 'vesho_montonio_sandbox', '1' ) === '1';
    if ( ! $access_key || ! $secret_key ) {
        wp_send_json_error( [ 'message' => 'Montonio pole seadistatud' ] );
    }

    $base_url = $sandbox
        ? 'https://sandbox-stargate.montonio.com/api'
        : 'https://stargate.montonio.com/api';

    $amount = (float) ( $order->total ?? 0 );

    $payload = [
        'accessKey' => $access_key,
        'amount'    => $amount,
        'currency'  => 'EUR',
        'exp'       => time() + 600,
    ];
    $token = vesho_jwt_encode( $payload, $secret_key );

    $response = wp_remote_post( "{$base_url}/api/v2/orders/{$reference}/refunds", [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ],
        'body'    => json_encode( [
            'amount'   => $amount,
            'currency' => 'EUR',
        ] ),
        'timeout' => 20,
    ] );

    if ( is_wp_error( $response ) ) {
        wp_send_json_error( [ 'message' => 'Montonio ühenduse viga: ' . $response->get_error_message() ] );
    }

    $http_code = wp_remote_retrieve_response_code( $response );
    $data      = json_decode( wp_remote_retrieve_body( $response ), true );

    if ( $http_code < 200 || $http_code >= 300 ) {
        wp_send_json_error( [ 'message' => $data['message'] ?? $data['error'] ?? 'Montonio tagasimakse viga' ] );
    }

    $wpdb->update( $wpdb->prefix . 'vesho_shop_orders', [
        'refund_amount' => $amount,
        'status'        => 'returned',
    ], [ 'id' => $order->id ] );

    vesho_crm_log_activity( 'refund_montonio', "Montonio tagasimakse: viide {$reference}, summa: {$amount}", 'shop_order', $order->id );
    wp_send_json_success( [ 'message' => 'Montonio tagasimakse õnnestus' ] );
}

// ── REST: Stripe webhook ──────────────────────────────────────────────────────
function vesho_rest_stripe_webhook( WP_REST_Request $request ) {
    global $wpdb;
    $secret_key     = get_option('vesho_stripe_secret_key','');
    $webhook_secret = get_option('vesho_stripe_webhook_secret','');
    $sig            = $request->get_header('stripe-signature');
    $body           = $request->get_body();

    if ($webhook_secret && $sig) {
        $parts = [];
        foreach (explode(',', $sig) as $p) { [$k,$v] = explode('=', $p, 2); $parts[$k] = $v; }
        $ts       = $parts['t'] ?? 0;
        $expected = hash_hmac('sha256', "$ts.$body", $webhook_secret);
        if (!hash_equals($expected, $parts['v1'] ?? '')) {
            return new WP_REST_Response(['error'=>'Invalid signature'], 400);
        }
    }

    $event = json_decode($body, true);
    if (($event['type'] ?? '') === 'payment_intent.succeeded') {
        $pi         = $event['data']['object'];
        $paid_eur   = ($pi['amount_received'] ?? 0) / 100;
        $invoice_id = intval($pi['metadata']['invoice_id'] ?? 0);
        $order_id   = intval($pi['metadata']['order_id'] ?? 0);
        if ($invoice_id && vesho_validate_paid_amount('invoice', $invoice_id, $paid_eur)) {
            vesho_mark_invoice_paid($invoice_id);
        } elseif ($order_id && vesho_validate_paid_amount('shop_order', $order_id, $paid_eur)) {
            vesho_confirm_shop_order($order_id);
        }
    }
    return new WP_REST_Response(['received'=>true], 200);
}

// ── REST: Maksekeskus notify ──────────────────────────────────────────────────
function vesho_rest_mc_notify( WP_REST_Request $request ) {
    global $wpdb;
    $shop_id    = get_option('vesho_mc_shop_id','');
    $secret_key = get_option('vesho_mc_secret_key','');
    $sandbox    = get_option('vesho_mc_sandbox','1') === '1';
    if (!$shop_id || !$secret_key) return new WP_REST_Response(['ok'=>true], 200);

    $base_url     = $sandbox ? 'https://api.test.maksekeskus.ee/v1' : 'https://api.maksekeskus.ee/v1';
    $raw_json     = $request->get_param('json') ?? '';
    $received_mac = strtoupper($request->get_param('mac') ?? '');
    $transaction  = null;

    if ($raw_json && $received_mac) {
        $expected_mac = strtoupper(hash('sha512', $raw_json . $secret_key));
        if (!hash_equals($expected_mac, $received_mac)) {
            return new WP_REST_Response(['error'=>'Invalid MAC'], 400);
        }
        $transaction = json_decode($raw_json, true)['transaction'] ?? null;
    } elseif ($request->get_json_params()['transaction']['id'] ?? null) {
        $transaction = $request->get_json_params()['transaction'];
    } else {
        return new WP_REST_Response(['error'=>'Invalid payload'], 400);
    }

    if (!$transaction || ($transaction['status'] ?? '') !== 'completed') return new WP_REST_Response(['ok'=>true], 200);

    $tx_id    = intval($transaction['id']);
    $ver      = wp_remote_get("$base_url/transactions/$tx_id/", [
        'headers' => ['Authorization' => 'Basic ' . base64_encode("$shop_id:$secret_key")],
    ]);
    $ver_data = json_decode(wp_remote_retrieve_body($ver), true);
    if (($ver_data['status'] ?? '') !== 'completed') return new WP_REST_Response(['ok'=>true], 200);

    $merchant_data   = $ver_data['merchant_data'] ?? '';
    $verified_amount = $ver_data['amount'] ?? null;

    if (str_starts_with($merchant_data, 'invoice:')) {
        $inv_id = intval(explode(':', $merchant_data)[1]);
        if ($inv_id && vesho_validate_paid_amount('invoice', $inv_id, $verified_amount)) {
            vesho_mark_invoice_paid($inv_id);
        }
    } elseif (str_starts_with($merchant_data, 'order:')) {
        $ord_id = intval(explode(':', $merchant_data)[1]);
        if ($ord_id && vesho_validate_paid_amount('shop_order', $ord_id, $verified_amount)) {
            vesho_confirm_shop_order($ord_id);
        }
    }
    return new WP_REST_Response(['ok'=>true], 200);
}

// ── REST: Montonio notify ─────────────────────────────────────────────────────
function vesho_rest_montonio_notify( WP_REST_Request $request ) {
    $secret_key  = get_option('vesho_montonio_secret_key','');
    if (!$secret_key) return new WP_REST_Response(['ok'=>true], 200);
    $params      = $request->get_json_params();
    $order_token = $params['orderToken'] ?? '';
    if (!$order_token) return new WP_REST_Response(['ok'=>true], 200);

    $decoded = vesho_jwt_decode($order_token, $secret_key);
    if (!$decoded || ($decoded['paymentStatus'] ?? '') !== 'PAID') return new WP_REST_Response(['ok'=>true], 200);

    $ref         = $decoded['merchantReference'] ?? '';
    $paid_amount = $decoded['grandTotal'] ?? null;
    if (str_starts_with($ref, 'invoice-')) {
        $inv_id = intval(explode('-', $ref, 2)[1]);
        if ($inv_id && vesho_validate_paid_amount('invoice', $inv_id, $paid_amount)) {
            vesho_mark_invoice_paid($inv_id);
        }
    } elseif (str_starts_with($ref, 'order-')) {
        $ord_id = intval(explode('-', $ref, 2)[1]);
        if ($ord_id && vesho_validate_paid_amount('shop_order', $ord_id, $paid_amount)) {
            vesho_confirm_shop_order($ord_id);
        }
    }
    return new WP_REST_Response(['ok'=>true], 200);
}

// ── AJAX: Worker clock-in / clock-out (portal button) ────────────────────────
add_action( 'wp_ajax_vesho_worker_clock_action',        'vesho_ajax_worker_clock_action' );
add_action( 'wp_ajax_nopriv_vesho_worker_clock_action', 'vesho_ajax_worker_clock_action' );
function vesho_ajax_worker_clock_action() {
    check_ajax_referer( 'vesho_portal_nonce', 'nonce' );

    // Resolve worker from WP session (preferred) or PHP session
    $worker = null;
    if ( is_user_logged_in() && class_exists( 'Vesho_CRM_Worker_Portal' ) ) {
        $worker = Vesho_CRM_Worker_Portal::get_current_worker();
    }
    if ( ! $worker ) {
        if ( session_status() === PHP_SESSION_NONE ) {
            session_start();
        }
        $worker_id = absint( $_SESSION['vesho_worker_id'] ?? 0 );
        if ( $worker_id ) {
            global $wpdb;
            $worker = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}vesho_workers WHERE id=%d AND active=1 LIMIT 1",
                $worker_id
            ) );
        }
    }
    if ( ! $worker ) {
        wp_send_json_error( ['message' => 'Pole sisse logitud'] );
    }

    global $wpdb;
    $wid         = (int) $worker->id;
    $action_type = sanitize_text_field( $_POST['action_type'] ?? '' );

    $lat = isset($_POST['lat']) && $_POST['lat'] !== '' ? (float)$_POST['lat'] : null;
    $lng = isset($_POST['lng']) && $_POST['lng'] !== '' ? (float)$_POST['lng'] : null;

    // Geofence validation (only if office coords configured)
    $office_lat    = (float) get_option('vesho_office_lat', 0);
    $office_lng    = (float) get_option('vesho_office_lng', 0);
    $geofence_m    = (int)   get_option('vesho_geofence_radius', 0);
    $geofence_warn = (bool)  get_option('vesho_geofence_warn_only', '1');

    $geo_warning = '';
    if ( $geofence_m > 0 && $office_lat && $office_lng && $lat !== null && $lng !== null ) {
        // Haversine distance (metres)
        $earth_r = 6371000;
        $dLat    = deg2rad( $lat - $office_lat );
        $dLng    = deg2rad( $lng - $office_lng );
        $a       = sin($dLat/2)**2 + cos(deg2rad($office_lat)) * cos(deg2rad($lat)) * sin($dLng/2)**2;
        $dist_m  = 2 * $earth_r * asin(sqrt($a));

        if ( $dist_m > $geofence_m ) {
            $dist_km = round($dist_m / 1000, 2);
            if ( ! $geofence_warn ) {
                wp_send_json_error(['message' => sprintf('Oled töökohast liiga kaugel (%.0f m). Kellalöök lubatud ainult %.0f m raadiuses.', $dist_m, $geofence_m)]);
            }
            $geo_warning = sprintf(' (hoiatus: %.0f m töökoha raadiusest väljas)', $dist_m);
        }
    }

    if ( $action_type === 'in' ) {
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}vesho_work_hours
             WHERE worker_id=%d AND DATE(start_time)=CURDATE() AND end_time IS NULL",
            $wid
        ) );
        if ( $existing ) {
            wp_send_json_error( ['message' => 'Oled juba tööl'] );
        }
        $now = current_time( 'mysql' );
        $wpdb->insert( $wpdb->prefix . 'vesho_work_hours', [
            'worker_id'     => $wid,
            'date'          => current_time( 'Y-m-d' ),
            'start_time'    => $now,
            'hours'         => 0,
            'clock_in_lat'  => $lat,
            'clock_in_lng'  => $lng,
            'created_at'    => $now,
        ] );
        wp_send_json_success( ['message' => 'Tööpäev alustatud' . $geo_warning, 'time' => current_time('H:i'), 'geo_warning' => $geo_warning !== ''] );

    } elseif ( $action_type === 'out' ) {
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_work_hours
             WHERE worker_id=%d AND DATE(start_time)=CURDATE() AND end_time IS NULL
             ORDER BY start_time DESC LIMIT 1",
            $wid
        ) );
        if ( ! $row ) {
            wp_send_json_error( ['message' => 'Pole sisse logitud'] );
        }
        $end   = current_time( 'mysql' );
        $hours = round( ( strtotime( $end ) - strtotime( $row->start_time ) ) / 3600, 2 );
        $wpdb->update(
            $wpdb->prefix . 'vesho_work_hours',
            ['end_time' => $end, 'hours' => max(0.01, $hours), 'clock_out_lat' => $lat, 'clock_out_lng' => $lng],
            ['id' => $row->id]
        );
        wp_send_json_success( ['message' => 'Tööpäev lõpetatud' . $geo_warning, 'hours' => $hours, 'geo_warning' => $geo_warning !== ''] );
    }

    wp_send_json_error( ['message' => 'Tundmatu toiming'] );
}

// ── AJAX: EAN lookup (inventory) ─────────────────────────────────────────────
add_action( 'wp_ajax_vesho_ean_lookup', 'vesho_ajax_ean_lookup' );
function vesho_ajax_ean_lookup() {
    check_ajax_referer( 'vesho_crm_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( ['message' => 'Puudub luba'], 403 );
    global $wpdb;
    $ean = sanitize_text_field( $_POST['ean'] ?? '' );
    if ( ! $ean ) wp_send_json_error( ['message' => 'EAN kood puudub'] );

    $item = $wpdb->get_row( $wpdb->prepare(
        "SELECT id, name, sku, ean, quantity, unit, purchase_price, sell_price, location, category
         FROM {$wpdb->prefix}vesho_inventory WHERE ean=%s AND archived=0 LIMIT 1", $ean
    ) );
    if ( $item ) {
        wp_send_json_success( $item );
    } else {
        // Try SKU match as fallback
        $item = $wpdb->get_row( $wpdb->prepare(
            "SELECT id, name, sku, ean, quantity, unit, purchase_price, sell_price, location, category
             FROM {$wpdb->prefix}vesho_inventory WHERE sku=%s AND archived=0 LIMIT 1", $ean
        ) );
        if ( $item ) {
            wp_send_json_success( $item );
        } else {
            wp_send_json_error( ['message' => 'Toodet ei leitud: ' . esc_html( $ean )] );
        }
    }
}

// ── AJAX: Stock count update (warehouse) ──────────────────────────────────────
add_action( 'wp_ajax_vesho_stock_count_update', 'vesho_ajax_stock_count_update' );
function vesho_ajax_stock_count_update() {
    check_ajax_referer( 'vesho_crm_nonce', 'nonce' );
    if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( ['message' => 'Puudub luba'], 403 );
    global $wpdb;
    $inventory_id = absint( $_POST['inventory_id'] ?? 0 );
    $qty          = floatval( $_POST['quantity'] ?? 0 );
    $note         = sanitize_text_field( $_POST['note'] ?? '' );
    if ( ! $inventory_id ) wp_send_json_error( ['message' => 'Toode puudub'] );

    $item = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vesho_inventory WHERE id=%d AND archived=0", $inventory_id
    ) );
    if ( ! $item ) wp_send_json_error( ['message' => 'Toodet ei leitud'] );

    $diff = $qty - $item->quantity;
    $wpdb->update( $wpdb->prefix . 'vesho_inventory', ['quantity' => $qty], ['id' => $inventory_id] );

    // Log difference as write-off or note
    if ( $diff != 0 ) {
        $user = wp_get_current_user();
        $wpdb->insert( $wpdb->prefix . 'vesho_inventory_writeoffs', [
            'inventory_id' => $inventory_id,
            'qty'          => abs( $diff ),
            'type'         => $diff < 0 ? 'inventory_count' : 'inventory_count_plus',
            'reason'       => 'Inventuur' . ( $note ? ': ' . $note : '' ) . ' (delta: ' . ( $diff > 0 ? '+' : '' ) . number_format( $diff, 3 ) . ')',
            'user_name'    => $user ? $user->display_name : '',
            'created_at'   => current_time( 'mysql' ),
        ] );
    }

    vesho_crm_log_activity( 'stock_count', "Inventuur: {$item->name} — uus kogus: {$qty} (delta: {$diff})", 'inventory', $inventory_id );
    wp_send_json_success( ['new_quantity' => $qty, 'item_name' => $item->name] );
}

// ── AJAX: Shop enquiry (public) ──────────────────────────────────────────────
add_action( 'wp_ajax_nopriv_vesho_shop_enquiry', 'vesho_ajax_shop_enquiry' );
add_action( 'wp_ajax_vesho_shop_enquiry',        'vesho_ajax_shop_enquiry' );
function vesho_ajax_shop_enquiry() {
    check_ajax_referer( 'vesho_shop_enquiry', 'nonce' );
    global $wpdb;
    $item_id     = absint( $_POST['item_id'] ?? 0 );
    $client_name = sanitize_text_field( $_POST['client_name'] ?? '' );
    $contact     = sanitize_text_field( $_POST['contact'] ?? '' );
    $quantity    = max( 1, intval( $_POST['quantity'] ?? 1 ) );
    $message     = sanitize_textarea_field( $_POST['message'] ?? '' );

    if ( ! $item_id || ! $client_name || ! $contact ) {
        wp_send_json_error( 'Täitke kohustuslikud väljad.' );
    }

    $item = $wpdb->get_row( $wpdb->prepare(
        "SELECT name, shop_price FROM {$wpdb->prefix}vesho_inventory WHERE id=%d AND archived=0 LIMIT 1", $item_id
    ) );
    if ( ! $item ) {
        wp_send_json_error( 'Toodet ei leitud.' );
    }

    $note = sprintf( 'Pood: %s × %d (%.2f €/tk)%s', $item->name, $quantity, $item->shop_price, $message ? ' | '.$message : '' );

    $wpdb->insert( "{$wpdb->prefix}vesho_guest_requests", [
        'name'         => $client_name,
        'phone'        => $contact,
        'service_type' => 'Pood',
        'description'  => $note,
        'status'       => 'new',
        'created_at'   => current_time( 'mysql' ),
    ] );

    wp_send_json_success();
}

// ── AJAX: Worker barcode / QR card login ─────────────────────────────────────
add_action( 'wp_ajax_nopriv_vesho_worker_barcode_login', 'vesho_ajax_worker_barcode_login' );
add_action( 'wp_ajax_vesho_worker_barcode_login',        'vesho_ajax_worker_barcode_login' );
function vesho_ajax_worker_barcode_login() {
    check_ajax_referer( 'vesho_portal_nonce', 'nonce' );
    global $wpdb;
    $token = sanitize_text_field( $_POST['token'] ?? '' );
    if ( ! $token ) wp_send_json_error( ['message' => 'Token puudub'] );

    $worker = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vesho_workers WHERE barcode_token=%s AND active=1 LIMIT 1", $token
    ) );
    if ( ! $worker ) wp_send_json_error( ['message' => 'Töötajat ei leitud või kaart ei ole aktiivne'] );

    // Log the worker in via WordPress user account if linked, or via session
    if ( ! empty( $worker->user_id ) ) {
        wp_set_auth_cookie( $worker->user_id, true );
        wp_send_json_success( ['name' => $worker->name, 'redirect' => home_url('/worker/') ] );
    }

    // Fallback: session-based (if no WP user linked)
    if ( ! session_id() ) @session_start();
    $_SESSION['vesho_worker_id']   = $worker->id;
    $_SESSION['vesho_worker_name'] = $worker->name;
    wp_send_json_success( ['name' => $worker->name, 'redirect' => home_url('/worker/') ] );
}

// ── AJAX: Upload workorder photo ──────────────────────────────────────────────
add_action('wp_ajax_vesho_upload_workorder_photo', 'vesho_ajax_upload_workorder_photo');
add_action('wp_ajax_nopriv_vesho_upload_workorder_photo', 'vesho_ajax_upload_workorder_photo');
function vesho_ajax_upload_workorder_photo() {
    check_ajax_referer('vesho_worker_nonce', 'nonce');
    if (!current_user_can('read') && !isset($_SESSION['vesho_worker_id'])) {
        wp_send_json_error(['message' => 'Pole lubatud']);
    }
    global $wpdb;
    $workorder_id = absint($_POST['workorder_id'] ?? 0);
    $worker_id    = absint($_SESSION['vesho_worker_id'] ?? 0) ?: absint($_POST['worker_id'] ?? 0);

    if (!$workorder_id || empty($_FILES['photo'])) {
        wp_send_json_error(['message' => 'Vigased andmed']);
    }

    // Check photo count
    $count = (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_workorder_photos WHERE workorder_id=%d", $workorder_id
    ));
    if ($count >= 5) wp_send_json_error(['message' => 'Maksimaalselt 5 fotot']);

    // Use WP upload handling
    if (!function_exists('wp_handle_upload')) require_once(ABSPATH . 'wp-admin/includes/file.php');
    $upload = wp_handle_upload($_FILES['photo'], ['test_form' => false]);
    if (isset($upload['error'])) wp_send_json_error(['message' => $upload['error']]);

    $wpdb->insert($wpdb->prefix.'vesho_workorder_photos', [
        'workorder_id' => $workorder_id,
        'worker_id'    => $worker_id ?: null,
        'filename'     => $upload['url'],
        'created_at'   => current_time('mysql'),
    ]);

    wp_send_json_success(['url' => $upload['url'], 'id' => $wpdb->insert_id]);
}

// ── Cron: Mark overdue invoices ───────────────────────────────────────────────
function vesho_crm_mark_overdue_invoices() {
    global $wpdb;
    $today = current_time( 'Y-m-d' );
    $wpdb->query( $wpdb->prepare(
        "UPDATE {$wpdb->prefix}vesho_invoices
         SET status = 'overdue'
         WHERE status = 'sent'
           AND due_date IS NOT NULL
           AND due_date < %s",
        $today
    ) );
}

// ── Cron: Low stock alert ─────────────────────────────────────────────────────
function vesho_crm_check_low_stock() {
    if ( get_option( 'vesho_low_stock_alert', '1' ) !== '1' ) return;
    global $wpdb;
    $items = $wpdb->get_results(
        "SELECT name, quantity, min_quantity, unit
         FROM {$wpdb->prefix}vesho_inventory
         WHERE archived=0 AND min_quantity IS NOT NULL AND quantity <= min_quantity
         ORDER BY name"
    );
    if ( empty( $items ) ) return;
    $notify = get_option( 'vesho_notify_email', get_option( 'admin_email' ) );
    if ( ! $notify ) return;
    $co   = get_option( 'vesho_company_name', 'Vesho CRM' );
    $body = "Tere!\n\nJärgmistel laokaupadel on laoseis alla miinimumi:\n\n";
    foreach ( $items as $item ) {
        $body .= sprintf( "  • %s: %.3g %s (min: %.3g)\n", $item->name, $item->quantity, $item->unit, $item->min_quantity );
    }
    $body .= "\nPalun täienda laovaru.\n\nLugupidamisega,\n{$co}";
    wp_mail( $notify, "[{$co}] ⚠️ Madal laoseis — " . count( $items ) . " kaupa", $body );
}

// ── Cron: Maintenance reminders to clients ────────────────────────────────────
function vesho_crm_send_maintenance_reminders() {
    $days = (int) get_option( 'vesho_maintenance_reminder_days', '3' );
    if ( $days <= 0 ) return;
    global $wpdb;
    $target_date = date( 'Y-m-d', strtotime( "+{$days} days" ) );
    $items = $wpdb->get_results( $wpdb->prepare(
        "SELECT m.id, m.scheduled_date, m.description,
                d.name as device_name,
                c.email as client_email, c.name as client_name
         FROM {$wpdb->prefix}vesho_maintenances m
         JOIN {$wpdb->prefix}vesho_devices d ON d.id=m.device_id
         JOIN {$wpdb->prefix}vesho_clients c ON c.id=d.client_id
         WHERE m.status='scheduled' AND m.scheduled_date=%s
           AND m.reminder_sent IS NULL
         ORDER BY m.id",
        $target_date
    ) );
    if ( empty( $items ) ) return;
    $co = get_option( 'vesho_company_name', 'Vesho CRM' );
    foreach ( $items as $m ) {
        if ( ! $m->client_email ) continue;
        $date_fmt = date( 'd.m.Y', strtotime( $m->scheduled_date ) );
        $body  = "Tere, {$m->client_name}!\n\n";
        $body .= "Tuletame meelde, et {$days} päeva pärast ({$date_fmt}) on teie seadmele \"{$m->device_name}\" planeeritud hooldus.\n";
        if ( $m->description ) $body .= "\nKirjeldus: {$m->description}\n";
        $body .= "\nKüsimuste korral palun võtke meiega ühendust.\n\nLugupidamisega,\n{$co}";
        wp_mail( $m->client_email, "[{$co}] Hoolduse meeldetuletus — {$date_fmt}", $body );
        // Mark reminder sent
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}vesho_maintenances SET reminder_sent=NOW() WHERE id=%d",
            $m->id
        ) );
    }
}

// ── Cron: Worker reminder 1 day before work order ─────────────────────────────
function vesho_crm_send_worker_reminders() {
    if ( get_option( 'vesho_worker_reminder', '0' ) !== '1' ) return;
    global $wpdb;
    $tomorrow = date( 'Y-m-d', strtotime( '+1 day' ) );
    $orders = $wpdb->get_results( $wpdb->prepare(
        "SELECT wo.id, wo.title, wo.scheduled_date, wo.description,
                w.name as worker_name, w.work_email
         FROM {$wpdb->prefix}vesho_workorders wo
         JOIN {$wpdb->prefix}vesho_workers w ON w.id=wo.worker_id
         WHERE wo.status IN ('pending','assigned') AND wo.scheduled_date=%s
           AND w.work_email != '' AND w.active=1
         ORDER BY wo.id",
        $tomorrow
    ) );
    if ( empty( $orders ) ) return;
    $co = get_option( 'vesho_company_name', 'Vesho CRM' );
    $portal = home_url( '/worker/' );
    foreach ( $orders as $wo ) {
        $date_fmt = date( 'd.m.Y', strtotime( $wo->scheduled_date ) );
        $body  = "Tere, {$wo->worker_name}!\n\n";
        $body .= "Meeldetuletus: homme ({$date_fmt}) on sul töökäsk:\n\n";
        $body .= "Töökäsk: {$wo->title}\n";
        if ( $wo->description ) $body .= "Kirjeldus: {$wo->description}\n";
        $body .= "\nPortaal: {$portal}\n\nLugupidamisega,\n{$co}";
        wp_mail( $wo->work_email, "[{$co}] Meeldetuletus: töökäsk {$date_fmt}", $body );
    }
}

/**
 * New AJAX handler functions for vesho-crm inventory system.
 *
 * Requires:
 *   - PhpSpreadsheet (for XLSX parsing):  composer require phpoffice/phpspreadsheet
 *     OR use the lightweight fallback CSV-only path included below.
 *
 * All handlers validate wp_nonce with key 'vesho_crm_nonce'.
 */

defined( 'ABSPATH' ) || exit;

/* ── Helper ─────────────────────────────────────────────────────────────── */
function vesho_inv_check_nonce() {
    if ( ! check_ajax_referer( 'vesho_crm_nonce', 'nonce', false ) ) {
        wp_send_json_error( [ 'message' => 'Vigane turvakood' ], 403 );
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   1. vesho_save_inventory_inline
   Saves add or edit of an inventory item from the inline form.
   ═══════════════════════════════════════════════════════════════════════════ */
add_action( 'wp_ajax_vesho_save_inventory_inline', 'vesho_ajax_save_inventory_inline' );
function vesho_ajax_save_inventory_inline() {
    global $wpdb;
    vesho_inv_check_nonce();

    $id           = absint( $_POST['inventory_id'] ?? 0 );
    $image_delete = ( $_POST['image_delete'] ?? '0' ) === '1';
    $image_url    = esc_url_raw( $_POST['image_url'] ?? '' );

    // Handle file upload
    if ( ! empty( $_FILES['image_file']['tmp_name'] ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        $uploaded = wp_handle_upload( $_FILES['image_file'], [ 'test_form' => false ] );
        if ( isset( $uploaded['url'] ) ) {
            $image_url = $uploaded['url'];
        }
    } elseif ( $image_delete ) {
        $image_url = '';
    }

    $data = [
        'name'             => sanitize_text_field( $_POST['name'] ?? '' ),
        'sku'              => sanitize_text_field( $_POST['sku'] ?? '' ),
        'ean'              => sanitize_text_field( $_POST['ean'] ?? '' ),
        'category'         => sanitize_text_field( $_POST['category'] ?? '' ),
        'unit'             => sanitize_text_field( $_POST['unit'] ?? 'tk' ),
        'quantity'         => (float) ( $_POST['quantity'] ?? 0 ),
        'min_quantity'     => strlen( $_POST['min_quantity'] ?? '' ) ? (float) $_POST['min_quantity'] : null,
        'purchase_price'   => strlen( $_POST['purchase_price'] ?? '' ) ? (float) $_POST['purchase_price'] : null,
        'sell_price'       => strlen( $_POST['sell_price'] ?? '' ) ? (float) $_POST['sell_price'] : null,
        'location'         => sanitize_text_field( $_POST['location'] ?? '' ),
        'notes'            => sanitize_textarea_field( $_POST['notes'] ?? '' ),
        'shop_enabled'     => isset( $_POST['shop_enabled'] ) ? 1 : 0,
        'shop_description' => sanitize_textarea_field( $_POST['shop_description'] ?? '' ),
        'shop_price'       => strlen( $_POST['shop_price'] ?? '' ) ? (float) $_POST['shop_price'] : null,
        'image_url'        => $image_url,
    ];

    if ( empty( $data['name'] ) ) {
        wp_send_json_error( [ 'message' => 'Toote nimi on kohustuslik' ] );
    }

    $table = $wpdb->prefix . 'vesho_inventory';

    if ( $id ) {
        // Check if product going from out-of-stock to in-stock → trigger notifications
        $old_qty = (float) $wpdb->get_var( $wpdb->prepare(
            "SELECT quantity FROM {$wpdb->prefix}vesho_inventory WHERE id=%d", $id
        ));
        $wpdb->update( $table, $data, [ 'id' => $id ] );
        if ( $old_qty <= 0 && $data['quantity'] > 0 ) {
            vesho_send_stock_notifications( $id, $data['name'] );
        }
        wp_send_json_success( [ 'message' => 'Uuendatud: ' . $data['name'], 'id' => $id, 'image_url' => $image_url ] );
    } else {
        $data['archived'] = 0;
        $wpdb->insert( $table, $data );
        wp_send_json_success( [ 'message' => 'Lisatud: ' . $data['name'], 'id' => $wpdb->insert_id, 'image_url' => $image_url ] );
    }
}

// ── Stock notification: send emails when product back in stock ────────────────
function vesho_send_stock_notifications( int $inventory_id, string $product_name ): void {
    global $wpdb;
    $emails = $wpdb->get_col( $wpdb->prepare(
        "SELECT email FROM {$wpdb->prefix}vesho_stock_notifications WHERE inventory_id=%d AND sent=0",
        $inventory_id
    ));
    if ( empty($emails) ) return;
    $co       = get_option( 'vesho_company_name', get_bloginfo('name') );
    $shop_url = get_permalink( get_page_by_path('pood') ) ?: home_url('/pood/');
    $prod_url = add_query_arg(['shop_view'=>'product','pid'=>$inventory_id], $shop_url);
    foreach ( $emails as $email ) {
        wp_mail(
            $email,
            "[{$co}] Toode on taas laos saadaval",
            "Tere!\n\nToode \"{$product_name}\" on taas laos saadaval.\n\nVaata toodet: {$prod_url}\n\nLugupidamisega,\n{$co}"
        );
    }
    $wpdb->query( $wpdb->prepare(
        "UPDATE {$wpdb->prefix}vesho_stock_notifications SET sent=1 WHERE inventory_id=%d AND sent=0",
        $inventory_id
    ));
}

// ── AJAX: Subscribe to back-in-stock notification ────────────────────────────
add_action( 'wp_ajax_vesho_stock_notify',        'vesho_ajax_stock_notify' );
add_action( 'wp_ajax_nopriv_vesho_stock_notify', 'vesho_ajax_stock_notify' );
function vesho_ajax_stock_notify() {
    check_ajax_referer( 'vesho_cart_nonce', 'nonce' );
    global $wpdb;
    $pid   = absint( $_POST['pid'] ?? 0 );
    $email = sanitize_email( $_POST['email'] ?? '' );
    if ( !$pid || !is_email($email) ) wp_send_json_error( 'Vigane sisend' );
    // Verify product exists and is out of stock
    $qty = $wpdb->get_var( $wpdb->prepare(
        "SELECT quantity FROM {$wpdb->prefix}vesho_inventory WHERE id=%d AND shop_enabled=1 AND archived=0",
        $pid
    ));
    if ( $qty === null ) wp_send_json_error( 'Toodet ei leitud' );
    if ( (float)$qty > 0 ) wp_send_json_error( 'Toode on juba laos saadaval' );
    // Max 500 subscribers per product (spam prevention)
    $count = (int)$wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_stock_notifications WHERE inventory_id=%d",
        $pid
    ));
    if ( $count >= 500 ) wp_send_json_error( 'Teavitusnimekiri on täis' );
    $wpdb->query( $wpdb->prepare(
        "INSERT IGNORE INTO {$wpdb->prefix}vesho_stock_notifications (inventory_id, email) VALUES (%d, %s)",
        $pid, $email
    ));
    wp_send_json_success( 'Teavitame teid kui toode on taas saadaval!' );
}

/* ═══════════════════════════════════════════════════════════════════════════
   2. vesho_archive_inventory
   ═══════════════════════════════════════════════════════════════════════════ */
add_action( 'wp_ajax_vesho_archive_inventory', 'vesho_ajax_archive_inventory' );
function vesho_ajax_archive_inventory() {
    global $wpdb;
    vesho_inv_check_nonce();
    $id = absint( $_POST['inventory_id'] ?? 0 );
    if ( ! $id ) wp_send_json_error( [ 'message' => 'Vigane ID' ] );
    $wpdb->update( $wpdb->prefix . 'vesho_inventory', [ 'archived' => 1 ], [ 'id' => $id ] );
    wp_send_json_success( [ 'message' => 'Arhiveeritud' ] );
}

/* ═══════════════════════════════════════════════════════════════════════════
   3. vesho_bulk_delete_inventory
   ═══════════════════════════════════════════════════════════════════════════ */
add_action( 'wp_ajax_vesho_bulk_delete_inventory', 'vesho_ajax_bulk_delete_inventory' );
function vesho_ajax_bulk_delete_inventory() {
    global $wpdb;
    vesho_inv_check_nonce();
    $raw = sanitize_text_field( $_POST['ids'] ?? '' );
    $ids = array_filter( array_map( 'absint', explode( ',', $raw ) ) );
    if ( empty( $ids ) ) wp_send_json_error( [ 'message' => 'Pole ID-sid' ] );
    $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
    $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}vesho_inventory WHERE id IN ($placeholders)", ...$ids ) );
    wp_send_json_success( [ 'deleted' => count( $ids ) ] );
}

/* ═══════════════════════════════════════════════════════════════════════════
   4. vesho_toggle_shop
   ═══════════════════════════════════════════════════════════════════════════ */
add_action( 'wp_ajax_vesho_toggle_shop', 'vesho_ajax_toggle_shop' );
function vesho_ajax_toggle_shop() {
    global $wpdb;
    vesho_inv_check_nonce();
    $id      = absint( $_POST['inventory_id'] ?? 0 );
    $enabled = (int) ( $_POST['enabled'] ?? 0 );
    if ( ! $id ) wp_send_json_error( [ 'message' => 'Vigane ID' ] );
    $wpdb->update( $wpdb->prefix . 'vesho_inventory', [ 'shop_enabled' => $enabled ], [ 'id' => $id ] );
    wp_send_json_success( [ 'enabled' => $enabled ] );
}

/* ═══════════════════════════════════════════════════════════════════════════
   5. vesho_gen_locations
   Generates warehouse location codes (e.g. A-01-03) and inserts them.
   ═══════════════════════════════════════════════════════════════════════════ */
add_action( 'wp_ajax_vesho_get_locations', 'vesho_ajax_get_locations' );
function vesho_ajax_get_locations() {
    global $wpdb;
    vesho_inv_check_nonce();
    $rows = $wpdb->get_results(
        "SELECT wl.*, i.id as item_id, i.name as item_name, i.sku, i.ean, i.quantity, i.unit
         FROM {$wpdb->prefix}vesho_warehouse_locations wl
         LEFT JOIN {$wpdb->prefix}vesho_inventory i ON i.location = wl.code
         ORDER BY wl.code ASC"
    );
    wp_send_json_success( $rows );
}

add_action( 'wp_ajax_vesho_add_location', 'vesho_ajax_add_location' );
function vesho_ajax_add_location() {
    global $wpdb;
    vesho_inv_check_nonce();
    $code = strtoupper( sanitize_text_field( $_POST['code'] ?? '' ) );
    $desc = sanitize_text_field( $_POST['description'] ?? '' );
    if ( ! $code ) wp_send_json_error( [ 'message' => 'Kood puudub' ] );
    $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}vesho_warehouse_locations WHERE code=%s", $code ) );
    if ( $exists ) wp_send_json_error( [ 'message' => 'Kood on juba olemas' ] );
    $wpdb->insert( $wpdb->prefix . 'vesho_warehouse_locations', [ 'code' => $code, 'description' => $desc, 'created_at' => current_time('mysql') ] );
    wp_send_json_success( [ 'id' => $wpdb->insert_id ] );
}

add_action( 'wp_ajax_vesho_assign_location', 'vesho_ajax_assign_location' );
function vesho_ajax_assign_location() {
    global $wpdb;
    vesho_inv_check_nonce();
    $code    = strtoupper( sanitize_text_field( $_POST['code'] ?? '' ) );
    $inv_id  = absint( $_POST['inventory_id'] ?? 0 );
    if ( ! $code || ! $inv_id ) wp_send_json_error( [ 'message' => 'Vigased parameetrid' ] );
    // Check location exists
    $loc = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}vesho_warehouse_locations WHERE code=%s", $code ) );
    if ( ! $loc ) wp_send_json_error( [ 'message' => 'Aadress ei leitud' ] );
    // Check not occupied by another item
    $occupied = $wpdb->get_row( $wpdb->prepare( "SELECT id, name FROM {$wpdb->prefix}vesho_inventory WHERE location=%s AND id!=%d", $code, $inv_id ) );
    if ( $occupied ) wp_send_json_error( [ 'message' => 'Hõivatud: ' . $occupied->name ] );
    $wpdb->update( $wpdb->prefix . 'vesho_inventory', [ 'location' => $code ], [ 'id' => $inv_id ] );
    wp_send_json_success();
}

add_action( 'wp_ajax_vesho_get_stock_counts', 'vesho_ajax_get_stock_counts' );
function vesho_ajax_get_stock_counts() {
    global $wpdb;
    vesho_inv_check_nonce();
    $rows = $wpdb->get_results(
        "SELECT sc.*, COUNT(sci.id) as item_count,
            SUM(CASE WHEN sci.counted_qty IS NOT NULL THEN 1 ELSE 0 END) as counted_count
         FROM {$wpdb->prefix}vesho_stock_counts sc
         LEFT JOIN {$wpdb->prefix}vesho_stock_count_items sci ON sci.stock_count_id = sc.id
         GROUP BY sc.id ORDER BY sc.created_at DESC"
    );
    wp_send_json_success( $rows );
}

add_action( 'wp_ajax_vesho_gen_locations', 'vesho_ajax_gen_locations' );
function vesho_ajax_gen_locations() {
    global $wpdb;
    vesho_inv_check_nonce();

    $blockRange = strtoupper( sanitize_text_field( $_POST['block_range'] ?? 'A' ) );
    $rows       = max( 1, min( 99, absint( $_POST['rows'] ?? 5 ) ) );
    $cols       = max( 1, min( 99, absint( $_POST['cols'] ?? 10 ) ) );
    $desc       = sanitize_text_field( $_POST['description'] ?? '' );

    // Parse block range: 'A' or 'A-C'
    $blocks = [];
    if ( strlen( $blockRange ) === 1 ) {
        $blocks = [ $blockRange ];
    } elseif ( strlen( $blockRange ) >= 3 && $blockRange[1] === '-' ) {
        $start = ord( $blockRange[0] );
        $end   = ord( $blockRange[2] );
        for ( $c = $start; $c <= $end; $c++ ) {
            $blocks[] = chr( $c );
        }
    } else {
        $blocks = [ $blockRange[0] ?? 'A' ];
    }

    $table   = $wpdb->prefix . 'vesho_warehouse_locations';
    $created = 0;

    foreach ( $blocks as $block ) {
        for ( $r = 1; $r <= $rows; $r++ ) {
            for ( $p = 1; $p <= $cols; $p++ ) {
                $code = sprintf( '%s-%02d-%02d', $block, $r, $p );
                $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE code=%s", $code ) );
                if ( ! $exists ) {
                    $wpdb->insert( $table, [ 'code' => $code, 'description' => $desc, 'created_at' => current_time( 'mysql' ) ] );
                    $created++;
                }
            }
        }
    }

    wp_send_json_success( [ 'created' => $created ] );
}

/* ═══════════════════════════════════════════════════════════════════════════
   6. vesho_delete_location
   ═══════════════════════════════════════════════════════════════════════════ */
add_action( 'wp_ajax_vesho_delete_location', 'vesho_ajax_delete_location' );
function vesho_ajax_delete_location() {
    global $wpdb;
    vesho_inv_check_nonce();
    $id = absint( $_POST['location_id'] ?? 0 );
    if ( ! $id ) wp_send_json_error( [ 'message' => 'Vigane ID' ] );
    $wpdb->delete( $wpdb->prefix . 'vesho_warehouse_locations', [ 'id' => $id ] );
    wp_send_json_success();
}

/* ═══════════════════════════════════════════════════════════════════════════
   7. vesho_create_stock_count
   ═══════════════════════════════════════════════════════════════════════════ */
add_action( 'wp_ajax_vesho_create_stock_count', 'vesho_ajax_create_stock_count' );
function vesho_ajax_create_stock_count() {
    global $wpdb;
    vesho_inv_check_nonce();

    $name = sanitize_text_field( $_POST['name'] ?? '' );
    if ( empty( $name ) ) wp_send_json_error( [ 'message' => 'Nimi puudub' ] );

    $user = wp_get_current_user();

    // Create the count record
    $wpdb->insert( $wpdb->prefix . 'vesho_stock_counts', [
        'name'       => $name,
        'status'     => 'draft',
        'created_by' => $user->display_name ?: $user->user_login,
        'created_at' => current_time( 'mysql' ),
    ] );
    $sc_id = $wpdb->insert_id;
    if ( ! $sc_id ) wp_send_json_error( [ 'message' => 'Andmebaasi viga' ] );

    // Populate items from current inventory
    $items = $wpdb->get_results( "SELECT id, name, sku, ean, unit, quantity, category, location FROM {$wpdb->prefix}vesho_inventory WHERE archived=0 ORDER BY name ASC" );
    foreach ( $items as $item ) {
        $wpdb->insert( $wpdb->prefix . 'vesho_stock_count_items', [
            'stock_count_id' => $sc_id,
            'inventory_id'   => $item->id,
            'name'           => $item->name,
            'sku'            => $item->sku,
            'ean'            => $item->ean,
            'unit'           => $item->unit,
            'category'       => $item->category,
            'location'       => $item->location,
            'expected_qty'   => $item->quantity,
            'counted_qty'    => null,
        ] );
    }

    wp_send_json_success( [ 'id' => $sc_id, 'item_count' => count( $items ) ] );
}

/* ═══════════════════════════════════════════════════════════════════════════
   8. vesho_get_stock_count
   ═══════════════════════════════════════════════════════════════════════════ */
add_action( 'wp_ajax_vesho_get_stock_count', 'vesho_ajax_get_stock_count' );
function vesho_ajax_get_stock_count() {
    global $wpdb;
    vesho_inv_check_nonce();

    $id = absint( $_POST['stock_count_id'] ?? 0 );
    if ( ! $id ) wp_send_json_error( [ 'message' => 'Vigane ID' ] );

    $count = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}vesho_stock_counts WHERE id=%d", $id ) );
    if ( ! $count ) wp_send_json_error( [ 'message' => 'Inventuuri ei leitud' ] );

    $items = $wpdb->get_results( $wpdb->prepare(
        "SELECT sci.*, i.quantity as current_qty
         FROM {$wpdb->prefix}vesho_stock_count_items sci
         LEFT JOIN {$wpdb->prefix}vesho_inventory i ON i.id = sci.inventory_id
         WHERE sci.stock_count_id = %d
         ORDER BY sci.location ASC, sci.name ASC",
        $id
    ) );

    wp_send_json_success( [
        'id'      => $count->id,
        'name'    => $count->name,
        'status'  => $count->status,
        'notes'   => $count->notes,
        'items'   => $items,
    ] );
}

/* ═══════════════════════════════════════════════════════════════════════════
   9. vesho_save_count_item
   ═══════════════════════════════════════════════════════════════════════════ */
add_action( 'wp_ajax_vesho_save_count_item', 'vesho_ajax_save_count_item' );
function vesho_ajax_save_count_item() {
    global $wpdb;
    vesho_inv_check_nonce();

    $sc_id  = absint( $_POST['stock_count_id'] ?? 0 );
    $inv_id = absint( $_POST['inventory_id'] ?? 0 );
    $qty    = (float) ( $_POST['counted_qty'] ?? 0 );

    if ( ! $sc_id || ! $inv_id ) wp_send_json_error( [ 'message' => 'Vigased parameetrid' ] );

    // Check count is still draft
    $status = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$wpdb->prefix}vesho_stock_counts WHERE id=%d", $sc_id ) );
    if ( $status !== 'draft' ) wp_send_json_error( [ 'message' => 'Inventuur on juba lõpetatud' ] );

    $wpdb->update(
        $wpdb->prefix . 'vesho_stock_count_items',
        [ 'counted_qty' => $qty ],
        [ 'stock_count_id' => $sc_id, 'inventory_id' => $inv_id ]
    );

    wp_send_json_success( [ 'qty' => $qty ] );
}

/* ═══════════════════════════════════════════════════════════════════════════
   10. vesho_finalize_stock_count
   Finalizes count and applies counted quantities to inventory.
   ═══════════════════════════════════════════════════════════════════════════ */
add_action( 'wp_ajax_vesho_finalize_stock_count', 'vesho_ajax_finalize_stock_count' );
function vesho_ajax_finalize_stock_count() {
    global $wpdb;
    vesho_inv_check_nonce();

    $sc_id = absint( $_POST['stock_count_id'] ?? 0 );
    if ( ! $sc_id ) wp_send_json_error( [ 'message' => 'Vigane ID' ] );

    $count = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}vesho_stock_counts WHERE id=%d", $sc_id ) );
    if ( ! $count ) wp_send_json_error( [ 'message' => 'Ei leitud' ] );
    if ( $count->status === 'finalized' ) wp_send_json_error( [ 'message' => 'Juba lõpetatud' ] );

    // Get all items that have been counted
    $items = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vesho_stock_count_items WHERE stock_count_id=%d AND counted_qty IS NOT NULL",
        $sc_id
    ) );

    $user = wp_get_current_user();

    foreach ( $items as $item ) {
        $old_qty = (float) $wpdb->get_var( $wpdb->prepare(
            "SELECT quantity FROM {$wpdb->prefix}vesho_inventory WHERE id=%d", $item->inventory_id
        ) );
        $new_qty = (float) $item->counted_qty;
        $diff    = $new_qty - $old_qty;

        // Update inventory quantity
        $wpdb->update( $wpdb->prefix . 'vesho_inventory', [ 'quantity' => $new_qty ], [ 'id' => $item->inventory_id ] );

        // Log difference as writeoff/addition if significant
        if ( abs( $diff ) >= 0.001 ) {
            $wpdb->insert( $wpdb->prefix . 'vesho_inventory_writeoffs', [
                'inventory_id' => $item->inventory_id,
                'qty'          => abs( $diff ),
                'type'         => 'writeoff',
                'reason'       => 'Inventuur: ' . $count->name . ' (' . ( $diff > 0 ? '+' : '' ) . number_format( $diff, 3 ) . ')',
                'user_name'    => $user->display_name ?: $user->user_login,
                'created_at'   => current_time( 'mysql' ),
            ] );
        }
    }

    $wpdb->update(
        $wpdb->prefix . 'vesho_stock_counts',
        [ 'status' => 'finalized', 'finalized_at' => current_time( 'mysql' ) ],
        [ 'id' => $sc_id ]
    );

    wp_send_json_success( [ 'applied' => count( $items ) ] );
}

/* ═══════════════════════════════════════════════════════════════════════════
   11. vesho_delete_stock_count
   ═══════════════════════════════════════════════════════════════════════════ */
add_action( 'wp_ajax_vesho_delete_stock_count', 'vesho_ajax_delete_stock_count' );
function vesho_ajax_delete_stock_count() {
    global $wpdb;
    vesho_inv_check_nonce();
    $id = absint( $_POST['stock_count_id'] ?? 0 );
    if ( ! $id ) wp_send_json_error( [ 'message' => 'Vigane ID' ] );
    $wpdb->delete( $wpdb->prefix . 'vesho_stock_count_items',    [ 'stock_count_id' => $id ] );
    $wpdb->delete( $wpdb->prefix . 'vesho_stock_count_sections', [ 'stock_count_id' => $id ] );
    $wpdb->delete( $wpdb->prefix . 'vesho_stock_counts',         [ 'id' => $id ] );
    wp_send_json_success();
}

/* ═══════════════════════════════════════════════════════════════════════════
   12. vesho_csv_preview
   Parses a CSV or XLSX file and returns rows as JSON for preview.

   For XLSX support install PhpSpreadsheet:
       composer require phpoffice/phpspreadsheet
   Without it, only CSV files are parsed.
   ═══════════════════════════════════════════════════════════════════════════ */
add_action( 'wp_ajax_vesho_csv_preview', 'vesho_ajax_csv_preview' );
function vesho_ajax_csv_preview() {
    vesho_inv_check_nonce();

    if ( empty( $_FILES['csv_file']['tmp_name'] ) ) {
        wp_send_json_error( [ 'message' => 'Fail puudub' ] );
    }

    $file     = $_FILES['csv_file'];
    $ext      = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
    $tmp      = $file['tmp_name'];
    $rows     = [];
    $columns  = [ 'name','sku','ean','unit','quantity','purchase_price','sell_price','shop_price','min_quantity','category','location','supplier','description','notes' ];

    if ( $ext === 'csv' || $ext === 'txt' ) {
        // ── CSV parse ──────────────────────────────────────────────────────
        $handle = fopen( $tmp, 'r' );
        if ( ! $handle ) wp_send_json_error( [ 'message' => 'Faili avamine ebaõnnestus' ] );

        // Detect delimiter
        $first_line = fgets( $handle );
        rewind( $handle );
        $delim = ( substr_count( $first_line, ';' ) > substr_count( $first_line, ',' ) ) ? ';' : ',';

        $header = null;
        while ( ( $line = fgetcsv( $handle, 0, $delim ) ) !== false ) {
            if ( $header === null ) {
                $header = array_map( 'trim', array_map( 'strtolower', $line ) );
                // Remove BOM if present
                if ( isset( $header[0] ) ) {
                    $header[0] = preg_replace( '/^\xef\xbb\xbf/', '', $header[0] );
                }
                continue;
            }
            if ( empty( array_filter( $line ) ) ) continue;
            $row = [];
            foreach ( $columns as $col ) {
                $idx = array_search( $col, $header );
                $row[ $col ] = $idx !== false ? trim( $line[ $idx ] ?? '' ) : '';
            }
            if ( ! empty( $row['name'] ) ) {
                $rows[] = $row;
            }
        }
        fclose( $handle );

    } elseif ( in_array( $ext, [ 'xlsx', 'xls' ] ) ) {
        // ── XLSX parse — requires PhpSpreadsheet ───────────────────────────
        $spreadsheet_path = ABSPATH . 'vendor/autoload.php'; // adjust if needed
        if ( ! file_exists( $spreadsheet_path ) ) {
            // Fallback: try plugin vendor path
            $spreadsheet_path = VESHO_CRM_PATH . 'vendor/autoload.php';
        }

        if ( file_exists( $spreadsheet_path ) ) {
            require_once $spreadsheet_path;
            try {
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load( $tmp );
                $sheet       = $spreadsheet->getActiveSheet();
                $data        = $sheet->toArray( null, true, true, false );

                $header = null;
                foreach ( $data as $line ) {
                    if ( $header === null ) {
                        $header = array_map( 'trim', array_map( 'strtolower', array_map( 'strval', $line ) ) );
                        continue;
                    }
                    if ( empty( array_filter( $line ) ) ) continue;
                    $row = [];
                    foreach ( $columns as $col ) {
                        $idx = array_search( $col, $header );
                        $row[ $col ] = $idx !== false ? trim( strval( $line[ $idx ] ?? '' ) ) : '';
                    }
                    if ( ! empty( $row['name'] ) ) {
                        $rows[] = $row;
                    }
                }
            } catch ( \Exception $e ) {
                wp_send_json_error( [ 'message' => 'XLSX viga: ' . $e->getMessage() ] );
            }
        } else {
            wp_send_json_error( [ 'message' => 'XLSX tugi puudub. Installi PhpSpreadsheet: composer require phpoffice/phpspreadsheet' ] );
        }
    } else {
        wp_send_json_error( [ 'message' => 'Tundmatu failitüüp: ' . $ext ] );
    }

    if ( empty( $rows ) ) {
        wp_send_json_error( [ 'message' => 'Failis pole andmeid. Kontrolli päiseridu ja eraldajat (koma/semikoolon).' ] );
    }

    wp_send_json_success( [ 'rows' => array_slice( $rows, 0, 2000 ) ] );
}

/* ═══════════════════════════════════════════════════════════════════════════
   13. vesho_import_inventory  (JSON rows version from preview)
   Accepts rows_json from the preview modal and imports them.
   ═══════════════════════════════════════════════════════════════════════════ */
add_action( 'wp_ajax_vesho_import_inventory_json', 'vesho_ajax_import_inventory_json' );
function vesho_ajax_import_inventory_json() {
    global $wpdb;
    vesho_inv_check_nonce();

    // Support both file upload (legacy) and JSON rows from preview
    if ( ! empty( $_POST['rows_json'] ) ) {
        $rows = json_decode( stripslashes( $_POST['rows_json'] ), true );
        if ( ! is_array( $rows ) ) {
            wp_send_json_error( [ 'message' => 'Vigane andmevorming' ] );
        }
    } elseif ( ! empty( $_FILES['csv_file']['tmp_name'] ) ) {
        // Legacy file upload path — call preview parser and use its result
        $_REQUEST['nonce'] = $_POST['nonce'];
        ob_start();
        vesho_ajax_csv_preview();
        $json = ob_get_clean();
        $d    = json_decode( $json, true );
        if ( empty( $d['success'] ) || empty( $d['data']['rows'] ) ) {
            wp_send_json_error( [ 'message' => 'Faili lugemine ebaõnnestus' ] );
        }
        $rows = $d['data']['rows'];
    } else {
        wp_send_json_error( [ 'message' => 'Andmed puuduvad' ] );
    }

    $table    = $wpdb->prefix . 'vesho_inventory';
    $imported = 0;
    $updated  = 0;

    foreach ( $rows as $row ) {
        if ( empty( $row['name'] ) ) continue;

        $data = [
            'name'           => sanitize_text_field( $row['name'] ),
            'sku'            => sanitize_text_field( $row['sku'] ?? '' ),
            'ean'            => sanitize_text_field( $row['ean'] ?? '' ),
            'unit'           => sanitize_text_field( $row['unit'] ?? 'tk' ),
            'quantity'       => (float) ( $row['quantity'] ?? 0 ),
            'purchase_price' => strlen( $row['purchase_price'] ?? '' ) ? (float) $row['purchase_price'] : null,
            'sell_price'     => strlen( $row['sell_price'] ?? '' )     ? (float) $row['sell_price']     : null,
            'shop_price'     => strlen( $row['shop_price'] ?? '' )     ? (float) $row['shop_price']     : null,
            'min_quantity'   => strlen( $row['min_quantity'] ?? '' )   ? (float) $row['min_quantity']   : null,
            'category'       => sanitize_text_field( $row['category'] ?? '' ),
            'location'       => sanitize_text_field( $row['location'] ?? '' ),
            'supplier'       => sanitize_text_field( $row['supplier'] ?? '' ),
            'description'    => sanitize_textarea_field( $row['description'] ?? '' ),
            'notes'          => sanitize_textarea_field( $row['notes'] ?? '' ),
            'archived'       => 0,
        ];

        // Check by SKU first, then by name
        $existing_id = null;
        if ( ! empty( $data['sku'] ) ) {
            $existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE sku=%s LIMIT 1", $data['sku'] ) );
        }
        if ( ! $existing_id && ! empty( $data['name'] ) ) {
            $existing_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE name=%s LIMIT 1", $data['name'] ) );
        }

        if ( $existing_id ) {
            unset( $data['archived'] );
            $wpdb->update( $table, $data, [ 'id' => $existing_id ] );
            $updated++;
        } else {
            $wpdb->insert( $table, $data );
            $imported++;
        }
    }

    wp_send_json_success( [ 'imported' => $imported, 'updated' => $updated ] );
}

// ── E-shop: Order not received (refused delivery) ────────────────────────────
add_action('wp_ajax_vesho_order_not_received', 'vesho_ajax_order_not_received');
function vesho_ajax_order_not_received() {
    check_ajax_referer('vesho_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    global $wpdb;
    $order_id = absint($_POST['order_id']);
    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vesho_shop_orders WHERE id=%d", $order_id));
    if (!$order) wp_send_json_error('Order not found');

    // Update status to returned
    $wpdb->update($wpdb->prefix.'vesho_shop_orders',
        ['status' => 'returned', 'refund_amount' => $order->total],
        ['id' => $order_id]
    );

    // Try to initiate refund based on payment method
    $refund_result = null;
    if (!empty($order->stripe_payment_id)) {
        // Stripe refund
        $secret = get_option('vesho_stripe_secret_key','');
        $resp = wp_remote_post('https://api.stripe.com/v1/refunds', [
            'headers' => ['Authorization' => 'Basic ' . base64_encode($secret . ':')],
            'body' => ['payment_intent' => $order->stripe_payment_id],
        ]);
        $refund_result = 'stripe';
    } elseif (!empty($order->mc_transaction_id)) {
        // MC refund
        $shop_id    = get_option('vesho_mc_shop_id','');
        $secret_key = get_option('vesho_mc_secret_key','');
        $resp = wp_remote_post("https://payment.maksekeskus.ee/v1/transactions/{$order->mc_transaction_id}/refunds", [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($shop_id . ':' . $secret_key),
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode(['amount' => (float)$order->total, 'comment' => 'Ei võetud vastu']),
        ]);
        $refund_result = 'mc';
    } elseif (!empty($order->montonio_payment_reference)) {
        // Montonio refund
        $ak = get_option('vesho_montonio_access_key','');
        $sk = get_option('vesho_montonio_secret_key','');
        $jwt = vesho_jwt_encode(['iss' => $ak, 'amount' => (float)$order->total, 'currency' => 'EUR', 'iat' => time(), 'exp' => time()+300], $sk);
        $resp = wp_remote_post("https://api.montonio.com/api/v2/orders/{$order->montonio_payment_reference}/refunds", [
            'headers' => ['Authorization' => 'Bearer ' . $jwt, 'Content-Type' => 'application/json'],
            'body' => wp_json_encode(['amount' => (float)$order->total, 'currency' => 'EUR']),
        ]);
        $refund_result = 'montonio';
    }

    // Log activity
    $wpdb->insert($wpdb->prefix.'vesho_activity_log', [
        'user_id'     => get_current_user_id(),
        'action'      => 'order_not_received',
        'description' => "Tellimus #{$order_id} märgiti 'ei võetud vastu', tagasimakse algatatud (" . ($refund_result ?? 'manuaalne') . ")",
        'created_at'  => current_time('mysql'),
    ]);

    wp_send_json_success(['message' => 'Tellimus märgitud tagastatuvaks']);
}

/* ═══════════════════════════════════════════════════════════════════════════
   NOTE: The following handlers already exist in vesho-crm.php — keep them:
   - vesho_writeoff_inventory
   - vesho_stock_count_update
   - vesho_save_inventory        (form POST via admin-post.php)
   - vesho_restore_inventory     (form POST via admin-post.php)
   - vesho_delete_inventory      (form POST via admin-post.php)
   ═══════════════════════════════════════════════════════════════════════════ */

// ── Credit note AJAX ──────────────────────────────────────────────────────────
add_action('wp_ajax_vesho_create_credit_note', 'vesho_ajax_create_credit_note');
function vesho_ajax_create_credit_note() {
    check_ajax_referer('vesho_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');
    global $wpdb;

    $invoice_id = absint($_POST['invoice_id']);
    $amount     = (float)$_POST['amount'];
    $reason     = sanitize_text_field($_POST['reason'] ?? '');

    $invoice = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}vesho_invoices WHERE id=%d", $invoice_id));
    if (!$invoice) wp_send_json_error('Arve ei leitud');

    // Generate credit note number: KARV-YYYY-NNNN
    $year  = date('Y');
    $count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vesho_credit_notes WHERE YEAR(created_at)='$year'");
    $number = 'KARV-' . $year . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

    $wpdb->insert($wpdb->prefix.'vesho_credit_notes', [
        'credit_note_number' => $number,
        'invoice_id'         => $invoice_id,
        'client_id'          => $invoice->client_id,
        'amount'             => $amount,
        'reason'             => $reason,
        'status'             => 'issued',
        'issued_date'        => current_time('Y-m-d'),
    ]);

    // Log
    $wpdb->insert($wpdb->prefix.'vesho_activity_log', [
        'user_id'     => get_current_user_id(),
        'action'      => 'credit_note_created',
        'description' => "Kreeditarve $number loodud arve {$invoice->invoice_number} alusel",
        'created_at'  => current_time('mysql'),
    ]);

    wp_send_json_success(['number' => $number, 'id' => $wpdb->insert_id]);
}

// ── Stock count sections AJAX ─────────────────────────────────────────────────
add_action('wp_ajax_vesho_save_stock_section', 'vesho_ajax_save_stock_section');
function vesho_ajax_save_stock_section() {
    check_ajax_referer('vesho_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    global $wpdb;
    $count_id  = absint($_POST['count_id']);
    $name      = sanitize_text_field($_POST['name']);
    $worker_id = absint($_POST['worker_id']);
    $wpdb->insert($wpdb->prefix.'vesho_stock_count_sections', [
        'stock_count_id' => $count_id,
        'name'           => $name,
        'worker_id'      => $worker_id ?: null,
    ]);
    wp_send_json_success(['id' => $wpdb->insert_id, 'name' => $name]);
}

add_action('wp_ajax_vesho_delete_stock_section', 'vesho_ajax_delete_stock_section');
function vesho_ajax_delete_stock_section() {
    check_ajax_referer('vesho_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    global $wpdb;
    $wpdb->delete($wpdb->prefix.'vesho_stock_count_sections', ['id' => absint($_POST['section_id'])]);
    wp_send_json_success();
}

add_action('wp_ajax_vesho_get_stock_sections', 'vesho_ajax_get_stock_sections');
function vesho_ajax_get_stock_sections() {
    check_ajax_referer('vesho_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();
    global $wpdb;
    $count_id = absint($_POST['count_id']);
    $sections = $wpdb->get_results($wpdb->prepare(
        "SELECT s.*, w.name as worker_name,
                COUNT(sci.id) as item_count
         FROM {$wpdb->prefix}vesho_stock_count_sections s
         LEFT JOIN {$wpdb->prefix}vesho_workers w ON w.id = s.worker_id
         LEFT JOIN {$wpdb->prefix}vesho_stock_count_items sci ON sci.section_id = s.id
         WHERE s.stock_count_id = %d
         GROUP BY s.id
         ORDER BY s.id ASC",
        $count_id
    ));
    wp_send_json_success($sections);
}

// ── AJAX: Shop cart – add item ─────────────────────────────────────────────
add_action('wp_ajax_vesho_cart_add',        'vesho_ajax_cart_add');
add_action('wp_ajax_nopriv_vesho_cart_add', 'vesho_ajax_cart_add');
function vesho_ajax_cart_add() {
    check_ajax_referer('vesho_cart_nonce', 'nonce');
    if (session_status() === PHP_SESSION_NONE) session_start();
    $pid = absint($_POST['pid']);
    $qty = max(1, absint($_POST['qty'] ?? 1));
    if (!isset($_SESSION['vesho_cart'])) $_SESSION['vesho_cart'] = [];
    $_SESSION['vesho_cart'][$pid] = ($_SESSION['vesho_cart'][$pid] ?? 0) + $qty;

    // Lock in active campaign discount at cart-add time so it survives campaign expiry
    if (empty($_SESSION['vesho_cart_campaign'])) {
        global $wpdb;
        $today = date('Y-m-d');
        $cam = $wpdb->get_row(
            "SELECT name, discount_percent, free_shipping, visible_to_guests
             FROM {$wpdb->prefix}vesho_campaigns
             WHERE paused=0
               AND (valid_from IS NULL OR valid_from <= '$today')
               AND (valid_until IS NULL OR valid_until >= '$today')
               AND (target='epood' OR target='both')
             ORDER BY discount_percent DESC LIMIT 1"
        );
        if ($cam) {
            $_SESSION['vesho_cart_campaign'] = [
                'name'             => $cam->name,
                'discount_percent' => (float)$cam->discount_percent,
                'free_shipping'    => (bool)$cam->free_shipping,
                'visible_to_guests'=> (bool)$cam->visible_to_guests,
            ];
        }
    }

    wp_send_json_success(['count' => array_sum($_SESSION['vesho_cart'])]);
}

// ── AJAX: Shop cart – update qty / remove ─────────────────────────────────
add_action('wp_ajax_vesho_cart_update',        'vesho_ajax_cart_update');
add_action('wp_ajax_nopriv_vesho_cart_update', 'vesho_ajax_cart_update');
function vesho_ajax_cart_update() {
    check_ajax_referer('vesho_cart_nonce', 'nonce');
    if (session_status() === PHP_SESSION_NONE) session_start();
    $pid = absint($_POST['pid']);
    $qty = absint($_POST['qty']);
    if ($qty <= 0) unset($_SESSION['vesho_cart'][$pid]);
    else $_SESSION['vesho_cart'][$pid] = $qty;
    wp_send_json_success(['count' => array_sum($_SESSION['vesho_cart'] ?? [])]);
}

// ── AJAX: Shop – place order ───────────────────────────────────────────────
add_action('wp_ajax_vesho_shop_place_order',        'vesho_ajax_shop_place_order');
add_action('wp_ajax_nopriv_vesho_shop_place_order', 'vesho_ajax_shop_place_order');
function vesho_ajax_shop_place_order() {
    check_ajax_referer('vesho_cart_nonce', 'nonce');
    if (session_status() === PHP_SESSION_NONE) session_start();
    global $wpdb;

    // Accept items_json from [vesho_shop] shortcode JS (overrides session cart)
    $items_json = sanitize_text_field($_POST['items_json'] ?? '');
    if ($items_json) {
        $json_items = json_decode(stripslashes($items_json), true);
        if (is_array($json_items) && !empty($json_items)) {
            $_SESSION['vesho_cart'] = [];
            foreach ($json_items as $ji) {
                $pid = absint($ji['pid'] ?? 0);
                $qty = absint($ji['qty'] ?? 0);
                if ($pid && $qty) $_SESSION['vesho_cart'][$pid] = $qty;
            }
        }
    }

    $cart = $_SESSION['vesho_cart'] ?? [];
    if (empty($cart)) wp_send_json_error('Ostukorv on tühi');

    $name     = sanitize_text_field($_POST['name'] ?? '');
    $phone    = sanitize_text_field($_POST['phone'] ?? '');
    $email    = sanitize_email($_POST['email'] ?? '');
    $company  = sanitize_text_field($_POST['company'] ?? '');
    $address  = sanitize_text_field($_POST['address'] ?? '');
    $city     = sanitize_text_field($_POST['city'] ?? '');
    $postcode = sanitize_text_field($_POST['postcode'] ?? '');
    $shipping_method = sanitize_text_field($_POST['shipping_method'] ?? $_POST['shipping_method'] ?? 'pickup');
    $payment_method  = sanitize_text_field($_POST['payment_method'] ?? 'stripe');

    // Resolve logged-in client info from session
    if (is_user_logged_in()) {
        $wp_user = wp_get_current_user();
        $db_client = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_clients WHERE user_id=%d LIMIT 1",
            $wp_user->ID
        ));
        if ($db_client) {
            if (!$name)  $name  = $db_client->name;
            if (!$email) $email = $db_client->email;
            if (!$phone) $phone = $db_client->phone ?? '';
        }
    }

    // Calculate totals
    $subtotal   = 0;
    $items_data = [];
    foreach ($cart as $pid => $qty) {
        $product = $wpdb->get_row($wpdb->prepare(
            "SELECT id, name, shop_price, unit FROM {$wpdb->prefix}vesho_inventory WHERE id=%d AND shop_price>0 AND archived=0",
            absint($pid)
        ));
        if (!$product) continue;
        $line_total   = $product->shop_price * $qty;
        $subtotal    += $line_total;
        $items_data[] = ['product' => $product, 'qty' => $qty, 'orig_price' => $product->shop_price, 'total' => $line_total];
    }
    if (empty($items_data)) wp_send_json_error('Ükski toode pole saadaval');

    // Active campaign discount — use current or session-locked campaign (whichever is better for customer)
    $today    = date('Y-m-d');
    $is_guest = !isset($_SESSION['vesho_client_id']);
    $campaign = $wpdb->get_row(
        "SELECT * FROM {$wpdb->prefix}vesho_campaigns
         WHERE paused=0
           AND (valid_from IS NULL OR valid_from <= '$today')
           AND (valid_until IS NULL OR valid_until >= '$today')
           AND (target='epood' OR target='both')
         ORDER BY discount_percent DESC LIMIT 1"
    );
    $discount_pct  = 0;
    $campaign_name = '';
    $free_shipping = false;
    if ($campaign && (!$is_guest || $campaign->visible_to_guests)) {
        $discount_pct  = (float)$campaign->discount_percent;
        $campaign_name = $campaign->name;
        $free_shipping = (bool)$campaign->free_shipping;
    }
    // Fall back to session-locked campaign if current one is worse or gone
    $sess_cam = $_SESSION['vesho_cart_campaign'] ?? null;
    if ($sess_cam && (!$is_guest || $sess_cam['visible_to_guests'])) {
        if ($sess_cam['discount_percent'] > $discount_pct) {
            $discount_pct  = $sess_cam['discount_percent'];
            $campaign_name = $sess_cam['name'] . ' (lukustatud)';
            $free_shipping = $free_shipping || $sess_cam['free_shipping'];
        }
    }
    $discount_amount = $discount_pct > 0 ? round($subtotal * $discount_pct / 100, 2) : 0;
    $subtotal_after  = round($subtotal - $discount_amount, 2);

    // Shipping costs from settings (Seaded → E-pood)
    $shipping_costs = [
        'pickup'  => (float)get_option('vesho_shop_ship_pickup_price',      '0'),
        'courier' => (float)get_option('vesho_shop_ship_courier_price',     '0'),
        'omniva'  => (float)get_option('vesho_shop_ship_parcelshop_price',  '0'),
        'dpd'     => (float)get_option('vesho_shop_ship_parcelshop_price',  '0'),
    ];
    $shipping_cost = $free_shipping ? 0.00 : ($shipping_costs[$shipping_method] ?? 0.00);
    $total          = $subtotal_after + $shipping_cost;

    // Find existing client by email
    $client_id = null;
    if ($email) {
        $client_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}vesho_clients WHERE email=%s LIMIT 1",
            $email
        ));
    }

    // Generate order number
    $year  = date('Y');
    $count = (int)$wpdb->get_var("SELECT COUNT(*)+1 FROM {$wpdb->prefix}vesho_shop_orders");
    $order_number = 'ORD-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);

    // Create order
    $wpdb->insert($wpdb->prefix . 'vesho_shop_orders', [
        'order_number'     => $order_number,
        'client_id'        => $client_id,
        'client_name'      => $name,
        'client_email'     => $email,
        'client_phone'     => $phone,
        'client_company'   => $company,
        'shipping_address' => "$address, $city $postcode",
        'shipping_method'  => $shipping_method,
        'shipping_price'   => $shipping_cost,
        'subtotal'         => $subtotal,
        'discount_amount'  => $discount_amount,
        'total'            => $total,
        'notes'            => $campaign_name ? "Kampaania: $campaign_name" : '',
        'status'           => 'pending_payment',
        'payment_method'   => $payment_method,
        'created_at'       => current_time('mysql'),
    ]);
    $order_id = $wpdb->insert_id;

    // Insert order items & reserve stock — unit_price is discounted price (like 3006)
    foreach ($items_data as $item) {
        $disc_unit  = $discount_pct > 0 ? round($item['orig_price'] * (1 - $discount_pct / 100), 2) : $item['orig_price'];
        $disc_total = round($disc_unit * $item['qty'], 2);
        $wpdb->insert($wpdb->prefix . 'vesho_shop_order_items', [
            'order_id'     => $order_id,
            'inventory_id' => $item['product']->id,
            'name'         => $item['product']->name,
            'quantity'     => $item['qty'],
            'unit_price'   => $disc_unit,
            'total'        => $disc_total,
        ]);
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}vesho_inventory SET quantity=quantity-%d WHERE id=%d",
            $item['qty'], $item['product']->id
        ));
    }

    $_SESSION['vesho_last_order_id'] = $order_id;
    unset($_SESSION['vesho_cart'], $_SESSION['vesho_cart_campaign']);

    $shop_page = get_page_by_path('pood');
    $shop_url  = $shop_page ? get_permalink($shop_page) : home_url('/pood/');

    // ── Stripe ──────────────────────────────────────────────────────────
    if ($payment_method === 'stripe') {
        $secret = get_option('vesho_stripe_secret_key', '');
        if (!$secret) wp_send_json_error('Stripe pole seadistatud');
        $resp = wp_remote_post('https://api.stripe.com/v1/payment_intents', [
            'headers' => ['Authorization' => 'Basic ' . base64_encode($secret . ':')],
            'body'    => [
                'amount'   => (int)round($total * 100),
                'currency' => 'eur',
                'metadata' => ['order_id' => $order_id, 'order_number' => $order_number],
            ],
        ]);
        $pi = json_decode(wp_remote_retrieve_body($resp), true);
        if (!empty($pi['client_secret'])) {
            $wpdb->update($wpdb->prefix . 'vesho_shop_orders', ['stripe_payment_id' => $pi['id']], ['id' => $order_id]);
            wp_send_json_success(['method' => 'stripe', 'client_secret' => $pi['client_secret'], 'order_id' => $order_id, 'order_number' => $order_number, 'total' => $total, 'discount_amount' => $discount_amount, 'campaign_name' => $campaign_name, 'shipping_price' => $shipping_cost]);
        }
        wp_send_json_error('Stripe viga: ' . ($pi['error']['message'] ?? 'tundmatu'));

    // ── Maksekeskus ─────────────────────────────────────────────────────
    } elseif ($payment_method === 'mc') {
        $shop_id    = get_option('vesho_mc_shop_id', '');
        $secret_key = get_option('vesho_mc_secret_key', '');
        $sandbox    = get_option('vesho_mc_sandbox', '1') === '1';
        if (!$shop_id || !$secret_key) wp_send_json_error('Maksekeskus pole seadistatud');
        $api_url    = $sandbox ? 'https://sandbox-payment.maksekeskus.ee/v1/transactions' : 'https://payment.maksekeskus.ee/v1/transactions';
        $return_url = add_query_arg(['vesho_shop_mc_return' => 1, 'order_id' => $order_id], $shop_url);
        $resp = wp_remote_post($api_url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode($shop_id . ':' . $secret_key),
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode([
                'amount'           => number_format($total, 2, '.', ''),
                'currency'         => 'EUR',
                'reference'        => $order_number,
                'merchant_data'    => 'order:' . $order_id,
                'return_url'       => $return_url,
                'cancel_url'       => add_query_arg(['shop_view' => 'cancel'], $shop_url),
                'notification_url' => rest_url('vesho-crm/v1/mc-notify'),
            ]),
        ]);
        $txn = json_decode(wp_remote_retrieve_body($resp), true);
        if (!empty($txn['payment_link'])) {
            $wpdb->update($wpdb->prefix . 'vesho_shop_orders', ['mc_transaction_id' => $txn['id']], ['id' => $order_id]);
            wp_send_json_success(['method' => 'mc', 'redirect_url' => $txn['payment_link'], 'order_id' => $order_id, 'order_number' => $order_number, 'total' => $total]);
        }
        wp_send_json_error('Maksekeskus viga');

    // ── Montonio ────────────────────────────────────────────────────────
    } elseif ($payment_method === 'montonio') {
        $ak = get_option('vesho_montonio_access_key', '');
        $sk = get_option('vesho_montonio_secret_key', '');
        if (!$ak || !$sk) wp_send_json_error('Montonio pole seadistatud');
        $return_url = add_query_arg(['vesho_shop_montonio_return' => 1, 'order_id' => $order_id], $shop_url);
        $payload = [
            'accessKey'         => $ak,
            'merchantReference' => 'order-' . $order_id,
            'returnUrl'         => $return_url,
            'notificationUrl'   => rest_url('vesho-crm/v1/montonio-webhook'),
            'currency'          => 'EUR',
            'grandTotal'        => (float)$total,
            'lineItems'         => array_map(function($i) {
                return ['name' => $i['product']->name, 'quantity' => $i['qty'], 'finalPrice' => $i['price'], 'currency' => 'EUR'];
            }, $items_data),
            'iat' => time(),
            'exp' => time() + 600,
        ];
        if (!function_exists('vesho_jwt_encode')) {
            wp_send_json_error('JWT helper puudub');
        }
        $jwt     = vesho_jwt_encode($payload, $sk);
        $api_url = get_option('vesho_montonio_sandbox', '1') === '1' ? 'https://sandbox-stargate.montonio.com/api/orders' : 'https://stargate.montonio.com/api/orders';
        $resp    = wp_remote_post($api_url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode(['data' => $jwt]),
        ]);
        $result = json_decode(wp_remote_retrieve_body($resp), true);
        if (!empty($result['paymentUrl'])) {
            $wpdb->update($wpdb->prefix . 'vesho_shop_orders', ['montonio_payment_reference' => $order_number], ['id' => $order_id]);
            wp_send_json_success(['method' => 'montonio', 'redirect_url' => $result['paymentUrl'], 'order_id' => $order_id]);
        }
        wp_send_json_error('Montonio viga');
    }

    wp_send_json_error('Makseviis ei ole seadistatud');
}

// ── AJAX: Shop – confirm Stripe payment (update order status) ─────────────────
add_action('wp_ajax_vesho_shop_stripe_confirm',        'vesho_ajax_shop_stripe_confirm');
add_action('wp_ajax_nopriv_vesho_shop_stripe_confirm', 'vesho_ajax_shop_stripe_confirm');
function vesho_ajax_shop_stripe_confirm() {
    check_ajax_referer('vesho_cart_nonce', 'nonce');
    global $wpdb;
    $order_id = absint($_POST['order_id'] ?? 0);
    if (!$order_id) wp_send_json_error('order_id puudub');

    $order = $wpdb->get_row($wpdb->prepare(
        "SELECT id, stripe_payment_id, total, order_number FROM {$wpdb->prefix}vesho_shop_orders WHERE id=%d LIMIT 1",
        $order_id
    ));
    if (!$order) wp_send_json_error('Tellimust ei leitud');

    // Verify with Stripe
    $secret_key = get_option('vesho_stripe_secret_key', '');
    if ($secret_key && $order->stripe_payment_id) {
        $resp = wp_remote_get('https://api.stripe.com/v1/payment_intents/' . $order->stripe_payment_id, [
            'headers' => ['Authorization' => 'Basic ' . base64_encode($secret_key . ':')],
        ]);
        $pi = json_decode(wp_remote_retrieve_body($resp), true);
        if (($pi['status'] ?? '') !== 'succeeded') {
            wp_send_json_error('Makse pole kinnitatud (staatus: ' . ($pi['status'] ?? 'tundmatu') . ')');
        }
    }

    $wpdb->update($wpdb->prefix . 'vesho_shop_orders', ['status' => 'pending'], ['id' => $order_id]);

    // Email client
    $email_row = $wpdb->get_row($wpdb->prepare(
        "SELECT client_email, client_name FROM {$wpdb->prefix}vesho_shop_orders WHERE id=%d", $order_id
    ));
    if ($email_row && $email_row->client_email) {
        $co = get_option('vesho_company_name', get_bloginfo('name'));
        wp_mail(
            $email_row->client_email,
            "[{$co}] Tellimus #{$order->order_number} kinnitatud",
            "Tere {$email_row->client_name},\n\nTeie tellimus #{$order->order_number} ({$order->total} €) on kinnitatud. Võtame ühendust tarne osas.\n\nAitäh!"
        );
    }

    wp_send_json_success(['order_number' => $order->order_number, 'total' => $order->total]);
}

// ── AJAX: Inventory categories ────────────────────────────────────────────────
add_action('wp_ajax_vesho_save_inv_category',   'vesho_ajax_save_inv_category');
add_action('wp_ajax_vesho_delete_inv_category', 'vesho_ajax_delete_inv_category');

function vesho_ajax_save_inv_category() {
    check_ajax_referer('vesho_crm_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Puudub õigus');
    global $wpdb;

    $id    = absint($_POST['cat_id'] ?? 0);
    $name  = sanitize_text_field($_POST['name'] ?? '');
    $color = sanitize_hex_color($_POST['color'] ?? '#00b4c8') ?: '#00b4c8';
    $sort  = absint($_POST['sort_order'] ?? 0);

    if (!$name) wp_send_json_error('Nimi on kohustuslik');

    $slug = sanitize_title($name);

    if ($id) {
        $wpdb->update(
            $wpdb->prefix . 'vesho_inventory_categories',
            ['name' => $name, 'color' => $color, 'sort_order' => $sort, 'slug' => $slug],
            ['id' => $id]
        );
    } else {
        $wpdb->insert(
            $wpdb->prefix . 'vesho_inventory_categories',
            ['name' => $name, 'color' => $color, 'sort_order' => $sort, 'slug' => $slug]
        );
        $id = $wpdb->insert_id;
    }

    wp_send_json_success(['id' => $id, 'name' => $name, 'color' => $color, 'sort_order' => $sort]);
}

function vesho_ajax_delete_inv_category() {
    check_ajax_referer('vesho_crm_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error('Puudub õigus');
    global $wpdb;

    $id = absint($_POST['cat_id'] ?? 0);
    if (!$id) wp_send_json_error('ID puudub');

    $cat = $wpdb->get_row($wpdb->prepare(
        "SELECT name FROM {$wpdb->prefix}vesho_inventory_categories WHERE id=%d", $id
    ));
    if (!$cat) wp_send_json_error('Ei leitud');

    $wpdb->delete($wpdb->prefix . 'vesho_inventory_categories', ['id' => $id]);
    wp_send_json_success();
}
