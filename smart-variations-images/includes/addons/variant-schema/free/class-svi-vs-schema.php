<?php
/**
 * ProductGroup structured data for variable products.
 *
 * Google's product variants markup describes a variable product as a
 * ProductGroup whose `hasVariant` entries are Products, with `variesBy` naming
 * the attributes that distinguish them. Each variant links to the parent URL
 * that preselects it (the URL the copy-variation-link button builds) and lists
 * the images of its SVI gallery, so a colour variant carries its own pictures.
 *
 * The parent Product that WooCommerce prints is left untouched.
 *
 * @package Smart_Variations_Images\Addons\Variant_Schema
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_VS_Schema')) {
	/**
	 * Builds and prints the ProductGroup node.
	 */
	class SVI_VS_Schema
	{
		/**
		 * Attribute names that map to a schema.org property.
		 *
		 * @var array<string, string>
		 */
		const VARIES_BY = array(
			'color'    => 'https://schema.org/color',
			'colour'   => 'https://schema.org/color',
			'size'     => 'https://schema.org/size',
			'material' => 'https://schema.org/material',
			'pattern'  => 'https://schema.org/pattern',
		);

		/**
		 * Register the hooks.
		 */
		public static function init(): void
		{
			add_action('wp_footer', array(__CLASS__, 'output'), 20);
		}

		/**
		 * Name of the plugin that already prints variants, if any.
		 *
		 * @return string
		 */
		public static function conflict(): string
		{
			if (class_exists('WPSEO_WooCommerce_Structured_Data') || defined('WPSEO_WOOCOMMERCE_FILE')) {
				return 'Yoast WooCommerce SEO';
			}

			if (class_exists('RankMath') || defined('RANK_MATH_VERSION')) {
				return 'Rank Math';
			}

			return '';
		}

		/**
		 * Whether the module may print its node.
		 *
		 * @return bool
		 */
		public static function is_active(): bool
		{
			if ('' !== self::conflict()) {
				return false;
			}

			/**
			 * Filters whether SVI prints the ProductGroup node.
			 *
			 * Returning true also overrides the automatic switch-off when an
			 * SEO plugin already prints variants.
			 *
			 * @param bool $enabled Default: the setting, and no conflicting plugin.
			 */
			return (bool) apply_filters('svi_variant_schema_enabled', SVI_VS_Settings::is_enabled());
		}

		/**
		 * Print the JSON-LD node on product pages.
		 */
		public static function output(): void
		{
			if (!function_exists('is_product') || !is_product() || !self::is_active()) {
				return;
			}

			$product = wc_get_product(get_queried_object_id());

			if (!$product instanceof WC_Product || !$product->is_type('variable')) {
				return;
			}

			$group = self::product_group($product);

			if (!$group) {
				return;
			}

			printf(
				'<script type="application/ld+json">%s</script>' . "\n",
				wp_json_encode($group, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON-LD payload.
			);
		}

		/**
		 * Build the ProductGroup node for a variable product.
		 *
		 * @param WC_Product $product Variable product.
		 * @return array|null
		 */
		public static function product_group(WC_Product $product): ?array
		{
			if (!$product->is_type('variable')) {
				return null;
			}

			$product_id = (int) $product->get_id();
			$galleries  = self::parent_galleries($product_id);
			$variants   = array();
			$varies_by  = array();

			foreach ($product->get_children() as $child_id) {
				$variation = wc_get_product($child_id);

				if (!$variation instanceof WC_Product_Variation) {
					continue;
				}

				$variant = self::variant($variation, $galleries);

				if (!$variant) {
					continue;
				}

				$variants[] = $variant;

				foreach (array_keys((array) $variation->get_attributes()) as $attribute) {
					$varies_by[$attribute] = self::varies_by($attribute);
				}
			}

			if (count($variants) < 2) {
				return null;
			}

			return array(
				'@context'        => 'https://schema.org',
				'@type'           => 'ProductGroup',
				'name'            => $product->get_name(),
				'productGroupID'  => $product->get_sku() ? $product->get_sku() : (string) $product_id,
				'url'             => get_permalink($product_id),
				'variesBy'        => array_values($varies_by),
				'hasVariant'      => $variants,
			);
		}

		/**
		 * One Product inside the group.
		 *
		 * @param WC_Product_Variation $variation Variation.
		 * @param array                $galleries Parent galleries.
		 * @return array|null
		 */
		private static function variant(WC_Product_Variation $variation, array $galleries): ?array
		{
			$images = self::variation_images($variation, $galleries);

			if (!$images) {
				return null;
			}

			$price = (float) wc_get_price_to_display($variation);

			if ($price <= 0) {
				return null;
			}

			$variant = array(
				'@type' => 'Product',
				'name'  => self::variant_name($variation),
				'image' => $images,
				'url'   => $variation->get_permalink(),
				'offers' => array(
					'@type'         => 'Offer',
					'url'           => $variation->get_permalink(),
					'price'         => wc_format_decimal($price, wc_get_price_decimals()),
					'priceCurrency' => get_woocommerce_currency(),
					'availability'  => self::availability($variation),
					'itemCondition' => 'https://schema.org/NewCondition',
				),
			);

			if ($variation->get_sku()) {
				$variant['sku'] = $variation->get_sku();
			}

			$gtin = self::gtin($variation);

			if ('' !== $gtin) {
				$variant[self::gtin_key($gtin)] = $gtin;
			}

			foreach (array_keys((array) $variation->get_attributes()) as $attribute) {
				$property = self::property_key((string) $attribute);

				if ('' === $property || isset($variant[$property])) {
					continue;
				}

				$value = SVI_VS_Group_Data::attribute_label($variation, (string) $attribute);

				if ('' !== $value) {
					$variant[$property] = $value;
				}
			}

			return $variant;
		}

		/**
		 * schema.org value for an attribute name.
		 *
		 * @param string $attribute Attribute taxonomy or custom name.
		 * @return string
		 */
		public static function varies_by(string $attribute): string
		{
			$property = self::property_key($attribute);

			return '' !== $property ? self::VARIES_BY[$property] : wc_attribute_label($attribute);
		}

		/**
		 * schema.org property an attribute maps to.
		 *
		 * Attribute names are shop-specific ("hoodie-colour", "shoe-size"), so
		 * the suffix decides: the colour of a hoodie is still a colour.
		 *
		 * @param string $attribute Attribute taxonomy or custom name.
		 * @return string color, size, material, pattern or ''.
		 */
		public static function property_key(string $attribute): string
		{
			$key = strtolower(str_replace('pa_', '', $attribute));

			foreach (array('color', 'size', 'material', 'pattern') as $property) {
				if ($key === $property || str_ends_with($key, '-' . $property) || str_ends_with($key, '_' . $property)) {
					return $property;
				}
			}

			if (str_ends_with($key, '-colour') || str_ends_with($key, '_colour') || 'colour' === $key) {
				return 'color';
			}

			return '';
		}

		/**
		 * URLs of the SVI gallery of a variation.
		 *
		 * @param WC_Product_Variation $variation Variation.
		 * @param array|null           $galleries Parent galleries, read when null.
		 * @return string[]
		 */
		public static function variation_images(WC_Product_Variation $variation, ?array $galleries = null): array
		{
			if (null === $galleries) {
				$galleries = self::parent_galleries((int) $variation->get_parent_id());
			}

			$urls  = array();
			$match = SVI_VS_Group_Data::match_gallery($galleries, $variation);
			$ids   = $match ? array_values(array_filter(array_map('absint', (array) ($match['imgs'] ?? array())))) : array();

			foreach ($ids as $image_id) {
				$url = wp_get_attachment_image_url($image_id, 'full') ?: wp_get_attachment_image_url($image_id, 'large');

				if ($url) {
					$urls[] = $url;
				}
			}

			if (!$urls) {
				$fallback = (int) $variation->get_image_id();
				$url      = $fallback > 0 ? wp_get_attachment_image_url($fallback, 'full') : '';

				if ($url) {
					$urls[] = $url;
				}
			}

			/**
			 * Filters the images used for a variant, so feed plugins can reuse
			 * the SVI galleries.
			 *
			 * @param string[]             $urls      Image URLs.
			 * @param WC_Product_Variation $variation Variation.
			 */
			return array_values(array_unique((array) apply_filters('svi_variation_image_urls', $urls, $variation)));
		}

		/**
		 * Name of a variant, with its attribute values.
		 *
		 * @param WC_Product_Variation $variation Variation.
		 * @return string
		 */
		private static function variant_name(WC_Product_Variation $variation): string
		{
			if (class_exists('SVI_SV_Card')) {
				return SVI_SV_Card::variation_title($variation);
			}

			$parent = wc_get_product($variation->get_parent_id());
			$name   = $parent ? $parent->get_name() : $variation->get_name();
			$values = array();

			foreach (array_keys((array) $variation->get_attributes()) as $attribute) {
				$value = SVI_VS_Group_Data::attribute_label($variation, (string) $attribute);

				if ('' !== $value) {
					$values[] = $value;
				}
			}

			return $values ? $name . ' – ' . implode(' – ', $values) : $name;
		}

		/**
		 * schema.org availability of a variation.
		 *
		 * @param WC_Product_Variation $variation Variation.
		 * @return string
		 */
		private static function availability(WC_Product_Variation $variation): string
		{
			if ($variation->is_in_stock()) {
				return 'https://schema.org/InStock';
			}

			if ($variation->backorders_allowed()) {
				return 'https://schema.org/BackOrder';
			}

			return 'https://schema.org/OutOfStock';
		}

		/**
		 * GTIN of a variation, when WooCommerce stores one.
		 *
		 * @param WC_Product_Variation $variation Variation.
		 * @return string
		 */
		private static function gtin(WC_Product_Variation $variation): string
		{
			if (!method_exists($variation, 'get_global_unique_id')) {
				return '';
			}

			return (string) $variation->get_global_unique_id();
		}

		/**
		 * Property name for a GTIN, by length.
		 *
		 * @param string $gtin GTIN.
		 * @return string
		 */
		private static function gtin_key(string $gtin): string
		{
			switch (strlen($gtin)) {
				case 8:
					return 'gtin8';
				case 12:
					return 'gtin12';
				case 13:
					return 'gtin13';
				case 14:
					return 'gtin14';
				default:
					return 'gtin';
			}
		}

		/**
		 * Read the SVI galleries of a product.
		 *
		 * @param int $product_id Product ID.
		 * @return array
		 */
		public static function parent_galleries(int $product_id): array
		{
			$raw = get_post_meta($product_id, 'woosvi_slug', true);

			if (is_string($raw) && '' !== trim($raw)) {
				$decoded = json_decode($raw, true);
				$raw     = is_array($decoded) ? $decoded : array();
			}

			if (!is_array($raw)) {
				return array();
			}

			return class_exists('Smart_Variations_Images')
				? Smart_Variations_Images::normalize_galleries($raw)
				: array();
		}
	}
}
