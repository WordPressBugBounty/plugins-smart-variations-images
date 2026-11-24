<?php
/**
 * Settings tab for importing galleries from another plugin.
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Gallery_Import_Admin')) {
    /**
     * Registers the tab, the AJAX endpoints and the screen markup.
     *
     * The tab is always registered so the feature can be found; the screen
     * explains itself when no source data exists yet.
     */
    final class SVI_Gallery_Import_Admin
    {
        /** Pane id inside the Migration tab. */
        const PANE_ID = 'variation-galleries';

        /** AJAX nonce action. */
        const NONCE = 'svi_gallery_import';

        /** Variations per request. */
        const BATCH = 25;

        /**
         * Hook everything up.
         */
        public static function init(): void
        {
            add_filter('svi_migration_screens', array(__CLASS__, 'register_screen'), 20);

            foreach (array('preview', 'run', 'undo', 'runs') as $action) {
                add_action('wp_ajax_svi_gallery_import_' . $action, array(__CLASS__, 'handle_' . $action));
            }
        }

        /**
         * Add the pane to the Migration tab.
         *
         * @param array $screens Registered panes.
         * @return array
         */
        public static function register_screen(array $screens): array
        {
            $screens   = is_array($screens) ? $screens : array();
            $screens[] = array(
                'id'     => self::PANE_ID,
                'label'  => __('Variation galleries', 'smart-variations-images'),
                'order'  => 20,
                'render' => array(__CLASS__, 'render_screen'),
            );

            return $screens;
        }

        /**
         * Screen markup.
         */
        public static function render_screen(): void
        {
            $sources = SVI_Gallery_Import_Runner::detect_all();
            $status  = array(
                SVI_Gallery_Import_Source::STATUS_ACTIVE   => __('Installed and active', 'smart-variations-images'),
                SVI_Gallery_Import_Source::STATUS_INACTIVE => __('Installed, not active', 'smart-variations-images'),
                SVI_Gallery_Import_Source::STATUS_DATA     => __('Removed, data left behind', 'smart-variations-images'),
                SVI_Gallery_Import_Source::STATUS_NONE     => __('Not on this site', 'smart-variations-images'),
            );
            ?>
            <div class="svi-gallery-importer" data-nonce="<?php echo esc_attr(wp_create_nonce(self::NONCE)); ?>">
                <p><strong><?php esc_html_e('Back up your database before you start.', 'smart-variations-images'); ?></strong>
                    <?php esc_html_e('The import only adds SVI galleries. The other plugin keeps all of its data, and every product keeps a backup of its previous SVI gallery so you can undo.', 'smart-variations-images'); ?></p>

                <p class="description"><?php esc_html_e('Every plugin SVI can read is listed below. Greyed-out rows are plugins that are not installed on this site, so there is nothing to import from them.', 'smart-variations-images'); ?></p>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php esc_html_e('Import from', 'smart-variations-images'); ?></th>
                            <th scope="col"><?php esc_html_e('Status', 'smart-variations-images'); ?></th>
                            <th scope="col"><?php esc_html_e('Variations with images', 'smart-variations-images'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sources as $slug => $item) : ?>
                            <?php $ready = (int) $item['total'] > 0; ?>
                            <tr class="<?php echo $ready ? '' : 'svi-source--off'; ?>">
                                <td>
                                    <label>
                                        <input type="radio" name="svi_gallery_source" value="<?php echo esc_attr($slug); ?>" <?php disabled(!$ready); ?> />
                                        <strong><?php echo esc_html($item['label']); ?></strong>
                                    </label>
                                    <?php if (!$ready) : ?>
                                        <span class="svi-source__hint"><?php echo esc_html(
                                            !empty($item['reason'])
                                                ? $item['reason']
                                                : (SVI_Gallery_Import_Source::STATUS_NONE === $item['status']
                                                    ? __('Not installed on this site.', 'smart-variations-images')
                                                    : __('Installed, but no variation images saved yet.', 'smart-variations-images'))
                                        ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($status[$item['status']] ?? $item['status']); ?></td>
                                <td><?php echo $ready ? esc_html((string) (int) $item['total']) : '&mdash;'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <p>
                    <label>
                        <input type="checkbox" name="svi_gallery_overwrite" value="1" />
                        <?php esc_html_e('Replace an SVI gallery that already exists for the same variation', 'smart-variations-images'); ?>
                    </label>
                </p>

                <p>
                    <button type="button" class="button" data-svi-gallery="preview"><?php esc_html_e('Preview', 'smart-variations-images'); ?></button>
                    <button type="button" class="button button-primary" data-svi-gallery="run"><?php esc_html_e('Import', 'smart-variations-images'); ?></button>
                    <span class="spinner"></span>
                </p>

                <div class="svi-gallery-importer__progress" hidden>
                    <div class="svi-gp__track">
                        <div class="svi-gp__bar" data-svi-gallery="bar"></div>
                    </div>
                    <span class="svi-gp__label" data-svi-gallery="label"></span>
                    <p class="svi-gp__meta" data-svi-gallery="text"></p>
                </div>

                <pre data-svi-gallery="out" style="max-height:320px;overflow:auto;background:#f6f7f7;padding:12px;margin-top:12px;" hidden></pre>

                <div class="svi-importer__undo" data-svi-gallery="undo"></div>
            </div>

            <script>
            (function () {
                var root = document.currentScript.previousElementSibling;
                while (root && !root.classList.contains('svi-gallery-importer')) {
                    root = root.previousElementSibling;
                }
                if (!root || root.dataset.ready === '1') { return; }
                root.dataset.ready = '1';

                var box = root;
                var nonce = box.dataset.nonce;
                var out = box.querySelector('[data-svi-gallery="out"]');
                var bar = box.querySelector('[data-svi-gallery="bar"]');
                var txt = box.querySelector('[data-svi-gallery="text"]');
                var wrap = box.querySelector('.svi-gallery-importer__progress');
                var undoBox = box.querySelector('[data-svi-gallery="undo"]');
                var batch = <?php echo (int) self::BATCH; ?>;
                var str = <?php echo wp_json_encode(array(
                    'recent'     => __('Recent imports', 'smart-variations-images'),
                    'undo'       => __('Undo this import', 'smart-variations-images'),
                    'undoFailed' => __('Undo failed.', 'smart-variations-images'),
                    'variationOne' => __('%d variation imported', 'smart-variations-images'),
                    'variationMany' => __('%d variations imported', 'smart-variations-images'),
                    'productOne' => __('%d product touched', 'smart-variations-images'),
                    'productMany' => __('%d products touched', 'smart-variations-images'),
                )); ?>;

                function source() {
                    var el = box.querySelector('input[name="svi_gallery_source"]:checked');
                    return el ? el.value : '';
                }

                function overwrite() {
                    var el = box.querySelector('input[name="svi_gallery_overwrite"]');
                    return el && el.checked ? '1' : '';
                }

                function post(action, data) {
                    var body = new URLSearchParams();
                    body.set('action', 'svi_gallery_import_' + action);
                    body.set('_ajax_nonce', nonce);
                    Object.keys(data).forEach(function (k) { body.set(k, data[k]); });
                    return fetch(window.ajaxurl, { method: 'POST', credentials: 'same-origin', body: body })
                        .then(function (r) { return r.json(); });
                }

                function show(text) {
                    out.hidden = false;
                    out.textContent += text + '\n';
                    out.scrollTop = out.scrollHeight;
                }

                var labelEl = box.querySelector('[data-svi-gallery="label"]');

                function progress(done, total, label) {
                    wrap.hidden = false;
                    var pct = total > 0 ? Math.min(100, Math.round((done / total) * 100)) : 0;
                    bar.style.width = pct + '%';
                    if (labelEl) { labelEl.textContent = label + ' — ' + pct + '%'; }
                    txt.textContent = done + ' of ' + total + ' variations · ' + label;
                }

                function preview() {
                    if (!source()) { show('Pick a source first.'); return; }
                    out.hidden = false; out.textContent = '';
                    post('preview', { source: source(), offset: 0, limit: batch, overwrite: overwrite() })
                        .then(function (res) {
                            if (!res.success) { show('Preview failed.'); return; }
                            show('Preview: ' + res.data.preview + ' variations would be imported, ' + res.data.skipped + ' skipped (of ' + res.data.total + ').'
                                + (res.data.summary_line ? ' ' + res.data.summary_line : ''));
                        });
                }

                function run(offset, runId, total, migrated) {
                    if (!source()) { show('Pick a source first.'); return; }
                    post('run', { source: source(), run_id: runId, offset: offset, limit: batch, overwrite: overwrite() })
                        .then(function (res) {
                            if (!res.success) { show('Import failed.'); return; }
                            var d = res.data;
                            var done = migrated + d.migrated;
                            progress(d.next_offset, d.total, done + ' imported');
                            if (d.done) {
                                show('Done. '
                                    + (done === 1 ? str.variationOne : str.variationMany).replace('%d', done)
                                    + ', ' + d.skipped + ' skipped in this batch, '
                                    + (d.products === 1 ? str.productOne : str.productMany).replace('%d', d.products)
                                    + '. Run id: ' + d.run_id);
                                loadRuns();
                                return;
                            }
                            setTimeout(function () { run(d.next_offset, d.run_id, d.total, done); }, 120);
                        });
                }

                function loadRuns() {
                    if (!undoBox) { return; }

                    post('runs', {}).then(function (res) {
                        if (!res.success || !res.data.runs.length) {
                            undoBox.innerHTML = '';
                            return;
                        }

                        undoBox.innerHTML = '<p><strong>' + str.recent + '</strong></p>';

                        var list = document.createElement('ul');
                        list.className = 'svi-importer__runs';

                        res.data.runs.forEach(function (run) {
                            var li = document.createElement('li');
                            var label = document.createElement('span');
                            label.textContent = run.started + ' · ' + run.label + ' · ' + run.summary;
                            li.appendChild(label);

                            var button = document.createElement('button');
                            button.type = 'button';
                            button.className = 'button-link-delete';
                            button.textContent = str.undo;
                            button.addEventListener('click', function () { undoRun(run.id); });
                            li.appendChild(button);

                            list.appendChild(li);
                        });

                        undoBox.appendChild(list);
                    });
                }

                function undoRun(id) {
                    post('undo', { run_id: id }).then(function (res) {
                        show(res.success ? res.data.message : str.undoFailed);
                        loadRuns();
                    });
                }

                box.addEventListener('click', function (event) {
                    var button = event.target.closest('[data-svi-gallery]');
                    if (!button) { return; }
                    var what = button.dataset.sviGallery;
                    if (what === 'preview') { preview(); }
                    if (what === 'run') { out.hidden = false; out.textContent = ''; progress(0, 1, 'starting'); run(0, '', 0, 0); }
                });

                loadRuns();
            })();
            </script>
            <?php
        }

        /**
         * AJAX: preview one page.
         */
        public static function handle_preview(): void
        {
            self::guard();

            $slug = isset($_POST['source']) ? sanitize_key(wp_unslash($_POST['source'])) : '';

            wp_send_json_success(SVI_Gallery_Import_Runner::preview(
                $slug,
                self::offset(),
                self::limit(),
                !empty($_POST['overwrite'])
            ));
        }

        /**
         * AJAX: import one page.
         */
        public static function handle_run(): void
        {
            self::guard();

            $slug  = isset($_POST['source']) ? sanitize_key(wp_unslash($_POST['source'])) : '';
            $run   = isset($_POST['run_id']) ? sanitize_text_field(wp_unslash($_POST['run_id'])) : '';

            wp_send_json_success(SVI_Gallery_Import_Runner::import(
                $slug,
                $run,
                self::offset(),
                self::limit(),
                !empty($_POST['overwrite'])
            ));
        }

        /**
         * AJAX: undo a run.
         */
        public static function handle_undo(): void
        {
            self::guard();

            $run = isset($_POST['run_id']) ? sanitize_text_field(wp_unslash($_POST['run_id'])) : '';

            $result = SVI_Gallery_Import_Runner::undo($run);

            if (!isset($result['error'])) {
                $result['message'] = sprintf(
                    /* translators: %d: number of products restored. */
                    _n('Restored %d product.', 'Restored %d products.', (int) $result['restored'], 'smart-variations-images'),
                    (int) $result['restored']
                );
            }

            wp_send_json_success($result);
        }

        /**
         * AJAX: list the runs that can still be undone.
         */
        public static function handle_runs(): void
        {
            self::guard();

            wp_send_json_success(array('runs' => SVI_Gallery_Import_Runner::runs_report()));
        }

        /**
         * Capability and nonce check.
         */
        private static function guard(): void
        {
            check_ajax_referer(self::NONCE);

            if (!current_user_can('manage_woocommerce')) {
                wp_send_json_error(array('message' => 'forbidden'), 403);
            }
        }

        /**
         * Requested offset.
         *
         * @return int
         */
        private static function offset(): int
        {
            return isset($_POST['offset']) ? absint($_POST['offset']) : 0;
        }

        /**
         * Requested page size, clamped.
         *
         * @return int
         */
        private static function limit(): int
        {
            $limit = isset($_POST['limit']) ? absint($_POST['limit']) : self::BATCH;

            return min(100, max(1, $limit));
        }
    }
}
