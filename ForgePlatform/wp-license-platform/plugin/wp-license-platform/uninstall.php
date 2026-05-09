<?php
/**
 * Uninstall — removes all plugin data when deleted.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;
$prefix = $wpdb->prefix;

// Drop custom tables (in reverse dependency order)
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
	$wpdb->query( "DROP TABLE IF EXISTS {$prefix}{$table}" ); // phpcs:ignore
}

// Remove options
delete_option( 'wplp_settings' );
delete_option( 'wplp_paypal_credentials' );
delete_option( 'wplp_paypal_webhook_id' );
delete_option( 'wplp_db_version' );

// Clear cron
$timestamp = wp_next_scheduled( 'wplp_daily_license_check' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'wplp_daily_license_check' );
}

// Clear transients
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wplp_%' OR option_name LIKE '_transient_timeout_wplp_%'" ); // phpcs:ignore
