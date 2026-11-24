<?php
/**
 * The standalone wizard screen.
 *
 * @package Smart_Variations_Images\Addons\Setup_Wizard
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Setup_Wizard_Page')) {
	/**
	 * Registers a hidden admin page and renders it without the admin chrome:
	 * no menu, no admin bar and no admin notices from any plugin.
	 */
	class SVI_Setup_Wizard_Page
	{
		/**
		 * Hook suffix of the wizard page.
		 *
		 * @var string
		 */
		private static string $hook = '';

		/**
		 * Register the hooks.
		 */
		public static function init(): void
		{
			add_action('admin_menu', [__CLASS__, 'register_page'], 20);
			add_filter('wpsfsvi_register_settings_woosvi_options', [__CLASS__, 'add_settings_link'], 40);
			add_filter('wpsfsvi_register_settings_clean_woosvi_options', [__CLASS__, 'add_settings_link'], 40);
			add_action('admin_print_footer_scripts', [__CLASS__, 'open_product_tab']);
		}

		/**
		 * Register a hidden page, then render it without the admin chrome.
		 *
		 * The page hangs off the SVI settings slug: WordPress needs a parent to
		 * grant access, but a parent that is itself a submenu item is never
		 * rendered, so the wizard stays out of the menu.
		 */
		public static function register_page(): void
		{
			self::$hook = (string) add_submenu_page(
				SVI_Setup_Wizard_Data::SETTINGS_PAGE,
				__('SVI setup wizard', 'smart-variations-images'),
				__('SVI setup wizard', 'smart-variations-images'),
				'edit_products',
				SVI_Setup_Wizard_State::PAGE,
				[__CLASS__, 'render']
			);

			if ('' !== self::$hook) {
				// Runs before admin-header.php prints the menu, bar and notices.
				add_action('load-' . self::$hook, [__CLASS__, 'render']);
			}
		}

		/**
		 * Current step from the query string.
		 *
		 * @return int
		 */
		public static function current_step(): int
		{
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading the step to display.
			$step = isset($_GET['step']) ? absint($_GET['step']) : 1;

			return max(1, min(SVI_Setup_Wizard_State::STEPS, $step));
		}

		/**
		 * Print the standalone screen and stop.
		 */
		public static function render(): void
		{
			if (!current_user_can('edit_products')) {
				wp_die(esc_html__('You are not allowed to run this setup.', 'smart-variations-images'));
			}

			// Nothing may echo into this document: the callbacks are removed and
			// the hooks Freemius and other plugins use are cleared as well.
			remove_all_actions('admin_notices');
			remove_all_actions('all_admin_notices');
			remove_all_actions('network_admin_notices');
			remove_all_actions('admin_footer_text');
			remove_all_actions('update_footer');

			SVI_Setup_Wizard_Assets::enqueue();

			$step  = self::current_step();
			$title = sprintf(
				/* translators: %s: plugin name. */
				__('Setup wizard — %s', 'smart-variations-images'),
				__('Smart Variations Images', 'smart-variations-images')
			);
			?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php echo esc_attr(get_bloginfo('charset')); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="robots" content="noindex, nofollow" />
	<title><?php echo esc_html($title); ?></title>
	<?php wp_print_styles(); ?>
	<?php wp_print_head_scripts(); ?>
</head>
<body class="svi-wizard-page<?php echo is_rtl() ? ' rtl' : ''; ?>">
	<main class="svi-wizard" data-svi-wizard data-step="<?php echo esc_attr((string) $step); ?>">
		<header class="svi-wizard__brand">
			<img
				class="svi-wizard__logo"
				src="<?php echo esc_url(SVI_Setup_Wizard_Assets::logo_url()); ?>"
				alt="<?php esc_attr_e('Smart Variations Images & Swatches', 'smart-variations-images'); ?>"
				width="215"
				height="43"
				onerror="this.onerror=null;this.src='<?php echo esc_url(SVI_Setup_Wizard_Assets::logo_fallback_url()); ?>';"
			/>
		</header>

		<?php SVI_Setup_Wizard_Steps::render_indicator($step); ?>

		<div class="svi-wizard__card">
			<form class="svi-wizard__form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-svi-wizard-form>
				<input type="hidden" name="action" value="<?php echo esc_attr(SVI_Setup_Wizard_Ajax::ACTION); ?>" />
				<input type="hidden" name="step" value="<?php echo esc_attr((string) $step); ?>" data-svi-step-field />
				<?php wp_nonce_field(SVI_Setup_Wizard_Ajax::NONCE); ?>

				<?php SVI_Setup_Wizard_Steps::render($step); ?>
			</form>
		</div>

		<footer class="svi-wizard__footer">
			<a class="svi-wizard__exit" href="<?php echo esc_url(admin_url('plugins.php')); ?>">
				<?php esc_html_e('Exit wizard', 'smart-variations-images'); ?>
			</a>
		</footer>
	</main>
	<?php wp_print_footer_scripts(); ?>
</body>
</html>
			<?php
			exit;
		}

		/**
		 * Add a "Run the setup wizard" row to the Support tab.
		 *
		 * @param array $settings Settings payload.
		 * @return array
		 */
		public static function add_settings_link($settings): array
		{
			if (!is_array($settings) || empty($settings['sections'])) {
				return $settings;
			}

			foreach ($settings['sections'] as $index => $section) {
				if (!isset($section['section_id']) || 'section_support' !== $section['section_id']) {
					continue;
				}

				$settings['sections'][$index]['fields'][] = array(
					'id'     => 'svi_setup_wizard_link',
					'title'  => '',
					'type'   => 'custom',
					'output' => array(__CLASS__, 'render_settings_link'),
				);

				break;
			}

			return $settings;
		}

		/**
		 * Markup of the Support tab row.
		 */
		public static function render_settings_link(): void
		{
			echo '<p><a class="button" href="' . esc_url(SVI_Setup_Wizard_Data::wizard_url(1)) . '">'
				. esc_html__('Run the setup wizard', 'smart-variations-images')
				. '</a> <span class="description">'
				. esc_html__('Five short steps: welcome, swatches, gallery, your first product and the finish.', 'smart-variations-images')
				. '</span></p>';
		}

		/**
		 * Open the SVI tab when a wizard link points at it.
		 */
		public static function open_product_tab(): void
		{
			$screen = function_exists('get_current_screen') ? get_current_screen() : null;

			if (!$screen instanceof WP_Screen || 'product' !== $screen->id) {
				return;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading the deep-link flag.
			if (!isset($_GET['svi-tab']) || 'svi_swatches' !== sanitize_key($_GET['svi-tab'])) {
				return;
			}
			?>
			<script>
				jQuery(function ($) {
					var tab = $('ul.product_data_tabs li a[href="#svi_swatches_product_data"]');

					if (tab.length) {
						tab.trigger('click');
					}
				});
			</script>
			<?php
		}
	}
}
