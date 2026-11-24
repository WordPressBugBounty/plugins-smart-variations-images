<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Bundled framework class and dynamic option hook retain their legacy public API.

require_once __DIR__ . '/class-wpsfsvi-subtabs.php';

/**
 * WordPress Settings Framework
 *
 * @author  David Rosendo
 * @link 	https://github.com/drosendo/WordPress-Settings-Framework
 * @author  Gilbert Pellegrom, James Kemp
 * @link    https://github.com/gilbitron/WordPress-Settings-Framework
 * @version 1.6.11
 * @license MIT
 */

if (!class_exists('WordPressSettingsFrameworkSVI')) {
	/**
	 * WordPressSettingsFramework class
	 */
	class WordPressSettingsFrameworkSVI
	{
		/**
		 * @access private
		 * @var array
		 */
		private $settings_wrapper;

		/**
		 * @access private
		 * @var array
		 */
		private $settings;

		/**
		 * @access private
		 * @var array
		 */
		private $tabs;

		/**
		 * Tab layout: horizontal (default) or vertical.
		 *
		 * Set with `'tabs_layout' => 'vertical'` in the settings array. The
		 * vertical layout becomes a horizontal strip below 960px, so narrow and
		 * touch admin screens keep working without a second markup path.
		 *
		 * @access private
		 * @var string
		 */
		private $tabs_layout = 'horizontal';

		/**
		 * @access private
		 * @var string
		 */
		private $option_group;

		/**
		 * Per-request cache of resolved settings, keyed by option group and shape.
		 *
		 * @access private
		 * @var array<string, array>
		 */
		private static $settings_cache = array();

		/**
		 * @access private
		 * @var array
		 */
		private $settings_page = array();

		/**
		 * @access private
		 * @var string
		 */
		private $options_path;

		/**
		 * @access private
		 * @var string
		 */
		private $options_url;

		/**
		 * @access protected
		 * @var array
		 */
		protected $setting_defaults = array(
			'id'          => 'default_field',
			'title'       => 'Default Field',
			'desc'        => '',
			'std'         => '',
			'type'        => 'text',
			'placeholder' => '',
			'choices'     => array(),
			'class'       => '',
			'subfields'   => array(),
		);

		/**
		 * WordPressSettingsFramework constructor.
		 *
		 * @param null|string $settings_file Path to a settings file, or null if you pass the option_group manually and construct your settings with a filter.
		 * @param bool|string $option_group  Option group name, usually a short slug.
		 */
		public function __construct($settings_file = null, $option_group = false)
		{
			$this->option_group = $option_group;

			if ($settings_file) {
				if (!is_file($settings_file)) {
					return;
				}

				require_once $settings_file;

				if (!$this->option_group) {
					$this->option_group = preg_replace('/[^a-z0-9]+/i', '', basename($settings_file, '.php'));
				}
			}

			if (empty($this->option_group)) {
				return;
			}

			$this->options_path = plugin_dir_path(__FILE__);
			$this->options_url  = plugin_dir_url(__FILE__);

			$this->construct_settings();

			if (is_admin()) {
				global $pagenow;

				add_action('admin_init', array($this, 'admin_init'));
				add_action('wpsfsvi_do_settings_sections_' . $this->option_group, array($this, 'do_tabless_settings_sections'), 10);

				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if (isset($_GET['page']) && sanitize_text_field(wp_unslash($_GET['page'])) === $this->settings_page['slug']) {
					if ($pagenow !== 'options-general.php') {
						add_action('admin_notices', array($this, 'admin_notices'));
					}
					add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
				}

				if ($this->has_tabs()) {
					add_action('wpsfsvi_before_settings_' . $this->option_group, array($this, 'tab_links'));

					remove_action('wpsfsvi_do_settings_sections_' . $this->option_group, array($this, 'do_tabless_settings_sections'), 10);
					add_action('wpsfsvi_do_settings_sections_' . $this->option_group, array($this, 'do_tabbed_settings_sections'), 10);
				}

				add_action('wp_ajax_wpsfsvi_export_settings', array($this, 'export_settings'));
				add_action('wp_ajax_wpsfsvi_import_settings', array($this, 'import_settings'));
			}
		}

		/**
		 * Construct Settings.
		 */
		public function construct_settings()
		{
			$this->settings_wrapper = apply_filters('wpsfsvi_register_settings_clean_' . $this->option_group, apply_filters('wpsfsvi_register_settings_' . $this->option_group, array()));

			if (!is_array($this->settings_wrapper)) {
				return new WP_Error('broke', esc_html__('wpsfsvi settings must be an array', 'smart-variations-images'));
			}

			// If "sections" is set, this settings group probably has tabs
			if (isset($this->settings_wrapper['sections'])) {
				$this->tabs     = (isset($this->settings_wrapper['tabs'])) ? $this->settings_wrapper['tabs'] : array();
				$this->settings = $this->settings_wrapper['sections'];

				$layout = isset($this->settings_wrapper['tabs_layout']) ? (string) $this->settings_wrapper['tabs_layout'] : 'horizontal';
				$layout = apply_filters('wpsfsvi_tabs_layout_' . $this->option_group, $layout);
				$this->tabs_layout = ('vertical' === strtolower($layout)) ? 'vertical' : 'horizontal';
				// If not, it's probably just an array of settings
			} else {
				$this->settings = $this->settings_wrapper;
			}

			$this->settings_page['slug'] = sprintf('%s-settings', str_replace('_', '-', $this->option_group));
		}

		/**
		 * Get the option group for this instance
		 *
		 * @return string the "option_group"
		 */
		public function get_option_group()
		{
			return $this->option_group;
		}

		/**
		 * Registers the internal WordPress settings
		 */
		public function admin_init()
		{
			register_setting($this->option_group, $this->option_group . '_settings', array($this, 'settings_validate'));
			$this->process_settings();
		}

		/**
		 * Add Settings Page
		 *
		 * @param array $args
		 */
		public function add_settings_page($args)
		{
			$defaults = array(
				'parent_slug' => false,
				'page_slug'   => '',
				'page_title'  => '',
				'menu_title'  => '',
				'capability'  => 'manage_options',
			);

			$args = wp_parse_args($args, $defaults);

			$this->settings_page['title']      = $args['page_title'];
			$this->settings_page['capability'] = $args['capability'];

			if ($args['parent_slug']) {
				add_submenu_page(
					$args['parent_slug'],
					$this->settings_page['title'],
					$args['menu_title'],
					$args['capability'],
					$this->settings_page['slug'],
					array($this, 'settings_page_content')
				);
			} else {
				add_menu_page(
					$this->settings_page['title'],
					$args['menu_title'],
					$args['capability'],
					$this->settings_page['slug'],
					array($this, 'settings_page_content'),
					apply_filters('wpsfsvi_menu_icon_url_' . $this->option_group, ''),
					apply_filters('wpsfsvi_menu_position_' . $this->option_group, null)
				);
			}
		}

		/**
		 * Settings Page Content
		 */

		public function settings_page_content()
		{
			if (!current_user_can($this->settings_page['capability'])) {
				wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'smart-variations-images'));
			}
?>
			<div class="wpsfsvi-settings wpsfsvi-settings--<?php echo esc_attr($this->option_group); ?>">
				<?php $this->settings_header(); ?>
				<div class="wpsfsvi-settings__content wpsfsvi-settings__content--<?php echo esc_attr($this->tabs_layout); ?>">
					<?php $this->settings(); ?>
				</div>
			</div>
		<?php
		}

		/**
		 * Settings Header.
		 */
		public function settings_header()
		{
		?>
			<div class="wpsfsvi-settings__header">
				<h2><?php echo wp_kses_post(apply_filters('wpsfsvi_title_' . $this->option_group, $this->settings_page['title'])); ?></h2>
				<?php do_action('wpsfsvi_after_title_' . $this->option_group); ?>
			</div>
		<?php
		}

		/**
		 * Displays any errors from the WordPress settings API
		 */
		public function admin_notices()
		{
			settings_errors();
		}

		/**
		 * Enqueue scripts and styles
		 */
		public function admin_enqueue_scripts()
		{
			$assets_version = defined('SMART_VARIATIONS_IMAGES_VERSION') ? SMART_VARIATIONS_IMAGES_VERSION : '1.0.0';

			// scripts
			wp_register_script('jquery-ui-timepicker', $this->options_url . 'assets/vendor/jquery-timepicker/jquery.ui.timepicker.js', array('jquery', 'jquery-ui-core'), $assets_version, true);
			wp_register_script('smart-variations-images', $this->options_url . 'assets/js/main.js', array('jquery'), $assets_version, true);

			wp_enqueue_script('jquery');
			wp_enqueue_script('farbtastic');
			wp_enqueue_script('media-upload');
			wp_enqueue_script('thickbox');
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-datepicker');
			wp_enqueue_script('jquery-ui-timepicker');
			wp_enqueue_script('smart-variations-images');

			$data = array(
				'select_file'          => esc_html__('Please select a file to import', 'smart-variations-images'),
				'invalid_file'         => esc_html__('Invalid file', 'smart-variations-images'),
				'something_went_wrong' => esc_html__('Something went wrong', 'smart-variations-images'),
			);
			wp_localize_script('smart-variations-images', 'wpsfsvi_vars', $data);

			// styles
			wp_register_style('jquery-ui-timepicker', $this->options_url . 'assets/vendor/jquery-timepicker/jquery.ui.timepicker.css', array(), $assets_version);
			wp_register_style('smart-variations-images', $this->options_url . 'assets/css/main.css', array(), $assets_version);

			wp_enqueue_style('farbtastic');
			wp_enqueue_style('thickbox');
			wp_enqueue_style('jquery-ui-timepicker');
			wp_enqueue_style('smart-variations-images');
		}

		/**
		 * Adds a filter for settings validation.
		 *
		 * @param $input
		 *
		 * @return array
		 */
		public function settings_validate($input)
		{
			$input = $this->preserve_locked_settings(is_array($input) ? $input : array());

			return apply_filters($this->option_group . '_settings_validate', $input);
		}

		/**
		 * Keep stored values for locked (PRO only) fields that the form did not post.
		 *
		 * Locked fields render as a notice or a hidden input, so a save never
		 * carries them and the Settings API would overwrite them with nothing.
		 * Copying the stored value back keeps them for when the licence returns.
		 *
		 * @param array $input Posted settings.
		 * @return array Settings to store.
		 */
		private function preserve_locked_settings(array $input)
		{
			$locked = $this->get_locked_field_defaults();

			if (empty($locked)) {
				return $input;
			}

			$saved = get_option($this->option_group . '_settings');

			if (!is_array($saved) || empty($saved) || empty($this->settings) || !is_array($this->settings)) {
				return $input;
			}

			foreach ($this->settings as $section) {
				if (empty($section['fields']) || !is_array($section['fields'])) {
					continue;
				}

				foreach ($section['fields'] as $field) {
					if (empty($field['id'])) {
						continue;
					}

					$field_id = (string) $field['id'];

					if (!array_key_exists($field_id, $locked)) {
						continue;
					}

					$setting_key = ($this->has_tabs())
						? sprintf('%s_%s_%s', $section['tab_id'], $section['section_id'], $field_id)
						: sprintf('%s_%s', $section['section_id'], $field_id);

					if (!array_key_exists($setting_key, $input) && array_key_exists($setting_key, $saved)) {
						$input[$setting_key] = $saved[$setting_key];
					}
				}
			}

			return $input;
		}

		/**
		 * Displays the "section_description" if specified in $this->settings
		 *
		 * @param array callback args from add_settings_section()
		 */
		public function section_intro($args)
		{
			if (!empty($this->settings)) {
				foreach ($this->settings as $section) {
					if ($section['section_id'] == $args['id']) {
						$renderClass = '';

						$renderClass .= self::add_show_hide_classes($section);

						if ($renderClass) {
							echo '<span class="' . esc_attr($renderClass) . '"></span>';
						}
						if (isset($section['section_description']) && $section['section_description']) {
							echo '<div class="wpsfsvi-section-description wpsfsvi-section-description--' . esc_attr($section['section_id']) . '">' . wp_kses_post($section['section_description']) . '</div>';
						}
						break;
					}
				}
			}
		}

		/**
		 * Processes $this->settings and adds the sections and fields via the WordPress settings API
		 */
		private function process_settings()
		{
			if (!empty($this->settings)) {
				usort($this->settings, array($this, 'sort_array'));

				foreach ($this->settings as $section) {
					if (isset($section['section_id']) && $section['section_id'] && isset($section['section_title'])) {
						$page_name = ($this->has_tabs()) ? sprintf('%s_%s', $this->option_group, $section['tab_id']) : $this->option_group;

						add_settings_section($section['section_id'], $section['section_title'], array($this, 'section_intro'), $page_name);

						if (isset($section['fields']) && is_array($section['fields']) && !empty($section['fields'])) {
							foreach ($section['fields'] as $field) {
								if (isset($field['id']) && $field['id'] && isset($field['title'])) {
									$tooltip     = '';

									if (isset($field['link']) && is_array($field['link'])) {
										$link_url      = (isset($field['link']['url'])) ? esc_url($field['link']['url']) : '';
										$link_text     = (isset($field['link']['text'])) ? esc_html($field['link']['text']) : esc_html__('Learn More', 'smart-variations-images');
										$link_external = (isset($field['link']['external'])) ? (bool) $field['link']['external'] : true;
										$link_type     = (isset($field['link']['type'])) ? esc_attr($field['link']['type']) : 'tooltip';
										$link_target   = ($link_external) ? ' target="_blank"' : '';

										if ('tooltip' === $link_type) {
											$link_text = sprintf('<i class="dashicons dashicons-info wpsfsvi-link-icon" title="%s"><span class="screen-reader-text">%s</span></i>', $link_text, $link_text);
										}

										$link = ($link_url) ? sprintf('<a class="wpsfsvi-link" href="%s"%s>%s</a>', $link_url, $link_target, $link_text) : '';

										if ($link && 'tooltip' === $link_type) {
											$tooltip = $link;
										} elseif ($link) {
											$field['subtitle'] .= (empty($field['subtitle'])) ? $link : sprintf('<br/><br/>%s', $link);
										}
									}

									$title = (!empty($field['subtitle'])) ? sprintf('%s %s<span class="wpsfsvi-subtitle">%s</span>', $field['title'], $tooltip, $field['subtitle']) : sprintf('%s %s', $field['title'], $tooltip);

									add_settings_field(
										$field['id'],
										$title,
										array($this, 'generate_setting'),
										$page_name,
										$section['section_id'],
										array(
											'section' => $section,
											'field'   => $field,
										)
									);
								}
							}
						}
					}
				}
			}
		}

		/**
		 * Usort callback. Sorts $this->settings by "section_order"
		 *
		 * @param array $a Sortable Array.
		 * @param array $b Sortable Array.
		 *
		 * @return array
		 */
		public function sort_array($a, $b)
		{
			if (!isset($a['section_order'])) {
				return 0;
			}

			return ($a['section_order'] > $b['section_order']) ? 1 : 0;
		}

		/**
		 * Generates the HTML output of the settings fields
		 *
		 * @param array $args callback args from add_settings_field()
		 */
		public function generate_setting($args)
		{
			$section                = $args['section'];
			$this->setting_defaults = apply_filters('wpsfsvi_defaults_' . $this->option_group, $this->setting_defaults);

			$args = wp_parse_args($args['field'], $this->setting_defaults);

			$options = get_option($this->option_group . '_settings');

			$args['id']    = ($this->has_tabs()) ? sprintf('%s_%s_%s', $section['tab_id'], $section['section_id'], $args['id']) : sprintf('%s_%s', $section['section_id'], $args['id']);
			$args['value'] = (isset($options[$args['id']])) ? $options[$args['id']] : (isset($args['default']) ? $args['default'] : '');
			$args['name']  = $this->generate_field_name($args['id']);

			$args['class'] .= self::add_show_hide_classes($args);

			do_action('wpsfsvi_before_field_' . $this->option_group);
			do_action('wpsfsvi_before_field_' . $this->option_group . '_' . $args['id']);

			$this->do_field_method($args);

			do_action('wpsfsvi_after_field_' . $this->option_group);
			do_action('wpsfsvi_after_field_' . $this->option_group . '_' . $args['id']);
		}

		/**
		 * Do field method, if it exists
		 *
		 * @param array $args
		 */
		public function do_field_method($args)
		{
			$generate_field_method = sprintf('generate_%s_field', $args['type']);

			if (method_exists($this, $generate_field_method)) {
				$this->$generate_field_method($args);
			}
		}

		/**
		 * Generate: Text field
		 *
		 * @param array $args
		 */
		public function generate_text_field($args)
		{
			$args['value'] = esc_attr(stripslashes($args['value']));

			echo '<input type="text" name="' . esc_attr($args['name']) . '" id="' . esc_attr($args['id']) . '" value="' . esc_attr($args['value']) . '" placeholder="' . esc_attr($args['placeholder']) . '" class="regular-text ' . esc_attr($args['class']) . '" />';

			$this->generate_description($args['desc']);
		}

		/**
		 * Generate: Hidden field.
		 *
		 * @param array $args
		 */
		public function generate_hidden_field($args)
		{
			$args['value'] = esc_attr(stripslashes($args['value']));

			echo '<input type="hidden" name="' . esc_attr($args['name']) . '" id="' . esc_attr($args['id']) . '" value="' . esc_attr($args['value']) . '"  class="hidden-field ' . esc_attr($args['class']) . '" />';
		}

		/**
		 * Generate: Number field
		 *
		 * @param array $args
		 */
		public function generate_number_field($args)
		{
			$args['value'] = esc_attr(stripslashes($args['value']));

			echo '<input type="number" name="' . esc_attr($args['name']) . '" id="' . esc_attr($args['id']) . '" value="' . esc_attr($args['value']) . '" placeholder="' . esc_attr($args['placeholder']) . '" class="regular-text ' . esc_attr($args['class']) . '" />';

			$this->generate_description($args['desc']);
		}

		/**
		 * Generate: Time field
		 *
		 * @param array $args
		 */
		public function generate_time_field($args)
		{
			$args['value'] = esc_attr(stripslashes($args['value']));

			$timepicker = (!empty($args['timepicker'])) ? esc_attr(wp_json_encode($args['timepicker'])) : '';

			echo '<input type="text" name="' . esc_attr($args['name']) . '" id="' . esc_attr($args['id']) . '" value="' . esc_attr($args['value']) . '" class="timepicker regular-text ' . esc_attr($args['class']) . '" data-timepicker="' . esc_attr($timepicker) . '" />';

			$this->generate_description($args['desc']);
		}

		/**
		 * Generate: Date field
		 *
		 * @param array $args
		 */
		public function generate_date_field($args)
		{
			$args['value'] = esc_attr(stripslashes($args['value']));

			$datepicker = (!empty($args['datepicker'])) ? esc_attr(wp_json_encode($args['datepicker'])) : '';

			echo '<input type="text" name="' . esc_attr($args['name']) . '" id="' . esc_attr($args['id']) . '" value="' . esc_attr($args['value']) . '" class="datepicker regular-text ' . esc_attr($args['class']) . '" data-datepicker="' . esc_attr($datepicker) . '" />';

			$this->generate_description($args['desc']);
		}

		/**
		 * Generate Export Field.
		 *
		 * @param array $args Arguments.
		 */
		public function generate_export_field($args)
		{
			$args['value'] = esc_attr(stripslashes($args['value']));
			$args['value'] = empty($args['value']) ? esc_html__('Export Settings', 'smart-variations-images') : $args['value'];
			$option_group  = $this->option_group;
			$export_url    = site_url() . '/wp-admin/admin-ajax.php?action=wpsfsvi_export_settings&_wpnonce=' . wp_create_nonce('wpsfsvi_export_settings') . '&option_group=' . $option_group;

			echo '<a target="_blank" href="' . esc_url($export_url) . '" class="button" name="' . esc_attr($args['name']) . '" id="' . esc_attr($args['id']) . '">' . esc_html($args['value']) . '</a>';

			$options = get_option($option_group . '_settings');
			$this->generate_description($args['desc']);
		}

		/**
		 * Generate Import Field.
		 *
		 * @param array $args Arguments.
		 */
		public function generate_import_field($args)
		{
			$args['value'] = esc_attr(stripslashes($args['value']));
			$args['value'] = empty($args['value']) ? esc_html__('Import Settings', 'smart-variations-images') : $args['value'];
			$option_group  = $this->option_group;

			echo sprintf(
				'
				<div class="wpsfsvi-import">
					<div class="wpsfsvi-import__false_btn">
						<input type="file" name="wpsfsvi-import-field" class="wpsfsvi-import__file_field" id="%s" accept=".json"/>
						<button type="button" name="wpsfsvi_import_button" class="button wpsfsvi-import__button" id="%s-button">%s</button>
						<input type="hidden" class="wpsfsvi_import_nonce" value="%s"></input>
						<input type="hidden" class="wpsfsvi_import_option_group" value="%s"></input>
					</div>
					<span class="spinner"></span>
				</div>',
				esc_attr($args['id']),
				esc_attr($args['id']),
				esc_attr($args['value']),
				esc_attr(wp_create_nonce('wpsfsvi_import_settings')),
				esc_attr($this->option_group)
			);

			$this->generate_description($args['desc']);
		}

		/**
		 * Generate: Group field
		 *
		 * Generates a table of subfields, and a javascript template for create new repeatable rows
		 *
		 * @param array $args
		 */
		public function generate_group_field($args)
		{
			$value     = (array) $args['value'];
			$row_count = (!empty($value)) ? count($value) : 1;

			echo '<table class="widefat wpsfsvi-group" cellspacing="0">';

			echo '<tbody>';

			for ($row = 0; $row < $row_count; $row++) {
				echo wp_kses_post($this->generate_group_row_template($args, false, $row));
			}

			echo '</tbody>';

			echo '</table>';

			printf('<script type="text/html" id="%s_template">%s</script>', esc_attr($args['id']), wp_kses_post($this->generate_group_row_template($args, true)));

			$this->generate_description($args['desc']);
		}

		/**
		 * Generate group row template
		 *
		 * @param array $args  Field arguments
		 * @param bool  $blank Blank values
		 * @param int   $row   Iterator
		 *
		 * @return string|bool
		 */
		public function generate_group_row_template($args, $blank = false, $row = 0)
		{
			$row_template = false;
			$row_id       = (!empty($args['value'][$row]['row_id'])) ? $args['value'][$row]['row_id'] : $row;
			$row_id_value = ($blank) ? '' : $row_id;

			if ($args['subfields']) {
				$row_class = ($row % 2 == 0) ? 'alternate' : '';

				$row_template .= sprintf('<tr class="wpsfsvi-group__row %s">', $row_class);

				$row_template .= sprintf('<td class="wpsfsvi-group__row-index"><span>%d</span></td>', $row);

				$row_template .= '<td class="wpsfsvi-group__row-fields">';

				$row_template .= '<input type="hidden" class="wpsfsvi-group__row-id" name="' . sprintf('%s[%d][row_id]', esc_attr($args['name']), esc_attr($row)) . '" value="' . esc_attr($row_id_value) . '" />';

				foreach ($args['subfields'] as $subfield) {
					$subfield = wp_parse_args($subfield, $this->setting_defaults);

					$subfield['value'] = ($blank) ? '' : (isset($args['value'][$row][$subfield['id']]) ? $args['value'][$row][$subfield['id']] : '');
					$subfield['name']  = sprintf('%s[%d][%s]', $args['name'], $row, $subfield['id']);
					$subfield['id']    = sprintf('%s_%d_%s', $args['id'], $row, $subfield['id']);

					$class = sprintf('wpsfsvi-group__field-wrapper--%s', $subfield['type']);

					$row_template .= sprintf('<div class="wpsfsvi-group__field-wrapper %s">', $class);
					$row_template .= sprintf('<label for="%s" class="wpsfsvi-group__field-label">%s</label>', $subfield['id'], $subfield['title']);

					ob_start();
					$this->do_field_method($subfield);
					$row_template .= ob_get_clean();

					$row_template .= '</div>';
				}

				$row_template .= '</td>';

				$row_template .= '<td class="wpsfsvi-group__row-actions">';

				$row_template .= sprintf('<a href="javascript: void(0);" class="wpsfsvi-group__row-add" data-template="%s_template"><span class="dashicons dashicons-plus-alt"></span></a>', $args['id']);
				$row_template .= '<a href="javascript: void(0);" class="wpsfsvi-group__row-remove"><span class="dashicons dashicons-trash"></span></a>';

				$row_template .= '</td>';

				$row_template .= '</tr>';
			}

			return $row_template;
		}

		/**
		 * Generate: Select field
		 *
		 * @param array $args Arguments.
		 */
		public function generate_select_field($args)
		{
			$args['value'] = esc_html(esc_attr($args['value']));

			echo '<select name="' . esc_attr($args['name']) . '" id="' . esc_attr($args['id']) . '" class="' . esc_attr($args['class']) . '">';

			foreach ($args['choices'] as $value => $text) {
				if (is_array($text)) {
					echo sprintf('<optgroup label="%s">', esc_html($value));
					foreach ($text as $group_value => $group_text) {
						$selected = ($group_value === $args['value']) ? 'selected="selected"' : '';
						echo sprintf('<option value="%s" %s>%s</option>', esc_attr($group_value), esc_html($selected), esc_html($group_text));
					}
					echo '</optgroup>';
					continue;
				}

				$selected = (strval($value) === $args['value']) ? 'selected="selected"' : '';

				echo sprintf('<option value="%s" %s>%s</option>', esc_attr($value), esc_html($selected), esc_html($text));
			}

			echo '</select>';

			$this->generate_description($args['desc']);
		}

		/**
		 * Generate: Password field
		 *
		 * @param array $args
		 */
		public function generate_password_field($args)
		{
			$args['value'] = esc_attr(stripslashes($args['value']));

				echo '<input type="password" name="' . esc_attr($args['name']) . '" id="' . esc_attr($args['id']) . '" value="' . esc_attr($args['value']) . '" placeholder="' . esc_attr($args['placeholder']) . '" class="regular-text ' . esc_attr($args['class']) . '" />';

			$this->generate_description($args['desc']);
		}

		/**
		 * Generate: Textarea field
		 *
		 * @param array $args
		 */
		public function generate_textarea_field($args)
		{
			$args['value'] = esc_textarea(stripslashes((string) $args['value']));

			echo '<textarea name="' . esc_attr($args['name']) . '" id="' . esc_attr($args['id']) . '" placeholder="' . esc_attr($args['placeholder']) . '" rows="5" cols="60" class="' . esc_attr($args['class']) . '">' . esc_textarea($args['value']) . '</textarea>';

			$this->generate_description($args['desc']);
		}

		/**
		 * Generate: Radio field
		 *
		 * @param array $args
		 */
		public function generate_radio_field($args)
		{
			$args['value'] = esc_html(esc_attr($args['value']));

			foreach ($args['choices'] as $value => $text) {
				$field_id = sprintf('%s_%s', $args['id'], $value);
				$checked  = ($value == $args['value']) ? 'checked="checked"' : '';

				echo sprintf('<label><input type="radio" name="%s" id="%s" value="%s" class="%s" %s> %s</label><br />', esc_attr($args['name']), esc_attr($field_id), esc_attr($value), esc_attr($args['class']), esc_attr($checked), esc_html($text));
			}

			$this->generate_description($args['desc']);
		}

		/**
		 * Generate: Checkbox field
		 *
		 * @param array $args
		 */
		public function generate_checkbox_field($args)
		{
			$args['value'] = esc_attr(stripslashes($args['value']));
			$checked       = ($args['value']) ? 'checked="checked"' : '';

			echo '<input type="hidden" name="' . esc_attr($args['name']) . '" value="0" />';
			echo '<label><input type="checkbox" name="' . esc_attr($args['name']) . '" id="' . esc_attr($args['id']) . '" value="1" class="' . esc_attr($args['class']) . '" ' . esc_attr($checked) . '> ' . wp_kses_post($args['desc']) . '</label>';
		}

		/**
		 * Generate: Toggle field
		 *
		 * @param array $args
		 */
		public function generate_toggle_field($args)
		{
			$args['value'] = esc_attr(stripslashes($args['value']));
			$checked       = ($args['value']) ? 'checked="checked"' : '';

			echo '<input type="hidden" name="' . esc_attr($args['name']) . '" value="0" />';
			echo '<label class="switch"><input type="checkbox" name="' . esc_attr($args['name']) . '" id="' . esc_attr($args['id']) . '" value="1" class="' . esc_attr($args['class']) . '" ' . esc_attr($checked) . '> <span class="slider"></span></label>';
			$this->generate_description($args['desc']);
		}

		/**
		 * Generate: Checkboxes field
		 *
		 * @param array $args
		 */
		public function generate_checkboxes_field($args)
		{
			echo '<input type="hidden" name="' . esc_attr($args['name']) . '" value="0" />';

			echo '<ul class="wpsfsvi-list wpsfsvi-list--checkboxes">';

			foreach ($args['choices'] as $value => $text) {
				$checked  = (is_array($args['value']) && in_array(strval($value), array_map('strval', $args['value']), true)) ? 'checked="checked"' : '';
				$field_id = sprintf('%s_%s', $args['id'], $value);

				echo sprintf('<li><label><input type="checkbox" name="%s[]" id="%s" value="%s" class="%s" %s> %s</label></li>', esc_attr($args['name']), esc_attr($field_id), esc_attr($value), esc_attr($args['class']), esc_attr($checked), esc_html($text));
			}

			echo '</ul>';

			$this->generate_description($args['desc']);
		}

		/**
		 * Generate: Color field
		 *
		 * @param array $args
		 */
		public function generate_color_field($args)
		{
			$color_picker_id = sprintf('%s_cp', $args['id']);
			$args['value']   = esc_attr(stripslashes($args['value']));

			echo '<div class="wpsfsvi-color" style="position:relative;">';

			echo sprintf('<input type="text" name="%s" id="%s" value="%s" class="%s">', esc_attr($args['name']), esc_attr($args['id']), esc_attr($args['value']), esc_attr($args['class']));

			echo sprintf('<div id="%s" style="position:absolute;top:0;left:190px;background:#fff;z-index:9999;"></div>', esc_attr($color_picker_id));

			$this->generate_description($args['desc']);

			echo '<script type="text/javascript">
                jQuery(document).ready(function($){
					var colorPicker = $("#' . esc_js($color_picker_id) . '");
					colorPicker.farbtastic("#' . esc_js($args['id']) . '");
                    colorPicker.hide();
					$("#' . esc_js($args['id']) . '").on("focus", function(){
                        colorPicker.show();
                    });
					$("#' . esc_js($args['id']) . '").on("blur", function(){
                        colorPicker.hide();
                        if($(this).val() == "") $(this).val("#");
                    });
                });
                </script>';

			echo '</div>';
		}

		/**
		 * Generate: File field
		 *
		 * @param array $args
		 */
		public function generate_file_field($args)
		{
			$args['value'] = esc_attr($args['value']);
			$button_id     = sprintf('%s_button', $args['id']);

			echo sprintf('<input type="text" name="%s" id="%s" value="%s" class="regular-text %s"> ', esc_attr($args['name']), esc_attr($args['id']), esc_attr($args['value']), esc_attr($args['class']));

			echo sprintf('<input type="button" class="button wpsfsvi-browse" id="%s" value="Browse" />', esc_attr($button_id));

		?>
			<script type='text/javascript'>
				jQuery(document).ready(function($) {

					// Uploading files
					var file_frame;
					var wp_media_post_id = wp.media.model.settings.post.id; // Store the old id.
					var set_to_post_id = 0;

					jQuery(document.body).on('click', '#<?php echo esc_attr($button_id); ?>', function(event) {

						event.preventDefault();

						// If the media frame already exists, reopen it.
						if (file_frame) {
							// Set the post ID to what we want
							file_frame.uploader.uploader.param('post_id', set_to_post_id);
							// Open frame
							file_frame.open();
							return;
						} else {
							// Set the wp.media post id so the uploader grabs the ID we want when initialised.
							wp.media.model.settings.post.id = set_to_post_id;
						}

						// Create the media frame.
						file_frame = wp.media.frames.file_frame = wp.media({
							title: '<?php echo esc_html__('Select a image to upload', 'smart-variations-images'); ?>',
							button: {
								text: '<?php echo esc_html__('Use this image', 'smart-variations-images'); ?>',
							},
							multiple: false // Set to true to allow multiple files to be selected
						});

						// When an image is selected, run a callback.
						file_frame.on('select', function() {
							// We set multiple to false so only get one image from the uploader
							attachment = file_frame.state().get('selection').first().toJSON();

							// Do something with attachment.id and/or attachment.url here
							$('#image-preview').attr('src', attachment.url).css('width', 'auto');
							$('#image_attachment_id').val(attachment.id);
							$('#<?php echo esc_attr($args['id']); ?>').val(attachment.url);

							// Restore the main post ID
							wp.media.model.settings.post.id = wp_media_post_id;
						});

						// Finally, open the modal
						file_frame.open();
					});

					// Restore the main ID when the add media button is pressed
					jQuery('a.add_media').on('click', function() {
						wp.media.model.settings.post.id = wp_media_post_id;
					});
				});
			</script>
		<?php
		}

		/**
		 * Generate: Editor field
		 *
		 * @param array $args
		 */
		public function generate_editor_field($args)
		{
			wp_editor($args['value'], $args['id'], array('textarea_name' => $args['name']));

			$this->generate_description($args['desc']);
		}

		/**
		 * Generate: Code editor field
		 *
		 * @param array $args
		 */
		public function generate_code_editor_field($args)
		{
			printf(
				'<textarea
					name="%s"
					id="%s"
					placeholder="%s"
					rows="5"
					cols="60"
					class="%s"
				>%s</textarea>',
				esc_attr($args['name']),
				esc_attr($args['id']),
				esc_attr($args['placeholder']),
				esc_attr($args['class']),
				esc_html($args['value'])
			);

			$settings = wp_enqueue_code_editor(array('type' => esc_attr($args['mimetype'])));

			wp_add_inline_script(
				'code-editor',
				sprintf(
					'jQuery( function() { wp.codeEditor.initialize( "%s", %s ); } );',
					esc_attr($args['id']),
					wp_json_encode($settings)
				)
			);

			$this->generate_description($args['desc']);
		}

		/**
		 * Generate: Custom field
		 *
		 * @param array $args
		 */
		public function generate_custom_field($args)
		{
			// The wrapper lets the stylesheet collapse the label column for a
			// field that has no label, so full-width screens keep their width.
			echo '<div class="wpsfsvi-field-custom">';

			if (isset($args['output']) && is_callable($args['output'])) {
				call_user_func($args['output'], $args);
			} else {
				echo wp_kses_post(isset($args['output']) ? $args['output'] : $args['default']);
			}

			echo '</div>';
		}

		/**
		 * Generate: Multi Inputs field
		 *
		 * @param array $args
		 */
		public function generate_multiinputs_field($args)
		{
			$field_titles = array_keys($args['default']);
			$values       = array_values($args['value']);

			echo '<div class="wpsfsvi-multifields">';

			$i = 0;
			while ($i < count($values)) :

				$field_id = sprintf('%s_%s', $args['id'], $i);
				$value    = esc_attr(stripslashes($values[$i]));

				echo '<div class="wpsfsvi-multifields__field">';
				echo '<input type="text" name="' . esc_attr($args['name']) . '[]" id="' . esc_attr($field_id) . '" value="' . esc_attr($value) . '" class="regular-text ' . esc_attr($args['class']) . '" placeholder="' . esc_attr($args['placeholder']) . '" />';
				echo '<br><span>' . esc_html($field_titles[$i]) . '</span>';
				echo '</div>';

				$i++;
			endwhile;

			echo '</div>';

			$this->generate_description($args['desc']);
		}

		/**
		 * Generate: Field ID
		 *
		 * @param mixed $id
		 *
		 * @return string
		 */
		public function generate_field_name($id)
		{
			return sprintf('%s_settings[%s]', $this->option_group, $id);
		}

		/**
		 * Generate: Description
		 *
		 * @param mixed $description
		 */
		public function generate_description($description)
		{
			if ($description && $description !== '') {
				echo '<p class="description">' . wp_kses_post($description) . '</p>';
			}
		}

		/**
		 * Output the settings form
		 */
		public function settings()
		{
			do_action('wpsfsvi_before_settings_' . $this->option_group);
		?>
			<form action="options.php" method="post" novalidate enctype="multipart/form-data">
				<?php do_action('wpsfsvi_before_settings_fields_' . $this->option_group); ?>
				<?php settings_fields($this->option_group); ?>

				<?php do_action('wpsfsvi_do_settings_sections_' . $this->option_group); ?>

				<?php if (apply_filters('wpsfsvi_show_save_changes_button_' . $this->option_group, true)) { ?>
					<p class="submit">
						<input type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'smart-variations-images'); ?>" />
					</p>
				<?php } ?>
			</form>
		<?php
			do_action('wpsfsvi_after_settings_' . $this->option_group);
		}

		/**
		 * Helper: Get Settings
		 *
		 * @return array
		 */
		public function get_settings($clean = false)
		{
			$settings_name = $this->option_group . '_settings';

			// `$clean` produces a different key shape, so both shapes need their own cache entry.
			$cache_key = $settings_name . ( $clean ? '_clean' : '' );

			if (isset(self::$settings_cache[$cache_key])) {
				return self::$settings_cache[$cache_key];
			}

			$saved_settings             = get_option($this->option_group . '_settings');

			self::$settings_cache[$cache_key] = array();

			foreach ($this->settings as $section) {
				if (empty($section['fields'])) {
					continue;
				}

				foreach ($section['fields'] as $field) {
					if (!empty($field['default']) && is_array($field['default'])) {
						$field['default'] = array_values($field['default']);
					}

					if ($clean) {
						$setting_key_clean = ($this->has_tabs()) ? sprintf('%s', $field['id']) : sprintf('%s', $field['id']);
					}
					$setting_key = ($this->has_tabs()) ? sprintf('%s_%s_%s', $section['tab_id'], $section['section_id'], $field['id']) : sprintf('%s_%s', $section['section_id'], $field['id']);


					if (isset($saved_settings[$setting_key])) {
						self::$settings_cache[$cache_key][($clean) ? $setting_key_clean : $setting_key] = $saved_settings[$setting_key];
					} else {
						self::$settings_cache[$cache_key][($clean) ? $setting_key_clean : $setting_key] = (isset($field['default'])) ? $field['default'] : false;
					}
				}
			}

			return self::$settings_cache[$cache_key];
		}

		/**
		 * Get the tabbed key for every registered field, mapped to its default.
		 *
		 * @return array<string, mixed> Tabbed key => default value.
		 */
		public function get_setting_keys()
		{
			return $this->get_settings();
		}

		/**
		 * Defaults of the fields this install shows as locked (PRO only).
		 *
		 * The settings file turns a PRO field into a notice ('custom' rendered by
		 * wpsfsvisvi_info) or a hidden field when premium code cannot be used, so the
		 * registered definitions already say which fields are locked. Ids that are
		 * also registered as a normal field elsewhere are left alone.
		 *
		 * @return array<string, mixed> Field id (clean key) => default value.
		 */
		public function get_locked_field_defaults()
		{
			$locked   = array();
			$unlocked = array();

			if (empty($this->settings) || !is_array($this->settings)) {
				return $locked;
			}

			foreach ($this->settings as $section) {
				if (empty($section['fields']) || !is_array($section['fields'])) {
					continue;
				}

				foreach ($section['fields'] as $field) {
					if (empty($field['id'])) {
						continue;
					}

					$field_id   = (string) $field['id'];
					$field_type = isset($field['type']) ? $field['type'] : '';

					$is_locked = ('hidden' === $field_type)
						|| (
							'custom' === $field_type
							&& isset($field['output'])
							&& 'wpsfsvisvi_info' === $field['output']
						);

					if (!$is_locked) {
						$unlocked[$field_id] = true;
						continue;
					}

					$default = (isset($field['default'])) ? $field['default'] : false;

					if (is_array($default)) {
						$default = array_values($default);
					}

					$locked[$field_id] = $default;
				}
			}

			foreach (array_keys($unlocked) as $field_id) {
				unset($locked[$field_id]);
			}

			return $locked;
		}

		/**
		 * Reduce a settings payload to the registered tabbed keys.
		 *
		 * Accepts payloads written by older releases, where each field was stored
		 * under its bare id (e.g. `hide_thumbs`), and maps them to the current
		 * `<tab>_<section>_<field>` key. Unknown keys are dropped.
		 *
		 * @param array $settings Settings payload.
		 * @return array<string, mixed> Normalized settings.
		 */
		public function normalize_settings(array $settings)
		{
			$registered = $this->get_settings();
			$normalized = array();

			foreach ($registered as $key => $default) {
				if (array_key_exists($key, $settings)) {
					$normalized[$key] = $settings[$key];
					continue;
				}

				$legacy = $this->find_legacy_key($key, $settings, $registered);

				if (null !== $legacy) {
					$normalized[$key] = $settings[$legacy];
				}
			}

			return $normalized;
		}

		/**
		 * Find the legacy flat key that belongs to a tabbed key.
		 *
		 * Legacy keys are the bare field ids, so the longest matching suffix wins:
		 * `thumb_imagecaption` must beat `imagecaption` for
		 * `main_section_imagesettings_thumb_imagecaption`.
		 *
		 * @param string $key        Tabbed key.
		 * @param array  $settings   Settings payload.
		 * @param array  $registered Registered settings, keyed by tabbed key.
		 * @return string|null Legacy key, or null when there is no match.
		 */
		private function find_legacy_key($key, array $settings, array $registered)
		{
			$best = null;

			foreach ($settings as $candidate => $value) {
				$candidate = (string) $candidate;

				if ('' === $candidate || isset($registered[$candidate])) {
					continue;
				}

				if (substr($key, -strlen($candidate)) !== $candidate) {
					continue;
				}

				if (null === $best || strlen($candidate) > strlen($best)) {
					$best = $candidate;
				}
			}

			return $best;
		}

		/**
		 * Tabless Settings sections
		 */
		public function do_tabless_settings_sections()
		{
		?>
			<div class="wpsfsvi-section wpsfsvi-tabless">
				<?php do_settings_sections($this->option_group); ?>
			</div>
			<?php
		}

		/**
		 * Tabbed Settings sections
		 */
		public function do_tabbed_settings_sections()
		{
			$i = 0;
			foreach ($this->tabs as $tab_data) {
			?>
				<div id="tab-<?php echo esc_attr($tab_data['id']); ?>" class="wpsfsvi-section wpsfsvi-tab wpsfsvi-tab--<?php echo esc_attr($tab_data['id']); ?> <?php
																																			if ($i == 0) {
																																				echo 'wpsfsvi-tab--active';
																																			}
																																			?>
				">
					<div class="postbox">
						<?php do_settings_sections(sprintf('%s_%s', $this->option_group, $tab_data['id'])); ?>
					</div>
				</div>
			<?php
				$i++;
			}
		}

		/**
		 * Output the tab links
		 */
		public function tab_links()
		{
			if (!apply_filters('wpsfsvi_show_tab_links_' . $this->option_group, true)) {
				return;
			}

			do_action('wpsfsvi_before_tab_links_' . $this->option_group);
			?>
			<div class="wpsfsvi-nav-wrap wpsfsvi-nav-wrap--<?php echo esc_attr($this->tabs_layout); ?>">
				<button type="button" class="wpsfsvi-nav__arrow wpsfsvi-nav__arrow--prev" aria-label="<?php esc_attr_e('Scroll tabs left', 'smart-variations-images'); ?>" disabled>
					<span aria-hidden="true">&#8249;</span>
				</button>
				<ul class="wpsfsvi-nav wpsfsvi-nav--<?php echo esc_attr($this->tabs_layout); ?>">
				<?php
				$i = 0;
				$last_group = '';

				foreach ($this->tabs as $tab_data) {
					if (!$this->tab_has_settings($tab_data['id'])) {
						continue;
					}

					if (!isset($tab_data['class'])) {
						$tab_data['class'] = '';
					}

					$tab_data['class'] .= self::add_show_hide_classes($tab_data);

					$active = ($i == 0) ? 'wpsfsvi-nav__item--active' : '';

					$group = isset($tab_data['group']) ? (string) $tab_data['group'] : '';

					// Thin rule between groups; the strip hides it.
					if ($i > 0 && '' !== $group && $group !== $last_group) {
						echo '<li class="wpsfsvi-nav__divider" aria-hidden="true"></li>';
					}

					$last_group = $group;
				?>
					<li class="wpsfsvi-nav__item <?php echo esc_attr($active); ?>">
						<a class="wpsfsvi-nav__item-link <?php echo esc_attr($tab_data['class']); ?>" href="#tab-<?php echo esc_attr($tab_data['id']); ?>"><?php echo esc_html($tab_data['title']); ?></a>
					</li>
				<?php
					$i++;
				}
				?>
				</ul>
				<button type="button" class="wpsfsvi-nav__arrow wpsfsvi-nav__arrow--next" aria-label="<?php esc_attr_e('Scroll tabs right', 'smart-variations-images'); ?>" disabled>
					<span aria-hidden="true">&#8250;</span>
				</button>
				<div class="wpsfsvi-nav__item--last">
					<input type="submit" class="button-primary wpsfsvi-button-submit" value="<?php esc_attr_e('Save Changes', 'smart-variations-images'); ?>">
				</div>
			</div>

			<?php // Add this here so notices are moved. 
			?>
			<div class="wrap wpsfsvi-notices">
				<h2>&nbsp;</h2>
			</div>
<?php
			do_action('wpsfsvi_after_tab_links_' . $this->option_group);
		}

		/**
		 * Does this tab have settings?
		 *
		 * @param string $tab_id
		 *
		 * @return bool
		 */
		public function tab_has_settings($tab_id)
		{
			if (empty($this->settings)) {
				return false;
			}

			foreach ($this->settings as $settings_section) {
				if ($tab_id !== $settings_section['tab_id']) {
					continue;
				}

				return true;
			}

			return false;
		}

		/**
		 * Check if this settings instance has tabs
		 */
		public function has_tabs()
		{
			if (!empty($this->tabs)) {
				return true;
			}

			return false;
		}

		/**
		 * Add Show Hide Classes.
		 */
		public static function add_show_hide_classes($args, $type = 'show_if')
		{
			$class = '';
			$slug  = ' ' . str_replace('_', '-', $type);
			if (isset($args[$type]) && is_array($args[$type])) {
				$class .= $slug;
				foreach ($args[$type] as $condition) {
					if (isset($condition['field']) && $condition['value']) {
						$value_string = '';
						foreach ($condition['value'] as $value) {
							if (!empty($value_string)) {
								$value_string .= '||';
							}
							$value_string .= $value;
						}

						if (!empty($value_string)) {
							$class .= $slug . '--' . $condition['field'] . '===' . $value_string;
						}
					} else {
						$and_string = '';
						foreach ($condition as $and_condition) {
							if (!isset($and_condition['field']) || !isset($and_condition['value'])) {
								continue;
							}

							if (!empty($and_string)) {
								$and_string .= '&&';
							}

							$value_string = '';
							foreach ($and_condition['value'] as $value) {
								if (!empty($value_string)) {
									$value_string .= '||';
								}
								$value_string .= $value;
							}

							if (isset($value_string) && ($value_string === '0' || !empty($value_string))) {
								$and_string .= $and_condition['field'] . '===' . $value_string;
							}
						}

						if (!empty($and_string)) {
							$class .= $slug . '--' . $and_string;
						}
					}
				}
			}

			// Run the function again with hide if.
			if ('hide_if' !== $type) {
				$class .= self::add_show_hide_classes($args, 'hide_if');
			}

			return $class;
		}

		/**
		 * Handle export settings action.
		 */
		public function export_settings()
		{
			$_wpnonce     = filter_input(INPUT_GET, '_wpnonce');
			$option_group = filter_input(INPUT_GET, 'option_group');

			if (empty($_wpnonce) || !wp_verify_nonce($_wpnonce, 'wpsfsvi_export_settings')) {
				wp_die(esc_html__('Action failed.', 'smart-variations-images'));
			}

			if (empty($option_group)) {
				wp_die(esc_html__('No option group specified.', 'smart-variations-images'));
			}

			// Export the shape the settings screen reads, so an export always
			// represents what the merchant sees and an import can restore it.
			$options = $this->get_settings();

			if (empty($options)) {
				$options = get_option($option_group . '_settings');
			}

			// output the file contents to the browser.
			header('Content-Type: text/json; charset=utf-8');
			header('Content-Disposition: attachment; filename=wpsfsvi-settings-' . $option_group . '.json');
			echo wp_json_encode($options);
			exit;
		}

		/**
		 * Import settings.
		 */
		public function import_settings()
		{
			$_wpnonce     = filter_input(INPUT_POST, '_wpnonce');
			$option_group = filter_input(INPUT_POST, 'option_group');
			$settings     = filter_input(INPUT_POST, 'settings');

			if ($option_group !== $this->option_group) {
				return;
			}

			// verify nonce.
			if (empty($_wpnonce) || !wp_verify_nonce($_wpnonce, 'wpsfsvi_import_settings')) {
				wp_send_json_error();
			}

			// check if $settings is a valid json.
			if (!is_string($settings) || !is_array(json_decode($settings, true))) {
				wp_send_json_error();
			}

			$settings_data = json_decode($settings, true);
			$normalized    = $this->normalize_settings($settings_data);

			// Reject payloads that match none of this screen's fields.
			if (empty($normalized)) {
				wp_send_json_error();
			}

			update_option($option_group . '_settings', $normalized);

			wp_send_json_success();
		}
	}
}

if (!function_exists('wpsfsvi_get_setting')) {
	/**
	 * Get a setting from an option group
	 *
	 * @param string $option_group
	 * @param string $section_id May also be prefixed with tab ID
	 * @param string $field_id
	 *
	 * @return mixed
	 */
	function wpsfsvi_get_setting($option_group, $section_id, $field_id)
	{
		$options = get_option($option_group . '_settings');
		if (isset($options[$section_id . '_' . $field_id])) {
			return $options[$section_id . '_' . $field_id];
		}

		return false;
	}
}

if (!function_exists('wpsfsvi_delete_settings')) {
	/**
	 * Delete all the saved settings from a settings file/option group
	 *
	 * @param string $option_group
	 */
	function wpsfsvi_delete_settings($option_group)
	{
		delete_option($option_group . '_settings');
	}
}
