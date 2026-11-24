<?php
/**
 * WP-CLI surface for the mirror.
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Mirror_CLI')) {
    /**
     * `wp svi-mirror <command>`.
     */
    final class SVI_Mirror_CLI
    {
        /**
         * Register the command when WP-CLI is running.
         */
        public static function init(): void
        {
            if (!defined('WP_CLI') || !WP_CLI || !class_exists('WP_CLI')) {
                return;
            }

            WP_CLI::add_command('svi-mirror', __CLASS__);
        }

        /**
         * Show the mirror state.
         *
         * ## EXAMPLES
         *
         *     wp svi-mirror status
         *
         * @param array $args       Positional args.
         * @param array $assoc_args Flags.
         */
        public function status($args, $assoc_args): void
        {
            WP_CLI::line((string) wp_json_encode(SVI_Mirror_Status::snapshot(), JSON_PRETTY_PRINT));
        }

        /**
         * Read or set the mode.
         *
         * ## OPTIONS
         *
         * [<mode>]
         * : off | write | write+backfill
         *
         * @param array $args       Positional args.
         * @param array $assoc_args Flags.
         */
        public function mode($args, $assoc_args): void
        {
            if (empty($args)) {
                WP_CLI::line(SVI_Mirror_Settings::mode());

                return;
            }

            if (!SVI_Mirror_Settings::set_mode((string) $args[0])) {
                WP_CLI::error('Unknown mode. Use off, write or write+backfill.');
            }

            WP_CLI::success('Mode set to ' . SVI_Mirror_Settings::mode());
        }

        /**
         * Start the catalogue pass.
         *
         * @param array $args       Positional args.
         * @param array $assoc_args Flags.
         */
        public function backfill($args, $assoc_args): void
        {
            $state = SVI_Mirror_Backfill::start();

            if (isset($state['message'])) {
                WP_CLI::error($state['message']);
            }

            WP_CLI::success('Backfill scheduled.');
        }

        /**
         * Pause the catalogue pass.
         *
         * @param array $args       Positional args.
         * @param array $assoc_args Flags.
         */
        public function pause($args, $assoc_args): void
        {
            SVI_Mirror_Backfill::pause();
            WP_CLI::success('Backfill paused.');
        }

        /**
         * Resume the catalogue pass.
         *
         * @param array $args       Positional args.
         * @param array $assoc_args Flags.
         */
        public function resume($args, $assoc_args): void
        {
            $state = SVI_Mirror_Backfill::resume();

            if (isset($state['message'])) {
                WP_CLI::error($state['message']);
            }

            WP_CLI::success('Backfill resumed.');
        }

        /**
         * Sync specific products now (ignores the hash short-circuit).
         *
         * ## OPTIONS
         *
         * <id>...
         * : One or more product ids.
         *
         * @param array $args       Product ids.
         * @param array $assoc_args Flags.
         */
        public function sync($args, $assoc_args): void
        {
            foreach ($args as $id) {
                $payload = SVI_Mirror_Writer::sync((int) $id);
                WP_CLI::line(sprintf('%d: %s', (int) $id, null === $payload ? 'no-op' : 'mirrored'));
            }
        }

        /**
         * Report drift between the mirror and a fresh conversion.
         *
         * @param array $args       Positional args.
         * @param array $assoc_args Flags.
         */
        public function drift($args, $assoc_args): void
        {
            $limit = isset($assoc_args['limit']) ? (int) $assoc_args['limit'] : 25;
            WP_CLI::line((string) wp_json_encode(SVI_Mirror_Maintenance::drift_sample($limit), JSON_PRETTY_PRINT));
        }

        /**
         * Delete mirror data for every product.
         *
         * ## OPTIONS
         *
         * [--yes]
         * : Skip the confirmation.
         *
         * @param array $args       Positional args.
         * @param array $assoc_args Flags.
         */
        public function purge($args, $assoc_args): void
        {
            WP_CLI::confirm('Delete _svi_galleries, its backup and the migration marker for every product?', $assoc_args);
            $result = SVI_Mirror_Maintenance::purge();
            WP_CLI::success(sprintf('Mirror data removed from %d products.', $result['removed']));
        }

        /**
         * Delete mirror data for specific products.
         *
         * ## OPTIONS
         *
         * <id>...
         * : One or more product ids.
         *
         * [--yes]
         * : Skip the confirmation.
         *
         * @param array $args       Product ids.
         * @param array $assoc_args Flags.
         */
        public function rollback($args, $assoc_args): void
        {
            WP_CLI::confirm('Delete mirror data for the given products?', $assoc_args);
            $result = SVI_Mirror_Maintenance::rollback(array_map('intval', $args));
            WP_CLI::success(sprintf('Mirror data removed from %d products.', $result['removed']));
        }
    }
}
