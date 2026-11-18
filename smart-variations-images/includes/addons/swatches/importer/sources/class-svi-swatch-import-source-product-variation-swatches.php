<?php
/**
 * Import source: Product Variation Swatches for WooCommerce
 * (product-variation-swatches-for-woocommerce, ThemeHunk).
 *
 * Storage read here, verified on the wordpress.org build:
 *  - Swatch type: `woocommerce_attribute_taxonomies.attribute_type`
 *    (color, image, label, radio).
 *  - Term values: one meta key per attribute taxonomy, `product_{taxonomy}`,
 *    holding the colour hex, the attachment ID or the label text.
 *  - Per-product settings live in `th_custom_attribute_settings`, which only
 *    covers custom (non-taxonomy) attributes, so they are reported as not
 *    migrated.
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Swatch_Import_Source_Product_Variation_Swatches')) {
	/**
	 * Adapter for ThemeHunk's Product Variation Swatches.
	 */
	class SVI_Swatch_Import_Source_Product_Variation_Swatches extends SVI_Swatch_Import_Source
	{
		/**
		 * Source attribute type => SVI swatch type.
		 *
		 * @var array<string, string>
		 */
		const TYPE_MAP = [
			'color' => 'color',
			'image' => 'image',
			'label' => 'text',
			'radio' => 'radio',
		];

		/**
		 * Prefix of the per-term meta key.
		 */
		const TERM_META_PREFIX = 'product_';

		/**
		 * Source slug.
		 *
		 * @return string
		 */
		public function get_slug(): string
		{
			return 'product-variation-swatches-for-woocommerce';
		}

		/**
		 * Plugin name.
		 *
		 * @return string
		 */
		public function get_label(): string
		{
			return __('Product Variation Swatches for WooCommerce (ThemeHunk)', 'smart-variations-images');
		}

		/**
		 * Plugin files.
		 *
		 * @return string[]
		 */
		protected function get_plugin_files(): array
		{
			return [
				'product-variation-swatches-for-woocommerce/product-variation-swatches-for-woocommerce.php',
			];
		}

		/**
		 * Source attribute type => SVI swatch type.
		 *
		 * @return array<string, string>
		 */
		public function get_attribute_type_map(): array
		{
			return self::TYPE_MAP;
		}

		/**
		 * Term meta keys this source owns (one per attribute taxonomy).
		 *
		 * @return string[]
		 */
		public function get_term_meta_keys(): array
		{
			$keys = [];

			foreach ((array) wc_get_attribute_taxonomies() as $attribute) {
				$keys[] = self::TERM_META_PREFIX . wc_attribute_taxonomy_name($attribute->attribute_name);
			}

			return $keys;
		}

		/**
		 * Build attribute and term items.
		 *
		 * @return array[]
		 */
		protected function build_items(): array
		{
			$items = $this->build_attribute_term_items(function ($term_id, $type) {
				$term = get_term($term_id);

				if (!$term instanceof WP_Term) {
					return null;
				}

				$value = get_term_meta($term_id, self::TERM_META_PREFIX . $term->taxonomy, true);

				if (!is_scalar($value) || '' === trim((string) $value)) {
					return null;
				}

				$value = (string) $value;

				if ('image' === $type) {
					$image_id = $this->resolve_attachment($value);

					if (!$image_id) {
						return [
							'unsupported' => true,
							'reason' => __('The swatch image is not in the media library.', 'smart-variations-images'),
						];
					}

					return ['colors' => [], 'image' => $image_id, 'label' => ''];
				}

				if ('color' === $type) {
					return ['colors' => [$value], 'image' => 0, 'label' => ''];
				}

				return ['colors' => [], 'image' => 0, 'label' => $value];
			});

			// Custom-attribute settings are reported so the preview is honest.
			global $wpdb;

			$custom = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = %s",
					'th_custom_attribute_settings'
				)
			);

			if ($custom > 0) {
				$items[] = [
					'kind' => 'override',
					'part' => 'overrides',
					'title' => __('Custom attribute settings', 'smart-variations-images'),
					'unsupported' => true,
					/* translators: %d: number of products. */
					'reason' => sprintf(_n('%d product only configures non-taxonomy attributes, which SVI cannot override.', '%d products only configure non-taxonomy attributes, which SVI cannot override.', $custom, 'smart-variations-images'), $custom),
				];
			}

			return $items;
		}
	}
}
