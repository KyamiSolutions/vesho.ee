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
        // Admin AJAX: create release package
        add_action( 'wp_ajax_vesho_create_release', [ __CLASS__, 'ajax_create_release' ] );
        // Admin AJAX: force-check for updates now
        add_action( 'wp_ajax_vesho_force_update_check', [ __CLASS__, 'ajax_force_check' ] );
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

        $url      = self::get_server_url() . '/' . $type . '-info.json';
        $response = wp_remote_get( $url, [
            'timeout'   => 10,
            'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
            'headers'   => [ 'Cache-Control' => 'no-cache' ],
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

    // ── Get current info.json contents (for admin UI) ─────────────────────────

    public static function get_release_info( $type ) {
        $upload = wp_upload_dir();
        $file   = $upload['basedir'] . '/vesho-releases/' . $type . '-info.json';
        if ( ! file_exists( $file ) ) return null;
        return json_decode( file_get_contents( $file ) );
    }
}
