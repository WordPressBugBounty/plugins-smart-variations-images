<?php
/**
 * Source: Ajaxy WooCommerce Multiple Variation Image.
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Gallery_Import_Source_Avmi')) {
    /**
     * Stores a comma separated list of attachment ids per variation.
     */
    final class SVI_Gallery_Import_Source_Avmi extends SVI_Gallery_Import_Source
    {
        public function get_slug(): string
        {
            return 'avmi';
        }

        public function get_label(): string
        {
            return __('Ajaxy WooCommerce Multiple Variation Image', 'smart-variations-images');
        }

        protected function get_plugin_files(): array
        {
            return array('additional-variation-images/additional-variation-images.php');
        }

        public function get_meta_key(): string
        {
            return 'avmi_image_id';
        }
    }
}
