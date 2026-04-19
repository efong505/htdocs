<?php
/**
 * Upgrade page — BackForge Pro.
 *
 * @package BackForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap bf-wrap">
	<div class="bf-header">
		<h1 class="bf-header__title">
			<span class="bf-header__icon"><span class="dashicons dashicons-cloud-upload"></span></span>
			BackForge Pro
		</h1>
	</div>

	<!-- Upgrade Banner -->
	<div style="margin-bottom:24px;border-radius:var(--bf-radius);overflow:hidden;border:1px solid var(--bf-border);">
		<img src="<?php echo esc_url( WPS3B_PLUGIN_URL . 'admin/images/upgrade-banner.png' ); ?>" alt="BackForge Pro" style="display:block;width:100%;height:auto;" />
	</div>

	<div class="bf-upgrade-hero">
		<h2><?php esc_html_e( 'Upgrade to BackForge Pro', 'wp-s3-backup' ); ?></h2>
		<p><?php esc_html_e( 'Your backups. Your bucket. Your control. Take it to the next level.', 'wp-s3-backup' ); ?></p>
	</div>

	<div class="bf-upgrade-grid">

		<div class="bf-upgrade-card">
			<div class="bf-upgrade-card__icon dashicons dashicons-backup"></div>
			<h3><?php esc_html_e( 'Advanced Restore', 'wp-s3-backup' ); ?></h3>
			<ul>
				<li><?php esc_html_e( 'Selective restore — database only or files only', 'wp-s3-backup' ); ?></li>
				<li><?php esc_html_e( 'Serialization-safe URL replacement on restore', 'wp-s3-backup' ); ?></li>
				<li><?php esc_html_e( 'Restore from another site in the same S3 bucket', 'wp-s3-backup' ); ?></li>
				<li><?php esc_html_e( 'Upload & restore from downloaded backup files', 'wp-s3-backup' ); ?></li>
			</ul>
		</div>

		<div class="bf-upgrade-card">
			<div class="bf-upgrade-card__icon dashicons dashicons-cloud-upload"></div>
			<h3><?php esc_html_e( 'Smarter Backups', 'wp-s3-backup' ); ?></h3>
			<ul>
				<li><?php esc_html_e( 'Incremental — only upload files that changed', 'wp-s3-backup' ); ?></li>
				<li><?php esc_html_e( 'AES-256 encryption before uploading to S3', 'wp-s3-backup' ); ?></li>
				<li><?php esc_html_e( 'Custom schedules — hourly, every 4/6 hours', 'wp-s3-backup' ); ?></li>
				<li><?php esc_html_e( 'Time-of-day picker for daily/weekly backups', 'wp-s3-backup' ); ?></li>
			</ul>
		</div>

		<div class="bf-upgrade-card">
			<div class="bf-upgrade-card__icon dashicons dashicons-cloud-saved"></div>
			<h3><?php esc_html_e( 'S3 Storage Management', 'wp-s3-backup' ); ?></h3>
			<ul>
				<li><?php esc_html_e( 'Change storage class — Standard to Glacier and more', 'wp-s3-backup' ); ?></li>
				<li><?php esc_html_e( 'Monthly cost estimate based on your actual usage', 'wp-s3-backup' ); ?></li>
				<li><?php esc_html_e( 'Storage class breakdown with per-class costs', 'wp-s3-backup' ); ?></li>
			</ul>
		</div>

		<div class="bf-upgrade-card">
			<div class="bf-upgrade-card__icon dashicons dashicons-email-alt"></div>
			<h3><?php esc_html_e( 'Notifications', 'wp-s3-backup' ); ?></h3>
			<ul>
				<li><?php esc_html_e( 'Email alerts on backup success or failure', 'wp-s3-backup' ); ?></li>
				<li><?php esc_html_e( 'Slack integration for team notifications', 'wp-s3-backup' ); ?></li>
				<li><?php esc_html_e( 'Custom webhook URL for any integration', 'wp-s3-backup' ); ?></li>
			</ul>
		</div>

	</div>

	<div class="bf-pricing-section">
		<h2><?php esc_html_e( 'Simple, Transparent Pricing', 'wp-s3-backup' ); ?></h2>

		<div class="bf-pricing-grid">
			<div class="bf-pricing-card">
				<h3><?php esc_html_e( 'Personal', 'wp-s3-backup' ); ?></h3>
				<div><span class="bf-pricing-amount">$49</span><span class="bf-pricing-period">/<?php esc_html_e( 'year', 'wp-s3-backup' ); ?></span></div>
				<p><?php esc_html_e( '1 site license', 'wp-s3-backup' ); ?></p>
			</div>

			<div class="bf-pricing-card bf-pricing-featured">
				<div class="bf-pricing-popular"><?php esc_html_e( 'Most Popular', 'wp-s3-backup' ); ?></div>
				<h3><?php esc_html_e( 'Professional', 'wp-s3-backup' ); ?></h3>
				<div><span class="bf-pricing-amount">$99</span><span class="bf-pricing-period">/<?php esc_html_e( 'year', 'wp-s3-backup' ); ?></span></div>
				<p><?php esc_html_e( '5 site licenses', 'wp-s3-backup' ); ?></p>
			</div>

			<div class="bf-pricing-card">
				<h3><?php esc_html_e( 'Agency', 'wp-s3-backup' ); ?></h3>
				<div><span class="bf-pricing-amount">$199</span><span class="bf-pricing-period">/<?php esc_html_e( 'year', 'wp-s3-backup' ); ?></span></div>
				<p><?php esc_html_e( 'Unlimited sites', 'wp-s3-backup' ); ?></p>
			</div>
		</div>

		<p style="margin-top:24px;">
			<a href="<?php echo esc_url( apply_filters( 'wps3b_pro_purchase_url', 'https://ekewaka.com/pricing/' ) ); ?>" target="_blank" class="bf-btn bf-btn--primary" style="font-size:15px;padding:12px 32px;">
				<?php esc_html_e( 'Get BackForge Pro', 'wp-s3-backup' ); ?>
			</a>
		</p>
	</div>
</div>
