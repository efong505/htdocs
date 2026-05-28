<?php
if (!defined('ABSPATH')) exit;

class SFS_Blocklist {

	public static function init() {
		add_action('init', [__CLASS__, 'check_request'], 1);
		add_action('wp_ajax_sfs_blocklist_action', [__CLASS__, 'handle_ajax']);
	}

	public static function check_request() {
		if (is_admin() && current_user_can('manage_options')) return;

		$ip = SFS_Plugin::get_client_ip();

		if (self::is_allowed($ip)) return;

		if (self::is_blocked($ip)) {
			SFS_Logger::log('blocked_ip', 'critical', 'IP on blocklist');
			http_response_code(403);
			wp_die(
				__('Access denied. Your IP has been blocked.', 'sf-security'),
				__('Blocked', 'sf-security'),
				['response' => 403]
			);
		}
	}

	public static function is_blocked($ip) {
		$list = self::get_cached_list('block');
		// Direct match
		if (isset($list[$ip])) return true;
		// CIDR match
		foreach ($list as $entry => $v) {
			if (strpos($entry, '/') !== false && self::ip_in_cidr($ip, $entry)) {
				return true;
			}
		}
		return false;
	}

	public static function is_allowed($ip) {
		$list = self::get_cached_list('allow');
		if (isset($list[$ip])) return true;
		foreach ($list as $entry => $v) {
			if (strpos($entry, '/') !== false && self::ip_in_cidr($ip, $entry)) {
				return true;
			}
		}
		return false;
	}

	public static function add($ip, $type = 'block', $reason = '', $source = 'manual', $expires = null) {
		global $wpdb;
		$wpdb->replace($wpdb->prefix . 'sfs_blocklist', [
			'ip_address' => sanitize_text_field($ip),
			'list_type'  => $type === 'allow' ? 'allow' : 'block',
			'reason'     => sanitize_text_field($reason),
			'source'     => in_array($source, ['manual', 'auto', 'import']) ? $source : 'manual',
			'expires_at' => $expires,
		]);
		self::invalidate_cache();
	}

	public static function remove($id) {
		global $wpdb;
		$wpdb->delete($wpdb->prefix . 'sfs_blocklist', ['id' => (int) $id]);
		self::invalidate_cache();
	}

	public static function get_all($type = '') {
		global $wpdb;
		$where = '';
		$params = [];
		if ($type) {
			$where = 'WHERE list_type = %s';
			$params[] = $type;
		}
		$sql = "SELECT * FROM {$wpdb->prefix}sfs_blocklist $where ORDER BY created_at DESC";
		return $params ? $wpdb->get_results($wpdb->prepare($sql, $params)) : $wpdb->get_results($sql);
	}

	public static function count($type = 'block') {
		global $wpdb;
		return (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}sfs_blocklist WHERE list_type = %s", $type
		));
	}

	public static function handle_ajax() {
		check_ajax_referer('sfs_admin', 'nonce');
		if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

		$action = sanitize_key($_POST['blocklist_action'] ?? '');

		switch ($action) {
			case 'add':
				$ip = sanitize_text_field($_POST['ip'] ?? '');
				$type = sanitize_key($_POST['type'] ?? 'block');
				$reason = sanitize_text_field($_POST['reason'] ?? '');
				if (empty($ip)) wp_send_json_error('IP address required.');
				self::add($ip, $type, $reason, 'manual');
				wp_send_json_success('Added.');
				break;

			case 'remove':
				$id = (int) ($_POST['id'] ?? 0);
				if (!$id) wp_send_json_error('Invalid ID.');
				self::remove($id);
				wp_send_json_success('Removed.');
				break;

			case 'unlock':
				$ip = sanitize_text_field($_POST['ip'] ?? '');
				SFS_Login::clear_lockout($ip);
				wp_send_json_success('Lockout cleared.');
				break;

			default:
				wp_send_json_error('Invalid action.');
		}
	}

	private static function get_cached_list($type) {
		$key = 'sfs_' . $type . 'list';
		$list = get_transient($key);
		if ($list === false) {
			$list = self::load_list($type);
			set_transient($key, $list, 300);
		}
		return $list;
	}

	private static function load_list($type) {
		global $wpdb;
		$now = current_time('mysql', true);
		$rows = $wpdb->get_col($wpdb->prepare(
			"SELECT ip_address FROM {$wpdb->prefix}sfs_blocklist WHERE list_type = %s AND (expires_at IS NULL OR expires_at > %s)",
			$type, $now
		));
		$list = [];
		foreach ($rows as $ip) {
			$list[$ip] = true;
		}
		return $list;
	}

	private static function invalidate_cache() {
		delete_transient('sfs_blocklist');
		delete_transient('sfs_allowlist');
	}

	private static function ip_in_cidr($ip, $cidr) {
		list($subnet, $mask) = explode('/', $cidr);
		$mask = (int) $mask;
		if ($mask < 0 || $mask > 32) return false;
		return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === (ip2long($subnet) & ~((1 << (32 - $mask)) - 1));
	}
}
