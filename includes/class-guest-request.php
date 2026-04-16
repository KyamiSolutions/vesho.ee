<?php
/**
 * Vesho CRM – Guest Request class
 * Handles public service request form submissions.
 *
 * @package Vesho_CRM
 */

defined( 'ABSPATH' ) || exit;

class Vesho_CRM_Guest_Request {

    public static function init() {
        add_action( 'wp_ajax_nopriv_vesho_guest_request', array( __CLASS__, 'handle_ajax' ) );
        add_action( 'wp_ajax_vesho_guest_request',        array( __CLASS__, 'handle_ajax' ) );

        // Also hook the shortcode for embedding standalone form
        add_shortcode( 'vesho_request_form', array( __CLASS__, 'shortcode_form' ) );
    }

    /**
     * AJAX handler for guest service requests.
     */
    public static function handle_ajax() {
        // Verify nonce from vesho_nonce_field or nonce param
        $nonce = $_POST['nonce'] ?? $_POST['vesho_nonce_field'] ?? '';
        if ( ! wp_verify_nonce( $nonce, 'vesho_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Turvakontroll ebaõnnestus. Laadige leht uuesti.', 'vesho-crm' ) ) );
        }

        $name           = sanitize_text_field( $_POST['name'] ?? '' );
        $email          = sanitize_email( $_POST['email'] ?? '' );
        $phone          = sanitize_text_field( $_POST['phone'] ?? '' );
        $device_name    = sanitize_text_field( $_POST['device_name'] ?? '' );
        $service_type   = sanitize_text_field( $_POST['service_type'] ?? '' );
        $preferred_date = sanitize_text_field( $_POST['preferred_date'] ?? '' );
        $description    = sanitize_textarea_field( $_POST['description'] ?? '' );

        // Validate required fields
        if ( empty( $name ) ) {
            wp_send_json_error( array( 'message' => __( 'Nimi on kohustuslik', 'vesho-crm' ) ) );
        }
        if ( empty( $email ) || ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => __( 'Kehtiv e-posti aadress on kohustuslik', 'vesho-crm' ) ) );
        }

        // Validate date if provided
        $date_value = null;
        if ( ! empty( $preferred_date ) ) {
            $ts = strtotime( $preferred_date );
            if ( $ts && $ts > time() ) {
                $date_value = date( 'Y-m-d', $ts );
            }
        }

        global $wpdb;
        $table = $wpdb->prefix . 'vesho_guest_requests';

        // Check if table exists
        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) !== $table ) {
            // Table doesn't exist yet – run install
            Vesho_CRM_Database::install();
        }

        $result = $wpdb->insert( $table, array(
            'name'           => $name,
            'email'          => $email,
            'phone'          => $phone,
            'device_name'    => $device_name,
            'service_type'   => $service_type,
            'preferred_date' => $date_value,
            'description'    => $description,
            'status'         => 'new',
            'created_at'     => current_time( 'mysql' ),
        ) );

        if ( $result === false ) {
            wp_send_json_error( array(
                'message' => __( 'Andmebaasi viga. Palun proovige uuesti.', 'vesho-crm' ),
            ) );
        }

        // Send admin notification
        self::send_admin_notification( array(
            'name'           => $name,
            'email'          => $email,
            'phone'          => $phone,
            'device_name'    => $device_name,
            'service_type'   => $service_type,
            'preferred_date' => $preferred_date,
            'description'    => $description,
        ) );

        // Send confirmation to client
        self::send_client_confirmation( $name, $email, $service_type );

        wp_send_json_success( array(
            'message' => __( 'Päring edukalt saadetud! Võtame teiega ühendust 24 tunni jooksul.', 'vesho-crm' ),
        ) );
    }

    /**
     * Send notification email to admin.
     */
    private static function send_admin_notification( $data ) {
        $admin_email = Vesho_CRM_Database::get_setting( 'notify_email', get_option( 'admin_email' ) );
        $company     = Vesho_CRM_Database::get_setting( 'company_name', get_bloginfo( 'name' ) );

        $subject = sprintf( '[%s] Uus teenusepäring: %s', $company, $data['name'] );

        $message  = "Uus teenusepäring on laekunud veebilehelt:\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Nimi:             {$data['name']}\n";
        $message .= "E-posti aadress:  {$data['email']}\n";
        $message .= "Telefon:          {$data['phone']}\n";
        $message .= "Seade/toode:      {$data['device_name']}\n";
        $message .= "Teenuse tüüp:     {$data['service_type']}\n";
        $message .= "Soovitud kuupäev: {$data['preferred_date']}\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "Kirjeldus:\n{$data['description']}\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "Halda päringuuid: " . admin_url( 'admin.php?page=vesho-crm-requests' ) . "\n";

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            "Reply-To: {$data['name']} <{$data['email']}>",
        );

        wp_mail( $admin_email, $subject, $message, $headers );
    }

    /**
     * Send confirmation to the client.
     */
    private static function send_client_confirmation( $name, $email, $service_type ) {
        $company = Vesho_CRM_Database::get_setting( 'company_name', get_bloginfo( 'name' ) );
        $phone   = Vesho_CRM_Database::get_setting( 'company_phone', '' );
        $from    = Vesho_CRM_Database::get_setting( 'company_email', get_option( 'admin_email' ) );

        $subject = sprintf( '[%s] Päring vastu võetud', $company );

        $first_name = explode( ' ', trim( $name ) )[0];

        $message  = "Tere, {$first_name}!\n\n";
        $message .= "Täname teid päringu eest! Oleme selle kätte saanud ja võtame teiega ühendust 24 tunni jooksul.\n\n";
        if ( $service_type ) {
            $message .= "Soovitud teenus: {$service_type}\n\n";
        }
        $message .= "Kui teil on küsimusi, võtke meiega ühendust:\n";
        if ( $phone ) $message .= "Telefon: {$phone}\n";
        $message .= "E-post:  {$from}\n\n";
        $message .= "Parimate soovidega,\n{$company} meeskond";

        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            "From: {$company} <{$from}>",
        );

        wp_mail( $email, $subject, $message, $headers );
    }

    /**
     * Shortcode: [vesho_request_form]
     * Renders a standalone service request form.
     */
    public static function shortcode_form( $atts ) {
        $atts = shortcode_atts( array(
            'service' => '',
            'title'   => __( 'Küsi Pakkumist', 'vesho-crm' ),
        ), $atts, 'vesho_request_form' );

        global $wpdb;
        $services = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}vesho_services WHERE active = 1 ORDER BY sort_order ASC, id ASC"
        );

        ob_start();
        ?>
        <div class="vesho-request-form-wrap" style="max-width:600px;margin:0 auto;">
            <h3 style="margin-bottom:20px;"><?php echo esc_html( $atts['title'] ); ?></h3>
            <div id="vrf-success" style="display:none;background:#dcfce7;border:1px solid #bbf7d0;border-radius:6px;padding:16px;color:#166534;margin-bottom:16px;">
                ✅ <?php _e( 'Päring edukalt saadetud! Võtame teiega ühendust 24 tunni jooksul.', 'vesho-crm' ); ?>
            </div>
            <div id="vrf-error" style="display:none;background:#fee2e2;border:1px solid #fecaca;border-radius:6px;padding:16px;color:#991b1b;margin-bottom:16px;"></div>
            <form id="vesho-request-form-sc" novalidate>
                <?php wp_nonce_field( 'vesho_nonce', 'vesho_nonce_field' ); ?>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:5px;"><?php _e( 'Nimi', 'vesho-crm' ); ?> *</label>
                        <input type="text" name="name" required placeholder="<?php esc_attr_e( 'Teie nimi', 'vesho-crm' ); ?>"
                            style="width:100%;padding:10px 12px;border:1px solid #dce8ef;border-radius:4px;font-size:14px;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:5px;"><?php _e( 'E-post', 'vesho-crm' ); ?> *</label>
                        <input type="email" name="email" required placeholder="nimi@ettevote.ee"
                            style="width:100%;padding:10px 12px;border:1px solid #dce8ef;border-radius:4px;font-size:14px;box-sizing:border-box;">
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:5px;"><?php _e( 'Telefon', 'vesho-crm' ); ?></label>
                        <input type="tel" name="phone" placeholder="+372 5XXX XXXX"
                            style="width:100%;padding:10px 12px;border:1px solid #dce8ef;border-radius:4px;font-size:14px;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block;font-size:13px;font-weight:600;margin-bottom:5px;"><?php _e( 'Teenus', 'vesho-crm' ); ?></label>
                        <select name="service_type" style="width:100%;padding:10px 12px;border:1px solid #dce8ef;border-radius:4px;font-size:14px;box-sizing:border-box;">
                            <option value=""><?php _e( '— Vali teenus —', 'vesho-crm' ); ?></option>
                            <?php foreach ( $services as $svc ) : ?>
                                <option value="<?php echo esc_attr( $svc->name ); ?>" <?php selected( $atts['service'], $svc->name ); ?>>
                                    <?php echo esc_html( $svc->icon . ' ' . $svc->name ); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="Muu"><?php _e( 'Muu', 'vesho-crm' ); ?></option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:13px;font-weight:600;margin-bottom:5px;"><?php _e( 'Kirjeldus', 'vesho-crm' ); ?></label>
                    <textarea name="description" rows="4" placeholder="<?php esc_attr_e( 'Kirjeldage oma soovi...', 'vesho-crm' ); ?>"
                        style="width:100%;padding:10px 12px;border:1px solid #dce8ef;border-radius:4px;font-size:14px;box-sizing:border-box;resize:vertical;"></textarea>
                </div>
                <button type="submit"
                    style="background:#00b4c8;color:#fff;border:none;border-radius:4px;padding:12px 28px;font-size:13px;font-weight:700;letter-spacing:0.07em;text-transform:uppercase;cursor:pointer;width:100%;">
                    <?php _e( 'Saada Päring', 'vesho-crm' ); ?>
                </button>
            </form>
            <script>
            (function(){
                var form = document.getElementById('vesho-request-form-sc');
                if(!form) return;
                form.addEventListener('submit', function(e){
                    e.preventDefault();
                    var fd = new FormData(form);
                    fd.append('action', 'vesho_guest_request');
                    var errEl = document.getElementById('vrf-error');
                    var sucEl = document.getElementById('vrf-success');
                    var btn = form.querySelector('button[type="submit"]');
                    errEl.style.display = 'none';
                    btn.disabled = true; btn.textContent = '<?php echo esc_js( __( 'Saadan...', 'vesho-crm' ) ); ?>';
                    fetch('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {method:'POST', body:fd})
                    .then(function(r){return r.json();})
                    .then(function(d){
                        if(d.success){
                            form.style.display='none';
                            sucEl.style.display='block';
                        } else {
                            errEl.textContent = (d.data&&d.data.message)||'Viga';
                            errEl.style.display='block';
                            btn.disabled=false;
                            btn.textContent='<?php echo esc_js( __( 'Saada Päring', 'vesho-crm' ) ); ?>';
                        }
                    });
                });
            })();
            </script>
        </div>
        <?php
        return ob_get_clean();
    }
}
