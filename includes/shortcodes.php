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

