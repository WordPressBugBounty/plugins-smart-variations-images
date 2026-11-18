<?php
/**
 * Swatches Addon Boot
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SVI_Swatches_Main' ) ) {
	/**
	 * Main class for SVI Swatches Addon.
	 */
	class SVI_Swatches_Main {

		/**
		 * Boot state.
		 *
		 * @var bool
		 */
		private static bool $booted = false;

		/**
		 * Boot the addon.
		 */
		public static function boot(): void {
			if ( self::$booted ) {
				return;
			}

			self::$booted = true;

			// Load dependencies.
			require_once __DIR__ . '/class-svi-swatch-values.php';
			require_once __DIR__ . '/class-svi-swatches-settings.php';
			require_once __DIR__ . '/class-svi-swatches.php';
			require_once __DIR__ . '/class-svi-swatches-frontend.php';
			require_once __DIR__ . '/class-svi-swatches-product.php';

			// Initialize Settings.
			SVI_Swatches_Settings::init();

			// Initialize Core Logic.
			SVI_Swatches::instance()->init();
			
			// Initialize Frontend Rendering.
			SVI_Swatches_Frontend::init();

			// Initialize per-product overrides (admin).
			SVI_Swatches_Product::init();

			// Swatch importer (free feature: import from other swatch plugins).
			require_once __DIR__ . '/importer/bootstrap.php';
		}
	}

	// Hook into SVI Addon Loader.
	// The loader in includes/addons/bootstrap.php just requires this file.
	// We need to verify if we need to hook or just run.
	// Since bootstrap.php globs and requires main.php, we should run boot() directly
	// OR hook to 'svi_addons_loaded' if we want to wait for core.
	
	// SVI v6 bootstrap.php requires the file immediately.
	// Safer to run boot() now.
	SVI_Swatches_Main::boot();
}
