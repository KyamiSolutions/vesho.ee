<?php
/**
 * Template Name: Portal Template
 * Template Post Type: page
 */
defined( 'ABSPATH' ) || exit;
// Standalone portal page — no site header/footer
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?php wp_title('|', true, 'right'); bloginfo('name'); ?></title>
<?php wp_head(); ?>
</head>
<body style="margin:0;padding:0;background:#0d1f2d">
<?php while ( have_posts() ) : the_post(); the_content(); endwhile; ?>
<?php wp_footer(); ?>
</body>
</html>
