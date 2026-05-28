<?php
if (!defined('ABSPATH')) exit;

class SFS_Plugin {

	private static $instance = null;

	public static function init() {
		if (self::$instance === null) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		SFS_Login::init();
		SFS_Blocklist::init();
		SFS_Rate_Limiter::init();
		SFS_Firewall::init();
		SFS_Settings::init();

		add_action('admin_menu', [$this, 'register_menus']);
		add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
	}

	public function register_menus() {
		add_menu_page(
			'ShieldForge', 'ShieldForge', 'manage_options',
			'sfs-dashboard', [SFS_Settings::class, 'page_dashboard'],
			'dashicons-shield', 3
		);
		add_submenu_page('sfs-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'sfs-dashboard', [SFS_Settings::class, 'page_dashboard']);
		add_submenu_page('sfs-dashboard', 'Firewall', 'Firewall', 'manage_options', 'sfs-firewall', [SFS_Settings::class, 'page_firewall']);
		add_submenu_page('sfs-dashboard', 'Blocklist', 'Blocklist', 'manage_options', 'sfs-blocklist', [SFS_Settings::class, 'page_blocklist']);
		add_submenu_page('sfs-dashboard', 'Activity Log', 'Activity Log', 'manage_options', 'sfs-log', [SFS_Settings::class, 'page_log']);
		add_submenu_page('sfs-dashboard', 'Settings', 'Settings', 'manage_options', 'sfs-settings', [SFS_Settings::class, 'page_settings']);
	}

	public function enqueue_assets($hook) {
		if (strpos($hook, 'sfs-') !== false) {
			wp_enqueue_style('sfs-admin', SFS_PLUGIN_URL . 'admin/css/admin.css', [], SFS_VERSION);
			wp_enqueue_script('sfs-admin', SFS_PLUGIN_URL . 'admin/js/admin.js', ['jquery'], SFS_VERSION, true);
			wp_localize_script('sfs-admin', 'sfs_ajax', [
				'url'   => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('sfs_admin'),
			]);
		}

		// Plugin list icon and banner
		if ($hook === 'plugins.php') {
			$icon_url = esc_url(SFS_PLUGIN_URL . 'assets/logo-mark.png');
			wp_add_inline_style('list-tables', "
				.plugins tr[data-plugin='sf-security/sf-security.php'] .plugin-title strong::before {
					content: '';
					display: inline-block;
					width: 24px;
					height: 24px;
					background: url('{$icon_url}') no-repeat center center;
					background-size: contain;
					vertical-align: middle;
					margin-right: 6px;
				}
			");
		}
	}

	public static function activate() {
		self::create_tables();
		self::set_defaults();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		// Keep data on deactivation, clean on uninstall
	}

	private static function create_tables() {
		global $wpdb;
		$charset = $wpdb->get_charset_collate();

		$sql = "
		CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sfs_log (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			event_type VARCHAR(50) NOT NULL,
			severity ENUM('info','warning','critical') DEFAULT 'info',
			ip_address VARCHAR(45) NOT NULL,
			user_agent VARCHAR(500) DEFAULT '',
			username VARCHAR(100) DEFAULT '',
			user_id BIGINT UNSIGNED DEFAULT 0,
			details TEXT DEFAULT '',
			country_code CHAR(2) DEFAULT '',
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			KEY event_type (event_type),
			KEY ip_address (ip_address),
			KEY created_at (created_at),
			KEY severity (severity)
		) $charset;

		CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sfs_blocklist (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			ip_address VARCHAR(45) NOT NULL,
			list_type ENUM('block','allow') DEFAULT 'block',
			reason VARCHAR(255) DEFAULT '',
			source ENUM('manual','auto','import') DEFAULT 'manual',
			expires_at DATETIME DEFAULT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY ip_list (ip_address, list_type),
			KEY list_type (list_type),
			KEY expires_at (expires_at)
		) $charset;

		CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sfs_login_attempts (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			ip_address VARCHAR(45) NOT NULL,
			username VARCHAR(100) NOT NULL,
			success TINYINT(1) DEFAULT 0,
			attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			KEY ip_address (ip_address),
			KEY attempted_at (attempted_at)
		) $charset;

		CREATE TABLE IF NOT EXISTS {$wpdb->prefix}sfs_lockouts (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			ip_address VARCHAR(45) NOT NULL,
			reason VARCHAR(255) DEFAULT '',
			lockout_count INT UNSIGNED DEFAULT 1,
			locked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			expires_at DATETIME NOT NULL,
			active TINYINT(1) DEFAULT 1,
			KEY ip_address (ip_address),
			KEY active (active),
			KEY expires_at (expires_at)
		) $charset;
		";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);
		update_option('sfs_db_version', SFS_VERSION);
	}

	private static function set_defaults() {
		$defaults = [
			'sfs_lockout_threshold'   => 5,
			'sfs_lockout_window'      => 15,
			'sfs_lockout_duration'    => 15,
			'sfs_lockout_escalation'  => '1',
			'sfs_permanent_ban_after' => 3,
			'sfs_disable_xmlrpc'      => '1',
			'sfs_hide_login_errors'   => '1',
			'sfs_block_user_enum'     => '1',
			'sfs_rate_limit'          => 120,
			'sfs_rate_limit_login'    => 10,
			'sfs_waf_enabled'         => '1',
			'sfs_notify_lockout'      => '1',
			'sfs_log_retention_days'  => 90,
		];
		foreach ($defaults as $key => $value) {
			if (get_option($key) === false) {
				add_option($key, $value);
			}
		}
	}

	public static function get_client_ip() {
		$keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
		foreach ($keys as $key) {
			if (!empty($_SERVER[$key])) {
				$ip = explode(',', $_SERVER[$key]);
				return sanitize_text_field(trim($ip[0]));
			}
		}
		return '0.0.0.0';
	}
}
