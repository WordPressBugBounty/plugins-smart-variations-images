<?php

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Freemius and legacy plugin bootstrap APIs are public compatibility contracts.
/**
 * Smart Variations Images & Swatches for WooCommerce
 *
 * A WooCommerce extension plugin that allows users to add multiple images to the product gallery
 * and use them as variable product variations images without needing to insert images per variation.
 *
 * @package           Smart_Variations_Images
 * @author            David Rosendo
 * @link              https://www.rosendo.pt
 * @since             5.2.29
 * @license           GPL-2.0+
 * @wordpress-plugin
 *
 * Plugin Name:       Smart Variations Images & Swatches for WooCommerce
 * Plugin URI:        https://www.smart-variations.com/
 * Description:       Every swatch option free: color, image, text and radio. Plus a whole WooCommerce gallery per color, set once and reused by every size.
 * Version:           5.3.2
 * WC requires at least: 8.2
 * WC tested up to:   11.3.0
 * Author:            David Rosendo
 * Author URI:        https://www.rosendo.pt
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       smart-variations-images
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if ( !defined( 'WPINC' ) ) {
    die;
}
/**
 * Define plugin constants.
 */
define( 'SMART_VARIATIONS_IMAGES_VERSION', '5.3.2' );
// Current plugin version.
define( 'WCSVFS_VERSION', '1.0' );
// Version for additional functionality.
define( 'SMART_SVI_DIR_URL', plugin_dir_url( __FILE__ ) );
// Plugin directory URL.
// Load minified assets when not in SCRIPT_DEBUG
define( 'SMART_SCRIPT_DEBUG', ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min' ) );
// Script debugging flag.
define( 'SMART_SVI_OPTIONS_CONTROL', '1' );
// Options control flag.
define( 'SMART_SVI_PROVS', '<span class="wpsfsvi-label label-warning">SVI PRO</span>' );
// Pro version label.
// Check if Freemius is already initialized.
if ( function_exists( 'svi_fs' ) ) {
    svi_fs()->set_basename( false, __FILE__ );
    return;
} else {
    /**
     * Initialize Freemius SDK for premium features and licensing.
     *
     * @return Freemius
     */
    if ( !function_exists( 'svi_fs' ) ) {
        function svi_fs() {
            global $svi_fs;
            if ( !isset( $svi_fs ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/includes/library/freemius/start.php';
                $svi_fs = fs_dynamic_init( array(
                    'id'               => '2228',
                    'slug'             => 'smart-variations-images',
                    'type'             => 'plugin',
                    'public_key'       => 'pk_6a5f1fc0c8ab537a0b07683099ada',
                    'is_premium'       => false,
                    'has_addons'       => false,
                    'has_paid_plans'   => true,
                    'trial'            => array(
                        'days'               => 7,
                        'is_require_payment' => true,
                    ),
                    'menu'             => array(
                        'slug'       => 'woosvi-options-settings',
                        'first-path' => 'admin.php?page=woosvi-options-settings',
                        'support'    => false,
                        'network'    => true,
                        'parent'     => array(
                            'slug' => 'woocommerce',
                        ),
                    ),
                    'is_live'          => true,
                    'is_org_compliant' => true,
                ) );
            }
            return $svi_fs;
        }

        // Initialize Freemius.
        svi_fs();
        // Signal that SDK was initiated.
        do_action( 'svi_fs_loaded' );
    }
}
/**
 * Include custom hooks for Freemius display.
 */
require plugin_dir_path( __FILE__ ) . 'includes/freemius_conditions.php';
/**
 * Black Friday campaign notice.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-smart-variations-images-black-friday.php';
$svi_black_friday = new Smart_Variations_Images_Black_Friday();
$svi_black_friday->run();
/**
 * Activation hook for the plugin.
 *
 * This function runs during plugin activation and sets up necessary configurations.
 */
function activate_smart_variations_images() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-smart-variations-images-activator.php';
    Smart_Variations_Images_Activator::activate();
}

register_activation_hook( __FILE__, 'activate_smart_variations_images' );
/**
 * Include the core plugin class and additional functionality.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-smart-variations-images.php';
require plugin_dir_path( __FILE__ ) . 'includes/class-wcsvfs.php';
/**
 * Begins execution of the plugin.
 *
 * Initializes the main plugin class and runs the plugin.
 */
function run_smart_variations_images() {
    $plugin = new Smart_Variations_Images();
    $plugin->run();
    $wcsvfs = new Wcsvfs($plugin->options);
    $wcsvfs->run();
}

/**
 * Main instance of the Smart_Variations_Images class.
 *
 * @return Smart_Variations_Images
 */
if ( !function_exists( 'WC_SVINST' ) ) {
    function WC_SVINST() {
        return Smart_Variations_Images::instance();
    }

}
/**
 * Main instance of the Wcsvfs class.
 *
 * @return Wcsvfs
 */
if ( !function_exists( 'WC_SVFS' ) ) {
    function WC_SVFS() {
        return Wcsvfs::instance();
    }

}
/**
 * Debugging function to print and die (for administrators only).
 *
 * @param mixed $args The data to debug.
 */
if ( !function_exists( 'fs_dd' ) ) {
    function fs_dd(  $args  ) {
        if ( current_user_can( 'administrator' ) ) {
            // echo "<pre>" . var_export($args, true) . "</pre>";
            die;
        }
    }

}
/**
 * Debugging function to print (for administrators only).
 *
 * @param mixed $args The data to debug.
 */
if ( !function_exists( 'fs_ddd' ) ) {
    function fs_ddd(  $args  ) {
        if ( current_user_can( 'administrator' ) ) {
            // echo "<pre>" . var_export($args, true) . "</pre>";
        }
    }

}
// Run the plugin after all plugins are loaded.
add_action( 'init', 'run_smart_variations_images' );
/**
 * Declare compatibility with WooCommerce custom order tables.
 */
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );
/**
 * Swatches module: new renderer, settings tab and per-product overrides.
 */
require plugin_dir_path( __FILE__ ) . 'includes/addons/swatches/bootstrap-5x.php';