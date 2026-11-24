<?php
/**
 * Observes legacy gallery writes and mirrors them.
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Mirror_Observer')) {
    /**
     * Watches `woosvi_slug` writes instead of editing the five call sites.
     *
     * That keeps `admin/class-smart-variations-images-admin.php` and
     * `public/class-smart-variations-images-public.php` untouched, and it also
     * catches writers we do not own (REST import, the WooCommerce CSV
     * importer, third-party code, the galleries-import add-on).
     */
    final class SVI_Mirror_Observer
    {
        /** Legacy meta key. */
        const META_KEY = 'woosvi_slug';

        /** Product ids seen in this request. */
        const MAX_QUEUE = 50;

        /**
         * Pending product ids, keyed by id.
         *
         * @var array<int,bool>
         */
        private static array $queue = array();

        /**
         * Register state.
         *
         * @var bool
         */
        private static bool $registered = false;

        /**
         * Hook the meta actions.
         */
        public static function register(): void
        {
            if (self::$registered) {
                return;
            }

            self::$registered = true;

            add_action('added_post_meta', array(__CLASS__, 'capture'), 10, 4);
            add_action('updated_post_meta', array(__CLASS__, 'capture'), 10, 4);
            add_action('deleted_post_meta', array(__CLASS__, 'capture_deleted'), 10, 4);
            add_action('shutdown', array(__CLASS__, 'flush'), 1);
        }

        /**
         * Queue a write.
         *
         * @param int    $meta_id   Meta row id.
         * @param int    $object_id Post id.
         * @param string $meta_key  Meta key.
         * @param mixed  $meta_value Value.
         */
        public static function capture($meta_id, $object_id, $meta_key, $meta_value): void
        {
            if (self::META_KEY !== $meta_key) {
                return;
            }

            self::enqueue((int) $object_id);
        }

        /**
         * Queue a delete: the mirror becomes an explicit empty gallery.
         *
         * @param array  $meta_ids   Meta row ids.
         * @param int    $object_id  Post id.
         * @param string $meta_key   Meta key.
         * @param mixed  $meta_value Value.
         */
        public static function capture_deleted($meta_ids, $object_id, $meta_key, $meta_value): void
        {
            if (self::META_KEY !== $meta_key) {
                return;
            }

            $product_id = (int) $object_id;

            if ($product_id <= 0) {
                return;
            }

            SVI_Mirror_Writer::sync($product_id, true);
        }

        /**
         * Add a product to the queue.
         *
         * @param int $product_id Product id.
         */
        private static function enqueue(int $product_id): void
        {
            if ($product_id <= 0) {
                return;
            }

            self::$queue[$product_id] = true;

            if (count(self::$queue) >= self::MAX_QUEUE) {
                self::flush();
            }
        }

        /**
         * Mirror every queued product.
         *
         * Runs on `shutdown` so a bulk import mirrors after it has finished
         * writing, not once per row. A request that never reaches shutdown is
         * repaired by the backfill pass.
         */
        public static function flush(): void
        {
            if (empty(self::$queue)) {
                return;
            }

            $ids = array_keys(self::$queue);
            self::$queue = array();

            foreach ($ids as $product_id) {
                SVI_Mirror_Writer::sync((int) $product_id);
            }
        }
    }
}
