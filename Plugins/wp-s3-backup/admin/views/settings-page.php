<?php
/**
 * Settings page view.
 *
 * @package WP_S3_Backup
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
?>

<div class="wrap wps3b-wrap">
	<h1><?php esc_html_e( 'WP S3 Backup — Settings', 'wp-s3-backup' ); ?></h1>

	<?php settings_errors( 'wps3b_settings' ); ?>

	<!-- Status Bar -->
	<div class="wps3b-status-bar">
		<div class="wps3b-status-item">
			<span class="wps3b-status-label"><?php esc_html_e( 'Credentials:', 'wp-s3-backup' ); ?></span>
			<?php if ( $has_creds ) : ?>
				<span class="wps3b-status-ok">&#9679; <?php esc_html_e( 'Configured', 'wp-s3-backup' ); ?></span>
			<?php else : ?>
				<span class="wps3b-status-warn">&#9679; <?php esc_html_e( 'Not configured', 'wp-s3-backup' ); ?></span>
			<?php endif; ?>
		</div>
		<div class="wps3b-status-item">
			<span class="wps3b-status-label"><?php esc_html_e( 'Schedule:', 'wp-s3-backup' ); ?></span>
			<?php if ( $settings['enabled'] && $next_run ) : ?>
				<span class="wps3b-status-ok">&#9679;
					<?php
					printf(
						/* translators: Next backup date/time */
						esc_html__( 'Next: %s', 'wp-s3-backup' ),
						esc_html( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $next_run ), 'Y-m-d H:i:s' ) )
					);
					?>
				</span>
			<?php else : ?>
				<span class="wps3b-status-off">&#9679; <?php esc_html_e( 'Disabled', 'wp-s3-backup' ); ?></span>
			<?php endif; ?>
		</div>
		<div class="wps3b-status-item">
			<span class="wps3b-status-label"><?php esc_html_e( 'Last backup:', 'wp-s3-backup' ); ?></span>
			<?php if ( $last_backup ) : ?>
				<span class="wps3b-status-ok"><?php echo esc_html( $last_backup ); ?></span>
			<?php else : ?>
				<span class="wps3b-status-off"><?php esc_html_e( 'Never', 'wp-s3-backup' ); ?></span>
			<?php endif; ?>
		</div>
		<?php if ( $last_error ) : ?>
		<div class="wps3b-status-item">
			<span class="wps3b-status-label"><?php esc_html_e( 'Last error:', 'wp-s3-backup' ); ?></span>
			<span class="wps3b-status-err"><?php echo esc_html( $last_error ); ?></span>
		</div>
		<?php endif; ?>
	</div>

	<form method="post" action="">
		<?php wp_nonce_field( 'wps3b_save_settings', 'wps3b_nonce' ); ?>

		<!-- AWS Credentials -->
		<h2><?php esc_html_e( 'AWS Credentials', 'wp-s3-backup' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="wps3b_access_key"><?php esc_html_e( 'Access Key ID', 'wp-s3-backup' ); ?></label></th>
				<td>
					<input type="text" id="wps3b_access_key" name="wps3b_access_key"
						value="<?php echo esc_attr( $masked_ak ); ?>"
						class="regular-text" autocomplete="off"
						placeholder="AKIAIOSFODNN7EXAMPLE" />
					<p class="description"><?php esc_html_e( 'IAM user access key with S3 permissions.', 'wp-s3-backup' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="wps3b_secret_key"><?php esc_html_e( 'Secret Access Key', 'wp-s3-backup' ); ?></label></th>
				<td>
					<input type="password" id="wps3b_secret_key" name="wps3b_secret_key"
						value="<?php echo esc_attr( $masked_sk ); ?>"
						class="regular-text" autocomplete="off" />
					<p class="description"><?php esc_html_e( 'Encrypted at rest using your WordPress security salts.', 'wp-s3-backup' ); ?></p>
				</td>
			</tr>
		</table>

		<!-- S3 Configuration -->
		<h2><?php esc_html_e( 'S3 Configuration', 'wp-s3-backup' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="wps3b_bucket"><?php esc_html_e( 'Bucket Name', 'wp-s3-backup' ); ?></label></th>
				<td>
					<input type="text" id="wps3b_bucket" name="wps3b_bucket"
						value="<?php echo esc_attr( $settings['bucket'] ); ?>"
						class="regular-text" placeholder="wp-s3-backup-mysite" />
				</td>
			</tr>
			<tr>
				<th><label for="wps3b_region"><?php esc_html_e( 'Region', 'wp-s3-backup' ); ?></label></th>
				<td>
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
				</td>
			</tr>
			<tr>
				<th><label for="wps3b_s3_prefix"><?php esc_html_e( 'Path Prefix', 'wp-s3-backup' ); ?></label></th>
				<td>
					<input type="text" id="wps3b_s3_prefix" name="wps3b_s3_prefix"
						value="<?php echo esc_attr( $settings['s3_prefix'] ); ?>"
						class="regular-text" placeholder="backups/mysite" />
					<p class="description"><?php esc_html_e( 'Folder path in the bucket. Leave empty for auto-detection from site URL.', 'wp-s3-backup' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Test Connection', 'wp-s3-backup' ); ?></th>
				<td>
					<button type="button" id="wps3b-test-connection" class="button button-secondary">
						<?php esc_html_e( 'Test Connection', 'wp-s3-backup' ); ?>
					</button>
					<span id="wps3b-test-result"></span>
				</td>
			</tr>
		</table>

		<!-- Backup Options -->
		<h2><?php esc_html_e( 'Backup Options', 'wp-s3-backup' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Schedule', 'wp-s3-backup' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wps3b_enabled" value="1" <?php checked( $settings['enabled'], 1 ); ?> />
						<?php esc_html_e( 'Enable automatic scheduled backups', 'wp-s3-backup' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><label for="wps3b_frequency"><?php esc_html_e( 'Frequency', 'wp-s3-backup' ); ?></label></th>
				<td>
					<select id="wps3b_frequency" name="wps3b_frequency">
						<option value="twicedaily" <?php selected( $settings['frequency'], 'twicedaily' ); ?>><?php esc_html_e( 'Twice Daily', 'wp-s3-backup' ); ?></option>
						<option value="daily" <?php selected( $settings['frequency'], 'daily' ); ?>><?php esc_html_e( 'Daily', 'wp-s3-backup' ); ?></option>
						<option value="weekly" <?php selected( $settings['frequency'], 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'wp-s3-backup' ); ?></option>
						<option value="monthly" <?php selected( $settings['frequency'], 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'wp-s3-backup' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Backup Contents', 'wp-s3-backup' ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="wps3b_backup_db" value="1" <?php checked( $settings['backup_db'], 1 ); ?> />
						<?php esc_html_e( 'Database', 'wp-s3-backup' ); ?>
					</label>
					<br />
					<label>
						<input type="checkbox" name="wps3b_backup_files" value="1" <?php checked( $settings['backup_files'], 1 ); ?> />
						<?php esc_html_e( 'Files (wp-content)', 'wp-s3-backup' ); ?>
					</label>
				</td>
			</tr>
			<tr>
				<th><label for="wps3b_exclude_paths"><?php esc_html_e( 'Exclude Paths', 'wp-s3-backup' ); ?></label></th>
				<td>
					<textarea id="wps3b_exclude_paths" name="wps3b_exclude_paths" rows="3" class="large-text"><?php echo esc_textarea( $settings['exclude_paths'] ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Comma-separated paths relative to wp-content/ to exclude from file backup.', 'wp-s3-backup' ); ?></p>
				</td>
			</tr>
		</table>

		<!-- External Service Disclosure -->
		<p class="description">
			<?php
			printf(
				/* translators: 1: AWS Terms link, 2: AWS Privacy link */
				esc_html__( 'This plugin uploads backup files to Amazon S3. By using this plugin, you agree to %1$s and %2$s.', 'wp-s3-backup' ),
				'<a href="https://aws.amazon.com/service-terms/" target="_blank">' . esc_html__( 'AWS Terms of Service', 'wp-s3-backup' ) . '</a>',
				'<a href="https://aws.amazon.com/privacy/" target="_blank">' . esc_html__( 'AWS Privacy Policy', 'wp-s3-backup' ) . '</a>'
			);
			?>
		</p>

		<p class="submit">
			<input type="submit" name="wps3b_save_settings" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'wp-s3-backup' ); ?>" />
		</p>
	</form>
</div>
