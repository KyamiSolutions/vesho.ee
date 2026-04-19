<?php
/**
 * Template Name: Täislaius – Elementor
 * Template Post Type: page
 *
 * Täislaius mallileht Elementori lehtedele.
 * Navigatsioon ja jalus on nähtavad, aga lehekülje sisu on täislaius
 * ilma lisaümbriku või leheriba (page-banner) piiranguteta.
 */
defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
    the_post();
    the_content();
endwhile;

get_footer();
