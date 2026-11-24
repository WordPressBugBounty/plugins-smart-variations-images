<?php
/**
 * Import source: WooCommerce core variation galleries (WooCommerce 11.1+).
 *
 * WooCommerce 11.1 stores the images of each variation in the variation's own
 * `_thumbnail_id` and `_product_image_gallery`, and the Product Gallery block,
 * REST v3 and the feeds read them from there. This source reads them and turns
 * them into SVI galleries.
 *
 * When every variation that shares one attribute value (all the Red sizes, for
 * example) carries the same image set, one SVI gallery is written for that
 * value. When they differ, one gallery per variation is written instead.
 *
 * @package Smart_Variations_Images
 * @since   5.3.12
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Gallery_Import_Source_WC_Core')) {
    /**
     * Adapter for the per-variation galleries WooCommerce keeps in core.
     */
    class SVI_Gallery_Import_Source_WC_Core extends SVI_Gallery_Import_Source
    {
        /** Core variation gallery meta. */
        const GALLERY_META = '_product_image_gallery';

        /** Core variation image meta. */
        const IMAGE_META = '_thumbnail_id';

        /** Parents already handled in this request. */
        private array $processed = array();

        /** Variations read in this request. */
        private int $variations = 0;

        /**
         * Machine name.
         *
         * @return string
         */
        public function get_slug(): string
        {
            return 'wc-core';
        }

        /**
         * Label.
         *
         * @return string
         */
        public function get_label(): string
        {
            return __('WooCommerce (core variation galleries)', 'smart-variations-images');
        }

        /**
         * WooCommerce ships the data, so the plugin file is WooCommerce itself.
         *
         * @return string[]
         */
        protected function get_plugin_files(): array
        {
            return array('woocommerce/woocommerce.php');
        }

        /**
         * Meta key used by the detection query.
         *
         * @return string
         */
        public function get_meta_key(): string
        {
            return self::GALLERY_META;
        }

        /**
         * Core variation galleries only exist from WooCommerce 11.1.
         *
         * @return bool
         */
        private function supported(): bool
        {
            return defined('WC_VERSION') && version_compare((string) WC_VERSION, '11.1', '>=');
        }

        /**
         * Status, with an explanation when the WooCommerce version is too old.
         *
         * @return string
         */
        public function status(): string
        {
            return $this->supported() ? parent::status() : self::STATUS_NONE;
        }

        /**
         * Reason shown next to the status.
         *
         * @return string
         */
        public function get_status_reason(): string
        {
            if ($this->supported()) {
                return '';
            }

            return sprintf(
                /* translators: %s: WooCommerce version. */
                __('WooCommerce 11.1 or newer is required for per-variation galleries; this store runs %s.', 'smart-variations-images'),
                defined('WC_VERSION') ? WC_VERSION : '?'
            );
        }

        /**
         * Variations that carry a core image or gallery.
         *
         * @return int
         */
        public function total(): int
        {
            global $wpdb;

            if (!$this->supported()) {
                return 0;
            }

            return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
                 LEFT JOIN {$wpdb->postmeta} g ON g.post_id = p.ID AND g.meta_key = '" . esc_sql(self::GALLERY_META) . "' AND g.meta_value <> ''
                 LEFT JOIN {$wpdb->postmeta} t ON t.post_id = p.ID AND t.meta_key = '" . esc_sql(self::IMAGE_META) . "' AND t.meta_value <> ''
                 WHERE p.post_type = 'product_variation'
                   AND ( g.post_id IS NOT NULL OR t.post_id IS NOT NULL )"
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

            if (!$this->supported()) {
                return array();
            }

            $rows = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->prepare(
                    "SELECT DISTINCT p.ID FROM {$wpdb->posts} p
                     LEFT JOIN {$wpdb->postmeta} g ON g.post_id = p.ID AND g.meta_key = %s AND g.meta_value <> ''
                     LEFT JOIN {$wpdb->postmeta} t ON t.post_id = p.ID AND t.meta_key = %s AND t.meta_value <> ''
                     WHERE p.post_type = 'product_variation'
                       AND ( g.post_id IS NOT NULL OR t.post_id IS NOT NULL )
                     ORDER BY p.ID ASC
                     LIMIT %d OFFSET %d",
                    self::GALLERY_META,
                    self::IMAGE_META,
                    max(1, $limit),
                    max(0, $offset)
                )
            );

            return array_map('intval', (array) $rows);
        }

        /**
         * Counters for the run summary.
         *
         * @return array<string,int>
         */
        public function get_summary(): array
        {
            return array('variations' => $this->variations);
        }

        /**
         * Write one product, grouping equal image sets by attribute value.
         *
         * @param SVI_Gallery_Import_Writer $writer       Writer.
         * @param int                       $variation_id Variation id.
         * @return array
         */
        public function write(SVI_Gallery_Import_Writer $writer, int $variation_id): array
        {
            $result = array('status' => 'skipped', 'reason' => '', 'parent' => 0, 'images' => 0);
            $variation = function_exists('wc_get_product') ? wc_get_product($variation_id) : null;

            if (!$variation instanceof WC_Product_Variation) {
                $result['reason'] = 'invalid-variation';

                return $result;
            }

            $parent_id = (int) $variation->get_parent_id();
            $result['parent'] = $parent_id;

            // One product per request pass: the runner walks variations, this
            // source writes whole products.
            if (isset($this->processed[$parent_id])) {
                $result['reason'] = 'already-processed';

                return $result;
            }

            $this->processed[$parent_id] = true;
            $parent = wc_get_product($parent_id);

            if (!$parent instanceof WC_Product || !$parent->is_type('variable')) {
                $result['reason'] = 'no-parent';

                return $result;
            }

            $rows = $this->variation_images($parent);

            if (!$rows) {
                $result['reason'] = 'no-images';

                return $result;
            }

            $this->variations += count($rows);
            $group = $this->grouped_images($parent, $rows);

            if (null !== $group) {
                foreach ($group['values'] as $value => $images) {
                    $written = $writer->write_slugs($images, array(sanitize_title((string) $value)), $parent_id, $this->get_slug());

                    if (in_array($written['status'], array('preview', 'migrated'), true)) {
                        $result = array_merge($written, array('parent' => $parent_id));
                    }
                }

                return $result;
            }

            foreach ($rows as $child_id => $row) {
                $written = $writer->write($row['images'], (int) $child_id, $this->get_slug());

                if (in_array($written['status'], array('preview', 'migrated'), true)) {
                    $result = array_merge($written, array('parent' => $parent_id));
                }
            }

            return $result;
        }

        /**
         * Core image set of every variation of a product.
         *
         * The variation image is read from its own meta: `get_image_id()` would
         * fall back to the parent image and give every variation the same one.
         *
         * @param WC_Product $parent Parent product.
         * @return array<int,array{images:int[],attrs:array<string,string>}>
         */
        private function variation_images(WC_Product $parent): array
        {
            $rows = array();

            foreach ($parent->get_children() as $child_id) {
                $child_id = (int) $child_id;
                $image    = absint(get_post_meta($child_id, self::IMAGE_META, true));
                $gallery  = $this->parse(get_post_meta($child_id, self::GALLERY_META, true));
                $images   = array_values(array_unique(array_filter(array_merge(array($image), $gallery))));
                $child    = wc_get_product($child_id);

                $rows[$child_id] = array(
                    'images' => $images,
                    'attrs'  => $child instanceof WC_Product_Variation ? (array) $child->get_attributes() : array(),
                );
            }

            return $rows;
        }

        /**
         * Attribute whose values share one image set across every variation.
         *
         * @param WC_Product $parent Parent product.
         * @param array      $rows   Variation rows.
         * @return array{attribute:string,values:array<string,int[]>}|null
         */
        private function grouped_images(WC_Product $parent, array $rows): ?array
        {
            foreach ($parent->get_attributes() as $key => $attribute) {
                if (!$attribute instanceof WC_Product_Attribute || !$attribute->get_variation()) {
                    continue;
                }

                $sets = array();

                foreach ($rows as $row) {
                    $value = isset($row['attrs'][$key]) ? (string) $row['attrs'][$key] : '';

                    if ('' === $value) {
                        continue;
                    }

                    $sets[$value][] = $row['images'];
                }

                if (count($sets) < 2) {
                    continue;
                }

                $uniform = true;

                foreach ($sets as $group) {
                    if (empty($group[0])) {
                        $uniform = false;
                        break;
                    }

                    foreach ($group as $images) {
                        if ($images !== $group[0]) {
                            $uniform = false;
                            break 2;
                        }
                    }
                }

                if (!$uniform) {
                    continue;
                }

                return array(
                    'attribute' => (string) $key,
                    'values'    => array_map(
                        static function ($group) {
                            return $group[0];
                        },
                        $sets
                    ),
                );
            }

            return null;
        }
    }
}
