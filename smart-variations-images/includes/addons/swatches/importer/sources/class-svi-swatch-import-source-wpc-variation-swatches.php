<?php
/**
 * Import source: WPC Variation Swatches (wpc-variation-swatches).
 *
 * Storage read here, verified on the wordpress.org build:
 *  - Swatch type: `woocommerce_attribute_taxonomies.attribute_type`
 *    (button, color, image, radio, select).
 *  - Term values: `wpcvs_color` (hex), `wpcvs_image` (attachment ID),
 *    `wpcvs_button` / `wpcvs_radio` (label text). `wpcvs_tooltip` and
 *    `wpcvs_group` have no SVI equivalent.
 *  - No per-product overrides; globals live in the `wpcvs_settings` option.
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Swatch_Import_Source_WPC_Variation_Swatches')) {
	/**
	 * Adapter for WPC Variation Swatches.
	 */
	class SVI_Swatch_Import_Source_WPC_Variation_Swatches extends SVI_Swatch_Import_Source
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
		 * Source slug.
		 *
		 * @return string
		 */
		public function get_slug(): string
		{
			return 'wpc-variation-swatches';
		}

		/**
		 * Plugin name.
		 *
		 * @return string
		 */
		public function get_label(): string
		{
			return __('WPC Variation Swatches', 'smart-variations-images');
		}

		/**
		 * Plugin files.
		 *
		 * @return string[]
		 */
		protected function get_plugin_files(): array
		{
			return [
				'wpc-variation-swatches/wpc-variation-swatches.php',
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
			return ['wpcvs_color', 'wpcvs_image', 'wpcvs_button', 'wpcvs_radio'];
		}

		/**
		 * Settings options left behind, for the detection summary.
		 *
		 * @return string[]
		 */
		protected function get_option_evidence(): array
		{
			return false !== get_option('wpcvs_settings', false) ? ['wpcvs_settings'] : [];
		}

		/**
		 * Build attribute and term items.
		 *
		 * @return array[]
		 */
		protected function build_items(): array
		{
			return $this->build_attribute_term_items(function ($term_id, $type) {
				$color = (string) get_term_meta($term_id, 'wpcvs_color', true);
				$image = get_term_meta($term_id, 'wpcvs_image', true);
				$button = (string) get_term_meta($term_id, 'wpcvs_button', true);
				$radio = (string) get_term_meta($term_id, 'wpcvs_radio', true);

				$image_id = $this->resolve_attachment($image);
				$label = '' !== trim($button) ? $button : $radio;

				if ('' === trim($color) && 0 === $image_id && '' === trim($label)) {
					return null;
				}

				if ('image' === $type && 0 === $image_id && '' !== trim((string) $image)) {
					return [
						'unsupported' => true,
						'reason' => __('The swatch image is not in the media library.', 'smart-variations-images'),
					];
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
