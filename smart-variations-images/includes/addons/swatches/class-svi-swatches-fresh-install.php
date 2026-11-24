<?php
/**
 * Defaults a freshly activated site starts with.
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Swatches_Fresh_Install')) {
	/**
	 * Seeds the swatch defaults on a site that has never saved an SVI setting,
	 * and flags the one-time notice that points at the Swatches settings.
	 */
	class SVI_Swatches_Fresh_Install
	{
		/**
		 * Option holding the plugin settings.
		 */
		const OPTION = 'woosvi_options_settings';

		/**
		 * Option holding the version that enabled the notice.
		 */
		const NOTICE_OPTION = 'woosvi_swatches_notice';

		/**
		 * Seed the defaults, once per site.
		 *
		 * Any stored value wins, including swatches switched off, so upgrades
		 * and existing installs keep exactly what they have.
		 *
		 * @param bool|null $fresh Whether this is a fresh install, when already known.
		 */
		public static function seed($fresh = null): void
		{
			if (null === $fresh) {
				$fresh = SVI_Setup_Wizard_State::is_fresh_install();
			}

			if (!$fresh) {
				return;
			}

			update_option(
				self::OPTION,
				array(
					'main_section_global_default'                      => '1',
					'main_section_global_default_swatches'             => '1',
					'swatches_swatches_conversion_swatch_type_default' => 'text',
				)
			);

			update_option(self::NOTICE_OPTION, SMART_VARIATIONS_IMAGES_VERSION);
		}
	}
}
