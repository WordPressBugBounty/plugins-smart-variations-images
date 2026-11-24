<?php
/**
 * "Variations as products" — the loop card.
 *
 * WooCommerce already renders a variation inside a product loop: the link is
 * the parent permalink with the variation attributes (the same URL the copy
 * variation link button builds), the price is the variation price and the
 * add to cart URL points at the parent. This class fills the gaps: the card
 * title, the SVI gallery image of that variation, the parent rating, the
 * "Select options" button and the parent category classes.
 *
 * @package Smart_Variations_Images\Addons\Single_Variations
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_SV_Card')) {
	/**
	 * Loop card filters and helpers.
	 */
	class SVI_SV_Card
	{
		/**
		 * Parent galleries, keyed by product ID.
		 *
		 * @var array<int, array>
		 */
		private static array $galleries = array();

		/**
		 * First gallery image per variation.
		 *
		 * @var array<int, int>
		 */
		private static array $images = array();

		/**
		 * Register the hooks.
		 */
		public static function init(): void
		{
			add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue'));
			add_filter('the_title', array(__CLASS__, 'filter_title'), 10, 2);
			add_filter('woocommerce_product_title', array(__CLASS__, 'filter_product_title'), 10, 2);
			add_filter('woocommerce_product_variation_get_image_id', array(__CLASS__, 'filter_image_id'), 10, 2);
			add_filter('woocommerce_product_variation_get_average_rating', array(__CLASS__, 'filter_parent_rating'), 10, 2);
			add_filter('woocommerce_product_variation_get_rating_counts', array(__CLASS__, 'filter_parent_rating_counts'), 10, 2);
			add_filter('woocommerce_product_variation_get_review_count', array(__CLASS__, 'filter_parent_review_count'), 10, 2);
			add_filter('woocommerce_product_add_to_cart_text', array(__CLASS__, 'filter_cart_text'), 10, 2);
			add_filter('woocommerce_product_add_to_cart_url', array(__CLASS__, 'filter_cart_url'), 10, 2);
			add_filter('woocommerce_post_class', array(__CLASS__, 'filter_post_class'), 10, 2);
			add_filter('woocommerce_product_is_visible', array(__CLASS__, 'filter_is_visible'), 10, 2);
			add_filter('post_class', array(__CLASS__, 'filter_core_post_class'), 10, 3);
			add_filter('woocommerce_loop_add_to_cart_args', array(__CLASS__, 'filter_cart_args'), 10, 2);
			add_action('woocommerce_after_shop_loop_item', array(__CLASS__, 'render_button'), 10);
		}

		/**
		 * Load the card stylesheet on the pages that can show cards.
		 */
		public static function enqueue(): void
		{
			if (!SVI_SV_Settings::is_enabled()) {
				return;
			}

			$is_archive = (function_exists('is_shop') && is_shop())
				|| (function_exists('is_product_taxonomy') && is_product_taxonomy())
				|| is_search();

			if (!$is_archive) {
				return;
			}

			$suffix  = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
			$version = defined('SMART_VARIATIONS_IMAGES_VERSION') ? SMART_VARIATIONS_IMAGES_VERSION : '1';
			$css     = __DIR__ . '/../assets/css/single-variations' . $suffix . '.css';

			wp_enqueue_style(
				'svi-single-variations',
				plugins_url('assets/css/single-variations' . $suffix . '.css', __DIR__ . '/../main.php'),
				array(),
				file_exists($css) ? (string) filemtime($css) : $version
			);
		}

		/**
		 * Drop the per-request caches.
		 */
		public static function flush_cache(): void
		{
			self::$galleries = array();
			self::$images    = array();
		}

		/**
		 * Whether a post is one of our variation cards on this request.
		 *
		 * @param int $post_id Post ID.
		 * @return bool
		 */
		public static function is_loop_variation($post_id): bool
		{
			$post_id = (int) $post_id;

			if ($post_id <= 0 || !SVI_SV_Query::is_injected()) {
				return false;
			}

			if (is_admin() && !wp_doing_ajax()) {
				return false;
			}

			return 'product_variation' === get_post_type($post_id);
		}

		/**
		 * Card title for a variation.
		 *
		 * @param WC_Product_Variation $variation Variation.
		 * @return string
		 */
		public static function variation_title($variation): string
		{
			$parent = wc_get_product($variation->get_parent_id());
			$title  = $parent ? $parent->get_name() : $variation->get_title();
			$values = self::attribute_labels($variation);

			if ($values) {
				$title .= ' – ' . implode(' – ', $values);
			}

			/**
			 * Filters the card title of a variation.
			 *
			 * @param string               $title     Computed title.
			 * @param WC_Product_Variation $variation Variation.
			 */
			return (string) apply_filters('svi_sv_title', $title, $variation);
		}

		/**
		 * Human labels of the attributes that distinguish the variation.
		 *
		 * @param WC_Product_Variation $variation Variation.
		 * @return string[]
		 */
		public static function attribute_labels($variation): array
		{
			$labels = array();

			foreach ($variation->get_attributes() as $taxonomy => $slug) {
				$slug = (string) $slug;

				if ('' === $slug) {
					continue;
				}

				$label = '';

				if (taxonomy_exists($taxonomy)) {
					$term = get_term_by('slug', $slug, $taxonomy);
					$label = $term && !is_wp_error($term) ? $term->name : $slug;
				} else {
					$label = $slug;
				}

				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce display hook.
				$labels[] = (string) apply_filters('woocommerce_variation_option_name', $label);
			}

			return array_values(array_filter($labels));
		}

		/**
		 * Replace the loop title of a variation card.
		 *
		 * @param string $title   Post title.
		 * @param int    $post_id Post ID.
		 * @return string
		 */
		public static function filter_title($title, $post_id = 0)
		{
			if (!self::is_loop_variation($post_id)) {
				return $title;
			}

			$variation = wc_get_product((int) $post_id);

			return $variation instanceof WC_Product_Variation ? self::variation_title($variation) : $title;
		}

		/**
		 * Replace `$product->get_title()` for a variation card.
		 *
		 * @param string   $title   Parent title.
		 * @param WC_Product $product Product.
		 * @return string
		 */
		public static function filter_product_title($title, $product = null)
		{
			if (!$product instanceof WC_Product_Variation || !self::is_loop_variation($product->get_id())) {
				return $title;
			}

			return self::variation_title($product);
		}

		/**
		 * Use the first image of the variation's SVI gallery.
		 *
		 * @param int        $image_id Image ID.
		 * @param WC_Product $product  Product.
		 * @return int
		 */
		public static function filter_image_id($image_id, $product = null)
		{
			if (!$product instanceof WC_Product_Variation || !self::is_loop_variation($product->get_id())) {
				return $image_id;
			}

			$gallery_id = self::gallery_image_id($product);

			return $gallery_id > 0 ? $gallery_id : $image_id;
		}

		/**
		 * First image of the SVI gallery that belongs to a variation.
		 *
		 * @param WC_Product_Variation $variation Variation.
		 * @return int Attachment ID, 0 when the variation has no gallery.
		 */
		public static function gallery_image_id($variation): int
		{
			$variation_id = (int) $variation->get_id();

			if (array_key_exists($variation_id, self::$images)) {
				return self::$images[$variation_id];
			}

			self::$images[$variation_id] = 0;

			$galleries = self::parent_galleries((int) $variation->get_parent_id());
			$match     = self::match_gallery($galleries, $variation);

			if ($match) {
				$images = array_values(array_filter(array_map('intval', (array) ($match['imgs'] ?? array()))));

				if ($images) {
					self::$images[$variation_id] = (int) $images[0];
				}
			}

			/**
			 * Filters the SVI gallery image used on a variation card.
			 *
			 * @param int                  $image_id  Attachment ID.
			 * @param WC_Product_Variation $variation Variation.
			 */
			return (int) apply_filters('svi_sv_card_image_id', self::$images[$variation_id], $variation);
		}

		/**
		 * All galleries of a parent product.
		 *
		 * @param int $product_id Parent product ID.
		 * @return array
		 */
		public static function parent_galleries(int $product_id): array
		{
			if (isset(self::$galleries[$product_id])) {
				return self::$galleries[$product_id];
			}

			$raw = get_post_meta($product_id, 'woosvi_slug', true);

			if (is_string($raw) && '' !== $raw) {
				$raw = json_decode($raw, true);
			}

			self::$galleries[$product_id] = class_exists('Smart_Variations_Images')
				? Smart_Variations_Images::normalize_galleries($raw)
				: array();

			return self::$galleries[$product_id];
		}

		/**
		 * Most specific gallery whose slugs a variation carries.
		 *
		 * @param array                $galleries Galleries.
		 * @param WC_Product_Variation $variation Variation.
		 * @return array|null
		 */
		public static function match_gallery(array $galleries, $variation): ?array
		{
			if (!$galleries) {
				return null;
			}

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

				if ('svidefault' === $special) {
					$fallback = $fallback ?? $gallery;
					continue;
				}

				if ('sviproglobal' === $special) {
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
		 * Add the card marker to block themes, which use post_class().
		 *
		 * @param string[] $classes   Classes.
		 * @param string   $css_class Extra classes.
		 * @param int      $post_id   Post ID.
		 * @return string[]
		 */
		public static function filter_core_post_class($classes, $css_class = '', $post_id = 0)
		{
			if (!is_array($classes) || !self::is_loop_variation($post_id)) {
				return $classes;
			}

			$classes[] = 'svi-sv-card';

			return array_values(array_unique($classes));
		}

		/**
		 * Keep the card of a variation whose parent was hidden from the shop.
		 *
		 * The parent is hidden on purpose in PRO ("hide the parent product"), and
		 * a variation inherits the parent's catalog visibility, which would make
		 * `content-product.php` skip the card.
		 *
		 * @param bool $visible    Visibility from WooCommerce.
		 * @param int  $product_id Product ID.
		 * @return bool
		 */
		public static function filter_is_visible($visible, $product_id = 0)
		{
			if ($visible || !self::is_loop_variation($product_id)) {
				return $visible;
			}

			$variation = wc_get_product((int) $product_id);

			if (!$variation instanceof WC_Product_Variation) {
				return $visible;
			}

			$parent = wc_get_product($variation->get_parent_id());

			if (!$parent || 'publish' !== $parent->get_status()) {
				return $visible;
			}

			if ('yes' === get_option('woocommerce_hide_out_of_stock_items') && !$variation->is_in_stock()) {
				return false;
			}

			return true;
		}

		/**
		 * Print the card button when the theme removes the WooCommerce one.
		 *
		 * Divi and other themes unhook `woocommerce_template_loop_add_to_cart`
		 * from the loop, which would leave a variation card without any button.
		 * When the theme keeps it, our text/URL filters already shape it.
		 */
		public static function render_button(): void
		{
			global $product;

			if (!$product instanceof WC_Product_Variation || !self::is_loop_variation($product->get_id())) {
				return;
			}

			if (has_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart')) {
				return;
			}

			woocommerce_template_loop_add_to_cart();
		}

		/**
		 * Reshape the loop button: no AJAX, and an accurate accessible name.
		 *
		 * The card button opens the product page with the variation selected,
		 * so WooCommerce must not turn it into an AJAX add-to-cart button.
		 *
		 * @param array      $args    Button arguments.
		 * @param WC_Product $product Product.
		 * @return array
		 */
		public static function filter_cart_args($args, $product = null)
		{
			if (!$product instanceof WC_Product_Variation || !self::is_loop_variation($product->get_id())) {
				return $args;
			}

			if (isset($args['class'])) {
				$classes = array_filter(
					preg_split('/\s+/', (string) $args['class']),
					static function ($class) {
						return 'ajax_add_to_cart' !== $class;
					}
				);

				$args['class'] = implode(' ', $classes);
			}

			$args['attributes'] = isset($args['attributes']) ? (array) $args['attributes'] : array();
			$args['attributes']['aria-label'] = sprintf(
				/* translators: %s: variation card title. */
				__('Select options: %s', 'smart-variations-images'),
				wp_strip_all_tags(self::variation_title($product))
			);

			unset($args['attributes']['data-success_message'], $args['attributes']['data-product_sku']);

			return $args;
		}

		/**
		 * Parent rating, shown on the variation card.
		 *
		 * @param float      $rating  Rating.
		 * @param WC_Product $product Product.
		 * @return float
		 */
		public static function filter_parent_rating($rating, $product = null)
		{
			$parent = self::parent_for_rating($product);

			return $parent ? (float) $parent->get_average_rating() : $rating;
		}

		/**
		 * Parent rating counts, shown on the variation card.
		 *
		 * @param array      $counts  Counts.
		 * @param WC_Product $product Product.
		 * @return array
		 */
		public static function filter_parent_rating_counts($counts, $product = null)
		{
			$parent = self::parent_for_rating($product);

			return $parent ? $parent->get_rating_counts() : $counts;
		}

		/**
		 * Parent review count, shown on the variation card.
		 *
		 * @param int        $count   Count.
		 * @param WC_Product $product Product.
		 * @return int
		 */
		public static function filter_parent_review_count($count, $product = null)
		{
			$parent = self::parent_for_rating($product);

			return $parent ? (int) $parent->get_review_count() : $count;
		}

		/**
		 * Parent product of a variation card, or null when not ours.
		 *
		 * @param mixed $product Product.
		 * @return WC_Product|null
		 */
		private static function parent_for_rating($product): ?WC_Product
		{
			if (!$product instanceof WC_Product_Variation || !self::is_loop_variation($product->get_id())) {
				return null;
			}

			$parent = wc_get_product($product->get_parent_id());

			return $parent ? $parent : null;
		}

		/**
		 * "Select options" on the card instead of "Add to cart".
		 *
		 * @param string     $text    Button text.
		 * @param WC_Product $product Product.
		 * @return string
		 */
		public static function filter_cart_text($text, $product = null)
		{
			if (!$product instanceof WC_Product_Variation || !self::is_loop_variation($product->get_id())) {
				return $text;
			}

			if (!$product->is_purchasable() || !$product->is_in_stock()) {
				return $text;
			}

			/**
			 * Filters the card button label of a variation.
			 *
			 * @param string               $text      Button text.
			 * @param WC_Product_Variation $product   Variation.
			 */
			return (string) apply_filters('svi_sv_cart_text', __('Select options', 'smart-variations-images'), $product);
		}

		/**
		 * Send the card button to the preselected product page.
		 *
		 * @param string     $url     Button URL.
		 * @param WC_Product $product Product.
		 * @return string
		 */
		public static function filter_cart_url($url, $product = null)
		{
			if (!$product instanceof WC_Product_Variation || !self::is_loop_variation($product->get_id())) {
				return $url;
			}

			/**
			 * Filters the card button URL of a variation.
			 *
			 * The default is the parent permalink with the variation attributes,
			 * the same URL the "copy variation link" button produces.
			 *
			 * @param string               $url     Button URL.
			 * @param WC_Product_Variation $product Variation.
			 */
			return (string) apply_filters('svi_sv_cart_url', $product->get_permalink(), $product);
		}

		/**
		 * Add the parent categories and a marker class to the card.
		 *
		 * Themes style cards by `product_cat-*` classes, which a variation does
		 * not have on its own.
		 *
		 * @param string[]   $classes Classes.
		 * @param WC_Product $product Product.
		 * @return string[]
		 */
		public static function filter_post_class($classes, $product = null)
		{
			if (!$product instanceof WC_Product_Variation || !self::is_loop_variation($product->get_id())) {
				return $classes;
			}

			$classes[] = 'svi-sv-card';

			$parent = wc_get_product($product->get_parent_id());

			if ($parent) {
				$classes = array_merge($classes, wc_get_product_taxonomy_class($parent->get_category_ids(), 'product_cat'));
			}

			return array_values(array_unique($classes));
		}
	}
}
