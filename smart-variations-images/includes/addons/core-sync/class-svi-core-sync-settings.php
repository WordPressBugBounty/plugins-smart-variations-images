<?php
/**
 * Setting for the reverse sync, shown inside the Migration tab.
 *
 * @package Smart_Variations_Images\Addons\Core_Sync
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Core_Sync_Settings')) {
	/**
	 * Adds "Also save SVI galleries to WooCommerce variations" to the Migration tab.
	 */
	class SVI_Core_Sync_Settings
	{
		/**
		 * Field id.
		 */
		const FIELD = 'svi_core_sync_enabled';

		/**
		 * Values read from the option store, built once per request.
		 *
		 * @var array<string, mixed>|null
		 */
		private static $values = null;

		/**
		 * Register the hooks.
		 */
		public static function init(): void
		{
			add_filter('wpsfsvi_register_settings_woosvi_options', array(__CLASS__, 'register_section'), 50);
			add_filter('wpsfsvi_register_settings_clean_woosvi_options', array(__CLASS__, 'register_section'), 50);
		}

		/**
		 * Append the section to the Migration tab.
		 *
		 * @param array $settings Settings payload.
		 * @return array
		 */
		public static function register_section($settings): array
		{
			if (!is_array($settings)) {
				return $settings;
			}

			$has_tab = false;

			foreach ($settings['tabs'] as $tab) {
				if (isset($tab['id']) && 'migration' === $tab['id']) {
					$has_tab = true;
					break;
				}
			}

			if (!$has_tab) {
				return $settings;
			}

			foreach ($settings['sections'] as $section) {
				if (isset($section['section_id']) && 'section_core_sync' === $section['section_id']) {
					return $settings;
				}
			}

			$settings['sections'][] = array(
				'section_title'       => __('WooCommerce core variation galleries', 'smart-variations-images'),
				'tab_id'              => 'migration',
				'section_id'          => 'section_core_sync',
				'section_order'       => 90,
				'section_description' => __('WooCommerce 11.1 and newer can store a gallery on each variation. SVI reads those galleries in the import above. Turn this on to also write SVI galleries back into WooCommerce, so the core Product Gallery block, REST v3, feeds and structured data see the same images.', 'smart-variations-images'),
				'fields'              => array(
					array(
						'id'      => self::FIELD,
						'type'    => 'toggle',
						'title'   => __('Also save SVI galleries to WooCommerce variations', 'smart-variations-images'),
						'desc'    => __('Off by default. Before the first write each product keeps a backup of its variation images. Turning this off never deletes what WooCommerce already holds.', 'smart-variations-images'),
						'default' => false,
					),
				),
			);

			return $settings;
		}

		/**
		 * Whether the reverse sync is on.
		 *
		 * @return bool
		 */
		public static function is_enabled(): bool
		{
			if (null === self::$values) {
				self::$values = self::read_store();
			}

			$value = self::$values[self::FIELD] ?? false;

			if (is_bool($value)) {
				return $value;
			}

			return in_array(strtolower((string) $value), array('1', 'yes', 'on', 'true'), true);
		}

		/**
		 * Read the field from the option stores.
		 *
		 * @return array<string, mixed>
		 */
		private static function read_store(): array
		{
			$map = array();

			foreach (array('woosvi_options_settings', 'woosvi_options') as $option_name) {
				$stored = get_option($option_name, array());
				$stored = is_object($stored) ? get_object_vars($stored) : (array) $stored;

				if (array_key_exists(self::FIELD, $stored)) {
					$map[self::FIELD] = $stored[self::FIELD];
					continue;
				}

				$suffix = '_' . self::FIELD;

				foreach ($stored as $name => $value) {
					if (substr((string) $name, -strlen($suffix)) === $suffix) {
						$map[self::FIELD] = $value;
						break;
					}
				}
			}

			return $map;
		}
	}
}
