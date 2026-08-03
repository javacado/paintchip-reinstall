<?php
defined( 'ABSPATH' ) || exit;

/**
 * Three tables:
 *
 *   pci_runs    - one row per uploaded report
 *   pci_items   - one row per distinct SKU in that report, with its resolved action
 *   pci_journal - one row per product actually touched, holding a before-snapshot
 *
 * The journal is what makes rollback possible. Nothing is ever written to a
 * product without a snapshot being written first, in the same request.
 */
class PCI_Schema {

	const DB_VERSION = '1.2.0';
	const OPT_DB     = 'pci_db_version';

	public static function table( $name ) {
		global $wpdb;
		return $wpdb->prefix . 'pci_' . $name;
	}

	public static function maybe_upgrade() {
		if ( get_option( self::OPT_DB ) !== self::DB_VERSION ) {
			self::install();
		}
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$runs    = self::table( 'runs' );
		$items   = self::table( 'items' );
		$journal = self::table( 'journal' );

		dbDelta( "CREATE TABLE {$runs} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			filename VARCHAR(255) NOT NULL DEFAULT '',
			stored_path VARCHAR(500) NOT NULL DEFAULT '',
			file_hash CHAR(40) NOT NULL DEFAULT '',
			report_date VARCHAR(40) NOT NULL DEFAULT '',
			status VARCHAR(20) NOT NULL DEFAULT 'parsed',
			stats LONGTEXT NULL,
			created_at DATETIME NOT NULL,
			created_by BIGINT UNSIGNED NOT NULL DEFAULT 0,
			applied_at DATETIME NULL,
			applied_by BIGINT UNSIGNED NULL,
			rolled_back_at DATETIME NULL,
			rolled_back_by BIGINT UNSIGNED NULL,
			PRIMARY KEY (id),
			KEY status (status),
			KEY file_hash (file_hash)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$items} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			run_id BIGINT UNSIGNED NOT NULL,
			sku VARCHAR(100) NOT NULL DEFAULT '',
			vend VARCHAR(10) NOT NULL DEFAULT '',
			item_id VARCHAR(40) NOT NULL DEFAULT '',
			description VARCHAR(190) NOT NULL DEFAULT '',
			dept VARCHAR(20) NOT NULL DEFAULT '',
			file_qty INT NOT NULL DEFAULT 0,
			file_max INT NOT NULL DEFAULT 0,
			file_min INT NOT NULL DEFAULT 0,
			file_price DECIMAL(12,2) NULL,
			file_cost DECIMAL(12,2) NULL,
			row_count SMALLINT NOT NULL DEFAULT 1,
			action VARCHAR(24) NOT NULL DEFAULT '',
			flag_reason VARCHAR(190) NOT NULL DEFAULT '',
			product_id BIGINT UNSIGNED NULL,
			product_title TEXT NULL,
			cur_qty VARCHAR(20) NULL,
			cur_price VARCHAR(20) NULL,
			cur_status VARCHAR(20) NULL,
			cur_manage VARCHAR(10) NULL,
			raw LONGTEXT NULL,
			PRIMARY KEY (id),
			KEY run_action (run_id, action),
			KEY run_sku (run_id, sku),
			KEY run_vend (run_id, vend)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$journal} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			run_id BIGINT UNSIGNED NOT NULL,
			product_id BIGINT UNSIGNED NOT NULL,
			sku VARCHAR(100) NOT NULL DEFAULT '',
			action VARCHAR(24) NOT NULL DEFAULT '',
			snapshot LONGTEXT NULL,
			applied LONGTEXT NULL,
			restored TINYINT(1) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY run (run_id),
			KEY product (product_id)
		) {$charset};" );

		$catalog = $wpdb->prefix . 'pci_catalog';
		dbDelta( "CREATE TABLE {$catalog} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			sku VARCHAR(100) NOT NULL DEFAULT '',
			vend VARCHAR(10) NOT NULL DEFAULT '',
			title VARCHAR(250) NOT NULL DEFAULT '',
			upc VARCHAR(20) NOT NULL DEFAULT '',
			msrp DECIMAL(12,2) NULL,
			net DECIMAL(12,2) NULL,
			qoh INT NOT NULL DEFAULT 0,
			image_url VARCHAR(500) NOT NULL DEFAULT '',
			categories TEXT NULL,
			raw LONGTEXT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY sku (sku),
			KEY vend (vend),
			KEY upc (upc)
		) {$charset};" );

		$crawl = $wpdb->prefix . 'pci_crawl';
		dbDelta( "CREATE TABLE {$crawl} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			url VARCHAR(500) NOT NULL DEFAULT '',
			url_hash CHAR(40) NOT NULL DEFAULT '',
			kind VARCHAR(20) NOT NULL DEFAULT 'directory',
			label VARCHAR(250) NOT NULL DEFAULT '',
			depth SMALLINT NOT NULL DEFAULT 0,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			items_found INT NOT NULL DEFAULT 0,
			message VARCHAR(250) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL,
			crawled_at DATETIME NULL,
			PRIMARY KEY (id),
			UNIQUE KEY url_hash (url_hash),
			KEY status (status)
		) {$charset};" );

		update_option( self::OPT_DB, self::DB_VERSION );
	}
}
