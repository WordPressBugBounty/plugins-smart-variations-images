<?php
/**
 * Version tracking for the What's new screen.
 *
 * @package Smart_Variations_Images\Addons\Whats_New
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Whats_New_Tracker')) {
	/**
	 * Keeps the installed version and announces the change. The setup wizard
	 * listens to it to offer itself once, on the release that added it.
	 */
	class SVI_Whats_New_Tracker
	{
		/**
		 * Option holding the version this site has already seen.
		 */
		const VERSION_OPTION = 'woosvi_installed_version';

		/**
		 * Settings page slug.
		 */
		const SETTINGS_PAGE = 'woosvi-options-settings';

		/**
		 * Tab id of the What's new screen (#tab-whats_new).
		 */
		const TAB_ID = 'whats_new';

		/**
		 * Register the hooks.
		 */
		public static function init(): void
		{
			add_action('admin_init', [__CLASS__, 'sync_version'], 5);
		}

		/**
		 * Current plugin version.
		 *
		 * @return string
		 */
		public static function current_version(): string
		{
			return defined('SMART_VARIATIONS_IMAGES_VERSION') ? (string) SMART_VARIATIONS_IMAGES_VERSION : '';
		}

		/**
		 * Whether this site has never saved an SVI setting.
		 *
		 * @return bool
		 */
		public static function is_fresh_install(): bool
		{
			return SVI_Setup_Wizard_State::is_fresh_install();
		}

		/**
		 * Keep the stored version in step and announce the change.
		 *
		 * Plugin updates do not fire the activation hook, so this is the only
		 * place that sees them.
		 */
		public static function sync_version(): void
		{
			$current = self::current_version();
			$stored  = (string) get_option(self::VERSION_OPTION, '');

			if ('' === $current || $stored === $current) {
				return;
			}

			update_option(self::VERSION_OPTION, $current, false);

			/**
			 * Fires when the stored SVI version changes.
			 *
			 * @param string $current  Version now running.
			 * @param string $previous Version seen before, '' on the first run.
			 */
			do_action('svi_version_changed', $current, $stored);
		}

		/**
		 * Activation bookkeeping.
		 *
		 * The What's new screen has nothing to remember here; the setup wizard
		 * owns the first-run redirect and the one-time notice.
		 *
		 * @param bool $network_wide Activated for the whole network.
		 * @param bool $fresh        No SVI setting was ever saved on this site.
		 */
		public static function on_activate(bool $network_wide, bool $fresh): void
		{
			SVI_Setup_Wizard_State::on_activate($network_wide, $fresh);
		}
	}
}
