<?php
/**
 * Vesho Theme Functions
 *
 * @package Vesho
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

define( 'VESHO_THEME_VERSION', '1.0.0' );
define( 'VESHO_THEME_URI', get_template_directory_uri() );
define( 'VESHO_THEME_DIR', get_template_directory() );

// ── Theme Setup ────────────────────────────────────────────────────────────────
add_action( 'after_setup_theme', 'vesho_theme_setup' );
function vesho_theme_setup() {
    // Make theme available for translation
    load_theme_textdomain( 'vesho', VESHO_THEME_DIR . '/languages' );

    // Title tag support
    add_theme_support( 'title-tag' );

    // Post thumbnails
    add_theme_support( 'post-thumbnails' );
    add_image_size( 'vesho-hero',    1440, 600, true );
    add_image_size( 'vesho-card',    640,  420, true );
    add_image_size( 'vesho-thumb',   320,  220, true );

    // HTML5 support
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ) );

    // Custom logo
    add_theme_support( 'custom-logo', array(
        'height'      => 60,
        'width'       => 200,
        'flex-height' => true,
        'flex-width'  => true,
        'header-text' => array( 'site-title', 'site-description' ),
    ) );

    // Custom header
    add_theme_support( 'custom-header', array(
        'default-image' => '',
        'width'         => 1440,
        'height'        => 600,
        'flex-width'    => true,
        'flex-height'   => true,
    ) );

    // Selective refresh for widgets
    add_theme_support( 'customize-selective-refresh-widgets' );

    // Automatic feed links
    add_theme_support( 'automatic-feed-links' );

    // Wide and full alignment for blocks
    add_theme_support( 'align-wide' );
    add_theme_support( 'responsive-embeds' );

    // Editor styles
    add_theme_support( 'editor-styles' );
    add_editor_style( 'assets/css/editor-style.css' );

    // Nav menus
    register_nav_menus( array(
        'primary'    => __( 'Peamine menüü', 'vesho' ),
        'footer-1'   => __( 'Jaluse menüü 1', 'vesho' ),
        'footer-2'   => __( 'Jaluse menüü 2', 'vesho' ),
    ) );
}

// Fallback nav kui 'primary' asukohale pole menüüd määratud
function vesho_fallback_nav() {
    $pages = array(
        home_url( '/' )          => __( 'Avaleht', 'vesho' ),
        home_url( '/teenused/' ) => __( 'Teenused', 'vesho' ),
        home_url( '/meist/' )    => __( 'Meist', 'vesho' ),
        home_url( '/kontakt/' )  => __( 'Kontakt', 'vesho' ),
    );
    echo '<ul class="nav__list">';
    foreach ( $pages as $url => $label ) {
        $active = ( untrailingslashit( $_SERVER['REQUEST_URI'] ) === parse_url( $url, PHP_URL_PATH ) || ( $url === home_url( '/' ) && is_front_page() ) ) ? ' nav__item--active' : '';
        echo '<li class="nav__item' . $active . '"><a class="nav__link' . ( $active ? ' nav__link--active' : '' ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
    }
    echo '</ul>';
}

// ── Enqueue Scripts & Styles ───────────────────────────────────────────────────
add_action( 'wp_enqueue_scripts', 'vesho_enqueue_assets' );
function vesho_enqueue_assets() {
    // Google Fonts: Barlow + Barlow Condensed
    wp_enqueue_style(
        'vesho-google-fonts',
        'https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Barlow+Condensed:wght@400;600;700;800&display=swap',
        array(),
        null
    );

    // Main theme CSS (design system)
    wp_enqueue_style(
        'vesho-main',
        VESHO_THEME_URI . '/assets/css/vesho.css',
        array( 'vesho-google-fonts' ),
        filemtime( VESHO_THEME_DIR . '/assets/css/vesho.css' )
    );

    // Theme stylesheet (style.css)
    wp_enqueue_style(
        'vesho-style',
        get_stylesheet_uri(),
        array( 'vesho-main' ),
        VESHO_THEME_VERSION
    );

    // Main JS
    wp_enqueue_script(
        'vesho-js',
        VESHO_THEME_URI . '/assets/js/vesho.js',
        array(),
        filemtime( VESHO_THEME_DIR . '/assets/js/vesho.js' ),
        true // Load in footer
    );

    // Pass data to JS
    wp_localize_script( 'vesho-js', 'VeshoData', array(
        'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
        'nonce'     => wp_create_nonce( 'vesho_nonce' ),
        'homeUrl'   => home_url( '/' ),
        'restUrl'   => rest_url( 'vesho/v1/' ),
        'restNonce' => wp_create_nonce( 'wp_rest' ),
    ) );

    // Comment reply script
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }

    // Client portal CSS — laetakse ainult kui lehel on portaali shortcode
    global $post;
    if ( $post && has_shortcode( $post->post_content, 'vesho_client_portal' ) ) {
        wp_enqueue_style(
            'vesho-portal',
            VESHO_THEME_URI . '/assets/css/portal.css',
            array( 'vesho-main' ),
            filemtime( VESHO_THEME_DIR . '/assets/css/portal.css' )
        );
    }

    // Worker portal CSS
    if ( $post && has_shortcode( $post->post_content, 'vesho_worker_portal' ) ) {
        wp_enqueue_style(
            'vesho-worker-portal',
            VESHO_THEME_URI . '/assets/css/worker-portal.css',
            array( 'vesho-main' ),
            filemtime( VESHO_THEME_DIR . '/assets/css/worker-portal.css' )
        );
    }
}

// ── Register Sidebars / Widgets ────────────────────────────────────────────────
add_action( 'widgets_init', 'vesho_register_sidebars' );
function vesho_register_sidebars() {
    $defaults = array(
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    );

    register_sidebar( array(
        'name'          => __( 'Küljeriba', 'vesho' ),
        'id'            => 'sidebar-1',
        'description'   => __( 'Lisa vidinad küljeribale.', 'vesho' ),
    ) + $defaults );

    register_sidebar( array(
        'name'          => __( 'Jalus – Veerg 1', 'vesho' ),
        'id'            => 'footer-1',
        'description'   => __( 'Jaluse esimene veerg.', 'vesho' ),
    ) + $defaults );

    register_sidebar( array(
        'name'          => __( 'Jalus – Veerg 2', 'vesho' ),
        'id'            => 'footer-2',
        'description'   => __( 'Jaluse teine veerg.', 'vesho' ),
    ) + $defaults );

    register_sidebar( array(
        'name'          => __( 'Jalus – Veerg 3', 'vesho' ),
        'id'            => 'footer-3',
        'description'   => __( 'Jaluse kolmas veerg.', 'vesho' ),
    ) + $defaults );
}

// ── Custom Post Types ──────────────────────────────────────────────────────────
add_action( 'init', 'vesho_register_post_types' );
function vesho_register_post_types() {
    // Services CPT
    register_post_type( 'vesho_service', array(
        'labels' => array(
            'name'               => __( 'Teenused', 'vesho' ),
            'singular_name'      => __( 'Teenus', 'vesho' ),
            'add_new'            => __( 'Lisa teenus', 'vesho' ),
            'add_new_item'       => __( 'Lisa uus teenus', 'vesho' ),
            'edit_item'          => __( 'Muuda teenust', 'vesho' ),
            'new_item'           => __( 'Uus teenus', 'vesho' ),
            'view_item'          => __( 'Vaata teenust', 'vesho' ),
            'search_items'       => __( 'Otsi teenuseid', 'vesho' ),
            'not_found'          => __( 'Teenuseid ei leitud', 'vesho' ),
            'not_found_in_trash' => __( 'Prügikastis teenuseid ei ole', 'vesho' ),
        ),
        'public'       => true,
        'has_archive'  => false,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-hammer',
        'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
        'rewrite'      => array( 'slug' => 'teenused' ),
    ) );
}

// ── Custom Taxonomies ──────────────────────────────────────────────────────────
add_action( 'init', 'vesho_register_taxonomies' );
function vesho_register_taxonomies() {
    register_taxonomy( 'service_category', 'vesho_service', array(
        'labels' => array(
            'name'              => __( 'Kategooriad', 'vesho' ),
            'singular_name'     => __( 'Kategooria', 'vesho' ),
            'search_items'      => __( 'Otsi kategooriaid', 'vesho' ),
            'all_items'         => __( 'Kõik kategooriad', 'vesho' ),
            'parent_item'       => __( 'Ülemkategooria', 'vesho' ),
            'parent_item_colon' => __( 'Ülemkategooria:', 'vesho' ),
            'edit_item'         => __( 'Muuda kategooriat', 'vesho' ),
            'update_item'       => __( 'Uuenda kategooriat', 'vesho' ),
            'add_new_item'      => __( 'Lisa uus kategooria', 'vesho' ),
            'new_item_name'     => __( 'Uue kategooria nimi', 'vesho' ),
            'menu_name'         => __( 'Kategooriad', 'vesho' ),
        ),
        'hierarchical' => true,
        'public'        => true,
        'show_in_rest'  => true,
        'rewrite'       => array( 'slug' => 'teenuse-kategooria' ),
    ) );
}

// ── Custom Meta Boxes ──────────────────────────────────────────────────────────
add_action( 'add_meta_boxes', 'vesho_add_meta_boxes' );
function vesho_add_meta_boxes() {
    add_meta_box(
        'vesho_service_details',
        __( 'Teenuse detailid', 'vesho' ),
        'vesho_service_meta_box_callback',
        'vesho_service',
        'normal',
        'high'
    );
}

function vesho_service_meta_box_callback( $post ) {
    wp_nonce_field( 'vesho_service_meta', 'vesho_service_meta_nonce' );
    $price = get_post_meta( $post->ID, '_vesho_price', true );
    $icon  = get_post_meta( $post->ID, '_vesho_icon', true );
    $active = get_post_meta( $post->ID, '_vesho_active', true );
    ?>
    <table class="form-table">
        <tr>
            <th><label for="vesho_price"><?php _e( 'Hind (€)', 'vesho' ); ?></label></th>
            <td><input type="number" step="0.01" id="vesho_price" name="vesho_price" value="<?php echo esc_attr( $price ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="vesho_icon"><?php _e( 'Ikoon (emoji)', 'vesho' ); ?></label></th>
            <td><input type="text" id="vesho_icon" name="vesho_icon" value="<?php echo esc_attr( $icon ); ?>" class="regular-text" placeholder="💧" /></td>
        </tr>
        <tr>
            <th><label><?php _e( 'Aktiivne', 'vesho' ); ?></label></th>
            <td><input type="checkbox" name="vesho_active" value="1" <?php checked( $active, '1' ); ?> /> <?php _e( 'Näita avalikul lehel', 'vesho' ); ?></td>
        </tr>
    </table>
    <?php
}

add_action( 'save_post', 'vesho_save_service_meta' );
function vesho_save_service_meta( $post_id ) {
    if ( ! isset( $_POST['vesho_service_meta_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['vesho_service_meta_nonce'], 'vesho_service_meta' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['vesho_price'] ) ) {
        update_post_meta( $post_id, '_vesho_price', sanitize_text_field( $_POST['vesho_price'] ) );
    }
    if ( isset( $_POST['vesho_icon'] ) ) {
        update_post_meta( $post_id, '_vesho_icon', sanitize_text_field( $_POST['vesho_icon'] ) );
    }
    update_post_meta( $post_id, '_vesho_active', isset( $_POST['vesho_active'] ) ? '1' : '0' );
}

// ── Excerpt length ─────────────────────────────────────────────────────────────
add_filter( 'excerpt_length', function() { return 20; } );
add_filter( 'excerpt_more', function() { return '...'; } );

// ── Template helpers ───────────────────────────────────────────────────────────
/**
 * Get theme services (from CRM plugin or CPT)
 */
function vesho_get_services( $limit = 0 ) {
    global $wpdb;
    $table = $wpdb->prefix . 'vesho_services';

    // Try CRM plugin table first
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table ) {
        $sql = "SELECT * FROM $table WHERE active = 1 ORDER BY id ASC";
        if ( $limit > 0 ) {
            $sql .= $wpdb->prepare( ' LIMIT %d', $limit );
        }
        $services = $wpdb->get_results( $sql );
        if ( ! empty( $services ) ) return $services;
    }

    // Fallback to CPT
    $args = array(
        'post_type'      => 'vesho_service',
        'post_status'    => 'publish',
        'posts_per_page' => $limit > 0 ? $limit : -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    );
    $posts = get_posts( $args );
    $services = array();
    foreach ( $posts as $p ) {
        $services[] = (object) array(
            'id'          => $p->ID,
            'name'        => $p->post_title,
            'description' => get_the_excerpt( $p ),
            'price'       => get_post_meta( $p->ID, '_vesho_price', true ),
            'icon'        => get_post_meta( $p->ID, '_vesho_icon', true ) ?: '💧',
            'active'      => 1,
        );
    }
    return $services;
}

/**
 * Get company settings
 */
function vesho_get_setting( $key, $default = '' ) {
    global $wpdb;
    $table = $wpdb->prefix . 'vesho_settings';
    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table ) {
        $val = $wpdb->get_var( $wpdb->prepare( "SELECT setting_value FROM $table WHERE setting_key = %s LIMIT 1", $key ) );
        if ( $val !== null ) return $val;
    }
    return get_option( 'vesho_' . $key, $default );
}

/**
 * Page banner helper
 */
function vesho_page_banner( $title, $subtitle = '' ) {
    ?>
    <div class="page-banner">
        <div class="container">
            <h1 class="page-banner__title"><?php echo esc_html( $title ); ?></h1>
            <?php if ( $subtitle ) : ?>
                <p class="page-banner__sub"><?php echo esc_html( $subtitle ); ?></p>
            <?php endif; ?>
            <nav class="breadcrumb">
                <a href="<?php echo home_url( '/' ); ?>"><?php _e( 'Avaleht', 'vesho' ); ?></a>
                <span class="breadcrumb__sep">›</span>
                <span class="breadcrumb__current"><?php echo esc_html( $title ); ?></span>
            </nav>
        </div>
    </div>
    <?php
}

// ── AJAX: Guest service request ────────────────────────────────────────────────
add_action( 'wp_ajax_nopriv_vesho_guest_request', 'vesho_ajax_guest_request' );
add_action( 'wp_ajax_vesho_guest_request', 'vesho_ajax_guest_request' );
function vesho_ajax_guest_request() {
    check_ajax_referer( 'vesho_nonce', 'nonce' );

    $data = array(
        'name'           => sanitize_text_field( $_POST['name'] ?? '' ),
        'email'          => sanitize_email( $_POST['email'] ?? '' ),
        'phone'          => sanitize_text_field( $_POST['phone'] ?? '' ),
        'device_name'    => sanitize_text_field( $_POST['device_name'] ?? '' ),
        'service_type'   => sanitize_text_field( $_POST['service_type'] ?? '' ),
        'preferred_date' => sanitize_text_field( $_POST['preferred_date'] ?? '' ),
        'description'    => sanitize_textarea_field( $_POST['description'] ?? '' ),
        'created_at'     => current_time( 'mysql' ),
    );

    if ( empty( $data['name'] ) || empty( $data['email'] ) ) {
        wp_send_json_error( array( 'message' => 'Nimi ja e-post on kohustuslikud' ) );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'vesho_guest_requests';

    if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table ) {
        $result = $wpdb->insert( $table, $data );
        if ( $result === false ) {
            wp_send_json_error( array( 'message' => 'Andmebaasi viga' ) );
        }
    }

    // Send admin notification email
    $admin_email = vesho_get_setting( 'company_email', get_option( 'admin_email' ) );
    $subject = sprintf( __( 'Uus teenusepäring: %s', 'vesho' ), $data['name'] );
    $message  = "Uus teenusepäring on laekunud:\n\n";
    $message .= "Nimi: {$data['name']}\n";
    $message .= "E-post: {$data['email']}\n";
    $message .= "Telefon: {$data['phone']}\n";
    $message .= "Seade: {$data['device_name']}\n";
    $message .= "Teenus: {$data['service_type']}\n";
    $message .= "Soovitud kuupäev: {$data['preferred_date']}\n";
    $message .= "Kirjeldus: {$data['description']}\n";
    wp_mail( $admin_email, $subject, $message );

    wp_send_json_success( array( 'message' => 'Päring edukalt saadetud' ) );
}

// ── AJAX: Contact form ─────────────────────────────────────────────────────────
add_action( 'wp_ajax_nopriv_vesho_contact', 'vesho_ajax_contact' );
add_action( 'wp_ajax_vesho_contact', 'vesho_ajax_contact' );
function vesho_ajax_contact() {
    check_ajax_referer( 'vesho_nonce', 'nonce' );

    $name    = sanitize_text_field( $_POST['name'] ?? '' );
    $email   = sanitize_email( $_POST['email'] ?? '' );
    $phone   = sanitize_text_field( $_POST['phone'] ?? '' );
    $message = sanitize_textarea_field( $_POST['message'] ?? '' );

    if ( empty( $name ) || empty( $email ) || empty( $message ) ) {
        wp_send_json_error( array( 'message' => 'Nimi, e-post ja sõnum on kohustuslikud' ) );
    }

    $admin_email = vesho_get_setting( 'company_email', get_option( 'admin_email' ) );
    $subject     = sprintf( 'Uus kontaktisoov: %s', $name );
    $body        = "Nimi: $name\nE-post: $email\nTelefon: $phone\n\n$message";
    $headers     = array( "Reply-To: $name <$email>", 'Content-Type: text/plain; charset=UTF-8' );

    $sent = wp_mail( $admin_email, $subject, $body, $headers );
    if ( $sent ) {
        wp_send_json_success( array( 'message' => 'Sõnum edukalt saadetud' ) );
    } else {
        wp_send_json_error( array( 'message' => 'Saatmine ebaõnnestus' ) );
    }
}

// ── Customizer options ─────────────────────────────────────────────────────────
add_action( 'customize_register', 'vesho_customizer_register' );
function vesho_customizer_register( $wp_customize ) {

    // ── Panel: Vesho ──────────────────────────────────────────────────────────
    $wp_customize->add_panel( 'vesho_panel', array(
        'title'    => __( 'Vesho seaded', 'vesho' ),
        'priority' => 30,
    ) );

    // ── Section: Bränding ─────────────────────────────────────────────────────
    $wp_customize->add_section( 'vesho_branding', array(
        'title'    => __( 'Bränding', 'vesho' ),
        'panel'    => 'vesho_panel',
        'priority' => 10,
    ) );

    // Brand primary color
    $wp_customize->add_setting( 'vesho_brand_color', array(
        'default'           => '#00b4c8',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vesho_brand_color', array(
        'label'   => __( 'Põhivärv (tsüaan)', 'vesho' ),
        'section' => 'vesho_branding',
    ) ) );

    // Brand dark color
    $wp_customize->add_setting( 'vesho_brand_dark', array(
        'default'           => '#0d1f2d',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'vesho_brand_dark', array(
        'label'   => __( 'Tume taustavärv (navy)', 'vesho' ),
        'section' => 'vesho_branding',
    ) ) );

    // Company name (for portals / emails)
    $wp_customize->add_setting( 'vesho_company_display_name', array(
        'default'           => 'VESHO',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vesho_company_display_name', array(
        'label'       => __( 'Ettevõtte kuvatav nimi', 'vesho' ),
        'description' => __( 'Kuvatakse portaalide logos ja meilides.', 'vesho' ),
        'section'     => 'vesho_branding',
        'type'        => 'text',
    ) );

    // ── Section: Avaleht ──────────────────────────────────────────────────────
    $wp_customize->add_section( 'vesho_homepage', array(
        'title'    => __( 'Avaleht', 'vesho' ),
        'panel'    => 'vesho_panel',
        'priority' => 20,
    ) );

    $wp_customize->add_setting( 'vesho_hero_headline', array(
        'default'           => 'VESI. PUHAS. USALDUSVÄÄRNE.',
        'sanitize_callback' => 'sanitize_text_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'vesho_hero_headline', array(
        'label'   => __( 'Hero pealkiri', 'vesho' ),
        'section' => 'vesho_homepage',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vesho_hero_sub', array(
        'default'           => 'Professionaalsed veesüsteemide lahendused kodudele ja ettevõtetele üle Eesti.',
        'sanitize_callback' => 'sanitize_textarea_field',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'vesho_hero_sub', array(
        'label'   => __( 'Hero alampealkiri', 'vesho' ),
        'section' => 'vesho_homepage',
        'type'    => 'textarea',
    ) );

    $wp_customize->add_setting( 'vesho_hero_badge', array(
        'default'           => 'Premium veelahendused',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vesho_hero_badge', array(
        'label'   => __( 'Hero badge tekst', 'vesho' ),
        'section' => 'vesho_homepage',
        'type'    => 'text',
    ) );

    // Stats
    foreach ( array(
        array( 'vesho_stat1_num',   '500+', 'Stat 1 number' ),
        array( 'vesho_stat1_label', 'Rahulolev klient', 'Stat 1 silt' ),
        array( 'vesho_stat2_num',   '10+',  'Stat 2 number' ),
        array( 'vesho_stat2_label', 'Aastat kogemust',  'Stat 2 silt' ),
        array( 'vesho_stat3_num',   '24h',  'Stat 3 number' ),
        array( 'vesho_stat3_label', 'Reaktsiooniaeg',   'Stat 3 silt' ),
        array( 'vesho_stat4_num',   '99%',  'Stat 4 number' ),
        array( 'vesho_stat4_label', 'Rahulolu',         'Stat 4 silt' ),
    ) as $s ) {
        $wp_customize->add_setting( $s[0], array( 'default' => $s[1], 'sanitize_callback' => 'sanitize_text_field' ) );
        $wp_customize->add_control( $s[0], array( 'label' => __( $s[2], 'vesho' ), 'section' => 'vesho_homepage', 'type' => 'text' ) );
    }

    // Why us section
    foreach ( array(
        array( 'vesho_why_label', 'Miks valida Vesho?',  'Miks valida – silt' ),
        array( 'vesho_why_title', 'Usaldusväärne partner veesüsteemide hoolduses', 'Miks valida – pealkiri' ),
        array( 'vesho_why_desc',  'Üle 10 aasta kogemusega meeskond tagab teie veesüsteemide optimaalse töö. Pakume kiireid lahendusi, läbipaistvat hinnastamist ja püsivat kvaliteeti.', 'Miks valida – kirjeldus' ),
        array( 'vesho_why_item1', 'Sertifitseeritud ja kogenud tehnikud',              'Miks valida – punkt 1' ),
        array( 'vesho_why_item2', 'Läbipaistev hinnastamine ilma peidetud tasudeta',   'Miks valida – punkt 2' ),
        array( 'vesho_why_item3', '24-tunnine reageering hädaolukordades',             'Miks valida – punkt 3' ),
        array( 'vesho_why_item4', 'Garantii kõikidele töödele ja materjalidele',       'Miks valida – punkt 4' ),
        array( 'vesho_why_item5', 'Üle 500 rahuloleva kliendi üle Eesti',             'Miks valida – punkt 5' ),
        array( 'vesho_why_badge1_title', 'Parim teenindus 2024',   'Märk 1 pealkiri' ),
        array( 'vesho_why_badge1_sub',   'Eesti veemajanduse liit','Märk 1 alapealkiri' ),
        array( 'vesho_why_badge2_title', '4.9 / 5.0',              'Märk 2 pealkiri' ),
        array( 'vesho_why_badge2_sub',   'Klientide hinnang',      'Märk 2 alapealkiri' ),
        array( 'vesho_why_badge3_title', '2 aasta garantii',       'Märk 3 pealkiri' ),
        array( 'vesho_why_badge3_sub',   'Kõikidele töödele',      'Märk 3 alapealkiri' ),
    ) as $w ) {
        $wp_customize->add_setting( $w[0], array( 'default' => $w[1], 'sanitize_callback' => 'sanitize_text_field' ) );
        $wp_customize->add_control( $w[0], array( 'label' => __( $w[2], 'vesho' ), 'section' => 'vesho_homepage', 'type' => 'text' ) );
    }

    // CTA section
    $wp_customize->add_setting( 'vesho_cta_title', array(
        'default'           => 'Valmis alustama?',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vesho_cta_title', array(
        'label'   => __( 'CTA pealkiri', 'vesho' ),
        'section' => 'vesho_homepage',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vesho_cta_sub', array(
        'default'           => 'Võtke meiega ühendust ja saage tasuta konsultatsioon juba täna.',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vesho_cta_sub', array(
        'label'   => __( 'CTA alamtekst', 'vesho' ),
        'section' => 'vesho_homepage',
        'type'    => 'text',
    ) );

    // ── Section: Kontakt ──────────────────────────────────────────────────────
    $wp_customize->add_section( 'vesho_contact', array(
        'title'    => __( 'Kontaktandmed', 'vesho' ),
        'panel'    => 'vesho_panel',
        'priority' => 30,
    ) );

    foreach ( array(
        'vesho_phone'   => array( 'Telefon',  '+372 5XXX XXXX', 'text',  'sanitize_text_field' ),
        'vesho_email'   => array( 'E-post',   'info@vesho.ee',  'email', 'sanitize_email' ),
        'vesho_address' => array( 'Aadress',  'Tallinn, Eesti', 'text',  'sanitize_text_field' ),
    ) as $key => $cfg ) {
        $wp_customize->add_setting( $key, array( 'default' => $cfg[1], 'sanitize_callback' => $cfg[3] ) );
        $wp_customize->add_control( $key, array( 'label' => __( $cfg[0], 'vesho' ), 'section' => 'vesho_contact', 'type' => $cfg[2] ) );
    }

    // ── Section: Sotsiaalmeedia ───────────────────────────────────────────────
    $wp_customize->add_section( 'vesho_social', array(
        'title'    => __( 'Sotsiaalmeedia', 'vesho' ),
        'panel'    => 'vesho_panel',
        'priority' => 40,
    ) );

    foreach ( array(
        'vesho_facebook'  => 'Facebook URL',
        'vesho_instagram' => 'Instagram URL',
        'vesho_linkedin'  => 'LinkedIn URL',
        'vesho_youtube'   => 'YouTube URL',
    ) as $key => $label ) {
        $wp_customize->add_setting( $key, array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
        $wp_customize->add_control( $key, array( 'label' => $label, 'section' => 'vesho_social', 'type' => 'url' ) );
    }

    // ── Section: Portaalid ────────────────────────────────────────────────────
    $wp_customize->add_section( 'vesho_portals', array(
        'title'    => __( 'Portaalide tekstid', 'vesho' ),
        'panel'    => 'vesho_panel',
        'priority' => 50,
    ) );

    $wp_customize->add_setting( 'vesho_client_portal_title', array(
        'default'           => 'Klientide Portaal',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vesho_client_portal_title', array(
        'label'       => __( 'Kliendi portaali pealkiri', 'vesho' ),
        'description' => __( 'Kuvatakse sisselogimiskaardil.', 'vesho' ),
        'section'     => 'vesho_portals',
        'type'        => 'text',
    ) );

    $wp_customize->add_setting( 'vesho_worker_portal_title', array(
        'default'           => 'Töötaja Portaal',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vesho_worker_portal_title', array(
        'label'   => __( 'Töötaja portaali pealkiri', 'vesho' ),
        'section' => 'vesho_portals',
        'type'    => 'text',
    ) );

    // ── Section: Jalus ────────────────────────────────────────────────────────
    $wp_customize->add_section( 'vesho_footer', array(
        'title'    => __( 'Jalus', 'vesho' ),
        'panel'    => 'vesho_panel',
        'priority' => 60,
    ) );

    $wp_customize->add_setting( 'vesho_footer_tagline', array(
        'default'           => 'Professionaalsed veesüsteemide lahendused.',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vesho_footer_tagline', array(
        'label'   => __( 'Jaluse lühikirjeldus', 'vesho' ),
        'section' => 'vesho_footer',
        'type'    => 'text',
    ) );

    $wp_customize->add_setting( 'vesho_footer_copyright', array(
        'default'           => '© ' . date('Y') . ' Vesho OÜ. Kõik õigused kaitstud.',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'vesho_footer_copyright', array(
        'label'   => __( 'Copyright tekst', 'vesho' ),
        'section' => 'vesho_footer',
        'type'    => 'text',
    ) );
}

// ── Dünaamilised CSS variableid Customizerist ──────────────────────────────────
add_action( 'wp_head', 'vesho_output_css_variables', 5 );
function vesho_output_css_variables() {
    $primary      = sanitize_hex_color( get_theme_mod( 'vesho_brand_color', '#00b4c8' ) );
    $dark         = sanitize_hex_color( get_theme_mod( 'vesho_brand_dark',  '#0d1f2d' ) );
    // Generate hover color (darken by ~15%)
    $primary_hover = vesho_darken_color( $primary, 15 );
    echo "<style id=\"vesho-brand-vars\">:root{" .
         "--vesho-primary:{$primary};" .
         "--vesho-primary-hover:{$primary_hover};" .
         "--vesho-dark:{$dark};" .
         "--cyan:{$primary};" .
         "--navy:{$dark};" .
         "--navy-deep:" . vesho_darken_color($dark, 10) . ";" .
         "}</style>\n";
}

// Helper: darken a hex color by a percentage
function vesho_darken_color( $hex, $percent ) {
    $hex = ltrim( $hex, '#' );
    if ( strlen($hex) === 3 ) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    $r = max( 0, (int)(hexdec(substr($hex,0,2)) * (1 - $percent/100)) );
    $g = max( 0, (int)(hexdec(substr($hex,2,2)) * (1 - $percent/100)) );
    $b = max( 0, (int)(hexdec(substr($hex,4,2)) * (1 - $percent/100)) );
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

// Customizer live preview (postMessage transport)
add_action( 'customize_preview_init', 'vesho_customizer_live_preview' );
function vesho_customizer_live_preview() {
    wp_add_inline_script( 'customize-preview', "
    (function($){
        wp.customize('vesho_brand_color', function(v){
            v.bind(function(c){
                document.getElementById('vesho-brand-vars') && (function(){
                    var s = document.getElementById('vesho-brand-vars');
                    s.textContent = s.textContent.replace(/--vesho-primary:[^;]+/, '--vesho-primary:'+c)
                                                 .replace(/--cyan:[^;]+/, '--cyan:'+c);
                })();
            });
        });
        wp.customize('vesho_hero_headline', function(v){
            v.bind(function(t){ $('.hero__title').text(t); });
        });
        wp.customize('vesho_hero_sub', function(v){
            v.bind(function(t){ $('.hero__sub').text(t); });
        });
    })(jQuery);
    " );
}

// ── Head – preconnect for Google Fonts ────────────────────────────────────────
add_action( 'wp_head', 'vesho_preconnect', 1 );
function vesho_preconnect() {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}

// ── Hide admin bar for clients & workers (not admins) ─────────────────────────
add_filter( 'show_admin_bar', 'vesho_hide_admin_bar_for_users' );
function vesho_hide_admin_bar_for_users( $show ) {
    if ( ! is_user_logged_in() ) return false;
    $user = wp_get_current_user();
    // Only show admin bar to users who can access wp-admin
    if ( current_user_can( 'manage_options' ) || current_user_can( 'edit_posts' ) ) {
        return $show;
    }
    return false;
}

// ── Fix modals when admin bar is visible (push fixed elements down) ───────────
add_action( 'wp_head', 'vesho_admin_bar_modal_fix', 99 );
function vesho_admin_bar_modal_fix() {
    if ( ! is_admin_bar_showing() ) return;
    ?>
    <style id="vesho-adminbar-fix">
        /* Sticky header must sit below admin bar */
        body.admin-bar .site-header { top: 32px !important; }

        /* Modal backdrops must cover the full remaining viewport */
        body.admin-bar .modal-backdrop,
        body.admin-bar #modal-backdrop,
        body.admin-bar #login-modal-backdrop {
            top: 32px !important;
            height: calc(100vh - 32px) !important;
        }
        /* Modals (right-side drawers): push down so admin bar doesn't overlap */
        body.admin-bar .modal,
        body.admin-bar #service-modal,
        body.admin-bar #login-modal {
            top: 32px !important;
        }

        @media screen and (max-width: 782px) {
            body.admin-bar .site-header { top: 46px !important; }
            body.admin-bar .modal-backdrop,
            body.admin-bar #modal-backdrop,
            body.admin-bar #login-modal-backdrop {
                top: 46px !important;
                height: calc(100vh - 46px) !important;
            }
            body.admin-bar .modal,
            body.admin-bar #service-modal,
            body.admin-bar #login-modal {
                top: 46px !important;
            }
        }
    </style>
    <?php
}

// ── Body classes ───────────────────────────────────────────────────────────────
add_filter( 'body_class', 'vesho_body_classes' );
function vesho_body_classes( $classes ) {
    if ( is_singular() ) $classes[] = 'singular';
    if ( is_front_page() ) $classes[] = 'is-front-page';
    return $classes;
}

// ── Nav walker for primary menu ────────────────────────────────────────────────
class Vesho_Nav_Walker extends Walker_Nav_Menu {
    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $classes   = empty( $item->classes ) ? array() : (array) $item->classes;
        $classes[] = 'nav__item';
        if ( in_array( 'current-menu-item', $classes ) || in_array( 'current-menu-parent', $classes ) ) {
            $classes[] = 'nav__item--active';
        }

        $class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
        $output .= '<li class="' . esc_attr( $class_names ) . '">';

        $atts           = array();
        $atts['href']   = ! empty( $item->url ) ? $item->url : '#';
        $atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
        $atts['target'] = ! empty( $item->target ) ? $item->target : '';
        $atts['rel']    = ! empty( $item->xfn ) ? $item->xfn : '';
        $atts['class']  = 'nav__link';
        if ( in_array( 'nav__item--active', $classes ) ) {
            $atts['class'] .= ' nav__link--active';
            $atts['aria-current'] = 'page';
        }

        $atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

        $attributes = '';
        foreach ( $atts as $attr => $value ) {
            if ( ! empty( $value ) ) {
                $attributes .= ' ' . esc_attr( $attr ) . '="' . esc_attr( $value ) . '"';
            }
        }

        $output .= '<a' . $attributes . '>' . apply_filters( 'the_title', $item->title, $item->ID ) . '</a>';
    }
}
