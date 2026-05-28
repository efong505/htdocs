<?php
if (!defined('ABSPATH')) exit;

class SFS_Firewall {

	public static function init() {
		if (get_option('sfs_waf_enabled', '1') !== '1') return;
		add_action('init', [__CLASS__, 'inspect_request'], 3);
	}

	public static function inspect_request() {
		if (is_admin() && current_user_can('manage_options')) return;
		if (defined('DOING_CRON') && DOING_CRON) return;

		$ip = SFS_Plugin::get_client_ip();
		if (SFS_Blocklist::is_allowed($ip)) return;

		// Skip static files
		$uri = $_SERVER['REQUEST_URI'] ?? '';
		if (preg_match('/\.(css|js|jpg|jpeg|png|gif|svg|woff2?|ttf|ico|map)(\?|$)/i', $uri)) return;

		$request_data = self::get_request_data();
		$rules = self::get_rules();

		foreach ($rules as $rule) {
			if (!$rule['enabled']) continue;

			foreach ($rule['targets'] as $target) {
				$input = $request_data[$target] ?? '';
				if (empty($input)) continue;

				if (preg_match($rule['pattern'], $input)) {
					SFS_Logger::log('waf_block', 'critical', [
						'rule_id'   => $rule['id'],
						'rule_name' => $rule['name'],
						'target'    => $target,
						'uri'       => $uri,
					]);

					http_response_code(403);
					wp_die(
						__('This request has been blocked by the firewall.', 'sf-security'),
						__('Blocked', 'sf-security'),
						['response' => 403]
					);
				}
			}
		}
	}

	private static function get_request_data() {
		static $data = null;
		if ($data !== null) return $data;

		$data = [
			'uri'          => $_SERVER['REQUEST_URI'] ?? '',
			'query_string' => $_SERVER['QUERY_STRING'] ?? '',
			'post_body'    => file_get_contents('php://input') ?: '',
			'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? '',
		];
		return $data;
	}

	private static function get_rules() {
		static $rules = null;
		if ($rules !== null) return $rules;

		$rules = [
			// SQL Injection
			[
				'id' => 'sqli-001', 'name' => 'SQL Injection — UNION SELECT', 'category' => 'sqli',
				'pattern' => '/union\s+(all\s+)?select/i',
				'targets' => ['query_string', 'post_body', 'uri'], 'enabled' => true,
			],
			[
				'id' => 'sqli-002', 'name' => 'SQL Injection — OR/AND boolean', 'category' => 'sqli',
				'pattern' => '/\b(or|and)\s+\d+\s*=\s*\d+/i',
				'targets' => ['query_string', 'post_body'], 'enabled' => true,
			],
			[
				'id' => 'sqli-003', 'name' => 'SQL Injection — DROP/ALTER/TRUNCATE', 'category' => 'sqli',
				'pattern' => '/\b(drop|alter|truncate)\s+(table|database)\b/i',
				'targets' => ['query_string', 'post_body'], 'enabled' => true,
			],
			[
				'id' => 'sqli-004', 'name' => 'SQL Injection — SLEEP/BENCHMARK', 'category' => 'sqli',
				'pattern' => '/\b(sleep|benchmark)\s*\(/i',
				'targets' => ['query_string', 'post_body'], 'enabled' => true,
			],
			[
				'id' => 'sqli-005', 'name' => 'SQL Injection — LOAD_FILE/INTO OUTFILE', 'category' => 'sqli',
				'pattern' => '/\b(load_file|into\s+outfile|into\s+dumpfile)\b/i',
				'targets' => ['query_string', 'post_body'], 'enabled' => true,
			],

			// XSS
			[
				'id' => 'xss-001', 'name' => 'XSS — Script tag', 'category' => 'xss',
				'pattern' => '/<script[\s>]/i',
				'targets' => ['query_string', 'post_body', 'uri'], 'enabled' => true,
			],
			[
				'id' => 'xss-002', 'name' => 'XSS — javascript: protocol', 'category' => 'xss',
				'pattern' => '/javascript\s*:/i',
				'targets' => ['query_string', 'post_body'], 'enabled' => true,
			],
			[
				'id' => 'xss-003', 'name' => 'XSS — Event handlers', 'category' => 'xss',
				'pattern' => '/\bon(error|load|mouseover|click|focus|blur|submit|change)\s*=/i',
				'targets' => ['query_string', 'post_body'], 'enabled' => true,
			],
			[
				'id' => 'xss-004', 'name' => 'XSS — iframe/object/embed', 'category' => 'xss',
				'pattern' => '/<(iframe|object|embed)[\s>]/i',
				'targets' => ['query_string', 'post_body'], 'enabled' => true,
			],

			// Path Traversal
			[
				'id' => 'trav-001', 'name' => 'Path Traversal — dot-dot-slash', 'category' => 'traversal',
				'pattern' => '/(\.\.[\/\\\\]){2,}/i',
				'targets' => ['uri', 'query_string'], 'enabled' => true,
			],
			[
				'id' => 'trav-002', 'name' => 'Path Traversal — etc/passwd', 'category' => 'traversal',
				'pattern' => '/(etc\/(passwd|shadow|hosts)|win\.ini|boot\.ini)/i',
				'targets' => ['uri', 'query_string'], 'enabled' => true,
			],

			// Remote Code Execution
			[
				'id' => 'rce-001', 'name' => 'RCE — eval/assert in request', 'category' => 'rce',
				'pattern' => '/\b(eval|assert)\s*\(/i',
				'targets' => ['query_string', 'post_body'], 'enabled' => true,
			],
			[
				'id' => 'rce-002', 'name' => 'RCE — system/exec/passthru', 'category' => 'rce',
				'pattern' => '/\b(system|exec|passthru|shell_exec|popen|proc_open)\s*\(/i',
				'targets' => ['query_string', 'post_body'], 'enabled' => true,
			],
			[
				'id' => 'rce-003', 'name' => 'RCE — PHP wrappers', 'category' => 'rce',
				'pattern' => '/php:\/\/(input|filter|data)/i',
				'targets' => ['uri', 'query_string'], 'enabled' => true,
			],
			[
				'id' => 'rce-004', 'name' => 'RCE — base64_decode execution', 'category' => 'rce',
				'pattern' => '/base64_decode\s*\(/i',
				'targets' => ['query_string', 'post_body'], 'enabled' => true,
			],

			// WordPress-Specific
			[
				'id' => 'wp-001', 'name' => 'WP — wp-config.php access', 'category' => 'wp',
				'pattern' => '/wp-config\.php/i',
				'targets' => ['uri'], 'enabled' => true,
			],
			[
				'id' => 'wp-002', 'name' => 'WP — debug.log access', 'category' => 'wp',
				'pattern' => '/debug\.log/i',
				'targets' => ['uri'], 'enabled' => true,
			],
			[
				'id' => 'wp-003', 'name' => 'WP — PHP in uploads', 'category' => 'wp',
				'pattern' => '/\/uploads\/.*\.ph(p|tml|ar)/i',
				'targets' => ['uri'], 'enabled' => true,
			],

			// Bad Bots
			[
				'id' => 'bot-001', 'name' => 'Bad Bot — Vulnerability scanners', 'category' => 'bot',
				'pattern' => '/(nikto|sqlmap|wpscan|acunetix|nessus|openvas|havij)/i',
				'targets' => ['user_agent'], 'enabled' => true,
			],
			[
				'id' => 'bot-002', 'name' => 'Bad Bot — Empty user agent', 'category' => 'bot',
				'pattern' => '/^$/i',
				'targets' => ['user_agent'], 'enabled' => false, // Disabled by default — can cause false positives
			],
		];

		return $rules;
	}

	public static function get_rules_public() {
		return self::get_rules();
	}
}
