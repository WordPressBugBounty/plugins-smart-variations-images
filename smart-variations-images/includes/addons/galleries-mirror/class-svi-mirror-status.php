<?php
/**
 * Read-only counters for the mirror.
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Mirror_Status')) {
    /**
     * Cheap aggregate queries, used by WP-CLI and the hidden QA panel.
     */
    final class SVI_Mirror_Status
    {
        /**
         * Snapshot of the mirror state.
         *
         * @return array
         */
        public static function snapshot(): array
        {
            return array(
                'mode'          => SVI_Mirror_Settings::mode(),
                'should_mirror' => SVI_Mirror_Mode::should_mirror(),
                'host_v3'       => SVI_Mirror_Mode::host_is_v3(),
                'legacy'        => self::legacy_products(),
                'mirrored'      => self::mirrored_products(),
                'partial'       => self::marker_count('partial'),
                'error'         => self::marker_count('error'),
                'cleared'       => self::marker_count('cleared'),
                'backfill'      => SVI_Mirror_Backfill::state(),
            );
        }

        /**
         * Products carrying a non-empty legacy payload.
         *
         * @return int
         */
        private static function legacy_products(): int
        {
            global $wpdb;

            return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                "SELECT COUNT(DISTINCT p.ID)
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
                 WHERE p.post_type = 'product'
                   AND pm.meta_key = 'woosvi_slug'
                   AND pm.meta_value <> ''"
            );
        }

        /**
         * Products carrying a schema-3 payload.
         *
         * @return int
         */
        private static function mirrored_products(): int
        {
            global $wpdb;

            return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                "SELECT COUNT(DISTINCT p.ID)
                 FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
                 WHERE p.post_type = 'product'
                   AND pm.meta_key = '_svi_galleries'"
            );
        }

        /**
         * Products whose marker holds a given status.
         *
         * Serialised values only: this is a diagnostic count, not a data source.
         *
         * @param string $status Marker status.
         * @return int
         */
        private static function marker_count(string $status): int
        {
            global $wpdb;

            // Match the status field only. A plain LIKE '%error%' also matched
            // the always-present `"error" => null` key and reported every
            // product as failed.
            $needles = array(
                sprintf('s:6:"status";s:%d:"%s";', strlen($status), $status),
                sprintf('"status":"%s"', $status),
                sprintf('"status": "%s"', $status),
            );

            $clauses = array();
            $params  = array();

            foreach ($needles as $needle) {
                $clauses[] = 'pm.meta_value LIKE %s';
                $params[]  = '%' . $wpdb->esc_like($needle) . '%';
            }

            $sql = "SELECT COUNT(DISTINCT pm.post_id)
                     FROM {$wpdb->postmeta} pm
                     INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                     WHERE pm.meta_key = '_svi_migration'
                       AND (" . implode(' OR ', $clauses) . ")
                       AND p.post_type = 'product'";

            return (int) $wpdb->get_var($wpdb->prepare($sql, $params)); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        }
    }
}
