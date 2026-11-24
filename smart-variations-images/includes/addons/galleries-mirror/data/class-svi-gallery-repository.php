<?php
/**
 * SVI Galleries v3 — gallery repository (WooCommerce-coupled).
 *
 * @package Smart_Variations_Images
 * @since   5.4.0
 */

/**
 * WC-CRUD access to the SVI gallery meta keys.
 *
 * Meta keys:
 *  - _svi_galleries         (schema 3 payload, written through WC CRUD)
 *  - _svi_galleries_backup  (raw legacy payload backup, single slot)
 *  - _svi_migration         (per-product migration marker)
 *  - woosvi_slug            (legacy v5 meta — READ-ONLY here, never written)
 */
class SVI_Gallery_Repository {

	const META_GALLERIES = '_svi_galleries';
	const META_BACKUP    = '_svi_galleries_backup';
	const META_MIGRATION = '_svi_migration';
	const META_LEGACY    = 'woosvi_slug';

	/**
	 * Read the schema-3 payload, defaulting to an empty gallery when missing.
	 *
	 * @param WC_Product $product
	 * @return array
	 */
	public function get( WC_Product $product ) {
		$payload = $product->get_meta( self::META_GALLERIES );

		if ( is_string( $payload ) && '' !== trim( $payload ) ) {
			$decoded = json_decode( $payload, true );
			if ( is_array( $decoded ) ) {
				$payload = $decoded;
			}
		}

		if ( is_array( $payload ) ) {
			return $payload;
		}

		return array(
			'schema'  => SVI_Gallery_Schema::SVI_GALLERY_SCHEMA,
			'entries' => array(),
		);
	}

	/**
	 * Write the canonical payload via update_meta_data only.
	 *
	 * The caller is responsible for calling $product->save(); this method never
	 * saves the product itself. The revision token is bumped on every write.
	 *
	 * @param WC_Product $product
	 * @param array      $payload
	 * @return void
	 */
	public function save( WC_Product $product, array $payload ) {
		$payload               = $this->prune_unlinked_videos( $product, $payload );
		$canonical             = SVI_Gallery_Schema::canonicalise( $payload );
		$canonical['revision'] = time();
		$canonical['writer']   = SVI_Gallery_Schema::default_writer();

		$product->update_meta_data( self::META_GALLERIES, $canonical );
	}

	/**
	 * Apply the write-time invariants to a payload without persisting it.
	 *
	 * Used by the drift check so an expected payload is compared with the same
	 * pruning a real write applies (unlinked videos are dropped on save).
	 *
	 * @param WC_Product $product Product the payload belongs to.
	 * @param array      $payload Payload to normalise.
	 * @return array
	 */
	public function preview_write( WC_Product $product, array $payload ) {
		return $this->prune_unlinked_videos( $product, $payload );
	}

	/**
	 * Drop videos that are not linked to an image of the product's galleries.
	 *
	 * A video always plays on one gallery image. When its poster image is not
	 * part of the gallery it belongs to - stale attachment ids from CSV/legacy
	 * imports, or an image that was later removed - the storefront can never
	 * render it, so the invariant is enforced on write instead of keeping dead
	 * media around (and instead of explaining it in the UI):
	 *
	 *  - a video with no poster at all            -> removed.
	 *  - a variable product with galleries        -> the poster must be an image
	 *    of the video's own entry (or the featured image: the 'wc_svimainvideo'
	 *    slot) because plain gallery images never receive entry videos.
	 *  - a simple product (or a variable product without galleries) -> the
	 *    poster must be one of the product images the resolver walks, or an
	 *    image of the entry.
	 *
	 * The legacy payload is preserved in the single-slot backup, so nothing is
	 * lost irreversibly.
	 *
	 * @param WC_Product $product Product the payload belongs to.
	 * @param array      $payload Payload about to be persisted.
	 * @return array Payload with unlinked videos removed.
	 */
	private function prune_unlinked_videos( WC_Product $product, array $payload ) {
		if ( empty( $payload['entries'] ) || ! is_array( $payload['entries'] ) ) {
			return $payload;
		}

		$featured    = (int) $product->get_image_id();
		$is_variable = $product->is_type( 'variable' );

		$base_ids = array( $featured );
		foreach ( (array) $product->get_gallery_image_ids() as $gallery_id ) {
			$base_ids[] = (int) $gallery_id;
		}
		if ( $is_variable && class_exists( 'SVI_Gallery_Resolver' ) ) {
			foreach ( SVI_Gallery_Resolver::native_variation_galleries( $product ) as $ids ) {
				foreach ( (array) $ids as $native_id ) {
					$base_ids[] = (int) $native_id;
				}
			}
		}
		$base_ids = array_values( array_unique( array_filter( $base_ids ) ) );

		foreach ( $payload['entries'] as $key => $entry ) {
			if ( ! is_array( $entry ) || empty( $entry['media'] ) || ! is_array( $entry['media'] ) ) {
				continue;
			}

			$image_ids = array();
			foreach ( $entry['media'] as $media ) {
				if ( is_array( $media ) && 'image' === ( $media['kind'] ?? '' ) ) {
					$image_id = (int) ( $media['id'] ?? 0 );
					if ( $image_id > 0 ) {
						$image_ids[] = $image_id;
					}
				}
			}

			$kept = array();
			foreach ( $entry['media'] as $media ) {
				if ( ! is_array( $media ) ) {
					continue;
				}

				if ( 'video' === ( $media['kind'] ?? '' ) && ! $this->video_is_linked( $media, $featured, $image_ids, $base_ids, $is_variable ) ) {
					continue;
				}

				$kept[] = $media;
			}

			$payload['entries'][ $key ]['media'] = $kept;
		}

		return $payload;
	}

	/**
	 * Whether a video media item plays on an image this product can render.
	 *
	 * Mirrors SVI_Gallery_Resolver::build_images(): the featured image carries
	 * the main video for every product, a variable product binds videos to the
	 * images of the entry that owns them, and the other products bind them to
	 * any product image by poster id.
	 *
	 * @param array $media        Video media item.
	 * @param int   $featured     Featured (main) image id.
	 * @param array $entry_images Image ids of the entry owning the video.
	 * @param array $base_ids     Featured + WooCommerce gallery image ids.
	 * @param bool  $is_variable  Whether the product is variable.
	 * @return bool True when the video can be rendered on the storefront.
	 */
	private function video_is_linked( array $media, $featured, array $entry_images, array $base_ids, $is_variable ) {
		$poster = (int) ( $media['poster_id'] ?? $media['image_id'] ?? 0 );

		if ( $poster <= 0 ) {
			return false;
		}

		// Main image video ('wc_svimainvideo'): poster is the featured image.
		if ( $poster === $featured ) {
			return true;
		}

		if ( $is_variable ) {
			return in_array( $poster, $entry_images, true );
		}

		// Simple products (or variable products without galleries) bind videos
		// by poster id over the product images the resolver walks.
		return in_array( $poster, $base_ids, true ) || in_array( $poster, $entry_images, true );
	}

	/**
	 * Store a single-slot backup of the raw legacy payload.
	 *
	 * @param WC_Product $product
	 * @param mixed      $legacy Raw woosvi_slug payload.
	 * @return void
	 */
	public function store_legacy_backup( WC_Product $product, $legacy ) {
		$product->update_meta_data(
			self::META_BACKUP,
			array(
				'at'      => time(),
				'from'    => self::META_LEGACY,
				'payload' => $legacy,
				'writer'  => SVI_Gallery_Schema::default_writer(),
			)
		);
	}

	/**
	 * Read the legacy woosvi_slug payload, READ-ONLY.
	 *
	 * If woosvi_slug holds a JSON string it is decoded in memory and returned;
	 * the normalised array is never written back (v6 defect #5).
	 *
	 * @param WC_Product $product
	 * @return mixed Legacy payload, or null when absent.
	 */
	public function get_legacy_payload( WC_Product $product ) {
		$raw = $product->get_meta( self::META_LEGACY );

		if ( is_string( $raw ) && '' !== trim( $raw ) ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				return $decoded; // In-memory only; never written back.
			}
		}

		if ( is_array( $raw ) ) {
			return $raw;
		}

		return null;
	}

	/**
	 * Delete the legacy backup slot (rollback support). Never touches woosvi_slug.
	 *
	 * @param WC_Product $product
	 * @return void
	 */
	public function delete_legacy_payload( WC_Product $product ) {
		$product->delete_meta_data( self::META_BACKUP );
	}

	/**
	 * Read the per-product migration marker.
	 *
	 * @param WC_Product $product
	 * @return array
	 */
	public function get_migration_marker( WC_Product $product ) {
		$marker = $product->get_meta( self::META_MIGRATION );
		return is_array( $marker ) ? $marker : array();
	}

	/**
	 * Write the per-product migration marker.
	 *
	 * @param WC_Product $product
	 * @param array      $marker
	 * @return void
	 */
	public function set_migration_marker( WC_Product $product, array $marker ) {
		$product->update_meta_data( self::META_MIGRATION, $marker );
	}
}
