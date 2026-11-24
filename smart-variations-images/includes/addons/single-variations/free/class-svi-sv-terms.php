<?php
/**
 * "Variations as products" — product taxonomy handling.
 *
 * A variation carries no `product_cat` or `product_tag` terms of its own, so a
 * category archive (an inner join on the term relationships) would drop it.
 * The archive clause is taken out of the query and rebuilt as an EXISTS against
 * the parent product, which also keeps the result counts right.
 *
 * The taxonomy and term query vars are left untouched: `get_queried_object()`
 * and the layered nav widget both read them.
 *
 * @package Smart_Variations_Images\Addons\Single_Variations
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_SV_Terms')) {
	/**
	 * Category, tag and brand inheritance for variation cards.
	 */
	class SVI_SV_Terms
	{
		/**
		 * Let variations inherit the categories, tags and brands of the parent.
		 *
		 * A product taxonomy archive filters through a tax_query with an inner
		 * join, and a variation carries no terms of its own, so it would be
		 * dropped. The clause is taken out of the query and rebuilt as an
		 * EXISTS against the parent product, which also keeps the counts right.
		 *
		 * Attribute (`pa_*`) clauses are left alone: PRO handles them against
		 * the variation's own attribute meta.
		 *
		 * @param WP_Query $query Query object.
		 */
		public static function inherit_from_parent(WP_Query $query): void
		{
			$stripped = array();

			// A pretty permalink archive passes the term as a query var, which
			// WP_Query turns into its own tax clause after this hook runs.
			foreach (get_object_taxonomies('product') as $taxonomy) {
				if (!self::is_category_taxonomy($taxonomy)) {
					continue;
				}

				$object = get_taxonomy($taxonomy);

				if (!$object || empty($object->query_var)) {
					continue;
				}

				$value = $query->get($object->query_var);

				if (empty($value)) {
					continue;
				}

				$stripped[] = array(
					'taxonomy'         => $taxonomy,
					'terms'            => (array) $value,
					'field'            => 'slug',
					'include_children' => true,
				);
			}

			// Programmatic clauses (added with tax_query) are handled here too.
			$tax_query = $query->get('tax_query');

			if (!empty($tax_query) && is_array($tax_query)) {
				$kept      = self::strip_tax_clauses($tax_query, $stripped);
				$query->set('tax_query', $kept);
			}

			if (!$stripped) {
				return;
			}

			$query->set('svi_sv_terms', $stripped);
		}

		/**
		 * Drop the archive term clause once WP_Query has parsed it.
		 *
		 * The taxonomy and term query vars stay untouched, because
		 * `get_queried_object()` (and the layered nav widget with it) reads them.
		 * The clause itself is rebuilt in `sql()`.
		 *
		 * @param WP_Query $query Query object.
		 */
		public static function strip_parsed_clauses($query): void
		{
			if (!SVI_SV_Query::is_variation_query($query)) {
				return;
			}

			$terms = $query->get('svi_sv_terms');

			if (empty($terms) || !is_array($terms) || !isset($query->tax_query->queries)) {
				return;
			}

			$taxonomies = array();

			foreach ($terms as $clause) {
				if (isset($clause['taxonomy'])) {
					$taxonomies[] = (string) $clause['taxonomy'];
				}
			}

			if (!$taxonomies) {
				return;
			}

			foreach ($query->tax_query->queries as $key => $clause) {
				if (is_array($clause) && isset($clause['taxonomy']) && in_array((string) $clause['taxonomy'], $taxonomies, true)) {
					unset($query->tax_query->queries[$key]);
				}
			}
		}

		/**
		 * Remove the product taxonomy clauses from a tax query.
		 *
		 * @param array $tax_query Tax query.
		 * @param array $stripped  Collected clauses, by reference.
		 * @return array Remaining tax query.
		 */
		public static function strip_tax_clauses(array $tax_query, array &$stripped): array
		{
			$result = array();

			foreach ($tax_query as $key => $clause) {
				if ('relation' === $key) {
					$result[$key] = $clause;
					continue;
				}

				if (!is_array($clause)) {
					$result[$key] = $clause;
					continue;
				}

				if (isset($clause['taxonomy'], $clause['terms']) && self::is_category_taxonomy((string) $clause['taxonomy'])) {
					$clause['terms'] = (array) $clause['terms'];
					$stripped[]      = $clause;
					continue;
				}

				// Nested groups (relation + clauses).
				if (!isset($clause['taxonomy'])) {
					$nested = self::strip_tax_clauses($clause, $stripped);

					if (!empty($nested)) {
						$result[$key] = $nested;
					}

					continue;
				}

				$result[$key] = $clause;
			}

			return $result;
		}

		/**
		 * Product taxonomies that a variation inherits from its parent.
		 *
		 * @param string $taxonomy Taxonomy name.
		 * @return bool
		 */
		private static function is_category_taxonomy(string $taxonomy): bool
		{
			$inherited = array('product_cat', 'product_tag');

			if (taxonomy_exists('product_brand')) {
				$inherited[] = 'product_brand';
			}

			/**
			 * Filters the product taxonomies a variation inherits from its parent.
			 *
			 * Attribute taxonomies (`pa_*`) are never inherited: a variation
			 * carries its own attribute values in post meta.
			 *
			 * @param string[] $inherited Taxonomy names.
			 */
			$inherited = (array) apply_filters('svi_sv_inherited_taxonomies', $inherited);

			return in_array($taxonomy, $inherited, true);
		}

		/**
		 * SQL for the inherited category conditions.
		 *
		 * @param array $clauses Stripped tax clauses.
		 * @return string
		 */
		public static function sql(array $clauses): string
		{
			global $wpdb;

			$sql = '';

			foreach ($clauses as $clause) {
				$operator = isset($clause['operator']) ? strtoupper((string) $clause['operator']) : 'IN';
				$not      = 'NOT IN' === $operator ? 'NOT ' : '';

				$product_match = self::term_exists_sql($clause, "{$wpdb->posts}.ID");
				$parent_match  = self::term_exists_sql($clause, "{$wpdb->posts}.post_parent");

				if ('' === $product_match || '' === $parent_match) {
					continue;
				}

				$product_branch = "{$wpdb->posts}.post_type <> 'product_variation' AND {$not}{$product_match}";

				/**
				 * Filters the variation's own terms for an inherited taxonomy condition.
				 *
				 * PRO returns `has` (the variation manages its own terms) and
				 * `match` (its terms satisfy the clause). When present, the own
				 * terms replace the parent's instead of adding to them.
				 *
				 * @param array  $own      Empty by default.
				 * @param array  $clause   Tax clause.
				 * @param string $taxonomy Taxonomy name.
				 */
				$taxonomy = isset($clause['taxonomy']) ? (string) $clause['taxonomy'] : '';
				$own      = (array) apply_filters('svi_sv_variation_terms', array(), $clause, $taxonomy);

				if (!empty($own['has']) && !empty($own['match'])) {
					$condition = '( ( ' . $own['has'] . ' AND ' . $own['match'] . ' ) OR ( NOT ( ' . $own['has'] . ' ) AND ' . $parent_match . ' ) )';
				} else {
					$condition = $parent_match;
				}

				if ('NOT IN' === $operator) {
					$condition = 'NOT ( ' . $condition . ' )';
				}

				$variation_branch = "{$wpdb->posts}.post_type = 'product_variation' AND {$condition}";

				$sql .= " AND ( ( {$product_branch} ) OR ( {$variation_branch} ) )";
			}

			return $sql;
		}

		/**
		 * EXISTS() that tells whether an object carries the terms of a clause.
		 *
		 * @param array  $clause        Tax clause.
		 * @param string $object_column Column holding the object ID.
		 * @return string SQL, or '' when the clause cannot be resolved.
		 */
		public static function term_exists_sql(array $clause, string $object_column): string
		{
			global $wpdb;

			$taxonomy = isset($clause['taxonomy']) ? (string) $clause['taxonomy'] : '';

			if ('' === $taxonomy || empty($clause['terms'])) {
				return '';
			}

			$by_id         = in_array(isset($clause['field']) ? $clause['field'] : 'term_id', array('term_id', 'id'), true);
			$term_ids      = self::term_ids($taxonomy, (array) $clause['terms'], $by_id);
			$include_child = !isset($clause['include_children']) || $clause['include_children'];

			if (!$term_ids) {
				return '';
			}

			$in       = implode(',', array_map('absint', $term_ids));
			$term_sql = " svi_sv_tt.term_id IN ({$in})";

			if ($include_child) {
				$term_sql .= " OR svi_sv_tt.parent IN ({$in})";
			}

			return 'EXISTS ( SELECT 1 FROM ' . $wpdb->term_relationships . ' svi_sv_tr'
				. ' INNER JOIN ' . $wpdb->term_taxonomy . ' svi_sv_tt ON svi_sv_tt.term_taxonomy_id = svi_sv_tr.term_taxonomy_id'
				. ' WHERE svi_sv_tr.object_id = ' . $object_column
				. ' AND svi_sv_tt.taxonomy = ' . $wpdb->prepare('%s', $taxonomy)
				. " AND ( {$term_sql} ) )";
		}

		/**
		 * Term IDs of a stripped clause.
		 *
		 * @param string $taxonomy Taxonomy.
		 * @param array  $terms    Terms.
		 * @param bool   $by_id    True when the terms are IDs.
		 * @return int[]
		 */
		private static function term_ids(string $taxonomy, array $terms, bool $by_id): array
		{
			if (!taxonomy_exists($taxonomy)) {
				return array();
			}

			if ($by_id) {
				return array_filter(array_map('absint', $terms));
			}

			$ids = array();

			foreach ($terms as $term) {
				$object = get_term_by('slug', (string) $term, $taxonomy);

				if ($object && !is_wp_error($object)) {
					$ids[] = (int) $object->term_id;
				}
			}

			return $ids;
		}
	}
}
