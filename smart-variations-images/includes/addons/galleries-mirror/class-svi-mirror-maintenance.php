<?php
/**
 * Rollback, purge, drift check and legacy export.
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Mirror_Maintenance')) {
    /**
     * Everything that removes or audits mirror data.
     *
     * `woosvi_slug` is never touched by any method here.
     */
    final class SVI_Mirror_Maintenance
    {
        /**
         * Meta keys owned by the mirror.
         *
         * @return string[]
         */
        public static function keys(): array
        {
            return array('_svi_galleries', '_svi_galleries_backup', '_svi_migration');
        }

        /**
         * Remove mirror data for the given products (or the whole catalogue).
         *
         * @param int[] $ids Product ids. Empty means every product.
         * @return array{removed:int}
         */
        public static function rollback(array $ids = array()): array
        {
            $ids = $ids ? array_map('intval', $ids) : self::product_ids_with('_svi_galleries');

            foreach ($ids as $id) {
                foreach (self::keys() as $key) {
                    delete_post_meta($id, $key);
                }
            }

            return array('removed' => count($ids));
        }

        /**
         * Remove mirror data for every product, including cleared markers.
         *
         * @return array{removed:int}
         */
        public static function purge(): array
        {
            $ids = array_unique(array_merge(
                self::product_ids_with('_svi_galleries'),
                self::product_ids_with('_svi_migration')
            ));

            SVI_Mirror_Backfill::reset();

            return self::rollback($ids);
        }

        /**
         * Compare the stored mirror with a fresh conversion of the legacy data.
         *
         * @param int $limit Products to sample.
         * @return array{checked:int,drift:int,ids:int[]}
         */
        public static function drift_sample(int $limit = 25): array
        {
            $ids    = array_slice(self::product_ids_with('_svi_galleries'), 0, max(1, $limit));
            $drift  = array();

            foreach ($ids as $id) {
                $product = function_exists('wc_get_product') ? wc_get_product($id) : null;

                if (!$product instanceof WC_Product) {
                    continue;
                }

                $repo     = new SVI_Gallery_Repository();
                $expected = SVI_Legacy_Normalizer::normalize_legacy($product);

                if (is_array($expected)) {
                    // Compare against what a write would actually store.
                    $expected = $repo->preview_write($product, $expected);
                }

                $stored = self::fingerprint($repo->get($product));
                $expected = self::fingerprint($expected);

                if ($stored !== $expected) {
                    $drift[] = $id;
                }
            }

            return array(
                'checked' => count($ids),
                'drift'   => count($drift),
                'ids'     => $drift,
            );
        }

        /**
         * Export the legacy payload of every product.
         *
         * @return array<int,mixed>
         */
        public static function export_legacy(): array
        {
            $out = array();

            foreach (self::product_ids_with('woosvi_slug') as $id) {
                $raw = get_post_meta($id, 'woosvi_slug', true);

                if (is_string($raw) && '' !== trim($raw)) {
                    $decoded = json_decode($raw, true);
                    $raw     = is_array($decoded) ? $decoded : $raw;
                }

                $out[$id] = $raw;
            }

            return $out;
        }

        /**
         * Stable fingerprint of the entries, ignoring revision and writer.
         *
         * @param array|null $payload Schema-3 payload.
         * @return string
         */
        private static function fingerprint($payload): string
        {
            $entries = (is_array($payload) && isset($payload['entries']) && is_array($payload['entries']))
                ? array_values($payload['entries'])
                : array();

            usort(
                $entries,
                static function ($a, $b) {
                    return strcmp((string) ($a['id'] ?? ''), (string) ($b['id'] ?? ''));
                }
            );

            return md5((string) wp_json_encode($entries));
        }

        /**
         * Product ids carrying a meta key.
         *
         * @param string $meta_key Meta key.
         * @return int[]
         */
        private static function product_ids_with(string $meta_key): array
        {
            global $wpdb;

            $rows = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->prepare(
                    "SELECT DISTINCT p.ID
                     FROM {$wpdb->posts} p
                     INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
                     WHERE p.post_type = 'product'
                       AND pm.meta_key = %s
                     ORDER BY p.ID ASC",
                    $meta_key
                )
            );

            return array_map('intval', (array) $rows);
        }
    }
}
