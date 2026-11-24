<?php
/**
 * Variant schema settings.
 *
 * One tab: the free ProductGroup switch with the reason when another SEO
 * plugin already outputs variants, and (PRO) the feed options.
 *
 * @package Smart_Variations_Images\Addons\Variant_Schema
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_VS_Settings')) {
	/**
	 * Settings tab and the read helpers.
	 */
	class SVI_VS_Settings
	{
		/**
		 * Settings tab id.
		 */
		const TAB_ID = 'variant_schema';

		/**
		 * Field ids read by the module.
		 *
		 * @var string[]
		 */
		const FIELDS = array('svi_vs_enabled');

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
			add_filter('wpsfsvi_register_settings_woosvi_options', array(__CLASS__, 'register_tab'), 38);
			add_filter('wpsfsvi_register_settings_clean_woosvi_options', array(__CLASS__, 'register_tab'), 38);
		}

		/**
		 * Add the tab and its free section.
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
				'title' => __('Variants & feeds', 'smart-variations-images'),
			);

			$settings['sections'][] = array(
				'section_title'       => __('Structured data', 'smart-variations-images'),
				'tab_id'              => self::TAB_ID,
				'section_id'          => 'section_vs_schema',
				'section_order'       => 10,
				'section_description' => __('Variable products are described as a ProductGroup with one Product per variation, following the Google product variants markup. Every variant points at the parent URL that preselects it and lists the images of its SVI gallery.', 'smart-variations-images'),
				'fields'              => array(
					array(
						'id'      => 'svi_vs_enabled',
						'type'    => 'toggle',
						'title'   => __('Describe the variations in structured data', 'smart-variations-images'),
						'desc'    => __('On by default. Turn it off to leave the structured data exactly as WooCommerce prints it.', 'smart-variations-images'),
						'default' => true,
					),
					array(
						'id'     => 'svi_vs_conflict_note',
						'type'   => 'custom',
						'title'  => '',
						'output' => array(__CLASS__, 'conflict_note'),
					),
				),
			);

			return $settings;
		}

		/**
		 * Explain the automatic switch-off when another plugin owns the schema.
		 */
		public static function conflict_note(): void
		{
			$conflict = SVI_VS_Schema::conflict();

			if ('' === $conflict) {
				return;
			}

			printf(
				'<p class="description">%s</p>',
				esc_html(
					sprintf(
						/* translators: %s: SEO plugin name. */
						__('Paused: %s already prints product variants, so SVI leaves the structured data to it. Filter svi_variant_schema_enabled returns true to force ours.', 'smart-variations-images'),
						$conflict
					)
				)
			);
		}

		/**
		 * Whether the module may output its ProductGroup.
		 *
		 * @return bool
		 */
		public static function is_enabled(): bool
		{
			if (null === self::$values) {
				self::$values = self::read_store();
			}

			$value = self::$values['svi_vs_enabled'] ?? true;

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
	}
}
