<?php
/**
 * "What's new" tab in the SVI settings.
 *
 * @package Smart_Variations_Images\Addons\Whats_New
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Whats_New_Settings')) {
	/**
	 * Adds the tab that lists the release highlights.
	 */
	class SVI_Whats_New_Settings
	{
		/**
		 * Tab id, also used by links (#tab-whats_new).
		 */
		const TAB_ID = SVI_Whats_New_Tracker::TAB_ID;

		/**
		 * Section id.
		 */
		const SECTION_ID = 'section_whats_new';

		/**
		 * Register the hooks.
		 */
		public static function init(): void
		{
			add_filter('wpsfsvi_register_settings_woosvi_options', [__CLASS__, 'register_tab'], 30);
			add_filter('wpsfsvi_register_settings_clean_woosvi_options', [__CLASS__, 'register_tab'], 30);
			add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue']);
		}

		/**
		 * Add the tab and its single section.
		 *
		 * The framework runs this filter twice (once for the settings and once
		 * for the cleaned copy), so the tab is only added when missing.
		 *
		 * @param array $settings Settings payload.
		 * @return array
		 */
		public static function register_tab($settings): array
		{
			if (!is_array($settings)) {
				return $settings;
			}

			$settings['tabs']     = isset($settings['tabs']) && is_array($settings['tabs']) ? $settings['tabs'] : array();
			$settings['sections'] = isset($settings['sections']) && is_array($settings['sections']) ? $settings['sections'] : array();

			foreach ($settings['tabs'] as $tab) {
				if (isset($tab['id']) && self::TAB_ID === $tab['id']) {
					return $settings;
				}
			}

			$settings['tabs'][] = array(
				'id'    => self::TAB_ID,
				'title' => __("What's new", 'smart-variations-images'),
			);

			$settings['sections'][] = array(
				'section_title'       => '',
				'tab_id'              => self::TAB_ID,
				'section_id'          => self::SECTION_ID,
				'section_order'       => 10,
				'section_description' => self::panel_html(),
				'fields'              => array(),
			);

			return $settings;
		}

		/**
		 * Highlights of the running version.
		 *
		 * Bundled images only: nothing is fetched from the network.
		 *
		 * @return array{version:string,lead:string,items:array<int,array{title:string,text:string,image:string,alt:string}>}
		 */
		public static function release(): array
		{
			$releases = array(
				'5.3.13' => array(
					'lead'  => __('Search engines and product feeds can now read the SVI galleries: one ProductGroup per variable product, with one variant per variation and the images of its own colour.', 'smart-variations-images'),
					'items' => array(
						array(
							'title' => __('Variants with their own images', 'smart-variations-images'),
							'text'  => __('Variable products are described as a ProductGroup with variesBy and one Product per variation. Each variant lists the images of its SVI gallery and links to the URL that preselects it. The setting pauses itself when Yoast WooCommerce SEO or Rank Math already prints variants.', 'smart-variations-images'),
							'image' => 'highlight-variant-schema.svg',
							'alt'   => __('A product group with one entry per colour.', 'smart-variations-images'),
						),
					),
				),
				'5.3.12' => array(
					'lead'  => __('WooCommerce can now keep a gallery on each variation. SVI reads those galleries, turns equal image sets into one gallery per colour, and can write its own galleries back so the rest of WooCommerce sees them.', 'smart-variations-images'),
					'items' => array(
						array(
							'title' => __('Your WooCommerce galleries, in SVI', 'smart-variations-images'),
							'text'  => __('The Migration tab lists "WooCommerce (core variation galleries)" next to the other import sources. Twelve variations that share one image per colour become three SVI galleries, with a backup per product and one undo action per run.', 'smart-variations-images'),
							'image' => 'highlight-core-bridge.svg',
							'alt'   => __('A WooCommerce variation gallery imported into three SVI colour galleries.', 'smart-variations-images'),
						),
					),
				),
				'5.3.9' => array(
					'lead'  => __('A calm, guided start: one wizard for swatches and galleries, one notice per update, and nothing that follows you around the admin.', 'smart-variations-images'),
					'items' => array(
						array(
							'title' => __('A setup wizard that fits the plugin', 'smart-variations-images'),
							'text'  => __('Five full-screen steps: welcome, swatches with a live preview, gallery layout with thumbnails and lens or lightbox, your first products, and the finish. It reads your current settings first and only saves the step you complete.', 'smart-variations-images'),
							'image' => 'highlight-wizard.svg',
							'alt'   => __('The setup wizard with its step indicator and swatch preview.', 'smart-variations-images'),
						),
						array(
							'title' => __('One notice, once', 'smart-variations-images'),
							'text'  => __('After updating, the Plugins screen, the Dashboard and the SVI settings mention the wizard once. Dismiss it, or open the wizard, and it never comes back. Fresh installs go straight to the wizard instead.', 'smart-variations-images'),
							'image' => 'highlight-notice.svg',
							'alt'   => __('The one-time setup wizard notice with its action and dismiss links.', 'smart-variations-images'),
						),
						array(
							'title' => __('Reachable whenever you need it', 'smart-variations-images'),
							'text'  => __('Run it again from the Support tab at any time. Nothing you set before is cleared, and every step can be skipped.', 'smart-variations-images'),
							'image' => 'highlight-support.svg',
							'alt'   => __('The Run the setup wizard button on the Support tab.', 'smart-variations-images'),
						),
					),
				),
			);

			$version  = SVI_Whats_New_Tracker::current_version();
			$selected = '';

			foreach (array_keys($releases) as $known) {
				if (!version_compare($version, (string) $known, '>=')) {
					continue;
				}

				// Keep the newest release that is not newer than the running version.
				if ('' === $selected || version_compare((string) $known, $selected, '>')) {
					$selected = (string) $known;
				}
			}

			if ('' === $selected) {
				$selected = (string) key($releases);
			}

			return array(
				'version' => $selected,
				'lead'    => isset($releases[$selected]['lead']) ? (string) $releases[$selected]['lead'] : '',
				'items'   => isset($releases[$selected]['items']) ? $releases[$selected]['items'] : array(),
			);
		}

		/**
		 * URL of the bundled image.
		 *
		 * @param string $file File name.
		 * @return string
		 */
		private static function image_url(string $file): string
		{
			$base = defined('SMART_SVI_DIR_URL')
				? SMART_SVI_DIR_URL
				: plugin_dir_url(dirname(dirname(dirname(__FILE__))));

			return $base . 'includes/addons/whats-new/assets/images/' . $file;
		}

		/**
		 * Full changelog link.
		 *
		 * The public changelog lives on WordPress.org; a filter lets the site
		 * owner point it at smart-variations.com once that page exists.
		 *
		 * @return string
		 */
		public static function read_more_url(): string
		{
			return (string) apply_filters(
				'svi_whats_new_read_more_url',
				'https://wordpress.org/plugins/smart-variations-images/#developers'
			);
		}

		/**
		 * Whether this is the free build.
		 *
		 * Free build only: a licensed PRO install does not need the upgrade box,
		 * and a licensed install of the premium package must not see it either.
		 *
		 * @return bool
		 */
		public static function is_free_build(): bool
		{
			$free = true;

			if (function_exists('svi_fs') && is_object(svi_fs())) {
				$free = !svi_fs()->is_premium();
			}

			return (bool) apply_filters('svi_whats_new_is_free', $free);
		}

		/**
		 * URL of the setup wizard, or '' when the addon is unavailable.
		 *
		 * @return string
		 */
		private static function wizard_url(): string
		{
			if (class_exists('SVI_Setup_Wizard_Data')) {
				return SVI_Setup_Wizard_Data::wizard_url(1);
			}

			return (string) apply_filters('svi_setup_wizard_url', '');
		}

		/**
		 * URL of the bundled brand logo.
		 *
		 * @return string
		 */
		private static function logo_url(): string
		{
			$base = defined('SMART_SVI_DIR_URL')
				? SMART_SVI_DIR_URL
				: plugin_dir_url(dirname(dirname(dirname(__FILE__))));

			return $base . 'admin/images/brand/logo-header-light-swatches.svg';
		}

		/**
		 * Markup of the tab.
		 *
		 * @return string
		 */
		public static function panel_html(): string
		{
			$release = self::release();
			$wizard  = self::wizard_url();

			$html  = '<div class="svi-whats-new">';
			$html .= '<section class="svi-whats-new__hero">';
			$html .= '<img class="svi-whats-new__logo" src="' . esc_url(self::logo_url()) . '" alt="' . esc_attr__('Smart Variations Images & Swatches', 'smart-variations-images') . '" width="190" height="38" />';

			if ('' !== $release['version']) {
				$html .= '<p class="svi-whats-new__version">'
					. '<span class="svi-whats-new__version-label">' . esc_html__('Version', 'smart-variations-images') . '</span> '
					. '<span class="svi-whats-new__version-number">' . esc_html($release['version']) . '</span>'
					. '</p>';
			}

			$html .= '<h2 class="svi-whats-new__title">'
				. esc_html(
					'' !== $release['version']
						/* translators: %s: plugin version. */
						? sprintf(__("What's new in %s", 'smart-variations-images'), $release['version'])
						: __("What's new", 'smart-variations-images')
				)
				. '</h2>';

			if ('' !== $release['lead']) {
				$html .= '<p class="svi-whats-new__lead">' . esc_html($release['lead']) . '</p>';
			}

			$html .= '<p class="svi-whats-new__actions">';

			if ('' !== $wizard) {
				$html .= '<a class="svi-whats-new__button" href="' . esc_url($wizard) . '">'
					. esc_html__('Run the setup wizard', 'smart-variations-images')
					. '</a>';
			}

			$html .= '<a class="svi-whats-new__link" href="' . esc_url(self::read_more_url()) . '" target="_blank" rel="noopener">'
				. esc_html__('Read the full changelog', 'smart-variations-images')
				. '</a>';
			$html .= '</p>';
			$html .= '</section>';

			if (!empty($release['items'])) {
				$html .= '<ul class="svi-whats-new__list">';

				foreach ($release['items'] as $item) {
					$html .= '<li class="svi-whats-new__item">'
						. '<span class="svi-whats-new__media"><img src="' . esc_url(self::image_url($item['image'])) . '" alt="' . esc_attr($item['alt']) . '" width="200" height="120" loading="lazy" decoding="async" /></span>'
						. '<span class="svi-whats-new__text">'
						. '<strong class="svi-whats-new__item-title">' . esc_html($item['title']) . '</strong>'
						. '<span class="svi-whats-new__item-text">' . esc_html($item['text']) . '</span>'
						. '</span></li>';
				}

				$html .= '</ul>';
			}

			if (self::is_free_build()) {
				$html .= self::pro_box();
			}

			$html .= '</div>';

			return $html;
		}

		/**
		 * "What SVI PRO adds" box for the free build.
		 *
		 * @return string
		 */
		private static function pro_box(): string
		{
			$upgrade = function_exists('svi_upgrade_url') ? (string) svi_upgrade_url() : 'https://www.smart-variations.com/';

			$html  = '<div class="svi-whats-new__pro">';
			$html .= '<h3>' . esc_html__('What SVI PRO adds', 'smart-variations-images') . '</h3>';
			$html .= '<ul>';
			$html .= '<li>' . esc_html__('The whole gallery under the variations, in columns.', 'smart-variations-images') . '</li>';
			$html .= '<li>' . esc_html__('Video in the gallery, and the variation image in the cart, emails and orders.', 'smart-variations-images') . '</li>';
			$html .= '<li>' . esc_html__('Sticky product summary and finer slider, lightbox and lens controls.', 'smart-variations-images') . '</li>';
			$html .= '</ul>';
			$html .= '<p><a class="button button-primary" href="' . esc_url($upgrade) . '" target="_blank" rel="noopener">'
				. esc_html__('See SVI PRO', 'smart-variations-images')
				. '</a></p>';
			$html .= '</div>';

			return $html;
		}

		/**
		 * Load the tab stylesheet on the SVI settings screen only.
		 */
		public static function enqueue(): void
		{
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading the current admin page.
			$page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';

			if (SVI_Whats_New_Tracker::SETTINGS_PAGE !== $page) {
				return;
			}

			$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
			$base   = defined('SMART_SVI_DIR_URL')
				? SMART_SVI_DIR_URL
				: plugin_dir_url(dirname(dirname(dirname(__FILE__))));

			wp_enqueue_style(
				'svi-whats-new',
				$base . 'includes/addons/whats-new/assets/css/whats-new' . $suffix . '.css',
				array(),
				defined('SMART_VARIATIONS_IMAGES_VERSION') ? SMART_VARIATIONS_IMAGES_VERSION : '1'
			);
		}
	}
}
