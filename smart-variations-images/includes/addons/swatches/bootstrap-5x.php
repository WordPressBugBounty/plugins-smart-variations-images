<?php
/**
 * Swatches module bootstrap for SVI 5.2.x.
 *
 * The module was written against the v6 code base. This file gives it the
 * pieces it needs from 5.2.x: the option helpers, the settings tab in the
 * wp-settings-framework, and the guard that pauses the legacy Wcsvfs renderer
 * so a product never shows two rows of swatches.
 *
 * @package Smart_Variations_Images
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Read a swatch/plugin option from the 5.2.x option store.
 *
 * Mirrors the v6 helper so the module code stays identical in both versions.
 *
 * @param string $key     Option key.
 * @param mixed  $default Fallback value.
 * @return mixed
 */
if (!function_exists('svi_get_option')) {
    function svi_get_option($key, $default = null)
    {
        static $options = null;

        if (null === $options) {
            $options = svi_swatches_option_map();
        }

        return array_key_exists($key, $options) ? $options[$key] : $default;
    }
}

/**
 * The single on/off switch for swatches, owned by the Global tab.
 *
 * `default_swatches` predates this module: it is what the legacy Wcsvfs renderer
 * read at boot, and it is still the only switch the settings screens show. The
 * module follows it instead of keeping a second toggle that could disagree.
 *
 * @return bool
 */
function svi_swatches_master_enabled()
{
    static $enabled = null;

    if (null !== $enabled) {
        return $enabled;
    }

    $stored = get_option('woosvi_options_settings', array());
    $stored = is_object($stored) ? get_object_vars($stored) : (array) $stored;

    if (array_key_exists('main_section_global_default_swatches', $stored)) {
        $enabled = (bool) $stored['main_section_global_default_swatches'];

        return $enabled;
    }

    $legacy = get_option('woosvi_options', array());
    $legacy = is_object($legacy) ? get_object_vars($legacy) : (array) $legacy;

    $enabled = !empty($legacy['default_swatches']);

    return $enabled;
}

/**
 * Start the settings screen with the Swatches tab already hidden.
 *
 * The admin script keeps it in sync while the switch is flipped; this only
 * stops the tab from flashing for a store that has swatches turned off.
 *
 * @param string $classes Body classes.
 * @return string
 */
add_filter('admin_body_class', function ($classes) {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;

    if (!$screen || false === strpos((string) $screen->id, 'woosvi-options-settings')) {
        return $classes;
    }

    if (svi_swatches_master_enabled()) {
        return $classes;
    }

    return trim($classes . ' svi-swatches-off');
});

/**
 * Field ids the module owns.
 *
 * @return string[]
 */
function svi_swatches_field_ids()
{
    static $ids = null;

    if (null !== $ids) {
        return $ids;
    }

    $ids = array();

    if (class_exists('SVI_Swatches_Settings')) {
        $tabs = SVI_Swatches_Settings::register_tab(array());

        foreach ($tabs['swatches']['sections'] as $section) {
            foreach ($section['fields'] as $field) {
                $ids[] = $field['id'];
            }
        }
    }

    return $ids;
}

/**
 * Map stored settings onto the bare field ids the module asks for.
 *
 * The settings screen saves `<tab>_<section>_<field>`, so each stored key is
 * matched by suffix. The tabbed store is read last so it wins over the legacy
 * flat one.
 *
 * @return array
 */
function svi_swatches_option_map()
{
    static $map = null;

    if (null !== $map) {
        return $map;
    }

    $map  = array();
    $ids  = svi_swatches_field_ids();
    $keys = array('woosvi_options', 'woosvi_options_settings');

    foreach ($keys as $option_name) {
        $stored = get_option($option_name, array());
        $stored = is_object($stored) ? get_object_vars($stored) : (array) $stored;

        foreach ($stored as $name => $value) {
            $map[$name] = $value;
        }

        foreach ($ids as $id) {
            if (array_key_exists($id, $stored)) {
                $map[$id] = $stored[$id];
                continue;
            }

            $suffix = '_' . $id;

            foreach ($stored as $name => $value) {
                if (substr($name, -strlen($suffix)) === $suffix) {
                    $map[$id] = $value;
                    break;
                }
            }
        }
    }

    // `swatches_enabled` is not a setting of its own: it mirrors the Global switch.
    $map['swatches_enabled'] = svi_swatches_master_enabled() ? '1' : '0';

    return $map;
}

/**
 * Persist a single plugin option.
 *
 * @param string $key   Option key.
 * @param mixed  $value Value.
 * @return bool
 */
if (!function_exists('svi_update_option')) {
    function svi_update_option($key, $value)
    {
        $stored = get_option('woosvi_options', array());
        $stored = is_object($stored) ? get_object_vars($stored) : (array) $stored;

        $stored[$key] = $value;

        return update_option('woosvi_options', $stored);
    }
}

/**
 * Boot the module once WooCommerce is available.
 */
add_action('init', function () {
    if (!class_exists('WooCommerce')) {
        return;
    }

    require_once __DIR__ . '/main.php';
}, 5);

/**
 * Built-in defaults for every swatch setting, keyed like the tabbed store.
 *
 * @return array<string, mixed>
 */
function svi_swatches_default_option_values()
{
    static $defaults = null;

    if (null !== $defaults) {
        return $defaults;
    }

    $defaults = array();

    if (!class_exists('SVI_Swatches_Settings')) {
        return $defaults;
    }

    $tabs = SVI_Swatches_Settings::register_tab(array());

    foreach ($tabs['swatches']['sections'] as $section) {
        foreach ($section['fields'] as $field) {
            $default = array_key_exists('default', $field) ? $field['default'] : null;

            if (null === $default || '' === $default) {
                continue;
            }

            $defaults[sprintf('swatches_swatches_%s_%s', $section['id'], $field['id'])] = array(
                'field' => $field['id'],
                'value' => is_bool($default) ? ($default ? '1' : '0') : $default,
            );
        }
    }

    return $defaults;
}

/**
 * Fill settings this screen has never saved with their built-in default.
 *
 * The settings framework reads the option while it builds its fields, before
 * seeding can run, so without this the first visit after an upgrade would show
 * every toggle as off while the frontend already used the defaults. Saving that
 * screen would then switch swatches off.
 */
add_filter('option_woosvi_options_settings', function ($value) {
    $defaults = svi_swatches_default_option_values();

    if (!$defaults) {
        return $value;
    }

    $options = is_object($value) ? get_object_vars($value) : (array) $value;
    $missing = array_diff_key($defaults, $options);

    if (!$missing) {
        return $value;
    }

    $legacy = get_option('woosvi_options', array());
    $legacy = is_object($legacy) ? get_object_vars($legacy) : (array) $legacy;

    foreach ($missing as $key => $meta) {
        // A value already saved through svi_update_option() wins over the default.
        if (!array_key_exists($meta['field'], $legacy)) {
            $options[$key] = $meta['value'];
        }
    }

    return is_object($value) ? (object) $options : $options;
}, 5);

/**
 * Pause the legacy Wcsvfs renderer on the frontend.
 *
 * Wcsvfs hooks woocommerce_dropdown_variation_attribute_options_html at
 * priority 100 and wraps whatever it finds, so a color/image/label attribute
 * printed both engines and its wrapper pushed the label out of alignment.
 *
 * Its hooks are remove()d instead of faking its `default_swatches` option: the
 * option is read and written by the settings screen, WP-CLI and any other
 * read-modify-write code, and rewriting it here made those writes drop the
 * value. Removing the callback leaves stored settings untouched. The admin term
 * fields of Wcsvfs stay available.
 */
add_action('init', function () {
    if (is_admin() && !wp_doing_ajax()) {
        return;
    }

    if (!class_exists('Wcsvfs_Public')) {
        return;
    }

    $hooks = array(
        'woocommerce_dropdown_variation_attribute_options_html' => array('get_swatch_html', 100),
        'wp_enqueue_scripts'                                    => array('enqueue_styles', 100),
        'wcsvfs_swatch_html'                                    => array('swatch_html', 5),
    );

    global $wp_filter;

    foreach ($hooks as $tag => $hook) {
        list($method, $priority) = $hook;

        if (empty($wp_filter[$tag]->callbacks[$priority])) {
            continue;
        }

        foreach ($wp_filter[$tag]->callbacks[$priority] as $callback) {
            $function = isset($callback['function']) ? $callback['function'] : null;

            if (!is_array($function) || !is_object($function[0]) || !($function[0] instanceof Wcsvfs_Public)) {
                continue;
            }

            if ($method === $function[1]) {
                remove_filter($tag, $function, $priority);
            }
        }
    }

    $GLOBALS['svi_swatches_legacy_paused'] = true;
}, 20);

/**
 * Register the module settings inside the 5.2.x settings framework.
 *
 * The module describes its tab in one array (used by the v6 framework) and this
 * mapper turns it into the sections/fields the 5.2.x framework expects.
 *
 * @param array $settings Settings payload.
 * @return array
 */
function svi_swatches_map_settings($settings)
{
    if (!class_exists('SVI_Swatches_Settings')) {
        return $settings;
    }


    $tabs = SVI_Swatches_Settings::register_tab(array());

    if (empty($tabs['swatches']['sections'])) {
        return $settings;
    }

    $settings['tabs']     = isset($settings['tabs']) ? $settings['tabs'] : array();
    $settings['sections'] = isset($settings['sections']) ? $settings['sections'] : array();

    // Both settings filters run; keep a single Swatches tab.
    foreach ($settings['tabs'] as $tab) {
        if (isset($tab['id']) && 'swatches' === $tab['id']) {
            return $settings;
        }
    }

    $swatches_tab = array(
        'id'    => 'swatches',
        'title' => $tabs['swatches']['label'],
    );

    // Right after the first tab (Global) so it is easy to find.
    array_splice($settings['tabs'], 1, 0, array($swatches_tab));

    $order = 10;

    foreach ($tabs['swatches']['sections'] as $section) {
        $fields = array();

        foreach ($section['fields'] as $field) {
            $mapped = array(
                'id'      => $field['id'],
                'title'   => isset($field['label']) ? $field['label'] : '',
                'default' => isset($field['default']) ? $field['default'] : '',
            );

            switch ($field['type']) {
                case 'html':
                    $mapped['type']  = 'custom';
                    $mapped['title'] = '';
                    // A callback, not a string: the framework runs kses over
                    // string output and would strip the file input.
                    $html            = isset($field['html']) ? $field['html'] : '';
                    $mapped['output'] = static function () use ($html) {
                        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Markup built and escaped by the module.
                    };
                    break;

                case 'select':
                    $mapped['type']    = 'select';
                    // The 5.2.x framework names this key "choices".
                    $mapped['choices'] = isset($field['options']) ? $field['options'] : array();
                    break;

                case 'toggle':
                    $mapped['type'] = 'toggle';
                    break;

                case 'number':
                    $mapped['type'] = 'number';
                    break;

                case 'color':
                    $mapped['type'] = 'color';
                    break;

                default:
                    $mapped['type'] = 'text';
                    break;
            }

            if (!empty($field['description'])) {
                $mapped['desc'] = $field['description'];
            }

            $fields[] = $mapped;
        }

        $settings['sections'][] = array(
            'section_title' => $section['title'],
            'tab_id'        => 'swatches',
            'section_id'    => 'swatches_' . $section['id'],
            'section_order' => $order,
            'fields'        => $fields,
        );

        $order += 10;
    }

    return $settings;
}

/**
 * Keep swatch settings that the current form did not submit.
 *
 * While the tab is hidden its fields are not in the form, and the settings
 * framework replaces the whole option with what was posted. Without this,
 * turning swatches off and saving would wipe every swatch setting.
 *
 * @param mixed $value     New option value.
 * @param mixed $old_value Stored value.
 * @return mixed
 */
add_filter('pre_update_option_woosvi_options_settings', function ($value, $old_value) {
    $old = is_object($old_value) ? get_object_vars($old_value) : (array) $old_value;
    $new = is_object($value) ? get_object_vars($value) : (array) $value;

    $kept = array();

    foreach ($old as $key => $stored) {
        if (0 === strpos($key, 'swatches_swatches_') && !array_key_exists($key, $new)) {
            $kept[$key] = $stored;
        }
    }

    if (!$kept) {
        return $value;
    }

    $new += $kept;

    return is_object($value) ? (object) $new : $new;
}, 10, 2);

/**
 * Copy values saved before this screen existed into the tabbed store.
 *
 * Without this the screen would show defaults while the frontend kept using the
 * older flat values.
 */
function svi_swatches_seed_settings()
{
    $version = defined('SMART_VARIATIONS_IMAGES_VERSION') ? (string) SMART_VARIATIONS_IMAGES_VERSION : '1';

    if ($version === (string) get_option('svi_swatches_settings_seeded', '')) {
        return;
    }

    $stored = get_option('woosvi_options_settings', array());
    $stored = is_object($stored) ? get_object_vars($stored) : (array) $stored;
    $legacy = get_option('woosvi_options', array());
    $legacy = is_object($legacy) ? get_object_vars($legacy) : (array) $legacy;

    $settings = $stored;

    foreach (svi_swatches_default_option_values() as $key => $meta) {
        if (array_key_exists($key, $settings)) {
            continue;
        }

        // A store upgraded from the legacy engine keeps its old values; anything
        // else gets the built-in default, so the screen matches the frontend.
        $settings[$key] = array_key_exists($meta['field'], $legacy) ? $legacy[$meta['field']] : $meta['value'];
    }

    update_option('woosvi_options_settings', $settings);
    update_option('svi_swatches_settings_seeded', $version);
}

add_action('admin_init', function () {
    if (!class_exists('SVI_Swatches_Settings')) {
        return;
    }

    svi_swatches_seed_settings();
});

add_filter('wpsfsvi_register_settings_woosvi_options', 'svi_swatches_map_settings', 20);
add_filter('wpsfsvi_register_settings_clean_woosvi_options', 'svi_swatches_map_settings', 20);
