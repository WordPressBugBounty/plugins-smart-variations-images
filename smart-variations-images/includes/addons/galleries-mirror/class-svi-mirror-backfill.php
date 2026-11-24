<?php
/**
 * Background pass over the existing catalogue.
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Mirror_Backfill')) {
    /**
     * Mirrors products that were never saved after the feature was enabled.
     *
     * Batched and cursor-based (post id), so it resumes after a timeout without
     * re-reading the table. Uses Action Scheduler when WooCommerce provides it
     * and falls back to a single WP-Cron event.
     */
    final class SVI_Mirror_Backfill
    {
        /** State option. */
        const STATE = 'svi_mirror_backfill_state';

        /** Batch hook. */
        const HOOK = 'svi_mirror_sync_batch';

        /** Products per batch. */
        const BATCH = 25;

        /**
         * Running / paused / done / idle.
         *
         * @var string
         */
        const RUNNING = 'running';

        /**
         * Hook the batch action.
         */
        public static function register(): void
        {
            add_action(self::HOOK, array(__CLASS__, 'handle_batch'), 10, 1);
        }

        /**
         * Current state.
         *
         * @return array
         */
        public static function state(): array
        {
            $state = get_option(self::STATE, array());

            return is_array($state) ? $state : array();
        }

        /**
         * Start (or restart) the pass.
         *
         * @return array State after the call.
         */
        public static function start(): array
        {
            if (!SVI_Mirror_Mode::should_backfill()) {
                return array('status' => 'disabled', 'message' => 'Mode is not write+backfill.');
            }

            $state = array(
                'status'    => self::RUNNING,
                'cursor'    => 0,
                'processed' => 0,
                'synced'    => 0,
                'skipped'   => 0,
                'errors'    => 0,
                'started'   => time(),
                'updated'   => time(),
            );

            update_option(self::STATE, $state, false);
            self::schedule(0);

            return $state;
        }

        /**
         * Pause the pass. Nothing is deleted.
         *
         * @return array
         */
        public static function pause(): array
        {
            $state           = self::state();
            $state['status'] = 'paused';
            $state['updated'] = time();
            update_option(self::STATE, $state, false);

            return $state;
        }

        /**
         * Resume a paused pass from its cursor.
         *
         * @return array
         */
        public static function resume(): array
        {
            $state = self::state();

            if (self::RUNNING === ($state['status'] ?? '')) {
                return $state;
            }

            if (!SVI_Mirror_Mode::should_backfill()) {
                return array('status' => 'disabled', 'message' => 'Mode is not write+backfill.');
            }

            $state['status']  = self::RUNNING;
            $state['updated'] = time();
            update_option(self::STATE, $state, false);
            self::schedule((int) ($state['cursor'] ?? 0));

            return $state;
        }

        /**
         * Forget the pass (does not touch product data).
         *
         * @return void
         */
        public static function reset(): void
        {
            delete_option(self::STATE);
        }

        /**
         * Process one batch.
         *
         * @param int $cursor Last processed product id.
         */
        public static function handle_batch($cursor = 0): void
        {
            $state = self::state();

            if (self::RUNNING !== ($state['status'] ?? '')) {
                return;
            }

            if (!SVI_Mirror_Mode::should_backfill()) {
                $state['status']  = 'disabled';
                $state['updated'] = time();
                update_option(self::STATE, $state, false);

                return;
            }

            $cursor = (int) $cursor;
            $ids    = self::ids_after($cursor);

            if (empty($ids)) {
                $state['status']  = 'done';
                $state['updated'] = time();
                $state['cursor']  = $cursor;
                update_option(self::STATE, $state, false);

                return;
            }

            $synced  = 0;
            $skipped = 0;
            $errors  = 0;

            foreach ($ids as $product_id) {
                $before = time();

                try {
                    $payload = SVI_Mirror_Writer::sync((int) $product_id);
                    $marker  = get_post_meta((int) $product_id, '_svi_migration', true);

                    if (null === $payload) {
                        ++$skipped;
                    } elseif (is_array($marker) && 'error' === ($marker['status'] ?? '')) {
                        ++$errors;
                    } else {
                        ++$synced;
                    }
                } catch (Throwable $error) {
                    ++$errors;
                    error_log('[SVI Mirror] backfill ' . $product_id . ': ' . $error->getMessage()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
                }

                // Rest a little on slow hosts so a background pass never spikes CPU.
                if (time() === $before && function_exists('usleep')) {
                    usleep(5000);
                }
            }

            $state['cursor']    = (int) end($ids);
            $state['processed'] = (int) ($state['processed'] ?? 0) + count($ids);
            $state['synced']    = (int) ($state['synced'] ?? 0) + $synced;
            $state['skipped']   = (int) ($state['skipped'] ?? 0) + $skipped;
            $state['errors']    = (int) ($state['errors'] ?? 0) + $errors;
            $state['updated']   = time();

            if (count($ids) < self::BATCH) {
                $state['status'] = 'done';
            }

            update_option(self::STATE, $state, false);

            if (self::RUNNING === $state['status']) {
                self::schedule($state['cursor']);
            }
        }

        /**
         * Queue the next batch.
         *
         * @param int $cursor Cursor.
         */
        private static function schedule(int $cursor): void
        {
            if (function_exists('as_has_scheduled_action') && function_exists('as_enqueue_async_action')) {
                if (!as_has_scheduled_action(self::HOOK, array($cursor), 'svi-galleries')) {
                    as_enqueue_async_action(self::HOOK, array($cursor), 'svi-galleries');
                }

                return;
            }

            if (function_exists('wp_schedule_single_event')) {
                wp_schedule_single_event(time() + 5, self::HOOK, array($cursor));
            }
        }

        /**
         * Product ids that carry a legacy payload, after the cursor.
         *
         * @param int $cursor Cursor.
         * @return int[]
         */
        private static function ids_after(int $cursor): array
        {
            global $wpdb;

            $rows = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $wpdb->prepare(
                    "SELECT DISTINCT p.ID
                     FROM {$wpdb->posts} p
                     INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
                     WHERE p.post_type = 'product'
                       AND pm.meta_key = 'woosvi_slug'
                       AND pm.meta_value <> ''
                       AND p.ID > %d
                     ORDER BY p.ID ASC
                     LIMIT %d",
                    $cursor,
                    self::BATCH
                )
            );

            return array_map('intval', (array) $rows);
        }
    }
}
