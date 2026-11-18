<?php
/**
 * Boot the swatch importer.
 *
 * @package Smart_Variations_Images\Addons\Swatches
 */

if (!defined('ABSPATH')) {
	exit;
}

require_once __DIR__ . '/class-svi-swatch-import-source.php';
require_once __DIR__ . '/class-svi-swatch-import-writer.php';
require_once __DIR__ . '/class-svi-swatch-import-runner.php';
require_once __DIR__ . '/sources/class-svi-swatch-import-source-woo-variation-swatches.php';
require_once __DIR__ . '/sources/class-svi-swatch-import-source-variation-swatches-woo.php';
require_once __DIR__ . '/sources/class-svi-swatch-import-source-wcboost-variation-swatches.php';
require_once __DIR__ . '/sources/class-svi-swatch-import-source-variation-swatches-for-woocommerce.php';
require_once __DIR__ . '/sources/class-svi-swatch-import-source-product-variations-swatches.php';
require_once __DIR__ . '/sources/class-svi-swatch-import-source-woo-product-variation-swatches.php';
require_once __DIR__ . '/sources/class-svi-swatch-import-source-product-variation-swatches.php';
require_once __DIR__ . '/sources/class-svi-swatch-import-source-wpc-variation-swatches.php';
require_once __DIR__ . '/class-svi-swatch-import-admin.php';
require_once __DIR__ . '/class-svi-swatch-import-cli.php';

if (!function_exists('svi_swatch_import_default_sources')) {
	/**
	 * Built-in import sources.
	 *
	 * @param SVI_Swatch_Import_Source[] $sources Registered sources.
	 * @return SVI_Swatch_Import_Source[]
	 */
	function svi_swatch_import_default_sources($sources)
	{
		$sources = is_array($sources) ? $sources : [];

		$sources[] = new SVI_Swatch_Import_Source_Woo_Variation_Swatches();
		$sources[] = new SVI_Swatch_Import_Source_Variation_Swatches_Woo();
		$sources[] = new SVI_Swatch_Import_Source_WCBoost_Variation_Swatches();
		$sources[] = new SVI_Swatch_Import_Source_Variation_Swatches_For_WooCommerce();
		$sources[] = new SVI_Swatch_Import_Source_Product_Variations_Swatches();
		$sources[] = new SVI_Swatch_Import_Source_Woo_Product_Variation_Swatches();
		$sources[] = new SVI_Swatch_Import_Source_Product_Variation_Swatches();
		$sources[] = new SVI_Swatch_Import_Source_WPC_Variation_Swatches();

		return $sources;
	}
}

add_filter('svi_swatch_import_sources', 'svi_swatch_import_default_sources');

SVI_Swatch_Import_Admin::init();
SVI_Swatch_Import_CLI::init();
