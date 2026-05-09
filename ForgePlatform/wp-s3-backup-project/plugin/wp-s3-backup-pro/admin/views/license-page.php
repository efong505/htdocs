<?php
/**
 * License page — BackForge Pro dark UI.
 *
 * @package BackForge_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$status     = WPS3B_Pro_License::get_status();
$masked_key = WPS3B_Pro_License::get_masked_key();
$has_key    = ! empty( get_option( 'wps3b_pro_license_key', '' ) );
?>

<div class="wrap bf-wrap">
	<div class="bf-header">
		<h1 class="bf-header__title">
			<span class="bf-header__icon"><span class="dashicons dashicons-cloud-upload"></span></span>
			BackForge — License
			<span class="bf-pro-badge">Pro</span>
		</h1>
	</div>

	<?php settings_errors( 'wps3b_pro' ); ?>

	<!-- License Status -->
	<div class="bf-dashboard" style="margin-bottom:24px;">
		<div class="bf-stat-card">
			<div class="bf-stat-card__icon" style="background:<?php echo 'active' === $status['status'] ? 'var(--bf-success-bg)' : 'var(--bf-danger-bg)'; ?>;color:<?php echo 'active' === $status['status'] ? 'var(--bf-success)' : 'var(--bf-danger)'; ?>;">
				<span class="dashicons dashicons-<?php echo 'active' === $status['status'] ? 'yes-alt' : 'warning'; ?>"></span>
			</div>
			<?php if ( 'active' === $status['status'] ) : ?>
				<span class="bf-status bf-status--ok"><span class="bf-status__dot"></span> Active</span>
			<?php elseif ( 'grace' === $status['status'] ) : ?>
				<span class="bf-status bf-status--warn"><span class="bf-status__dot"></span> Grace Period</span>
			<?php elseif ( 'expired' === $status['status'] ) : ?>
				<span class="bf-status bf-status--err"><span class="bf-status__dot"></span> Expired</span>
			<?php else : ?>
				<span class="bf-status bf-status--off"><span class="bf-status__dot"></span> Not activated</span>
			<?php endif; ?>
			<span class="bf-stat-card__label">License Status</span>
		</div>
		<?php if ( 'active' === $status['status'] && ! empty( $status['tier'] ) ) : ?>
		<div class="bf-stat-card">
			<div class="bf-stat-card__icon bf-stat-card__icon--storage"><span class="dashicons dashicons-awards"></span></div>
			<span class="bf-stat-card__value"><?php echo esc_html( ucfirst( $status['tier'] ) ); ?></span>
			<span class="bf-stat-card__label">License Tier</span>
		</div>
		<?php endif; ?>
		<?php if ( ! empty( $status['expires'] ) ) : ?>
		<div class="bf-stat-card">
			<div class="bf-stat-card__icon bf-stat-card__icon--schedule"><span class="dashicons dashicons-calendar-alt"></span></div>
			<span class="bf-stat-card__value"><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $status['expires'] ) ) ); ?></span>
			<span class="bf-stat-card__label">Expires</span>
		</div>
		<?php endif; ?>
	</div>

	<?php if ( $has_key && WPS3B_Pro_License::is_licensed() ) : ?>

		<!-- License Key + Deactivate -->
		<div class="bf-card" style="max-width:500px;margin-bottom:24px;">
			<div class="bf-card__header">
				<span class="dashicons dashicons-admin-network"></span>
				License Key
			</div>
			<div class="bf-card__body">
				<code style="font-size:14px;color:var(--bf-teal);background:rgba(20,184,166,0.1);padding:6px 12px;border-radius:var(--bf-radius-xs);display:inline-block;"><?php echo esc_html( $masked_key ); ?></code>
			</div>
			<div class="bf-card__footer">
				<form method="post" style="display:inline;">
					<?php wp_nonce_field( 'wps3b_pro_license', 'wps3b_pro_nonce' ); ?>
					<button type="submit" name="wps3b_pro_deactivate" class="bf-btn bf-btn--outline bf-btn--sm" onclick="return confirm('Deactivate this license?');">
						<span class="dashicons dashicons-dismiss"></span> Deactivate
					</button>
				</form>
			</div>
		</div>

		<!-- Pro Settings -->
		<?php
		$pro = WPS3B_Pro::get_settings();
		$inc_stats = WPS3B_Pro_Incremental::get_stats();
		?>
		<form method="post">
			<?php wp_nonce_field( 'wps3b_pro_settings', 'wps3b_pro_settings_nonce' ); ?>

			<div class="bf-cards">

				<!-- Notifications -->
				<div class="bf-card">
					<div class="bf-card__header">
						<span class="dashicons dashicons-email-alt"></span>
						Notifications
					</div>
					<div class="bf-card__body">
						<div class="bf-field">
							<label for="wps3b_pro_notification_email">Email Address</label>
							<input type="email" id="wps3b_pro_notification_email" name="wps3b_pro_notification_email" value="<?php echo esc_attr( $pro['notification_email'] ); ?>" />
						</div>
						<div class="bf-field">
							<label>Notify On</label>
							<label class="bf-check"><input type="checkbox" name="wps3b_pro_notify_success" value="1" <?php checked( $pro['notify_success'], 1 ); ?> /> Successful backups</label>
							<label class="bf-check"><input type="checkbox" name="wps3b_pro_notify_failure" value="1" <?php checked( $pro['notify_failure'], 1 ); ?> /> Failed backups</label>
						</div>
						<div class="bf-field">
							<label for="wps3b_pro_webhook_url">Slack / Webhook URL</label>
							<input type="url" id="wps3b_pro_webhook_url" name="wps3b_pro_webhook_url" value="<?php echo esc_attr( $pro['webhook_url'] ); ?>" placeholder="https://hooks.slack.com/services/..." />
							<p class="description">Slack incoming webhook or any HTTP endpoint.</p>
						</div>
					</div>
				</div>

				<!-- Encryption -->
				<div class="bf-card">
					<div class="bf-card__header">
						<span class="dashicons dashicons-lock"></span>
						Encryption
					</div>
					<div class="bf-card__body">
						<div class="bf-field">
							<label class="bf-check"><input type="checkbox" name="wps3b_pro_encryption_enabled" value="1" <?php checked( $pro['encryption_enabled'], 1 ); ?> /> Encrypt backups with AES-256 before uploading</label>
						</div>
						<div class="bf-field">
							<label for="wps3b_pro_encryption_password">Encryption Password</label>
							<input type="password" id="wps3b_pro_encryption_password" name="wps3b_pro_encryption_password" value="<?php echo esc_attr( ! empty( $pro['encryption_password'] ) ? '********' : '' ); ?>" autocomplete="new-password" />
							<p class="description">Keep this password safe — you need it to restore encrypted backups.</p>
						</div>
					</div>
				</div>

				<!-- Incremental -->
				<div class="bf-card">
					<div class="bf-card__header">
						<span class="dashicons dashicons-update"></span>
						Incremental Backups
					</div>
					<div class="bf-card__body">
						<div class="bf-field">
							<label class="bf-check"><input type="checkbox" name="wps3b_pro_incremental_enabled" value="1" <?php checked( $pro['incremental_enabled'], 1 ); ?> /> Only back up files changed since last backup</label>
							<?php if ( $inc_stats['tracked_files'] > 0 ) : ?>
								<p class="description">Tracking <?php echo esc_html( $inc_stats['tracked_files'] ); ?> files. Last snapshot: <?php echo esc_html( $inc_stats['last_updated'] ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<!-- Custom Schedule -->
				<div class="bf-card">
					<div class="bf-card__header">
						<span class="dashicons dashicons-clock"></span>
						Custom Schedule
					</div>
					<div class="bf-card__body">
						<div class="bf-field">
							<label for="wps3b_pro_custom_schedule">Frequency</label>
							<select id="wps3b_pro_custom_schedule" name="wps3b_pro_custom_schedule">
								<option value="">— Use free plugin setting —</option>
								<option value="hourly" <?php selected( $pro['custom_schedule'], 'hourly' ); ?>>Hourly</option>
								<option value="every_4_hours" <?php selected( $pro['custom_schedule'], 'every_4_hours' ); ?>>Every 4 Hours</option>
								<option value="every_6_hours" <?php selected( $pro['custom_schedule'], 'every_6_hours' ); ?>>Every 6 Hours</option>
								<option value="twicedaily" <?php selected( $pro['custom_schedule'], 'twicedaily' ); ?>>Twice Daily</option>
								<option value="daily" <?php selected( $pro['custom_schedule'], 'daily' ); ?>>Daily</option>
								<option value="weekly" <?php selected( $pro['custom_schedule'], 'weekly' ); ?>>Weekly</option>
							</select>
						</div>
						<div class="bf-field">
							<label for="wps3b_pro_schedule_time">Preferred Time</label>
							<input type="time" id="wps3b_pro_schedule_time" name="wps3b_pro_schedule_time" value="<?php echo esc_attr( $pro['schedule_time'] ); ?>" />
							<p class="description">Server timezone. Applies to daily/weekly schedules.</p>
						</div>
					</div>
				</div>

			</div>

			<button type="submit" name="wps3b_pro_save_settings" class="bf-btn bf-btn--primary">
				<span class="dashicons dashicons-saved"></span>
				Save Pro Settings
			</button>
		</form>

	<?php else : ?>

		<!-- Activation Form -->
		<div class="bf-card" style="max-width:500px;">
			<div class="bf-card__header">
				<span class="dashicons dashicons-admin-network"></span>
				Activate License
			</div>
			<div class="bf-card__body">
				<form method="post">
					<?php wp_nonce_field( 'wps3b_pro_license', 'wps3b_pro_nonce' ); ?>
					<div class="bf-field">
						<label for="wps3b_pro_license_key">License Key</label>
						<input type="text" id="wps3b_pro_license_key" name="wps3b_pro_license_key" value="" placeholder="WPS3B-XXXX-XXXX-XXXX" autocomplete="off" />
						<p class="description">Enter your license key from your purchase confirmation email.</p>
					</div>
					<button type="submit" name="wps3b_pro_activate" class="bf-btn bf-btn--primary">
						<span class="dashicons dashicons-yes-alt"></span>
						Activate License
					</button>
				</form>
			</div>
		</div>

		<div class="bf-pro-hint" style="margin-top:16px;max-width:500px;">
			<span class="bf-pro-badge">Pro</span>
			Don't have a license? <a href="<?php echo esc_url( apply_filters( 'wps3b_pro_purchase_url', 'https://ekewaka.com/pricing/' ) ); ?>" target="_blank">Purchase BackForge Pro</a> to unlock all advanced features.
		</div>

	<?php endif; ?>
</div>
