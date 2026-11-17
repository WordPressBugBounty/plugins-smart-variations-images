<?php
/**
 * Per-product swatch overrides.
 *
 * Lets a merchant change the swatch type and the colour, image and label of
 * each value for a single product, on top of the global definitions. Works for
 * global attributes (pa_*) and for product-level custom attributes alike.
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Swatches_Product')) {
	/**
	 * Product-level swatch overrides: admin UI, storage and reading.
	 */
	class SVI_Swatches_Product
	{

		/**
		 * Post meta key holding the overrides.
		 */
		const META_KEY = '_svi_swatch_overrides';

		/**
		 * Per-request cache of cleaned overrides.
		 *
		 * @var array<int, array>
		 */
		private static array $cache = [];

		/**
		 * Register hooks.
		 */
		public static function init(): void
		{
			if (!is_admin()) {
				return;
			}

			add_filter('woocommerce_product_data_tabs', [__CLASS__, 'add_tab']);
			add_action('woocommerce_product_data_panels', [__CLASS__, 'render_panel']);
			add_action('woocommerce_process_product_meta', [__CLASS__, 'save'], 20);
			add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue']);
		}

		/**
		 * Add the product data tab.
		 *
		 * @param array $tabs Existing tabs.
		 * @return array
		 */
		public static function add_tab(array $tabs): array
		{
			$tabs['svi_swatches'] = [
				'label' => __('SVI Swatches', 'smart-variations-images'),
				'target' => 'svi_swatches_product_data',
				'class' => ['show_if_variable'],
				'priority' => 62,
			];

			return $tabs;
		}

		/**
		 * Read the stored overrides for a product.
		 *
		 * @param int $product_id Product ID.
		 * @return array<string, array{type:string,terms:array<string, array{color:string,color2:string,color3:string,color4:string,image:int,label:string}>}>
		 */
		public static function get_overrides(int $product_id): array
		{
			$product_id = absint($product_id);

			if ($product_id <= 0) {
				return [];
			}

			if (isset(self::$cache[ $product_id ])) {
				return self::$cache[ $product_id ];
			}

			$raw = get_post_meta($product_id, self::META_KEY, true);

			if (!is_array($raw)) {
				return [];
			}

			$clean = [];

			if (empty($raw)) {
				self::$cache[ $product_id ] = [];

				return [];
			}

			foreach ($raw as $attribute => $data) {
				if (!is_string($attribute) || '' === $attribute || !is_array($data)) {
					continue;
				}

				$type = isset($data['type']) && is_string($data['type']) ? $data['type'] : '';

				$terms = [];

				if (isset($data['terms']) && is_array($data['terms'])) {
					foreach ($data['terms'] as $slug => $values) {
						if (!is_string($slug) || '' === $slug || !is_array($values)) {
							continue;
						}

						$terms[ $slug ] = [
							'color' => self::clean_color($values['color'] ?? ''),
							'color2' => self::clean_color($values['color2'] ?? ''),
							'color3' => self::clean_color($values['color3'] ?? ''),
							'color4' => self::clean_color($values['color4'] ?? ''),
							'image' => absint($values['image'] ?? 0),
							'label' => self::clean_text($values['label'] ?? ''),
						];
					}
				}

				// Attribute keys differ in case between the product object ("Print")
				// and the variation key ("print"), so they are stored lower case.
				$clean[ strtolower( $attribute ) ] = [
					'type' => self::clean_type($type),
					'terms' => $terms,
				];
			}

			self::$cache[ $product_id ] = $clean;

			return $clean;
		}

		/**
		 * Forget the cached overrides after a save.
		 *
		 * @param int $product_id Product ID.
		 */
		public static function flush_cache(int $product_id): void
		{
			unset(self::$cache[ absint($product_id) ]);
		}

		/**
		 * Type configured for one attribute on one product.
		 *
		 * @param int    $product_id Product ID.
		 * @param string $attribute  Attribute key (pa_* or custom name).
		 * @return string Type, or an empty string when there is no override.
		 */
		public static function get_type_override(int $product_id, string $attribute): string
		{
			$overrides = self::get_overrides($product_id);

			return $overrides[ strtolower( $attribute ) ]['type'] ?? '';
		}

		/**
		 * Per-value override for one attribute value.
		 *
		 * @param int    $product_id Product ID.
		 * @param string $attribute  Attribute key.
		 * @param string $slug       Value slug.
		 * @return array{color:string,color2:string,color3:string,color4:string,image:int,label:string}
		 */
		public static function get_term_override(int $product_id, string $attribute, string $slug): array
		{
			$overrides = self::get_overrides($product_id);

			return $overrides[ strtolower( $attribute ) ]['terms'][ $slug ] ?? [
				'color' => '',
				'color2' => '',
				'color3' => '',
				'color4' => '',
				'image' => 0,
				'label' => '',
			];
		}

		/**
		 * Values of every variation attribute of a product, ready for the UI.
		 *
		 * @param WC_Product $product Product.
		 * @return array<int, array{key:string,label:string,is_taxonomy:bool,type:string,terms:array}>
		 */
		public static function get_attribute_rows(WC_Product $product): array
		{
			$rows      = [];
			$overrides = self::get_overrides($product->get_id());

			foreach ($product->get_attributes() as $key => $attribute) {
				if (!$attribute instanceof WC_Product_Attribute || !$attribute->get_variation()) {
					continue;
				}

				$name  = $attribute->get_name();
				$terms = [];

				if ($attribute->is_taxonomy()) {
					$taxonomy_terms = wc_get_product_terms($product->get_id(), $name, ['fields' => 'all']);

					foreach ($taxonomy_terms as $term) {
						$terms[] = [
							'slug' => $term->slug,
							'name' => $term->name,
						];
					}
				} else {
					foreach ($attribute->get_options() as $option) {
						$terms[] = [
							'slug' => sanitize_title($option),
							'name' => $option,
						];
					}
				}

				$key_lc = strtolower( $name );

				foreach ($terms as $index => $term) {
					$override = $overrides[ $key_lc ]['terms'][ $term['slug'] ] ?? [
						'color' => '',
						'color2' => '',
						'color3' => '',
						'color4' => '',
						'image' => 0,
						'label' => '',
					];

					$terms[ $index ]['override'] = $override;
				}

				$rows[] = [
					'key' => $name,
					'label' => $attribute->is_taxonomy()
						? wc_attribute_label($name, $product)
						: $name,
					'is_taxonomy' => $attribute->is_taxonomy(),
					'type' => $overrides[ $key_lc ]['type'] ?? '',
					'terms' => $terms,
				];
			}

			return $rows;
		}

		/**
		 * Render the product data panel.
		 */
		public static function render_panel(): void
		{
			global $post, $thepostid;

			$product_id = $post instanceof WP_Post ? (int) $post->ID : (int) $thepostid;
			$product    = $product_id ? wc_get_product($product_id) : false;

			echo '<div id="svi_swatches_product_data" class="panel woocommerce_options_panel">';

			if (!$product instanceof WC_Product) {
				echo '<p>' . esc_html__('Save the product to configure swatches.', 'smart-variations-images') . '</p></div>';

				return;
			}

			$rows = self::get_attribute_rows($product);

			if (empty($rows)) {
				echo '<p>' . esc_html__('Add variation attributes to configure per-product swatches.', 'smart-variations-images') . '</p></div>';

				return;
			}

			wp_nonce_field('svi_swatch_overrides', 'svi_swatch_overrides_nonce');

			echo '<p class="form-field">'
				. '<strong>' . esc_html__('Per-product overrides', 'smart-variations-images') . '</strong><br>'
				. '<span class="description">'
				. esc_html__('Leave a field empty to inherit the global value. Overrides only apply to this product.', 'smart-variations-images')
				. '<br>'
				. esc_html__('Up to four colors: two split the swatch in half, three or four become stripes.', 'smart-variations-images')
				. '</span></p>';

			$types = [
				'' => __('Inherit global type', 'smart-variations-images'),
				'color' => __('Color', 'smart-variations-images'),
				'image' => __('Image', 'smart-variations-images'),
				'text' => __('Text / Button', 'smart-variations-images'),
				'radio' => __('Radio buttons', 'smart-variations-images'),
			];

			foreach ($rows as $row) {
				printf(
					'<div class="svi-swatch-product-attribute"><h4>%s%s</h4>',
					esc_html($row['label']),
					$row['is_taxonomy'] ? '' : ' <em>(' . esc_html__('custom attribute', 'smart-variations-images') . ')</em>'
				);

				printf(
					'<p class="form-field"><label>%s</label><select name="svi_swatch_type[%s]">',
					esc_html__('Swatch type', 'smart-variations-images'),
					esc_attr($row['key'])
				);

				foreach ($types as $value => $label) {
					printf(
						'<option value="%s"%s>%s</option>',
						esc_attr($value),
						selected($row['type'], $value, false),
						esc_html($label)
					);
				}

				echo '</select></p>';

				// Six columns do not fit narrow screens: let the table scroll on its own.
				echo '<div class="svi-swatch-product-table-wrap">';

				echo '<table class="widefat striped svi-swatch-product-table"><thead><tr>'
					. '<th>' . esc_html__('Value', 'smart-variations-images') . '</th>'
					. '<th>' . esc_html__('Color', 'smart-variations-images') . '</th>'
					. '<th>' . esc_html__('Second color', 'smart-variations-images') . '</th>'
					. '<th>' . esc_html__('Third color', 'smart-variations-images') . '</th>'
					. '<th>' . esc_html__('Fourth color', 'smart-variations-images') . '</th>'
					. '<th>' . esc_html__('Image', 'smart-variations-images') . '</th>'
					. '<th>' . esc_html__('Label', 'smart-variations-images') . '</th>'
					. '</tr></thead><tbody>';

				foreach ($row['terms'] as $term) {
					$base = sprintf('svi_swatch_terms[%s][%s]', $row['key'], $term['slug']);
					$image_url = $term['override']['image'] ? wp_get_attachment_image_url($term['override']['image'], 'thumbnail') : '';

					printf(
						'<tr><td><strong>%s</strong><br><code>%s</code></td>'
						. '<td><input type="text" class="svi-swatch-color-field" name="%s[color]" value="%s" placeholder="#ffffff"></td>'
						. '<td><input type="text" class="svi-swatch-color-field" name="%s[color2]" value="%s" placeholder="#ffffff"></td>'
						. '<td><input type="text" class="svi-swatch-color-field" name="%s[color3]" value="%s" placeholder="#ffffff"></td>'
						. '<td><input type="text" class="svi-swatch-color-field" name="%s[color4]" value="%s" placeholder="#ffffff"></td>'
						. '<td><div class="svi-swatch-image-cell">'
						. '<input type="hidden" class="svi-swatch-image-id" name="%s[image]" value="%d">'
						. '<img class="svi-swatch-image-preview" src="%s" alt="" width="28" height="28"%s>'
						. '<button type="button" class="button svi-swatch-image-pick">%s</button>'
						. '<button type="button" class="button-link svi-swatch-image-clear">%s</button>'
						. '</div></td>'
						. '<td><input type="text" name="%s[label]" value="%s" placeholder="%s"></td></tr>',
						esc_html($term['name']),
						esc_html($term['slug']),
						esc_attr($base),
						esc_attr($term['override']['color']),
						esc_attr($base),
						esc_attr($term['override']['color2']),
						esc_attr($base),
						esc_attr($term['override']['color3']),
						esc_attr($base),
						esc_attr($term['override']['color4']),
						esc_attr($base),
						(int) $term['override']['image'],
						esc_url($image_url ? $image_url : wc_placeholder_img_src()),
						$image_url ? '' : ' style="opacity:.4"',
						esc_html__('Select image', 'smart-variations-images'),
						esc_html__('Clear', 'smart-variations-images'),
						esc_attr($base),
						esc_attr($term['override']['label']),
						esc_attr($term['name'])
					);
				}

				echo '</tbody></table></div></div>';
			}

			echo '</div>';
		}

		/**
		 * Persist the overrides.
		 *
		 * @param int $product_id Product ID.
		 */
		public static function save($product_id): void
		{
			$product_id = absint($product_id);

			$nonce = isset($_POST['svi_swatch_overrides_nonce'])
				? sanitize_text_field(wp_unslash($_POST['svi_swatch_overrides_nonce']))
				: '';

			if (!$nonce || !wp_verify_nonce($nonce, 'svi_swatch_overrides')) {
				return;
			}

			if (!current_user_can('edit_product', $product_id)) {
				return;
			}

			// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitised field by field below.
			$raw_types = isset($_POST['svi_swatch_type']) && is_array($_POST['svi_swatch_type'])
				? wp_unslash($_POST['svi_swatch_type'])
				: [];
			$raw_terms = isset($_POST['svi_swatch_terms']) && is_array($_POST['svi_swatch_terms'])
				? wp_unslash($_POST['svi_swatch_terms'])
				: [];
			// phpcs:enable

			$overrides = [];

			foreach ($raw_types as $attribute => $type) {
				if (!is_string($attribute) || '' === $attribute) {
					continue;
				}

				$clean_type = self::clean_type((string) $type);
				$terms      = [];

				if (isset($raw_terms[ $attribute ]) && is_array($raw_terms[ $attribute ])) {
					foreach ($raw_terms[ $attribute ] as $slug => $values) {
						if (!is_string($slug) || '' === $slug || !is_array($values)) {
							continue;
						}

						$entry = [
							'color' => self::clean_color($values['color'] ?? ''),
							'color2' => self::clean_color($values['color2'] ?? ''),
							'color3' => self::clean_color($values['color3'] ?? ''),
							'color4' => self::clean_color($values['color4'] ?? ''),
							'image' => absint($values['image'] ?? 0),
							'label' => self::clean_text($values['label'] ?? ''),
						];

						if ('' !== $entry['color'] || '' !== $entry['color2'] || '' !== $entry['color3'] || '' !== $entry['color4'] || $entry['image'] > 0 || '' !== $entry['label']) {
							$terms[ $slug ] = $entry;
						}
					}
				}

				if ('' === $clean_type && empty($terms)) {
					continue;
				}

				$overrides[ $attribute ] = [
					'type' => $clean_type,
					'terms' => $terms,
				];
			}

			if (empty($overrides)) {
				delete_post_meta($product_id, self::META_KEY);

				return;
			}

			update_post_meta($product_id, self::META_KEY, $overrides);
			self::flush_cache($product_id);
		}

		/**
		 * Admin assets for the panel.
		 *
		 * @param string $hook Current admin page.
		 */
		public static function enqueue($hook): void
		{
			if (!in_array($hook, ['post.php', 'post-new.php'], true)) {
				return;
			}

			$screen = get_current_screen();

			if (!$screen || 'product' !== $screen->post_type) {
				return;
			}

			wp_enqueue_media();
			wp_enqueue_style('wp-color-picker');
			wp_enqueue_style(
				'svi-swatches-admin',
				plugin_dir_url(__FILE__) . 'public/assets/css/swatches-admin' . SMART_SCRIPT_DEBUG . '.css',
				[],
				'1.3.6'
			);
			wp_enqueue_script(
				'svi-swatches-product-admin',
				plugin_dir_url(__FILE__) . 'public/assets/js/admin-swatches' . SMART_SCRIPT_DEBUG . '.js',
				['jquery', 'wp-color-picker'],
				'1.3.6',
				true
			);
		}

		/**
		 * Normalise a swatch type.
		 *
		 * @param string $type Raw type.
		 * @return string
		 */
		public static function clean_type(string $type): string
		{
			$type = sanitize_key($type);

			return in_array($type, ['color', 'image', 'text', 'radio'], true) ? $type : '';
		}

		/**
		 * Normalise a colour.
		 *
		 * @param mixed $color Raw colour.
		 * @return string
		 */
		private static function clean_color($color): string
		{
			if (!is_string($color)) {
				return '';
			}

			$color = trim($color);

			if ('' === $color) {
				return '';
			}

			if ('#' !== $color[0]) {
				$color = '#' . $color;
			}

			$clean = sanitize_hex_color($color);

			return $clean ? $clean : '';
		}

		/**
		 * Normalise a label.
		 *
		 * @param mixed $label Raw label.
		 * @return string
		 */
		private static function clean_text($label): string
		{
			return is_string($label) ? sanitize_text_field($label) : '';
		}
	}
}
