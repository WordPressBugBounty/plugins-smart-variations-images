<?php
/**
 * Swatches Core Logic
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Main Logic for Swatches.
 */
class SVI_Swatches
{

	/**
	 * Singleton instance.
	 *
	 * @var SVI_Swatches|null
	 */
	private static ?SVI_Swatches $instance = null;

	/**
	 * Per-request cache of variation availability maps.
	 *
	 * @var array<int, array>
	 */
	private static array $variation_map_cache = [];

	/**
	 * Per-request cache of automatic swatch images.
	 *
	 * @var array<string, int>
	 */
	private static array $auto_image_cache = [];

	/**
	 * Format hint used to detect our own export files.
	 */
	private const EXPORT_FORMAT = 'svi-swatches';

	/**
	 * Get singleton instance.
	 *
	 * @return SVI_Swatches
	 */
	public static function instance(): SVI_Swatches
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize core logic.
	 */
	public function init(): void
	{
		// Frontend Integration Hooks (Critical for JS options).
		add_filter('svi_gallery_config', [$this, 'add_frontend_config'], 10, 2);
		add_filter('svi_frontend_options', [$this, 'add_frontend_config'], 10, 2);

		// Enqueue Assets.
		add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

		// Backend Fields.
		if (is_admin()) {
			// Attribute Fields (Global Type Definition).
			add_action('woocommerce_after_add_attribute_fields', [$this, 'add_attribute_fields']);
			add_action('woocommerce_after_edit_attribute_fields', [$this, 'edit_attribute_fields']);

			// Save Attribute Fields.
			// woocommerce_attribute_added passes ($id, $data)
			add_action('woocommerce_attribute_added', [$this, 'save_attribute_fields'], 10, 2);
			// woocommerce_attribute_updated passes ($id, $data, $old_slug)
			add_action('woocommerce_attribute_updated', [$this, 'save_attribute_fields'], 10, 3);

			// Term Fields (Values).
			add_action('admin_init', [$this, 'init_term_fields']);
			add_action('admin_init', [$this, 'maybe_convert_legacy_data']);
			add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

			// Swatch definitions export/import.
			add_action('admin_post_svi_swatches_export', [$this, 'export_definitions']);
			add_action('admin_post_svi_swatches_import', [$this, 'import_definitions']);
			add_action('admin_post_svi_swatches_convert', [$this, 'convert_legacy_request']);
		}
	}

	/**
	 * Add configuration to SVI Frontend.
	 *
	 * @param array $config     Existing config.
	 * @param int   $product_id Product the config belongs to, when known.
	 * @return array Modified config.
	 */
	public function add_frontend_config(array $config, $product_id = 0): array
	{
		// This callback serves both filters: svi_frontend_options passes an ID,
		// svi_gallery_config passes the product object.
		if ($product_id instanceof WC_Product) {
			$product_id = $product_id->get_id();
		} elseif (is_object($product_id) && isset($product_id->ID)) {
			$product_id = (int) $product_id->ID;
		}

		$product_id = absint($product_id);

		$tooltip_mode = svi_get_option('tooltip_mode', 'text');

		if (!in_array($tooltip_mode, ['text', 'image', 'none'], true)) {
			$tooltip_mode = 'text';
		}

		// The older toggle stays authoritative when it is switched off.
		if (!svi_get_option('enable_tooltips', true)) {
			$tooltip_mode = 'none';
		}

		$config['addons']['swatches'] = [
			'enabled' => (bool) svi_get_option('swatches_enabled', true),
			'shape' => svi_get_option('swatch_shape', 'round'),
			'size' => [
				'w' => (int) svi_get_option('swatch_size_w', 30),
				'h' => (int) svi_get_option('swatch_size_h', 30),
			],
			'tooltips' => 'none' !== $tooltip_mode,
			'tooltipMode' => $tooltip_mode,
			'large_preview' => (bool) svi_get_option('large_preview', true),
			'oosBehavior' => svi_get_option('oos_behavior', 'blur'),
		];

		if ($product_id > 0) {
			$availability = self::get_variation_map($product_id);

			if (!empty($availability['variations'])) {
				$config['addons']['swatches']['availability'] = $availability;
			}
		}

		return $config;
	}

	/**
	 * Resolve the swatch type to use for one attribute of one product.
	 *
	 * Order: per-product override, per-attribute setting, global default,
	 * then no swatches (plain dropdown).
	 *
	 * @param int    $product_id Product ID.
	 * @param string $attribute  Attribute key (pa_* or custom name).
	 * @return string Type, or an empty string for a dropdown.
	 */
	public function resolve_swatch_type($product_id, $attribute): string
	{
		$product_id = absint($product_id);
		$attribute  = (string) $attribute;

		$override = SVI_Swatches_Product::get_type_override($product_id, $attribute);

		if ('' !== $override) {
			return $override;
		}

		$attribute_id    = $this->get_attribute_id_by_taxonomy($attribute);
		$attribute_type  = $attribute_id ? (string) $this->get_attribute_swatch_type($attribute_id) : '';

		if ('' !== $attribute_type) {
			return $attribute_type;
		}

		// Legacy stores kept the swatch kind in WooCommerce's attribute type.
		// Reading it here means an upgraded store shows swatches on the very
		// first request, before any conversion runs.
		$legacy_type = $attribute_id ? $this->get_legacy_attribute_type($attribute_id) : '';

		if ('' !== $legacy_type) {
			return $legacy_type;
		}

		$default = svi_get_option('swatch_type_default', 'none');

		if (in_array($default, ['color', 'image', 'text', 'radio'], true)) {
			return $default;
		}

		return '';
	}

	/**
	 * Resolve the visual values for one attribute value of one product.
	 *
	 * Per-value overrides win slot by slot over the four term colours; anything
	 * not overridden falls back to the stored term meta (either key family).
	 *
	 * @param int    $product_id Product ID.
	 * @param string $attribute  Attribute key.
	 * @param string $slug       Value slug.
	 * @param int    $term_id    Term ID, or 0 for custom attributes.
	 * @return array{colors:string[],image:int,label:string}
	 */
	public function resolve_swatch_values($product_id, $attribute, $slug, $term_id = 0): array
	{
		$values = [
			'colors' => [],
			'image' => 0,
			'label' => '',
		];

		if ($term_id > 0) {
			$stored = SVI_Swatch_Values::get($term_id);

			$values['colors'] = $stored['colors'];
			$values['image'] = $stored['image'];
			$values['label'] = $stored['label'];
		}

		$override = SVI_Swatches_Product::get_term_override(absint($product_id), (string) $attribute, (string) $slug);

		$slots = [
			$override['color'] ?? '',
			$override['color2'] ?? '',
			$override['color3'] ?? '',
			$override['color4'] ?? '',
		];

		if ('' !== $slots[0] || '' !== $slots[1] || '' !== $slots[2] || '' !== $slots[3]) {
			$colors = [];

			foreach ($slots as $index => $slot) {
				if ('' !== $slot) {
					$colors[] = $slot;
				} elseif (!empty($values['colors'][ $index ])) {
					$colors[] = $values['colors'][ $index ];
				}
			}

			$values['colors'] = SVI_Swatch_Values::sanitize_colors($colors);
		}

		if ($override['image'] > 0) {
			$values['image'] = $override['image'];
		}

		if ('' !== $override['label']) {
			$values['label'] = $override['label'];
		}

		return $values;
	}

	/**
	 * Find a fallback image for an attribute value.
	 *
	 * Used by the automatic image mode: the first image of the first variation
	 * that uses the value becomes its swatch.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $attribute  Attribute key.
	 * @param string $slug       Value slug.
	 * @return int Attachment ID, or 0.
	 */
	public function get_auto_image($product_id, $attribute, $slug): int
	{
		$product_id = absint($product_id);
		$attribute  = (string) $attribute;
		$slug       = (string) $slug;

		if ($product_id <= 0 || '' === $attribute || '' === $slug) {
			return 0;
		}

		$cache_key = $product_id . '|' . $attribute . '|' . $slug;

		if (isset(self::$auto_image_cache[$cache_key])) {
			return self::$auto_image_cache[$cache_key];
		}

		$image = 0;
		$product = wc_get_product($product_id);

		if ($product instanceof WC_Product && $product->is_type('variable')) {
			$key = 0 === strpos($attribute, 'attribute_') ? substr($attribute, 10) : $attribute;

			$galleries = $product->get_meta('_svi_galleries', true);

			if (is_array($galleries) && !empty($galleries['entries'])) {
				foreach ($galleries['entries'] as $entry) {
					if ('variation' !== ($entry['type'] ?? '') || empty($entry['variation_id'])) {
						continue;
					}

					$variation = wc_get_product((int) $entry['variation_id']);

					if (!$variation instanceof WC_Product) {
						continue;
					}

					$attributes = $variation->get_attributes();

					if (($attributes[ $key ] ?? '') !== $slug) {
						continue;
					}

					foreach (($entry['media'] ?? []) as $media) {
						if ('image' === ($media['kind'] ?? '') && !empty($media['id'])) {
							$image = (int) $media['id'];
							break 2;
						}
					}
				}
			}

			if (!$image) {
				foreach ($product->get_children() as $child_id) {
					$variation = wc_get_product($child_id);

					if (!$variation instanceof WC_Product) {
						continue;
					}

					$attributes = $variation->get_attributes();

					if (($attributes[ $key ] ?? '') === $slug && $variation->get_image_id()) {
						$image = (int) $variation->get_image_id();
						break;
					}
				}
			}
		}

		self::$auto_image_cache[$cache_key] = $image;

		return $image;
	}

	/**
	 * Build a compact variation map used by the swatch availability engine.
	 *
	 * Knowing which combinations exist and which of them are in stock is what
	 * lets swatches stay correct on products above WooCommerce's AJAX variation
	 * threshold, where WooCommerce stops refreshing the attribute selects.
	 *
	 * @param int $product_id Product ID.
	 * @return array{attributes:string[],variations:array<int,array{a:array<string,string>,stock:bool,buy:bool}>}
	 */
	public static function get_variation_map($product_id): array
	{
		$product_id = absint($product_id);
		$empty      = ['attributes' => [], 'variations' => []];

		if ($product_id <= 0) {
			return $empty;
		}

		if (isset(self::$variation_map_cache[$product_id])) {
			return self::$variation_map_cache[$product_id];
		}

		$product = wc_get_product($product_id);

		if (!$product instanceof WC_Product || !$product->is_type('variable')) {
			self::$variation_map_cache[$product_id] = $empty;

			return $empty;
		}

		$children = $product->get_children();

		if (empty($children)) {
			self::$variation_map_cache[$product_id] = $empty;

			return $empty;
		}

		// Guard against pathological products.
		$children = array_slice($children, 0, 500);

		$variations = [];
		$attributes = [];

		foreach ($children as $child_id) {
			$variation = wc_get_product($child_id);

			if (!$variation instanceof WC_Product) {
				continue;
			}

			$attributes_map = [];

			foreach ($variation->get_attributes() as $key => $value) {
				$name = 'attribute_' . $key;

				$attributes[ $name ] = true;

				// An empty value means "any" for that attribute.
				$attributes_map[ $name ] = is_string($value) ? $value : '';
			}

			$variations[] = [
				'a' => $attributes_map,
				'stock' => (bool) $variation->is_in_stock(),
				'buy' => (bool) $variation->is_purchasable(),
			];
		}

		$map = [
			'attributes' => array_keys($attributes),
			'variations' => $variations,
		];

		self::$variation_map_cache[$product_id] = $map;

		return $map;
	}

	/**
	 * Enqueue frontend assets.
	 */
	public function enqueue_assets(): void
	{
		if (!svi_get_option('swatches_enabled', true)) {
			return;
		}

		// Catalog cards and the attribute filter both live on shop and archive
		// pages, so the assets follow whichever of them is switched on.
		$needs_archive = svi_get_option('show_in_catalog', false) || svi_get_option('swatch_filter_widget', false);
		$is_catalog_page = is_shop() || is_product_taxonomy();

		if (!is_product() && !($needs_archive && $is_catalog_page)) {
			return;
		}

		$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
		$url    = plugin_dir_url(__FILE__);
		// Bump on every asset change so browsers pick the new file up.
		$ver    = '1.3.6';

		wp_enqueue_style('svi-swatches', $url . 'public/assets/css/swatches' . $suffix . '.css', [], $ver);

		// The base runtime only exists in the v6 build; skip it when absent so
		// WordPress does not drop the whole script for an unknown dependency.
		$deps = ['jquery'];

		if (wp_script_is('svi-base-runtime', 'registered')) {
			$deps[] = 'svi-base-runtime';
		}

		wp_enqueue_script('svi-swatches', $url . 'public/assets/js/swatches' . $suffix . '.js', $deps, $ver, true);
	}

	/**
	 * Render fields on Attribute "Add New" screen.
	 */
	public function add_attribute_fields(): void
	{
		?>
		<div class="form-field">
			<label for="svi_swatch_type"><?php _e('Swatch Type', 'smart-variations-images'); ?></label>
			<select name="svi_swatch_type" id="svi_swatch_type">
				<option value=""><?php _e('None (Dropdown)', 'smart-variations-images'); ?></option>
				<option value="color"><?php _e('Color', 'smart-variations-images'); ?></option>
				<option value="image"><?php _e('Image', 'smart-variations-images'); ?></option>
				<option value="text"><?php _e('Text / Button', 'smart-variations-images'); ?></option>
				<option value="radio"><?php _e('Radio Buttons', 'smart-variations-images'); ?></option>
			</select>
			<p class="description">
				<?php _e('Select how this attribute is displayed on the frontend.', 'smart-variations-images'); ?>
			</p>
		</div>

		<div class="form-field svi-opt-visual">
			<label for="svi_swatch_shape"><?php _e('Swatch Shape', 'smart-variations-images'); ?></label>
			<select name="svi_swatch_shape" id="svi_swatch_shape">
				<option value="round"><?php _e('Round', 'smart-variations-images'); ?></option>
				<option value="square"><?php _e('Square', 'smart-variations-images'); ?></option>
			</select>
			<p class="description"><?php _e('The shape of your swatches on the frontend.', 'smart-variations-images'); ?></p>
		</div>

		<div class="form-field svi-opt-visual">
			<label><?php _e('Swatch Size (px)', 'smart-variations-images'); ?></label>
			<div style="display: flex; gap: 10px;">
				<label title="Width">W: <input type="number" name="svi_swatch_size_w" value="30" style="width: 60px;" /></label>
				<label title="Height">H: <input type="number" name="svi_swatch_size_h" value="30"
						style="width: 60px;" /></label>
			</div>
			<p class="description"><?php _e('The size of your swatches on the frontend.', 'smart-variations-images'); ?></p>
		</div>

		<div class="form-field svi-opt-visual">
			<label for="svi_enable_tooltips"><?php _e('Enable Tooltips?', 'smart-variations-images'); ?></label>
			<select name="svi_enable_tooltips" id="svi_enable_tooltips">
				<option value="yes"><?php _e('Yes', 'smart-variations-images'); ?></option>
				<option value="no"><?php _e('No', 'smart-variations-images'); ?></option>
			</select>
			<p class="description">
				<?php _e('When enabled, a tooltip style text description will accompany your swatches.', 'smart-variations-images'); ?>
			</p>
		</div>

		<div class="form-field svi-opt-image-only">
			<label for="svi_large_preview"><?php _e('Show Large Preview?', 'smart-variations-images'); ?></label>
			<select name="svi_large_preview" id="svi_large_preview">
				<option value="yes"><?php _e('Yes', 'smart-variations-images'); ?></option>
				<option value="no"><?php _e('No', 'smart-variations-images'); ?></option>
			</select>
			<p class="description">
				<?php _e('Display a larger preview of the image swatch within a tooltip.', 'smart-variations-images'); ?>
			</p>
		</div>

		<div class="form-field svi-opt-common">
			<label for="svi_show_in_catalog"><?php _e('Show Swatch in Catalog?', 'smart-variations-images'); ?></label>
			<select name="svi_show_in_catalog" id="svi_show_in_catalog">
				<option value="no"><?php _e('No', 'smart-variations-images'); ?></option>
				<option value="yes"><?php _e('Yes', 'smart-variations-images'); ?></option>
			</select>
			<p class="description">
				<?php _e('When enabled, available swatches will be displayed in the catalog listing for each product.', 'smart-variations-images'); ?>
			</p>
		</div>

		<div class="form-field svi-opt-catalog-method">
			<label for="svi_catalog_swatch_method"><?php _e('Catalog Swatch Method', 'smart-variations-images'); ?></label>
			<select name="svi_catalog_swatch_method" id="svi_catalog_swatch_method">
				<option value="link"><?php _e('Link to Product', 'smart-variations-images'); ?></option>
				<option value="image"><?php _e('Change Product Image', 'smart-variations-images'); ?></option>
			</select>
			<p class="description">
				<?php _e('What should happen when a user clicks on the swatch in the catalog.', 'smart-variations-images'); ?>
			</p>
		</div>

		<div class="form-field svi-opt-common">
			<label for="svi_overflow"><?php _e('Overflow', 'smart-variations-images'); ?></label>
			<select name="svi_overflow" id="svi_overflow">
				<option value="stacked"><?php _e('Stacked', 'smart-variations-images'); ?></option>
				<option value="single_line"><?php _e('Single Line', 'smart-variations-images'); ?></option>
				<option value="slider"><?php _e('Slider', 'smart-variations-images'); ?></option>
			</select>
			<p class="description">
				<?php _e('How to display swatches with a large number of attributes.', 'smart-variations-images'); ?>
			</p>
		</div>

		<div class="form-field svi-opt-common">
			<label for="svi_show_in_filters"><?php _e('Show Swatch in Filters?', 'smart-variations-images'); ?></label>
			<select name="svi_show_in_filters" id="svi_show_in_filters">
				<option value="no"><?php _e('No', 'smart-variations-images'); ?></option>
				<option value="yes"><?php _e('Yes', 'smart-variations-images'); ?></option>
			</select>
			<p class="description">
				<?php _e('When enabled, swatches will be displayed in the WooCommerce layered nav filter widgets.', 'smart-variations-images'); ?>
			</p>
		</div>

		<div class="form-field svi-opt-common">
			<label for="svi_group_terms"><?php _e('Groups', 'smart-variations-images'); ?></label>
			<input type="text" name="svi_group_terms" id="svi_group_terms" value="" />
			<p class="description"><?php _e('Enter group labels separated by commas.', 'smart-variations-images'); ?></p>
		</div>
		<?php
	}

	/**
	 * Render fields on Attribute "Edit" screen.
	 */
	public function edit_attribute_fields(): void
	{
		$id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
		$type = $this->get_attribute_option($id, 'swatch_type');
		$shape = $this->get_attribute_option($id, 'swatch_shape', 'round');
		$size_w = $this->get_attribute_option($id, 'swatch_size_w', 30);
		$size_h = $this->get_attribute_option($id, 'swatch_size_h', 30);
		$tooltips = $this->get_attribute_option($id, 'enable_tooltips', 'yes');
		$large_preview = $this->get_attribute_option($id, 'large_preview', 'yes');
		$catalog = $this->get_attribute_option($id, 'show_in_catalog', 'no');
		$catalog_method = $this->get_attribute_option($id, 'catalog_swatch_method', 'link');
		$overflow = $this->get_attribute_option($id, 'overflow', 'stacked');
		$filters = $this->get_attribute_option($id, 'show_in_filters', 'no');
		$groups = $this->get_attribute_option($id, 'group_terms', '');
		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="svi_swatch_type"><?php _e('Swatch Type', 'smart-variations-images'); ?></label>
			</th>
			<td>
				<select name="svi_swatch_type" id="svi_swatch_type">
					<option value="" <?php selected($type, ''); ?>>
						<?php _e('None (Dropdown)', 'smart-variations-images'); ?>
					</option>
					<option value="color" <?php selected($type, 'color'); ?>>
						<?php _e('Color', 'smart-variations-images'); ?>
					</option>
					<option value="image" <?php selected($type, 'image'); ?>>
						<?php _e('Image', 'smart-variations-images'); ?>
					</option>
					<option value="text" <?php selected($type, 'text'); ?>>
						<?php _e('Text / Button', 'smart-variations-images'); ?>
					</option>
					<option value="radio" <?php selected($type, 'radio'); ?>>
						<?php _e('Radio Buttons', 'smart-variations-images'); ?>
					</option>
				</select>
				<p class="description">
					<?php _e('Select how this attribute is displayed on the frontend.', 'smart-variations-images'); ?>
				</p>
			</td>
		</tr>

		<tr class="form-field svi-opt-visual">
			<th scope="row" valign="top">
				<label for="svi_swatch_shape"><?php _e('Swatch Shape', 'smart-variations-images'); ?></label>
			</th>
			<td>
				<select name="svi_swatch_shape" id="svi_swatch_shape">
					<option value="round" <?php selected($shape, 'round'); ?>>
						<?php _e('Round', 'smart-variations-images'); ?>
					</option>
					<option value="square" <?php selected($shape, 'square'); ?>>
						<?php _e('Square', 'smart-variations-images'); ?>
					</option>
				</select>
				<p class="description"><?php _e('The shape of your swatches on the frontend.', 'smart-variations-images'); ?>
				</p>
			</td>
		</tr>

		<tr class="form-field svi-opt-visual">
			<th scope="row" valign="top">
				<label><?php _e('Swatch Size (px)', 'smart-variations-images'); ?></label>
			</th>
			<td>
				<div style="display: flex; gap: 10px;">
					<label title="Width">W: <input type="number" name="svi_swatch_size_w"
							value="<?php echo esc_attr($size_w); ?>" style="width: 60px;" /></label>
					<label title="Height">H: <input type="number" name="svi_swatch_size_h"
							value="<?php echo esc_attr($size_h); ?>" style="width: 60px;" /></label>
				</div>
				<p class="description"><?php _e('The size of your swatches on the frontend.', 'smart-variations-images'); ?>
				</p>
			</td>
		</tr>

		<tr class="form-field svi-opt-visual">
			<th scope="row" valign="top">
				<label for="svi_enable_tooltips"><?php _e('Enable Tooltips?', 'smart-variations-images'); ?></label>
			</th>
			<td>
				<select name="svi_enable_tooltips" id="svi_enable_tooltips">
					<option value="yes" <?php selected($tooltips, 'yes'); ?>><?php _e('Yes', 'smart-variations-images'); ?>
					</option>
					<option value="no" <?php selected($tooltips, 'no'); ?>><?php _e('No', 'smart-variations-images'); ?>
					</option>
				</select>
				<p class="description">
					<?php _e('When enabled, a tooltip style text description will accompany your swatches.', 'smart-variations-images'); ?>
				</p>
			</td>
		</tr>

		<tr class="form-field svi-opt-image-only">
			<th scope="row" valign="top">
				<label for="svi_large_preview"><?php _e('Show Large Preview?', 'smart-variations-images'); ?></label>
			</th>
			<td>
				<select name="svi_large_preview" id="svi_large_preview">
					<option value="yes" <?php selected($large_preview, 'yes'); ?>>
						<?php _e('Yes', 'smart-variations-images'); ?>
					</option>
					<option value="no" <?php selected($large_preview, 'no'); ?>>
						<?php _e('No', 'smart-variations-images'); ?>
					</option>
				</select>
				<p class="description">
					<?php _e('Display a larger preview of the image swatch within a tooltip.', 'smart-variations-images'); ?>
				</p>
			</td>
		</tr>

		<tr class="form-field svi-opt-common">
			<th scope="row" valign="top">
				<label for="svi_show_in_catalog"><?php _e('Show Swatch in Catalog?', 'smart-variations-images'); ?></label>
			</th>
			<td>
				<select name="svi_show_in_catalog" id="svi_show_in_catalog">
					<option value="no" <?php selected($catalog, 'no'); ?>><?php _e('No', 'smart-variations-images'); ?>
					</option>
					<option value="yes" <?php selected($catalog, 'yes'); ?>><?php _e('Yes', 'smart-variations-images'); ?>
					</option>
				</select>
				<p class="description">
					<?php _e('When enabled, available swatches will be displayed in the catalog listing for each product.', 'smart-variations-images'); ?>
				</p>
			</td>
		</tr>

		<tr class="form-field svi-opt-catalog-method">
			<th scope="row" valign="top">
				<label for="svi_catalog_swatch_method"><?php _e('Catalog Swatch Method', 'smart-variations-images'); ?></label>
			</th>
			<td>
				<select name="svi_catalog_swatch_method" id="svi_catalog_swatch_method">
					<option value="link" <?php selected($catalog_method, 'link'); ?>>
						<?php _e('Link to Product', 'smart-variations-images'); ?>
					</option>
					<option value="image" <?php selected($catalog_method, 'image'); ?>>
						<?php _e('Change Product Image', 'smart-variations-images'); ?>
					</option>
				</select>
				<p class="description">
					<?php _e('What should happen when a user clicks on the swatch in the catalog.', 'smart-variations-images'); ?>
				</p>
			</td>
		</tr>

		<tr class="form-field svi-opt-common">
			<th scope="row" valign="top">
				<label for="svi_overflow"><?php _e('Overflow', 'smart-variations-images'); ?></label>
			</th>
			<td>
				<select name="svi_overflow" id="svi_overflow">
					<option value="stacked" <?php selected($overflow, 'stacked'); ?>>
						<?php _e('Stacked', 'smart-variations-images'); ?>
					</option>
					<option value="single_line" <?php selected($overflow, 'single_line'); ?>>
						<?php _e('Single Line', 'smart-variations-images'); ?>
					</option>
					<option value="slider" <?php selected($overflow, 'slider'); ?>>
						<?php _e('Slider', 'smart-variations-images'); ?>
					</option>
				</select>
				<p class="description">
					<?php _e('How to display swatches with a large number of attributes.', 'smart-variations-images'); ?>
				</p>
			</td>
		</tr>

		<tr class="form-field svi-opt-common">
			<th scope="row" valign="top">
				<label for="svi_show_in_filters"><?php _e('Show Swatch in Filters?', 'smart-variations-images'); ?></label>
			</th>
			<td>
				<select name="svi_show_in_filters" id="svi_show_in_filters">
					<option value="no" <?php selected($filters, 'no'); ?>><?php _e('No', 'smart-variations-images'); ?>
					</option>
					<option value="yes" <?php selected($filters, 'yes'); ?>><?php _e('Yes', 'smart-variations-images'); ?>
					</option>
				</select>
				<p class="description">
					<?php _e('When enabled, swatches will be displayed in the WooCommerce layered nav filter widgets.', 'smart-variations-images'); ?>
				</p>
			</td>
		</tr>

		<tr class="form-field svi-opt-common">
			<th scope="row" valign="top">
				<label for="svi_group_terms"><?php _e('Groups', 'smart-variations-images'); ?></label>
			</th>
			<td>
				<input type="text" name="svi_group_terms" id="svi_group_terms" value="<?php echo esc_attr($groups); ?>" />
				<p class="description"><?php _e('Enter group labels separated by commas.', 'smart-variations-images'); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save Attribute Fields.
	 * 
	 * @param int $id Attribute ID.
	 * @param array $data Attribute data.
	 * @param string $old_slug Old attribute slug (on update).
	 */
	public function save_attribute_fields($id, $data, $old_slug = null): void
	{
		$keys = [
			'svi_swatch_type',
			'svi_swatch_shape',
			'svi_swatch_size_w',
			'svi_swatch_size_h',
			'svi_enable_tooltips',
			'svi_large_preview',
			'svi_show_in_catalog',
			'svi_catalog_swatch_method',
			'svi_overflow',
			'svi_show_in_filters',
			'svi_group_terms',
			// Styling Options
			'svi_swatch_bg_color',
			'svi_swatch_border_color',
			'svi_swatch_text_color',
			'svi_swatch_accent_color'
		];

		foreach ($keys as $key) {
			if (isset($_POST[$key])) {
				$clean_key = str_replace('svi_', '', $key); // standardized option name
				// e.g. "svi_attribute_swatch_{key}_{id}"
				update_option("svi_attribute_{$clean_key}_{$id}", sanitize_text_field($_POST[$key]));
			}
		}
	}

	/**
	 * Helper: Get Attribute Option.
	 * 
	 * @param int $attribute_id
	 * @param string $option_key (without prefix)
	 * @param mixed $default
	 * @return mixed
	 */
	public function get_attribute_option($attribute_id, $option_key, $default = '')
	{
		return get_option("svi_attribute_{$option_key}_{$attribute_id}", $default);
	}

	/**
	 * Helper: Get Attribute Swatch Type.
	 * 
	 * @param int $attribute_id
	 * @return string
	 */
	public function get_attribute_swatch_type($attribute_id): string
	{
		return $this->get_attribute_option($attribute_id, 'swatch_type', '');
	}

	/**
	 * Init Term Fields for all attributes.
	 */
	public function init_term_fields(): void
	{
		$attributes = wc_get_attribute_taxonomies();

		if (!$attributes) {
			return;
		}

		foreach ($attributes as $attribute) {
			$taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);

			add_action($taxonomy . '_add_form_fields', [$this, 'add_term_fields']);
			add_action($taxonomy . '_edit_form_fields', [$this, 'edit_term_fields']);

			add_action('created_' . $taxonomy, [$this, 'save_term_fields']);
			add_action('edited_' . $taxonomy, [$this, 'save_term_fields']);
		}
	}

	/**
	 * Render fields on "Add New" Term screen.
	 */
	public function add_term_fields($taxonomy): void
	{
		// We need to know the attribute ID to check the type.
		$attribute_id = $this->get_attribute_id_by_taxonomy($taxonomy);
		$type = $this->get_attribute_swatch_type($attribute_id);

		?>
		<div class="svi-term-fields-wrapper" data-swatch-type="<?php echo esc_attr($type); ?>">

			<?php if ($type === 'color'): ?>
				<div class="form-field term-swatch-color-wrap">
					<label for="svi_swatch_color"><?php _e('Color 1 (Primary)', 'smart-variations-images'); ?></label>
					<input type="text" name="svi_swatch_color" id="svi_swatch_color" class="svi-color-picker" value="" />
				</div>
				<div class="form-field term-swatch-color-wrap">
					<label for="svi_swatch_color_2"><?php _e('Color 2 (Optional)', 'smart-variations-images'); ?></label>
					<input type="text" name="svi_swatch_color_2" id="svi_swatch_color_2" class="svi-color-picker" value="" />
				</div>
				<div class="form-field term-swatch-color-wrap">
					<label for="svi_swatch_color_3"><?php _e('Color 3 (Optional)', 'smart-variations-images'); ?></label>
					<input type="text" name="svi_swatch_color_3" id="svi_swatch_color_3" class="svi-color-picker" value="" />
				</div>
				<div class="form-field term-swatch-color-wrap">
					<label for="svi_swatch_color_4"><?php _e('Color 4 (Optional)', 'smart-variations-images'); ?></label>
					<input type="text" name="svi_swatch_color_4" id="svi_swatch_color_4" class="svi-color-picker" value="" />
				</div>
			<?php endif; ?>

			<?php if ($type === 'image'): ?>
				<div class="form-field term-swatch-image-wrap">
					<label><?php _e('Image', 'smart-variations-images'); ?></label>
					<div class="svi-image-uploader">
						<input type="hidden" name="svi_swatch_image_id" id="svi_swatch_image_id" value="" />
						<div class="image-preview-wrapper">
							<img src="<?php echo wc_placeholder_img_src(); ?>" class="image-preview" width="50px" height="50px" />
						</div>
						<button type="button"
							class="button svi-upload-image-btn"><?php _e('Upload/Add Image', 'smart-variations-images'); ?></button>
						<button type="button" class="button svi-remove-image-btn hidden"
							style="display:none;"><?php _e('Remove', 'smart-variations-images'); ?></button>
					</div>
				</div>
			<?php endif; ?>

		</div>
		<?php
	}

	/**
	 * Render fields on "Edit" Term screen.
	 *
	 * @param WP_Term $term Term object.
	 */
	public function edit_term_fields($term): void
	{
		$taxonomy = $term->taxonomy;
		$attribute_id = $this->get_attribute_id_by_taxonomy($taxonomy);
		$type = $this->get_attribute_swatch_type($attribute_id);

		$color = get_term_meta($term->term_id, 'svi_swatch_color', true);
		$color2 = get_term_meta($term->term_id, 'svi_swatch_color_2', true);
		$color3 = get_term_meta($term->term_id, 'svi_swatch_color_3', true);
		$color4 = get_term_meta($term->term_id, 'svi_swatch_color_4', true);

		$image_id = get_term_meta($term->term_id, 'svi_swatch_image_id', true);
		$image_src = $image_id ? wp_get_attachment_thumb_url($image_id) : wc_placeholder_img_src();
		?>
		<?php if ($type === 'color'): ?>
			<tr class="form-field term-swatch-color-wrap">
				<th scope="row"><label for="svi_swatch_color"><?php _e('Color 1 (Primary)', 'smart-variations-images'); ?></label>
				</th>
				<td>
					<input type="text" name="svi_swatch_color" id="svi_swatch_color" class="svi-color-picker"
						value="<?php echo esc_attr($color); ?>" />
				</td>
			</tr>
			<tr class="form-field term-swatch-color-wrap">
				<th scope="row"><label
						for="svi_swatch_color_2"><?php _e('Color 2 (Optional)', 'smart-variations-images'); ?></label></th>
				<td>
					<input type="text" name="svi_swatch_color_2" id="svi_swatch_color_2" class="svi-color-picker"
						value="<?php echo esc_attr($color2); ?>" />
				</td>
			</tr>
			<tr class="form-field term-swatch-color-wrap">
				<th scope="row"><label
						for="svi_swatch_color_3"><?php _e('Color 3 (Optional)', 'smart-variations-images'); ?></label></th>
				<td>
					<input type="text" name="svi_swatch_color_3" id="svi_swatch_color_3" class="svi-color-picker"
						value="<?php echo esc_attr($color3); ?>" />
				</td>
			</tr>
			<tr class="form-field term-swatch-color-wrap">
				<th scope="row"><label
						for="svi_swatch_color_4"><?php _e('Color 4 (Optional)', 'smart-variations-images'); ?></label></th>
				<td>
					<input type="text" name="svi_swatch_color_4" id="svi_swatch_color_4" class="svi-color-picker"
						value="<?php echo esc_attr($color4); ?>" />
				</td>
			</tr>
		<?php endif; ?>

		<?php if ($type === 'image'): ?>
			<tr class="form-field term-swatch-image-wrap">
				<th scope="row"><label><?php _e('Image', 'smart-variations-images'); ?></label></th>
				<td>
					<div class="svi-image-uploader">
						<input type="hidden" name="svi_swatch_image_id" id="svi_swatch_image_id"
							value="<?php echo esc_attr($image_id); ?>" />
						<div class="image-preview-wrapper" style="margin-bottom:10px;">
							<img src="<?php echo esc_url($image_src); ?>" class="image-preview" width="50px" height="50px" />
						</div>
						<button type="button"
							class="button svi-upload-image-btn"><?php _e('Upload/Add Image', 'smart-variations-images'); ?></button>
						<button type="button" class="button svi-remove-image-btn"
							style="<?php echo $image_id ? '' : 'display:none;'; ?>"><?php _e('Remove', 'smart-variations-images'); ?></button>
					</div>
				</td>
			</tr>
		<?php endif; ?>
	<?php
	}

	/**
	 * Save Term Meta.
	 *
	 * @param int $term_id Term ID.
	 */
	public function save_term_fields($term_id): void
	{
		if (isset($_POST['svi_swatch_color'])) {
			update_term_meta($term_id, 'svi_swatch_color', sanitize_hex_color($_POST['svi_swatch_color']));
		}
		if (isset($_POST['svi_swatch_color_2'])) {
			update_term_meta($term_id, 'svi_swatch_color_2', sanitize_hex_color($_POST['svi_swatch_color_2']));
		}
		if (isset($_POST['svi_swatch_color_3'])) {
			update_term_meta($term_id, 'svi_swatch_color_3', sanitize_hex_color($_POST['svi_swatch_color_3']));
		}
		if (isset($_POST['svi_swatch_color_4'])) {
			update_term_meta($term_id, 'svi_swatch_color_4', sanitize_hex_color($_POST['svi_swatch_color_4']));
		}
		if (isset($_POST['svi_swatch_image_id'])) {
			update_term_meta($term_id, 'svi_swatch_image_id', absint($_POST['svi_swatch_image_id']));
		}
	}

	/**
	 * Helper: Get Attribute ID from Taxonomy Slug.
	 * e.g. 'pa_color' -> 12
	 */
	public function get_attribute_id_by_taxonomy($taxonomy)
	{
		// Strip 'pa_' prefix
		if (strpos($taxonomy, 'pa_') === 0) {
			$slug = substr($taxonomy, 3);
			$id = wc_attribute_taxonomy_id_by_name($slug);
			return $id;
		}
		return 0;
	}

	/**
	 * Swatch type stored by the legacy engine, mapped to this module's types.
	 *
	 * @param int $attribute_id Attribute taxonomy id.
	 * @return string
	 */
	public function get_legacy_attribute_type($attribute_id): string
	{
		$attribute_id = absint($attribute_id);

		if (!$attribute_id) {
			return '';
		}

		$taxonomies = wc_get_attribute_taxonomies();

		foreach ($taxonomies as $attribute) {
			if ((int) $attribute->attribute_id !== $attribute_id) {
				continue;
			}

			$legacy = isset($attribute->attribute_type) ? (string) $attribute->attribute_type : '';

			return isset(self::LEGACY_TYPE_MAP[$legacy]) ? self::LEGACY_TYPE_MAP[$legacy] : '';
		}

		return '';
	}

	/**
	 * Enqueue Admin Scripts for Color Picker and Media Uploader.
	 */
	public function enqueue_admin_scripts(): void
	{
		$screen = get_current_screen();

		// Screen bases:
		// 'term' / 'edit-tags': Editing specific terms (Blue, Red).
		// 'product_page_product_attributes': Editing the global attribute definition (Color).
		$allowed_screens = ['term', 'edit-tags', 'product_page_product_attributes'];
		$is_settings     = $screen && false !== strpos((string) $screen->id, 'woosvi-options-settings');

		if (!$screen || (!in_array($screen->base, $allowed_screens, true) && !$is_settings)) {
			return;
		}

		wp_enqueue_style('wp-color-picker');

		if ($is_settings) {
			wp_enqueue_style('svi-swatches-admin', plugin_dir_url(__FILE__) . 'public/assets/css/swatches-admin' . SMART_SCRIPT_DEBUG . '.css', [], '1.3.6');
		}

		wp_enqueue_media();
		wp_enqueue_script('svi-swatches-admin', plugin_dir_url(__FILE__) . 'public/assets/js/admin-swatches' . SMART_SCRIPT_DEBUG . '.js', ['jquery', 'wp-color-picker'], '1.3.6', true);

		wp_localize_script('svi-swatches-admin', 'sviSwatchesImport', [
			'action' => admin_url('admin-post.php'),
			'nonce' => wp_create_nonce('svi_swatches_import'),
		]);
	}

	/**
	 * Collect every swatch definition stored by the plugin.
	 *
	 * @return array
	 */
	public static function get_definitions(): array
	{
		$definitions = [
			'format' => self::EXPORT_FORMAT,
			'version' => 1,
			'attributes' => [],
			'terms' => [],
			'products' => [],
		];

		foreach (wc_get_attribute_taxonomies() as $attribute) {
			$attribute_id = (int) $attribute->attribute_id;
			$taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);

			$definitions['attributes'][$taxonomy] = [
				'name' => $attribute->attribute_name,
				'label' => $attribute->attribute_label,
				'swatch_type' => get_option("svi_attribute_swatch_type_{$attribute_id}", ''),
				'catalog' => [
					'show_in_catalog' => get_option("svi_attribute_show_in_catalog_{$attribute_id}", ''),
					'catalog_swatch_method' => get_option("svi_attribute_catalog_swatch_method_{$attribute_id}", ''),
				],
			];

			$terms = get_terms([
				'taxonomy' => $taxonomy,
				'hide_empty' => false,
				'fields' => 'ids',
			]);

			if (is_wp_error($terms)) {
				continue;
			}

			foreach ($terms as $term_id) {
				$values = SVI_Swatch_Values::get((int) $term_id);

				if (empty($values['colors']) && 0 === $values['image'] && '' === $values['label']) {
					continue;
				}

				$term = get_term((int) $term_id, $taxonomy);

				if (!$term instanceof WP_Term) {
					continue;
				}

				$definitions['terms'][$taxonomy][$term->slug] = $values;
			}
		}

		$overrides = get_posts([
			'post_type' => 'product',
			'post_status' => 'any',
			'fields' => 'ids',
			'posts_per_page' => -1,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- One-off admin export.
			'meta_key' => '_svi_swatch_overrides',
			'meta_compare' => 'EXISTS',
		]);

		foreach ($overrides as $product_id) {
			$stored = get_post_meta((int) $product_id, '_svi_swatch_overrides', true);

			if (is_array($stored) && !empty($stored)) {
				$definitions['products'][(string) $product_id] = $stored;
			}
		}

		return $definitions;
	}

	/**
	 * Write swatch definitions, merging them into what is already stored.
	 *
	 * @param array $definitions Parsed export payload.
	 * @return array{terms:int,products:int,attributes:int} Counts of imported rows.
	 */
	public static function apply_definitions(array $definitions): array
	{
		$counts = [
			'attributes' => 0,
			'terms' => 0,
			'products' => 0,
		];

		foreach ((array) ($definitions['attributes'] ?? []) as $taxonomy => $data) {
			$attribute_id = wc_attribute_taxonomy_id_by_name(str_replace('pa_', '', (string) $taxonomy));

			if (!$attribute_id) {
				continue;
			}

			$type = isset($data['swatch_type']) ? sanitize_key((string) $data['swatch_type']) : '';

			if ('' !== $type) {
				update_option("svi_attribute_swatch_type_{$attribute_id}", $type);
				$counts['attributes']++;
			}

			foreach (['show_in_catalog', 'catalog_swatch_method'] as $key) {
				if (!empty($data['catalog'][$key])) {
					update_option("svi_attribute_{$key}_{$attribute_id}", sanitize_key((string) $data['catalog'][$key]));
				}
			}
		}

		foreach ((array) ($definitions['terms'] ?? []) as $taxonomy => $terms) {
			if (!taxonomy_exists((string) $taxonomy)) {
				continue;
			}

			foreach ((array) $terms as $slug => $values) {
				$term = get_term_by('slug', (string) $slug, (string) $taxonomy);

				if (!$term instanceof WP_Term) {
					continue;
				}

				$colors = SVI_Swatch_Values::sanitize_colors((array) ($values['colors'] ?? []));
				$image = isset($values['image']) ? absint($values['image']) : 0;
				$label = isset($values['label']) ? sanitize_text_field((string) $values['label']) : '';

				foreach ($colors as $index => $color) {
					$meta_key = 0 === $index ? 'svi_swatch_color' : 'svi_swatch_color_' . ($index + 1);
					update_term_meta($term->term_id, $meta_key, $color);
					// Keep the legacy key in sync so both readers agree.
					if (0 === $index) {
						update_term_meta($term->term_id, 'color', $color);
					}
				}

				if ($image > 0) {
					update_term_meta($term->term_id, 'svi_swatch_image_id', $image);
					update_term_meta($term->term_id, 'image', $image);
				}

				if ('' !== $label) {
					update_term_meta($term->term_id, 'svi_swatch_label', $label);
					update_term_meta($term->term_id, 'label', $label);
				}

				$counts['terms']++;
			}
		}

		foreach ((array) ($definitions['products'] ?? []) as $product_id => $overrides) {
			$product_id = absint($product_id);

			if (!$product_id || !is_array($overrides) || !get_post($product_id)) {
				continue;
			}

			update_post_meta($product_id, '_svi_swatch_overrides', $overrides);
			$counts['products']++;
		}

		return $counts;
	}

	/**
	 * Markup for the export/import tools in the settings tab.
	 *
	 * @return string
	 */
	/**
	 * Legacy attribute type => swatch type.
	 *
	 * Stores configured before this module kept the swatch kind in WooCommerce's
	 * own attribute type column.
	 *
	 * @var array<string, string>
	 */
	const LEGACY_TYPE_MAP = [
		'color' => 'color',
		'image' => 'image',
		'label' => 'text',
		'text'  => 'text',
		'radio' => 'radio',
	];

	/**
	 * Copy legacy swatch configuration into the keys this module reads.
	 *
	 * Never overwrites an existing value and never deletes anything: the old
	 * attribute types and term meta stay untouched, so the change is reversible.
	 *
	 * @param bool $dry_run Only count what would be converted.
	 * @return array{types:int,terms:int}
	 */
	public static function convert_legacy_data(bool $dry_run = false): array
	{
		$counts = [
			'types' => 0,
			'terms' => 0,
		];

		$attributes = wc_get_attribute_taxonomies();

		if (!$attributes) {
			return $counts;
		}

		foreach ($attributes as $attribute) {
			$attribute_id = (int) $attribute->attribute_id;
			$legacy_type  = isset($attribute->attribute_type) ? (string) $attribute->attribute_type : '';
			$has_type     = (string) get_option("svi_attribute_swatch_type_{$attribute_id}", '');

			if ('' === $has_type && isset(self::LEGACY_TYPE_MAP[$legacy_type])) {
				$counts['types']++;

				if (!$dry_run) {
					update_option("svi_attribute_swatch_type_{$attribute_id}", self::LEGACY_TYPE_MAP[$legacy_type]);
				}
			}

			$taxonomy = wc_attribute_taxonomy_name($attribute->attribute_name);

			if (!taxonomy_exists($taxonomy)) {
				continue;
			}

			$terms = get_terms([
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
			]);

			if (is_wp_error($terms) || empty($terms)) {
				continue;
			}

			update_meta_cache('term', $terms);

			foreach ($terms as $term_id) {
				$term_id = (int) $term_id;

				// Colours: one slot at a time, both families side by side.
				$slots = [
					['svi_swatch_color', 'color'],
					['svi_swatch_color_2', 'color_2'],
				];

				foreach ($slots as $slot) {
					list($primary, $legacy) = $slot;

					$current = (string) get_term_meta($term_id, $primary, true);
					$old     = (string) get_term_meta($term_id, $legacy, true);

					if ('' === trim($current) && '' !== trim($old)) {
						$counts['terms']++;

						if (!$dry_run) {
							update_term_meta($term_id, $primary, sanitize_hex_color($old));
						}
					}
				}

				$current_image = absint(get_term_meta($term_id, 'svi_swatch_image_id', true));
				$legacy_image  = absint(get_term_meta($term_id, 'image', true));

				if (!$current_image && $legacy_image) {
					$counts['terms']++;

					if (!$dry_run) {
						update_term_meta($term_id, 'svi_swatch_image_id', $legacy_image);
					}
				}

				$current_label = (string) get_term_meta($term_id, 'svi_swatch_label', true);
				$legacy_label  = (string) get_term_meta($term_id, 'label', true);

				if ('' === trim($current_label) && '' !== trim($legacy_label)) {
					$counts['terms']++;

					if (!$dry_run) {
						update_term_meta($term_id, 'svi_swatch_label', sanitize_text_field($legacy_label));
					}
				}
			}
		}

		if (!$dry_run) {
			update_option('svi_swatches_legacy_converted', SMART_VARIATIONS_IMAGES_VERSION);
		}

		return $counts;
	}

	/**
	 * Run the conversion from the settings screen.
	 */
	public function convert_legacy_request(): void
	{
		if (!current_user_can('manage_woocommerce')) {
			wp_die(esc_html__('You are not allowed to convert swatch data.', 'smart-variations-images'), 403);
		}

		check_admin_referer('svi_swatches_convert');

		$counts = self::convert_legacy_data();

		wp_safe_redirect(add_query_arg([
			'svi_swatches_import'   => 'converted',
			'svi_swatches_types'    => (int) $counts['types'],
			'svi_swatches_termmeta' => (int) $counts['terms'],
		], wp_get_referer() ?: admin_url('admin.php?page=woosvi-options-settings')));
		exit;
	}

	/**
	 * Run the conversion once after upgrading from a legacy swatch setup.
	 */
	public function maybe_convert_legacy_data(): void
	{
		if (get_option('svi_swatches_legacy_converted')) {
			return;
		}

		// Leave the settings screen and the manual converter alone, so the
		// "Convert now" button still reports the real counts the first time.
		if (isset($_GET['page']) && 'woosvi-options-settings' === sanitize_key(wp_unslash($_GET['page']))) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen check.
			return;
		}

		if (isset($_REQUEST['action']) && 'svi_swatches_convert' === sanitize_key(wp_unslash($_REQUEST['action']))) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request check.
			return;
		}

		self::convert_legacy_data();
	}

	public static function get_definitions_tools_html(): string
	{
		$export_url = wp_nonce_url(
			admin_url('admin-post.php?action=svi_swatches_export'),
			'svi_swatches_export'
		);

		// The settings screen already wraps everything in its own form, so the
		// import posts through a form created by admin-swatches.js instead of a
		// nested one (which browsers discard).
		$html = '<div class="svi-definitions-tools">';

		$html .= '<div class="svi-definitions-tool">';
		$html .= '<p class="svi-definitions-tool__title">' . esc_html__('Export', 'smart-variations-images') . '</p>';
		$html .= '<p class="svi-definitions-tool__row"><a href="' . esc_url($export_url) . '" class="button">' . esc_html__('Download swatch definitions', 'smart-variations-images') . '</a></p>';
		$html .= '<p class="description">' . esc_html__('A JSON file with attribute types, value colours/images and per-product overrides.', 'smart-variations-images') . '</p>';
		$html .= '</div>';

		$html .= '<div class="svi-definitions-tool">';
		$html .= '<p class="svi-definitions-tool__title">' . esc_html__('Import', 'smart-variations-images') . '</p>';
		$html .= '<p class="svi-definitions-tool__row">';
		$html .= '<input type="file" class="svi-definitions-file" accept="application/json,.json" />';
		$html .= '</p>';
		$html .= '<p class="svi-definitions-tool__row">';
		$html .= '<button type="button" class="button svi-definitions-import">' . esc_html__('Import definitions', 'smart-variations-images') . '</button>';
		$html .= '</p>';
		$html .= '<p class="description">' . esc_html__('Values are merged into the existing ones; nothing is deleted. Matching is done by attribute taxonomy and term slug, so the import works across sites.', 'smart-variations-images') . '</p>';
		$html .= '</div>';

		// Legacy stores keep the swatch kind in WooCommerce's attribute type and
		// the colours/images in older term meta keys.
		$convert_url = wp_nonce_url(
			admin_url('admin-post.php?action=svi_swatches_convert'),
			'svi_swatches_convert'
		);
		$converted   = get_option('svi_swatches_legacy_converted');

		$html .= '<div class="svi-definitions-tool">';
		$html .= '<p class="svi-definitions-tool__title">' . esc_html__('Convert legacy swatch data', 'smart-variations-images') . '</p>';
		$html .= '<p class="svi-definitions-tool__row"><a href="' . esc_url($convert_url) . '" class="button">' . esc_html__('Convert now', 'smart-variations-images') . '</a></p>';
		$html .= '<p class="description">' . esc_html__('Copies the swatch type of each attribute and the colour, image and label of each term into the keys this module reads. Existing values are never overwritten and nothing is deleted.', 'smart-variations-images') . '</p>';

		if ($converted) {
			$html .= '<p class="description svi-definitions-tool__meta">' . esc_html(sprintf(
				/* translators: %s: version number. */
				__('Last conversion ran on plugin version %s.', 'smart-variations-images'),
				(string) $converted
			)) . '</p>';
		}

		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Stream a JSON export of the swatch definitions.
	 */
	public function export_definitions(): void
	{
		if (!current_user_can('manage_woocommerce')) {
			wp_die(esc_html__('You are not allowed to export swatch definitions.', 'smart-variations-images'), 403);
		}

		check_admin_referer('svi_swatches_export');

		$payload = wp_json_encode(self::get_definitions(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

		nocache_headers();
		header('Content-Type: application/json; charset=utf-8');
		header('Content-Disposition: attachment; filename="svi-swatches-' . gmdate('Y-m-d') . '.json"');
		echo $payload; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Raw JSON download.
		exit;
	}

	/**
	 * Import a JSON export produced by export_definitions().
	 */
	public function import_definitions(): void
	{
		if (!current_user_can('manage_woocommerce')) {
			wp_die(esc_html__('You are not allowed to import swatch definitions.', 'smart-variations-images'), 403);
		}

		check_admin_referer('svi_swatches_import');

		$redirect = add_query_arg('svi_swatches_import', 'failed', wp_get_referer() ?: admin_url('admin.php?page=woosvi-options-settings'));
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Decoded below.
		$posted = isset($_POST['svi_swatches_json']) ? wp_unslash($_POST['svi_swatches_json']) : '';
		$raw = '';

		if (is_string($posted) && '' !== $posted) {
			$raw = $posted;
		} else {
			$file = isset($_FILES['svi_swatches_file']) ? $_FILES['svi_swatches_file'] : null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read from disk below.

			if ($file && isset($file['tmp_name']) && UPLOAD_ERR_OK === ($file['error'] ?? UPLOAD_ERR_NO_FILE) && is_uploaded_file($file['tmp_name'])) {
				$raw = file_get_contents($file['tmp_name']); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local upload.
			}
		}

		$data = json_decode((string) $raw, true);

		if (!is_array($data) || (isset($data['format']) && self::EXPORT_FORMAT !== $data['format'])) {
			wp_safe_redirect($redirect);
			exit;
		}

		$counts = self::apply_definitions($data);

		wp_safe_redirect(add_query_arg([
			'svi_swatches_import' => 'done',
			'svi_swatches_terms' => (int) $counts['terms'],
			'svi_swatches_products' => (int) $counts['products'],
		], wp_get_referer() ?: admin_url('admin.php?page=woosvi-options-settings')));
		exit;
	}
}
