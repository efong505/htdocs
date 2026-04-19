<?php
/**
 * Backups page view — lists backups in S3 with download/delete actions.
 *
 * @package WP_S3_Backup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_creds = WPS3B_Settings::has_credentials();
$backups   = array();
$error     = '';

if ( $has_creds ) {
	$result = WPS3B_Backup_Manager::list_backups();
	if ( is_wp_error( $result ) ) {
		$error = $result->get_error_message();
	} else {
		$backups = $result;
	}
}
?>

<div class="wrap wps3b-wrap">
	<h1><?php esc_html_e( 'WP S3 Backup — Backups', 'wp-s3-backup' ); ?></h1>

	<?php settings_errors( 'wps3b_backups' ); ?>

	<?php if ( ! $has_creds ) : ?>
		<div class="notice notice-warning">
			<p>
				<?php
				printf(
					/* translators: Settings page link */
					esc_html__( 'AWS credentials are not configured. %s to set them up.', 'wp-s3-backup' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=wps3b_settings' ) ) . '">' . esc_html__( 'Go to Settings', 'wp-s3-backup' ) . '</a>'
				);
				?>
			</p>
		</div>
	<?php else : ?>

		<!-- Backup Now -->
		<form method="post" style="margin-bottom: 20px;">
			<?php wp_nonce_field( 'wps3b_backup_now', 'wps3b_backup_nonce' ); ?>
			<button type="submit" name="wps3b_backup_now" class="button button-primary">
				<?php esc_html_e( 'Create Backup Now', 'wp-s3-backup' ); ?>
			</button>
		</form>

		<?php if ( $error ) : ?>
			<div class="notice notice-error">
				<p><?php echo esc_html( $error ); ?></p>
			</div>
		<?php elseif ( empty( $backups ) ) : ?>
			<div class="notice notice-info">
				<p><?php esc_html_e( 'No backups found in S3. Click "Create Backup Now" to create your first backup.', 'wp-s3-backup' ); ?></p>
			</div>
		<?php else : ?>
			<table class="widefat striped wps3b-backups-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'wp-s3-backup' ); ?></th>
						<th><?php esc_html_e( 'Files', 'wp-s3-backup' ); ?></th>
						<th><?php esc_html_e( 'Total Size', 'wp-s3-backup' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'wp-s3-backup' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $backups as $backup ) : ?>
					<tr>
						<td>
							<strong><?php echo esc_html( $backup['date'] ); ?></strong>
						</td>
						<td>
							<?php
							foreach ( $backup['files'] as $file ) :
								$basename = basename( $file['key'] );
								$download_url = wp_nonce_url(
									add_query_arg( array(
										'page'           => 'wps3b_backups',
										'wps3b_download' => rawurlencode( $file['key'] ),
									), admin_url( 'admin.php' ) ),
									'wps3b_download_backup'
								);
							?>
								<div class="wps3b-file-item">
									<a href="<?php echo esc_url( $download_url ); ?>" title="<?php esc_attr_e( 'Download', 'wp-s3-backup' ); ?>">
										<?php echo esc_html( $basename ); ?>
									</a>
									<span class="wps3b-file-size">(<?php echo esc_html( size_format( $file['size'] ) ); ?>)</span>
								</div>
							<?php endforeach; ?>
						</td>
						<td><?php echo esc_html( size_format( $backup['total_size'] ) ); ?></td>
						<td>
							<?php
							$delete_url = wp_nonce_url(
								add_query_arg( array(
									'page'         => 'wps3b_backups',
									'wps3b_delete' => rawurlencode( $backup['timestamp'] ),
								), admin_url( 'admin.php' ) ),
								'wps3b_delete_backup'
							);
							?>
							<a href="<?php echo esc_url( $delete_url ); ?>"
							   class="wps3b-delete-link"
							   onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to delete this backup?', 'wp-s3-backup' ); ?>');">
								<?php esc_html_e( 'Delete', 'wp-s3-backup' ); ?>
							</a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p class="description">
				<?php
				printf(
					/* translators: Number of backups */
					esc_html__( '%d backup(s) found in S3.', 'wp-s3-backup' ),
					count( $backups )
				);
				?>
			</p>
		<?php endif; ?>

	<?php endif; ?>
</div>
