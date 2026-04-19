<?php
/**
 * Logs page view — displays backup activity log.
 *
 * @package WP_S3_Backup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$entries = WPS3B_Logger::get_entries();
?>

<div class="wrap wps3b-wrap">
	<h1><?php esc_html_e( 'WP S3 Backup — Activity Log', 'wp-s3-backup' ); ?></h1>

	<?php if ( empty( $entries ) ) : ?>
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
				<tr>
					<td><?php echo esc_html( $entry['time'] ); ?></td>
					<td>
						<span class="wps3b-log-level wps3b-log-<?php echo esc_attr( $entry['level'] ); ?>">
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
	<?php endif; ?>
</div>
