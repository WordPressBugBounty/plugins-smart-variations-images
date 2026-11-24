<?php
/**
 * Reverse sync: write the SVI galleries into the WooCommerce variation data.
 *
 * WooCommerce 11.1 keeps each variation's image and gallery in the variation
 * post (`_thumbnail_id` and `_product_image_gallery`). The Product Gallery
 * block, REST v3, the feeds and the JSON-LD read them from there, so a store
 * that designs its galleries in SVI can let WooCommerce see the same pictures.
 *
 * The setting is off by default. The first write of a product stores the old
 * variation images in `_svi_core_sync_backup`; nothing is ever deleted when the
 * setting is turned off.
 *
 * @package Smart_Variations_Images\Addons\Core_Sync
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Core_Sync')) {
	/**
	 * Product save hook that mirrors SVI galleries into core.
	 */
	class SVI_Core_Sync
	{
		/**
		 * Backup of the core values before the first write, per product.
		 */
		const BACKUP_META = '_svi_core_sync_backup';

		/**
		 * Variation galleries as WooCommerce stores them.
		 */
		const GALLERY_META = '_product_image_gallery';

		/**
		 * Guard against re-entrancy while writing meta.
		 *
		 * @var bool
		 */
		private static bool $running = false;

		/**
		 * Register the hooks.
		 */
		public static function init(): void
		{
			add_action('woocommerce_after_product_object_save', array(__CLASS__, 'sync'), 20, 1);
		}

		/**
		 * Mirror the SVI galleries of one product into its variations.
		 *
		 * @param WC_Product $product Product that was just saved.
		 */
		public static function sync($product): void
		{
			if (self::$running || !$product instanceof WC_Product || !$product->is_type('variable')) {
				return;
			}

			if (!SVI_Core_Sync_Settings::is_enabled()) {
				return;
			}

			$product_id = (int) $product->get_id();
			$galleries  = self::parent_galleries($product_id);

			if (!$galleries) {
				return;
			}

			self::$running = true;

			$backup = array();
			$wrote  = false;

			foreach ($product->get_children() as $child_id) {
				$child_id  = (int) $child_id;
				$variation = wc_get_product($child_id);

				if (!$variation instanceof WC_Product_Variation) {
					continue;
				}

				$gallery = self::match_gallery($galleries, $variation);

				if (!$gallery) {
					continue;
				}

				$images = array_values(array_filter(array_map('absint', (array) ($gallery['imgs'] ?? array()))));

				if (!$images) {
					continue;
				}

				$current_image   = absint(get_post_meta($child_id, '_thumbnail_id', true));
				$current_gallery = (string) get_post_meta($child_id, self::GALLERY_META, true);

				$new_image   = (int) $images[0];
				$new_gallery = implode(',', array_slice($images, 1));

				if ($current_image === $new_image && $current_gallery === $new_gallery) {
					continue;
				}

				$backup[$child_id] = array(
					'image'   => $current_image,
					'gallery' => $current_gallery,
				);

				update_post_meta($child_id, '_thumbnail_id', $new_image);

				if ('' === $new_gallery) {
					delete_post_meta($child_id, self::GALLERY_META);
				} else {
					update_post_meta($child_id, self::GALLERY_META, $new_gallery);
				}

				$wrote = true;
			}

			if ($backup) {
				self::backup_once($product_id, $backup);
			}

			if ($wrote) {
				clean_post_cache($product_id);

				foreach (array_keys($backup) as $child_id) {
					clean_post_cache((int) $child_id);
				}

				if (function_exists('wc_delete_product_transients')) {
					wc_delete_product_transients($product_id);
				}
			}

			self::$running = false;
		}

		/**
		 * Read the SVI galleries of a product.
		 *
		 * @param int $product_id Product ID.
		 * @return array
		 */
		private static function parent_galleries(int $product_id): array
		{
			$raw = get_post_meta($product_id, 'woosvi_slug', true);

			if (is_string($raw) && '' !== trim($raw)) {
				$decoded = json_decode($raw, true);
				$raw     = is_array($decoded) ? $decoded : array();
			}

			if (!is_array($raw)) {
				return array();
			}

			return class_exists('Smart_Variations_Images')
				? Smart_Variations_Images::normalize_galleries($raw)
				: array();
		}

		/**
		 * Most specific gallery whose slugs the variation carries.
		 *
		 * @param array                $galleries Galleries.
		 * @param WC_Product_Variation $variation Variation.
		 * @return array|null
		 */
		private static function match_gallery(array $galleries, $variation): ?array
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

		/**
		 * Keep the core values from before the first write, once per product.
		 *
		 * @param int   $product_id Product ID.
		 * @param array $values     Variation values by variation ID.
		 */
		private static function backup_once(int $product_id, array $values): void
		{
			$existing = get_post_meta($product_id, self::BACKUP_META, true);

			if (is_array($existing) && !empty($existing['variations'])) {
				return;
			}

			update_post_meta(
				$product_id,
				self::BACKUP_META,
				array(
					'at'         => time(),
					'variations' => $values,
				)
			);
		}
	}
}
