<?php
/**
 * Vesho Theme - Default Page Template
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>

<main class="site-main" id="main-content">
    <?php vesho_page_banner( get_the_title() ); ?>

    <div class="section">
        <div class="container-sm">
            <?php
            while ( have_posts() ) :
                the_post();
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class( 'page-content' ); ?>>
                    <?php if ( has_post_thumbnail() ) : ?>
                        <div class="page-featured-image" style="margin-bottom:32px;">
                            <?php the_post_thumbnail( 'vesho-hero', array( 'style' => 'width:100%;height:auto;border-radius:8px;' ) ); ?>
                        </div>
                    <?php endif; ?>
                    <?php the_content(); ?>
                </article>
            <?php endwhile; ?>
        </div>
    </div>
</main>

<?php get_footer(); ?>
