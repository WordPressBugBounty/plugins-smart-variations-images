<?php
/**
 * Swatches Settings
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Handles settings registration for Swatches.
 */
class SVI_Swatches_Settings
{

	/**
	 * Init settings hooks.
	 */
	public static function init(): void
	{
		add_filter('woosvi_options_framework_tabs', [__CLASS__, 'register_tab']);
		add_action('admin_notices', [__CLASS__, 'render_import_notice']);
	}

	/**
	 * Report the result of a swatch definitions import.
	 */
	public static function render_import_notice(): void
	{
		if (!isset($_GET['svi_swatches_import'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only flash message.
			return;
		}

		$status = sanitize_key(wp_unslash($_GET['svi_swatches_import'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ('converted' === $status) {
			$types = isset($_GET['svi_swatches_types']) ? absint($_GET['svi_swatches_types']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$terms = isset($_GET['svi_swatches_termmeta']) ? absint($_GET['svi_swatches_termmeta']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html(sprintf(
					/* translators: 1: number of attributes, 2: number of term values. */
					__('Legacy swatch data converted: %1$d attributes and %2$d term values.', 'smart-variations-images'),
					$types,
					$terms
				))
			);

			return;
		}

		if ('done' === $status) {
			$terms = isset($_GET['svi_swatches_terms']) ? absint($_GET['svi_swatches_terms']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$products = isset($_GET['svi_swatches_products']) ? absint($_GET['svi_swatches_products']) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html(sprintf(
					/* translators: 1: number of terms, 2: number of products. */
					__('Swatch definitions imported: %1$d values and %2$d product overrides.', 'smart-variations-images'),
					$terms,
					$products
				))
			);

			return;
		}

		printf(
			'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
			esc_html__('The swatch definitions file could not be imported. Please upload a JSON file exported by this plugin.', 'smart-variations-images')
		);
	}

	/**
	 * Register the Swatches settings tab.
	 *
	 * @param array $tabs Existing tabs.
	 * @return array Modified tabs.
	 */
	public static function register_tab(array $tabs): array
	{
		$swatches_tab = [
			'label' => __('Swatches', 'smart-variations-images'),
			'priority' => 55, // Place after General Options (50)
			'description' => __('Configure the appearance and behavior of variation swatches. <strong>Note:</strong> These settings apply to attributes that are configured with a specific type (Color, Image, etc.) in the global attribute settings.', 'smart-variations-images'),
			'sections' => [
				'general' => [
					'id' => 'general',
					'title' => __('General', 'smart-variations-images'),
					'fields' => [
						'swatch_shape' => [
							'id' => 'swatch_shape',
							'type' => 'select',
							'label' => __('Swatch Shape', 'smart-variations-images'),
							'options' => [
								'round' => __('Round', 'smart-variations-images'),
								'square' => __('Square', 'smart-variations-images'),
								'rounded' => __('Rounded (custom radius)', 'smart-variations-images'),
								'auto' => __('Automatic (legacy per type)', 'smart-variations-images'),
							],
							'default' => 'round',
						],
					],
				],
				'styling' => [
					'id' => 'styling',
					'title' => __('Styling', 'smart-variations-images'),
					'fields' => [
						// Dimensions
						'swatch_size_w' => [
							'id' => 'swatch_size_w',
							'type' => 'number',
							'label' => __('Width (px)', 'smart-variations-images'),
							'default' => 30,
						],
						'swatch_size_h' => [
							'id' => 'swatch_size_h',
							'type' => 'number',
							'label' => __('Height (px)', 'smart-variations-images'),
							'default' => 30,
						],
						'swatch_radius' => [
							'id' => 'swatch_radius',
							'type' => 'number',
							'label' => __('Border Radius (px)', 'smart-variations-images'),
							'default' => 30, // Default round
							'conditions' => [
								[
									'field' => 'swatch_shape',
									'operator' => 'equals',
									'value' => 'rounded',
								],
							],
						],
						'swatch_border_width' => [
							'id' => 'swatch_border_width',
							'type' => 'number',
							'label' => __('Border Width (px)', 'smart-variations-images'),
							'default' => 1,
						],
						'swatch_fontsize' => [
							'id' => 'swatch_fontsize',
							'type' => 'number',
							'label' => __('Font Size (px)', 'smart-variations-images'),
							'default' => 14,
						],
						// Normal State Colors
						'swatch_color_text' => [
							'id' => 'swatch_color_text',
							'type' => 'color',
							'label' => __('Text Color', 'smart-variations-images'),
							'default' => '#333333',
						],
						'swatch_color_bg' => [
							'id' => 'swatch_color_bg',
							'type' => 'color',
							'label' => __('Background Color', 'smart-variations-images'),
							'default' => '#ffffff',
						],
						'swatch_color_border' => [
							'id' => 'swatch_color_border',
							'type' => 'color',
							'label' => __('Border Color', 'smart-variations-images'),
							'default' => '#dddddd',
						],
					],
				],
				'styling_hover' => [
					'id' => 'styling_hover',
					'title' => __('Hover State', 'smart-variations-images'),
					'fields' => [
						'hover_color_text' => [
							'id' => 'hover_color_text',
							'type' => 'color',
							'label' => __('Text Color (Hover)', 'smart-variations-images'),
							'default' => '#000000',
						],
						'hover_color_bg' => [
							'id' => 'hover_color_bg',
							'type' => 'color',
							'label' => __('Background Color (Hover)', 'smart-variations-images'),
							'default' => '#f0f0f0',
						],
						'hover_color_border' => [
							'id' => 'hover_color_border',
							'type' => 'color',
							'label' => __('Border Color (Hover)', 'smart-variations-images'),
							'default' => '#999999',
						],
						'hover_scale' => [
							'id' => 'hover_scale',
							'type' => 'number',
							'label' => __('Scale Effect', 'smart-variations-images'),
							'desc' => __('Zoom factor on hover (e.g., 1.1 for 10% zoom). Set 1 to disable.', 'smart-variations-images'),
							'default' => 1.0,
							'input_attrs' => ['step' => '0.1', 'min' => 1],
						],
					],
				],
				'styling_selected' => [
					'id' => 'styling_selected',
					'title' => __('Selected State', 'smart-variations-images'),
					'fields' => [
						'selected_color_text' => [
							'id' => 'selected_color_text',
							'type' => 'color',
							'label' => __('Text Color (Selected)', 'smart-variations-images'),
							'default' => '#ffffff',
						],
						'selected_color_bg' => [
							'id' => 'selected_color_bg',
							'type' => 'color',
							'label' => __('Background Color (Selected)', 'smart-variations-images'),
							'default' => '#333333',
						],
						'selected_color_border' => [
							'id' => 'selected_color_border',
							'type' => 'color',
							'label' => __('Border Color (Selected)', 'smart-variations-images'),
							'default' => '#333333',
						],
					],
				],
				'tooltip' => [
					'id' => 'tooltip',
					'title' => __('Tooltip', 'smart-variations-images'),
					'fields' => [
						'enable_tooltips' => [
							'id' => 'enable_tooltips',
							'type' => 'toggle',
							'label' => __('Enable Tooltips', 'smart-variations-images'),
							'default' => true,
						],
						'tooltip_mode' => [
							'id' => 'tooltip_mode',
							'type' => 'select',
							'label' => __('Tooltip Content', 'smart-variations-images'),
							'desc' => __('Choose what the tooltip shows: the value name, a larger image, or nothing.', 'smart-variations-images'),
							'options' => [
								'text' => __('Value name', 'smart-variations-images'),
								'image' => __('Image preview', 'smart-variations-images'),
								'none' => __('No tooltip', 'smart-variations-images'),
							],
							'default' => 'text',
							'conditions' => [
								[
									'field' => 'enable_tooltips',
									'operator' => 'equals',
									'value' => true,
								],
							],
						],
						'tooltip_color_text' => [
							'id' => 'tooltip_color_text',
							'type' => 'color',
							'label' => __('Tooltip Text Color', 'smart-variations-images'),
							'default' => '#ffffff',
							'conditions' => [
								[
									'field' => 'enable_tooltips',
									'operator' => 'equals',
									'value' => true,
								],
							],
						],
						'tooltip_color_bg' => [
							'id' => 'tooltip_color_bg',
							'type' => 'color',
							'label' => __('Tooltip Background', 'smart-variations-images'),
							'default' => '#000000',
							'conditions' => [
								[
									'field' => 'enable_tooltips',
									'operator' => 'equals',
									'value' => true,
								],
							],
						],
						'tooltip_border_radius' => [
							'id' => 'tooltip_border_radius',
							'type' => 'number',
							'label' => __('Border Radius (px)', 'smart-variations-images'),
							'default' => 4,
							'conditions' => [
								[
									'field' => 'enable_tooltips',
									'operator' => 'equals',
									'value' => true,
								],
							],
						],
						'large_preview' => [
							'id' => 'large_preview',
							'type' => 'toggle',
							'label' => __('Large Image Preview', 'smart-variations-images'),
							'desc' => __('Show a larger preview of the image swatch on hover.', 'smart-variations-images'),
							'default' => true,
							'conditions' => [
								[
									'field' => 'enable_tooltips',
									'operator' => 'equals',
									'value' => true,
								],
							],
						],
					],
				],
				'product_page' => [
					'id' => 'product_page',
					'title' => __('Product Page', 'smart-variations-images'),
					'fields' => [
						'clear_on_reselect' => [
							'id' => 'clear_on_reselect',
							'type' => 'toggle',
							'label' => __('Clear on Reselect', 'smart-variations-images'),
							'desc' => __('Clicking the selected swatch again will clear the selection.', 'smart-variations-images'),
							'default' => false,
						],
						'show_selected_label' => [
							'id' => 'show_selected_label',
							'type' => 'toggle',
							'label' => __('Show Selected Label', 'smart-variations-images'),
							'desc' => __('Display the name of the selected attribute, e.g., Color: Red.', 'smart-variations-images'),
							'default' => true,
						],
						'oos_behavior' => [
							'id' => 'oos_behavior',
							'type' => 'select',
							'label' => __('Out of Stock Behavior', 'smart-variations-images'),
							'options' => [
								'blur' => __('Blur', 'smart-variations-images'),
								'dim' => __('Dim only', 'smart-variations-images'),
								'cross' => __('Strikethrough', 'smart-variations-images'),
								'hide' => __('Hide', 'smart-variations-images'),
							],
							'default' => 'blur',
						],
						'swatch_align' => [
							'id' => 'swatch_align',
							'type' => 'select',
							'label' => __('Alignment', 'smart-variations-images'),
							'options' => [
								'flex-start' => __('Left', 'smart-variations-images'),
								'center' => __('Center', 'smart-variations-images'),
								'flex-end' => __('Right', 'smart-variations-images'),
							],
							'default' => 'flex-start',
						],
					],
				],
				'catalog' => [
					'id' => 'catalog',
					'title' => __('Catalog / Shop', 'smart-variations-images'),
					'fields' => [
						'show_in_catalog' => [
							'id' => 'show_in_catalog',
							'type' => 'toggle',
							'label' => __('Enable on Catalog', 'smart-variations-images'),
							'default' => false,
						],
						'catalog_swatch_align' => [
							'id' => 'catalog_swatch_align',
							'type' => 'select',
							'label' => __('Alignment', 'smart-variations-images'),
							'options' => [
								'flex-start' => __('Left', 'smart-variations-images'),
								'center' => __('Center', 'smart-variations-images'),
								'flex-end' => __('Right', 'smart-variations-images'),
							],
							'default' => 'flex-start',
							'conditions' => [
								[
									'field' => 'show_in_catalog',
									'operator' => 'equals',
									'value' => true,
								],
							],
						],
						'catalog_position' => [
							'id' => 'catalog_position',
							'type' => 'select',
							'label' => __('Showcase Position', 'smart-variations-images'),
							'desc' => __('Choose where to display the swatches in the shop loop.', 'smart-variations-images'),
							'options' => [
								'woocommerce_before_shop_loop_item' => __('Display before product loop item', 'smart-variations-images'),
								'woocommerce_before_shop_loop_item_title' => __('Display before product title', 'smart-variations-images'),
								'woocommerce_shop_loop_item_title' => __('Display next to product title', 'smart-variations-images'),
								'woocommerce_after_shop_loop_item_title' => __('Display after product title', 'smart-variations-images'),
								'woocommerce_after_shop_loop_item' => __('Display after product loop item', 'smart-variations-images'),
							],
							'default' => 'woocommerce_after_shop_loop_item',
							'conditions' => [
								[
									'field' => 'show_in_catalog',
									'operator' => 'equals',
									'value' => true,
								],
							],
						],
						'catalog_position_priority' => [
							'id' => 'catalog_position_priority',
							'type' => 'number',
							'label' => __('Position Priority', 'smart-variations-images'),
							'desc' => __('Order priority for the hook execution. Lower numbers display earlier.', 'smart-variations-images'),
							'default' => 30,
							'conditions' => [
								[
									'field' => 'show_in_catalog',
									'operator' => 'equals',
									'value' => true,
								],
							],
						],
						'catalog_swatch_size' => [
							'id' => 'catalog_swatch_size',
							'type' => 'number',
							'label' => __('Swatch Size (px)', 'smart-variations-images'),
							'desc' => __('Override global swatch size for catalog. Default: 30', 'smart-variations-images'),
							'default' => 30,
							'conditions' => [
								[
									'field' => 'show_in_catalog',
									'operator' => 'equals',
									'value' => true,
								],
							],
						],
						'catalog_swatch_gap' => [
							'id' => 'catalog_swatch_gap',
							'type' => 'number',
							'label' => __('Swatch Gap (px)', 'smart-variations-images'),
							'desc' => __('Space between swatches.', 'smart-variations-images'),
							'default' => 5,
							'conditions' => [
								[
									'field' => 'show_in_catalog',
									'operator' => 'equals',
									'value' => true,
								],
							],
						],
						'catalog_swatch_border_radius' => [
							'id' => 'catalog_swatch_border_radius',
							'type' => 'number',
							'label' => __('Border Radius (px)', 'smart-variations-images'),
							'default' => 30,
							'conditions' => [
								[
									'field' => 'show_in_catalog',
									'operator' => 'equals',
									'value' => true,
								],
							],
						],
						'catalog_swatch_border_width' => [
							'id' => 'catalog_swatch_border_width',
							'type' => 'number',
							'label' => __('Border Width (px)', 'smart-variations-images'),
							'desc' => __('Leave empty to inherit global settings.', 'smart-variations-images'),
							'default' => '',
							'conditions' => [
								[
									'field' => 'show_in_catalog',
									'operator' => 'equals',
									'value' => true,
								],
							],
						],
						'catalog_hover_style' => [
							'id' => 'catalog_hover_style',
							'type' => 'select',
							'label' => __('Hover Effect', 'smart-variations-images'),
							'options' => [
								'border' => __('Border Change', 'smart-variations-images'),
								'fade' => __('Fade/Opacity', 'smart-variations-images'),
								'none' => __('None', 'smart-variations-images'),
							],
							'default' => 'border',
							'conditions' => [
								[
									'field' => 'show_in_catalog',
									'operator' => 'equals',
									'value' => true,
								],
							],
						],
						'catalog_hover_border_color' => [
							'id' => 'catalog_hover_border_color',
							'type' => 'color',
							'label' => __('Hover Border Color', 'smart-variations-images'),
							'default' => '#999999',
							'conditions' => [
								[
									'field' => 'show_in_catalog',
									'operator' => 'equals',
									'value' => true,
								],
								[
									'field' => 'catalog_hover_style',
									'operator' => 'equals',
									'value' => 'border',
								],
							],
						],
						'catalog_max_items' => [
							'id' => 'catalog_max_items',
							'type' => 'number',
							'label' => __('Max Items', 'smart-variations-images'),
							'desc' => __('Limit the number of swatches shown. 0 for unlimited.', 'smart-variations-images'),
							'default' => 0,
							'conditions' => [
								[
									'field' => 'show_in_catalog',
									'operator' => 'equals',
									'value' => true,
								],
							],
						],
						'catalog_overflow' => [
							'id' => 'catalog_overflow',
							'type' => 'select',
							'label' => __('Overflow Behavior', 'smart-variations-images'),
							'options' => [
								'stacked' => __('Stacked', 'smart-variations-images'),
								'single_line' => __('Single Line (Scroll)', 'smart-variations-images'),
								'slider' => __('Slider', 'smart-variations-images'),
							],
							'default' => 'stacked',
							'conditions' => [
								[
									'field' => 'show_in_catalog',
									'operator' => 'equals',
									'value' => true,
								],
							],
						],
						'catalog_add_to_cart' => [
							'id' => 'catalog_add_to_cart',
							'type' => 'toggle',
							'label' => __('Show Add to Cart', 'smart-variations-images'),
							'desc' => __('Show an Add to Cart button when a variation is selected.', 'smart-variations-images'),
							'default' => true,
							'conditions' => [
								[
									'field' => 'show_in_catalog',
									'operator' => 'equals',
									'value' => true,
								],
							],
						],
						// Slider Styling
						'catalog_slider_arrows_hover' => [
							'id' => 'catalog_slider_arrows_hover',
							'type' => 'toggle',
							'label' => __('Show Arrows on Hover Only', 'smart-variations-images'),
							'default' => true,
							'conditions' => [
								[
									'field' => 'show_in_catalog',
									'operator' => 'equals',
									'value' => true,
								],
								[
									'field' => 'catalog_overflow',
									'operator' => 'equals',
									'value' => 'slider',
								],
							],
						],
						'catalog_arrow_bg' => [
							'id' => 'catalog_arrow_bg',
							'type' => 'color',
							'label' => __('Arrow Background', 'smart-variations-images'),
							'default' => '#ffffff',
							'conditions' => [
								[
									'field' => 'show_in_catalog',
									'operator' => 'equals',
									'value' => true,
								],
								[
									'field' => 'catalog_overflow',
									'operator' => 'equals',
									'value' => 'slider',
								],
							],
						],
						'catalog_arrow_color' => [
							'id' => 'catalog_arrow_color',
							'type' => 'color',
							'label' => __('Arrow Icon Color', 'smart-variations-images'),
							'default' => '#333333',
							'conditions' => [
								[
									'field' => 'show_in_catalog',
									'operator' => 'equals',
									'value' => true,
								],
								[
									'field' => 'catalog_overflow',
									'operator' => 'equals',
									'value' => 'slider',
								],
							],
						],
						'catalog_arrow_border' => [
							'id' => 'catalog_arrow_border',
							'type' => 'color',
							'label' => __('Arrow Border Color', 'smart-variations-images'),
							'default' => '#dddddd',
							'conditions' => [
								[
									'field' => 'show_in_catalog',
									'operator' => 'equals',
									'value' => true,
								],
								[
									'field' => 'catalog_overflow',
									'operator' => 'equals',
									'value' => 'slider',
								],
							],
						],
						'catalog_attribute' => [
							'id' => 'catalog_attribute',
							'type' => 'text',
							'label' => __('Attribute to show', 'smart-variations-images'),
							'desc' => __('Attribute name, for example pa_color. Leave empty to show every variation attribute.', 'smart-variations-images'),
							'default' => '',
						],
						'catalog_image_action' => [
							'id' => 'catalog_image_action',
							'type' => 'select',
							'label' => __('Swap the card image on', 'smart-variations-images'),
							'desc' => __('Uses the SVI gallery image of the hovered value.', 'smart-variations-images'),
							'options' => [
								'none' => __('Do not swap', 'smart-variations-images'),
								'hover' => __('Hover', 'smart-variations-images'),
								'click' => __('Click', 'smart-variations-images'),
							],
							'default' => 'hover',
							'conditions' => [
								[
									'field' => 'show_in_catalog',
									'operator' => 'equals',
									'value' => true,
								],
							],
						],
						'catalog_link_variation' => [
							'id' => 'catalog_link_variation',
							'type' => 'toggle',
							'label' => __('Link to the preselected variation', 'smart-variations-images'),
							'desc' => __('Adds the clicked value to the product link so the product page opens with that variation selected.', 'smart-variations-images'),
							'default' => true,
							'conditions' => [
								[
									'field' => 'show_in_catalog',
									'operator' => 'equals',
									'value' => true,
								],
							],
						],
					],
				],
				'conversion' => [
					'id' => 'conversion',
					'title' => __('Automatic Conversion', 'smart-variations-images'),
					'fields' => [
						'swatch_type_default' => [
							'id' => 'swatch_type_default',
							'type' => 'select',
							'label' => __('Render attributes without a type as', 'smart-variations-images'),
							'desc' => __('Attributes that have no swatch type of their own are shown using this style instead of a dropdown. Choose "Dropdown" to leave them untouched.', 'smart-variations-images'),
							'options' => [
								'none' => __('Dropdown (no change)', 'smart-variations-images'),
								'text' => __('Text buttons', 'smart-variations-images'),
								'color' => __('Color (falls back to text)', 'smart-variations-images'),
								'image' => __('Image (falls back to text)', 'smart-variations-images'),
								'radio' => __('Radio buttons', 'smart-variations-images'),
							],
							'default' => 'none',
						],
						'swatch_auto_image' => [
							'id' => 'swatch_auto_image',
							'type' => 'toggle',
							'label' => __('Use the variation image', 'smart-variations-images'),
							'desc' => __('When a value has no image of its own, use the first image of a variation that contains it.', 'smart-variations-images'),
							'default' => false,
						],
					],
				],
				'filters' => [
					'id' => 'filters',
					'title' => __('Filters', 'smart-variations-images'),
					'fields' => [
						'swatch_filter_widget' => [
							'id' => 'swatch_filter_widget',
							'type' => 'toggle',
							'label' => __('Swatches in the "Filter by attribute" widget', 'smart-variations-images'),
							'default' => false,
						],
						'swatch_filter_min_count' => [
							'id' => 'swatch_filter_min_count',
							'type' => 'number',
							'label' => __('Hide values with fewer products than', 'smart-variations-images'),
							'default' => 0,
							'conditions' => [
								[
									'field' => 'swatch_filter_widget',
									'operator' => 'equals',
									'value' => true,
								],
							],
						],
						'swatch_filter_blocks_note' => [
							'id' => 'swatch_filter_blocks_note',
							'type' => 'html',
							'html' => '<p class="description">'
								. esc_html__('Block themes: enable "Color / image" as the attribute type in Products > Attributes. Swatch colours and images are stored in the format that type understands, so the native filter block renders them without extra setup.', 'smart-variations-images')
								. '</p>',
						],
					],
				],
				'import_export' => [
					'id' => 'import_export',
					'title' => __('Swatch Definitions', 'smart-variations-images'),
					'fields' => [
						'swatch_definitions_tools' => [
							'id' => 'swatch_definitions_tools',
							'type' => 'html',
							'html' => SVI_Swatches::get_definitions_tools_html(),
						],
					],
				],
			],
			'return' => isset($tabs['swatches']['return']) ? $tabs['swatches']['return'] : null, // Preserve if exists
		];

		$tabs['swatches'] = $swatches_tab;

		return $tabs;
	}
}
