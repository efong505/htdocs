<?php
/**
 * Database helper — table creation and common queries.
 *
 * @package WP_License_Platform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPLP_DB {

	/**
	 * Create all custom tables on plugin activation.
	 */
	public static function create_tables() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();
		$prefix  = $wpdb->prefix;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// Products
		dbDelta( "CREATE TABLE {$prefix}wplp_products (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			name VARCHAR(255) NOT NULL,
			slug VARCHAR(255) NOT NULL,
			description TEXT,
			version VARCHAR(20) DEFAULT '1.0.0',
			file_path VARCHAR(500),
			license_prefix VARCHAR(10) DEFAULT 'WPLP',
			status ENUM('active','inactive') DEFAULT 'active',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			UNIQUE KEY idx_slug (slug),
			KEY idx_status (status)
		) {$charset};" );

		// Product tiers
		dbDelta( "CREATE TABLE {$prefix}wplp_product_tiers (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			product_id BIGINT UNSIGNED NOT NULL,
			name VARCHAR(100) NOT NULL,
			display_name VARCHAR(100) NOT NULL,
			price DECIMAL(10,2) NOT NULL,
			currency CHAR(3) DEFAULT 'USD',
			billing_period ENUM('monthly','annual','lifetime') DEFAULT 'annual',
			sites_allowed INT UNSIGNED DEFAULT 1,
			is_featured TINYINT(1) DEFAULT 0,
			sort_order INT DEFAULT 0,
			status ENUM('active','inactive') DEFAULT 'active',
			created_at DATETIME NOT NULL,
			KEY idx_product (product_id),
			KEY idx_status (status)
		) {$charset};" );

		// Customers
		dbDelta( "CREATE TABLE {$prefix}wplp_customers (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			wp_user_id BIGINT UNSIGNED DEFAULT NULL,
			email VARCHAR(255) NOT NULL,
			first_name VARCHAR(100),
			last_name VARCHAR(100),
			company VARCHAR(255),
			country_code CHAR(2),
			vat_number VARCHAR(50),
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			UNIQUE KEY idx_email (email),
			KEY idx_wp_user (wp_user_id)
		) {$charset};" );

		// Orders
		dbDelta( "CREATE TABLE {$prefix}wplp_orders (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			order_number VARCHAR(50) NOT NULL,
			customer_id BIGINT UNSIGNED NOT NULL,
			product_id BIGINT UNSIGNED NOT NULL,
			tier_id BIGINT UNSIGNED NOT NULL,
			status ENUM('pending','completed','refunded','failed') DEFAULT 'pending',
			subtotal DECIMAL(10,2) NOT NULL,
			tax_amount DECIMAL(10,2) DEFAULT 0.00,
			tax_rate DECIMAL(5,2) DEFAULT 0.00,
			tax_country CHAR(2),
			total DECIMAL(10,2) NOT NULL,
			currency CHAR(3) DEFAULT 'USD',
			paypal_order_id VARCHAR(100),
			paypal_capture_id VARCHAR(100),
			invoice_number VARCHAR(50),
			ip_address VARCHAR(45),
			billing_country CHAR(2),
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			UNIQUE KEY idx_order_number (order_number),
			KEY idx_customer (customer_id),
			KEY idx_status (status),
			KEY idx_paypal (paypal_order_id),
			KEY idx_created (created_at)
		) {$charset};" );

		// Licenses
		dbDelta( "CREATE TABLE {$prefix}wplp_licenses (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			license_key VARCHAR(50) NOT NULL,
			order_id BIGINT UNSIGNED NOT NULL,
			customer_id BIGINT UNSIGNED NOT NULL,
			product_id BIGINT UNSIGNED NOT NULL,
			tier_id BIGINT UNSIGNED NOT NULL,
			status ENUM('active','expired','revoked','suspended') DEFAULT 'active',
			sites_allowed INT UNSIGNED DEFAULT 1,
			sites_active INT UNSIGNED DEFAULT 0,
			expires_at DATETIME,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			UNIQUE KEY idx_key (license_key),
			KEY idx_customer (customer_id),
			KEY idx_product (product_id),
			KEY idx_status (status),
			KEY idx_expires (expires_at)
		) {$charset};" );

		// Activations
		dbDelta( "CREATE TABLE {$prefix}wplp_activations (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			license_id BIGINT UNSIGNED NOT NULL,
			site_url VARCHAR(500) NOT NULL,
			activated_at DATETIME NOT NULL,
			last_checked DATETIME,
			KEY idx_license (license_id)
		) {$charset};" );

		// VAT evidence
		dbDelta( "CREATE TABLE {$prefix}wplp_vat_evidence (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			order_id BIGINT UNSIGNED NOT NULL,
			evidence_type VARCHAR(50) NOT NULL,
			country_code CHAR(2) NOT NULL,
			raw_data TEXT,
			created_at DATETIME NOT NULL,
			KEY idx_order (order_id)
		) {$charset};" );

		update_option( 'wplp_db_version', WPLP_VERSION );
	}

	/**
	 * Drop all custom tables on uninstall.
	 */
	public static function drop_tables() {
		global $wpdb;
		$prefix = $wpdb->prefix;

		$tables = array(
			'wplp_vat_evidence',
			'wplp_activations',
			'wplp_licenses',
			'wplp_orders',
			'wplp_customers',
			'wplp_product_tiers',
			'wplp_products',
		);

		foreach ( $tables as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS {$prefix}{$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		delete_option( 'wplp_db_version' );
	}

	// ─── Product Queries ─────────────────────────────

	public static function get_products( $status = 'active' ) {
		global $wpdb;
		if ( empty( $status ) ) {
			return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wplp_products ORDER BY name" );
		}
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wplp_products WHERE status = %s ORDER BY name",
			$status
		) );
	}

	public static function get_product( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wplp_products WHERE id = %d",
			$id
		) );
	}

	public static function get_product_by_slug( $slug ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wplp_products WHERE slug = %s",
			$slug
		) );
	}

	// ─── Tier Queries ────────────────────────────────

	public static function get_tiers( $product_id, $status = 'active' ) {
		global $wpdb;
		if ( empty( $status ) ) {
			return $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wplp_product_tiers WHERE product_id = %d ORDER BY sort_order, price",
				$product_id
			) );
		}
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wplp_product_tiers WHERE product_id = %d AND status = %s ORDER BY sort_order, price",
			$product_id,
			$status
		) );
	}

	public static function get_tier( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wplp_product_tiers WHERE id = %d",
			$id
		) );
	}

	// ─── Stats ───────────────────────────────────────

	public static function get_order_count( $status = 'completed' ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}wplp_orders WHERE status = %s",
			$status
		) );
	}

	public static function get_total_revenue( $status = 'completed' ) {
		global $wpdb;
		return (float) $wpdb->get_var( $wpdb->prepare(
			"SELECT COALESCE(SUM(total), 0) FROM {$wpdb->prefix}wplp_orders WHERE status = %s",
			$status
		) );
	}

	public static function get_active_license_count() {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}wplp_licenses WHERE status = 'active'"
		);
	}

	public static function get_customer_count() {
		global $wpdb;
		return (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}wplp_customers"
		);
	}
}
