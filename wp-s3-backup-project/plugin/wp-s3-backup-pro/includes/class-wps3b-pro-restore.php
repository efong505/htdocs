<?php
/**
 * Pro restore features — selective restore (DB-only/files-only) and URL replacement.
 *
 * @package WP_S3_Backup_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPS3B_Pro_Restore {

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'handle_pro_restore' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_external_restore' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_upload_restore' ) );
		add_action( 'wp_ajax_wps3b_pro_list_external', array( __CLASS__, 'ajax_list_external_backups' ) );
		add_action( 'wp_ajax_wps3b_pro_upload_backup', array( __CLASS__, 'ajax_upload_backup' ) );
	}

	/**
	 * Handle Pro selective restore (overrides free plugin's full restore).
	 */
	public static function handle_pro_restore() {
		if ( ! isset( $_POST['wps3b_pro_confirm_restore'] ) ) {
			return;
		}
		check_admin_referer( 'wps3b_confirm_restore', 'wps3b_restore_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$timestamp    = sanitize_text_field( wp_unslash( $_POST['wps3b_restore_timestamp'] ?? '' ) );
		$restore_type = sanitize_key( wp_unslash( $_POST['wps3b_restore_type'] ?? 'full' ) );
		$old_url      = esc_url_raw( wp_unslash( $_POST['wps3b_old_url'] ?? '' ) );
		$new_url      = esc_url_raw( wp_unslash( $_POST['wps3b_new_url'] ?? '' ) );

		if ( empty( $timestamp ) ) {
			return;
		}

		delete_transient( 'wps3b_restore_manifest' );

		$result = self::run_selective_restore( $timestamp, $restore_type );

		if ( is_wp_error( $result ) ) {
			add_settings_error( 'wps3b_backups', 'restore_failed', $result->get_error_message(), 'error' );
			return;
		}

		// URL replacement after DB restore
		if ( in_array( $restore_type, array( 'full', 'database' ), true ) && ! empty( $old_url ) && ! empty( $new_url ) && $old_url !== $new_url ) {
			$replaced = self::search_replace_urls( $old_url, $new_url );
			if ( is_wp_error( $replaced ) ) {
				add_settings_error( 'wps3b_backups', 'url_replace_failed',
					sprintf( __( 'Restore succeeded but URL replacement failed: %s', 'wp-s3-backup-pro' ), $replaced->get_error_message() ),
					'warning'
				);
				return;
			}
		}

		add_settings_error( 'wps3b_backups', 'restore_success',
			__( 'Restore completed successfully. Please go to Settings > Permalinks and click Save Changes.', 'wp-s3-backup-pro' ),
			'success'
		);
	}

	/**
	 * Run a selective restore (database-only, files-only, or full).
	 */
	public static function run_selective_restore( $timestamp, $type = 'full' ) {
		$s3 = WPS3B_Backup_Manager::get_s3_client();
		if ( is_wp_error( $s3 ) ) {
			return $s3;
		}

		$prefix  = WPS3B_Backup_Manager::get_s3_prefix();
		$objects = $s3->list_objects( $prefix . $timestamp );
		if ( is_wp_error( $objects ) ) {
			return $objects;
		}

		$db_key    = '';
		$files_key = '';
		foreach ( $objects as $obj ) {
			$basename = basename( $obj['key'] );
			if ( preg_match( '/db\.sql\.gz$/', $basename ) ) {
				$db_key = $obj['key'];
			} elseif ( preg_match( '/files\.zip$/', $basename ) ) {
				$files_key = $obj['key'];
			}
		}

		$restore_db    = in_array( $type, array( 'full', 'database' ), true ) && ! empty( $db_key );
		$restore_files = in_array( $type, array( 'full', 'files' ), true ) && ! empty( $files_key );

		if ( ! $restore_db && ! $restore_files ) {
			return new WP_Error( 'wps3b_nothing', __( 'No matching backup files found for the selected restore type.', 'wp-s3-backup-pro' ) );
		}

		// Delegate to the free plugin's restore with selective behavior
		// We use the same internal methods via WPS3B_Restore
		$temp_dir = WPS3B_TEMP_DIR;
		if ( ! is_dir( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}

		WPS3B_Logger::info( sprintf( 'Pro selective restore (%s) started for: %s', $type, $timestamp ) );

		// Don't use .maintenance file — it blocks admin-ajax.php and prevents progress polling

		try {
			$pro_settings = WPS3B_Pro::get_settings();

			if ( $restore_db ) {
				WPS3B_Logger::info( 'Downloading database from S3...' );
				$db_path = self::download( $s3, $db_key, $temp_dir );
				if ( is_wp_error( $db_path ) ) {
					throw new Exception( $db_path->get_error_message() );
				}
				// Decrypt if encryption was enabled
				if ( $pro_settings['encryption_enabled'] && ! empty( $pro_settings['encryption_password'] ) ) {
					$dec = WPS3B_Pro_Encryption::decrypt_file( $db_path, $pro_settings['encryption_password'] );
					if ( is_wp_error( $dec ) ) {
						throw new Exception( $dec->get_error_message() );
					}
				}
				WPS3B_Logger::info( 'Importing database...' );
				$result = self::import_database( $db_path );
				if ( is_wp_error( $result ) ) {
					throw new Exception( $result->get_error_message() );
				}
				@unlink( $db_path );
			}

			if ( $restore_files ) {
				WPS3B_Logger::info( 'Downloading files archive from S3...' );
				$files_path = self::download( $s3, $files_key, $temp_dir );
				if ( is_wp_error( $files_path ) ) {
					throw new Exception( $files_path->get_error_message() );
				}
				if ( $pro_settings['encryption_enabled'] && ! empty( $pro_settings['encryption_password'] ) ) {
					$dec = WPS3B_Pro_Encryption::decrypt_file( $files_path, $pro_settings['encryption_password'] );
					if ( is_wp_error( $dec ) ) {
						throw new Exception( $dec->get_error_message() );
					}
				}
				WPS3B_Logger::info( 'Extracting files...' );
				$result = self::extract_files( $files_path );
				if ( is_wp_error( $result ) ) {
					throw new Exception( $result->get_error_message() );
				}
				@unlink( $files_path );
			}

			WPS3B_Logger::success( 'Pro selective restore completed: ' . $timestamp );
			return true;

		} catch ( Exception $e ) {
			WPS3B_Logger::error( 'Pro restore failed: ' . $e->getMessage() );
			return new WP_Error( 'wps3b_restore_failed', $e->getMessage() );
		} finally {
			// No maintenance file to clean up
		}
	}

	/**
	 * Serialization-safe search and replace for URLs in the database.
	 */
	public static function search_replace_urls( $old_url, $new_url ) {
		global $wpdb;

		$old_url = untrailingslashit( $old_url );
		$new_url = untrailingslashit( $new_url );

		$tables = $wpdb->get_col(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $wpdb->prefix ) . '%' )
		);

		$total_replaced = 0;

		foreach ( $tables as $table ) {
			$columns = $wpdb->get_results( "SHOW COLUMNS FROM `{$table}`", ARRAY_A );
			$text_columns = array();
			foreach ( $columns as $col ) {
				if ( preg_match( '/(text|varchar|longtext|mediumtext|char)/i', $col['Type'] ) ) {
					$text_columns[] = $col['Field'];
				}
			}

			if ( empty( $text_columns ) ) {
				continue;
			}

			// Get primary key
			$pk_col = null;
			foreach ( $columns as $col ) {
				if ( 'PRI' === $col['Key'] ) {
					$pk_col = $col['Field'];
					break;
				}
			}

			if ( ! $pk_col ) {
				// No primary key — do simple UPDATE
				foreach ( $text_columns as $col_name ) {
					$wpdb->query( $wpdb->prepare(
						"UPDATE `{$table}` SET `{$col_name}` = REPLACE(`{$col_name}`, %s, %s) WHERE `{$col_name}` LIKE %s",
						$old_url, $new_url, '%' . $wpdb->esc_like( $old_url ) . '%'
					));
				}
				continue;
			}

			// Serialization-safe: fetch rows, unserialize, replace, re-serialize
			foreach ( $text_columns as $col_name ) {
				$rows = $wpdb->get_results( $wpdb->prepare(
					"SELECT `{$pk_col}`, `{$col_name}` FROM `{$table}` WHERE `{$col_name}` LIKE %s",
					'%' . $wpdb->esc_like( $old_url ) . '%'
				), ARRAY_A );

				foreach ( $rows as $row ) {
					$value   = $row[ $col_name ];
					$new_val = self::recursive_unserialize_replace( $old_url, $new_url, $value );
					if ( $new_val !== $value ) {
						$wpdb->update( $table, array( $col_name => $new_val ), array( $pk_col => $row[ $pk_col ] ) );
						$total_replaced++;
					}
				}
			}

			if ( function_exists( 'set_time_limit' ) ) {
				@set_time_limit( 300 );
			}
		}

		WPS3B_Logger::info( sprintf( 'URL replacement: %s → %s (%d updates)', $old_url, $new_url, $total_replaced ) );
		return $total_replaced;
	}

	/**
	 * Recursively unserialize, replace, and re-serialize data.
	 */
	private static function recursive_unserialize_replace( $search, $replace, $data ) {
		$unserialized = @unserialize( $data );
		if ( false !== $unserialized && $data !== $unserialized ) {
			$replaced = self::recursive_replace( $search, $replace, $unserialized );
			return serialize( $replaced );
		}
		if ( is_string( $data ) ) {
			return str_replace( $search, $replace, $data );
		}
		return $data;
	}

	/**
	 * Recursively replace in arrays/objects.
	 */
	private static function recursive_replace( $search, $replace, $data ) {
		if ( is_string( $data ) ) {
			return str_replace( $search, $replace, $data );
		}
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				$data[ $key ] = self::recursive_replace( $search, $replace, $value );
			}
			return $data;
		}
		if ( is_object( $data ) ) {
			foreach ( $data as $key => $value ) {
				$data->$key = self::recursive_replace( $search, $replace, $value );
			}
			return $data;
		}
		return $data;
	}

	/**
	 * AJAX: List backups from a different S3 prefix.
	 */
	public static function ajax_list_external_backups() {
		check_ajax_referer( 'wps3b_pro_ajax', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$ext_prefix = sanitize_text_field( wp_unslash( $_POST['external_prefix'] ?? '' ) );
		if ( empty( $ext_prefix ) ) {
			wp_send_json_error( __( 'Please enter a path prefix.', 'wp-s3-backup-pro' ) );
		}

		$ext_prefix = trailingslashit( $ext_prefix );

		$s3 = WPS3B_Backup_Manager::get_s3_client();
		if ( is_wp_error( $s3 ) ) {
			wp_send_json_error( $s3->get_error_message() );
		}

		$objects = $s3->list_objects( $ext_prefix );
		if ( is_wp_error( $objects ) ) {
			wp_send_json_error( $objects->get_error_message() );
		}

		if ( empty( $objects ) ) {
			wp_send_json_error( __( 'No backup files found at that prefix.', 'wp-s3-backup-pro' ) );
		}

		// Group by timestamp
		$backups = array();
		foreach ( $objects as $obj ) {
			$basename = str_replace( $ext_prefix, '', $obj['key'] );
			if ( preg_match( '/^(\d{4}-\d{2}-\d{2}-\d{6})/', $basename, $m ) ) {
				$ts = $m[1];
				if ( ! isset( $backups[ $ts ] ) ) {
					$backups[ $ts ] = array(
						'timestamp'  => $ts,
						'date'       => substr( $ts, 0, 10 ) . ' ' . substr( $ts, 11, 2 ) . ':' . substr( $ts, 13, 2 ) . ':' . substr( $ts, 15, 2 ),
						'files'      => array(),
						'total_size' => 0,
					);
				}
				$backups[ $ts ]['files'][] = basename( $obj['key'] ) . ' (' . size_format( $obj['size'] ) . ')';
				$backups[ $ts ]['total_size'] += $obj['size'];
			}
		}

		krsort( $backups );
		wp_send_json_success( array_values( $backups ) );
	}

	/**
	 * Handle restore from external prefix.
	 */
	public static function handle_external_restore() {
		if ( ! isset( $_POST['wps3b_pro_external_restore'] ) ) {
			return;
		}
		check_admin_referer( 'wps3b_pro_external_restore', 'wps3b_pro_ext_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$ext_prefix   = trailingslashit( sanitize_text_field( wp_unslash( $_POST['wps3b_ext_prefix'] ?? '' ) ) );
		$timestamp    = sanitize_text_field( wp_unslash( $_POST['wps3b_ext_timestamp'] ?? '' ) );
		$restore_type = sanitize_key( wp_unslash( $_POST['wps3b_ext_restore_type'] ?? 'full' ) );
		$old_url      = esc_url_raw( wp_unslash( $_POST['wps3b_ext_old_url'] ?? '' ) );
		$new_url      = esc_url_raw( wp_unslash( $_POST['wps3b_ext_new_url'] ?? '' ) );

		if ( empty( $ext_prefix ) || empty( $timestamp ) ) {
			add_settings_error( 'wps3b_backups', 'ext_missing', 'Missing prefix or timestamp.', 'error' );
			return;
		}

		// Use the background restore system
		update_option( 'wps3b_restore_status', array(
			'running'      => true,
			'timestamp'    => $timestamp,
			'prefix'       => $ext_prefix,
			'restore_type' => $restore_type,
			'old_url'      => $old_url,
			'new_url'      => $new_url,
			'step'         => 'queued',
			'message'      => 'External restore queued...',
			'progress'     => 0,
			'started'      => time(),
			'steps'        => array(),
			'error'        => '',
		) );

		if ( ! wp_next_scheduled( 'wps3b_run_background_restore' ) ) {
			wp_schedule_single_event( time(), 'wps3b_run_background_restore' );
		}
		spawn_cron();

		wp_safe_redirect( admin_url( 'admin.php?page=wps3b_backups' ) );
		exit;
	}

	/**
	 * AJAX: Upload a backup file (db.sql.gz or files.zip) to the temp directory.
	 */
	public static function ajax_upload_backup() {
		check_ajax_referer( 'wps3b_pro_ajax', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		if ( empty( $_FILES['backup_file'] ) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK ) {
			wp_send_json_error( __( 'Upload failed.', 'wp-s3-backup-pro' ) );
		}

		$file = $_FILES['backup_file'];
		$name = sanitize_file_name( $file['name'] );
		$ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );

		// Validate: only .gz (database) or .zip (files) or .json (manifest)
		$allowed = array( 'gz', 'zip', 'json' );
		if ( ! in_array( $ext, $allowed, true ) ) {
			wp_send_json_error( __( 'Only .sql.gz, .zip, and .json backup files are allowed.', 'wp-s3-backup-pro' ) );
		}

		$temp_dir = WPS3B_TEMP_DIR;
		if ( ! is_dir( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}

		$dest = $temp_dir . '/' . $name;
		if ( ! move_uploaded_file( $file['tmp_name'], $dest ) ) {
			wp_send_json_error( __( 'Failed to save uploaded file.', 'wp-s3-backup-pro' ) );
		}

		// Determine file type
		$type = 'unknown';
		if ( preg_match( '/db\.sql\.gz$/', $name ) ) {
			$type = 'database';
		} elseif ( preg_match( '/files\.zip$/', $name ) ) {
			$type = 'files';
		} elseif ( preg_match( '/manifest\.json$/', $name ) ) {
			$type = 'manifest';
		}

		wp_send_json_success( array(
			'file_name' => $name,
			'file_path' => $dest,
			'file_size' => size_format( filesize( $dest ) ),
			'file_type' => $type,
		) );
	}

	/**
	 * Handle restore from uploaded backup files.
	 */
	public static function handle_upload_restore() {
		if ( ! isset( $_POST['wps3b_pro_upload_restore'] ) ) {
			return;
		}
		check_admin_referer( 'wps3b_pro_upload_restore', 'wps3b_pro_upload_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$restore_type = sanitize_key( wp_unslash( $_POST['wps3b_upload_restore_type'] ?? 'full' ) );
		$old_url      = esc_url_raw( wp_unslash( $_POST['wps3b_upload_old_url'] ?? '' ) );
		$new_url      = esc_url_raw( wp_unslash( $_POST['wps3b_upload_new_url'] ?? '' ) );
		$db_file      = sanitize_text_field( wp_unslash( $_POST['wps3b_upload_db_path'] ?? '' ) );
		$files_file   = sanitize_text_field( wp_unslash( $_POST['wps3b_upload_files_path'] ?? '' ) );

		$temp_dir      = realpath( WPS3B_TEMP_DIR );
		$restore_db    = in_array( $restore_type, array( 'full', 'database' ), true ) && ! empty( $db_file );
		$restore_files = in_array( $restore_type, array( 'full', 'files' ), true ) && ! empty( $files_file );

		if ( $restore_db ) {
			$real_db = realpath( $db_file );
			if ( ! $real_db || 0 !== strpos( $real_db, $temp_dir ) ) {
				add_settings_error( 'wps3b_backups', 'upload_invalid', 'Invalid database file path.', 'error' );
				return;
			}
		}
		if ( $restore_files ) {
			$real_files = realpath( $files_file );
			if ( ! $real_files || 0 !== strpos( $real_files, $temp_dir ) ) {
				add_settings_error( 'wps3b_backups', 'upload_invalid', 'Invalid files archive path.', 'error' );
				return;
			}
		}

		if ( ! $restore_db && ! $restore_files ) {
			add_settings_error( 'wps3b_backups', 'upload_empty', 'No backup files uploaded.', 'error' );
			return;
		}

		// Use background restore with upload mode
		update_option( 'wps3b_restore_status', array(
			'running'      => true,
			'timestamp'    => 'upload-' . time(),
			'prefix'       => '',
			'restore_type' => $restore_type,
			'old_url'      => $old_url,
			'new_url'      => $new_url,
			'upload_db'    => $restore_db ? $db_file : '',
			'upload_files' => $restore_files ? $files_file : '',
			'step'         => 'queued',
			'message'      => 'Upload restore queued...',
			'progress'     => 0,
			'started'      => time(),
			'steps'        => array(),
			'error'        => '',
		) );

		if ( ! wp_next_scheduled( 'wps3b_run_background_restore' ) ) {
			wp_schedule_single_event( time(), 'wps3b_run_background_restore' );
		}
		spawn_cron();

		wp_safe_redirect( admin_url( 'admin.php?page=wps3b_backups' ) );
		exit;
	}

	/**
	 * Download a file from S3 via pre-signed URL.
	 */
	private static function download( $s3, $s3_key, $temp_dir ) {
		$url        = $s3->get_presigned_url( $s3_key, 3600 );
		$local_path = $temp_dir . '/' . basename( $s3_key );

		$response = wp_remote_get( $url, array(
			'timeout'  => 600,
			'stream'   => true,
			'filename' => $local_path,
		));

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			@unlink( $local_path );
			return new WP_Error( 'wps3b_download', sprintf( __( 'Download failed (HTTP %d).', 'wp-s3-backup-pro' ), $code ) );
		}

		return $local_path;
	}

	/**
	 * Import gzipped SQL dump.
	 */
	private static function import_database( $gz_path ) {
		global $wpdb;

		$gz = gzopen( $gz_path, 'rb' );
		if ( ! $gz ) {
			return new WP_Error( 'wps3b_gz_open', __( 'Could not open database dump.', 'wp-s3-backup-pro' ) );
		}

		$wpdb->query( 'SET foreign_key_checks = 0' );
		$statement = '';

		while ( ! gzeof( $gz ) ) {
			$line = gzgets( $gz );
			if ( false === $line ) {
				break;
			}
			$trimmed = trim( $line );
			if ( empty( $trimmed ) || 0 === strpos( $trimmed, '--' ) || 0 === strpos( $trimmed, '/*' ) ) {
				continue;
			}
			$statement .= $line;
			if ( preg_match( '/;\s*$/', $trimmed ) ) {
				$wpdb->query( $statement );
				$statement = '';
			}
			if ( function_exists( 'set_time_limit' ) ) {
				@set_time_limit( 300 );
			}
		}

		gzclose( $gz );
		$wpdb->query( 'SET foreign_key_checks = 1' );
		return true;
	}

	/**
	 * Extract files zip archive.
	 */
	private static function extract_files( $zip_path ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'wps3b_zip', __( 'ZipArchive extension required.', 'wp-s3-backup-pro' ) );
		}

		$zip = new ZipArchive();
		if ( true !== $zip->open( $zip_path ) ) {
			return new WP_Error( 'wps3b_zip', __( 'Could not open archive.', 'wp-s3-backup-pro' ) );
		}

		$extract_to = dirname( WP_CONTENT_DIR );
		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$entry = $zip->getNameIndex( $i );
			if ( false !== strpos( $entry, '..' ) || 0 !== strpos( $entry, 'wp-content/' ) ) {
				continue;
			}
			$target = $extract_to . '/' . $entry;
			if ( '/' === substr( $entry, -1 ) ) {
				wp_mkdir_p( $target );
				continue;
			}
			wp_mkdir_p( dirname( $target ) );
			$content = $zip->getFromIndex( $i );
			if ( false !== $content ) {
				file_put_contents( $target, $content );
			}
			if ( function_exists( 'set_time_limit' ) ) {
				@set_time_limit( 300 );
			}
		}

		$zip->close();
		return true;
	}
}
