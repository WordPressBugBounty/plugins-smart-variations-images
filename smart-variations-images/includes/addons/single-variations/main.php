<?php
/**
 * "Variations as products" addon boot.
 *
 * Shows selected variations as separate cards in the shop, the product
 * category/tag archives and the search results, linking to the parent product
 * with the variation preselected (the URL the "copy variation link" button
 * already produces, so SVI opens the gallery of that variation).
 *
 * @package Smart_Variations_Images\Addons\Single_Variations
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Single_Variations_Main')) {
	/**
	 * Loads the free part always and the PRO part only when premium code runs.
	 */
	class SVI_Single_Variations_Main
	{
		/**
		 * Boot state.
		 *
		 * @var bool
		 */
		private static bool $booted = false;

		/**
		 * Boot the addon once.
		 */
		public static function boot(): void
		{
			if (self::$booted) {
				return;
			}

			self::$booted = true;

			require_once __DIR__ . '/free/class-svi-sv-settings.php';
			require_once __DIR__ . '/free/class-svi-sv-terms.php';
			require_once __DIR__ . '/free/class-svi-sv-query.php';
			require_once __DIR__ . '/free/class-svi-sv-variation-fields.php';
			require_once __DIR__ . '/free/class-svi-sv-card.php';
			require_once __DIR__ . '/free/class-svi-sv-uninstall.php';

			SVI_SV_Settings::init();
			SVI_SV_Query::init();
			SVI_SV_Variation_Fields::init();
			SVI_SV_Card::init();
			SVI_SV_Uninstall::init();

			if (self::premium_ready()) {
				self::boot_premium();
			}
		}

		/**
		 * Whether the PRO files may be loaded on this install.
		 *
		 * @return bool
		 */
		public static function premium_ready(): bool
		{
			if (!function_exists('svi_fs')) {
				return false;
			}

			$fs = svi_fs();

			return is_object($fs)
				&& $fs->is__premium_only()
				&& $fs->can_use_premium_code__premium_only();
		}

		/**
		 * Load and start the PRO classes.
		 */
		private static function boot_premium(): void
		{
			$files = array(
				'class-svi-sv-variation-overrides__premium_only.php' => 'SVI_SV_Variation_Overrides',
				'class-svi-sv-grouping__premium_only.php'            => 'SVI_SV_Grouping',
				'class-svi-sv-taxonomies__premium_only.php'          => 'SVI_SV_Taxonomies',
				'class-svi-sv-filters__premium_only.php'             => 'SVI_SV_Filters',
				'class-svi-sv-card-pro__premium_only.php'            => 'SVI_SV_Card_Pro',
				'class-svi-sv-bulk__premium_only.php'                => 'SVI_SV_Bulk',
				'class-svi-sv-seo__premium_only.php'                 => 'SVI_SV_Seo',
			);

			foreach ($files as $file => $class) {
				$path = __DIR__ . '/pro/' . $file;

				if (!file_exists($path)) {
					continue;
				}

				require_once $path;

				if (class_exists($class)) {
					call_user_func(array($class, 'init'));
				}
			}
		}
	}

	SVI_Single_Variations_Main::boot();
}
