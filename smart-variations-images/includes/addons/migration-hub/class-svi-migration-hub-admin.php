<?php
/**
 * The Migration settings tab: one tab, horizontal sub-tabs, one screen each.
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Migration_Hub_Admin')) {
    /**
     * Owns the tab shell. Importers contribute panes through
     * `svi_migration_screens` so neither has to know about the other.
     */
    final class SVI_Migration_Hub_Admin
    {
        /** Tab id. */
        const TAB_ID = 'migration';

        /**
         * Hook the tab.
         */
        public static function init(): void
        {
            add_filter('wpsfsvi_register_settings_woosvi_options', array(__CLASS__, 'register_tab'), 40);
            add_filter('wpsfsvi_register_settings_clean_woosvi_options', array(__CLASS__, 'register_tab'), 40);
        }

        /**
         * Registered panes, sorted by order.
         *
         * @return array
         */
        public static function screens(): array
        {
            $screens = apply_filters('svi_migration_screens', array());
            $screens = is_array($screens) ? $screens : array();

            $valid = array();

            foreach ($screens as $screen) {
                if (!is_array($screen) || empty($screen['id']) || empty($screen['label']) || empty($screen['render']) || !is_callable($screen['render'])) {
                    continue;
                }

                $screen['order'] = isset($screen['order']) ? (int) $screen['order'] : 10;
                $valid[] = $screen;
            }

            usort(
                $valid,
                static function ($a, $b) {
                    return $a['order'] <=> $b['order'];
                }
            );

            return $valid;
        }

        /**
         * Register the tab and move Import / Export to the end.
         *
         * @param array $settings Settings payload.
         * @return array
         */
        public static function register_tab(array $settings): array
        {
            $settings['tabs']     = isset($settings['tabs']) && is_array($settings['tabs']) ? $settings['tabs'] : array();
            $settings['sections'] = isset($settings['sections']) && is_array($settings['sections']) ? $settings['sections'] : array();

            if (!self::has_tab($settings['tabs'], self::TAB_ID)) {
                $tab   = array('id' => self::TAB_ID, 'title' => __('Migration', 'smart-variations-images'));
                $tabs  = array();
                $added = false;

                foreach ($settings['tabs'] as $existing) {
                    // Migration sits last but before "What's new".
                    if (!$added && isset($existing['id']) && 'whats_new' === $existing['id']) {
                        $tabs[] = $tab;
                        $added  = true;
                    }

                    $tabs[] = $existing;
                }

                if (!$added) {
                    $tabs[] = $tab;
                }

                $settings['tabs'] = $tabs;
            }

            $settings['sections'][] = array(
                'section_title' => __('Migrate from other plugins', 'smart-variations-images'),
                'tab_id'        => self::TAB_ID,
                'section_id'    => 'section_migration_hub',
                'section_order' => 10,
                'fields'        => array(
                    array(
                        'id'      => 'hub',
                        'title'   => '',
                        'default' => '',
                        'type'    => 'custom',
                        'output'  => array(__CLASS__, 'render_hub'),
                    ),
                ),
            );

            // One Import / Export tab, always last.
            $settings['tabs'] = self::move_last($settings['tabs'], 'import_export');

            return $settings;
        }

        /**
         * Horizontal sub-tabs, rendered by the settings framework.
         */
        public static function render_hub(): void
        {
            $screens = self::screens();

            if (empty($screens)) {
                echo '<p>' . esc_html__('No migration source is available on this site.', 'smart-variations-images') . '</p>';

                return;
            }

            $tabs = array();

            foreach ($screens as $screen) {
                $tabs[$screen['id']] = array(
                    'label'  => $screen['label'],
                    'render' => $screen['render'],
                );
            }

            if (class_exists('WPSF_SVI_Subtabs')) {
                WPSF_SVI_Subtabs::render($tabs, array('layout' => 'horizontal', 'id' => 'svi-migration'));

                return;
            }

            // Fallback: a host without the framework helper still shows both.
            foreach ($tabs as $key => $tab) {
                echo '<h3>' . esc_html($tab['label']) . '</h3>';
                call_user_func($tab['render']);
            }
        }

        /**
         * Is a tab id already registered?
         *
         * @param array  $tabs Registered tabs.
         * @param string $id   Tab id.
         * @return bool
         */
        private static function has_tab(array $tabs, string $id): bool
        {
            foreach ($tabs as $tab) {
                if (isset($tab['id']) && $id === $tab['id']) {
                    return true;
                }
            }

            return false;
        }

        /**
         * Move a tab to the end of the strip.
         *
         * @param array  $tabs Registered tabs.
         * @param string $id   Tab id.
         * @return array
         */
        private static function move_last(array $tabs, string $id): array
        {
            $found = null;
            $rest  = array();

            foreach ($tabs as $tab) {
                if (isset($tab['id']) && $id === $tab['id']) {
                    $found = $tab;
                    continue;
                }

                $rest[] = $tab;
            }

            if (null !== $found) {
                $rest[] = $found;
            }

            return $rest;
        }
    }
}
