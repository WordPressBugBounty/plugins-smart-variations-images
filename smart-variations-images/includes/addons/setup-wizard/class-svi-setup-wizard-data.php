<?php
/**
 * Read-only data used to pre-fill and render the setup wizard.
 *
 * @package Smart_Variations_Images\Addons\Setup_Wizard
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Setup_Wizard_Data')) {
	/**
	 * Everything the wizard reads: stored settings, the shop environment and
	 * the products listed on step 4. Nothing here writes to the database.
	 */
	class SVI_Setup_Wizard_Data
	{
		/**
		 * Option holding the plugin settings.
		 */
		const OPTION = 'woosvi_options_settings';

		/**
		 * Slug of the SVI settings screen.
		 */
		const SETTINGS_PAGE = 'woosvi-options-settings';

		/**
		 * Stored settings, untouched by filters so the wizard shows what the
		 * site really has saved.
		 *
		 * @return array
		 */
		public static function settings(): array
		{
			$stored = get_option(self::OPTION, array());

			return is_object($stored) ? get_object_vars($stored) : (array) $stored;
		}

		/**
		 * Whether swatches are on, including legacy installs that only set the
		 * master switch in the old option.
		 *
		 * @return bool
		 */
		public static function swatches_enabled(): bool
		{
			if (function_exists('svi_swatches_master_enabled')) {
				return (bool) svi_swatches_master_enabled();
			}

			$settings = self::settings();

			return !empty($settings['main_section_global_default_swatches']);
		}

		/**
		 * Default style for attributes without a type.
		 *
		 * @return string text|radio|none
		 */
		public static function swatch_style(): string
		{
			$settings = self::settings();
			$style    = isset($settings['swatches_swatches_conversion_swatch_type_default'])
				? (string) $settings['swatches_swatches_conversion_swatch_type_default']
				: 'text';

			return in_array($style, array('text', 'radio', 'none'), true) ? $style : 'text';
		}

		/**
		 * Whether shop cards show swatches.
		 *
		 * @return bool
		 */
		public static function catalog_swatches(): bool
		{
			if (function_exists('svi_get_option')) {
				return (bool) svi_get_option('show_in_catalog', false);
			}

			$settings = self::settings();

			return !empty($settings['swatches_swatches_catalog_show_in_catalog']);
		}

		/**
		 * Out-of-stock behaviour on the product page.
		 *
		 * @return string hide|cross|blur
		 */
		public static function oos_behavior(): string
		{
			$settings = self::settings();
			$oos      = isset($settings['swatches_swatches_product_page_oos_behavior'])
				? (string) $settings['swatches_swatches_product_page_oos_behavior']
				: 'cross';

			return in_array($oos, array('hide', 'cross', 'blur'), true) ? $oos : 'cross';
		}

		/**
		 * Gallery layout.
		 *
		 * @return string slider|stacked
		 */
		public static function layout(): string
		{
			$settings = self::settings();

			if (!empty($settings['stacked_section_stack_stacked'])) {
				return 'stacked';
			}

			return 'slider';
		}

		/**
		 * Thumbnail position.
		 *
		 * @return string below|left|right|hidden
		 */
		public static function thumbnails(): string
		{
			$settings = self::settings();

			if (!empty($settings['thumbnails_section_thumbs_hide_thumbs'])) {
				return 'hidden';
			}

			$position = isset($settings['thumbnails_section_thumbs_slider_position'])
				? (string) $settings['thumbnails_section_thumbs_slider_position']
				: '0';

			$map = array('0' => 'below', '1' => 'left', '2' => 'right');

			return isset($map[$position]) ? $map[$position] : 'below';
		}

		/**
		 * Magnifier lens state.
		 *
		 * @return bool
		 */
		public static function lens(): bool
		{
			$settings = self::settings();

			return !empty($settings['lens_section_lenssvi_lens']);
		}

		/**
		 * Lightbox state.
		 *
		 * @return bool
		 */
		public static function lightbox(): bool
		{
			$settings = self::settings();

			return !empty($settings['lightbox_section_lightboxsvi_lightbox']);
		}

		/**
		 * Whether the gallery changes as soon as one attribute is picked.
		 *
		 * @return bool
		 */
		public static function single_attribute_swap(): bool
		{
			$settings = self::settings();

			return !empty($settings['main_section_variations_swselect']);
		}

		/**
		 * Another swatches plugin that left data behind, if any.
		 *
		 * @return string Plugin label, or '' when none was found.
		 */
		public static function import_source_label(): string
		{
			if (!class_exists('SVI_Swatch_Import_Runner') || !class_exists('SVI_Swatch_Import_Admin') || !SVI_Swatch_Import_Admin::should_show()) {
				return '';
			}

			foreach (SVI_Swatch_Import_Runner::get_sources() as $source) {
				if ($source->has_candidates()) {
					return (string) $source->get_label();
				}
			}

			return '';
		}

		/**
		 * URL of the existing import tool, if it is available.
		 *
		 * @return string
		 */
		public static function import_url(): string
		{
			return admin_url('admin.php?page=' . self::SETTINGS_PAGE . '#tab-swatches_import');
		}

		/**
		 * WooCommerce version, or '' when WooCommerce is not active.
		 *
		 * @return string
		 */
		public static function woocommerce_version(): string
		{
			return defined('WC_VERSION') ? (string) WC_VERSION : '';
		}

		/**
		 * Active theme name.
		 *
		 * @return string
		 */
		public static function theme_name(): string
		{
			$theme = wp_get_theme();

			return (string) $theme->get('Name');
		}

		/**
		 * Published variable products.
		 *
		 * @param int $limit Number of products, -1 for all.
		 * @return array
		 */
		public static function variable_products(int $limit = 5): array
		{
			if (!function_exists('wc_get_products')) {
				return array();
			}

			return (array) wc_get_products(
				array(
					'type'    => 'variable',
					'status'  => 'publish',
					'limit'   => $limit,
					'orderby' => 'date',
					'order'   => 'DESC',
				)
			);
		}

		/**
		 * Number of published variable products, cached for ten minutes.
		 *
		 * @return int
		 */
		public static function variable_product_count(): int
		{
			$cached = get_transient('svi_wizard_variable_count');

			if (false !== $cached) {
				return (int) $cached;
			}

			$count = count(self::variable_products(-1));

			set_transient('svi_wizard_variable_count', $count, 10 * MINUTE_IN_SECONDS);

			return $count;
		}

		/**
		 * Galleries created for a product, and how many values it has.
		 *
		 * @param WC_Product $product Product.
		 * @return array{created:int,expected:int}
		 */
		public static function gallery_counts($product): array
		{
			$stored   = $product->get_meta('_svi_galleries', true);
			$galleries = is_array($stored) && isset($stored['entries']) ? $stored['entries'] : $stored;

			// Older stores keep their galleries in the legacy meta key.
			if (empty($galleries)) {
				$galleries = get_post_meta($product->get_id(), 'woosvi_slug', true);
			}

			$created = 0;

			if (class_exists('Smart_Variations_Images')) {
				$created = count(Smart_Variations_Images::normalize_galleries($galleries));
			} elseif (is_array($galleries)) {
				$created = count($galleries);
			}

			$expected = 0;

			foreach ((array) $product->get_variation_attributes() as $values) {
				$expected += count((array) $values);
			}

			return array('created' => $created, 'expected' => $expected);
		}

		/**
		 * Product edit screen with the SVI tab pre-opened.
		 *
		 * @param int $product_id Product ID.
		 * @return string
		 */
		public static function product_editor_url(int $product_id): string
		{
			return add_query_arg(
				array(
					'post'        => $product_id,
					'action'      => 'edit',
					'svi-tab'     => 'svi_swatches',
				),
				admin_url('post.php')
			);
		}

		/**
		 * Wizard URL for a given step.
		 *
		 * @param int $step Step number.
		 * @return string
		 */
		public static function wizard_url(int $step = 1): string
		{
			return (string) apply_filters(
				'svi_setup_wizard_url',
				add_query_arg(
					array(
						'page' => SVI_Setup_Wizard_State::PAGE,
						'step' => max(1, min(SVI_Setup_Wizard_State::STEPS, $step)),
					),
					admin_url('admin.php')
				)
			);
		}

		/**
		 * SVI settings screen URL.
		 *
		 * @return string
		 */
		public static function settings_url(): string
		{
			return admin_url('admin.php?page=' . self::SETTINGS_PAGE);
		}
	}
}
