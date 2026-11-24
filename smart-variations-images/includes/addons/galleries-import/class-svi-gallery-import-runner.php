<?php
/**
 * Orchestrates detection, preview, batched import and undo.
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Gallery_Import_Runner')) {
    /**
     * Stateless batching: the caller drives the cursor, so a slow run resumes
     * exactly where it stopped.
     */
    final class SVI_Gallery_Import_Runner
    {
        /** Recent runs, used by Undo. */
        const RUNS_OPTION = 'svi_gallery_import_runs';

        /** Runs kept. */
        const MAX_RUNS = 5;

        /**
         * Registered sources.
         *
         * @return SVI_Gallery_Import_Source[]
         */
        public static function get_sources(): array
        {
            $sources = apply_filters('svi_gallery_import_sources', array());

            return is_array($sources) ? $sources : array();
        }

        /**
         * One source by slug.
         *
         * @param string $slug Source slug.
         * @return SVI_Gallery_Import_Source|null
         */
        public static function get_source(string $slug): ?SVI_Gallery_Import_Source
        {
            foreach (self::get_sources() as $source) {
                if ($source instanceof SVI_Gallery_Import_Source && $source->get_slug() === $slug) {
                    return $source;
                }
            }

            return null;
        }

        /**
         * Detection report for every source.
         *
         * @return array<string,array>
         */
        public static function detect_all(): array
        {
            $report = array();

            foreach (self::get_sources() as $source) {
                if (!$source instanceof SVI_Gallery_Import_Source) {
                    continue;
                }

                $report[$source->get_slug()] = array(
                    'slug'   => $source->get_slug(),
                    'label'  => $source->get_label(),
                    'status' => $source->status(),
                    'total'  => $source->total(),
                    'reason' => $source->get_status_reason(),
                );
            }

            return $report;
        }

        /**
         * Sources with data to import. An installed but empty plugin is not
         * shown: there would be nothing to do.
         *
         * @return array<string,array>
         */
        public static function get_detected(): array
        {
            return array_filter(
                self::detect_all(),
                static function ($item) {
                    return (int) $item['total'] > 0;
                }
            );
        }

        /**
         * Report what an import would change, without writing.
         *
         * @param string $slug      Source slug.
         * @param int    $offset    Offset.
         * @param int    $limit     Page size.
         * @param bool   $overwrite Replace existing entries.
         * @return array
         */
        public static function preview(string $slug, int $offset = 0, int $limit = 25, bool $overwrite = false): array
        {
            $source = self::get_source($slug);

            if (null === $source) {
                return array('error' => 'unknown-source');
            }

            $writer = new SVI_Gallery_Import_Writer($overwrite, true);
            $ids    = $source->variation_ids($offset, max(1, $limit));
            $counts = array('preview' => 0, 'skipped' => 0);
            $sample = array();

            foreach ($ids as $variation_id) {
                $result = $source->write($writer, $variation_id);
                $counts[$result['status']] = ($counts[$result['status']] ?? 0) + 1;

                if ('preview' === $result['status'] && count($sample) < 10) {
                    $sample[] = array(
                        'variation' => $variation_id,
                        'parent'    => $result['parent'],
                        'images'    => $result['images'],
                    );
                }
            }

            return array(
                'source'      => $slug,
                'total'       => $source->total(),
                'offset'      => $offset,
                'processed'   => count($ids),
                'preview'     => $counts['preview'] ?? 0,
                'skipped'     => $counts['skipped'] ?? 0,
                'sample'      => $sample,
                'galleries'   => $writer->created(),
                'summary_line' => self::summary_line($source, $writer),
                'next_offset' => $offset + count($ids),
                'done'        => count($ids) < max(1, $limit),
            );
        }

        /**
         * Import one page.
         *
         * @param string      $slug      Source slug.
         * @param string|null $run_id    Run id, created on the first call.
         * @param int         $offset    Offset.
         * @param int         $limit     Page size.
         * @param bool        $overwrite Replace existing entries.
         * @return array
         */
        public static function import(string $slug, ?string $run_id = null, int $offset = 0, int $limit = 25, bool $overwrite = false): array
        {
            $source = self::get_source($slug);

            if (null === $source) {
                return array('error' => 'unknown-source');
            }

            $run_id = $run_id ? preg_replace('/[^a-z0-9_]/', '', $run_id) : 'run_' . time();
            $writer = new SVI_Gallery_Import_Writer($overwrite, false);
            $ids    = $source->variation_ids($offset, max(1, $limit));
            $counts = array('migrated' => 0, 'skipped' => 0);

            foreach ($ids as $variation_id) {
                $result = $source->write($writer, $variation_id);
                $counts[$result['status']] = ($counts[$result['status']] ?? 0) + 1;
            }

            $runs = self::get_runs();
            $run  = $runs[$run_id] ?? array(
                'id'       => $run_id,
                'source'   => $slug,
                'started'  => time(),
                'migrated' => 0,
                'skipped'  => 0,
                'products' => array(),
            );

            $run['updated']  = time();
            $run['migrated'] = (int) $run['migrated'] + ($counts['migrated'] ?? 0);
            $run['skipped']  = (int) $run['skipped'] + ($counts['skipped'] ?? 0);
            $run['products'] = array_values(array_unique(array_merge(
                (array) $run['products'],
                $writer->touched()
            )));

            $runs[$run_id] = $run;
            $runs          = array_slice($runs, -self::MAX_RUNS, null, true);

            update_option(self::RUNS_OPTION, $runs, false);

            return array(
                'run_id'      => $run_id,
                'source'      => $slug,
                'total'       => $source->total(),
                'offset'      => $offset,
                'processed'   => count($ids),
                'migrated'    => $counts['migrated'] ?? 0,
                'skipped'     => $counts['skipped'] ?? 0,
                'products'    => count($run['products']),
                'galleries'   => $writer->created(),
                'summary_line' => self::summary_line($source, $writer),
                'next_offset' => $offset + count($ids),
                'done'        => count($ids) < max(1, $limit),
            );
        }

        /**
         * "42 variation galleries → 6 SVI galleries" for sources that group.
         *
         * @param SVI_Gallery_Import_Source $source Source.
         * @param SVI_Gallery_Import_Writer $writer Writer.
         * @return string
         */
        private static function summary_line(SVI_Gallery_Import_Source $source, SVI_Gallery_Import_Writer $writer): string
        {
            $summary    = $source->get_summary();
            $variations = (int) ($summary['variations'] ?? 0);

            if ($variations <= 0) {
                return '';
            }

            return sprintf(
                /* translators: 1: variation galleries read, 2: SVI galleries written. */
                __('%1$d variation galleries → %2$d SVI galleries.', 'smart-variations-images'),
                $variations,
                $writer->created()
            );
        }

        /**
         * Put every product of a run back exactly as it was.
         *
         * @param string $run_id Run id.
         * @return array
         */
        public static function undo(string $run_id): array
        {
            $runs = self::get_runs();

            if (!isset($runs[$run_id])) {
                return array('error' => 'unknown-run');
            }

            $restored = 0;

            foreach ((array) $runs[$run_id]['products'] as $product_id) {
                $product_id = (int) $product_id;
                $backup     = get_post_meta($product_id, SVI_Gallery_Import_Writer::BACKUP_META, true);

                if (empty($backup) || !is_array($backup)) {
                    continue;
                }

                $payload = $backup['payload'] ?? null;

                if (null === $payload || '' === $payload) {
                    delete_post_meta($product_id, SVI_Gallery_Import_Writer::LEGACY_META);
                } else {
                    update_post_meta($product_id, SVI_Gallery_Import_Writer::LEGACY_META, $payload);
                }

                delete_post_meta($product_id, SVI_Gallery_Import_Writer::BACKUP_META);
                ++$restored;
            }

            unset($runs[$run_id]);
            update_option(self::RUNS_OPTION, $runs, false);

            return array('restored' => $restored);
        }

        /**
         * Recent runs, newest first, formatted for the screen.
         *
         * @return array<int,array>
         */
        public static function runs_report(): array
        {
            $labels = array();

            foreach (self::get_sources() as $source) {
                $labels[$source->get_slug()] = $source->get_label();
            }

            $report = array();

            foreach (array_reverse(self::get_runs(), true) as $run_id => $run) {
                $migrated = (int) ($run['migrated'] ?? 0);
                $products = count((array) ($run['products'] ?? array()));

                $report[] = array(
                    'id'       => $run_id,
                    'label'    => $labels[$run['source']] ?? $run['source'],
                    'started'  => wp_date(
                        get_option('date_format') . ' ' . get_option('time_format'),
                        (int) ($run['started'] ?? 0)
                    ),
                    'migrated' => $migrated,
                    'skipped'  => (int) ($run['skipped'] ?? 0),
                    'products' => $products,
                    'summary'  => sprintf(
                        '%s · %s',
                        sprintf(
                            /* translators: %d: number of variations. */
                            _n('%d variation', '%d variations', $migrated, 'smart-variations-images'),
                            $migrated
                        ),
                        sprintf(
                            /* translators: %d: number of products. */
                            _n('%d product', '%d products', $products, 'smart-variations-images'),
                            $products
                        )
                    ),
                );
            }

            return $report;
        }

        /**
         * Recent runs, newest last.
         *
         * @return array
         */
        public static function get_runs(): array
        {
            $runs = get_option(self::RUNS_OPTION, array());

            return is_array($runs) ? $runs : array();
        }
    }
}
