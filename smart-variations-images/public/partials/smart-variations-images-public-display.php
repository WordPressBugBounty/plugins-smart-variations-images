<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce, Flatsome, and legacy SVI integration hooks.

/**
 * Single Product Image
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/product-image.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.5.1
 */

defined('ABSPATH') || exit;

$smart_variations_images_meta = get_post_meta(get_the_ID());

// Note: `wc_get_gallery_image_html` was added in WC 3.3.2 and did not exist prior. This check protects against theme overrides being used on older versions of WC.
if (!function_exists('wc_get_gallery_image_html')) {
    return;
}

global $product;
$smart_variations_images_product = !empty($product) ? $product : wc_get_product(get_the_ID());

if (!$smart_variations_images_product instanceof WC_Product) {
    return;
}

$smart_variations_images_product_id = apply_filters(
    'svi_product_id',
    $smart_variations_images_product->get_id()
);
$smart_variations_images_load_data = $this->loadProduct($smart_variations_images_product_id);
$smart_variations_images_is_ajax_request = wp_doing_ajax();
$smart_variations_images_initial_markup = $this->get_initial_gallery_markup($smart_variations_images_load_data, $smart_variations_images_product_id);

if (!$smart_variations_images_is_ajax_request) {
    $this->prime_product_data($smart_variations_images_product_id, $smart_variations_images_load_data);
}

$smart_variations_images_data = '';

// Always encode data for Vue component - pass via data-wcsvi on BOTH AJAX and initial page load
// This ensures the gallery has data immediately without needing to wait for inline scripts or AJAX
$smart_variations_images_load = wp_json_encode($smart_variations_images_load_data);
$smart_variations_images_data = function_exists('wc_esc_json') ? wc_esc_json($smart_variations_images_load) : _wp_specialchars($smart_variations_images_load, ENT_QUOTES, 'UTF-8', true);
//wp_localize_script($this->plugin_name, 'wcsvi_' . $pid, json_encode($this->loadProduct($pid)));
//$columns           = apply_filters('woocommerce_product_thumbnails_columns', 4);
$smart_variations_images_thumbnail_id = $smart_variations_images_product->get_image_id();

$smart_variations_images_variable = '';

if (array_key_exists('slugs', $smart_variations_images_load_data) && !empty($smart_variations_images_load_data['slugs']) && !$smart_variations_images_product->is_type('simple'))
    $smart_variations_images_variable = 'svi-variable';

// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Required WooCommerce compatibility hook.
$smart_variations_images_wrapper_classes = apply_filters(
    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Required WooCommerce compatibility hook.
    'woocommerce_single_product_image_gallery_classes',
    array(
        'gallery-svi',
        'woocommerce-product-gallery',
        'woocommerce-product-gallery--' . ($smart_variations_images_product->get_image_id() ? 'with-images' : 'without-images'),
        $smart_variations_images_variable,
        'images',
    )
);

if (property_exists($this->options, 'sviforce_image') && $this->options->sviforce_image) {
    if (($smart_variations_images_key = array_search('images', $smart_variations_images_wrapper_classes)) !== false) {
        unset($smart_variations_images_wrapper_classes[$smart_variations_images_key]);
    }
}
// Remove 'images' class when using Divi Builder
if ($this->options->template === 'Divi' && $this->validate_runningDivi($smart_variations_images_product)) {
    if (($smart_variations_images_key = array_search('images', $smart_variations_images_wrapper_classes)) !== false) {
        unset($smart_variations_images_wrapper_classes[$smart_variations_images_key]);
    }
}
if (property_exists($this->options, 'custom_class') && !empty(trim($this->options->custom_class))) {
    $smart_variations_images_wrapper_classes[] = $this->options->custom_class;
}
$smart_variations_images_wrapper_classes = array_values($smart_variations_images_wrapper_classes);

$smart_variations_images_div_attributes = [
    'data-sviproduct_id="' . esc_attr($smart_variations_images_product_id) . '"',
    'data-wcsvi-ref="' . esc_attr($smart_variations_images_product_id) . '"',
    'class="' . esc_attr(implode(' ', array_map('sanitize_html_class', $smart_variations_images_wrapper_classes))) . '"',
];

$smart_variations_images_transition_style = 'transition: opacity .25s ease-in-out;';

if (!empty($smart_variations_images_initial_markup) || $this->options->preload_fimg) {
    $smart_variations_images_transition_style = 'opacity: 1; ' . $smart_variations_images_transition_style;
} else {
    $smart_variations_images_transition_style = 'opacity: 0; ' . $smart_variations_images_transition_style;
}

$smart_variations_images_div_attributes[] = 'style="' . esc_attr($smart_variations_images_transition_style) . '"';

// Always pass data-wcsvi to Vue component for immediate initialization with gallery data
if ($smart_variations_images_data) {
    $smart_variations_images_div_attributes[] = 'data-wcsvi="' . $smart_variations_images_data . '"';
}

?>
<div <?php echo wp_kses_post(implode(' ', $smart_variations_images_div_attributes)); ?>>
    <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy public SVI extension hook. ?>
    <?php do_action('svi_before_images'); ?>
    <?php if ($this->options->template == 'flatsome') { ?>
        <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Required Flatsome compatibility hook. ?>
        <?php (!defined('DOING_AJAX') ? do_action('flatsome_sale_flash') : ''); ?>

        <div class="image-tools absolute top show-on-hover right z-3">
            <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Required Flatsome compatibility hook. ?>
            <?php do_action('flatsome_product_image_tools_top'); ?>
        </div>
    <?php } ?>
    <div class="svi_wrapper">
        <?php if ($smart_variations_images_initial_markup) : ?>
            <div class="svi-initial-holder">
                <?php echo wp_kses_post($smart_variations_images_initial_markup); ?>
            </div>
        <?php elseif ($this->options->preload_fimg) : ?>
            <div class="svi-initial-holder">
                <?php echo wp_kses_post(get_the_post_thumbnail($smart_variations_images_product_id, $this->options->main_imagesize)); ?>
            </div>
        <?php endif; ?>
        <div class="svi-app-entry" data-svi-mount></div>
    </div>&nbsp;
</div>
