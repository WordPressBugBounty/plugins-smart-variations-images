<?php
/**
 * Gallery importer entry point.
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Gallery_Import_Main')) {
    /**
     * Loads and boots the importer once.
     */
    final class SVI_Gallery_Import_Main
    {
        /**
         * Boot state.
         *
         * @var bool
         */
        private static bool $booted = false;

        /**
         * Boot the add-on.
         */
        public static function boot(): void
        {
            if (self::$booted) {
                return;
            }

            self::$booted = true;

            require_once __DIR__ . '/class-svi-gallery-import-source.php';
            require_once __DIR__ . '/class-svi-gallery-import-writer.php';
            require_once __DIR__ . '/class-svi-gallery-import-runner.php';
            require_once __DIR__ . '/sources/class-svi-gallery-import-source-woo-variation-gallery.php';
            require_once __DIR__ . '/sources/class-svi-gallery-import-source-wc-additional-variation-images.php';
            require_once __DIR__ . '/sources/class-svi-gallery-import-source-rtwpvg.php';
            require_once __DIR__ . '/sources/class-svi-gallery-import-source-avmi.php';
            require_once __DIR__ . '/sources/class-svi-gallery-import-source-woothumbs.php';
            require_once __DIR__ . '/sources/class-svi-gallery-import-source-wc-core.php';
            require_once __DIR__ . '/class-svi-gallery-import-admin.php';
            require_once __DIR__ . '/class-svi-gallery-import-cli.php';

            add_filter('svi_gallery_import_sources', array('SVI_Gallery_Import_Main', 'default_sources'));

            SVI_Gallery_Import_Admin::init();
            SVI_Gallery_Import_CLI::init();
        }

        /**
         * Built-in sources.
         *
         * @param array $sources Registered sources.
         * @return array
         */
        public static function default_sources($sources): array
        {
            $sources = is_array($sources) ? $sources : array();

            $sources[] = new SVI_Gallery_Import_Source_Woo_Variation_Gallery();
            $sources[] = new SVI_Gallery_Import_Source_WC_Additional_Variation_Images();
            $sources[] = new SVI_Gallery_Import_Source_Rtwpvg();
            $sources[] = new SVI_Gallery_Import_Source_Avmi();
            $sources[] = new SVI_Gallery_Import_Source_Woothumbs();
            $sources[] = new SVI_Gallery_Import_Source_WC_Core();

            return $sources;
        }
    }

    SVI_Gallery_Import_Main::boot();
}
