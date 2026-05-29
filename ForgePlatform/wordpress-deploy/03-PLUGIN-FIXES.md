# Plugin Fixes — Pre-Submission Corrections

*Issues identified during review that MUST be fixed before WordPress.org submission.*

---

## BackForge (wp-s3-backup)

### CRITICAL — Will Cause Rejection

#### 1. Remove `wps3b-debug.php`
**Issue:** Contains a hardcoded emergency reset key (`backforge2026`) accessible via URL. This is a security vulnerability and will be immediately flagged.

**Fix:** Delete `wps3b-debug.php` from the plugin directory entirely. This is a development tool, not for production/distribution.

**File:** `wp-s3-backup-project/plugin/wp-s3-backup/wps3b-debug.php`
**Action:** DELETE this file before creating submission ZIP.

---

#### 2. Update Plugin URI
**Issue:** Plugin URI points to GitHub (`https://github.com/ekewaka/wp-s3-backup`) instead of the product page.

**Fix:** Change to `https://ekewaka.com/backforge`

**File:** `wp-s3-backup-project/plugin/wp-s3-backup/wp-s3-backup.php`
**Line:** Plugin header `Plugin URI`

```php
// Before:
* Plugin URI:  https://github.com/ekewaka/wp-s3-backup

// After:
* Plugin URI:  https://ekewaka.com/backforge
```

---

#### 3. Author Name Consistency
**Issue:** Author is "Edward Fong" in BackForge but "Ekewaka" in the other plugins. WordPress.org ties plugins to your account — use consistent branding.

**Recommendation:** Use "Ekewaka" across all three for brand consistency (matches your WordPress.org username).

**File:** `wp-s3-backup-project/plugin/wp-s3-backup/wp-s3-backup.php`
```php
// Before:
* Author:      Edward Fong

// After:
* Author:      Ekewaka
```

---

### MODERATE — May Cause Issues

#### 4. `@set_time_limit()` Usage
**Issue:** Multiple uses of `@set_time_limit()` with error suppression. While not strictly forbidden, reviewers sometimes flag this.

**Fix:** Already wrapped in `if (function_exists('set_time_limit'))` which is acceptable. The `@` suppression is fine here since some hosts disable it. **No change needed.**

---

#### 5. `file_put_contents()` in Debug File
**Issue:** The debug file uses `file_put_contents()` to modify `wp-config.php`. This would be a major red flag.

**Fix:** Already resolved by removing `wps3b-debug.php` (fix #1 above).

---

### MINOR — Cleanup

#### 6. Remove `assets/ASSETS-GUIDE.md`
**Issue:** Development documentation file that shouldn't be in the distributed plugin.

**File:** `wp-s3-backup-project/plugin/wp-s3-backup/assets/ASSETS-GUIDE.md`
**Action:** DELETE before submission (or move to project docs).

---

## ShieldForge (sf-security)

### CRITICAL — Will Cause Rejection

#### 1. Missing `readme.txt`
**Issue:** No `readme.txt` file exists. This is REQUIRED for WordPress.org submission.

**Fix:** Create `readme.txt` (full content provided below in the Appendix section).

---

#### 2. Description Promises Unimplemented Features
**Issue:** Plugin header says "country blocking, and file integrity monitoring" — neither is implemented yet.

**Fix:** Update the description to only mention what's actually in v1.0.0.

**File:** `shield-forge-project/plugin/sf-security/sf-security.php`
```php
// Before:
* Description: Self-hosted WordPress security with firewall, brute force protection, country blocking, and file integrity monitoring — zero cloud dependency.

// After:
* Description: Self-hosted WordPress security with login hardening, brute force protection, IP blocklist, rate limiting, and web application firewall — zero cloud dependency.
```

---

#### 3. Missing `LICENSE` File
**Issue:** No LICENSE file in the plugin directory.

**Fix:** Add a `LICENSE` file with GPL-2.0-or-later text.

---

#### 4. Missing `Domain Path` in Plugin Header
**Issue:** Plugin header is missing `Domain Path: /languages` which is needed for i18n.

**Fix:** Add to plugin header:
```php
* Domain Path: /languages
```

---

### MODERATE — May Cause Issues

#### 5. Menu Position Hardcoded to 3
**Issue:** `add_menu_page()` uses position `3` which conflicts with Dashboard. WordPress.org reviewers sometimes flag aggressive menu positioning.

**Fix:** Use a higher number or `null`:
```php
// Before:
'dashicons-shield', 3

// After:
'dashicons-shield', 80
```

Same issue exists in BackForge — both use position 3.

---

#### 6. `http_response_code()` Before `wp_die()`
**Issue:** Calling `http_response_code(403)` before `wp_die()` is redundant since `wp_die()` accepts a response code. Some reviewers flag this.

**Fix:** Remove the redundant `http_response_code()` calls:
```php
// Before:
http_response_code(403);
wp_die(__('Access denied.', 'sf-security'), __('Blocked', 'sf-security'), ['response' => 403]);

// After:
wp_die(__('Access denied.', 'sf-security'), __('Blocked', 'sf-security'), ['response' => 403]);
```

**Files:** `class-sfs-blocklist.php`, `class-sfs-firewall.php`

---

#### 7. `sleep(1)` Not Present But Worth Noting
**Issue:** No `sleep()` calls in ShieldForge (good). Just confirming this isn't an issue.

---

### MINOR — Cleanup

#### 8. Empty `data/` and `languages/` Directories
**Issue:** Empty directories are fine but should contain an `index.php` file to prevent directory listing.

**Fix:** Add `<?php // Silence is golden.` to:
- `data/index.php`
- `languages/index.php`

---

## DripForge (nl-drip-engine)

### CRITICAL — Will Cause Rejection

#### 1. Version Mismatch
**Issue:** `readme.txt` has `Stable tag: 1.1.1` but plugin header has `Version: 1.2.0`. These MUST match.

**Fix:** Update `readme.txt`:
```
Stable tag: 1.2.0
```

And add the 1.2.0 changelog entry to readme.txt.

---

#### 2. Remove `.psd` File
**Issue:** `assets/banner-772x250.psd` is a Photoshop source file. Should not be in the distributed plugin.

**File:** `drip-forge-project/plugin/nl-drip-engine/assets/banner-772x250.psd`
**Action:** DELETE before submission. PSD files go in your local design folder, not the plugin.

---

#### 3. Missing `uninstall.php`
**Issue:** No `uninstall.php` file exists. While not strictly required, WordPress.org strongly recommends it and reviewers may flag its absence for a plugin that creates database tables.

**Fix:** Create `uninstall.php`:
```php
<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

global $wpdb;

// Drop custom tables
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nlde_send_log");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nlde_subscriber_sequences");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nlde_sequence_emails");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nlde_sequences");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}nlde_subscribers");

// Delete options
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'nlde_%'");

// Delete transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_nlde_%' OR option_name LIKE '_transient_timeout_nlde_%'");
```

---

#### 4. Missing `LICENSE` File
**Issue:** No LICENSE file in the plugin directory.

**Fix:** Add GPL-2.0-or-later LICENSE file.

---

#### 5. Missing `Domain Path` in Plugin Header
**Issue:** Plugin header is missing `Domain Path: /languages`.

**Fix:**
```php
* Domain Path: /languages
* Requires at least: 5.0
* Requires PHP: 7.4
```

---

### MODERATE — May Cause Issues

#### 6. `error_log()` Usage
**Issue:** `class-cron.php` line 49 uses `error_log("DripForge: Sent {$sent_count} emails.")`. WordPress.org reviewers sometimes flag `error_log()` as it writes to server logs.

**Fix:** Remove or wrap in `WP_DEBUG` check:
```php
// Before:
error_log("DripForge: Sent {$sent_count} emails.");

// After:
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log("DripForge: Sent {$sent_count} emails.");
}
```

---

#### 7. `inject_plugin_icon()` Filter Manipulation
**Issue:** The `inject_plugin_icon()` method modifies the `site_transient_update_plugins` transient to inject custom icons. This is a known pattern but some reviewers may question it since it mimics update API behavior.

**Recommendation:** This is acceptable for self-hosted plugins not on WordPress.org. However, once the plugin IS on WordPress.org, this code becomes unnecessary — WordPress.org serves icons from the `/assets/` SVN directory automatically.

**Fix for submission:** Remove the `inject_plugin_icon()` method and its hooks, and remove the `plugin_info()` method (WordPress.org provides this automatically). Also remove `plugin_row_meta` "View details" link (WordPress.org adds this).

```php
// REMOVE these lines from __construct/init:
add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
add_filter('plugin_row_meta', [$this, 'plugin_row_meta'], 10, 2);
add_filter('site_transient_update_plugins', [$this, 'inject_plugin_icon']);
add_filter('transient_update_plugins', [$this, 'inject_plugin_icon']);
add_action('admin_enqueue_scripts', [$this, 'load_thickbox']);

// REMOVE these methods entirely:
// - load_thickbox()
// - inject_plugin_icon()
// - plugin_info()
// - plugin_row_meta()
```

---

#### 8. `sleep(1)` in Cron Processing
**Issue:** `class-cron.php` uses `sleep(1)` for rate limiting between email batches. Some reviewers flag `sleep()`.

**Fix:** This is acceptable for cron processing but add a comment explaining why:
```php
// Throttle: pause between batches to avoid overwhelming SMTP server
if ($sent_count % 10 === 0) {
    sleep(1);
}
```

---

### MINOR — Cleanup

#### 9. Add `index.php` to Empty Directories
Add `<?php // Silence is golden.` to any empty directories to prevent directory listing.

---

## Appendix: ShieldForge readme.txt

```
=== ShieldForge ===
Contributors: ekewaka
Tags: security, firewall, brute force, login protection, waf
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-hosted WordPress security with login hardening, brute force protection, IP blocklist, rate limiting, and web application firewall.

== Description ==

ShieldForge protects your WordPress site with enterprise-grade security features that run entirely on your own server — no cloud service required, no subscription fees. Part of the Forge Product Family.

**Features:**

* Brute force protection with escalating lockout durations
* Automatic permanent ban after repeated lockouts
* IP blocklist with CIDR range support
* IP allowlist to bypass all checks
* Rate limiting (general, login, XML-RPC endpoints)
* Web Application Firewall (WAF) with 21 built-in rules
* Per-rule WAF enable/disable toggles
* 4 WAF presets (Default, Strict, Minimal, Paranoid)
* Save and load custom WAF profiles
* XML-RPC disabled by default
* Login error messages hidden (prevents username enumeration)
* User enumeration blocking (author parameter + REST API)
* Email notifications on lockout events
* Activity log with severity filtering
* Automatic log purge (configurable retention)
* Security status dashboard

**WAF Protection Categories:**

* SQL Injection (UNION, boolean, DROP/ALTER, SLEEP, LOAD_FILE)
* Cross-Site Scripting (script tags, javascript: protocol, event handlers, iframe/object)
* Path Traversal (dot-dot-slash, sensitive file access)
* Remote Code Execution (eval, system/exec, PHP wrappers, base64_decode)
* WordPress-Specific (wp-config access, debug.log access, PHP in uploads)
* Bad Bots (vulnerability scanner detection)

== Installation ==

1. Upload the `sf-security` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to ShieldForge → Dashboard to see your security status
4. Review settings at ShieldForge → Settings
5. The plugin is active immediately with sensible defaults

== Frequently Asked Questions ==

= Will this lock me out of my own site? =

No. Logged-in administrators are always bypassed by all security checks. The plugin also supports an IP allowlist for additional safety.

= Does this work with Cloudflare? =

Yes. The plugin detects the real visitor IP from the CF-Connecting-IP header when Cloudflare is in use.

= Does this slow down my site? =

No. Security checks run early in the WordPress lifecycle and use transient caching for blocklist lookups. The performance impact is negligible.

= Can I disable specific WAF rules? =

Yes. Each of the 21 WAF rules can be individually toggled on or off. You can also use presets (Default, Strict, Minimal, Paranoid) or save your own custom profiles.

= What happens when I deactivate the plugin? =

All data (logs, blocklist, settings) is preserved. Data is only removed when you DELETE the plugin through the WordPress admin.

== Screenshots ==

1. Security dashboard with threat statistics and status checklist
2. WAF rules with per-rule toggles and preset selector
3. IP blocklist management with active lockouts
4. Activity log with severity filtering
5. Settings page with all configuration options

== Changelog ==

= 1.0.0 =
* Initial release
* Brute force protection with escalating lockouts
* IP blocklist and allowlist with CIDR support
* Rate limiting (general, login, XML-RPC)
* Web Application Firewall with 21 rules
* Per-rule WAF toggles and presets
* Custom WAF profile save/load
* XML-RPC disable
* Login error hiding
* User enumeration blocking
* Email lockout notifications
* Activity log with auto-purge

== Upgrade Notice ==

= 1.0.0 =
Initial release.
```

---

## Summary of Actions Required

| Plugin | Action | Priority |
|--------|--------|----------|
| BackForge | Delete `wps3b-debug.php` | CRITICAL |
| BackForge | Update Plugin URI to ekewaka.com/backforge | CRITICAL |
| BackForge | Change Author to "Ekewaka" | Moderate |
| BackForge | Delete `assets/ASSETS-GUIDE.md` | Minor |
| ShieldForge | Create `readme.txt` | CRITICAL |
| ShieldForge | Fix description (remove unimplemented features) | CRITICAL |
| ShieldForge | Add `LICENSE` file | CRITICAL |
| ShieldForge | Add `Domain Path` to header | CRITICAL |
| ShieldForge | Change menu position from 3 to 80 | Moderate |
| ShieldForge | Remove redundant `http_response_code()` | Moderate |
| ShieldForge | Add `index.php` to empty dirs | Minor |
| DripForge | Fix version mismatch (readme Stable tag → 1.2.0) | CRITICAL |
| DripForge | Delete `.psd` file from assets | CRITICAL |
| DripForge | Create `uninstall.php` | CRITICAL |
| DripForge | Add `LICENSE` file | CRITICAL |
| DripForge | Add `Domain Path` to header | CRITICAL |
| DripForge | Remove `inject_plugin_icon` / `plugin_info` methods | Moderate |
| DripForge | Wrap `error_log()` in WP_DEBUG check | Moderate |
| DripForge | Add `index.php` to empty dirs | Minor |
