<?php
/**
 * Import source: WCBoost Variation Swatches (wcboost-variation-swatches).
 *
 * Storage read here, verified on version 1.1.5:
 *  - Swatch type: `woocommerce_attribute_taxonomies.attribute_type`
 *    (color, image, label, button, select).
 *  - Term values: `swatches_color` (hex), `swatches_image` (attachment ID),
 *    `swatches_label` (text).
 *  - Per-product overrides: post meta `wcboost_variation_swatches` holding
 *    `[attribute => ['type', 'shape', 'size', 'custom_size', 'swatches' => [term_id => ['color','image','label']]]]`.
 *  - Global settings: individual options `wcboost_variation_swatches_{name}`.
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Swatch_Import_Source_WCBoost_Variation_Swatches')) {
	/**
	 * Adapter for WCBoost Variation Swatches.
	 */
	class SVI_Swatch_Import_Source_WCBoost_Variation_Swatches extends SVI_Swatch_Import_Source
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
			'button' => 'text',
		];

		/**
		 * Prefix of the per-product override meta key.
		 */
		const PRODUCT_META = 'wcboost_variation_swatches';

		/**
		 * Prefix of the global settings options.
		 */
		const SETTINGS_PREFIX = 'wcboost_variation_swatches_';

		/**
		 * Source slug.
		 *
		 * @return string
		 */
		public function get_slug(): string
		{
			return 'wcboost-variation-swatches';
		}

		/**
		 * Plugin name.
		 *
		 * @return string
		 */
		public function get_label(): string
		{
			return __('WCBoost Variation Swatches', 'smart-variations-images');
		}

		/**
		 * Plugin files.
		 *
		 * @return string[]
		 */
		protected function get_plugin_files(): array
		{
			return [
				'wcboost-variation-swatches/wcboost-variation-swatches.php',
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
			return ['swatches_color', 'swatches_image', 'swatches_label'];
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
		 * Settings options left behind, for the detection summary.
		 *
		 * @return string[]
		 */
		protected function get_option_evidence(): array
		{
			global $wpdb;

			$count = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
					$wpdb->esc_like(self::SETTINGS_PREFIX) . '%'
				)
			);

			return $count > 0 ? [self::SETTINGS_PREFIX . '*'] : [];
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

					$color = (string) get_term_meta($term_id, 'swatches_color', true);
					$image = (string) get_term_meta($term_id, 'swatches_image', true);
					$label = (string) get_term_meta($term_id, 'swatches_label', true);

					$image_id = $this->resolve_image($image);

					if ('' === trim($color) && 0 === $image_id && '' === trim($label)) {
						continue;
					}

					$item = [
						'kind' => 'term',
						'part' => 'terms',
						'title' => sprintf('%s → %s', $taxonomy, $term->name),
						'payload' => [
							'term_id' => $term_id,
							'colors' => '' !== trim($color) ? [$color] : [],
							'image' => $image_id,
							'label' => $label,
						],
					];

					if ('' !== trim($image) && 0 === $image_id) {
						$item['warning'] = __('The swatch image is not in the media library.', 'smart-variations-images');
					}

					$items[] = $item;
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
		 * Resolve a stored image value to an attachment ID.
		 *
		 * @param string $value Raw value.
		 * @return int
		 */
		private function resolve_image(string $value): int
		{
			$value = trim($value);

			if ('' === $value) {
				return 0;
			}

			return ctype_digit($value) ? absint($value) : (int) attachment_url_to_postid($value);
		}

		/**
		 * Per-product overrides.
		 *
		 * @return array[]
		 */
		private function build_override_items(): array
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

					foreach ((array) ($data['swatches'] ?? []) as $term_id => $values) {
						$term = get_term((int) $term_id, $attribute);

						if (!$term instanceof WP_Term || !is_array($values)) {
							continue;
						}

						$image_id = $this->resolve_image((string) ($values['image'] ?? ''));

						if ('' !== trim((string) ($values['image'] ?? '')) && 0 === $image_id) {
							/* translators: %s: term name. */
							$warnings[] = sprintf(__('image for "%s" is not in the media library', 'smart-variations-images'), $term->name);
						}

						$terms[$term->slug] = [
							'color' => (string) ($values['color'] ?? ''),
							'image' => $image_id,
							'label' => (string) ($values['label'] ?? ''),
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

		/**
		 * Map the global settings this source saved.
		 *
		 * @return array[]
		 */
		private function build_setting_items(): array
		{
			$items = [];

			$shape = get_option(self::SETTINGS_PREFIX . 'shape', null);

			if (null !== $shape) {
				$map = ['round' => 'round', 'rounded' => 'rounded', 'square' => 'square'];

				if (isset($map[(string) $shape])) {
					$items[] = [
						'kind' => 'setting',
						'part' => 'settings',
						'title' => __('Swatch shape', 'smart-variations-images'),
						'payload' => [
							'key' => 'swatches_swatches_general_swatch_shape',
							'value' => $map[(string) $shape],
						],
					];
				}
			}

			$size = get_option(self::SETTINGS_PREFIX . 'size', null);

			if (is_array($size)) {
				foreach (['width' => 'swatches_swatches_styling_swatch_size_w', 'height' => 'swatches_swatches_styling_swatch_size_h'] as $field => $key) {
					if (!empty($size[$field])) {
						$items[] = [
							'kind' => 'setting',
							'part' => 'settings',
							'title' => 'width' === $field ? __('Swatch width', 'smart-variations-images') : __('Swatch height', 'smart-variations-images'),
							'payload' => [
								'key' => $key,
								'value' => absint($size[$field]),
							],
						];
					}
				}
			}

			$tooltip = get_option(self::SETTINGS_PREFIX . 'tooltip', null);

			if (null !== $tooltip) {
				$items[] = [
					'kind' => 'setting',
					'part' => 'settings',
					'title' => __('Enable tooltip', 'smart-variations-images'),
					'payload' => [
						'key' => 'swatches_swatches_tooltip_enable_tooltips',
						'value' => in_array((string) $tooltip, ['yes', '1', 'true'], true) ? '1' : '0',
					],
				];
			}

			$auto_button = get_option(self::SETTINGS_PREFIX . 'auto_button', null);

			if (null !== $auto_button) {
				$items[] = [
					'kind' => 'setting',
					'part' => 'settings',
					'title' => __('Convert dropdowns to buttons', 'smart-variations-images'),
					'payload' => [
						'key' => 'swatches_swatches_conversion_swatch_type_default',
						'value' => in_array((string) $auto_button, ['yes', '1', 'true'], true) ? 'text' : 'none',
					],
				];
			}

			$invalid = get_option(self::SETTINGS_PREFIX . 'invalid_display', null);

			if (null !== $invalid) {
				$map = [
					'hide' => 'hide',
					'blur' => 'blur',
					'cross' => 'cross',
					'line' => 'cross',
					'blur_cross' => 'blur',
					'blur_line' => 'blur',
					'blur_slash' => 'blur',
				];

				if (isset($map[(string) $invalid])) {
					$items[] = [
						'kind' => 'setting',
						'part' => 'settings',
						'title' => __('Out-of-stock behaviour', 'smart-variations-images'),
						'payload' => [
							'key' => 'swatches_swatches_product_page_oos_behavior',
							'value' => $map[(string) $invalid],
						],
					];
				}
			}

			$selected_label = get_option(self::SETTINGS_PREFIX . 'show_selected_label', null);

			if (null !== $selected_label) {
				$items[] = [
					'kind' => 'setting',
					'part' => 'settings',
					'title' => __('Show selected label', 'smart-variations-images'),
					'payload' => [
						'key' => 'swatches_swatches_product_page_show_selected_label',
						'value' => in_array((string) $selected_label, ['yes', '1', 'true'], true) ? '1' : '0',
					],
				];
			}

			return $items;
		}
	}
}
