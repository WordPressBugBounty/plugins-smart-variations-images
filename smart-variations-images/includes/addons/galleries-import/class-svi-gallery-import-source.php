<?php
/**
 * Base class for a "galleries from another plugin" import source.
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Gallery_Import_Source')) {
    /**
     * Detection, counting and batching live here; an adapter only describes the
     * meta key another plugin used and how its payload is shaped.
     */
    abstract class SVI_Gallery_Import_Source
    {
        /** Plugin folder is present and active. */
        const STATUS_ACTIVE = 'active';

        /** Plugin folder is present but the plugin is not active. */
        const STATUS_INACTIVE = 'inactive';

        /** Plugin is gone, but its meta rows are still in the database. */
        const STATUS_DATA = 'data';

        /** Nothing found. */
        const STATUS_NONE = 'none';

        /**
         * Machine name used by the UI and WP-CLI.
         *
         * @return string
         */
        abstract public function get_slug(): string;

        /**
         * Factual plugin name shown in the UI.
         *
         * @return string
         */
        abstract public function get_label(): string;

        /**
         * Plugin files that identify the source (relative to the plugins dir).
         *
         * @return string[]
         */
        abstract protected function get_plugin_files(): array;

        /**
         * Meta key the source stored variation images under.
         *
         * @return string
         */
        abstract public function get_meta_key(): string;

        /**
         * Turn a stored payload into attachment ids.
         *
         * @param mixed $raw Stored meta value.
         * @return int[]
         */
        public function parse($raw): array
        {
            if (is_string($raw) && '' !== trim($raw)) {
                $maybe = maybe_unserialize($raw);

                if (is_array($maybe)) {
                    $raw = $maybe;
                } else {
                    $raw = explode(',', $raw);
                }
            }

            if (!is_array($raw)) {
                return array();
            }

            $ids = array();

            foreach ($raw as $value) {
                $id = absint($value);

                if ($id > 0) {
                    $ids[] = $id;
                }
            }

            return array_values(array_unique($ids));
        }

        /**
         * Detection status.
         *
         * @return string
         */
        public function status(): string
        {
            $installed = false;

            foreach ($this->get_plugin_files() as $file) {
                if (file_exists(WP_PLUGIN_DIR . '/' . $file)) {
                    $installed = true;
                    break;
                }
            }

            $total = $this->total();

            if ($installed) {
                foreach ($this->get_plugin_files() as $file) {
                    if (is_plugin_active($file)) {
                        return self::STATUS_ACTIVE;
                    }
                }

                return self::STATUS_INACTIVE;
            }

            return $total > 0 ? self::STATUS_DATA : self::STATUS_NONE;
        }

        /**
         * Variations carrying this source's payload.
         *
         * @return int
         */
        public function total(): int
        {
            global $wpdb;

            return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->prepare(
                    "SELECT COUNT(DISTINCT pm.post_id)
                     FROM {$wpdb->postmeta} pm
                     INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                     WHERE pm.meta_key = %s
                       AND pm.meta_value <> ''
                       AND pm.meta_value <> 'a:0:{}'
                       AND p.post_type = 'product_variation'",
                    $this->get_meta_key()
                )
            );
        }

        /**
         * One page of variation ids.
         *
         * @param int $offset Offset.
         * @param int $limit  Page size.
         * @return int[]
         */
        public function variation_ids(int $offset, int $limit): array
        {
            global $wpdb;

            $rows = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->prepare(
                    "SELECT DISTINCT pm.post_id
                     FROM {$wpdb->postmeta} pm
                     INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
                     WHERE pm.meta_key = %s
                       AND pm.meta_value <> ''
                       AND pm.meta_value <> 'a:0:{}'
                       AND p.post_type = 'product_variation'
                     ORDER BY pm.post_id ASC
                     LIMIT %d OFFSET %d",
                    $this->get_meta_key(),
                    max(1, $limit),
                    max(0, $offset)
                )
            );

            return array_map('intval', (array) $rows);
        }

        /**
         * Extra explanation shown next to the status, when there is one.
         *
         * @return string
         */
        public function get_status_reason(): string
        {
            return '';
        }

        /**
         * Write one variation through the shared writer.
         *
         * Sources that can group several variations into one gallery override
         * this to work per product instead of per variation.
         *
         * @param SVI_Gallery_Import_Writer $writer       Writer.
         * @param int                       $variation_id Variation id.
         * @return array
         */
        public function write(SVI_Gallery_Import_Writer $writer, int $variation_id): array
        {
            $images = $this->parse(get_post_meta($variation_id, $this->get_meta_key(), true));

            return $writer->write($images, $variation_id, $this->get_slug());
        }

        /**
         * Counters the run can report, e.g. variations read and galleries made.
         *
         * @return array<string,int>
         */
        public function get_summary(): array
        {
            return array();
        }

        /**
         * Whether anything can be imported.
         *
         * @return bool
         */
        public function has_candidates(): bool
        {
            return $this->total() > 0;
        }
    }
}
