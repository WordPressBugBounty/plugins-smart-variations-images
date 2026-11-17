<?php
/**
 * Swatches Catalog Handling
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles rendering swatches in shop catalog.
 */
class SVI_Swatches_Catalog {

	/**
	 * Init hooks.
	 */
	public static function init(): void {
		add_action( 'woocommerce_after_shop_loop_item', [ __CLASS__, 'render_catalog_swatches' ], 15 );
	}

	/**
	 * Render swatches in loop.
	 */
	public static function render_catalog_swatches(): void {
		global $product;

		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return;
		}

		$attributes = $product->get_variation_attributes();
		$swatch_instance = SVI_Swatches::instance();

		foreach ( $attributes as $name => $options ) {
			// $name is 'pa_color' (slug)
			// Get ID
			$attribute_id = wc_attribute_taxonomy_id_by_name( $name );
			
			// Check if Catalog Mode is enabled for this attribute
			$show_in_catalog = $swatch_instance->get_attribute_option( $attribute_id, 'show_in_catalog', 'no' );
			
			if ( $show_in_catalog !== 'yes' ) {
				continue;
			}

			// Get rendering options
			$swatch_type = $swatch_instance->get_attribute_swatch_type( $attribute_id );
			$method      = $swatch_instance->get_attribute_option( $attribute_id, 'catalog_swatch_method', 'link' );
			$overflow    = $swatch_instance->get_attribute_option( $attribute_id, 'overflow', 'stacked' );
			$shape       = $swatch_instance->get_attribute_option( $attribute_id, 'swatch_shape', 'round' );
			$size_w      = $swatch_instance->get_attribute_option( $attribute_id, 'swatch_size_w', 30 );
			$size_h      = $swatch_instance->get_attribute_option( $attribute_id, 'swatch_size_h', 30 );

			// Render Wrapper
			echo sprintf( 
				'<div class="svi-catalog-swatches svi-shape-%s svi-overflow-%s" style="--svi-swatch-w:%spx; --svi-swatch-h:%spx;">',
				esc_attr( $shape ),
				esc_attr( $overflow ),
				esc_attr( $size_w ),
				esc_attr( $size_h )
			);

			$terms = wc_get_product_terms( $product->get_id(), $name, [ 'fields' => 'all' ] );

			foreach ( $terms as $term ) {
				// Only show available options
				if ( ! in_array( $term->slug, $options ) ) {
					continue;
				}

				// Data for logic
				$color    = get_term_meta( $term->term_id, 'svi_swatch_color', true );
				$image_id = get_term_meta( $term->term_id, 'svi_swatch_image_id', true );
				$img_src  = $image_id ? wp_get_attachment_thumb_url( $image_id ) : '';
				
				// Href logic
				// Link to product page with pre-selection
				$permalnk = $product->get_permalink();
				$link     = add_query_arg( 'attribute_' . $name, $term->slug, $permalnk );

				// Determine Content
				$content = '';
				if ( $swatch_type === 'color' && $color ) {
					$content = sprintf( '<span class="svi-swatch-inner" style="background-color:%s;"></span>', esc_attr( $color ) );
				} elseif ( $swatch_type === 'image' && $img_src ) {
					$content = sprintf( '<img src="%s" alt="%s" class="svi-swatch-image" />', esc_url( $img_src ), esc_attr( $term->name ) );
				} else {
					$content = sprintf( '<span class="svi-swatch-text">%s</span>', esc_html( $term->name ) );
				}

				// Interaction Logic (Link vs Image Swap)
				// For now simple link. For image swap we need JS and data attr.
				// Adding data-src-large for image swap logic
				$large_src = $image_id ? wp_get_attachment_url( $image_id ) : '';
				
				// For Variation Image Swap in catalog, we need the VARIATION image, not the TERM image.
				// But finding exact variation match in loop is expensive.
				// Iconic usually swaps to the TERM image or tries to find variation.
				// Let's stick to TERM image if method is 'image', or fallback to product link.

				echo sprintf( 
					'<a href="%s" class="svi-swatch svi-type-%s" title="%s" data-catalog-method="%s" data-image-src="%s">
						%s
					</a>',
					esc_url( $link ),
					esc_attr( $swatch_type ),
					esc_attr( $term->name ),
					esc_attr( $method ),
					esc_url( $large_src ), // Used for image swap
					$content
				);
			}

			echo '</div>';
		}
	}
}
