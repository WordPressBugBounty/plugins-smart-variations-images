<?php
/**
 * Small read helpers shared by the schema and the feeds.
 *
 * @package Smart_Variations_Images\Addons\Variant_Schema
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_VS_Group_Data')) {
	/**
	 * Attribute labels and gallery matching.
	 */
	class SVI_VS_Group_Data
	{
		/**
		 * Human label of one attribute value of a variation.
		 *
		 * @param WC_Product_Variation $variation Variation.
		 * @param string               $attribute Attribute taxonomy or custom name.
		 * @return string
		 */
		public static function attribute_label(WC_Product_Variation $variation, string $attribute): string
		{
			$attributes = (array) $variation->get_attributes();

			if (!isset($attributes[$attribute]) || '' === (string) $attributes[$attribute]) {
				return '';
			}

			$slug = (string) $attributes[$attribute];

			if (taxonomy_exists($attribute)) {
				$term = get_term_by('slug', $slug, $attribute);

				if ($term && !is_wp_error($term)) {
					return $term->name;
				}
			}

			return $slug;
		}

		/**
		 * Most specific gallery whose slugs the variation carries.
		 *
		 * @param array                $galleries Galleries.
		 * @param WC_Product_Variation $variation Variation.
		 * @return array|null
		 */
		public static function match_gallery(array $galleries, WC_Product_Variation $variation): ?array
		{
			$slugs = array_map('strtolower', array_map('strval', array_values((array) $variation->get_attributes())));
			$slugs = array_filter($slugs);

			$best     = null;
			$best_len = -1;
			$fallback = null;

			foreach ($galleries as $gallery) {
				$gallery_slugs = array_map('strtolower', array_map('strval', (array) ($gallery['slugs'] ?? array())));

				if (!$gallery_slugs) {
					continue;
				}

				$special = $gallery_slugs[0];

				if ('svidefault' === $special || 'sviproglobal' === $special) {
					$fallback = $fallback ?? $gallery;
					continue;
				}

				if (array_diff($gallery_slugs, $slugs)) {
					continue;
				}

				if (count($gallery_slugs) > $best_len) {
					$best     = $gallery;
					$best_len = count($gallery_slugs);
				}
			}

			return $best ?? $fallback;
		}
	}
}
