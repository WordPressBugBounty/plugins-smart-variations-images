<?php
/**
 * SVI Galleries v3 — L2 gallery migrator (WooCommerce-coupled).
 *
 * @package Smart_Variations_Images
 * @since   5.4.0
 */

/**
 * Converts a single product's legacy gallery data to schema 3 and persists it.
 *
 * Responsibilities (single-product, idempotent):
 *  - skip when schema >= 3 and entries are non-empty (unless forced);
 *  - convert via SVI_Legacy_Normalizer;
 *  - store the legacy backup exactly once;
 *  - save the schema-3 payload, write the `_svi_migration` marker, fire
 *    `svi_product_gallery_migrated`, then persist the meta once.
 *
 * Invariants:
 *  - woosvi_slug is NEVER written or deleted;
 *  - unresolved entries are preserved (never dropped, never null);
 *  - the featured image is NOT prepended into a stored default entry (that is a
 *    read-time concern owned by SVI_Gallery_Resolver).
 */
class SVI_Gallery_Migrator {

	/**
	 * @var SVI_Gallery_Repository
	 */
	private $repo;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repo = new SVI_Gallery_Repository();
	}

	/**
	 * Register the admin write-path hook.
	 *
	 * Priority 20 so it runs after SVI_Product_Data_Tab::save_product_data()
	 * (priority 10): when the builder submitted a payload this callback is a
	 * no-op, so a single save never writes the payload twice.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'stage_on_admin_save' ), 20, 1 );
	}

	/**
	 * Admin save callback: stage a legacy -> schema 3 conversion for products
	 * that have not been migrated yet.
	 *
	 * Never calls $product->save() - WooCommerce is already saving this object,
	 * so update_meta_data() alone is the correct (and only) way to persist.
	 * Calling save() from inside woocommerce_admin_process_product_object is
	 * unsupported and was the cause of the reference implementation's
	 * double-save defect.
	 *
	 * @param WC_Product $product Product being saved.
	 * @return void
	 */
	public static function stage_on_admin_save( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		if ( ! SVI_Mirror_Mode::should_mirror() ) {
			return; // Legacy-only install: no mirror to maintain.
		}

		if ( ! $product->is_type( array( 'variable', 'simple' ) ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_products' ) ) {
			return;
		}

		// The Vue builder already staged the canonical payload for this save.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by SVI_Product_Data_Tab::save_product_data().
		if ( isset( $_POST['svi_galleries_payload'] ) ) {
			return;
		}

		( new self() )->migrate_on_write( $product );
	}

	/**
	 * Materialise schema 3 after a legacy write made outside this plugin.
	 *
	 * The legacy integration contracts (the `wc/svi/{id}` REST import and the
	 * `woocommerce_product_import_inserted_product_object` CSV/API import) keep
	 * writing `woosvi_slug` for backward compatibility. On an install that reads
	 * schema 3 that write would otherwise leave `_svi_galleries` stale: the
	 * resolver would keep serving the previous gallery and the import would look
	 * like a silent no-op. Those handlers call this right after they write the
	 * legacy meta.
	 *
	 * No-op - and therefore byte-identical to the pre-5.4.0 behaviour - while the
	 * legacy read path is active. Runs in an import/CLI context where no
	 * WooCommerce save is in flight (WooCommerce's importer fires its
	 * `..._inserted_product_object` action *after* its own `$product->save()`), so
	 * exactly one save is performed here.
	 *
	 * @param int|WC_Product $product_or_id Product or product id.
	 * @return array|null The persisted schema-3 payload, or null when nothing was done.
	 */
	public static function sync_from_legacy_write( $product_or_id ) {
		if ( ! SVI_Mirror_Mode::should_mirror() ) {
			return null; // Legacy-only install: no mirror to maintain.
		}

		if ( $product_or_id instanceof WC_Product ) {
			$product = $product_or_id;
		} else {
			$product = wc_get_product( (int) $product_or_id );
		}

		if ( ! $product instanceof WC_Product ) {
			return null;
		}

		$migrator = new self();

		// Mirrors the admin write path: only the product types the builder owns.
		if ( ! $product->is_type( array( 'variable', 'simple' ) ) ) {
			return null;
		}

		$payload = $migrator->stage( $product, true );
		if ( null === $payload ) {
			return null;
		}

		// Meta-only persist: never re-enter the WooCommerce product save.
		$product->save_meta_data();

		return $payload;
	}

	/**
	 * Whether a product still needs a schema 1/2 -> 3 conversion.
	 *
	 * An absent `_svi_galleries` is pending when any legacy source exists; a
	 * present schema-3 payload - including an explicitly empty one - is
	 * authoritative and is never re-populated from woosvi_slug (§7.5).
	 *
	 * @param WC_Product $product Product to inspect.
	 * @return bool
	 */
	public function needs_conversion( WC_Product $product ) {
		$current = $product->get_meta( SVI_Gallery_Repository::META_GALLERIES );

		if ( is_string( $current ) && '' !== trim( $current ) ) {
			$decoded = json_decode( $current, true );
			if ( is_array( $decoded ) ) {
				$current = $decoded;
			}
		}

		if ( is_array( $current ) ) {
			return false !== SVI_Gallery_Schema::needs_upgrade( $current );
		}

		return null !== $this->repo->get_legacy_payload( $product );
	}

	/**
	 * Convert a product's legacy galleries to schema 3 and stage the meta on the
	 * WC_Product object WITHOUT persisting it.
	 *
	 * This is the seam the admin save path uses: it never calls
	 * $product->save(), because WooCommerce persists the object immediately after
	 * `woocommerce_admin_process_product_object`. Calling save() from inside that
	 * hook is unsupported and caused the reference implementation's double-save
	 * defect. Only update_meta_data() is used, so the product row stays untouched
	 * until the caller (this class, or WooCommerce) saves the object.
	 *
	 * @param WC_Product $product Product to migrate.
	 * @param bool       $force   Force re-conversion even when already migrated.
	 * @return array|null The staged schema-3 payload, or null when nothing was
	 *                    staged.
	 */
	public function stage( WC_Product $product, $force = false ) {
		$current     = $this->repo->get( $product );
		$is_schema_3 = isset( $current['schema'] ) && (int) $current['schema'] >= SVI_Gallery_Schema::SVI_GALLERY_SCHEMA;
		$has_entries = ! empty( $current['entries'] );

		if ( $is_schema_3 && $has_entries && ! $force ) {
			return null; // Already migrated; no-op.
		}

		$source = SVI_Legacy_Normalizer::classify( $product, $force );
		if ( null === $source ) {
			return null; // Nothing to convert.
		}

		$payload = SVI_Legacy_Normalizer::convert( $source['raw'], $source['from'], $product );

		$this->ensure_backup( $product, $source['from'], $source['raw'] );

		$this->repo->save( $product, $payload );

		$this->repo->set_migration_marker(
			$product,
			array(
				'from'   => $source['from'],
				'to'     => SVI_Gallery_Schema::SVI_GALLERY_SCHEMA,
				'at'     => time(),
				'stats'  => self::compute_stats( $payload ),
				'status' => 'done',
				'error'  => null,
			)
		);

		/**
		 * Fires after a product's galleries are migrated to schema 3.
		 *
		 * @param int   $product_id Product id.
		 * @param array $payload    Schema-3 payload.
		 * @param mixed $legacy     Raw legacy source payload.
		 */
		do_action( 'svi_product_gallery_migrated', $product->get_id(), $payload, $source['raw'] );

		return $payload;
	}

	/**
	 * Migrate a product if needed (stage + persist with a single save).
	 *
	 * Used by the bulk runner, the CLI and the dry-run tooling, where no outer
	 * WooCommerce save is in flight.
	 *
	 * @param WC_Product $product Product to migrate.
	 * @param bool       $force   Force re-conversion even when already migrated.
	 * @return array|null The schema-3 payload, or null when already migrated /
	 *                    nothing to do.
	 */
	public function migrate_if_needed( WC_Product $product, $force = false ) {
		$payload = $this->stage( $product, $force );
		if ( null === $payload ) {
			return null;
		}

		// Meta-only persist: backup + payload + marker, no product-save hooks.
		$product->save_meta_data();

		return $payload;
	}

	/**
	 * Migrate on the product-save path (stage only - WooCommerce performs the save).
	 *
	 * Only products whose `_svi_galleries` is absent (with a legacy source) or
	 * schema 1/2 are staged. An explicitly empty schema-3 payload is a "clear"
	 * and is never re-populated from woosvi_slug (§7.5); a save that carried a
	 * builder payload is skipped by the caller (stage_on_admin_save()).
	 *
	 * @param WC_Product $product Product to migrate.
	 * @return array|null The staged payload, or null when already migrated /
	 *                    nothing to do.
	 */
	public function migrate_on_write( WC_Product $product ) {
		if ( ! $this->needs_conversion( $product ) ) {
			return null;
		}

		return $this->stage( $product );
	}

	/**
	 * Store the legacy backup exactly once (single-slot safety net).
	 *
	 * @param WC_Product $product Product.
	 * @param int        $from    Source schema (1 or 2).
	 * @param mixed      $raw     Raw source payload.
	 * @return void
	 */
	private function ensure_backup( WC_Product $product, $from, $raw ) {
		$existing = $product->get_meta( SVI_Gallery_Repository::META_BACKUP );
		if ( ! empty( $existing ) ) {
			return; // Already backed up once; never overwrite.
		}

		if ( 2 === (int) $from ) {
			$product->update_meta_data(
				SVI_Gallery_Repository::META_BACKUP,
				array(
					'at'      => time(),
					'source'  => 'schema-2',
					'payload' => $raw,
					'writer'  => SVI_Gallery_Schema::default_writer(),
				)
			);
			return;
		}

		$this->repo->store_legacy_backup( $product, $raw );
	}

	/**
	 * Compute per-migration statistics.
	 *
	 * @param array $payload Schema-3 payload.
	 * @return array{entries:int, resolved:int, unresolved:int}
	 */
	private static function compute_stats( array $payload ) {
		$entries    = isset( $payload['entries'] ) && is_array( $payload['entries'] ) ? $payload['entries'] : array();
		$unresolved = 0;
		foreach ( $entries as $entry ) {
			if ( ! empty( $entry['unresolved'] ) ) {
				++$unresolved;
			}
		}

		return array(
			'entries'    => count( $entries ),
			'resolved'   => count( $entries ) - $unresolved,
			'unresolved' => $unresolved,
		);
	}
}
