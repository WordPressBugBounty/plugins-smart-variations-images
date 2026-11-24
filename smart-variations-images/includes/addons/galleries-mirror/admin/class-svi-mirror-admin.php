<?php
/**
 * Hidden QA panel. No merchant-facing UI.
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('SVI_Mirror_Admin')) {
    /**
     * Prints the mirror snapshot only when explicitly requested with
     * `?svi_mirror_qa=1` (or `?svi_mirror_qa=drift`) by a shop manager.
     */
    final class SVI_Mirror_Admin
    {
        /**
         * Hook the notice.
         */
        public static function init(): void
        {
            if (!is_admin()) {
                return;
            }

            add_action('admin_notices', array(__CLASS__, 'maybe_render'));
        }

        /**
         * Render the snapshot.
         */
        public static function maybe_render(): void
        {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only diagnostic.
            if (!isset($_GET['svi_mirror_qa']) || !current_user_can('manage_woocommerce')) {
                return;
            }

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $what = sanitize_key(wp_unslash((string) $_GET['svi_mirror_qa']));
            $data = array('snapshot' => SVI_Mirror_Status::snapshot());

            if ('drift' === $what) {
                $data['drift'] = SVI_Mirror_Maintenance::drift_sample(25);
            }

            echo '<div class="notice notice-info"><p><strong>'
                . esc_html__('SVI galleries mirror (diagnostic)', 'smart-variations-images')
                . '</strong></p><pre style="max-height:340px;overflow:auto;">'
                . esc_html((string) wp_json_encode($data, JSON_PRETTY_PRINT))
                . '</pre></div>';
        }
    }
}
