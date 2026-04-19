<?php
/**
 * Template Name: Lõuend – Elementor (ilma päiseta)
 * Template Post Type: page
 *
 * Tühi lõuend Elementori maandumislehtedele.
 * Ei rendereeri navigatsiooni ega jalust — ainult lehe sisu.
 * Kasulik kampaanialehtedele ja täieliku kontrolli saamiseks.
 */
defined( 'ABSPATH' ) || exit;

// Minimal head (styles + scripts)
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class( 'elementor-canvas-page' ); ?>>
<?php wp_body_open(); ?>

<div id="page-content" class="canvas-page">
<?php
while ( have_posts() ) :
    the_post();
    the_content();
endwhile;
?>
</div>

<?php wp_footer(); ?>
</body>
</html>
