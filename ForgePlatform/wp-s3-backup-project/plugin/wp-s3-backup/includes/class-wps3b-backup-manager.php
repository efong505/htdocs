<?php
/**
 * Backup manager — orchestrates the full backup flow.
 *
 * Coordinates the backup engine (export) and S3 client (upload).
 * Handles scheduled and manual backups, listing, downloading, and deleting.
 *
 * @package WP_S3_Backup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPS3B_Backup_Manager {

	/**
	 * Get an S3 client instance using stored credentials.
	 *
	 * @return WPS3B_S3_Client|WP_Error
	 */
	public static function get_s3_client() {
		$creds = self::get_credentials();
		if ( is_wp_error( $creds ) ) {
			return $creds;
		}

		$settings = get_option( 'wps3b_settings', array() );
		$bucket   = isset( $settings['bucket'] ) ? $settings['bucket'] : '';
		$region   = isset( $settings['region'] ) ? $settings['region'] : 'us-east-1';
		$endpoint = isset( $settings['custom_endpoint'] ) ? $settings['custom_endpoint'] : '';

		if ( empty( $bucket ) ) {
			return new WP_Error( 'wps3b_no_bucket', __( 'S3 bucket name is not configured.', 'wp-s3-backup' ) );
		}

		return new WPS3B_S3_Client( $creds['access_key'], $creds['secret_key'], $region, $bucket, $endpoint );
	}

	/**
	 * Get decrypted AWS credentials.
	 *
	 * @return array{access_key: string, secret_key: string}|WP_Error
	 */
	public static function get_credentials() {
		$encrypted = get_option( 'wps3b_aws_credentials', '' );
		if ( empty( $encrypted ) ) {
			return new WP_Error( 'wps3b_no_creds', __( 'AWS credentials are not configured.', 'wp-s3-backup' ) );
		}

		$decrypted = WPS3B_Crypto::decrypt( $encrypted );
		if ( false === $decrypted || empty( $decrypted ) ) {
			return new WP_Error( 'wps3b_decrypt_fail', __( 'Could not decrypt AWS credentials. Please re-enter them in settings.', 'wp-s3-backup' ) );
		}

		$creds = json_decode( $decrypted, true );
		if ( ! $creds || empty( $creds['access_key'] ) || empty( $creds['secret_key'] ) ) {
			return new WP_Error( 'wps3b_invalid_creds', __( 'Stored credentials are invalid. Please re-enter them in settings.', 'wp-s3-backup' ) );
		}

		return $creds;
	}

	/**
	 * Get the S3 path prefix for this site's backups.
	 *
	 * @return string Path prefix like 'backups/mysite/'.
	 */
	public static function get_s3_prefix() {
		$settings = get_option( 'wps3b_settings', array() );

		if ( ! empty( $settings['s3_prefix'] ) ) {
			$prefix = $settings['s3_prefix'];
		} else {
			$url  = get_site_url();
			$host = wp_parse_url( $url, PHP_URL_HOST );
			$path = wp_parse_url( $url, PHP_URL_PATH );
			$site_name = sanitize_title( $host );
			if ( ! empty( $path ) && '/' !== $path ) {
				$site_name .= '-' . sanitize_title( trim( $path, '/' ) );
			}
			$prefix = 'backups/' . $site_name;
		}

		$prefix = apply_filters( 'wps3b_s3_path_prefix', $prefix );
		return trailingslashit( $prefix );
	}

	/**
	 * Run a full backup: export database + files, upload to S3.
	 *
	 * @return array|WP_Error Manifest data on success, WP_Error on failure.
	 */
	public static function run_backup() {
		$settings = get_option( 'wps3b_settings', array() );
		$backup_db    = isset( $settings['backup_db'] ) ? (bool) $settings['backup_db'] : true;
		$backup_files = isset( $settings['backup_files'] ) ? (bool) $settings['backup_files'] : true;
		$excludes     = isset( $settings['exclude_paths'] ) ? array_map( 'trim', explode( ',', $settings['exclude_paths'] ) ) : array();

		if ( ! $backup_db && ! $backup_files ) {
			return new WP_Error( 'wps3b_nothing', __( 'Both database and file backups are disabled.', 'wp-s3-backup' ) );
		}

		$s3 = self::get_s3_client();
		if ( is_wp_error( $s3 ) ) {
			WPS3B_Logger::error( 'Backup failed: ' . $s3->get_error_message() );
			do_action( 'wps3b_backup_failed', $s3 );
			return $s3;
		}

		$engine = new WPS3B_Backup_Engine( $excludes );
		$prefix = self::get_s3_prefix();

		WPS3B_Logger::info( 'Backup started.' );
		do_action( 'wps3b_before_backup' );

		$db_info    = null;
		$files_info = null;

		try {
			// Export database
			if ( $backup_db ) {
				WPS3B_Logger::info( 'Exporting database...' );
				$db_info = $engine->export_database();
				if ( is_wp_error( $db_info ) ) {
					throw new Exception( $db_info->get_error_message() );
				}
				WPS3B_Logger::info( sprintf( 'Database exported: %d tables, %d rows.', $db_info['tables'], $db_info['rows'] ) );
			}

			// Export files
			if ( $backup_files ) {
				WPS3B_Logger::info( 'Archiving files...' );
				$files_info = $engine->export_files();
				if ( is_wp_error( $files_info ) ) {
					throw new Exception( $files_info->get_error_message() );
				}
				WPS3B_Logger::info( sprintf( 'Files archived: %d files.', $files_info['files'] ) );
			}

			// Generate manifest
			$manifest = $engine->generate_manifest( $db_info, $files_info );
			if ( is_wp_error( $manifest ) ) {
				throw new Exception( $manifest->get_error_message() );
			}

			// Upload to S3
			if ( $db_info ) {
				WPS3B_Logger::info( 'Uploading database to S3...' );
				do_action( 'wps3b_before_upload', $db_info['path'] );
				$result = $s3->upload_file( $db_info['path'], $prefix . $db_info['filename'], 'application/gzip' );
				if ( is_wp_error( $result ) ) {
					throw new Exception( 'DB upload failed: ' . $result->get_error_message() );
				}
				do_action( 'wps3b_after_upload', $prefix . $db_info['filename'] );
			}

			if ( $files_info ) {
				WPS3B_Logger::info( 'Uploading files to S3...' );
				do_action( 'wps3b_before_upload', $files_info['path'] );
				$result = $s3->upload_file( $files_info['path'], $prefix . $files_info['filename'], 'application/zip' );
				if ( is_wp_error( $result ) ) {
					throw new Exception( 'Files upload failed: ' . $result->get_error_message() );
				}
				do_action( 'wps3b_after_upload', $prefix . $files_info['filename'] );
			}

			// Upload manifest
			WPS3B_Logger::info( 'Uploading manifest...' );
			$result = $s3->upload_file( $manifest['path'], $prefix . $manifest['filename'], 'application/json' );
			if ( is_wp_error( $result ) ) {
				throw new Exception( 'Manifest upload failed: ' . $result->get_error_message() );
			}

			// Success
			update_option( 'wps3b_last_backup', current_time( 'mysql' ) );
			delete_option( 'wps3b_last_error' );

			$summary = sprintf(
				'Backup completed successfully. DB: %s, Files: %s',
				$db_info ? size_format( filesize( $db_info['path'] ) ) : 'skipped',
				$files_info ? size_format( filesize( $files_info['path'] ) ) : 'skipped'
			);
			WPS3B_Logger::success( $summary );

			do_action( 'wps3b_after_backup', $manifest['data'] );

			return $manifest['data'];

		} catch ( Exception $e ) {
			$error_msg = $e->getMessage();
			update_option( 'wps3b_last_error', $error_msg );
			WPS3B_Logger::error( 'Backup failed: ' . $error_msg );
			do_action( 'wps3b_backup_failed', new WP_Error( 'wps3b_backup_failed', $error_msg ) );

			return new WP_Error( 'wps3b_backup_failed', $error_msg );

		} finally {
			// Always clean up temp files
			$engine->cleanup();
		}
	}

	/**
	 * List backups stored in S3.
	 *
	 * Groups files by timestamp (manifest + db + files = one backup).
	 *
	 * @return array|WP_Error Array of backup groups or error.
	 */
	public static function list_backups() {
		$s3 = self::get_s3_client();
		if ( is_wp_error( $s3 ) ) {
			return $s3;
		}

		$prefix  = self::get_s3_prefix();
		$objects = $s3->list_objects( $prefix );
		if ( is_wp_error( $objects ) ) {
			return $objects;
		}

		// Group by timestamp prefix (e.g., 2026-06-15-120000)
		$backups = array();
		foreach ( $objects as $obj ) {
			$basename = str_replace( $prefix, '', $obj['key'] );

			// Extract timestamp from filename (first 17 chars: 2026-06-15-120000)
			if ( preg_match( '/^(\d{4}-\d{2}-\d{2}-\d{6})/', $basename, $matches ) ) {
				$ts = $matches[1];
				if ( ! isset( $backups[ $ts ] ) ) {
					$backups[ $ts ] = array(
						'timestamp' => $ts,
						'date'      => substr( $ts, 0, 10 ) . ' ' . substr( $ts, 11, 2 ) . ':' . substr( $ts, 13, 2 ) . ':' . substr( $ts, 15, 2 ),
						'files'     => array(),
						'total_size' => 0,
					);
				}
				$backups[ $ts ]['files'][] = array(
					'key'           => $obj['key'],
					'size'          => $obj['size'],
					'last_modified' => $obj['last_modified'],
					'storage_class' => isset( $obj['storage_class'] ) ? $obj['storage_class'] : 'STANDARD',
				);
				$backups[ $ts ]['total_size'] += $obj['size'];
			}
		}

		// Sort by timestamp descending (newest first)
		krsort( $backups );

		return array_values( $backups );
	}

	/**
	 * Delete a backup (all files with the same timestamp prefix).
	 *
	 * @param string $timestamp Backup timestamp (e.g., '2026-06-15-120000').
	 * @return true|WP_Error
	 */
	public static function delete_backup( $timestamp ) {
		$s3 = self::get_s3_client();
		if ( is_wp_error( $s3 ) ) {
			return $s3;
		}

		$prefix  = self::get_s3_prefix();
		$objects = $s3->list_objects( $prefix . $timestamp );
		if ( is_wp_error( $objects ) ) {
			return $objects;
		}

		$errors = array();
		foreach ( $objects as $obj ) {
			$result = $s3->delete_object( $obj['key'] );
			if ( is_wp_error( $result ) ) {
				$errors[] = $result->get_error_message();
			}
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error( 'wps3b_delete_error', implode( '; ', $errors ) );
		}

		WPS3B_Logger::info( 'Deleted backup: ' . $timestamp );
		return true;
	}

	/**
	 * Get a pre-signed download URL for a backup file.
	 *
	 * @param string $s3_key Full S3 object key.
	 * @return string|WP_Error Pre-signed URL or error.
	 */
	public static function get_download_url( $s3_key ) {
		$s3 = self::get_s3_client();
		if ( is_wp_error( $s3 ) ) {
			return $s3;
		}

		return $s3->get_presigned_url( $s3_key, 3600 );
	}

	/**
	 * Test the S3 connection with current settings.
	 *
	 * @return true|WP_Error
	 */
	public static function test_connection() {
		$s3 = self::get_s3_client();
		if ( is_wp_error( $s3 ) ) {
			return $s3;
		}

		return $s3->test_connection();
	}
}
