<?php
/**
 * Simple activity logger for backup operations.
 *
 * Stores log entries in wp_options as a serialized array.
 * Keeps the last 100 entries and auto-prunes older ones.
 *
 * @package WP_S3_Backup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPS3B_Logger {

	const OPTION_KEY = 'wps3b_log';
	const MAX_ENTRIES = 100;

	/**
	 * Add a log entry.
	 *
	 * @param string $level   Log level: 'info', 'success', 'warning', 'error'.
	 * @param string $message Human-readable message.
	 * @param array  $context Optional additional data.
	 */
	public static function log( $level, $message, $context = array() ) {
		$entries = get_option( self::OPTION_KEY, array() );

		array_unshift( $entries, array(
			'time'    => current_time( 'mysql' ),
			'level'   => sanitize_key( $level ),
			'message' => sanitize_text_field( $message ),
			'context' => $context,
		) );

		// Keep only the last MAX_ENTRIES
		$entries = array_slice( $entries, 0, self::MAX_ENTRIES );

		update_option( self::OPTION_KEY, $entries, false );
	}

	/**
	 * Shorthand for info level.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context.
	 */
	public static function info( $message, $context = array() ) {
		self::log( 'info', $message, $context );
	}

	/**
	 * Shorthand for success level.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context.
	 */
	public static function success( $message, $context = array() ) {
		self::log( 'success', $message, $context );
	}

	/**
	 * Shorthand for error level.
	 *
	 * @param string $message Log message.
	 * @param array  $context Optional context.
	 */
	public static function error( $message, $context = array() ) {
		self::log( 'error', $message, $context );
	}

	/**
	 * Get all log entries.
	 *
	 * @param int $limit Number of entries to return. 0 = all.
	 * @return array Log entries, newest first.
	 */
	public static function get_entries( $limit = 0 ) {
		$entries = get_option( self::OPTION_KEY, array() );
		if ( $limit > 0 ) {
			return array_slice( $entries, 0, $limit );
		}
		return $entries;
	}

	/**
	 * Clear all log entries.
	 */
	public static function clear() {
		delete_option( self::OPTION_KEY );
	}
}
