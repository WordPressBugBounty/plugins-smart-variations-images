<?php
/**
 * Single write path for every gallery import.
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Gallery_Import_Writer')) {
    /**
     * Merges a variation's images into the parent product's `woosvi_slug`.
     *
     * Writing is additive: an existing gallery for the same attribute
     * combination keeps its images and gains the missing ones. `overwrite`
     * replaces that entry instead. Nothing is ever deleted from the source
     * plugin, and the parent payload is backed up once before the first write.
     */
    final class SVI_Gallery_Import_Writer
    {
        /** SVI legacy gallery meta. */
        const LEGACY_META = 'woosvi_slug';

        /** Write-once backup of the payload before the first import. */
        const BACKUP_META = 'woosvi_slug_import_backup';

        /**
         * Replace an existing entry instead of merging into it.
         *
         * @var bool
         */
        private bool $overwrite;

        /**
         * Report only, write nothing.
         *
         * @var bool
         */
        private bool $dry_run;

        /**
         * Parents touched in this batch.
         *
         * @var array<int,bool>
         */
        private array $touched = array();

        /**
         * Galleries created in this batch.
         *
         * @var int
         */
        private int $created = 0;

        /**
         * Constructor.
         *
         * @param bool $overwrite Replace existing entries.
         * @param bool $dry_run   Compute the outcome without writing.
         */
        public function __construct(bool $overwrite = false, bool $dry_run = false)
        {
            $this->overwrite = $overwrite;
            $this->dry_run   = $dry_run;
        }

        /**
         * Parent products written in this batch.
         *
         * @return int[]
         */
        public function touched(): array
        {
            return array_keys($this->touched);
        }

        /**
         * Galleries created (not merged) by this batch.
         *
         * @return int
         */
        public function created(): int
        {
            return $this->created;
        }

        /**
         * Import one variation.
         *
         * @param int[]  $image_ids    Attachment ids from the source plugin.
         * @param int    $variation_id Variation id.
         * @param string $source       Source slug, for the backup record.
         * @return array{status:string,reason:string,parent:int,images:int}
         */
        public function write(array $image_ids, int $variation_id, string $source): array
        {
            $result = array('status' => 'skipped', 'reason' => '', 'parent' => 0, 'images' => 0);

            $variation = function_exists('wc_get_product') ? wc_get_product($variation_id) : null;

            if (!$variation instanceof WC_Product_Variation) {
                $result['reason'] = 'invalid-variation';

                return $result;
            }

            $images = $this->valid_images($image_ids);

            if (empty($images)) {
                $result['reason'] = 'no-images';

                return $result;
            }

            $parent_id = (int) $variation->get_parent_id();
            $parent    = $parent_id > 0 ? wc_get_product($parent_id) : null;

            if (!$parent instanceof WC_Product) {
                $result['reason'] = 'no-parent';

                return $result;
            }

            $result['parent'] = $parent_id;
            $slugs            = $this->build_slugs($variation, $parent);

            return $this->write_slugs($images, $slugs, $parent_id, $source, $result);
        }

        /**
         * Import one gallery for an explicit attribute combination.
         *
         * Used by sources that group variations (one gallery per colour, for
         * example) and by the per-variation path above.
         *
         * @param int[]  $image_ids Attachment ids already validated.
         * @param array  $slugs     Attribute slugs of the gallery.
         * @param int    $parent_id Parent product id.
         * @param string $source    Source slug, for the backup record.
         * @param array  $result    Result to complete.
         * @return array{status:string,reason:string,parent:int,images:int}
         */
        public function write_slugs(array $image_ids, array $slugs, int $parent_id, string $source, array $result = array()): array
        {
            $result = wp_parse_args(
                $result,
                array('status' => 'skipped', 'reason' => '', 'parent' => $parent_id, 'images' => 0)
            );

            $images = $this->valid_images($image_ids);

            if (empty($images)) {
                $result['reason'] = 'no-images';

                return $result;
            }

            $payload          = $this->read_payload($parent_id);
            $key              = $this->slug_key($slugs);
            $found            = null;

            foreach ($payload as $index => $entry) {
                if (!is_array($entry) || !isset($entry['slugs']) || !is_array($entry['slugs'])) {
                    continue;
                }

                if ($this->slug_key($entry['slugs']) === $key) {
                    $found = $index;
                    break;
                }
            }

            if (null !== $found) {
                $existing = array_values(array_filter(array_map('absint', (array) ($payload[$found]['imgs'] ?? array()))));
                $merged   = $this->overwrite
                    ? $images
                    : array_values(array_unique(array_merge($existing, $images)));

                if ($merged === $existing) {
                    $result['reason'] = 'already-present';

                    return $result;
                }

                $payload[$found]['imgs'] = $merged;
            } else {
                $payload[] = array(
                    'slugs'       => $slugs,
                    'imgs'        => $images,
                    'loop_hidden' => false,
                );

                ++$this->created;
            }

            $result['status'] = $this->dry_run ? 'preview' : 'migrated';
            $result['images'] = count($images);

            if ($this->dry_run) {
                return $result;
            }

            $this->backup_once($parent_id, $source);

            update_post_meta($parent_id, self::LEGACY_META, $payload);

            $this->touched[$parent_id] = true;

            return $result;
        }

        /**
         * Attribute slugs of a variation, in the parent's attribute order.
         *
         * The legacy matcher joins slugs with `_svipro_` in attribute order, so
         * the order is part of the data, not cosmetic.
         *
         * @param WC_Product_Variation $variation Variation.
         * @param WC_Product           $parent    Parent product.
         * @return string[]
         */
        private function build_slugs(WC_Product_Variation $variation, WC_Product $parent): array
        {
            $attributes = (array) $variation->get_attributes();
            $slugs      = array();

            if ($parent->is_type('variable')) {
                foreach ($parent->get_attributes() as $key => $attribute) {
                    $meta_key = 'attribute_' . $key;

                    if (!array_key_exists($meta_key, $attributes)) {
                        continue;
                    }

                    $value = trim((string) $attributes[$meta_key]);

                    if ('' === $value) {
                        continue;
                    }

                    $slugs[] = sanitize_title(strtolower($value));
                }
            }

            if (empty($slugs)) {
                foreach ($attributes as $value) {
                    $value = trim((string) $value);

                    if ('' === $value) {
                        continue;
                    }

                    $slugs[] = sanitize_title(strtolower($value));
                }
            }

            return empty($slugs) ? array('svidefault') : $slugs;
        }

        /**
         * Order-insensitive key for an attribute combination.
         *
         * @param array $slugs Slugs.
         * @return string
         */
        private function slug_key(array $slugs): string
        {
            $normalised = array();

            foreach ($slugs as $slug) {
                if (!is_scalar($slug)) {
                    continue;
                }

                $slug = sanitize_title(strtolower((string) $slug));

                if ('' !== $slug) {
                    $normalised[] = $slug;
                }
            }

            sort($normalised);

            return implode('|', $normalised);
        }

        /**
         * Read the parent payload, decoding a JSON string in memory.
         *
         * @param int $parent_id Parent product id.
         * @return array
         */
        private function read_payload(int $parent_id): array
        {
            $raw = get_post_meta($parent_id, self::LEGACY_META, true);

            if (is_string($raw) && '' !== trim($raw)) {
                $decoded = json_decode($raw, true);
                $raw     = is_array($decoded) ? $decoded : array();
            }

            return is_array($raw) ? array_values($raw) : array();
        }

        /**
         * Keep only ids that are still image attachments.
         *
         * @param array $image_ids Candidate ids.
         * @return int[]
         */
        private function valid_images(array $image_ids): array
        {
            $valid = array();

            foreach ($image_ids as $image_id) {
                $image_id = absint($image_id);

                if ($image_id > 0 && wp_attachment_is_image($image_id)) {
                    $valid[] = $image_id;
                }
            }

            return array_values(array_unique($valid));
        }

        /**
         * Record the pre-import payload exactly once per product.
         *
         * @param int    $parent_id Parent product id.
         * @param string $source    Source slug.
         */
        private function backup_once(int $parent_id, string $source): void
        {
            if (!empty(get_post_meta($parent_id, self::BACKUP_META, true))) {
                return;
            }

            $raw = get_post_meta($parent_id, self::LEGACY_META, true);

            update_post_meta(
                $parent_id,
                self::BACKUP_META,
                array(
                    'at'      => time(),
                    'source'  => $source,
                    'payload' => $raw,
                )
            );
        }
    }
}
