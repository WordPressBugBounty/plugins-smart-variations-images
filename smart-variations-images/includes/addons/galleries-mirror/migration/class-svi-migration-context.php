<?php
/**
 * SVI Galleries v3 — migration resolution context (WooCommerce-coupled, READ-ONLY).
 *
 * @package Smart_Variations_Images
 * @since   5.4.0
 */

/**
 * Builds the WP-coupled resolution context consumed by the pure schema 1→3
 * transform (SVI_Gallery_Schema::upgrade_schema_1_to_3()).
 *
 * Phase 1 deliberately kept the transform free of WordPress/WooCommerce calls;
 * this class is the other half of that split: it reads the live taxonomy terms,
 * custom-attribute options and variation attribute sets for a product and hands
 * them to the transform as a plain array.
 *
 * Resolution is exact-first, then the bounded ladder already implemented in
 * SVI_Gallery_Schema (dash/underscore/%20/space, lowercase, sanitize-title
 * collapsing, optional transliteration). No open-ended similar_text heuristics.
 *
 * This class MUST NOT write: no update_post_meta, no $product->save(), no
 * term inserts. It is read-only by contract.
 */
class SVI_Migration_Context {

	/**
	 * Build the resolution context for a product.
	 *
	 * @param WC_Product $product Product to inspect.
	 * @return array{term_map:array, custom_option_map:array, variation_map:array, trid_map:array}
	 */
	public static function build( WC_Product $product ) {
		$term_map          = array();
		$custom_option_map = array();
		$variation_map     = array();
		$trid_map          = array();

		foreach ( (array) $product->get_attributes() as $attribute ) {
			if ( ! $attribute instanceof WC_Product_Attribute ) {
				continue;
			}

			$key = (string) $attribute->get_name();
			if ( '' === $key ) {
				continue;
			}

			if ( $attribute->is_taxonomy() ) {
				$slug_to_id = array();
				foreach ( (array) $attribute->get_terms() as $term ) {
					if ( $term instanceof WP_Term ) {
						$slug_to_id[ (string) $term->slug ] = (int) $term->term_id;
					} elseif ( is_object( $term ) && isset( $term->slug, $term->term_id ) ) {
						$slug_to_id[ (string) $term->slug ] = (int) $term->term_id;
					}
				}
				if ( ! empty( $slug_to_id ) ) {
					$term_map[ $key ] = $slug_to_id;
				}
			} else {
				$slug_to_raw = array();
				foreach ( (array) $attribute->get_options() as $option ) {
					$slug_to_raw[ SVI_Gallery_Schema::normalise_slug( (string) $option ) ] = (string) $option;
				}
				if ( ! empty( $slug_to_raw ) ) {
					$custom_option_map[ $key ] = $slug_to_raw;
				}
			}
		}

		foreach ( (array) $product->get_children() as $variation_id ) {
			$variation_id = (int) $variation_id;
			$variation    = wc_get_product( $variation_id );
			if ( ! $variation instanceof WC_Product_Variation ) {
				continue;
			}

			$variation_map[] = array(
				'id'         => $variation_id,
				'attributes' => (array) $variation->get_attributes(),
			);

			$trid = self::variation_trid( $variation_id );
			if ( $trid > 0 ) {
				$trid_map[ $variation_id ] = $trid;
			}
		}

		return array(
			'term_map'          => $term_map,
			'custom_option_map' => $custom_option_map,
			'variation_map'     => $variation_map,
			'trid_map'          => $trid_map,
		);
	}

	/**
	 * Resolve a variation's WPML translation id (feature-detected, no WPML hard
	 * dependency). Returns 0 when WPML is absent or the id is unknown.
	 *
	 * @param int $variation_id Variation id.
	 * @return int
	 */
	private static function variation_trid( $variation_id ) {
		if ( function_exists( 'wpml_get_content_trid' ) ) {
			return (int) wpml_get_content_trid( (int) $variation_id );
		}
		return 0;
	}
}
