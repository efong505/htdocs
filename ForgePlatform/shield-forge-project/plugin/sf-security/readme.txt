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
