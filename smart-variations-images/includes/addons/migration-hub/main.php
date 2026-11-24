<?php
/**
 * Migration hub entry point.
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Migration_Hub_Main')) {
    /**
     * Loads and boots the hub once.
     */
    final class SVI_Migration_Hub_Main
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

            require_once __DIR__ . '/class-svi-migration-hub-admin.php';

            SVI_Migration_Hub_Admin::init();
        }
    }

    SVI_Migration_Hub_Main::boot();
}
