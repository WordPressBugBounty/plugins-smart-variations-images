<?php
/**
 * Source: WooCommerce Variation Gallery (woo-variation-gallery).
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Gallery_Import_Source_Woo_Variation_Gallery')) {
    /**
     * Stores an array of attachment ids per variation.
     */
    final class SVI_Gallery_Import_Source_Woo_Variation_Gallery extends SVI_Gallery_Import_Source
    {
        public function get_slug(): string
        {
            return 'woo-variation-gallery';
        }

        public function get_label(): string
        {
            return __('Additional Variation Images Gallery for WooCommerce', 'smart-variations-images');
        }

        protected function get_plugin_files(): array
        {
            return array('woo-variation-gallery/woo-variation-gallery.php');
        }

        public function get_meta_key(): string
        {
            return 'woo_variation_gallery_images';
        }
    }
}
