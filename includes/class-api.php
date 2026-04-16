<?php
/**
 * Vesho CRM – REST API class
 * Registers REST API endpoints under namespace vesho/v1
 *
 * @package Vesho_CRM
 */

defined( 'ABSPATH' ) || exit;

class Vesho_CRM_API {

    const NAMESPACE = 'vesho/v1';

    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes() {
        $ns = self::NAMESPACE;

        // ── Clients ───────────────────────────────────────────────────────────
        register_rest_route( $ns, '/clients', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_clients' ),    'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_client' ),  'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
        ) );
        register_rest_route( $ns, '/clients/(?P<id>\d+)', array(
            array( 'methods' => 'GET',    'callback' => array( __CLASS__, 'get_client' ),    'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
            array( 'methods' => 'PUT',    'callback' => array( __CLASS__, 'update_client' ), 'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
            array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_client' ), 'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
        ) );

        // ── Devices ───────────────────────────────────────────────────────────
        register_rest_route( $ns, '/devices', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_devices' ),   'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_device' ), 'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
        ) );
        register_rest_route( $ns, '/devices/(?P<id>\d+)', array(
            array( 'methods' => 'GET',    'callback' => array( __CLASS__, 'get_device' ),    'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
            array( 'methods' => 'PUT',    'callback' => array( __CLASS__, 'update_device' ), 'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
            array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_device' ), 'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
        ) );

        // ── Maintenances ──────────────────────────────────────────────────────
        register_rest_route( $ns, '/maintenances', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_maintenances' ),   'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_maintenance' ), 'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
        ) );
        register_rest_route( $ns, '/maintenances/(?P<id>\d+)', array(
            array( 'methods' => 'PUT',    'callback' => array( __CLASS__, 'update_maintenance' ), 'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
            array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_maintenance' ), 'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
        ) );

        // ── Invoices ──────────────────────────────────────────────────────────
        register_rest_route( $ns, '/invoices', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_invoices' ),   'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_invoice' ), 'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
        ) );
        register_rest_route( $ns, '/invoices/(?P<id>\d+)', array(
            array( 'methods' => 'PUT',    'callback' => array( __CLASS__, 'update_invoice' ), 'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
            array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_invoice' ), 'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
        ) );

        // ── Services ──────────────────────────────────────────────────────────
        register_rest_route( $ns, '/services', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_services' ),   'permission_callback' => '__return_true' ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_service' ), 'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
        ) );
        register_rest_route( $ns, '/services/(?P<id>\d+)', array(
            array( 'methods' => 'PUT',    'callback' => array( __CLASS__, 'update_service' ), 'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
            array( 'methods' => 'DELETE', 'callback' => array( __CLASS__, 'delete_service' ), 'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
        ) );

        // ── Guest requests ────────────────────────────────────────────────────
        register_rest_route( $ns, '/guest-requests', array(
            array( 'methods' => 'GET',  'callback' => array( __CLASS__, 'get_requests' ),   'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
            array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'create_request' ), 'permission_callback' => '__return_true' ),
        ) );
        register_rest_route( $ns, '/guest-requests/(?P<id>\d+)', array(
            array( 'methods' => 'PUT', 'callback' => array( __CLASS__, 'update_request' ), 'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
        ) );

        // ── Stats ─────────────────────────────────────────────────────────────
        register_rest_route( $ns, '/stats', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'get_stats' ),
            'permission_callback' => array( __CLASS__, 'admin_permission' ),
        ) );

        // ── Settings ──────────────────────────────────────────────────────────
        register_rest_route( $ns, '/settings', array(
            array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'get_settings' ), 'permission_callback' => '__return_true' ),
            array( 'methods' => 'PUT', 'callback' => array( __CLASS__, 'update_settings' ), 'permission_callback' => array( __CLASS__, 'admin_permission' ) ),
        ) );

        // ── Payment webhooks ───────────────────────────────────────────────────
        register_rest_route( $ns, '/stripe-webhook', array(
            'methods'             => 'POST',
            'callback'            => 'vesho_rest_stripe_webhook',
            'permission_callback' => '__return_true',
        ) );
        register_rest_route( $ns, '/mc-notify', array(
            'methods'             => 'POST',
            'callback'            => 'vesho_rest_mc_notify',
            'permission_callback' => '__return_true',
        ) );
        register_rest_route( $ns, '/montonio-webhook', array(
            'methods'             => 'POST',
            'callback'            => 'vesho_rest_montonio_notify',
            'permission_callback' => '__return_true',
        ) );
    }

    // ── Permissions ───────────────────────────────────────────────────────────

    public static function admin_permission( $request ) {
        // Check WP nonce for logged-in admin
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        // Check REST nonce header
        $nonce = $request->get_header( 'X-WP-Nonce' );
        if ( $nonce && wp_verify_nonce( $nonce, 'wp_rest' ) && current_user_can( 'manage_options' ) ) {
            return true;
        }

        return new WP_Error( 'rest_forbidden', __( 'Puudub vajalik juurdepääsuõigus.', 'vesho-crm' ), array( 'status' => 403 ) );
    }

    // ── Clients ───────────────────────────────────────────────────────────────

    public static function get_clients( $request ) {
        $args = array(
            'limit'  => min( absint( $request->get_param( 'limit' ) ?: 50 ), 200 ),
            'offset' => absint( $request->get_param( 'offset' ) ?: 0 ),
            'search' => sanitize_text_field( $request->get_param( 'search' ) ?: '' ),
            'type'   => sanitize_text_field( $request->get_param( 'type' ) ?: '' ),
        );
        $clients = Vesho_CRM_Database::get_clients( $args );
        $total   = Vesho_CRM_Database::count_clients();
        return rest_ensure_response( array( 'data' => $clients, 'total' => $total ) );
    }

    public static function get_client( $request ) {
        $id     = absint( $request['id'] );
        $client = Vesho_CRM_Database::get_client( $id );
        if ( ! $client ) return new WP_Error( 'not_found', 'Klient ei leitud', array( 'status' => 404 ) );
        // Don't expose password hash
        unset( $client->password );
        $client->devices  = Vesho_CRM_Database::get_devices_for_client( $id );
        $client->invoices = Vesho_CRM_Database::get_invoices_for_client( $id );
        return rest_ensure_response( $client );
    }

    public static function create_client( $request ) {
        global $wpdb;
        $params = $request->get_json_params() ?: $request->get_params();

        $data = array(
            'name'        => sanitize_text_field( $params['name'] ?? '' ),
            'email'       => sanitize_email( $params['email'] ?? '' ),
            'phone'       => sanitize_text_field( $params['phone'] ?? '' ),
            'address'     => sanitize_textarea_field( $params['address'] ?? '' ),
            'client_type' => sanitize_text_field( $params['client_type'] ?? 'eraisik' ),
            'reg_code'    => sanitize_text_field( $params['reg_code'] ?? '' ),
            'vat_number'  => sanitize_text_field( $params['vat_number'] ?? '' ),
            'notes'       => sanitize_textarea_field( $params['notes'] ?? '' ),
            'created_at'  => current_time( 'mysql' ),
        );

        if ( ! $data['name'] || ! $data['email'] ) {
            return new WP_Error( 'invalid_data', 'Nimi ja e-post on kohustuslikud', array( 'status' => 400 ) );
        }

        if ( ! empty( $params['password'] ) ) {
            $data['password'] = wp_hash_password( $params['password'] );
        }

        $result = $wpdb->insert( $wpdb->prefix . 'vesho_clients', $data );
        if ( ! $result ) {
            return new WP_Error( 'db_error', $wpdb->last_error, array( 'status' => 500 ) );
        }

        return rest_ensure_response( Vesho_CRM_Database::get_client( $wpdb->insert_id ) );
    }

    public static function update_client( $request ) {
        global $wpdb;
        $id     = absint( $request['id'] );
        $client = Vesho_CRM_Database::get_client( $id );
        if ( ! $client ) return new WP_Error( 'not_found', 'Klient ei leitud', array( 'status' => 404 ) );

        $params = $request->get_json_params() ?: $request->get_params();
        $data   = array();
        $fields = array( 'name', 'email', 'phone', 'address', 'client_type', 'reg_code', 'vat_number', 'notes' );

        foreach ( $fields as $field ) {
            if ( isset( $params[ $field ] ) ) {
                $data[ $field ] = $field === 'email'
                    ? sanitize_email( $params[ $field ] )
                    : sanitize_text_field( $params[ $field ] );
            }
        }

        if ( ! empty( $params['password'] ) ) {
            $data['password'] = wp_hash_password( $params['password'] );
        }

        if ( empty( $data ) ) {
            return new WP_Error( 'no_data', 'Muudatusi ei esitatud', array( 'status' => 400 ) );
        }

        $wpdb->update( $wpdb->prefix . 'vesho_clients', $data, array( 'id' => $id ) );
        $updated = Vesho_CRM_Database::get_client( $id );
        unset( $updated->password );
        return rest_ensure_response( $updated );
    }

    public static function delete_client( $request ) {
        global $wpdb;
        $id = absint( $request['id'] );
        $wpdb->delete( $wpdb->prefix . 'vesho_clients', array( 'id' => $id ) );
        return rest_ensure_response( array( 'deleted' => true, 'id' => $id ) );
    }

    // ── Devices ───────────────────────────────────────────────────────────────

    public static function get_devices( $request ) {
        global $wpdb;
        $client_id = absint( $request->get_param( 'client_id' ) ?: 0 );
        if ( $client_id ) {
            $devices = Vesho_CRM_Database::get_devices_for_client( $client_id );
        } else {
            $devices = $wpdb->get_results(
                "SELECT d.*, c.name as client_name FROM {$wpdb->prefix}vesho_devices d
                 LEFT JOIN {$wpdb->prefix}vesho_clients c ON d.client_id = c.id
                 ORDER BY d.created_at DESC LIMIT 100"
            );
        }
        return rest_ensure_response( $devices );
    }

    public static function get_device( $request ) {
        global $wpdb;
        $id = absint( $request['id'] );
        $device = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}vesho_devices WHERE id = %d", $id ) );
        if ( ! $device ) return new WP_Error( 'not_found', 'Seadet ei leitud', array( 'status' => 404 ) );
        return rest_ensure_response( $device );
    }

    public static function create_device( $request ) {
        global $wpdb;
        $params = $request->get_json_params() ?: $request->get_params();
        $data = array(
            'client_id'     => absint( $params['client_id'] ?? 0 ),
            'name'          => sanitize_text_field( $params['name'] ?? '' ),
            'model'         => sanitize_text_field( $params['model'] ?? '' ),
            'serial_number' => sanitize_text_field( $params['serial_number'] ?? '' ),
            'install_date'  => sanitize_text_field( $params['install_date'] ?? '' ) ?: null,
            'location'      => sanitize_text_field( $params['location'] ?? '' ),
            'notes'         => sanitize_textarea_field( $params['notes'] ?? '' ),
            'created_at'    => current_time( 'mysql' ),
        );
        if ( ! $data['client_id'] || ! $data['name'] ) {
            return new WP_Error( 'invalid_data', 'client_id ja nimi on kohustuslikud', array( 'status' => 400 ) );
        }
        $wpdb->insert( $wpdb->prefix . 'vesho_devices', $data );
        return rest_ensure_response( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}vesho_devices WHERE id = %d", $wpdb->insert_id ) ) );
    }

    public static function update_device( $request ) {
        global $wpdb;
        $id     = absint( $request['id'] );
        $params = $request->get_json_params() ?: $request->get_params();
        $data   = array();
        foreach ( array( 'name', 'model', 'serial_number', 'install_date', 'location', 'notes' ) as $f ) {
            if ( isset( $params[ $f ] ) ) $data[ $f ] = sanitize_text_field( $params[ $f ] );
        }
        if ( $data ) $wpdb->update( $wpdb->prefix . 'vesho_devices', $data, array( 'id' => $id ) );
        return rest_ensure_response( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}vesho_devices WHERE id = %d", $id ) ) );
    }

    public static function delete_device( $request ) {
        global $wpdb;
        $id = absint( $request['id'] );
        $wpdb->delete( $wpdb->prefix . 'vesho_devices', array( 'id' => $id ) );
        return rest_ensure_response( array( 'deleted' => true ) );
    }

    // ── Maintenances ──────────────────────────────────────────────────────────

    public static function get_maintenances( $request ) {
        global $wpdb;
        $status = sanitize_text_field( $request->get_param( 'status' ) ?: '' );
        $where  = $status ? $wpdb->prepare( 'WHERE m.status = %s', $status ) : '';
        return rest_ensure_response( $wpdb->get_results(
            "SELECT m.*, d.name as device_name, c.name as client_name, c.id as client_id
             FROM {$wpdb->prefix}vesho_maintenances m
             JOIN {$wpdb->prefix}vesho_devices d ON m.device_id = d.id
             JOIN {$wpdb->prefix}vesho_clients c ON d.client_id = c.id
             $where ORDER BY m.scheduled_date ASC LIMIT 100"
        ) );
    }

    public static function create_maintenance( $request ) {
        global $wpdb;
        $params = $request->get_json_params() ?: $request->get_params();
        $data = array(
            'device_id'      => absint( $params['device_id'] ?? 0 ),
            'scheduled_date' => sanitize_text_field( $params['scheduled_date'] ?? '' ) ?: null,
            'description'    => sanitize_textarea_field( $params['description'] ?? '' ),
            'status'         => sanitize_text_field( $params['status'] ?? 'scheduled' ),
            'locked_price'   => ! empty( $params['locked_price'] ) ? (float) $params['locked_price'] : null,
            'worker_id'      => ! empty( $params['worker_id'] ) ? absint( $params['worker_id'] ) : null,
            'created_at'     => current_time( 'mysql' ),
        );
        if ( ! $data['device_id'] ) return new WP_Error( 'invalid_data', 'device_id on kohustuslik', array( 'status' => 400 ) );
        $wpdb->insert( $wpdb->prefix . 'vesho_maintenances', $data );
        return rest_ensure_response( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}vesho_maintenances WHERE id = %d", $wpdb->insert_id ) ) );
    }

    public static function update_maintenance( $request ) {
        global $wpdb;
        $id     = absint( $request['id'] );
        $params = $request->get_json_params() ?: $request->get_params();
        $data   = array();
        $str_fields = array( 'scheduled_date', 'completed_date', 'description', 'status', 'notes' );
        foreach ( $str_fields as $f ) {
            if ( isset( $params[ $f ] ) ) $data[ $f ] = sanitize_text_field( $params[ $f ] );
        }
        if ( isset( $params['locked_price'] ) ) $data['locked_price'] = (float) $params['locked_price'];
        if ( isset( $params['worker_id'] ) )    $data['worker_id']    = absint( $params['worker_id'] );
        if ( $data ) $wpdb->update( $wpdb->prefix . 'vesho_maintenances', $data, array( 'id' => $id ) );
        return rest_ensure_response( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}vesho_maintenances WHERE id = %d", $id ) ) );
    }

    public static function delete_maintenance( $request ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'vesho_maintenances', array( 'id' => absint( $request['id'] ) ) );
        return rest_ensure_response( array( 'deleted' => true ) );
    }

    // ── Invoices ──────────────────────────────────────────────────────────────

    public static function get_invoices( $request ) {
        global $wpdb;
        $status = sanitize_text_field( $request->get_param( 'status' ) ?: '' );
        $where  = $status ? $wpdb->prepare( 'WHERE i.status = %s', $status ) : '';
        return rest_ensure_response( $wpdb->get_results(
            "SELECT i.*, c.name as client_name
             FROM {$wpdb->prefix}vesho_invoices i
             LEFT JOIN {$wpdb->prefix}vesho_clients c ON i.client_id = c.id
             $where ORDER BY i.created_at DESC LIMIT 100"
        ) );
    }

    public static function create_invoice( $request ) {
        global $wpdb;
        $params = $request->get_json_params() ?: $request->get_params();
        $data = array(
            'client_id'      => absint( $params['client_id'] ?? 0 ),
            'invoice_number' => sanitize_text_field( $params['invoice_number'] ?? Vesho_CRM_Database::get_next_invoice_number() ),
            'invoice_date'   => sanitize_text_field( $params['invoice_date'] ?? date( 'Y-m-d' ) ),
            'due_date'       => sanitize_text_field( $params['due_date'] ?? '' ) ?: null,
            'amount'         => (float) ( $params['amount'] ?? 0 ),
            'status'         => sanitize_text_field( $params['status'] ?? 'unpaid' ),
            'description'    => sanitize_textarea_field( $params['description'] ?? '' ),
            'created_at'     => current_time( 'mysql' ),
        );
        if ( ! $data['client_id'] ) return new WP_Error( 'invalid_data', 'client_id on kohustuslik', array( 'status' => 400 ) );
        $wpdb->insert( $wpdb->prefix . 'vesho_invoices', $data );
        return rest_ensure_response( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}vesho_invoices WHERE id = %d", $wpdb->insert_id ) ) );
    }

    public static function update_invoice( $request ) {
        global $wpdb;
        $id     = absint( $request['id'] );
        $params = $request->get_json_params() ?: $request->get_params();
        $data   = array();
        foreach ( array( 'invoice_number', 'invoice_date', 'due_date', 'status', 'description' ) as $f ) {
            if ( isset( $params[ $f ] ) ) $data[ $f ] = sanitize_text_field( $params[ $f ] );
        }
        if ( isset( $params['amount'] ) ) $data['amount'] = (float) $params['amount'];
        if ( $data ) $wpdb->update( $wpdb->prefix . 'vesho_invoices', $data, array( 'id' => $id ) );
        return rest_ensure_response( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}vesho_invoices WHERE id = %d", $id ) ) );
    }

    public static function delete_invoice( $request ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'vesho_invoices', array( 'id' => absint( $request['id'] ) ) );
        return rest_ensure_response( array( 'deleted' => true ) );
    }

    // ── Services ──────────────────────────────────────────────────────────────

    public static function get_services( $request ) {
        $active_only = (bool) $request->get_param( 'active' );
        return rest_ensure_response( Vesho_CRM_Database::get_services( $active_only ) );
    }

    public static function create_service( $request ) {
        global $wpdb;
        $params = $request->get_json_params() ?: $request->get_params();
        $data = array(
            'name'        => sanitize_text_field( $params['name'] ?? '' ),
            'description' => sanitize_textarea_field( $params['description'] ?? '' ),
            'price'       => (float) ( $params['price'] ?? 0 ),
            'icon'        => sanitize_text_field( $params['icon'] ?? '💧' ),
            'active'      => isset( $params['active'] ) ? (int) $params['active'] : 1,
            'created_at'  => current_time( 'mysql' ),
        );
        if ( ! $data['name'] ) return new WP_Error( 'invalid_data', 'Nimi on kohustuslik', array( 'status' => 400 ) );
        $wpdb->insert( $wpdb->prefix . 'vesho_services', $data );
        return rest_ensure_response( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}vesho_services WHERE id = %d", $wpdb->insert_id ) ) );
    }

    public static function update_service( $request ) {
        global $wpdb;
        $id     = absint( $request['id'] );
        $params = $request->get_json_params() ?: $request->get_params();
        $data   = array();
        foreach ( array( 'name', 'description', 'icon' ) as $f ) {
            if ( isset( $params[ $f ] ) ) $data[ $f ] = sanitize_text_field( $params[ $f ] );
        }
        if ( isset( $params['price'] ) )  $data['price']  = (float) $params['price'];
        if ( isset( $params['active'] ) ) $data['active'] = (int) $params['active'];
        if ( $data ) $wpdb->update( $wpdb->prefix . 'vesho_services', $data, array( 'id' => $id ) );
        return rest_ensure_response( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}vesho_services WHERE id = %d", $id ) ) );
    }

    public static function delete_service( $request ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'vesho_services', array( 'id' => absint( $request['id'] ) ) );
        return rest_ensure_response( array( 'deleted' => true ) );
    }

    // ── Guest requests ────────────────────────────────────────────────────────

    public static function get_requests( $request ) {
        return rest_ensure_response( Vesho_CRM_Database::get_recent_requests( 100 ) );
    }

    public static function create_request( $request ) {
        global $wpdb;
        $params = $request->get_json_params() ?: $request->get_params();
        $data = array(
            'name'           => sanitize_text_field( $params['name'] ?? '' ),
            'email'          => sanitize_email( $params['email'] ?? '' ),
            'phone'          => sanitize_text_field( $params['phone'] ?? '' ),
            'device_name'    => sanitize_text_field( $params['device_name'] ?? '' ),
            'service_type'   => sanitize_text_field( $params['service_type'] ?? '' ),
            'preferred_date' => sanitize_text_field( $params['preferred_date'] ?? '' ) ?: null,
            'description'    => sanitize_textarea_field( $params['description'] ?? '' ),
            'created_at'     => current_time( 'mysql' ),
        );
        if ( ! $data['name'] || ! $data['email'] ) {
            return new WP_Error( 'invalid_data', 'Nimi ja e-post on kohustuslikud', array( 'status' => 400 ) );
        }
        $wpdb->insert( $wpdb->prefix . 'vesho_guest_requests', $data );
        // Email notification
        $admin_email = Vesho_CRM_Database::get_setting( 'notify_email', get_option( 'admin_email' ) );
        wp_mail( $admin_email, 'Uus teenusepäring: ' . $data['name'], print_r( $data, true ) );
        return rest_ensure_response( array( 'id' => $wpdb->insert_id, 'message' => 'Päring edukalt saadetud' ) );
    }

    public static function update_request( $request ) {
        global $wpdb;
        $id     = absint( $request['id'] );
        $params = $request->get_json_params() ?: $request->get_params();
        $data   = array();
        if ( isset( $params['status'] ) )      $data['status']      = sanitize_text_field( $params['status'] );
        if ( isset( $params['admin_notes'] ) ) $data['admin_notes'] = sanitize_textarea_field( $params['admin_notes'] );
        if ( $data ) $wpdb->update( $wpdb->prefix . 'vesho_guest_requests', $data, array( 'id' => $id ) );
        return rest_ensure_response( array( 'updated' => true ) );
    }

    // ── Stats ─────────────────────────────────────────────────────────────────

    public static function get_stats( $request ) {
        return rest_ensure_response( array(
            'total_clients'          => Vesho_CRM_Database::count_clients(),
            'pending_maintenances'   => Vesho_CRM_Database::count_pending_maintenances(),
            'unpaid_invoices'        => Vesho_CRM_Database::count_unpaid_invoices(),
            'unpaid_amount'          => Vesho_CRM_Database::sum_unpaid_invoices(),
        ) );
    }

    // ── Settings ──────────────────────────────────────────────────────────────

    public static function get_settings( $request ) {
        global $wpdb;
        $rows = $wpdb->get_results( "SELECT setting_key, setting_value FROM {$wpdb->prefix}vesho_settings" );
        $out  = array();
        // Only expose public settings without authentication
        $public_keys = array( 'company_name', 'company_phone', 'company_email', 'company_address', 'working_hours' );
        $is_admin    = current_user_can( 'manage_options' );
        foreach ( $rows as $row ) {
            if ( $is_admin || in_array( $row->setting_key, $public_keys ) ) {
                $out[ $row->setting_key ] = $row->setting_value;
            }
        }
        return rest_ensure_response( $out );
    }

    public static function update_settings( $request ) {
        $params = $request->get_json_params() ?: $request->get_params();
        $allowed = array( 'company_name', 'company_email', 'company_phone', 'company_address', 'company_reg', 'company_vat', 'working_hours', 'invoice_prefix', 'invoice_next_num', 'vat_rate', 'notify_email' );
        foreach ( $allowed as $key ) {
            if ( isset( $params[ $key ] ) ) {
                Vesho_CRM_Database::update_setting( $key, sanitize_text_field( $params[ $key ] ) );
            }
        }
        return rest_ensure_response( array( 'updated' => true ) );
    }
}
