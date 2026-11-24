<?php
/**
 * SVI Galleries v3 — gallery schema (pure PHP, no WordPress/WooCommerce).
 *
 * @package Smart_Variations_Images
 * @since   5.4.0
 */

/**
 * Canonical data-model definition for SVI galleries (schema 3).
 *
 * This class is intentionally free of any WordPress or WooCommerce calls so it
 * can be exercised by the pure PHPUnit layer (tests/unit/schema). It owns the
 * single schema constant, server-derived id builders, canonicalisation and
 * per-type sanitisation, and the pure schema 1→3 and 2→3 conversion cores.
 */
class SVI_Gallery_Schema {

	/**
	 * The only schema constant in the codebase.
	 */
	const SVI_GALLERY_SCHEMA = 3;

	/**
	 * Allowed entry types.
	 *
	 * @var string[]
	 */
	const ENTRY_TYPES = array( 'variation', 'attribute_term', 'global', 'default', 'product' );

	/**
	 * Derive a server-authoritative entry id from type + conditions.
	 *
	 * The id is a pure function of the entry's type and conditions; any client
	 * supplied id is ignored by the caller (canonicalise()).
	 *
	 * @param string $type       Entry type.
	 * @param array  $conditions Conditions (see §6.1).
	 * @return string
	 */
	public static function build_entry_id( $type, array $conditions = array() ) {
		$type = strtolower( (string) $type );

		switch ( $type ) {
			case 'variation':
				return 'variation:' . (int) ( $conditions['variation_id'] ?? 0 );

			case 'attribute_term':
				$terms  = self::normalise_terms( $conditions['terms'] ?? array() );
				$custom = self::normalise_custom( $conditions['custom'] ?? array() );

				if ( ! empty( $terms ) && ! empty( $custom ) ) {
					return 'taxonomy:' . md5( self::build_taxonomy_id_input( $terms ) . '|' . self::build_custom_id_input( $custom ) );
				}
				if ( ! empty( $terms ) ) {
					return 'taxonomy:' . md5( self::build_taxonomy_id_input( $terms ) );
				}
				if ( ! empty( $custom ) ) {
					return 'custom:' . md5( self::build_custom_id_input( $custom ) );
				}
				return 'attribute_term:' . md5( '' );

			case 'global':
				return 'global';

			case 'default':
				return 'default';

			case 'product':
				return 'product';
		}

		return 'attribute_term:' . md5( '' );
	}

	/**
	 * Derive a media uid.
	 *
	 * @param string $kind      image|video.
	 * @param int    $id        Image id (image kind) or poster fallback.
	 * @param string $source    Video source URL.
	 * @param int    $poster_id Video poster image id.
	 * @return string
	 */
	public static function build_media_uid( $kind, $id = 0, $source = '', $poster_id = 0 ) {
		$kind = strtolower( (string) $kind );

		if ( 'video' === $kind ) {
			$poster = (int) ( $poster_id ? $poster_id : $id );
			return 'vid:' . md5( (string) $source . '|' . $poster );
		}

		return 'img:' . (int) $id;
	}

	/**
	 * Bounded slug normalisation ladder (§7.3.2): %20↔space, lowercase,
	 * `-`↔`_`, sanitize_title-style collapsing, optional transliteration.
	 *
	 * Pure PHP (no WP sanitize_title()).
	 *
	 * @param string $slug
	 * @return string
	 */
	public static function normalise_slug( $slug ) {
		$s = (string) $slug;
		$s = rawurldecode( $s );
		$s = strtolower( $s );

		if ( function_exists( 'iconv' ) ) {
			$trans = @iconv( 'UTF-8', 'ASCII//TRANSLIT//IGNORE', $s ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( false !== $trans ) {
				$s = $trans;
			}
		}

		$s = str_replace( '_', '-', $s );
		$s = preg_replace( '/[\s]+/', '-', $s );
		$s = preg_replace( '/[^a-z0-9\-]+/', '-', $s );
		$s = preg_replace( '/-{2,}/', '-', $s );

		return trim( $s, '-' );
	}

	/**
	 * Whether a payload needs upgrading, returning the current schema.
	 *
	 * @param mixed $payload Raw meta value.
	 * @return int|false Current schema, or false when already at target.
	 */
	public static function needs_upgrade( $payload ) {
		if ( ! is_array( $payload ) ) {
			return false;
		}

		if ( array_key_exists( 'schema', $payload ) ) {
			$schema = (int) $payload['schema'];
			return $schema >= self::SVI_GALLERY_SCHEMA ? false : max( $schema, 1 );
		}

		if ( array_key_exists( 'version', $payload ) ) {
			$version = (int) $payload['version'];
			return $version >= self::SVI_GALLERY_SCHEMA ? false : max( $version, 2 );
		}

		// No schema/version key: legacy woosvi_slug (schema 1) array.
		return 1;
	}

	/**
	 * Coerce a legacy loop_hidden value to bool.
	 *
	 * @param mixed $value
	 * @return bool
	 */
	public static function to_bool( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}
		if ( null === $value ) {
			return false;
		}
		if ( is_int( $value ) ) {
			return 0 !== $value;
		}
		if ( is_string( $value ) ) {
			$filtered = filter_var( $value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );
			if ( null !== $filtered ) {
				return $filtered;
			}
		}
		return (bool) $value;
	}

	/**
	 * Default writer version string.
	 *
	 * @return string
	 */
	/**
	 * The plugin version that introduced schema 3 (diagnostics fallback only).
	 *
	 * @return string
	 */
	public static function default_writer() {
		return defined( 'SMART_VARIATIONS_IMAGES_VERSION' ) ? SMART_VARIATIONS_IMAGES_VERSION : '5.4.0';
	}

	/**
	 * Normalise a `terms` condition map to sorted, unique, positive term ids.
	 *
	 * @param array $terms
	 * @return array
	 */
	public static function normalise_terms( $terms ) {
		$out = array();
		if ( ! is_array( $terms ) ) {
			return $out;
		}
		foreach ( $terms as $tax => $ids ) {
			$ids = array_values( array_unique( array_filter( array_map( 'intval', (array) $ids ), static function ( $id ) {
				return $id > 0;
			} ) ) );
			sort( $ids, SORT_NUMERIC );
			if ( ! empty( $ids ) ) {
				$out[ (string) $tax ] = $ids;
			}
		}
		ksort( $out );
		return $out;
	}

	/**
	 * Normalise a `custom` condition list to a deterministic, deduped order.
	 *
	 * @param array $custom
	 * @return array
	 */
	public static function normalise_custom( $custom ) {
		$out = array();
		if ( ! is_array( $custom ) ) {
			return $out;
		}
		foreach ( $custom as $c ) {
			if ( ! is_array( $c ) ) {
				continue;
			}
			$key = (string) ( $c['attribute_key'] ?? '' );
			if ( '' === $key ) {
				continue;
			}
			$value      = (string) ( $c['value'] ?? '' );
			$value_slug = isset( $c['value_slug'] ) && '' !== (string) $c['value_slug'] ? (string) $c['value_slug'] : self::normalise_slug( $value );
			$out[]      = array(
				'attribute_key' => $key,
				'value'         => $value,
				'value_slug'    => $value_slug,
			);
		}
		usort(
			$out,
			static function ( $a, $b ) {
				return strcmp( $a['attribute_key'] . ':' . $a['value_slug'], $b['attribute_key'] . ':' . $b['value_slug'] );
			}
		);
		return array_values( array_unique( $out, SORT_REGULAR ) );
	}

	/**
	 * Build the canonical md5 input for a `terms` map.
	 *
	 * @param array $terms
	 * @return string
	 */
	private static function build_taxonomy_id_input( array $terms ) {
		$parts = array();
		foreach ( $terms as $tax => $ids ) {
			foreach ( $ids as $id ) {
				$parts[] = 'taxonomy:' . $tax . ':' . (int) $id;
			}
		}
		sort( $parts, SORT_STRING );
		return implode( '|', $parts );
	}

	/**
	 * Build the canonical md5 input for a `custom` list.
	 *
	 * @param array $custom
	 * @return string
	 */
	private static function build_custom_id_input( array $custom ) {
		$parts = array();
		foreach ( $custom as $c ) {
			$parts[] = (string) ( $c['attribute_key'] ?? '' ) . ':' . (string) ( $c['value_slug'] ?? '' );
		}
		sort( $parts, SORT_STRING );
		return implode( '|', $parts );
	}

	/**
	 * Sanitise a media list, deriving uid/kind and dense positions.
	 *
	 * @param array $media
	 * @return array
	 */
	private static function sanitise_media( $media ) {
		if ( ! is_array( $media ) ) {
			return array();
		}

		$out = array();
		foreach ( array_values( $media ) as $m ) {
			if ( ! is_array( $m ) ) {
				continue;
			}
			$kind = strtolower( (string) ( $m['kind'] ?? '' ) );

			if ( 'image' === $kind ) {
				$id = (int) ( $m['id'] ?? 0 );
				if ( $id <= 0 ) {
					continue;
				}
				$out[] = array(
					'uid'  => self::build_media_uid( 'image', $id ),
					'kind' => 'image',
					'id'   => $id,
				);
			} elseif ( 'video' === $kind ) {
				$source = (string) ( $m['source'] ?? '' );
				if ( '' === $source ) {
					continue;
				}
				// A video always plays on an image: without a poster there is
				// nothing to bind it to, so it is not a valid media item.
				$poster_id = (int) ( $m['poster_id'] ?? $m['image_id'] ?? 0 );
				if ( $poster_id <= 0 ) {
					continue;
				}
				$out[] = array(
					'uid'       => self::build_media_uid( 'video', 0, $source, $poster_id ),
					'kind'      => 'video',
					'source'    => $source,
					'poster_id' => $poster_id,
				);
			}
		}

		$position = 0;
		foreach ( $out as &$item ) {
			$item['position'] = $position;
			++$position;
		}
		unset( $item );

		return $out;
	}

	/**
	 * Sanitise an entry's settings bag, preserving unknown keys verbatim.
	 *
	 * @param array $settings
	 * @return array
	 */
	private static function sanitise_settings( $settings ) {
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		$out = array();
		foreach ( $settings as $key => $value ) {
			if ( 'loop_hidden' === $key ) {
				$out['loop_hidden'] = self::to_bool( $value );
			} else {
				$out[ $key ] = $value; // preserve unknown keys verbatim.
			}
		}

		if ( ! array_key_exists( 'loop_hidden', $out ) ) {
			$out['loop_hidden'] = false;
		}

		return $out;
	}

	/**
	 * The pinned value forced by an entry's type.
	 *
	 * @param string $type
	 * @return string
	 */
	private static function pinned_for_type( $type ) {
		if ( 'global' === $type ) {
			return 'global';
		}
		if ( 'default' === $type ) {
			return 'default';
		}
		return 'none';
	}

	/**
	 * Sanitise a single entry per type, preserving unresolved outcomes.
	 *
	 * @param mixed $entry
	 * @return array|null
	 */
	public static function sanitise_entry( $entry ) {
		if ( ! is_array( $entry ) ) {
			return null;
		}

		$type = strtolower( (string) ( $entry['type'] ?? '' ) );
		if ( ! in_array( $type, self::ENTRY_TYPES, true ) ) {
			return null;
		}

		$conditions = array();
		if ( 'variation' === $type ) {
			$conditions['variation_id'] = (int) ( $entry['conditions']['variation_id'] ?? 0 );
			$trid                       = $entry['conditions']['trid'] ?? null;
			if ( null !== $trid && '' !== $trid ) {
				$conditions['trid'] = (int) $trid;
			}
		} elseif ( 'attribute_term' === $type ) {
			$terms = self::normalise_terms( $entry['conditions']['terms'] ?? array() );
			if ( ! empty( $terms ) ) {
				$conditions['terms'] = $terms;
			}
			$custom = self::normalise_custom( $entry['conditions']['custom'] ?? array() );
			if ( ! empty( $custom ) ) {
				$conditions['custom'] = $custom;
			}
		}

		$sanitised = array(
			'id'         => self::build_entry_id( $type, $conditions ),
			'type'       => $type,
			'pinned'     => self::pinned_for_type( $type ),
			'conditions' => $conditions,
		);

		// Unresolved preservation (attribute_term + unresolved + legacy_slugs).
		if ( 'attribute_term' === $type && ! empty( $entry['unresolved'] ) ) {
			$sanitised['unresolved'] = true;
			$slugs                   = isset( $entry['legacy_slugs'] ) && is_array( $entry['legacy_slugs'] ) ? $entry['legacy_slugs'] : array();
			$sanitised['legacy_slugs'] = array_values(
				array_filter(
					array_map( 'strval', $slugs ),
					static function ( $s ) {
						return '' !== $s;
					}
				)
			);
			if ( empty( $sanitised['conditions'] ) ) {
				$sanitised['id'] = 'unresolved:' . md5( implode( '|', $sanitised['legacy_slugs'] ) );
			}
		}

		$sanitised['media']    = self::sanitise_media( $entry['media'] ?? array() );
		$sanitised['settings'] = self::sanitise_settings( $entry['settings'] ?? array() );

		return $sanitised;
	}

	/**
	 * Enforce every §6.2 invariant over a payload and return a canonical
	 * schema-3 payload. Client ids are ignored and recomputed; pinned is forced
	 * for global/default; sort is re-sequenced `(index+1)*10` for non-pinned
	 * entries; media uid/position are dense; unknown settings keys survive.
	 *
	 * @param array $payload
	 * @return array
	 */
	public static function canonicalise( array $payload ) {
		$raw = isset( $payload['entries'] ) && is_array( $payload['entries'] ) ? $payload['entries'] : array();

		$non_pinned = array();
		$defaults   = array();
		$globals    = array();

		foreach ( array_values( $raw ) as $raw_entry ) {
			$entry = self::sanitise_entry( $raw_entry );
			if ( null === $entry ) {
				continue;
			}
			if ( 'default' === $entry['type'] ) {
				$defaults[] = $entry;
			} elseif ( 'global' === $entry['type'] ) {
				$globals[] = $entry;
			} else {
				$non_pinned[] = $entry;
			}
		}

		$entries = array();
		$i       = 0;
		foreach ( $non_pinned as $entry ) {
			$entry['sort'] = ( $i + 1 ) * 10;
			$entries[]     = $entry;
			++$i;
		}
		foreach ( $defaults as $entry ) {
			$entry['sort'] = 0;
			$entries[]     = $entry;
		}
		foreach ( $globals as $entry ) {
			$entry['sort'] = 0;
			$entries[]     = $entry;
		}

		return array(
			'schema'   => self::SVI_GALLERY_SCHEMA,
			'revision' => isset( $payload['revision'] ) ? (int) $payload['revision'] : 0,
			'writer'   => isset( $payload['writer'] ) && '' !== (string) $payload['writer'] ? (string) $payload['writer'] : self::default_writer(),
			'entries'  => $entries,
		);
	}

	/**
	 * Schema 1 (woosvi_slug) -> 3. Delegates to the conversion core.
	 *
	 * @param mixed $legacy  Raw woosvi_slug payload.
	 * @param array $context Resolved resolution context.
	 * @return array
	 */
	public static function upgrade_schema_1_to_3( $legacy, array $context = array() ) {
		return SVI_Gallery_Schema_Upgrade::schema_1_to_3( $legacy, $context );
	}

	/**
	 * Schema 2 -> 3. Delegates to the conversion core.
	 *
	 * @param array $payload Schema-2 payload.
	 * @return array
	 */
	public static function upgrade_schema_2_to_3( array $payload ) {
		return SVI_Gallery_Schema_Upgrade::schema_2_to_3( $payload );
	}
}
