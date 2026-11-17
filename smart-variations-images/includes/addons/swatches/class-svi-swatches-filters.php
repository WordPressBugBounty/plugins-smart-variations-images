<?php
/**
 * Swatches Filter Widget Handling
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles rendering swatches in layered nav filters.
 */
class SVI_Swatches_Filters {

	/**
	 * Init hooks.
	 */
	public static function init(): void {
		add_filter( 'woocommerce_layered_nav_term_html', [ __CLASS__, 'render_filter_swatches' ], 10, 4 );
	}

	/**
	 * Render swatch inside filter widget term.
	 *
	 * @param string $term_html Original HTML.
	 * @param object $term      Taxonomy Term.
	 * @param string $link      Filter Link.
	 * @param int    $count     Count.
	 * @return string Modified HTML.
	 */
	public static function render_filter_swatches( $term_html, $term, $link, $count ): string {
		$taxonomy = $term->taxonomy; // e.g., 'pa_color'
		
		// Only handle product attributes
		if ( strpos( $taxonomy, 'pa_' ) !== 0 ) {
			return $term_html;
		}

		$attribute_id = wc_attribute_taxonomy_id_by_name( substr( $taxonomy, 3 ) );
		$swatch_instance = SVI_Swatches::instance();

		// Check if "Show in Filters" is enabled
		$show_in_filters = $swatch_instance->get_attribute_option( $attribute_id, 'show_in_filters', 'no' );

		if ( $show_in_filters !== 'yes' ) {
			return $term_html;
		}

		// Get Swatch Type
		$swatch_type  = $swatch_instance->get_attribute_swatch_type( $attribute_id );
		
		if ( ! $swatch_type || $swatch_type === '' ) {
			return $term_html;
		}

		// Get Term Meta
		$color    = get_term_meta( $term->term_id, 'svi_swatch_color', true );
		$image_id = get_term_meta( $term->term_id, 'svi_swatch_image_id', true );

		$swatch_markup = '';
		$style = 'display:inline-block; width:20px; height:20px; border-radius:50%; margin-right:8px; vertical-align:middle; border:1px solid #ddd;';

		if ( $swatch_type === 'color' && $color ) {
			$swatch_markup = sprintf( '<span class="svi-filter-swatch svi-type-color" style="%s background-color:%s;"></span>', $style, esc_attr( $color ) );
		} elseif ( $swatch_type === 'image' && $image_id ) {
			$img_src = wp_get_attachment_thumb_url( $image_id );
			$swatch_markup = sprintf( '<img src="%s" class="svi-filter-swatch svi-type-image" style="%s object-fit:cover;" />', esc_url( $img_src ), $style );
		}

		if ( $swatch_markup ) {
			// Prepend swatch to the term name in the link
			// The $term_html is typically: <a href...>Name</a> <span class="count">...</span>
			// We want: <a href...><span class="swatch"></span> Name</a> ...
			
			// Simple replace for now, replacing the Term Name with Swatch + Term Name
			// CAUTION: This might be fragile if themes customize filter HTML heavily.
			// A safer way is to return the swatch + html, but inside the anchor?
			
			// Woo core passes: <a rel="nofollow" href="...">Term Name</a> <span class="count">(1)</span>
			// or with "chosen" class.
			
			// Let's protect the term name match
			$name = $term->name;
			$term_html = str_replace( '>' . $name . '<', '>' . $swatch_markup . ' ' . $name . '<', $term_html );
		}

		return $term_html;
	}
}
