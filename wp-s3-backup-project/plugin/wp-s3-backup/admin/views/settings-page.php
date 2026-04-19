<?php
/**
 * Settings page — BackForge card-based layout.
 *
 * @package BackForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings    = WPS3B_Settings::get_settings();
$has_creds   = WPS3B_Settings::has_credentials();
$masked_ak   = WPS3B_Settings::get_masked_access_key();
$masked_sk   = WPS3B_Settings::get_masked_secret_key();
$last_backup = get_option( 'wps3b_last_backup', '' );
$last_error  = get_option( 'wps3b_last_error', '' );
$next_run    = wp_next_scheduled( 'wps3b_scheduled_backup' );
$is_pro      = function_exists( 'wps3b_is_pro_active' ) && wps3b_is_pro_active();
$upgrade_url = admin_url( 'admin.php?page=wps3b_upgrade' );
?>

<div class="wrap bf-wrap">
	<div class="bf-header">
		<h1 class="bf-header__title">
			<span class="bf-header__icon"><span class="dashicons dashicons-cloud-upload"></span></span>
			BackForge
		</h1>
	</div>

	<?php settings_errors( 'wps3b_settings' ); ?>

	<!-- Dashboard Banner -->
	<div style="margin-bottom:24px;border-radius:var(--bf-radius);overflow:hidden;border:1px solid var(--bf-border);">
		<img src="<?php echo esc_url( WPS3B_PLUGIN_URL . 'admin/images/dashboard-banner.png' ); ?>" alt="BackForge" style="display:block;width:100%;height:auto;" />
	</div>

	<!-- Dashboard Status Cards -->
	<div class="bf-dashboard">
		<div class="bf-stat-card">
			<div class="bf-stat-card__icon bf-stat-card__icon--backup">
				<span class="dashicons dashicons-backup"></span>
			</div>
			<span class="bf-stat-card__value"><?php echo $last_backup ? esc_html( human_time_diff( strtotime( $last_backup ), current_time( 'timestamp' ) ) ) : '—'; ?></span>
			<span class="bf-stat-card__label"><?php echo $last_backup ? esc_html__( 'Since last backup', 'wp-s3-backup' ) : esc_html__( 'No backups yet', 'wp-s3-backup' ); ?></span>
		</div>
		<div class="bf-stat-card">
			<div class="bf-stat-card__icon bf-stat-card__icon--schedule">
				<span class="dashicons dashicons-calendar-alt"></span>
			</div>
			<?php if ( $settings['enabled'] && $next_run ) : ?>
				<span class="bf-stat-card__value"><?php echo esc_html( human_time_diff( time(), $next_run ) ); ?></span>
				<span class="bf-stat-card__label"><?php esc_html_e( 'Until next backup', 'wp-s3-backup' ); ?></span>
			<?php else : ?>
				<span class="bf-stat-card__value"><?php esc_html_e( 'Off', 'wp-s3-backup' ); ?></span>
				<span class="bf-stat-card__label"><?php esc_html_e( 'Schedule disabled', 'wp-s3-backup' ); ?></span>
			<?php endif; ?>
		</div>
		<div class="bf-stat-card">
			<div class="bf-stat-card__icon bf-stat-card__icon--storage">
				<span class="dashicons dashicons-cloud-saved"></span>
			</div>
			<?php if ( $has_creds ) : ?>
				<span class="bf-status bf-status--ok"><span class="bf-status__dot"></span> <?php esc_html_e( 'Connected', 'wp-s3-backup' ); ?></span>
			<?php else : ?>
				<span class="bf-status bf-status--warn"><span class="bf-status__dot"></span> <?php esc_html_e( 'Not configured', 'wp-s3-backup' ); ?></span>
			<?php endif; ?>
			<span class="bf-stat-card__label"><?php esc_html_e( 'S3 Connection', 'wp-s3-backup' ); ?></span>
		</div>
		<?php if ( $last_error ) : ?>
		<div class="bf-stat-card">
			<div class="bf-stat-card__icon bf-stat-card__icon--error">
				<span class="dashicons dashicons-warning"></span>
			</div>
			<span class="bf-status bf-status--err"><span class="bf-status__dot"></span> <?php esc_html_e( 'Error', 'wp-s3-backup' ); ?></span>
			<span class="bf-stat-card__label" title="<?php echo esc_attr( $last_error ); ?>"><?php echo esc_html( wp_trim_words( $last_error, 6 ) ); ?></span>
		</div>
		<?php endif; ?>
	</div>

	<form method="post" action="">
		<?php wp_nonce_field( 'wps3b_save_settings', 'wps3b_nonce' ); ?>

		<!-- Settings Cards -->
		<div class="bf-cards">

			<!-- AWS Credentials -->
			<div class="bf-card">
				<div class="bf-card__header">
					<span class="dashicons dashicons-admin-network"></span>
					<?php esc_html_e( 'AWS Credentials', 'wp-s3-backup' ); ?>
				</div>
				<div class="bf-card__body">
					<div class="bf-field">
						<label for="wps3b_access_key"><?php esc_html_e( 'Access Key ID', 'wp-s3-backup' ); ?></label>
						<input type="text" id="wps3b_access_key" name="wps3b_access_key"
							value="<?php echo esc_attr( $masked_ak ); ?>"
							class="regular-text" autocomplete="off"
							placeholder="AKIAIOSFODNN7EXAMPLE" />
						<p class="description"><?php esc_html_e( 'IAM user access key with S3 permissions.', 'wp-s3-backup' ); ?></p>
					</div>
					<div class="bf-field">
						<label for="wps3b_secret_key"><?php esc_html_e( 'Secret Access Key', 'wp-s3-backup' ); ?></label>
						<input type="password" id="wps3b_secret_key" name="wps3b_secret_key"
							value="<?php echo esc_attr( $masked_sk ); ?>"
							class="regular-text" autocomplete="off" />
						<p class="description"><?php esc_html_e( 'Encrypted at rest using your WordPress security salts.', 'wp-s3-backup' ); ?></p>
					</div>
				</div>
			</div>

			<!-- S3 Configuration -->
			<div class="bf-card">
				<div class="bf-card__header">
					<span class="dashicons dashicons-cloud-upload"></span>
					<?php esc_html_e( 'S3 Configuration', 'wp-s3-backup' ); ?>
				</div>
				<div class="bf-card__body">
					<div class="bf-field">
						<label for="wps3b_bucket"><?php esc_html_e( 'Bucket Name', 'wp-s3-backup' ); ?></label>
						<input type="text" id="wps3b_bucket" name="wps3b_bucket"
							value="<?php echo esc_attr( $settings['bucket'] ); ?>"
							class="regular-text" placeholder="wp-s3-backup-mysite" />
					</div>
					<div class="bf-field">
						<label for="wps3b_region"><?php esc_html_e( 'Region', 'wp-s3-backup' ); ?></label>
						<select id="wps3b_region" name="wps3b_region">
							<?php
							$regions = array(
								'us-east-1' => 'US East (N. Virginia)', 'us-east-2' => 'US East (Ohio)',
								'us-west-1' => 'US West (N. California)', 'us-west-2' => 'US West (Oregon)',
								'af-south-1' => 'Africa (Cape Town)', 'ap-east-1' => 'Asia Pacific (Hong Kong)',
								'ap-south-1' => 'Asia Pacific (Mumbai)', 'ap-southeast-1' => 'Asia Pacific (Singapore)',
								'ap-southeast-2' => 'Asia Pacific (Sydney)', 'ap-northeast-1' => 'Asia Pacific (Tokyo)',
								'ap-northeast-2' => 'Asia Pacific (Seoul)', 'ap-northeast-3' => 'Asia Pacific (Osaka)',
								'ca-central-1' => 'Canada (Central)', 'eu-central-1' => 'Europe (Frankfurt)',
								'eu-west-1' => 'Europe (Ireland)', 'eu-west-2' => 'Europe (London)',
								'eu-west-3' => 'Europe (Paris)', 'eu-north-1' => 'Europe (Stockholm)',
								'eu-south-1' => 'Europe (Milan)', 'me-south-1' => 'Middle East (Bahrain)',
								'sa-east-1' => 'South America (São Paulo)',
							);
							foreach ( $regions as $code => $label ) :
							?>
								<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $settings['region'], $code ); ?>>
									<?php echo esc_html( $label . ' (' . $code . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="bf-field">
						<label for="wps3b_s3_prefix"><?php esc_html_e( 'Path Prefix', 'wp-s3-backup' ); ?></label>
						<input type="text" id="wps3b_s3_prefix" name="wps3b_s3_prefix"
							value="<?php echo esc_attr( $settings['s3_prefix'] ); ?>"
							class="regular-text" placeholder="backups/mysite" />
						<p class="description"><?php esc_html_e( 'Folder path in the bucket. Leave empty for auto-detection.', 'wp-s3-backup' ); ?></p>
					</div>
					<div class="bf-field">
						<label for="wps3b_custom_endpoint"><?php esc_html_e( 'Custom Endpoint', 'wp-s3-backup' ); ?></label>
						<input type="url" id="wps3b_custom_endpoint" name="wps3b_custom_endpoint"
							value="<?php echo esc_attr( $settings['custom_endpoint'] ?? '' ); ?>"
							placeholder="https://s3.us-west-001.backblazeb2.com" />
						<p class="description"><?php esc_html_e( 'Leave empty for AWS S3. For S3-compatible providers: Backblaze B2, Wasabi, DigitalOcean Spaces, MinIO.', 'wp-s3-backup' ); ?></p>
					</div>
				</div>
				<div class="bf-card__footer">
					<button type="button" id="wps3b-test-connection" class="bf-btn bf-btn--outline bf-btn--sm">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Test Connection', 'wp-s3-backup' ); ?>
					</button>
					<span id="bf-test-result"></span>
				</div>
			</div>

			<!-- Backup Schedule -->
			<div class="bf-card">
				<div class="bf-card__header">
					<span class="dashicons dashicons-clock"></span>
					<?php esc_html_e( 'Backup Schedule', 'wp-s3-backup' ); ?>
				</div>
				<div class="bf-card__body">
					<div class="bf-field">
						<label class="bf-check">
							<input type="checkbox" name="wps3b_enabled" value="1" <?php checked( $settings['enabled'], 1 ); ?> />
							<?php esc_html_e( 'Enable automatic scheduled backups', 'wp-s3-backup' ); ?>
						</label>
					</div>
					<div class="bf-field">
						<label for="wps3b_frequency"><?php esc_html_e( 'Frequency', 'wp-s3-backup' ); ?></label>
						<select id="wps3b_frequency" name="wps3b_frequency">
							<?php if ( $is_pro ) : ?>
							<option value="hourly" <?php selected( $settings['frequency'], 'hourly' ); ?>><?php esc_html_e( 'Hourly', 'wp-s3-backup' ); ?></option>
							<option value="every_4_hours" <?php selected( $settings['frequency'], 'every_4_hours' ); ?>><?php esc_html_e( 'Every 4 Hours', 'wp-s3-backup' ); ?></option>
							<option value="every_6_hours" <?php selected( $settings['frequency'], 'every_6_hours' ); ?>><?php esc_html_e( 'Every 6 Hours', 'wp-s3-backup' ); ?></option>
							<?php endif; ?>
							<option value="twicedaily" <?php selected( $settings['frequency'], 'twicedaily' ); ?>><?php esc_html_e( 'Twice Daily', 'wp-s3-backup' ); ?></option>
							<option value="daily" <?php selected( $settings['frequency'], 'daily' ); ?>><?php esc_html_e( 'Daily', 'wp-s3-backup' ); ?></option>
							<option value="weekly" <?php selected( $settings['frequency'], 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'wp-s3-backup' ); ?></option>
							<option value="monthly" <?php selected( $settings['frequency'], 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'wp-s3-backup' ); ?></option>
						</select>
					</div>
				</div>
			</div>

			<!-- Backup Contents -->
			<div class="bf-card">
				<div class="bf-card__header">
					<span class="dashicons dashicons-portfolio"></span>
					<?php esc_html_e( 'Backup Contents', 'wp-s3-backup' ); ?>
				</div>
				<div class="bf-card__body">
					<div class="bf-field">
						<label class="bf-check">
							<input type="checkbox" name="wps3b_backup_db" value="1" <?php checked( $settings['backup_db'], 1 ); ?> />
							<?php esc_html_e( 'Database', 'wp-s3-backup' ); ?>
						</label>
						<label class="bf-check">
							<input type="checkbox" name="wps3b_backup_files" value="1" <?php checked( $settings['backup_files'], 1 ); ?> />
							<?php esc_html_e( 'Files (wp-content)', 'wp-s3-backup' ); ?>
						</label>
					</div>
					<div class="bf-field">
						<label for="wps3b_exclude_paths"><?php esc_html_e( 'Exclude Paths', 'wp-s3-backup' ); ?></label>
						<textarea id="wps3b_exclude_paths" name="wps3b_exclude_paths" rows="2" class="large-text"><?php echo esc_textarea( $settings['exclude_paths'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Comma-separated paths relative to wp-content/ to exclude.', 'wp-s3-backup' ); ?></p>
					</div>
				</div>
			</div>

			<!-- Pro Features -->
			<div class="bf-card bf-card--full">
				<div class="bf-card__header">
					<span class="dashicons dashicons-star-filled"></span>
					<?php esc_html_e( 'Advanced Features', 'wp-s3-backup' ); ?>
					<?php if ( ! $is_pro ) : ?><span class="bf-pro-badge">Pro</span><?php endif; ?>
				</div>
				<div class="bf-card__body">
					<?php if ( $is_pro ) : ?>
						<?php $pro_settings = WPS3B_Pro::get_settings(); ?>
						<div style="display:flex;flex-wrap:wrap;gap:24px;">
							<div>
								<span class="bf-status bf-status--<?php echo $pro_settings['incremental_enabled'] ? 'ok' : 'off'; ?>">
									<span class="bf-status__dot"></span> <?php esc_html_e( 'Incremental', 'wp-s3-backup' ); ?>
								</span>
							</div>
							<div>
								<span class="bf-status bf-status--<?php echo $pro_settings['encryption_enabled'] ? 'ok' : 'off'; ?>">
									<span class="bf-status__dot"></span> <?php esc_html_e( 'Encryption', 'wp-s3-backup' ); ?>
								</span>
							</div>
							<div>
								<span class="bf-status bf-status--<?php echo ! empty( $pro_settings['notification_email'] ) ? 'ok' : 'off'; ?>">
									<span class="bf-status__dot"></span> <?php esc_html_e( 'Email Alerts', 'wp-s3-backup' ); ?>
								</span>
							</div>
							<div>
								<span class="bf-status bf-status--<?php echo ! empty( $pro_settings['webhook_url'] ) ? 'ok' : 'off'; ?>">
									<span class="bf-status__dot"></span> <?php esc_html_e( 'Slack/Webhook', 'wp-s3-backup' ); ?>
								</span>
							</div>
						</div>
					<?php else : ?>
						<p style="color:var(--bf-text-muted);margin:0 0 12px;">
							<?php esc_html_e( 'Incremental backups, AES-256 encryption, email & Slack notifications, custom schedules, and more.', 'wp-s3-backup' ); ?>
						</p>
					<?php endif; ?>
				</div>
				<div class="bf-card__footer">
					<?php if ( $is_pro ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wps3b_license' ) ); ?>" class="bf-btn bf-btn--outline bf-btn--sm">
							<?php esc_html_e( 'Manage Pro Settings', 'wp-s3-backup' ); ?>
						</a>
					<?php else : ?>
						<a href="<?php echo esc_url( $upgrade_url ); ?>" class="bf-btn bf-btn--primary bf-btn--sm">
							<?php esc_html_e( 'Upgrade to Pro', 'wp-s3-backup' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>

		</div>

		<p class="description" style="margin-bottom:12px;">
			<?php
			printf(
				esc_html__( 'This plugin uploads backup files to Amazon S3. By using this plugin, you agree to %1$s and %2$s.', 'wp-s3-backup' ),
				'<a href="https://aws.amazon.com/service-terms/" target="_blank">' . esc_html__( 'AWS Terms of Service', 'wp-s3-backup' ) . '</a>',
				'<a href="https://aws.amazon.com/privacy/" target="_blank">' . esc_html__( 'AWS Privacy Policy', 'wp-s3-backup' ) . '</a>'
			);
			?>
		</p>

		<button type="submit" name="wps3b_save_settings" class="bf-btn bf-btn--primary">
			<span class="dashicons dashicons-saved"></span>
			<?php esc_html_e( 'Save Settings', 'wp-s3-backup' ); ?>
		</button>
	</form>
</div>
