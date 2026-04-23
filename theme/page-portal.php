<?php
/**
 * Template Name: Portal Template
 * Template Post Type: page
 *
 * Eraldiseisev portaali mall — ei sisalda avaliku saidi päist ega jalust.
 * Portaalil on oma navigatsioon (sidebar / bottom nav mobiilil).
 */
defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php wp_title( '|', true, 'right' ); bloginfo( 'name' ); ?></title>
    <?php wp_head(); ?>
    <style>
        /* Portaali leht: ilma saidi päiseta, täisekraan */
        body.portal-page {
            margin: 0;
            padding: 0;
            background: #f8fafc;
            overflow-x: hidden;
        }
        /* WP admin toolbar — kompensatsioon portaali topbarile */
        body.portal-page.admin-bar .vcp-topbar,
        body.portal-page.admin-bar .vwp-topbar {
            top: 32px;
        }
        @media (max-width: 782px) {
            body.portal-page.admin-bar .vcp-topbar,
            body.portal-page.admin-bar .vwp-topbar {
                top: 46px;
            }
        }
    </style>
</head>
<body <?php body_class( 'portal-page' ); ?>>
<?php wp_body_open(); ?>

<?php while ( have_posts() ) : the_post(); the_content(); endwhile; ?>

<?php wp_footer(); ?>
</body>
</html>
