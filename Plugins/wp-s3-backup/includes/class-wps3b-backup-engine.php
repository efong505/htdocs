<?php
/**
 * Backup engine — creates database dumps and file archives.
 *
 * All operations use PHP-native functions (no shell commands).
 * Database export uses $wpdb with batched queries.
 * File backup uses ZipArchive with configurable exclusions.
 *
 * @package WP_S3_Backup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPS3B_Backup_Engine {

	/** @var array Paths to exclude from file backup (relative to wp-content) */
	private $exclude_paths;

	/**
	 * @param array $exclude_paths Paths to exclude from file backup.
	 */
	public function __construct( $exclude_paths = array() ) {
		$default_excludes = array(
			'cache',
			'ai1wm-backups',
			'updraft',
			'backups-dup-pro',
			'node_modules',
			'upgrade',
			'wps3b-temp',
		);

		$this->exclude_paths = apply_filters(
			'wps3b_exclude_paths',
			! empty( $exclude_paths ) ? $exclude_paths : $default_excludes
		);
	}

	/**
	 * Ensure the temp directory exists and is protected.
	 *
	 * @return string Path to temp directory.
	 */
	private function ensure_temp_dir() {
		$temp_dir = WPS3B_TEMP_DIR;

		if ( ! is_dir( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}

		// Protect with .htaccess
		$htaccess = $temp_dir . '/.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( $htaccess, "Deny from all\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		// Protect with index.php
		$index = $temp_dir . '/index.php';
		if ( ! file_exists( $index ) ) {
			file_put_contents( $index, "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		}

		return $temp_dir;
	}

	/**
	 * Generate a timestamped filename.
	 *
	 * @param string $suffix File suffix (e.g., 'db.sql.gz', 'files.zip').
	 * @return string Filename like '2026-06-15-120000-db.sql.gz'.
	 */
	private function get_filename( $suffix ) {
		$timestamp = gmdate( 'Y-m-d-His' );
		return apply_filters( 'wps3b_backup_filename', $timestamp . '-' . $suffix, $suffix );
	}

	/**
	 * Export the WordPress database to a gzipped SQL file.
	 *
	 * Iterates all tables with the site's prefix, exports CREATE TABLE
	 * and INSERT statements in batches of 1000 rows.
	 *
	 * @return array{path: string, filename: string, tables: int, rows: int}|WP_Error
	 */
	public function export_database() {
		global $wpdb;

		do_action( 'wps3b_before_backup' );

		$temp_dir = $this->ensure_temp_dir();
		$filename = $this->get_filename( 'db.sql.gz' );
		$filepath = $temp_dir . '/' . $filename;

		$gz = gzopen( $filepath, 'wb9' );
		if ( ! $gz ) {
			return new WP_Error( 'wps3b_db_export', __( 'Could not create database dump file.', 'wp-s3-backup' ) );
		}

		gzwrite( $gz, "-- WP S3 Backup Database Dump\n" );
		gzwrite( $gz, "-- Generated: " . gmdate( 'Y-m-d H:i:s' ) . " UTC\n" );
		gzwrite( $gz, "-- WordPress: " . get_bloginfo( 'version' ) . "\n" );
		gzwrite( $gz, "-- Site URL: " . get_site_url() . "\n\n" );
		gzwrite( $gz, "SET NAMES utf8mb4;\n" );
		gzwrite( $gz, "SET foreign_key_checks = 0;\n" );
		gzwrite( $gz, "SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';\n\n" );

		// Get all tables with this site's prefix
		$tables = $wpdb->get_col(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$wpdb->esc_like( $wpdb->prefix ) . '%'
			)
		);

		$exclude_tables = apply_filters( 'wps3b_exclude_tables', array() );
		$total_tables   = 0;
		$total_rows     = 0;

		foreach ( $tables as $table ) {
			if ( in_array( $table, $exclude_tables, true ) ) {
				continue;
			}

			// Get CREATE TABLE statement
			$create = $wpdb->get_row( "SHOW CREATE TABLE `{$table}`", ARRAY_N ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( ! $create ) {
				continue;
			}

			gzwrite( $gz, "\n-- Table: {$table}\n" );
			gzwrite( $gz, "DROP TABLE IF EXISTS `{$table}`;\n" );
			gzwrite( $gz, $create[1] . ";\n\n" );

			// Export rows in batches
			$offset    = 0;
			$batch     = 1000;
			$has_rows  = true;

			while ( $has_rows ) {
				$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$wpdb->prepare( "SELECT * FROM `{$table}` LIMIT %d, %d", $offset, $batch ),
					ARRAY_A
				);

				if ( empty( $rows ) ) {
					$has_rows = false;
					break;
				}

				foreach ( $rows as $row ) {
					$values = array();
					foreach ( $row as $value ) {
						if ( null === $value ) {
							$values[] = 'NULL';
						} else {
							$values[] = "'" . esc_sql( $value ) . "'";
						}
					}
					gzwrite( $gz, "INSERT INTO `{$table}` VALUES (" . implode( ',', $values ) . ");\n" );
					$total_rows++;
				}

				$offset += $batch;

				if ( function_exists( 'set_time_limit' ) ) {
					@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				}
			}

			$total_tables++;
		}

		gzwrite( $gz, "\nSET foreign_key_checks = 1;\n" );
		gzclose( $gz );

		return array(
			'path'     => $filepath,
			'filename' => $filename,
			'tables'   => $total_tables,
			'rows'     => $total_rows,
		);
	}

	/**
	 * Create a zip archive of the wp-content directory.
	 *
	 * Respects exclusion paths configured in settings.
	 *
	 * @return array{path: string, filename: string, files: int}|WP_Error
	 */
	public function export_files() {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'wps3b_zip_missing', __( 'PHP ZipArchive extension is required for file backups.', 'wp-s3-backup' ) );
		}

		$temp_dir   = $this->ensure_temp_dir();
		$filename   = $this->get_filename( 'files.zip' );
		$filepath   = $temp_dir . '/' . $filename;
		$source_dir = WP_CONTENT_DIR;

		$zip = new ZipArchive();
		$result = $zip->open( $filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE );
		if ( true !== $result ) {
			return new WP_Error( 'wps3b_zip_create', __( 'Could not create zip archive.', 'wp-s3-backup' ) );
		}

		$total_files = 0;

		try {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $source_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::SELF_FIRST
			);

			foreach ( $iterator as $item ) {
				$real_path     = $item->getPathname();
				$relative_path = str_replace( $source_dir . DIRECTORY_SEPARATOR, '', $real_path );
				$relative_path = str_replace( '\\', '/', $relative_path );

				if ( $this->is_excluded( $relative_path ) ) {
					continue;
				}

				if ( $item->isDir() ) {
					$zip->addEmptyDir( 'wp-content/' . $relative_path );
				} elseif ( $item->isFile() && $item->isReadable() ) {
					$zip->addFile( $real_path, 'wp-content/' . $relative_path );
					$total_files++;
				}

				if ( function_exists( 'set_time_limit' ) ) {
					@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				}
			}
		} catch ( Exception $e ) {
			$zip->close();
			@unlink( $filepath ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return new WP_Error( 'wps3b_zip_error', $e->getMessage() );
		}

		$zip->close();

		return array(
			'path'     => $filepath,
			'filename' => $filename,
			'files'    => $total_files,
		);
	}

	/**
	 * Generate a manifest JSON file with backup metadata.
	 *
	 * @param array $db_info    Database export info from export_database().
	 * @param array $files_info File export info from export_files().
	 * @return array{path: string, filename: string}|WP_Error
	 */
	public function generate_manifest( $db_info = null, $files_info = null ) {
		$temp_dir = $this->ensure_temp_dir();
		$filename = $this->get_filename( 'manifest.json' );
		$filepath = $temp_dir . '/' . $filename;

		$manifest = array(
			'version'           => WPS3B_VERSION,
			'timestamp'         => gmdate( 'Y-m-d\TH:i:s\Z' ),
			'site_url'          => get_site_url(),
			'site_name'         => get_bloginfo( 'name' ),
			'wordpress_version' => get_bloginfo( 'version' ),
			'php_version'       => phpversion(),
			'table_prefix'      => $GLOBALS['wpdb']->prefix,
			'active_theme'      => get_stylesheet(),
			'active_plugins'    => get_option( 'active_plugins', array() ),
			'backup_contents'   => array(),
		);

		if ( $db_info && ! is_wp_error( $db_info ) ) {
			$manifest['backup_contents']['database'] = array(
				'file'     => $db_info['filename'],
				'size'     => filesize( $db_info['path'] ),
				'checksum' => 'sha256:' . hash_file( 'sha256', $db_info['path'] ),
				'tables'   => $db_info['tables'],
				'rows'     => $db_info['rows'],
			);
		}

		if ( $files_info && ! is_wp_error( $files_info ) ) {
			$manifest['backup_contents']['files'] = array(
				'file'           => $files_info['filename'],
				'size'           => filesize( $files_info['path'] ),
				'checksum'       => 'sha256:' . hash_file( 'sha256', $files_info['path'] ),
				'total_files'    => $files_info['files'],
				'excluded_paths' => $this->exclude_paths,
			);
		}

		$json = wp_json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		if ( false === file_put_contents( $filepath, $json ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			return new WP_Error( 'wps3b_manifest', __( 'Could not create manifest file.', 'wp-s3-backup' ) );
		}

		return array(
			'path'     => $filepath,
			'filename' => $filename,
			'data'     => $manifest,
		);
	}

	/**
	 * Check if a relative path should be excluded from the backup.
	 *
	 * @param string $relative_path Path relative to wp-content.
	 * @return bool True if excluded.
	 */
	private function is_excluded( $relative_path ) {
		foreach ( $this->exclude_paths as $exclude ) {
			$exclude = trim( $exclude );
			if ( empty( $exclude ) ) {
				continue;
			}

			// Directory/path prefix match
			if ( 0 === strpos( $relative_path, $exclude ) || false !== strpos( $relative_path, '/' . $exclude ) ) {
				return true;
			}

			// Wildcard extension match (e.g., "*.log")
			if ( 0 === strpos( $exclude, '*.' ) ) {
				$ext = substr( $exclude, 1 );
				if ( substr( $relative_path, -strlen( $ext ) ) === $ext ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Clean up all temp files.
	 */
	public function cleanup() {
		$temp_dir = WPS3B_TEMP_DIR;

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
