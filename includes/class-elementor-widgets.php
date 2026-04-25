<?php
/**
 * Vesho Elementor Widgets
 * Registreerib kohandatud Elementori widgetid.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'elementor/widgets/register', 'vesho_register_elementor_widgets' );
function vesho_register_elementor_widgets( $widgets_manager ) {
    require_once VESHO_CRM_PATH . 'includes/widget-stats-grid.php';
    $widgets_manager->register( new Vesho_Widget_Stats_Grid() );
}

add_action( 'elementor/elements/categories_registered', 'vesho_add_elementor_category' );
function vesho_add_elementor_category( $elements_manager ) {
    $elements_manager->add_category( 'vesho', [
        'title' => 'Vesho',
        'icon'  => 'fa fa-plug',
    ] );
}
