<?php
/**
 * WP-CLI surface for the gallery importer.
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Gallery_Import_CLI')) {
    /**
     * `wp svi-gallery-import <command>`.
     */
    final class SVI_Gallery_Import_CLI
    {
        /**
         * Register the command when WP-CLI is running.
         */
        public static function init(): void
        {
            if (!defined('WP_CLI') || !WP_CLI || !class_exists('WP_CLI')) {
                return;
            }

            WP_CLI::add_command('svi-gallery-import', __CLASS__);
        }

        /**
         * List sources and what they hold.
         *
         * @param array $args       Positional args.
         * @param array $assoc_args Flags.
         */
        public function sources($args, $assoc_args): void
        {
            $rows = array();

            foreach (SVI_Gallery_Import_Runner::detect_all() as $item) {
                $rows[] = array(
                    'slug'   => $item['slug'],
                    'label'  => $item['label'],
                    'status' => $item['status'],
                    'total'  => $item['total'],
                );
            }

            WP_CLI\Utils\format_items('table', $rows, array('slug', 'label', 'status', 'total'));
        }

        /**
         * Preview a source without writing.
         *
         * ## OPTIONS
         *
         * <slug>
         * : Source slug.
         *
         * @param array $args       Positional args.
         * @param array $assoc_args Flags.
         */
        public function preview($args, $assoc_args): void
        {
            $slug   = (string) ($args[0] ?? '');
            $limit  = isset($assoc_args['limit']) ? (int) $assoc_args['limit'] : 25;
            $result = SVI_Gallery_Import_Runner::preview($slug, 0, $limit, !empty($assoc_args['overwrite']));

            WP_CLI::line((string) wp_json_encode($result, JSON_PRETTY_PRINT));
        }

        /**
         * Import a source.
         *
         * ## OPTIONS
         *
         * <slug>
         * : Source slug.
         *
         * [--limit=<n>]
         * : Variations per request.
         *
         * [--overwrite]
         * : Replace an existing gallery for the same variation.
         *
         * [--once]
         * : Run a single page.
         *
         * @param array $args       Positional args.
         * @param array $assoc_args Flags.
         */
        public function import($args, $assoc_args): void
        {
            $slug  = (string) ($args[0] ?? '');
            $limit = isset($assoc_args['limit']) ? min(100, max(1, (int) $assoc_args['limit'])) : 25;

            if (null === SVI_Gallery_Import_Runner::get_source($slug)) {
                WP_CLI::error('Unknown source: ' . $slug);
            }

            $offset = 0;
            $run    = '';
            $moved  = 0;

            do {
                $result = SVI_Gallery_Import_Runner::import($slug, $run, $offset, $limit, !empty($assoc_args['overwrite']));

                if (isset($result['error'])) {
                    WP_CLI::error($result['error']);
                }

                $run    = $result['run_id'];
                $moved += (int) $result['migrated'];
                $offset = (int) $result['next_offset'];

                WP_CLI::line(sprintf(
                    '%d/%d processed, %d imported',
                    $offset,
                    (int) $result['total'],
                    $moved
                ));
            } while (!$result['done'] && empty($assoc_args['once']));

            WP_CLI::success(sprintf('Imported %d variations. Run id: %s', $moved, $run));
        }

        /**
         * Undo an import run.
         *
         * ## OPTIONS
         *
         * <run_id>
         * : Run id printed by `import`.
         *
         * @param array $args       Positional args.
         * @param array $assoc_args Flags.
         */
        public function undo($args, $assoc_args): void
        {
            $run    = (string) ($args[0] ?? '');
            $result = SVI_Gallery_Import_Runner::undo($run);

            if (isset($result['error'])) {
                WP_CLI::error($result['error']);
            }

            WP_CLI::success(sprintf('Restored %d products.', (int) $result['restored']));
        }

        /**
         * List recent runs.
         *
         * @param array $args       Positional args.
         * @param array $assoc_args Flags.
         */
        public function runs($args, $assoc_args): void
        {
            $rows = array();

            foreach (SVI_Gallery_Import_Runner::get_runs() as $run) {
                $rows[] = array(
                    'run_id'   => $run['id'],
                    'source'   => $run['source'],
                    'products' => count((array) $run['products']),
                    'migrated' => (int) $run['migrated'],
                );
            }

            if (empty($rows)) {
                WP_CLI::line('No runs yet.');

                return;
            }

            WP_CLI\Utils\format_items('table', $rows, array('run_id', 'source', 'products', 'migrated'));
        }
    }
}
