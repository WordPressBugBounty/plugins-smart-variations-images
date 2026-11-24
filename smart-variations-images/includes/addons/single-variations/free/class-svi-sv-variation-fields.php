<?php
/**
 * "Variations as products" — per-variation opt-in.
 *
 * Adds "Show in shop as its own product" to the variations panel, under the
 * SVI gallery builder, and stores the answer in `_svi_sv_visible`.
 *
 * @package Smart_Variations_Images\Addons\Single_Variations
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_SV_Variation_Fields')) {
	/**
	 * Variation panel field and its save routine.
	 */
	class SVI_SV_Variation_Fields
	{
		/**
		 * Contexts written by the free checkbox.
		 *
		 * @var string
		 */
		const FREE_TOKENS = 'catalog,search,filter';

		/**
		 * Register the hooks.
		 */
		public static function init(): void
		{
			add_action('woocommerce_product_after_variable_attributes', array(__CLASS__, 'render'), 20, 3);
			add_action('woocommerce_save_product_variation', array(__CLASS__, 'save'), 20, 2);
			add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue'));
		}

		/**
		 * Load the small stylesheet that frames the checkbox on product screens.
		 *
		 * @param string $hook Current admin page.
		 */
		public static function enqueue($hook): void
		{
			if ('post.php' !== $hook && 'post-new.php' !== $hook) {
				return;
			}

			$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
			$file   = __DIR__ . '/../assets/css/single-variations' . $suffix . '.css';

			wp_enqueue_style(
				'svi-single-variations-admin',
				plugins_url('assets/css/single-variations' . $suffix . '.css', __DIR__ . '/../main.php'),
				array(),
				file_exists($file) ? (string) filemtime($file) : SMART_VARIATIONS_IMAGES_VERSION
			);
		}

		/**
		 * Print the checkbox inside the variation panel.
		 *
		 * @param int     $loop           Panel index.
		 * @param array   $variation_data Variation data.
		 * @param WP_Post $variation      Variation post.
		 */
		public static function render($loop, $variation_data, $variation): void
		{
			if (!$variation instanceof WP_Post) {
				return;
			}

			/**
			 * Filters whether the free opt-in is printed in the variation panel.
			 *
			 * PRO replaces it with the per-context switches.
			 *
			 * @param bool $render Default true.
			 */
			if (!apply_filters('svi_sv_render_free_toggle', true)) {
				return;
			}

			$checked = self::is_enabled_for((int) $variation->ID);

			echo '<div class="svi-sv-variation-field">';
			printf(
				'<input type="hidden" name="svi_sv_visible[%1$s]" value="0" />',
				esc_attr((string) $loop)
			);
			printf(
				'<label><input type="checkbox" name="svi_sv_visible[%1$s]" value="1" %2$s /> %3$s</label>',
				esc_attr((string) $loop),
				checked($checked, true, false),
				esc_html__('Show in shop as its own product', 'smart-variations-images')
			);

			if (!SVI_SV_Settings::is_enabled()) {
				echo '<span class="description">'
					. esc_html__('Turn on "Show variations as separate products in the shop" in the SVI settings to display them.', 'smart-variations-images')
					. '</span>';
			}

			echo '</div>';
		}

		/**
		 * Persist the checkbox.
		 *
		 * WooCommerce verifies the variations nonce before this action runs on
		 * both save paths, so only the capability is checked here.
		 *
		 * @param int $variation_id Variation ID.
		 * @param int $index        Panel index.
		 */
		public static function save($variation_id, $index): void
		{
			$variation_id = absint($variation_id);

			if ($variation_id <= 0 || !current_user_can('edit_post', $variation_id)) {
				return;
			}

			if (!isset($_POST['svi_sv_visible']) || !is_array($_POST['svi_sv_visible'])) {
				return;
			}

			$raw = wp_unslash($_POST['svi_sv_visible']); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verified the variations nonce.
			$value = isset($raw[$index]) ? (string) $raw[$index] : '0';

			if ('1' === $value) {
				self::set_visibility($variation_id, self::FREE_TOKENS);
			} else {
				self::set_visibility($variation_id, '');
			}
		}

		/**
		 * Store the visibility tokens of a variation.
		 *
		 * @param int    $variation_id Variation ID.
		 * @param string $tokens       Comma separated context tokens, empty hides it.
		 */
		public static function set_visibility(int $variation_id, string $tokens): void
		{
			$variation_id = absint($variation_id);

			if ($variation_id <= 0) {
				return;
			}

			$tokens = self::clean_tokens($tokens);
			$stored = (string) get_post_meta($variation_id, SVI_SV_Query::META_VISIBLE, true);

			if ($stored === $tokens) {
				return;
			}

			if ('' === $tokens) {
				delete_post_meta($variation_id, SVI_SV_Query::META_VISIBLE);
			} else {
				update_post_meta($variation_id, SVI_SV_Query::META_VISIBLE, $tokens);
			}

			self::flush_caches($variation_id);
		}

		/**
		 * Tokens of a variation, in a predictable order.
		 *
		 * @param int $variation_id Variation ID.
		 * @return string
		 */
		public static function get_tokens(int $variation_id): string
		{
			return self::clean_tokens((string) get_post_meta($variation_id, SVI_SV_Query::META_VISIBLE, true));
		}

		/**
		 * Whether a variation is shown somewhere.
		 *
		 * @param int $variation_id Variation ID.
		 * @return bool
		 */
		public static function is_enabled_for(int $variation_id): bool
		{
			return '' !== self::get_tokens($variation_id);
		}

		/**
		 * Normalise a token list to the three known contexts.
		 *
		 * @param string $tokens Raw value.
		 * @return string
		 */
		public static function clean_tokens(string $tokens): string
		{
			$known = array(SVI_SV_Query::TOKEN_CATALOG, SVI_SV_Query::TOKEN_SEARCH, SVI_SV_Query::TOKEN_FILTER);
			$parts = array_filter(array_map('trim', explode(',', strtolower($tokens))));

			return implode(',', array_values(array_intersect($known, $parts)));
		}

		/**
		 * Drop the caches a visibility change can invalidate.
		 *
		 * @param int $variation_id Variation ID.
		 */
		public static function flush_caches(int $variation_id): void
		{
			$parent_id = (int) wp_get_post_parent_id($variation_id);

			if ($parent_id > 0 && function_exists('wc_delete_product_transients')) {
				wc_delete_product_transients($parent_id);
			}

			if (function_exists('wc_delete_product_transients')) {
				wc_delete_product_transients($variation_id);
			}

			SVI_SV_Card::flush_cache();
		}
	}
}
