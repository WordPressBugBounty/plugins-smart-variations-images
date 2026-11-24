<?php
/**
 * Catalog swatches inside block themes.
 *
 * Classic themes print the shop card swatches from the WooCommerce loop hook.
 * Block themes render the loop through the Product Collection block instead,
 * which never fires that hook, so the swatches were missing on Twenty
 * Twenty-Four, Twenty Twenty-Five and every other block theme.
 *
 * The card markup is built by SVI_Swatches_Frontend::catalog_swatches_html(),
 * the same function the classic loop uses, so both paths stay identical.
 *
 * @package Smart_Variations_Images\Addons\Swatches
 * @since   5.3.15
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Swatches_Blocks')) {
	/**
	 * Appends the card swatches to the Product Collection items.
	 */
	final class SVI_Swatches_Blocks
	{
		/**
		 * Block that holds one item per product.
		 */
		const PRODUCT_TEMPLATE = 'woocommerce/product-template';

		/**
		 * Render filter state, so a nested list is only touched once.
		 *
		 * @var bool
		 */
		private static bool $running = false;

		/**
		 * Hook the renderer.
		 */
		public static function init(): void
		{
			add_filter('render_block', array(__CLASS__, 'append_to_product_template'), 20, 2);
		}

		/**
		 * Add the swatches to every item of a Product Collection list.
		 *
		 * @param string $html  Rendered block.
		 * @param array  $block Block data.
		 * @return string
		 */
		public static function append_to_product_template($html, $block)
		{
			if (
				self::$running
				|| !is_string($html)
				|| '' === $html
				|| !is_array($block)
				|| self::PRODUCT_TEMPLATE !== ($block['blockName'] ?? '')
			) {
				return $html;
			}

			if (!function_exists('svi_get_option') || !svi_get_option('show_in_catalog', false)) {
				return $html;
			}

			if (!preg_match_all('#<li\b[^>]*data-wp-key="product-item-(\d+)"[^>]*>.*?</li>#s', $html, $items, PREG_SET_ORDER)) {
				return $html;
			}

			self::$running = true;

			foreach ($items as $item) {
				// WooCommerce can hand the same list through render_block more
				// than once (initial render and interactivity pass); never stack
				// two swatch blocks on one card.
				if (false !== strpos($item[0], 'svi-catalog-swatches')) {
					continue;
				}

				$product_id = (int) $item[1];
				$product    = $product_id > 0 ? wc_get_product($product_id) : false;

				if (!$product instanceof WC_Product) {
					continue;
				}

				$swatches = SVI_Swatches_Frontend::catalog_swatches_html($product);

				if ('' === $swatches) {
					continue;
				}

				$replacement = substr($item[0], 0, -strlen('</li>')) . $swatches . '</li>';

				$html = str_replace($item[0], $replacement, $html);
			}

			self::$running = false;

			return $html;
		}
	}
}
