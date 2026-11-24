<?php
/**
 * SVI Galleries v3 — L1 legacy normalizer (read-time adapter, READ-ONLY).
 *
 * @package Smart_Variations_Images
 * @since   5.4.0
 */

/**
 * Reads legacy gallery data (woosvi_slug or a schema-2 `_svi_galleries`
 * payload) and returns the schema-3 payload IN MEMORY, without writing
 * anything to the database.
 *
 * This is the read-time adapter: it never calls update_post_meta, never calls
 * $product->save(), and never mutates woosvi_slug. The conversion core lives in
 * SVI_Gallery_Schema (Phase 1); this class only supplies the WP-coupled context
 * and chooses the correct upgrade path (schema 1→3 or 2→3).
 */
class SVI_Legacy_Normalizer {

	/**
	 * Classify the legacy source for a product.
	 *
	 * Precedence: a schema-2 (or schema-1) payload already stored in
	 * `_svi_galleries` wins over woosvi_slug (it is the newer lineage). When
	 * `_svi_galleries` already holds schema 3 the product is considered already
	 * migrated and this returns null unless `$force` is true, in which case the
	 * conversion falls back to the legacy woosvi_slug source.
	 *
	 * @param WC_Product $product Product to inspect.
	 * @param bool       $force   Re-convert from legacy even when already migrated.
	 * @return array{from:int, raw:mixed}|null Null when there is nothing to convert.
	 */
	public static function classify( WC_Product $product, $force = false ) {
		$raw_galleries = self::read_galleries_meta( $product );
		if ( null !== $raw_galleries ) {
			$needs = SVI_Gallery_Schema::needs_upgrade( $raw_galleries );
			if ( 2 === $needs ) {
				return array( 'from' => 2, 'raw' => $raw_galleries );
			}
			if ( 1 === $needs ) {
				return array( 'from' => 1, 'raw' => $raw_galleries );
			}
			// Already schema 3: nothing to normalise (unless forced).
			if ( ! $force ) {
				return null;
			}
		}

		$legacy = self::read_legacy_meta( $product );
		if ( is_array( $legacy ) ) {
			$needs = SVI_Gallery_Schema::needs_upgrade( $legacy );
			if ( 2 === $needs ) {
				return array( 'from' => 2, 'raw' => $legacy );
			}
			return array( 'from' => 1, 'raw' => $legacy );
		}

		return null;
	}

	/**
	 * Read the legacy woosvi_slug payload, decoding single- and double-encoded
	 * JSON strings in memory (never written back).
	 *
	 * F10 stores woosvi_slug as the 4-byte string `"[]"`: a JSON-encoded empty
	 * array whose first decode yields the string `[]`, requiring a second decode.
	 *
	 * @param WC_Product $product Product to read.
	 * @return array|null
	 */
	private static function read_legacy_meta( WC_Product $product ) {
		$raw = $product->get_meta( SVI_Gallery_Repository::META_LEGACY );

		if ( is_array( $raw ) ) {
			return $raw;
		}

		if ( is_string( $raw ) && '' !== trim( $raw ) ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
			// Double-encoded JSON string (e.g. `"[]"`).
			if ( is_string( $decoded ) && '' !== trim( $decoded ) ) {
				$decoded_twice = json_decode( $decoded, true );
				if ( is_array( $decoded_twice ) ) {
					return $decoded_twice;
				}
			}
		}

		return null;
	}

	/**
	 * Normalize a product's legacy galleries to schema 3 in memory.
	 *
	 * @param WC_Product $product Product to read.
	 * @return array|null Schema-3 payload, or null when there is nothing to convert.
	 */
	public static function normalize( WC_Product $product ) {
		$source = self::classify( $product );
		if ( null === $source ) {
			return null;
		}

		return self::convert( $source['raw'], $source['from'], $product );
	}

	/**
	 * Normalize a product's legacy woosvi_slug to schema 3 in memory, ignoring
	 * any stored `_svi_galleries` (schema 1/2/3). This is the deterministic
	 * "pre-migration" read path used by the Phase 3 parity gate to prove the
	 * resolver reproduces 5.3.0 from the same source the baselines captured.
	 *
	 * @param WC_Product $product Product to read.
	 * @return array|null Schema-3 payload, or null when there is nothing to convert.
	 */
	public static function normalize_legacy( WC_Product $product ) {
		$legacy = self::read_legacy_meta( $product );
		if ( ! is_array( $legacy ) ) {
			return null;
		}

		$needs = SVI_Gallery_Schema::needs_upgrade( $legacy );
		$from  = ( 2 === $needs ) ? 2 : 1;

		return self::convert( $legacy, $from, $product );
	}

	/**
	 * Convert a classified raw legacy payload to schema 3.
	 *
	 * @param mixed      $raw     Raw legacy payload (schema 1 or 2).
	 * @param int        $from    Source schema (1 or 2).
	 * @param WC_Product $product Product (context source for schema-1 conversion).
	 * @return array Schema-3 payload.
	 */
	public static function convert( $raw, $from, WC_Product $product ) {
		if ( 2 === (int) $from ) {
			return SVI_Gallery_Schema::upgrade_schema_2_to_3( (array) $raw );
		}

		$context = SVI_Migration_Context::build( $product );
		return SVI_Gallery_Schema::upgrade_schema_1_to_3( $raw, $context );
	}

	/**
	 * Read the raw `_svi_galleries` meta, decoding a JSON string when present.
	 *
	 * @param WC_Product $product Product to read.
	 * @return array|null Null when the key is absent or not an array.
	 */
	private static function read_galleries_meta( WC_Product $product ) {
		$raw = $product->get_meta( SVI_Gallery_Repository::META_GALLERIES );

		if ( is_string( $raw ) && '' !== trim( $raw ) ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		return is_array( $raw ) ? $raw : null;
	}
}
