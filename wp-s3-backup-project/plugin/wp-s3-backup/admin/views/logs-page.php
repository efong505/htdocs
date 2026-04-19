<?php
/**
 * Logs page — BackForge branded.
 *
 * @package BackForge
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$entries = WPS3B_Logger::get_entries();
?>

<div class="wrap bf-wrap">
	<div class="bf-header">
		<h1 class="bf-header__title">
			<span class="bf-header__icon"><span class="dashicons dashicons-cloud-upload"></span></span>
			BackForge — Activity Log
		</h1>
	</div>

	<div class="bf-log-controls">
		<button type="button" id="wps3b-refresh-logs" class="bf-btn bf-btn--outline bf-btn--sm">
			<span class="dashicons dashicons-update wps3b-spin-icon"></span>
			<?php esc_html_e( 'Refresh', 'wp-s3-backup' ); ?>
		</button>
		<label class="bf-check" style="margin:0;">
			<input type="checkbox" id="wps3b-auto-refresh" />
			<?php esc_html_e( 'Auto-refresh every 5s', 'wp-s3-backup' ); ?>
		</label>
		<span id="bf-refresh-status" style="margin-left:auto;font-size:12px;color:var(--bf-text-muted);"></span>
	</div>

	<div id="wps3b-log-container">
		<?php WPS3B_Logger::render_table( $entries ); ?>
	</div>
</div>
