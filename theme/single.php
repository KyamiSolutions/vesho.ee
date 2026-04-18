<?php
/**
 * Vesho Theme - Single Post Template
 */
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) : the_post();
    $cats = get_the_category();
    $cat_name = ! empty( $cats ) ? esc_html( $cats[0]->name ) : '';
    $cat_link = ! empty( $cats ) ? esc_url( get_category_link( $cats[0]->term_id ) ) : '';
?>

<?php vesho_page_banner( get_the_title(), $cat_name ? '<a href="' . $cat_link . '" style="color:inherit;opacity:.8;">' . $cat_name . '</a>' : get_the_date( 'd.m.Y' ) ); ?>

<main class="site-main blog-main" id="main-content">
    <div class="container section">
        <div class="blog-layout">

            <!-- Article content -->
            <div class="blog-content">
                <article id="post-<?php the_ID(); ?>" <?php post_class( 'single-article' ); ?>>
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="single-article__thumb">
                            <?php the_post_thumbnail( 'large' ); ?>
                        </div>
                    <?php endif; ?>

                    <div class="single-article__meta">
                        <time datetime="<?php the_time( 'Y-m-d' ); ?>"><?php the_time( 'd.m.Y' ); ?></time>
                        <?php if ( $cat_name ) : ?>
                            <span>·</span>
                            <a href="<?php echo $cat_link; ?>"><?php echo $cat_name; ?></a>
                        <?php endif; ?>
                        <span>·</span>
                        <?php the_author(); ?>
                    </div>

                    <div class="single-article__body entry-content">
                        <?php the_content(); ?>
                    </div>

                    <div class="single-article__footer">
                        <a class="btn btn-sm btn-outline" href="<?php echo esc_url( get_post_type_archive_link( 'post' ) ?: home_url( '/uudised/' ) ); ?>">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 5 5 12 12 19"/></svg>
                            <?php _e( 'Tagasi uudistesse', 'vesho' ); ?>
                        </a>
                    </div>
                </article>

                <!-- Prev / Next navigation -->
                <div class="single-nav">
                    <?php
                    $prev = get_previous_post();
                    $next = get_next_post();
                    if ( $prev ) : ?>
                        <a class="single-nav__link single-nav__link--prev" href="<?php echo esc_url( get_permalink( $prev ) ); ?>">
                            <span class="single-nav__dir">← <?php _e( 'Eelmine', 'vesho' ); ?></span>
                            <span class="single-nav__ttl"><?php echo esc_html( get_the_title( $prev ) ); ?></span>
                        </a>
                    <?php else : ?><div></div><?php endif; ?>
                    <?php if ( $next ) : ?>
                        <a class="single-nav__link single-nav__link--next" href="<?php echo esc_url( get_permalink( $next ) ); ?>">
                            <span class="single-nav__dir"><?php _e( 'Järgmine', 'vesho' ); ?> →</span>
                            <span class="single-nav__ttl"><?php echo esc_html( get_the_title( $next ) ); ?></span>
                        </a>
                    <?php else : ?><div></div><?php endif; ?>
                </div>
            </div>

            <!-- Sidebar -->
            <aside class="blog-sidebar">
                <div class="sidebar-widget">
                    <h3 class="sidebar-widget__title"><?php _e( 'Kategooriad', 'vesho' ); ?></h3>
                    <ul class="sidebar-cat-list">
                        <?php
                        $all_cats = get_categories( [ 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC' ] );
                        foreach ( $all_cats as $c ) :
                        ?>
                        <li class="sidebar-cat-list__item<?php echo ( ! empty( $cats ) && $cats[0]->term_id === $c->term_id ) ? ' sidebar-cat-list__item--active' : ''; ?>">
                            <a href="<?php echo esc_url( get_category_link( $c->term_id ) ); ?>" class="sidebar-cat-list__link">
                                <?php echo esc_html( $c->name ); ?>
                                <span class="sidebar-cat-list__count"><?php echo $c->count; ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Recent posts -->
                <div class="sidebar-widget">
                    <h3 class="sidebar-widget__title"><?php _e( 'Viimased artiklid', 'vesho' ); ?></h3>
                    <ul class="sidebar-recent-list">
                        <?php
                        $recent = get_posts( [ 'numberposts' => 5, 'post_status' => 'publish', 'exclude' => [ get_the_ID() ] ] );
                        foreach ( $recent as $rp ) :
                        ?>
                        <li class="sidebar-recent-list__item">
                            <a href="<?php echo esc_url( get_permalink( $rp ) ); ?>" class="sidebar-recent-list__link">
                                <span class="sidebar-recent-list__title"><?php echo esc_html( $rp->post_title ); ?></span>
                                <span class="sidebar-recent-list__date"><?php echo get_the_date( 'd.m.Y', $rp ); ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </aside>

        </div>
    </div>
</main>

<?php endwhile; ?>

<?php get_footer(); ?>
