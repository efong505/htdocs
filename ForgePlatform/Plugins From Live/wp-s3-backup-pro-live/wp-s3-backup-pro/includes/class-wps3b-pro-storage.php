<?php
/**
 * S3 storage class management and cost estimation.
 *
 * @package WP_S3_Backup_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPS3B_Pro_Storage {

	const STORAGE_CLASSES = array(
		'STANDARD'            => 'S3 Standard',
		'STANDARD_IA'         => 'S3 Standard-IA',
		'INTELLIGENT_TIERING' => 'S3 Intelligent-Tiering',
		'GLACIER_IR'          => 'S3 Glacier Instant Retrieval',
		'GLACIER'             => 'S3 Glacier Flexible Retrieval',
		'DEEP_ARCHIVE'        => 'S3 Glacier Deep Archive',
	);

	const COST_PER_GB = array(
		'STANDARD'            => 0.023,
		'STANDARD_IA'         => 0.0125,
		'INTELLIGENT_TIERING' => 0.023,
		'GLACIER_IR'          => 0.004,
		'GLACIER'             => 0.0036,
		'DEEP_ARCHIVE'        => 0.00099,
	);

	public static function init() {
		add_action( 'wp_ajax_wps3b_pro_change_storage', array( __CLASS__, 'ajax_change_storage' ) );
	}

	/**
	 * Calculate estimated monthly cost for a list of backup files.
	 */
	public static function estimate_cost( $files ) {
		$monthly_cost = 0;
		foreach ( $files as $file ) {
			$gb    = $file['size'] / ( 1024 * 1024 * 1024 );
			$class = $file['storage_class'] ?? 'STANDARD';
			$monthly_cost += $gb * ( self::COST_PER_GB[ $class ] ?? 0.023 );
		}
		return $monthly_cost;
	}

	/**
	 * Get storage summary for all backups.
	 */
	public static function get_summary() {
		$backups = WPS3B_Backup_Manager::list_backups();
		if ( is_wp_error( $backups ) ) {
			return $backups;
		}

		$total_size  = 0;
		$all_files   = array();
		$by_class    = array();

		foreach ( $backups as $backup ) {
			foreach ( $backup['files'] as $file ) {
				$total_size += $file['size'];
				$all_files[] = $file;
				$class = $file['storage_class'] ?? 'STANDARD';
				if ( ! isset( $by_class[ $class ] ) ) {
					$by_class[ $class ] = array( 'count' => 0, 'size' => 0 );
				}
				$by_class[ $class ]['count']++;
				$by_class[ $class ]['size'] += $file['size'];
			}
		}

		return array(
			'total_backups' => count( $backups ),
			'total_size'    => $total_size,
			'total_files'   => count( $all_files ),
			'by_class'      => $by_class,
			'monthly_cost'  => self::estimate_cost( $all_files ),
		);
	}

	/**
	 * Change storage class of an S3 object (copy-to-self).
	 */
	public static function change_storage_class( $s3_key, $new_class ) {
		if ( ! array_key_exists( $new_class, self::STORAGE_CLASSES ) ) {
			return new WP_Error( 'wps3b_invalid_class', __( 'Invalid storage class.', 'wp-s3-backup-pro' ) );
		}

		$s3 = WPS3B_Backup_Manager::get_s3_client();
		if ( is_wp_error( $s3 ) ) {
			return $s3;
		}

		$settings = get_option( 'wps3b_settings', array() );
		$bucket   = $settings['bucket'] ?? '';

		// Use the S3 client's sign_request via reflection or direct API call
		// CopyObject: PUT with x-amz-copy-source header
		$uri = '/' . ltrim( $s3_key, '/' );

		// We need to access the S3 client's private sign method
		// Instead, use wp_remote_request with manual signing
		$creds = WPS3B_Backup_Manager::get_credentials();
		if ( is_wp_error( $creds ) ) {
			return $creds;
		}

		$region   = $settings['region'] ?? 'us-east-1';
		$endpoint = sprintf( 'https://%s.s3.%s.amazonaws.com', $bucket, $region );
		$host     = sprintf( '%s.s3.%s.amazonaws.com', $bucket, $region );

		$timestamp  = gmdate( 'Ymd\THis\Z' );
		$date_stamp = gmdate( 'Ymd' );
		$copy_source = '/' . $bucket . $uri;

		$extra_headers = array(
			'Host'                 => $host,
			'x-amz-copy-source'   => $copy_source,
			'x-amz-date'          => $timestamp,
			'x-amz-content-sha256' => hash( 'sha256', '' ),
			'x-amz-storage-class' => $new_class,
			'x-amz-metadata-directive' => 'COPY',
		);

		// Sign the request
		$sorted = array();
		foreach ( $extra_headers as $k => $v ) {
			$sorted[ strtolower( $k ) ] = trim( $v );
		}
		ksort( $sorted );

		$canonical_headers = '';
		foreach ( $sorted as $k => $v ) {
			$canonical_headers .= $k . ':' . $v . "\n";
		}
		$signed_headers = implode( ';', array_keys( $sorted ) );

		$canonical_request = implode( "\n", array(
			'PUT', $uri, '', $canonical_headers, $signed_headers, hash( 'sha256', '' ),
		));

		$scope          = $date_stamp . '/' . $region . '/s3/aws4_request';
		$string_to_sign = "AWS4-HMAC-SHA256\n{$timestamp}\n{$scope}\n" . hash( 'sha256', $canonical_request );

		$k_date    = hash_hmac( 'sha256', $date_stamp, 'AWS4' . $creds['secret_key'], true );
		$k_region  = hash_hmac( 'sha256', $region, $k_date, true );
		$k_service = hash_hmac( 'sha256', 's3', $k_region, true );
		$k_signing = hash_hmac( 'sha256', 'aws4_request', $k_service, true );
		$signature = hash_hmac( 'sha256', $string_to_sign, $k_signing );

		$extra_headers['Authorization'] = sprintf(
			'AWS4-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s',
			$creds['access_key'], $scope, $signed_headers, $signature
		);

		$response = wp_remote_request( $endpoint . $uri, array(
			'method'  => 'PUT',
			'headers' => $extra_headers,
			'timeout' => 60,
		));

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code >= 200 && $code < 300 ) {
			return true;
		}

		return new WP_Error( 'wps3b_storage_change', sprintf(
			__( 'Failed to change storage class (HTTP %d).', 'wp-s3-backup-pro' ),
			$code
		));
	}

	/**
	 * AJAX handler for changing storage class.
	 */
	public static function ajax_change_storage() {
		check_ajax_referer( 'wps3b_pro_ajax', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'wp-s3-backup-pro' ) );
		}

		$s3_key    = sanitize_text_field( wp_unslash( $_POST['s3_key'] ?? '' ) );
		$new_class = sanitize_text_field( wp_unslash( $_POST['storage_class'] ?? '' ) );

		if ( empty( $s3_key ) || empty( $new_class ) ) {
			wp_send_json_error( __( 'Missing parameters.', 'wp-s3-backup-pro' ) );
		}

		$result = self::change_storage_class( $s3_key, $new_class );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( __( 'Storage class updated.', 'wp-s3-backup-pro' ) );
	}
}
