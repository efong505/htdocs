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

	/**
	 * Render the log table HTML.
	 *
	 * Used by both the logs page and the AJAX refresh handler.
	 *
	 * @param array $entries Log entries.
	 */
	public static function render_table( $entries ) {
		if ( empty( $entries ) ) : ?>
			<div class="notice notice-info">
				<p><?php esc_html_e( 'No log entries yet. Entries will appear here after your first backup.', 'wp-s3-backup' ); ?></p>
			</div>
		<?php else : ?>
			<table class="widefat striped wps3b-logs-table">
				<thead>
					<tr>
						<th style="width:160px;"><?php esc_html_e( 'Time', 'wp-s3-backup' ); ?></th>
						<th style="width:80px;"><?php esc_html_e( 'Level', 'wp-s3-backup' ); ?></th>
						<th><?php esc_html_e( 'Message', 'wp-s3-backup' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $entries as $entry ) : ?>
					<tr class="bf-log-row-<?php echo esc_attr( $entry['level'] ); ?>">
						<td><?php echo esc_html( $entry['time'] ); ?></td>
						<td>
							<span class="bf-log-level bf-log-<?php echo esc_attr( $entry['level'] ); ?>">
								<?php echo esc_html( strtoupper( $entry['level'] ) ); ?>
							</span>
						</td>
						<td><?php echo esc_html( $entry['message'] ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p class="description">
				<?php
				printf(
					/* translators: Number of log entries */
					esc_html__( 'Showing %d entries (max 100 kept).', 'wp-s3-backup' ),
					count( $entries )
				);
				?>
			</p>
		<?php endif;
	}
}
