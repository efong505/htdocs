<?php
/**
 * Settings page and form handling.
 *
 * Manages the plugin settings UI, credential encryption/storage,
 * form validation, and AJAX handlers for test connection.
 *
 * @package WP_S3_Backup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPS3B_Settings {

	const SETTINGS_KEY = 'wps3b_settings';
	const CREDS_KEY    = 'wps3b_aws_credentials';

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'handle_save' ) );
		add_action( 'wp_ajax_wps3b_test_connection', array( __CLASS__, 'ajax_test_connection' ) );
	}

	/**
	 * Get all settings with defaults.
	 *
	 * @return array
	 */
	public static function get_settings() {
		return wp_parse_args( get_option( self::SETTINGS_KEY, array() ), array(
			'bucket'       => '',
			'region'       => 'us-east-1',
			's3_prefix'    => '',
			'frequency'    => 'daily',
			'enabled'      => 0,
			'backup_db'    => 1,
			'backup_files' => 1,
			'exclude_paths' => 'cache,ai1wm-backups,updraft,node_modules,upgrade,wps3b-temp',
		) );
	}

	/**
	 * Get masked access key for display.
	 *
	 * @return string Masked key like 'AKIA****WXYZ' or empty string.
	 */
	public static function get_masked_access_key() {
		$creds = WPS3B_Backup_Manager::get_credentials();
		if ( is_wp_error( $creds ) ) {
			return '';
		}
		$key = $creds['access_key'];
		if ( strlen( $key ) <= 8 ) {
			return str_repeat( '*', strlen( $key ) );
		}
		return substr( $key, 0, 4 ) . str_repeat( '*', strlen( $key ) - 8 ) . substr( $key, -4 );
	}

	/**
	 * Get masked secret key for display.
	 *
	 * @return string Masked key like '****abcd' or empty string.
	 */
	public static function get_masked_secret_key() {
		$creds = WPS3B_Backup_Manager::get_credentials();
		if ( is_wp_error( $creds ) ) {
			return '';
		}
		return str_repeat( '*', 36 ) . substr( $creds['secret_key'], -4 );
	}

	/**
	 * Check if credentials are configured.
	 *
	 * @return bool
	 */
	public static function has_credentials() {
		return ! is_wp_error( WPS3B_Backup_Manager::get_credentials() );
	}

	/**
	 * Handle settings form submission.
	 */
	public static function handle_save() {
		if ( ! isset( $_POST['wps3b_save_settings'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-s3-backup' ) );
		}

		check_admin_referer( 'wps3b_save_settings', 'wps3b_nonce' );

		// Save general settings
		$settings = array(
			'bucket'        => sanitize_text_field( wp_unslash( $_POST['wps3b_bucket'] ?? '' ) ),
			'region'        => sanitize_text_field( wp_unslash( $_POST['wps3b_region'] ?? 'us-east-1' ) ),
			's3_prefix'     => sanitize_text_field( wp_unslash( $_POST['wps3b_s3_prefix'] ?? '' ) ),
			'frequency'     => sanitize_key( wp_unslash( $_POST['wps3b_frequency'] ?? 'daily' ) ),
			'enabled'       => isset( $_POST['wps3b_enabled'] ) ? 1 : 0,
			'backup_db'     => isset( $_POST['wps3b_backup_db'] ) ? 1 : 0,
			'backup_files'  => isset( $_POST['wps3b_backup_files'] ) ? 1 : 0,
			'exclude_paths' => sanitize_text_field( wp_unslash( $_POST['wps3b_exclude_paths'] ?? '' ) ),
		);

		// Validate region
		$valid_regions = array(
			'us-east-1', 'us-east-2', 'us-west-1', 'us-west-2',
			'af-south-1', 'ap-east-1', 'ap-south-1', 'ap-south-2',
			'ap-southeast-1', 'ap-southeast-2', 'ap-southeast-3', 'ap-southeast-4',
			'ap-northeast-1', 'ap-northeast-2', 'ap-northeast-3',
			'ca-central-1', 'ca-west-1',
			'eu-central-1', 'eu-central-2', 'eu-west-1', 'eu-west-2', 'eu-west-3',
			'eu-south-1', 'eu-south-2', 'eu-north-1',
			'il-central-1', 'me-south-1', 'me-central-1',
			'sa-east-1',
		);
		if ( ! in_array( $settings['region'], $valid_regions, true ) ) {
			$settings['region'] = 'us-east-1';
		}

		// Validate frequency
		$valid_frequencies = array( 'hourly', 'twicedaily', 'daily', 'weekly', 'monthly' );
		if ( ! in_array( $settings['frequency'], $valid_frequencies, true ) ) {
			$settings['frequency'] = 'daily';
		}

		update_option( self::SETTINGS_KEY, $settings );

		// Save credentials (only if user entered new ones)
		$access_key = sanitize_text_field( wp_unslash( $_POST['wps3b_access_key'] ?? '' ) );
		$secret_key = sanitize_text_field( wp_unslash( $_POST['wps3b_secret_key'] ?? '' ) );

		if ( ! empty( $access_key ) && false === strpos( $access_key, '****' ) &&
		     ! empty( $secret_key ) && false === strpos( $secret_key, '****' ) ) {
			$encrypted = WPS3B_Crypto::encrypt( wp_json_encode( array(
				'access_key' => $access_key,
				'secret_key' => $secret_key,
			) ) );

			if ( false !== $encrypted ) {
				update_option( self::CREDS_KEY, $encrypted );
			}
		}

		// Reschedule cron
		WPS3B_Plugin::reschedule_cron( $settings );

		add_settings_error( 'wps3b_settings', 'wps3b_saved', __( 'Settings saved.', 'wp-s3-backup' ), 'success' );
	}

	/**
	 * AJAX handler for testing S3 connection.
	 */
	public static function ajax_test_connection() {
		check_ajax_referer( 'wps3b_test_connection', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'wp-s3-backup' ) );
		}

		$result = WPS3B_Backup_Manager::test_connection();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( __( 'Connection successful! Your S3 bucket is accessible.', 'wp-s3-backup' ) );
	}

	/**
	 * Render the settings page.
	 */
	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require WPS3B_PLUGIN_DIR . 'admin/views/settings-page.php';
	}

	/**
	 * Render the backups page.
	 */
	public static function render_backups_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require WPS3B_PLUGIN_DIR . 'admin/views/backups-page.php';
	}

	/**
	 * Render the logs page.
	 */
	public static function render_logs_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require WPS3B_PLUGIN_DIR . 'admin/views/logs-page.php';
	}
}
