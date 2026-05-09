<?php
/**
 * Uninstall handler — removes all plugin data when deleted.
 *
 * This file runs when the plugin is deleted (not deactivated) from
 * the WordPress admin. It removes all options and temp files.
 *
 * @package WP_S3_Backup
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove all plugin options
delete_option( 'wps3b_settings' );
delete_option( 'wps3b_aws_credentials' );
delete_option( 'wps3b_last_backup' );
delete_option( 'wps3b_last_error' );
delete_option( 'wps3b_log' );

// Remove temp directory
$temp_dir = WP_CONTENT_DIR . '/wps3b-temp';
if ( is_dir( $temp_dir ) ) {
	$files = glob( $temp_dir . '/*' );
	if ( $files ) {
		array_map( 'unlink', $files );
	}
	rmdir( $temp_dir );
}

// Clear scheduled cron
$timestamp = wp_next_scheduled( 'wps3b_scheduled_backup' );
if ( $timestamp ) {
	wp_unschedule_event( $timestamp, 'wps3b_scheduled_backup' );
}
