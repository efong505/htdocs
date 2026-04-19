<?php
/**
 * Storage management page — BackForge Pro.
 *
 * @package BackForge_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$summary = WPS3B_Pro_Storage::get_summary();
$backups = WPS3B_Backup_Manager::list_backups();
$classes = WPS3B_Pro_Storage::STORAGE_CLASSES;
$costs   = WPS3B_Pro_Storage::COST_PER_GB;

$class_colors = array(
	'STANDARD'            => '#2563eb',
	'STANDARD_IA'         => '#7c3aed',
	'INTELLIGENT_TIERING' => '#0891b2',
	'GLACIER_IR'          => '#0369a1',
	'GLACIER'             => '#0e7490',
	'DEEP_ARCHIVE'        => '#1e3a5f',
);
?>

<div class="wrap bf-wrap">
	<div class="bf-header">
		<h1 class="bf-header__title">
			<span class="bf-header__icon"><span class="dashicons dashicons-cloud-upload"></span></span>
			BackForge — Storage
			<span class="bf-pro-badge">Pro</span>
		</h1>
	</div>

	<?php if ( is_wp_error( $summary ) ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( $summary->get_error_message() ); ?></p></div>
	<?php else : ?>

	<!-- Summary Dashboard -->
	<div class="bf-dashboard">
		<div class="bf-stat-card">
			<div class="bf-stat-card__icon bf-stat-card__icon--backup"><span class="dashicons dashicons-backup"></span></div>
			<span class="bf-stat-card__value"><?php echo esc_html( $summary['total_backups'] ); ?></span>
			<span class="bf-stat-card__label"><?php esc_html_e( 'Backups', 'wp-s3-backup-pro' ); ?></span>
		</div>
		<div class="bf-stat-card">
			<div class="bf-stat-card__icon bf-stat-card__icon--storage"><span class="dashicons dashicons-cloud-saved"></span></div>
			<span class="bf-stat-card__value"><?php echo esc_html( size_format( $summary['total_size'] ) ); ?></span>
			<span class="bf-stat-card__label"><?php esc_html_e( 'Total Size', 'wp-s3-backup-pro' ); ?></span>
		</div>
		<div class="bf-stat-card">
			<div class="bf-stat-card__icon bf-stat-card__icon--cost"><span class="dashicons dashicons-chart-area"></span></div>
			<span class="bf-stat-card__value">$<?php echo esc_html( number_format( $summary['monthly_cost'], 4 ) ); ?></span>
			<span class="bf-stat-card__label"><?php esc_html_e( 'Est. Monthly Cost', 'wp-s3-backup-pro' ); ?></span>
		</div>
	</div>

	<!-- Storage Class Breakdown -->
	<?php if ( ! empty( $summary['by_class'] ) ) : ?>
	<div class="bf-card" style="margin-bottom:24px;">
		<div class="bf-card__header">
			<span class="dashicons dashicons-chart-pie"></span>
			<?php esc_html_e( 'Storage Class Breakdown', 'wp-s3-backup-pro' ); ?>
		</div>
		<div class="bf-card__body" style="padding:0;">
			<table class="widefat" style="border:none;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Storage Class', 'wp-s3-backup-pro' ); ?></th>
						<th><?php esc_html_e( 'Files', 'wp-s3-backup-pro' ); ?></th>
						<th><?php esc_html_e( 'Size', 'wp-s3-backup-pro' ); ?></th>
						<th><?php esc_html_e( 'Cost/GB/Month', 'wp-s3-backup-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $summary['by_class'] as $class => $info ) : ?>
					<tr>
						<td><span class="bf-storage-badge" style="background-color:<?php echo esc_attr( $class_colors[ $class ] ?? '#64748b' ); ?>;"><?php echo esc_html( $classes[ $class ] ?? $class ); ?></span></td>
						<td><?php echo esc_html( $info['count'] ); ?></td>
						<td><?php echo esc_html( size_format( $info['size'] ) ); ?></td>
						<td>$<?php echo esc_html( number_format( $costs[ $class ] ?? 0.023, 4 ) ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php endif; ?>

	<!-- Change Storage Class -->
	<?php if ( ! is_wp_error( $backups ) && ! empty( $backups ) ) : ?>
	<div class="bf-card">
		<div class="bf-card__header">
			<span class="dashicons dashicons-migrate"></span>
			<?php esc_html_e( 'Change Storage Class', 'wp-s3-backup-pro' ); ?>
		</div>
		<div class="bf-card__body" style="padding:0;">
			<p style="padding:16px 20px 0;margin:0;color:var(--bf-text-muted);font-size:13px;"><?php esc_html_e( 'Move older backups to cheaper storage classes to reduce costs. Glacier classes have retrieval fees and delays.', 'wp-s3-backup-pro' ); ?></p>
			<table class="widefat" style="border:none;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Backup', 'wp-s3-backup-pro' ); ?></th>
						<th><?php esc_html_e( 'File', 'wp-s3-backup-pro' ); ?></th>
						<th><?php esc_html_e( 'Size', 'wp-s3-backup-pro' ); ?></th>
						<th><?php esc_html_e( 'Current Class', 'wp-s3-backup-pro' ); ?></th>
						<th><?php esc_html_e( 'Change To', 'wp-s3-backup-pro' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $backups as $backup ) : ?>
						<?php foreach ( $backup['files'] as $file ) : ?>
						<tr>
							<td><?php echo esc_html( $backup['date'] ); ?></td>
							<td><?php echo esc_html( basename( $file['key'] ) ); ?></td>
							<td><?php echo esc_html( size_format( $file['size'] ) ); ?></td>
							<td><span class="bf-storage-badge" style="background-color:<?php echo esc_attr( $class_colors[ $file['storage_class'] ] ?? '#64748b' ); ?>;"><?php echo esc_html( $classes[ $file['storage_class'] ] ?? $file['storage_class'] ); ?></span></td>
							<td>
								<select class="wps3b-storage-select" data-key="<?php echo esc_attr( $file['key'] ); ?>">
									<option value=""><?php esc_html_e( '— No change —', 'wp-s3-backup-pro' ); ?></option>
									<?php foreach ( $classes as $code => $label ) : ?>
										<?php if ( $code !== $file['storage_class'] ) : ?>
										<option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $label ); ?> ($<?php echo esc_html( number_format( $costs[ $code ], 4 ) ); ?>/GB)</option>
										<?php endif; ?>
									<?php endforeach; ?>
								</select>
								<button type="button" class="bf-btn bf-btn--outline bf-btn--sm wps3b-change-storage-btn" disabled><?php esc_html_e( 'Apply', 'wp-s3-backup-pro' ); ?></button>
								<span class="wps3b-storage-result" style="margin-left:8px;font-size:12px;"></span>
							</td>
						</tr>
						<?php endforeach; ?>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php endif; ?>

	<?php endif; ?>
</div>
