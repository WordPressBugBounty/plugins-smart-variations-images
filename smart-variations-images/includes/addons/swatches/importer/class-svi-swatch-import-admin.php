<?php
/**
 * Admin screen for importing swatches from another plugin.
 *
 * The screen lives in its own settings tab, right after Import / Export, and is
 * only registered when SVI swatches are switched on and at least one supported
 * plugin left data behind.
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Swatch_Import_Admin')) {
	/**
	 * Settings tab, AJAX handlers and the Swatches tab notice.
	 */
	class SVI_Swatch_Import_Admin
	{
		/**
		 * Tab id of the import screen.
		 */
		const TAB_ID = 'swatches_import';

		/**
		 * AJAX nonce action.
		 */
		const NONCE = 'svi_swatch_import';

		/**
		 * Hook everything up.
		 *
		 * @return void
		 */
		public static function init(): void
		{
			add_filter('wpsfsvi_register_settings_woosvi_options', [__CLASS__, 'register_tab'], 30);
			add_filter('wpsfsvi_register_settings_clean_woosvi_options', [__CLASS__, 'register_tab'], 30);
			add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue']);

			foreach (['scan', 'preview', 'import', 'undo', 'runs'] as $action) {
				add_action('wp_ajax_svi_swatch_import_' . $action, [__CLASS__, 'handle_' . $action]);
			}
		}

		/**
		 * Whether the screen has anything to show.
		 *
		 * The tab is registered whenever a source is detected and hidden by the
		 * same switch that hides the Swatches tab (body class `svi-swatches-off`),
		 * so turning swatches on or off updates both tabs without a reload.
		 *
		 * @return bool
		 */
		public static function should_show(): bool
		{
			foreach (SVI_Swatch_Import_Runner::get_sources() as $source) {
				if ($source->has_candidates()) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Add the import tab after Import / Export, plus the notice on the Swatches tab.
		 *
		 * @param array $settings Settings payload.
		 * @return array
		 */
		public static function register_tab(array $settings): array
		{
			if (!self::should_show()) {
				return $settings;
			}

			$settings['tabs'] = isset($settings['tabs']) && is_array($settings['tabs']) ? $settings['tabs'] : [];
			$settings['sections'] = isset($settings['sections']) && is_array($settings['sections']) ? $settings['sections'] : [];

			if (!self::has_tab($settings['tabs'], self::TAB_ID)) {
				$tab = [
					'id' => self::TAB_ID,
					'title' => __('Import Swatches', 'smart-variations-images'),
				];

				$settings['tabs'] = self::insert_tab_after($settings['tabs'], 'import_export', $tab);
			}

			$settings['sections'][] = [
				'section_title' => __('Import Swatches from another plugin', 'smart-variations-images'),
				'tab_id' => self::TAB_ID,
				'section_id' => 'section_import',
				'section_order' => 10,
				'fields' => [
					[
						'id' => 'importer',
						'title' => '',
						'default' => '',
						'type' => 'custom',
						'output' => [__CLASS__, 'render_screen'],
					],
				],
			];

			$settings = self::add_swatches_notice($settings);

			return $settings;
		}

		/**
		 * Is a tab id already registered?
		 *
		 * @param array  $tabs Registered tabs.
		 * @param string $id   Tab id.
		 * @return bool
		 */
		private static function has_tab(array $tabs, string $id): bool
		{
			foreach ($tabs as $tab) {
				if (isset($tab['id']) && $id === $tab['id']) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Insert a tab right after another one, or append it.
		 *
		 * @param array  $tabs    Registered tabs.
		 * @param string $after   Tab id to follow.
		 * @param array  $new_tab Tab to insert.
		 * @return array
		 */
		private static function insert_tab_after(array $tabs, string $after, array $new_tab): array
		{
			$position = null;

			foreach ($tabs as $index => $tab) {
				if (isset($tab['id']) && $after === $tab['id']) {
					$position = $index;
					break;
				}
			}

			if (null === $position) {
				$tabs[] = $new_tab;

				return $tabs;
			}

			array_splice($tabs, $position + 1, 0, [$new_tab]);

			return $tabs;
		}

		/**
		 * Point the Swatches tab at the import screen.
		 *
		 * @param array $settings Settings payload.
		 * @return array
		 */
		private static function add_swatches_notice(array $settings): array
		{
			foreach ($settings['sections'] as $index => $section) {
				if (!isset($section['tab_id']) || 'swatches' !== $section['tab_id'] || !isset($section['fields'])) {
					continue;
				}

				foreach ($section['fields'] as $field) {
					if (isset($field['id']) && 'svi_swatch_import_notice' === $field['id']) {
						return $settings;
					}
				}

				array_unshift($settings['sections'][$index]['fields'], [
					'id' => 'svi_swatch_import_notice',
					'title' => '',
					'default' => '',
					'type' => 'custom',
					'output' => [__CLASS__, 'render_swatches_notice'],
				]);

				return $settings;
			}

			return $settings;
		}

		/**
		 * Notice shown inside the Swatches tab.
		 *
		 * @return void
		 */
		public static function render_swatches_notice(): void
		{
			$detected = SVI_Swatch_Import_Runner::get_detected();

			if (empty($detected)) {
				return;
			}

			$labels = wp_list_pluck($detected, 'label');

			printf(
				'<div class="svi-import-notice"><p>%s <a href="#tab-%s">%s</a></p></div>',
				esc_html(sprintf(
					/* translators: %s: list of plugin names. */
					_n(
						'Swatch settings from %s were found in this database. You can import them into SVI.',
						'Swatch settings from %s were found in this database. You can import them into SVI.',
						count($labels),
						'smart-variations-images'
					),
					implode(', ', $labels)
				)),
				esc_attr(self::TAB_ID),
				esc_html__('Open the import screen', 'smart-variations-images')
			);
		}

		/**
		 * Markup of the import screen.
		 *
		 * @return void
		 */
		public static function render_screen(): void
		{
			$detected = SVI_Swatch_Import_Runner::get_detected();
			$status_labels = [
				SVI_Swatch_Import_Source::STATUS_ACTIVE => __('Plugin active', 'smart-variations-images'),
				SVI_Swatch_Import_Source::STATUS_INACTIVE => __('Plugin installed, not active', 'smart-variations-images'),
				SVI_Swatch_Import_Source::STATUS_DATA => __('Plugin removed, data left behind', 'smart-variations-images'),
			];

			?>
			<div class="svi-importer" data-nonce="<?php echo esc_attr(wp_create_nonce(self::NONCE)); ?>">
				<div class="svi-importer__notice">
					<p><strong><?php esc_html_e('Back up your database before you start.', 'smart-variations-images'); ?></strong>
						<?php esc_html_e('The import only adds SVI swatch values. Nothing is deleted: the other plugin keeps all of its data, and SVI values that already exist are kept unless you tick “Overwrite”. Use Undo to put SVI back exactly as it was.', 'smart-variations-images'); ?></p>
				</div>

				<p class="description">
					<?php esc_html_e('SVI can read the swatch settings another plugin left in this database and copy them into SVI. The other plugin keeps working, so you can switch back until you are happy.', 'smart-variations-images'); ?>
				</p>

				<?php if (empty($detected)) : ?>
					<p><?php esc_html_e('No supported plugin data was found.', 'smart-variations-images'); ?></p>
					<?php return; ?>
				<?php endif; ?>

				<table class="widefat striped svi-importer__sources">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e('Import from', 'smart-variations-images'); ?></th>
							<th scope="col"><?php esc_html_e('Status', 'smart-variations-images'); ?></th>
							<th scope="col"><?php esc_html_e('Data found', 'smart-variations-images'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($detected as $slug => $report) : ?>
							<?php
							$evidence = $report['evidence'];
							$counts = [];

							if (!empty($evidence['attributes'])) {
								/* translators: %d: number of attributes. */
								$counts[] = sprintf(_n('%d attribute', '%d attributes', (int) $evidence['attributes'], 'smart-variations-images'), (int) $evidence['attributes']);
							}

							if (!empty($evidence['terms'])) {
								/* translators: %d: number of values. */
								$counts[] = sprintf(_n('%d term value', '%d term values', (int) $evidence['terms'], 'smart-variations-images'), (int) $evidence['terms']);
							}

							if (!empty($evidence['products'])) {
								/* translators: %d: number of products. */
								$counts[] = sprintf(_n('%d product override', '%d product overrides', (int) $evidence['products'], 'smart-variations-images'), (int) $evidence['products']);
							}

							if (!empty($evidence['options'])) {
								$counts[] = implode(', ', array_map('sanitize_text_field', (array) $evidence['options']));
							}
							?>
							<tr>
								<td>
									<label>
										<input type="radio" name="svi_import_source" value="<?php echo esc_attr($slug); ?>" />
										<strong><?php echo esc_html($report['label']); ?></strong>
									</label>
								</td>
								<td><?php echo esc_html($status_labels[$report['status']] ?? $report['status']); ?></td>
								<td><?php echo esc_html(implode(' · ', $counts)); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<div class="svi-importer__options">
					<p><strong><?php esc_html_e('What to import', 'smart-variations-images'); ?></strong></p>
					<?php foreach (SVI_Swatch_Import_Source::get_part_labels() as $part => $label) : ?>
						<label class="svi-importer__part">
							<input type="checkbox" name="svi_import_parts[]" value="<?php echo esc_attr($part); ?>" checked="checked" />
							<?php echo esc_html($label); ?>
						</label>
					<?php endforeach; ?>

					<label class="svi-importer__overwrite">
						<input type="checkbox" name="svi_import_overwrite" value="1" />
						<?php esc_html_e('Overwrite swatch values SVI already has', 'smart-variations-images'); ?>
						<span class="description"><?php esc_html_e('Leave this off to keep every SVI value that is already set.', 'smart-variations-images'); ?></span>
					</label>
				</div>

				<p class="svi-importer__actions">
					<button type="button" class="button" data-svi-import="preview"><?php esc_html_e('Preview', 'smart-variations-images'); ?></button>
					<button type="button" class="button button-primary" data-svi-import="run"><?php esc_html_e('Import', 'smart-variations-images'); ?></button>
					<span class="spinner"></span>
				</p>

				<div class="svi-importer__scan" aria-live="polite"></div>

				<div class="svi-importer__progress" hidden>
					<div class="svi-importer__bar"><span></span></div>
					<p class="svi-importer__progress-text"></p>
				</div>

				<div class="svi-importer__results"></div>

				<div class="svi-importer__summary" aria-live="polite"></div>

				<div class="svi-importer__undo"></div>
			</div>
			<?php
		}

		/**
		 * Load the screen assets on the SVI settings page.
		 *
		 * @param string $hook Current admin page hook.
		 * @return void
		 */
		public static function enqueue($hook): void
		{
			if (false === strpos((string) $hook, 'woosvi-options-settings')) {
				return;
			}

			$base = plugins_url('../', __FILE__);

			wp_enqueue_style(
				'svi-swatch-importer',
				$base . 'public/assets/css/swatch-importer.css',
				[],
				'1.0.1'
			);

			wp_enqueue_script(
				'svi-swatch-importer',
				$base . 'public/assets/js/swatch-importer.js',
				[],
				'1.0.1',
				true
			);

			wp_localize_script('svi-swatch-importer', 'sviImporterStrings', [
				'item' => __('Item', 'smart-variations-images'),
				'status' => __('Status', 'smart-variations-images'),
				'details' => __('Details', 'smart-variations-images'),
				'found' => __('What can be imported:', 'smart-variations-images'),
				'notMigrated' => __('not migrated', 'smart-variations-images'),
				'recentRuns' => __('Recent imports', 'smart-variations-images'),
				'undo' => __('Undo this import', 'smart-variations-images'),
				'undone' => __('undone', 'smart-variations-images'),
				'incomplete' => __('incomplete', 'smart-variations-images'),
				'restored' => __('Values restored:', 'smart-variations-images'),
				'chooseSource' => __('Choose a source first.', 'smart-variations-images'),
				'choosePart' => __('Choose at least one part to import.', 'smart-variations-images'),
				'done' => __('Finished. Check a product page and the shop before you deactivate the other plugin.', 'smart-variations-images'),
				'previewDone' => __('Preview finished. Nothing was written yet.', 'smart-variations-images'),
				'confirmUndo' => __('Undo this import? The SVI values it changed will go back to what they were before.', 'smart-variations-images'),
				'confirmImport' => __('Import these swatch settings into SVI now?', 'smart-variations-images'),
				'summaryCreated' => __('created', 'smart-variations-images'),
				'summaryUpdated' => __('replaced', 'smart-variations-images'),
				'summaryKept' => __('kept (already set)', 'smart-variations-images'),
				'summaryUnchanged' => __('already identical', 'smart-variations-images'),
				'summarySkipped' => __('not migrated', 'smart-variations-images'),
			]);
		}

		/**
		 * Capability and nonce check for every AJAX request.
		 *
		 * @return void
		 */
		private static function verify_request(): void
		{
			if (!current_user_can('manage_woocommerce')) {
				wp_send_json_error(['message' => __('You are not allowed to import swatches.', 'smart-variations-images')], 403);
			}

			$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';

			if (!wp_verify_nonce($nonce, self::NONCE)) {
				wp_send_json_error(['message' => __('The request expired. Reload the page and try again.', 'smart-variations-images')], 403);
			}
		}

		/**
		 * Read the source slug from the request.
		 *
		 * @return string
		 */
		private static function request_source(): string
		{
			return isset($_POST['source']) ? sanitize_key(wp_unslash($_POST['source'])) : '';
		}

		/**
		 * Read the import options from the request.
		 *
		 * @return array
		 */
		private static function request_options(): array
		{
			$raw = [
				'overwrite' => !empty($_POST['overwrite']),
				'parts' => isset($_POST['parts']) ? (array) wp_unslash($_POST['parts']) : [],
			];

			return SVI_Swatch_Import_Runner::sanitize_options($raw);
		}

		/**
		 * Batch size, kept small enough for a shared host.
		 *
		 * @return int
		 */
		private static function batch_size(): int
		{
			return (int) apply_filters('svi_swatch_import_batch_size', 50);
		}

		/**
		 * AJAX: scan one source.
		 *
		 * @return void
		 */
		public static function handle_scan(): void
		{
			self::verify_request();

			$source = SVI_Swatch_Import_Runner::get_source(self::request_source());

			if (!$source) {
				wp_send_json_error(['message' => __('Unknown import source.', 'smart-variations-images')]);
			}

			wp_send_json_success([
				'detect' => $source->detect(),
				'scan' => $source->scan(),
			]);
		}

		/**
		 * AJAX: dry run.
		 *
		 * @return void
		 */
		public static function handle_preview(): void
		{
			self::verify_request();

			$offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;
			$result = SVI_Swatch_Import_Runner::preview(self::request_source(), $offset, self::batch_size(), self::request_options());

			if (is_wp_error($result)) {
				wp_send_json_error(['message' => $result->get_error_message()]);
			}

			wp_send_json_success($result);
		}

		/**
		 * AJAX: import one batch.
		 *
		 * @return void
		 */
		public static function handle_import(): void
		{
			self::verify_request();

			$run_id = isset($_POST['run_id']) ? sanitize_text_field(wp_unslash($_POST['run_id'])) : null;
			$offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;

			$result = SVI_Swatch_Import_Runner::import(self::request_source(), $run_id ?: null, $offset, self::batch_size(), self::request_options());

			if (is_wp_error($result)) {
				wp_send_json_error(['message' => $result->get_error_message()]);
			}

			wp_send_json_success($result);
		}

		/**
		 * AJAX: undo a run.
		 *
		 * @return void
		 */
		public static function handle_undo(): void
		{
			self::verify_request();

			$run_id = isset($_POST['run_id']) ? sanitize_text_field(wp_unslash($_POST['run_id'])) : '';
			$result = SVI_Swatch_Import_Runner::undo($run_id);

			if (is_wp_error($result)) {
				wp_send_json_error(['message' => $result->get_error_message()]);
			}

			wp_send_json_success($result);
		}

		/**
		 * AJAX: list the runs that can still be undone.
		 *
		 * @return void
		 */
		public static function handle_runs(): void
		{
			self::verify_request();

			$runs = [];

			foreach (SVI_Swatch_Import_Runner::get_runs() as $run) {
				$runs[] = [
					'id' => $run['id'],
					'source_label' => $run['source_label'],
					'started' => wp_date(get_option('date_format') . ' ' . get_option('time_format'), (int) $run['started']),
					'finished' => !empty($run['finished']),
					'undone' => !empty($run['undone']),
					'created' => (int) ($run['counts']['created'] ?? 0),
					'updated' => (int) ($run['counts']['updated'] ?? 0),
				];
			}

			wp_send_json_success(['runs' => $runs]);
		}
	}
}
