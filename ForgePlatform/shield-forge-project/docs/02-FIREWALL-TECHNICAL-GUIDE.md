# ShieldForge — Firewall & WAF Technical Guide

*Created: June 2025*

---

## Table of Contents

1. [WAF Architecture](#waf-architecture)
2. [Rule Format](#rule-format)
3. [Built-in Ruleset](#built-in-ruleset)
4. [Request Inspection Points](#request-inspection-points)
5. [Performance Strategy](#performance-strategy)
6. [mu-Plugin Early Blocking](#mu-plugin-early-blocking)
7. [Rate Limiter Design](#rate-limiter-design)
8. [Bypass & Whitelist Logic](#bypass--whitelist-logic)

---

## WAF Architecture

The WAF operates as a pattern-matching engine that inspects incoming HTTP requests against a set of rules. Each rule defines a pattern (regex), a target (what part of the request to inspect), a severity, and an action (block or log).

### Execution Order
```
1. IP Blocklist (transient cache) → 403 if blocked
2. Rate Limiter (transient counter) → 429 if exceeded
3. Country Check (GeoIP) → 403 if blocked country
4. WAF Rules (pattern match) → 403 if matched
5. Request proceeds to WordPress
```

Each layer is independent. A request must pass all layers to reach WordPress.

---

## Rule Format

Rules are stored as a PHP array in `data/waf-rules.php`. Each rule:

```php
[
    'id'       => 'sqli-001',
    'name'     => 'SQL Injection — UNION SELECT',
    'category' => 'sqli',
    'pattern'  => '/union\s+(all\s+)?select/i',
    'targets'  => ['query_string', 'post_body', 'uri'],
    'action'   => 'block',        // block | log
    'severity' => 'critical',     // critical | high | medium | low
    'enabled'  => true,
]
```

### Fields

| Field | Type | Description |
|-------|------|-------------|
| `id` | string | Unique rule identifier |
| `name` | string | Human-readable description |
| `category` | string | Group: sqli, xss, traversal, rce, wp-specific, bot |
| `pattern` | string | PCRE regex pattern |
| `targets` | array | What to inspect (see Request Inspection Points) |
| `action` | string | `block` (403 + log) or `log` (log only, for learning mode) |
| `severity` | string | For dashboard display and notification filtering |
| `enabled` | bool | Toggle individual rules on/off |

---

## Built-in Ruleset

### SQL Injection (sqli)
```
sqli-001  UNION SELECT
sqli-002  OR 1=1 / AND 1=1 variations
sqli-003  DROP TABLE / DROP DATABASE
sqli-004  INSERT INTO / UPDATE SET with suspicious patterns
sqli-005  CONCAT() / CHAR() obfuscation
sqli-006  Hex-encoded SQL keywords (0x)
sqli-007  Comment-based bypass (/**/,  --)
sqli-008  SLEEP() / BENCHMARK() (timing attacks)
sqli-009  LOAD_FILE() / INTO OUTFILE
sqli-010  Information_schema access
```

### Cross-Site Scripting (xss)
```
xss-001   <script> tags
xss-002   javascript: protocol in attributes
xss-003   Event handlers (onerror=, onload=, onmouseover=, etc.)
xss-004   <iframe>, <object>, <embed> tags
xss-005   data: URI with script content
xss-006   SVG with embedded script
xss-007   Expression() in CSS (IE)
xss-008   document.cookie access
xss-009   eval() in inline script
```

### Path Traversal (traversal)
```
trav-001  ../ and ..\ sequences
trav-002  URL-encoded traversal (%2e%2e%2f)
trav-003  Double-encoded traversal
trav-004  /etc/passwd, /etc/shadow access
trav-005  Windows path traversal (C:\, cmd.exe)
trav-006  Null byte injection (%00)
```

### Remote Code Execution (rce)
```
rce-001   eval() / assert() in request
rce-002   base64_decode() in request
rce-003   system() / exec() / passthru() / shell_exec()
rce-004   proc_open() / popen()
rce-005   PHP wrapper (php://input, php://filter)
rce-006   Backtick execution
rce-007   preg_replace with /e modifier
```

### WordPress-Specific (wp)
```
wp-001    wp-config.php direct access
wp-002    debug.log access
wp-003    readme.html / license.txt probing (version fingerprinting)
wp-004    wp-includes direct PHP execution
wp-005    Uploads directory PHP execution
wp-006    TimThumb vulnerability patterns
wp-007    Revolution Slider exploit patterns
wp-008    xmlrpc.php pingback abuse
wp-009    wp-cron.php abuse (DDoS amplification)
wp-010    REST API user enumeration (/wp-json/wp/v2/users)
```

### Bad Bots (bot)
```
bot-001   Known vulnerability scanners (Nikto, sqlmap, WPScan, Acunetix)
bot-002   Empty user agent
bot-003   Fake Googlebot (claims Google UA but IP doesn't match Google ranges)
bot-004   Known spam bot user agents
bot-005   Aggressive crawlers (> 100 req/min from single IP)
```

---

## Request Inspection Points

| Target | Source | Notes |
|--------|--------|-------|
| `uri` | `$_SERVER['REQUEST_URI']` | Full URI including query string |
| `query_string` | `$_SERVER['QUERY_STRING']` | Just the query parameters |
| `post_body` | `file_get_contents('php://input')` | POST data (read once, cache) |
| `user_agent` | `$_SERVER['HTTP_USER_AGENT']` | Browser/bot identifier |
| `referer` | `$_SERVER['HTTP_REFERER']` | Referring page |
| `cookies` | `$_SERVER['HTTP_COOKIE']` | Cookie header |
| `headers` | Selected `$_SERVER['HTTP_*']` | Custom headers |
| `request_method` | `$_SERVER['REQUEST_METHOD']` | GET, POST, PUT, DELETE |
| `filename` | Parsed from URI | For file-access rules |

### Implementation
```php
public static function get_request_data() {
    static $data = null;
    if ($data !== null) return $data;

    $data = [
        'uri'          => $_SERVER['REQUEST_URI'] ?? '',
        'query_string' => $_SERVER['QUERY_STRING'] ?? '',
        'post_body'    => file_get_contents('php://input'),
        'user_agent'   => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'referer'      => $_SERVER['HTTP_REFERER'] ?? '',
        'ip'           => self::get_client_ip(),
        'method'       => $_SERVER['REQUEST_METHOD'] ?? 'GET',
    ];
    return $data;
}
```

The request data is read once and cached in a static variable. This avoids reading `php://input` multiple times (it can only be read once in some configurations).

---

## Performance Strategy

### Combined Regex
Instead of running N separate `preg_match()` calls (one per rule), combine rules by category into a single alternation pattern:

```php
// Instead of:
foreach ($rules as $rule) {
    if (preg_match($rule['pattern'], $input)) { ... }
}

// Do:
$combined = '/(' . implode('|', $patterns_for_category) . ')/i';
if (preg_match($combined, $input, $matches)) {
    // Determine which specific rule matched
}
```

This reduces the number of regex engine invocations from ~50 to ~6 (one per category).

### Transient-Cached Blocklist
```php
public static function is_blocked($ip) {
    $blocklist = get_transient('sfs_blocklist');
    if ($blocklist === false) {
        $blocklist = self::load_blocklist_from_db();
        set_transient('sfs_blocklist', $blocklist, 300); // 5 min cache
    }
    return isset($blocklist[$ip]);
}
```

The blocklist transient is invalidated whenever the blocklist is modified (add/remove/expire).

### Skip Known-Safe Requests
Don't run WAF on:
- Requests from allowlisted IPs
- Logged-in admin users (optional, configurable)
- Static file requests (.css, .js, .jpg, .png, .gif, .svg, .woff2)
- WordPress cron requests (wp-cron.php from localhost)

---

## mu-Plugin Early Blocking

For maximum protection, ShieldForge can install an optional mu-plugin that runs before any other plugin loads.

### How It Works
- ShieldForge writes `wp-content/mu-plugins/sfs-early-block.php` on activation (if user opts in)
- The mu-plugin is minimal: reads the transient blocklist and blocks IPs before WordPress fully loads
- It does NOT contain WAF rules (too complex for mu-plugin, and rules need the full plugin loaded)
- It only handles: IP blocklist check + rate limit check

### mu-Plugin Code (minimal)
```php
<?php
// ShieldForge Early Block — do not edit, managed by ShieldForge plugin
if (!defined('ABSPATH')) exit;

$ip = $_SERVER['HTTP_CF_CONNECTING_IP']
    ?? explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '')[0]
    ?? $_SERVER['REMOTE_ADDR']
    ?? '';
$ip = trim($ip);

// Check blocklist from options (transients aren't available this early)
$blocked = get_option('sfs_blocked_ips_cache', []);
if (isset($blocked[$ip])) {
    http_response_code(403);
    exit('Access denied.');
}
```

### Installation/Removal
- Installed via a toggle in ShieldForge settings: "Enable early blocking (mu-plugin)"
- Removed on plugin deactivation or when toggle is turned off
- File is overwritten on each plugin update to ensure it stays current
- If the mu-plugin file is manually deleted, ShieldForge continues to work — just without early blocking

---

## Rate Limiter Design

### Sliding Window Algorithm
Uses WordPress transients with IP-based keys.

```php
public static function check_rate_limit($ip, $limit = 60, $window = 60) {
    $key = 'sfs_rate_' . md5($ip);
    $data = get_transient($key);

    if ($data === false) {
        set_transient($key, ['count' => 1, 'start' => time()], $window);
        return true; // allowed
    }

    if (time() - $data['start'] > $window) {
        // Window expired, reset
        set_transient($key, ['count' => 1, 'start' => time()], $window);
        return true;
    }

    $data['count']++;
    set_transient($key, $data, $window - (time() - $data['start']));

    if ($data['count'] > $limit) {
        return false; // rate limited
    }

    return true;
}
```

### Per-Endpoint Limits
| Endpoint | Default Limit | Window |
|----------|--------------|--------|
| General | 120 req/min | 60s |
| wp-login.php | 10 req/min | 60s |
| xmlrpc.php | 5 req/min | 60s |
| wp-json/* | 60 req/min | 60s |
| admin-ajax.php | 120 req/min | 60s |

Limits are configurable in settings.

---

## Bypass & Whitelist Logic

### Order of Precedence
```
1. Allowlisted IP → skip ALL checks (blocklist, rate limit, WAF, country)
2. Logged-in admin → skip WAF (optional, configurable)
3. Whitelisted path → skip WAF for that specific URL
4. WordPress cron → skip rate limit
5. Static files → skip WAF
```

### Allowlist Storage
```php
// Stored in options, cached in transient
$allowlist = get_option('sfs_allowlist', []);
// Format: ['192.168.1.1' => true, '10.0.0.0/8' => true]
```

### CIDR Matching
For IP ranges in the blocklist/allowlist:
```php
public static function ip_in_cidr($ip, $cidr) {
    list($subnet, $mask) = explode('/', $cidr);
    return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
}
```
