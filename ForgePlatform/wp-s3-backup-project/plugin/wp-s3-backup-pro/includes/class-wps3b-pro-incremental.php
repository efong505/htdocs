<?php
/**
 * Incremental backup engine — only backs up changed files.
 *
 * Stores a manifest of file paths + modification times after each backup.
 * On next backup, compares current files against the stored manifest
 * and excludes unchanged files via the wps3b_exclude_paths filter.
 *
 * @package WP_S3_Backup_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPS3B_Pro_Incremental {

	const MANIFEST_OPTION = 'wps3b_pro_incremental_manifest';

	public static function init() {
		$settings = WPS3B_Pro::get_settings();
		if ( ! $settings['incremental_enabled'] ) {
			return;
		}

		add_filter( 'wps3b_exclude_paths', array( __CLASS__, 'filter_unchanged_files' ) );
		add_action( 'wps3b_after_backup', array( __CLASS__, 'save_manifest' ) );
	}

	/**
	 * Build a manifest of current wp-content files with modification times.
	 */
	public static function scan_files() {
		$source_dir = WP_CONTENT_DIR;
		$manifest   = array();

		try {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $source_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::SELF_FIRST
			);

			foreach ( $iterator as $item ) {
				if ( ! $item->isFile() || ! $item->isReadable() ) {
					continue;
				}
				$relative = str_replace( $source_dir . DIRECTORY_SEPARATOR, '', $item->getPathname() );
				$relative = str_replace( '\\', '/', $relative );
				$manifest[ $relative ] = array(
					'mtime' => $item->getMTime(),
					'size'  => $item->getSize(),
				);
			}
		} catch ( Exception $e ) {
			// Fall back to full backup if scan fails
			return array();
		}

		return $manifest;
	}

	/**
	 * Filter out unchanged files by adding them to the exclude list.
	 */
	public static function filter_unchanged_files( $excludes ) {
		$previous = get_option( self::MANIFEST_OPTION, array() );
		if ( empty( $previous ) ) {
			// No previous manifest — do a full backup
			return $excludes;
		}

		$current = self::scan_files();
		if ( empty( $current ) ) {
			return $excludes;
		}

		$unchanged = array();
		foreach ( $current as $path => $info ) {
			if ( isset( $previous[ $path ] )
				&& $previous[ $path ]['mtime'] === $info['mtime']
				&& $previous[ $path ]['size'] === $info['size']
			) {
				$unchanged[] = $path;
			}
		}

		WPS3B_Logger::info( sprintf(
			'Incremental backup: %d total files, %d unchanged (excluded), %d changed.',
			count( $current ),
			count( $unchanged ),
			count( $current ) - count( $unchanged )
		));

		return array_merge( $excludes, $unchanged );
	}

	/**
	 * Save the current file manifest after a successful backup.
	 */
	public static function save_manifest( $manifest_data ) {
		$current = self::scan_files();
		if ( ! empty( $current ) ) {
			update_option( self::MANIFEST_OPTION, $current, false );
		}
	}

	/**
	 * Reset the incremental manifest (forces next backup to be full).
	 */
	public static function reset() {
		delete_option( self::MANIFEST_OPTION );
	}

	/**
	 * Get stats about the current manifest.
	 */
	public static function get_stats() {
		$manifest = get_option( self::MANIFEST_OPTION, array() );
		return array(
			'tracked_files' => count( $manifest ),
			'last_updated'  => ! empty( $manifest ) ? get_option( 'wps3b_last_backup', '' ) : '',
		);
	}
}
