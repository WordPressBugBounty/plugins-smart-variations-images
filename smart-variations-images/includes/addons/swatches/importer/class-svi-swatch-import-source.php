<?php
/**
 * Base class for a "swatches from another plugin" import source.
 *
 * A source turns the data another swatches plugin left in the database into a
 * flat list of import items. Detection, counting and batching live here so an
 * adapter only has to describe its own storage format.
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Swatch_Import_Source')) {
	/**
	 * Every adapter extends this class and registers itself on
	 * `svi_swatch_import_sources`.
	 */
	abstract class SVI_Swatch_Import_Source
	{
		/** Plugin folder is present and active. */
		const STATUS_ACTIVE = 'active';

		/** Plugin folder is present but the plugin is not active. */
		const STATUS_INACTIVE = 'inactive';

		/** Plugin is gone, but its settings are still in the database. */
		const STATUS_DATA = 'data';

		/** Nothing found. */
		const STATUS_NONE = 'none';

		/**
		 * Cached item list.
		 *
		 * @var array[]|null
		 */
		private $items = null;

		/**
		 * Machine name used by the UI and WP-CLI.
		 *
		 * @return string
		 */
		abstract public function get_slug(): string;

		/**
		 * Factual plugin name shown in the UI.
		 *
		 * @return string
		 */
		abstract public function get_label(): string;

		/**
		 * Plugin files that identify the source (relative to the plugins dir).
		 *
		 * @return string[]
		 */
		abstract protected function get_plugin_files(): array;

		/**
		 * Source attribute type => SVI swatch type.
		 *
		 * @return array<string, string>
		 */
		abstract public function get_attribute_type_map(): array;

		/**
		 * Term meta keys this source owns.
		 *
		 * @return string[]
		 */
		abstract public function get_term_meta_keys(): array;

		/**
		 * Build the ordered item list: attributes, terms, overrides, settings.
		 *
		 * @return array[]
		 */
		abstract protected function build_items(): array;

		/**
		 * Per-product overrides left by this source.
		 *
		 * @return int
		 */
		protected function count_products(): int
		{
			return 0;
		}

		/**
		 * Settings options left by this source, for the detection summary.
		 *
		 * @return string[]
		 */
		protected function get_option_evidence(): array
		{
			return [];
		}

		/**
		 * Is the source plugin installed (active or not)?
		 *
		 * @return bool
		 */
		protected function plugin_installed(): bool
		{
			if (!function_exists('get_plugins')) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugins = get_plugins();

			foreach ($this->get_plugin_files() as $file) {
				if (isset($plugins[$file])) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Does any term of a taxonomy carry one of the given meta keys?
		 *
		 * @param string   $taxonomy Taxonomy name.
		 * @param string[] $keys     Meta keys.
		 * @return int Number of matching values.
		 */
		protected function count_meta_in_taxonomy(string $taxonomy, array $keys): int
		{
			global $wpdb;

			$keys = array_values(array_filter(array_map('strval', $keys)));

			if (empty($keys)) {
				return 0;
			}

			$placeholders = implode(', ', array_fill(0, count($keys), '%s'));

			$sql = $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Placeholders built above.
				"SELECT COUNT(*) FROM {$wpdb->termmeta} tm INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = tm.term_id WHERE tt.taxonomy = %s AND tm.meta_key IN ({$placeholders})",
				array_merge([$taxonomy], $keys)
			);

			return (int) $wpdb->get_var($sql); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		}

		/**
		 * Decide whether this source should own an attribute taxonomy.
		 *
		 * Two plugins can leave the same `attribute_type` values behind, so the
		 * term meta is what decides: a taxonomy with this source's values is
		 * claimed, a taxonomy with another source's values is not, and a
		 * taxonomy with no swatch values at all belongs to an installed plugin.
		 *
		 * @param string $taxonomy Taxonomy name.
		 * @return bool
		 */
		protected function claim_taxonomy(string $taxonomy): bool
		{
			$my_map = $this->get_attribute_type_map();
			$foreign_maps = [];

			foreach (SVI_Swatch_Import_Runner::get_sources() as $source) {
				if ($source->get_slug() === $this->get_slug()) {
					continue;
				}

				$foreign_maps = array_merge($foreign_maps, $source->get_attribute_type_map());
			}

			$type = $this->attribute_type_for_taxonomy($taxonomy);

			// A swatch type only this source uses (WVS "button", VSW "label") says
			// nothing ambiguous, so the attribute is this source's even after the
			// plugin is gone and its terms carry no swatch values.
			if ('' !== $type && isset($my_map[$type]) && !isset($foreign_maps[$type])) {
				return true;
			}

			$mine = $this->count_meta_in_taxonomy($taxonomy, $this->get_term_meta_keys());

			if ($mine > 0) {
				return true;
			}

			$foreign_keys = [];

			foreach (SVI_Swatch_Import_Runner::get_sources() as $source) {
				if ($source->get_slug() === $this->get_slug()) {
					continue;
				}

				$foreign_keys = array_merge($foreign_keys, $source->get_term_meta_keys());
			}

			if ($this->count_meta_in_taxonomy($taxonomy, $foreign_keys) > 0) {
				return false;
			}

			// Two plugins can map the same type (both WVS and WCBoost know "button")
			// and a label swatch stores no term meta at all, so there is nothing to
			// tell them apart. An installed plugin keeps those attributes: claiming
			// one twice only repeats the same swatch type, while skipping it would
			// leave the attribute as a plain dropdown after the import.
			return $this->plugin_installed();
		}

		/**
		 * Public wrapper for the installed check.
		 *
		 * @return bool
		 */
		public function is_plugin_installed(): bool
		{
			return $this->plugin_installed();
		}

		/**
		 * Attribute type stored in WooCommerce for one taxonomy.
		 *
		 * @param string $taxonomy Taxonomy name.
		 * @return string
		 */
		protected function attribute_type_for_taxonomy(string $taxonomy): string
		{
			static $map = null;

			if (null === $map) {
				$map = [];

				foreach ((array) wc_get_attribute_taxonomies() as $attribute) {
					$map[wc_attribute_taxonomy_name($attribute->attribute_name)] = isset($attribute->attribute_type) ? (string) $attribute->attribute_type : '';
				}
			}

			return $map[$taxonomy] ?? '';
		}

		/**
		 * Count the attributes this source owns.
		 *
		 * @return int
		 */
		protected function count_claimed_attributes(): int
		{
			$map = $this->get_attribute_type_map();
			$count = 0;
			$taxonomies = wc_get_attribute_taxonomies();

			foreach ((array) $taxonomies as $attribute) {
				$type = isset($attribute->attribute_type) ? (string) $attribute->attribute_type : '';

				if (!isset($map[$type])) {
					continue;
				}

				$taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);

				if ($this->claim_taxonomy($taxonomy)) {
					$count++;
				}
			}

			return $count;
		}

		/**
		 * Count every term meta value this source owns.
		 *
		 * @return int
		 */
		protected function count_own_term_meta(): int
		{
			global $wpdb;

			$keys = array_values(array_filter(array_map('strval', $this->get_term_meta_keys())));

			if (empty($keys)) {
				return 0;
			}

			$placeholders = implode(', ', array_fill(0, count($keys), '%s'));

			return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Placeholders built above.
					"SELECT COUNT(*) FROM {$wpdb->termmeta} WHERE meta_key IN ({$placeholders})",
					$keys
				)
			);
		}

		/**
		 * Cheap check used to decide whether the screen should exist at all.
		 *
		 * Avoids the per-taxonomy queries of `detect()` on every settings page load.
		 *
		 * @return bool
		 */
		public function has_candidates(): bool
		{
			return $this->plugin_installed()
				|| $this->count_own_term_meta() > 0
				|| $this->count_products() > 0
				|| !empty($this->get_option_evidence());
		}

		/**
		 * Quick database evidence that the source left data behind.
		 *
		 * @return array{attributes:int,terms:int,products:int,options:string[]}
		 */
		protected function get_evidence(): array
		{
			return [
				'attributes' => $this->count_claimed_attributes(),
				'terms' => $this->count_own_term_meta(),
				'products' => $this->count_products(),
				'options' => $this->get_option_evidence(),
			];
		}

		/**
		 * Build attribute and term items with one reader per term.
		 *
		 * The reader receives the term ID and the source attribute type and
		 * returns `['colors'=>[], 'image'=>int, 'label'=>string]`, or an array
		 * with `unsupported` + `reason`, or null to skip the term.
		 *
		 * @param callable $reader Term reader.
		 * @return array[]
		 */
		protected function build_attribute_term_items(callable $reader): array
		{
			$items = [];
			$map = $this->get_attribute_type_map();

			foreach ((array) wc_get_attribute_taxonomies() as $attribute) {
				$type = isset($attribute->attribute_type) ? (string) $attribute->attribute_type : '';

				if (!isset($map[$type])) {
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
						'type' => $map[$type],
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

					$value = $reader($term_id, $type);

					if (null === $value) {
						continue;
					}

					$item = [
						'kind' => 'term',
						'part' => 'terms',
						'title' => sprintf('%s → %s', $taxonomy, $term->name),
					];

					if (!empty($value['unsupported'])) {
						$item['unsupported'] = true;
						$item['reason'] = $value['reason'] ?? __('No SVI equivalent.', 'smart-variations-images');
						$items[] = $item;
						continue;
					}

					$item['payload'] = [
						'term_id' => $term_id,
						'colors' => (array) ($value['colors'] ?? []),
						'image' => (int) ($value['image'] ?? 0),
						'label' => (string) ($value['label'] ?? ''),
					];

					if (!empty($value['warning'])) {
						$item['warning'] = $value['warning'];
					}

					$items[] = $item;
				}
			}

			return $items;
		}

		/**
		 * Resolve an attachment value that may be an ID or a URL.
		 *
		 * @param mixed $value Stored value.
		 * @return int
		 */
		protected function resolve_attachment($value): int
		{
			$value = is_scalar($value) ? trim((string) $value) : '';

			if ('' === $value) {
				return 0;
			}

			return ctype_digit($value) ? absint($value) : (int) attachment_url_to_postid($value);
		}

		/**
		 * Importable parts, with their human readable labels.
		 *
		 * @return array<string, string>
		 */
		public static function get_part_labels(): array
		{
			return [
				'attributes' => __('Attribute swatch types', 'smart-variations-images'),
				'terms' => __('Term colours, images and labels', 'smart-variations-images'),
				'overrides' => __('Per-product overrides', 'smart-variations-images'),
				'settings' => __('Global swatch settings', 'smart-variations-images'),
			];
		}

		/**
		 * Full item list, built once per request.
		 *
		 * @return array[]
		 */
		public function get_items(): array
		{
			if (null === $this->items) {
				$this->items = array_values($this->build_items());
			}

			return $this->items;
		}

		/**
		 * Report whether the plugin, its data, or both are present.
		 *
		 * @return array{slug:string,label:string,status:string,plugin_state:string,evidence:array}
		 */
		public function detect(): array
		{
			$plugin_state = 'deleted';
			$active = false;
			$installed = false;

			if (!function_exists('get_plugins')) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugins = get_plugins();

			foreach ($this->get_plugin_files() as $file) {
				if (!isset($plugins[$file])) {
					continue;
				}

				$installed = true;

				if (is_plugin_active($file)) {
					$active = true;
				}
			}

			if ($installed) {
				$plugin_state = $active ? 'active' : 'inactive';
			}

			$evidence = $this->get_evidence();
			$has_data = $evidence['attributes'] > 0 || $evidence['terms'] > 0 || $evidence['products'] > 0 || !empty($evidence['options']);

			if ($active) {
				$status = self::STATUS_ACTIVE;
			} elseif ($installed) {
				$status = self::STATUS_INACTIVE;
			} elseif ($has_data) {
				$status = self::STATUS_DATA;
			} else {
				$status = self::STATUS_NONE;
			}

			return [
				'slug' => $this->get_slug(),
				'label' => $this->get_label(),
				'status' => $status,
				'plugin_state' => $plugin_state,
				'evidence' => $evidence,
			];
		}

		/**
		 * Count what can be imported, plus what cannot.
		 *
		 * @return array{parts:array<string,int>,total:int,unsupported:string[]}
		 */
		public function scan(): array
		{
			$parts = array_fill_keys(array_keys(self::get_part_labels()), 0);
			$unsupported = [];
			$total = 0;

			foreach ($this->get_items() as $item) {
				$part = isset($item['part']) ? (string) $item['part'] : '';

				if (!isset($parts[$part])) {
					continue;
				}

				$total++;

				if (!empty($item['unsupported'])) {
					$unsupported[] = isset($item['title']) ? (string) $item['title'] : $part;
					continue;
				}

				$parts[$part]++;
			}

			return [
				'parts' => $parts,
				'total' => $total,
				'unsupported' => array_values(array_unique($unsupported)),
			];
		}
	}
}
