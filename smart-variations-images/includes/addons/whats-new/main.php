<?php
/**
 * "What's new" addon boot.
 *
 * @package Smart_Variations_Images\Addons\Whats_New
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Whats_New_Main')) {
	/**
	 * Boots the "What's new" screen, the update notice and the first-run redirect.
	 */
	class SVI_Whats_New_Main
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

			require_once __DIR__ . '/class-svi-whats-new-tracker.php';
			require_once __DIR__ . '/class-svi-whats-new-settings.php';

			SVI_Whats_New_Tracker::init();
			SVI_Whats_New_Settings::init();
		}
	}

	SVI_Whats_New_Main::boot();
}
