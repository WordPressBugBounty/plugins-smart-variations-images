<?php

/**
 * Fired during plugin activation
 *
 * @link       https://www.rosendo.pt
 * @since      1.0.0
 *
 * @package    Smart_Variations_Images
 * @subpackage Smart_Variations_Images/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Smart_Variations_Images
 * @subpackage Smart_Variations_Images/includes
 * @author     David Rosendo <david@rosendo.pt>
 */
class Smart_Variations_Images_Activator
{

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate($network_wide = false)
	{
		// Check fro PRO SVI v3 and Deactivate it.
		if (is_plugin_active('smart-variations-images-pro/svipro.php')) {
			deactivate_plugins('smart-variations-images-pro/svipro.php');
		}

		require_once plugin_dir_path(__FILE__) . 'addons/setup-wizard/class-svi-setup-wizard-state.php';
		require_once plugin_dir_path(__FILE__) . 'addons/swatches/class-svi-swatches-fresh-install.php';

		$fresh = SVI_Setup_Wizard_State::is_fresh_install();

		SVI_Swatches_Fresh_Install::seed($fresh);

		// Arms the first-run redirect on a fresh single activation, and the
		// one-time notice on an existing install re-activated for this release.
		SVI_Setup_Wizard_State::on_activate((bool) $network_wide, $fresh);
	}
}
