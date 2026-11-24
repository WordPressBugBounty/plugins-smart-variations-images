<?php
/**
 * "Variations as products" — query layer.
 *
 * Adds the ticked variations to the product queries the shop, the category and
 * tag archives and the search results use, and nothing else: admin screens,
 * REST requests, feeds and secondary queries are never touched.
 *
 * Visibility lives in one postmeta key on the variation (`_svi_sv_visible`,
 * a comma separated list of contexts). The filtering therefore happens in SQL,
 * which keeps pagination, result counts and the "Showing 1–12 of N" text
 * correct.
 *
 * @package Smart_Variations_Images\Addons\Single_Variations
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_SV_Query')) {
	/**
	 * Query injection for variation cards.
	 */
	class SVI_SV_Query
	{
		/**
		 * Post meta holding the visible contexts, e.g. "catalog,search,filter".
		 */
		const META_VISIBLE = '_svi_sv_visible';

		/**
		 * Context token: shop and product taxonomies.
		 */
		const TOKEN_CATALOG = 'catalog';

		/**
		 * Context token: search results.
		 */
		const TOKEN_SEARCH = 'search';

		/**
		 * Context token: filtered results (layered nav, price, stock).
		 */
		const TOKEN_FILTER = 'filter';

		/**
		 * True once at least one query on this request carries variations.
		 *
		 * @var bool
		 */
		private static bool $injected = false;

		/**
		 * Register the hooks.
		 */
		public static function init(): void
		{
			add_action('pre_get_posts', array(__CLASS__, 'maybe_inject'), 20);
			add_action('parse_tax_query', array('SVI_SV_Terms', 'strip_parsed_clauses'), 20);
			add_filter('query_loop_block_query_vars', array(__CLASS__, 'inject_block_query'), 20, 2);
			add_filter('posts_where', array(__CLASS__, 'filter_where'), 20, 2);
			add_filter('posts_clauses', array(__CLASS__, 'filter_order_clauses'), 20, 2);
		}

		/**
		 * Whether variations were added to a query on this request.
		 *
		 * @return bool
		 */
		public static function is_injected(): bool
		{
			return self::$injected;
		}

		/**
		 * Whether this request may show variation cards at all.
		 *
		 * @return bool
		 */
		public static function is_frontend_request(): bool
		{
			if (!SVI_SV_Settings::is_enabled()) {
				return false;
			}

			if (is_admin() || is_feed() || is_embed()) {
				return false;
			}

			if (wp_doing_ajax()) {
				return false;
			}

			if (defined('REST_REQUEST') && REST_REQUEST) {
				return false;
			}

			return true;
		}

		/**
		 * Context of the current request: catalog, search or null.
		 *
		 * A catalog request that runs through a filter becomes the filter
		 * context, which PRO per-variation overrides can switch off.
		 *
		 * @return string|null
		 */
		public static function request_context(): ?string
		{
			if (!self::is_frontend_request()) {
				return null;
			}

			$context = null;

			if (is_search()) {
				$context = SVI_SV_Settings::in_search() ? self::TOKEN_SEARCH : null;
			} elseif (function_exists('is_shop') && is_shop()) {
				$context = SVI_SV_Settings::in_shop() ? self::TOKEN_CATALOG : null;
			} elseif (function_exists('is_product_taxonomy') && is_product_taxonomy()) {
				$context = SVI_SV_Settings::in_tax() ? self::TOKEN_CATALOG : null;
			}

			if (self::TOKEN_CATALOG === $context && function_exists('is_filtered') && is_filtered()) {
				return self::TOKEN_FILTER;
			}

			return $context;
		}

		/**
		 * Add variations to the main product query.
		 *
		 * @param WP_Query $query Query object.
		 */
		public static function maybe_inject($query): void
		{
			if (!$query instanceof WP_Query || !$query->is_main_query()) {
				return;
			}

			$context = self::context_for_query($query);

			if (null === $context) {
				return;
			}

			self::apply($query, $context);
		}

		/**
		 * Add variations to a Product Collection block query (block themes).
		 *
		 * @param array $query_vars Query vars for the block loop.
		 * @param mixed $block      Block instance.
		 * @return array
		 */
		public static function inject_block_query($query_vars, $block = null): array
		{
			if (!is_array($query_vars)) {
				return $query_vars;
			}

			$post_type = isset($query_vars['post_type']) ? (array) $query_vars['post_type'] : array();

			if (!in_array('product', $post_type, true)) {
				return $query_vars;
			}

			$context = self::request_context();

			if (null === $context) {
				return $query_vars;
			}

			$query_vars['post_type'] = array_values(array_unique(array_merge($post_type, array('product_variation'))));
			$query_vars['svi_sv_injected'] = true;
			$query_vars['svi_sv_token'] = $context;

			if (!empty($query_vars['tax_query']) && is_array($query_vars['tax_query'])) {
				$stripped = array();
				$kept     = self::strip_tax_clauses($query_vars['tax_query'], $stripped);

				if ($stripped) {
					$query_vars['svi_sv_terms'] = $stripped;
					$query_vars['tax_query']    = $kept;
				}
			}

			if (self::TOKEN_SEARCH === $context && empty($query_vars['s'])) {
				$query_vars['s'] = get_search_query();
			}

			/**
			 * Filters the query vars used to show variation cards.
			 *
			 * Search plugins can extend the query here (for example to add
			 * their own meta or taxonomy conditions).
			 *
			 * @param array  $query_vars Query vars.
			 * @param string $context    catalog, search or filter.
			 */
			$query_vars = apply_filters('svi_sv_query_args', $query_vars, $context);

			self::$injected = true;

			return $query_vars;
		}

		/**
		 * Context of a main query, read from the query flags themselves.
		 *
		 * @param WP_Query $query Query object.
		 * @return string|null
		 */
		private static function context_for_query(WP_Query $query): ?string
		{
			if (!SVI_SV_Settings::is_enabled()) {
				return null;
			}

			if (is_admin() || is_feed() || is_embed() || wp_doing_ajax()) {
				return null;
			}

			if (defined('REST_REQUEST') && REST_REQUEST) {
				return null;
			}

			$context = null;

			if ($query->is_search()) {
				$context = SVI_SV_Settings::in_search() ? self::TOKEN_SEARCH : null;
			} elseif ($query->is_post_type_archive('product')) {
				$context = SVI_SV_Settings::in_shop() ? self::TOKEN_CATALOG : null;
			} elseif ($query->is_tax(get_object_taxonomies('product'))) {
				$context = SVI_SV_Settings::in_tax() ? self::TOKEN_CATALOG : null;
			}

			if (self::TOKEN_CATALOG === $context && function_exists('is_filtered') && is_filtered()) {
				return self::TOKEN_FILTER;
			}

			return $context;
		}

		/**
		 * Add the variation post type and the visibility token to a query.
		 *
		 * @param WP_Query $query   Query object.
		 * @param string   $context Context token.
		 */
		private static function apply(WP_Query $query, string $context): void
		{
			$post_type = $query->get('post_type');
			$post_type = $post_type ? (array) $post_type : array('product');
			$post_type = array_filter($post_type);

			if (in_array('product_variation', $post_type, true)) {
				return;
			}

			$post_type[] = 'product_variation';

			/**
			 * Filters the query vars used to show variation cards.
			 *
			 * @param array  $query_vars Query vars.
			 * @param string $context    catalog, search or filter.
			 */
			$args = apply_filters(
				'svi_sv_query_args',
				array(
					'post_type'        => array_values($post_type),
					'svi_sv_injected'  => true,
					'svi_sv_token'     => $context,
				),
				$context
			);

			foreach ($args as $key => $value) {
				$query->set($key, $value);
			}

			SVI_SV_Terms::inherit_from_parent($query);

			self::$injected = true;
		}

		/**
		 * Keep only the variations the merchant allowed in this context.
		 *
		 * Applies to product rows only when the variation is visible, has a
		 * published parent and passes the extra conditions PRO adds.
		 *
		 * @param string   $where WHERE clause.
		 * @param WP_Query $query Query object.
		 * @return string
		 */
		public static function filter_where($where, $query)
		{
			if (!$query instanceof WP_Query || !$query->get('svi_sv_injected')) {
				return $where;
			}

			$token = (string) $query->get('svi_sv_token');

			if ('' === $token) {
				return $where;
			}

			global $wpdb;

			$sql = $wpdb->prepare(
				" AND ( {$wpdb->posts}.post_type <> 'product_variation'"
				. " OR ( EXISTS ( SELECT 1 FROM {$wpdb->postmeta} svi_sv_m WHERE svi_sv_m.post_id = {$wpdb->posts}.ID AND svi_sv_m.meta_key = %s AND svi_sv_m.meta_value LIKE %s )"
				. " AND EXISTS ( SELECT 1 FROM {$wpdb->posts} svi_sv_p WHERE svi_sv_p.ID = {$wpdb->posts}.post_parent AND svi_sv_p.post_status = 'publish' ) ) )",
				self::META_VISIBLE,
				'%' . $wpdb->esc_like($token) . '%'
			);

			/**
			 * Filters the SQL that decides which variations are visible.
			 *
			 * PRO adds the group representative rule here, and search plugins
			 * can add their own conditions.
			 *
			 * @param string   $sql   SQL fragment, already prefixed with " AND ".
			 * @param WP_Query $query Query object.
			 * @param string   $token Context token.
			 */
			// WooCommerce hides out-of-stock products through a product_visibility
			// term, which a variation never has: apply the same rule to the cards.
			if ('yes' === get_option('woocommerce_hide_out_of_stock_items')) {
				$sql .= " AND ( {$wpdb->posts}.post_type <> 'product_variation' OR EXISTS ("
					. " SELECT 1 FROM {$wpdb->postmeta} svi_sv_st WHERE svi_sv_st.post_id = {$wpdb->posts}.ID"
					. ' AND svi_sv_st.meta_key = ' . $wpdb->prepare('%s', '_stock_status')
					. " AND svi_sv_st.meta_value <> 'outofstock' ) )";
			}

			$sql = apply_filters('svi_sv_visible_variations', $sql, $query, $token);

			$terms = $query->get('svi_sv_terms');

			if (!empty($terms) && is_array($terms)) {
				$sql .= SVI_SV_Terms::sql($terms);
			}

			return $where . $sql;
		}

		/**
		 * Sort a variation by its parent's menu order.
		 *
		 * WooCommerce gives every variation a menu order of its own (1, 2, 3…),
		 * which would push the cards behind every product in the default
		 * "custom ordering + name" shop sorting.
		 *
		 * @param array    $clauses Query clauses.
		 * @param WP_Query $query   Query object.
		 * @return array
		 */
		public static function filter_order_clauses($clauses, $query)
		{
			global $wpdb;

			if (!self::is_variation_query($query) || empty($clauses['orderby'])) {
				return $clauses;
			}

			$needle = "{$wpdb->posts}.menu_order";

			if (false === strpos((string) $clauses['orderby'], $needle)) {
				return $clauses;
			}

			$clauses['join'] .= " LEFT JOIN {$wpdb->posts} svi_sv_mp ON svi_sv_mp.ID = {$wpdb->posts}.post_parent";
			$clauses['orderby'] = str_replace(
				$needle,
				"IF( {$wpdb->posts}.post_type = 'product_variation', COALESCE( svi_sv_mp.menu_order, 0 ), {$needle} )",
				(string) $clauses['orderby']
			);

			return $clauses;
		}

		/**
		 * Visibility tokens a variation must have to appear in a context.
		 *
		 * @param string $context Context token.
		 * @return string
		 */
		public static function token_for(string $context): string
		{
			return in_array($context, array(self::TOKEN_CATALOG, self::TOKEN_SEARCH, self::TOKEN_FILTER), true)
				? $context
				: self::TOKEN_CATALOG;
		}

		/**
		 * Mark a query as carrying variations (used by PRO helpers).
		 *
		 * @param WP_Query $query Query object.
		 * @return bool
		 */
		public static function is_variation_query($query): bool
		{
			return $query instanceof WP_Query && (bool) $query->get('svi_sv_injected');
		}
	}
}
