<?php
/**
 * SVI Galleries — schema 1 (woosvi_slug) / schema 2 -> schema 3 conversion core.
 *
 * Split out of SVI_Gallery_Schema so each file stays reviewable. Pure PHP: no
 * WordPress or WooCommerce calls, the resolved context is passed in.
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if ( ! class_exists( 'SVI_Gallery_Schema_Upgrade' ) ) {
class SVI_Gallery_Schema_Upgrade {
	/**
	 * Pure schema 1 (woosvi_slug) → 3 conversion core.
	 *
	 * Takes an explicit resolved context (no get_term/get_post inside):
	 *  - term_map:         taxonomy => [slug => term_id]
	 *  - custom_option_map:attribute_key => [slug => raw_value]
	 *  - variation_map:    [ [id, attributes => [tax|key => slug|raw]] ]
	 *  - trid_map:         variation_id => trid (WPML)
	 *
	 * @param mixed $legacy  Raw woosvi_slug array.
	 * @param array $context Resolution context.
	 * @return array
	 */
	public static function schema_1_to_3( $legacy, array $context = array() ) {
		$legacy  = is_array( $legacy ) ? array_values( $legacy ) : array();
		$entries = array();

		foreach ( $legacy as $raw ) {
			if ( ! is_array( $raw ) ) {
				continue;
			}
			$entry = self::upgrade_schema_1_entry( $raw, $context );
			if ( null !== $entry ) {
				$entries[] = $entry;
			}
		}

		return SVI_Gallery_Schema::canonicalise( array( 'entries' => $entries ) );
	}

	/**
	 * Convert a single legacy entry, classifying per §7.3.
	 *
	 * @param array $raw
	 * @param array $context
	 * @return array|null
	 */
	private static function upgrade_schema_1_entry( array $raw, array $context ) {
		$slugs = isset( $raw['slugs'] ) && is_array( $raw['slugs'] ) ? $raw['slugs'] : array();
		$slugs = array_values(
			array_filter(
				array_map( 'strval', $slugs ),
				static function ( $s ) {
					return '' !== $s;
				}
			)
		);

		$media       = self::media_from_legacy( $raw['imgs'] ?? array(), $raw['video'] ?? array() );
		$loop_hidden = $raw['loop_hidden'] ?? false;

		if ( empty( $slugs ) ) {
			if ( empty( $media ) ) {
				return null;
			}
			return array(
				'type'     => 'product',
				'media'    => $media,
				'settings' => array( 'loop_hidden' => $loop_hidden ),
			);
		}

		$first = $slugs[0];
		if ( 'sviproglobal' === $first ) {
			return array( 'type' => 'global', 'media' => $media, 'settings' => array( 'loop_hidden' => $loop_hidden ) );
		}
		if ( 'svidefault' === $first ) {
			return array( 'type' => 'default', 'media' => $media, 'settings' => array( 'loop_hidden' => $loop_hidden ) );
		}
		if ( in_array( 'unsigned_svi', $slugs, true ) ) {
			return null; // derived at read time; nothing to migrate.
		}

		$variation_id = self::match_variation( $slugs, $context['variation_map'] ?? array() );
		if ( null !== $variation_id ) {
			$conditions = array( 'variation_id' => $variation_id );
			if ( isset( $context['trid_map'][ $variation_id ] ) ) {
				$conditions['trid'] = (int) $context['trid_map'][ $variation_id ];
			}
			return array(
				'type'       => 'variation',
				'conditions' => $conditions,
				'media'      => $media,
				'settings'   => array( 'loop_hidden' => $loop_hidden ),
			);
		}

		$resolved = self::resolve_slugs( $slugs, $context );
		if ( $resolved['resolved'] && ( ! empty( $resolved['terms'] ) || ! empty( $resolved['custom'] ) ) ) {
			$conditions = array();
			if ( ! empty( $resolved['terms'] ) ) {
				$conditions['terms'] = $resolved['terms'];
			}
			if ( ! empty( $resolved['custom'] ) ) {
				$conditions['custom'] = $resolved['custom'];
			}
			return array(
				'type'       => 'attribute_term',
				'conditions' => $conditions,
				'media'      => $media,
				'settings'   => array( 'loop_hidden' => $loop_hidden ),
			);
		}

		// Unresolved: preserve, never drop, never null.
		return array(
			'type'         => 'attribute_term',
			'unresolved'   => true,
			'legacy_slugs' => $slugs,
			'media'        => $media,
			'settings'     => array( 'loop_hidden' => $loop_hidden ),
		);
	}

	/**
	 * Build schema-3 media from a legacy `imgs` list + `video` map.
	 *
	 * @param mixed $imgs
	 * @param mixed $video
	 * @return array
	 */
	private static function media_from_legacy( $imgs, $video ) {
		$media = array();

		if ( is_array( $imgs ) ) {
			foreach ( $imgs as $img ) {
				$id = (int) $img;
				if ( $id > 0 ) {
					$media[] = array( 'kind' => 'image', 'id' => $id );
				}
			}
		}

		if ( is_array( $video ) ) {
			foreach ( $video as $key => $value ) {
				if ( is_array( $value ) ) {
					// [slug|wc_svimainvideo][id] => url.
					foreach ( $value as $poster => $url ) {
						if ( is_scalar( $url ) && ( is_int( $poster ) || ctype_digit( (string) $poster ) ) ) {
							$media[] = array( 'kind' => 'video', 'source' => (string) $url, 'poster_id' => (int) $poster );
						}
					}
				} elseif ( is_scalar( $value ) && '' !== (string) $value && ( is_int( $key ) || ctype_digit( (string) $key ) ) ) {
					// [id] => url.
					$media[] = array( 'kind' => 'video', 'source' => (string) $value, 'poster_id' => (int) $key );
				}
			}
		}

		return $media;
	}

	/**
	 * Exact variation match: all slugs match exactly one variation's attribute
	 * set (values normalised to slugs, order-insensitive).
	 *
	 * @param array $slugs
	 * @param mixed $variations
	 * @return int|null
	 */
	private static function match_variation( array $slugs, $variations ) {
		if ( ! is_array( $variations ) ) {
			return null;
		}

		$target = array();
		foreach ( $slugs as $slug ) {
			$target[] = SVI_Gallery_Schema::normalise_slug( $slug );
		}
		sort( $target, SORT_STRING );

		$matches = array();
		foreach ( $variations as $v ) {
			if ( ! is_array( $v ) || ! isset( $v['id'] ) ) {
				continue;
			}
			$signature = array();
			foreach ( (array) ( $v['attributes'] ?? array() ) as $value ) {
				$normalised = SVI_Gallery_Schema::normalise_slug( (string) $value );
				if ( '' !== $normalised ) {
					$signature[] = $normalised;
				}
			}
			sort( $signature, SORT_STRING );

			if ( $signature === $target ) {
				$matches[] = (int) $v['id'];
			}
		}

		return 1 === count( $matches ) ? $matches[0] : null;
	}

	/**
	 * Resolve slugs to terms/custom conditions, exact-first then normalised.
	 *
	 * @param array $slugs
	 * @param array $context
	 * @return array{resolved:bool, terms:array, custom:array}
	 */
	private static function resolve_slugs( array $slugs, array $context ) {
		$term_map   = isset( $context['term_map'] ) && is_array( $context['term_map'] ) ? $context['term_map'] : array();
		$custom_map = isset( $context['custom_option_map'] ) && is_array( $context['custom_option_map'] ) ? $context['custom_option_map'] : array();

		$terms    = array();
		$custom   = array();
		$resolved = true;

		foreach ( $slugs as $slug ) {
			$slug = (string) $slug;

			$term_match = self::match_term( $term_map, $slug );
			if ( null === $term_match ) {
				$term_match = self::match_term( $term_map, SVI_Gallery_Schema::normalise_slug( $slug ) );
			}
			if ( null !== $term_match ) {
				$terms[ $term_match[0] ][] = $term_match[1];
				continue;
			}

			$custom_match = self::match_custom( $custom_map, $slug );
			if ( null === $custom_match ) {
				$custom_match = self::match_custom( $custom_map, SVI_Gallery_Schema::normalise_slug( $slug ) );
			}
			if ( null !== $custom_match ) {
				$custom[] = $custom_match;
				continue;
			}

			$resolved = false;
		}

		return array(
			'resolved' => $resolved,
			'terms'    => SVI_Gallery_Schema::normalise_terms( $terms ),
			'custom'   => SVI_Gallery_Schema::normalise_custom( $custom ),
		);
	}

	/**
	 * Find a unique taxonomy/term match for a candidate slug.
	 *
	 * @param array  $term_map
	 * @param string $candidate
	 * @return array{0:string,1:int}|null
	 */
	private static function match_term( array $term_map, $candidate ) {
		$found = array();
		foreach ( $term_map as $tax => $slug_to_id ) {
			if ( is_array( $slug_to_id ) && array_key_exists( $candidate, $slug_to_id ) ) {
				$found[] = array( (string) $tax, (int) $slug_to_id[ $candidate ] );
			}
		}
		return 1 === count( $found ) ? $found[0] : null;
	}

	/**
	 * Find a unique custom-attribute match for a candidate slug.
	 *
	 * @param array  $custom_map
	 * @param string $candidate
	 * @return array|null
	 */
	private static function match_custom( array $custom_map, $candidate ) {
		$found = array();
		foreach ( $custom_map as $key => $slug_to_raw ) {
			if ( is_array( $slug_to_raw ) && array_key_exists( $candidate, $slug_to_raw ) ) {
				$found[] = array(
					'attribute_key' => (string) $key,
					'value'         => (string) $slug_to_raw[ $candidate ],
					'value_slug'    => SVI_Gallery_Schema::normalise_slug( $candidate ),
				);
			}
		}
		return 1 === count( $found ) ? $found[0] : null;
	}

	/**
	 * Additive schema 2 → 3 transform (§6.4). Accepts `version: 2` as an alias
	 * of `schema: 2`.
	 *
	 * @param array $payload
	 * @return array
	 */
	public static function schema_2_to_3( array $payload ) {
		$raw     = isset( $payload['entries'] ) && is_array( $payload['entries'] ) ? $payload['entries'] : array();
		$entries = array();

		foreach ( array_values( $raw ) as $e ) {
			if ( ! is_array( $e ) ) {
				continue;
			}
			$entry = self::upgrade_schema_2_entry( $e );
			if ( null !== $entry ) {
				$entries[] = $entry;
			}
		}

		return SVI_Gallery_Schema::canonicalise( array( 'entries' => $entries ) );
	}

	/**
	 * Convert a single schema-2 entry (nest conditions, derive pinned, convert
	 * legacy type to unresolved attribute_term).
	 *
	 * @param array $e
	 * @return array|null
	 */
	private static function upgrade_schema_2_entry( array $e ) {
		$type = strtolower( (string) ( $e['type'] ?? '' ) );

		if ( 'legacy' === $type ) {
			$slugs = isset( $e['legacy_slugs'] ) && is_array( $e['legacy_slugs'] ) ? $e['legacy_slugs'] : ( isset( $e['slugs'] ) && is_array( $e['slugs'] ) ? $e['slugs'] : array() );
			return array(
				'type'         => 'attribute_term',
				'unresolved'   => true,
				'legacy_slugs' => array_values( array_map( 'strval', $slugs ) ),
				'media'        => $e['media'] ?? array(),
				'settings'     => $e['settings'] ?? array(),
			);
		}

		if ( ! in_array( $type, SVI_Gallery_Schema::ENTRY_TYPES, true ) ) {
			return null;
		}

		$conditions = array();
		if ( 'variation' === $type ) {
			$vid                       = $e['variation_id'] ?? $e['conditions']['variation_id'] ?? 0;
			$conditions['variation_id'] = (int) $vid;
			$trid                       = $e['trid'] ?? $e['conditions']['trid'] ?? null;
			if ( null !== $trid && '' !== $trid ) {
				$conditions['trid'] = (int) $trid;
			}
		} elseif ( 'attribute_term' === $type ) {
			$terms = $e['taxonomies'] ?? $e['conditions']['terms'] ?? array();
			if ( is_array( $terms ) && ! empty( $terms ) ) {
				$conditions['terms'] = $terms;
			}
			$custom = $e['custom_conditions'] ?? $e['conditions']['custom'] ?? array();
			if ( is_array( $custom ) && ! empty( $custom ) ) {
				$conditions['custom'] = $custom;
			}
		}

		return array(
			'type'       => $type,
			'conditions' => $conditions,
			'media'      => $e['media'] ?? array(),
			'settings'   => $e['settings'] ?? array(),
		);
	}
}
}
