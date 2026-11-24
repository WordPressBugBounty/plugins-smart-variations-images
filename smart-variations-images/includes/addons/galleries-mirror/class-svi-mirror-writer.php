<?php
/**
 * Writes the schema-3 mirror for one product.
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Mirror_Writer')) {
    /**
     * Converts the current `woosvi_slug` payload into `_svi_galleries`.
     *
     * Invariants:
     *  - `woosvi_slug` is read, never written or deleted;
     *  - only meta is persisted (`save_meta_data()`), so the WooCommerce
     *    product save is never re-entered from inside a meta hook;
     *  - a failure leaves the legacy write untouched and records the reason
     *    in `_svi_migration` for `wp svi-mirror status`.
     */
    final class SVI_Mirror_Writer
    {
        /**
         * Re-entrancy guard.
         *
         * @var bool
         */
        private static bool $syncing = false;

        /**
         * Mirror one product.
         *
         * @param int|WC_Product $product_or_id Product or id.
         * @param bool           $cleared       True when woosvi_slug was deleted.
         * @return array|null The persisted payload, or null when nothing was done.
         */
        public static function sync($product_or_id, bool $cleared = false): ?array
        {
            if (self::$syncing || !SVI_Mirror_Mode::should_mirror()) {
                return null;
            }

            $product = $product_or_id instanceof WC_Product
                ? $product_or_id
                : (function_exists('wc_get_product') ? wc_get_product((int) $product_or_id) : null);

            if (!$product instanceof WC_Product) {
                return null;
            }

            // The gallery builder owns these two types only.
            if (!$product->is_type(array('variable', 'simple'))) {
                return null;
            }

            self::$syncing = true;

            try {
                return self::sync_product($product, $cleared);
            } catch (Throwable $error) {
                self::record_error($product, $error);

                return null;
            } finally {
                self::$syncing = false;
            }
        }

        /**
         * Convert and persist.
         *
         * @param WC_Product $product Product.
         * @param bool       $cleared True when the legacy payload was deleted.
         * @return array|null
         */
        private static function sync_product(WC_Product $product, bool $cleared): ?array
        {
            $repo   = new SVI_Gallery_Repository();
            $legacy = $repo->get_legacy_payload($product);
            $hash   = (null === $legacy) ? '' : md5((string) wp_json_encode($legacy));
            $marker = $repo->get_migration_marker($product);
            $stored = $repo->get($product);
            $has_entries = !empty($stored['entries']);

            if ($cleared) {
                if (!$has_entries && !empty($marker['cleared_at'])) {
                    return null;
                }

                $payload = SVI_Gallery_Schema::canonicalise(array('entries' => array()));
                $repo->save($product, $payload);

                $marker['mode']        = 'mirror';
                $marker['status']      = 'cleared';
                $marker['source_hash'] = '';
                $marker['cleared_at']  = time();
                $marker['stats']       = array('entries' => 0, 'resolved' => 0, 'unresolved' => 0);
                $repo->set_migration_marker($product, $marker);
                $product->save_meta_data();

                return $payload;
            }

            if ('' === $hash) {
                return null; // No legacy galleries: nothing to mirror.
            }

            // Already mirrored from this exact payload.
            if (
                'mirror' === ($marker['mode'] ?? '')
                && $hash === ($marker['source_hash'] ?? null)
                && $has_entries
                && 'error' !== ($marker['status'] ?? '')
            ) {
                return null;
            }

            $migrator = new SVI_Gallery_Migrator();
            $payload  = $migrator->stage($product, true);

            if (null === $payload) {
                return null;
            }

            $marker = $repo->get_migration_marker($product);
            $marker['mode']        = 'mirror';
            $marker['source_hash'] = $hash;
            $marker['mirrored_at'] = time();
            $marker['status']      = 'done';

            if (!empty($marker['stats']['unresolved'])) {
                $marker['status'] = 'partial';
            }

            $repo->set_migration_marker($product, $marker);
            $product->save_meta_data();

            return $payload;
        }

        /**
         * Persist the failure so it is visible to CLI and QA, never to merchants.
         *
         * @param WC_Product $product Product.
         * @param Throwable  $error   Caught error.
         */
        private static function record_error(WC_Product $product, Throwable $error): void
        {
            try {
                $repo   = new SVI_Gallery_Repository();
                $marker = $repo->get_migration_marker($product);

                $marker['mode']   = 'mirror';
                $marker['status'] = 'error';
                $marker['error']  = $error->getMessage();
                $marker['at']     = time();

                $repo->set_migration_marker($product, $marker);
                $product->save_meta_data();
            } catch (Throwable $ignored) {
                // Never let mirror bookkeeping break the merchant's save.
            }

            error_log('[SVI Mirror] ' . $error->getMessage()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
        }
    }
}
