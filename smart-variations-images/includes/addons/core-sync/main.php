<?php
/**
 * Reverse sync boot: SVI galleries → WooCommerce core variation galleries.
 *
 * @package Smart_Variations_Images\Addons\Core_Sync
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Core_Sync_Main')) {
	/**
	 * Loads the setting and the writer.
	 */
	class SVI_Core_Sync_Main
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

			require_once __DIR__ . '/class-svi-core-sync-settings.php';
			require_once __DIR__ . '/class-svi-core-sync.php';

			SVI_Core_Sync_Settings::init();
			SVI_Core_Sync::init();
		}
	}

	SVI_Core_Sync_Main::boot();
}
