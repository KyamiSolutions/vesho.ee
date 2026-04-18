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
        add_action( 'wp_ajax_vesho_add_maintenance_ajax',   array( __CLASS__, 'ajax_add_maintenance' ) );
        add_action( 'wp_ajax_vesho_get_client_devices',     array( __CLASS__, 'ajax_get_client_devices' ) );
        add_action( 'wp_ajax_vesho_postpone_maintenance',   array( __CLASS__, 'ajax_postpone_maintenance' ) );
        add_action( 'wp_ajax_vesho_create_credit_note',     array( __CLASS__, 'ajax_create_credit_note' ) );
        add_action( 'admin_post_vesho_generate_worker_barcode', array( __CLASS__, 'handle_generate_worker_barcode' ) );
        add_action( 'admin_post_vesho_export_invoices_csv',     array( __CLASS__, 'handle_export_invoices_csv' ) );
        add_action( 'admin_post_vesho_export_maintenances_csv', array( __CLASS__, 'handle_export_maintenances_csv' ) );
    }

    // ── Menu registration ──────────────────────────────────────────────────────

    public static function register_menus() {
        $icon = 'data:image/svg+xml;base64,' . base64_encode(
            '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="currentColor">'
            . '<path d="M16 4C16 4 6 14 6 20C6 25.5 10.5 30 16 30C21.5 30 26 25.5 26 20C26 14 16 4 16 4Z" fill="#00b4c8"/></svg>'
        );

        add_menu_page( 'Vesho CRM', 'Vesho CRM', 'manage_options', 'vesho-crm',
            array( __CLASS__, 'page_dashboard' ), $icon, 25 );

        // Dashboard (overrides duplicate parent label)
        add_submenu_page( 'vesho-crm', 'Töölaud', 'Töölaud', 'manage_options', 'vesho-crm', array( __CLASS__, 'page_dashboard' ) );
        add_submenu_page( 'vesho-crm', 'Meeldetuletused', 'Meeldetuletused', 'manage_options', 'vesho-crm-reminders', array( __CLASS__, 'page_reminders' ) );

        // ── KLIENDID ──
        add_submenu_page( 'vesho-crm', 'Kliendid', 'Kliendid', 'manage_options', 'vesho-crm-clients', array( __CLASS__, 'page_clients' ) );
        add_submenu_page( 'vesho-crm', 'Seadmed', 'Seadmed', 'manage_options', 'vesho-crm-devices', array( __CLASS__, 'page_devices' ) );
        add_submenu_page( 'vesho-crm', 'Päringud', 'Päringud', 'manage_options', 'vesho-crm-requests', array( __CLASS__, 'page_requests' ) );
        add_submenu_page( 'vesho-crm', 'Tugipiletid', 'Tugipiletid', 'manage_options', 'vesho-crm-tickets', array( __CLASS__, 'page_tickets' ) );

        // ── HOOLDUSED ──
        add_submenu_page( 'vesho-crm', 'Kalender', 'Kalender', 'manage_options', 'vesho-crm-calendar', array( __CLASS__, 'page_calendar' ) );
        add_submenu_page( 'vesho-crm', 'Marsruut', 'Marsruut', 'manage_options', 'vesho-crm-route', array( __CLASS__, 'page_route' ) );
        add_submenu_page( 'vesho-crm', 'Hooldused', 'Hooldused', 'manage_options', 'vesho-crm-maintenances', array( __CLASS__, 'page_maintenances' ) );

        // ── TÖÖ ──
        add_submenu_page( 'vesho-crm', 'Töötajad', 'Töötajad', 'manage_options', 'vesho-crm-workers', array( __CLASS__, 'page_workers' ) );
        add_submenu_page( 'vesho-crm', 'Töötunnid', 'Töötunnid', 'manage_options', 'vesho-crm-workhours', array( __CLASS__, 'page_workhours' ) );
        add_submenu_page( 'vesho-crm', 'Töökäsud', 'Töökäsud', 'manage_options', 'vesho-crm-workorders', array( __CLASS__, 'page_workorders' ) );

        // ── ARVELDUS ──
        add_submenu_page( 'vesho-crm', 'Arved', 'Arved', 'manage_options', 'vesho-crm-invoices', array( __CLASS__, 'page_invoices' ) );
        add_submenu_page( 'vesho-crm', 'Müügiraport', 'Müügiraport', 'manage_options', 'vesho-crm-sales', array( __CLASS__, 'page_sales' ) );
        add_submenu_page( 'vesho-crm', 'Tellimused', 'Tellimused', 'manage_options', 'vesho-crm-orders', array( __CLASS__, 'page_orders' ) );
        add_submenu_page( 'vesho-crm', 'Kampaaniad', 'Kampaaniad', 'manage_options', 'vesho-crm-campaigns', array( __CLASS__, 'page_campaigns' ) );

        // ── LADU ──
        add_submenu_page( 'vesho-crm', 'Ladu', 'Ladu', 'manage_options', 'vesho-crm-inventory', array( __CLASS__, 'page_inventory' ) );
        add_submenu_page( 'vesho-crm', 'Vastuvõtt', 'Vastuvõtt', 'manage_options', 'vesho-crm-receipts', array( __CLASS__, 'page_receipts' ) );
        add_submenu_page( 'vesho-crm', 'Hinnakiri', 'Hinnakiri', 'manage_options', 'vesho-crm-pricelist', array( __CLASS__, 'page_pricelist' ) );

        // ── MUU ──
        add_submenu_page( 'vesho-crm', 'Teenused', 'Teenused', 'manage_options', 'vesho-crm-services', array( __CLASS__, 'page_services' ) );
        add_submenu_page( 'vesho-crm', 'Tegevuslogi', 'Tegevuslogi', 'manage_options', 'vesho-crm-activity', array( __CLASS__, 'page_activity_log' ) );
        add_submenu_page( 'vesho-crm', 'Seaded', 'Seaded', 'manage_options', 'vesho-crm-settings', array( __CLASS__, 'page_settings' ) );
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
                            $inv_number = Vesho_CRM_Database::get_next_invoice_number();
                            $wpdb->insert( $wpdb->prefix . 'vesho_invoices', array(
                                'client_id'      => $dev_client->client_id,
                                'invoice_number' => $inv_number,
                                'status'         => 'draft',
                                'invoice_date'   => current_time('Y-m-d'),
                                'due_date'       => date('Y-m-d', strtotime('+14 days')),
                                'amount'         => $locked_price,
                                'maintenance_id' => $id,
                                'description'    => 'Automaatselt loodud hooldusest #' . $id,
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
        if ( $id ) { $wpdb->update($wpdb->prefix.'vesho_workorders',$data,array('id'=>$id)); $msg='updated'; }
        else { $data['created_at']=current_time('mysql'); $wpdb->insert($wpdb->prefix.'vesho_workorders',$data); $msg='added'; }
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
            'portal_title','notify_email','maintenance_reminder_days',
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
            'portal_show_services','portal_show_invoices','portal_show_support',
            'notify_new_request','notify_new_ticket','notify_invoice_paid','notify_new_client',
            'notify_maintenance_reminder','notify_low_stock','notify_worker_shift',
            'low_stock_alert','worker_reminder','cookie_banner','maintenance_mode','coming_soon_mode',
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
}
