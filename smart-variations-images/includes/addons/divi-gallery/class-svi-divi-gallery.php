<?php
/**
 * Which gallery module a Divi product page uses.
 *
 * Divi renders the product gallery through its own WooCommerce Images module
 * and SVI replaces that module output, so SVI has to stay out of its regular
 * WooCommerce hook render to avoid a second gallery above the breadcrumb.
 *
 * Divi 4 stores the module as a shortcode (`et_pb_wc_images`); Divi 5 stores it
 * as a block (`divi/woocommerce-product-images`). Both are checked, and the two
 * formats are told apart because only a block module renders the WooCommerce
 * product-image template, which is the one place SVI may render there.
 *
 * @package Smart_Variations_Images\Addons\Divi_Gallery
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Divi_Gallery')) {
	/**
	 * Answers two questions for the frontend controller, and owns the logic.
	 */
	class SVI_Divi_Gallery
	{
		/**
		 * Divi 4: rendered through `et_module_shortcode_output`.
		 */
		const FORMAT_SHORTCODE = 'shortcode';

		/**
		 * Divi 5: renders `single-product/product-image.php` through `wc_get_template`.
		 */
		const FORMAT_BLOCK = 'block';

		/**
		 * Register the hooks.
		 */
		public static function init(): void
		{
			add_filter('svi_skip_gallery_render', [__CLASS__, 'skip_render'], 10, 4);
			add_filter('svi_running_divi_gallery', [__CLASS__, 'running'], 10, 2);
		}

		/**
		 * Whether the frontend controller must not render the gallery itself.
		 *
		 * @param bool   $skip                   Decision so far.
		 * @param mixed  $product                Product being rendered.
		 * @param bool   $from_template_override Whether the WooCommerce template override is capturing the output.
		 * @param string $template               Active theme folder.
		 * @return bool
		 */
		public static function skip_render($skip, $product, $from_template_override = false, $template = ''): bool
		{
			if ($skip) {
				return true;
			}

			if ('Divi' !== $template || wp_doing_ajax()) {
				return false;
			}

			if (!$product instanceof WC_Product) {
				return false;
			}

			$format = self::marker($product);

			if (null === $format) {
				return false;
			}

			// A Divi 5 block module renders the product-image template, so the
			// override path is the only place SVI can render there.
			return !($from_template_override && self::FORMAT_BLOCK === $format);
		}

		/**
		 * Whether this product page runs a Divi gallery module.
		 *
		 * @param bool  $running Decision so far.
		 * @param mixed $product Product being rendered.
		 * @return bool
		 */
		public static function running($running, $product = null): bool
		{
			if ($running || !$product instanceof WC_Product) {
				return (bool) $running;
			}

			return null !== self::marker($product);
		}

		/**
		 * Which Divi gallery module the product page uses, if any.
		 *
		 * @param WC_Product $product Product being rendered.
		 * @return string|null shortcode|block|null
		 */
		private static function marker(WC_Product $product): ?string
		{
			$product_post = get_post($product->get_id());

			if ($product_post instanceof WP_Post) {
				$match = self::match($product_post->post_content);

				if (null !== $match) {
					return $match;
				}
			}

			if (class_exists('ET_Theme_Builder_Request')) {
				$tb_layouts = et_theme_builder_get_template_layouts(ET_Theme_Builder_Request::from_post($product->get_id()));

				if (!empty($tb_layouts) && $tb_layouts[ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE]['override']) {
					$template_content = get_the_content(null, false, $tb_layouts[ET_THEME_BUILDER_BODY_LAYOUT_POST_TYPE]['id']);

					return self::match($template_content);
				}
			}

			return null;
		}

		/**
		 * Classify a content string by the Divi gallery module it contains.
		 *
		 * @param mixed $content Content to search.
		 * @return string|null shortcode|block|null
		 */
		private static function match($content): ?string
		{
			if (self::contains($content, self::markers(self::FORMAT_SHORTCODE))) {
				return self::FORMAT_SHORTCODE;
			}

			if (self::contains($content, self::markers(self::FORMAT_BLOCK))) {
				return self::FORMAT_BLOCK;
			}

			return null;
		}

		/**
		 * Markers that identify a Divi gallery module in stored content.
		 *
		 * @param string $format shortcode|block|'' for both.
		 * @return string[]
		 */
		private static function markers(string $format = ''): array
		{
			$shortcode = array('et_pb_wc_images', 'et_pb_wc_gallery');
			$block     = array('divi/woocommerce-product-images', 'divi/woocommerce-product-gallery');

			if (self::FORMAT_SHORTCODE === $format) {
				return $shortcode;
			}

			if (self::FORMAT_BLOCK === $format) {
				return $block;
			}

			return array_merge($shortcode, $block);
		}

		/**
		 * Whether a content string contains any of the given markers.
		 *
		 * @param mixed    $content Content to search.
		 * @param string[] $markers Markers to look for.
		 * @return bool
		 */
		private static function contains($content, array $markers): bool
		{
			$content = (string) $content;

			foreach ($markers as $marker) {
				if ('' !== $marker && false !== strpos($content, $marker)) {
					return true;
				}
			}

			return false;
		}
	}
}
