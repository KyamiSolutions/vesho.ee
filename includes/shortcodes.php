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
add_shortcode( 'vesho_services_cards', function( $atts ) {
    $atts = shortcode_atts( ['limit' => 12, 'all' => '0', 'debug' => '0'], $atts );
    $limit = max(1, intval($atts['limit']));
    $show_all = $atts['all'] === '1';
    $debug    = $atts['debug'] === '1';
    global $wpdb;
    $all_services = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}vesho_services WHERE active=1 ORDER BY sort_order ASC, id ASC LIMIT %d",
        100
    ) );

    // Filter by show_on_website in PHP (robust — works even if column doesn't exist yet)
    if ( ! $show_all ) {
        $web = array_filter( $all_services, fn($s) => ! empty( $s->show_on_website ) );
        $all_services = ! empty($web) ? array_values($web) : $all_services;
    }
    $services = array_slice( $all_services, 0, $limit );

    if ( $debug ) {
        $raw = $wpdb->get_results( "SELECT id, name, active, show_on_website FROM {$wpdb->prefix}vesho_services ORDER BY id ASC" );
        $out = '<div style="background:#1e293b;color:#e2e8f0;padding:16px;border-radius:8px;margin-bottom:20px;font-family:monospace;font-size:13px">';
        $out .= '<strong style="color:#00b4c8">🛠 vesho_services_cards debug</strong><br><br>';
        $out .= '<strong>show_all:</strong> ' . ($show_all?'jah':'ei') . ' | <strong>limit:</strong> ' . $limit . ' | <strong>kuvatavaid:</strong> ' . count($services) . '<br><br>';
        $out .= '<table style="border-collapse:collapse;width:100%"><tr style="color:#94a3b8"><th style="text-align:left;padding:4px 8px">ID</th><th style="text-align:left;padding:4px 8px">Nimi</th><th style="padding:4px 8px">active</th><th style="padding:4px 8px">show_on_website</th></tr>';
        foreach ( $raw as $r ) {
            $web_val = property_exists($r,'show_on_website') ? $r->show_on_website : '<em style="color:#f87171">VEERG PUUDUB</em>';
            $out .= '<tr style="border-top:1px solid #334155"><td style="padding:4px 8px">'.$r->id.'</td><td style="padding:4px 8px">'.esc_html($r->name).'</td><td style="text-align:center;padding:4px 8px">'.$r->active.'</td><td style="text-align:center;padding:4px 8px">'.$web_val.'</td></tr>';
        }
        $out .= '</table></div>';
        echo $out;
    }

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

// ── [vesho_shop] ─────────────────────────────────────────────────────────────
// Helper: read cart from session, enrich with DB product data
if ( ! function_exists('_vsho_cart') ) :
function _vsho_cart( $wpdb ) {
    if ( session_status() === PHP_SESSION_NONE ) session_start();
    $raw = $_SESSION['vesho_cart'] ?? [];
    $empty = ['items'=>[],'count'=>0,'subtotal'=>0.0,'vat'=>0.0,'total'=>0.0];
    if ( empty($raw) ) return $empty;
    $ids = array_values( array_filter( array_map('absint', array_keys($raw)) ) );
    if ( ! $ids ) return $empty;
    $ph  = implode(',', array_fill(0, count($ids), '%d'));
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT id,name,category,shop_price,image_url,unit
         FROM {$wpdb->prefix}vesho_inventory
         WHERE id IN($ph) AND shop_price>0 AND archived=0",
        ...$ids
    ) );
    $items = []; $sub = 0.0;
    foreach ( $rows as $r ) {
        $qty  = max(1, (int)( $raw[$r->id] ?? 0 ));
        $line = round( (float)$r->shop_price * $qty, 2 );
        $sub += $line;
        $items[] = ['p'=>$r,'qty'=>$qty,'line'=>$line];
    }
    $vr  = (float)get_option('vesho_vat_rate','24') / 100;
    $vat = round( $sub * $vr, 2 );
    return ['items'=>$items,'count'=>(int)array_sum($raw),'subtotal'=>$sub,'vat'=>$vat,'total'=>round($sub+$vat,2)];
}
endif;

add_shortcode( 'vesho_shop', function( $atts ) {
    global $wpdb;
    if ( session_status() === PHP_SESSION_NONE ) session_start();

    $view     = sanitize_key( $_GET['shop_view'] ?? 'grid' );
    $page_url = get_permalink() ?: home_url('/');
    $nonce    = wp_create_nonce('vesho_cart_nonce');
    $ajax_url = admin_url('admin-ajax.php');
    $cart     = _vsho_cart( $wpdb );

    $shop_url = remove_query_arg(['shop_view','pid'], $page_url);
    $cart_url = add_query_arg('shop_view','cart', $page_url);
    $chk_url  = add_query_arg('shop_view','checkout', $page_url);

    // Payment-gateway return overrides
    if ( ! empty($_GET['vesho_shop_mc_return']) || ! empty($_GET['vesho_shop_montonio_return']) ) $view = 'success';

    // Active shop campaign
    $today    = date('Y-m-d');
    $campaign = $wpdb->get_row(
        "SELECT * FROM {$wpdb->prefix}vesho_campaigns
         WHERE paused=0
           AND (valid_from IS NULL OR valid_from<='$today')
           AND (valid_until IS NULL OR valid_until>='$today')
           AND (target='epood' OR target='both')
         ORDER BY discount_percent DESC LIMIT 1"
    );
    $camp_disc = $campaign ? (float)$campaign->discount_percent : 0;

    // Logged-in client + loyalty discount
    $client = null;
    if ( is_user_logged_in() ) {
        $client = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vesho_clients WHERE user_id=%d LIMIT 1",
            get_current_user_id()
        ) );
    }
    // Global loyalty discount for all registered clients (from settings)
    $global_loyalty = is_user_logged_in() ? (float)get_option('vesho_shop_loyalty_discount', 0) : 0;
    // Per-client loyalty from DB (may override global)
    $client_disc = $client ? max( (float)($client->loyalty_pct ?? 0), $global_loyalty ) : $global_loyalty;
    $eff_disc    = max( $camp_disc, $client_disc ); // effective discount %

    // VAT rate from settings
    $vat_rate = (float)get_option('vesho_vat_rate','24') / 100;
    $vat_pct  = (int)round($vat_rate * 100); // e.g. 24

    // Payment settings
    // Payment — checkbox AND keys must both be set (matches admin settings panel)
    $stripe_enabled   = get_option('vesho_stripe_enabled','0')==='1'
                        && get_option('vesho_stripe_pub_key','')
                        && get_option('vesho_stripe_secret_key','');
    $stripe_pub_key   = (string) get_option('vesho_stripe_pub_key','');
    $mc_enabled       = get_option('vesho_mc_enabled','0')==='1'
                        && get_option('vesho_mc_shop_id','')
                        && get_option('vesho_mc_secret_key','');
    $montonio_enabled = get_option('vesho_montonio_enabled','0')==='1'
                        && get_option('vesho_montonio_access_key','')
                        && get_option('vesho_montonio_secret_key','');

    // Delivery options — option keys match Seaded → E-pood admin panel
    $del_opts = [];
    if ( get_option('vesho_shop_ship_pickup_enabled','1')!=='0' )
        $del_opts[] = ['id'=>'pickup', 'label'=>'Kaupluses pealekorjamine', 'icon'=>'🏪', 'price'=>(float)get_option('vesho_shop_ship_pickup_price','0')];
    if ( get_option('vesho_shop_ship_courier_enabled','1')!=='0' )
        $del_opts[] = ['id'=>'courier','label'=>'Kuller',                    'icon'=>'🚚','price'=>(float)get_option('vesho_shop_ship_courier_price','0')];
    if ( get_option('vesho_shop_ship_parcelshop_enabled','1')!=='0' )
        $del_opts[] = ['id'=>'omniva', 'label'=>'Pakiautomaat (Omniva/DPD)', 'icon'=>'📦','price'=>(float)get_option('vesho_shop_ship_parcelshop_price','0')];
    if ( empty($del_opts) )
        $del_opts[] = ['id'=>'pickup','label'=>'Pealekorjamine','icon'=>'🏪','price'=>0.0];

    // Checkout totals (used in JS; computed here so they're always defined)
    $sub_chk = 0.0; $vat_chk = 0.0;
    foreach ( $cart['items'] as $ci ) {
        $dp   = $eff_disc > 0 ? round( (float)$ci['p']->shop_price*(1-$eff_disc/100), 2 ) : (float)$ci['p']->shop_price;
        $sub_chk += round( $dp * $ci['qty'], 2 );
    }
    $sub_chk = round($sub_chk,2);
    $vat_chk = round($sub_chk*$vat_rate,2);

    ob_start(); ?>
<style>
#vshop{font-family:'Barlow',sans-serif;color:#1a2a38;line-height:1.5}
#vshop *{box-sizing:border-box}
#vshop a{text-decoration:none;color:inherit;transition:color .15s}
/* Cart bar */
#vshop .vs-cartbar{background:#fff;border-bottom:1px solid #dce8ef;padding:9px 0}
#vshop .vs-cartbar-inner{display:flex;align-items:center;justify-content:flex-end;gap:16px;max-width:1200px;margin:0 auto;padding:0 24px}
#vshop .vs-back-top{font-size:13px;color:#5a7080;margin-right:auto}
#vshop .vs-back-top:hover{color:#00b4c8}
#vshop .vs-cart-link{display:inline-flex;align-items:center;gap:6px;font-family:'Barlow Condensed',sans-serif;font-weight:700;font-size:14px;color:#0d1f2d}
#vshop .vs-cart-link:hover{color:#00b4c8}
#vshop .vs-cart-badge{background:#00b4c8;color:#fff;font-size:11px;font-weight:700;padding:2px 7px;border-radius:20px;min-width:20px;text-align:center;line-height:1.4}
/* Body bg */
#vshop .vs-bg{background:#f4f7f9;padding:28px 0;min-height:500px}
#vshop .vs-wrap{max-width:1200px;margin:0 auto;padding:0 24px}
/* Campaign banner */
#vshop .vs-camp{display:flex;align-items:center;gap:14px;background:#fff;border-left:4px solid #00b4c8;border-radius:8px;padding:12px 18px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,.06);flex-wrap:wrap}
#vshop .vs-camp-pct{background:#00b4c8;color:#fff;font-family:'Barlow Condensed',sans-serif;font-size:14px;font-weight:800;padding:5px 12px;border-radius:4px;white-space:nowrap;flex-shrink:0}
#vshop .vs-camp-name{font-weight:700;font-size:14px;color:#0d1f2d}
#vshop .vs-camp-date{font-size:12px;color:#5a7080;margin-top:2px}
/* Layout */
#vshop .vs-layout{display:flex;gap:20px;align-items:flex-start}
/* Sidebar */
#vshop .vs-sidebar{flex:0 0 230px;border-radius:8px;overflow:hidden;position:sticky;top:80px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
#vshop .vs-sb-hdr{background:#0d1f2d;color:#fff;font-family:'Barlow Condensed',sans-serif;font-size:12px;font-weight:700;letter-spacing:.1em;padding:12px 16px;margin:0;text-transform:uppercase}
#vshop .vs-cat-btn{display:flex;align-items:center;gap:10px;padding:11px 16px;cursor:pointer;border:none;border-bottom:1px solid #dce8ef;background:#fff;width:100%;text-align:left;font-family:'Barlow',sans-serif;font-size:13px;color:#1a2a38;font-weight:500;transition:background .12s}
#vshop .vs-cat-btn:last-child{border-bottom:none}
#vshop .vs-cat-btn:hover{background:#f4f7f9}
#vshop .vs-cat-btn.active{background:#e0f7fa;color:#006d7e;font-weight:700}
#vshop .vs-cat-dot{width:9px;height:9px;border-radius:50%;flex-shrink:0;display:inline-block}
#vshop .vs-cat-cnt{margin-left:auto;background:#e8eef2;color:#5a7080;font-size:11px;font-weight:700;padding:2px 7px;border-radius:20px;min-width:22px;text-align:center}
#vshop .vs-cat-btn.active .vs-cat-cnt{background:#00b4c8;color:#fff}
/* Main */
#vshop .vs-main{flex:1;min-width:0}
#vshop .vs-toolbar{display:flex;align-items:center;gap:10px;margin-bottom:16px;background:#fff;border-radius:8px;padding:10px 14px;box-shadow:0 2px 8px rgba(0,0,0,.06);flex-wrap:wrap}
#vshop .vs-search{flex:1;min-width:160px;padding:9px 12px;border:1px solid #dce8ef;border-radius:4px;font-family:'Barlow',sans-serif;font-size:13px;color:#1a2a38;outline:none;background:#f4f7f9;transition:border .15s}
#vshop .vs-search:focus{border-color:#00b4c8;background:#fff}
#vshop .vs-result-txt{font-size:13px;color:#5a7080;white-space:nowrap}
#vshop .vs-sort{border:1px solid #dce8ef;border-radius:4px;padding:9px 10px;font-family:'Barlow',sans-serif;font-size:13px;color:#1a2a38;background:#f4f7f9;cursor:pointer;outline:none;margin-left:auto}
/* Product grid */
#vshop .vs-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:16px}
#vshop .vs-card{background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06);transition:box-shadow .15s,transform .15s;display:flex;flex-direction:column}
#vshop .vs-card:hover{box-shadow:0 6px 20px rgba(0,0,0,.12);transform:translateY(-2px)}
#vshop .vs-card-img-wrap{position:relative;height:175px;background:#e8eef2;overflow:hidden;display:block}
#vshop .vs-card-img-wrap img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .3s}
#vshop .vs-card:hover .vs-card-img-wrap img{transform:scale(1.04)}
#vshop .vs-no-img{display:flex;align-items:center;justify-content:center;height:100%;color:#94a3b8;font-family:'Barlow',sans-serif;font-size:.8rem;font-weight:500;letter-spacing:.5px}
#vshop .vs-sale-badge{position:absolute;top:10px;left:10px;background:#e11d48;color:#fff;font-family:'Barlow Condensed',sans-serif;font-size:12px;font-weight:700;padding:3px 8px;border-radius:4px}
#vshop .vs-stock-badge{position:absolute;top:10px;left:10px;background:#64748b;color:#fff;font-family:'Barlow Condensed',sans-serif;font-size:12px;font-weight:700;padding:3px 8px;border-radius:4px}
#vshop .vs-add-btn--oos{background:#94a3b8!important;cursor:not-allowed!important;opacity:.8}
#vshop .vs-stock-ok{font-size:13px;font-weight:600;color:#16a34a;margin:4px 0}
#vshop .vs-stock-out{font-size:13px;font-weight:600;color:#dc2626;margin:4px 0}
#vshop .vs-notify-wrap{margin-top:6px}
#vshop .vs-notify-btn{width:100%;padding:7px;background:transparent;border:1px solid #00b4c8;color:#00b4c8;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer}
#vshop .vs-notify-form{display:flex;gap:6px;margin-top:6px}
#vshop .vs-notify-email{flex:1;padding:7px 10px;border:1px solid #dce8ef;border-radius:6px;font-size:13px;min-width:0}
#vshop .vs-notify-submit{padding:7px 12px;background:#00b4c8;color:#fff;border:none;border-radius:6px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap}
#vshop .vs-card-body{padding:14px 16px;flex:1;display:flex;flex-direction:column;gap:5px}
#vshop .vs-card-cat{font-size:11px;font-weight:600;color:#00b4c8;text-transform:uppercase;letter-spacing:.05em}
#vshop .vs-card-name{font-family:'Barlow Condensed',sans-serif;font-size:16px;font-weight:700;color:#0d1f2d;line-height:1.25}
#vshop .vs-card-name a:hover{color:#00b4c8}
#vshop .vs-card-desc{font-size:12px;color:#5a7080;line-height:1.55;flex:1}
#vshop .vs-card-price{font-family:'Barlow Condensed',sans-serif;font-size:20px;font-weight:800;color:#0d1f2d;margin-top:2px}
#vshop .vs-price-orig{font-size:13px;color:#94a3b8;text-decoration:line-through;margin-right:4px}
#vshop .vs-price-disc{color:#00b4c8}
#vshop .vs-card-unit{font-size:11px;color:#5a7080;font-weight:400;font-family:'Barlow',sans-serif}
#vshop .vs-add-btn{margin-top:auto;padding:10px 0;background:#00b4c8;color:#fff;border:none;border-radius:4px;font-family:'Barlow Condensed',sans-serif;font-size:14px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;cursor:pointer;transition:background .15s;width:100%}
#vshop .vs-add-btn:hover{background:#008fa0}
#vshop .vs-add-btn.added{background:#16a34a}
/* Product detail */
#vshop .vs-back-link{display:inline-flex;align-items:center;gap:6px;font-size:13px;color:#5a7080;margin-bottom:20px;font-weight:600}
#vshop .vs-back-link:hover{color:#00b4c8}
#vshop .vs-detail{background:#fff;border-radius:8px;padding:32px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
#vshop .vs-detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:36px;align-items:start}
#vshop .vs-detail-img-wrap{border-radius:8px;overflow:hidden;background:#e8eef2;aspect-ratio:1/1;display:flex;align-items:center;justify-content:center}
#vshop .vs-detail-img-wrap img{width:100%;height:100%;object-fit:cover;display:block}
#vshop .vs-detail-cat{display:inline-block;font-size:11px;font-weight:700;color:#fff;background:#00b4c8;border-radius:4px;padding:3px 10px;margin-bottom:12px;text-transform:uppercase;letter-spacing:.05em}
#vshop .vs-detail-name{font-family:'Barlow Condensed',sans-serif;font-size:30px;font-weight:800;color:#0d1f2d;line-height:1.1;margin-bottom:14px}
#vshop .vs-detail-price{font-family:'Barlow Condensed',sans-serif;font-size:34px;font-weight:900;color:#0d1f2d;margin-bottom:6px;line-height:1}
#vshop .vs-detail-price-orig{font-size:18px;color:#94a3b8;text-decoration:line-through;margin-right:8px}
#vshop .vs-detail-price-disc{color:#00b4c8}
#vshop .vs-disc-note{font-size:13px;color:#16a34a;font-weight:600;margin-bottom:14px}
#vshop .vs-detail-desc{font-size:15px;color:#4b6174;line-height:1.7;margin-bottom:20px}
#vshop .vs-qty-row{display:flex;align-items:center;gap:12px;margin-bottom:14px;flex-wrap:wrap}
#vshop .vs-qty{display:flex;align-items:center;border:1px solid #dce8ef;border-radius:4px;overflow:hidden}
#vshop .vs-qty-btn{width:36px;height:40px;background:#f4f7f9;border:none;cursor:pointer;font-size:18px;color:#0d1f2d;transition:background .12s;font-family:'Barlow',sans-serif;line-height:1}
#vshop .vs-qty-btn:hover{background:#e0f7fa;color:#00b4c8}
#vshop .vs-qty input{width:52px;height:40px;text-align:center;border:none;border-left:1px solid #dce8ef;border-right:1px solid #dce8ef;font-family:'Barlow',sans-serif;font-size:15px;font-weight:600;color:#0d1f2d;outline:none}
#vshop .vs-add-big-btn{padding:13px 28px;background:#00b4c8;color:#fff;border:none;border-radius:4px;font-family:'Barlow Condensed',sans-serif;font-size:16px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;transition:background .15s}
#vshop .vs-add-big-btn:hover{background:#008fa0}
#vshop .vs-add-big-btn.added{background:#16a34a}
/* Shared buttons */
#vshop .vs-btn-outline{display:inline-flex;align-items:center;gap:6px;padding:11px 22px;background:transparent;color:#0d1f2d;border:2px solid #dce8ef;border-radius:4px;font-family:'Barlow Condensed',sans-serif;font-size:13px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;cursor:pointer;transition:all .15s;text-decoration:none}
#vshop .vs-btn-outline:hover{border-color:#0d1f2d;color:#0d1f2d}
#vshop .vs-btn-primary{display:inline-flex;align-items:center;gap:6px;padding:11px 22px;background:#00b4c8;color:#fff;border:none;border-radius:4px;font-family:'Barlow Condensed',sans-serif;font-size:13px;font-weight:700;letter-spacing:.05em;text-transform:uppercase;cursor:pointer;transition:background .15s;text-decoration:none}
#vshop .vs-btn-primary:hover{background:#008fa0;color:#fff}
/* Cart view */
#vshop .vs-cart-wrap{max-width:880px;margin:0 auto}
#vshop .vs-page-title{font-family:'Barlow Condensed',sans-serif;font-size:28px;font-weight:800;color:#0d1f2d;margin-bottom:22px}
#vshop .vs-cart-table{width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06)}
#vshop .vs-cart-table th{background:#0d1f2d;color:#fff;font-family:'Barlow Condensed',sans-serif;font-size:11px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;padding:12px 16px;text-align:left}
#vshop .vs-cart-table td{padding:13px 16px;border-bottom:1px solid #e8eef2;vertical-align:middle;font-size:14px}
#vshop .vs-cart-table tr:last-child td{border-bottom:none}
#vshop .vs-cart-prod{display:flex;align-items:center;gap:12px}
#vshop .vs-cart-thumb{width:50px;height:50px;border-radius:6px;object-fit:cover;flex-shrink:0;background:#e8eef2}
#vshop .vs-thumb-ph{width:50px;height:50px;border-radius:6px;background:#e8eef2;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:10px;text-align:center;line-height:1.3;flex-shrink:0}
#vshop .vs-cart-name{font-weight:600;color:#0d1f2d}
#vshop .vs-cart-cat{font-size:11px;color:#5a7080;margin-top:2px}
#vshop .vs-cart-price{font-family:'Barlow Condensed',sans-serif;font-size:15px;font-weight:700;color:#0d1f2d}
#vshop .vs-cart-qty{display:flex;align-items:center;border:1px solid #dce8ef;border-radius:4px;overflow:hidden;width:fit-content}
#vshop .vs-cart-qty-btn{width:29px;height:31px;background:#f4f7f9;border:none;cursor:pointer;font-size:15px;color:#0d1f2d;transition:background .12s;line-height:1}
#vshop .vs-cart-qty-btn:hover{background:#e0f7fa}
#vshop .vs-cart-qty input{width:38px;height:31px;text-align:center;border:none;border-left:1px solid #dce8ef;border-right:1px solid #dce8ef;font-family:'Barlow',sans-serif;font-size:13px;font-weight:600;color:#0d1f2d;outline:none}
#vshop .vs-remove-btn{background:none;border:none;color:#94a3b8;cursor:pointer;font-size:20px;padding:2px 6px;line-height:1;transition:color .12s}
#vshop .vs-remove-btn:hover{color:#e11d48}
#vshop .vs-cart-summary{background:#fff;border-radius:8px;padding:22px 24px;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-top:18px}
#vshop .vs-sum-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;font-size:14px;color:#4b6174;border-bottom:1px solid #e8eef2}
#vshop .vs-sum-row:last-of-type{border-bottom:none}
#vshop .vs-sum-row.vs-total{border-bottom:none;padding-top:12px;font-family:'Barlow Condensed',sans-serif;font-size:22px;font-weight:800;color:#0d1f2d}
#vshop .vs-cart-actions{display:flex;gap:12px;margin-top:16px;flex-wrap:wrap}
/* Empty / result pages */
#vshop .vs-empty{text-align:center;padding:52px 24px;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
#vshop .vs-empty-icon{font-size:48px;margin-bottom:14px}
#vshop .vs-empty-txt{font-size:15px;color:#5a7080;margin-bottom:20px}
#vshop .vs-result{text-align:center;padding:60px 24px;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
#vshop .vs-result-circle{width:76px;height:76px;border-radius:50%;margin:0 auto 22px;display:flex;align-items:center;justify-content:center;font-size:2rem;color:#fff}
#vshop .vs-result h2{font-family:'Barlow Condensed',sans-serif;font-size:28px;font-weight:800;color:#0d1f2d;margin-bottom:10px}
#vshop .vs-result p{color:#5a7080;font-size:15px;margin-bottom:24px}
#vshop .vs-result-btns{display:flex;gap:12px;justify-content:center;flex-wrap:wrap}
/* Checkout */
#vshop .vs-chk-grid{display:grid;grid-template-columns:3fr 2fr;gap:22px;align-items:start}
#vshop .vs-chk-block{background:#fff;border-radius:8px;padding:22px 24px;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:16px}
#vshop .vs-chk-block:last-child{margin-bottom:0}
#vshop .vs-chk-block h3{font-family:'Barlow Condensed',sans-serif;font-size:16px;font-weight:700;color:#0d1f2d;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid #e8eef2;letter-spacing:.03em}
#vshop .vs-field{margin-bottom:13px}
#vshop .vs-field label{display:block;font-size:11px;font-weight:700;color:#0d1f2d;margin-bottom:5px;letter-spacing:.06em;text-transform:uppercase}
#vshop .vs-field input,#vshop .vs-field select{width:100%;padding:10px 12px;border:1px solid #dce8ef;border-radius:4px;font-family:'Barlow',sans-serif;font-size:14px;color:#1a2a38;outline:none;background:#fff;transition:border .15s}
#vshop .vs-field input:focus,#vshop .vs-field select:focus{border-color:#00b4c8;box-shadow:0 0 0 3px rgba(0,180,200,.10)}
#vshop .vs-field-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
#vshop .vs-del-opt{display:flex;align-items:center;gap:10px;padding:11px 14px;border:2px solid #e8eef2;border-radius:6px;cursor:pointer;margin-bottom:8px;transition:border .15s;user-select:none}
#vshop .vs-del-opt:hover{border-color:#00b4c8}
#vshop .vs-del-opt.sel{border-color:#00b4c8;background:#e0f7fa}
#vshop .vs-del-opt input[type=radio]{accent-color:#00b4c8;width:15px;height:15px;cursor:pointer;flex-shrink:0}
#vshop .vs-del-label{font-size:14px;font-weight:600;color:#0d1f2d;flex:1}
#vshop .vs-del-price{font-family:'Barlow Condensed',sans-serif;font-size:14px;font-weight:700;color:#00b4c8;white-space:nowrap}
#vshop .vs-pay-opt{display:flex;align-items:center;gap:10px;padding:11px 14px;border:2px solid #e8eef2;border-radius:6px;cursor:pointer;margin-bottom:8px;transition:border .15s;user-select:none}
#vshop .vs-pay-opt:hover{border-color:#00b4c8}
#vshop .vs-pay-opt.sel{border-color:#00b4c8;background:#e0f7fa}
#vshop .vs-pay-opt input[type=radio]{accent-color:#00b4c8;width:15px;height:15px;cursor:pointer;flex-shrink:0}
#vshop .vs-pay-lbl{font-size:14px;font-weight:500;color:#1a2a38}
#vshop .vs-chk-item{display:flex;justify-content:space-between;align-items:flex-start;padding:9px 0;border-bottom:1px solid #e8eef2;font-size:13px}
#vshop .vs-chk-item:last-child{border-bottom:none;margin-bottom:8px}
#vshop .vs-chk-item-name{font-weight:600;color:#0d1f2d}
#vshop .vs-chk-item-sub{font-size:11px;color:#5a7080;margin-top:1px}
#vshop .vs-chk-item-price{font-family:'Barlow Condensed',sans-serif;font-weight:700;color:#0d1f2d;flex-shrink:0;margin-left:8px}
#vshop .vs-submit-btn{width:100%;padding:13px;background:#00b4c8;color:#fff;border:none;border-radius:4px;font-family:'Barlow Condensed',sans-serif;font-size:16px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;cursor:pointer;transition:background .15s;margin-top:14px}
#vshop .vs-submit-btn:hover:not(:disabled){background:#008fa0}
#vshop .vs-submit-btn:disabled{opacity:.6;cursor:not-allowed}
#vshop .vs-alert{padding:11px 15px;border-radius:4px;font-size:14px;margin-bottom:14px}
#vshop .vs-alert-err{background:#fee2e2;color:#991b1b;border:1px solid #fecaca}
#vshop .vs-alert-ok{background:#dcfce7;color:#166534;border:1px solid #bbf7d0}
#vsStripeCard{padding:11px 12px;border:1px solid #dce8ef;border-radius:4px;background:#fff;margin-top:12px;display:none}
/* Responsive */
@media(max-width:900px){
  #vshop .vs-chk-grid{grid-template-columns:1fr}
  #vshop .vs-detail-grid{grid-template-columns:1fr}
}
@media(max-width:700px){
  #vshop .vs-layout{flex-direction:column}
  #vshop .vs-sidebar{flex:none;width:100%;position:static}
  #vshop .vs-cart-table th:nth-child(2),#vshop .vs-cart-table td:nth-child(2){display:none}
  #vshop .vs-wrap{padding:0 14px}
}
</style>
<div id="vshop">

<!-- Cart bar -->
<div class="vs-cartbar">
  <div class="vs-cartbar-inner">
    <a href="<?php echo esc_url($cart_url); ?>" class="vs-cart-link">
      🛒 Ostukorv
      <span class="vs-cart-badge" id="vsCartBadge" <?php if(!$cart['count'])echo 'style="display:none"'; ?>>
        <?php echo (int)$cart['count']; ?>
      </span>
    </a>
  </div>
</div>

<div class="vs-bg"><div class="vs-wrap">

<?php
/* ═══════════════════════════════════════════════════════ GRID ═══ */
if ( $view === 'grid' ) :
    $init_cat = sanitize_text_field( $_GET['cat'] ?? '' );
    $init_q   = sanitize_text_field( $_GET['q']   ?? '' );
    $products = $wpdb->get_results(
        "SELECT i.id,i.name,i.category,i.shop_price,i.shop_description,i.image_url,i.unit,i.quantity,
                COALESCE(c.color,'#00b4c8') as cat_color
         FROM {$wpdb->prefix}vesho_inventory i
         LEFT JOIN {$wpdb->prefix}vesho_inventory_categories c ON c.name=i.category
         WHERE i.shop_enabled=1 AND i.shop_price>0 AND i.archived=0
         ORDER BY i.category ASC,i.name ASC"
    );
    $db_cats = $wpdb->get_results(
        "SELECT c.name,c.color,COUNT(i.id) as cnt
         FROM {$wpdb->prefix}vesho_inventory_categories c
         LEFT JOIN {$wpdb->prefix}vesho_inventory i
           ON i.category=c.name AND i.shop_enabled=1 AND i.shop_price>0 AND i.archived=0
         GROUP BY c.id,c.name,c.color ORDER BY c.sort_order ASC,c.name ASC"
    );
    $total_count = count($products);
?>

<?php if ( $campaign && $camp_disc > 0 ) : ?>
<div class="vs-camp">
  <div class="vs-camp-pct">-<?php echo (int)$camp_disc; ?>%</div>
  <div>
    <div class="vs-camp-name">🏷️ <?php echo esc_html($campaign->name); ?></div>
    <?php if ( $campaign->valid_until ) : ?>
    <div class="vs-camp-date">Kehtib kuni <?php echo esc_html(date_i18n('d.m.Y', strtotime($campaign->valid_until))); ?></div>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div class="vs-layout">

  <!-- Sidebar -->
  <aside class="vs-sidebar">
    <p class="vs-sb-hdr">☰ Kategooriad</p>
    <button class="vs-cat-btn <?php echo !$init_cat?'active':''; ?>" data-cat="all">
      <span class="vs-cat-dot" style="background:#0d1f2d"></span>
      Kõik tooted
      <span class="vs-cat-cnt"><?php echo (int)$total_count; ?></span>
    </button>
    <?php foreach ( $db_cats as $cat ) : if ( (int)$cat->cnt < 1 ) continue; ?>
    <button class="vs-cat-btn <?php echo $init_cat===$cat->name?'active':''; ?>" data-cat="<?php echo esc_attr($cat->name); ?>">
      <span class="vs-cat-dot" style="background:<?php echo esc_attr($cat->color ?: '#00b4c8'); ?>"></span>
      <?php echo esc_html($cat->name); ?>
      <span class="vs-cat-cnt"><?php echo (int)$cat->cnt; ?></span>
    </button>
    <?php endforeach; ?>
  </aside>

  <!-- Main -->
  <div class="vs-main">
    <div class="vs-toolbar">
      <input type="text" class="vs-search" id="vsSearch" placeholder="Otsi tooteid…" autocomplete="off" value="<?php echo esc_attr($init_q); ?>">
      <span class="vs-result-txt" id="vsResultTxt"><?php echo (int)$total_count; ?> toodet</span>
      <select class="vs-sort" id="vsSort">
        <option value="name-asc">Nimi A–Z</option>
        <option value="name-desc">Nimi Z–A</option>
        <option value="price-asc">Hind ↑</option>
        <option value="price-desc">Hind ↓</option>
      </select>
    </div>

    <?php if ( empty($products) ) : ?>
    <div class="vs-empty">
      <div class="vs-empty-icon">📦</div>
      <div class="vs-empty-txt">Tooteid pole veel lisatud.</div>
    </div>
    <?php else : ?>
    <div class="vs-grid" id="vsGrid">
      <?php foreach ( $products as $p ) :
        $p_url      = esc_url( add_query_arg(['shop_view'=>'product','pid'=>$p->id], $page_url) );
        $p_disc     = round( (float)$p->shop_price * (1 - $eff_disc/100), 2 );
        $has_disc   = $eff_disc > 0;
        $p_in_stock = ( (int)($p->quantity ?? 0) ) > 0;
      ?>
      <div class="vs-card"
           data-cat="<?php echo esc_attr($p->category); ?>"
           data-name="<?php echo esc_attr(strtolower($p->name)); ?>"
           data-price="<?php echo (float)$p->shop_price; ?>">
        <a href="<?php echo $p_url; ?>" class="vs-card-img-wrap">
          <?php if ( !empty($p->image_url) ) : ?>
          <img src="<?php echo esc_url($p->image_url); ?>" alt="<?php echo esc_attr($p->name); ?>" loading="lazy">
          <?php else : ?>
          <div class="vs-no-img">Pilt puudub</div>
          <?php endif; ?>
          <?php if ( !$p_in_stock ) : ?>
          <span class="vs-stock-badge">Laos otsas</span>
          <?php elseif ( $has_disc ) : ?>
          <span class="vs-sale-badge">-<?php echo (int)$eff_disc; ?>%</span>
          <?php endif; ?>
        </a>
        <div class="vs-card-body">
          <div class="vs-card-cat"><?php echo esc_html($p->category); ?></div>
          <div class="vs-card-name">
            <a href="<?php echo $p_url; ?>"><?php echo esc_html($p->name); ?></a>
          </div>
          <?php if ( !empty($p->shop_description) ) : ?>
          <div class="vs-card-desc"><?php echo esc_html(wp_trim_words($p->shop_description, 12, '…')); ?></div>
          <?php endif; ?>
          <div class="vs-card-price">
            <?php if ( $has_disc ) : ?>
            <span class="vs-price-orig"><?php echo number_format((float)$p->shop_price,2,',',' '); ?> €</span>
            <span class="vs-price-disc"><?php echo number_format($p_disc,2,',',' '); ?> €</span>
            <?php else : ?>
            <?php echo number_format((float)$p->shop_price,2,',',' '); ?> €
            <?php endif; ?>
            <?php if ( !empty($p->unit) ) : ?>
            <span class="vs-card-unit">/ <?php echo esc_html($p->unit); ?></span>
            <?php endif; ?>
          </div>
          <?php if ( $p_in_stock ) : ?>
          <button class="vs-add-btn" data-pid="<?php echo (int)$p->id; ?>">Lisa korvi</button>
          <?php else : ?>
          <button class="vs-add-btn vs-add-btn--oos" disabled>Laos otsas</button>
          <div class="vs-notify-wrap" data-pid="<?php echo (int)$p->id; ?>">
            <button class="vs-notify-btn" onclick="vsNotifyOpen(this)">🔔 Teavita mind</button>
            <div class="vs-notify-form" style="display:none">
              <input type="email" class="vs-notify-email" placeholder="Sinu e-post">
              <button class="vs-notify-submit" onclick="vsNotifySubmit(this)">OK</button>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div><!-- .vs-main -->
</div><!-- .vs-layout -->

<?php
/* ═══════════════════════════════════════════════════ PRODUCT ═══ */
elseif ( $view === 'product' ) :
    $pid  = absint( $_GET['pid'] ?? 0 );
    $prod = $pid ? $wpdb->get_row( $wpdb->prepare(
        "SELECT i.*, COALESCE(c.color,'#00b4c8') as cat_color
         FROM {$wpdb->prefix}vesho_inventory i
         LEFT JOIN {$wpdb->prefix}vesho_inventory_categories c ON c.name=i.category
         WHERE i.id=%d AND i.shop_enabled=1 AND i.shop_price>0 AND i.archived=0 LIMIT 1",
        $pid
    ) ) : null;
    // Load sidebar data for product view too
    $db_cats_p = $wpdb->get_results(
        "SELECT c.name,c.color,COUNT(i.id) as cnt
         FROM {$wpdb->prefix}vesho_inventory_categories c
         LEFT JOIN {$wpdb->prefix}vesho_inventory i
           ON i.category=c.name AND i.shop_enabled=1 AND i.shop_price>0 AND i.archived=0
         GROUP BY c.id,c.name,c.color ORDER BY c.sort_order ASC,c.name ASC"
    );
    $total_count_p = (int)$wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->prefix}vesho_inventory WHERE shop_enabled=1 AND shop_price>0 AND archived=0"
    );
    if ( ! $prod ) :
?>
<div class="vs-layout">
  <aside class="vs-sidebar">
    <p class="vs-sb-hdr">☰ Kategooriad</p>
    <a href="<?php echo esc_url($shop_url); ?>" class="vs-cat-btn" style="text-decoration:none">
      <span class="vs-cat-dot" style="background:#0d1f2d"></span>
      Kõik tooted
      <span class="vs-cat-cnt"><?php echo $total_count_p; ?></span>
    </a>
    <?php foreach ( $db_cats_p as $cat ) : if ( (int)$cat->cnt < 1 ) continue; ?>
    <a href="<?php echo esc_url(add_query_arg('cat', urlencode($cat->name), $shop_url)); ?>" class="vs-cat-btn" style="text-decoration:none">
      <span class="vs-cat-dot" style="background:<?php echo esc_attr($cat->color ?: '#00b4c8'); ?>"></span>
      <?php echo esc_html($cat->name); ?>
      <span class="vs-cat-cnt"><?php echo (int)$cat->cnt; ?></span>
    </a>
    <?php endforeach; ?>
  </aside>
  <div class="vs-main">
    <div class="vs-empty">
      <div class="vs-empty-icon">🔍</div>
      <div class="vs-empty-txt">Toodet ei leitud.</div>
      <a href="<?php echo esc_url($shop_url); ?>" class="vs-btn-outline">← Tagasi poodi</a>
    </div>
  </div>
</div>
<?php else :
    $p_disc      = round( (float)$prod->shop_price * (1 - $eff_disc/100), 2 );
    $has_disc    = $eff_disc > 0;
    $prod_in_stock = ( (int)($prod->quantity ?? 0) ) > 0;
?>
<div class="vs-layout">

  <!-- Sidebar with categories + search -->
  <aside class="vs-sidebar">
    <p class="vs-sb-hdr">☰ Kategooriad</p>
    <a href="<?php echo esc_url($shop_url); ?>" class="vs-cat-btn" style="text-decoration:none">
      <span class="vs-cat-dot" style="background:#0d1f2d"></span>
      Kõik tooted
      <span class="vs-cat-cnt"><?php echo $total_count_p; ?></span>
    </a>
    <?php foreach ( $db_cats_p as $cat ) :
      if ( (int)$cat->cnt < 1 ) continue;
      $is_active = $prod->category === $cat->name;
    ?>
    <a href="<?php echo esc_url(add_query_arg('cat', urlencode($cat->name), $shop_url)); ?>"
       class="vs-cat-btn <?php echo $is_active?'active':''; ?>" style="text-decoration:none">
      <span class="vs-cat-dot" style="background:<?php echo esc_attr($cat->color ?: '#00b4c8'); ?>"></span>
      <?php echo esc_html($cat->name); ?>
      <span class="vs-cat-cnt"><?php echo (int)$cat->cnt; ?></span>
    </a>
    <?php endforeach; ?>
    <!-- Search box in sidebar -->
    <div style="padding:12px 16px;border-top:1px solid #dce8ef;background:#fff">
      <form action="<?php echo esc_url($shop_url); ?>" method="get" style="display:flex;gap:6px">
        <input type="text" name="q" placeholder="Otsi…"
               style="flex:1;padding:8px 10px;border:1px solid #dce8ef;border-radius:4px;font-family:'Barlow',sans-serif;font-size:13px;outline:none;min-width:0">
        <button type="submit"
                style="padding:8px 12px;background:#00b4c8;color:#fff;border:none;border-radius:4px;font-family:'Barlow Condensed',sans-serif;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap">🔍</button>
      </form>
    </div>
  </aside>

  <!-- Product detail -->
  <div class="vs-main">
    <div class="vs-detail">
      <div class="vs-detail-grid">
        <div class="vs-detail-img-wrap">
          <?php if ( !empty($prod->image_url) ) : ?>
          <img src="<?php echo esc_url($prod->image_url); ?>" alt="<?php echo esc_attr($prod->name); ?>">
          <?php else : ?>
          <div class="vs-no-img" style="font-size:1rem;height:100%;width:100%">Pilt puudub</div>
          <?php endif; ?>
        </div>
        <div>
          <span class="vs-detail-cat"><?php echo esc_html($prod->category); ?></span>
          <h1 class="vs-detail-name"><?php echo esc_html($prod->name); ?></h1>
          <div class="vs-detail-price">
            <?php if ( $has_disc ) : ?>
            <span class="vs-detail-price-orig"><?php echo number_format((float)$prod->shop_price,2,',',' '); ?> €</span>
            <span class="vs-detail-price-disc"><?php echo number_format($p_disc,2,',',' '); ?> €</span>
            <?php else : ?>
            <?php echo number_format((float)$prod->shop_price,2,',',' '); ?> €
            <?php endif; ?>
            <?php if ( !empty($prod->unit) ) : ?>
            <span style="font-size:15px;font-weight:400;color:#5a7080">/ <?php echo esc_html($prod->unit); ?></span>
            <?php endif; ?>
          </div>
          <?php if ( $has_disc ) : ?>
          <div class="vs-disc-note">✓ Kampaaniahind (–<?php echo (int)$eff_disc; ?>%)</div>
          <?php endif; ?>
          <!-- Stock status -->
          <?php if ( $prod_in_stock ) : ?>
          <div class="vs-stock-ok">✓ Laos olemas</div>
          <?php else : ?>
          <div class="vs-stock-out">✗ Laos otsas</div>
          <?php endif; ?>
          <?php if ( !empty($prod->shop_description) ) : ?>
          <p class="vs-detail-desc"><?php echo nl2br(esc_html($prod->shop_description)); ?></p>
          <?php endif; ?>
          <div class="vs-qty-row">
            <?php if ( $prod_in_stock ) : ?>
            <div class="vs-qty">
              <button class="vs-qty-btn" onclick="vsQtyAdj(-1)">−</button>
              <input type="number" id="vsProdQty" value="1" min="1" max="99">
              <button class="vs-qty-btn" onclick="vsQtyAdj(1)">+</button>
            </div>
            <button class="vs-add-big-btn" id="vsDetailAddBtn" data-pid="<?php echo (int)$prod->id; ?>">Lisa korvi</button>
            <?php else : ?>
            <button class="vs-add-big-btn vs-add-btn--oos" disabled>Laos otsas</button>
            <div class="vs-notify-detail" data-pid="<?php echo (int)$prod->id; ?>">
              <p style="font-size:13px;color:#64748b;margin:8px 0 6px">Soovid teavitust kui toode taas saadaval?</p>
              <div style="display:flex;gap:8px;flex-wrap:wrap">
                <input type="email" id="vsDetailNotifyEmail" placeholder="Sinu e-post" style="flex:1;min-width:180px;padding:9px 12px;border:1px solid #dce8ef;border-radius:6px;font-size:14px">
                <button onclick="vsDetailNotify()" style="padding:9px 16px;background:#00b4c8;color:#fff;border:none;border-radius:6px;font-size:14px;font-weight:700;cursor:pointer;white-space:nowrap">🔔 Teavita mind</button>
              </div>
              <div id="vsDetailNotifyMsg" style="font-size:13px;margin-top:6px"></div>
            </div>
            <?php endif; ?>
          </div>
          <a href="<?php echo esc_url($cart_url); ?>" class="vs-btn-outline">🛒 Vaata ostukorvi</a>
        </div>
      </div>
    </div>
  </div><!-- .vs-main -->
</div><!-- .vs-layout -->
<?php endif; ?>

<?php
/* ═══════════════════════════════════════════════════════ CART ═══ */
elseif ( $view === 'cart' ) :
?>
<div class="vs-cart-wrap">
  <h1 class="vs-page-title">🛒 Ostukorv</h1>
  <?php if ( empty($cart['items']) ) : ?>
  <div class="vs-empty">
    <div class="vs-empty-icon">🛒</div>
    <div class="vs-empty-txt">Ostukorv on tühi.</div>
    <a href="<?php echo esc_url($shop_url); ?>" class="vs-btn-primary">← Jätka ostmist</a>
  </div>
  <?php else : ?>
  <table class="vs-cart-table">
    <thead><tr>
      <th>Toode</th>
      <th>Hind</th>
      <th>Kogus</th>
      <th>Kokku</th>
      <th></th>
    </tr></thead>
    <tbody>
    <?php foreach ( $cart['items'] as $ci ) :
      $cp         = $ci['p'];
      $disc_price = $eff_disc > 0 ? round((float)$cp->shop_price*(1-$eff_disc/100),2) : (float)$cp->shop_price;
      $line_total = round($disc_price * $ci['qty'], 2);
    ?>
    <tr>
      <td>
        <div class="vs-cart-prod">
          <?php if ( !empty($cp->image_url) ) : ?>
          <img src="<?php echo esc_url($cp->image_url); ?>" class="vs-cart-thumb" alt="">
          <?php else : ?>
          <div class="vs-thumb-ph">Pilt puudub</div>
          <?php endif; ?>
          <div>
            <div class="vs-cart-name"><?php echo esc_html($cp->name); ?></div>
            <div class="vs-cart-cat"><?php echo esc_html($cp->category); ?></div>
          </div>
        </div>
      </td>
      <td>
        <div class="vs-cart-price">
          <?php if ( $eff_disc > 0 ) : ?>
          <span style="text-decoration:line-through;color:#94a3b8;font-size:12px"><?php echo number_format((float)$cp->shop_price,2,',',' '); ?> €</span><br>
          <?php endif; ?>
          <?php echo number_format($disc_price,2,',',' '); ?> €
        </div>
      </td>
      <td>
        <div class="vs-cart-qty">
          <button class="vs-cart-qty-btn" onclick="vsCartQty(<?php echo (int)$cp->id; ?>,-1)">−</button>
          <input type="number" value="<?php echo (int)$ci['qty']; ?>" min="1" max="99"
                 id="vsQty<?php echo (int)$cp->id; ?>"
                 onchange="vsCartSet(<?php echo (int)$cp->id; ?>,this.value)">
          <button class="vs-cart-qty-btn" onclick="vsCartQty(<?php echo (int)$cp->id; ?>,1)">+</button>
        </div>
      </td>
      <td><div class="vs-cart-price"><?php echo number_format($line_total,2,',',' '); ?> €</div></td>
      <td><button class="vs-remove-btn" onclick="vsCartRemove(<?php echo (int)$cp->id; ?>)" title="Eemalda">×</button></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <div class="vs-cart-summary">
    <?php if ( $eff_disc > 0 ) : ?>
    <div class="vs-sum-row">
      <span>Tooted (ilma sooduseta)</span>
      <span><?php echo number_format($cart['subtotal'],2,',',' '); ?> €</span>
    </div>
    <div class="vs-sum-row" style="color:#16a34a;font-weight:600">
      <span>Soodustus –<?php echo (int)$eff_disc; ?>%</span>
      <span>−<?php echo number_format(round($cart['subtotal']-$sub_chk,2),2,',',' '); ?> €</span>
    </div>
    <?php endif; ?>
    <div class="vs-sum-row"><span>Vahesumma (km-ta)</span><span><?php echo number_format($sub_chk,2,',',' '); ?> €</span></div>
    <div class="vs-sum-row"><span>KM <?php echo $vat_pct; ?>%</span><span><?php echo number_format($vat_chk,2,',',' '); ?> €</span></div>
    <div class="vs-sum-row vs-total"><span>Kokku (ilma tarneta)</span><span><?php echo number_format(round($sub_chk+$vat_chk,2),2,',',' '); ?> €</span></div>
    <div class="vs-cart-actions">
      <a href="<?php echo esc_url($shop_url); ?>" class="vs-btn-outline">← Jätka ostmist</a>
      <a href="<?php echo esc_url($chk_url); ?>" class="vs-btn-primary">Vormista tellimus →</a>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php
/* ══════════════════════════════════════════════════ CHECKOUT ═══ */
elseif ( $view === 'checkout' ) :
    if ( empty($cart['items']) ) :
?>
  <div class="vs-empty">
    <div class="vs-empty-icon">🛒</div>
    <div class="vs-empty-txt">Ostukorv on tühi.</div>
    <a href="<?php echo esc_url($shop_url); ?>" class="vs-btn-primary">← Tagasi poodi</a>
  </div>
<?php else :
    $client_name  = $client ? esc_attr($client->name ?? '') : '';
    $client_email = $client ? esc_attr($client->email ?? '') : '';
    $client_phone = $client ? esc_attr($client->phone ?? '') : '';
?>
  <h1 class="vs-page-title">Vormista tellimus</h1>
  <div id="vsChkAlert"></div>
  <div class="vs-chk-grid">

    <!-- Left: Fields -->
    <div>
      <div class="vs-chk-block">
        <h3>Tarneandmed</h3>
        <div class="vs-field"><label for="chkName">Nimi *</label>
          <input type="text" id="chkName" placeholder="Teie täisnimi" value="<?php echo $client_name; ?>"></div>
        <div class="vs-field"><label for="chkEmail">E-post *</label>
          <input type="email" id="chkEmail" placeholder="teie@email.ee" value="<?php echo $client_email; ?>"></div>
        <div class="vs-field"><label for="chkPhone">Telefon</label>
          <input type="tel" id="chkPhone" placeholder="+372 5xxx xxxx" value="<?php echo $client_phone; ?>"></div>
        <div class="vs-field"><label for="chkCompany">Ettevõte (valikuline)</label>
          <input type="text" id="chkCompany" placeholder="Ettevõtte nimi"></div>
        <div class="vs-field"><label for="chkAddr">Aadress *</label>
          <input type="text" id="chkAddr" placeholder="Tänav, maja, korter"></div>
        <div class="vs-field-row">
          <div class="vs-field"><label for="chkCity">Linn *</label>
            <input type="text" id="chkCity" placeholder="Linn"></div>
          <div class="vs-field"><label for="chkZip">Postiindeks *</label>
            <input type="text" id="chkZip" placeholder="10001"></div>
        </div>
      </div>

      <div class="vs-chk-block">
        <h3>Tarneviis</h3>
        <?php foreach ( $del_opts as $di => $del ) : ?>
        <label class="vs-del-opt<?php echo $di===0?' sel':''; ?>">
          <input type="radio" name="vsDelivery" value="<?php echo esc_attr($del['id']); ?>"
                 data-price="<?php echo (float)$del['price']; ?>"
                 <?php echo $di===0?'checked':''; ?>>
          <span class="vs-del-label"><?php echo esc_html($del['icon'].' '.$del['label']); ?></span>
          <span class="vs-del-price"><?php echo $del['price']>0 ? number_format($del['price'],2,',',' ').' €' : 'Tasuta'; ?></span>
        </label>
        <?php endforeach; ?>
      </div>

      <div class="vs-chk-block">
        <h3>Makseviis</h3>
        <?php
        $first_pay = true;
        if ( $stripe_enabled ) : ?>
        <label class="vs-pay-opt sel">
          <input type="radio" name="vsPayment" value="stripe" checked>
          <span class="vs-pay-lbl">💳 Stripe kaardimakse</span>
        </label>
        <?php $first_pay=false; endif;
        if ( $mc_enabled ) : ?>
        <label class="vs-pay-opt<?php echo $first_pay?' sel':''; ?>">
          <input type="radio" name="vsPayment" value="mc" <?php echo $first_pay?'checked':''; ?>>
          <span class="vs-pay-lbl">🏦 Maksekeskus pangalink</span>
        </label>
        <?php $first_pay=false; endif;
        if ( $montonio_enabled ) : ?>
        <label class="vs-pay-opt<?php echo $first_pay?' sel':''; ?>">
          <input type="radio" name="vsPayment" value="montonio" <?php echo $first_pay?'checked':''; ?>>
          <span class="vs-pay-lbl">🏦 Montonio pangalink</span>
        </label>
        <?php $first_pay=false; endif;
        if ( ! $stripe_enabled && ! $mc_enabled && ! $montonio_enabled ) : ?>
        <div class="vs-alert vs-alert-err">Makseviis ei ole seadistatud. Palun pöörduge administraatori poole.</div>
        <?php endif; ?>
        <div id="vsStripeCard"></div>
      </div>
    </div>

    <!-- Right: Summary -->
    <div>
      <div class="vs-chk-block">
        <h3>Tellimuse kokkuvõte</h3>
        <?php foreach ( $cart['items'] as $ci ) :
          $dp = $eff_disc>0 ? round((float)$ci['p']->shop_price*(1-$eff_disc/100),2) : (float)$ci['p']->shop_price;
          $lt = round($dp*$ci['qty'],2);
        ?>
        <div class="vs-chk-item">
          <div>
            <div class="vs-chk-item-name"><?php echo esc_html($ci['p']->name); ?></div>
            <div class="vs-chk-item-sub"><?php echo (int)$ci['qty']; ?> tk × <?php echo number_format($dp,2,',',' '); ?> €</div>
          </div>
          <div class="vs-chk-item-price"><?php echo number_format($lt,2,',',' '); ?> €</div>
        </div>
        <?php endforeach; ?>
        <div class="vs-sum-row"><span>Vahesumma</span><span><?php echo number_format($sub_chk,2,',',' '); ?> €</span></div>
        <div class="vs-sum-row"><span>KM <?php echo $vat_pct; ?>%</span><span><?php echo number_format($vat_chk,2,',',' '); ?> €</span></div>
        <div class="vs-sum-row"><span>Tarne</span><span id="vsDelPriceTxt"><?php echo $del_opts[0]['price']>0 ? number_format($del_opts[0]['price'],2,',',' ').' €' : 'Tasuta'; ?></span></div>
        <div class="vs-sum-row vs-total"><span>Kokku</span><span id="vsTotalTxt"><?php echo number_format(round($sub_chk+$vat_chk+($del_opts[0]['price']??0),2),2,',',' '); ?> €</span></div>
        <button class="vs-submit-btn" id="vsPlaceOrderBtn">Kinnita ja maksa</button>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php
/* ══════════════════════════════════════════════════ SUCCESS ═══ */
elseif ( $view === 'success' ) :
    $oid  = absint( $_GET['order_id'] ?? ($_SESSION['vesho_last_order_id'] ?? 0) );
    $ord  = $oid ? $wpdb->get_row( $wpdb->prepare(
        "SELECT order_number,total FROM {$wpdb->prefix}vesho_shop_orders WHERE id=%d LIMIT 1", $oid
    ) ) : null;
?>
<div class="vs-result">
  <div class="vs-result-circle" style="background:#16a34a">✓</div>
  <h2>Tellimus on vormistatud!</h2>
  <?php if ( $ord ) : ?>
  <p>Tellimus <strong><?php echo esc_html($ord->order_number); ?></strong> — kokku <strong><?php echo number_format((float)$ord->total,2,',',' '); ?> €</strong><br>Saadame kinnituse e-posti teel.</p>
  <?php else : ?>
  <p>Täname teid ostu eest! Saadame kinnituse e-posti teel.</p>
  <?php endif; ?>
  <div class="vs-result-btns">
    <a href="<?php echo esc_url($shop_url); ?>" class="vs-btn-primary">← Tagasi poodi</a>
    <?php if ( is_user_logged_in() ) : ?>
    <a href="<?php echo esc_url(home_url('/portaal/?view=orders')); ?>" class="vs-btn-outline">Minu tellimused</a>
    <?php endif; ?>
  </div>
</div>

<?php
/* ═══════════════════════════════════════════════════ CANCEL ═══ */
elseif ( $view === 'cancel' ) :
?>
<div class="vs-result">
  <div class="vs-result-circle" style="background:#dc2626">✕</div>
  <h2>Makse katkestatud</h2>
  <p>Tellimus jäi vormistamata. Ostukorv on alles — proovige uuesti.</p>
  <div class="vs-result-btns">
    <a href="<?php echo esc_url($cart_url); ?>" class="vs-btn-primary">← Tagasi ostukorvi</a>
    <a href="<?php echo esc_url($shop_url); ?>" class="vs-btn-outline">Tagasi poodi</a>
  </div>
</div>

<?php endif; // end view routing ?>

</div></div><!-- .vs-wrap / .vs-bg -->
</div><!-- #vshop -->

<script>
(function(){
'use strict';
var AJAX=<?php echo json_encode($ajax_url); ?>,
    NONCE=<?php echo json_encode($nonce); ?>,
    SHOP=<?php echo json_encode($shop_url); ?>,
    CART_URL=<?php echo json_encode($cart_url); ?>,
    CHK=<?php echo json_encode($chk_url); ?>,
    SUB=<?php echo json_encode($sub_chk); ?>,
    VAT=<?php echo json_encode($vat_chk); ?>,
    STRIPE_ON=<?php echo json_encode((bool)$stripe_enabled); ?>,
    STRIPE_KEY=<?php echo json_encode($stripe_pub_key); ?>,
    INIT_CAT=<?php echo json_encode($init_cat ?? ''); ?>,
    INIT_Q=<?php echo json_encode($init_q ?? ''); ?>;

/* ─── Badge ─── */
function setBadge(n){
  var b=document.getElementById('vsCartBadge');
  if(!b)return;
  b.textContent=n>0?n:'';
  b.style.display=n>0?'':'none';
}

/* ─── AJAX helper ─── */
function cartPost(action,data,cb){
  var fd=new FormData();
  fd.append('action',action);fd.append('nonce',NONCE);
  Object.keys(data).forEach(function(k){fd.append(k,data[k]);});
  fetch(AJAX,{method:'POST',body:fd})
    .then(function(r){return r.json();})
    .then(function(d){if(cb)cb(d);});
}

function cartAdd(pid,qty,btn){
  cartPost('vesho_cart_add',{pid:pid,qty:qty||1},function(d){
    if(d.success){
      setBadge(d.data.count);
      if(btn){
        var orig=btn.textContent;
        btn.textContent='Lisatud ✓';btn.classList.add('added');
        setTimeout(function(){btn.textContent=orig;btn.classList.remove('added');},1800);
      }
    }
  });
}

function cartUpdate(pid,qty,cb){
  cartPost('vesho_cart_update',{pid:pid,qty:qty},function(d){
    if(d.success){setBadge(d.data.count);if(cb)cb();}
  });
}

/* ─── Grid: category + search + sort ─── */
var grid=document.getElementById('vsGrid');
if(grid){
  var cards=Array.from(grid.querySelectorAll('.vs-card'));
  var resultTxt=document.getElementById('vsResultTxt');
  var searchEl=document.getElementById('vsSearch');
  var sortEl=document.getElementById('vsSort');
  var activeCat=INIT_CAT||'all';

  function filterCards(){
    var q=searchEl?searchEl.value.toLowerCase().trim():'';
    var vis=[];
    cards.forEach(function(c){
      var show=(activeCat==='all'||c.dataset.cat===activeCat)&&(!q||(c.dataset.name||'').indexOf(q)>=0);
      c.style.display=show?'':'none';
      if(show)vis.push(c);
    });
    if(resultTxt)resultTxt.textContent=vis.length+' toodet';
  }

  function sortAndFilter(){
    var v=sortEl?sortEl.value:'name-asc';
    cards.sort(function(a,b){
      if(v==='price-asc')return+a.dataset.price-+b.dataset.price;
      if(v==='price-desc')return+b.dataset.price-+a.dataset.price;
      if(v==='name-desc')return(b.dataset.name||'').localeCompare(a.dataset.name||'','et');
      return(a.dataset.name||'').localeCompare(b.dataset.name||'','et');
    });
    cards.forEach(function(c){grid.appendChild(c);});
    filterCards();
  }

  document.querySelectorAll('.vs-cat-btn').forEach(function(btn){
    btn.addEventListener('click',function(){
      activeCat=this.dataset.cat;
      document.querySelectorAll('.vs-cat-btn').forEach(function(b){b.classList.remove('active');});
      this.classList.add('active');
      filterCards();
    });
  });
  if(searchEl)searchEl.addEventListener('input',filterCards);
  if(sortEl)sortEl.addEventListener('change',sortAndFilter);

  // Apply URL-passed initial category/search on load
  if(INIT_CAT||INIT_Q)filterCards();

  grid.addEventListener('click',function(e){
    var btn=e.target.closest('.vs-add-btn');
    if(btn)cartAdd(btn.dataset.pid,1,btn);
  });
}

/* ─── Product detail ─── */
var detBtn=document.getElementById('vsDetailAddBtn');
if(detBtn){
  detBtn.addEventListener('click',function(){
    var q=parseInt((document.getElementById('vsProdQty')||{}).value)||1;
    cartAdd(detBtn.dataset.pid,q,detBtn);
  });
}
window.vsQtyAdj=function(d){
  var el=document.getElementById('vsProdQty');if(!el)return;
  el.value=Math.max(1,Math.min(99,(parseInt(el.value)||1)+d));
};

/* ─── Back-in-stock notifications ─── */
window.vsNotifyOpen=function(btn){
  var wrap=btn.closest('.vs-notify-wrap');
  btn.style.display='none';
  wrap.querySelector('.vs-notify-form').style.display='flex';
  wrap.querySelector('.vs-notify-email').focus();
};
window.vsNotifySubmit=function(btn){
  var wrap=btn.closest('.vs-notify-wrap');
  var email=wrap.querySelector('.vs-notify-email').value.trim();
  var pid=wrap.closest('[data-pid]').dataset.pid;
  if(!email){return;}
  btn.disabled=true;btn.textContent='…';
  fetch('<?php echo $ajax_url; ?>',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'action=vesho_stock_notify&nonce=<?php echo $nonce; ?>&pid='+pid+'&email='+encodeURIComponent(email)})
  .then(r=>r.json()).then(d=>{
    wrap.innerHTML='<span style="color:#16a34a;font-size:13px">✓ '+(d.data||'Teavitame sind!')+'</span>';
  }).catch(()=>{btn.disabled=false;btn.textContent='OK';});
};
window.vsDetailNotify=function(){
  var el=document.getElementById('vsDetailNotifyEmail');
  var msg=document.getElementById('vsDetailNotifyMsg');
  var wrap=document.querySelector('.vs-notify-detail');
  var pid=wrap?wrap.dataset.pid:0;
  if(!el||!el.value.trim())return;
  fetch('<?php echo $ajax_url; ?>',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'action=vesho_stock_notify&nonce=<?php echo $nonce; ?>&pid='+pid+'&email='+encodeURIComponent(el.value.trim())})
  .then(r=>r.json()).then(d=>{
    if(msg)msg.innerHTML='<span style="color:'+(d.success?'#16a34a':'#dc2626')+'">'+( d.data||'Viga')+'</span>';
    if(d.success&&el)el.value='';
  });
};

/* ─── Cart controls ─── */
window.vsCartQty=function(pid,delta){
  var el=document.getElementById('vsQty'+pid);if(!el)return;
  var nv=Math.max(0,(parseInt(el.value)||1)+delta);
  if(nv===0){vsCartRemove(pid);return;}
  el.value=nv;
  cartUpdate(pid,nv,function(){location.reload();});
};
window.vsCartSet=function(pid,val){
  var nv=Math.max(0,parseInt(val)||0);
  if(nv===0){vsCartRemove(pid);return;}
  cartUpdate(pid,nv,function(){location.reload();});
};
window.vsCartRemove=function(pid){cartUpdate(pid,0,function(){location.reload();});};

/* ─── Checkout: delivery highlight + total recalc ─── */
document.querySelectorAll('.vs-del-opt').forEach(function(opt){
  opt.addEventListener('click',function(){
    document.querySelectorAll('.vs-del-opt').forEach(function(o){o.classList.remove('sel');});
    this.classList.add('sel');recalcTotal();
  });
});
function recalcTotal(){
  var radio=document.querySelector('input[name="vsDelivery"]:checked');
  var dp=radio?parseFloat(radio.dataset.price)||0:0;
  var tot=SUB+VAT+dp;
  var dtxt=document.getElementById('vsDelPriceTxt');
  var ttxt=document.getElementById('vsTotalTxt');
  if(dtxt)dtxt.textContent=dp>0?(dp.toFixed(2).replace('.',',')+' \u20ac'):'Tasuta';
  if(ttxt)ttxt.textContent=tot.toFixed(2).replace('.',',')+' \u20ac';
}

/* ─── Payment opt highlight ─── */
document.querySelectorAll('.vs-pay-opt').forEach(function(opt){
  opt.addEventListener('click',function(){
    document.querySelectorAll('.vs-pay-opt').forEach(function(o){o.classList.remove('sel');});
    this.classList.add('sel');toggleStripeEl();
  });
});

/* ─── Stripe element ─── */
var stripeObj=null,cardEl=null;
function toggleStripeEl(){
  var pm=document.querySelector('input[name="vsPayment"]:checked');
  var sc=document.getElementById('vsStripeCard');if(!sc)return;
  if(pm&&pm.value==='stripe'&&STRIPE_ON){
    sc.style.display='block';
    if(!stripeObj&&STRIPE_KEY&&window.Stripe){
      stripeObj=Stripe(STRIPE_KEY);
      cardEl=stripeObj.elements().create('card',{style:{base:{fontFamily:"'Barlow',sans-serif",fontSize:'15px',color:'#1a2a38'}}});
      cardEl.mount('#vsStripeCard');
    }
  } else {sc.style.display='none';}
}
if(STRIPE_ON){
  var ss=document.createElement('script');ss.src='https://js.stripe.com/v3/';
  ss.onload=function(){toggleStripeEl();};document.head.appendChild(ss);
}

/* ─── Checkout submit ─── */
var orderBtn=document.getElementById('vsPlaceOrderBtn');
if(orderBtn){
  orderBtn.addEventListener('click',function(){
    var alertDiv=document.getElementById('vsChkAlert');
    function showErr(msg){
      alertDiv.innerHTML='<div class="vs-alert vs-alert-err">'+msg+'</div>';
      window.scrollTo({top:alertDiv.getBoundingClientRect().top+window.scrollY-80,behavior:'smooth'});
      orderBtn.disabled=false;orderBtn.textContent='Kinnita ja maksa';
    }
    var name=((document.getElementById('chkName')||{}).value||'').trim();
    var email=((document.getElementById('chkEmail')||{}).value||'').trim();
    var phone=((document.getElementById('chkPhone')||{}).value||'').trim();
    var company=((document.getElementById('chkCompany')||{}).value||'').trim();
    var addr=((document.getElementById('chkAddr')||{}).value||'').trim();
    var city=((document.getElementById('chkCity')||{}).value||'').trim();
    var zip=((document.getElementById('chkZip')||{}).value||'').trim();
    if(!name||!email||!addr||!city||!zip){showErr('Palun täitke kõik kohustuslikud väljad (*).');return;}
    if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){showErr('Palun sisestage kehtiv e-posti aadress.');return;}
    var pm=(document.querySelector('input[name="vsPayment"]:checked')||{}).value||'stripe';
    var del=(document.querySelector('input[name="vsDelivery"]:checked')||{}).value||'pickup';
    orderBtn.disabled=true;orderBtn.textContent='Töötleb…';alertDiv.innerHTML='';

    cartPost('vesho_shop_place_order',{
      name:name,email:email,phone:phone,company:company,
      address:addr,city:city,postcode:zip,
      shipping_method:del,payment_method:pm
    },function(d){
      if(!d.success){showErr(d.data||'Viga tellimisel. Palun proovige uuesti.');return;}
      var r=d.data;
      if(r.method==='stripe'){
        if(!stripeObj||!cardEl){showErr('Stripe lähtestamine ebaõnnestus. Palun laadige leht uuesti.');return;}
        stripeObj.confirmCardPayment(r.client_secret,{payment_method:{card:cardEl}})
          .then(function(res){
            if(res.error){showErr(res.error.message);return;}
            cartPost('vesho_shop_stripe_confirm',{order_id:r.order_id},function(){
              window.location=SHOP+'?shop_view=success&order_id='+r.order_id;
            });
          });
      } else if(r.redirect_url){
        window.location=r.redirect_url;
      } else {
        window.location=SHOP+'?shop_view=success&order_id='+(r.order_id||'');
      }
    });
  });
}

})();
</script>
    <?php
    return ob_get_clean();
} );
