<?php
/**
 * Wizard scripts, styles and brand assets.
 *
 * @package Smart_Variations_Images\Addons\Setup_Wizard
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Setup_Wizard_Assets')) {
	/**
	 * Everything the wizard loads. Nothing comes from a CDN: the brand files
	 * ship inside the plugin and the fonts are the system stack.
	 */
	class SVI_Setup_Wizard_Assets
	{
		/**
		 * Plugin base URL.
		 *
		 * @return string
		 */
		private static function plugin_url(): string
		{
			if (defined('SMART_SVI_DIR_URL')) {
				return (string) SMART_SVI_DIR_URL;
			}

			return plugin_dir_url(dirname(dirname(dirname(__FILE__))));
		}

		/**
		 * URL of a bundled brand file.
		 *
		 * @param string $file File name inside admin/images/brand/.
		 * @return string
		 */
		public static function brand_url(string $file): string
		{
			return self::plugin_url() . 'admin/images/brand/' . $file;
		}

		/**
		 * WordPress style variant: an SVG logo with a PNG fallback.
		 *
		 * @return string
		 */
		public static function logo_url(): string
		{
			return (string) apply_filters('svi_setup_wizard_logo_url', self::brand_url('logo-header-light-swatches.svg'));
		}

		/**
		 * PNG fallback of the logo, used as an <img> onerror handler.
		 *
		 * @return string
		 */
		public static function logo_fallback_url(): string
		{
			return self::brand_url('logo-header-light-swatches@2x.png');
		}

		/**
		 * Register the wizard assets on the wizard screen only.
		 */
		public static function enqueue(): void
		{
			$suffix  = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
			$version = defined('SMART_VARIATIONS_IMAGES_VERSION') ? SMART_VARIATIONS_IMAGES_VERSION : '1';
			$base    = plugin_dir_url(__FILE__) . 'assets/';

			// WordPress admin controls: checkboxes, radios and dashicons.
			foreach (array('common', 'forms', 'buttons', 'dashicons') as $handle) {
				wp_enqueue_style($handle);
			}

			wp_enqueue_style(
				'svi-setup-wizard',
				$base . 'css/setup-wizard' . $suffix . '.css',
				array('common', 'forms', 'buttons', 'dashicons'),
				$version
			);

			wp_enqueue_script(
				'svi-setup-wizard',
				$base . 'js/setup-wizard' . $suffix . '.js',
				array(),
				$version,
				true
			);

			wp_localize_script(
				'svi-setup-wizard',
				'sviWizard',
				array(
					'ajaxUrl'   => admin_url('admin-ajax.php'),
					'action'    => SVI_Setup_Wizard_Ajax::AJAX_ACTION,
					'nonce'     => wp_create_nonce(SVI_Setup_Wizard_Ajax::NONCE),
					'steps'     => SVI_Setup_Wizard_State::STEPS,
					'settings'  => SVI_Setup_Wizard_Data::settings_url(),
					'saving'    => __('Saving…', 'smart-variations-images'),
					'saved'     => __('Saved', 'smart-variations-images'),
					'errorText' => __('Something went wrong while saving. Please try again.', 'smart-variations-images'),
					'promoCopy' => __('Copy', 'smart-variations-images'),
					'promoCopied' => __('Copied', 'smart-variations-images'),
				)
			);
		}
	}
}
