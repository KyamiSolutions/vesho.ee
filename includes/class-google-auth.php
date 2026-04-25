<?php
/**
 * Vesho CRM — Google OAuth 2.0 login
 * Toetab: client portaal + worker portaal
 * Seadistatav CRM Seaded → Integratsioonid
 *
 * @package Vesho_CRM
 */
defined( 'ABSPATH' ) || exit;

class Vesho_Google_Auth {

    const GOOGLE_AUTH_URL  = 'https://accounts.google.com/o/oauth2/v2/auth';
    const GOOGLE_TOKEN_URL = 'https://oauth2.googleapis.com/token';
    const GOOGLE_USER_URL  = 'https://www.googleapis.com/oauth2/v2/userinfo';

    public static function init() {
        add_action( 'init', [ __CLASS__, 'handle_callback' ] );
    }

    // ── Settings helpers ──────────────────────────────────────────────────────

    public static function client_id() {
        return trim( get_option( 'vesho_google_client_id', '' ) );
    }

    public static function client_secret() {
        return trim( get_option( 'vesho_google_client_secret', '' ) );
    }

    public static function enabled_for( $portal ) {
        // Credentials puuduvad → keelatud
        if ( ! self::client_id() || ! self::client_secret() ) return false;
        // Checkbox seade: vesho_google_login_client / vesho_google_login_worker
        $option_key = ( $portal === 'worker' ) ? 'vesho_google_login_worker' : 'vesho_google_login_client';
        return get_option( $option_key, '1' ) === '1';
    }

    // ── Build OAuth URL ───────────────────────────────────────────────────────

    public static function auth_url( $portal ) {
        $state = wp_create_nonce( 'vesho_google_' . $portal );
        set_transient( 'vesho_google_state_' . $state, $portal, 600 );

        return add_query_arg( [
            'client_id'     => self::client_id(),
            'redirect_uri'  => self::redirect_uri( $portal ),
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ], self::GOOGLE_AUTH_URL );
    }

    public static function redirect_uri( $portal ) {
        return add_query_arg( [
            'vesho_google_cb' => $portal,
        ], home_url( '/' ) );
    }

    // ── Handle callback ───────────────────────────────────────────────────────

    public static function handle_callback() {
        $portal = sanitize_key( $_GET['vesho_google_cb'] ?? '' );
        if ( ! $portal ) return;

        $code  = sanitize_text_field( $_GET['code']  ?? '' );
        $state = sanitize_text_field( $_GET['state'] ?? '' );
        $error = sanitize_text_field( $_GET['error'] ?? '' );

        // Determine redirect page
        $page_opt = $portal === 'worker' ? 'vesho_worker_page' : 'vesho_portal_page';
        $page_id  = (int) get_option( $page_opt, 0 );
        $page_url = $page_id ? get_permalink( $page_id ) : home_url( '/' );

        if ( $error || ! $code || ! $state ) {
            wp_redirect( add_query_arg( 'google_error', '1', $page_url ) );
            exit;
        }

        // Verify state
        $stored = get_transient( 'vesho_google_state_' . $state );
        if ( $stored !== $portal ) {
            wp_redirect( add_query_arg( 'google_error', 'state', $page_url ) );
            exit;
        }
        delete_transient( 'vesho_google_state_' . $state );

        // Exchange code for token
        $token_resp = wp_remote_post( self::GOOGLE_TOKEN_URL, [
            'body' => [
                'code'          => $code,
                'client_id'     => self::client_id(),
                'client_secret' => self::client_secret(),
                'redirect_uri'  => self::redirect_uri( $portal ),
                'grant_type'    => 'authorization_code',
            ],
            'timeout' => 15,
        ] );

        if ( is_wp_error( $token_resp ) ) {
            wp_redirect( add_query_arg( 'google_error', 'token', $page_url ) );
            exit;
        }

        $token_data = json_decode( wp_remote_retrieve_body( $token_resp ), true );
        $access_token = $token_data['access_token'] ?? '';

        if ( ! $access_token ) {
            wp_redirect( add_query_arg( 'google_error', 'token', $page_url ) );
            exit;
        }

        // Get user info
        $user_resp = wp_remote_get( self::GOOGLE_USER_URL, [
            'headers' => [ 'Authorization' => 'Bearer ' . $access_token ],
            'timeout' => 15,
        ] );

        if ( is_wp_error( $user_resp ) ) {
            wp_redirect( add_query_arg( 'google_error', 'userinfo', $page_url ) );
            exit;
        }

        $user_data = json_decode( wp_remote_retrieve_body( $user_resp ), true );
        $email = strtolower( trim( $user_data['email'] ?? '' ) );
        $name  = sanitize_text_field( $user_data['name'] ?? '' );

        if ( ! $email ) {
            wp_redirect( add_query_arg( 'google_error', 'email', $page_url ) );
            exit;
        }

        // Login based on portal
        if ( $portal === 'client' ) {
            self::login_client( $email, $name, $page_url );
        } elseif ( $portal === 'worker' ) {
            self::login_worker( $email, $name, $page_url );
        }
    }

    // ── Client login ──────────────────────────────────────────────────────────

    private static function login_client( $email, $name, $page_url ) {
        global $wpdb;

        // Find client by email
        $client = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_clients WHERE LOWER(email) = %s LIMIT 1",
            $email
        ) );

        if ( ! $client ) {
            // Auto-create if portal registration is on
            if ( get_option( 'vesho_portal_registration', '0' ) === '1' ) {
                $wpdb->insert( $wpdb->prefix . 'vesho_clients', [
                    'name'         => $name ?: $email,
                    'email'        => $email,
                    'created_at'   => current_time( 'mysql' ),
                    'password'     => '',
                    'account_type' => 'eraisik',
                ] );
                $client = $wpdb->get_row( $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}vesho_clients WHERE LOWER(email) = %s LIMIT 1",
                    $email
                ) );
            }
        }

        if ( ! $client ) {
            wp_redirect( add_query_arg( 'google_error', 'no_account', $page_url ) );
            exit;
        }

        // Set session
        if ( session_status() === PHP_SESSION_NONE ) session_start();
        $_SESSION['vesho_client_id']    = (int) $client->id;
        $_SESSION['vesho_client_email'] = $client->email;
        $_SESSION['vesho_client_name']  = $client->name;

        wp_redirect( $page_url );
        exit;
    }

    // ── Worker login ──────────────────────────────────────────────────────────

    private static function login_worker( $email, $name, $page_url ) {
        global $wpdb;

        // Try WP user first
        $wp_user = get_user_by( 'email', $email );
        if ( $wp_user && ( current_user_can( 'manage_options' ) || get_user_meta( $wp_user->ID, 'vesho_worker_id', true ) ) ) {
            wp_set_auth_cookie( $wp_user->ID, true );
            wp_redirect( $page_url );
            exit;
        }

        // Find worker by email in vesho_workers
        $worker = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_workers WHERE LOWER(email) = %s AND active = 1 LIMIT 1",
            $email
        ) );

        if ( ! $worker ) {
            wp_redirect( add_query_arg( 'google_error', 'no_worker', $page_url ) );
            exit;
        }

        // Set session for worker
        if ( session_status() === PHP_SESSION_NONE ) session_start();
        $_SESSION['vesho_worker_id']   = (int) $worker->id;
        $_SESSION['vesho_worker_name'] = $worker->name;

        wp_redirect( $page_url );
        exit;
    }

    // ── Login button HTML ─────────────────────────────────────────────────────

    public static function login_button( $portal ) {
        if ( ! self::enabled_for( $portal ) ) return '';
        $url = self::auth_url( $portal );
        return '<a href="' . esc_url( $url ) . '" class="vesho-google-btn">
            <svg width="18" height="18" viewBox="0 0 48 48" style="flex-shrink:0">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                <path fill="none" d="M0 0h48v48H0z"/>
            </svg>
            Jätka Google\'iga
        </a>';
    }
}

Vesho_Google_Auth::init();
