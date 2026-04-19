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
		add_action( 'wp_ajax_wps3b_get_logs', array( __CLASS__, 'ajax_get_logs' ) );
		add_action( 'wp_ajax_wps3b_backup_step', array( __CLASS__, 'ajax_backup_step' ) );
		add_action( 'wp_ajax_wps3b_delete_backup', array( __CLASS__, 'ajax_delete_backup' ) );
		add_action( 'wp_ajax_wps3b_list_prefixes', array( __CLASS__, 'ajax_list_prefixes' ) );
		add_action( 'wp_ajax_wps3b_restore_step', array( __CLASS__, 'ajax_restore_step' ) );
	}

	/**
	 * Get all settings with defaults.
	 *
	 * @return array
	 */
	public static function get_settings() {
		return wp_parse_args( get_option( self::SETTINGS_KEY, array() ), array(
			'bucket'          => '',
			'region'          => 'us-east-1',
			's3_prefix'       => '',
			'custom_endpoint' => '',
			'frequency'       => 'daily',
			'enabled'         => 0,
			'backup_db'       => 1,
			'backup_files'    => 1,
			'exclude_paths'   => 'cache,ai1wm-backups,updraft,node_modules,upgrade,wps3b-temp',
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
			'bucket'          => sanitize_text_field( wp_unslash( $_POST['wps3b_bucket'] ?? '' ) ),
			'region'          => sanitize_text_field( wp_unslash( $_POST['wps3b_region'] ?? 'us-east-1' ) ),
			's3_prefix'       => sanitize_text_field( wp_unslash( $_POST['wps3b_s3_prefix'] ?? '' ) ),
			'custom_endpoint' => esc_url_raw( wp_unslash( $_POST['wps3b_custom_endpoint'] ?? '' ) ),
			'frequency'       => sanitize_key( wp_unslash( $_POST['wps3b_frequency'] ?? 'daily' ) ),
			'enabled'         => isset( $_POST['wps3b_enabled'] ) ? 1 : 0,
			'backup_db'       => isset( $_POST['wps3b_backup_db'] ) ? 1 : 0,
			'backup_files'    => isset( $_POST['wps3b_backup_files'] ) ? 1 : 0,
			'exclude_paths'   => sanitize_text_field( wp_unslash( $_POST['wps3b_exclude_paths'] ?? '' ) ),
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
		$valid_frequencies = array( 'hourly', 'every_4_hours', 'every_6_hours', 'twicedaily', 'daily', 'weekly', 'monthly' );
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
	 * AJAX handler for refreshing logs.
	 */
	public static function ajax_get_logs() {
		check_ajax_referer( 'wps3b_refresh_logs', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Unauthorized.', 'wp-s3-backup' ) );
		}

		$entries = WPS3B_Logger::get_entries();

		ob_start();
		WPS3B_Logger::render_table( $entries );
		$html = ob_get_clean();

		wp_send_json_success( array(
			'html'  => $html,
			'count' => count( $entries ),
		) );
	}

	/**
	 * AJAX handler for step-based backup.
	 */
	public static function ajax_backup_step() {
		check_ajax_referer( 'wps3b_backup_step', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$action = sanitize_key( $_POST['step'] ?? '' );

		switch ( $action ) {

			case 'start':
				// Check if already running
				$status = get_option( 'wps3b_backup_status', array() );
				if ( ! empty( $status['running'] ) ) {
					wp_send_json_error( 'A backup is already in progress.' );
				}

				// Verify S3 connection first
				$s3 = WPS3B_Backup_Manager::get_s3_client();
				if ( is_wp_error( $s3 ) ) {
					wp_send_json_error( $s3->get_error_message() );
				}

				// Initialize status
				$ts = gmdate( 'Y-m-d-His' );
				update_option( 'wps3b_backup_status', array(
					'running'   => true,
					'timestamp' => $ts,
					'step'      => 'queued',
					'message'   => 'Backup queued...',
					'progress'  => 0,
					'started'   => time(),
					'steps'     => array(),
					'error'     => '',
				) );

				// Schedule immediate cron run
				if ( ! wp_next_scheduled( 'wps3b_run_background_backup' ) ) {
					wp_schedule_single_event( time(), 'wps3b_run_background_backup' );
				}

				// Trigger cron
				spawn_cron();

				wp_send_json_success( array( 'message' => 'Backup started.' ) );
				break;

			case 'poll':
				$status = get_option( 'wps3b_backup_status', array() );

				// Fallback: if cron hasn't picked up the backup within 10s, run it directly
				if ( ! empty( $status['running'] ) && 'queued' === ( $status['step'] ?? '' ) ) {
					$queued_at = $status['started'] ?? 0;
					if ( $queued_at && ( time() - $queued_at ) > 10 ) {
						wp_clear_scheduled_hook( 'wps3b_run_background_backup' );
						self::update_backup_status( 'starting', 'Starting backup...', 1 );

						ignore_user_abort( true );
						header( 'Content-Type: application/json; charset=utf-8' );
						echo wp_json_encode( array( 'success' => true, 'data' => get_option( 'wps3b_backup_status', array() ) ) );
						if ( function_exists( 'fastcgi_finish_request' ) ) {
							fastcgi_finish_request();
						} else {
							ob_end_flush();
							flush();
						}

						self::run_background_backup();
						exit;
					}
				}

				wp_send_json_success( $status );
				break;

			case 'cancel':
				wp_clear_scheduled_hook( 'wps3b_run_background_backup' );
				delete_option( 'wps3b_backup_status' );
				wp_send_json_success();
				break;

			case 'dismiss':
				delete_option( 'wps3b_backup_status' );
				wp_send_json_success();
				break;

			default:
				wp_send_json_error( 'Unknown action.' );
		}
	}

	/**
	 * Run backup in the background via wp-cron.
	 */
	public static function run_background_backup() {
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 600 );
		}

		$status = get_option( 'wps3b_backup_status', array() );
		if ( empty( $status['running'] ) ) {
			return;
		}

		$ts       = $status['timestamp'];
		$settings = get_option( 'wps3b_settings', array() );
		$excludes = isset( $settings['exclude_paths'] ) ? array_map( 'trim', explode( ',', $settings['exclude_paths'] ) ) : array();
		$do_db    = ! empty( $settings['backup_db'] );
		$do_files = ! empty( $settings['backup_files'] );

		$engine  = new WPS3B_Backup_Engine( $excludes, $ts );
		$s3      = WPS3B_Backup_Manager::get_s3_client();
		$prefix  = WPS3B_Backup_Manager::get_s3_prefix();

		if ( is_wp_error( $s3 ) ) {
			self::update_backup_status( 'error', $s3->get_error_message(), 0, true );
			return;
		}

		WPS3B_Logger::info( 'Background backup started.' );
		do_action( 'wps3b_before_backup' );

		$db_info    = null;
		$files_info = null;

		try {
			// Step 1: Export database
			if ( $do_db ) {
				self::update_backup_status( 'export_db', 'Exporting database...', 10 );
				$db_info = $engine->export_database();
				if ( is_wp_error( $db_info ) ) {
					throw new Exception( $db_info->get_error_message() );
				}
				$msg = sprintf( 'Database exported: %d tables, %s rows (%s)', $db_info['tables'], number_format( $db_info['rows'] ), size_format( filesize( $db_info['path'] ) ) );
				WPS3B_Logger::info( $msg );
				self::add_backup_step( $msg );
			}

			// Step 2: Export files
			if ( $do_files ) {
				self::update_backup_status( 'export_files', 'Archiving files...', 25 );
				$files_info = $engine->export_files();
				if ( is_wp_error( $files_info ) ) {
					throw new Exception( $files_info->get_error_message() );
				}
				$msg = sprintf( 'Files archived: %s files (%s)', number_format( $files_info['files'] ), size_format( filesize( $files_info['path'] ) ) );
				WPS3B_Logger::info( $msg );
				self::add_backup_step( $msg );
			}

			// Step 3: Upload database
			if ( $db_info ) {
				self::update_backup_status( 'upload_db', 'Uploading database to S3...', 45 );
				do_action( 'wps3b_before_upload', $db_info['path'] );
				$result = $s3->upload_file( $db_info['path'], $prefix . $db_info['filename'], 'application/gzip' );
				if ( is_wp_error( $result ) ) {
					throw new Exception( 'DB upload failed: ' . $result->get_error_message() );
				}
				do_action( 'wps3b_after_upload', $prefix . $db_info['filename'] );
				WPS3B_Logger::info( 'Database uploaded to S3.' );
				self::add_backup_step( 'Database uploaded to S3.' );
			}

			// Step 4: Upload files
			if ( $files_info ) {
				self::update_backup_status( 'upload_files', 'Uploading files to S3...', 65 );
				do_action( 'wps3b_before_upload', $files_info['path'] );
				$result = $s3->upload_file( $files_info['path'], $prefix . $files_info['filename'], 'application/zip' );
				if ( is_wp_error( $result ) ) {
					throw new Exception( 'Files upload failed: ' . $result->get_error_message() );
				}
				do_action( 'wps3b_after_upload', $prefix . $files_info['filename'] );
				WPS3B_Logger::info( 'Files uploaded to S3.' );
				self::add_backup_step( 'Files uploaded to S3.' );
			}

			// Step 5: Manifest
			self::update_backup_status( 'manifest', 'Finalizing manifest...', 90 );
			$manifest = $engine->generate_manifest( $db_info, $files_info );
			if ( is_wp_error( $manifest ) ) {
				throw new Exception( $manifest->get_error_message() );
			}
			$result = $s3->upload_file( $manifest['path'], $prefix . $manifest['filename'], 'application/json' );
			if ( is_wp_error( $result ) ) {
				throw new Exception( 'Manifest upload failed: ' . $result->get_error_message() );
			}

			// Done
			update_option( 'wps3b_last_backup', current_time( 'mysql' ) );
			delete_option( 'wps3b_last_error' );
			do_action( 'wps3b_after_backup', $manifest['data'] );
			WPS3B_Logger::success( 'Backup completed successfully.' );
			self::add_backup_step( 'Backup complete!' );
			self::update_backup_status( 'complete', 'Backup complete!', 100, true );

		} catch ( Exception $e ) {
			$error_msg = $e->getMessage();
			update_option( 'wps3b_last_error', $error_msg );
			WPS3B_Logger::error( 'Backup failed: ' . $error_msg );
			do_action( 'wps3b_backup_failed', new WP_Error( 'wps3b_backup_failed', $error_msg ) );
			self::update_backup_status( 'error', $error_msg, 0, true );
		} finally {
			$engine->cleanup();
		}
	}

	/**
	 * Update the backup status option.
	 */
	private static function update_backup_status( $step, $message, $progress, $finished = false ) {
		$status = get_option( 'wps3b_backup_status', array() );
		$status['step']     = $step;
		$status['message']  = $message;
		$status['progress'] = $progress;
		if ( $finished ) {
			$status['running'] = false;
		}
		if ( 'error' === $step ) {
			$status['error']   = $message;
			$status['running'] = false;
		}
		update_option( 'wps3b_backup_status', $status );
	}

	/**
	 * Add a completed step to the status.
	 */
	private static function add_backup_step( $message ) {
		$status = get_option( 'wps3b_backup_status', array() );
		if ( ! isset( $status['steps'] ) ) {
			$status['steps'] = array();
		}
		$status['steps'][] = $message;
		update_option( 'wps3b_backup_status', $status );
	}

	/**
	 * AJAX handler for background restore.
	 */
	public static function ajax_restore_step() {
		check_ajax_referer( 'wps3b_backup_step', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$action = sanitize_key( $_POST['step'] ?? '' );

		switch ( $action ) {

			case 'start':
				$status = get_option( 'wps3b_restore_status', array() );
				if ( ! empty( $status['running'] ) ) {
					wp_send_json_error( 'A restore is already in progress.' );
				}

				$timestamp = sanitize_text_field( wp_unslash( $_POST['timestamp'] ?? '' ) );
				$prefix    = sanitize_text_field( wp_unslash( $_POST['prefix'] ?? '' ) );
				$old_url   = esc_url_raw( wp_unslash( $_POST['old_url'] ?? '' ) );
				$new_url   = esc_url_raw( wp_unslash( $_POST['new_url'] ?? '' ) );
				$type      = sanitize_key( wp_unslash( $_POST['restore_type'] ?? 'full' ) );

				if ( empty( $timestamp ) ) {
					wp_send_json_error( 'Missing timestamp.' );
				}

				update_option( 'wps3b_restore_status', array(
					'running'      => true,
					'timestamp'    => $timestamp,
					'prefix'       => $prefix,
					'restore_type' => $type,
					'old_url'      => $old_url,
					'new_url'      => $new_url,
					'step'         => 'queued',
					'message'      => 'Restore queued...',
					'progress'     => 0,
					'started'      => time(),
					'steps'        => array(),
					'error'        => '',
				) );

				if ( ! wp_next_scheduled( 'wps3b_run_background_restore' ) ) {
					wp_schedule_single_event( time(), 'wps3b_run_background_restore' );
				}
				spawn_cron();

				wp_send_json_success( array( 'message' => 'Restore started.' ) );
				break;

			case 'poll':
				$status = get_option( 'wps3b_restore_status', array() );

				// Chunked extraction: run next batch synchronously within this request
				if ( ! empty( $status['running'] ) && 'extract_chunk' === ( $status['step'] ?? '' ) ) {
					$zip_path = $status['extract_zip'] ?? '';
					if ( ! empty( $zip_path ) && file_exists( $zip_path ) ) {
						self::resume_restore_after_chunk();
						$status = get_option( 'wps3b_restore_status', array() );
					}
				}

				// Fallback: if cron hasn't picked up the restore within 10s, run it directly
				if ( ! empty( $status['running'] ) && 'queued' === ( $status['step'] ?? '' ) ) {
					$queued_at = $status['started'] ?? 0;
					if ( $queued_at && ( time() - $queued_at ) > 10 ) {
						// Cron failed — run restore directly
						wp_clear_scheduled_hook( 'wps3b_run_background_restore' );
						self::update_restore_status( 'starting', 'Starting restore...', 1 );

						// Send response manually (wp_send_json calls die)
						ignore_user_abort( true );
						header( 'Content-Type: application/json; charset=utf-8' );
						echo wp_json_encode( array( 'success' => true, 'data' => get_option( 'wps3b_restore_status', array() ) ) );
						if ( function_exists( 'fastcgi_finish_request' ) ) {
							fastcgi_finish_request();
						} else {
							ob_end_flush();
							flush();
						}

						self::run_background_restore();
						exit;
					}
				}

				wp_send_json_success( $status );
				break;

			case 'cancel':
				wp_clear_scheduled_hook( 'wps3b_run_background_restore' );
				delete_option( 'wps3b_restore_status' );
				@unlink( ABSPATH . '.maintenance' );
				wp_send_json_success();
				break;

			case 'dismiss':
				delete_option( 'wps3b_restore_status' );
				wp_send_json_success();
				break;

			default:
				wp_send_json_error( 'Unknown action.' );
		}
	}

	/**
	 * Run restore in the background via wp-cron.
	 */
	public static function run_background_restore() {
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 900 );
		}

		$status = get_option( 'wps3b_restore_status', array() );
		if ( empty( $status['running'] ) ) {
			return;
		}

		$timestamp    = $status['timestamp'];
		$custom_prefix = ! empty( $status['prefix'] ) ? trailingslashit( $status['prefix'] ) : '';
		$restore_type = ! empty( $status['restore_type'] ) ? $status['restore_type'] : 'full';
		$old_url      = $status['old_url'] ?? '';
		$new_url      = $status['new_url'] ?? '';
		$upload_db    = $status['upload_db'] ?? '';
		$upload_files = $status['upload_files'] ?? '';
		$is_upload    = ! empty( $upload_db ) || ! empty( $upload_files );

		$temp_dir = WPS3B_TEMP_DIR;
		if ( ! is_dir( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}

		$do_db    = in_array( $restore_type, array( 'full', 'database' ), true );
		$do_files = in_array( $restore_type, array( 'full', 'files' ), true );

		WPS3B_Logger::info( 'Background restore started for: ' . $timestamp );
		// Don't use .maintenance file — it blocks admin-ajax.php and prevents progress polling

		try {
			if ( $is_upload ) {
				// Upload mode — files already on disk
				if ( $do_db && ! empty( $upload_db ) ) {
					self::update_restore_status( 'import_db', 'Importing uploaded database...', 20 );
					$result = self::restore_import_db( $upload_db );
					if ( is_wp_error( $result ) ) {
						throw new Exception( $result->get_error_message() );
					}
					@unlink( $upload_db );
					self::add_restore_step( 'Database imported successfully.' );
				}
				if ( $do_files && ! empty( $upload_files ) ) {
					self::update_restore_status( 'extract_files', 'Extracting uploaded files...', 55 );
					$result = self::restore_extract_files_chunked( $upload_files );
					if ( is_wp_error( $result ) ) {
						throw new Exception( $result->get_error_message() );
					}
					if ( 'continue' === $result ) {
						return;
					}
					@unlink( $upload_files );
					self::add_restore_step( 'Files restored successfully.' );
				}
			} else {
				// S3 mode — download then process
				$s3 = WPS3B_Backup_Manager::get_s3_client();
				if ( is_wp_error( $s3 ) ) {
					throw new Exception( $s3->get_error_message() );
				}

				$prefix  = ! empty( $custom_prefix ) ? $custom_prefix : WPS3B_Backup_Manager::get_s3_prefix();
				$local_prefix = WPS3B_Backup_Manager::get_s3_prefix();
				$objects = $s3->list_objects( $prefix . $timestamp );
				if ( is_wp_error( $objects ) || empty( $objects ) ) {
					throw new Exception( 'No backup files found at ' . $prefix . $timestamp );
				}

				$db_key    = '';
				$files_key = '';
				foreach ( $objects as $obj ) {
					$bn = basename( $obj['key'] );
					if ( preg_match( '/db\.sql\.gz$/', $bn ) ) { $db_key = $obj['key']; }
					elseif ( preg_match( '/files\.zip$/', $bn ) ) { $files_key = $obj['key']; }
				}

				// If restoring from a different prefix, copy files to local prefix first (S3 server-side copy)
				if ( ! empty( $custom_prefix ) && $custom_prefix !== $local_prefix ) {
					self::update_restore_status( 'copy_s3', 'Copying backup files within S3...', 5 );
					WPS3B_Logger::info( 'Copying backup from ' . $prefix . ' to ' . $local_prefix );

					foreach ( $objects as $obj ) {
						$source = $obj['key'];
						$dest   = $local_prefix . basename( $source );
						$copy_result = $s3->copy_object( $source, $dest );
						if ( is_wp_error( $copy_result ) ) {
							throw new Exception( 'S3 copy failed for ' . basename( $source ) . ': ' . $copy_result->get_error_message() );
						}
					}

					// Update keys to point to local copies
					if ( ! empty( $db_key ) ) { $db_key = $local_prefix . basename( $db_key ); }
					if ( ! empty( $files_key ) ) { $files_key = $local_prefix . basename( $files_key ); }
					self::add_restore_step( 'Backup copied to local prefix via S3.' );
				}

				if ( $do_db && ! empty( $db_key ) ) {
					self::update_restore_status( 'download_db', 'Downloading database from S3...', 10 );
					$db_path = self::restore_download( $s3, $db_key, $temp_dir );
					if ( is_wp_error( $db_path ) ) { throw new Exception( $db_path->get_error_message() ); }
					self::add_restore_step( 'Database downloaded from S3.' );

					self::update_restore_status( 'import_db', 'Importing database...', 25 );
					$result = self::restore_import_db( $db_path );
					if ( is_wp_error( $result ) ) { throw new Exception( $result->get_error_message() ); }
					@unlink( $db_path );
					self::add_restore_step( 'Database imported successfully.' );
				}

				if ( $do_files && ! empty( $files_key ) ) {
					self::update_restore_status( 'download_files', 'Downloading files from S3...', 45 );
					$files_path = self::restore_download( $s3, $files_key, $temp_dir );
					if ( is_wp_error( $files_path ) ) { throw new Exception( $files_path->get_error_message() ); }
					self::add_restore_step( 'Files downloaded from S3.' );

					self::update_restore_status( 'extract_files', 'Extracting files...', 70 );
					$result = self::restore_extract_files_chunked( $files_path );
					if ( is_wp_error( $result ) ) { throw new Exception( $result->get_error_message() ); }
					if ( 'continue' === $result ) {
						// Chunked extraction in progress — exit and let poll trigger next batch
						return;
					}
					@unlink( $files_path );
					self::add_restore_step( 'Files restored successfully.' );
				}
			}

			// URL replacement (Pro feature)
			if ( $do_db && ! empty( $old_url ) && ! empty( $new_url ) && $old_url !== $new_url ) {
				self::update_restore_status( 'url_replace', 'Replacing URLs...', 92 );
				if ( class_exists( 'WPS3B_Pro_Restore' ) ) {
					WPS3B_Pro_Restore::search_replace_urls( $old_url, $new_url );
					self::add_restore_step( 'URLs replaced.' );
				}
			}

			WPS3B_Logger::success( 'Background restore completed for: ' . $timestamp );
			self::add_restore_step( 'Restore complete!' );
			self::update_restore_status( 'complete', 'Restore complete!', 100, true );

		} catch ( Exception $e ) {
			WPS3B_Logger::error( 'Restore failed: ' . $e->getMessage() );
			self::update_restore_status( 'error', $e->getMessage(), 0, true );
		} finally {
			// Only clean up temp if not in chunked extraction mode
			$final_status = get_option( 'wps3b_restore_status', array() );
			if ( 'extract_chunk' !== ( $final_status['step'] ?? '' ) ) {
				$tfiles = glob( $temp_dir . '/*' );
				if ( $tfiles ) {
					foreach ( $tfiles as $f ) {
						if ( is_file( $f ) && ! in_array( basename( $f ), array( '.htaccess', 'index.php' ), true ) ) {
							@unlink( $f );
						}
					}
				}
			}
		}
	}

		private static function update_restore_status( $step, $message, $progress, $finished = false ) {
		$status = get_option( 'wps3b_restore_status', array() );
		$status['step']     = $step;
		$status['message']  = $message;
		$status['progress'] = $progress;
		if ( $finished ) {
			$status['running'] = false;
		}
		if ( 'error' === $step ) {
			$status['error']   = $message;
			$status['running'] = false;
		}
		update_option( 'wps3b_restore_status', $status );
	}

	/**
	 * Add a completed step to restore status.
	 */
	private static function add_restore_step( $message ) {
		$status = get_option( 'wps3b_restore_status', array() );
		if ( ! isset( $status['steps'] ) ) {
			$status['steps'] = array();
		}
		$status['steps'][] = $message;
		update_option( 'wps3b_restore_status', $status );
	}

	/**
	 * Download a file from S3 for restore.
	 */
	private static function restore_download( $s3, $s3_key, $temp_dir ) {
		$url        = $s3->get_presigned_url( $s3_key, 3600 );
		$local_path = $temp_dir . '/' . basename( $s3_key );

		$response = wp_remote_get( $url, array(
			'timeout'  => 600,
			'stream'   => true,
			'filename' => $local_path,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			@unlink( $local_path );
			return new WP_Error( 'download_failed', sprintf( 'Download failed (HTTP %d).', $code ) );
		}

		return $local_path;
	}

	/**
	 * Import gzipped SQL dump for restore.
	 */
	private static function restore_import_db( $gz_path ) {
		global $wpdb;

		$gz = gzopen( $gz_path, 'rb' );
		if ( ! $gz ) {
			return new WP_Error( 'gz_open', 'Could not open database dump.' );
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
	 * Extract files zip in chunks to avoid execution time limits.
	 *
	 * @param string $zip_path Path to the zip file.
	 * @param int    $offset   File index to start from.
	 * @return true|string|WP_Error True if done, 'continue' if more chunks needed.
	 */
	private static function restore_extract_files_chunked( $zip_path, $offset = 0 ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'zip_missing', 'ZipArchive extension required.' );
		}

		$zip = new ZipArchive();
		if ( true !== $zip->open( $zip_path ) ) {
			return new WP_Error( 'zip_open', 'Could not open archive.' );
		}

		$total      = $zip->numFiles;
		$extract_to = dirname( WP_CONTENT_DIR );
		$batch_size = 200;
		$end        = min( $offset + $batch_size, $total );

		// Collect safe entry names for this batch
		$entries = array();
		for ( $i = $offset; $i < $end; $i++ ) {
			$entry = $zip->getNameIndex( $i );
			if ( false !== strpos( $entry, '..' ) || 0 !== strpos( $entry, 'wp-content/' ) ) {
				continue;
			}
			$entries[] = $entry;
		}

		// Extract entire batch at once using native extractTo
		if ( ! empty( $entries ) ) {
			$zip->extractTo( $extract_to, $entries );
		}

		$zip->close();

		if ( $end < $total ) {
			$pct = 70 + (int) ( ( $end / $total ) * 20 );
			$msg = sprintf( 'Extracting files... %s / %s', number_format( $end ), number_format( $total ) );
			$status = get_option( 'wps3b_restore_status', array() );
			$status['step']           = 'extract_chunk';
			$status['message']        = $msg;
			$status['progress']       = $pct;
			$status['extract_zip']    = $zip_path;
			$status['extract_offset'] = $end;
			$status['extract_total']  = $total;
			update_option( 'wps3b_restore_status', $status );
			return 'continue';
		}

		return true;
	}

	/**
	 * Resume restore after a chunked extraction batch completes.
	 */
	private static function resume_restore_after_chunk() {
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 300 );
		}

		$status  = get_option( 'wps3b_restore_status', array() );
		$zip_path = $status['extract_zip'] ?? '';
		$offset   = $status['extract_offset'] ?? 0;

		$result = self::restore_extract_files_chunked( $zip_path, $offset );

		if ( is_wp_error( $result ) ) {
			WPS3B_Logger::error( 'Restore failed: ' . $result->get_error_message() );
			self::update_restore_status( 'error', $result->get_error_message(), 0, true );
			return;
		}

		if ( 'continue' === $result ) {
			// More chunks needed — poll will trigger next batch
			return;
		}

		// Extraction complete — clean up zip and continue restore
		@unlink( $zip_path );
		self::add_restore_step( 'Files restored successfully.' );

		// Clean up chunk tracking
		$status = get_option( 'wps3b_restore_status', array() );
		unset( $status['extract_zip'], $status['extract_offset'], $status['extract_total'] );
		update_option( 'wps3b_restore_status', $status );

		// Continue with URL replacement and completion
		$old_url = $status['old_url'] ?? '';
		$new_url = $status['new_url'] ?? '';
		$restore_type = $status['restore_type'] ?? 'full';
		$do_db = in_array( $restore_type, array( 'full', 'database' ), true );

		try {
			if ( $do_db && ! empty( $old_url ) && ! empty( $new_url ) && $old_url !== $new_url ) {
				self::update_restore_status( 'url_replace', 'Replacing URLs...', 92 );
				if ( class_exists( 'WPS3B_Pro_Restore' ) ) {
					WPS3B_Pro_Restore::search_replace_urls( $old_url, $new_url );
					self::add_restore_step( 'URLs replaced.' );
				}
			}

			WPS3B_Logger::success( 'Background restore completed for: ' . $status['timestamp'] );
			self::add_restore_step( 'Restore complete!' );
			self::update_restore_status( 'complete', 'Restore complete!', 100, true );

		} catch ( Exception $e ) {
			WPS3B_Logger::error( 'Restore failed: ' . $e->getMessage() );
			self::update_restore_status( 'error', $e->getMessage(), 0, true );
		} finally {
			$temp_dir = WPS3B_TEMP_DIR;
			$tfiles = glob( $temp_dir . '/*' );
			if ( $tfiles ) {
				foreach ( $tfiles as $f ) {
					if ( is_file( $f ) && ! in_array( basename( $f ), array( '.htaccess', 'index.php' ), true ) ) {
						@unlink( $f );
					}
				}
			}
		}
	}

	/**
	 * AJAX handler for listing S3 prefixes (site folders) in the bucket.
	 */
	public static function ajax_list_prefixes() {
		if ( ! check_ajax_referer( 'wps3b_backup_step', 'nonce', false ) ) {
			check_ajax_referer( 'wps3b_pro_ajax', 'nonce' );
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$s3 = WPS3B_Backup_Manager::get_s3_client();
		if ( is_wp_error( $s3 ) ) {
			wp_send_json_error( $s3->get_error_message() );
		}

		// List all objects and extract unique top-level prefixes
		$objects = $s3->list_objects( '' );
		if ( is_wp_error( $objects ) ) {
			wp_send_json_error( $objects->get_error_message() );
		}

		$prefixes = array();
		$current  = rtrim( WPS3B_Backup_Manager::get_s3_prefix(), '/' );

		foreach ( $objects as $obj ) {
			$key = $obj['key'];
			// Extract prefix up to the timestamp part
			// e.g., "backups/mysite/2026-04-17-195920-db.sql.gz" → "backups/mysite"
			if ( preg_match( '#^(.+)/\d{4}-\d{2}-\d{2}-\d{6}#', $key, $m ) ) {
				$prefix = $m[1];
				if ( ! isset( $prefixes[ $prefix ] ) ) {
					$prefixes[ $prefix ] = array(
						'prefix'     => $prefix,
						'is_current' => ( $prefix === $current ),
						'file_count' => 0,
						'total_size' => 0,
					);
				}
				$prefixes[ $prefix ]['file_count']++;
				$prefixes[ $prefix ]['total_size'] += $obj['size'];
			}
		}

		// Format sizes
		foreach ( $prefixes as &$p ) {
			$p['total_size_formatted'] = size_format( $p['total_size'] );
		}

		ksort( $prefixes );
		wp_send_json_success( array_values( $prefixes ) );
	}

	/**
	 * AJAX handler for deleting a backup.
	 */
	public static function ajax_delete_backup() {
		check_ajax_referer( 'wps3b_backup_step', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized.' );
		}

		$timestamp = sanitize_text_field( wp_unslash( $_POST['timestamp'] ?? '' ) );
		if ( empty( $timestamp ) ) {
			wp_send_json_error( 'Missing timestamp.' );
		}

		$result = WPS3B_Backup_Manager::delete_backup( $timestamp );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( array( 'message' => 'Backup deleted.' ) );
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

	/**
	 * Render the upgrade page.
	 */
	public static function render_upgrade_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require WPS3B_PLUGIN_DIR . 'admin/views/upgrade-page.php';
	}
}
