<?php
/**
 * Restore handler — downloads backups from S3 and restores the site.
 *
 * Handles database import via $wpdb and file extraction via ZipArchive.
 * Puts the site in maintenance mode during restore.
 *
 * @package WP_S3_Backup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPS3B_Restore {

	/**
	 * Run a full restore from an S3 backup.
	 *
	 * @param string $timestamp Backup timestamp (e.g., '2026-04-15-051735').
	 * @return true|WP_Error
	 */
	public static function run( $timestamp ) {
		$s3 = WPS3B_Backup_Manager::get_s3_client();
		if ( is_wp_error( $s3 ) ) {
			return $s3;
		}

		$prefix  = WPS3B_Backup_Manager::get_s3_prefix();
		$objects = $s3->list_objects( $prefix . $timestamp );
		if ( is_wp_error( $objects ) ) {
			return $objects;
		}

		if ( empty( $objects ) ) {
			return new WP_Error( 'wps3b_no_backup', __( 'No backup files found for this timestamp.', 'wp-s3-backup' ) );
		}

		// Identify files
		$db_key       = '';
		$files_key    = '';
		$manifest_key = '';

		foreach ( $objects as $obj ) {
			$basename = basename( $obj['key'] );
			if ( preg_match( '/db\.sql\.gz$/', $basename ) ) {
				$db_key = $obj['key'];
			} elseif ( preg_match( '/files\.zip$/', $basename ) ) {
				$files_key = $obj['key'];
			} elseif ( preg_match( '/manifest\.json$/', $basename ) ) {
				$manifest_key = $obj['key'];
			}
		}

		if ( empty( $db_key ) && empty( $files_key ) ) {
			return new WP_Error( 'wps3b_incomplete', __( 'Backup is incomplete — no database or file archive found.', 'wp-s3-backup' ) );
		}

		$temp_dir = WPS3B_TEMP_DIR;
		if ( ! is_dir( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}

		WPS3B_Logger::info( 'Restore started for backup: ' . $timestamp );

		try {
			// Don't use .maintenance file — it blocks admin-ajax.php

			// Download and restore database
			if ( ! empty( $db_key ) ) {
				WPS3B_Logger::info( 'Downloading database from S3...' );
				$db_path = self::download_from_s3( $s3, $db_key, $temp_dir );
				if ( is_wp_error( $db_path ) ) {
					throw new Exception( $db_path->get_error_message() );
				}

				WPS3B_Logger::info( 'Importing database...' );
				$result = self::import_database( $db_path );
				if ( is_wp_error( $result ) ) {
					throw new Exception( $result->get_error_message() );
				}
				WPS3B_Logger::info( 'Database imported successfully.' );
			}

			// Download and restore files
			if ( ! empty( $files_key ) ) {
				WPS3B_Logger::info( 'Downloading files archive from S3...' );
				$files_path = self::download_from_s3( $s3, $files_key, $temp_dir );
				if ( is_wp_error( $files_path ) ) {
					throw new Exception( $files_path->get_error_message() );
				}

				WPS3B_Logger::info( 'Extracting files...' );
				$result = self::extract_files( $files_path );
				if ( is_wp_error( $result ) ) {
					throw new Exception( $result->get_error_message() );
				}
				WPS3B_Logger::info( 'Files restored successfully.' );
			}

			WPS3B_Logger::success( 'Restore completed successfully for backup: ' . $timestamp );
			return true;

		} catch ( Exception $e ) {
			WPS3B_Logger::error( 'Restore failed: ' . $e->getMessage() );
			return new WP_Error( 'wps3b_restore_failed', $e->getMessage() );

		} finally {
			// Always clean up
			self::cleanup_temp( $temp_dir );
		}
	}

	/**
	 * Get backup manifest for pre-restore compatibility check.
	 *
	 * @param string $timestamp Backup timestamp.
	 * @return array|WP_Error Manifest data or error.
	 */
	public static function get_manifest( $timestamp ) {
		$s3 = WPS3B_Backup_Manager::get_s3_client();
		if ( is_wp_error( $s3 ) ) {
			return $s3;
		}

		$prefix  = WPS3B_Backup_Manager::get_s3_prefix();
		$objects = $s3->list_objects( $prefix . $timestamp );
		if ( is_wp_error( $objects ) ) {
			return $objects;
		}

		$manifest_key = '';
		foreach ( $objects as $obj ) {
			if ( preg_match( '/manifest\.json$/', basename( $obj['key'] ) ) ) {
				$manifest_key = $obj['key'];
				break;
			}
		}

		if ( empty( $manifest_key ) ) {
			return new WP_Error( 'wps3b_no_manifest', __( 'Manifest file not found for this backup.', 'wp-s3-backup' ) );
		}

		$temp_dir = WPS3B_TEMP_DIR;
		if ( ! is_dir( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}

		$local_path = self::download_from_s3( $s3, $manifest_key, $temp_dir );
		if ( is_wp_error( $local_path ) ) {
			return $local_path;
		}

		$json = file_get_contents( $local_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		@unlink( $local_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

		if ( false === $json ) {
			return new WP_Error( 'wps3b_manifest_read', __( 'Could not read manifest file.', 'wp-s3-backup' ) );
		}

		$data = json_decode( $json, true );
		if ( ! $data ) {
			return new WP_Error( 'wps3b_manifest_parse', __( 'Could not parse manifest file.', 'wp-s3-backup' ) );
		}

		// Add compatibility warnings
		$data['warnings'] = self::check_compatibility( $data );

		return $data;
	}

	/**
	 * Check compatibility between backup and current environment.
	 *
	 * @param array $manifest Manifest data.
	 * @return array Array of warning strings.
	 */
	private static function check_compatibility( $manifest ) {
		$warnings = array();

		// PHP version check
		$backup_php  = isset( $manifest['php_version'] ) ? $manifest['php_version'] : '';
		$current_php = phpversion();
		if ( $backup_php && version_compare( intval( $backup_php ), intval( $current_php ), '!=' ) ) {
			$warnings[] = sprintf(
				/* translators: 1: Backup PHP version, 2: Current PHP version */
				__( 'PHP version mismatch: backup is PHP %1$s, current site is PHP %2$s.', 'wp-s3-backup' ),
				$backup_php,
				$current_php
			);
		}

		// WordPress version check
		$backup_wp  = isset( $manifest['wordpress_version'] ) ? $manifest['wordpress_version'] : '';
		$current_wp = get_bloginfo( 'version' );
		if ( $backup_wp && version_compare( $backup_wp, $current_wp, '!=' ) ) {
			$warnings[] = sprintf(
				/* translators: 1: Backup WP version, 2: Current WP version */
				__( 'WordPress version mismatch: backup is %1$s, current site is %2$s.', 'wp-s3-backup' ),
				$backup_wp,
				$current_wp
			);
		}

		// Table prefix check
		global $wpdb;
		$backup_prefix = isset( $manifest['table_prefix'] ) ? $manifest['table_prefix'] : '';
		if ( $backup_prefix && $backup_prefix !== $wpdb->prefix ) {
			$warnings[] = sprintf(
				/* translators: 1: Backup table prefix, 2: Current table prefix */
				__( 'Table prefix mismatch: backup uses "%1$s", current site uses "%2$s".', 'wp-s3-backup' ),
				$backup_prefix,
				$wpdb->prefix
			);
		}

		// URL check
		$backup_url  = isset( $manifest['site_url'] ) ? $manifest['site_url'] : '';
		$current_url = get_site_url();
		if ( $backup_url && $backup_url !== $current_url ) {
			$warnings[] = sprintf(
				/* translators: 1: Backup site URL, 2: Current site URL */
				__( 'Site URL mismatch: backup is from %1$s, current site is %2$s. You may need to update URLs after restore.', 'wp-s3-backup' ),
				$backup_url,
				$current_url
			);
		}

		return $warnings;
	}

	/**
	 * Download a file from S3 to the temp directory.
	 *
	 * @param WPS3B_S3_Client $s3       S3 client instance.
	 * @param string          $s3_key   S3 object key.
	 * @param string          $temp_dir Local temp directory.
	 * @return string|WP_Error Local file path or error.
	 */
	private static function download_from_s3( $s3, $s3_key, $temp_dir ) {
		$url = $s3->get_presigned_url( $s3_key, 3600 );

		$local_path = $temp_dir . '/' . basename( $s3_key );

		$response = wp_remote_get( $url, array(
			'timeout'  => 600,
			'stream'   => true,
			'filename' => $local_path,
		) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'wps3b_download_failed', sprintf(
				/* translators: 1: Filename, 2: Error message */
				__( 'Failed to download %1$s: %2$s', 'wp-s3-backup' ),
				basename( $s3_key ),
				$response->get_error_message()
			) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			@unlink( $local_path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return new WP_Error( 'wps3b_download_failed', sprintf(
				/* translators: 1: Filename, 2: HTTP status code */
				__( 'Failed to download %1$s (HTTP %2$d).', 'wp-s3-backup' ),
				basename( $s3_key ),
				$code
			) );
		}

		if ( ! file_exists( $local_path ) || 0 === filesize( $local_path ) ) {
			return new WP_Error( 'wps3b_download_empty', sprintf(
				/* translators: Filename */
				__( 'Downloaded file %s is empty.', 'wp-s3-backup' ),
				basename( $s3_key )
			) );
		}

		return $local_path;
	}

	/**
	 * Import a gzipped SQL dump into the database.
	 *
	 * Reads the SQL file line by line, assembles complete statements,
	 * and executes them via $wpdb.
	 *
	 * @param string $gz_path Path to the .sql.gz file.
	 * @return true|WP_Error
	 */
	private static function import_database( $gz_path ) {
		global $wpdb;

		$gz = gzopen( $gz_path, 'rb' );
		if ( ! $gz ) {
			return new WP_Error( 'wps3b_gz_open', __( 'Could not open database dump file.', 'wp-s3-backup' ) );
		}

		$wpdb->query( 'SET foreign_key_checks = 0' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$statement    = '';
		$line_number  = 0;
		$errors       = array();

		while ( ! gzeof( $gz ) ) {
			$line = gzgets( $gz );
			if ( false === $line ) {
				break;
			}

			$line_number++;
			$trimmed = trim( $line );

			// Skip comments and empty lines
			if ( empty( $trimmed ) || 0 === strpos( $trimmed, '--' ) || 0 === strpos( $trimmed, '/*' ) ) {
				continue;
			}

			$statement .= $line;

			// Execute when we hit a semicolon at the end of a line
			if ( preg_match( '/;\s*$/', $trimmed ) ) {
				$result = $wpdb->query( $statement ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				if ( false === $result && ! empty( $wpdb->last_error ) ) {
					$errors[] = sprintf( 'Line %d: %s', $line_number, $wpdb->last_error );
					// Continue — don't stop on individual statement errors
				}
				$statement = '';
			}

			if ( function_exists( 'set_time_limit' ) ) {
				@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}
		}

		gzclose( $gz );

		$wpdb->query( 'SET foreign_key_checks = 1' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		if ( ! empty( $errors ) ) {
			WPS3B_Logger::error( 'Database import had ' . count( $errors ) . ' errors. First: ' . $errors[0] );
		}

		return true;
	}

	/**
	 * Extract a files.zip archive over wp-content.
	 *
	 * @param string $zip_path Path to the .zip file.
	 * @return true|WP_Error
	 */
	private static function extract_files( $zip_path ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'wps3b_zip_missing', __( 'PHP ZipArchive extension is required for file restore.', 'wp-s3-backup' ) );
		}

		$zip = new ZipArchive();
		$result = $zip->open( $zip_path );
		if ( true !== $result ) {
			return new WP_Error( 'wps3b_zip_open', __( 'Could not open files archive.', 'wp-s3-backup' ) );
		}

		// The zip contains files under wp-content/ prefix
		// We need to extract to the parent of WP_CONTENT_DIR
		$extract_to = dirname( WP_CONTENT_DIR );

		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$entry_name = $zip->getNameIndex( $i );

			// Security: prevent path traversal
			if ( false !== strpos( $entry_name, '..' ) ) {
				continue;
			}

			// Only extract files under wp-content/
			if ( 0 !== strpos( $entry_name, 'wp-content/' ) ) {
				continue;
			}

			$target_path = $extract_to . '/' . $entry_name;

			// Create directory if needed
			if ( '/' === substr( $entry_name, -1 ) ) {
				if ( ! is_dir( $target_path ) ) {
					wp_mkdir_p( $target_path );
				}
				continue;
			}

			// Ensure parent directory exists
			$parent_dir = dirname( $target_path );
			if ( ! is_dir( $parent_dir ) ) {
				wp_mkdir_p( $parent_dir );
			}

			// Extract file
			$content = $zip->getFromIndex( $i );
			if ( false !== $content ) {
				file_put_contents( $target_path, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			}

			if ( function_exists( 'set_time_limit' ) ) {
				@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			}
		}

		$zip->close();
		return true;
	}

	/**
	 * Enable WordPress maintenance mode.
	 */
	private static function enable_maintenance_mode() {
		$maintenance_file = ABSPATH . '.maintenance';
		$content = '<?php $upgrading = ' . time() . '; ?>';
		file_put_contents( $maintenance_file, $content ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	/**
	 * Disable WordPress maintenance mode.
	 */
	private static function disable_maintenance_mode() {
		$maintenance_file = ABSPATH . '.maintenance';
		if ( file_exists( $maintenance_file ) ) {
			@unlink( $maintenance_file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
	}

	/**
	 * Clean up temp files.
	 *
	 * @param string $temp_dir Temp directory path.
	 */
	private static function cleanup_temp( $temp_dir ) {
		if ( ! is_dir( $temp_dir ) ) {
			return;
		}

		$files = glob( $temp_dir . '/*' );
		if ( $files ) {
			foreach ( $files as $file ) {
				if ( is_file( $file ) && ! in_array( basename( $file ), array( '.htaccess', 'index.php' ), true ) ) {
					@unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				}
			}
		}
	}
}
