<?php
/**
 * Migration hub bootstrap for SVI 5.3.x.
 *
 * One "Migration" settings tab, with vertical sub-tabs. Importer add-ons
 * register a screen on `svi_migration_screens`; this add-on owns the shell.
 *
 * @package Smart_Variations_Images
 * @since   5.3.10
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('init', function () {
    require_once __DIR__ . '/main.php';
}, 5);
