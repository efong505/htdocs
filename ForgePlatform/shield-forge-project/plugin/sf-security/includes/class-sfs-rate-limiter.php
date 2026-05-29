<?php
if (!defined('ABSPATH')) exit;

class SFS_Rate_Limiter {

	public static function init() {
		add_action('init', [__CLASS__, 'check_rate'], 2);
	}

	public static function check_rate() {
		if (is_admin() && current_user_can('manage_options')) return;
		if (defined('DOING_CRON') && DOING_CRON) return;

		$ip = SFS_Plugin::get_client_ip();

		if (SFS_Blocklist::is_allowed($ip)) return;

		$limit = self::get_limit_for_request();
		if (!self::is_within_limit($ip, $limit)) {
			SFS_Logger::log('rate_limited', 'warning', ['limit' => $limit, 'uri' => $_SERVER['REQUEST_URI'] ?? '']);
			wp_die(
				__('Too many requests. Please slow down.', 'sf-security'),
				__('Rate Limited', 'sf-security'),
				['response' => 429]
			);
		}
	}

	private static function get_limit_for_request() {
		$uri = $_SERVER['REQUEST_URI'] ?? '';

		if (strpos($uri, 'wp-login.php') !== false) {
			return (int) get_option('sfs_rate_limit_login', 10);
		}
		if (strpos($uri, 'xmlrpc.php') !== false) {
			return 5;
		}
		return (int) get_option('sfs_rate_limit', 120);
	}

	private static function is_within_limit($ip, $limit, $window = 60) {
		$key = 'sfs_rate_' . md5($ip . self::get_endpoint_group());
		$data = get_transient($key);

		if ($data === false) {
			set_transient($key, ['count' => 1, 'start' => time()], $window);
			return true;
		}

		if (time() - $data['start'] > $window) {
			set_transient($key, ['count' => 1, 'start' => time()], $window);
			return true;
		}

		$data['count']++;
		$remaining_ttl = $window - (time() - $data['start']);
		set_transient($key, $data, max($remaining_ttl, 1));

		return $data['count'] <= $limit;
	}

	private static function get_endpoint_group() {
		$uri = $_SERVER['REQUEST_URI'] ?? '';
		if (strpos($uri, 'wp-login.php') !== false) return 'login';
		if (strpos($uri, 'xmlrpc.php') !== false) return 'xmlrpc';
		if (strpos($uri, 'wp-json') !== false) return 'rest';
		return 'general';
	}
}
