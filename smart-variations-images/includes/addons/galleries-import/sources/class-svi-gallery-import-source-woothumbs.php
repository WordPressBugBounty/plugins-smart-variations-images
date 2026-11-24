<?php
/**
 * Source: WooThumbs for WooCommerce (Iconic).
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Gallery_Import_Source_Woothumbs')) {
    /**
     * Stores a comma separated list of attachment ids per variation.
     */
    final class SVI_Gallery_Import_Source_Woothumbs extends SVI_Gallery_Import_Source
    {
        public function get_slug(): string
        {
            return 'woothumbs';
        }

        public function get_label(): string
        {
            return __('WooThumbs for WooCommerce (Iconic)', 'smart-variations-images');
        }

        protected function get_plugin_files(): array
        {
            return array('iconic-woothumbs/iconic-woothumbs.php');
        }

        public function get_meta_key(): string
        {
            return 'variation_image_gallery';
        }
    }
}
