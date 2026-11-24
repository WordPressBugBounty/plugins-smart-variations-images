<?php
/**
 * Data cleanup for the "Variations as products" module.
 *
 * The plugin keeps its data unless the merchant turns on "Delete this module's
 * data on uninstall". When they did, only the meta and the option this module
 * owns are removed: product galleries, swatches, orders and every other plugin
 * setting are left untouched.
 *
 * The cleanup hangs off Freemius' `after_uninstall` hook rather than a bundled
 * uninstall.php. Freemius registers its own uninstall callback, and WordPress
 * skips that callback entirely when an uninstall.php is present, so a second
 * file would silently break the SDK's uninstall tracking (and the plugin
 * package is rejected on upload for the same reason).
 *
 * @package Smart_Variations_Images\Addons\Single_Variations
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_SV_Uninstall')) {
	/**
	 * Registers the module cleanup on the Freemius uninstall event.
	 */
	final class SVI_SV_Uninstall
	{
		/**
		 * Postmeta written by the free and the PRO part of the module.
		 *
		 * @var string[]
		 */
		const META_KEYS = array(
			'_svi_sv_visible',
			'_svi_sv_title',
			'_svi_sv_no_cart',
			'_svi_sv_group_attribute',
			'_svi_sv_group_rep',
			'_svi_sv_group_key',
			'_svi_sv_hide_parent',
			'_svi_sv_categories',
			'_svi_sv_tags',
		);

		/**
		 * Option holding the PRO bulk run state.
		 */
		const BULK_STATE_OPTION = 'svi_sv_bulk_state';

		/**
		 * Boot state.
		 *
		 * @var bool
		 */
		private static bool $booted = false;

		/**
		 * Hook the cleanup on the plugin uninstall.
		 */
		public static function init(): void
		{
			if (self::$booted) {
				return;
			}

			self::$booted = true;

			if (!function_exists('svi_fs')) {
				return;
			}

			$fs = svi_fs();

			if (!is_object($fs) || !method_exists($fs, 'add_action')) {
				return;
			}

			$fs->add_action('after_uninstall', array(__CLASS__, 'cleanup'));
		}

		/**
		 * Delete the module data, when the merchant asked for it.
		 */
		public static function cleanup(): void
		{
			if (!class_exists('SVI_SV_Settings') || !SVI_SV_Settings::delete_data_on_uninstall()) {
				return;
			}

			global $wpdb;

			foreach (self::META_KEYS as $meta_key) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- One-off uninstall cleanup.
				$wpdb->delete($wpdb->postmeta, array('meta_key' => $meta_key));
			}

			delete_option(self::BULK_STATE_OPTION);
		}
	}
}
