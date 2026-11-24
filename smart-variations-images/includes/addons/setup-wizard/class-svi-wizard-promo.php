<?php
/**
 * The SVI PRO panel at the end of step 5.
 *
 * @package Smart_Variations_Images\Addons\Setup_Wizard
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Wizard_Promo')) {
	/**
	 * Two variants of the same panel: a dated offer while the campaign runs and
	 * a plain upgrade card the rest of the year. The decision is made on every
	 * render from the site clock, so the panel changes without a plugin update.
	 */
	class SVI_Wizard_Promo
	{
		/**
		 * User meta prefix of the per-campaign dismissal.
		 */
		const DISMISS_META = 'svi_wizard_promo_dismissed_';

		/**
		 * Query argument of the dismissal link.
		 */
		const DISMISS_ARG = 'svi-wizard-promo-dismiss';

		/**
		 * Compare and upgrade links, kept here so every build shares them.
		 */
		const COMPARE_URL = 'https://smart-variations.com/?utm_source=wp-admin&utm_medium=wizard&utm_campaign=upgrade';

		/**
		 * Campaign definition.
		 *
		 * @return array
		 */
		public static function config(): array
		{
			$promo = array(
				'id'         => 'bf50-2026',
				'start'      => '2026-11-19 00:00:00 UTC',
				'end'        => '2026-12-01 04:59:59 UTC',
				'badge'      => __('Black Friday', 'smart-variations-images'),
				'title'      => __('SVI PRO is 50% off', 'smart-variations-images'),
				'price_old'  => '$69.99',
				'price_new'  => '$34.99',
				'price_note' => __('first year, before taxes', 'smart-variations-images'),
				'discount'   => '50%',
				'coupon'     => 'BF50',
				'url'        => 'https://smart-variations.com/black-friday/?utm_source=wp-admin&utm_medium=wizard&utm_campaign=bf50',
				'ends_label' => __('Offer ends November 30.', 'smart-variations-images'),
			);

			/**
			 * Filter the campaign shown on the last wizard step.
			 *
			 * @param array $promo Campaign definition.
			 */
			$promo = (array) apply_filters('svi_wizard_promo', $promo);

			return $promo;
		}

		/**
		 * Whether the campaign window is open right now.
		 *
		 * @return bool
		 */
		public static function promo_is_live(): bool
		{
			$promo = self::config();

			if (empty($promo['start']) || empty($promo['end']) || empty($promo['id'])) {
				return false;
			}

			$start = strtotime((string) $promo['start']);
			$end   = strtotime((string) $promo['end']);

			if (false === $start || false === $end) {
				return false;
			}

			$now = time();

			return $now >= $start && $now <= $end;
		}

		/**
		 * Whether this user closed the current campaign.
		 *
		 * @return bool
		 */
		public static function is_dismissed(): bool
		{
			$promo = self::config();

			if (empty($promo['id'])) {
				return false;
			}

			return '1' === (string) get_user_meta(get_current_user_id(), self::DISMISS_META . sanitize_key((string) $promo['id']), true);
		}

		/**
		 * Which variant to render: 'promo' during the campaign, 'standard'
		 * otherwise, including once the user closed it.
		 *
		 * @return string
		 */
		public static function variant(): string
		{
			return (self::promo_is_live() && !self::is_dismissed()) ? 'promo' : 'standard';
		}

		/**
		 * Whether this is the PRO build.
		 *
		 * @return bool
		 */
		public static function is_pro_build(): bool
		{
			return function_exists('svi_fs') && svi_fs()->is_premium();
		}

		/**
		 * Whether the upgrade panel is allowed. Paysites never see it; a filter
		 * can hide it everywhere else.
		 *
		 * @return bool
		 */
		public static function is_visible(): bool
		{
			// Paying stores never see an upgrade panel, whatever the build.
			$visible = !SVI_Setup_Wizard_Freemius::is_paying();

			/**
			 * Filter whether the wizard shows its SVI PRO panel.
			 *
			 * @param bool $visible Default visibility.
			 */
			return (bool) apply_filters('svi_wizard_promo_visible', $visible);
		}

		/**
		 * Handle the per-user dismissal.
		 */
		public static function handle_dismiss(): void
		{
			if (!isset($_GET[self::DISMISS_ARG])) {
				return;
			}

			$nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';

			if (!wp_verify_nonce($nonce, 'svi_wizard_promo') || !current_user_can('edit_products')) {
				return;
			}

			$promo = self::config();

			if (!empty($promo['id'])) {
				update_user_meta(get_current_user_id(), self::DISMISS_META . sanitize_key((string) $promo['id']), '1');
			}

			wp_safe_redirect(SVI_Setup_Wizard_Data::wizard_url(SVI_Setup_Wizard_State::STEPS));
			exit;
		}

		/**
		 * URL that hides the campaign for this user.
		 *
		 * @return string
		 */
		private static function dismiss_url(): string
		{
			return wp_nonce_url(
				add_query_arg(
					array(
						'page'            => SVI_Setup_Wizard_State::PAGE,
						'step'            => SVI_Setup_Wizard_State::STEPS,
						self::DISMISS_ARG => '1',
					),
					admin_url('admin.php')
				),
				'svi_wizard_promo'
			);
		}

		/**
		 * Which panel to show: 'pro', 'promo', 'standard' or '' for none.
		 *
		 * The PRO build points at what it already unlocked. The free build
		 * offers the upgrade, and only to a store that does not pay.
		 *
		 * @return string
		 */
		public static function panel(): string
		{
			$panel = '';

			if (self::is_pro_build()) {
				$panel = 'pro';
			} elseif (self::is_visible()) {
				$panel = 'promo' === self::variant() ? 'promo' : 'standard';
			}

			/**
			 * Filter which SVI PRO panel the last step shows.
			 *
			 * @param string $panel pro|promo|standard|''.
			 */
			return (string) apply_filters('svi_wizard_promo_panel', $panel);
		}

		/**
		 * Render the panel chosen by panel().
		 */
		public static function render(): void
		{
			switch (self::panel()) {
				case 'pro':
					self::render_pro_box();
					break;
				case 'promo':
					self::render_promo();
					break;
				case 'standard':
					self::render_standard();
					break;
			}
		}

		/**
		 * Bundled brand symbol, used by the promo card.
		 *
		 * @return string
		 */
		private static function symbol_url(): string
		{
			return SVI_Setup_Wizard_Assets::brand_url('simbolo-claro.svg');
		}

		/**
		 * Variant A: the dated offer.
		 */
		private static function render_promo(): void
		{
			$promo  = self::config();
			$symbol = self::symbol_url();
			?>
			<section class="svi-promo svi-promo--offer" aria-labelledby="svi-promo-title">
				<a class="svi-promo__close" href="<?php echo esc_url(self::dismiss_url()); ?>" aria-label="<?php esc_attr_e('Hide this offer', 'smart-variations-images'); ?>">&times;</a>

				<div class="svi-promo__art" aria-hidden="true">
					<span class="svi-promo__symbol"><img src="<?php echo esc_url($symbol); ?>" alt="" width="52" height="52" /></span>
				</div>

				<div class="svi-promo__body">
					<?php if (!empty($promo['badge'])) : ?>
						<span class="svi-promo__badge"><?php echo esc_html($promo['badge']); ?></span>
					<?php endif; ?>

					<h3 class="svi-promo__title" id="svi-promo-title"><?php echo esc_html($promo['title']); ?></h3>

					<p class="svi-promo__prices">
						<s class="svi-promo__price-old"><?php echo esc_html($promo['price_old']); ?></s>
						<span class="svi-promo__price-new"><?php echo esc_html($promo['price_new']); ?></span>
						<span class="svi-promo__price-note"><?php echo esc_html($promo['price_note']); ?></span>
					</p>

					<p class="svi-promo__line">
						<?php esc_html_e('Video in galleries, variation images in cart and emails, Quick View and gallery CSV import.', 'smart-variations-images'); ?>
					</p>

					<?php if (!empty($promo['coupon'])) : ?>
						<p class="svi-promo__coupon">
							<span class="svi-promo__coupon-code"><?php echo esc_html($promo['coupon']); ?></span>
							<button type="button" class="svi-promo__copy" data-svi-copy="<?php echo esc_attr($promo['coupon']); ?>" data-svi-copied="<?php esc_attr_e('Copied', 'smart-variations-images'); ?>">
								<?php esc_html_e('Copy', 'smart-variations-images'); ?>
							</button>
						</p>
					<?php endif; ?>

					<p class="svi-promo__cta">
						<a class="svi-button svi-button--primary" href="<?php echo esc_url($promo['url']); ?>" target="_blank" rel="noopener noreferrer">
							<?php
							printf(
								/* translators: %s: discount, e.g. 50%. */
								esc_html__('Get %s off', 'smart-variations-images'),
								esc_html(isset($promo['discount']) ? $promo['discount'] : '')
							);
							?>
						</a>
					</p>

					<?php if (!empty($promo['ends_label'])) : ?>
						<p class="svi-promo__ends"><?php echo esc_html($promo['ends_label']); ?></p>
					<?php endif; ?>
				</div>
			</section>
			<?php
		}

		/**
		 * Variant B: the standard upgrade card.
		 */
		private static function render_standard(): void
		{
			$upgrade = SVI_Setup_Wizard_Freemius::upgrade_url();

			if ('' === $upgrade) {
				return;
			}
			?>
			<section class="svi-promo" aria-labelledby="svi-promo-title">
				<h3 class="svi-promo__title" id="svi-promo-title"><?php esc_html_e('Want more from your galleries?', 'smart-variations-images'); ?></h3>

				<ul class="svi-promo__features">
					<li><span class="svi-promo__tick" aria-hidden="true"></span><?php esc_html_e('Video in galleries', 'smart-variations-images'); ?></li>
					<li><span class="svi-promo__tick" aria-hidden="true"></span><?php esc_html_e('Variation image in cart, emails and orders', 'smart-variations-images'); ?></li>
					<li><span class="svi-promo__tick" aria-hidden="true"></span><?php esc_html_e('Quick View', 'smart-variations-images'); ?></li>
					<li><span class="svi-promo__tick" aria-hidden="true"></span><?php esc_html_e('Gallery CSV import/export', 'smart-variations-images'); ?></li>
				</ul>

				<p class="svi-promo__cta">
					<a class="svi-button svi-button--primary" href="<?php echo esc_url($upgrade); ?>">
						<?php esc_html_e('Upgrade to SVI PRO', 'smart-variations-images'); ?>
					</a>
				</p>

				<p class="svi-promo__compare">
					<a href="<?php echo esc_url(self::COMPARE_URL); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e('Compare free and PRO', 'smart-variations-images'); ?>
					</a>
				</p>
			</section>
			<?php
		}

		/**
		 * PRO build: point at the settings this build already unlocked.
		 */
		private static function render_pro_box(): void
		{
			$links = array(
				array(
					'label' => __('Video in galleries', 'smart-variations-images'),
					'tab'   => 'video',
				),
				array(
					'label' => __('Variation image in cart and emails', 'smart-variations-images'),
					'tab'   => 'main',
				),
				array(
					'label' => __('Quick View', 'smart-variations-images'),
					'tab'   => 'main',
				),
				array(
					'label' => __('Gallery CSV import/export', 'smart-variations-images'),
					'tab'   => 'main',
				),
			);
			?>
			<section class="svi-promo" aria-labelledby="svi-promo-title">
				<h3 class="svi-promo__title" id="svi-promo-title"><?php esc_html_e('PRO features to try', 'smart-variations-images'); ?></h3>

				<ul class="svi-promo__features svi-promo__features--links">
					<?php foreach ($links as $link) : ?>
						<li>
							<span class="svi-promo__tick" aria-hidden="true"></span>
							<a href="<?php echo esc_url(SVI_Setup_Wizard_Data::settings_url() . '#tab-' . $link['tab']); ?>"><?php echo esc_html($link['label']); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
			<?php
		}
	}
}
