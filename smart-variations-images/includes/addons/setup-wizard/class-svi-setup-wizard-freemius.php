<?php
/**
 * Hands the screen over to Freemius first, then to the wizard.
 *
 * @package Smart_Variations_Images\Addons\Setup_Wizard
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Setup_Wizard_Freemius')) {
	/**
	 * Freemius owns the first screen after activation: the licence form on PRO,
	 * the opt-in on the free build. The wizard waits for it, then takes over.
	 */
	class SVI_Setup_Wizard_Freemius
	{
		/**
		 * Freemius module affix, used in its filter names.
		 */
		const AFFIX = 'smart-variations-images';

		/**
		 * Freemius URL filters that lead away from the activation flow.
		 */
		const URL_FILTERS = array(
			'after_connect_url',
			'after_skip_url',
			'after_pending_connect_url',
		);

		/**
		 * Register the hooks.
		 */
		public static function init(): void
		{
			// Freemius prefixes these with fs_{$tag}_{affix}.
			foreach (self::URL_FILTERS as $filter) {
				add_filter('fs_' . $filter . '_' . self::AFFIX, [__CLASS__, 'after_activation_url'], 20);
			}
		}

		/**
		 * Whether the Freemius SDK is loading in activation mode.
		 *
		 * @return bool
		 */
		public static function is_activation_mode(): bool
		{
			if (!function_exists('svi_fs')) {
				return false;
			}

			$fs = svi_fs();

			$mode = is_object($fs) && method_exists($fs, 'is_activation_mode')
				? (bool) $fs->is_activation_mode()
				: false;

			/**
			 * Filter whether Freemius is still asking for a licence or opt-in.
			 *
			 * @param bool $mode Activation mode from the SDK.
			 */
			return (bool) apply_filters('svi_setup_wizard_is_activation_mode', $mode);
		}

		/**
		 * Whether the site pays for SVI, so promo boxes stay hidden.
		 *
		 * @return bool
		 */
		public static function is_paying(): bool
		{
			if (!function_exists('svi_fs')) {
				return false;
			}

			$fs = svi_fs();

			return is_object($fs) && method_exists($fs, 'is_paying') && (bool) $fs->is_paying();
		}

		/**
		 * Send the user to the wizard once Freemius is done.
		 *
		 * The pending flag is left untouched: it is only cleared when the wizard
		 * is opened, finished or dismissed, so a licence screen in between does
		 * not lose the invitation.
		 *
		 * @param string $url URL Freemius prepared.
		 * @return string
		 */
		public static function after_activation_url($url): string
		{
			if (!SVI_Setup_Wizard_State::is_pending()) {
				return (string) $url;
			}

			if (!current_user_can('edit_products')) {
				return (string) $url;
			}

			return SVI_Setup_Wizard_Data::wizard_url(1);
		}

		/**
		 * Freemius upgrade URL, or '' when the SDK is unavailable.
		 *
		 * @return string
		 */
		public static function upgrade_url(): string
		{
			if (!function_exists('svi_fs')) {
				return '';
			}

			$fs = svi_fs();

			if (!is_object($fs) || !method_exists($fs, 'get_upgrade_url')) {
				return '';
			}

			return (string) $fs->get_upgrade_url();
		}
	}
}
