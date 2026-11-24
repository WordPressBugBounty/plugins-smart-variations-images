<?php
/**
 * First-run setup wizard.
 *
 * @package Smart_Variations_Images\Addons\Setup_Wizard
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Setup_Wizard_Main')) {
	/**
	 * Boots the wizard: state (redirect and notice), Freemius hand-off, page,
	 * assets, saving and the SVI PRO panel.
	 */
	class SVI_Setup_Wizard_Main
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

			foreach (array('data', 'freemius', 'state', 'assets', 'ajax', 'steps', 'page') as $file) {
				require_once __DIR__ . '/class-svi-setup-wizard-' . $file . '.php';
			}

			require_once __DIR__ . '/class-svi-wizard-promo.php';

			SVI_Setup_Wizard_State::init();
			SVI_Setup_Wizard_Freemius::init();
			SVI_Setup_Wizard_Ajax::init();
			SVI_Setup_Wizard_Page::init();

			add_action('admin_init', ['SVI_Wizard_Promo', 'handle_dismiss'], 6);
		}
	}

	SVI_Setup_Wizard_Main::boot();
}
