<?php
/**
 * Import source: Variation Swatches for WooCommerce (variation-swatches-for-woocommerce).
 *
 * Storage read here, verified on the wordpress.org build:
 *  - Swatch type: `woocommerce_attribute_taxonomies.attribute_type`
 *    (color, image, label, radio).
 *  - Term values: generic term meta `color` (hex), `image` (attachment ID),
 *    `label` (text), plus tooltip keys this importer does not map.
 *  - No per-product overrides.
 *
 * This is the same term-meta family SVI's own legacy converter reads, so the
 * adapter mostly makes sure the values also land in the `svi_swatch_*` keys.
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Swatch_Import_Source_Variation_Swatches_For_WooCommerce')) {
	/**
	 * Adapter for Variation Swatches for WooCommerce (WooSuite).
	 */
	class SVI_Swatch_Import_Source_Variation_Swatches_For_WooCommerce extends SVI_Swatch_Import_Source
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
		 * Source slug.
		 *
		 * @return string
		 */
		public function get_slug(): string
		{
			return 'variation-swatches-for-woocommerce';
		}

		/**
		 * Plugin name.
		 *
		 * @return string
		 */
		public function get_label(): string
		{
			return __('Variation Swatches for WooCommerce (WooSuite)', 'smart-variations-images');
		}

		/**
		 * Plugin files.
		 *
		 * @return string[]
		 */
		protected function get_plugin_files(): array
		{
			return [
				'variation-swatches-for-woocommerce/variation-swatches-for-woocommerce.php',
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
			return ['color', 'image', 'label'];
		}

		/**
		 * Build attribute and term items.
		 *
		 * @return array[]
		 */
		protected function build_items(): array
		{
			return $this->build_attribute_term_items(function ($term_id, $type) {
				$color = (string) get_term_meta($term_id, 'color', true);
				$image = get_term_meta($term_id, 'image', true);
				$label = (string) get_term_meta($term_id, 'label', true);

				$image_id = $this->resolve_attachment($image);

				if ('' === trim($color) && 0 === $image_id && '' === trim($label)) {
					return null;
				}

				if ('image' === $type && 0 === $image_id) {
					if ('' !== trim((string) $image)) {
						return [
							'unsupported' => true,
							'reason' => __('The swatch image is not in the media library.', 'smart-variations-images'),
						];
					}
				}

				return [
					'colors' => '' !== trim($color) ? [$color] : [],
					'image' => $image_id,
					'label' => $label,
				];
			});
		}
	}
}
