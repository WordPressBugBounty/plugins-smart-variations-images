<?php
/**
 * Render WooCommerce "color / image" (`wc-visual`) attributes as SVI swatches.
 *
 * WooCommerce 10.9+ can mark an attribute as `wc-visual`: each value then holds
 * a colour (`color` term meta) or an image (`image` term meta), mixed freely in
 * the same attribute. SVI already reads both keys as its legacy value family,
 * so only the attribute type has to be answered — the renderer already falls
 * back from a colour to an image and back for a single value.
 *
 * Nothing is written here and no core data is changed.
 *
 * @package Smart_Variations_Images\Addons\Core_Bridge
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Core_Visual')) {
	/**
	 * Answers the `wc-visual` attribute type for the swatch renderer.
	 */
	class SVI_Core_Visual
	{
		/**
		 * Per-attribute resolved type, cached.
		 *
		 * @var array<int, string>
		 */
		private static array $types = array();

		/**
		 * Register the hooks.
		 */
		public static function init(): void
		{
			add_filter('svi_resolve_swatch_type', array(__CLASS__, 'resolve_type'), 10, 4);
		}

		/**
		 * Resolve a `wc-visual` attribute to colour or image swatches.
		 *
		 * SVI owns one swatch type per attribute, so the type is taken from the
		 * values: image when any value carries a picture (a photo of the fabric
		 * reads better than a flat colour), colour otherwise.
		 *
		 * @param string $type         Type resolved by SVI.
		 * @param string $attribute    Attribute taxonomy.
		 * @param int    $product_id   Product ID.
		 * @param int    $attribute_id Attribute taxonomy ID.
		 * @return string
		 */
		public static function resolve_type($type, $attribute = '', $product_id = 0, $attribute_id = 0)
		{
			$type = (string) $type;

			if ('' !== $type || !$attribute_id || !self::is_visual_attribute((int) $attribute_id)) {
				return $type;
			}

			if (isset(self::$types[(int) $attribute_id])) {
				return self::$types[(int) $attribute_id];
			}

			$terms = get_terms(
				array(
					'taxonomy'   => (string) $attribute,
					'hide_empty' => false,
					'number'     => 100,
				)
			);

			$has_image = false;
			$has_color = false;

			if (!is_wp_error($terms)) {
				foreach ($terms as $term) {
					if (!$has_image && absint(get_term_meta($term->term_id, 'image', true)) > 0) {
						$has_image = true;
					}

					if (!$has_color && '' !== (string) get_term_meta($term->term_id, 'color', true)) {
						$has_color = true;
					}

					if ($has_image && $has_color) {
						break;
					}
				}
			}

			$resolved = $has_image ? 'image' : ($has_color ? 'color' : '');

			self::$types[(int) $attribute_id] = $resolved;

			return $resolved;
		}

		/**
		 * Whether WooCommerce marks the attribute as `wc-visual`.
		 *
		 * @param int $attribute_id Attribute taxonomy ID.
		 * @return bool
		 */
		private static function is_visual_attribute(int $attribute_id): bool
		{
			if (!function_exists('wc_get_attribute_taxonomies')) {
				return false;
			}

			foreach (wc_get_attribute_taxonomies() as $attribute) {
				if ((int) $attribute->attribute_id !== $attribute_id) {
					continue;
				}

				return isset($attribute->attribute_type) && 'wc-visual' === (string) $attribute->attribute_type;
			}

			return false;
		}
	}
}
