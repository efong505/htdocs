<?php
if (!defined('ABSPATH')) exit;

class SFS_Login {

	public static function init() {
		add_action('wp_login_failed', [__CLASS__, 'on_login_failed'], 10, 1);
		add_action('wp_login', [__CLASS__, 'on_login_success'], 10, 2);
		add_filter('authenticate', [__CLASS__, 'check_lockout'], 30, 3);
		add_filter('login_errors', [__CLASS__, 'hide_login_errors']);
		add_action('init', [__CLASS__, 'block_user_enumeration']);

		if (get_option('sfs_disable_xmlrpc', '1') === '1') {
			add_filter('xmlrpc_enabled', '__return_false');
			add_filter('wp_headers', [__CLASS__, 'remove_xmlrpc_header']);
		}
	}

	public static function on_login_failed($username) {
		global $wpdb;
		$ip = SFS_Plugin::get_client_ip();

		$wpdb->insert($wpdb->prefix . 'sfs_login_attempts', [
			'ip_address' => $ip,
			'username'   => sanitize_text_field($username),
			'success'    => 0,
		]);

		SFS_Logger::log('login_failed', 'warning', ['username' => $username], $username);

		// Check if threshold reached
		$window = (int) get_option('sfs_lockout_window', 15);
		$threshold = (int) get_option('sfs_lockout_threshold', 5);
		$since = gmdate('Y-m-d H:i:s', time() - ($window * MINUTE_IN_SECONDS));

		$failures = (int) $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}sfs_login_attempts WHERE ip_address = %s AND success = 0 AND attempted_at >= %s",
			$ip, $since
		));

		if ($failures >= $threshold) {
			self::lockout($ip);
		}
	}

	public static function on_login_success($username, $user) {
		global $wpdb;
		$ip = SFS_Plugin::get_client_ip();

		$wpdb->insert($wpdb->prefix . 'sfs_login_attempts', [
			'ip_address' => $ip,
			'username'   => $username,
			'success'    => 1,
		]);

		SFS_Logger::log('login_success', 'info', ['user_id' => $user->ID], $username);

		// Clear failed attempts for this IP on successful login
		$wpdb->delete($wpdb->prefix . 'sfs_login_attempts', [
			'ip_address' => $ip,
			'success'    => 0,
		]);
	}

	public static function check_lockout($user, $username, $password) {
		if (empty($username)) return $user;

		$ip = SFS_Plugin::get_client_ip();
		$lockout = self::get_active_lockout($ip);

		if ($lockout) {
			SFS_Logger::log('blocked_ip', 'critical', ['reason' => 'Active lockout'], $username);
			$remaining = human_time_diff(time(), strtotime($lockout->expires_at));
			return new WP_Error('sfs_locked_out', sprintf(
				__('Too many failed login attempts. Please try again in %s.', 'sf-security'),
				$remaining
			));
		}

		// Also check blocklist
		if (SFS_Blocklist::is_blocked($ip)) {
			return new WP_Error('sfs_blocked', __('Access denied.', 'sf-security'));
		}

		return $user;
	}

	public static function hide_login_errors($error) {
		if (get_option('sfs_hide_login_errors', '1') !== '1') return $error;
		return __('Invalid login credentials.', 'sf-security');
	}

	public static function block_user_enumeration() {
		if (get_option('sfs_block_user_enum', '1') !== '1') return;

		// Block ?author=N enumeration
		if (!is_admin() && isset($_GET['author']) && is_numeric($_GET['author'])) {
			SFS_Logger::log('user_enum_blocked', 'warning', 'author parameter blocked');
			wp_die(__('Access denied.', 'sf-security'), 403);
		}

		// Block REST API user endpoint for unauthenticated users
		add_filter('rest_endpoints', function ($endpoints) {
			if (!is_user_logged_in()) {
				unset($endpoints['/wp/v2/users']);
				unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
			}
			return $endpoints;
		});
	}

	public static function remove_xmlrpc_header($headers) {
		unset($headers['X-Pingback']);
		return $headers;
	}

	private static function lockout($ip) {
		global $wpdb;
		$table = $wpdb->prefix . 'sfs_lockouts';

		// Get previous lockout count for escalation
		$prev = $wpdb->get_var($wpdb->prepare(
			"SELECT MAX(lockout_count) FROM $table WHERE ip_address = %s", $ip
		));
		$count = $prev ? (int) $prev + 1 : 1;

		// Escalating duration
		$base_duration = (int) get_option('sfs_lockout_duration', 15);
		if (get_option('sfs_lockout_escalation', '1') === '1') {
			$duration = $base_duration * pow(2, min($count - 1, 4)); // 15, 30, 60, 120, 240 max
		} else {
			$duration = $base_duration;
		}

		$expires = gmdate('Y-m-d H:i:s', time() + ($duration * MINUTE_IN_SECONDS));

		$wpdb->insert($table, [
			'ip_address'    => $ip,
			'reason'        => 'Brute force threshold exceeded',
			'lockout_count' => $count,
			'expires_at'    => $expires,
			'active'        => 1,
		]);

		SFS_Logger::log('lockout', 'critical', [
			'duration_min' => $duration,
			'lockout_count' => $count,
		]);

		// Auto-ban after N lockouts
		$ban_after = (int) get_option('sfs_permanent_ban_after', 3);
		if ($ban_after > 0 && $count >= $ban_after) {
			SFS_Blocklist::add($ip, 'block', 'Auto-banned after ' . $count . ' lockouts', 'auto');
			SFS_Logger::log('auto_banned', 'critical', 'Permanent ban after ' . $count . ' lockouts');
		}

		// Email notification
		if (get_option('sfs_notify_lockout', '1') === '1') {
			$admin_email = get_option('admin_email');
			$site_name = get_bloginfo('name');
			wp_mail(
				$admin_email,
				sprintf('[%s] Security: IP Locked Out', $site_name),
				sprintf(
					"IP Address: %s\nLockout #%d\nDuration: %d minutes\nExpires: %s\n\nManage in ShieldForge → Blocklist",
					$ip, $count, $duration, $expires
				)
			);
		}
	}

	public static function get_active_lockout($ip) {
		global $wpdb;
		return $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}sfs_lockouts WHERE ip_address = %s AND active = 1 AND expires_at > %s ORDER BY expires_at DESC LIMIT 1",
			$ip, current_time('mysql', true)
		));
	}

	public static function get_active_lockouts() {
		global $wpdb;
		return $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}sfs_lockouts WHERE active = 1 AND expires_at > %s ORDER BY locked_at DESC",
			current_time('mysql', true)
		));
	}

	public static function clear_lockout($ip) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'sfs_lockouts',
			['active' => 0],
			['ip_address' => $ip, 'active' => 1]
		);
	}
}
