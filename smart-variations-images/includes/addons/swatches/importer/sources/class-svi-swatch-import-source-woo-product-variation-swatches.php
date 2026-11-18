<?php
/**
 * Import source: Woo Product Variation Swatches (woo-product-variation-swatches, RadiusTheme).
 *
 * Storage read here, verified on the wordpress.org build:
 *  - Swatch type: `woocommerce_attribute_taxonomies.attribute_type`
 *    (color, image, button, radio).
 *  - Term values: `product_attribute_color` (hex), `product_attribute_image`
 *    (attachment ID), plus `is_dual_color` + `secondary_color`.
 *  - Per-product overrides: post meta `_rtwpvs` holding
 *    `[attribute => ['type' => …, 'data' => [term_slug => ['color','secondary_color','is_dual_color','image',…]]]]`.
 *
 * Note: the term meta keys are identical to Woo Variation Swatches, so in a
 * database that once held both plugins only the values tell them apart.
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Swatch_Import_Source_Woo_Product_Variation_Swatches')) {
	/**
	 * Adapter for RadiusTheme's Woo Product Variation Swatches.
	 */
	class SVI_Swatch_Import_Source_Woo_Product_Variation_Swatches extends SVI_Swatch_Import_Source
	{
		/**
		 * Source attribute type => SVI swatch type.
		 *
		 * @var array<string, string>
		 */
		const TYPE_MAP = [
			'color' => 'color',
			'image' => 'image',
			'button' => 'text',
			'radio' => 'radio',
		];

		/**
		 * Per-product override meta key.
		 */
		const PRODUCT_META = '_rtwpvs';

		/**
		 * Source slug.
		 *
		 * @return string
		 */
		public function get_slug(): string
		{
			return 'woo-product-variation-swatches';
		}

		/**
		 * Plugin name.
		 *
		 * @return string
		 */
		public function get_label(): string
		{
			return __('Woo Product Variation Swatches', 'smart-variations-images');
		}

		/**
		 * Plugin files.
		 *
		 * @return string[]
		 */
		protected function get_plugin_files(): array
		{
			return [
				'woo-product-variation-swatches/woo-product-variation-swatches.php',
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
			return ['product_attribute_color', 'product_attribute_image'];
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
				$color = (string) get_term_meta($term_id, 'product_attribute_color', true);
				$image_id = absint(get_term_meta($term_id, 'product_attribute_image', true));
				$colors = '' !== trim($color) ? [$color] : [];

				if ('yes' === (string) get_term_meta($term_id, 'is_dual_color', true)) {
					$secondary = (string) get_term_meta($term_id, 'secondary_color', true);

					if ('' !== trim($secondary)) {
						$colors[] = $secondary;
					}
				}

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

				if (!is_array($stored)) {
					continue;
				}

				foreach ($stored as $attribute => $data) {
					if (!is_string($attribute) || !is_array($data)) {
						continue;
					}

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

					$type = isset($data['type']) ? (string) $data['type'] : '';
					$terms = [];
					$warnings = [];

					foreach ((array) ($data['data'] ?? []) as $slug => $values) {
						if (!is_array($values)) {
							continue;
						}

						$term = get_term_by('slug', (string) $slug, $attribute);

						if (!$term instanceof WP_Term) {
							continue;
						}

						$image_id = $this->resolve_attachment($values['image'] ?? '');

						if (!empty($values['image']) && 0 === $image_id) {
							/* translators: %s: term name. */
							$warnings[] = sprintf(__('image for "%s" is not in the media library', 'smart-variations-images'), $term->name);
						}

						$colors = [];

						if (!empty($values['color'])) {
							$colors[] = (string) $values['color'];
						}

						if ('yes' === (string) ($values['is_dual_color'] ?? '') && !empty($values['secondary_color'])) {
							$colors[] = (string) $values['secondary_color'];
						}

						$terms[$term->slug] = [
							'color' => $colors[0] ?? '',
							'color2' => $colors[1] ?? '',
							'image' => $image_id,
							'label' => '',
						];
					}

					if (empty($terms) && '' === $type) {
						continue;
					}

					$item = [
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

					if (!empty($warnings)) {
						/* translators: %s: list of skipped images. */
						$item['warning'] = sprintf(__('Not migrated: %s.', 'smart-variations-images'), implode(', ', $warnings));
					}

					$items[] = $item;
				}
			}

			return $items;
		}
	}
}
