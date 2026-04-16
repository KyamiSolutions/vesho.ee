<?php
/**
 * Template Name: Portal Template
 * Template Post Type: page
 */
defined( 'ABSPATH' ) || exit;

get_header();
?>
<main class="site-main" id="main-content" style="background:#f4f7f9;min-height:100vh;padding:0">
    <?php while ( have_posts() ) : the_post(); the_content(); endwhile; ?>
</main>
<?php get_footer(); ?>
