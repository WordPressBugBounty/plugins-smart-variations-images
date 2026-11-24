<?php
/**
 * One-time notice for installs that started with swatches on.
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

defined('WPINC') || die;

/**
 * Points a freshly activated site at the swatches settings.
 *
 * The activator seeds the swatch defaults and stores the flag this class
 * reads, so only a fresh install ever sees the notice. Dismissal is kept per
 * user, and the stored settings are never modified here.
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */
class SVI_Swatches_Notice
{
    /**
     * Option holding the version that enabled the notice.
     */
    const FLAG = 'woosvi_swatches_notice';

    /**
     * User meta flag set once the notice is dismissed.
     */
    const DISMISS_META = 'woosvi_swatches_notice_dismissed';

    /**
     * Query argument used by the dismiss link.
     */
    const DISMISS_ARG = 'woosvi_swatches_notice_dismiss';

    /**
     * Nonce action and field name of the dismiss link.
     */
    const NONCE = 'woosvi_swatches_notice_dismiss';

    /**
     * Settings page that hosts the Swatches tab.
     */
    const SETTINGS_PAGE = 'admin.php?page=woosvi-options-settings';

    /**
     * Register the admin hooks.
     */
    public function run()
    {
        add_action('admin_init', array($this, 'dismiss'));
        add_action('admin_notices', array($this, 'render'));
    }

    /**
     * Persist the dismissal for the current user and reload the same screen.
     */
    public function dismiss()
    {
        if (!isset($_GET[self::DISMISS_ARG]) || !current_user_can('edit_products')) {
            return;
        }

        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

        if (!wp_verify_nonce($nonce, self::NONCE)) {
            return;
        }

        update_user_meta(get_current_user_id(), self::DISMISS_META, 'yes');

        $redirect = remove_query_arg(array(self::DISMISS_ARG, '_wpnonce'));
        wp_safe_redirect($redirect ? $redirect : admin_url());
        exit;
    }

    /**
     * Print the notice.
     */
    public function render()
    {
        if (!$this->should_render()) {
            return;
        }

        $dismiss_url = add_query_arg(
            array(
                self::DISMISS_ARG => '1',
                '_wpnonce'        => wp_create_nonce(self::NONCE),
            )
        );

        $settings_url = admin_url(self::SETTINGS_PAGE) . '#tab-swatches';
        ?>
        <div class="notice notice-info is-dismissible svi-swatches-notice">
            <p>
                <?php esc_html_e('Your variation dropdowns now show as swatches.', 'smart-variations-images'); ?>
                <a href="<?php echo esc_url($settings_url); ?>"><?php esc_html_e('Set colors and images per attribute here →', 'smart-variations-images'); ?></a>
            </p>
            <button type="button" class="notice-dismiss" data-svi-dismiss="<?php echo esc_url($dismiss_url); ?>">
                <span class="screen-reader-text"><?php esc_html_e('Dismiss this notice.', 'smart-variations-images'); ?></span>
            </button>
        </div>
        <script>
            document.querySelectorAll('.svi-swatches-notice .notice-dismiss').forEach(function (button) {
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    window.location.href = button.getAttribute('data-svi-dismiss');
                });
            });
        </script>
        <?php
    }

    /**
     * Whether the current user and screen allow the notice.
     *
     * @return bool
     */
    private function should_render()
    {
        if ('' === (string) get_option(self::FLAG, '')) {
            return false;
        }

        if (!current_user_can('edit_products')) {
            return false;
        }

        if ('' !== (string) get_user_meta(get_current_user_id(), self::DISMISS_META, true)) {
            return false;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        // The notice links to this screen; repeating it there only adds noise.
        if ($screen instanceof WP_Screen && false !== strpos((string) $screen->id, 'woosvi-options-settings')) {
            return false;
        }

        return true;
    }
}
