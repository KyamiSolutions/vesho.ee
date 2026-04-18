<?php
/**
 * Vesho CRM – Public Shortcodes
 * Used in Elementor HTML widgets for dynamic content
 */
defined( 'ABSPATH' ) || exit;

// ── [vesho_team_cards] ────────────────────────────────────────────────────────
add_shortcode( 'vesho_team_cards', function() {
    global $wpdb;
    $workers = $wpdb->get_results(
        "SELECT id, name, role FROM {$wpdb->prefix}vesho_workers WHERE active=1 AND show_on_website=1 ORDER BY id ASC LIMIT 8"
    );
    $role_labels = ['technician'=>'Tehnik','manager'=>'Juht','admin'=>'Administraator','sales'=>'Müügijuht','other'=>'Töötaja'];

    if ( empty($workers) ) return '';
    $use_labels = true;

    ob_start(); ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:20px">
    <?php foreach ( $workers as $w ) :
        $role = $use_labels ? ($role_labels[$w->role] ?? ucfirst($w->role)) : $w->role;
    ?>
        <div style="background:#f8fafc;border-radius:14px;padding:28px 20px;text-align:center">
            <div style="width:60px;height:60px;border-radius:50%;background:linear-gradient(135deg,#00b4c8,#0097aa);color:#fff;font-size:22px;font-weight:700;display:flex;align-items:center;justify-content:center;margin:0 auto 14px">
                <?php echo esc_html( strtoupper( mb_substr( $w->name, 0, 1 ) ) ); ?>
            </div>
            <div style="font-weight:700;color:#0d1f2d;font-size:15px"><?php echo esc_html( $w->name ); ?></div>
            <div style="color:#00b4c8;font-size:12px;font-weight:600;margin-top:4px"><?php echo esc_html( $role ); ?></div>
        </div>
    <?php endforeach; ?>
    </div>
    <?php return ob_get_clean();
} );

// ── [vesho_services_cards] ────────────────────────────────────────────────────
add_shortcode( 'vesho_services_cards', function() {
    global $wpdb;
    $services = $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}vesho_services WHERE active=1 ORDER BY sort_order ASC, id ASC LIMIT 12"
    );

    if ( empty($services) ) {
        $services = [
            (object)['name'=>'Hooldus & Remont','description'=>'Regulaarne veesüsteemide hooldus ja kiirete rikete kõrvaldamine.','icon'=>'🔧','price'=>null,'price_unit'=>''],
            (object)['name'=>'Filtreerimine','description'=>'Vee puhastus ja filtreerislahendused kodudele ning äridele.','icon'=>'💧','price'=>null,'price_unit'=>''],
            (object)['name'=>'Paigaldus','description'=>'Uute veesüsteemide ja seadmete professionaalne paigaldus.','icon'=>'⚙️','price'=>null,'price_unit'=>''],
            (object)['name'=>'Puurkaev','description'=>'Puurkaevude puurimine, hooldus ja remont.','icon'=>'🌊','price'=>null,'price_unit'=>''],
            (object)['name'=>'Pumbasüsteemid','description'=>'Pumbasüsteemide paigaldus, hooldus ja remont.','icon'=>'⚡','price'=>null,'price_unit'=>''],
            (object)['name'=>'Konsultatsioon','description'=>'Tasuta esmane konsultatsioon veesüsteemide küsimustes.','icon'=>'📋','price'=>null,'price_unit'=>''],
        ];
    }

    // Active maintenance campaign
    $today_svc = date( 'Y-m-d' );
    $svc_campaign = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vesho_campaigns
         WHERE paused=0 AND valid_from<=%s AND valid_until>=%s AND (target='hooldus' OR target='both')
         ORDER BY maintenance_discount_percent DESC LIMIT 1",
        $today_svc, $today_svc
    ) );
    $svc_disc = $svc_campaign ? (float) $svc_campaign->maintenance_discount_percent : 0;

    $modal_url = '';
    ob_start();

    if ( $svc_disc > 0 ) : ?>
    <div style="background:linear-gradient(135deg,#e0f7fa,#b2ebf2);border:1.5px solid #00b4c8;border-radius:14px;padding:16px 22px;margin-bottom:24px;display:flex;align-items:center;gap:16px;flex-wrap:wrap">
        <div style="background:#00b4c8;color:#fff;font-size:18px;font-weight:900;padding:8px 16px;border-radius:10px;white-space:nowrap">-<?php echo (int) $svc_disc; ?>%</div>
        <div>
            <div style="font-weight:700;color:#0d1f2d;font-size:16px">🏷️ <?php echo esc_html( $svc_campaign->name ); ?></div>
            <?php if ( $svc_campaign->description ) : ?><div style="color:#4b6174;font-size:13px;margin-top:3px"><?php echo esc_html( $svc_campaign->description ); ?></div><?php endif; ?>
            <?php if ( $svc_campaign->valid_until ) : ?><div style="color:#00b4c8;font-size:12px;font-weight:600;margin-top:3px">Kehtib kuni <?php echo esc_html( date_i18n( 'd.m.Y', strtotime( $svc_campaign->valid_until ) ) ); ?></div><?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:24px">
    <?php foreach ( $services as $svc ) :
        $svc_orig  = ! empty( $svc->price ) ? (float) $svc->price : null;
        $svc_discounted = ( $svc_orig !== null && $svc_disc > 0 ) ? round( $svc_orig * ( 1 - $svc_disc / 100 ), 2 ) : null;
    ?>
        <div style="background:#fff;border-radius:16px;padding:28px;box-shadow:0 2px 16px rgba(13,31,45,.07);display:flex;flex-direction:column;gap:12px;border-top:3px solid #00b4c8">
            <div style="font-size:36px;line-height:1"><?php echo esc_html( $svc->icon ?? '💧' ); ?></div>
            <h3 style="font-size:17px;font-weight:700;color:#0d1f2d;margin:0"><?php echo esc_html( $svc->name ); ?></h3>
            <p style="color:#4b6174;font-size:14px;line-height:1.65;margin:0;flex:1"><?php echo esc_html( $svc->description ?? '' ); ?></p>
            <?php if ( $svc_orig !== null ) : ?>
            <div style="font-size:13px;color:#6b8599">
                <?php if ( $svc_discounted !== null ) : ?>
                    alates <span style="text-decoration:line-through;color:#9ca3af"><?php echo number_format( $svc_orig, 2, ',', ' ' ); ?> €</span>
                    <strong style="color:#00b4c8;font-size:16px"><?php echo number_format( $svc_discounted, 2, ',', ' ' ); ?> €</strong>
                    <?php if ( $svc->price_unit ) echo '/ ' . esc_html( $svc->price_unit ); ?>
                <?php else : ?>
                    alates <strong style="color:#0d1f2d;font-size:16px"><?php echo number_format( $svc_orig, 2, ',', ' ' ); ?> €</strong>
                    <?php if ( $svc->price_unit ) echo '/ ' . esc_html( $svc->price_unit ); ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <button aria-controls="service-modal"
                    data-service="<?php echo esc_attr( $svc->name ); ?>"
                    style="padding:11px;background:#00b4c8;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:700;cursor:pointer;margin-top:auto">
                Küsi Pakkumist
            </button>
        </div>
    <?php endforeach; ?>
    </div>
    <?php return ob_get_clean();
} );

// ── [vesho_news_grid count="6"] ───────────────────────────────────────────────
add_shortcode( 'vesho_news_grid', function( $atts ) {
    $atts = shortcode_atts( ['count' => 6], $atts );
    $q = new WP_Query([
        'post_type' => 'post', 'post_status' => 'publish',
        'posts_per_page' => (int)$atts['count'], 'orderby' => 'date', 'order' => 'DESC',
    ]);

    if ( ! $q->have_posts() ) {
        return '<div style="text-align:center;padding:48px 20px;color:#6b8599"><div style="font-size:48px;margin-bottom:16px">📰</div><p>Uudiseid pole veel lisatud.</p></div>';
    }

    ob_start(); ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:28px">
    <?php while ( $q->have_posts() ) : $q->the_post(); ?>
        <article style="background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 2px 16px rgba(13,31,45,.07)">
            <?php if ( has_post_thumbnail() ) : ?>
                <a href="<?php the_permalink(); ?>" style="display:block;height:180px;overflow:hidden">
                    <?php the_post_thumbnail('medium', ['style'=>'width:100%;height:180px;object-fit:cover;display:block']); ?>
                </a>
            <?php else : ?>
                <div style="height:100px;background:linear-gradient(135deg,#e0f7fa,#b2ebf2);display:flex;align-items:center;justify-content:center;font-size:32px">💧</div>
            <?php endif; ?>
            <div style="padding:20px">
                <div style="font-size:12px;color:#6b8599;margin-bottom:8px"><?php echo get_the_date('j. F Y'); ?></div>
                <h3 style="font-size:16px;font-weight:700;color:#0d1f2d;margin:0 0 8px;line-height:1.4">
                    <a href="<?php the_permalink(); ?>" style="color:inherit;text-decoration:none"><?php the_title(); ?></a>
                </h3>
                <p style="color:#4b6174;font-size:13px;line-height:1.6;margin:0 0 14px"><?php echo wp_trim_words(get_the_excerpt(), 18, '…'); ?></p>
                <a href="<?php the_permalink(); ?>" style="font-size:13px;font-weight:600;color:#00b4c8;text-decoration:none">Loe edasi →</a>
            </div>
        </article>
    <?php endwhile; wp_reset_postdata(); ?>
    </div>
    <?php return ob_get_clean();
} );

// ── [vesho_shop_grid] ─────────────────────────────────────────────────────────

// Helper: get cart items with DB prices
function vesho_get_cart_items() {
    global $wpdb;
    if ( session_status() === PHP_SESSION_NONE ) session_start();
    $cart = $_SESSION['vesho_cart'] ?? [];
    if ( empty( $cart ) ) return [];
    $ids          = array_map( 'absint', array_keys( $cart ) );
    $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
    $products     = $wpdb->get_results( $wpdb->prepare(
        "SELECT id, name, shop_price, unit, quantity FROM {$wpdb->prefix}vesho_inventory WHERE id IN ($placeholders)",
        ...$ids
    ) );
    $result = [];
    foreach ( $products as $p ) {
        $result[] = [
            'product' => $p,
            'qty'     => $cart[ $p->id ],
            'total'   => $p->shop_price * $cart[ $p->id ],
        ];
    }
    return $result;
}

add_shortcode( 'vesho_shop_grid', function () {
    global $wpdb;

    // ── Session bootstrap ──────────────────────────────────────────────────
    if ( session_status() === PHP_SESSION_NONE ) session_start();

    // ── Handle POST actions ────────────────────────────────────────────────
    $post_action = sanitize_key( $_POST['shop_action'] ?? '' );
    if ( $post_action === 'add_to_cart' && ! empty( $_POST['pid'] ) ) {
        $pid = absint( $_POST['pid'] );
        $qty = max( 1, absint( $_POST['qty'] ?? 1 ) );
        if ( ! isset( $_SESSION['vesho_cart'] ) ) $_SESSION['vesho_cart'] = [];
        $_SESSION['vesho_cart'][ $pid ] = ( $_SESSION['vesho_cart'][ $pid ] ?? 0 ) + $qty;
    } elseif ( $post_action === 'update_cart' ) {
        // Remove buttons: vesho_remove[pid]
        foreach ( $_POST['vesho_remove'] ?? [] as $pid => $v ) {
            unset( $_SESSION['vesho_cart'][ absint( $pid ) ] );
        }
        // Quantity updates: vesho_cart_qty[pid]
        foreach ( $_POST['vesho_cart_qty'] ?? [] as $pid => $qty ) {
            $pid = absint( $pid );
            $qty = absint( $qty );
            if ( ! isset( $_POST['vesho_remove'][ $pid ] ) ) {
                if ( $qty <= 0 ) unset( $_SESSION['vesho_cart'][ $pid ] );
                else $_SESSION['vesho_cart'][ $pid ] = $qty;
            }
        }
    } elseif ( $post_action === 'clear_cart' ) {
        unset( $_SESSION['vesho_cart'] );
    }

    // ── Handle payment return: Maksekeskus ────────────────────────────────
    if ( ! empty( $_GET['vesho_shop_mc_return'] ) && ! empty( $_GET['order_id'] ) ) {
        $order_id   = absint( $_GET['order_id'] );
        $shop_id    = get_option( 'vesho_mc_shop_id', '' );
        $secret_key = get_option( 'vesho_mc_secret_key', '' );
        $sandbox    = get_option( 'vesho_mc_sandbox', '1' ) === '1';
        $order      = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}vesho_shop_orders WHERE id=%d", $order_id ) );
        if ( $order && $shop_id && $secret_key && $order->mc_transaction_id ) {
            $base = $sandbox ? 'https://api.test.maksekeskus.ee/v1' : 'https://api.maksekeskus.ee/v1';
            $ver  = wp_remote_get( "$base/transactions/{$order->mc_transaction_id}/", [
                'headers' => [ 'Authorization' => 'Basic ' . base64_encode( "$shop_id:$secret_key" ) ],
            ] );
            $vd = json_decode( wp_remote_retrieve_body( $ver ), true );
            if ( ! empty( $vd['status'] ) && in_array( $vd['status'], [ 'COMPLETED', 'PAID' ], true ) ) {
                $wpdb->update( $wpdb->prefix . 'vesho_shop_orders', [ 'status' => 'paid', 'paid_at' => current_time( 'mysql' ) ], [ 'id' => $order_id ] );
            }
        }
        wp_safe_redirect( add_query_arg( 'shop_view', 'success', get_permalink() ) );
        exit;
    }

    // ── Handle payment return: Montonio ───────────────────────────────────
    if ( ! empty( $_GET['vesho_shop_montonio_return'] ) && ! empty( $_GET['order_id'] ) ) {
        $order_id = absint( $_GET['order_id'] );
        $wpdb->update( $wpdb->prefix . 'vesho_shop_orders', [ 'status' => 'paid', 'paid_at' => current_time( 'mysql' ) ], [ 'id' => $order_id ] );
        wp_safe_redirect( add_query_arg( 'shop_view', 'success', get_permalink() ) );
        exit;
    }

    $shop_view = sanitize_key( $_GET['shop_view'] ?? '' );
    $page_url  = get_permalink();
    $ajax_url  = admin_url( 'admin-ajax.php' );
    $cart_nonce = wp_create_nonce( 'vesho_cart_nonce' );

    ob_start();

    // ══════════════════════════════════════════════════════════════════════
    // VIEW: SUCCESS
    // ══════════════════════════════════════════════════════════════════════
    if ( $shop_view === 'success' ) {
        $order_id = absint( $_SESSION['vesho_last_order_id'] ?? 0 );
        $order    = $order_id ? $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}vesho_shop_orders WHERE id=%d", $order_id ) ) : null;
        ?>
        <div style="text-align:center;padding:60px 20px">
          <div style="font-size:64px">✅</div>
          <h2 style="color:#0d1f2d;margin:16px 0 8px">Tellimus edastatud!</h2>
          <p style="color:#4b6174">Tellimus <strong>#<?php echo $order ? esc_html( $order->order_number ) : ''; ?></strong> on vastu võetud.</p>
          <?php if ( $order && $order->client_email ) : ?>
          <p style="color:#4b6174">Saadame kinnituse e-postile <strong><?php echo esc_html( $order->client_email ); ?></strong></p>
          <?php endif; ?>
          <a href="<?php echo esc_url( $page_url ); ?>" style="display:inline-block;margin-top:20px;padding:12px 28px;background:#00b4c8;color:#fff;border-radius:8px;text-decoration:none;font-weight:700">← Tagasi poodi</a>
        </div>
        <?php
        return ob_get_clean();
    }

    // ══════════════════════════════════════════════════════════════════════
    // VIEW: CANCEL
    // ══════════════════════════════════════════════════════════════════════
    if ( $shop_view === 'cancel' ) {
        ?>
        <div style="text-align:center;padding:60px 20px">
          <div style="font-size:64px">❌</div>
          <h2 style="color:#0d1f2d;margin:16px 0 8px">Makse tühistatud</h2>
          <p style="color:#4b6174">Teie tellimust ei töödeldud. Ostukorv on alles.</p>
          <a href="<?php echo esc_url( add_query_arg( 'shop_view', 'cart', $page_url ) ); ?>" style="display:inline-block;margin-top:20px;padding:12px 28px;background:#00b4c8;color:#fff;border-radius:8px;text-decoration:none;font-weight:700">← Tagasi korvi</a>
        </div>
        <?php
        return ob_get_clean();
    }

    // ══════════════════════════════════════════════════════════════════════
    // VIEW: CART
    // ══════════════════════════════════════════════════════════════════════
    if ( $shop_view === 'cart' ) {
        $cart_items = vesho_get_cart_items();
        $subtotal   = array_sum( array_column( $cart_items, 'total' ) );

        // Active campaign for cart view
        $today_cart   = date( 'Y-m-d' );
        $is_cli_cart  = ! empty( $_SESSION['vesho_client_id'] );
        $cart_campaign = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_campaigns
             WHERE paused=0 AND valid_from<=%s AND valid_until>=%s AND (target='epood' OR target='both')
             ORDER BY discount_percent DESC LIMIT 1",
            $today_cart, $today_cart
        ) );
        if ( $cart_campaign && ! $is_cli_cart && ! $cart_campaign->visible_to_guests ) {
            $cart_campaign = null;
        }
        $sess_cam_cart = $_SESSION['vesho_cart_campaign'] ?? null;
        if ( $sess_cam_cart && ( ! $cart_campaign || $sess_cam_cart['discount_percent'] > $cart_campaign->discount_percent ) ) {
            $cart_disc_pct  = (float) $sess_cam_cart['discount_percent'];
            $cart_disc_name = $sess_cam_cart['name'] . ' (lukustatud)';
        } elseif ( $cart_campaign ) {
            $cart_disc_pct  = (float) $cart_campaign->discount_percent;
            $cart_disc_name = $cart_campaign->name;
        } else {
            $cart_disc_pct  = 0;
            $cart_disc_name = '';
        }
        $cart_disc_amount = $cart_disc_pct > 0 ? round( $subtotal * $cart_disc_pct / 100, 2 ) : 0;
        $cart_total_after = round( $subtotal - $cart_disc_amount, 2 );
        ?>
        <h2 style="font-size:22px;font-weight:700;color:#0d1f2d;margin:0 0 24px">Ostukorv</h2>
        <?php if ( empty( $cart_items ) ) : ?>
        <div style="text-align:center;padding:48px 20px;color:#6b8599">
            <div style="font-size:48px;margin-bottom:16px">🛒</div>
            <p>Teie ostukorv on tühi.</p>
            <a href="<?php echo esc_url( $page_url ); ?>" style="display:inline-block;margin-top:12px;padding:10px 24px;background:#00b4c8;color:#fff;border-radius:8px;text-decoration:none;font-weight:600">← Tagasi poodi</a>
        </div>
        <?php else : ?>
        <form method="POST">
        <input type="hidden" name="shop_action" value="update_cart">
        <table style="width:100%;border-collapse:collapse;margin-bottom:24px">
            <thead>
                <tr style="border-bottom:2px solid #e5edf4">
                    <th style="text-align:left;padding:10px 0;font-size:13px;color:#6b8599;font-weight:600">TOODE</th>
                    <th style="text-align:center;padding:10px 0;font-size:13px;color:#6b8599;font-weight:600">KOGUS</th>
                    <th style="text-align:right;padding:10px 0;font-size:13px;color:#6b8599;font-weight:600">HIND</th>
                    <th style="text-align:right;padding:10px 0;font-size:13px;color:#6b8599;font-weight:600">KOKKU</th>
                    <th style="padding:10px 0"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ( $cart_items as $ci ) : $p = $ci['product']; ?>
                <tr style="border-bottom:1px solid #f0f4f8">
                    <td style="padding:14px 0">
                        <div style="font-weight:600;color:#0d1f2d"><?php echo esc_html( $p->name ); ?></div>
                        <div style="font-size:12px;color:#6b8599"><?php echo esc_html( $p->unit ?? 'tk' ); ?></div>
                    </td>
                    <td style="text-align:center;padding:14px 8px">
                        <input type="number" name="vesho_cart_qty[<?php echo $p->id; ?>]" value="<?php echo (int) $ci['qty']; ?>" min="1" max="<?php echo (int) $p->quantity; ?>" style="width:60px;padding:6px;border:1.5px solid #d0dce8;border-radius:6px;text-align:center;font-size:14px">
                    </td>
                    <td style="text-align:right;padding:14px 8px;font-size:14px;color:#4b6174"><?php echo number_format( $p->shop_price, 2, ',', ' ' ); ?> €</td>
                    <td style="text-align:right;padding:14px 8px;font-weight:700;color:#0d1f2d"><?php echo number_format( $ci['total'], 2, ',', ' ' ); ?> €</td>
                    <td style="padding:14px 0 14px 12px">
                        <button type="submit" name="vesho_remove[<?php echo $p->id; ?>]" value="1" style="background:none;border:none;color:#9ca3af;font-size:18px;cursor:pointer;padding:0" title="Eemalda">×</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align:right;padding:16px 8px 0;font-size:15px;color:#6b8599">Vahesumma:</td>
                    <td style="text-align:right;padding:16px 8px 0;font-size:<?php echo $cart_disc_pct > 0 ? '15px;color:#6b8599' : '18px;font-weight:800;color:#0d1f2d'; ?>"><?php echo number_format( $subtotal, 2, ',', ' ' ); ?> €</td>
                    <td></td>
                </tr>
                <?php if ( $cart_disc_pct > 0 ) : ?>
                <tr>
                    <td colspan="3" style="text-align:right;padding:4px 8px;font-size:13px;color:#059669">🏷️ <?php echo esc_html( $cart_disc_name ); ?> (-<?php echo (int) $cart_disc_pct; ?>%):</td>
                    <td style="text-align:right;padding:4px 8px;font-size:13px;font-weight:700;color:#059669">-<?php echo number_format( $cart_disc_amount, 2, ',', ' ' ); ?> €</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="3" style="text-align:right;padding:4px 8px;font-size:17px;font-weight:800;color:#0d1f2d;border-top:2px solid #e5edf4">Kokku:</td>
                    <td style="text-align:right;padding:4px 8px;font-size:17px;font-weight:800;color:#0d1f2d;border-top:2px solid #e5edf4"><?php echo number_format( $cart_total_after, 2, ',', ' ' ); ?> €</td>
                    <td style="border-top:2px solid #e5edf4"></td>
                </tr>
                <?php endif; ?>
            </tfoot>
        </table>
        <div style="display:flex;gap:12px;justify-content:space-between;flex-wrap:wrap">
            <a href="<?php echo esc_url( $page_url ); ?>" style="padding:11px 22px;border:1.5px solid #d0dce8;border-radius:8px;text-decoration:none;font-weight:600;color:#4b6174;font-size:14px">← Jätka ostlemist</a>
            <a href="<?php echo esc_url( add_query_arg( 'shop_view', 'checkout', $page_url ) ); ?>" style="padding:11px 28px;background:#00b4c8;color:#fff;border-radius:8px;text-decoration:none;font-weight:700;font-size:14px">Vormista tellimus →</a>
        </div>
        </form>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }

    // ══════════════════════════════════════════════════════════════════════
    // VIEW: CHECKOUT
    // ══════════════════════════════════════════════════════════════════════
    if ( $shop_view === 'checkout' ) {
        $cart_items = vesho_get_cart_items();
        if ( empty( $cart_items ) ) {
            wp_safe_redirect( $page_url );
            exit;
        }
        $subtotal = array_sum( array_column( $cart_items, 'total' ) );

        $stripe_enabled   = get_option( 'vesho_stripe_enabled', '0' ) === '1';
        $mc_enabled       = get_option( 'vesho_mc_enabled', '0' ) === '1';
        $montonio_enabled = get_option( 'vesho_montonio_enabled', '0' ) === '1';
        $stripe_pub       = get_option( 'vesho_stripe_pub_key', '' );

        $shipping_options = [
            'omniva'  => [ 'label' => 'Omniva pakiautomaat', 'cost' => 3.99 ],
            'dpd'     => [ 'label' => 'DPD pakipunkt',       'cost' => 3.99 ],
            'courier' => [ 'label' => 'Kullerteenused',      'cost' => 6.99 ],
            'pickup'  => [ 'label' => 'Järeletulemine',      'cost' => 0.00 ],
        ];
        ?>
        <h2 style="font-size:22px;font-weight:700;color:#0d1f2d;margin:0 0 28px">Tellimuse vormistamine</h2>
        <div style="display:grid;grid-template-columns:1fr 340px;gap:32px;align-items:start" id="vesho-checkout-wrap">

          <!-- LEFT: form -->
          <div>
            <div id="vesho-checkout-msg" style="display:none;padding:12px 16px;border-radius:8px;font-size:14px;margin-bottom:16px"></div>

            <div style="background:#fff;border-radius:14px;padding:24px;box-shadow:0 2px 12px rgba(13,31,45,.07);margin-bottom:20px">
              <h3 style="font-size:15px;font-weight:700;color:#0d1f2d;margin:0 0 16px">Kontaktandmed</h3>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div style="grid-column:1/-1"><label style="display:block;font-size:13px;font-weight:600;margin-bottom:5px">Nimi *</label>
                  <input type="text" id="co-name" required style="width:100%;padding:10px 12px;border:1.5px solid #d0dce8;border-radius:8px;font-size:14px;box-sizing:border-box"></div>
                <div><label style="display:block;font-size:13px;font-weight:600;margin-bottom:5px">Telefon *</label>
                  <input type="tel" id="co-phone" required style="width:100%;padding:10px 12px;border:1.5px solid #d0dce8;border-radius:8px;font-size:14px;box-sizing:border-box"></div>
                <div><label style="display:block;font-size:13px;font-weight:600;margin-bottom:5px">E-post *</label>
                  <input type="email" id="co-email" required style="width:100%;padding:10px 12px;border:1.5px solid #d0dce8;border-radius:8px;font-size:14px;box-sizing:border-box"></div>
                <div><label style="display:block;font-size:13px;font-weight:600;margin-bottom:5px">Firma</label>
                  <input type="text" id="co-company" style="width:100%;padding:10px 12px;border:1.5px solid #d0dce8;border-radius:8px;font-size:14px;box-sizing:border-box"></div>
                <div><label style="display:block;font-size:13px;font-weight:600;margin-bottom:5px">Registrikood</label>
                  <input type="text" id="co-regnr" style="width:100%;padding:10px 12px;border:1.5px solid #d0dce8;border-radius:8px;font-size:14px;box-sizing:border-box"></div>
              </div>
            </div>

            <div style="background:#fff;border-radius:14px;padding:24px;box-shadow:0 2px 12px rgba(13,31,45,.07);margin-bottom:20px">
              <h3 style="font-size:15px;font-weight:700;color:#0d1f2d;margin:0 0 16px">Tarneaadress</h3>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div style="grid-column:1/-1"><label style="display:block;font-size:13px;font-weight:600;margin-bottom:5px">Aadress *</label>
                  <input type="text" id="co-address" required style="width:100%;padding:10px 12px;border:1.5px solid #d0dce8;border-radius:8px;font-size:14px;box-sizing:border-box"></div>
                <div><label style="display:block;font-size:13px;font-weight:600;margin-bottom:5px">Linn *</label>
                  <input type="text" id="co-city" required style="width:100%;padding:10px 12px;border:1.5px solid #d0dce8;border-radius:8px;font-size:14px;box-sizing:border-box"></div>
                <div><label style="display:block;font-size:13px;font-weight:600;margin-bottom:5px">Indeks *</label>
                  <input type="text" id="co-postcode" required style="width:100%;padding:10px 12px;border:1.5px solid #d0dce8;border-radius:8px;font-size:14px;box-sizing:border-box"></div>
                <div><label style="display:block;font-size:13px;font-weight:600;margin-bottom:5px">Riik</label>
                  <input type="text" id="co-country" value="Eesti" style="width:100%;padding:10px 12px;border:1.5px solid #d0dce8;border-radius:8px;font-size:14px;box-sizing:border-box"></div>
              </div>
            </div>

            <div style="background:#fff;border-radius:14px;padding:24px;box-shadow:0 2px 12px rgba(13,31,45,.07);margin-bottom:20px">
              <h3 style="font-size:15px;font-weight:700;color:#0d1f2d;margin:0 0 16px">Tarneviis</h3>
              <?php foreach ( $shipping_options as $key => $opt ) : ?>
              <label style="display:flex;align-items:center;gap:10px;padding:12px;border:1.5px solid #e5edf4;border-radius:8px;cursor:pointer;margin-bottom:8px;transition:border-color .15s" onclick="veshoSelectShipping(this,'<?php echo $key; ?>')">
                <input type="radio" name="shipping_method" value="<?php echo $key; ?>" <?php echo $key === 'omniva' ? 'checked' : ''; ?> style="accent-color:#00b4c8">
                <span style="flex:1;font-size:14px;font-weight:600;color:#0d1f2d"><?php echo esc_html( $opt['label'] ); ?></span>
                <span style="font-size:14px;font-weight:700;color:#00b4c8"><?php echo $opt['cost'] > 0 ? number_format( $opt['cost'], 2, ',', ' ' ) . ' €' : 'Tasuta'; ?></span>
              </label>
              <?php endforeach; ?>
            </div>

            <?php if ( $stripe_enabled || $mc_enabled || $montonio_enabled ) : ?>
            <div style="background:#fff;border-radius:14px;padding:24px;box-shadow:0 2px 12px rgba(13,31,45,.07);margin-bottom:20px">
              <h3 style="font-size:15px;font-weight:700;color:#0d1f2d;margin:0 0 16px">Makseviis</h3>
              <?php if ( $stripe_enabled ) : ?>
              <label style="display:flex;align-items:center;gap:10px;padding:12px;border:1.5px solid #e5edf4;border-radius:8px;cursor:pointer;margin-bottom:8px">
                <input type="radio" name="payment_method" value="stripe" checked style="accent-color:#00b4c8">
                <span style="font-size:14px;font-weight:600;color:#0d1f2d">💳 Pangakaart (Stripe)</span>
              </label>
              <?php endif; ?>
              <?php if ( $mc_enabled ) : ?>
              <label style="display:flex;align-items:center;gap:10px;padding:12px;border:1.5px solid #e5edf4;border-radius:8px;cursor:pointer;margin-bottom:8px">
                <input type="radio" name="payment_method" value="mc" <?php echo ! $stripe_enabled ? 'checked' : ''; ?> style="accent-color:#00b4c8">
                <span style="font-size:14px;font-weight:600;color:#0d1f2d">🏦 Maksekeskus</span>
              </label>
              <?php endif; ?>
              <?php if ( $montonio_enabled ) : ?>
              <label style="display:flex;align-items:center;gap:10px;padding:12px;border:1.5px solid #e5edf4;border-radius:8px;cursor:pointer;margin-bottom:8px">
                <input type="radio" name="payment_method" value="montonio" <?php echo ( ! $stripe_enabled && ! $mc_enabled ) ? 'checked' : ''; ?> style="accent-color:#00b4c8">
                <span style="font-size:14px;font-weight:600;color:#0d1f2d">🏦 Montonio</span>
              </label>
              <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Stripe card element placeholder -->
            <?php if ( $stripe_enabled && $stripe_pub ) : ?>
            <div id="vesho-stripe-card-wrap" style="background:#fff;border-radius:14px;padding:24px;box-shadow:0 2px 12px rgba(13,31,45,.07);margin-bottom:20px">
              <h3 style="font-size:15px;font-weight:700;color:#0d1f2d;margin:0 0 16px">Kaardi andmed</h3>
              <div id="vesho-stripe-card" style="padding:12px;border:1.5px solid #d0dce8;border-radius:8px"></div>
              <div id="vesho-stripe-errors" style="color:#dc2626;font-size:13px;margin-top:8px"></div>
            </div>
            <?php endif; ?>

            <button id="vesho-place-order-btn" type="button" onclick="veshoPlaceOrder()" style="width:100%;padding:14px;background:#00b4c8;color:#fff;border:none;border-radius:10px;font-size:16px;font-weight:700;cursor:pointer">
              Kinnita tellimus ja maksa
            </button>
            <p style="text-align:center;margin-top:10px">
              <a href="<?php echo esc_url( add_query_arg( 'shop_view', 'cart', $page_url ) ); ?>" style="font-size:13px;color:#6b8599;text-decoration:none">← Tagasi korvi</a>
            </p>
          </div>

          <!-- RIGHT: order summary -->
          <div style="background:#fff;border-radius:14px;padding:24px;box-shadow:0 2px 12px rgba(13,31,45,.07);position:sticky;top:20px">
            <h3 style="font-size:15px;font-weight:700;color:#0d1f2d;margin:0 0 16px">Tellimuse kokkuvõte</h3>
            <?php foreach ( $cart_items as $ci ) : $p = $ci['product']; ?>
            <div style="display:flex;justify-content:space-between;font-size:13px;padding:6px 0;border-bottom:1px solid #f0f4f8">
              <span style="color:#4b6174"><?php echo esc_html( $p->name ); ?> ×<?php echo (int) $ci['qty']; ?></span>
              <span style="font-weight:600"><?php echo number_format( $ci['total'], 2, ',', ' ' ); ?> €</span>
            </div>
            <?php endforeach; ?>
            <div style="display:flex;justify-content:space-between;font-size:13px;padding:10px 0;color:#4b6174">
              <span>Tarne:</span>
              <span id="vesho-shipping-display">3,99 €</span>
            </div>
            <div style="display:flex;justify-content:space-between;font-size:17px;font-weight:800;padding-top:10px;border-top:2px solid #e5edf4;color:#0d1f2d">
              <span>Kokku:</span>
              <span id="vesho-total-display"><?php echo number_format( $subtotal + 3.99, 2, ',', ' ' ); ?> €</span>
            </div>
          </div>
        </div>

        <script>
        var veshoCartNonce='<?php echo esc_js( $cart_nonce ); ?>';
        var ajaxurl='<?php echo esc_js( $ajax_url ); ?>';
        var veshoSubtotal=<?php echo json_encode( round( $subtotal, 2 ) ); ?>;
        var veshoShippingCosts=<?php echo json_encode( array_map( fn($o) => $o['cost'], $shipping_options ) ); ?>;
        var veshoStripeEnabled=<?php echo $stripe_enabled && $stripe_pub ? 'true' : 'false'; ?>;
        var veshoStripePubKey=<?php echo json_encode( $stripe_pub ); ?>;

        function veshoSelectShipping(lbl, key) {
          var cost = veshoShippingCosts[key] || 0;
          document.getElementById('vesho-shipping-display').textContent = cost > 0 ? cost.toFixed(2).replace('.',',')+' €' : 'Tasuta';
          var total = veshoSubtotal + cost;
          document.getElementById('vesho-total-display').textContent = total.toFixed(2).replace('.',',')+' €';
        }
        // Init shipping display
        veshoSelectShipping(null, 'omniva');

        var stripe = null, cardElement = null;
        if (veshoStripeEnabled && veshoStripePubKey) {
          var sc = document.createElement('script'); sc.src='https://js.stripe.com/v3/'; sc.onload=function(){
            stripe = Stripe(veshoStripePubKey);
            var elements = stripe.elements();
            cardElement = elements.create('card', {style:{base:{fontSize:'15px',color:'#0d1f2d'}}});
            cardElement.mount('#vesho-stripe-card');
          }; document.head.appendChild(sc);
        }

        function veshoShowMsg(msg, ok) {
          var el = document.getElementById('vesho-checkout-msg');
          el.style.display = 'block';
          el.style.background = ok ? '#ecfdf5' : '#fef2f2';
          el.style.color = ok ? '#065f46' : '#991b1b';
          el.textContent = msg;
          el.scrollIntoView({behavior:'smooth',block:'center'});
        }

        function veshoPlaceOrder() {
          var name = document.getElementById('co-name').value.trim();
          var phone = document.getElementById('co-phone').value.trim();
          var email = document.getElementById('co-email').value.trim();
          var address = document.getElementById('co-address').value.trim();
          var city = document.getElementById('co-city').value.trim();
          var postcode = document.getElementById('co-postcode').value.trim();
          if (!name||!phone||!email||!address||!city||!postcode) {
            veshoShowMsg('Palun täitke kõik kohustuslikud väljad (*).', false); return;
          }
          var shipping_method = document.querySelector('input[name="shipping_method"]:checked')?.value || 'omniva';
          var payment_method  = document.querySelector('input[name="payment_method"]:checked')?.value || 'stripe';
          var btn = document.getElementById('vesho-place-order-btn');
          btn.disabled=true; btn.textContent='Töötlen…';

          var fd = new FormData();
          fd.append('action','vesho_shop_place_order');
          fd.append('nonce', veshoCartNonce);
          fd.append('name', name); fd.append('phone', phone); fd.append('email', email);
          fd.append('company', document.getElementById('co-company').value);
          fd.append('address', address); fd.append('city', city); fd.append('postcode', postcode);
          fd.append('shipping_method', shipping_method);
          fd.append('payment_method', payment_method);

          fetch(ajaxurl,{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
            if (!d.success) { veshoShowMsg(d.data||'Viga tellimuse koostamisel.', false); btn.disabled=false; btn.textContent='Kinnita tellimus ja maksa'; return; }
            var data = d.data;
            if (data.method === 'stripe') {
              stripe.confirmCardPayment(data.client_secret, {payment_method:{card:cardElement}}).then(function(res){
                if (res.error) { veshoShowMsg(res.error.message, false); btn.disabled=false; btn.textContent='Kinnita tellimus ja maksa'; }
                else { window.location.href = '<?php echo esc_js( add_query_arg( 'shop_view', 'success', $page_url ) ); ?>'; }
              });
            } else if (data.redirect_url) {
              window.location.href = data.redirect_url;
            } else {
              window.location.href = '<?php echo esc_js( add_query_arg( 'shop_view', 'success', $page_url ) ); ?>';
            }
          }).catch(()=>{ veshoShowMsg('Ühenduse viga.', false); btn.disabled=false; btn.textContent='Kinnita tellimus ja maksa'; });
        }
        </script>
        <?php
        return ob_get_clean();
    }

    // ══════════════════════════════════════════════════════════════════════
    // VIEW: GRID (default)
    // ══════════════════════════════════════════════════════════════════════
    $search   = isset( $_GET['s'] )   ? sanitize_text_field( $_GET['s'] )   : '';
    $category = isset( $_GET['cat'] ) ? sanitize_text_field( $_GET['cat'] ) : '';

    $where = "archived=0 AND shop_price > 0";
    if ( $search )   $where .= $wpdb->prepare( " AND (name LIKE %s OR sku LIKE %s)", '%' . $wpdb->esc_like( $search ) . '%', '%' . $wpdb->esc_like( $search ) . '%' );
    if ( $category ) $where .= $wpdb->prepare( " AND category=%s", $category );

    $items = $wpdb->get_results(
        "SELECT id, name, sku, category, unit, shop_price, description, quantity
         FROM {$wpdb->prefix}vesho_inventory WHERE $where ORDER BY category ASC, name ASC LIMIT 200"
    );
    $categories = $wpdb->get_col(
        "SELECT DISTINCT category FROM {$wpdb->prefix}vesho_inventory WHERE archived=0 AND shop_price>0 AND category!='' ORDER BY category ASC"
    );

    $cart_count = array_sum( $_SESSION['vesho_cart'] ?? [] );

    // ── Active campaign ────────────────────────────────────────────────────
    $today_grid    = date( 'Y-m-d' );
    $is_client     = ! empty( $_SESSION['vesho_client_id'] );
    $grid_campaign = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vesho_campaigns
         WHERE paused=0 AND valid_from<=%s AND valid_until>=%s AND (target='epood' OR target='both')
         ORDER BY discount_percent DESC LIMIT 1",
        $today_grid, $today_grid
    ) );
    if ( $grid_campaign && ! $is_client && ! $grid_campaign->visible_to_guests ) {
        $grid_campaign = null;
    }
    // Also check session-locked campaign (persists after campaign expires)
    $sess_cam_grid = $_SESSION['vesho_cart_campaign'] ?? null;
    if ( $sess_cam_grid && ( ! $grid_campaign || $sess_cam_grid['discount_percent'] > $grid_campaign->discount_percent ) ) {
        $eff_discount = (float) $sess_cam_grid['discount_percent'];
        $eff_name     = $sess_cam_grid['name'];
        $eff_locked   = true;
    } elseif ( $grid_campaign ) {
        $eff_discount = (float) $grid_campaign->discount_percent;
        $eff_name     = $grid_campaign->name;
        $eff_locked   = false;
    } else {
        $eff_discount = 0;
        $eff_name     = '';
        $eff_locked   = false;
    }

    // ── Campaign banner ────────────────────────────────────────────────────
    if ( $eff_discount > 0 ) {
        $badge_text = $eff_locked ? $eff_name . ' (lukustatud)' : $eff_name;
        $disc_disp  = '-' . (int) $eff_discount . '%';
        echo '<div style="background:linear-gradient(135deg,#e0f7fa,#b2ebf2);border:1.5px solid #00b4c8;border-radius:14px;padding:16px 22px;margin-bottom:24px;display:flex;align-items:center;gap:16px;flex-wrap:wrap">';
        echo '<div style="background:#00b4c8;color:#fff;font-size:18px;font-weight:900;padding:8px 16px;border-radius:10px;white-space:nowrap">' . esc_html( $disc_disp ) . '</div>';
        echo '<div>';
        echo '<div style="font-weight:700;color:#0d1f2d;font-size:16px">🏷️ ' . esc_html( $badge_text ) . '</div>';
        if ( $grid_campaign && $grid_campaign->description ) {
            echo '<div style="color:#4b6174;font-size:13px;margin-top:3px">' . esc_html( $grid_campaign->description ) . '</div>';
        }
        if ( $grid_campaign && $grid_campaign->valid_until ) {
            echo '<div style="color:#00b4c8;font-size:12px;font-weight:600;margin-top:3px">Kehtib kuni ' . esc_html( date_i18n( 'd.m.Y', strtotime( $grid_campaign->valid_until ) ) ) . '</div>';
        }
        echo '</div>';
        echo '</div>';
    }

    // Filter bar
    echo '<div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-bottom:28px">';
    echo '<form method="GET" style="display:flex;gap:8px;flex:1;min-width:200px;max-width:380px">';
    if ( $category ) echo '<input type="hidden" name="cat" value="' . esc_attr( $category ) . '">';
    echo '<input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="Otsi toodet…" style="flex:1;padding:10px 14px;border:1.5px solid #d0dce8;border-radius:8px;font-size:14px">';
    echo '<button type="submit" style="padding:10px 18px;background:#00b4c8;color:#fff;border:none;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer">Otsi</button>';
    echo '</form>';

    if ( ! empty( $categories ) ) {
        echo '<div style="display:flex;gap:8px;flex-wrap:wrap">';
        $all_active = ! $category ? 'background:#00b4c8;color:#fff' : 'background:#f0f4f8;color:#4b6174';
        echo '<a href="' . esc_url( $page_url ) . ( $search ? '?s=' . urlencode( $search ) : '' ) . '" style="padding:7px 14px;border-radius:20px;font-size:13px;font-weight:600;text-decoration:none;' . $all_active . '">Kõik</a>';
        foreach ( $categories as $cat ) {
            $href   = $page_url . '?cat=' . urlencode( $cat ) . ( $search ? '&s=' . urlencode( $search ) : '' );
            $active = ( $category === $cat ) ? 'background:#00b4c8;color:#fff' : 'background:#f0f4f8;color:#4b6174';
            echo '<a href="' . esc_url( $href ) . '" style="padding:7px 14px;border-radius:20px;font-size:13px;font-weight:600;text-decoration:none;' . $active . '">' . esc_html( $cat ) . '</a>';
        }
        echo '</div>';
    }
    echo '</div>';

    if ( empty( $items ) ) {
        echo '<div style="text-align:center;padding:60px 20px"><div style="font-size:48px;margin-bottom:16px">🔧</div>';
        echo '<p style="color:#6b8599">' . ( $search || $category ? 'Tooteid ei leitud.' : 'Pood on varsti avatud.' ) . '</p></div>';
        return ob_get_clean();
    }

    echo '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:22px">';
    foreach ( $items as $item ) :
        $in_stock    = $item->quantity > 0;
        $orig_price  = (float) $item->shop_price;
        $disc_price  = $eff_discount > 0 ? round( $orig_price * ( 1 - $eff_discount / 100 ), 2 ) : null;
        $savings     = $disc_price !== null ? round( $orig_price - $disc_price, 2 ) : 0;

        echo '<div style="background:#fff;border-radius:14px;padding:24px;box-shadow:0 2px 12px rgba(13,31,45,.07);display:flex;flex-direction:column;gap:10px;position:relative">';
        // Badges
        if ( $disc_price !== null ) {
            echo '<span style="position:absolute;top:12px;left:12px;background:#00b4c8;color:#fff;font-size:11px;font-weight:800;padding:3px 9px;border-radius:20px">-' . (int) $eff_discount . '%</span>';
        }
        if ( ! $in_stock ) echo '<span style="position:absolute;top:12px;right:12px;background:#fee2e2;color:#b91c1c;font-size:10px;font-weight:700;padding:3px 8px;border-radius:20px">Otsas</span>';
        echo '<div style="width:56px;height:56px;background:linear-gradient(135deg,#e0f7fa,#b2ebf2);border-radius:12px;display:flex;align-items:center;justify-content:center;margin-top:' . ( $disc_price !== null ? '14px' : '0' ) . '">';
        echo '<svg width="28" height="28" viewBox="0 0 32 32" fill="none"><path d="M16 4C16 4 6 14 6 20C6 25.5228 10.4772 30 16 30C21.5228 30 26 25.5228 26 20C26 14 16 4 16 4Z" fill="#00b4c8" opacity=".7"/></svg></div>';
        if ( $item->category ) echo '<span style="font-size:11px;font-weight:600;color:#00b4c8;text-transform:uppercase">' . esc_html( $item->category ) . '</span>';
        echo '<h3 style="font-size:15px;font-weight:700;color:#0d1f2d;margin:0;line-height:1.35">' . esc_html( $item->name ) . '</h3>';
        if ( $item->sku ) echo '<span style="font-size:11px;color:#6b8599;font-family:monospace">SKU: ' . esc_html( $item->sku ) . '</span>';
        if ( $item->description ) echo '<p style="font-size:13px;color:#4b6174;line-height:1.55;margin:0">' . esc_html( wp_trim_words( $item->description, 18, '…' ) ) . '</p>';
        echo '<div style="display:flex;align-items:center;justify-content:space-between;margin-top:auto;padding-top:8px;border-top:1px solid #f0f4f8">';
        echo '<div>';
        if ( $disc_price !== null ) {
            echo '<div style="font-size:13px;color:#9ca3af;text-decoration:line-through">' . number_format( $orig_price, 2, ',', ' ' ) . ' €</div>';
            echo '<div style="font-size:20px;font-weight:800;color:#00b4c8">' . number_format( $disc_price, 2, ',', ' ' ) . ' €</div>';
            echo '<div style="font-size:11px;color:#059669;font-weight:600">Säästad ' . number_format( $savings, 2, ',', ' ' ) . ' €</div>';
        } else {
            echo '<div style="font-size:20px;font-weight:800;color:#0d1f2d">' . number_format( $orig_price, 2, ',', ' ' ) . ' €</div>';
            echo '<div style="font-size:11px;color:#6b8599">/ tükk</div>';
        }
        echo '</div>';
        $btn_bg  = $in_stock ? '#00b4c8' : '#9ca3af';
        $btn_cur = $in_stock ? 'pointer' : 'not-allowed';
        $disabled = $in_stock ? '' : 'disabled';
        echo '<div style="display:flex;flex-direction:column;gap:6px;align-items:flex-end">';
        echo '<input type="number" id="qty_' . $item->id . '" value="1" min="1" max="' . (int) $item->quantity . '" style="width:54px;padding:5px 6px;border:1.5px solid #d0dce8;border-radius:6px;font-size:13px;text-align:center">';
        echo '<button onclick="veshoAddToCart(' . $item->id . ',\'' . esc_js( $item->name ) . '\')" style="padding:8px 14px;background:' . $btn_bg . ';color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:' . $btn_cur . '" ' . $disabled . '>Lisa korvi</button>';
        echo '</div>';
        echo '</div></div>';
    endforeach;
    echo '</div>';

    // Floating cart button
    ?>
    <div id="vesho-cart-fab" style="position:fixed;bottom:24px;right:24px;z-index:999;<?php echo $cart_count > 0 ? '' : 'display:none'; ?>">
      <a href="<?php echo esc_url( add_query_arg( 'shop_view', 'cart', $page_url ) ); ?>" style="display:flex;align-items:center;gap:8px;background:#00b4c8;color:#fff;padding:12px 20px;border-radius:50px;text-decoration:none;font-weight:700;box-shadow:0 4px 20px rgba(0,180,200,.4)">
        🛒 Korv <span id="vesho-cart-count" style="background:rgba(255,255,255,.3);padding:2px 8px;border-radius:20px"><?php echo $cart_count; ?></span>
      </a>
    </div>

    <!-- Toast notification -->
    <div id="vesho-toast" style="display:none;position:fixed;bottom:80px;right:24px;z-index:10000;background:#0d1f2d;color:#fff;padding:12px 20px;border-radius:10px;font-size:14px;font-weight:600;box-shadow:0 4px 20px rgba(0,0,0,.2)"></div>

    <script>
    var veshoCartNonce = '<?php echo esc_js( $cart_nonce ); ?>';
    var ajaxurl = '<?php echo esc_js( $ajax_url ); ?>';

    function veshoUpdateCartBadge(count) {
      var fab = document.getElementById('vesho-cart-fab');
      var badge = document.getElementById('vesho-cart-count');
      if (badge) badge.textContent = count;
      if (fab) fab.style.display = count > 0 ? 'block' : 'none';
    }

    function showToast(msg) {
      var t = document.getElementById('vesho-toast');
      t.textContent = msg; t.style.display = 'block';
      setTimeout(function(){ t.style.display='none'; }, 3000);
    }

    function veshoAddToCart(pid, name) {
      var form = new FormData();
      form.append('action', 'vesho_cart_add');
      form.append('nonce', veshoCartNonce);
      form.append('pid', pid);
      form.append('qty', document.getElementById('qty_'+pid)?.value || 1);
      fetch(ajaxurl, {method:'POST', body:form})
        .then(r=>r.json())
        .then(d=>{ if(d.success){ veshoUpdateCartBadge(d.data.count); showToast(name+' lisatud korvi ✓'); } });
    }
    </script>
    <?php
    return ob_get_clean();
} );
