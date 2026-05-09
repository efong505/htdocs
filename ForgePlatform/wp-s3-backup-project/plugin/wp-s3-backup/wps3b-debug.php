<?php
/**
 * BackForge Debug Helper — mu-plugin for troubleshooting.
 *
 * Features:
 * - Toggle WordPress WP_DEBUG on/off from admin UI
 * - View and clear BackForge restore/backup status
 * - Delete .maintenance file
 * - Clear temp files
 * - Emergency reset URL for when the site is broken
 *
 * Admin page: BackForge → Debug
 * Emergency URL: /wp-admin/admin-ajax.php?action=wps3b_emergency_reset&key=backforge2026
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Emergency reset via URL — works even when site is broken
add_action( 'wp_ajax_wps3b_emergency_reset', function() {
	if ( ( $_GET['key'] ?? '' ) !== 'backforge2026' ) wp_die( 'Invalid key.' );

	delete_option( 'wps3b_restore_status' );
	delete_option( 'wps3b_backup_status' );
	wp_clear_scheduled_hook( 'wps3b_run_background_restore' );
	wp_clear_scheduled_hook( 'wps3b_run_background_backup' );
	@unlink( ABSPATH . '.maintenance' );

	$temp = WP_CONTENT_DIR . '/wps3b-temp';
	if ( is_dir( $temp ) ) {
		$files = glob( $temp . '/*' );
		if ( $files ) {
			foreach ( $files as $f ) {
				if ( is_file( $f ) && ! in_array( basename( $f ), array( '.htaccess', 'index.php' ), true ) ) {
					@unlink( $f );
				}
			}
		}
	}

	wp_die( '<h2>BackForge Reset Complete</h2><p>Restore status, backup status, maintenance mode, and temp files cleared.</p><p><a href="' . admin_url( 'admin.php?page=wps3b_backups' ) . '">Back to Backups</a></p>' );
});

// Debug admin page
add_action( 'admin_menu', function() {
	add_submenu_page(
		'wps3b_settings',
		'Debug',
		'Debug',
		'manage_options',
		'wps3b_debug',
		'wps3b_debug_page'
	);
}, 99 );

/**
 * Toggle WP_DEBUG in wp-config.php.
 */
function wps3b_toggle_wp_debug( $enable ) {
	$config_path = ABSPATH . 'wp-config.php';
	if ( ! is_writable( $config_path ) ) {
		return false;
	}

	$config = file_get_contents( $config_path );
	if ( false === $config ) {
		return false;
	}

	$debug_val     = $enable ? 'true' : 'false';
	$debug_log_val = $enable ? 'true' : 'false';

	// Replace or add WP_DEBUG
	if ( preg_match( "/define\s*\(\s*['\"]WP_DEBUG['\"]\s*,\s*(true|false)\s*\)/", $config ) ) {
		$config = preg_replace(
			"/define\s*\(\s*['\"]WP_DEBUG['\"]\s*,\s*(true|false)\s*\)/",
			"define( 'WP_DEBUG', {$debug_val} )",
			$config
		);
	}

	// Replace or add WP_DEBUG_LOG
	if ( preg_match( "/define\s*\(\s*['\"]WP_DEBUG_LOG['\"]\s*,\s*(true|false)\s*\)/", $config ) ) {
		$config = preg_replace(
			"/define\s*\(\s*['\"]WP_DEBUG_LOG['\"]\s*,\s*(true|false)\s*\)/",
			"define( 'WP_DEBUG_LOG', {$debug_log_val} )",
			$config
		);
	} elseif ( $enable ) {
		// Add WP_DEBUG_LOG after WP_DEBUG if it doesn't exist
		$config = preg_replace(
			"/(define\s*\(\s*['\"]WP_DEBUG['\"]\s*,\s*true\s*\)\s*;)/",
			"$1\ndefine( 'WP_DEBUG_LOG', true );",
			$config
		);
	}

	// Replace or add WP_DEBUG_DISPLAY
	if ( preg_match( "/define\s*\(\s*['\"]WP_DEBUG_DISPLAY['\"]\s*,\s*(true|false)\s*\)/", $config ) ) {
		$config = preg_replace(
			"/define\s*\(\s*['\"]WP_DEBUG_DISPLAY['\"]\s*,\s*(true|false)\s*\)/",
			"define( 'WP_DEBUG_DISPLAY', false )",
			$config
		);
	} elseif ( $enable ) {
		// Add WP_DEBUG_DISPLAY after WP_DEBUG_LOG
		$config = preg_replace(
			"/(define\s*\(\s*['\"]WP_DEBUG_LOG['\"]\s*,\s*true\s*\)\s*;)/",
			"$1\ndefine( 'WP_DEBUG_DISPLAY', false );",
			$config
		);
	}

	return file_put_contents( $config_path, $config ) !== false;
}

function wps3b_debug_page() {
	if ( ! current_user_can( 'manage_options' ) ) return;

	$message = '';

	// Handle actions
	if ( isset( $_POST['wps3b_debug_action'] ) && check_admin_referer( 'wps3b_debug' ) ) {
		$action = sanitize_key( $_POST['wps3b_debug_action'] );
		switch ( $action ) {
			case 'toggle_debug':
				$enable = ! WP_DEBUG;
				if ( wps3b_toggle_wp_debug( $enable ) ) {
					$message = $enable
						? '<div class="notice notice-success"><p>WP_DEBUG enabled. Errors will be logged to <code>wp-content/debug.log</code>. Reload the page to see the change.</p></div>'
						: '<div class="notice notice-success"><p>WP_DEBUG disabled. Reload the page to see the change.</p></div>';
				} else {
					$message = '<div class="notice notice-error"><p>Could not modify wp-config.php. Check file permissions.</p></div>';
				}
				break;
			case 'clear_debug_log':
				$log = WP_CONTENT_DIR . '/debug.log';
				if ( file_exists( $log ) ) {
					file_put_contents( $log, '' );
					$message = '<div class="notice notice-success"><p>Debug log cleared.</p></div>';
				}
				break;
			case 'clear_restore':
				delete_option( 'wps3b_restore_status' );
				wp_clear_scheduled_hook( 'wps3b_run_background_restore' );
				$message = '<div class="notice notice-success"><p>Restore status cleared.</p></div>';
				break;
			case 'clear_backup':
				delete_option( 'wps3b_backup_status' );
				wp_clear_scheduled_hook( 'wps3b_run_background_backup' );
				$message = '<div class="notice notice-success"><p>Backup status cleared.</p></div>';
				break;
			case 'delete_maintenance':
				@unlink( ABSPATH . '.maintenance' );
				$message = '<div class="notice notice-success"><p>.maintenance file deleted.</p></div>';
				break;
			case 'clear_temp':
				$temp = defined( 'WPS3B_TEMP_DIR' ) ? WPS3B_TEMP_DIR : WP_CONTENT_DIR . '/wps3b-temp';
				$files = glob( $temp . '/*' );
				$count = 0;
				if ( $files ) {
					foreach ( $files as $f ) {
						if ( is_file( $f ) && ! in_array( basename( $f ), array( '.htaccess', 'index.php' ), true ) ) {
							@unlink( $f );
							$count++;
						}
					}
				}
				$message = '<div class="notice notice-success"><p>' . $count . ' temp files deleted.</p></div>';
				break;
		}
	}

	$restore_status = get_option( 'wps3b_restore_status', array() );
	$backup_status  = get_option( 'wps3b_backup_status', array() );
	$maintenance    = file_exists( ABSPATH . '.maintenance' );
	$temp_dir       = defined( 'WPS3B_TEMP_DIR' ) ? WPS3B_TEMP_DIR : WP_CONTENT_DIR . '/wps3b-temp';
	$temp_files     = is_dir( $temp_dir ) ? glob( $temp_dir . '/*' ) : array();
	$debug_log      = WP_CONTENT_DIR . '/debug.log';
	$debug_log_size = file_exists( $debug_log ) ? filesize( $debug_log ) : 0;
	$debug_log_tail = '';
	if ( $debug_log_size > 0 ) {
		$fp = fopen( $debug_log, 'r' );
		$tail_bytes = min( $debug_log_size, 5000 );
		fseek( $fp, -$tail_bytes, SEEK_END );
		$debug_log_tail = fread( $fp, $tail_bytes );
		fclose( $fp );
	}

	echo $message;
	?>
	<div class="wrap">
		<h1>BackForge Debug</h1>

		<h2>WordPress Debug Mode</h2>
		<p>Status: <strong><?php echo WP_DEBUG ? '🟡 ON' : '⚪ OFF'; ?></strong></p>
		<p style="color:#666;font-size:13px;">When enabled, PHP errors are logged to <code>wp-content/debug.log</code> (not displayed on screen).</p>
		<form method="post" style="display:inline;">
			<?php wp_nonce_field( 'wps3b_debug' ); ?>
			<input type="hidden" name="wps3b_debug_action" value="toggle_debug" />
			<button type="submit" class="button button-primary">
				<?php echo WP_DEBUG ? 'Turn Debug OFF' : 'Turn Debug ON'; ?>
			</button>
		</form>

		<?php if ( $debug_log_size > 0 ) : ?>
		<div style="margin-top:12px;">
			<p>Log file: <strong><?php echo size_format( $debug_log_size ); ?></strong></p>
			<form method="post" style="display:inline;">
				<?php wp_nonce_field( 'wps3b_debug' ); ?>
				<input type="hidden" name="wps3b_debug_action" value="clear_debug_log" />
				<button type="submit" class="button button-secondary">Clear Debug Log</button>
			</form>
			<details style="margin-top:8px;">
				<summary style="cursor:pointer;color:#2271b1;">View last entries</summary>
				<pre style="background:#1e293b;color:#e2e8f0;padding:16px;border-radius:8px;overflow:auto;max-height:400px;font-size:12px;margin-top:8px;"><?php echo esc_html( $debug_log_tail ); ?></pre>
			</details>
		</div>
		<?php endif; ?>

		<hr style="margin:24px 0;" />

		<h2>Restore Status</h2>
		<?php if ( empty( $restore_status ) ) : ?>
			<p>No active restore.</p>
		<?php else : ?>
			<pre style="background:#1e293b;color:#e2e8f0;padding:16px;border-radius:8px;overflow:auto;font-size:12px;"><?php echo esc_html( print_r( $restore_status, true ) ); ?></pre>
			<form method="post">
				<?php wp_nonce_field( 'wps3b_debug' ); ?>
				<input type="hidden" name="wps3b_debug_action" value="clear_restore" />
				<button type="submit" class="button button-secondary">Clear Restore Status</button>
			</form>
		<?php endif; ?>

		<h2>Backup Status</h2>
		<?php if ( empty( $backup_status ) ) : ?>
			<p>No active backup.</p>
		<?php else : ?>
			<pre style="background:#1e293b;color:#e2e8f0;padding:16px;border-radius:8px;overflow:auto;font-size:12px;"><?php echo esc_html( print_r( $backup_status, true ) ); ?></pre>
			<form method="post">
				<?php wp_nonce_field( 'wps3b_debug' ); ?>
				<input type="hidden" name="wps3b_debug_action" value="clear_backup" />
				<button type="submit" class="button button-secondary">Clear Backup Status</button>
			</form>
		<?php endif; ?>

		<h2>Maintenance Mode</h2>
		<p>Status: <strong><?php echo $maintenance ? '🔴 ACTIVE' : '🟢 Off'; ?></strong></p>
		<?php if ( $maintenance ) : ?>
			<form method="post">
				<?php wp_nonce_field( 'wps3b_debug' ); ?>
				<input type="hidden" name="wps3b_debug_action" value="delete_maintenance" />
				<button type="submit" class="button button-secondary">Delete .maintenance File</button>
			</form>
		<?php endif; ?>

		<h2>Temp Files</h2>
		<?php if ( empty( $temp_files ) ) : ?>
			<p>No temp files.</p>
		<?php else : ?>
			<ul>
			<?php foreach ( $temp_files as $f ) : if ( ! is_file( $f ) ) continue; ?>
				<li><?php echo esc_html( basename( $f ) . ' — ' . size_format( filesize( $f ) ) ); ?></li>
			<?php endforeach; ?>
			</ul>
			<form method="post">
				<?php wp_nonce_field( 'wps3b_debug' ); ?>
				<input type="hidden" name="wps3b_debug_action" value="clear_temp" />
				<button type="submit" class="button button-secondary">Clear Temp Files</button>
			</form>
		<?php endif; ?>

		<hr style="margin:24px 0;" />
		<h2>Emergency Reset URL</h2>
		<p style="color:#666;font-size:13px;">If the site is completely broken (503, fatal error), visit this URL while logged in to clear all BackForge state:</p>
		<code style="display:block;padding:8px;background:#f0f0f0;border-radius:4px;"><?php echo esc_url( admin_url( 'admin-ajax.php?action=wps3b_emergency_reset&key=backforge2026' ) ); ?></code>
	</div>
	<?php
}
