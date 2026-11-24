<?php
/**
 * "Copy link to this variation" addon boot.
 *
 * @package Smart_Variations_Images\Addons\Copy_Variation_Link
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Copy_Variation_Link_Main')) {
	/**
	 * Boots the copy button on the product page.
	 */
	class SVI_Copy_Variation_Link_Main
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

			require_once __DIR__ . '/class-svi-copy-variation-link.php';

			SVI_Copy_Variation_Link::init();
		}
	}

	SVI_Copy_Variation_Link_Main::boot();
}
