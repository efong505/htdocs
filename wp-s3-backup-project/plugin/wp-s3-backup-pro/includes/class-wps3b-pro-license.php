<?php
/**
 * License validation against the license platform API.
 *
 * @package WP_S3_Backup_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPS3B_Pro_License {

	const API_URL = 'https://ekewaka.com/wp-json/wplp/v1/';

	/**
	 * Get the API URL (filterable for dev/staging).
	 */
	public static function get_api_url() {
		return apply_filters( 'wps3b_pro_api_url', self::API_URL );
	}
	const CACHE_KEY  = 'wps3b_pro_license';
	const CACHE_TTL  = DAY_IN_SECONDS;
	const GRACE_DAYS = 7;

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'daily_check' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_activation' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_deactivation' ) );
	}

	/**
	 * Check if Pro is currently licensed.
	 */
	public static function is_licensed() {
		$data = get_transient( self::CACHE_KEY );
		if ( $data && ! empty( $data['valid'] ) ) {
			if ( isset( $data['expires'] ) && strtotime( $data['expires'] ) < time() ) {
				return false;
			}
			return true;
		}
		$last_valid = get_option( 'wps3b_pro_last_valid', 0 );
		if ( $last_valid && ( time() - $last_valid ) < ( self::GRACE_DAYS * DAY_IN_SECONDS ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Get the license tier.
	 */
	public static function get_tier() {
		$data = get_transient( self::CACHE_KEY );
		return $data && isset( $data['tier'] ) ? $data['tier'] : 'free';
	}

	/**
	 * Validate license key against the API.
	 */
	public static function validate( $key ) {
		$response = wp_remote_post( self::get_api_url() . 'validate', array(
			'timeout' => 15,
			'body'    => array(
				'license_key' => $key,
				'site_url'    => get_site_url(),
				'product'     => 'wp-s3-backup-pro',
				'version'     => WPS3B_PRO_VERSION,
			),
		));

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $body && ! empty( $body['valid'] ) ) {
			set_transient( self::CACHE_KEY, $body, self::CACHE_TTL );
			update_option( 'wps3b_pro_last_valid', time() );
			return $body;
		}

		delete_transient( self::CACHE_KEY );
		return false;
	}

	/**
	 * Daily re-validation via transient expiry.
	 */
	public static function daily_check() {
		if ( get_transient( self::CACHE_KEY ) ) {
			return;
		}
		$key = get_option( 'wps3b_pro_license_key', '' );
		if ( ! empty( $key ) ) {
			self::validate( $key );
		}
	}

	/**
	 * Handle license activation form.
	 */
	public static function handle_activation() {
		if ( ! isset( $_POST['wps3b_pro_activate'] ) ) {
			return;
		}
		check_admin_referer( 'wps3b_pro_license', 'wps3b_pro_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$key = sanitize_text_field( wp_unslash( $_POST['wps3b_pro_license_key'] ?? '' ) );
		update_option( 'wps3b_pro_license_key', $key );

		$result = self::validate( $key );
		if ( $result && ! empty( $result['valid'] ) ) {
			wp_remote_post( self::get_api_url() . 'activate', array(
				'body' => array(
					'license_key' => $key,
					'site_url'    => get_site_url(),
					'product'     => 'wp-s3-backup-pro',
				),
			));
			add_settings_error( 'wps3b_pro', 'activated', __( 'License activated successfully.', 'wp-s3-backup-pro' ), 'success' );
		} else {
			add_settings_error( 'wps3b_pro', 'invalid', __( 'Invalid license key.', 'wp-s3-backup-pro' ), 'error' );
		}
	}

	/**
	 * Handle license deactivation.
	 */
	public static function handle_deactivation() {
		if ( ! isset( $_POST['wps3b_pro_deactivate'] ) ) {
			return;
		}
		check_admin_referer( 'wps3b_pro_license', 'wps3b_pro_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$key = get_option( 'wps3b_pro_license_key', '' );
		if ( $key ) {
			wp_remote_post( self::get_api_url() . 'deactivate', array(
				'body' => array(
					'license_key' => $key,
					'site_url'    => get_site_url(),
					'product'     => 'wp-s3-backup-pro',
				),
			));
		}

		delete_option( 'wps3b_pro_license_key' );
		delete_transient( self::CACHE_KEY );
		delete_option( 'wps3b_pro_last_valid' );
		add_settings_error( 'wps3b_pro', 'deactivated', __( 'License deactivated.', 'wp-s3-backup-pro' ), 'success' );
	}

	/**
	 * Get masked license key for display.
	 */
	public static function get_masked_key() {
		$key = get_option( 'wps3b_pro_license_key', '' );
		if ( empty( $key ) || strlen( $key ) < 10 ) {
			return '';
		}
		return substr( $key, 0, 5 ) . str_repeat( '*', strlen( $key ) - 9 ) . substr( $key, -4 );
	}

	/**
	 * Get license status info for display.
	 */
	public static function get_status() {
		$data = get_transient( self::CACHE_KEY );
		if ( $data && ! empty( $data['valid'] ) ) {
			return array(
				'status'  => 'active',
				'tier'    => $data['tier'] ?? 'personal',
				'expires' => $data['expires'] ?? '',
			);
		}
		$key = get_option( 'wps3b_pro_license_key', '' );
		if ( empty( $key ) ) {
			return array( 'status' => 'none' );
		}
		$last_valid = get_option( 'wps3b_pro_last_valid', 0 );
		if ( $last_valid && ( time() - $last_valid ) < ( self::GRACE_DAYS * DAY_IN_SECONDS ) ) {
			return array( 'status' => 'grace' );
		}
		return array( 'status' => 'expired' );
	}
}
