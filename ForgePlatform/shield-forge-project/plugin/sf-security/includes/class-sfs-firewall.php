<?php
if (!defined('ABSPATH')) exit;

class SFS_Firewall {

	public static function init() {
		if (get_option('sfs_waf_enabled', '1') !== '1') return;
		add_action('init', [__CLASS__, 'inspect_request'], 3);
		add_action('wp_ajax_sfs_waf_toggle_rule', [__CLASS__, 'ajax_toggle_rule']);
		add_action('wp_ajax_sfs_waf_load_preset', [__CLASS__, 'ajax_load_preset']);
		add_action('wp_ajax_sfs_waf_save_profile', [__CLASS__, 'ajax_save_profile']);
		add_action('wp_ajax_sfs_waf_delete_profile', [__CLASS__, 'ajax_delete_profile']);
	}

	public static function inspect_request() {
		if (is_admin() && current_user_can('manage_options')) return;
		if (defined('DOING_CRON') && DOING_CRON) return;

		$ip = SFS_Plugin::get_client_ip();
		if (SFS_Blocklist::is_allowed($ip)) return;

		$uri = $_SERVER['REQUEST_URI'] ?? '';
		if (preg_match('/\.(css|js|jpg|jpeg|png|gif|svg|woff2?|ttf|ico|map)(\?|$)/i', $uri)) return;

		$request_data = self::get_request_data();
		$rules = self::get_active_rules();

		foreach ($rules as $rule) {
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

	// --- Rule Management ---

	public static function get_active_rules() {
		$rules = self::get_base_rules();
		$overrides = get_option('sfs_waf_rule_overrides', []);

		foreach ($rules as &$rule) {
			if (isset($overrides[$rule['id']])) {
				$rule['enabled'] = (bool) $overrides[$rule['id']];
			}
		}

		return array_filter($rules, fn($r) => $r['enabled']);
	}

	public static function get_rules_with_state() {
		$rules = self::get_base_rules();
		$overrides = get_option('sfs_waf_rule_overrides', []);

		foreach ($rules as &$rule) {
			$rule['default'] = $rule['enabled'];
			if (isset($overrides[$rule['id']])) {
				$rule['enabled'] = (bool) $overrides[$rule['id']];
			}
		}

		return $rules;
	}

	public static function toggle_rule($rule_id, $enabled) {
		$overrides = get_option('sfs_waf_rule_overrides', []);
		$overrides[$rule_id] = (bool) $enabled;
		update_option('sfs_waf_rule_overrides', $overrides);
	}

	// --- Presets ---

	public static function get_presets() {
		return [
			'default' => [
				'name'        => 'Default',
				'description' => 'Recommended settings. All rules enabled except empty user agent.',
				'overrides'   => [], // Empty = use base defaults
			],
			'strict' => [
				'name'        => 'Strict',
				'description' => 'All rules enabled including empty user agent blocking.',
				'overrides'   => ['bot-002' => true],
			],
			'minimal' => [
				'name'        => 'Minimal',
				'description' => 'Only critical rules: SQLi, RCE, and WP-specific. Good for troubleshooting.',
				'overrides'   => [
					'xss-001' => false, 'xss-002' => false, 'xss-003' => false, 'xss-004' => false,
					'trav-001' => false, 'trav-002' => false,
					'bot-001' => false, 'bot-002' => false,
				],
			],
			'paranoid' => [
				'name'        => 'Paranoid',
				'description' => 'Maximum protection. All rules enabled. May cause false positives.',
				'overrides'   => ['bot-002' => true],
			],
		];
	}

	public static function apply_preset($preset_key) {
		$presets = self::get_presets();
		if (!isset($presets[$preset_key])) return false;

		if ($preset_key === 'default') {
			delete_option('sfs_waf_rule_overrides');
		} else {
			update_option('sfs_waf_rule_overrides', $presets[$preset_key]['overrides']);
		}
		return true;
	}

	// --- Custom Profiles ---

	public static function get_saved_profiles() {
		return get_option('sfs_waf_profiles', []);
	}

	public static function save_profile($name) {
		$profiles = self::get_saved_profiles();
		$overrides = get_option('sfs_waf_rule_overrides', []);
		$slug = sanitize_title($name);

		$profiles[$slug] = [
			'name'      => sanitize_text_field($name),
			'overrides' => $overrides,
			'saved_at'  => current_time('mysql'),
		];

		update_option('sfs_waf_profiles', $profiles);
		return $slug;
	}

	public static function load_profile($slug) {
		$profiles = self::get_saved_profiles();
		if (!isset($profiles[$slug])) return false;

		update_option('sfs_waf_rule_overrides', $profiles[$slug]['overrides']);
		return true;
	}

	public static function delete_profile($slug) {
		$profiles = self::get_saved_profiles();
		unset($profiles[$slug]);
		update_option('sfs_waf_profiles', $profiles);
	}

	// --- AJAX Handlers ---

	public static function ajax_toggle_rule() {
		check_ajax_referer('sfs_admin', 'nonce');
		if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

		$rule_id = sanitize_key($_POST['rule_id'] ?? '');
		$enabled = ($_POST['enabled'] ?? '0') === '1';

		if (empty($rule_id)) wp_send_json_error('Missing rule ID.');

		self::toggle_rule($rule_id, $enabled);
		wp_send_json_success(['rule_id' => $rule_id, 'enabled' => $enabled]);
	}

	public static function ajax_load_preset() {
		check_ajax_referer('sfs_admin', 'nonce');
		if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

		$preset = sanitize_key($_POST['preset'] ?? '');

		// Check if it's a saved profile
		$profiles = self::get_saved_profiles();
		if (isset($profiles[$preset])) {
			self::load_profile($preset);
			wp_send_json_success(['loaded' => $preset, 'type' => 'profile']);
		}

		// Otherwise try built-in presets
		if (self::apply_preset($preset)) {
			wp_send_json_success(['loaded' => $preset, 'type' => 'preset']);
		}

		wp_send_json_error('Invalid preset.');
	}

	public static function ajax_save_profile() {
		check_ajax_referer('sfs_admin', 'nonce');
		if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

		$name = sanitize_text_field($_POST['profile_name'] ?? '');
		if (empty($name)) wp_send_json_error('Profile name required.');

		$slug = self::save_profile($name);
		wp_send_json_success(['slug' => $slug, 'name' => $name]);
	}

	public static function ajax_delete_profile() {
		check_ajax_referer('sfs_admin', 'nonce');
		if (!current_user_can('manage_options')) wp_send_json_error('Unauthorized');

		$slug = sanitize_key($_POST['profile_slug'] ?? '');
		if (empty($slug)) wp_send_json_error('Missing profile.');

		self::delete_profile($slug);
		wp_send_json_success();
	}

	// --- Internals ---

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

	private static function get_base_rules() {
		return [
			['id' => 'sqli-001', 'name' => 'SQL Injection — UNION SELECT', 'category' => 'sqli', 'pattern' => '/union\s+(all\s+)?select/i', 'targets' => ['query_string', 'post_body', 'uri'], 'enabled' => true],
			['id' => 'sqli-002', 'name' => 'SQL Injection — OR/AND boolean', 'category' => 'sqli', 'pattern' => '/\b(or|and)\s+\d+\s*=\s*\d+/i', 'targets' => ['query_string', 'post_body'], 'enabled' => true],
			['id' => 'sqli-003', 'name' => 'SQL Injection — DROP/ALTER/TRUNCATE', 'category' => 'sqli', 'pattern' => '/\b(drop|alter|truncate)\s+(table|database)\b/i', 'targets' => ['query_string', 'post_body'], 'enabled' => true],
			['id' => 'sqli-004', 'name' => 'SQL Injection — SLEEP/BENCHMARK', 'category' => 'sqli', 'pattern' => '/\b(sleep|benchmark)\s*\(/i', 'targets' => ['query_string', 'post_body'], 'enabled' => true],
			['id' => 'sqli-005', 'name' => 'SQL Injection — LOAD_FILE/INTO OUTFILE', 'category' => 'sqli', 'pattern' => '/\b(load_file|into\s+outfile|into\s+dumpfile)\b/i', 'targets' => ['query_string', 'post_body'], 'enabled' => true],
			['id' => 'xss-001', 'name' => 'XSS — Script tag', 'category' => 'xss', 'pattern' => '/<script[\s>]/i', 'targets' => ['query_string', 'post_body', 'uri'], 'enabled' => true],
			['id' => 'xss-002', 'name' => 'XSS — javascript: protocol', 'category' => 'xss', 'pattern' => '/javascript\s*:/i', 'targets' => ['query_string', 'post_body'], 'enabled' => true],
			['id' => 'xss-003', 'name' => 'XSS — Event handlers', 'category' => 'xss', 'pattern' => '/\bon(error|load|mouseover|click|focus|blur|submit|change)\s*=/i', 'targets' => ['query_string', 'post_body'], 'enabled' => true],
			['id' => 'xss-004', 'name' => 'XSS — iframe/object/embed', 'category' => 'xss', 'pattern' => '/<(iframe|object|embed)[\s>]/i', 'targets' => ['query_string', 'post_body'], 'enabled' => true],
			['id' => 'trav-001', 'name' => 'Path Traversal — dot-dot-slash', 'category' => 'traversal', 'pattern' => '/(\.\.[\/\\\\]){2,}/i', 'targets' => ['uri', 'query_string'], 'enabled' => true],
			['id' => 'trav-002', 'name' => 'Path Traversal — etc/passwd', 'category' => 'traversal', 'pattern' => '/(etc\/(passwd|shadow|hosts)|win\.ini|boot\.ini)/i', 'targets' => ['uri', 'query_string'], 'enabled' => true],
			['id' => 'rce-001', 'name' => 'RCE — eval/assert in request', 'category' => 'rce', 'pattern' => '/\b(eval|assert)\s*\(/i', 'targets' => ['query_string', 'post_body'], 'enabled' => true],
			['id' => 'rce-002', 'name' => 'RCE — system/exec/passthru', 'category' => 'rce', 'pattern' => '/\b(system|exec|passthru|shell_exec|popen|proc_open)\s*\(/i', 'targets' => ['query_string', 'post_body'], 'enabled' => true],
			['id' => 'rce-003', 'name' => 'RCE — PHP wrappers', 'category' => 'rce', 'pattern' => '/php:\/\/(input|filter|data)/i', 'targets' => ['uri', 'query_string'], 'enabled' => true],
			['id' => 'rce-004', 'name' => 'RCE — base64_decode execution', 'category' => 'rce', 'pattern' => '/base64_decode\s*\(/i', 'targets' => ['query_string', 'post_body'], 'enabled' => true],
			['id' => 'wp-001', 'name' => 'WP — wp-config.php access', 'category' => 'wp', 'pattern' => '/wp-config\.php/i', 'targets' => ['uri'], 'enabled' => true],
			['id' => 'wp-002', 'name' => 'WP — debug.log access', 'category' => 'wp', 'pattern' => '/debug\.log/i', 'targets' => ['uri'], 'enabled' => true],
			['id' => 'wp-003', 'name' => 'WP — PHP in uploads', 'category' => 'wp', 'pattern' => '/\/uploads\/.*\.ph(p|tml|ar)/i', 'targets' => ['uri'], 'enabled' => true],
			['id' => 'bot-001', 'name' => 'Bad Bot — Vulnerability scanners', 'category' => 'bot', 'pattern' => '/(nikto|sqlmap|wpscan|acunetix|nessus|openvas|havij)/i', 'targets' => ['user_agent'], 'enabled' => true],
			['id' => 'bot-002', 'name' => 'Bad Bot — Empty user agent', 'category' => 'bot', 'pattern' => '/^$/i', 'targets' => ['user_agent'], 'enabled' => false],
		];
	}

	public static function get_rules_public() {
		return self::get_rules_with_state();
	}
}
