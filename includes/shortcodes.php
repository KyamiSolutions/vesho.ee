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

add_shortcode( 'vesho_shop_grid', function ( $atts ) {
    $atts = shortcode_atts( [ 'hero' => '0' ], $atts );
    $show_hero = $atts['hero'] !== '0';
    global $wpdb;

    // ── Session bootstrap ──────────────────────────────────────────────────
    if ( session_status() === PHP_SESSION_NONE ) session_start();
    $nav_cart_count = array_sum( $_SESSION['vesho_cart'] ?? [] );

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

    // Shared: sidebar data available in all views
    $search   = isset( $_GET['vs'] ) ? sanitize_text_field( $_GET['vs'] ) : '';
    $category = isset( $_GET['cat'] ) ? sanitize_text_field( $_GET['cat'] ) : '';
    $sort     = isset( $_GET['sort'] ) ? sanitize_key( $_GET['sort'] ) : 'name';
    $managed_cats = $wpdb->get_results(
        "SELECT ic.name, ic.color FROM {$wpdb->prefix}vesho_inventory_categories ic
         ORDER BY ic.sort_order ASC, ic.name ASC"
    );
    if ( empty( $managed_cats ) ) {
        $raw_cats     = $wpdb->get_col( "SELECT DISTINCT category FROM {$wpdb->prefix}vesho_inventory WHERE archived=0 AND shop_price>0 AND shop_enabled=1 AND category!='' ORDER BY category ASC" );
        $managed_cats = array_map( fn($n) => (object)['name'=>$n,'color'=>'#00b4c8'], $raw_cats );
    }
    $cat_cnts = [];
    foreach ( $wpdb->get_results( "SELECT category, COUNT(*) c FROM {$wpdb->prefix}vesho_inventory WHERE archived=0 AND shop_price>0 AND shop_enabled=1 AND category!='' GROUP BY category" ) as $r ) {
        $cat_cnts[ $r->category ] = (int) $r->c;
    }

    ob_start();
    ?>
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800&family=Barlow:wght@300;400;500;600;700&display=swap');
    .vsho-root{font-family:'Barlow',sans-serif;color:#1a2a38;line-height:1.6;background:#f4f7f9}
    .vsho-root *{box-sizing:border-box}
    /* ── Shop header nav (3006 style) ── */
    .vsho-shopnav{background:#0d1f2d;padding:18px 0;border-bottom:2px solid #00b4c8}
    .vsho-shopnav-inner{max-width:1280px;margin:0 auto;padding:0 24px;display:flex;align-items:center;justify-content:space-between}
    .vsho-shopnav-logo{font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:1.4rem;color:#fff;text-transform:uppercase;letter-spacing:2px;text-decoration:none}
    .vsho-shopnav-logo span{color:#00b4c8}
    .vsho-shopnav-links{display:flex;align-items:center;gap:24px}
    .vsho-shopnav-links a{color:rgba(255,255,255,.8);font-weight:500;font-size:.95rem;text-decoration:none;transition:color .2s;position:relative}
    .vsho-shopnav-links a:hover{color:#00b4c8}
    .vsho-shopnav-badge{background:#00b4c8;color:#fff;font-size:.7rem;font-weight:700;padding:2px 6px;border-radius:20px;margin-left:4px;vertical-align:middle}
    /* ── Hero (optional) ── */
    .vsho-hero{background:linear-gradient(135deg,#0b1c2b 0%,#0d3347 100%);padding:64px 32px 56px;text-align:center}
    .vsho-hero-eyebrow{color:#00b4c8;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:2px;margin-bottom:14px}
    .vsho-hero-title{font-family:'Barlow Condensed',sans-serif;color:#fff;font-size:52px;font-weight:800;line-height:1.05;margin:0 0 14px;text-transform:uppercase}
    .vsho-hero-sub{color:#7a9eb0;font-size:16px}
    /* ── Layout ── */
    .vsho-layout{max-width:1280px;margin:0 auto;padding:32px 24px;display:grid;grid-template-columns:260px 1fr;gap:32px;align-items:start}
    @media(max-width:900px){.vsho-layout{grid-template-columns:1fr;padding:20px 16px}}
    .vsho-content{max-width:1280px;margin:0 auto;padding:32px 24px}
    @media(max-width:900px){.vsho-content{padding:20px 16px}}
    /* ── Sidebar ── */
    .vsho-sidebar{background:#fff;border-radius:8px;padding:0;border:1px solid #e8ecef;align-self:start;position:sticky;top:24px;overflow:hidden}
    @media(max-width:900px){.vsho-sidebar{position:static}}
    .vsho-sidebar-hdr{background:#0d1f2d;padding:14px 20px;display:flex;align-items:center;gap:8px}
    .vsho-sidebar-hdr-txt{color:#fff;font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:1rem;text-transform:uppercase;letter-spacing:.5px}
    .vsho-sidebar-hdr-ico{color:#00b4c8;font-size:12px}
    .vsho-cat-list{list-style:none;margin:0;padding:0}
    .vsho-cat-list a{display:flex;align-items:center;justify-content:space-between;padding:11px 20px;color:#1a2a38;text-decoration:none;font-size:.92rem;border-bottom:1px solid #f0f4f8;transition:all .15s}
    .vsho-cat-list a:hover{background:#f4f7f9;color:#00b4c8}
    .vsho-cat-list a.active{background:#e0f7fa;color:#008fa0;font-weight:600}
    .vsho-cat-list a:last-child{border-bottom:none}
    .vsho-cat-count{font-size:.82rem;color:#5a7080;background:#f0f4f8;padding:1px 7px;border-radius:20px}
    .vsho-cat-list a.active .vsho-cat-count{background:#00b4c8;color:#fff}
    /* ── Toolbar ── */
    .vsho-toolbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;gap:12px;flex-wrap:wrap}
    .vsho-toolbar-left{display:flex;align-items:center;gap:12px;flex:1;min-width:0}
    .vsho-search-form{display:flex;flex:1;max-width:360px;border:2px solid #e0e6eb;border-radius:6px;overflow:hidden;background:#fff}
    .vsho-search-form input{flex:1;border:none;padding:9px 14px;font-family:'Barlow',sans-serif;font-size:.95rem;color:#1a2a38;outline:none;background:transparent;min-width:0}
    .vsho-search-form button{padding:0 16px;background:#00b4c8;color:#fff;border:none;font-size:.85rem;font-weight:700;cursor:pointer;white-space:nowrap;font-family:'Barlow',sans-serif;text-transform:uppercase;letter-spacing:.5px}
    .vsho-sort{padding:9px 14px;border:2px solid #e0e6eb;border-radius:6px;font-family:'Barlow',sans-serif;font-size:.92rem;color:#1a2a38;background:#fff;cursor:pointer}
    .vsho-results{font-size:.88rem;color:#5a7080}
    .vsho-view-toggle{display:flex;gap:4px}
    .vsho-vbtn{padding:8px 11px;border:2px solid #e0e6eb;border-radius:6px;background:#fff;cursor:pointer;font-size:13px;color:#5a7080;transition:.12s;line-height:1}
    .vsho-vbtn.active{background:#0d1f2d;border-color:#0d1f2d;color:#fff}
    /* ── Product grid ── */
    .vsho-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
    @media(max-width:1100px){.vsho-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:560px){.vsho-grid{grid-template-columns:1fr}}
    /* ── Product card ── */
    .vsho-card{background:#fff;border-radius:8px;overflow:hidden;border:1px solid #e8ecef;transition:transform .2s,box-shadow .2s;display:flex;flex-direction:column}
    .vsho-card:hover{transform:translateY(-4px);box-shadow:0 12px 32px rgba(0,0,0,.08)}
    .vsho-card-img{height:200px;background:linear-gradient(135deg,#e8ecef,#d4dbe2);display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;text-decoration:none;flex-shrink:0}
    .vsho-card-img img{width:100%;height:100%;object-fit:cover}
    .vsho-card-img-ph{font-size:3rem;opacity:.2;color:#0d1f2d;font-weight:800;font-family:'Barlow Condensed',sans-serif}
    .vsho-card-badge{position:absolute;top:10px;left:10px;background:#00b4c8;color:#fff;padding:3px 10px;border-radius:4px;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
    .vsho-card-badge.out{background:#dc2626}
    .vsho-card-body{padding:18px;display:flex;flex-direction:column;flex:1}
    .vsho-card-cat{font-size:.78rem;color:#00b4c8;text-transform:uppercase;letter-spacing:1px;font-weight:600;margin-bottom:5px}
    .vsho-card-name{font-family:'Barlow Condensed',sans-serif;font-size:1.05rem;font-weight:700;color:#1a2a38;margin-bottom:6px;text-decoration:none;display:block;line-height:1.2}
    .vsho-card-name:hover{color:#00b4c8}
    .vsho-card-desc{font-size:.88rem;color:#5a7080;margin-bottom:12px;flex:1;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
    .vsho-card-footer{display:flex;justify-content:space-between;align-items:center;margin-top:auto}
    .vsho-price{font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:1.3rem;color:#0d1f2d}
    .vsho-price .old{font-size:.88rem;color:#5a7080;text-decoration:line-through;font-weight:400;margin-right:4px}
    .vsho-price .cur{color:#0d1f2d}
    .vsho-price.sale .cur{color:#00b4c8}
    .vsho-stock-ok{font-size:.8rem;color:#16a34a;font-weight:600;margin-bottom:8px}
    .vsho-stock-no{font-size:.8rem;color:#dc2626;font-weight:600;margin-bottom:8px}
    .vsho-btn{padding:8px 16px;background:#00b4c8;color:#fff;border:none;border-radius:4px;font-family:'Barlow',sans-serif;font-weight:600;font-size:.83rem;text-transform:uppercase;letter-spacing:.5px;cursor:pointer;transition:background .2s;white-space:nowrap}
    .vsho-btn:hover{background:#008fa0}
    .vsho-btn:disabled{background:#e2e8f0;color:#94a3b8;cursor:not-allowed}
    /* ── List view ── */
    .vsho-grid.list-view{grid-template-columns:1fr;gap:10px}
    .vsho-grid.list-view .vsho-card{flex-direction:row}
    .vsho-grid.list-view .vsho-card-img{width:130px;height:auto;min-height:110px;flex-shrink:0}
    .vsho-grid.list-view .vsho-card-body{flex-direction:row;flex-wrap:wrap;align-items:center;padding:14px 18px}
    .vsho-grid.list-view .vsho-card-cat{width:100%}
    .vsho-grid.list-view .vsho-card-name{flex:1;margin:0 16px 0 0;font-size:1rem}
    .vsho-grid.list-view .vsho-card-desc{display:none}
    .vsho-grid.list-view .vsho-card-footer{margin-top:0}
    /* ── Pagination ── */
    .vsho-pag{display:flex;gap:8px;justify-content:center;margin-top:32px;flex-wrap:wrap}
    .vsho-pag a,.vsho-pag span{display:flex;align-items:center;justify-content:center;width:40px;height:40px;border-radius:6px;border:2px solid #e0e6eb;font-weight:600;font-size:.88rem;text-decoration:none;color:#1a2a38;background:#fff;transition:all .15s}
    .vsho-pag a:hover{border-color:#00b4c8;color:#00b4c8}
    .vsho-pag span.current{background:#00b4c8;border-color:#00b4c8;color:#fff}
    .vsho-pag span.dots{border:none;background:none;color:#5a7080}
    /* ── Campaign bar ── */
    .vsho-cam{display:flex;align-items:center;gap:16px;background:linear-gradient(135deg,#e0f7fa,#b2ebf2);border:1.5px solid #00b4c8;border-radius:8px;padding:14px 20px;margin-bottom:24px}
    .vsho-cam-badge{background:#00b4c8;color:#fff;font-size:.95rem;font-weight:900;padding:5px 14px;border-radius:6px;white-space:nowrap;font-family:'Barlow Condensed',sans-serif}
    /* ── Cart table ── */
    .vsho-cart-section{max-width:1000px;margin:0 auto}
    .vsho-cart-h{font-family:'Barlow Condensed',sans-serif;font-size:2rem;color:#0d1f2d;margin-bottom:24px;text-transform:uppercase;font-weight:800}
    .vsho-cart-tbl{width:100%;background:#fff;border-radius:8px;border:1px solid #e8ecef;border-collapse:collapse;overflow:hidden;margin-bottom:24px}
    .vsho-cart-tbl thead{background:#0d1f2d}
    .vsho-cart-tbl th{padding:14px 20px;text-align:left;font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:.88rem;text-transform:uppercase;letter-spacing:1px;color:#fff}
    .vsho-cart-tbl td{padding:16px 20px;border-bottom:1px solid #f0f4f8;vertical-align:middle;font-size:.92rem}
    .vsho-cart-tbl tbody tr:last-child td{border-bottom:none}
    .vsho-cart-item-name{font-weight:600;color:#0d1f2d}
    .vsho-cart-item-unit{font-size:.8rem;color:#5a7080}
    .vsho-cart-qty input{width:60px;padding:7px;text-align:center;border:2px solid #e0e6eb;border-radius:4px;font-family:'Barlow',sans-serif;font-size:.92rem}
    .vsho-cart-remove{background:none;border:none;color:#dc2626;cursor:pointer;font-size:.85rem;font-weight:600;transition:opacity .2s}
    .vsho-cart-remove:hover{opacity:.7}
    .vsho-cart-summary{background:#fff;border-radius:8px;border:1px solid #e8ecef;padding:24px;max-width:400px;margin-left:auto}
    .vsho-cart-sum-row{display:flex;justify-content:space-between;padding:9px 0;font-size:.92rem;color:#5a7080}
    .vsho-cart-sum-row.total{border-top:2px solid #0d1f2d;margin-top:8px;padding-top:14px;font-size:1.05rem;font-weight:700;color:#0d1f2d}
    .vsho-cart-empty{text-align:center;padding:60px 24px;color:#5a7080}
    .vsho-cart-empty h2{color:#0d1f2d;margin-bottom:12px;font-family:'Barlow Condensed',sans-serif;text-transform:uppercase}
    /* ── Buttons ── */
    .vsho-btn-primary{padding:12px 28px;background:#00b4c8;color:#fff;border:none;border-radius:6px;font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:1rem;text-transform:uppercase;letter-spacing:1px;cursor:pointer;transition:background .2s;text-decoration:none;display:inline-block}
    .vsho-btn-primary:hover{background:#008fa0;color:#fff}
    .vsho-btn-primary:disabled{background:#e2e8f0;color:#94a3b8;cursor:not-allowed}
    .vsho-btn-secondary{padding:12px 24px;background:#fff;color:#0d1f2d;border:2px solid #e0e6eb;border-radius:6px;font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:1rem;text-transform:uppercase;letter-spacing:1px;cursor:pointer;transition:all .2s;text-decoration:none;display:inline-block}
    .vsho-btn-secondary:hover{border-color:#0d1f2d}
    /* ── FAB ── */
    .vsho-fab{position:fixed;bottom:24px;right:24px;z-index:9999}
    .vsho-fab a{display:flex;align-items:center;gap:10px;background:#0d1f2d;color:#fff;padding:13px 22px;border-radius:50px;text-decoration:none;font-weight:700;font-size:.9rem;box-shadow:0 4px 20px rgba(13,31,45,.3);transition:background .2s}
    .vsho-fab a:hover{background:#162840;color:#fff}
    .vsho-fab-badge{background:#00b4c8;color:#fff;font-size:.7rem;font-weight:800;padding:2px 7px;border-radius:20px}
    .vsho-toast{display:none;position:fixed;bottom:80px;right:24px;z-index:10000;background:#0d1f2d;color:#fff;padding:11px 18px;border-radius:8px;font-size:.88rem;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.22)}
    .vsho-no-img{color:#94a3b8;font-size:.8rem;font-weight:500;letter-spacing:.5px;text-align:center}
    /* ── Product detail ── */
    .vsho-pd{max-width:1280px;margin:0 auto;padding:32px 24px;display:grid;grid-template-columns:260px 1fr;gap:32px;align-items:start}
    @media(max-width:900px){.vsho-pd{grid-template-columns:1fr;padding:20px 16px}}
    .vsho-pd-right{display:grid;grid-template-columns:1fr 1fr;gap:48px;align-items:start}
    @media(max-width:700px){.vsho-pd-right{grid-template-columns:1fr;gap:24px}}
    .vsho-pd-img{background:#fff;border-radius:8px;overflow:hidden;border:1px solid #e8ecef;aspect-ratio:1;display:flex;align-items:center;justify-content:center}
    .vsho-pd-img img{width:100%;height:100%;object-fit:cover}
    .vsho-pd-img-ph{font-size:5rem;opacity:.2;color:#0d1f2d;font-family:'Barlow Condensed',sans-serif;font-weight:800}
    .vsho-pd-info{padding:8px 0}
    .vsho-pd-cat{font-size:.8rem;color:#00b4c8;text-transform:uppercase;letter-spacing:1px;font-weight:600;margin-bottom:8px}
    .vsho-pd-title{font-family:'Barlow Condensed',sans-serif;font-size:2.2rem;font-weight:800;color:#0d1f2d;margin:0 0 14px;text-transform:uppercase;line-height:1.1}
    .vsho-pd-price{font-family:'Barlow Condensed',sans-serif;font-weight:800;font-size:2rem;color:#0d1f2d;margin-bottom:20px}
    .vsho-pd-price .old{font-size:1.1rem;color:#5a7080;text-decoration:line-through;font-weight:400;margin-right:8px}
    .vsho-pd-price .sale{color:#00b4c8}
    .vsho-pd-desc{color:#5a7080;font-size:.95rem;line-height:1.7;margin-bottom:24px;padding:16px;background:#fff;border-radius:6px;border:1px solid #e8ecef}
    .vsho-pd-stock-ok{color:#16a34a;font-weight:600;font-size:.9rem;margin-bottom:16px}
    .vsho-pd-stock-no{color:#dc2626;font-weight:600;font-size:.9rem;margin-bottom:16px}
    .vsho-pd-qty{display:flex;align-items:center;gap:12px;margin-bottom:20px}
    .vsho-pd-qty label{font-weight:600;font-size:.85rem;text-transform:uppercase;letter-spacing:.5px;color:#0d1f2d}
    .vsho-pd-qty input{width:70px;padding:10px;text-align:center;border:2px solid #e0e6eb;border-radius:6px;font-family:'Barlow',sans-serif;font-size:1rem;color:#0d1f2d}
    /* ── Breadcrumb ── */
    .vsho-bread{display:flex;align-items:center;gap:8px;font-size:.88rem;margin-bottom:20px;color:#5a7080}
    .vsho-bread a{color:#00b4c8;text-decoration:none}
    .vsho-bread a:hover{color:#008fa0}
    /* ── Steps (checkout) ── */
    .vsho-steps{display:flex;justify-content:center;gap:32px;margin-bottom:28px;flex-wrap:wrap}
    .vsho-step{display:flex;align-items:center;gap:8px;font-size:.88rem;color:#5a7080;font-weight:500}
    .vsho-step.active{color:#0d1f2d;font-weight:700}
    .vsho-step-num{width:28px;height:28px;border-radius:50%;border:2px solid #e0e6eb;display:flex;align-items:center;justify-content:center;font-size:.8rem;font-weight:700}
    .vsho-step.active .vsho-step-num{background:#00b4c8;border-color:#00b4c8;color:#fff}
    .vsho-step.done .vsho-step-num{background:#0d1f2d;border-color:#0d1f2d;color:#fff}
    /* ── Success/cancel ── */
    .vsho-result{text-align:center;padding:60px 20px}
    .vsho-result h2{font-family:'Barlow Condensed',sans-serif;font-size:2rem;text-transform:uppercase;color:#0d1f2d;margin:16px 0 8px}
    .vsho-result p{color:#5a7080}
    </style>
    <div class="vsho-root">
    <nav class="vsho-shopnav">
      <div class="vsho-shopnav-inner">
        <a href="<?php echo esc_url($page_url); ?>" class="vsho-shopnav-logo">VESHO <span>OÜ</span></a>
        <div class="vsho-shopnav-links">
          <a href="<?php echo esc_url($page_url); ?>">🏪 Pood</a>
          <a href="<?php echo esc_url(add_query_arg('shop_view','cart',$page_url)); ?>">
            🛒 Ostukorv<?php if($nav_cart_count>0) echo ' <span class="vsho-shopnav-badge">'.$nav_cart_count.'</span>'; ?>
          </a>
        </div>
      </div>
    </nav>
    <?php if($show_hero): ?>
    <div class="vsho-hero">
      <div class="vsho-hero-eyebrow">VESHO OÜ</div>
      <div class="vsho-hero-title">Pood</div>
      <div class="vsho-hero-sub">Veesüsteemide seadmed ja tarvikud</div>
    </div>
    <?php endif; ?>
    <?php

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
    // VIEW: PRODUCT DETAIL
    // ══════════════════════════════════════════════════════════════════════
    if ( $shop_view === 'product' ) {
        $pid  = absint( $_GET['pid'] ?? 0 );
        $item = $pid ? $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_inventory WHERE id=%d AND archived=0 AND shop_price>0",
            $pid
        ) ) : null;

        if ( ! $item ) {
            echo '<div style="text-align:center;padding:60px 20px"><div style="font-size:48px">🔧</div><p style="color:#6b8599">Toodet ei leitud.</p>';
            echo '<a href="' . esc_url( $page_url ) . '" style="display:inline-block;margin-top:16px;padding:10px 24px;background:#00b4c8;color:#fff;border-radius:8px;text-decoration:none;font-weight:700">← Tagasi poodi</a></div>';
            return ob_get_clean();
        }

        // Campaign for this product detail
        $today_pd   = date( 'Y-m-d' );
        $is_cli_pd  = ! empty( $_SESSION['vesho_client_id'] );
        $pd_campaign = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_campaigns
             WHERE paused=0 AND valid_from<=%s AND valid_until>=%s AND (target='epood' OR target='both')
             ORDER BY discount_percent DESC LIMIT 1",
            $today_pd, $today_pd
        ) );
        if ( $pd_campaign && ! $is_cli_pd && ! $pd_campaign->visible_to_guests ) $pd_campaign = null;
        $sess_cam_pd = $_SESSION['vesho_cart_campaign'] ?? null;
        if ( $sess_cam_pd && ( ! $pd_campaign || $sess_cam_pd['discount_percent'] > $pd_campaign->discount_percent ) ) {
            $pd_disc = (float) $sess_cam_pd['discount_percent'];
            $pd_cam_name = $sess_cam_pd['name'] . ' (lukustatud)';
        } elseif ( $pd_campaign ) {
            $pd_disc = (float) $pd_campaign->discount_percent;
            $pd_cam_name = $pd_campaign->name;
        } else {
            $pd_disc = 0; $pd_cam_name = '';
        }

        $orig_price = (float) $item->shop_price;
        $disc_price = $pd_disc > 0 ? round( $orig_price * ( 1 - $pd_disc / 100 ), 2 ) : null;
        $in_stock   = $item->quantity > 0;
        $unit_lbl   = esc_html( $item->unit ?: 'tk' );
        ?>
        <style>
        /* Sidebar (reuse grid vars) */
        .vshop-layout{display:grid;grid-template-columns:220px 1fr;gap:28px;align-items:start}
        @media(max-width:820px){.vshop-layout{grid-template-columns:1fr}}
        .vshop-sidebar{position:sticky;top:24px}
        @media(max-width:820px){.vshop-sidebar{position:static}}
        .vshop-sidebar-box{background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.07);margin-bottom:14px;border:1px solid #dce8ef}
        .vshop-sidebar-header{background:#0d1f2d;padding:11px 14px;display:flex;align-items:center;gap:8px}
        .vshop-sidebar-header-icon{color:#00b4c8;font-size:13px}
        .vshop-sidebar-header-text{color:#fff;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
        .vshop-cat-link{display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid #dce8ef;text-decoration:none;color:#1a2a38;font-size:13px;font-weight:500;transition:.12s}
        .vshop-cat-link:last-child{border-bottom:none}
        .vshop-cat-link:hover{background:#f4f7f9;color:#00b4c8}
        .vshop-cat-link.active{background:#e0f7fa;color:#00b4c8;font-weight:700}
        .vshop-cat-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
        .vshop-cat-count{margin-left:auto;font-size:11px;background:#f0f4f8;color:#5a7080;padding:2px 7px;border-radius:20px;font-weight:600}
        .vshop-cat-link.active .vshop-cat-count{background:#00b4c8;color:#fff}
        .vshop-search-form{display:flex;border:1.5px solid #dce8ef;border-radius:8px;overflow:hidden;background:#fff;margin-bottom:14px}
        .vshop-search-form input{flex:1;border:none;padding:10px 14px;font-size:13px;outline:none;color:#1a2a38;background:transparent;min-width:0}
        .vshop-search-form button{padding:0 14px;background:#00b4c8;color:#fff;border:none;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap}
        /* Product detail */
        .vpd-wrap{font-family:inherit}
        /* Breadcrumb */
        .vpd-bread{display:flex;align-items:center;gap:8px;font-size:13px;margin-bottom:24px;flex-wrap:wrap}
        .vpd-bread a{color:#6b8599;text-decoration:none;font-weight:500}
        .vpd-bread a:hover{color:#00b4c8}
        .vpd-bread-sep{color:#d0dce8}
        .vpd-bread-cur{color:#0d1f2d;font-weight:600}
        /* Campaign */
        .vpd-cam{display:flex;align-items:center;gap:12px;background:linear-gradient(135deg,#e0f7fa,#b2ebf2);border:1.5px solid #00b4c8;border-radius:10px;padding:12px 18px;margin-bottom:20px}
        .vpd-cam-badge{background:#00b4c8;color:#fff;font-size:14px;font-weight:900;padding:5px 12px;border-radius:7px;white-space:nowrap}
        /* Main layout */
        .vpd-main{display:grid;grid-template-columns:1fr 1fr;gap:32px;align-items:start}
        @media(max-width:700px){.vpd-main{grid-template-columns:1fr}}
        /* Image */
        .vpd-img-wrap{position:relative;background:#f8fafc;border-radius:16px;overflow:hidden;aspect-ratio:1;display:flex;align-items:center;justify-content:center;border:1.5px solid #f0f4f8}
        .vpd-img-wrap img{width:100%;height:100%;object-fit:cover}
        .vpd-img-wrap svg{width:80px;height:80px;opacity:.35}
        .vpd-disc-badge{position:absolute;top:14px;left:14px;background:#00b4c8;color:#fff;font-size:13px;font-weight:800;padding:5px 12px;border-radius:20px}
        .vpd-out-badge{position:absolute;top:14px;right:14px;background:#ef4444;color:#fff;font-size:12px;font-weight:700;padding:5px 10px;border-radius:20px}
        /* Details panel */
        .vpd-panel{display:flex;flex-direction:column;gap:14px}
        .vpd-cat-tag{display:inline-block;font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:1px;padding:3px 10px;border-radius:20px;color:#fff}
        .vpd-title{font-size:28px;font-weight:800;color:#0d1f2d;margin:0;line-height:1.2}
        .vpd-sku{font-size:12px;color:#94a3b8;font-family:monospace}
        .vpd-desc{font-size:14px;color:#4b6174;line-height:1.7;margin:0;padding:16px;background:#f8fafc;border-radius:10px;border-left:3px solid #e2e8f0}
        .vpd-price-box{background:#fff;border:1.5px solid #f0f4f8;border-radius:12px;padding:18px 20px}
        .vpd-price-orig{font-size:14px;color:#9ca3af;text-decoration:line-through}
        .vpd-price-main{font-size:34px;font-weight:900;color:#0d1f2d;line-height:1.1}
        .vpd-price-main.disc{color:#00b4c8}
        .vpd-price-unit{font-size:13px;color:#9ca3af;font-weight:400;margin-left:4px}
        .vpd-savings{font-size:12px;color:#059669;font-weight:700;margin-top:3px}
        .vpd-stock-ok{font-size:13px;color:#059669;font-weight:600;margin-top:8px}
        .vpd-stock-no{font-size:13px;color:#ef4444;font-weight:600;margin-top:8px}
        /* ATC */
        .vpd-atc-row{display:flex;gap:10px;align-items:stretch}
        .vpd-qty{display:flex;align-items:center;border:1.5px solid #e2e8f0;border-radius:10px;overflow:hidden;background:#f8fafc}
        .vpd-qty button{background:none;border:none;padding:12px 16px;font-size:18px;cursor:pointer;color:#4b6174;line-height:1;transition:.1s}
        .vpd-qty button:hover{background:#e0f7fa;color:#0097aa}
        .vpd-qty input{width:46px;border:none;background:none;text-align:center;font-size:16px;font-weight:700;color:#0d1f2d;padding:0}
        .vpd-atc-btn{flex:1;padding:14px 20px;background:#00b4c8;color:#fff;border:none;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;transition:.15s;white-space:nowrap}
        .vpd-atc-btn:hover{background:#0097aa}
        .vpd-atc-btn:disabled{background:#e2e8f0;color:#9ca3af;cursor:not-allowed}
        .vpd-added{display:none;align-items:center;gap:8px;background:#ecfdf5;color:#065f46;font-weight:700;font-size:14px;padding:12px 18px;border-radius:10px;border:1.5px solid #6ee7b7;margin-top:4px}
        /* Related */
        .vpd-related-title{font-size:17px;font-weight:800;color:#0d1f2d;margin:32px 0 16px}
        .vpd-related-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px}
        @media(max-width:700px){.vpd-related-grid{grid-template-columns:repeat(2,1fr)}}
        .vpd-rel-card{background:#fff;border-radius:10px;border:1.5px solid #f0f4f8;overflow:hidden;text-decoration:none;display:block;transition:.15s}
        .vpd-rel-card:hover{border-color:#00b4c8;box-shadow:0 4px 16px rgba(0,180,200,.1)}
        .vpd-rel-img{height:100px;background:#f8fafc;display:flex;align-items:center;justify-content:center}
        .vpd-rel-img img{width:100%;height:100%;object-fit:cover}
        .vpd-rel-img svg{width:36px;height:36px;opacity:.3}
        .vpd-rel-body{padding:10px}
        .vpd-rel-name{font-size:12px;font-weight:700;color:#0d1f2d;line-height:1.3;margin-bottom:4px}
        .vpd-rel-price{font-size:13px;font-weight:800;color:#00b4c8}
        /* FAB */
        .vpd-fab{position:fixed;bottom:24px;right:24px;z-index:999}
        .vpd-fab a{display:flex;align-items:center;gap:8px;background:#0d1f2d;color:#fff;padding:12px 20px;border-radius:50px;text-decoration:none;font-weight:700;font-size:14px;box-shadow:0 4px 20px rgba(13,31,45,.3)}
        .vpd-fab-badge{background:#00b4c8;padding:2px 8px;border-radius:20px;font-size:11px;font-weight:800}
        </style>

        <?php
        // Related products (same category, not this product)
        $related = $item->category ? $wpdb->get_results( $wpdb->prepare(
            "SELECT id,name,shop_price,image_url FROM {$wpdb->prefix}vesho_inventory
             WHERE archived=0 AND shop_price>0 AND shop_enabled=1 AND category=%s AND id!=%d ORDER BY RAND() LIMIT 4",
            $item->category, $item->id
        ) ) : [];
        // category color
        $pd_cat_color = '#00b4c8';
        $pd_cat_obj   = $wpdb->get_row( $wpdb->prepare("SELECT color FROM {$wpdb->prefix}vesho_inventory_categories WHERE name=%s", $item->category) );
        if ( $pd_cat_obj ) $pd_cat_color = $pd_cat_obj->color;
        $cart_count_pd = array_sum( $_SESSION['vesho_cart'] ?? [] );
        ?>

        <div class="vshop-layout">

        <!-- ── LEFT SIDEBAR ── -->
        <aside class="vshop-sidebar">
          <form class="vshop-search-form" method="GET" action="<?php echo esc_url($page_url); ?>">
            <input type="search" name="vs" value="<?php echo esc_attr($search); ?>" placeholder="Otsi toodet...">
            <button type="submit">Otsi</button>
          </form>
          <?php if ( ! empty( $managed_cats ) ) : ?>
          <div class="vshop-sidebar-box">
            <div class="vshop-sidebar-header">
              <span class="vshop-sidebar-header-icon">▼</span>
              <span class="vshop-sidebar-header-text">Kategooriad</span>
            </div>
            <a href="<?php echo esc_url($page_url); ?>" class="vshop-cat-link">
              <span class="vshop-cat-dot" style="background:#0d1f2d"></span>
              Kõik tooted
              <span class="vshop-cat-count"><?php echo array_sum($cat_cnts); ?></span>
            </a>
            <?php foreach ( $managed_cats as $cat ) :
              $is_active_pd = $item->category === $cat->name;
              $href_pd = add_query_arg( 'cat', $cat->name, $page_url );
            ?>
            <a href="<?php echo esc_url($href_pd); ?>" class="vshop-cat-link <?php echo $is_active_pd?'active':''; ?>">
              <span class="vshop-cat-dot" style="background:<?php echo esc_attr($cat->color); ?>"></span>
              <?php echo esc_html($cat->name); ?>
              <span class="vshop-cat-count"><?php echo $cat_cnts[$cat->name] ?? 0; ?></span>
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </aside>

        <!-- ── RIGHT: PRODUCT DETAIL ── -->
        <div class="vpd-wrap">

          <!-- Breadcrumb -->
          <div class="vpd-bread">
            <a href="<?php echo esc_url($page_url); ?>">Pood</a>
            <?php if ($item->category): ?>
            <span class="vpd-bread-sep">›</span>
            <a href="<?php echo esc_url(add_query_arg('cat', $item->category, $page_url)); ?>"><?php echo esc_html($item->category); ?></a>
            <?php endif; ?>
            <span class="vpd-bread-sep">›</span>
            <span class="vpd-bread-cur"><?php echo esc_html($item->name); ?></span>
          </div>

          <!-- Campaign -->
          <?php if ($pd_disc > 0): ?>
          <div class="vpd-cam">
            <div class="vpd-cam-badge">-<?php echo (int)$pd_disc; ?>%</div>
            <div>
              <div style="font-weight:700;color:#0d1f2d;font-size:14px">🏷️ <?php echo esc_html($pd_cam_name); ?></div>
              <?php if ($pd_campaign && $pd_campaign->valid_until): ?>
              <div style="font-size:12px;color:#0097aa;font-weight:600;margin-top:2px">Kehtib kuni <?php echo esc_html(date_i18n('d.m.Y', strtotime($pd_campaign->valid_until))); ?></div>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Main 2-col -->
          <div class="vpd-main">

            <!-- Image -->
            <div class="vpd-img-wrap">
              <?php if (!empty($item->image_url)): ?>
                <img src="<?php echo esc_url($item->image_url); ?>" alt="<?php echo esc_attr($item->name); ?>">
              <?php else: ?>
                <div class="vsho-no-img" style="font-size:1rem">Pilt puudub</div>
              <?php endif; ?>
              <?php if ($pd_disc > 0) echo '<span class="vpd-disc-badge">-'.(int)$pd_disc.'%</span>'; ?>
              <?php if (!$in_stock) echo '<span class="vpd-out-badge">Laost otsas</span>'; ?>
            </div>

            <!-- Details -->
            <div class="vpd-panel">
              <?php if ($item->category): ?>
              <div><span class="vpd-cat-tag" style="background:<?php echo esc_attr($pd_cat_color); ?>"><?php echo esc_html($item->category); ?></span></div>
              <?php endif; ?>

              <h1 class="vpd-title"><?php echo esc_html($item->name); ?></h1>

              <?php if ($item->sku): ?>
              <div class="vpd-sku">Kood: <?php echo esc_html($item->sku); ?></div>
              <?php endif; ?>

              <?php $desc = $item->shop_description ?: $item->description; if ($desc): ?>
              <p class="vpd-desc"><?php echo nl2br(esc_html($desc)); ?></p>
              <?php endif; ?>

              <div class="vpd-price-box">
                <?php if ($disc_price !== null): ?>
                <div class="vpd-price-orig"><?php echo number_format($orig_price, 2, ',', ' '); ?> €</div>
                <div class="vpd-price-main disc"><?php echo number_format($disc_price, 2, ',', ' '); ?> €<span class="vpd-price-unit">/ <?php echo esc_html($unit_lbl); ?></span></div>
                <div class="vpd-savings">Säästad <?php echo number_format($orig_price - $disc_price, 2, ',', ' '); ?> €</div>
                <?php else: ?>
                <div class="vpd-price-main"><?php echo number_format($orig_price, 2, ',', ' '); ?> €<span class="vpd-price-unit">/ <?php echo esc_html($unit_lbl); ?></span></div>
                <?php endif; ?>
                <?php if ($in_stock): ?>
                <div class="vpd-stock-ok">✓ Laos (<?php echo (int)$item->quantity; ?> <?php echo esc_html($unit_lbl); ?>)</div>
                <?php else: ?>
                <div class="vpd-stock-no">✗ Laost otsas</div>
                <?php endif; ?>
              </div>

              <div class="vpd-atc-row">
                <?php if ($in_stock): ?>
                <div class="vpd-qty">
                  <button type="button" onclick="var i=document.getElementById('pd-qty');i.value=Math.max(1,+i.value-1)">−</button>
                  <input type="number" id="pd-qty" value="1" min="1" max="<?php echo (int)$item->quantity; ?>">
                  <button type="button" onclick="var i=document.getElementById('pd-qty');i.value=Math.min(<?php echo (int)$item->quantity; ?>,+i.value+1)">+</button>
                </div>
                <button class="vpd-atc-btn" id="pd-atc-btn" onclick="veshoAddToCartPD(<?php echo $item->id; ?>,'<?php echo esc_js($item->name); ?>')">
                  🛒 Lisa korvi
                </button>
                <?php else: ?>
                <button class="vpd-atc-btn" disabled style="flex:1">Laost otsas</button>
                <?php endif; ?>
              </div>
              <div class="vpd-added" id="pd-added">✓ Lisatud ostukorvi!</div>
            </div>
          </div>

          <?php if (!empty($related)): ?>
          <div class="vpd-related-title">Samast kategooriast</div>
          <div class="vpd-related-grid">
          <?php foreach($related as $rel):
            $rel_url = add_query_arg(['shop_view'=>'product','pid'=>$rel->id], $page_url);
          ?>
            <a href="<?php echo esc_url($rel_url); ?>" class="vpd-rel-card">
              <div class="vpd-rel-img">
                <?php if (!empty($rel->image_url)): ?>
                  <img src="<?php echo esc_url($rel->image_url); ?>" alt="<?php echo esc_attr($rel->name); ?>">
                <?php else: ?>
                  <div class="vsho-no-img">Pilt puudub</div>
                <?php endif; ?>
              </div>
              <div class="vpd-rel-body">
                <div class="vpd-rel-name"><?php echo esc_html($rel->name); ?></div>
                <div class="vpd-rel-price"><?php echo number_format((float)$rel->shop_price,2,',',' '); ?> €</div>
              </div>
            </a>
          <?php endforeach; ?>
          </div>
          <?php endif; ?>

        </div><!-- /vpd-wrap -->
        </div><!-- /vshop-layout -->

        <div class="vpd-fab" id="vesho-cart-fab" <?php echo $cart_count_pd>0?'':'style="display:none"'; ?>>
          <a href="<?php echo esc_url(add_query_arg('shop_view','cart',$page_url)); ?>">
            🛒 Korv <span class="vpd-fab-badge" id="vesho-cart-count"><?php echo $cart_count_pd; ?></span>
          </a>
        </div>
        <div id="vesho-toast" style="display:none;position:fixed;bottom:80px;right:24px;z-index:10000;background:#0d1f2d;color:#fff;padding:11px 18px;border-radius:9px;font-size:13px;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.22)"></div>

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
        function veshoAddToCartPD(pid, name) {
          var btn = document.getElementById('pd-atc-btn');
          if (btn) { btn.disabled = true; btn.textContent = 'Lisamine...'; }
          var form = new FormData();
          form.append('action', 'vesho_cart_add');
          form.append('nonce', veshoCartNonce);
          form.append('pid', pid);
          form.append('qty', document.getElementById('pd-qty').value || 1);
          fetch(ajaxurl, {method:'POST', body:form})
            .then(r=>r.json())
            .then(d=>{
              if (d.success) {
                veshoUpdateCartBadge(d.data.count);
                var added = document.getElementById('pd-added');
                if (added) added.style.display = 'flex';
                if (btn) { btn.textContent = '✓ Lisatud'; }
                setTimeout(function(){
                  if (added) added.style.display = 'none';
                  if (btn) { btn.disabled = false; btn.textContent = '🛒 Lisa korvi'; }
                }, 2500);
              }
            });
        }
        </script>
        <?php
        return ob_get_clean();
    }

    // ══════════════════════════════════════════════════════════════════════
    // VIEW: GRID (default)
    // ══════════════════════════════════════════════════════════════════════
    $paged    = max( 1, absint( $_GET['paged'] ?? 1 ) );
    $per_page = 12;
    $sort_sql = match( $sort ) {
        'price_asc'  => 'shop_price ASC',
        'price_desc' => 'shop_price DESC',
        default      => 'name ASC',
    };

    $where = "archived=0 AND shop_price > 0 AND shop_enabled=1";
    if ( $search )   $where .= $wpdb->prepare( " AND (name LIKE %s OR sku LIKE %s OR shop_description LIKE %s OR description LIKE %s)", '%'.$wpdb->esc_like($search).'%', '%'.$wpdb->esc_like($search).'%', '%'.$wpdb->esc_like($search).'%', '%'.$wpdb->esc_like($search).'%' );
    if ( $category ) $where .= $wpdb->prepare( " AND category=%s", $category );

    $total_items = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}vesho_inventory WHERE $where");
    $total_pages = max(1, (int)ceil($total_items / $per_page));
    $paged       = min($paged, $total_pages);
    $offset      = ($paged - 1) * $per_page;

    $items = $wpdb->get_results(
        "SELECT id, name, sku, category, unit, shop_price, shop_description, description, quantity, image_url
         FROM {$wpdb->prefix}vesho_inventory WHERE $where ORDER BY $sort_sql LIMIT $per_page OFFSET $offset"
    );
    $cart_count = array_sum( $_SESSION['vesho_cart'] ?? [] );

    // ── Active campaign ────────────────────────────────────────────────────
    $today_grid    = date( 'Y-m-d' );
    $is_client     = ! empty( $_SESSION['vesho_client_id'] );
    $grid_campaign = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vesho_campaigns WHERE paused=0 AND valid_from<=%s AND valid_until>=%s AND (target='epood' OR target='both') ORDER BY discount_percent DESC LIMIT 1",
        $today_grid, $today_grid
    ) );
    if ( $grid_campaign && ! $is_client && ! $grid_campaign->visible_to_guests ) $grid_campaign = null;
    $sess_cam_grid = $_SESSION['vesho_cart_campaign'] ?? null;
    if ( $sess_cam_grid && ( ! $grid_campaign || $sess_cam_grid['discount_percent'] > $grid_campaign->discount_percent ) ) {
        $eff_discount = (float) $sess_cam_grid['discount_percent']; $eff_name = $sess_cam_grid['name']; $eff_locked = true;
    } elseif ( $grid_campaign ) {
        $eff_discount = (float) $grid_campaign->discount_percent; $eff_name = $grid_campaign->name; $eff_locked = false;
    } else {
        $eff_discount = 0; $eff_name = ''; $eff_locked = false;
    }
    ?>
    <style>
    .vshop-wrap{font-family:inherit}
    /* Campaign bar */
    .vshop-cam{display:flex;align-items:center;gap:16px;background:linear-gradient(135deg,#e0f7fa,#b2ebf2);border:1.5px solid #00b4c8;border-radius:12px;padding:14px 20px;margin-bottom:24px;flex-wrap:wrap}
    .vshop-cam-badge{background:#00b4c8;color:#fff;font-size:15px;font-weight:900;padding:6px 14px;border-radius:8px;white-space:nowrap}
    /* Two-column layout */
    .vshop-layout{display:grid;grid-template-columns:220px 1fr;gap:28px;align-items:start}
    @media(max-width:820px){.vshop-layout{grid-template-columns:1fr}}
    /* Sidebar */
    .vshop-sidebar{position:sticky;top:24px}
    @media(max-width:820px){.vshop-sidebar{position:static}}
    .vshop-sidebar-box{background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,.07);margin-bottom:14px;border:1px solid #dce8ef}
    .vshop-sidebar-header{background:#0d1f2d;padding:11px 14px;display:flex;align-items:center;gap:8px}
    .vshop-sidebar-header-icon{color:#00b4c8;font-size:13px}
    .vshop-sidebar-header-text{color:#fff;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
    .vshop-cat-link{display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid #dce8ef;text-decoration:none;color:#1a2a38;font-size:13px;font-weight:500;transition:.12s;cursor:pointer;border-top:none;border-left:none;border-right:none;background:none;width:100%;text-align:left}
    .vshop-cat-link:last-child{border-bottom:none}
    .vshop-cat-link:hover{background:#f4f7f9;color:#00b4c8}
    .vshop-cat-link.active{background:#e0f7fa;color:#00b4c8;font-weight:700}
    .vshop-cat-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
    .vshop-cat-count{margin-left:auto;font-size:11px;background:#f0f4f8;color:#5a7080;padding:2px 7px;border-radius:20px;font-weight:600}
    .vshop-cat-link.active .vshop-cat-count{background:#00b4c8;color:#fff}
    /* Topbar */
    .vshop-topbar{display:flex;gap:10px;align-items:center;margin-bottom:16px;flex-wrap:wrap}
    .vshop-search-form{display:flex;flex:1;min-width:160px;border:1.5px solid #e2e8f0;border-radius:9px;overflow:hidden;background:#fff}
    .vshop-search-form input{flex:1;border:none;padding:10px 14px;font-size:13px;outline:none;color:#0d1f2d;background:transparent}
    .vshop-search-form button{padding:0 16px;background:#00b4c8;color:#fff;border:none;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap}
    .vshop-sort{padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:9px;font-size:13px;color:#4b6174;background:#fff;cursor:pointer}
    .vshop-results{font-size:12px;color:#94a3b8;margin-bottom:14px}
    /* Grid */
    .vshop-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
    @media(max-width:1100px){.vshop-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:820px){.vshop-grid{grid-template-columns:repeat(3,1fr)}}
    @media(max-width:560px){.vshop-grid{grid-template-columns:repeat(2,1fr)}}
    @media(max-width:380px){.vshop-grid{grid-template-columns:1fr}}
    /* Cards */
    .vshop-card{background:#fff;border-radius:12px;box-shadow:0 1px 4px rgba(13,31,45,.07);overflow:hidden;display:flex;flex-direction:column;transition:box-shadow .18s,transform .18s;border:1.5px solid #f0f4f8;cursor:pointer}
    .vshop-card:hover{box-shadow:0 6px 24px rgba(13,31,45,.11);transform:translateY(-2px);border-color:#e0f7fa}
    .vshop-card-img{height:160px;background:linear-gradient(145deg,#e8f8fa,#d0f0f5);display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative;text-decoration:none;flex-shrink:0}
    .vshop-card-img img{width:100%;height:100%;object-fit:cover}
    .vshop-card-img svg{width:52px;height:52px;opacity:.6}
    .vshop-disc-badge{position:absolute;top:8px;left:8px;background:#00b4c8;color:#fff;font-size:11px;font-weight:800;padding:3px 8px;border-radius:20px;z-index:1}
    .vshop-out-badge{position:absolute;top:8px;right:8px;background:rgba(0,0,0,.55);color:#fff;font-size:10px;font-weight:700;padding:3px 7px;border-radius:20px}
    .vshop-card-body{padding:14px;display:flex;flex-direction:column;flex:1}
    .vshop-card-cat{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.8px;margin-bottom:4px}
    .vshop-card-name{font-size:14px;font-weight:700;color:#0d1f2d;line-height:1.35;text-decoration:none;display:block;margin-bottom:4px}
    .vshop-card-name:hover{color:#00b4c8}
    .vshop-card-desc{font-size:11px;color:#6b8599;line-height:1.5;flex:1;margin-bottom:10px}
    .vshop-price-orig{font-size:11px;color:#9ca3af;text-decoration:line-through}
    .vshop-price{font-size:20px;font-weight:900;color:#0d1f2d;line-height:1.1}
    .vshop-price.sale{color:#00b4c8}
    .vshop-price-unit{font-size:10px;color:#9ca3af;font-weight:400}
    .vshop-savings{font-size:10px;color:#059669;font-weight:700;margin-bottom:4px}
    .vshop-stock-ok{font-size:11px;color:#059669;font-weight:600;margin-bottom:8px}
    .vshop-stock-no{font-size:11px;color:#ef4444;font-weight:600;margin-bottom:8px}
    .vshop-btn{width:100%;padding:9px;background:#00b4c8;color:#fff;border:none;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;transition:background .15s}
    .vshop-btn:hover{background:#0097aa}
    .vshop-btn:disabled{background:#e2e8f0;cursor:not-allowed;color:#9ca3af}
    /* View toggle */
    .vshop-view-toggle{display:flex;gap:4px;margin-left:auto}
    .vshop-vbtn{padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:8px;background:#fff;cursor:pointer;font-size:14px;color:#94a3b8;transition:.12s;line-height:1}
    .vshop-vbtn.active{background:#0d1f2d;border-color:#0d1f2d;color:#fff}
    /* List view */
    .vshop-grid.list-view{grid-template-columns:1fr;gap:10px}
    .vshop-grid.list-view .vshop-card{flex-direction:row;border-radius:10px}
    .vshop-grid.list-view .vshop-card-img{width:130px;height:auto;min-height:110px;flex-shrink:0;border-radius:10px 0 0 10px}
    .vshop-grid.list-view .vshop-card-body{padding:14px 16px;flex-direction:row;flex-wrap:wrap;align-items:center;gap:0}
    .vshop-grid.list-view .vshop-card-cat{width:100%;margin-bottom:2px}
    .vshop-grid.list-view .vshop-card-name{font-size:15px;flex:1;margin-bottom:0;margin-right:16px}
    .vshop-grid.list-view .vshop-card-desc{display:none}
    .vshop-grid.list-view .vshop-price{font-size:18px}
    .vshop-grid.list-view .vshop-stock-ok,.vshop-grid.list-view .vshop-stock-no{margin-left:12px;margin-bottom:0}
    .vshop-grid.list-view .vshop-btn{width:auto;padding:8px 16px;margin-left:auto;flex-shrink:0;white-space:nowrap}
    /* Pagination */
    .vshop-pag{display:flex;gap:6px;align-items:center;justify-content:center;margin-top:24px;flex-wrap:wrap}
    .vshop-pag a,.vshop-pag span{padding:7px 13px;border-radius:8px;font-size:13px;font-weight:600;text-decoration:none;border:1.5px solid #e2e8f0;color:#4b6174;background:#fff;transition:.12s}
    .vshop-pag a:hover{background:#f0f9ff;border-color:#00b4c8;color:#00b4c8}
    .vshop-pag span.current{background:#00b4c8;border-color:#00b4c8;color:#fff}
    .vshop-pag span.dots{border:none;background:none;color:#94a3b8}
    /* FAB + toast */
    .vshop-fab{position:fixed;bottom:24px;right:24px;z-index:999}
    .vshop-fab a{display:flex;align-items:center;gap:10px;background:#0d1f2d;color:#fff;padding:13px 20px;border-radius:50px;text-decoration:none;font-weight:700;font-size:14px;box-shadow:0 4px 20px rgba(13,31,45,.3)}
    .vshop-fab-badge{background:#00b4c8;color:#fff;font-size:11px;font-weight:800;padding:2px 8px;border-radius:20px}
    .vshop-toast{display:none;position:fixed;bottom:84px;right:24px;z-index:10000;background:#0d1f2d;color:#fff;padding:11px 18px;border-radius:9px;font-size:13px;font-weight:600;box-shadow:0 4px 16px rgba(0,0,0,.22);animation:vfadeIn .18s}
    @keyframes vfadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:none}}
    </style>

    <div class="vshop-wrap">

    <?php if ( $eff_discount > 0 ) : ?>
    <div class="vshop-cam">
      <div class="vshop-cam-badge">-<?php echo (int) $eff_discount; ?>%</div>
      <div>
        <div style="font-weight:700;color:#0d1f2d;font-size:14px">🏷️ <?php echo esc_html( $eff_locked ? $eff_name . ' (lukustatud)' : $eff_name ); ?></div>
        <?php if ( $grid_campaign && $grid_campaign->valid_until ) : ?><div style="font-size:12px;color:#0097aa;font-weight:600;margin-top:2px">Kehtib kuni <?php echo esc_html( date_i18n( 'd.m.Y', strtotime( $grid_campaign->valid_until ) ) ); ?></div><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="vshop-layout">

    <!-- ── LEFT SIDEBAR ────────────────────────────────── -->
    <aside class="vshop-sidebar">
      <?php if ( ! empty( $managed_cats ) ) : ?>
      <div class="vshop-sidebar-box">
        <div class="vshop-sidebar-header">
          <span class="vshop-sidebar-header-icon">▼</span>
          <span class="vshop-sidebar-header-text">Kategooriad</span>
        </div>
        <?php
        $all_href = add_query_arg( array_filter( ['vs'=>$search,'sort'=>$sort==='name'?null:$sort] ), $page_url );
        ?>
        <a href="<?php echo esc_url($all_href); ?>" class="vshop-cat-link <?php echo !$category?'active':''; ?>">
          <span class="vshop-cat-dot" style="background:#0d1f2d"></span>
          Kõik tooted
          <span class="vshop-cat-count"><?php echo array_sum($cat_cnts); ?></span>
        </a>
        <?php foreach ( $managed_cats as $cat ) :
          $is_active = $category === $cat->name;
          $href = add_query_arg( array_filter(['vs'=>$search,'cat'=>$cat->name,'sort'=>$sort==='name'?null:$sort]), $page_url );
        ?>
        <a href="<?php echo esc_url($href); ?>" class="vshop-cat-link <?php echo $is_active?'active':''; ?>">
          <span class="vshop-cat-dot" style="background:<?php echo esc_attr($cat->color); ?>"></span>
          <?php echo esc_html($cat->name); ?>
          <span class="vshop-cat-count"><?php echo $cat_cnts[$cat->name] ?? 0; ?></span>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </aside>

    <!-- ── RIGHT CONTENT ──────────────────────────────── -->
    <div>
      <div class="vshop-topbar">
        <form class="vshop-search-form" method="GET" action="<?php echo esc_url($page_url); ?>">
          <?php if ($category) echo '<input type="hidden" name="cat" value="'.esc_attr($category).'">'; ?>
          <?php if ($sort!=='name') echo '<input type="hidden" name="sort" value="'.esc_attr($sort).'">'; ?>
          <input type="search" name="vs" value="<?php echo esc_attr($search); ?>" placeholder="Otsi toodet...">
          <button type="submit">Otsi</button>
        </form>
        <select class="vshop-sort" onchange="window.location=this.value">
          <?php
          $sort_opts=['name'=>'Nimi A–Z','price_asc'=>'Hind ↑','price_desc'=>'Hind ↓'];
          foreach($sort_opts as $v=>$l){
            $href=add_query_arg(array_filter(['vs'=>$search,'cat'=>$category,'sort'=>$v]),$page_url);
            echo '<option value="'.esc_url($href).'"'.selected($sort,$v,false).'>'.esc_html($l).'</option>';
          }
          ?>
        </select>
        <div class="vshop-view-toggle">
          <button class="vshop-vbtn" id="vbtn-grid" onclick="vshopSetView('grid')" title="Grid">⊞</button>
          <button class="vshop-vbtn" id="vbtn-list" onclick="vshopSetView('list')" title="Nimekiri">☰</button>
        </div>
      </div>
      <div class="vshop-results">
        <?php echo $total_items; ?> toodet<?php if($search) echo ' · "'.esc_html($search).'"'; if($category) echo ' · '.esc_html($category); ?>
        <?php if($total_pages>1) echo ' · lk '.$paged.'/'.$total_pages; ?>
      </div>

      <?php if ( empty($items) ) : ?>
      <div style="text-align:center;padding:60px 20px;color:#6b8599">
        <div style="font-size:48px;margin-bottom:14px">🔍</div>
        <p style="font-size:15px"><?php echo $search||$category?'Tooteid ei leitud.':'Pood on varsti avatud.'; ?></p>
        <?php if($search||$category): ?>
        <a href="<?php echo esc_url($page_url); ?>" style="display:inline-block;margin-top:12px;padding:9px 20px;background:#00b4c8;color:#fff;border-radius:8px;text-decoration:none;font-weight:600;font-size:13px">← Kõik tooted</a>
        <?php endif; ?>
      </div>
      <?php else : ?>
      <div class="vshop-grid" id="vshop-grid">
      <?php foreach($items as $item):
        $in_stock  =$item->quantity>0;
        $orig      =(float)$item->shop_price;
        $disc      =$eff_discount>0?round($orig*(1-$eff_discount/100),2):null;
        $detail_url=add_query_arg(['shop_view'=>'product','pid'=>$item->id],$page_url);
        $desc_text =$item->shop_description?:$item->description;
        // category color
        $cat_color = '#00b4c8';
        foreach($managed_cats as $mc){ if($mc->name===$item->category){$cat_color=$mc->color;break;} }
      ?>
        <div class="vshop-card" onclick="location.href='<?php echo esc_js($detail_url); ?>'">
          <a href="<?php echo esc_url($detail_url); ?>" class="vshop-card-img">
            <?php if($disc!==null) echo '<span class="vshop-disc-badge">-'.(int)$eff_discount.'%</span>'; ?>
            <?php if(!$in_stock) echo '<span class="vshop-out-badge">Otsas</span>'; ?>
            <?php if(!empty($item->image_url)): ?>
              <img src="<?php echo esc_url($item->image_url); ?>" alt="<?php echo esc_attr($item->name); ?>" loading="lazy">
            <?php else: ?>
              <div class="vsho-no-img">Pilt puudub</div>
            <?php endif; ?>
          </a>
          <div class="vshop-card-body">
            <?php if($item->category): ?>
            <div class="vshop-card-cat" style="color:<?php echo esc_attr($cat_color); ?>"><?php echo esc_html($item->category); ?></div>
            <?php endif; ?>
            <a href="<?php echo esc_url($detail_url); ?>" class="vshop-card-name"><?php echo esc_html($item->name); ?></a>
            <?php if($desc_text) echo '<div class="vshop-card-desc">'.esc_html(wp_trim_words($desc_text,12,'…')).'</div>'; ?>
            <?php if($disc!==null): ?>
            <div class="vshop-price-orig"><?php echo number_format($orig,2,',',' '); ?> €</div>
            <div class="vshop-price sale"><?php echo number_format($disc,2,',',' '); ?> € <span class="vshop-price-unit">/ <?php echo esc_html($item->unit?:'tk'); ?></span></div>
            <div class="vshop-savings">Säästad <?php echo number_format($orig-$disc,2,',',' '); ?> €</div>
            <?php else: ?>
            <div class="vshop-price"><?php echo number_format($orig,2,',',' '); ?> € <span class="vshop-price-unit">/ <?php echo esc_html($item->unit?:'tk'); ?></span></div>
            <?php endif; ?>
            <?php if($in_stock): ?>
            <div class="vshop-stock-ok">✓ Laos (<?php echo (int)$item->quantity; ?> <?php echo esc_html($item->unit?:'tk'); ?>)</div>
            <?php else: ?>
            <div class="vshop-stock-no">✗ Laost otsas</div>
            <?php endif; ?>
            <button class="vshop-btn" onclick="event.stopPropagation();veshoAddToCart(<?php echo $item->id; ?>,'<?php echo esc_js($item->name); ?>')" <?php echo $in_stock?'':'disabled'; ?>>🛒 Lisa korvi</button>
          </div>
        </div>
      <?php endforeach; ?>
      </div>

      <?php if($total_pages>1): ?>
      <div class="vshop-pag">
        <?php
        $pag_base = add_query_arg(array_filter(['vs'=>$search,'cat'=>$category,'sort'=>$sort==='name'?null:$sort]),$page_url);
        for($p=1;$p<=$total_pages;$p++){
          if($p===$paged){
            echo '<span class="current">'.$p.'</span>';
          } elseif($p===1||$p===$total_pages||abs($p-$paged)<=2){
            $href=add_query_arg('paged',$p,$pag_base);
            echo '<a href="'.esc_url($href).'">'.$p.'</a>';
          } elseif(abs($p-$paged)===3){
            echo '<span class="dots">…</span>';
          }
        }
        ?>
      </div>
      <?php endif; ?>

      <?php endif; ?>
    </div><!-- right -->

    </div><!-- .vshop-layout -->

    </div><!-- .vshop-wrap -->

    <div class="vshop-fab" id="vesho-cart-fab" <?php echo $cart_count > 0 ? '' : 'style="display:none"'; ?>>
      <a href="<?php echo esc_url( add_query_arg( 'shop_view', 'cart', $page_url ) ); ?>">
        🛒 Ostukorv <span class="vshop-fab-badge" id="vesho-cart-count"><?php echo $cart_count; ?></span>
      </a>
    </div>
    <div class="vshop-toast" id="vesho-toast"></div>

    <script>
    var veshoCartNonce = '<?php echo esc_js( $cart_nonce ); ?>';
    var ajaxurl = '<?php echo esc_js( $ajax_url ); ?>';

    function veshoUpdateCartBadge(count) {
      var fab = document.getElementById('vesho-cart-fab');
      var badge = document.getElementById('vesho-cart-count');
      if (badge) badge.textContent = count;
      if (fab) fab.style.display = count > 0 ? '' : 'none';
    }

    function showToast(msg) {
      var t = document.getElementById('vesho-toast');
      t.textContent = msg; t.style.display = 'block';
      setTimeout(function(){ t.style.display = 'none'; }, 2800);
    }

    function vshopSetView(v) {
      var grid=document.getElementById('vshop-grid');
      var bg=document.getElementById('vbtn-grid');
      var bl=document.getElementById('vbtn-list');
      if(!grid) return;
      if(v==='list'){grid.classList.add('list-view');bg.classList.remove('active');bl.classList.add('active');}
      else{grid.classList.remove('list-view');bg.classList.add('active');bl.classList.remove('active');}
      try{localStorage.setItem('vshopView',v);}catch(e){}
    }
    (function(){
      var v; try{v=localStorage.getItem('vshopView');}catch(e){}
      vshopSetView(v||'grid');
    })();

    function veshoAddToCart(pid, name) {
      var btn = event.currentTarget;
      btn.disabled = true; btn.textContent = '✓ Lisatud';
      var form = new FormData();
      form.append('action', 'vesho_cart_add');
      form.append('nonce', veshoCartNonce);
      form.append('pid', pid);
      form.append('qty', 1);
      fetch(ajaxurl, {method:'POST', body:form})
        .then(r=>r.json())
        .then(d=>{
          if (d.success) { veshoUpdateCartBadge(d.data.count); showToast('🛒 ' + name + ' lisatud!'); }
          setTimeout(function(){ btn.disabled = false; btn.innerHTML = '🛒 Lisa korvi'; }, 1800);
        });
    }
    </script>
    </div><!-- .vsho-root -->
    <?php
    return ob_get_clean();
} );

// ── [vesho_shop] = peamine shortcode (hero=1 vaikimisi) ──────────────────────
// [vesho_shop]        → hero + pood (live sait)
// [vesho_shop hero=0] → ainult pood (Elementor kus hero on eraldi)
// [vesho_shop_grid]   → alias hero=0 jaoks (backward compat)
add_shortcode( 'vesho_shop', function ( $atts ) {
    if ( ! isset( $atts['hero'] ) ) $atts['hero'] = '1';
    return do_shortcode( '[vesho_shop_grid hero="' . ( $atts['hero'] === '0' ? '0' : '1' ) . '"]' );
} );
