<?php
/**
 * Backups page — BackForge card-based layout.
 *
 * @package BackForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_creds    = WPS3B_Settings::has_credentials();
$is_pro       = function_exists( 'wps3b_is_pro_active' ) && wps3b_is_pro_active();
$backups      = array();
$error        = '';
$show_confirm = isset( $_GET['wps3b_confirm_restore'] );
$restore_data = get_transient( 'wps3b_restore_manifest' );
$upgrade_url  = admin_url( 'admin.php?page=wps3b_upgrade' );

if ( $has_creds && ! $show_confirm ) {
	$result = WPS3B_Backup_Manager::list_backups();
	if ( is_wp_error( $result ) ) {
		$error = $result->get_error_message();
	} else {
		$backups = $result;
	}
}

$total_storage = 0;
foreach ( $backups as $backup ) {
	$total_storage += $backup['total_size'];
}

$class_colors = array(
	'STANDARD'            => '#2563eb',
	'STANDARD_IA'         => '#7c3aed',
	'INTELLIGENT_TIERING' => '#0891b2',
	'ONEZONE_IA'          => '#9333ea',
	'GLACIER'             => '#0e7490',
	'GLACIER_IR'          => '#0369a1',
	'DEEP_ARCHIVE'        => '#1e3a5f',
	'REDUCED_REDUNDANCY'  => '#64748b',
);
$class_labels = array(
	'STANDARD'            => 'Standard',
	'STANDARD_IA'         => 'Standard-IA',
	'INTELLIGENT_TIERING' => 'Intelligent',
	'ONEZONE_IA'          => 'One Zone-IA',
	'GLACIER'             => 'Glacier',
	'GLACIER_IR'          => 'Glacier IR',
	'DEEP_ARCHIVE'        => 'Deep Archive',
	'REDUCED_REDUNDANCY'  => 'Reduced',
);
?>

<div class="wrap bf-wrap">
	<div class="bf-header">
		<h1 class="bf-header__title">
			<span class="bf-header__icon"><span class="dashicons dashicons-cloud-upload"></span></span>
			BackForge — Backups
		</h1>
	</div>

	<?php settings_errors( 'wps3b_backups' ); ?>

	<?php if ( ! $has_creds ) : ?>

		<div class="bf-empty">
			<div class="bf-empty__icon dashicons dashicons-admin-network"></div>
			<h3 class="bf-empty__title"><?php esc_html_e( 'Connect to Amazon S3', 'wp-s3-backup' ); ?></h3>
			<p class="bf-empty__text"><?php esc_html_e( 'Enter your AWS credentials to start backing up your site.', 'wp-s3-backup' ); ?></p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=wps3b_settings' ) ); ?>" class="bf-btn bf-btn--primary">
				<?php esc_html_e( 'Go to Settings', 'wp-s3-backup' ); ?>
			</a>
		</div>

	<?php elseif ( $show_confirm && $restore_data ) : ?>

		<?php
		$timestamp = $restore_data['timestamp'];
		$manifest  = $restore_data['manifest'];
		$is_error  = is_wp_error( $manifest );
		$warnings  = ! $is_error && isset( $manifest['warnings'] ) ? $manifest['warnings'] : array();
		?>

		<div class="bf-restore-confirm">
			<div class="bf-card">
				<div class="bf-card__header">
					<span class="dashicons dashicons-backup"></span>
					<?php esc_html_e( 'Confirm Restore', 'wp-s3-backup' ); ?>
				</div>
				<div class="bf-card__body">

					<div class="notice notice-warning" style="margin:0 0 16px;">
						<p><strong><?php esc_html_e( 'This will restore your site from this backup:', 'wp-s3-backup' ); ?></strong></p>
						<ul style="list-style:disc;margin-left:20px;">
							<li><?php esc_html_e( 'Database — all tables will be dropped and recreated', 'wp-s3-backup' ); ?></li>
							<li><?php esc_html_e( 'Files — wp-content directory will be overwritten', 'wp-s3-backup' ); ?></li>
							<li><?php esc_html_e( 'wp-config.php and core files will NOT be modified', 'wp-s3-backup' ); ?></li>
						</ul>
					</div>

					<?php if ( ! $is_error ) : ?>
					<table class="widefat striped" style="max-width:100%;">
						<tbody>
							<tr><th style="width:140px;"><?php esc_html_e( 'Backup Date', 'wp-s3-backup' ); ?></th><td><?php echo esc_html( $manifest['timestamp'] ?? $timestamp ); ?></td></tr>
							<tr><th><?php esc_html_e( 'Site URL', 'wp-s3-backup' ); ?></th><td><?php echo esc_html( $manifest['site_url'] ?? 'Unknown' ); ?></td></tr>
							<tr><th><?php esc_html_e( 'WordPress', 'wp-s3-backup' ); ?></th><td><?php echo esc_html( ( $manifest['wordpress_version'] ?? '?' ) . ' → ' . get_bloginfo( 'version' ) ); ?></td></tr>
							<?php if ( isset( $manifest['backup_contents']['database'] ) ) : ?>
							<tr><th><?php esc_html_e( 'Database', 'wp-s3-backup' ); ?></th><td><?php printf( '%d tables, %s rows (%s)', $manifest['backup_contents']['database']['tables'], number_format( $manifest['backup_contents']['database']['rows'] ), size_format( $manifest['backup_contents']['database']['size'] ) ); ?></td></tr>
							<?php endif; ?>
							<?php if ( isset( $manifest['backup_contents']['files'] ) ) : ?>
							<tr><th><?php esc_html_e( 'Files', 'wp-s3-backup' ); ?></th><td><?php printf( '%s files (%s)', number_format( $manifest['backup_contents']['files']['total_files'] ), size_format( $manifest['backup_contents']['files']['size'] ) ); ?></td></tr>
							<?php endif; ?>
						</tbody>
					</table>

					<?php if ( ! empty( $warnings ) ) : ?>
					<div class="notice notice-warning" style="margin:16px 0 0;">
						<p><strong><?php esc_html_e( 'Compatibility Warnings:', 'wp-s3-backup' ); ?></strong></p>
						<ul style="list-style:disc;margin-left:20px;">
							<?php foreach ( $warnings as $w ) : ?>
								<li><?php echo esc_html( $w ); ?></li>
							<?php endforeach; ?>
						</ul>
					</div>
					<?php endif; ?>
					<?php endif; ?>

				</div>
				<div class="bf-card__footer">
					<form method="post" style="display:inline;">
						<?php wp_nonce_field( 'wps3b_confirm_restore', 'wps3b_restore_nonce' ); ?>
						<input type="hidden" name="wps3b_restore_timestamp" value="<?php echo esc_attr( $timestamp ); ?>" />

						<?php if ( $is_pro ) : ?>
						<div style="margin-bottom:16px;">
							<strong><?php esc_html_e( 'Restore Type:', 'wp-s3-backup' ); ?></strong><br />
							<label class="bf-check"><input type="radio" name="wps3b_restore_type" value="full" checked /> <?php esc_html_e( 'Full Site', 'wp-s3-backup' ); ?></label>
							<label class="bf-check"><input type="radio" name="wps3b_restore_type" value="database" /> <?php esc_html_e( 'Database Only', 'wp-s3-backup' ); ?></label>
							<label class="bf-check"><input type="radio" name="wps3b_restore_type" value="files" /> <?php esc_html_e( 'Files Only', 'wp-s3-backup' ); ?></label>

							<div style="margin-top:12px;">
								<strong><?php esc_html_e( 'URL Replacement:', 'wp-s3-backup' ); ?></strong><br />
								<label style="font-size:12px;"><?php esc_html_e( 'Old URL', 'wp-s3-backup' ); ?></label>
								<input type="url" name="wps3b_old_url" value="<?php echo esc_attr( ! $is_error && isset( $manifest['site_url'] ) ? $manifest['site_url'] : '' ); ?>" class="regular-text" style="margin-bottom:4px;" /><br />
								<label style="font-size:12px;"><?php esc_html_e( 'New URL', 'wp-s3-backup' ); ?></label>
								<input type="url" name="wps3b_new_url" value="<?php echo esc_attr( get_site_url() ); ?>" class="regular-text" />
							</div>
						</div>
						<button type="submit" name="wps3b_pro_confirm_restore" class="bf-btn bf-btn--danger"
							onclick="return confirm('<?php esc_attr_e( 'Are you absolutely sure? This cannot be undone.', 'wp-s3-backup' ); ?>');">
							<span class="dashicons dashicons-backup"></span> <?php esc_html_e( 'Restore', 'wp-s3-backup' ); ?>
						</button>
						<?php else : ?>
						<button type="submit" name="wps3b_confirm_restore" class="bf-btn bf-btn--danger"
							onclick="return confirm('<?php esc_attr_e( 'Are you absolutely sure? This cannot be undone.', 'wp-s3-backup' ); ?>');">
							<span class="dashicons dashicons-backup"></span> <?php esc_html_e( 'Restore Full Site', 'wp-s3-backup' ); ?>
						</button>
						<?php endif; ?>
					</form>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wps3b_backups' ) ); ?>" class="bf-btn bf-btn--outline"><?php esc_html_e( 'Cancel', 'wp-s3-backup' ); ?></a>
				</div>
			</div>

			<?php if ( ! $is_pro ) : ?>
			<div class="bf-pro-hint" style="margin-top:16px;">
				<span class="bf-pro-badge">Pro</span>
				<?php printf( esc_html__( 'Need selective restore or URL replacement? %sUpgrade to Pro%s', 'wp-s3-backup' ), '<a href="' . esc_url( $upgrade_url ) . '">', '</a>' ); ?>
			</div>
			<?php endif; ?>
		</div>

	<?php else : ?>

		<!-- Dashboard Stats -->
		<?php if ( ! empty( $backups ) ) : ?>
		<div class="bf-dashboard">
			<div class="bf-stat-card">
				<div class="bf-stat-card__icon bf-stat-card__icon--backup"><span class="dashicons dashicons-backup"></span></div>
				<span class="bf-stat-card__value"><?php echo esc_html( count( $backups ) ); ?></span>
				<span class="bf-stat-card__label"><?php esc_html_e( 'Backups in S3', 'wp-s3-backup' ); ?></span>
			</div>
			<div class="bf-stat-card">
				<div class="bf-stat-card__icon bf-stat-card__icon--storage"><span class="dashicons dashicons-cloud-saved"></span></div>
				<span class="bf-stat-card__value"><?php echo esc_html( size_format( $total_storage ) ); ?></span>
				<span class="bf-stat-card__label"><?php esc_html_e( 'Total Storage', 'wp-s3-backup' ); ?></span>
			</div>
		</div>
		<?php endif; ?>

		<!-- Action Bar -->
		<div class="bf-action-bar">
			<button type="button" id="bf-backup-now" class="bf-btn bf-btn--primary">
				<span class="dashicons dashicons-cloud-upload"></span>
				<?php esc_html_e( 'Create Backup Now', 'wp-s3-backup' ); ?>
			</button>
		</div>

		<?php if ( $error ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html( $error ); ?></p></div>

		<?php elseif ( empty( $backups ) ) : ?>
			<div class="bf-empty">
				<div class="bf-empty__icon dashicons dashicons-cloud-upload"></div>
				<h3 class="bf-empty__title"><?php esc_html_e( 'No backups yet', 'wp-s3-backup' ); ?></h3>
				<p class="bf-empty__text"><?php esc_html_e( 'Create your first backup to protect your site. It only takes a minute.', 'wp-s3-backup' ); ?></p>
			</div>

			<?php do_action( 'wps3b_after_backups_list' ); ?>

			<?php if ( ! $is_pro ) : ?>
			<div class="bf-pro-hint" style="margin-top:20px;">
				<span class="bf-pro-badge">Pro</span>
				<?php printf( esc_html__( 'Restore from another site or upload a backup file? %sUpgrade to Pro%s', 'wp-s3-backup' ), '<a href="' . esc_url( $upgrade_url ) . '">', '</a>' ); ?>
			</div>
			<?php endif; ?>

		<?php else : ?>

			<?php if ( ! $is_pro ) : ?>
			<div class="bf-pro-banner">
				<span class="bf-pro-badge">Pro</span>
				<?php printf( esc_html__( 'Storage management, incremental backups, cross-site restore, and more. %sUpgrade to Pro%s', 'wp-s3-backup' ), '<a href="' . esc_url( $upgrade_url ) . '">', '</a>' ); ?>
			</div>
			<?php endif; ?>

			<!-- Backup Cards -->
			<div class="bf-backup-list">

				<!-- In-Progress Backup Card (hidden by default, shown by JS) -->
				<div id="bf-backup-progress" class="bf-backup-card" style="display:none;border-color:var(--bf-teal);">
					<div class="bf-backup-card__header">
						<span class="bf-backup-card__date">
							<span class="dashicons dashicons-update bf-spinning" style="color:var(--bf-teal);vertical-align:-3px;margin-right:4px;"></span>
							<span id="bf-backup-title"><?php esc_html_e( 'Creating Backup...', 'wp-s3-backup' ); ?></span>
						</span>
						<span class="bf-status bf-status--warn" id="bf-backup-badge"><span class="bf-status__dot"></span> <?php esc_html_e( 'In Progress', 'wp-s3-backup' ); ?></span>
					</div>
					<div class="bf-backup-card__files" id="bf-backup-files">
						<!-- DB file status -->
						<div class="bf-backup-file" id="bf-file-db">
							<div class="bf-backup-file__icon bf-backup-file__icon--db"><span class="dashicons dashicons-database"></span></div>
							<span class="bf-backup-file__name"><?php esc_html_e( 'Database', 'wp-s3-backup' ); ?></span>
							<span class="bf-backup-file__meta" id="bf-file-db-meta"><?php esc_html_e( 'Waiting', 'wp-s3-backup' ); ?></span>
							<span id="bf-file-db-status" class="bf-status bf-status--off" style="font-size:11px;"><span class="bf-status__dot"></span> <?php esc_html_e( 'Pending', 'wp-s3-backup' ); ?></span>
						</div>
						<!-- Files zip status -->
						<div class="bf-backup-file" id="bf-file-files">
							<div class="bf-backup-file__icon bf-backup-file__icon--files"><span class="dashicons dashicons-portfolio"></span></div>
							<span class="bf-backup-file__name"><?php esc_html_e( 'Files', 'wp-s3-backup' ); ?></span>
							<span class="bf-backup-file__meta" id="bf-file-files-meta"><?php esc_html_e( 'Waiting', 'wp-s3-backup' ); ?></span>
							<span id="bf-file-files-status" class="bf-status bf-status--off" style="font-size:11px;"><span class="bf-status__dot"></span> <?php esc_html_e( 'Pending', 'wp-s3-backup' ); ?></span>
						</div>
						<!-- Manifest status -->
						<div class="bf-backup-file" id="bf-file-manifest">
							<div class="bf-backup-file__icon bf-backup-file__icon--manifest"><span class="dashicons dashicons-media-text"></span></div>
							<span class="bf-backup-file__name"><?php esc_html_e( 'Manifest', 'wp-s3-backup' ); ?></span>
							<span class="bf-backup-file__meta" id="bf-file-manifest-meta"><?php esc_html_e( 'Waiting', 'wp-s3-backup' ); ?></span>
							<span id="bf-file-manifest-status" class="bf-status bf-status--off" style="font-size:11px;"><span class="bf-status__dot"></span> <?php esc_html_e( 'Pending', 'wp-s3-backup' ); ?></span>
						</div>
					</div>
					<div style="padding:12px 20px;border-top:1px solid var(--bf-border);">
						<div class="bf-progress"><div id="bf-backup-bar" class="bf-progress__bar" style="width:0%;"></div></div>
						<div style="display:flex;justify-content:space-between;margin-top:6px;">
							<span id="bf-backup-pct" style="font-size:12px;color:var(--bf-text-muted);">0%</span>
							<span id="bf-backup-elapsed" style="font-size:12px;color:var(--bf-text-muted);"></span>
						</div>
					</div>
				</div>

				<!-- In-Progress Restore Card (hidden by default, shown by JS) -->
				<div id="bf-restore-progress" class="bf-backup-card" style="display:none;border-color:#f59e0b;">
					<div class="bf-backup-card__header">
						<span class="bf-backup-card__date">
							<span class="dashicons dashicons-backup bf-pulse" style="color:#f59e0b;vertical-align:-3px;margin-right:4px;"></span>
							<span id="bf-restore-title"><?php esc_html_e( 'Restoring...', 'wp-s3-backup' ); ?></span>
						</span>
						<span class="bf-status bf-status--warn" id="bf-restore-badge"><span class="bf-status__dot"></span> <?php esc_html_e( 'Restoring', 'wp-s3-backup' ); ?></span>
					</div>
					<div class="bf-backup-card__files" id="bf-restore-steps"></div>
					<div style="padding:12px 20px;border-top:1px solid var(--bf-border);">
						<div class="bf-progress"><div id="bf-restore-bar" class="bf-progress__bar" style="width:0%;background:linear-gradient(90deg,#f59e0b,#d97706);"></div></div>
						<div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;">
							<span id="bf-restore-pct" style="font-size:12px;color:var(--bf-text-muted);">0%</span>
							<span id="bf-restore-elapsed" style="font-size:12px;color:var(--bf-text-muted);"></span>
							<button type="button" id="bf-restore-cancel" class="bf-btn bf-btn--outline bf-btn--sm" style="font-size:11px;padding:2px 10px;color:var(--bf-danger);border-color:var(--bf-danger);"><?php esc_html_e( 'Cancel', 'wp-s3-backup' ); ?></button>
						</div>
					</div>
				</div>

				<?php foreach ( $backups as $backup ) :
					$restore_url = wp_nonce_url( add_query_arg( array( 'page' => 'wps3b_backups', 'wps3b_restore' => rawurlencode( $backup['timestamp'] ) ), admin_url( 'admin.php' ) ), 'wps3b_restore_backup' );
					$delete_url  = wp_nonce_url( add_query_arg( array( 'page' => 'wps3b_backups', 'wps3b_delete' => rawurlencode( $backup['timestamp'] ) ), admin_url( 'admin.php' ) ), 'wps3b_delete_backup' );
				?>
				<div class="bf-backup-card bf-fade-in">
					<div class="bf-backup-card__header">
						<span class="bf-backup-card__date">
							<span class="dashicons dashicons-calendar-alt" style="color:var(--bf-teal);vertical-align:-3px;margin-right:4px;"></span>
							<?php echo esc_html( $backup['date'] ); ?>
						</span>
						<span class="bf-backup-card__size"><?php echo esc_html( size_format( $backup['total_size'] ) ); ?></span>
					</div>
					<div class="bf-backup-card__files">
						<?php foreach ( $backup['files'] as $file ) :
							$basename = basename( $file['key'] );
							$sc       = $file['storage_class'];
							$color    = $class_colors[ $sc ] ?? '#64748b';
							$label    = $class_labels[ $sc ] ?? $sc;
							$download = wp_nonce_url( add_query_arg( array( 'page' => 'wps3b_backups', 'wps3b_download' => rawurlencode( $file['key'] ) ), admin_url( 'admin.php' ) ), 'wps3b_download_backup' );

							$icon_class = 'bf-backup-file__icon--manifest';
							$icon       = 'dashicons-media-text';
							if ( preg_match( '/db\.sql\.gz$/', $basename ) ) {
								$icon_class = 'bf-backup-file__icon--db';
								$icon       = 'dashicons-database';
							} elseif ( preg_match( '/files\.zip$/', $basename ) ) {
								$icon_class = 'bf-backup-file__icon--files';
								$icon       = 'dashicons-portfolio';
							}
						?>
						<div class="bf-backup-file">
							<div class="bf-backup-file__icon <?php echo esc_attr( $icon_class ); ?>">
								<span class="dashicons <?php echo esc_attr( $icon ); ?>"></span>
							</div>
							<span class="bf-backup-file__name">
								<a href="<?php echo esc_url( $download ); ?>" title="<?php esc_attr_e( 'Download', 'wp-s3-backup' ); ?>"><?php echo esc_html( $basename ); ?></a>
							</span>
							<span class="bf-backup-file__meta"><?php echo esc_html( size_format( $file['size'] ) ); ?></span>
							<span class="bf-storage-badge" style="background-color:<?php echo esc_attr( $color ); ?>;"><?php echo esc_html( $label ); ?></span>
						</div>
						<?php endforeach; ?>
					</div>
					<div class="bf-backup-card__actions">
						<button type="button" class="bf-btn bf-btn--outline bf-btn--sm bf-restore-backup" data-timestamp="<?php echo esc_attr( $backup['timestamp'] ); ?>">
							<span class="dashicons dashicons-backup"></span> <?php esc_html_e( 'Restore', 'wp-s3-backup' ); ?>
						</button>
						<a href="<?php echo esc_url( $delete_url ); ?>" class="bf-btn bf-btn--outline bf-btn--sm bf-delete-backup" style="color:var(--bf-danger);" data-timestamp="<?php echo esc_attr( $backup['timestamp'] ); ?>">
							<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Delete', 'wp-s3-backup' ); ?>
						</a>
					</div>
				</div>
				<?php endforeach; ?>
			</div>

			<?php do_action( 'wps3b_after_backups_list' ); ?>

			<?php if ( ! $is_pro ) : ?>
			<div class="bf-pro-hint" style="margin-top:20px;">
				<span class="bf-pro-badge">Pro</span>
				<?php printf( esc_html__( 'Restore from another site or upload a backup file? %sUpgrade to Pro%s', 'wp-s3-backup' ), '<a href="' . esc_url( $upgrade_url ) . '">', '</a>' ); ?>
			</div>
			<?php endif; ?>

		<?php endif; ?>

	<?php endif; ?>
</div>
