<?php
/**
 * Gallery importer bootstrap for SVI 5.3.x.
 *
 * Reads variation galleries another plugin left in post meta and writes them
 * into SVI's native `woosvi_slug` structure. Nothing is deleted: the source
 * plugin keeps its data, and every touched product keeps a write-once backup.
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

add_action('init', function () {
    require_once __DIR__ . '/main.php';
}, 5);
