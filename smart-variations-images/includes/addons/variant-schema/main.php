<?php
/**
 * Variant schema boot: ProductGroup structured data and product feeds.
 *
 * @package Smart_Variations_Images\Addons\Variant_Schema
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Variant_Schema_Main')) {
	/**
	 * Loads the free part always and the feeds only when premium code runs.
	 */
	class SVI_Variant_Schema_Main
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

			require_once __DIR__ . '/free/class-svi-vs-settings.php';
			require_once __DIR__ . '/free/class-svi-vs-group-data.php';
			require_once __DIR__ . '/free/class-svi-vs-schema.php';

			SVI_VS_Settings::init();
			SVI_VS_Schema::init();

			if (!self::premium_ready()) {
				return;
			}

			$pro = __DIR__ . '/pro/class-svi-vs-feeds__premium_only.php';

			// The free build ships without the pro folder.
			if (!file_exists($pro)) {
				return;
			}

			require_once $pro;

			if (class_exists('SVI_VS_Feeds')) {
				SVI_VS_Feeds::init();
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

			return is_object($fs) && $fs->is__premium_only() && $fs->can_use_premium_code__premium_only();
		}
	}

	SVI_Variant_Schema_Main::boot();
}
