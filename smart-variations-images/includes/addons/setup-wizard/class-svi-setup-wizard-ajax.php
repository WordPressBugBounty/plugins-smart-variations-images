<?php
/**
 * Saving one wizard step.
 *
 * @package Smart_Variations_Images\Addons\Setup_Wizard
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Setup_Wizard_Ajax')) {
	/**
	 * Writes only the keys a step owns, so everything else in the settings
	 * option stays exactly as the store saved it. "Skip" saves nothing.
	 */
	class SVI_Setup_Wizard_Ajax
	{
		/**
		 * AJAX action.
		 */
		const AJAX_ACTION = 'svi_setup_wizard_save';

		/**
		 * Admin-post action, used when JavaScript is unavailable.
		 */
		const ACTION = 'svi_setup_wizard';

		/**
		 * Nonce action.
		 */
		const NONCE = 'svi_setup_wizard';

		/**
		 * Register the hooks.
		 */
		public static function init(): void
		{
			add_action('wp_ajax_' . self::AJAX_ACTION, [__CLASS__, 'handle_ajax']);
			add_action('admin_post_' . self::ACTION, [__CLASS__, 'handle_post']);
		}

		/**
		 * Step number from a request.
		 *
		 * @param array $source Request array.
		 * @return int
		 */
		private static function step_from(array $source): int
		{
			$step = isset($source['step']) ? absint($source['step']) : 1;

			return max(1, min(SVI_Setup_Wizard_State::STEPS, $step));
		}

		/**
		 * Handle an AJAX save.
		 */
		public static function handle_ajax(): void
		{
			check_ajax_referer(self::NONCE, 'nonce');

			if (!current_user_can('edit_products')) {
				wp_send_json_error(array('message' => __('You are not allowed to run this setup.', 'smart-variations-images')), 403);
			}

			$step = self::step_from($_POST);
			$skip = !empty($_POST['svi_wizard_skip']);

			if (!$skip) {
				self::save($step, wp_unslash($_POST));
			}

			if ($step >= SVI_Setup_Wizard_State::STEPS) {
				SVI_Setup_Wizard_State::clear('finished');
			}

			wp_send_json_success(
				array(
					'step' => min(SVI_Setup_Wizard_State::STEPS, $step + 1),
					'saved' => !$skip,
				)
			);
		}

		/**
		 * Handle the no-JS fallback.
		 */
		public static function handle_post(): void
		{
			if (!current_user_can('edit_products')) {
				wp_die(esc_html__('You are not allowed to run this setup.', 'smart-variations-images'));
			}

			check_admin_referer(self::NONCE);

			$step = self::step_from($_POST);

			if (!isset($_POST['svi_wizard_skip'])) {
				self::save($step, wp_unslash($_POST));
			}

			if ($step >= SVI_Setup_Wizard_State::STEPS) {
				SVI_Setup_Wizard_State::clear('finished');
				wp_safe_redirect(SVI_Setup_Wizard_Data::settings_url());
				exit;
			}

			wp_safe_redirect(SVI_Setup_Wizard_Data::wizard_url($step + 1));
			exit;
		}

		/**
		 * Store the settings of one step.
		 *
		 * @param int   $step Step number.
		 * @param array $post Submitted values.
		 */
		private static function save(int $step, array $post): void
		{
			$settings = SVI_Setup_Wizard_Data::settings();

			if (2 === $step) {
				$settings = array_merge($settings, self::step_swatches($post));
			}

			if (3 === $step) {
				$settings = array_merge($settings, self::step_gallery($post));
			}

			update_option(SVI_Setup_Wizard_Data::OPTION, $settings);
		}

		/**
		 * Keys owned by step 2.
		 *
		 * @param array $post Submitted values.
		 * @return array
		 */
		private static function step_swatches(array $post): array
		{
			$enabled = !empty($post['swatches']);
			$style   = isset($post['swatch_style']) ? sanitize_key($post['swatch_style']) : 'text';

			if (!in_array($style, array('text', 'radio', 'none'), true)) {
				$style = 'text';
			}

			$oos = isset($post['oos']) ? sanitize_key($post['oos']) : 'cross';

			if (!in_array($oos, array('hide', 'cross', 'blur'), true)) {
				$oos = 'cross';
			}

			$values = array(
				'main_section_global_default_swatches'             => $enabled ? '1' : '',
				'swatches_swatches_conversion_swatch_type_default' => $style,
				'swatches_swatches_catalog_show_in_catalog'        => !empty($post['catalog']) ? '1' : '',
				'swatches_swatches_product_page_oos_behavior'      => $oos,
			);

			if ($enabled) {
				// Swatches cannot show on a product page SVI is not rendering.
				$values['main_section_global_default'] = '1';
			}

			return $values;
		}

		/**
		 * Keys owned by step 3.
		 *
		 * @param array $post Submitted values.
		 * @return array
		 */
		private static function step_gallery(array $post): array
		{
			$stacked = isset($post['layout']) && 'stacked' === $post['layout'];
			$thumbs  = isset($post['thumbnails']) ? sanitize_key($post['thumbnails']) : 'below';

			if (!in_array($thumbs, array('below', 'left', 'right', 'hidden'), true)) {
				$thumbs = 'below';
			}

			$values = array(
				'slider_section_slidersvi_slider'                  => $stacked ? '' : '1',
				'stacked_section_stack_stacked'                    => $stacked ? '1' : '',
				'thumbnails_section_thumbs_hide_thumbs'            => 'hidden' === $thumbs ? '1' : '',
				'lens_section_lenssvi_lens'                        => !empty($post['lens']) ? '1' : '',
				'lightbox_section_lightboxsvi_lightbox'            => !empty($post['lightbox']) ? '1' : '',
				'main_section_variations_swselect'                 => !empty($post['swselect']) ? '1' : '',
			);

			if ('hidden' !== $thumbs) {
				$positions = array(
					'below' => '0',
					'left'  => '1',
					'right' => '2',
				);

				$values['thumbnails_section_thumbs_slider_position'] = $positions[$thumbs];
			}

			return $values;
		}
	}
}
