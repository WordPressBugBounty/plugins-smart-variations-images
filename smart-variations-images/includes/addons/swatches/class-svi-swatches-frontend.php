<?php
/**
 * Swatches Frontend Rendering
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Handles frontend rendering of swatches.
 */
class SVI_Swatches_Frontend
{

	/**
	 * Init hooks.
	 */
	public static function init(): void
	{
		// Hook into the variable product attribute dropdown.
		add_filter('woocommerce_dropdown_variation_attribute_options_html', [__CLASS__, 'render_swatches'], 10, 2);

		// Defer catalog hook registration to ensure options are loaded.
		add_action('wp', [__CLASS__, 'register_catalog_hooks']);

		// Classic "filter by attribute" widget.
		add_filter('woocommerce_layered_nav_term_html', [__CLASS__, 'render_filter_swatch'], 10, 4);
	}

	/**
	 * Register catalog hooks based on settings.
	 */
	public static function register_catalog_hooks(): void
	{
		// Hook into catalog loop.
		$catalog_hook = svi_get_option('catalog_position', 'woocommerce_after_shop_loop_item');
		$catalog_priority = (int) svi_get_option('catalog_position_priority', 30);

		add_action($catalog_hook, [__CLASS__, 'render_catalog_swatches'], $catalog_priority);
	}

	/**
	 * Render swatches instead of (or alongside) the dropdown.
	 *
	 * @param string $html The dropdown HTML.
	 * @param array  $args Arguments used to generate the dropdown.
	 * @return string Modified HTML.
	 */
	public static function render_swatches($html, $args): string
	{
		$swatch_html = self::generate_swatch_html($args['product'], $args['attribute'], $args['options'], $args['selected'] ?? '');

		if (!$swatch_html) {
			return $html;
		}

		// Append original select (hidden via CSS) so we don't break logic.
		return $swatch_html . '<div style="display:none; visibility:hidden;">' . $html . '</div>';
	}

	/**
	 * Render swatches in catalog loop.
	 */
	public static function render_catalog_swatches(): void
	{
		global $product;

		if (!$product || !$product->is_type('variable')) {
			return;
		}

		// Check global catalog visibility first.
		if (!svi_get_option('show_in_catalog', false)) {
			return;
		}

		$attributes = $product->get_variation_attributes();

		if (empty($attributes)) {
			return;
		}

		// Show a single attribute when one is configured.
		$only = (string) svi_get_option('catalog_attribute', '');

		if ('' !== $only) {
			$match = '';

			foreach (array_keys($attributes) as $name) {
				if ($name === $only || 'attribute_' . $name === $only || 'pa_' . $name === $only) {
					$match = $name;
					break;
				}
			}

			if ('' === $match) {
				return;
			}

			$attributes = [$match => $attributes[$match]];
		}

		$align = svi_get_option('catalog_swatch_align', 'flex-start');
		$max_items = (int) svi_get_option('catalog_max_items', 0);
		$show_cart = svi_get_option('catalog_add_to_cart', true);
		$overflow = svi_get_option('catalog_overflow', 'stacked');

		// Slider Options
		$hover_only = svi_get_option('catalog_slider_arrows_hover', true);
		$arrow_bg = svi_get_option('catalog_arrow_bg', '#ffffff');
		$arrow_color = svi_get_option('catalog_arrow_color', '#333333');
		$arrow_border = svi_get_option('catalog_arrow_border', '#dddddd');

		// styling options
		$size = (int) svi_get_option('catalog_swatch_size', 30);
		$gap = (int) svi_get_option('catalog_swatch_gap', 5);
		$radius = svi_get_option('catalog_swatch_border_radius', 30); // Removed (int) to allow empty checks or 0
		$border_width = svi_get_option('catalog_swatch_border_width', '');
		$hover_style = svi_get_option('catalog_hover_style', 'border');
		$hover_border = svi_get_option('catalog_hover_border_color', '#999999');

		// Map flex-start/end to left/right for easier debugging if needed, 
		// but CSS handles flex properties directly.
		$style = "--svi-catalog-align: {$align};";
		$style .= "--svi-arrow-bg: {$arrow_bg};";
		$style .= "--svi-arrow-color: {$arrow_color};";
		$style .= "--svi-arrow-border: {$arrow_border};";

		// Inject new styling vars
		$style .= "--svi-catalog-size: {$size}px;";
		$style .= "--svi-catalog-gap: {$gap}px;";

		if ($radius !== '') {
			$style .= "--svi-catalog-radius: " . (int) $radius . "px;";
		}

		if ($border_width !== '') {
			$style .= "--svi-catalog-border-width: " . (int) $border_width . "px;";
		}

		if ($hover_style === 'border') {
			$style .= "--svi-catalog-hover-border: {$hover_border};";
		}

		$container_classes = 'svi-slider-container';
		if ($hover_only) {
			$container_classes .= ' svi-show-on-hover';
		}

		// Add hover style class to wrapper
		$wrapper_classes = ['svi-catalog-swatches'];
		if ($hover_style === 'fade') {
			$wrapper_classes[] = 'svi-hover-fade';
		}

		$swatches_html = '';

		foreach ($attributes as $attribute_name => $options) {
			// Generate HTML for each attribute.
			$swatches_html .= self::generate_swatch_html($product, $attribute_name, $options, '', true);
		}

		if ('' === $swatches_html) {
			return;
		}

		// Slider Navigation Wrapper
		if ($overflow === 'slider') {
			echo '<div class="' . esc_attr($container_classes) . '">';
			echo '<button type="button" class="svi-slider-nav svi-nav-prev" aria-label="' . esc_attr__('Previous', 'smart-variations-images') . '"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/></svg></button>';
		}

		echo '<div class="' . esc_attr(implode(' ', $wrapper_classes)) . '" style="' . esc_attr($style) . '">';

		echo $swatches_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup is escaped in generate_swatch_html().

		if ($show_cart) {
			// Placeholder for Add to Cart button (JS will handle visibility/action)
			echo '<a href="#" class="button add_to_cart_button svi-catalog-add-to-cart" data-product_id="' . esc_attr($product->get_id()) . '">';
			echo esc_html__('Add to Cart', 'woocommerce');
			echo '</a>';
		}

		echo '</div>';

		if ($overflow === 'slider') {
			echo '<button type="button" class="svi-slider-nav svi-nav-next" aria-label="' . esc_attr__('Next', 'smart-variations-images') . '"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg></button>';
			echo '</div>'; // Close .svi-slider-container
		}
	}


	/**
	 * Replace a filter term with a swatch in the classic layered nav widget.
	 *
	 * @param string   $term_html Original term markup.
	 * @param WP_Term  $term       Term.
	 * @param string   $link       Term link.
	 * @param int      $count      Product count.
	 * @return string
	 */
	public static function render_filter_swatch($term_html, $term, $link, $count = 0)
	{
		if (!svi_get_option('swatch_filter_widget', false) || !$term instanceof WP_Term) {
			return $term_html;
		}

		$min = (int) svi_get_option('swatch_filter_min_count', 0);

		if ($min > 0 && (int) $count < $min) {
			return $term_html;
		}

		$attribute = $term->taxonomy;
		$type      = SVI_Swatches::instance()->resolve_swatch_type(0, $attribute);
		$values    = SVI_Swatch_Values::get($term->term_id);

		// Without a configured type, fall back to whatever the value stores so
		// the filter still shows colours and images.
		if ('' === $type) {
			if (!empty($values['colors'])) {
				$type = 'color';
			} elseif ($values['image'] > 0) {
				$type = 'image';
			} else {
				return $term_html;
			}
		}

		$inner = '';

		if ('color' === $type && !empty($values['colors'])) {
			$inner = sprintf('<span class="svi-swatch-inner" style="%s"></span>', esc_attr(SVI_Swatch_Values::build_color_css($values['colors'])));
		} elseif ('image' === $type && $values['image'] > 0) {
			$thumb = wp_get_attachment_image_url($values['image'], 'thumbnail');
			$inner = $thumb ? sprintf('<img src="%s" alt="" class="svi-swatch-image" loading="lazy" decoding="async" />', esc_url($thumb)) : '';
		}

		$label = '' !== $values['label'] ? $values['label'] : $term->name;

		// A value with no colour and no image keeps the default list markup
		// instead of rendering an empty box.
		if ('' === $inner) {
			return $term_html;
		}

		// WooCommerce marks the active term on its own list item, which we replace,
		// so the state is read back from the filter query.
		$chosen     = '';
		$chosen_nav = class_exists('WC_Query') ? WC_Query::get_layered_nav_chosen_attributes() : [];

		if (!empty($chosen_nav[$term->taxonomy]['terms']) && in_array($term->slug, (array) $chosen_nav[$term->taxonomy]['terms'], true)) {
			$chosen = ' chosen';
		}

		// WooCommerce prints its own <li> around this markup, so returning a
		// second one nested the row and doubled the theme's list indentation.
		$swatch = sprintf(
			'<%1$s%2$s class="svi-swatch svi-swatch-%3$s svi-filter-swatch" aria-label="%4$s">%5$s</%1$s>',
			$link ? 'a' : 'span',
			$link ? ' rel="nofollow" href="' . esc_url($link) . '"' : '',
			esc_attr($type),
			esc_attr($label),
			$inner
		);

		$count_html = apply_filters('woocommerce_layered_nav_count', '<span class="count">(' . absint($count) . ')</span>', $count, $term);

		return sprintf(
			'<span class="svi-filter-term%1$s">%2$s<span class="svi-filter-label">%3$s</span> %4$s</span>',
			esc_attr($chosen),
			$swatch,
			esc_html($term->name),
			$count_html
		);
	}

	/**
	 * Normalised values for an attribute of a product.
	 *
	 * @param WC_Product $product   Product.
	 * @param string     $attribute Attribute key.
	 * @return array<int, array{slug:string,name:string,term_id:int}>
	 */
	private static function get_attribute_values(WC_Product $product, string $attribute): array
	{
		$values = [];

		if (taxonomy_exists($attribute)) {
			foreach (wc_get_product_terms($product->get_id(), $attribute, ['fields' => 'all']) as $term) {
				$values[] = [
					'slug' => $term->slug,
					'name' => $term->name,
					'term_id' => (int) $term->term_id,
				];
			}

			return $values;
		}

		// Custom (non-taxonomy) attribute: values live on the product.
		foreach ($product->get_attributes() as $key => $product_attribute) {
			if (!$product_attribute instanceof WC_Product_Attribute) {
				continue;
			}

			// Custom attribute names keep their label case ("Print") while the
			// variation key is lower case ("print"), so compare case-insensitively.
			$name = strtolower($product_attribute->get_name());
			$lookup = strtolower($attribute);

			if ($name !== $lookup && 'pa_' . $name !== $lookup) {
				continue;
			}

			foreach ($product_attribute->get_options() as $option) {
				$values[] = [
					'slug' => sanitize_title($option),
					'name' => $option,
					'term_id' => 0,
				];
			}
		}

		return $values;
	}

	/**
	 * Whether the shop-card swatch row will render for a product.
	 *
	 * Mirrors the early-outs of generate_swatch_html() so the legacy loop
	 * showcase can step aside instead of printing a second row of mini images.
	 *
	 * @param WC_Product $product Product object.
	 * @return bool
	 */
	public static function product_has_catalog_swatches($product): bool
	{
		if (!$product instanceof WC_Product || !$product->is_type('variable')) {
			return false;
		}

		if (!svi_get_option('swatches_enabled', true) || !svi_get_option('show_in_catalog', false)) {
			return false;
		}

		$attributes = $product->get_variation_attributes();

		if (empty($attributes)) {
			return false;
		}

		$only = (string) svi_get_option('catalog_attribute', '');

		if ('' !== $only) {
			$match = '';

			foreach (array_keys($attributes) as $name) {
				if ($name === $only || 'attribute_' . $name === $only || 'pa_' . $name === $only) {
					$match = $name;
					break;
				}
			}

			if ('' === $match) {
				return false;
			}

			$attributes = [$match => $attributes[$match]];
		}

		$swatch_instance = SVI_Swatches::instance();
		$global_visible  = svi_get_option('show_in_catalog', false) ? 'yes' : 'no';

		foreach ($attributes as $attribute => $options) {
			if (empty($options)) {
				continue;
			}

			$attribute_id = $swatch_instance->get_attribute_id_by_taxonomy($attribute);

			if ('yes' !== $swatch_instance->get_attribute_option($attribute_id, 'show_in_catalog', $global_visible)) {
				continue;
			}

			if ('' !== $swatch_instance->resolve_swatch_type($product->get_id(), $attribute)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Swatch markup for one attribute.
	 *
	 * @param WC_Product $product       The product object.
	 * @param string     $attribute     The attribute slug (e.g., 'pa_color').
	 * @param array      $options       Available term slugs.
	 * @param string     $selected_slug Currently selected slug.
	 * @param bool       $is_catalog    Whether rendering for catalog.
	 * @return string HTML output.
	 */
	private static function generate_swatch_html($product, $attribute, $options, $selected_slug = '', $is_catalog = false): string
	{
		if (!svi_get_option('swatches_enabled', true) || empty($options)) {
			return '';
		}

		if (!$product instanceof WC_Product) {
			return '';
		}

		// Values come from the taxonomy for global attributes and from the
		// product's own options for custom attributes, so overrides work on both.
		$terms = self::get_attribute_values($product, $attribute);

		if (empty($terms)) {
			return '';
		}

		$swatch_instance = SVI_Swatches::instance();
		$product_id      = $product->get_id();
		$attribute_id    = $swatch_instance->get_attribute_id_by_taxonomy($attribute);

		// Per-product override wins, then the attribute setting, then the global
		// default (automatic conversion). An empty result keeps the dropdown.
		$swatch_type = $swatch_instance->resolve_swatch_type($product_id, $attribute);

		if (!$swatch_type) {
			return '';
		}

		// Prime term meta once so the loop below does not query per value.
		$term_ids = [];

		foreach ($terms as $term) {
			if (!empty($term['term_id'])) {
				$term_ids[] = (int) $term['term_id'];
			}
		}

		SVI_Swatch_Values::prime($term_ids);

		// Check Catalog Visibility per attribute.
		$catalog_method         = 'link';
		$permalink              = '';
		$catalog_image_action   = 'none';
		$catalog_link_variation = false;

		if ($is_catalog) {
			$global_catalog  = svi_get_option('show_in_catalog', false) ? 'yes' : 'no';
			$show_in_catalog = $swatch_instance->get_attribute_option($attribute_id, 'show_in_catalog', $global_catalog);

			if ('yes' !== $show_in_catalog) {
				return ''; // Attribute hidden in catalog.
			}

			$catalog_method = $swatch_instance->get_attribute_option($attribute_id, 'catalog_swatch_method', 'link');
			$permalink      = $product->get_permalink();

			$catalog_image_action   = (string) svi_get_option('catalog_image_action', 'hover');
			$catalog_link_variation = (bool) svi_get_option('catalog_link_variation', true);

			if (!in_array($catalog_image_action, ['none', 'hover', 'click'], true)) {
				$catalog_image_action = 'hover';
			}
		}

		// --- Retrieve Settings (Global Defaults -> Attribute Override) ---

		// Helper to get an option with attribute-level fallback.
		$get_opt = function ($key, $default) use ($swatch_instance, $attribute_id) {
			$global = svi_get_option($key, $default);

			return $swatch_instance->get_attribute_option($attribute_id, $key, $global);
		};

		// 1. General & Alignment
		$shape    = $get_opt('swatch_shape', 'round');
		$align    = $is_catalog ? $get_opt('catalog_swatch_align', 'flex-start') : $get_opt('swatch_align', 'flex-start');
		$overflow = $is_catalog ? $get_opt('catalog_overflow', 'stacked') : $get_opt('overflow', 'stacked');

		// 2. Styling - Dimensions
		if ($is_catalog) {
			$w = (int) svi_get_option('catalog_swatch_size', 30);
			$h = $w;
		} else {
			$w = $get_opt('swatch_size_w', 30);
			$h = $get_opt('swatch_size_h', 30);
		}

		$radius   = $get_opt('swatch_radius', 4);
		$fontSize = $get_opt('swatch_fontsize', 14);
		$borderW  = $get_opt('swatch_border_width', 1);

		// 3. Styling - Colors (Normal)
		$bg     = $get_opt('swatch_color_bg', '#ffffff');
		$text   = $get_opt('swatch_color_text', '#333333');
		$border = $get_opt('swatch_color_border', '#dddddd');

		// 4. Styling - Colors (Hover)
		$bg_h     = $get_opt('hover_color_bg', '#f0f0f0');
		$text_h   = $get_opt('hover_color_text', '#000000');
		$border_h = $get_opt('hover_color_border', '#999999');
		$scale    = $get_opt('hover_scale', 1.0);

		// 5. Styling - Colors (Selected)
		$bg_s     = $get_opt('selected_color_bg', '#333333');
		$text_s   = $get_opt('selected_color_text', '#ffffff');
		$border_s = $get_opt('selected_color_border', '#333333');

		// 6. Tooltip
		$tooltip_mode = $get_opt('tooltip_mode', 'text');

		if (!in_array($tooltip_mode, ['text', 'image', 'none'], true)) {
			$tooltip_mode = 'text';
		}

		// Backwards compatibility: the older toggle disabled tooltips entirely.
		if (!$get_opt('enable_tooltips', true)) {
			$tooltip_mode = 'none';
		}

		$tip_bg     = $get_opt('tooltip_color_bg', '#000000');
		$tip_text   = $get_opt('tooltip_color_text', '#ffffff');
		$tip_radius = $get_opt('tooltip_border_radius', 4);
		$preview    = $get_opt('large_preview', true);

		// 7. Behavior
		$oos_behavior  = $get_opt('oos_behavior', 'blur');
		$clear_reselect = $get_opt('clear_on_reselect', false);
		$show_label    = $get_opt('show_selected_label', true);
		$show_cart     = $is_catalog ? filter_var($get_opt('catalog_add_to_cart', 'yes'), FILTER_VALIDATE_BOOLEAN) : false;

		if ($is_catalog && $show_cart) {
			$catalog_method = 'ajax';
		}

		// Shape: "auto" keeps the historical per-type look.
		$shape_class = 'svi-shape-' . sanitize_html_class($shape);

		if ('auto' === $shape) {
			$shape_class = 'svi-shape-auto';
		}

		// --- Build CSS Variables ---
		$vars   = [];
		$vars[] = "--svi-w: {$w}px";
		$vars[] = "--svi-h: {$h}px";
		$vars[] = "--svi-radius: {$radius}px";
		$vars[] = "--svi-fs: {$fontSize}px";
		$vars[] = "--svi-bw: {$borderW}px";

		$vars[] = "--svi-bg: {$bg}";
		$vars[] = "--svi-text: {$text}";
		$vars[] = "--svi-border: {$border}";

		$vars[] = "--svi-bg-hover: {$bg_h}";
		$vars[] = "--svi-text-hover: {$text_h}";
		$vars[] = "--svi-border-hover: {$border_h}";
		$vars[] = "--svi-scale: {$scale}";

		$vars[] = "--svi-bg-selected: {$bg_s}";
		$vars[] = "--svi-text-selected: {$text_s}";
		$vars[] = "--svi-border-selected: {$border_s}";

		$vars[] = "--svi-tooltip-bg: {$tip_bg}";
		$vars[] = "--svi-tooltip-text: {$tip_text}";
		$vars[] = "--svi-tooltip-radius: {$tip_radius}px";

		$vars[] = "--svi-align: {$align}";

		$style_attr = implode('; ', $vars);

		// Catalog Max Items Logic
		$available_slugs = array_map('sanitize_title', array_map('strval', $options));
		$max_items    = $is_catalog ? (int) svi_get_option('catalog_max_items', 0) : 0;
		$count        = 0;
		$hidden_count = 0;

		// Attribute label for the group (screen readers + visible label).
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce attribute label filter.
		$attribute_label = wc_attribute_label($attribute, $product);

		// --- Generate HTML ---

		$wrapper_classes = [
			'svi-swatches-wrapper',
			$shape_class,
			'svi-overflow-' . sanitize_html_class($overflow),
			$is_catalog ? 'svi-catalog-wrapper' : '',
		];

		// The variation map travels with the markup so the availability engine
		// also works on builds that do not publish window.wcsvi.
		$availability_attr = '';

		if (!$is_catalog) {
			$availability = SVI_Swatches::get_variation_map($product_id);

			if (!empty($availability['variations'])) {
				$availability_attr = ' data-availability="' . esc_attr(wp_json_encode($availability)) . '"';
			}
		}

		$html = sprintf(
			'<div class="%s" data-attribute="%s" data-type="%s" data-catalog-method="%s" data-permalink="%s" data-link="%s" style="%s" data-clear="%s" data-oos="%s" data-tooltip-mode="%s" data-show-cart="%s" data-image-action="%s"%s role="radiogroup" aria-label="%s">',
			esc_attr(implode(' ', array_filter($wrapper_classes))),
			esc_attr($attribute),
			esc_attr($swatch_type),
			esc_attr($catalog_method),
			esc_url($permalink),
			esc_attr($catalog_link_variation ? 'true' : 'false'),
			esc_attr($style_attr),
			esc_attr($clear_reselect ? 'true' : 'false'),
			esc_attr($oos_behavior),
			esc_attr($tooltip_mode),
			esc_attr($show_cart ? 'true' : 'false'),
			esc_attr($catalog_image_action),
			$availability_attr, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			esc_attr($attribute_label)
		);

		// Chosen value markup. It is printed at the end of the row as a fallback;
		// swatches.js moves the span next to the attribute name ("Color: Red")
		// when the variations table provides a label cell.
		$label_html = !$is_catalog && $show_label
			? '<span class="svi-attribute-label-selected" aria-live="polite" hidden></span>'
			: '';

		foreach ($terms as $term) {
			$slug  = $term['slug'];
			$label = $term['name'];

			// A term missing from $options has no variation at all: the
			// combination does not exist, which is not the same as out of stock.
			// Custom attribute options keep their label case ("Plain"), so both
			// sides are compared as slugs.
			$is_available = in_array($slug, $available_slugs, true);
			$oos_class    = '';

			if (!$is_available) {
				$oos_class = 'svi-disabled';

				if ('hide' === $oos_behavior) {
					continue;
				}
			}

			// Catalog Limit.
			if ($is_catalog && $max_items > 0 && $count >= $max_items) {
				$hidden_count++;
				$count++;
				continue;
			}

			// Resolve visual values: per-product override, then stored term meta
			// from either key family, then (optionally) the variation image.
			$values = $swatch_instance->resolve_swatch_values($product_id, $attribute, $slug, (int) $term['term_id']);

			if (empty($values['colors']) && 0 === $values['image'] && '' === $values['label'] && svi_get_option('swatch_auto_image', false)) {
				$auto_image = $swatch_instance->get_auto_image($product_id, $attribute, $slug);

				if ($auto_image > 0) {
					$values['image'] = $auto_image;
				}
			}

			if (0 === $values['image'] && 'image' === $swatch_type && svi_get_option('swatch_auto_image', false)) {
				$values['image'] = $swatch_instance->get_auto_image($product_id, $attribute, $slug);
			}

			$selected_class = ($selected_slug === $slug) ? 'selected' : '';
			$inner          = '';
			$swatch_classes = [
				'svi-swatch',
				'svi-swatch-' . sanitize_html_class($swatch_type),
				$selected_class,
				$oos_class,
			];

			// Build the inner visual for the configured type, falling back to
			// the next available representation so no swatch renders empty.
			if ('color' === $swatch_type && !empty($values['colors'])) {
				$inner = sprintf(
					'<span class="svi-swatch-inner" style="%s"></span>',
					esc_attr(SVI_Swatch_Values::build_color_css($values['colors']))
				);
			} elseif ('image' === $swatch_type && $values['image'] > 0) {
				$img_src = wp_get_attachment_image_url($values['image'], 'thumbnail');

				if ($img_src) {
					$inner = sprintf(
						'<img src="%s" alt="" class="svi-swatch-image" loading="lazy" decoding="async" />',
						esc_url($img_src)
					);
				}
			} elseif ('radio' === $swatch_type) {
				$inner = '<span class="svi-radio-circle" aria-hidden="true"></span><span class="svi-radio-label">' . esc_html($label) . '</span>';
			}

			if ('' === $inner) {
				// Text/button type, or a type without stored data.
				$forced_label = ('text' === $swatch_type || 'label' === $swatch_type) ? $values['label'] : '';

				if ('' !== $forced_label) {
					$swatch_classes[] = 'svi-has-custom-label';
					$inner            = '<span class="svi-swatch-text">' . esc_html($forced_label) . '</span>';
				} elseif ('color' === $swatch_type || 'image' === $swatch_type) {
					// Configured as colour/image but nothing stored: keep the
					// stored label when present, otherwise show the term name.
					$swatch_classes[] = 'svi-swatch-fallback';
					$inner            = '<span class="svi-swatch-text">' . esc_html('' !== $values['label'] ? $values['label'] : $label) . '</span>';
				} else {
					$inner = '<span class="svi-swatch-text">' . esc_html($label) . '</span>';
				}
			}

			// One tooltip element carries both the picture and the name, so the
			// legacy large preview can no longer float on top of the label.
			$tooltip_html = '';

			if (!$is_catalog && 'none' !== $tooltip_mode) {
				$tooltip_label = '' !== $values['label'] ? $values['label'] : $label;
				$tooltip_focus = $tooltip_mode;

				// "Image" always shows the picture; the legacy large preview only
				// adds one when the tooltip is text based.
				$tooltip_id = $values['image'];

				if ('image' !== $tooltip_mode && !$preview) {
					$tooltip_id = 0;
				}

				$tip_inner   = '';
				$tip_classes = ['svi-tooltip'];

				if ($tooltip_id > 0) {
					$large_src = wp_get_attachment_image_url($tooltip_id, 'medium');

					if ($large_src) {
						$tip_classes[] = 'has-image';
						// No lazy loading: the card only shows on hover and a
						// deferred image would pop in with a collapsed box.
						$tip_inner .= wp_get_attachment_image(
							$tooltip_id,
							'medium',
							false,
							[
								'alt'     => '',
								'loading' => false,
								'class'   => 'svi-tooltip-image',
							]
						);
					}
				}

				// Image mode is picture only; without a picture the name is the tooltip.
				if ('image' !== $tooltip_focus || '' === $tip_inner) {
					$tip_inner .= '<span class="svi-tooltip-label">' . esc_html($tooltip_label) . '</span>';
				}

				$tooltip_html = sprintf(
					'<span class="%s" aria-hidden="true">%s</span>',
					esc_attr(implode(' ', $tip_classes)),
					$tip_inner
				);
			}

			$preview_html = '';

			$is_disabled = !$is_available;

			// Catalog cards swap the product image and link to a preselected
			// variation, so both need the value that was clicked.
			$extra = '';

			if ($is_catalog) {
				$card_image = $values['image'] > 0 ? wp_get_attachment_image_url($values['image'], 'woocommerce_thumbnail') : '';

				if (!$card_image && 'none' !== $catalog_image_action) {
					$auto = $swatch_instance->get_auto_image($product_id, $attribute, $slug);
					$card_image = $auto > 0 ? wp_get_attachment_image_url($auto, 'woocommerce_thumbnail') : '';
				}

				if ($card_image) {
					$extra .= ' data-image="' . esc_url($card_image) . '"';
				}

				if ($is_available && $catalog_link_variation && '' !== $permalink) {
					$extra .= ' data-url="' . esc_url(add_query_arg('attribute_' . $attribute, $slug, $permalink)) . '"';
				}
			}

			$html .= sprintf(
				'<button type="button" class="%1$s" data-value="%2$s" data-label="%3$s" role="radio" aria-checked="%4$s" aria-label="%3$s"%5$s%9$s>%6$s%7$s%8$s</button>',
				esc_attr(implode(' ', array_filter($swatch_classes))),
				esc_attr($slug),
				esc_attr($label),
				$selected_class ? 'true' : 'false',
				$is_disabled ? ' aria-disabled="true" tabindex="-1"' : ' tabindex="-1"',
				$inner,
				$tooltip_html,
				$preview_html,
				$extra // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
			);

			$count++;
		}

		// Fallback position: inline at the end of the row. swatches.js moves it
		// into the attribute label when that cell exists.
		$html .= $label_html;

		// Show More Link.
		if ($is_catalog && $hidden_count > 0) {
			$html .= sprintf(
				'<a href="%s" class="svi-show-more-link">+%d %s</a>',
				esc_url($permalink),
				$hidden_count,
				esc_html__('more', 'smart-variations-images')
			);
		}

		$html .= '</div>';

		return $html;
	}
}
