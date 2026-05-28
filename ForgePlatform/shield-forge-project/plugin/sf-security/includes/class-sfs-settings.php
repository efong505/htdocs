<?php
if (!defined('ABSPATH')) exit;

class SFS_Settings {

	public static function init() {
		add_action('admin_init', [__CLASS__, 'handle_settings_save']);
	}

	public static function handle_settings_save() {
		if (!isset($_POST['sfs_save_settings'])) return;
		if (!current_user_can('manage_options')) return;
		check_admin_referer('sfs_settings', 'sfs_nonce');

		$fields = [
			'sfs_lockout_threshold', 'sfs_lockout_window', 'sfs_lockout_duration',
			'sfs_lockout_escalation', 'sfs_permanent_ban_after', 'sfs_disable_xmlrpc',
			'sfs_hide_login_errors', 'sfs_block_user_enum', 'sfs_rate_limit',
			'sfs_rate_limit_login', 'sfs_waf_enabled', 'sfs_notify_lockout',
			'sfs_log_retention_days',
		];

		foreach ($fields as $field) {
			if (isset($_POST[$field])) {
				update_option($field, sanitize_text_field($_POST[$field]));
			} else {
				// Checkboxes that are unchecked
				if (in_array($field, ['sfs_lockout_escalation', 'sfs_disable_xmlrpc', 'sfs_hide_login_errors', 'sfs_block_user_enum', 'sfs_waf_enabled', 'sfs_notify_lockout'])) {
					update_option($field, '0');
				}
			}
		}

		wp_redirect(admin_url('admin.php?page=sfs-settings&saved=1'));
		exit;
	}

	// --- Pages ---

	public static function page_dashboard() {
		$stats = SFS_Logger::get_stats(7);
		$lockouts = SFS_Login::get_active_lockouts();
		$recent = SFS_Logger::get_recent(10);
		$blocked_count = SFS_Blocklist::count('block');
		include SFS_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	public static function page_firewall() {
		$rules = SFS_Firewall::get_rules_public();
		$recent = SFS_Logger::get_recent(50, ['event_type' => 'waf_block']);
		include SFS_PLUGIN_DIR . 'admin/views/firewall.php';
	}

	public static function page_blocklist() {
		$blocked = SFS_Blocklist::get_all('block');
		$allowed = SFS_Blocklist::get_all('allow');
		$lockouts = SFS_Login::get_active_lockouts();
		include SFS_PLUGIN_DIR . 'admin/views/blocklist.php';
	}

	public static function page_log() {
		$page = max(1, (int) ($_GET['paged'] ?? 1));
		$per_page = 50;
		$filter_type = sanitize_key($_GET['type'] ?? '');
		$filter_severity = sanitize_key($_GET['severity'] ?? '');
		$events = SFS_Logger::get_recent($per_page, [
			'event_type' => $filter_type,
			'severity'   => $filter_severity,
		]);
		include SFS_PLUGIN_DIR . 'admin/views/activity-log.php';
	}

	public static function page_settings() {
		include SFS_PLUGIN_DIR . 'admin/views/settings.php';
	}
}
