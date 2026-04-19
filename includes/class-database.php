<?php
/**
 * Vesho CRM – Database class
 * Creates and manages all custom database tables.
 *
 * @package Vesho_CRM
 */

defined( 'ABSPATH' ) || exit;

class Vesho_CRM_Database {

    /**
     * Run on activation and version change. Creates tables that don't exist
     * and adds missing columns to existing tables (safe to run multiple times).
     */
    public static function install() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // ── clients ─────────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_clients (
            id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id            BIGINT(20)   DEFAULT NULL,
            name               VARCHAR(255) NOT NULL,
            email              VARCHAR(255) NOT NULL,
            phone              VARCHAR(50)  DEFAULT '',
            address            TEXT         DEFAULT '',
            client_type        VARCHAR(20)  DEFAULT 'eraisik',
            company            VARCHAR(255) DEFAULT '',
            reg_code           VARCHAR(50)  DEFAULT '',
            vat_number         VARCHAR(50)  DEFAULT '',
            password           VARCHAR(255) DEFAULT '',
            notes              TEXT         DEFAULT '',
            email_verified     TINYINT(1)   DEFAULT 0,
            email_verify_token VARCHAR(100) DEFAULT '',
            created_at         DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY   uq_client_email (email)
        ) $charset;" );

        // Add missing columns to existing installs (safe to run multiple times)
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}vesho_clients ADD COLUMN IF NOT EXISTS company VARCHAR(255) DEFAULT ''" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}vesho_clients ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) DEFAULT 0" );
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}vesho_clients ADD COLUMN IF NOT EXISTS email_verify_token VARCHAR(100) DEFAULT ''" );

        // Devices: service interval (months) for auto-scheduling next maintenance
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}vesho_devices ADD COLUMN IF NOT EXISTS service_interval INT UNSIGNED DEFAULT NULL COMMENT 'Hoolduse intervall kuudes'" );

        // ── devices ─────────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_devices (
            id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id     INT UNSIGNED NOT NULL,
            name          VARCHAR(255) NOT NULL,
            model         VARCHAR(255) DEFAULT '',
            serial_number VARCHAR(100) DEFAULT '',
            install_date  DATE         DEFAULT NULL,
            location      VARCHAR(255) DEFAULT '',
            notes         TEXT         DEFAULT '',
            created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_devices_client (client_id)
        ) $charset;" );

        // ── maintenances ─────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_maintenances (
            id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
            device_id      INT UNSIGNED NOT NULL,
            scheduled_date DATE         DEFAULT NULL,
            completed_date DATE         DEFAULT NULL,
            description    TEXT         DEFAULT '',
            status         VARCHAR(30)  DEFAULT 'scheduled',
            locked_price   DECIMAL(10,2) DEFAULT NULL,
            worker_id      INT UNSIGNED DEFAULT NULL,
            notes          TEXT         DEFAULT '',
            created_at     DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_main_device   (device_id),
            KEY idx_main_status   (status),
            KEY idx_main_sched    (scheduled_date)
        ) $charset;" );

        // ── invoices ─────────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_invoices (
            id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id      INT UNSIGNED NOT NULL,
            invoice_number VARCHAR(50)  NOT NULL,
            invoice_date   DATE         DEFAULT NULL,
            due_date       DATE         DEFAULT NULL,
            amount         DECIMAL(10,2) DEFAULT 0.00,
            status         VARCHAR(20)  DEFAULT 'unpaid',
            description    TEXT         DEFAULT '',
            pdf_path       VARCHAR(500) DEFAULT '',
            created_at     DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_inv_client (client_id),
            KEY idx_inv_status (status),
            KEY idx_inv_due    (due_date)
        ) $charset;" );

        // ── services ─────────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_services (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name        VARCHAR(255) NOT NULL,
            description TEXT         DEFAULT '',
            price       DECIMAL(10,2) DEFAULT 0.00,
            icon        VARCHAR(10)  DEFAULT '💧',
            price_unit  VARCHAR(50)  DEFAULT '',
            active      TINYINT(1)   DEFAULT 1,
            sort_order  INT          DEFAULT 0,
            created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;" );

        // ── workers ──────────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_workers (
            id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id       BIGINT(20)   DEFAULT NULL,
            name          VARCHAR(255) NOT NULL,
            email         VARCHAR(255) DEFAULT '',
            phone         VARCHAR(50)  DEFAULT '',
            role          VARCHAR(50)  DEFAULT 'technician',
            password      VARCHAR(255) DEFAULT '',
            active        TINYINT(1)   DEFAULT 1,
            barcode_token VARCHAR(64)  DEFAULT '',
            created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_worker_email (email)
        ) $charset;" );

        // ── orders ───────────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_orders (
            id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id    INT UNSIGNED NOT NULL,
            worker_id    INT UNSIGNED DEFAULT NULL,
            order_date   DATE         DEFAULT NULL,
            service_type VARCHAR(255) DEFAULT '',
            status       VARCHAR(30)  DEFAULT 'pending',
            total        DECIMAL(10,2) DEFAULT 0.00,
            notes        TEXT         DEFAULT '',
            created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_orders_client (client_id),
            KEY idx_orders_status (status)
        ) $charset;" );

        // ── settings ─────────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_settings (
            id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            setting_key   VARCHAR(100) NOT NULL,
            setting_value TEXT         DEFAULT '',
            updated_at    DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_setting_key (setting_key)
        ) $charset;" );

        // ── sessions ─────────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_sessions (
            id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id  INT UNSIGNED NOT NULL,
            token      VARCHAR(255) NOT NULL,
            expires_at DATETIME     NOT NULL,
            role       VARCHAR(20)  DEFAULT 'client',
            created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_session_token (token),
            KEY idx_session_client     (client_id)
        ) $charset;" );

        // ── guest_service_requests ───────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_guest_requests (
            id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name           VARCHAR(255) NOT NULL,
            email          VARCHAR(255) NOT NULL,
            phone          VARCHAR(50)  DEFAULT '',
            device_name    VARCHAR(255) DEFAULT '',
            service_type   VARCHAR(255) DEFAULT '',
            preferred_date DATE         DEFAULT NULL,
            description    TEXT         DEFAULT '',
            status         VARCHAR(30)  DEFAULT 'new',
            admin_notes    TEXT         DEFAULT '',
            created_at     DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_greq_status (status),
            KEY idx_greq_date   (created_at)
        ) $charset;" );

        // ── support_tickets ──────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_support_tickets (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id   INT UNSIGNED NOT NULL,
            subject     VARCHAR(255) NOT NULL,
            message     TEXT         DEFAULT '',
            status      VARCHAR(20)  DEFAULT 'open',
            priority    VARCHAR(20)  DEFAULT 'normal',
            reply       TEXT         DEFAULT '',
            created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
            updated_at  DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_ticket_client (client_id),
            KEY idx_ticket_status (status)
        ) $charset;" );

        // ── ticket_replies ───────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_ticket_replies (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            ticket_id   INT UNSIGNED NOT NULL,
            author      VARCHAR(100) DEFAULT 'admin',
            message     TEXT         NOT NULL,
            created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_tr_ticket (ticket_id)
        ) $charset;" );

        // ── campaigns ────────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_campaigns (
            id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name       VARCHAR(255) NOT NULL,
            subject    VARCHAR(255) DEFAULT '',
            body       TEXT         DEFAULT '',
            status     VARCHAR(20)  DEFAULT 'draft',
            sent_at    DATETIME     DEFAULT NULL,
            recipients INT          DEFAULT 0,
            created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;" );

        // ── inventory ────────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_inventory (
            id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name             VARCHAR(255) NOT NULL,
            sku              VARCHAR(100) DEFAULT '',
            category         VARCHAR(100) DEFAULT '',
            unit             VARCHAR(30)  DEFAULT 'tk',
            quantity         DECIMAL(10,3) DEFAULT 0,
            min_quantity     DECIMAL(10,3) DEFAULT NULL,
            purchase_price   DECIMAL(10,2) DEFAULT 0.00,
            sell_price       DECIMAL(10,2) DEFAULT 0.00,
            location         VARCHAR(255) DEFAULT '',
            supplier         VARCHAR(255) DEFAULT '',
            description      TEXT         DEFAULT '',
            archived         TINYINT(1)   DEFAULT 0,
            created_at       DATETIME     DEFAULT CURRENT_TIMESTAMP,
            updated_at       DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_inv_cat (category),
            KEY idx_inv_sku (sku)
        ) $charset;" );

        // ── stock_receipts ───────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_stock_receipts (
            id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
            receipt_num  VARCHAR(50)  NOT NULL DEFAULT '',
            supplier     VARCHAR(255) DEFAULT '',
            status       VARCHAR(20)  DEFAULT 'pending',
            total        DECIMAL(10,2) DEFAULT 0.00,
            notes        TEXT         DEFAULT '',
            received_at  DATETIME     DEFAULT NULL,
            created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;" );

        // ── stock_receipt_items ───────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_stock_receipt_items (
            id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
            receipt_id      INT UNSIGNED NOT NULL,
            inventory_id    INT UNSIGNED DEFAULT NULL,
            quantity        DECIMAL(10,3) DEFAULT 0,
            purchase_price  DECIMAL(10,2) DEFAULT 0.00,
            PRIMARY KEY (id),
            KEY idx_sri_receipt (receipt_id)
        ) $charset;" );

        // ── workorders ───────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_workorders (
            id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
            client_id      INT UNSIGNED NOT NULL,
            device_id      INT UNSIGNED DEFAULT NULL,
            worker_id      INT UNSIGNED DEFAULT NULL,
            title          VARCHAR(255) NOT NULL DEFAULT '',
            description    TEXT         DEFAULT '',
            status         VARCHAR(30)  DEFAULT 'open',
            priority       VARCHAR(20)  DEFAULT 'normal',
            scheduled_date DATE         DEFAULT NULL,
            completed_date DATE         DEFAULT NULL,
            price          DECIMAL(10,2) DEFAULT NULL,
            notes          TEXT         DEFAULT '',
            created_at     DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_wo_client (client_id),
            KEY idx_wo_worker (worker_id),
            KEY idx_wo_status (status)
        ) $charset;" );

        // ── work_hours ───────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_work_hours (
            id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            worker_id     INT UNSIGNED NOT NULL,
            workorder_id  INT UNSIGNED DEFAULT NULL,
            date          DATE         NOT NULL,
            hours         DECIMAL(5,2) NOT NULL DEFAULT 0,
            description   VARCHAR(255) DEFAULT '',
            start_time    DATETIME     DEFAULT NULL,
            end_time      DATETIME     DEFAULT NULL,
            created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_wh_worker (worker_id),
            KEY idx_wh_date   (date)
        ) $charset;" );

        // ── invoice_items ─────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_invoice_items (
            id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            invoice_id    INT UNSIGNED NOT NULL,
            description   VARCHAR(255) NOT NULL DEFAULT '',
            quantity      DECIMAL(10,3) DEFAULT 1,
            unit_price    DECIMAL(10,2) DEFAULT 0.00,
            vat_rate      DECIMAL(5,2)  DEFAULT 0.00,
            total         DECIMAL(10,2) DEFAULT 0.00,
            PRIMARY KEY (id),
            KEY idx_ii_invoice (invoice_id)
        ) $charset;" );

        // ── price_list ───────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_price_list (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name        VARCHAR(255) NOT NULL,
            category    VARCHAR(100) DEFAULT '',
            unit        VARCHAR(30)  DEFAULT 'tk',
            price       DECIMAL(10,2) DEFAULT 0.00,
            vat_rate    DECIMAL(5,2)  DEFAULT 24.00,
            description TEXT         DEFAULT '',
            active      TINYINT(1)   DEFAULT 1,
            sort_order  INT          DEFAULT 0,
            created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;" );

        // ── inventory_writeoffs ──────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_inventory_writeoffs (
            id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
            inventory_id INT UNSIGNED NOT NULL,
            qty          DECIMAL(10,3) NOT NULL DEFAULT 0,
            reason       VARCHAR(255) DEFAULT '',
            type         VARCHAR(20)  DEFAULT 'writeoff',
            user_name    VARCHAR(255) DEFAULT '',
            created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_iwo_inv  (inventory_id),
            KEY idx_iwo_date (created_at)
        ) $charset;" );

        // ── activity_log ──────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_activity_log (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id     BIGINT(20)   DEFAULT NULL,
            user_name   VARCHAR(255) DEFAULT '',
            action      VARCHAR(100) NOT NULL DEFAULT '',
            description TEXT         DEFAULT '',
            object_type VARCHAR(50)  DEFAULT '',
            object_id   INT UNSIGNED DEFAULT NULL,
            ip_address  VARCHAR(45)  DEFAULT '',
            created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_al_user   (user_id),
            KEY idx_al_action (action),
            KEY idx_al_date   (created_at)
        ) $charset;" );

        // ── stock_notifications ───────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_stock_notifications (
            id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
            inventory_id INT UNSIGNED NOT NULL,
            email        VARCHAR(255) NOT NULL DEFAULT '',
            sent         TINYINT(1)   NOT NULL DEFAULT 0,
            created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_notif (inventory_id, email),
            KEY idx_notif_inv  (inventory_id),
            KEY idx_notif_sent (sent)
        ) $charset;" );

        // ── shop_orders ───────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_shop_orders (
            id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_number     VARCHAR(30)  NOT NULL DEFAULT '',
            client_id        INT UNSIGNED DEFAULT NULL,
            guest_name       VARCHAR(255) DEFAULT '',
            guest_email      VARCHAR(255) DEFAULT '',
            guest_phone      VARCHAR(50)  DEFAULT '',
            shipping_name    VARCHAR(255) DEFAULT '',
            shipping_address TEXT         DEFAULT '',
            shipping_method  VARCHAR(30)  DEFAULT 'pickup',
            shipping_price   DECIMAL(10,2) DEFAULT 0.00,
            subtotal         DECIMAL(10,2) DEFAULT 0.00,
            discount_amount  DECIMAL(10,2) DEFAULT 0.00,
            total            DECIMAL(10,2) DEFAULT 0.00,
            status           VARCHAR(30)  DEFAULT 'new',
            worker_id        INT UNSIGNED DEFAULT NULL,
            tracking_number  VARCHAR(100) DEFAULT '',
            return_reason    TEXT         DEFAULT '',
            notes            TEXT         DEFAULT '',
            payment_method   VARCHAR(30)  DEFAULT '',
            payment_ref      VARCHAR(255) DEFAULT '',
            paid_at          DATETIME     DEFAULT NULL,
            refund_amount         DECIMAL(10,2) DEFAULT 0.00,
            refund_pending_amount DECIMAL(10,2) DEFAULT 0.00,
            created_at       DATETIME     DEFAULT CURRENT_TIMESTAMP,
            updated_at       DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_so_client (client_id),
            KEY idx_so_status (status),
            KEY idx_so_date   (created_at)
        ) $charset;" );

        // ── shop_order_items ──────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_shop_order_items (
            id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id         INT UNSIGNED NOT NULL,
            inventory_id     INT UNSIGNED DEFAULT NULL,
            name             VARCHAR(255) NOT NULL DEFAULT '',
            sku              VARCHAR(100) DEFAULT '',
            quantity         DECIMAL(10,3) DEFAULT 1,
            unit_price       DECIMAL(10,2) DEFAULT 0.00,
            discount_percent DECIMAL(5,2)  DEFAULT 0.00,
            total            DECIMAL(10,2) DEFAULT 0.00,
            picked           TINYINT(1)   DEFAULT 0,
            picked_qty       DECIMAL(10,3) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_soi_order (order_id)
        ) $charset;" );

        // ── portal_notices ────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_portal_notices (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title       VARCHAR(255) NOT NULL DEFAULT '',
            message     TEXT         DEFAULT '',
            type        VARCHAR(20)  DEFAULT 'info',
            target      VARCHAR(20)  DEFAULT 'both',
            starts_at   DATE         DEFAULT NULL,
            ends_at     DATE         DEFAULT NULL,
            active      TINYINT(1)   DEFAULT 1,
            created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;" );
        // migrate: add type column if missing
        $notice_cols = $wpdb->get_col( "DESCRIBE `{$wpdb->prefix}vesho_portal_notices`" ) ?: [];
        if ( ! in_array( 'type', $notice_cols ) ) {
            $wpdb->query( "ALTER TABLE `{$wpdb->prefix}vesho_portal_notices` ADD COLUMN `type` VARCHAR(20) DEFAULT 'info' AFTER `message`" );
        }

        // ── stock_counts ──────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_stock_counts (
            id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name       VARCHAR(255) NOT NULL,
            status     VARCHAR(20)  DEFAULT 'active',
            created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;" );

        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_stock_count_sections (
            id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
            stock_count_id INT UNSIGNED NOT NULL,
            name           VARCHAR(255) NOT NULL,
            status         VARCHAR(20)  DEFAULT 'pending',
            worker_id      BIGINT(20)   DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_scs_count (stock_count_id)
        ) $charset;" );

        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_stock_count_items (
            id             BIGINT(20) NOT NULL AUTO_INCREMENT,
            section_id     BIGINT(20)   DEFAULT NULL,
            inventory_id   BIGINT(20)   NOT NULL,
            name           VARCHAR(255) NOT NULL DEFAULT '',
            sku            VARCHAR(100) DEFAULT NULL,
            ean            VARCHAR(50)  DEFAULT NULL,
            unit           VARCHAR(20)  DEFAULT 'tk',
            category       VARCHAR(100) DEFAULT NULL,
            location       VARCHAR(100) DEFAULT NULL,
            expected_qty   DECIMAL(10,2) DEFAULT 0,
            counted_qty    DECIMAL(10,2) DEFAULT NULL,
            worker_counted TINYINT(1)   DEFAULT 0,
            worker_done    TINYINT(1)   DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_sci_section (section_id)
        ) $charset;" );

        // ── warehouse_locations ───────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_warehouse_locations (
            id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
            code     VARCHAR(50)  NOT NULL,
            ean      VARCHAR(20)  DEFAULT '',
            label    VARCHAR(100) DEFAULT '',
            PRIMARY KEY (id),
            UNIQUE KEY uq_wl_code (code)
        ) $charset;" );

        // ── credit_notes ─────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_credit_notes (
            id                 bigint(20)   NOT NULL AUTO_INCREMENT,
            credit_note_number varchar(30)  NOT NULL,
            invoice_id         bigint(20)   NOT NULL,
            client_id          bigint(20)   NOT NULL,
            amount             decimal(10,2) NOT NULL DEFAULT 0,
            reason             varchar(255) DEFAULT '',
            status             varchar(20)  DEFAULT 'issued',
            issued_date        date         DEFAULT NULL,
            created_at         datetime     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY invoice_id (invoice_id),
            KEY client_id (client_id)
        ) $charset;" );

        // ── workorder_photos ──────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_workorder_photos (
            id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
            workorder_id INT UNSIGNED NOT NULL,
            worker_id    INT UNSIGNED DEFAULT NULL,
            filename     VARCHAR(255) NOT NULL,
            caption      VARCHAR(255) DEFAULT '',
            created_at   DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_wo_photos_woid (workorder_id)
        ) $charset;" );

        // ── Safe column migrations ────────────────────────────────────────────
        self::maybe_add_column( "{$wpdb->prefix}vesho_devices", 'maintenance_interval', "TINYINT UNSIGNED DEFAULT NULL" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_workers",    'barcode_token',   "VARCHAR(64) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_workers",    'user_id',         'BIGINT(20) DEFAULT NULL' );
        self::maybe_add_column( "{$wpdb->prefix}vesho_workers",    'work_email',      "VARCHAR(255) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_workers",    'pin',             "VARCHAR(255) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_workers",    'show_on_website', "TINYINT(1) DEFAULT 0" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_workers",    'can_inventory',   "TINYINT(1) DEFAULT 0" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_receipt_items", 'actual_qty', "DECIMAL(10,3) DEFAULT NULL" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_receipt_items", 'location',   "VARCHAR(255) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_receipt_items", 'ean',        "VARCHAR(20) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_receipt_items", 'notes',      "TEXT DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_receipts",      'worker_id',  "INT UNSIGNED DEFAULT NULL" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_warehouse_locations", 'description', "VARCHAR(255) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_work_hours", 'start_time',     'DATETIME DEFAULT NULL' );
        self::maybe_add_column( "{$wpdb->prefix}vesho_work_hours", 'end_time',       'DATETIME DEFAULT NULL' );
        self::maybe_add_column( "{$wpdb->prefix}vesho_work_hours", 'clock_in_lat',   'DECIMAL(10,7) DEFAULT NULL' );
        self::maybe_add_column( "{$wpdb->prefix}vesho_work_hours", 'clock_in_lng',   'DECIMAL(10,7) DEFAULT NULL' );
        self::maybe_add_column( "{$wpdb->prefix}vesho_work_hours", 'clock_out_lat',  'DECIMAL(10,7) DEFAULT NULL' );
        self::maybe_add_column( "{$wpdb->prefix}vesho_work_hours", 'clock_out_lng',  'DECIMAL(10,7) DEFAULT NULL' );
        self::maybe_add_column( "{$wpdb->prefix}vesho_clients",    'user_id',       'BIGINT(20) DEFAULT NULL' );
        self::maybe_add_column( "{$wpdb->prefix}vesho_clients",    'company',       "VARCHAR(255) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_clients",    'client_type',   "VARCHAR(20) DEFAULT 'eraisik'" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_clients",    'reg_code',      "VARCHAR(50) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_clients",    'vat_number',         "VARCHAR(50) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_clients",    'email_verified',     "TINYINT(1) DEFAULT 1" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_clients",    'email_verify_token', "VARCHAR(64) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_invoices",     'km_included',    "TINYINT(1) DEFAULT 1" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_workorders",  'materials_used', "TEXT DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_maintenances",'reminder_sent',  "DATETIME DEFAULT NULL" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_inventory",   'ean',            "VARCHAR(20) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_inventory",   'used_quantity',  "DECIMAL(10,3) DEFAULT 0" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_inventory",   'shop_price',     "DECIMAL(10,2) DEFAULT NULL" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_inventory",   'shop_enabled',   "TINYINT(1) NOT NULL DEFAULT 0" );
        // price_list — public visibility + work-type binding
        self::maybe_add_column( "{$wpdb->prefix}vesho_price_list",  'visible_public', "TINYINT(1) DEFAULT 0" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_price_list",  'work_type',      "VARCHAR(50) DEFAULT ''" );
        // campaigns — pricing discount fields
        self::maybe_add_column( "{$wpdb->prefix}vesho_campaigns",   'target',            "VARCHAR(20) DEFAULT 'both'" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_campaigns",   'discount_percent',  "DECIMAL(5,2) DEFAULT 0" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_campaigns",   'free_shipping',     "TINYINT(1) DEFAULT 0" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_campaigns",   'valid_from',        "DATE DEFAULT NULL" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_campaigns",   'valid_until',       "DATE DEFAULT NULL" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_campaigns",   'visible_to_guests', "TINYINT(1) DEFAULT 1" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_campaigns",   'paused',            "TINYINT(1) DEFAULT 0" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_campaigns",   'maintenance_discount_percent', "DECIMAL(5,2) DEFAULT 0" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_campaigns",   'notes',             "TEXT DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_maintenances", 'client_id',     'INT UNSIGNED DEFAULT NULL' );
        self::maybe_add_column( "{$wpdb->prefix}vesho_maintenances", 'service_id',    'INT UNSIGNED DEFAULT NULL' );
        self::maybe_add_column( "{$wpdb->prefix}vesho_workorder_photos", 'maintenance_id', 'INT UNSIGNED DEFAULT NULL' );
        self::maybe_add_column( "{$wpdb->prefix}vesho_workorder_photos", 'photo_type',     "VARCHAR(20) DEFAULT 'other'" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_maintenances",     'worker_notes',   "TEXT DEFAULT ''" );
        $wpdb->query("ALTER TABLE {$wpdb->prefix}vesho_workorder_photos ADD INDEX IF NOT EXISTS idx_wo_photos_mid (maintenance_id)");
        self::maybe_add_column( "{$wpdb->prefix}vesho_invoices",     'maintenance_id', 'INT UNSIGNED DEFAULT NULL' );
        self::maybe_add_column( "{$wpdb->prefix}vesho_workorders",   'work_type',      "VARCHAR(50) DEFAULT ''" );
        // Payment tracking columns
        self::maybe_add_column( "{$wpdb->prefix}vesho_invoices", 'stripe_payment_id',         "VARCHAR(100) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_invoices", 'mc_transaction_id',          "VARCHAR(100) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_invoices", 'montonio_payment_reference', "VARCHAR(100) DEFAULT ''" );
        // Refund tracking columns
        self::maybe_add_column( "{$wpdb->prefix}vesho_invoices",    'refund_amount',   "DECIMAL(10,2) DEFAULT 0.00" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_shop_orders", 'stripe_payment_id',         "VARCHAR(100) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_shop_orders", 'mc_transaction_id',          "VARCHAR(100) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_shop_orders", 'montonio_payment_reference', "VARCHAR(100) DEFAULT ''" );
        // stock_count_sections — worker assignment
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_count_sections", 'worker_id', "BIGINT(20) DEFAULT NULL" );
        // stock_count_items — item detail columns
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_count_items", 'name',         "VARCHAR(255) NOT NULL DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_count_items", 'sku',          "VARCHAR(100) DEFAULT NULL" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_count_items", 'ean',          "VARCHAR(50) DEFAULT NULL" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_count_items", 'unit',         "VARCHAR(20) DEFAULT 'tk'" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_count_items", 'category',     "VARCHAR(100) DEFAULT NULL" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_count_items", 'location',     "VARCHAR(100) DEFAULT NULL" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_count_items", 'counted_qty',  "DECIMAL(10,2) DEFAULT NULL" );

        // Support ticket file attachment
        self::maybe_add_column( "{$wpdb->prefix}vesho_support_tickets", 'attachment_url', "VARCHAR(500) DEFAULT NULL" );

        // Guest request campaign lock
        self::maybe_add_column( "{$wpdb->prefix}vesho_guest_requests", 'campaign_name',             "VARCHAR(255) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_guest_requests", 'campaign_discount_percent', "DECIMAL(5,2) DEFAULT 0" );

        // Maintenance campaign lock — discount locked at booking time
        self::maybe_add_column( "{$wpdb->prefix}vesho_maintenances", 'campaign_discount', "DECIMAL(5,2) DEFAULT 0" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_maintenances", 'campaign_name',     "VARCHAR(100) DEFAULT ''" );

        // Stock receipts — status workflow + batch support
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_receipts", 'reference_number', "VARCHAR(100) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_receipts", 'receipt_date',     "DATE DEFAULT NULL" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_receipts", 'batch_ref',        "VARCHAR(100) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_receipts", 'worker_name',      "VARCHAR(100) DEFAULT ''" );
        // Stock receipt items — selling price + product info for new items
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_receipt_items", 'unit_price',     "DECIMAL(10,2) DEFAULT 0.00" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_receipt_items", 'selling_price',  "DECIMAL(10,2) DEFAULT NULL" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_receipt_items", 'product_name',   "VARCHAR(255) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_receipt_items", 'product_sku',    "VARCHAR(100) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_receipt_items", 'product_unit',   "VARCHAR(20) DEFAULT 'tk'" );
        // Fix: inventory_id was originally NOT NULL — allow NULL for new products not yet in inventory
        self::maybe_modify_column_to_nullable( "{$wpdb->prefix}vesho_stock_receipt_items", 'inventory_id', "INT UNSIGNED DEFAULT NULL" );

        // Fix: receipt_num was originally NOT NULL without default — give existing tables a default
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}vesho_stock_receipts MODIFY COLUMN receipt_num VARCHAR(50) NOT NULL DEFAULT ''" );

        // Worker PIN: originally VARCHAR(10) — bcrypt hashes need 255 chars
        $wpdb->query( "ALTER TABLE {$wpdb->prefix}vesho_workers MODIFY COLUMN pin VARCHAR(255) DEFAULT ''" );

        // Inventory product image
        self::maybe_add_column( "{$wpdb->prefix}vesho_inventory", 'image_url', "VARCHAR(500) DEFAULT ''" );

        // ── inventory_categories ─────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_inventory_categories (
            id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name       VARCHAR(100) NOT NULL,
            slug       VARCHAR(100) NOT NULL DEFAULT '',
            color      VARCHAR(7)   DEFAULT '#00b4c8',
            sort_order INT          DEFAULT 0,
            created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_inv_cat_slug (slug)
        ) $charset;" );
        // Seed from existing free-text categories (runs once; ignored if already present)
        $existing_names = $wpdb->get_col(
            "SELECT DISTINCT category FROM {$wpdb->prefix}vesho_inventory WHERE category!='' AND archived=0 ORDER BY category ASC"
        );
        foreach ( $existing_names as $cat_name ) {
            $slug = sanitize_title( $cat_name );
            $wpdb->query( $wpdb->prepare(
                "INSERT IGNORE INTO {$wpdb->prefix}vesho_inventory_categories (name, slug) VALUES (%s, %s)",
                $cat_name, $slug
            ) );
        }

        // Return request extra fields
        self::maybe_add_column( "{$wpdb->prefix}vesho_shop_orders", 'return_description',  "TEXT DEFAULT NULL" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_shop_orders", 'return_photo_url',    "VARCHAR(500) DEFAULT NULL" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_shop_orders", 'return_status',       "VARCHAR(20) DEFAULT NULL" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_shop_orders", 'return_requested_at', "DATETIME DEFAULT NULL" );

        // Client geocoordinates for route planning
        self::maybe_add_column( "{$wpdb->prefix}vesho_clients", 'lat', 'DECIMAL(10,7) DEFAULT NULL' );
        self::maybe_add_column( "{$wpdb->prefix}vesho_clients", 'lng', 'DECIMAL(10,7) DEFAULT NULL' );

        // Invoice type (for credit note distinction)
        self::maybe_add_column( "{$wpdb->prefix}vesho_invoices", 'invoice_type',        "VARCHAR(20) DEFAULT 'invoice'" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_invoices", 'original_invoice_id', "INT UNSIGNED DEFAULT NULL" );

        // Guest request locked price at booking time
        self::maybe_add_column( "{$wpdb->prefix}vesho_guest_requests", 'locked_price', "DECIMAL(10,2) DEFAULT NULL" );

        // Shop order fields for public cart/checkout
        self::maybe_add_column( "{$wpdb->prefix}vesho_shop_orders", 'client_name',    "VARCHAR(255) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_shop_orders", 'client_email',   "VARCHAR(255) DEFAULT ''" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_shop_orders", 'client_phone',   "VARCHAR(50) DEFAULT NULL" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_shop_orders", 'client_company', "VARCHAR(255) DEFAULT NULL" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_shop_orders", 'shipping_cost',         "DECIMAL(10,2) DEFAULT 0" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_shop_orders", 'refund_pending_amount', "DECIMAL(10,2) DEFAULT 0.00" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_shop_orders", 'packed_at', "DATETIME DEFAULT NULL" );

        self::maybe_add_column( "{$wpdb->prefix}vesho_inventory_writeoffs", 'order_id',     "INT UNSIGNED DEFAULT NULL" );
        self::maybe_add_column( "{$wpdb->prefix}vesho_inventory_writeoffs", 'order_number', "VARCHAR(30) DEFAULT NULL" );

        // ── suppliers ────────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_suppliers (
            id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
            name        VARCHAR(255) NOT NULL,
            contact     VARCHAR(255) DEFAULT '',
            email       VARCHAR(255) DEFAULT '',
            phone       VARCHAR(50)  DEFAULT '',
            address     TEXT         DEFAULT '',
            reg_code    VARCHAR(50)  DEFAULT '',
            vat_number  VARCHAR(50)  DEFAULT '',
            notes       TEXT         DEFAULT '',
            active      TINYINT(1)   DEFAULT 1,
            created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset;" );

        // ── purchase_orders ──────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_purchase_orders (
            id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
            supplier_id     INT UNSIGNED DEFAULT NULL,
            order_number    VARCHAR(50)  DEFAULT '',
            order_date      DATE         DEFAULT NULL,
            expected_date   DATE         DEFAULT NULL,
            status          VARCHAR(20)  DEFAULT 'draft',
            total_amount    DECIMAL(10,2) DEFAULT 0.00,
            notes           TEXT         DEFAULT '',
            created_at      DATETIME     DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_po_supplier (supplier_id),
            KEY idx_po_status   (status)
        ) $charset;" );

        // ── purchase_order_items ─────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_purchase_order_items (
            id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
            purchase_order_id   INT UNSIGNED NOT NULL,
            inventory_id        INT UNSIGNED DEFAULT NULL,
            product_name        VARCHAR(255) DEFAULT '',
            sku                 VARCHAR(100) DEFAULT '',
            quantity            DECIMAL(10,3) DEFAULT 0,
            unit_price          DECIMAL(10,2) DEFAULT 0.00,
            line_total          DECIMAL(10,2) DEFAULT 0.00,
            received_qty        DECIMAL(10,3) DEFAULT 0,
            PRIMARY KEY (id),
            KEY idx_poi_order (purchase_order_id)
        ) $charset;" );

        // Link stock receipts to purchase orders
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_receipts", 'purchase_order_id', 'INT UNSIGNED DEFAULT NULL' );
        self::maybe_add_column( "{$wpdb->prefix}vesho_stock_receipts", 'supplier_id',       'INT UNSIGNED DEFAULT NULL' );

        // ── tasks ─────────────────────────────────────────────────────────────
        dbDelta( "CREATE TABLE {$wpdb->prefix}vesho_tasks (
            id                 INT UNSIGNED NOT NULL AUTO_INCREMENT,
            title              VARCHAR(255) NOT NULL,
            description        TEXT         DEFAULT '',
            status             VARCHAR(20)  DEFAULT 'open',
            priority           VARCHAR(20)  DEFAULT 'normal',
            assigned_worker_id INT UNSIGNED DEFAULT NULL,
            client_id          INT UNSIGNED DEFAULT NULL,
            due_date           DATE         DEFAULT NULL,
            created_by         VARCHAR(100) DEFAULT '',
            created_at         DATETIME     DEFAULT CURRENT_TIMESTAMP,
            completed_at       DATETIME     DEFAULT NULL,
            PRIMARY KEY (id),
            KEY idx_task_status (status),
            KEY idx_task_worker (assigned_worker_id),
            KEY idx_task_due    (due_date)
        ) $charset;" );

        // Ticket worker assignment
        self::maybe_add_column( "{$wpdb->prefix}vesho_support_tickets", 'assigned_worker_id', 'INT UNSIGNED DEFAULT NULL' );

        // ── Insert default settings if empty ─────────────────────────────────
        self::seed_default_settings();
    }

    /**
     * Add a column to a table if it doesn't already exist.
     */
    private static function maybe_add_column( $table, $column, $definition ) {
        global $wpdb;
        $cols = $wpdb->get_col( "DESCRIBE `$table`" );
        if ( $cols && ! in_array( $column, $cols, true ) ) {
            $wpdb->query( "ALTER TABLE `$table` ADD COLUMN `$column` $definition" );
        }
    }

    /**
     * Modify a column to allow NULL if it currently has NOT NULL constraint.
     */
    private static function maybe_modify_column_to_nullable( $table, $column, $definition ) {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT IS_NULLABLE FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = %s",
            $table, $column
        ) );
        if ( $row && strtoupper( $row->IS_NULLABLE ) === 'NO' ) {
            $wpdb->query( "ALTER TABLE `$table` MODIFY COLUMN `$column` $definition" );
        }
    }

    /**
     * Seed default settings rows.
     */
    private static function seed_default_settings() {
        global $wpdb;
        $table = $wpdb->prefix . 'vesho_settings';

        $defaults = array(
            'company_name'       => get_bloginfo( 'name' ) ?: 'Vesho OÜ',
            'company_email'      => get_option( 'admin_email' ) ?: 'info@vesho.ee',
            'company_phone'      => '+372 5XXX XXXX',
            'company_address'    => 'Tallinn, Eesti',
            'company_reg'        => '',
            'company_vat'        => '',
            'working_hours'      => 'E–R 9:00–18:00',
            'invoice_prefix'     => 'INV-',
            'invoice_next_num'   => '1001',
            'vat_rate'           => '24',
            'notify_email'       => get_option( 'admin_email' ) ?: '',
            'notify_new_request' => '1',
            'notify_new_ticket'  => '1',
            'notify_invoice_paid'=> '1',
            'notify_new_client'  => '1',
            'client_portal_notice'     => '',
            'worker_portal_notice'     => '',
            'maintenance_reminder_days'=> '3',
            'low_stock_alert'          => '1',
            'worker_reminder'          => '0',
        );

        foreach ( $defaults as $key => $value ) {
            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM $table WHERE setting_key = %s LIMIT 1",
                $key
            ) );
            if ( ! $existing ) {
                $wpdb->insert( $table, array(
                    'setting_key'   => $key,
                    'setting_value' => $value,
                ) );
            }
        }
    }

    /**
     * Get a setting value.
     */
    public static function get_setting( $key, $default = '' ) {
        global $wpdb;
        $table = $wpdb->prefix . 'vesho_settings';
        $val = $wpdb->get_var( $wpdb->prepare(
            "SELECT setting_value FROM $table WHERE setting_key = %s LIMIT 1",
            $key
        ) );
        return $val !== null ? $val : $default;
    }

    /**
     * Update a setting value.
     */
    public static function update_setting( $key, $value ) {
        global $wpdb;
        $table = $wpdb->prefix . 'vesho_settings';
        $existing = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $table WHERE setting_key = %s LIMIT 1",
            $key
        ) );
        if ( $existing ) {
            return $wpdb->update( $table, array( 'setting_value' => $value ), array( 'setting_key' => $key ) );
        } else {
            return $wpdb->insert( $table, array( 'setting_key' => $key, 'setting_value' => $value ) );
        }
    }

    /**
     * Get all clients with optional filter.
     */
    public static function get_clients( $args = array() ) {
        global $wpdb;
        $table  = $wpdb->prefix . 'vesho_clients';
        $limit  = isset( $args['limit'] )  ? absint( $args['limit'] )  : 50;
        $offset = isset( $args['offset'] ) ? absint( $args['offset'] ) : 0;
        $search = isset( $args['search'] ) ? sanitize_text_field( $args['search'] ) : '';
        $type   = isset( $args['type'] )   ? sanitize_text_field( $args['type'] )   : '';

        $where = array( '1=1' );
        $params = array();

        if ( $search ) {
            $where[] = '(name LIKE %s OR email LIKE %s OR phone LIKE %s)';
            $like    = '%' . $wpdb->esc_like( $search ) . '%';
            $params  = array_merge( $params, array( $like, $like, $like ) );
        }
        if ( $type ) {
            $where[]  = 'client_type = %s';
            $params[] = $type;
        }

        $sql = "SELECT * FROM $table WHERE " . implode( ' AND ', $where ) . " ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;

        return $wpdb->get_results( $wpdb->prepare( $sql, $params ) );
    }

    /**
     * Get client by ID.
     */
    public static function get_client( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_clients WHERE id = %d LIMIT 1",
            $id
        ) );
    }

    /**
     * Get client by email.
     */
    public static function get_client_by_email( $email ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_clients WHERE email = %s LIMIT 1",
            sanitize_email( $email )
        ) );
    }

    /**
     * Count clients.
     */
    public static function count_clients() {
        global $wpdb;
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_clients" );
    }

    /**
     * Count pending maintenances.
     */
    public static function count_pending_maintenances() {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_maintenances WHERE status = 'scheduled'"
        );
    }

    /**
     * Count unpaid invoices.
     */
    public static function count_unpaid_invoices() {
        global $wpdb;
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_invoices WHERE status IN ('unpaid', 'overdue')"
        );
    }

    /**
     * Sum unpaid invoice amounts.
     */
    public static function sum_unpaid_invoices() {
        global $wpdb;
        return (float) $wpdb->get_var(
            "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}vesho_invoices WHERE status IN ('unpaid', 'overdue')"
        );
    }

    /**
     * Get recent guest requests.
     */
    public static function get_recent_requests( $limit = 10 ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_guest_requests ORDER BY created_at DESC LIMIT %d",
            $limit
        ) );
    }

    /**
     * Get upcoming maintenances.
     */
    public static function get_upcoming_maintenances( $limit = 10 ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT m.*, d.name as device_name, c.name as client_name
             FROM {$wpdb->prefix}vesho_maintenances m
             JOIN {$wpdb->prefix}vesho_devices d ON m.device_id = d.id
             JOIN {$wpdb->prefix}vesho_clients c ON d.client_id = c.id
             WHERE m.status = 'scheduled' AND m.scheduled_date >= CURDATE()
             ORDER BY m.scheduled_date ASC
             LIMIT %d",
            $limit
        ) );
    }

    /**
     * Get all services.
     */
    public static function get_services( $active_only = false ) {
        global $wpdb;
        $table = $wpdb->prefix . 'vesho_services';
        $where = $active_only ? 'WHERE active = 1' : '';
        return $wpdb->get_results( "SELECT * FROM $table $where ORDER BY sort_order ASC, id ASC" );
    }

    /**
     * Get device with client info.
     */
    public static function get_devices_for_client( $client_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT d.*, COUNT(m.id) as maintenance_count
             FROM {$wpdb->prefix}vesho_devices d
             LEFT JOIN {$wpdb->prefix}vesho_maintenances m ON d.id = m.device_id
             WHERE d.client_id = %d
             GROUP BY d.id
             ORDER BY d.created_at DESC",
            $client_id
        ) );
    }

    /**
     * Get invoices for client.
     */
    public static function get_invoices_for_client( $client_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_invoices WHERE client_id = %d ORDER BY invoice_date DESC",
            $client_id
        ) );
    }

    /**
     * Get next invoice number.
     */
    public static function get_next_invoice_number() {
        $prefix = self::get_setting( 'invoice_prefix', 'INV-' );
        $next   = (int) self::get_setting( 'invoice_next_num', '1001' );
        self::update_setting( 'invoice_next_num', $next + 1 );
        return $prefix . str_pad( $next, 4, '0', STR_PAD_LEFT );
    }
}
