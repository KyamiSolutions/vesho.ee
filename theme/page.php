<?php
/**
 * Vesho Theme - Default Page Template
 */
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
    the_post();
    $is_elementor = get_post_meta( get_the_ID(), '_elementor_edit_mode', true ) === 'builder';
    if ( $is_elementor ) :
        // Elementor page — render full-width, no container wrapper
        the_content();
    else :
?>
<main class="site-main" id="main-content">
    <?php vesho_page_banner( get_the_title() ); ?>
    <div class="section">
        <div class="container-sm">
            <article id="post-<?php the_ID(); ?>" <?php post_class( 'page-content' ); ?>>
                <?php if ( has_post_thumbnail() ) : ?>
                    <div class="page-featured-image" style="margin-bottom:32px;">
                        <?php the_post_thumbnail( 'vesho-hero', array( 'style' => 'width:100%;height:auto;border-radius:8px;' ) ); ?>
                    </div>
                <?php endif; ?>
                <?php the_content(); ?>
            </article>
        </div>
    </div>
</main>
<?php
    endif;
endwhile;

get_footer();
