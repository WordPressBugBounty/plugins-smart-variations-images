<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- Freemius callback names are registered as public compatibility APIs.
function my_svi_pricing_js_path($default_pricing_js_path)
{
    return dirname(__FILE__) . '/library/freemius-pricing/freemius-pricing.js';
}

//svi_fs()->add_filter('freemius_pricing_js_path', 'my_svi_pricing_js_path');


if (!function_exists('svi_fs_custom_icon')) {
    function svi_fs_custom_icon()
    {
        return dirname(dirname(__FILE__)) . '/admin/images/svi.png';
    }
}

svi_fs()->add_filter('plugin_icon', 'svi_fs_custom_icon');

if (!function_exists('svi_fs_suport')) {
    /**
     * Support link used by the Freemius "Support" submenu.
     *
     * The free build is supported on WordPress.org, a licensed PRO install on
     * the smart-variations.com support area, and a self-managed PRO build has
     * no support area, so it points to the contact form instead of the
     * homepage the submenu used before.
     *
     * @return string
     */
    function svi_fs_suport()
    {
        if (function_exists('wpsfsvi_svi_support_target')) {
            $target = wpsfsvi_svi_support_target(
                svi_fs()->is_premium(),
                svi_fs()->can_use_premium_code__premium_only()
            );

            return $target['url'];
        }

        if (!svi_fs()->is_premium()) {
            return 'https://wordpress.org/support/plugin/smart-variations-images/';
        }

        return svi_fs()->can_use_premium_code__premium_only()
            ? 'https://www.smart-variations.com/support/'
            : 'https://www.smart-variations.com/contact-us/';
    }
}

svi_fs()->add_filter('support_forum_url', 'svi_fs_suport');

if (!function_exists('svi_fs_support_submenu_title')) {
    function svi_fs_support_submenu_title()
    {
        return __('Support', 'smart-variations-images');
    }
}

svi_fs()->add_filter('support_forum_submenu', 'svi_fs_support_submenu_title');


if (!function_exists('svi_fs_is_submenu_visible')) {
    function svi_fs_is_submenu_visible($is_visible, $submenu_id)
    {
        switch ($submenu_id) {
            case 'support':
                // Every edition has somewhere to send support: the WordPress.org
                // forum for the free build, the support area for PRO and the
                // contact form for self-managed installs.
                $return = true;
                break;
            case 'pricing':
                if (svi_fs()->is_plan('pro', true)) {
                    $return = false;
                } else {
                    $return = $is_visible;
                }
                break;
            case 'contact':

                $return = false;

                break;
            default:
                $return = $is_visible;
        }

        if (!current_user_can('edit_products')) {
            $return = false;
        }

        return $return;
    }
}

svi_fs()->add_filter('is_submenu_visible', 'svi_fs_is_submenu_visible', 10, 2);


function svi_default_currency($currency)
{
    return 'eur';
}

svi_fs()->add_filter('default_currency', 'svi_default_currency');