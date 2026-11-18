<?php
/**
 * Import source: Woo Variation Swatches (woo-variation-swatches).
 *
 * Storage read here, verified on version 2.6.2:
 *  - Swatch type: `woocommerce_attribute_taxonomies.attribute_type`
 *    (color, image, button, radio, select, and the newer mixed / wc-visual).
 *  - Term values: `product_attribute_color` (hex) and
 *    `product_attribute_image` (attachment ID). PRO adds `is_dual_color`,
 *    `secondary_color`, `tooltip_text`, `tooltip_image_id`, `group_name`.
 *  - Per-attribute options: `woo_variation_swatches_attribute__{attribute_id}`.
 *  - Global settings: `woo_variation_swatches`.
 *  - The free version has no per-product overrides.
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Swatch_Import_Source_Woo_Variation_Swatches')) {
	/**
	 * Adapter for Woo Variation Swatches.
	 */
	class SVI_Swatch_Import_Source_Woo_Variation_Swatches extends SVI_Swatch_Import_Source
	{
		/**
		 * Attribute types the source writes that map to an SVI swatch type.
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
		 * Source slug.
		 *
		 * @return string
		 */
		public function get_slug(): string
		{
			return 'woo-variation-swatches';
		}

		/**
		 * Plugin name.
		 *
		 * @return string
		 */
		public function get_label(): string
		{
			return __('Woo Variation Swatches', 'smart-variations-images');
		}

		/**
		 * Plugin files.
		 *
		 * @return string[]
		 */
		protected function get_plugin_files(): array
		{
			return [
				'woo-variation-swatches/woo-variation-swatches.php',
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
		 * Settings options left behind, for the detection summary.
		 *
		 * @return string[]
		 */
		protected function get_option_evidence(): array
		{
			global $wpdb;

			$options = [];

			if (false !== get_option('woo_variation_swatches', false)) {
				$options[] = 'woo_variation_swatches';
			}

			$attribute_options = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
					$wpdb->esc_like('woo_variation_swatches_attribute__') . '%'
				)
			);

			if ($attribute_options > 0) {
				$options[] = sprintf(
					/* translators: %d: number of options. */
					_n('%d per-attribute option', '%d per-attribute options', $attribute_options, 'smart-variations-images'),
					$attribute_options
				);
			}

			return $options;
		}

		/**
		 * Build attribute, term and setting items.
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
				$taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);

				if (!isset(self::TYPE_MAP[$type])) {
					if (in_array($type, ['mixed', 'wc-visual'], true) && $this->claim_taxonomy($taxonomy)) {
						$items[] = [
							'kind' => 'attribute',
							'part' => 'attributes',
							'title' => sprintf('%s (%s)', $attribute->attribute_label, $taxonomy),
							'unsupported' => true,
							'reason' => __('SVI has one swatch type per attribute; this source mixes colour and image per term.', 'smart-variations-images'),
						];
					}

					continue;
				}

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

					$payload = $this->read_term_values($term_id);
					$unsupported = false;
					$reason = '';

					if (empty($payload['colors']) && $payload['image'] <= 0) {
						continue;
					}

					if ('image' === self::TYPE_MAP[$type] && $payload['image'] <= 0 && empty($payload['colors'])) {
						$unsupported = true;
						$reason = __('No image stored for this term.', 'smart-variations-images');
					}

					$item = [
						'kind' => 'term',
						'part' => 'terms',
						'title' => sprintf('%s → %s', $taxonomy, $term->name),
						'payload' => $payload,
					];

					if ($unsupported) {
						$item['unsupported'] = true;
						$item['reason'] = $reason;
					}

					$items[] = $item;
				}
			}

			foreach ($this->build_setting_items() as $item) {
				$items[] = $item;
			}

			return $items;
		}

		/**
		 * Read the swatch values stored for one term.
		 *
		 * @param int $term_id Term ID.
		 * @return array{term_id:int,colors:string[],image:int,label:string}
		 */
		private function read_term_values(int $term_id): array
		{
			$colors = [];
			$primary = (string) get_term_meta($term_id, 'product_attribute_color', true);

			if ('' !== trim($primary)) {
				$colors[] = $primary;
			}

			$is_dual = (string) get_term_meta($term_id, 'is_dual_color', true);

			if (in_array($is_dual, ['yes', '1', 'true'], true)) {
				$secondary = (string) get_term_meta($term_id, 'secondary_color', true);

				if ('' !== trim($secondary)) {
					$colors[] = $secondary;
				}
			}

			$image = (string) get_term_meta($term_id, 'product_attribute_image', true);
			$image_id = 0;

			if ('' !== trim($image)) {
				$image_id = ctype_digit($image) ? absint($image) : (int) attachment_url_to_postid($image);
			}

			return [
				'term_id' => $term_id,
				'colors' => $colors,
				'image' => $image_id,
				'label' => '',
			];
		}

		/**
		 * Map the global settings this source saved.
		 *
		 * Only keys the source actually stored are mapped, so a store that never
		 * opened the source settings screen does not get SVI defaults overwritten.
		 *
		 * @return array[]
		 */
		private function build_setting_items(): array
		{
			$stored = get_option('woo_variation_swatches', []);
			$stored = is_object($stored) ? get_object_vars($stored) : (array) $stored;

			if (empty($stored)) {
				return [];
			}

			$map = [
				'shape_style' => [
					'svi' => 'swatches_swatches_general_swatch_shape',
					'transform' => static function ($value) {
						return 'squared' === $value ? 'square' : ('rounded' === $value ? 'round' : '');
					},
				],
				'enable_tooltip' => [
					'svi' => 'swatches_swatches_tooltip_enable_tooltips',
					'transform' => static function ($value) {
						return in_array((string) $value, ['yes', '1', 'true'], true) ? '1' : '0';
					},
				],
				'attribute_behavior' => [
					'svi' => 'swatches_swatches_product_page_oos_behavior',
					'transform' => static function ($value) {
						$value = (string) $value;

						if ('blur' === $value) {
							return 'blur';
						}

						if ('blur-no-cross' === $value) {
							return 'dim';
						}

						return 'hide' === $value ? 'hide' : '';
					},
				],
				'default_to_button' => [
					'svi' => 'swatches_swatches_conversion_swatch_type_default',
					'transform' => static function ($value) {
						return in_array((string) $value, ['yes', '1', 'true'], true) ? 'text' : '';
					},
				],
				'show_on_archive' => [
					'svi' => 'swatches_swatches_catalog_show_in_catalog',
					'transform' => static function ($value) {
						return in_array((string) $value, ['yes', '1', 'true'], true) ? '1' : '0';
					},
				],
				'width' => [
					'svi' => 'swatches_swatches_styling_swatch_size_w',
					'transform' => 'absint',
				],
				'height' => [
					'svi' => 'swatches_swatches_styling_swatch_size_h',
					'transform' => 'absint',
				],
			];

			$labels = [
				'shape_style' => __('Swatch shape', 'smart-variations-images'),
				'enable_tooltip' => __('Enable tooltip', 'smart-variations-images'),
				'attribute_behavior' => __('Out-of-stock behaviour', 'smart-variations-images'),
				'default_to_button' => __('Convert dropdowns to buttons', 'smart-variations-images'),
				'show_on_archive' => __('Show swatches in the shop', 'smart-variations-images'),
				'width' => __('Swatch width', 'smart-variations-images'),
				'height' => __('Swatch height', 'smart-variations-images'),
			];

			$items = [];

			foreach ($map as $source_key => $target) {
				if (!array_key_exists($source_key, $stored)) {
					continue;
				}

				$value = call_user_func($target['transform'], $stored[$source_key]);

				if ('' === $value) {
					continue;
				}

				$items[] = [
					'kind' => 'setting',
					'part' => 'settings',
					'title' => $labels[$source_key] ?? $source_key,
					'payload' => [
						'key' => $target['svi'],
						'value' => $value,
						'source' => $source_key,
					],
				];
			}

			return $items;
		}
	}
}
