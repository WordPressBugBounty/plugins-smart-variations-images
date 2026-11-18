<?php
/**
 * Import source: Variation Swatches for WooCommerce (variation-swatches-woo).
 *
 * Storage read here, verified on version 1.0.14:
 *  - Swatch type: `woocommerce_attribute_taxonomies.attribute_type`
 *    (color, image, label, select).
 *  - Term values: `cfvsw_color` (hex) and `cfvsw_image` (attachment URL).
 *  - Per-product overrides: post meta `cfvsw_product_attr_{attribute_name}`
 *    holding `['type' => ..., {term_id} => ['label', 'color', 'image']]`.
 *  - Global settings: options `cfvsw_global`, `cfvsw_shop`, `cfvsw_style`.
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Swatch_Import_Source_Variation_Swatches_Woo')) {
	/**
	 * Adapter for Variation Swatches for WooCommerce.
	 */
	class SVI_Swatch_Import_Source_Variation_Swatches_Woo extends SVI_Swatch_Import_Source
	{
		/**
		 * Attribute types that map to an SVI swatch type.
		 *
		 * @var array<string, string>
		 */
		const TYPE_MAP = [
			'color' => 'color',
			'image' => 'image',
			'label' => 'text',
		];

		/**
		 * Prefix of the per-product override meta keys.
		 */
		const PRODUCT_META_PREFIX = 'cfvsw_product_attr_';

		/**
		 * Source slug.
		 *
		 * @return string
		 */
		public function get_slug(): string
		{
			return 'variation-swatches-woo';
		}

		/**
		 * Plugin name.
		 *
		 * @return string
		 */
		public function get_label(): string
		{
			return __('Variation Swatches for WooCommerce', 'smart-variations-images');
		}

		/**
		 * Plugin files.
		 *
		 * @return string[]
		 */
		protected function get_plugin_files(): array
		{
			return [
				'variation-swatches-woo/variation-swatches-woo.php',
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
			return ['cfvsw_color', 'cfvsw_image'];
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
					"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key LIKE %s AND meta_key <> %s",
					$wpdb->esc_like(self::PRODUCT_META_PREFIX) . '%',
					self::PRODUCT_META_PREFIX . 'catalog_attr'
				)
			);
		}

		/**
		 * Settings options left behind, for the detection summary.
		 *
		 * @return string[]
		 */
		protected function get_option_evidence(): array
		{
			$options = [];

			foreach (['cfvsw_global', 'cfvsw_shop', 'cfvsw_style'] as $option) {
				if (false !== get_option($option, false)) {
					$options[] = $option;
				}
			}

			return $options;
		}

		/**
		 * Build attribute, term, override and setting items.
		 *
		 * @return array[]
		 */
		protected function build_items(): array
		{
			$items = [];
			$taxonomies = wc_get_attribute_taxonomies();

			if (!is_array($taxonomies)) {
				$taxonomies = [];
			}

			foreach ($taxonomies as $attribute) {
				$type = isset($attribute->attribute_type) ? (string) $attribute->attribute_type : '';

				if (!isset(self::TYPE_MAP[$type])) {
					continue;
				}

				$taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);

				if (!$this->claim_taxonomy($taxonomy)) {
					continue;
				}

				$items[] = [
					'kind' => 'attribute',
					'part' => 'attributes',
					'title' => sprintf('%s (%s)', $attribute->attribute_label, $taxonomy),
					'payload' => [
						'attribute_id' => (int) $attribute->attribute_id,
						'type' => self::TYPE_MAP[$type],
					],
				];

				if (!taxonomy_exists($taxonomy)) {
					continue;
				}

				$terms = get_terms([
					'taxonomy' => $taxonomy,
					'hide_empty' => false,
					'fields' => 'ids',
				]);

				if (is_wp_error($terms) || empty($terms)) {
					continue;
				}

				SVI_Swatch_Values::prime($terms);

				foreach ($terms as $term_id) {
					$term_id = (int) $term_id;
					$term = get_term($term_id, $taxonomy);

					if (!$term instanceof WP_Term) {
						continue;
					}

					$item = $this->build_term_item($term_id, $term, self::TYPE_MAP[$type]);

					if (null !== $item) {
						$items[] = $item;
					}
				}
			}

			foreach ($this->build_override_items() as $item) {
				$items[] = $item;
			}

			foreach ($this->build_setting_items() as $item) {
				$items[] = $item;
			}

			return $items;
		}

		/**
		 * One term item, or null when the term stores nothing.
		 *
		 * @param int     $term_id Term ID.
		 * @param WP_Term $term    Term.
		 * @param string  $type    SVI swatch type of the attribute.
		 * @return array|null
		 */
		private function build_term_item(int $term_id, WP_Term $term, string $type): ?array
		{
			$color = (string) get_term_meta($term_id, 'cfvsw_color', true);
			$image = (string) get_term_meta($term_id, 'cfvsw_image', true);

			$colors = '' !== trim($color) ? [$color] : [];
			$image_id = 0;
			$warning = '';

			if ('' !== trim($image)) {
				$image_id = (int) attachment_url_to_postid($image);

				if (!$image_id && 'image' === $type) {
					return [
						'kind' => 'term',
						'part' => 'terms',
						'title' => sprintf('%s → %s', $term->taxonomy, $term->name),
						'unsupported' => true,
						'reason' => __('The swatch image URL is not in the media library.', 'smart-variations-images'),
					];
				}

				if (!$image_id) {
					$warning = __('The swatch image URL is not in the media library; imported colours only.', 'smart-variations-images');
				}
			}

			if (empty($colors) && 0 === $image_id) {
				return null;
			}

			$item = [
				'kind' => 'term',
				'part' => 'terms',
				'title' => sprintf('%s → %s', $term->taxonomy, $term->name),
				'payload' => [
					'term_id' => $term_id,
					'colors' => $colors,
					'image' => $image_id,
					'label' => '',
				],
			];

			if ('' !== $warning) {
				$item['warning'] = $warning;
			}

			return $item;
		}

		/**
		 * Per-product overrides.
		 *
		 * @return array[]
		 */
		private function build_override_items(): array
		{
			global $wpdb;

			$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_key LIKE %s AND meta_key <> %s ORDER BY post_id",
					$wpdb->esc_like(self::PRODUCT_META_PREFIX) . '%',
					self::PRODUCT_META_PREFIX . 'catalog_attr'
				)
			);

			$items = [];

			foreach ((array) $rows as $row) {
				$product_id = (int) $row->post_id;
				$attribute = substr((string) $row->meta_key, strlen(self::PRODUCT_META_PREFIX));

				if ('' === $attribute || !taxonomy_exists($attribute)) {
					$items[] = [
						'kind' => 'override',
						'part' => 'overrides',
						'title' => sprintf('#%d → %s', $product_id, $attribute),
						'unsupported' => true,
						'reason' => __('SVI per-product overrides need a global (taxonomy) attribute.', 'smart-variations-images'),
					];
					continue;
				}

				$value = maybe_unserialize($row->meta_value);

				if (!is_array($value)) {
					continue;
				}

				$type = isset($value['type']) ? (string) $value['type'] : '';
				$terms = [];
				$warnings = [];

				foreach ($value as $key => $entry) {
					if ('type' === $key || !is_array($entry)) {
						continue;
					}

					$term = get_term((int) $key, $attribute);

					if (!$term instanceof WP_Term) {
						continue;
					}

					$image_id = 0;
					$image_value = isset($entry['image']) ? (string) $entry['image'] : '';

					if ('' !== trim($image_value)) {
						$image_id = ctype_digit($image_value) ? absint($image_value) : (int) attachment_url_to_postid($image_value);

						if (!$image_id) {
							/* translators: %s: term name. */
							$warnings[] = sprintf(__('image for "%s" is not in the media library', 'smart-variations-images'), $term->name);
						}
					}

					$terms[$term->slug] = [
						'color' => isset($entry['color']) ? (string) $entry['color'] : '',
						'image' => $image_id,
						'label' => isset($entry['label']) ? (string) $entry['label'] : '',
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

			return $items;
		}

		/**
		 * Map the global settings this source saved.
		 *
		 * @return array[]
		 */
		private function build_setting_items(): array
		{
			$global = get_option('cfvsw_global', []);
			$global = is_object($global) ? get_object_vars($global) : (array) $global;
			$shop = get_option('cfvsw_shop', []);
			$shop = is_object($shop) ? get_object_vars($shop) : (array) $shop;

			$items = [];

			if (array_key_exists('tooltip', $global)) {
				$items[] = [
					'kind' => 'setting',
					'part' => 'settings',
					'title' => __('Enable tooltip', 'smart-variations-images'),
					'payload' => [
						'key' => 'swatches_swatches_tooltip_enable_tooltips',
						'value' => $global['tooltip'] ? '1' : '0',
					],
				];
			}

			if (array_key_exists('auto_convert', $global)) {
				$items[] = [
					'kind' => 'setting',
					'part' => 'settings',
					'title' => __('Convert dropdowns to buttons', 'smart-variations-images'),
					'payload' => [
						'key' => 'swatches_swatches_conversion_swatch_type_default',
						'value' => $global['auto_convert'] ? 'text' : 'none',
					],
				];
			}

			if (array_key_exists('disable_attr_type', $global)) {
				$map = [
					'blurCross' => 'blur',
					'blur' => 'dim',
					'hide' => 'hide',
					'cross' => 'cross',
				];

				$value = (string) $global['disable_attr_type'];

				if (isset($map[$value])) {
					$items[] = [
						'kind' => 'setting',
						'part' => 'settings',
						'title' => __('Out-of-stock behaviour', 'smart-variations-images'),
						'payload' => [
							'key' => 'swatches_swatches_product_page_oos_behavior',
							'value' => $map[$value],
						],
					];
				}
			}

			if (array_key_exists('min_width', $global)) {
				$items[] = [
					'kind' => 'setting',
					'part' => 'settings',
					'title' => __('Swatch width', 'smart-variations-images'),
					'payload' => [
						'key' => 'swatches_swatches_styling_swatch_size_w',
						'value' => absint($global['min_width']),
					],
				];
			}

			if (array_key_exists('min_height', $global)) {
				$items[] = [
					'kind' => 'setting',
					'part' => 'settings',
					'title' => __('Swatch height', 'smart-variations-images'),
					'payload' => [
						'key' => 'swatches_swatches_styling_swatch_size_h',
						'value' => absint($global['min_height']),
					],
				];
			}

			if (array_key_exists('enable_swatches', $shop)) {
				$items[] = [
					'kind' => 'setting',
					'part' => 'settings',
					'title' => __('Show swatches in the shop', 'smart-variations-images'),
					'payload' => [
						'key' => 'swatches_swatches_catalog_show_in_catalog',
						'value' => $shop['enable_swatches'] ? '1' : '0',
					],
				];
			}

			return $items;
		}
	}
}
