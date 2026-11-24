<?php
/**
 * Galleries mirror add-on entry point.
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Mirror_Main')) {
    /**
     * Loads and boots the mirror once.
     */
    final class SVI_Mirror_Main
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

            self::load('SVI_Mirror_Settings', 'class-svi-mirror-settings.php');
            self::load('SVI_Mirror_Mode', 'class-svi-mirror-mode.php');
            self::load('SVI_Gallery_Schema', 'schema/class-svi-gallery-schema.php');
            self::load('SVI_Gallery_Schema_Upgrade', 'schema/class-svi-gallery-schema-upgrade.php');
            self::load('SVI_Gallery_Repository', 'data/class-svi-gallery-repository.php');
            self::load('SVI_Migration_Context', 'migration/class-svi-migration-context.php');
            self::load('SVI_Legacy_Normalizer', 'migration/class-svi-legacy-normalizer.php');
            self::load('SVI_Gallery_Migrator', 'migration/class-svi-gallery-migrator.php');
            self::load('SVI_Mirror_Writer', 'class-svi-mirror-writer.php');
            self::load('SVI_Mirror_Observer', 'class-svi-mirror-observer.php');
            self::load('SVI_Mirror_Backfill', 'class-svi-mirror-backfill.php');
            self::load('SVI_Mirror_Maintenance', 'class-svi-mirror-maintenance.php');
            self::load('SVI_Mirror_Status', 'class-svi-mirror-status.php');
            self::load('SVI_Mirror_Admin', 'admin/class-svi-mirror-admin.php');
            self::load('SVI_Mirror_CLI', 'cli/class-svi-mirror-cli.php');

            SVI_Mirror_Backfill::register();
            SVI_Mirror_Admin::init();
            SVI_Mirror_CLI::init();

            // Nothing else is hooked while the mirror is off: a legacy-only
            // install behaves exactly as 5.3.9 did.
            if (SVI_Mirror_Mode::should_mirror()) {
                SVI_Mirror_Observer::register();
            }
        }

        /**
         * Require a file unless its class is already provided by the host.
         *
         * A later release ships the same classes under `includes/galleries/`;
         * requiring them twice would be a fatal redeclaration.
         *
         * @param string $class Class the file declares.
         * @param string $file  File path relative to this directory.
         */
        private static function load(string $class, string $file): void
        {
            if (class_exists($class, false)) {
                return;
            }

            require_once __DIR__ . '/' . $file;
        }
    }

    SVI_Mirror_Main::boot();
}
