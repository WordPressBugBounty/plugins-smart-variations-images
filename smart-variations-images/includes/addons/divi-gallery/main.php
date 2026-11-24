<?php
/**
 * Divi gallery integration.
 *
 * @package Smart_Variations_Images\Addons\Divi_Gallery
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Divi_Gallery_Main')) {
	/**
	 * Boots the Divi gallery addon.
	 */
	class SVI_Divi_Gallery_Main
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

			require_once __DIR__ . '/class-svi-divi-gallery.php';

			SVI_Divi_Gallery::init();
		}
	}

	SVI_Divi_Gallery_Main::boot();
}
