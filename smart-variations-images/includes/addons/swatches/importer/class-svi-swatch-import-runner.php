<?php
/**
 * Runs swatch imports in batches and keeps the rollback log.
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Swatch_Import_Runner')) {
	/**
	 * Registry, batching and undo for every import source.
	 */
	class SVI_Swatch_Import_Runner
	{
		/**
		 * Option that stores the last runs and their rollback logs.
		 */
		const RUNS_OPTION = 'svi_swatch_import_runs';

		/**
		 * How many runs stay undoable.
		 */
		const MAX_RUNS = 5;

		/**
		 * Registered source instances, keyed by slug.
		 *
		 * @var SVI_Swatch_Import_Source[]|null
		 */
		private static $sources = null;

		/**
		 * Every registered source.
		 *
		 * @return SVI_Swatch_Import_Source[]
		 */
		public static function get_sources(): array
		{
			if (null !== self::$sources) {
				return self::$sources;
			}

			$sources = [];

			/**
			 * Register an extra swatch import source.
			 *
			 * @param SVI_Swatch_Import_Source[] $sources Sources keyed by slug.
			 */
			$registered = apply_filters('svi_swatch_import_sources', []);

			foreach ((array) $registered as $source) {
				if (!$source instanceof SVI_Swatch_Import_Source) {
					continue;
				}

				$sources[$source->get_slug()] = $source;
			}

			self::$sources = $sources;

			return self::$sources;
		}

		/**
		 * One source by slug.
		 *
		 * @param string $slug Source slug.
		 * @return SVI_Swatch_Import_Source|null
		 */
		public static function get_source(string $slug): ?SVI_Swatch_Import_Source
		{
			$sources = self::get_sources();

			return $sources[$slug] ?? null;
		}

		/**
		 * Detection report for every source.
		 *
		 * @return array[]
		 */
		public static function detect_all(): array
		{
			$report = [];

			foreach (self::get_sources() as $slug => $source) {
				$report[$slug] = $source->detect();
			}

			return $report;
		}

		/**
		 * Sources whose data (or plugin) can be imported from.
		 *
		 * @return array[]
		 */
		public static function get_detected(): array
		{
			$detected = [];

			foreach (self::detect_all() as $slug => $report) {
				if (SVI_Swatch_Import_Source::STATUS_NONE === $report['status']) {
					continue;
				}

				$detected[$slug] = $report;
			}

			return $detected;
		}

		/**
		 * Detection plus scan counts for every detected source.
		 *
		 * @return array[]
		 */
		public static function scan_all(): array
		{
			$rows = [];

			foreach (self::get_detected() as $slug => $report) {
				$source = self::get_source($slug);

				if (!$source) {
					continue;
				}

				$report['scan'] = $source->scan();
				$rows[$slug] = $report;
			}

			return $rows;
		}

		/**
		 * Default import options.
		 *
		 * @return array{overwrite:bool,parts:string[]}
		 */
		public static function default_options(): array
		{
			return [
				'overwrite' => false,
				'parts' => array_keys(SVI_Swatch_Import_Source::get_part_labels()),
			];
		}

		/**
		 * Normalise options coming from a request.
		 *
		 * @param array $options Raw options.
		 * @return array{overwrite:bool,parts:string[]}
		 */
		public static function sanitize_options(array $options): array
		{
			$allowed = array_keys(SVI_Swatch_Import_Source::get_part_labels());
			$parts = isset($options['parts']) && is_array($options['parts']) && !empty($options['parts']) ? $options['parts'] : $allowed;
			$parts = array_values(array_intersect($allowed, array_map('sanitize_key', $parts)));

			return [
				'overwrite' => !empty($options['overwrite']),
				'parts' => $parts,
			];
		}

		/**
		 * Items that belong to the selected parts.
		 *
		 * @param SVI_Swatch_Import_Source $source  Source.
		 * @param string[]                 $parts   Selected parts.
		 * @return array[]
		 */
		private static function filtered_items(SVI_Swatch_Import_Source $source, array $parts): array
		{
			$items = [];

			foreach ($source->get_items() as $item) {
				if (!isset($item['part']) || !in_array($item['part'], $parts, true)) {
					continue;
				}

				$items[] = $item;
			}

			return $items;
		}

		/**
		 * Dry run for one batch.
		 *
		 * @param string $slug    Source slug.
		 * @param int    $offset  Item offset.
		 * @param int    $limit   Batch size.
		 * @param array  $options Import options.
		 * @return array|\WP_Error
		 */
		public static function preview(string $slug, int $offset, int $limit, array $options)
		{
			$source = self::get_source($slug);

			if (!$source) {
				return new WP_Error('svi_import_unknown_source', __('Unknown import source.', 'smart-variations-images'));
			}

			$options = self::sanitize_options($options);
			$items = self::filtered_items($source, $options['parts']);
			$total = count($items);
			$offset = max(0, $offset);
			$limit = max(1, $limit);
			$batch = array_slice($items, $offset, $limit);

			$writer = new SVI_Swatch_Import_Writer($options['overwrite'], true);
			$rows = [];
			$summary = self::empty_counts();

			foreach ($batch as $item) {
				$log = [];
				$result = $writer->write($item, $log);

				$summary[$result['status']] = ($summary[$result['status']] ?? 0) + 1;

				$rows[] = [
					'title' => (string) ($item['title'] ?? ''),
					'part' => (string) ($item['part'] ?? ''),
					'status' => $result['status'],
					'message' => trim($result['message'] . ' ' . (string) ($item['warning'] ?? '')),
				];
			}

			$next = $offset + count($batch);

			return [
				'offset' => $offset,
				'next_offset' => $next,
				'total' => $total,
				'done' => $next >= $total,
				'rows' => $rows,
				'summary' => $summary,
			];
		}

		/**
		 * Write one batch, creating the run on the first call.
		 *
		 * @param string      $slug    Source slug.
		 * @param string|null $run_id  Existing run to resume.
		 * @param int         $offset  Item offset.
		 * @param int         $limit   Batch size.
		 * @param array       $options Import options.
		 * @return array|\WP_Error
		 */
		public static function import(string $slug, ?string $run_id, int $offset, int $limit, array $options)
		{
			$source = self::get_source($slug);

			if (!$source) {
				return new WP_Error('svi_import_unknown_source', __('Unknown import source.', 'smart-variations-images'));
			}

			$runs = self::get_runs();
			$run = $run_id && isset($runs[$run_id]) ? $runs[$run_id] : null;

			if (null === $run) {
				$run_id = self::new_run_id();
				$run = [
					'id' => $run_id,
					'source' => $slug,
					'source_label' => $source->get_label(),
					'started' => time(),
					'updated' => time(),
					'finished' => false,
					'undone' => false,
					'offset' => 0,
					'total' => 0,
					'counts' => self::empty_counts(),
					'options' => self::sanitize_options($options),
					'log' => [],
				];
				$offset = 0;
			}

			$items = self::filtered_items($source, $run['options']['parts']);
			$total = count($items);
			$offset = max(0, min($offset, $total));
			$limit = max(1, $limit);
			$batch = array_slice($items, $offset, $limit);

			$writer = new SVI_Swatch_Import_Writer(!empty($run['options']['overwrite']), false);
			$rows = [];

			foreach ($batch as $item) {
				$result = $writer->write($item, $run['log']);

				$run['counts'][$result['status']] = ($run['counts'][$result['status']] ?? 0) + 1;

				$rows[] = [
					'title' => (string) ($item['title'] ?? ''),
					'part' => (string) ($item['part'] ?? ''),
					'status' => $result['status'],
					'message' => trim($result['message'] . ' ' . (string) ($item['warning'] ?? '')),
				];
			}

			$next = $offset + count($batch);

			$run['offset'] = $next;
			$run['total'] = $total;
			$run['updated'] = time();
			$run['finished'] = $next >= $total;

			$runs[$run_id] = $run;
			self::save_runs($runs);

			return [
				'run_id' => $run_id,
				'offset' => $offset,
				'next_offset' => $next,
				'total' => $total,
				'done' => $run['finished'],
				'rows' => $rows,
				'counts' => $run['counts'],
			];
		}

		/**
		 * Undo everything a run changed.
		 *
		 * @param string $run_id Run ID.
		 * @return array|\WP_Error
		 */
		public static function undo(string $run_id)
		{
			$runs = self::get_runs();

			if (!isset($runs[$run_id])) {
				return new WP_Error('svi_import_unknown_run', __('That import run no longer exists.', 'smart-variations-images'));
			}

			$run = $runs[$run_id];

			if (!empty($run['undone'])) {
				return new WP_Error('svi_import_already_undone', __('This import was already undone.', 'smart-variations-images'));
			}

			$restored = 0;
			$failed = 0;

			foreach (array_reverse((array) ($run['log'] ?? [])) as $entry) {
				if (SVI_Swatch_Import_Writer::restore((array) $entry)) {
					$restored++;
				} else {
					$failed++;
				}
			}

			$run['undone'] = time();
			$run['log'] = [];
			$run['counts'] = self::empty_counts();

			$runs[$run_id] = $run;
			self::save_runs($runs);

			return [
				'run_id' => $run_id,
				'restored' => $restored,
				'failed' => $failed,
			];
		}

		/**
		 * Runs that happened, newest first.
		 *
		 * @return array[]
		 */
		public static function get_runs(): array
		{
			$runs = get_option(self::RUNS_OPTION, []);
			$runs = is_array($runs) ? $runs : [];

			uasort($runs, static function ($a, $b) {
				return (int) ($b['started'] ?? 0) <=> (int) ($a['started'] ?? 0);
			});

			return $runs;
		}

		/**
		 * One stored run.
		 *
		 * @param string $run_id Run ID.
		 * @return array|null
		 */
		public static function get_run(string $run_id): ?array
		{
			$runs = self::get_runs();

			return $runs[$run_id] ?? null;
		}

		/**
		 * Persist runs, keeping only the last few.
		 *
		 * @param array $runs Runs keyed by ID.
		 * @return void
		 */
		private static function save_runs(array $runs): void
		{
			uasort($runs, static function ($a, $b) {
				return (int) ($b['started'] ?? 0) <=> (int) ($a['started'] ?? 0);
			});

			$runs = array_slice($runs, 0, self::MAX_RUNS, true);

			update_option(self::RUNS_OPTION, $runs, false);
		}

		/**
		 * Per-status counters.
		 *
		 * @return array<string, int>
		 */
		private static function empty_counts(): array
		{
			return [
				'created' => 0,
				'updated' => 0,
				'kept' => 0,
				'unchanged' => 0,
				'skipped' => 0,
			];
		}

		/**
		 * New run identifier.
		 *
		 * @return string
		 */
		private static function new_run_id(): string
		{
			return gmdate('YmdHis') . '-' . wp_generate_password(6, false, false);
		}
	}
}
