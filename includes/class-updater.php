<?php defined('ABSPATH') || exit;

/**
 * GitHub-based update system for Vesho CRM plugin and Vesho theme.
 *
 * How it works:
 * 1. This class hooks into WordPress update transients
 * 2. It checks plugin-info.json / theme-info.json from GitHub (raw.githubusercontent.com)
 * 3. When a newer version is found, WordPress shows "Update available" on Töölaud → Uuendused
 * 4. Admin clicks Update → WP downloads ZIP from GitHub Release → installs it (no FTP needed)
 *
 * JSON files live in /releases/ folder in the GitHub repo and are auto-updated by GitHub Actions.
 */
class Vesho_CRM_Updater {

    const PLUGIN_FILE  = 'vesho-crm/vesho-crm.php';
    const PLUGIN_SLUG  = 'vesho-crm';
    const THEME_SLUG   = 'vesho';
    const GITHUB_RAW   = 'https://raw.githubusercontent.com/KyamiSolutions/vesho.ee/main/releases';

    public static function init() {
        // Hook into WordPress update checks
        add_filter( 'pre_set_site_transient_update_plugins', [ __CLASS__, 'check_plugin_update' ] );
        add_filter( 'pre_set_site_transient_update_themes',  [ __CLASS__, 'check_theme_update' ] );
        // Show plugin info in the "View version X details" popup
        add_filter( 'plugins_api', [ __CLASS__, 'plugin_info' ], 20, 3 );
        // Clear our cache after update completes
        add_action( 'upgrader_process_complete', [ __CLASS__, 'after_update' ], 10, 2 );
        // WP 6.3+ rollback workaround: delete theme dir BEFORE move_to_rollback_cache() runs.
        // admin_init fires before ANY upgrader code, so this is the safest hook.
        add_action( 'admin_init', [ __CLASS__, 'maybe_pre_delete_theme' ] );
        // Also hook upgrader_pre_install as belt-and-suspenders
        add_filter( 'upgrader_pre_install', [ __CLASS__, 'pre_theme_install' ], 10, 2 );
        // Admin AJAX: create release package
        add_action( 'wp_ajax_vesho_create_release', [ __CLASS__, 'ajax_create_release' ] );
        // Admin AJAX: force-check for updates now
        add_action( 'wp_ajax_vesho_force_update_check', [ __CLASS__, 'ajax_force_check' ] );
        // Admin AJAX: import starter content
        add_action( 'wp_ajax_vesho_import_starter_content', [ __CLASS__, 'ajax_import_starter_content' ] );
        // Admin AJAX: direct theme installer (bypasses WP backup/rollback)
        add_action( 'wp_ajax_vesho_install_theme', [ __CLASS__, 'ajax_install_theme' ] );
        // Admin AJAX: direct plugin installer (bypasses WP_Upgrader)
        add_action( 'wp_ajax_vesho_install_plugin', [ __CLASS__, 'ajax_install_plugin' ] );
    }

    /**
     * Fires on admin_init — BEFORE WP_Upgrader::run() calls move_to_rollback_cache().
     *
     * Single update:  wp-admin/update.php?action=upgrade-theme&theme=vesho
     * Bulk update:    wp-admin/update-core.php?action=do-theme-upgrade  (POST checked[]=vesho)
     * Note: "action" is always in $_GET (query string), NOT in $_POST.
     */
    public static function maybe_pre_delete_theme() {
        $action = isset( $_GET['action'] ) ? $_GET['action'] : '';

        // Single theme update via update.php
        $is_single = (
            $action === 'upgrade-theme'
            && isset( $_GET['theme'] )
            && sanitize_key( $_GET['theme'] ) === self::THEME_SLUG
        );

        // Bulk theme update via update-core.php (action in URL, themes in POST body)
        $is_bulk = (
            $action === 'do-theme-upgrade'
            && isset( $_POST['checked'] )
            && in_array( self::THEME_SLUG, (array) $_POST['checked'], true )
        );

        if ( $is_single || $is_bulk ) {
            self::delete_theme_dir_fs();
        }
    }

    /**
     * Delete theme directory using WP Filesystem abstraction
     * (handles Hostinger file ownership correctly).
     */
    private static function delete_theme_dir_fs() {
        global $wp_filesystem;

        $theme_dir = get_theme_root() . '/' . self::THEME_SLUG;

        // Init WP Filesystem if not already done
        if ( empty( $wp_filesystem ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        if ( ! empty( $wp_filesystem ) && $wp_filesystem->is_dir( $theme_dir ) ) {
            $wp_filesystem->delete( $theme_dir, true );
            return;
        }

        // Fallback: native PHP delete
        if ( is_dir( $theme_dir ) ) {
            self::recursive_rmdir( $theme_dir );
        }
    }

    private static function recursive_rmdir( $dir ) {
        $items = @scandir( $dir );
        if ( ! $items ) return;
        foreach ( $items as $item ) {
            if ( $item === '.' || $item === '..' ) continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir( $path ) ? self::recursive_rmdir( $path ) : @unlink( $path );
        }
        @rmdir( $dir );
    }

    public static function pre_theme_install( $return, $hook_extra ) {
        if ( ! isset( $hook_extra['theme'] ) || $hook_extra['theme'] !== self::THEME_SLUG ) {
            return $return;
        }
        self::delete_theme_dir_fs();
        return $return;
    }

    // ── Update server URL ─────────────────────────────────────────────────────

    public static function get_server_url() {
        $url = get_option( 'vesho_update_server_url', '' );
        if ( $url ) return rtrim( $url, '/' );
        // Default: GitHub repo /releases/ kaust (public repo, ei vaja autentimist)
        return self::GITHUB_RAW;
    }

    // ── Fetch remote info (cached 6h) ─────────────────────────────────────────

    private static function fetch_remote_info( $type ) {
        $cache_key = 'vesho_remote_' . $type . '_info';
        $cached    = get_transient( $cache_key );
        if ( $cached !== false ) return $cached;

        $url      = self::get_server_url() . '/' . $type . '-info.json?nocache=' . time();
        $response = wp_remote_get( $url, [
            'timeout'   => 10,
            'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
            'headers'   => [ 'Cache-Control' => 'no-cache', 'Pragma' => 'no-cache' ],
        ] );

        if ( is_wp_error( $response ) ) return null;
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body );
        if ( $data && ! empty( $data->version ) ) {
            set_transient( $cache_key, $data, 6 * HOUR_IN_SECONDS );
        }
        return $data ?: null;
    }

    // ── Plugin update check ───────────────────────────────────────────────────

    public static function check_plugin_update( $transient ) {
        if ( empty( $transient->checked ) ) return $transient;

        $info = self::fetch_remote_info( 'plugin' );
        if ( ! $info ) return $transient;

        $current = VESHO_CRM_VERSION;

        if ( version_compare( $info->version, $current, '>' ) ) {
            $transient->response[ self::PLUGIN_FILE ] = (object) [
                'id'          => 'vesho-crm/vesho-crm',
                'slug'        => self::PLUGIN_SLUG,
                'plugin'      => self::PLUGIN_FILE,
                'new_version' => $info->version,
                'url'         => $info->url ?? home_url(),
                'package'     => $info->download_url,
                'icons'       => [],
                'banners'     => [],
                'tested'      => $info->tested ?? '6.9',
                'requires_php'=> $info->requires_php ?? '7.4',
            ];
        } else {
            $transient->no_update[ self::PLUGIN_FILE ] = (object) [
                'id'          => 'vesho-crm/vesho-crm',
                'slug'        => self::PLUGIN_SLUG,
                'plugin'      => self::PLUGIN_FILE,
                'new_version' => $current,
                'url'         => '',
                'package'     => '',
            ];
        }

        return $transient;
    }

    // ── Theme update check ────────────────────────────────────────────────────

    public static function check_theme_update( $transient ) {
        if ( empty( $transient->checked ) ) return $transient;

        $info = self::fetch_remote_info( 'theme' );
        if ( ! $info ) return $transient;

        $theme   = wp_get_theme( self::THEME_SLUG );
        $current = $theme->get( 'Version' );

        if ( version_compare( $info->version, $current, '>' ) ) {
            $transient->response[ self::THEME_SLUG ] = [
                'theme'       => self::THEME_SLUG,
                'new_version' => $info->version,
                'url'         => $info->url ?? home_url(),
                'package'     => $info->download_url,
                'requires'    => $info->requires ?? '6.0',
                'requires_php'=> $info->requires_php ?? '7.4',
            ];
        }

        return $transient;
    }

    // ── Plugin info popup ─────────────────────────────────────────────────────

    public static function plugin_info( $result, $action, $args ) {
        if ( $action !== 'plugin_information' ) return $result;
        if ( ( $args->slug ?? '' ) !== self::PLUGIN_SLUG ) return $result;

        $info = self::fetch_remote_info( 'plugin' );
        if ( ! $info ) return $result;

        return (object) [
            'name'          => 'Vesho CRM',
            'slug'          => self::PLUGIN_SLUG,
            'version'       => $info->version,
            'author'        => '<a href="' . home_url() . '">Vesho</a>',
            'requires'      => $info->requires      ?? '6.0',
            'tested'        => $info->tested         ?? '6.9',
            'requires_php'  => $info->requires_php   ?? '7.4',
            'last_updated'  => $info->last_updated   ?? date( 'Y-m-d' ),
            'download_link' => $info->download_url,
            'sections'      => [
                'description' => '<p>Vesho CRM — klientide, hoolduste, töökäskude, inventuuri ja e-poe haldus WordPressis.</p>',
                'changelog'   => $info->changelog ?? '<p>Vaata versioonimuudatusi admin paneelist.</p>',
            ],
        ];
    }

    // ── After update: clear cache ─────────────────────────────────────────────

    public static function after_update( $upgrader, $options ) {
        if ( $options['type'] === 'plugin' && in_array( self::PLUGIN_FILE, (array) ( $options['plugins'] ?? [] ) ) ) {
            delete_transient( 'vesho_remote_plugin_info' );
        }
        if ( $options['type'] === 'theme' && in_array( self::THEME_SLUG, (array) ( $options['themes'] ?? [] ) ) ) {
            delete_transient( 'vesho_remote_theme_info' );
        }
    }

    // ── AJAX: force update check ──────────────────────────────────────────────

    public static function ajax_force_check() {
        check_ajax_referer( 'vesho_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error();
        delete_transient( 'vesho_remote_plugin_info' );
        delete_transient( 'vesho_remote_theme_info' );
        delete_site_transient( 'update_plugins' );
        delete_site_transient( 'update_themes' );
        wp_send_json_success( [ 'message' => 'Cache tühjendatud. WordPress kontrollib uuesti.' ] );
    }

    // ── AJAX: create release package ──────────────────────────────────────────

    public static function ajax_create_release() {
        check_ajax_referer( 'vesho_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

        $type      = sanitize_text_field( $_POST['type'] ?? '' );   // 'plugin' or 'theme'
        $version   = sanitize_text_field( $_POST['version'] ?? '' );
        $changelog = wp_kses_post( $_POST['changelog'] ?? '' );

        if ( ! in_array( $type, [ 'plugin', 'theme' ] ) ) wp_send_json_error( 'Vigane tüüp' );
        if ( ! preg_match( '/^\d+\.\d+(\.\d+)?$/', $version ) )    wp_send_json_error( 'Vigane versioon (näide: 1.6.6)' );

        $result = self::build_release( $type, $version, $changelog );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        // Update version constant in the source file too
        if ( $type === 'plugin' ) {
            self::bump_plugin_version( $version );
        } elseif ( $type === 'theme' ) {
            self::bump_theme_version( $version );
        }

        wp_send_json_success( [
            'message'      => ucfirst( $type ) . ' uuenduspakett v' . $version . ' loodud!',
            'download_url' => $result['download_url'],
            'info_url'     => $result['info_url'],
        ] );
    }

    // ── Build release ZIP + JSON ──────────────────────────────────────────────

    public static function build_release( $type, $version, $changelog = '' ) {
        $upload    = wp_upload_dir();
        $dir       = $upload['basedir'] . '/vesho-releases';
        $url_base  = $upload['baseurl'] . '/vesho-releases';

        if ( ! file_exists( $dir ) ) {
            wp_mkdir_p( $dir );
        }
        // Prevent directory listing and direct PHP execution
        if ( ! file_exists( $dir . '/.htaccess' ) ) {
            file_put_contents( $dir . '/.htaccess', "Options -Indexes\n<Files *.php>\nDeny from all\n</Files>" );
        }
        if ( ! file_exists( $dir . '/index.php' ) ) {
            file_put_contents( $dir . '/index.php', '<?php // Silence is golden.' );
        }

        if ( $type === 'plugin' ) {
            $source   = WP_PLUGIN_DIR . '/vesho-crm';
            $zip_name = 'vesho-crm.zip';
            $folder   = 'vesho-crm';
            $info = [
                'name'         => 'Vesho CRM',
                'slug'         => 'vesho-crm',
                'version'      => $version,
                'download_url' => $url_base . '/' . $zip_name,
                'url'          => home_url(),
                'requires'     => '6.0',
                'tested'       => '6.9',
                'requires_php' => '7.4',
                'last_updated' => current_time( 'Y-m-d H:i:s' ),
                'changelog'    => '<h4>' . esc_html( $version ) . '</h4>' . ( $changelog ? wpautop( $changelog ) : '<p>Uuendused ja parandused.</p>' ),
            ];
        } else {
            $source   = get_theme_root() . '/vesho';
            $zip_name = 'vesho-theme.zip';
            $folder   = 'vesho';
            $info = [
                'name'         => 'Vesho',
                'slug'         => 'vesho',
                'version'      => $version,
                'download_url' => $url_base . '/' . $zip_name,
                'url'          => home_url(),
                'requires'     => '6.0',
                'tested'       => '6.9',
                'requires_php' => '7.4',
                'last_updated' => current_time( 'Y-m-d H:i:s' ),
                'changelog'    => '<h4>' . esc_html( $version ) . '</h4>' . ( $changelog ? wpautop( $changelog ) : '<p>Uuendused ja parandused.</p>' ),
            ];
        }

        // Bump version in source files BEFORE zipping
        if ( $type === 'plugin' ) {
            self::bump_plugin_version( $version );
        } else {
            self::bump_theme_version( $version );
        }

        // Create ZIP
        $zip_path = $dir . '/' . $zip_name;
        $zip_ok   = self::zip_directory( $source, $zip_path, $folder );
        if ( ! $zip_ok ) {
            return new WP_Error( 'zip_failed', 'ZIP faili loomine ebaõnnestus. Kontrolli, et ZipArchive on PHP-s lubatud.' );
        }

        // Save info JSON
        $info_file = $dir . '/' . $type . '-info.json';
        file_put_contents( $info_file, wp_json_encode( $info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );

        // Clear cached info
        delete_transient( 'vesho_remote_' . $type . '_info' );

        return [
            'download_url' => $url_base . '/' . $zip_name,
            'info_url'     => $url_base . '/' . $type . '-info.json',
        ];
    }

    // ── ZIP a directory ───────────────────────────────────────────────────────

    private static function zip_directory( $source_dir, $zip_path, $folder_name ) {
        if ( ! class_exists( 'ZipArchive' ) ) return false;

        // Remove stale ZIP
        if ( file_exists( $zip_path ) ) unlink( $zip_path );

        $zip = new ZipArchive();
        if ( $zip->open( $zip_path, ZipArchive::CREATE ) !== true ) return false;

        $source_dir = rtrim( realpath( $source_dir ), DIRECTORY_SEPARATOR );

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $source_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        // Files/dirs to exclude from the release ZIP
        $exclude = [ '.git', '.github', 'node_modules', '.DS_Store', 'Thumbs.db', '.env' ];

        foreach ( $iterator as $file ) {
            if ( ! $file->isFile() ) continue;

            $real_path = $file->getRealPath();

            // Skip excluded paths
            $skip = false;
            foreach ( $exclude as $ex ) {
                if ( strpos( $real_path, DIRECTORY_SEPARATOR . $ex ) !== false ) {
                    $skip = true;
                    break;
                }
            }
            if ( $skip ) continue;

            $relative = $folder_name . DIRECTORY_SEPARATOR . substr( $real_path, strlen( $source_dir ) + 1 );
            $relative = str_replace( DIRECTORY_SEPARATOR, '/', $relative );
            $zip->addFile( $real_path, $relative );
        }

        return $zip->close();
    }

    // ── Bump version in plugin header ─────────────────────────────────────────

    private static function bump_plugin_version( $new_version ) {
        $plugin_file = WP_PLUGIN_DIR . '/vesho-crm/vesho-crm.php';
        if ( ! file_exists( $plugin_file ) ) return;

        $content = file_get_contents( $plugin_file );
        // Plugin header: Version: X.X.X
        $content = preg_replace( '/^(\s*\*\s*Version:\s*)[\d.]+/m', '${1}' . $new_version, $content );
        // PHP constant: define('VESHO_CRM_VERSION', 'X.X.X')
        $content = preg_replace( "/define\(\s*'VESHO_CRM_VERSION'\s*,\s*'[^']+'\s*\)/", "define('VESHO_CRM_VERSION', '" . $new_version . "')", $content );
        file_put_contents( $plugin_file, $content );
    }

    // ── Bump version in theme style.css ──────────────────────────────────────

    private static function bump_theme_version( $new_version ) {
        $style_file = get_theme_root() . '/vesho/style.css';
        if ( ! file_exists( $style_file ) ) return;

        $content = file_get_contents( $style_file );
        $content = preg_replace( '/^(Version:\s*)[\d.]+/m', '${1}' . $new_version, $content );
        file_put_contents( $style_file, $content );
    }

    // ── AJAX: import starter content ──────────────────────────────────────────

    public static function ajax_import_starter_content() {
        check_ajax_referer( 'vesho_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Unauthorized' );

        $xml_file = WP_PLUGIN_DIR . '/vesho-crm/data/starter-content.xml';
        if ( ! file_exists( $xml_file ) ) {
            wp_send_json_error( 'starter-content.xml ei leitud' );
        }

        // Parse WXR with full namespace support
        $xml_content = file_get_contents( $xml_file );
        // Replace localhost URLs with live site URL
        $local_url = 'http://localhost/wordpress';
        $live_url  = rtrim( home_url(), '/' );
        $xml_content = str_replace( $local_url, $live_url, $xml_content );

        $xml = simplexml_load_string( $xml_content );
        if ( ! $xml ) wp_send_json_error( 'XML parsimine ebaõnnestus' );

        $ns       = $xml->getNamespaces( true );
        $log      = [];
        $imported = 0;

        // Import pages & posts with ALL meta (including Elementor data)
        foreach ( $xml->channel->item as $item ) {
            $wp          = $item->children( $ns['wp'] );
            $post_type   = (string) $wp->post_type;
            $post_status = (string) $wp->status;
            $post_name   = (string) $wp->post_name;
            $title       = (string) $item->title;

            if ( ! in_array( $post_type, [ 'page', 'post' ], true ) ) continue;
            if ( $post_status !== 'publish' ) continue;

            // Get post content
            $content = '';
            foreach ( $item->children( 'http://purl.org/rss/1.0/modules/content/' ) as $c ) {
                $content = (string) $c;
            }
            $content = str_replace( $local_url, $live_url, $content );

            // Collect post meta (meta_key/meta_value are also in wp: namespace)
            $meta = [];
            foreach ( $wp->postmeta as $pm ) {
                $pm_wp = $pm->children( $ns['wp'] );
                $key   = (string) $pm_wp->meta_key;
                $val   = (string) $pm_wp->meta_value;
                if ( ! $key ) continue;
                // Skip Elementor computed/cache meta — Elementor regenerates these automatically
                $skip_meta = [ '_elementor_page_assets', '_elementor_css', '_elementor_inline_assets' ];
                if ( in_array( $key, $skip_meta, true ) ) continue;
                // Replace localhost URLs — handle both plain and JSON-escaped slashes
                $local_url_json = str_replace( '/', '\/', $local_url );
                $live_url_json  = str_replace( '/', '\/', rtrim( $live_url, '/' ) );
                $val = str_replace( $local_url_json, $live_url_json, $val );
                $val = str_replace( $local_url, rtrim( $live_url, '/' ), $val );
                // Force default page template — elementor_header_footer needs Pro
                if ( $key === '_wp_page_template' && $val === 'elementor_header_footer' ) {
                    $val = 'default';
                }
                $meta[ $key ] = $val;
            }

            // Check if exists
            $existing = get_page_by_path( $post_name, OBJECT, $post_type );
            if ( $existing ) {
                // Update existing post content + meta
                wp_update_post( [
                    'ID'           => $existing->ID,
                    'post_content' => $content,
                ] );
                foreach ( $meta as $k => $v ) {
                    // _elementor_data is JSON with backslashes — wp_slash() prevents wp_unslash() corruption
                    $meta_val = ( $k === '_elementor_data' ) ? wp_slash( $v ) : $v;
                    update_post_meta( $existing->ID, $k, $meta_val );
                }
                $log[] = "🔄 Uuendatud [{$post_type}]: {$title}";
                continue;
            }

            $post_id = wp_insert_post( [
                'post_title'   => $title,
                'post_name'    => $post_name,
                'post_content' => $content,
                'post_status'  => 'publish',
                'post_type'    => $post_type,
            ] );

            if ( is_wp_error( $post_id ) ) {
                $log[] = "❌ Viga [{$post_type}]: {$title}";
            } else {
                // Save all meta including Elementor data
                foreach ( $meta as $k => $v ) {
                    // _elementor_data is JSON with backslashes — wp_slash() prevents wp_unslash() corruption
                    $meta_val = ( $k === '_elementor_data' ) ? wp_slash( $v ) : $v;
                    update_post_meta( $post_id, $k, $meta_val );
                }
                $log[] = "✅ Imporditud [{$post_type}]: {$title}";
                $imported++;
            }
        }

        // Set front page if 'avaleht' page exists
        $front = get_page_by_path( 'avaleht' ) ?: get_page_by_path( 'kodu' );
        if ( $front ) {
            update_option( 'show_on_front', 'page' );
            update_option( 'page_on_front', $front->ID );
            $log[] = "🏠 Esileht seatud: {$front->post_title}";
        }

        // Create primary nav menu if missing
        $menu_name = 'Peamine';
        $menu_id   = 0;
        $existing_menu = get_term_by( 'name', $menu_name, 'nav_menu' );
        if ( ! $existing_menu ) {
            $menu_id = wp_create_nav_menu( $menu_name );
            $log[]   = "📋 Menüü loodud: {$menu_name}";
        } else {
            $menu_id = $existing_menu->term_id;
            $log[]   = "⏭ Menüü olemas: {$menu_name}";
        }

        // Add pages to menu
        $menu_pages = [ 'avaleht' => 'Avaleht', 'meist' => 'Meist', 'teenused' => 'Teenused', 'pood' => 'Pood', 'uudised' => 'Uudised', 'kontakt' => 'Kontakt' ];
        if ( $menu_id && ! is_wp_error( $menu_id ) ) {
            $existing_items = wp_get_nav_menu_items( $menu_id );
            $existing_slugs = [];
            if ( $existing_items ) {
                foreach ( $existing_items as $ei ) {
                    $p = get_post( $ei->object_id );
                    if ( $p ) $existing_slugs[] = $p->post_name;
                }
            }
            foreach ( $menu_pages as $slug => $label ) {
                if ( in_array( $slug, $existing_slugs, true ) ) continue;
                $page = get_page_by_path( $slug );
                if ( $page ) {
                    wp_update_nav_menu_item( $menu_id, 0, [
                        'menu-item-title'     => $label,
                        'menu-item-object'    => 'page',
                        'menu-item-object-id' => $page->ID,
                        'menu-item-type'      => 'post_type',
                        'menu-item-status'    => 'publish',
                    ] );
                    $log[] = "➕ Menüüsse lisatud: {$label}";
                }
            }
            // Assign menu to primary location
            $locations = get_theme_mod( 'nav_menu_locations', [] );
            $locations['primary'] = $menu_id;
            set_theme_mod( 'nav_menu_locations', $locations );
            $log[] = "📌 Menüü määratud: Primary Navigation";
        }

        wp_send_json_success( [
            'message' => "{$imported} elementi imporditud, menüü seadistatud.",
            'log'     => $log,
        ] );
    }

    // ── Get current info.json contents (for admin UI) ─────────────────────────

    public static function get_release_info( $type ) {
        // On live server: fetch from GitHub. On local: try local file first.
        $upload = wp_upload_dir();
        $file   = $upload['basedir'] . '/vesho-releases/' . $type . '-info.json';
        if ( file_exists( $file ) ) {
            return json_decode( file_get_contents( $file ) );
        }
        // Fall back to remote GitHub info
        return self::fetch_remote_info( $type );
    }

    /**
     * Direct theme installer — bypasses WP_Upgrader and move_to_rollback_cache entirely.
     * Downloads ZIP, deletes old theme, extracts and copies new theme.
     */
    public static function ajax_install_theme() {
        check_ajax_referer( 'vesho_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'install_themes' ) ) {
            wp_send_json_error( 'Pole õigusi' );
        }

        // Always fetch fresh info — never use cached version
        delete_transient( 'vesho_remote_theme_info' );

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';
        WP_Filesystem();
        global $wp_filesystem;

        // Get download URL
        $info = self::fetch_remote_info( 'theme' );
        if ( ! $info || empty( $info->download_url ) ) {
            wp_send_json_error( 'Uuenduse info puudub — kontrolli GitHubi' );
        }

        // Download ZIP to temp file
        $tmp_zip = download_url( $info->download_url );
        if ( is_wp_error( $tmp_zip ) ) {
            wp_send_json_error( 'Allalaadimine ebaõnnestus: ' . $tmp_zip->get_error_message() );
        }

        // Extract ZIP to temp directory
        $tmp_dir = WP_CONTENT_DIR . '/upgrade/vesho-theme-tmp-' . time();
        wp_mkdir_p( $tmp_dir );

        $unzip = unzip_file( $tmp_zip, $tmp_dir );
        @unlink( $tmp_zip );

        if ( is_wp_error( $unzip ) ) {
            $wp_filesystem->delete( $tmp_dir, true );
            wp_send_json_error( 'Lahtipakkimine ebaõnnestus: ' . $unzip->get_error_message() );
        }

        // Find extracted theme dir (should be vesho/ inside tmp_dir)
        $theme_src = $tmp_dir . '/' . self::THEME_SLUG;
        if ( ! is_dir( $theme_src ) ) {
            $dirs = glob( $tmp_dir . '/*', GLOB_ONLYDIR );
            $theme_src = ! empty( $dirs ) ? $dirs[0] : '';
        }

        if ( empty( $theme_src ) || ! is_dir( $theme_src ) ) {
            $wp_filesystem->delete( $tmp_dir, true );
            wp_send_json_error( 'Teema kausta ei leitud ZIP-is' );
        }

        $theme_dest = get_theme_root() . '/' . self::THEME_SLUG;

        // Delete old theme (no backup — this is the whole point)
        if ( $wp_filesystem && $wp_filesystem->is_dir( $theme_dest ) ) {
            $wp_filesystem->delete( $theme_dest, true );
        } elseif ( is_dir( $theme_dest ) ) {
            self::recursive_rmdir( $theme_dest );
        }

        // Copy new theme into place
        $copy_result = copy_dir( $theme_src, $theme_dest );
        $wp_filesystem->delete( $tmp_dir, true );

        if ( is_wp_error( $copy_result ) ) {
            wp_send_json_error( 'Kopeerimine ebaõnnestus: ' . $copy_result->get_error_message() );
        }

        // Clear caches
        wp_clean_themes_cache();
        delete_site_transient( 'update_themes' );
        delete_transient( 'vesho_remote_theme_info' );

        wp_send_json_success( 'Teema uuendatud versioonile ' . esc_html( $info->version ) . ' ✅' );
    }

    // ── Direct plugin installer (bypasses WP_Upgrader) ───────────────────────────
    public static function ajax_install_plugin() {
        check_ajax_referer( 'vesho_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'update_plugins' ) ) {
            wp_send_json_error( 'Pole õigusi' );
        }

        // Always fetch fresh info — never use cached version
        delete_transient( 'vesho_remote_plugin_info' );

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        WP_Filesystem();
        global $wp_filesystem;

        $info = self::fetch_remote_info( 'plugin' );
        if ( ! $info || empty( $info->download_url ) ) {
            wp_send_json_error( 'Uuenduse info puudub — kontrolli GitHubi' );
        }

        $tmp_zip = download_url( $info->download_url );
        if ( is_wp_error( $tmp_zip ) ) {
            wp_send_json_error( 'Allalaadimine ebaõnnestus: ' . $tmp_zip->get_error_message() );
        }

        $tmp_dir = WP_CONTENT_DIR . '/upgrade/vesho-plugin-tmp-' . time();
        wp_mkdir_p( $tmp_dir );

        $unzip = unzip_file( $tmp_zip, $tmp_dir );
        @unlink( $tmp_zip );

        if ( is_wp_error( $unzip ) ) {
            $wp_filesystem->delete( $tmp_dir, true );
            wp_send_json_error( 'Lahtipakkimine ebaõnnestus: ' . $unzip->get_error_message() );
        }

        // Find extracted plugin dir (should be vesho-crm/ inside tmp_dir)
        $plugin_src = $tmp_dir . '/' . self::PLUGIN_SLUG;
        if ( ! is_dir( $plugin_src ) ) {
            $dirs = glob( $tmp_dir . '/*', GLOB_ONLYDIR );
            $plugin_src = ! empty( $dirs ) ? $dirs[0] : '';
        }

        if ( empty( $plugin_src ) || ! is_dir( $plugin_src ) ) {
            $wp_filesystem->delete( $tmp_dir, true );
            wp_send_json_error( 'Plugina kausta ei leitud ZIP-is' );
        }

        // Install to the directory where plugin is currently running — never delete, just overwrite
        $plugin_dest = defined('VESHO_CRM_FILE') ? dirname( VESHO_CRM_FILE ) : WP_PLUGIN_DIR . '/' . self::PLUGIN_SLUG;
        wp_mkdir_p( $plugin_dest );

        $copy_result = copy_dir( $plugin_src, $plugin_dest );
        $wp_filesystem->delete( $tmp_dir, true );

        if ( is_wp_error( $copy_result ) ) {
            wp_send_json_error( 'Kopeerimine ebaõnnestus: ' . $copy_result->get_error_message() );
        }

        delete_site_transient( 'update_plugins' );
        delete_transient( 'vesho_remote_plugin_info' );

        if ( function_exists( 'opcache_reset' ) ) opcache_reset();
        if ( function_exists( 'opcache_invalidate' ) ) {
            opcache_invalidate( $plugin_dest . '/vesho-crm.php', true );
        }

        // Verify: read actual version from disk after copy
        clearstatcache( true, $plugin_dest . '/vesho-crm.php' );
        $verify_data    = function_exists('get_plugin_data') ? get_plugin_data( $plugin_dest . '/vesho-crm.php', false, false ) : [];
        $actual_version = $verify_data['Version'] ?? 'ei leidnud';

        wp_send_json_success( 'Sihtkaust: ' . $plugin_dest . ' | ZIP versioon: ' . esc_html( $info->version ) . ' | Failis: ' . $actual_version );
    }
}
