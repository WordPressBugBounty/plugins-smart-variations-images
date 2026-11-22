<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.rosendo.pt
 * @since      1.0.0
 *
 * @package    Smart_Variations_Images
 * @subpackage Smart_Variations_Images/includes
 */
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Smart_Variations_Images
 * @subpackage Smart_Variations_Images/includes
 * @author     David Rosendo <david@rosendo.pt>
 */
class Smart_Variations_Images {
    /**
     * The loader responsible for maintaining and registering all hooks that power the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Smart_Variations_Images_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /**
     * The WordPress Settings Framework instance for managing plugin settings.
     *
     * @since    1.0.0
     * @access   private
     * @var      WordPressSettingsFramework $wpsf The settings framework instance.
     */
    private $wpsf;

    /**
     * The single instance of the class.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Smart_Variations_Images|null $instance The singleton instance of the class.
     */
    protected static $instance = null;

    /**
     * The plugin options.
     *
     * @since    1.0.0
     * @access   public
     * @var      stdClass|null $options The current plugin options.
     */
    public $options = null;

    /**
     * The public-facing handler instance.
     *
     * Swatch rendering lives in a separate class and needs to know whether SVI
     * will render a given product before it hides the native attribute dropdowns.
     *
     * @since    5.2.40
     * @access   private
     * @var      Smart_Variations_Images_Public|null $plugin_public The public handler instance.
     */
    private $plugin_public = null;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->version = SMART_VARIATIONS_IMAGES_VERSION;
        $this->plugin_name = 'smart-variations-images';
        $this->load_dependencies();
        $this->options = $this->run_reduxMigration();
        // Without usable premium code, PRO-only options fall back to their
        // defaults at runtime, whatever an earlier licence saved.
        if ( empty( $this->options->status ) ) {
            $this->apply_free_option_limits();
        }
        $this->options->rtl = is_rtl();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Reset PRO-only options to their defaults when premium code cannot be used.
     *
     * A value saved while the licence was active (or imported from another
     * install) must not drive the front end after the licence lapses, so the
     * in-memory options object is reset. The stored settings are left untouched
     * so nothing is lost when the licence is restored.
     *
     * @since 5.3.0
     */
    private function apply_free_option_limits() : void {
        if ( !method_exists( $this->wpsf, 'get_locked_field_defaults' ) ) {
            return;
        }
        foreach ( $this->wpsf->get_locked_field_defaults() as $id => $default ) {
            if ( property_exists( $this->options, $id ) ) {
                $this->options->{$id} = $default;
            }
        }
    }

    /**
     * Convenience wrapper to check if a given option is enabled.
     *
     * @since 5.2.20
     * @param string $option  Option property to inspect.
     * @param bool   $default Optional fallback if the property does not exist.
     * @return bool
     */
    private function is_option_enabled( string $option, bool $default = false ) : bool {
        if ( !is_object( $this->options ) ) {
            return $default;
        }
        if ( !property_exists( $this->options, $option ) ) {
            return $default;
        }
        return (bool) $this->options->{$option};
    }

    /**
     * Get the main instance of the plugin.
     *
     * Ensures only one instance of the class is loaded or can be loaded (Singleton pattern).
     *
     * @since    1.0.0
     * @return   Smart_Variations_Images The single instance of the class.
     */
    public static function instance() : Smart_Variations_Images {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Normalise stored SVI galleries to attachment IDs.
     *
     * `woosvi_slug` normally stores `imgs` as attachment IDs, but CSV exports
     * and third-party importers can leave the exported `{id, url, full_url}`
     * entries in place. Resolve them once so every consumer (admin panel,
     * front-end gallery, export) works with the same shape. A JSON string, as
     * stored right after a CSV import, is decoded too.
     *
     * @since    5.3.2
     * @param    mixed $galleries Raw `woosvi_slug` value.
     * @return   array Galleries whose `imgs` entries are attachment IDs.
     */
    public static function normalize_galleries( $galleries ) : array {
        if ( is_string( $galleries ) ) {
            $decoded = json_decode( $galleries, true );
            $galleries = ( is_array( $decoded ) ? $decoded : [] );
        }
        if ( !is_array( $galleries ) ) {
            return [];
        }
        $normalized = [];
        foreach ( $galleries as $gallery ) {
            if ( !is_array( $gallery ) ) {
                continue;
            }
            $imgs = ( isset( $gallery['imgs'] ) && is_array( $gallery['imgs'] ) ? $gallery['imgs'] : [] );
            $ids = [];
            foreach ( $imgs as $img ) {
                $id = self::resolve_gallery_image_id( $img );
                if ( $id > 0 ) {
                    $ids[] = $id;
                }
            }
            $gallery['imgs'] = array_values( array_unique( $ids ) );
            $normalized[] = $gallery;
        }
        return $normalized;
    }

    /**
     * Re-link a stored gallery key to the attribute key WooCommerce uses.
     *
     * Gallery keys are attribute value slugs. A slug with a character outside
     * ASCII (the double prime in 18″, an emoji, a symbol) is stored by
     * WordPress percent-encoded (`18%e2%80%b3-x-24%e2%80%b3`), while the admin
     * screens show it decoded. Earlier builds cleaned that key with
     * sanitize_text_field(), which deletes every `%xx` octet, so the gallery
     * was saved as `18-x-24-…` and could never match its value again.
     *
     * The exact key always wins. When it is not found, keys are compared after
     * removing the octets from both sides, which repairs galleries saved by
     * those builds. A stripped form is only accepted when it points at a
     * single value, so two different values can never be cross-linked.
     *
     * @since    5.3.8
     * @param    string $key       Stored gallery key.
     * @param    array  $available Attribute keys available on the product.
     * @return   string|null Canonical attribute key, or null when unresolved.
     */
    public static function resolve_gallery_key( string $key, array $available ) : ?string {
        $key = trim( $key );
        if ( '' === $key ) {
            return null;
        }
        if ( array_key_exists( $key, $available ) ) {
            return $key;
        }
        $lower = strtolower( $key );
        foreach ( array_keys( $available ) as $candidate ) {
            if ( strtolower( (string) $candidate ) === $lower ) {
                return (string) $candidate;
            }
        }
        $needle = self::strip_gallery_key_octets( $key );
        $matches = [];
        foreach ( array_keys( $available ) as $candidate ) {
            if ( self::strip_gallery_key_octets( (string) $candidate ) === $needle ) {
                $matches[] = (string) $candidate;
            }
        }
        return ( 1 === count( $matches ) ? $matches[0] : null );
    }

    /**
     * Remove the percent-encoded octets from a gallery key.
     *
     * @since    5.3.8
     * @param    string $key Gallery key.
     * @return   string Lowercase key without `%xx` sequences.
     */
    private static function strip_gallery_key_octets( string $key ) : string {
        return strtolower( preg_replace( '/%[a-f0-9]{2}/i', '', $key ) );
    }

    /**
     * Resolve a single gallery `imgs` entry to an attachment ID.
     *
     * @since    5.3.2
     * @param    mixed $img Attachment ID or an `{id, url, full_url}` entry.
     * @return   int Attachment ID, or 0 when it cannot be resolved.
     */
    private static function resolve_gallery_image_id( $img ) : int {
        if ( is_numeric( $img ) ) {
            return (int) $img;
        }
        if ( !is_array( $img ) ) {
            return 0;
        }
        $filename = ( isset( $img['url'] ) ? sanitize_file_name( wp_basename( (string) $img['url'] ) ) : '' );
        $id = 0;
        // The export carries the source URL, which only resolves when the same
        // media item exists on this site.
        if ( !empty( $img['full_url'] ) ) {
            $id = (int) attachment_url_to_postid( (string) $img['full_url'] );
        }
        if ( $id <= 0 && '' !== $filename ) {
            $id = self::attachment_id_by_filename( $filename );
        }
        // Last resort: the exported ID, accepted only when it still points to
        // the same file, so a recycled ID cannot pull an unrelated image in.
        if ( $id <= 0 && !empty( $img['id'] ) && is_numeric( $img['id'] ) ) {
            $candidate = (int) $img['id'];
            $candidate_file = sanitize_file_name( wp_basename( (string) wp_get_attachment_url( $candidate ) ) );
            if ( '' === $filename || $filename === $candidate_file ) {
                $id = $candidate;
            }
        }
        return $id;
    }

    /**
     * Find an attachment ID from its base file name.
     *
     * @since    5.3.2
     * @param    string $filename Base file name.
     * @return   int Attachment ID, or 0 when not found.
     */
    private static function attachment_id_by_filename( string $filename ) : int {
        global $wpdb;
        if ( '' === $filename ) {
            return 0;
        }
        $id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND (meta_value = %s OR meta_value LIKE %s) ORDER BY post_id ASC LIMIT 1", $filename, '%/' . $wpdb->esc_like( $filename ) ) );
        return ( $id > 0 ? $id : 0 );
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     * - Smart_Variations_Images_Loader: Orchestrates the hooks of the plugin.
     * - Smart_Variations_Images_i18n: Defines internationalization functionality.
     * - Smart_Variations_Images_Admin: Defines all hooks for the admin area.
     * - Smart_Variations_Images_Public: Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() : void {
        /**
         * The class responsible for adding the options core plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/library/wp-settings-framework/wp-settings-framework.php';
        $this->wpsf = new WordPressSettingsFrameworkSVI(plugin_dir_path( dirname( __FILE__ ) ) . 'includes/library/wp-settings-framework/settings/svi-settings.php', 'woosvi_options');
        // Add admin menu
        add_action( 'admin_menu', [$this, 'add_settings_page'], 20 );
        add_filter( $this->wpsf->get_option_group() . '_settings_validate', [$this, 'validate_settings'] );
        /**
         * The class responsible for orchestrating the actions and filters of the core plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-smart-variations-images-loader.php';
        /**
         * The class responsible for defining internationalization functionality of the plugin.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-smart-variations-images-i18n.php';
        /**
         * The classes responsible for rendering HTML tags efficiently.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/library/php-html-generator/Markup.php';
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/library/php-html-generator/HtmlTag.php';
        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-smart-variations-images-admin.php';
        /**
         * The class responsible for defining all actions that occur in the public-facing side of the site.
         */
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-smart-variations-images-public.php';
        $this->loader = new Smart_Variations_Images_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Smart_Variations_Images_i18n class to set the domain and register the hook with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() : void {
        $plugin_i18n = new Smart_Variations_Images_i18n();
        $plugin_i18n->load_plugin_textdomain();
    }

    /**
     * Register all hooks related to the admin area functionality of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() : void {
        $plugin_admin = new Smart_Variations_Images_Admin($this->get_plugin_name(), $this->get_version(), $this->options);
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action(
            'woocommerce_variation_options',
            $plugin_admin,
            'variation_btn_builder',
            10,
            3
        );
        $this->loader->add_filter( 'woocommerce_product_data_tabs', $plugin_admin, 'images_section' );
        $panels = ( $this->version_check() ? 'woocommerce_product_write_panels' : 'woocommerce_product_data_panels' );
        $this->loader->add_action( $panels, $plugin_admin, 'images_settings' );
        $this->loader->add_action( 'wp_ajax_woosvi_esc_html', $plugin_admin, 'woosvi_esc_html' );
        $this->loader->add_action( 'wp_ajax_woosvi_reloadselect', $plugin_admin, 'reloadSelect_json' );
        $this->loader->add_action(
            'woocommerce_product_options_advanced',
            $plugin_admin,
            'sviDisableProduct_advancedTab',
            10,
            0
        );
        $this->loader->add_action( 'woocommerce_process_product_meta', $plugin_admin, 'sviSaveData' );
        if ( svi_fs()->is_free_plan() ) {
            $this->loader->add_filter( 'woocommerce_product_import_process_item_data', $plugin_admin, 'wc_ignore_svimeta_in_import' );
        }
        $this->loader->add_filter(
            'woocommerce_product_export_meta_value',
            $plugin_admin,
            'woo_handle_export',
            10,
            4
        );
    }

    /**
     * Register premium-only admin hooks.
     *
     * @since 5.2.20
     * @param Smart_Variations_Images_Admin $plugin_admin Admin handler instance.
     */
    private function register_premium_admin_hooks( Smart_Variations_Images_Admin $plugin_admin ) : void {
        $this->loader->add_action(
            'woocommerce_product_import_inserted_product_object',
            $plugin_admin,
            'handleImports__premium_only',
            10,
            2
        );
        if ( $this->is_option_enabled( 'sviemailadmin' ) ) {
            $this->loader->add_filter(
                'woocommerce_admin_order_item_thumbnail',
                $plugin_admin,
                'filter_woocommerce_admin_order_item_thumbnail__premium_only',
                10,
                3
            );
        }
    }

    /**
     * Register all hooks related to the public-facing functionality of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() : void {
        if ( !property_exists( $this->options, 'default' ) || !$this->options->default ) {
            return;
        }
        $plugin_public = new Smart_Variations_Images_Public($this->get_plugin_name(), $this->get_version(), $this->options);
        $this->plugin_public = $plugin_public;
        if ( $this->is_option_enabled( 'placeholder' ) ) {
            $this->options->placeholder_img = $plugin_public->imgtagger( wc_placeholder_img( $this->options->main_imagesize ) );
        }
        $this->loader->add_action(
            'wp_enqueue_scripts',
            $plugin_public,
            'load_scripts',
            99999
        );
        // Block themes render the template before wp_head(), so handles must be registered earlier.
        $this->loader->add_action(
            'wp_loaded',
            $plugin_public,
            'register_asset_handles',
            1
        );
        $this->loader->add_filter(
            'wc_get_template',
            $plugin_public,
            'filter_wc_get_template',
            1,
            5
        );
        $this->loader->add_action(
            'after_setup_theme',
            $plugin_public,
            'after_setup_theme',
            20
        );
        $this->loader->add_action(
            'woocommerce_before_single_product',
            $plugin_public,
            'remove_hooks',
            20
        );
        $this->loader->add_action(
            'woocommerce_before_single_product_summary',
            $plugin_public,
            'render_frontend',
            20
        );
        // Divi Builder compatibility - replace Divi's WooCommerce Images module with SVI
        $this->loader->add_filter(
            'et_module_shortcode_output',
            $plugin_public,
            'replace_divi_module_output',
            10,
            3
        );
        add_shortcode( 'svi_wcsc', [$plugin_public, 'render_sc_frontend'] );
        if ( $this->is_option_enabled( 'variation_thumbnails' ) ) {
            $this->loader->add_action(
                'woocommerce_single_variation',
                $plugin_public,
                'render_before_add_to_cart_button',
                5
            );
        }
        $this->loader->add_filter(
            'woocommerce_available_variation',
            $plugin_public,
            'filter_woocommerce_available_variation',
            99,
            2
        );
        $this->register_loop_showcase_hooks( $plugin_public );
        if ( $this->is_option_enabled( 'filter_attribute' ) ) {
            $this->loader->add_filter(
                'woocommerce_product_get_image',
                $plugin_public,
                'filter_main_product_image',
                10,
                5
            );
        }
        $this->loader->add_action( 'wp_ajax_woosvi_slugify', $plugin_public, 'woosvi_slugify' );
        $this->loader->add_action( 'wp_ajax_sviloadProduct', $plugin_public, 'loadProductAjax' );
        $this->loader->add_action( 'wp_ajax_nopriv_sviloadProduct', $plugin_public, 'loadProductAjax' );
        $this->loader->add_action( 'svi_before_images', $plugin_public, 'run_integrations' );
    }

    /**
     * Register loop showcase hooks depending on plan/option availability.
     *
     * @since 5.2.20
     * @param Smart_Variations_Images_Public $plugin_public Public handler instance.
     */
    private function register_loop_showcase_hooks( Smart_Variations_Images_Public $plugin_public ) : void {
        if ( !$this->is_option_enabled( 'loop_showcase' ) ) {
            return;
        }
        $this->loader->add_action(
            'woocommerce_before_shop_loop_item_title',
            $plugin_public,
            'svi_product_tn_images',
            10
        );
    }

    /**
     * Register premium-only public hooks.
     *
     * @since 5.2.20
     * @param Smart_Variations_Images_Public $plugin_public Public handler instance.
     */
    private function register_premium_public_hooks( Smart_Variations_Images_Public $plugin_public ) : void {
        if ( $this->is_option_enabled( 'svicart' ) ) {
            $this->loader->add_filter(
                'woocommerce_cart_item_thumbnail',
                $plugin_public,
                'filter_woocommerce_cart_item_thumbnail__premium_only',
                150,
                3
            );
            $this->loader->add_filter(
                'woocommerce_store_api_cart_item_images',
                $plugin_public,
                'filter_woocommerce_store_api_cart_item_images__premium_only',
                10,
                3
            );
        }
        if ( $this->is_option_enabled( 'sviemail' ) ) {
            $this->loader->add_filter(
                'woocommerce_email_order_items_args',
                $plugin_public,
                'filter_add_images_woocommerce_emails__premium_only',
                100,
                1
            );
            $this->loader->add_filter(
                'woocommerce_order_item_thumbnail',
                $plugin_public,
                'filter_woocommerce_order_item_email_thumbnail__premium_only',
                10,
                2
            );
        }
        if ( $this->is_option_enabled( 'order_details' ) ) {
            $this->loader->add_filter(
                'woocommerce_order_item_name',
                $plugin_public,
                'filter_woocommerce_order_item_thumbnail__premium_only',
                10,
                2
            );
        }
        if ( $this->is_option_enabled( 'quick_view' ) ) {
            remove_action( 'yith_wcqv_product_image', 'woocommerce_show_product_images', 20 );
            $this->loader->add_action(
                'yith_wcqv_product_image',
                $plugin_public,
                'render_frontend',
                20
            );
            $this->loader->add_action(
                'woocommerce_before_single_product_lightbox_summary',
                $plugin_public,
                'render_frontend',
                20
            );
        }
        // Quick View is PRO: its AJAX endpoint only exists with usable premium code.
        $this->loader->add_action( 'wp_ajax_loadProduct', $plugin_public, 'render_quick_view_frontend' );
        $this->loader->add_action( 'wp_ajax_nopriv_loadProduct', $plugin_public, 'render_quick_view_frontend' );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() : void {
        $this->loader->run();
    }

    /**
     * Get the public-facing handler instance.
     *
     * @since    5.2.40
     * @return   Smart_Variations_Images_Public|null The public handler, or null when SVI is disabled.
     */
    public function get_public() : ?Smart_Variations_Images_Public {
        return $this->plugin_public;
    }

    /**
     * Get the name of the plugin used to uniquely identify it within the context of WordPress
     * and to define internationalization functionality.
     *
     * @since    1.0.0
     * @return   string The name of the plugin.
     */
    public function get_plugin_name() : string {
        return $this->plugin_name;
    }

    /**
     * Get the reference to the class that orchestrates the hooks with the plugin.
     *
     * @since    1.0.0
     * @return   Smart_Variations_Images_Loader Orchestrates the hooks of the plugin.
     */
    public function get_loader() : Smart_Variations_Images_Loader {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since    1.0.0
     * @return   string The version number of the plugin.
     */
    public function get_version() : string {
        return $this->version;
    }

    /**
     * Check WooCommerce version.
     *
     * @since    1.0.0
     * @param    string $version The version to compare against (default: '3.0').
     * @return   bool True if the WooCommerce version is less than or equal to the specified version, false otherwise.
     */
    public function version_check( string $version = '3.0' ) : bool {
        if ( class_exists( 'WooCommerce' ) ) {
            global $woocommerce;
            if ( version_compare( $woocommerce->version, $version, '<=' ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add the settings page to the admin menu.
     *
     * @since    1.0.0
     */
    public function add_settings_page() : void {
        $this->wpsf->add_settings_page( [
            'parent_slug' => 'woocommerce',
            'page_title'  => esc_html__( 'Smart Variations Images & Swatches', 'smart-variations-images' ),
            'menu_title'  => esc_html__( 'SVI', 'smart-variations-images' ),
            'capability'  => 'edit_products',
            'page_slug'   => 'woocommerce_svi',
        ] );
    }

    /**
     * Migrate settings from Redux Framework to WordPress Settings Framework.
     *
     * @since    1.0.0
     * @return   stdClass The migrated plugin options.
     */
    public function run_reduxMigration() : stdClass {
        $this->migrateLegacySettings();
        $redux = get_option( 'woosvi_options' );
        $redux_imported = get_option( 'woosvi_options_settings_imported', false );
        if ( $redux && !$redux_imported ) {
            $wpsfsvi = $this->wpsf->get_settings();
            foreach ( $wpsfsvi as $key => $new ) {
                foreach ( $redux as $rx_key => $old ) {
                    if ( $this->str_ends_with( $key, $rx_key ) ) {
                        if ( is_array( $old ) ) {
                            $wpsfsvi[$key] = array_keys( array_filter( $old ) );
                        } else {
                            $wpsfsvi[$key] = $old;
                        }
                    }
                }
            }
            update_option( 'woosvi_options_settings_imported', true );
            update_option( 'woosvi_options_settings', $wpsfsvi );
        }
        return (object) $this->wpsf->get_settings( true );
    }

    /**
     * Move settings stored under bare field ids to the tabbed keys the runtime reads.
     *
     * Releases before the tabbed settings screen saved the option with each field
     * under its own id (e.g. `lens`, `slider_effect`). The settings screen and the
     * runtime only read `<tab>_<section>_<field>`, so those values were silently
     * ignored and the next save replaced them with defaults. Copy them once,
     * leaving the original keys in place so the change can be rolled back.
     *
     * @since 5.2.40
     */
    private function migrateLegacySettings() : void {
        if ( get_option( 'woosvi_options_legacy_migrated' ) ) {
            return;
        }
        $stored = get_option( 'woosvi_options_settings' );
        if ( !is_array( $stored ) || empty( $stored ) ) {
            update_option( 'woosvi_options_legacy_migrated', SMART_VARIATIONS_IMAGES_VERSION );
            return;
        }
        $normalized = $this->wpsf->normalize_settings( $stored );
        if ( $normalized ) {
            update_option( 'woosvi_options_settings', array_merge( $stored, $normalized ) );
        }
        update_option( 'woosvi_options_legacy_migrated', SMART_VARIATIONS_IMAGES_VERSION );
    }

    /**
     * Check if a string ends with a specific substring.
     *
     * @since    1.0.0
     * @param    string $string The string to check.
     * @param    string $endString The substring to look for at the end of the string.
     * @return   bool True if the string ends with the specified substring, false otherwise.
     */
    public function str_ends_with( string $string, string $endString ) : bool {
        $len = strlen( $endString );
        if ( $len === 0 ) {
            return true;
        }
        return substr( $string, -$len ) === $endString;
    }

    /**
     * Validate settings before saving.
     *
     * @since    1.0.0
     * @param    array $input The input settings to validate.
     * @return   array The validated settings.
     */
    public function validate_settings( array $input ) : array {
        // Validation logic can be added here if needed
        return $input;
    }

}
