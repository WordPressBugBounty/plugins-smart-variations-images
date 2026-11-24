<?php
/**
 * "Copy link to this variation" — the button next to the variation form.
 *
 * The link itself needs no new code on the reading side: SVI already applies
 * attribute_* query args to the variation selects on load
 * (swatches.js → preselectFromQuery), which then updates the gallery.
 *
 * @package Smart_Variations_Images\Addons\Copy_Variation_Link
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Copy_Variation_Link')) {
	/**
	 * Renders and wires the copy button.
	 */
	class SVI_Copy_Variation_Link
	{
		/**
		 * Register the hooks.
		 */
		public static function init(): void
		{
			add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue']);
			add_action('woocommerce_after_variations_form', [__CLASS__, 'render']);
		}

		/**
		 * Load the assets on single product pages only.
		 */
		public static function enqueue(): void
		{
			if (!function_exists('is_product') || !is_product()) {
				return;
			}

			/**
			 * Filters whether the copy button is printed on this product page.
			 *
			 * @param bool $enabled Default true.
			 */
			if (!apply_filters('svi_copy_variation_link_enabled', true)) {
				return;
			}

			$suffix  = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
			$version = defined('SMART_VARIATIONS_IMAGES_VERSION') ? SMART_VARIATIONS_IMAGES_VERSION : '1';

			wp_enqueue_style(
				'svi-copy-variation-link',
				plugin_dir_url(__FILE__) . 'assets/css/copy-variation-link' . $suffix . '.css',
				array(),
				$version
			);

			wp_enqueue_script(
				'svi-copy-variation-link',
				plugin_dir_url(__FILE__) . 'assets/js/copy-variation-link' . $suffix . '.js',
				array(),
				$version,
				true
			);

			wp_localize_script(
				'svi-copy-variation-link',
				'sviCopyLink',
				array(
					'label'  => __('Copy link to this variation', 'smart-variations-images'),
					'copied' => __('Link copied', 'smart-variations-images'),
					'failed' => __('The link could not be copied. Select the address bar and copy it manually.', 'smart-variations-images'),
				)
			);
		}

		/**
		 * Print the button after the variations form.
		 */
		public static function render(): void
		{
			/**
			 * Filters whether the copy button is printed on this product page.
			 *
			 * @param bool $enabled Default true.
			 */
			if (!apply_filters('svi_copy_variation_link_enabled', true)) {
				return;
			}

			echo self::markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside markup().
		}

		/**
		 * Button markup.
		 *
		 * The same markup is rebuilt by the script when a theme does not fire
		 * the variations-form hook (block themes), so both paths stay identical.
		 *
		 * @return string
		 */
		public static function markup(): string
		{
			return '<div class="svi-copy-link" data-svi-copy-link>'
				. '<button type="button" class="svi-copy-link__button">'
				. '<span class="svi-copy-link__icon" aria-hidden="true">'
				. '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" width="16" height="16" focusable="false" aria-hidden="true">'
				. '<path d="M12.5 3.5a3 3 0 0 1 4.2 4.2l-2.8 2.8-1.4-1.4 2.8-2.8a1 1 0 0 0-1.4-1.4l-2.8 2.8-1.4-1.4z" fill="currentColor"/>'
				. '<path d="M7.5 16.5a3 3 0 0 1-4.2-4.2l2.8-2.8 1.4 1.4-2.8 2.8a1 1 0 0 0 1.4 1.4l2.8-2.8 1.4 1.4z" fill="currentColor"/>'
				. '<path d="M7.1 11.5l4.4-4.4 1.4 1.4-4.4 4.4z" fill="currentColor"/>'
				. '</svg>'
				. '</span>'
				. '<span class="svi-copy-link__label">' . esc_html__('Copy link to this variation', 'smart-variations-images') . '</span>'
				. '</button>'
				. '<span class="svi-copy-link__status" role="status" aria-live="polite"></span>'
				. '</div>';
		}
	}
}
