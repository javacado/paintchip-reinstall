<?php
defined( 'ABSPATH' ) || exit;

/**
 * One adapter per supplier. Each supplier's portal is different, so the only
 * shared contract is: given a SKU, return normalised product data or an error.
 *
 * fetch() must return an array with these keys (missing values as ''):
 *
 *   sku          string  the SKU that was requested
 *   title         string  human-readable product name
 *   description   string  marketing copy, may be empty
 *   upc           string  goes to _wpm_gtin_code
 *   msrp          string  suggested retail, numeric string
 *   image_url     string  absolute URL to the largest available image
 *   categories    array   ordered, broadest first, e.g. ['Paints','Acrylics']
 *   source_url    string  the page this came from, for auditing
 */
interface PCI_Scraper {

	/** Supplier code from the report's Vend column, e.g. 'SS'. */
	public function vend_code();

	/** Human-readable supplier name. */
	public function name();

	/** True when this adapter can currently reach the supplier. */
	public function is_available();

	/**
	 * @param string $sku
	 * @return array|WP_Error
	 */
	public function fetch( $sku );
}
