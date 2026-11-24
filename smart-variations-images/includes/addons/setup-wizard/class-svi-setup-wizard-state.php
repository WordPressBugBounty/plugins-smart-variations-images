<?php
/**
 * When the wizard appears: the first-run redirect and the one-time notice.
 *
 * @package Smart_Variations_Images\Addons\Setup_Wizard
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Setup_Wizard_State')) {
	/**
	 * Keeps the "wizard pending" flag and decides when the wizard is announced.
	 *
	 * The flag is site-wide and is cleared in exactly three places: when the
	 * wizard is opened, when it is completed, or when the notice is dismissed.
	 * A redirect skipped because Freemius is still in activation mode does not
	 * clear it.
	 */
	class SVI_Setup_Wizard_State
	{
		/**
		 * Page slug.
		 */
		const PAGE = 'svi-setup-wizard';

		/**
		 * Number of steps.
		 */
		const STEPS = 5;

		/**
		 * Option holding the pending flag: '' (nothing), '1' (pending) or
		 * 'dismissed'.
		 */
		const PENDING_OPTION = 'woosvi_wizard_pending';

		/**
		 * Transient carrying the user to redirect after activation.
		 */
		const REDIRECT_FLAG = 'woosvi_activation_redirect';

		/**
		 * User meta set once the first-run redirect has happened.
		 */
		const REDIRECT_META = 'woosvi_first_run_redirected';

		/**
		 * Query argument of the dismiss link.
		 */
		const DISMISS_ARG = 'svi-wizard-notice-dismiss';

		/**
		 * Nonce action of the dismiss link.
		 */
		const NONCE = 'svi_wizard_pending';

		/**
		 * Version that introduced the wizard.
		 */
		const INTRODUCED_IN = '5.3.9';

		/**
		 * Register the hooks.
		 */
		public static function init(): void
		{
			add_action('svi_version_changed', [__CLASS__, 'on_version_change'], 10, 2);
			add_action('admin_init', [__CLASS__, 'handle_dismiss'], 6);
			add_action('admin_init', [__CLASS__, 'mark_opened'], 7);
			add_action('admin_init', [__CLASS__, 'maybe_redirect'], 20);
			add_action('admin_notices', [__CLASS__, 'render_notice']);
			add_action('network_admin_notices', [__CLASS__, 'render_notice']);
			add_action('admin_print_footer_scripts', [__CLASS__, 'persist_dismiss_script']);
			add_filter('svi_first_run_url', [__CLASS__, 'first_run_url']);
		}

		/**
		 * Current plugin version.
		 *
		 * @return string
		 */
		public static function current_version(): string
		{
			return defined('SMART_VARIATIONS_IMAGES_VERSION') ? (string) SMART_VARIATIONS_IMAGES_VERSION : '';
		}

		/**
		 * Pending flag value.
		 *
		 * @return string
		 */
		public static function pending(): string
		{
			return (string) get_option(self::PENDING_OPTION, '');
		}

		/**
		 * Whether the wizard still has to be announced or opened.
		 *
		 * @return bool
		 */
		public static function is_pending(): bool
		{
			return '1' === self::pending();
		}

		/**
		 * Whether this site has never saved an SVI setting.
		 *
		 * Reads the option table directly: filters fill missing swatch fields
		 * with defaults, which would make an untouched site look configured.
		 *
		 * @return bool
		 */
		public static function is_fresh_install(): bool
		{
			global $wpdb;

			$names        = array('woosvi_options', 'woosvi_options_settings', 'woosvi_options_settings_imported');
			$placeholders = implode(', ', array_fill(0, count($names), '%s'));
			$query        = "SELECT option_value FROM {$wpdb->options} WHERE option_name IN ({$placeholders})";

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Placeholders are built locally; filters would hide what is stored.
			$stored = $wpdb->get_col($wpdb->prepare($query, ...$names));

			foreach ((array) $stored as $raw) {
				if (!empty(maybe_unserialize($raw))) {
					return false;
				}
			}

			return true;
		}

		/**
		 * Arm the notice when the site updates to the release that added the
		 * wizard. Fired by the What's new tracker, which owns version tracking.
		 *
		 * @param string $current  Version now running.
		 * @param string $previous Version seen before, '' on the first run.
		 */
		public static function on_version_change($current, $previous): void
		{
			$current  = (string) $current;
			$previous = (string) $previous;

			if (self::INTRODUCED_IN !== $current) {
				return;
			}

			// The release that brought the wizard never redirects an existing
			// store away from its dashboard; it offers the wizard instead.
			if ('' !== $previous && version_compare($current, $previous, '<=')) {
				return;
			}

			if ('' === $previous && self::is_fresh_install()) {
				return;
			}

			self::arm();
		}

		/**
		 * Queue the notice once per site. A dismissed notice is never queued
		 * again, so a store sees it at most once.
		 */
		private static function arm(): void
		{
			if (self::INTRODUCED_IN !== self::current_version()) {
				return;
			}

			if ('' !== self::pending()) {
				return;
			}

			update_option(self::PENDING_OPTION, '1', false);
		}

		/**
		 * Whether this request must never show notices or redirect.
		 *
		 * @return bool
		 */
		public static function is_restricted_request(): bool
		{
			if (wp_doing_ajax() || wp_doing_cron()) {
				return true;
			}

			if (defined('REST_REQUEST') && REST_REQUEST) {
				return true;
			}

			if (defined('WP_CLI') && WP_CLI) {
				return true;
			}

			return false;
		}

		/**
		 * Screens where the notice may appear: the Plugins list, the Dashboard
		 * and the SVI settings pages. Nothing else, ever.
		 *
		 * @return bool
		 */
		private static function is_allowed_screen(): bool
		{
			$screen = function_exists('get_current_screen') ? get_current_screen() : null;

			if (!$screen instanceof WP_Screen) {
				return false;
			}

			return in_array(
				$screen->id,
				array(
					'plugins',
					'plugins-network',
					'dashboard',
					'woocommerce_page_' . SVI_Setup_Wizard_Data::SETTINGS_PAGE,
					'toplevel_page_' . SVI_Setup_Wizard_Data::SETTINGS_PAGE,
				),
				true
			);
		}

		/**
		 * Whether the notice should be printed for this user and screen.
		 *
		 * @return bool
		 */
		public static function should_notify(): bool
		{
			if (!self::is_pending()) {
				return false;
			}

			if (!current_user_can('edit_products') || self::is_restricted_request() || !self::is_allowed_screen()) {
				return false;
			}

			// The wizard page itself never shows admin notices.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading which page is open.
			if (isset($_GET['page']) && self::PAGE === sanitize_text_field(wp_unslash($_GET['page']))) {
				return false;
			}

			return true;
		}

		/**
		 * Print the setup-wizard notice.
		 */
		public static function render_notice(): void
		{
			if (!self::should_notify()) {
				return;
			}

			$dismiss_url = wp_nonce_url(
				add_query_arg(array(self::DISMISS_ARG => '1')),
				self::NONCE
			);
			?>
			<div class="notice notice-info is-dismissible svi-wizard-notice" data-svi-dismiss-url="<?php echo esc_url($dismiss_url); ?>">
				<p>
					<strong><?php esc_html_e('New in Smart Variations Images:', 'smart-variations-images'); ?></strong>
					<?php esc_html_e('a 2-minute setup wizard for swatches and galleries.', 'smart-variations-images'); ?>
				</p>
				<p>
					<a class="button button-primary" href="<?php echo esc_url(SVI_Setup_Wizard_Data::wizard_url(1)); ?>">
						<?php esc_html_e('Run setup wizard', 'smart-variations-images'); ?>
					</a>
					<a class="svi-wizard-notice__dismiss" href="<?php echo esc_url($dismiss_url); ?>" style="margin-inline-start:12px;">
						<?php esc_html_e('Dismiss', 'smart-variations-images'); ?>
					</a>
				</p>
			</div>
			<?php
		}

		/**
		 * Make WordPress' own dismiss button stick.
		 *
		 * The core button only hides the notice until the next page load, so it
		 * follows the same link the explicit Dismiss button uses.
		 */
		public static function persist_dismiss_script(): void
		{
			if (!self::should_notify()) {
				return;
			}
			?>
			<script>
				document.addEventListener( 'click', function ( event ) {
					var notice = event.target.closest ? event.target.closest( '.svi-wizard-notice' ) : null;

					if ( ! notice || ! event.target.classList.contains( 'notice-dismiss' ) ) {
						return;
					}

					var url = notice.getAttribute( 'data-svi-dismiss-url' );

					if ( url ) {
						window.location.href = url;
					}
				} );
			</script>
			<?php
		}

		/**
		 * Dismiss the notice for the whole site.
		 */
		public static function handle_dismiss(): void
		{
			if (!isset($_GET[self::DISMISS_ARG]) || !current_user_can('edit_products')) {
				return;
			}

			$nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

			if (!wp_verify_nonce($nonce, self::NONCE)) {
				return;
			}

			self::clear('dismissed');

			$redirect = remove_query_arg(array(self::DISMISS_ARG, '_wpnonce'));
			wp_safe_redirect($redirect ? $redirect : admin_url());
			exit;
		}

		/**
		 * Opening the wizard counts as having seen the notice.
		 */
		public static function mark_opened(): void
		{
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading which page was opened.
			$page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';

			if (self::PAGE !== $page || !current_user_can('edit_products')) {
				return;
			}

			if (self::is_pending()) {
				self::clear('opened');
			}
		}

		/**
		 * Clear the pending flag. Called when the wizard is opened, finished or
		 * dismissed, never when a redirect was merely postponed.
		 *
		 * @param string $reason Why it was cleared, kept for debugging.
		 */
		public static function clear(string $reason = 'opened'): void
		{
			if ('' === self::pending()) {
				return;
			}

			update_option(self::PENDING_OPTION, 'dismissed', false);

			/**
			 * Fires when the wizard stops being pending.
			 *
			 * @param string $reason opened|finished|dismissed.
			 */
			do_action('svi_setup_wizard_cleared', $reason);
		}

		/**
		 * Run once, on the request that follows a single fresh activation.
		 */
		public static function maybe_redirect(): void
		{
			$flag = get_transient(self::REDIRECT_FLAG);

			if (false === $flag) {
				return;
			}

			// Whoever activated the plugin is the only one redirected, and only once.
			$user_id = (int) $flag;

			if ($user_id <= 0 || $user_id !== get_current_user_id()) {
				return;
			}

			if (self::is_restricted_request() || !current_user_can('edit_products')) {
				return;
			}

			// Freemius owns the next screen while it is still asking for a
			// licence or an opt-in. The flag stays for after their flow.
			if (SVI_Setup_Wizard_Freemius::is_activation_mode()) {
				delete_transient(self::REDIRECT_FLAG);

				return;
			}

			if ('yes' === (string) get_user_meta($user_id, self::REDIRECT_META, true)) {
				return;
			}

			delete_transient(self::REDIRECT_FLAG);
			update_user_meta($user_id, self::REDIRECT_META, 'yes');

			wp_safe_redirect(self::first_run_url(''));
			exit;
		}

		/**
		 * Where the first-run redirect goes.
		 *
		 * @param string $url Fallback URL.
		 * @return string
		 */
		public static function first_run_url($url = ''): string
		{
			return SVI_Setup_Wizard_Data::wizard_url(1);
		}

		/**
		 * Activation bookkeeping.
		 *
		 * A fresh install gets the first-run wizard; an existing install gets
		 * the notice on its next admin request.
		 *
		 * @param bool $network_wide Activated for the whole network.
		 * @param bool $fresh        No SVI setting was ever saved on this site.
		 */
		public static function on_activate(bool $network_wide, bool $fresh): void
		{
			$current = self::current_version();

			if ('' === $current) {
				return;
			}

			if (!$fresh) {
				// Existing installs never redirect; they get the notice once.
				self::arm_existing();

				return;
			}

			if (self::INTRODUCED_IN === $current && '' === self::pending()) {
				update_option(self::PENDING_OPTION, '1', false);
			}

			if ($network_wide || is_network_admin()) {
				return;
			}

			if (defined('WP_CLI') && WP_CLI) {
				return;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading WordPress's own bulk-activation flag.
			if (!empty($_REQUEST['activate-multi'])) {
				return;
			}

			if (!function_exists('is_plugin_active') || !is_plugin_active('woocommerce/woocommerce.php')) {
				return;
			}

			// Only the activating user, and only within the next few minutes.
			set_transient(self::REDIRECT_FLAG, get_current_user_id(), 5 * MINUTE_IN_SECONDS);
		}

		/**
		 * Arm the notice for an install that already has settings.
		 */
		private static function arm_existing(): void
		{
			if ('' !== self::pending()) {
				return;
			}

			update_option(self::PENDING_OPTION, '1', false);
		}
	}
}
