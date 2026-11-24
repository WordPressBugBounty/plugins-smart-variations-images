<?php
/**
 * Source: Variation Images Gallery for WooCommerce (RadiusTheme).
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Gallery_Import_Source_Rtwpvg')) {
    /**
     * Stores an array of attachment ids per variation.
     */
    final class SVI_Gallery_Import_Source_Rtwpvg extends SVI_Gallery_Import_Source
    {
        public function get_slug(): string
        {
            return 'rtwpvg';
        }

        public function get_label(): string
        {
            return __('Variation Images Gallery for WooCommerce (RadiusTheme)', 'smart-variations-images');
        }

        protected function get_plugin_files(): array
        {
            return array('woo-product-variation-gallery/woo-product-variation-gallery.php');
        }

        public function get_meta_key(): string
        {
            return 'rtwpvg_images';
        }
    }
}
