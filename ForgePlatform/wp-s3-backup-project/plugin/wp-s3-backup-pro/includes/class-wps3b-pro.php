<?php
/**
 * Main Pro class — registers all Pro features and admin UI.
 *
 * @package WP_S3_Backup_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPS3B_Pro {

	/**
	 * Initialize Pro plugin.
	 */
	public static function init() {
		WPS3B_Pro_License::init();

		add_action( 'admin_menu', array( __CLASS__, 'register_menus' ), 25 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_save_settings' ) );

		if ( ! self::is_licensed() ) {
			return;
		}

		// Register all Pro features when licensed
		WPS3B_Pro_Notifications::init();
		WPS3B_Pro_Encryption::init();
		WPS3B_Pro_Storage::init();
		WPS3B_Pro_Schedule::init();
		WPS3B_Pro_Restore::init();
		WPS3B_Pro_Incremental::init();

		// Inject external restore UI on backups page
		add_action( 'wps3b_after_backups_list', array( __CLASS__, 'render_external_restore_ui' ) );
	}

	/**
	 * Check if Pro is licensed (proxy for license class).
	 */
	public static function is_licensed() {
		return WPS3B_Pro_License::is_licensed();
	}

	/**
	 * Register Pro admin menus.
	 */
	public static function register_menus() {
		add_submenu_page(
			'wps3b_settings',
			__( 'License', 'wp-s3-backup-pro' ),
			__( 'License', 'wp-s3-backup-pro' ),
			'manage_options',
			'wps3b_license',
			array( __CLASS__, 'render_license_page' )
		);

		if ( self::is_licensed() ) {
			add_submenu_page(
				'wps3b_settings',
				__( 'Storage Management', 'wp-s3-backup-pro' ),
				__( 'Storage', 'wp-s3-backup-pro' ),
				'manage_options',
				'wps3b_storage',
				array( __CLASS__, 'render_storage_page' )
			);
		}

		// Remove the "Upgrade to Pro" menu when licensed
		if ( self::is_licensed() ) {
			remove_submenu_page( 'wps3b_settings', 'wps3b_upgrade' );
		}
	}

	/**
	 * Enqueue Pro admin assets.
	 */
	public static function enqueue_assets( $hook ) {
		if ( strpos( $hook, 'wps3b_' ) === false && 'toplevel_page_wps3b_settings' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wps3b-pro-admin',
			WPS3B_PRO_URL . 'admin/css/pro-admin.css',
			array( 'wps3b-admin' ),
			WPS3B_PRO_VERSION
		);

		wp_enqueue_script(
			'wps3b-pro-admin',
			WPS3B_PRO_URL . 'admin/js/pro-admin.js',
			array( 'jquery' ),
			WPS3B_PRO_VERSION,
			true
		);

		wp_localize_script( 'wps3b-pro-admin', 'wps3b_pro', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'wps3b_pro_ajax' ),
		));
	}

	/**
	 * Handle Pro settings save (notifications, encryption, schedule).
	 */
	public static function handle_save_settings() {
		if ( ! isset( $_POST['wps3b_pro_save_settings'] ) ) {
			return;
		}
		check_admin_referer( 'wps3b_pro_settings', 'wps3b_pro_settings_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$pro_settings = array(
			// Notifications
			'notification_email'   => sanitize_email( wp_unslash( $_POST['wps3b_pro_notification_email'] ?? '' ) ),
			'notify_success'       => isset( $_POST['wps3b_pro_notify_success'] ) ? 1 : 0,
			'notify_failure'       => isset( $_POST['wps3b_pro_notify_failure'] ) ? 1 : 0,
			'webhook_url'          => esc_url_raw( wp_unslash( $_POST['wps3b_pro_webhook_url'] ?? '' ) ),
			// Encryption
			'encryption_enabled'   => isset( $_POST['wps3b_pro_encryption_enabled'] ) ? 1 : 0,
			'encryption_password'  => sanitize_text_field( wp_unslash( $_POST['wps3b_pro_encryption_password'] ?? '' ) ),
			// Incremental
			'incremental_enabled'  => isset( $_POST['wps3b_pro_incremental_enabled'] ) ? 1 : 0,
			// Schedule
			'custom_schedule'      => sanitize_key( wp_unslash( $_POST['wps3b_pro_custom_schedule'] ?? '' ) ),
			'schedule_time'        => sanitize_text_field( wp_unslash( $_POST['wps3b_pro_schedule_time'] ?? '' ) ),
		);

		// Don't overwrite password if placeholder submitted
		if ( empty( $pro_settings['encryption_password'] ) || false !== strpos( $pro_settings['encryption_password'], '****' ) ) {
			$existing = get_option( 'wps3b_pro_settings', array() );
			$pro_settings['encryption_password'] = $existing['encryption_password'] ?? '';
		}

		update_option( 'wps3b_pro_settings', $pro_settings );

		// Reschedule if custom schedule changed
		if ( ! empty( $pro_settings['custom_schedule'] ) ) {
			WPS3B_Pro_Schedule::reschedule( $pro_settings['custom_schedule'], $pro_settings['schedule_time'] );
		}

		add_settings_error( 'wps3b_pro', 'saved', __( 'Pro settings saved.', 'wp-s3-backup-pro' ), 'success' );
	}

	/**
	 * Get Pro settings with defaults.
	 */
	public static function get_settings() {
		return wp_parse_args( get_option( 'wps3b_pro_settings', array() ), array(
			'notification_email'  => get_option( 'admin_email' ),
			'notify_success'      => 1,
			'notify_failure'      => 1,
			'webhook_url'         => '',
			'encryption_enabled'  => 0,
			'encryption_password' => '',
			'incremental_enabled' => 0,
			'custom_schedule'     => '',
			'schedule_time'       => '03:00',
		));
	}

	/**
	 * Render the external restore UI (injected on backups page).
	 */
	public static function render_external_restore_ui() {
		?>
		<div style="margin-top:30px;">
			<h2 style="color:#fff;border:none;font-size:16px;">Restore from Another Site <span class="bf-pro-badge">Pro</span></h2>
			<p style="color:var(--bf-text-muted);font-size:13px;margin-bottom:16px;">Restore a backup from a different site in the same S3 bucket. Files are copied directly within S3 (no download required) then restored locally.</p>

			<div class="bf-card" style="max-width:700px;">
				<div class="bf-card__body">
					<div class="bf-field">
						<label>Source Path Prefix</label>
						<div style="display:flex;gap:8px;margin-bottom:8px;">
							<input type="text" id="wps3b-ext-prefix" placeholder="backups/other-site-com" style="flex:1;" />
							<button type="button" id="wps3b-ext-browse" class="bf-btn bf-btn--outline bf-btn--sm">Browse Backups</button>
						</div>
						<!-- Prefix selector -->
						<div style="display:flex;gap:8px;align-items:center;">
							<select id="wps3b-prefix-select" style="flex:1;">
								<option value="">— Select a site from your bucket —</option>
							</select>
							<button type="button" id="wps3b-load-prefixes" class="bf-btn bf-btn--outline bf-btn--sm">Load Sites</button>
						</div>
						<span id="wps3b-prefix-status" style="font-size:12px;margin-top:4px;display:block;"></span>
						<span id="wps3b-ext-status" style="font-size:12px;margin-top:4px;display:block;"></span>
					</div>
				</div>
			</div>

			<div id="wps3b-ext-results" style="display:none;max-width:700px;margin-top:12px;">
				<table class="widefat striped"><thead><tr><th>Date</th><th>Files</th><th>Size</th><th>Action</th></tr></thead><tbody id="wps3b-ext-tbody"></tbody></table>
			</div>

			<div id="wps3b-ext-restore-form" style="display:none;max-width:700px;margin-top:12px;">
				<div class="bf-card">
					<div class="bf-card__body">
						<form method="post">
							<?php wp_nonce_field( 'wps3b_pro_external_restore', 'wps3b_pro_ext_nonce' ); ?>
							<input type="hidden" name="wps3b_ext_prefix" id="wps3b-ext-form-prefix" />
							<input type="hidden" name="wps3b_ext_timestamp" id="wps3b-ext-form-timestamp" />
							<div class="bf-field">
								<label>Restore Type</label>
								<label class="bf-check"><input type="radio" name="wps3b_ext_restore_type" value="full" checked /> Full Site</label>
								<label class="bf-check"><input type="radio" name="wps3b_ext_restore_type" value="database" /> Database Only</label>
								<label class="bf-check"><input type="radio" name="wps3b_ext_restore_type" value="files" /> Files Only</label>
							</div>
							<div class="bf-field">
								<label>Old URL</label>
								<input type="url" name="wps3b_ext_old_url" placeholder="https://other-site.com" />
							</div>
							<div class="bf-field">
								<label>New URL</label>
								<input type="url" name="wps3b_ext_new_url" value="<?php echo esc_attr( get_site_url() ); ?>" />
							</div>
							<button type="submit" name="wps3b_pro_external_restore" class="bf-btn bf-btn--danger" onclick="return confirm('This will overwrite your site. Are you sure?');">Restore from External Backup</button>
						</form>
					</div>
				</div>
			</div>
		</div>
		<?php
		self::render_upload_restore_ui();
	}

	/**
	 * Render the upload & restore UI.
	 */
	public static function render_upload_restore_ui() {
		?>
		<div style="margin-top:30px;">
			<h2 style="color:#fff;border:none;font-size:16px;">Upload &amp; Restore <span class="bf-pro-badge">Pro</span></h2>
			<p style="color:var(--bf-text-muted);font-size:13px;margin-bottom:16px;">Upload backup files from another site and restore from them.</p>

			<div class="bf-card" style="max-width:700px;">
				<div class="bf-card__body">
					<div class="bf-field">
						<label>Database Backup</label>
						<input type="file" id="wps3b-upload-db" accept=".gz" style="display:none;" />
						<button type="button" class="bf-btn bf-btn--outline bf-btn--sm wps3b-upload-btn" data-target="db"><span class="dashicons dashicons-upload"></span> Upload .sql.gz</button>
						<span id="wps3b-upload-db-status" style="margin-left:8px;font-size:12px;"></span>
						<input type="hidden" id="wps3b-upload-db-path" />
					</div>
					<div class="bf-field">
						<label>Files Backup</label>
						<input type="file" id="wps3b-upload-files" accept=".zip" style="display:none;" />
						<button type="button" class="bf-btn bf-btn--outline bf-btn--sm wps3b-upload-btn" data-target="files"><span class="dashicons dashicons-upload"></span> Upload .zip</button>
						<span id="wps3b-upload-files-status" style="margin-left:8px;font-size:12px;"></span>
						<input type="hidden" id="wps3b-upload-files-path" />
					</div>
				</div>
			</div>

			<div id="wps3b-upload-restore-progress" style="display:none;margin:12px 0;max-width:400px;">
				<div class="bf-progress"><div id="wps3b-upload-restore-bar" class="bf-progress__bar" style="width:0%;"></div></div>
				<span id="wps3b-upload-restore-pct" style="font-size:12px;color:var(--bf-text-muted);"></span>
			</div>

			<div id="wps3b-upload-restore-form" style="display:none;max-width:700px;margin-top:12px;">
				<div class="bf-card">
					<div class="bf-card__body">
						<form method="post">
							<?php wp_nonce_field( 'wps3b_pro_upload_restore', 'wps3b_pro_upload_nonce' ); ?>
							<input type="hidden" name="wps3b_upload_db_path" id="wps3b-upload-db-path-form" />
							<input type="hidden" name="wps3b_upload_files_path" id="wps3b-upload-files-path-form" />
							<div class="bf-field">
								<label>Restore Type</label>
								<label class="bf-check"><input type="radio" name="wps3b_upload_restore_type" value="full" checked /> Full Site</label>
								<label class="bf-check"><input type="radio" name="wps3b_upload_restore_type" value="database" /> Database Only</label>
								<label class="bf-check"><input type="radio" name="wps3b_upload_restore_type" value="files" /> Files Only</label>
							</div>
							<div class="bf-field">
								<label>Old URL</label>
								<input type="url" name="wps3b_upload_old_url" placeholder="https://source-site.com" />
							</div>
							<div class="bf-field">
								<label>New URL</label>
								<input type="url" name="wps3b_upload_new_url" value="<?php echo esc_attr( get_site_url() ); ?>" />
							</div>
							<button type="submit" name="wps3b_pro_upload_restore" class="bf-btn bf-btn--danger" onclick="return confirm('This will overwrite your site. Are you sure?');">Restore from Uploaded Backup</button>
						</form>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the license page.
	 */
	public static function render_license_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require WPS3B_PRO_DIR . 'admin/views/license-page.php';
	}

	/**
	 * Render the storage management page.
	 */
	public static function render_storage_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require WPS3B_PRO_DIR . 'admin/views/storage-management.php';
	}
}
