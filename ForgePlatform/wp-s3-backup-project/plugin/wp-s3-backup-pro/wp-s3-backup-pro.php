<?php
/**
 * Plugin Name: WP S3 Backup Pro
 * Description: Advanced features for WP S3 Backup — incremental backups, notifications, encryption, storage management, and more.
 * Version:     1.0.0
 * Author:      Edward Fong
 * Requires Plugins: wp-s3-backup
 * License:     GPL-2.0-or-later
 * Text Domain: wp-s3-backup-pro
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPS3B_PRO_VERSION', '1.0.0' );
define( 'WPS3B_PRO_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPS3B_PRO_URL', plugin_dir_url( __FILE__ ) );

add_action( 'plugins_loaded', 'wps3b_pro_bootstrap', 15 );

function wps3b_pro_bootstrap() {
	if ( ! defined( 'WPS3B_VERSION' ) ) {
		add_action( 'admin_notices', function() {
			echo '<div class="notice notice-error"><p>';
			esc_html_e( 'WP S3 Backup Pro requires the free WP S3 Backup plugin to be active.', 'wp-s3-backup-pro' );
			echo '</p></div>';
		});
		return;
	}

	require_once WPS3B_PRO_DIR . 'includes/class-wps3b-pro-license.php';
	require_once WPS3B_PRO_DIR . 'includes/class-wps3b-pro-notifications.php';
	require_once WPS3B_PRO_DIR . 'includes/class-wps3b-pro-encryption.php';
	require_once WPS3B_PRO_DIR . 'includes/class-wps3b-pro-storage.php';
	require_once WPS3B_PRO_DIR . 'includes/class-wps3b-pro-schedule.php';
	require_once WPS3B_PRO_DIR . 'includes/class-wps3b-pro-restore.php';
	require_once WPS3B_PRO_DIR . 'includes/class-wps3b-pro-incremental.php';
	require_once WPS3B_PRO_DIR . 'includes/class-wps3b-pro.php';

	WPS3B_Pro::init();
}
