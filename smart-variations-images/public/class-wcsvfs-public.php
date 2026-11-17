<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Legacy WCSVFS class and WooCommerce/WCSVFS integration contracts.

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://www.smart-variations.com
 * @since      1.0.0
 *
 * @package    Wcsvfs
 * @subpackage Wcsvfs/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Wcsvfs
 * @subpackage Wcsvfs/public
 * @author     David Rosendo <david@rosendo.pt>
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Legacy class referenced by the WCSVFS loader.
class Wcsvfs_Public
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * The single instance of the class
     *
     * @var Wcsvfs_Public
     */
    protected static $instance = null;

    /**
     * Main instance
     *
     * @return Wcsvfs_Public
     */
    public static function instance()
    {
        if (null == self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Wcsvfs_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Wcsvfs_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/wcsvfs-public' . SMART_SCRIPT_DEBUG . '.css', array(), $this->version, 'all');
    }

    /**
     * Filter function to add swatches bellow the default selector
     *
     * @param $html
     * @param $args
     *
     * @return string
     */
    public function get_swatch_html($html, $args)
    {
        $swatch_types = WC_SVFS()->types;
        $attr = WC_SVFS()->get_tax_attribute($args['attribute']);
        // Return if this is normal attribute
        if (empty($attr)) {
            return $html;
        }

        if (!array_key_exists($attr->attribute_type, $swatch_types)) {
            return $html;
        }

        // Swatches replace the select box and rely on the SVI front-end handlers.
        // When SVI does not render this product (SVI disabled, or no SVI data and
        // "Disable SVI display" enabled) keep the native dropdown instead of
        // outputting swatches that cannot be clicked.
        if (!$this->svi_renders_product($args['product'] ?? null)) {
            return $html;
        }

        $options = $args['options'];
        $product = $args['product'];
        $attribute = $args['attribute'];
        $class = "variation-selector variation-select-{$attr->attribute_type}";
        $swatches = '';
        if (empty($options) && !empty($product) && !empty($attribute)) {
            $attributes = $product->get_variation_attributes();
            $options = $attributes[$attribute];
        }

        if (array_key_exists($attr->attribute_type, $swatch_types)) {
            if (!empty($options) && $product && taxonomy_exists($attribute)) {
                // Get terms if this is a taxonomy - ordered. We need the names too.
                $terms = wc_get_product_terms($product->get_id(), $attribute, array('fields' => 'all'));

                foreach ($terms as $term) {
                    if (in_array($term->slug, $options)) {
                        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy public WCSVFS extension hook.
                        $swatches .= apply_filters('wcsvfs_swatch_html', '', $term, $attr->attribute_name, $attr->attribute_type, $args);
                    }
                }
            }

            if (!empty($swatches)) {
                $class .= ' wcsvfs-hidden';
 
                $swatches = '<div class="wcsvfs-swatches wcsvfs-swatches-' . $attr->attribute_type . '" data-attribute_name="attribute_' . esc_attr($attribute) . '">' . $swatches . '</div>';
                $html = '<div class="' . esc_attr($class) . '">' . $html . '</div>' . $swatches;
            }
        }

        return $html;
    }

    /**
     * Whether the SVI front-end app will render the given product.
     *
     * Swatch markup is only useful when SVI's scripts run, because the click
     * handlers that keep the hidden attribute select in sync are bound there.
     *
     * @param mixed $product Product being rendered.
     * @return bool True when SVI renders this product.
     */
    private function svi_renders_product($product): bool
    {
        if (!$product instanceof WC_Product) {
            return true;
        }

        if (!function_exists('WC_SVINST')) {
            return false;
        }

        $svi = WC_SVINST();

        if (!is_object($svi) || !method_exists($svi, 'get_public')) {
            return false;
        }

        $svi_public = $svi->get_public();

        if (!$svi_public || !method_exists($svi_public, 'validate_run')) {
            return false;
        }

        return (bool) $svi_public->validate_run($product);
    }

    /**
     * Read a swatch value from either term-meta key family.
     *
     * Values saved by the WCSVFS module live in `color`, `image` and `label`.
     * Values saved by the newer swatch implementation live in `svi_swatch_color`,
     * `svi_swatch_image_id` and `svi_swatch_label`. Reading both keeps the
     * colours and images of existing stores no matter which one created them.
     *
     * @param int    $term_id Term ID.
     * @param string $key     One of color|image|label.
     * @return string Stored value, or an empty string.
     */
    private function get_swatch_meta(int $term_id, string $key): string
    {
        $map = [
            'color' => ['svi_swatch_color', 'color'],
            'image' => ['svi_swatch_image_id', 'image'],
            'label' => ['svi_swatch_label', 'label'],
        ];

        if (!isset($map[$key])) {
            return '';
        }

        foreach ($map[$key] as $meta_key) {
            $value = get_term_meta($term_id, $meta_key, true);

            if (is_scalar($value) && '' !== trim((string) $value)) {
                return trim((string) $value);
            }
        }

        return '';
    }

    /**
     * Print HTML of a single swatch
     *
     * @param $html
     * @param $term
     * @param $type
     * @param $args
     *
     * @return string
     */
    public function swatch_html($html, $term, $tax, $type, $args = false, $image = '')
    {
        $selected = '';
        $filter_value = filter_input(INPUT_GET, 'filter_' . $tax, FILTER_UNSAFE_RAW);
        if (is_string($filter_value) && '' !== $filter_value) {
            $taxonomies = explode(',', sanitize_text_field(wp_unslash($filter_value)));

            $selected = (in_array($term->slug, $taxonomies)) ? 'selected' : '';
        }
        if ($args) {
            $selected = (sanitize_title($args['selected']) == $term->slug) ? 'selected' : '';
        }

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Required WooCommerce compatibility hook.
        $name = esc_html(apply_filters('woocommerce_variation_option_name', $term->name));

        if ($image) {
            $image = json_encode($image);
        }

        // Read the swatch value from either meta key family so stores configured
        // by the newer keys keep their colours, images and labels.
        $swatch_color  = $this->get_swatch_meta($term->term_id, 'color');
        $swatch_image  = absint($this->get_swatch_meta($term->term_id, 'image'));
        $swatch_label  = $this->get_swatch_meta($term->term_id, 'label');

        switch ($type) {
            case 'color':
                $color = sanitize_hex_color($swatch_color);
                $rgb   = $color ? sscanf($color, "#%02x%02x%02x") : false;
                $shade = (is_array($rgb) && 3 === count($rgb)) ? sprintf('rgba(%d,%d,%d,0.5)', $rgb[0], $rgb[1], $rgb[2]) : 'transparent';
                $html = sprintf(
                    '<span data-svfs=\'%s\' class="swatch swatch-color swatch-%s %s" style="background-color:%s;color:%s;" title="%s" data-value="%s">%s</span>',
                    $image,
                    esc_attr($term->slug),
                    $selected,
                    esc_attr($color),
                    esc_attr($shade),
                    esc_attr($name),
                    esc_attr($term->slug),
                    $name
                );
                break;

            case 'image':
                $image = $swatch_image ? wp_get_attachment_image_src($swatch_image) : '';
                $image = $image ? $image[0] : WC()->plugin_url() . '/assets/images/placeholder.png';
                $html = sprintf(
                    '<span data-svfs=\'%s\' class="swatch swatch-image swatch-%s %s" title="%s" data-value="%s"><img src="%s" alt="%s"></span>',
                    $image,
                    esc_attr($term->slug),
                    $selected,
                    esc_attr($name),
                    esc_attr($term->slug),
                    esc_url($image),
                    esc_attr($name)
                );
                break;

            case 'label':
                $label = '' !== $swatch_label ? $swatch_label : $name;
                $html = sprintf(
                    '<span data-svfs=\'%s\' class="swatch swatch-label swatch-%s %s" title="%s" data-value="%s">%s</span>',
                    $image,
                    esc_attr($term->slug),
                    $selected,
                    esc_attr($name),
                    esc_attr($term->slug),
                    esc_html($label)
                );
                break;
        }

        return $html;
    }

}
