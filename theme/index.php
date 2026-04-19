<?php
/**
 * Vesho Theme - Homepage Template
 * Used as: Front page / Blog index fallback
 *
 * Elementori kasutajatele: loo WordPress leht, kasuta malli "Täislaius – Elementor"
 * ja lisa Shortcode widgetid: [vesho_hero] [vesho_stats] [vesho_services] [vesho_why_us] [vesho_cta]
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
                        <div class="blog-list">
                            <?php while ( have_posts() ) : the_post(); ?>
                                <article id="post-<?php the_ID(); ?>" <?php post_class( 'blog-list-item' ); ?>>
                                    <?php if ( has_post_thumbnail() ) : ?>
                                        <a class="blog-list-item__thumb" href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail( 'vesho-card' ); ?>
                                        </a>
                                    <?php endif; ?>
                                    <div class="blog-list-item__body">
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
?>

<main class="site-main" id="main-content">
    <?php
    // Renderdame iga sektsiooni shortcode funktsiooni kaudu —
    // sama kood mida kasutavad [vesho_hero], [vesho_stats] jne Elementoris.
    // phpcs:disable
    echo vesho_sc_hero();
    echo vesho_sc_stats();
    echo vesho_sc_services();
    echo vesho_sc_why_us();
    echo vesho_sc_cta();
    // phpcs:enable
    ?>
</main>

<?php get_footer(); ?>
