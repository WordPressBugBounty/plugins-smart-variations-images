<?php
/**
 * Markup of the five setup steps.
 *
 * @package Smart_Variations_Images\Addons\Setup_Wizard
 */

if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('SVI_Setup_Wizard_Steps')) {
	/**
	 * Renders the step indicator and one panel per step. Everything it shows is
	 * read from SVI_Setup_Wizard_Data, so the fields always start from what the
	 * store already saved.
	 */
	class SVI_Setup_Wizard_Steps
	{
		/**
		 * Step names used by the indicator.
		 *
		 * @return string[]
		 */
		public static function labels(): array
		{
			return array(
				1 => __('Welcome', 'smart-variations-images'),
				2 => __('Swatches', 'smart-variations-images'),
				3 => __('Gallery', 'smart-variations-images'),
				4 => __('First gallery', 'smart-variations-images'),
				5 => __('Done', 'smart-variations-images'),
			);
		}

		/**
		 * Numbered dots with the step names.
		 *
		 * @param int $current Current step.
		 */
		public static function render_indicator(int $current): void
		{
			echo '<ol class="svi-wizard__steps" aria-label="' . esc_attr__('Setup steps', 'smart-variations-images') . '">';

			foreach (self::labels() as $step => $label) {
				$classes = array('svi-wizard__step-item');

				if ($step === $current) {
					$classes[] = 'is-current';
				} elseif ($step < $current) {
					$classes[] = 'is-done';
				}

				echo '<li class="' . esc_attr(implode(' ', $classes)) . '"' . ($step === $current ? ' aria-current="step"' : '') . '>';
				echo '<span class="svi-wizard__dot" aria-hidden="true">' . ($step < $current ? '&#10003;' : esc_html((string) $step)) . '</span>';
				echo '<span class="svi-wizard__step-name">' . esc_html($label) . '</span>';
				echo '</li>';
			}

			echo '</ol>';
		}

		/**
		 * Render every step panel; only the current one is visible.
		 *
		 * @param int $current Current step.
		 */
		public static function render(int $current): void
		{
			$renderers = array(
				1 => 'welcome',
				2 => 'swatches',
				3 => 'gallery',
				4 => 'first_gallery',
				5 => 'done',
			);

			foreach ($renderers as $step => $method) {
				echo '<section class="svi-step" data-svi-step="' . esc_attr((string) $step) . '"' . ($step === $current ? '' : ' hidden') . '>';
				self::$method();
				echo '</section>';
			}
		}

		/**
		 * Green check or grey info icon, drawn inline so nothing is loaded.
		 *
		 * @param bool $ok Whether the row passed the check.
		 */
		private static function icon(bool $ok): void
		{
			if ($ok) {
				echo '<span class="svi-detected__icon" aria-hidden="true">'
					. '<svg viewBox="0 0 20 20" width="20" height="20" focusable="false"><circle cx="10" cy="10" r="10" fill="#10b981"/><path d="M5.6 10.3l2.6 2.6 5.9-5.9" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>'
					. '</span>';

				return;
			}

			echo '<span class="svi-detected__icon" aria-hidden="true">'
				. '<svg viewBox="0 0 20 20" width="20" height="20" focusable="false"><circle cx="10" cy="10" r="9" fill="none" stroke="#94a3b8" stroke-width="2"/><path d="M10 9v5" stroke="#94a3b8" stroke-width="2" stroke-linecap="round"/><circle cx="10" cy="5.6" r="1.2" fill="#94a3b8"/></svg>'
				. '</span>';
		}

		/**
		 * Action row: Back, Skip and the step's primary button.
		 *
		 * @param int    $step    Step number.
		 * @param string $primary Label of the primary button.
		 * @param bool   $skip    Print the Skip link.
		 */
		private static function actions(int $step, string $primary, bool $skip = true): void
		{
			echo '<div class="svi-step__actions">';

			if ($step > 1) {
				echo '<button type="button" class="svi-button svi-button--secondary svi-step__back" data-svi-back>'
					. esc_html__('Back', 'smart-variations-images')
					. '</button>';
			}

			echo '<span class="svi-step__spacer"></span>';

			if ($skip) {
				echo '<button type="button" class="svi-step__skip" data-svi-skip>'
					. esc_html__('Skip', 'smart-variations-images')
					. '</button>';
			}

			echo '<button type="submit" name="svi_wizard_next" value="1" class="svi-button svi-button--primary svi-step__next" data-svi-next>'
				. esc_html($primary)
				. '</button>';

			echo '</div>';
		}

		/**
		 * Step 1: welcome and what was detected.
		 */
		private static function welcome(): void
		{
			$woo    = SVI_Setup_Wizard_Data::woocommerce_version();
			$count  = SVI_Setup_Wizard_Data::variable_product_count();
			$source = SVI_Setup_Wizard_Data::import_source_label();
			?>
			<h2 class="svi-step__title" tabindex="-1"><?php esc_html_e('Welcome to Smart Variations Images', 'smart-variations-images'); ?></h2>
			<p class="svi-step__lead">
				<?php esc_html_e('Show a whole gallery for every color, and turn dropdowns into swatches. This takes about 2 minutes, and you can change everything later.', 'smart-variations-images'); ?>
			</p>

			<ul class="svi-detected">
				<li>
					<?php self::icon('' !== $woo); ?>
					<span><?php printf(esc_html__('WooCommerce version: %s', 'smart-variations-images'), esc_html('' !== $woo ? $woo : __('not active', 'smart-variations-images'))); ?></span>
				</li>
				<li>
					<?php self::icon(true); ?>
					<span><?php printf(esc_html__('Theme: %s', 'smart-variations-images'), esc_html(SVI_Setup_Wizard_Data::theme_name())); ?></span>
				</li>
				<li>
					<?php self::icon($count > 0); ?>
					<span><?php printf(esc_html__('Variable products found: %d', 'smart-variations-images'), (int) $count); ?></span>
				</li>
				<li>
					<?php self::icon('' !== $source); ?>
					<span>
						<?php
						if ('' !== $source) {
							printf(
								/* translators: %s: name of another swatches plugin. */
								esc_html__('Another swatches plugin: Found: %s', 'smart-variations-images'),
								esc_html($source)
							);
						} else {
							esc_html_e('Another swatches plugin: None', 'smart-variations-images');
						}
						?>
					</span>
				</li>
			</ul>

			<?php self::actions(1, __("Let's start", 'smart-variations-images')); ?>
			<?php
		}

		/**
		 * Step 2: swatches and the live preview.
		 */
		private static function swatches(): void
		{
			$source  = SVI_Setup_Wizard_Data::import_source_label();
			$current = SVI_Setup_Wizard_Data::swatch_style();
			?>
			<h2 class="svi-step__title" tabindex="-1"><?php esc_html_e('How should shoppers pick options?', 'smart-variations-images'); ?></h2>

			<label class="svi-toggle">
				<input type="checkbox" name="swatches" value="1" <?php checked(SVI_Setup_Wizard_Data::swatches_enabled()); ?> />
				<span><?php esc_html_e('Enable swatches', 'smart-variations-images'); ?></span>
			</label>

			<fieldset class="svi-fieldset">
				<legend><?php esc_html_e('Default style for attributes without a type', 'smart-variations-images'); ?></legend>
				<div class="svi-cards svi-cards--style">
					<?php
					$styles = array(
						'text'  => array(__('Buttons', 'smart-variations-images'), __('Text pills, like the tags on a product.', 'smart-variations-images')),
						'radio' => array(__('Radio', 'smart-variations-images'), __('A circle and a label, the classic radio look.', 'smart-variations-images')),
						'none'  => array(__('Keep dropdown', 'smart-variations-images'), __('Leave the native select in place.', 'smart-variations-images')),
					);

					foreach ($styles as $value => $data) :
						?>
						<label class="svi-card">
							<input type="radio" name="swatch_style" value="<?php echo esc_attr($value); ?>" <?php checked($current, $value); ?> />
							<span class="svi-card__title"><?php echo esc_html($data[0]); ?></span>
							<span class="svi-card__desc"><?php echo esc_html($data[1]); ?></span>
							<span class="svi-card__check" aria-hidden="true"></span>
						</label>
					<?php endforeach; ?>
				</div>
			</fieldset>

			<p class="svi-note">
				<?php esc_html_e('Color and image attributes:', 'smart-variations-images'); ?>
				<a href="<?php echo esc_url(admin_url('edit.php?post_type=product&page=product_attributes')); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e('Set colors and images per value in Products → Attributes', 'smart-variations-images'); ?>
				</a>
			</p>

			<label class="svi-toggle">
				<input type="checkbox" name="catalog" value="1" <?php checked(SVI_Setup_Wizard_Data::catalog_swatches()); ?> />
				<span>
					<?php esc_html_e('Show swatches on shop pages', 'smart-variations-images'); ?>
					<em><?php esc_html_e('Shoppers can pick a color right on the product card.', 'smart-variations-images'); ?></em>
				</span>
			</label>

			<fieldset class="svi-fieldset">
				<legend><?php esc_html_e('Out of stock options', 'smart-variations-images'); ?></legend>
				<div class="svi-inline-radios">
					<?php
					$oos_options = array(
						'hide'  => __('Hide', 'smart-variations-images'),
						'cross' => __('Cross out', 'smart-variations-images'),
						'blur'  => __('Blur', 'smart-variations-images'),
					);

					$current_oos = SVI_Setup_Wizard_Data::oos_behavior();

					foreach ($oos_options as $value => $label) :
						?>
						<label>
							<input type="radio" name="oos" value="<?php echo esc_attr($value); ?>" <?php checked($current_oos, $value); ?> />
							<span><?php echo esc_html($label); ?></span>
						</label>
					<?php endforeach; ?>
				</div>
			</fieldset>

			<div class="svi-preview" data-svi-preview data-style="<?php echo esc_attr($current); ?>" data-oos="<?php echo esc_attr(SVI_Setup_Wizard_Data::oos_behavior()); ?>">
				<p class="svi-preview__label"><?php esc_html_e('Live preview', 'smart-variations-images'); ?></p>
				<div class="svi-preview__row">
					<span class="svi-preview__attr"><?php esc_html_e('Color', 'smart-variations-images'); ?></span>
					<span class="svi-preview__swatches">
						<span class="svi-preview__swatch is-selected" title="<?php esc_attr_e('Red', 'smart-variations-images'); ?>"><i style="--svi-swatch-color:#e11d48"></i><?php esc_html_e('Red', 'smart-variations-images'); ?></span>
						<span class="svi-preview__swatch" title="<?php esc_attr_e('Blue', 'smart-variations-images'); ?>"><i style="--svi-swatch-color:#2563eb"></i><?php esc_html_e('Blue', 'smart-variations-images'); ?></span>
						<span class="svi-preview__swatch" title="<?php esc_attr_e('Green', 'smart-variations-images'); ?>"><i style="--svi-swatch-color:#16a34a"></i><?php esc_html_e('Green', 'smart-variations-images'); ?></span>
						<span class="svi-preview__select"><?php esc_html_e('Red', 'smart-variations-images'); ?></span>
					</span>
				</div>
				<div class="svi-preview__row">
					<span class="svi-preview__attr"><?php esc_html_e('Size', 'smart-variations-images'); ?></span>
					<span class="svi-preview__swatches">
						<span class="svi-preview__swatch">S</span>
						<span class="svi-preview__swatch is-oos">M</span>
						<span class="svi-preview__swatch">L</span>
						<span class="svi-preview__select">S</span>
					</span>
				</div>
			</div>

			<?php if ('' !== $source) : ?>
				<div class="svi-callout">
					<h3 class="svi-callout__title"><?php esc_html_e('Bring your existing swatches with you', 'smart-variations-images'); ?></h3>
					<p>
						<?php
						printf(
							/* translators: %s: name of another swatches plugin. */
							esc_html__('%s left data behind. Import its colours and images first, with a dry run and an undo, then continue here.', 'smart-variations-images'),
							esc_html($source)
						);
						?>
					</p>
					<p>
						<a class="svi-button svi-button--secondary" href="<?php echo esc_url(SVI_Setup_Wizard_Data::import_url()); ?>">
							<?php esc_html_e('Import swatches', 'smart-variations-images'); ?>
						</a>
					</p>
				</div>
			<?php endif; ?>

			<?php self::actions(2, __('Save & continue', 'smart-variations-images')); ?>
			<?php
		}

		/**
		 * Step 3: gallery layout and previews.
		 */
		private static function gallery(): void
		{
			$base           = plugin_dir_url(__FILE__) . 'assets/images/';
			$current_layout = SVI_Setup_Wizard_Data::layout();
			$current_thumbs = SVI_Setup_Wizard_Data::thumbnails();
			?>
			<h2 class="svi-step__title" tabindex="-1"><?php esc_html_e('How should the product gallery look?', 'smart-variations-images'); ?></h2>

			<fieldset class="svi-fieldset">
				<legend><?php esc_html_e('Layout', 'smart-variations-images'); ?></legend>
				<div class="svi-cards svi-cards--media">
					<?php
					$layouts = array(
						'slider'  => array(__('Slider', 'smart-variations-images'), 'layout-slider.svg'),
						'stacked' => array(__('Stacked', 'smart-variations-images'), 'layout-stacked.svg'),
					);

					foreach ($layouts as $value => $data) :
						?>
						<label class="svi-card svi-card--media">
							<input type="radio" name="layout" value="<?php echo esc_attr($value); ?>" <?php checked($current_layout, $value); ?> />
							<span class="svi-card__media"><img src="<?php echo esc_url($base . $data[1]); ?>" alt="" width="180" height="120" /></span>
							<span class="svi-card__title"><?php echo esc_html($data[0]); ?></span>
							<span class="svi-card__check" aria-hidden="true"></span>
						</label>
					<?php endforeach; ?>
				</div>
			</fieldset>

			<fieldset class="svi-fieldset">
				<legend><?php esc_html_e('Thumbnails', 'smart-variations-images'); ?></legend>
				<div class="svi-cards svi-cards--media svi-cards--4">
					<?php
					$thumbs = array(
						'below'  => array(__('Below', 'smart-variations-images'), 'thumbs-below.svg'),
						'left'   => array(__('Left', 'smart-variations-images'), 'thumbs-left.svg'),
						'right'  => array(__('Right', 'smart-variations-images'), 'thumbs-right.svg'),
						'hidden' => array(__('Hidden until an option is chosen', 'smart-variations-images'), 'thumbs-hidden.svg'),
					);

					foreach ($thumbs as $value => $data) :
						?>
						<label class="svi-card svi-card--media">
							<input type="radio" name="thumbnails" value="<?php echo esc_attr($value); ?>" <?php checked($current_thumbs, $value); ?> />
							<span class="svi-card__media"><img src="<?php echo esc_url($base . $data[1]); ?>" alt="" width="180" height="120" /></span>
							<span class="svi-card__title"><?php echo esc_html($data[0]); ?></span>
							<span class="svi-card__check" aria-hidden="true"></span>
						</label>
					<?php endforeach; ?>
				</div>
			</fieldset>

			<div class="svi-toggles">
				<label class="svi-toggle">
					<input type="checkbox" name="lens" value="1" <?php checked(SVI_Setup_Wizard_Data::lens()); ?> />
					<span><?php esc_html_e('Magnifier lens', 'smart-variations-images'); ?></span>
				</label>
				<label class="svi-toggle">
					<input type="checkbox" name="lightbox" value="1" <?php checked(SVI_Setup_Wizard_Data::lightbox()); ?> />
					<span><?php esc_html_e('Lightbox', 'smart-variations-images'); ?></span>
				</label>
			</div>

			<label class="svi-toggle">
				<input type="checkbox" name="swselect" value="1" <?php checked(SVI_Setup_Wizard_Data::single_attribute_swap()); ?> />
				<span>
					<?php esc_html_e('Change gallery when only one attribute is chosen', 'smart-variations-images'); ?>
					<em><?php esc_html_e('Show the red gallery as soon as Red is picked, before size.', 'smart-variations-images'); ?></em>
				</span>
			</label>

			<p class="svi-figure">
				<img src="<?php echo esc_url($base . 'gallery-switch.gif'); ?>" alt="<?php esc_attr_e('The gallery switching colour when a swatch is picked.', 'smart-variations-images'); ?>" width="360" height="360" loading="lazy" />
			</p>

			<?php self::actions(3, __('Save & continue', 'smart-variations-images')); ?>
			<?php
		}

		/**
		 * Step 4: the first product to fill in.
		 */
		private static function first_gallery(): void
		{
			$products = SVI_Setup_Wizard_Data::variable_products(5);
			?>
			<h2 class="svi-step__title" tabindex="-1"><?php esc_html_e('Add images for each color', 'smart-variations-images'); ?></h2>
			<p class="svi-step__lead">
				<?php esc_html_e('Open a product, pick a color, and add its images once. Every size of that color uses the same gallery.', 'smart-variations-images'); ?>
			</p>

			<?php if (empty($products)) : ?>
				<div class="svi-empty">
					<p><?php esc_html_e('No variable products yet.', 'smart-variations-images'); ?></p>
					<p>
						<a class="svi-button svi-button--secondary" href="https://smart-variations.com/docs/" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e('Watch the 1-minute how-to', 'smart-variations-images'); ?>
						</a>
					</p>
				</div>
			<?php else : ?>
				<ul class="svi-products">
					<?php
					foreach ($products as $product) :
						$counts = SVI_Setup_Wizard_Data::gallery_counts($product);
						?>
						<li class="svi-product">
							<span class="svi-product__thumb"><?php echo wp_kses_post($product->get_image('thumbnail')); ?></span>
							<span class="svi-product__name"><?php echo esc_html($product->get_name()); ?></span>
							<span class="svi-product__pill">
								<?php
								printf(
									/* translators: 1: galleries created, 2: galleries expected. */
									esc_html__('%1$d of %2$d galleries', 'smart-variations-images'),
									(int) $counts['created'],
									(int) $counts['expected']
								);
								?>
							</span>
							<a class="svi-button svi-button--secondary svi-product__link" href="<?php echo esc_url(SVI_Setup_Wizard_Data::product_editor_url((int) $product->get_id())); ?>">
								<?php esc_html_e('Set up galleries', 'smart-variations-images'); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<p class="svi-note">
				<a href="https://smart-variations.com/demo/" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e('See it on the live demo', 'smart-variations-images'); ?>
				</a>
			</p>

			<?php self::actions(4, __('Save & continue', 'smart-variations-images')); ?>
			<?php
		}

		/**
		 * Step 5: done, help links and the SVI PRO panel.
		 */
		private static function done(): void
		{
			$products = SVI_Setup_Wizard_Data::variable_products(1);
			$view_url = !empty($products) ? get_permalink($products[0]->get_id()) : '';
			?>
			<div class="svi-celebrate" aria-hidden="true">
				<span class="svi-celebrate__dot svi-celebrate__dot--1"></span>
				<span class="svi-celebrate__dot svi-celebrate__dot--2"></span>
				<span class="svi-celebrate__dot svi-celebrate__dot--3"></span>
				<svg class="svi-celebrate__mark" viewBox="0 0 64 64" width="64" height="64" focusable="false">
					<circle cx="32" cy="32" r="30" fill="#ecfdf5" stroke="#d1fae5" stroke-width="2"/>
					<path d="M20 33l8 8 16-17" fill="none" stroke="#047857" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</div>

			<h2 class="svi-step__title" tabindex="-1"><?php esc_html_e('You\'re all set', 'smart-variations-images'); ?></h2>
			<p class="svi-step__lead">
				<?php esc_html_e('Your settings are saved. Swatches and galleries are live on your store.', 'smart-variations-images'); ?>
			</p>

			<p class="svi-done-actions">
				<?php if ('' !== $view_url) : ?>
					<a class="svi-button svi-button--secondary" href="<?php echo esc_url($view_url); ?>" target="_blank" rel="noopener noreferrer">
						<?php esc_html_e('View a product', 'smart-variations-images'); ?>
					</a>
				<?php endif; ?>
				<a class="svi-button svi-button--secondary" href="<?php echo esc_url(SVI_Setup_Wizard_Data::settings_url()); ?>">
					<?php esc_html_e('Go to SVI settings', 'smart-variations-images'); ?>
				</a>
			</p>

			<ul class="svi-help">
				<li><a href="https://smart-variations.com/docs/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Knowledge base', 'smart-variations-images'); ?></a></li>
				<li><a href="https://wordpress.org/support/plugin/smart-variations-images/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Support forum', 'smart-variations-images'); ?></a></li>
				<li><a href="https://wordpress.org/support/plugin/smart-variations-images/reviews/#new-post" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Rate us on WordPress.org', 'smart-variations-images'); ?></a></li>
			</ul>

			<?php SVI_Wizard_Promo::render(); ?>

			<?php self::actions(5, __('Finish', 'smart-variations-images'), false); ?>
			<?php
		}
	}
}
