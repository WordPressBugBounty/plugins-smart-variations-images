<?php
/**
 * WP-CLI commands for the swatch importer.
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Swatch_Import_CLI')) {
	/**
	 * `wp svi import-swatches`.
	 */
	class SVI_Swatch_Import_CLI
	{
		/**
		 * Register the command when WP-CLI is running.
		 *
		 * @return void
		 */
		public static function init(): void
		{
			if (!defined('WP_CLI') || !WP_CLI) {
				return;
			}

			WP_CLI::add_command('svi import-swatches', __CLASS__);
		}

		/**
		 * List detected sources or import swatch data from one of them.
		 *
		 * ## OPTIONS
		 *
		 * [--list]
		 * : List detected sources and what they hold.
		 *
		 * [--runs]
		 * : List import runs that can still be undone.
		 *
		 * [--from=<slug>]
		 * : Source slug to import from.
		 *
		 * [--dry-run]
		 * : Show what would be written without writing anything.
		 *
		 * [--overwrite]
		 * : Replace SVI values that already exist.
		 *
		 * [--parts=<parts>]
		 * : Comma separated parts: attributes,terms,overrides,settings. Default: all.
		 *
		 * [--batch=<n>]
		 * : Items per batch. Default: 100.
		 *
		 * [--undo=<run-id>]
		 * : Restore the SVI values a run changed.
		 *
		 * [--format=<format>]
		 * : Output format: table or json.
		 *
		 * ## EXAMPLES
		 *
		 *     wp svi import-swatches --list
		 *     wp svi import-swatches --from=woo-variation-swatches --dry-run
		 *     wp svi import-swatches --from=woo-variation-swatches --overwrite
		 *     wp svi import-swatches --undo=20261117213000-abcdef
		 *
		 * @param array $args       Positional arguments.
		 * @param array $assoc_args Associative arguments.
		 * @return void
		 */
		public function __invoke(array $args, array $assoc_args): void
		{
			$format = isset($assoc_args['format']) ? (string) $assoc_args['format'] : 'table';

			if (isset($assoc_args['undo'])) {
				$this->undo((string) $assoc_args['undo'], $format);
				return;
			}

			if (isset($assoc_args['runs'])) {
				$this->list_runs($format);
				return;
			}

			if (isset($assoc_args['list']) || !isset($assoc_args['from'])) {
				$this->list_sources($format);
				return;
			}

			$this->import((string) $assoc_args['from'], $assoc_args, $format);
		}

		/**
		 * Print the detected sources.
		 *
		 * @param string $format Output format.
		 * @return void
		 */
		private function list_sources(string $format): void
		{
			$rows = [];

			foreach (SVI_Swatch_Import_Runner::detect_all() as $slug => $report) {
				$source = SVI_Swatch_Import_Runner::get_source($slug);

				if (!$source) {
					continue;
				}

				$scan = $source->scan();

				$rows[] = [
					'slug' => $slug,
					'label' => $report['label'],
					'status' => $report['status'],
					'attributes' => $scan['parts']['attributes'],
					'terms' => $scan['parts']['terms'],
					'overrides' => $scan['parts']['overrides'],
					'settings' => $scan['parts']['settings'],
					'unsupported' => count($scan['unsupported']),
				];
			}

			if (empty($rows)) {
				WP_CLI::warning(__('No import sources are registered.', 'smart-variations-images'));
				return;
			}

			WP_CLI\Utils\format_items($format, $rows, ['slug', 'label', 'status', 'attributes', 'terms', 'overrides', 'settings', 'unsupported']);
		}

		/**
		 * Import from one source.
		 *
		 * @param string $slug       Source slug.
		 * @param array  $assoc_args Command arguments.
		 * @param string $format     Output format.
		 * @return void
		 */
		private function import(string $slug, array $assoc_args, string $format): void
		{
			$source = SVI_Swatch_Import_Runner::get_source($slug);

			if (!$source) {
				WP_CLI::error(sprintf(
					/* translators: %s: source slug. */
					__('Unknown import source: %s', 'smart-variations-images'),
					$slug
				));
			}

			$options = SVI_Swatch_Import_Runner::sanitize_options([
				'overwrite' => !empty($assoc_args['overwrite']),
				'parts' => isset($assoc_args['parts']) ? explode(',', (string) $assoc_args['parts']) : [],
			]);

			if (empty($options['parts'])) {
				WP_CLI::error(__('No valid part selected.', 'smart-variations-images'));
			}

			$batch = isset($assoc_args['batch']) ? max(1, absint($assoc_args['batch'])) : 100;
			$dry_run = !empty($assoc_args['dry-run']);

			$offset = 0;
			$run_id = null;
			$rows = [];

			do {
				if ($dry_run) {
					$result = SVI_Swatch_Import_Runner::preview($slug, $offset, $batch, $options);
				} else {
					$result = SVI_Swatch_Import_Runner::import($slug, $run_id, $offset, $batch, $options);
				}

				if (is_wp_error($result)) {
					WP_CLI::error($result->get_error_message());
				}

				if (!$dry_run) {
					$run_id = $result['run_id'];
				}

				foreach ($result['rows'] as $row) {
					$row['part'] = $row['part'];
					$rows[] = $row;
				}

				$offset = $result['next_offset'];

				if (!$dry_run) {
					WP_CLI::log(sprintf(
						/* translators: 1: processed items, 2: total items. */
						__('Processed %1$d of %2$d items.', 'smart-variations-images'),
						$offset,
						$result['total']
					));
				}
			} while (empty($result['done']));

			if ('json' === $format) {
				WP_CLI::line(wp_json_encode(['run_id' => $run_id, 'dry_run' => $dry_run, 'rows' => $rows]));
			} else {
				WP_CLI\Utils\format_items('table', $rows, ['title', 'part', 'status', 'message']);
			}

			if ($dry_run) {
				WP_CLI::success(__('Dry run finished; nothing was written.', 'smart-variations-images'));
				return;
			}

			WP_CLI::success(sprintf(
				/* translators: %s: run id. */
				__('Import finished. Run id: %s', 'smart-variations-images'),
				$run_id
			));
		}

		/**
		 * Undo one run.
		 *
		 * @param string $run_id Run ID.
		 * @param string $format Output format.
		 * @return void
		 */
		private function undo(string $run_id, string $format): void
		{
			$result = SVI_Swatch_Import_Runner::undo($run_id);

			if (is_wp_error($result)) {
				WP_CLI::error($result->get_error_message());
			}

			if ('json' === $format) {
				WP_CLI::line(wp_json_encode($result));
			} else {
				WP_CLI::success(sprintf(
					/* translators: 1: restored values, 2: failed values. */
					__('Restored %1$d values; %2$d could not be restored.', 'smart-variations-images'),
					$result['restored'],
					$result['failed']
				));
			}
		}

		/**
		 * List undoable runs.
		 *
		 * @param string $format Output format.
		 * @return void
		 */
		private function list_runs(string $format): void
		{
			$rows = [];

			foreach (SVI_Swatch_Import_Runner::get_runs() as $run) {
				$rows[] = [
					'id' => $run['id'],
					'source' => $run['source'],
					'started' => gmdate('Y-m-d H:i', (int) $run['started']),
					'finished' => !empty($run['finished']) ? 'yes' : 'no',
					'undone' => !empty($run['undone']) ? 'yes' : 'no',
					'created' => (int) ($run['counts']['created'] ?? 0),
					'updated' => (int) ($run['counts']['updated'] ?? 0),
				];
			}

			if (empty($rows)) {
				WP_CLI::warning(__('No import runs stored.', 'smart-variations-images'));
				return;
			}

			WP_CLI\Utils\format_items($format, $rows, ['id', 'source', 'started', 'finished', 'undone', 'created', 'updated']);
		}
	}
}
