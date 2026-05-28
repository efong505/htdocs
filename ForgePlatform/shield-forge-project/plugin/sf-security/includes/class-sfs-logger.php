<?php
if (!defined('ABSPATH')) exit;

class SFS_Logger {

	public static function log($event_type, $severity, $details = '', $username = '') {
		global $wpdb;
		$wpdb->insert($wpdb->prefix . 'sfs_log', [
			'event_type' => sanitize_key($event_type),
			'severity'   => in_array($severity, ['info', 'warning', 'critical']) ? $severity : 'info',
			'ip_address' => SFS_Plugin::get_client_ip(),
			'user_agent' => substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500),
			'username'   => sanitize_text_field($username),
			'user_id'    => get_current_user_id(),
			'details'    => is_array($details) ? wp_json_encode($details) : sanitize_text_field($details),
		]);
	}

	public static function get_recent($limit = 50, $args = []) {
		global $wpdb;
		$where = 'WHERE 1=1';
		$params = [];

		if (!empty($args['event_type'])) {
			$where .= ' AND event_type = %s';
			$params[] = $args['event_type'];
		}
		if (!empty($args['severity'])) {
			$where .= ' AND severity = %s';
			$params[] = $args['severity'];
		}
		if (!empty($args['ip_address'])) {
			$where .= ' AND ip_address = %s';
			$params[] = $args['ip_address'];
		}

		$params[] = (int) $limit;
		$sql = "SELECT * FROM {$wpdb->prefix}sfs_log $where ORDER BY created_at DESC LIMIT %d";
		return $wpdb->get_results($wpdb->prepare($sql, $params));
	}

	public static function get_stats($days = 7) {
		global $wpdb;
		$since = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));
		return [
			'total_events' => (int) $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}sfs_log WHERE created_at >= %s", $since
			)),
			'blocked' => (int) $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}sfs_log WHERE created_at >= %s AND event_type IN ('blocked_ip','waf_block','rate_limited','lockout')", $since
			)),
			'login_failures' => (int) $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}sfs_log WHERE created_at >= %s AND event_type = 'login_failed'", $since
			)),
			'login_successes' => (int) $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}sfs_log WHERE created_at >= %s AND event_type = 'login_success'", $since
			)),
			'unique_ips_blocked' => (int) $wpdb->get_var($wpdb->prepare(
				"SELECT COUNT(DISTINCT ip_address) FROM {$wpdb->prefix}sfs_log WHERE created_at >= %s AND event_type IN ('blocked_ip','waf_block','rate_limited','lockout')", $since
			)),
		];
	}

	public static function purge_old() {
		global $wpdb;
		$days = (int) get_option('sfs_log_retention_days', 90);
		$cutoff = gmdate('Y-m-d H:i:s', time() - ($days * DAY_IN_SECONDS));
		$wpdb->query($wpdb->prepare(
			"DELETE FROM {$wpdb->prefix}sfs_log WHERE created_at < %s", $cutoff
		));
	}
}
