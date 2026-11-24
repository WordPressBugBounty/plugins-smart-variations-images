<?php
/**
 * "Variations as products" — global settings.
 *
 * One tab in the SVI settings, off by default. Everything the free part needs
 * to decide whether a variation may appear on a page is answered here, so the
 * query and the card never read the option store directly.
 *
 * @package Smart_Variations_Images\Addons\Single_Variations
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_SV_Settings')) {
	/**
	 * Settings tab and the read helpers used by the rest of the module.
	 */
	class SVI_SV_Settings
	{
		/**
		 * Settings tab id.
		 */
		const TAB_ID = 'single_variations';

		/**
		 * Field ids owned by this module.
		 *
		 * @var string[]
		 */
		const FIELDS = array(
			'svi_sv_enabled',
			'svi_sv_in_shop',
			'svi_sv_in_tax',
			'svi_sv_in_search',
			'svi_sv_delete_data',
		);

		/**
		 * Values read from the settings store, built once per request.
		 *
		 * @var array<string, mixed>|null
		 */
		private static $values = null;

		/**
		 * Register the hooks.
		 */
		public static function init(): void
		{
			add_filter('wpsfsvi_register_settings_woosvi_options', array(__CLASS__, 'register_tab'), 35);
			add_filter('wpsfsvi_register_settings_clean_woosvi_options', array(__CLASS__, 'register_tab'), 35);
		}

		/**
		 * Add the tab and its single section.
		 *
		 * The framework runs this filter twice (settings and the cleaned copy),
		 * so the tab is only added when it is missing.
		 *
		 * @param array $settings Settings payload.
		 * @return array
		 */
		public static function register_tab($settings): array
		{
			if (!is_array($settings)) {
				return $settings;
			}

			$settings['tabs']     = isset($settings['tabs']) && is_array($settings['tabs']) ? $settings['tabs'] : array();
			$settings['sections'] = isset($settings['sections']) && is_array($settings['sections']) ? $settings['sections'] : array();

			foreach ($settings['tabs'] as $tab) {
				if (isset($tab['id']) && self::TAB_ID === $tab['id']) {
					return $settings;
				}
			}

			$settings['tabs'][] = array(
				'id'    => self::TAB_ID,
				'title' => __('Variations as products', 'smart-variations-images'),
			);

			$settings['sections'][] = array(
				'section_title'       => __('Show variations in the shop', 'smart-variations-images'),
				'tab_id'              => self::TAB_ID,
				'section_id'          => 'svi_sv_main',
				'section_order'       => 10,
				'section_description' => __('Each variation you tick on the product variations panel becomes its own card in the shop. The card links back to the product with that variation already selected, so the SVI gallery of that variation opens at once.', 'smart-variations-images'),
				'fields'              => self::fields(),
			);

			return $settings;
		}

		/**
		 * Field definitions for the section.
		 *
		 * @return array
		 */
		private static function fields(): array
		{
			$enabled = array(
				array(
					'field' => self::TAB_ID . '_svi_sv_main_svi_sv_enabled',
					'value' => array('1'),
				),
			);

			$fields = array(
				array(
					'id'      => 'svi_sv_enabled',
					'type'    => 'toggle',
					'title'   => __('Show variations as separate products in the shop', 'smart-variations-images'),
					'desc'    => __('Off by default. When on, a variation appears in the shop only if "Show in shop as its own product" is ticked on that variation.', 'smart-variations-images'),
					'default' => false,
				),
				array(
					'id'      => 'svi_sv_in_shop',
					'type'    => 'toggle',
					'title'   => __('Shop page', 'smart-variations-images'),
					'desc'    => __('Include the ticked variations on the main shop page.', 'smart-variations-images'),
					'default' => true,
					'show_if' => $enabled,
				),
				array(
					'id'      => 'svi_sv_in_tax',
					'type'    => 'toggle',
					'title'   => __('Category and tag pages', 'smart-variations-images'),
					'desc'    => __('Include the ticked variations on product category, tag and attribute archives.', 'smart-variations-images'),
					'default' => true,
					'show_if' => $enabled,
				),
				array(
					'id'      => 'svi_sv_in_search',
					'type'    => 'toggle',
					'title'   => __('Search results', 'smart-variations-images'),
					'desc'    => __('Include the ticked variations in the shop search results.', 'smart-variations-images'),
					'default' => true,
					'show_if' => $enabled,
				),
				array(
					'id'      => 'svi_sv_delete_data',
					'type'    => 'toggle',
					'title'   => __('Delete this module\'s data on uninstall', 'smart-variations-images'),
					'desc'    => __('Off by default. When on, uninstalling the plugin also deletes the per-variation visibility settings of this module. Product galleries and swatches are never touched.', 'smart-variations-images'),
					'default' => false,
				),
				array(
					'id'     => 'svi_sv_pro_hint',
					'type'   => 'custom',
					'title'  => '',
					'output' => array(__CLASS__, 'pro_hint'),
				),
			);

			return $fields;
		}

		/**
		 * Small grey line with the upgrade link, shown only when PRO is not usable.
		 */
		public static function pro_hint(): void
		{
			if (SVI_Single_Variations_Main::premium_ready()) {
				return;
			}

			echo '<p class="svi-sv-pro-hint">'
				. esc_html__('Need one card per color, bulk setup or hiding the parent? Available in SVI PRO.', 'smart-variations-images')
				. ' <a href="' . esc_url(svi_upgrade_url()) . '" target="_blank" rel="noopener">'
				. esc_html__('See SVI PRO', 'smart-variations-images')
				. '</a></p>';
		}

		/**
		 * Feature master switch.
		 *
		 * @return bool
		 */
		public static function is_enabled(): bool
		{
			return self::flag('svi_sv_enabled', false);
		}

		/**
		 * Whether variants may appear on the shop page.
		 *
		 * @return bool
		 */
		public static function in_shop(): bool
		{
			return self::is_enabled() && self::flag('svi_sv_in_shop', true);
		}

		/**
		 * Whether variants may appear on category/tag archives.
		 *
		 * @return bool
		 */
		public static function in_tax(): bool
		{
			return self::is_enabled() && self::flag('svi_sv_in_tax', true);
		}

		/**
		 * Whether variants may appear in the search results.
		 *
		 * @return bool
		 */
		public static function in_search(): bool
		{
			return self::is_enabled() && self::flag('svi_sv_in_search', true);
		}

		/**
		 * Whether uninstall should delete this module's meta.
		 *
		 * @return bool
		 */
		public static function delete_data_on_uninstall(): bool
		{
			return self::flag('svi_sv_delete_data', false);
		}

		/**
		 * Read one setting.
		 *
		 * Stored keys are `<tab>_<section>_<field>`, so the field id is matched
		 * by suffix; older flat stores are read too.
		 *
		 * @param string $id      Field id.
		 * @param mixed  $default Fallback.
		 * @return mixed
		 */
		public static function get(string $id, $default = null)
		{
			if (null === self::$values) {
				self::$values = self::read_store();
			}

			return array_key_exists($id, self::$values) ? self::$values[$id] : $default;
		}

		/**
		 * Read a boolean setting.
		 *
		 * @param string $id      Field id.
		 * @param bool   $default Fallback.
		 * @return bool
		 */
		private static function flag(string $id, bool $default): bool
		{
			$value = self::get($id, $default);

			if (is_bool($value)) {
				return $value;
			}

			if (is_array($value)) {
				return !empty($value);
			}

			return in_array(strtolower((string) $value), array('1', 'yes', 'on', 'true'), true);
		}

		/**
		 * Build the id => value map from the two option stores.
		 *
		 * @return array<string, mixed>
		 */
		private static function read_store(): array
		{
			$map = array();

			foreach (array('woosvi_options', 'woosvi_options_settings') as $option_name) {
				$stored = get_option($option_name, array());
				$stored = is_object($stored) ? get_object_vars($stored) : (array) $stored;

				foreach (self::FIELDS as $id) {
					if (array_key_exists($id, $stored)) {
						$map[$id] = $stored[$id];
						continue;
					}

					$suffix = '_' . $id;

					foreach ($stored as $name => $value) {
						if (substr((string) $name, -strlen($suffix)) === $suffix) {
							$map[$id] = $value;
							break;
						}
					}
				}
			}

			return $map;
		}

		/**
		 * Forget the cached values (used after the bulk tool writes settings).
		 */
		public static function flush_cache(): void
		{
			self::$values = null;
		}
	}
}
