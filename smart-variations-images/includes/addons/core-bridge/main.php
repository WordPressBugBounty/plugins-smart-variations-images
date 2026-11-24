<?php
/**
 * Core bridge boot: WooCommerce "color / image" attributes as SVI swatches.
 *
 * @package Smart_Variations_Images\Addons\Core_Bridge
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Core_Bridge_Main')) {
	/**
	 * Loads the bridge once.
	 */
	class SVI_Core_Bridge_Main
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

			require_once __DIR__ . '/class-svi-core-visual.php';

			SVI_Core_Visual::init();
		}
	}

	SVI_Core_Bridge_Main::boot();
}
