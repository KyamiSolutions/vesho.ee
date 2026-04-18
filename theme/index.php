<?php
/**
 * Vesho Theme - Homepage Template
 * Used as: Front page / Blog index fallback
 */
defined( 'ABSPATH' ) || exit;

// If this is the blog index (not front page), show posts
if ( ! is_front_page() ) {
    get_header();
    vesho_page_banner( __( 'Uudised', 'vesho' ), __( 'Viimased uudised ja artiklid', 'vesho' ) );
    ?>
    <main class="site-main blog-main" id="main-content">
        <div class="container section">
            <div class="blog-layout">
                <!-- Main content -->
                <div class="blog-content">
                    <?php if ( have_posts() ) : ?>
                        <div class="blog-grid">
                            <?php while ( have_posts() ) : the_post(); ?>
                                <article id="post-<?php the_ID(); ?>" <?php post_class( 'blog-card' ); ?>>
                                    <?php if ( has_post_thumbnail() ) : ?>
                                        <a class="blog-card__thumb" href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail( 'vesho-card' ); ?>
                                        </a>
                                    <?php endif; ?>
                                    <div class="blog-card__body">
                                        <div class="blog-card__meta">
                                            <time datetime="<?php the_time( 'Y-m-d' ); ?>"><?php the_time( 'd.m.Y' ); ?></time>
                                            <span>·</span>
                                            <?php the_category( ', ' ); ?>
                                        </div>
                                        <h2 class="blog-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                                        <p class="blog-card__excerpt"><?php the_excerpt(); ?></p>
                                        <a class="btn btn-sm btn-primary" href="<?php the_permalink(); ?>"><?php _e( 'Loe edasi', 'vesho' ); ?></a>
                                    </div>
                                </article>
                            <?php endwhile; ?>
                        </div>
                        <div class="pagination"><?php the_posts_pagination(); ?></div>
                    <?php else : ?>
                        <p><?php _e( 'Postitusi ei leitud.', 'vesho' ); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <aside class="blog-sidebar">
                    <div class="sidebar-widget">
                        <h3 class="sidebar-widget__title"><?php _e( 'Kategooriad', 'vesho' ); ?></h3>
                        <ul class="sidebar-cat-list">
                            <?php
                            $cats = get_categories( [ 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC' ] );
                            foreach ( $cats as $cat ) :
                            ?>
                            <li class="sidebar-cat-list__item">
                                <a href="<?php echo esc_url( get_category_link( $cat->term_id ) ); ?>" class="sidebar-cat-list__link">
                                    <?php echo esc_html( $cat->name ); ?>
                                    <span class="sidebar-cat-list__count"><?php echo $cat->count; ?></span>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </aside>
            </div>
        </div>
    </main>
    <?php
    get_footer();
    return;
}

// ── FRONT PAGE ────────────────────────────────────────────────────────────────
get_header();

$hero_headline = get_theme_mod( 'vesho_hero_headline', "PUHASTA VETT.\nTULEVIKU JAOKS." );
$hero_sub      = get_theme_mod( 'vesho_hero_sub', 'Professionaalsed veepuhastussüsteemid. Hooldus. Paigaldus. Garantii.' );
$hero_badge    = get_theme_mod( 'vesho_hero_badge', 'Premium veelahendused' );
$services      = vesho_get_services( 3 );
$phone         = get_theme_mod( 'vesho_phone', '+372 5XXX XXXX' );
$stat1_num     = get_theme_mod( 'vesho_stat1_num',   '500+' );
$stat1_label   = get_theme_mod( 'vesho_stat1_label', 'Rahulolev klient' );
$stat2_num     = get_theme_mod( 'vesho_stat2_num',   '10+' );
$stat2_label   = get_theme_mod( 'vesho_stat2_label', 'Aastat kogemust' );
$stat3_num     = get_theme_mod( 'vesho_stat3_num',   '24h' );
$stat3_label   = get_theme_mod( 'vesho_stat3_label', 'Reaktsiooniaeg' );
$stat4_num     = get_theme_mod( 'vesho_stat4_num',   '99%' );
$stat4_label   = get_theme_mod( 'vesho_stat4_label', 'Rahulolu' );
$why_label     = get_theme_mod( 'vesho_why_label', 'Miks valida Vesho?' );
$why_title     = get_theme_mod( 'vesho_why_title', 'Usaldusväärne partner veesüsteemide hoolduses' );
$why_desc      = get_theme_mod( 'vesho_why_desc',  'Üle 10 aasta kogemusega meeskond tagab teie veesüsteemide optimaalse töö. Pakume kiireid lahendusi, läbipaistvat hinnastamist ja püsivat kvaliteeti.' );
$why_items     = array(
    get_theme_mod( 'vesho_why_item1', 'Sertifitseeritud ja kogenud tehnikud' ),
    get_theme_mod( 'vesho_why_item2', 'Läbipaistev hinnastamine ilma peidetud tasudeta' ),
    get_theme_mod( 'vesho_why_item3', '24-tunnine reageering hädaolukordades' ),
    get_theme_mod( 'vesho_why_item4', 'Garantii kõikidele töödele ja materjalidele' ),
    get_theme_mod( 'vesho_why_item5', 'Üle 500 rahuloleva kliendi üle Eesti' ),
);
$why_badge1_title = get_theme_mod( 'vesho_why_badge1_title', 'Parim teenindus 2024' );
$why_badge1_sub   = get_theme_mod( 'vesho_why_badge1_sub',   'Eesti veemajanduse liit' );
$why_badge2_title = get_theme_mod( 'vesho_why_badge2_title', '4.9 / 5.0' );
$why_badge2_sub   = get_theme_mod( 'vesho_why_badge2_sub',   'Klientide hinnang' );
$why_badge3_title = get_theme_mod( 'vesho_why_badge3_title', '2 aasta garantii' );
$why_badge3_sub   = get_theme_mod( 'vesho_why_badge3_sub',   'Kõikidele töödele' );
$cta_title     = get_theme_mod( 'vesho_cta_title', 'Valmis alustama?' );
$cta_sub       = get_theme_mod( 'vesho_cta_sub',   'Võtke meiega ühendust ja saage tasuta konsultatsioon juba täna.' );
?>

<main class="site-main" id="main-content">

    <!-- ── HERO SECTION ──────────────────────────────────────────────────────── -->
    <section class="hero hero--vesho" aria-label="<?php esc_attr_e( 'Peamine sektsioon', 'vesho' ); ?>">
        <div class="hero__bg-radial" aria-hidden="true"></div>

        <div class="hero__vesho-inner">
            <!-- Left: content -->
            <div class="hero__vesho-content">
                <span class="hero__vesho-badge"><?php echo esc_html( $hero_badge ); ?></span>
                <h1 class="hero__vesho-title">
                    <?php
                    $lines = explode( "\n", $hero_headline );
                    foreach ( $lines as $i => $line ) {
                        if ( $i === 0 ) {
                            echo '<span>' . esc_html( $line ) . '</span><br>';
                        } else {
                            echo '<span class="hero__vesho-title--teal">' . esc_html( $line ) . '</span>';
                        }
                    }
                    ?>
                </h1>
                <p class="hero__vesho-sub"><?php echo esc_html( $hero_sub ); ?></p>
                <button class="btn hero__vesho-cta" id="hero-cta-btn" aria-haspopup="dialog" aria-controls="service-modal">
                    <?php _e( 'Küsi Pakkumist', 'vesho' ); ?>
                </button>
            </div>

            <!-- Right: water filtration SVG + contact widget -->
            <div class="hero__vesho-visual" aria-hidden="true">
                <!-- Contact widget (inside visual column, absolutely positioned right) -->
                <div class="hero__vesho-contact" aria-hidden="false">
                    <div class="hero__vesho-contact-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#00b4c8" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 11 19.79 19.79 0 01.01 2.34 2 2 0 012 .17h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L6.09 7.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 14.92v2z"/></svg>
                    </div>
                    <strong class="hero__vesho-contact-name"><?php bloginfo( 'name' ); ?></strong>
                    <?php if ( $phone ) : ?>
                        <a href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>" class="hero__vesho-contact-phone"><?php echo esc_html( $phone ); ?></a>
                    <?php endif; ?>
                    <a href="mailto:<?php echo esc_attr( get_theme_mod( 'vesho_email', '' ) ); ?>" class="hero__vesho-contact-email-btn" aria-label="Saada e-kiri">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </a>
                </div>
                <svg viewBox="0 0 420 320" fill="none" xmlns="http://www.w3.org/2000/svg" class="hero__vesho-svg">
                    <!-- Horizontal pipes -->
                    <rect x="20" y="90" width="180" height="24" rx="4" fill="#1e3a4f" stroke="#2e5470" stroke-width="1.5"/>
                    <rect x="220" y="90" width="180" height="24" rx="4" fill="#1e3a4f" stroke="#2e5470" stroke-width="1.5"/>
                    <!-- Filter A tank -->
                    <rect x="80" y="40" width="50" height="120" rx="8" fill="#162840" stroke="#00b4c8" stroke-width="2"/>
                    <rect x="87" y="50" width="36" height="100" rx="4" fill="#0d2035" opacity=".8"/>
                    <rect x="87" y="90" width="36" height="60" rx="2" fill="#00b4c8" opacity=".3">
                        <animate attributeName="height" values="60;80;60" dur="3s" repeatCount="indefinite"/>
                        <animate attributeName="y" values="90;70;90" dur="3s" repeatCount="indefinite"/>
                    </rect>
                    <circle cx="105" cy="155" r="10" fill="#0d2035" stroke="#00b4c8" stroke-width="1.5"/>
                    <!-- Filter B tank -->
                    <rect x="290" y="35" width="50" height="130" rx="8" fill="#162840" stroke="#00b4c8" stroke-width="2"/>
                    <rect x="297" y="45" width="36" height="110" rx="4" fill="#0d2035" opacity=".8"/>
                    <rect x="297" y="85" width="36" height="70" rx="2" fill="#00b4c8" opacity=".25">
                        <animate attributeName="height" values="70;50;70" dur="2.5s" repeatCount="indefinite"/>
                        <animate attributeName="y" values="85;105;85" dur="2.5s" repeatCount="indefinite"/>
                    </rect>
                    <circle cx="315" cy="170" r="10" fill="#0d2035" stroke="#00b4c8" stroke-width="1.5"/>
                    <!-- Gauges -->
                    <circle cx="185" cy="78" r="22" fill="#0f2233" stroke="#2e5470" stroke-width="1.5"/>
                    <circle cx="185" cy="78" r="18" fill="#0a1825"/>
                    <text x="185" y="83" text-anchor="middle" fill="#00b4c8" font-size="9" font-family="monospace" font-weight="bold">8.5</text>
                    <circle cx="235" cy="78" r="22" fill="#0f2233" stroke="#2e5470" stroke-width="1.5"/>
                    <circle cx="235" cy="78" r="18" fill="#0a1825"/>
                    <text x="235" y="83" text-anchor="middle" fill="#00e5ff" font-size="7" font-family="monospace" font-weight="bold">856</text>
                    <!-- Vertical pipes -->
                    <rect x="100" y="160" width="10" height="80" rx="3" fill="#1e3a4f" stroke="#2e5470"/>
                    <rect x="310" y="165" width="10" height="75" rx="3" fill="#1e3a4f" stroke="#2e5470"/>
                    <!-- Tank A -->
                    <rect x="60" y="235" width="80" height="60" rx="6" fill="#0d1f2d" stroke="#00b4c8" stroke-width="1.5"/>
                    <rect x="64" y="260" width="72" height="31" rx="3" fill="#00b4c8" opacity=".35">
                        <animate attributeName="opacity" values=".35;.5;.35" dur="2s" repeatCount="indefinite"/>
                    </rect>
                    <circle cx="105" cy="248" r="3" fill="#00b4c8" opacity=".8">
                        <animate attributeName="cy" values="220;260;220" dur="1.5s" repeatCount="indefinite"/>
                        <animate attributeName="opacity" values=".8;0;.8" dur="1.5s" repeatCount="indefinite"/>
                    </circle>
                    <!-- Tank B -->
                    <rect x="270" y="236" width="80" height="60" rx="6" fill="#0d1f2d" stroke="#00b4c8" stroke-width="1.5"/>
                    <rect x="274" y="260" width="72" height="31" rx="3" fill="#00e5ff" opacity=".2">
                        <animate attributeName="opacity" values=".2;.4;.2" dur="2.5s" repeatCount="indefinite"/>
                    </rect>
                    <circle cx="315" cy="248" r="3" fill="#00e5ff" opacity=".8">
                        <animate attributeName="cy" values="220;262;220" dur="1.8s" repeatCount="indefinite"/>
                        <animate attributeName="opacity" values=".8;0;.8" dur="1.8s" repeatCount="indefinite"/>
                    </circle>
                    <!-- Bottom pipe + valve -->
                    <rect x="140" y="255" width="140" height="12" rx="3" fill="#1e3a4f" stroke="#2e5470"/>
                    <circle cx="210" cy="261" r="12" fill="#0f2233" stroke="#00b4c8" stroke-width="1.5"/>
                    <line x1="204" y1="255" x2="216" y2="267" stroke="#00b4c8" stroke-width="2"/>
                    <line x1="216" y1="255" x2="204" y2="267" stroke="#00b4c8" stroke-width="2"/>
                    <!-- Labels -->
                    <text x="105" y="175" text-anchor="middle" fill="rgba(255,255,255,.4)" font-size="8" font-family="sans-serif">FILTER A</text>
                    <text x="315" y="180" text-anchor="middle" fill="rgba(255,255,255,.4)" font-size="8" font-family="sans-serif">FILTER B</text>
                </svg>
            </div>
        </div>
    </section>

    <!-- ── STATS SECTION ─────────────────────────────────────────────────────── -->
    <section class="stats-section" aria-label="<?php esc_attr_e( 'Statistika', 'vesho' ); ?>">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-item__num"><?php echo esc_html( $stat1_num ); ?></span>
                    <span class="stat-item__label"><?php echo esc_html( $stat1_label ); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-item__num"><?php echo esc_html( $stat2_num ); ?></span>
                    <span class="stat-item__label"><?php echo esc_html( $stat2_label ); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-item__num"><?php echo esc_html( $stat3_num ); ?></span>
                    <span class="stat-item__label"><?php echo esc_html( $stat3_label ); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-item__num"><?php echo esc_html( $stat4_num ); ?></span>
                    <span class="stat-item__label"><?php echo esc_html( $stat4_label ); ?></span>
                </div>
            </div>
        </div>
    </section>

    <!-- ── SERVICES PREVIEW ──────────────────────────────────────────────────── -->
    <section class="section services-preview" id="services-preview">
        <div class="container">
            <div class="section-header text-center">
                <span class="section-label"><?php _e( 'Meie teenused', 'vesho' ); ?></span>
                <h2 class="section-title"><?php _e( 'Mida me pakume', 'vesho' ); ?></h2>
                <p class="section-desc" style="margin: 0 auto;">
                    <?php _e( 'Professionaalsed veesüsteemide teenused – hooldusest paigalduseni. Kõik ühes kohas.', 'vesho' ); ?>
                </p>
            </div>

            <div class="services-grid">
                <?php if ( ! empty( $services ) ) : ?>
                    <?php foreach ( $services as $svc ) : ?>
                        <div class="service-card">
                            <div class="service-card__icon" aria-hidden="true">
                                <?php echo esc_html( $svc->icon ?? '💧' ); ?>
                            </div>
                            <h3 class="service-card__name"><?php echo esc_html( $svc->name ); ?></h3>
                            <p class="service-card__desc"><?php echo esc_html( $svc->description ?? '' ); ?></p>
                            <?php if ( ! empty( $svc->price ) ) : ?>
                                <span class="service-card__price">
                                    <?php printf( __( 'alates %s €', 'vesho' ), number_format( (float) $svc->price, 2, ',', ' ' ) ); ?>
                                </span>
                            <?php endif; ?>
                            <button
                                class="btn btn-primary btn-sm service-card__cta"
                                data-service="<?php echo esc_attr( $svc->name ); ?>"
                                aria-haspopup="dialog"
                                aria-controls="service-modal"
                            >
                                <?php _e( 'Küsi Pakkumist', 'vesho' ); ?>
                            </button>
                        </div>
                    <?php endforeach; ?>
                <?php else : ?>
                    <!-- Default cards if no services configured -->
                    <div class="service-card">
                        <div class="service-card__icon" aria-hidden="true">🔧</div>
                        <h3 class="service-card__name"><?php _e( 'Hooldus & Remont', 'vesho' ); ?></h3>
                        <p class="service-card__desc"><?php _e( 'Regulaarne veesüsteemide hooldus ja kiirete rikete kõrvaldamine.', 'vesho' ); ?></p>
                        <button class="btn btn-primary btn-sm service-card__cta" data-service="Hooldus" aria-haspopup="dialog" aria-controls="service-modal"><?php _e( 'Küsi Pakkumist', 'vesho' ); ?></button>
                    </div>
                    <div class="service-card">
                        <div class="service-card__icon" aria-hidden="true">💧</div>
                        <h3 class="service-card__name"><?php _e( 'Filtreerimine', 'vesho' ); ?></h3>
                        <p class="service-card__desc"><?php _e( 'Vee puhastus ja filtreerislahendused kodudele ning äridele.', 'vesho' ); ?></p>
                        <button class="btn btn-primary btn-sm service-card__cta" data-service="Filtreerimine" aria-haspopup="dialog" aria-controls="service-modal"><?php _e( 'Küsi Pakkumist', 'vesho' ); ?></button>
                    </div>
                    <div class="service-card">
                        <div class="service-card__icon" aria-hidden="true">⚙️</div>
                        <h3 class="service-card__name"><?php _e( 'Paigaldus', 'vesho' ); ?></h3>
                        <p class="service-card__desc"><?php _e( 'Uute veesüsteemide ja seadmete professionaalne paigaldus.', 'vesho' ); ?></p>
                        <button class="btn btn-primary btn-sm service-card__cta" data-service="Paigaldus" aria-haspopup="dialog" aria-controls="service-modal"><?php _e( 'Küsi Pakkumist', 'vesho' ); ?></button>
                    </div>
                <?php endif; ?>
            </div>

            <div class="text-center mt-32">
                <a class="btn btn-navy btn-lg" href="<?php echo esc_url( get_permalink( get_page_by_path( 'teenused' ) ) ?: home_url( '/teenused/' ) ); ?>">
                    <?php _e( 'Vaata kõiki teenuseid', 'vesho' ); ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </a>
            </div>
        </div>
    </section>

    <!-- ── WHY CHOOSE US ─────────────────────────────────────────────────────── -->
    <section class="section why-us bg-light">
        <div class="container">
            <div class="why-us__inner">
                <div class="why-us__text">
                    <span class="section-label"><?php echo esc_html( $why_label ); ?></span>
                    <h2 class="section-title"><?php echo esc_html( $why_title ); ?></h2>
                    <p class="section-desc">
                        <?php echo esc_html( $why_desc ); ?>
                    </p>
                    <ul class="why-us__list">
                        <?php foreach ( $why_items as $item ) : ?>
                        <li class="why-us__item">
                            <span class="why-us__check" aria-hidden="true"></span>
                            <?php echo esc_html( $item ); ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <a class="btn btn-primary btn-lg" href="<?php echo esc_url( get_permalink( get_page_by_path( 'meist' ) ) ?: home_url( '/meist/' ) ); ?>">
                        <?php _e( 'Loe meie kohta lähemalt', 'vesho' ); ?>
                    </a>
                </div>
                <div class="why-us__visual" aria-hidden="true">
                    <div class="why-us__card">
                        <div class="why-us__card-icon">🏆</div>
                        <div class="why-us__card-body">
                            <strong><?php echo esc_html( $why_badge1_title ); ?></strong>
                            <span><?php echo esc_html( $why_badge1_sub ); ?></span>
                        </div>
                    </div>
                    <div class="why-us__card">
                        <div class="why-us__card-icon">⭐</div>
                        <div class="why-us__card-body">
                            <strong><?php echo esc_html( $why_badge2_title ); ?></strong>
                            <span><?php echo esc_html( $why_badge2_sub ); ?></span>
                        </div>
                    </div>
                    <div class="why-us__card">
                        <div class="why-us__card-icon">🛡️</div>
                        <div class="why-us__card-body">
                            <strong><?php echo esc_html( $why_badge3_title ); ?></strong>
                            <span><?php echo esc_html( $why_badge3_sub ); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ── CTA SECTION ───────────────────────────────────────────────────────── -->
    <section class="cta-section" aria-label="<?php esc_attr_e( 'Tegevusele kutsumise sektsioon', 'vesho' ); ?>">
        <div class="container">
            <div class="cta-section__inner">
                <div class="cta-section__text">
                    <h2 class="cta-section__title"><?php echo esc_html( $cta_title ); ?></h2>
                    <p class="cta-section__sub"><?php echo esc_html( $cta_sub ); ?></p>
                </div>
                <div class="cta-section__actions">
                    <button
                        class="btn btn-primary btn-lg"
                        id="cta-section-btn"
                        aria-haspopup="dialog"
                        aria-controls="service-modal"
                    >
                        <?php _e( 'Küsi Pakkumist', 'vesho' ); ?>
                    </button>
                    <?php $phone = get_theme_mod( 'vesho_phone', '+372 5XXX XXXX' ); ?>
                    <a class="btn btn-outline btn-lg" href="tel:<?php echo esc_attr( preg_replace( '/\s+/', '', $phone ) ); ?>">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07A19.5 19.5 0 013.07 9.81a19.79 19.79 0 01-3.07-8.64A2 2 0 012 .81h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L6.09 8.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                        <?php echo esc_html( $phone ); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

</main>

<?php get_footer(); ?>
