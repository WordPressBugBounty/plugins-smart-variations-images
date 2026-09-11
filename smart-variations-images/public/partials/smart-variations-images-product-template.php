<?php

/**
 * Template loader used to replace WooCommerce product image template calls.
 *
 * Ensures Smart Variations Images can supply its gallery when builders invoke
 * `wc_get_template( 'single-product/product-image.php' )` directly (e.g. Elementor).
 *
 * @package    Smart_Variations_Images
 * @subpackage Smart_Variations_Images/public/partials
 * @since      5.2.20
 */

defined('ABSPATH') || exit;

$smart_variations_images_instance = Smart_Variations_Images_Public::get_current_instance();
$smart_variations_images_context = Smart_Variations_Images_Public::pop_template_context();

if (!$smart_variations_images_instance instanceof Smart_Variations_Images_Public) {
    Smart_Variations_Images_Public::clear_current_instance();
    if (is_array($smart_variations_images_context) && !empty($smart_variations_images_context['located']) && file_exists($smart_variations_images_context['located'])) {
        include $smart_variations_images_context['located'];
    }
    return;
}

$smart_variations_images_output = $smart_variations_images_instance->capture_frontend_template();

Smart_Variations_Images_Public::clear_current_instance();

if ('' === trim($smart_variations_images_output) && is_array($smart_variations_images_context) && !empty($smart_variations_images_context['located']) && file_exists($smart_variations_images_context['located'])) {
    include $smart_variations_images_context['located'];
    return;
}

echo wp_kses_post($smart_variations_images_output);
