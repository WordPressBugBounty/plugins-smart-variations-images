<?php
/**
 * Source: WooCommerce Additional Variation Images (official).
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Gallery_Import_Source_WC_Additional_Variation_Images')) {
    /**
     * Stores a comma separated list of attachment ids per variation.
     */
    final class SVI_Gallery_Import_Source_WC_Additional_Variation_Images extends SVI_Gallery_Import_Source
    {
        public function get_slug(): string
        {
            return 'woocommerce-additional-variation-images';
        }

        public function get_label(): string
        {
            return __('WooCommerce Additional Variation Images', 'smart-variations-images');
        }

        protected function get_plugin_files(): array
        {
            return array(
                'woocommerce-additional-variation-images/woocommerce-additional-variation-images.php',
            );
        }

        public function get_meta_key(): string
        {
            return '_wc_additional_variation_images';
        }
    }
}
