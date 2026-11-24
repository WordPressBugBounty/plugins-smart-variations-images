<?php
/**
 * Galleries mirror bootstrap for SVI 5.3.x.
 *
 * Keeps the legacy `woosvi_slug` write path untouched and mirrors every write
 * into the `_svi_galleries` schema-3 payload, so a later release that reads
 * schema 3 already finds the data on every product.
 *
 * The mirror is silent: no settings field, no notice, no storefront change.
 * Mode lives in the `svi_galleries_mirror` option (off | write | write+backfill).
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WooCommerce')) {
    return;
}

/**
 * Boot the mirror once WooCommerce is available.
 *
 * Same timing as the swatches module: `init` @5, so admin saves, REST imports
 * and WP-Cron backfill all run after the observer is registered.
 */
add_action('init', function () {
    require_once __DIR__ . '/main.php';
}, 5);
