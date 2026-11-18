<?php
/**
 * Import source: Product Variations Swatches for WooCommerce
 * (product-variations-swatches-for-woocommerce, VillaTheme).
 *
 * Storage read here, verified on the wordpress.org build:
 *  - Swatch type: `woocommerce_attribute_taxonomies.attribute_type`
 *    (color, image, variation_img, button, radio, viwpvs_default).
 *  - Term values: term meta `vi_wpvs_terms_params` holding
 *    `['color' => [hex, ...], 'color_separator' => '1', 'img_id' => id]`.
 *  - Per-product overrides: post meta
 *    `_vi_woo_product_variation_swatches_product_attribute` holding
 *    `['attribute_type'], ['attribute_colors'][attribute][term_id],
 *      ['attribute_img_ids'][attribute][term_id]`.
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Swatch_Import_Source_Product_Variations_Swatches')) {
	/**
	 * Adapter for VillaTheme's Product Variations Swatches.
	 */
	class SVI_Swatch_Import_Source_Product_Variations_Swatches extends SVI_Swatch_Import_Source
	{
		/**
		 * Source attribute type => SVI swatch type.
		 *
		 * @var array<string, string>
		 */
		const TYPE_MAP = [
			'color' => 'color',
			'image' => 'image',
			'variation_img' => 'image',
			'button' => 'text',
			'radio' => 'radio',
		];

		/**
		 * Per-product override meta key.
		 */
		const PRODUCT_META = '_vi_woo_product_variation_swatches_product_attribute';

		/**
		 * Source slug.
		 *
		 * @return string
		 */
		public function get_slug(): string
		{
			return 'product-variations-swatches-for-woocommerce';
		}

		/**
		 * Plugin name.
		 *
		 * @return string
		 */
		public function get_label(): string
		{
			return __('Product Variations Swatches for WooCommerce', 'smart-variations-images');
		}

		/**
		 * Plugin files.
		 *
		 * @return string[]
		 */
		protected function get_plugin_files(): array
		{
			return [
				'product-variations-swatches-for-woocommerce/product-variations-swatches-for-woocommerce.php',
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
		 * Term meta keys this source owns.
		 *
		 * @return string[]
		 */
		public function get_term_meta_keys(): array
		{
			return ['vi_wpvs_terms_params'];
		}

		/**
		 * Per-product overrides left by this source.
		 *
		 * @return int
		 */
		protected function count_products(): int
		{
			global $wpdb;

			return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = %s",
					self::PRODUCT_META
				)
			);
		}

		/**
		 * Build attribute, term and override items.
		 *
		 * @return array[]
		 */
		protected function build_items(): array
		{
			$items = $this->build_attribute_term_items(function ($term_id, $type) {
				$params = get_term_meta($term_id, 'vi_wpvs_terms_params', true);

				if (!is_array($params)) {
					return null;
				}

				$colors = array_values(array_filter(array_map('strval', (array) ($params['color'] ?? []))));
				$image_id = absint($params['img_id'] ?? 0);

				if (empty($colors) && 0 === $image_id) {
					return null;
				}

				return [
					'colors' => $colors,
					'image' => $image_id,
					'label' => '',
				];
			});

			foreach ($this->build_overrides() as $item) {
				$items[] = $item;
			}

			return $items;
		}

		/**
		 * Per-product overrides.
		 *
		 * @return array[]
		 */
		private function build_overrides(): array
		{
			$posts = get_posts([
				'post_type' => ['product'],
				'post_status' => 'any',
				'posts_per_page' => -1,
				'fields' => 'ids',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One-off import scan.
				'meta_key' => self::PRODUCT_META,
			]);

			$items = [];

			foreach ($posts as $product_id) {
				$product_id = (int) $product_id;
				$stored = get_post_meta($product_id, self::PRODUCT_META, true);

				if (is_string($stored)) {
					$stored = json_decode($stored, true);
				}

				if (!is_array($stored)) {
					continue;
				}

				$types = (array) ($stored['attribute_type'] ?? []);
				$colors = (array) ($stored['attribute_colors'] ?? []);
				$images = (array) ($stored['attribute_img_ids'] ?? []);

				foreach (array_unique(array_merge(array_keys($colors), array_keys($images), array_keys($types))) as $attribute) {
					$attribute = (string) $attribute;

					if (!taxonomy_exists($attribute)) {
						$items[] = [
							'kind' => 'override',
							'part' => 'overrides',
							'title' => sprintf('#%d → %s', $product_id, $attribute),
							'unsupported' => true,
							'reason' => __('SVI per-product overrides need a global (taxonomy) attribute.', 'smart-variations-images'),
						];
						continue;
					}

					$terms = [];

					foreach ((array) ($colors[$attribute] ?? []) as $term_id => $values) {
						$term = get_term((int) $term_id, $attribute);

						if (!$term instanceof WP_Term) {
							continue;
						}

						$color_list = (array) $values;

						$terms[$term->slug] = [
							'color' => isset($color_list[0]) ? (string) $color_list[0] : '',
							'image' => absint(((array) ($images[$attribute] ?? []))[$term_id] ?? 0),
							'label' => '',
						];
					}

					foreach ((array) ($images[$attribute] ?? []) as $term_id => $image_id) {
						$term = get_term((int) $term_id, $attribute);

						if (!$term instanceof WP_Term) {
							continue;
						}

						if (!isset($terms[$term->slug])) {
							$terms[$term->slug] = ['color' => '', 'image' => 0, 'label' => ''];
						}

						$terms[$term->slug]['image'] = absint($image_id);
					}

					$type = isset($types[$attribute]) ? (string) $types[$attribute] : '';

					if (empty($terms) && '' === $type) {
						continue;
					}

					$items[] = [
						'kind' => 'override',
						'part' => 'overrides',
						'title' => sprintf('#%d → %s', $product_id, wc_attribute_label($attribute)),
						'payload' => [
							'product_id' => $product_id,
							'attribute' => $attribute,
							'type' => isset(self::TYPE_MAP[$type]) ? self::TYPE_MAP[$type] : '',
							'terms' => $terms,
						],
					];
				}
			}

			return $items;
		}
	}
}
