# ShieldForge — Project Plan

*Created: June 2025*

---

## Table of Contents

1. [Overview](#overview)
2. [Forge Family Positioning](#forge-family-positioning)
3. [Target Users](#target-users)
4. [Competitive Landscape](#competitive-landscape)
5. [Architecture](#architecture)
6. [Feature Phases](#feature-phases)
7. [Database Schema](#database-schema)
8. [File Structure](#file-structure)
9. [Free vs Pro Split](#free-vs-pro-split)
10. [Build Order](#build-order)

---

## Overview

ShieldForge is a self-hosted WordPress security plugin that protects sites from brute force attacks, malware, and common web vulnerabilities — without sending your data to a third-party cloud service.

**Brand:** ShieldForge (Forge Product Family)
**Slug:** `shieldforge` (target), `sf-security` (working slug)
**Prefix:** `sfs_` for all functions, options, hooks, database tables
**Text Domain:** `sf-security`

**Design Philosophy:**
- Lightweight — no bloat, no upsell nags, no phoning home
- Self-contained — no Composer, no external API dependencies for core features
- WordPress-native — uses wp-cron, WP database, Settings API, standard hooks
- Privacy-first — all scanning and blocking happens locally on the server
- Forge UI — dark SaaS admin interface consistent with BackForge and DripForge

---

## Forge Family Positioning

| Product | Purpose | Status |
|---------|---------|--------|
| **BackForge** | WordPress backup to Amazon S3 | ✅ Complete |
| **LicenseForge** | Self-hosted plugin licensing & sales | ✅ Complete |
| **DripForge** | Self-hosted email drip sequences | ✅ Complete |
| **ShieldForge** | Self-hosted WordPress security | 📋 Planning |

ShieldForge completes the "run your WordPress business without SaaS dependencies" story. A site owner using the full Forge stack has backups, licensing, email marketing, and security — all self-hosted, all under one design language.

---

## Target Users

- WordPress site owners who want security without SaaS subscriptions
- Developers managing multiple client sites who need a lightweight, consistent security layer
- Privacy-conscious users who don't want security scan data sent to third-party servers
- Users on shared hosting who can't configure server-level firewalls

---

## Competitive Landscape

| Feature | Wordfence Free | Sucuri Free | iThemes Security | ShieldForge (Target) |
|---------|---------------|-------------|-------------------|---------------------|
| Web Application Firewall | ✅ (delayed rules) | ❌ (cloud only in Pro) | ❌ | ✅ |
| Brute Force Protection | ✅ | ✅ | ✅ | ✅ |
| Malware Scanner | ✅ | ✅ (limited) | ❌ | ✅ |
| File Integrity Monitoring | ✅ | ✅ | ✅ | ✅ |
| 2FA | ✅ | ❌ | ✅ (Pro) | ✅ |
| Login Hardening | ✅ | ✅ | ✅ | ✅ |
| IP Blocklist | ✅ (delayed) | ❌ | ❌ | ✅ |
| Country Blocking | ❌ (Pro) | ❌ (Pro) | ❌ (Pro) | ✅ (Free) |
| Activity Log | ✅ | ✅ | ✅ | ✅ |
| REST API Protection | Partial | ❌ | Partial | ✅ |
| No Cloud Dependency | ❌ | ❌ | ❌ | ✅ |
| Dark SaaS UI | ❌ | ❌ | ❌ | ✅ |
| Self-hosted | ✅ | Partial | ✅ | ✅ |
| Price (Pro) | $119/yr | $199/yr | $99/yr | $49/yr |

**ShieldForge differentiators:**
1. Country blocking in free (competitors gate it behind Pro)
2. Zero cloud dependency — everything runs locally
3. REST API protection built-in (critical for sites running LicenseForge, WooCommerce, etc.)
4. Forge UI consistency — same dark SaaS design as BackForge/DripForge
5. Lower Pro price point

---

## Architecture

### Core Principles
- **Early loading** — security checks must run as early as possible, ideally via `mu-plugin` dropin or `plugins_loaded` priority 1
- **Minimal DB queries** — hot path (every request) should check in-memory or transient-cached blocklists, not query the DB on every hit
- **Fail-closed** — if something goes wrong with the security check, block the request rather than allow it
- **Non-blocking logging** — log events asynchronously or batch-write to avoid slowing down requests
- **No SDK, no Composer** — WordPress-native, same as all Forge plugins

### Request Flow
```
Incoming Request
    │
    ▼
[mu-plugin dropin] ← Optional, for earliest possible blocking
    │
    ▼
[IP Blocklist Check] ← Transient-cached, no DB query on hit
    │ blocked → 403 + log
    ▼
[Rate Limiter] ← Transient-based sliding window
    │ exceeded → 429 + log + temp block
    ▼
[Country Check] ← If enabled, GeoIP lookup from local DB
    │ blocked → 403 + log
    ▼
[WAF Rules] ← Pattern matching on $_GET, $_POST, $_SERVER
    │ matched → 403 + log
    ▼
[WordPress loads normally]
    │
    ▼
[Login Hardening] ← hooks into authenticate, wp_login_failed
[REST API Protection] ← hooks into rest_authentication_errors
[File Monitor] ← wp-cron scheduled scan
[Malware Scanner] ← wp-cron scheduled scan
```

### Data Storage
- **Options API** — plugin settings, feature toggles
- **Custom Tables** — security log, IP blocklist, login attempts, file checksums
- **Transients** — cached blocklist for hot path, rate limit counters, GeoIP cache
- **Flat File** — mu-plugin dropin (optional), .htaccess rules

---

## Feature Phases

### Phase 1 — Login Hardening & Brute Force Protection
The foundation. Protects the most common attack vector.

**Features:**
- Login attempt tracking (IP, username, timestamp, success/fail)
- Auto-lockout after N failed attempts (configurable: default 5 in 15 minutes)
- Lockout duration escalation (5 min → 15 min → 1 hour → 24 hours)
- Permanent IP ban after N lockouts (configurable)
- Username enumeration prevention (block `?author=N` and REST user endpoint)
- Custom login error message (don't reveal whether username or password was wrong)
- XMLRPC disable toggle (most sites don't need it, it's a common attack vector)
- Admin notification on lockout (email to admin)
- Lockout whitelist (never lock out these IPs)

**Dashboard:**
- Recent login attempts (last 50)
- Currently locked out IPs
- Failed vs. successful login chart (last 7 days)
- Quick stats: total blocks today, active lockouts, unique IPs blocked

### Phase 2 — IP Blocklist & Rate Limiting
Block known bad actors and prevent abuse.

**Features:**
- Manual IP blocklist (single IP, CIDR range, wildcard)
- Manual IP allowlist (bypass all checks)
- Automatic blocklist (IPs that trigger lockouts get auto-added)
- Blocklist expiry (temporary blocks with TTL, permanent blocks)
- Rate limiter — sliding window per IP (configurable: default 60 req/min)
- Rate limit by endpoint (stricter limits on wp-login.php, xmlrpc.php, wp-admin/admin-ajax.php)
- Transient-cached hot path — blocklist loaded into transient on first check, refreshed on change
- Import/export blocklist (CSV)
- Bulk actions (block, unblock, extend)

### Phase 3 — Web Application Firewall (WAF)
Pattern-based request filtering.

**Features:**
- Rule engine — regex patterns matched against request URI, query string, POST body, headers, user agent
- Built-in ruleset covering:
  - SQL injection patterns (`UNION SELECT`, `OR 1=1`, `DROP TABLE`, etc.)
  - XSS patterns (`<script>`, `javascript:`, `onerror=`, etc.)
  - Path traversal (`../`, `..%2f`, `/etc/passwd`)
  - PHP injection (`eval(`, `base64_decode(`, `system(`, `exec(`)
  - WordPress-specific (`wp-config.php` access, debug.log access, readme.html probing)
  - Bad bot user agents (known scanners, scrapers)
- Rule severity levels (block, log-only, challenge)
- Custom rules — admin can add their own regex patterns
- Whitelist paths — exclude specific URLs from WAF (e.g., admin-ajax.php for known-safe AJAX)
- WAF log — every blocked request with full details (IP, URI, matched rule, timestamp)
- Learning mode — log but don't block for N days, then review before enabling

### Phase 4 — File Integrity Monitoring
Detect unauthorized file changes.

**Features:**
- Baseline scan — checksum all WordPress core files, plugin files, theme files
- Store checksums in custom table (path, SHA-256 hash, size, mtime, scan timestamp)
- Scheduled re-scan (daily via wp-cron)
- Compare against baseline — flag new, modified, and deleted files
- WordPress.org API verification — compare core file checksums against official WordPress checksums
- Ignore list — paths to skip (uploads, cache directories, known-changing files)
- Alert on change — email notification when files change outside of plugin/theme updates
- Dashboard widget — files changed since last scan, last scan time, next scan time
- One-click re-baseline after legitimate updates

### Phase 5 — Malware Scanner
Detect known malware patterns in files.

**Features:**
- Signature-based scanning — regex patterns for known PHP malware:
  - Obfuscated code (`eval(base64_decode(`, `str_rot13(`, `gzinflate(`)
  - Backdoor patterns (`$_GET['cmd']`, `shell_exec(`, `passthru(`)
  - Spam injection (`<iframe` in non-template files, hidden links)
  - Crypto miners (known JS miner patterns)
  - Mailer scripts (unauthorized `mail()` calls outside WordPress)
- Scan scope: wp-content (plugins, themes, uploads), WordPress root
- Skip binary files, images, known-safe large files
- Chunked scanning — process N files per cron run to avoid timeouts
- Quarantine — move suspicious files to a protected directory (not delete)
- False positive management — mark files as safe, exclude from future scans
- Scan report — summary with file path, matched pattern, severity, action taken

### Phase 6 — Two-Factor Authentication
Protect admin accounts even if passwords are compromised.

**Features:**
- TOTP (Time-based One-Time Password) — works with Google Authenticator, Authy, 1Password
- QR code setup flow in user profile
- Backup codes (10 single-use codes generated on setup)
- Per-role enforcement (require 2FA for administrators, optional for editors)
- Grace period — N days to set up 2FA after enforcement is enabled
- Remember device — optional "trust this device for 30 days" checkbox
- Recovery — admin can reset another user's 2FA

### Phase 7 — Country Blocking (GeoIP)
Block or allow traffic by country.

**Features:**
- MaxMind GeoLite2 database (free, user provides their own license key for download)
- Block mode or allow mode (block specific countries, or allow only specific countries)
- Apply to: entire site, wp-login.php only, wp-admin only, REST API only
- Bypass for logged-in users
- Country displayed in security log entries
- Auto-update GeoIP database monthly via wp-cron
- Fallback behavior when GeoIP lookup fails (allow or block, configurable)

### Phase 8 — Activity Log & REST API Protection
Complete audit trail and API hardening.

**Features:**
- Log all security-relevant events:
  - Login success/failure
  - User created/deleted/role changed
  - Plugin installed/activated/deactivated/deleted
  - Theme switched
  - Options changed (specific sensitive options)
  - File editor used
  - Post/page published/deleted
  - Export triggered
- REST API protection:
  - Disable REST API for unauthenticated users (toggle)
  - Whitelist specific REST routes (e.g., LicenseForge validation endpoints)
  - Rate limit REST API separately from frontend
  - Block user enumeration via `/wp-json/wp/v2/users`
- Log retention — auto-purge after N days (configurable, default 90)
- Log export (CSV)
- Log search and filter (by event type, user, IP, date range)

---

## Database Schema

```sql
-- Security event log
CREATE TABLE {prefix}sfs_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(50) NOT NULL,       -- login_failed, login_success, blocked_ip, waf_block, etc.
    severity ENUM('info','warning','critical') DEFAULT 'info',
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(500) DEFAULT '',
    username VARCHAR(100) DEFAULT '',
    user_id BIGINT UNSIGNED DEFAULT 0,
    details TEXT DEFAULT '',               -- JSON: matched rule, URI, etc.
    country_code CHAR(2) DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY event_type (event_type),
    KEY ip_address (ip_address),
    KEY created_at (created_at),
    KEY severity (severity)
);

-- IP blocklist
CREATE TABLE {prefix}sfs_blocklist (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,       -- single IP or CIDR
    list_type ENUM('block','allow') DEFAULT 'block',
    reason VARCHAR(255) DEFAULT '',
    source ENUM('manual','auto','import') DEFAULT 'manual',
    expires_at DATETIME DEFAULT NULL,      -- NULL = permanent
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY ip_list (ip_address, list_type),
    KEY list_type (list_type),
    KEY expires_at (expires_at)
);

-- Login attempts (for lockout tracking)
CREATE TABLE {prefix}sfs_login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    username VARCHAR(100) NOT NULL,
    success TINYINT(1) DEFAULT 0,
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    KEY ip_address (ip_address),
    KEY attempted_at (attempted_at)
);

-- Lockouts (active and historical)
CREATE TABLE {prefix}sfs_lockouts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    reason VARCHAR(255) DEFAULT '',
    lockout_count INT UNSIGNED DEFAULT 1,  -- escalation counter
    locked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    active TINYINT(1) DEFAULT 1,
    KEY ip_address (ip_address),
    KEY active (active),
    KEY expires_at (expires_at)
);

-- File checksums (integrity monitoring)
CREATE TABLE {prefix}sfs_file_checksums (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    file_path VARCHAR(500) NOT NULL,
    file_hash CHAR(64) NOT NULL,           -- SHA-256
    file_size BIGINT UNSIGNED DEFAULT 0,
    file_mtime INT UNSIGNED DEFAULT 0,
    scan_group VARCHAR(20) NOT NULL,       -- 'baseline' or scan timestamp
    scanned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY path_group (file_path(255), scan_group),
    KEY scan_group (scan_group)
);

-- 2FA secrets
CREATE TABLE {prefix}sfs_two_factor (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    secret VARCHAR(64) NOT NULL,           -- encrypted TOTP secret
    backup_codes TEXT DEFAULT '',           -- JSON array, encrypted
    enabled TINYINT(1) DEFAULT 0,
    enabled_at DATETIME DEFAULT NULL,
    last_used_at DATETIME DEFAULT NULL
);
```

---

## File Structure

```
sf-security/
├── sf-security.php                    ← Entry point, plugin header, bootstrap
├── uninstall.php                      ← Clean removal (drop tables, delete options)
├── readme.txt                         ← WordPress.org format
├── includes/
│   ├── class-sfs-plugin.php           ← Activation, menus, cron, mu-plugin dropin
│   ├── class-sfs-settings.php         ← Settings page, AJAX handlers
│   ├── class-sfs-logger.php           ← Security event logging
│   ├── class-sfs-login.php            ← Login hardening, brute force, lockouts
│   ├── class-sfs-blocklist.php        ← IP block/allow list management
│   ├── class-sfs-rate-limiter.php     ← Request rate limiting
│   ├── class-sfs-firewall.php         ← WAF rule engine
│   ├── class-sfs-file-monitor.php     ← File integrity monitoring
│   ├── class-sfs-scanner.php          ← Malware scanner
│   ├── class-sfs-two-factor.php       ← TOTP 2FA
│   ├── class-sfs-geoip.php           ← Country blocking / GeoIP
│   └── class-sfs-activity.php         ← Activity log + REST API protection
├── admin/
│   ├── views/
│   │   ├── dashboard.php              ← Security overview
│   │   ├── firewall.php               ← WAF rules + log
│   │   ├── blocklist.php              ← IP management
│   │   ├── scanner.php                ← Malware scan results
│   │   ├── file-monitor.php           ← File integrity results
│   │   ├── activity-log.php           ← Full event log
│   │   ├── login-security.php         ← Login hardening settings
│   │   ├── two-factor.php             ← 2FA management
│   │   └── settings.php               ← General settings
│   ├── css/admin.css                  ← Dark SaaS UI (Forge design system)
│   └── js/admin.js                    ← Dashboard charts, AJAX, real-time log
├── data/
│   ├── waf-rules.php                  ← Built-in WAF ruleset (PHP array)
│   └── malware-signatures.php         ← Malware patterns (PHP array)
├── mu-plugin/
│   └── sfs-early-block.php            ← Optional mu-plugin for earliest blocking
└── languages/
    └── sf-security.pot
```

---

## Free vs Pro Split

### Free (ShieldForge)
- Login hardening + brute force protection
- IP blocklist (manual + auto)
- Rate limiting
- WAF with built-in ruleset
- File integrity monitoring
- Activity log (30-day retention)
- Username enumeration prevention
- XMLRPC disable
- REST API user endpoint blocking
- Country blocking (GeoIP) ← **differentiator: free, not Pro**
- Dashboard with security overview

### Pro (ShieldForge Pro)
- Two-factor authentication (TOTP)
- Malware scanner with quarantine
- Custom WAF rules
- WAF learning mode
- Extended activity log (unlimited retention)
- Activity log for content changes (posts, pages, options)
- Real-time security notifications (email + Slack/webhook)
- Scheduled security reports (weekly email digest)
- Multi-site support
- Priority support
- White-label option

### Pricing (via LicenseForge)
| Tier | Price | Sites |
|------|-------|-------|
| Personal | $39/yr | 1 |
| Professional | $79/yr | 5 |
| Agency | $149/yr | Unlimited |

---

## Build Order

### Phase 1 — MVP (Login Hardening + Blocklist + Dashboard)
Ship a useful plugin fast. This alone beats having nothing.

1. Plugin scaffold (entry point, activation, tables, settings)
2. Login attempt tracking + auto-lockout
3. IP blocklist (manual block/allow + auto-block on lockout)
4. Security log (login events, blocks)
5. Dashboard (stats, recent events, active lockouts)
6. Admin UI (Forge dark theme)
7. Settings page (lockout thresholds, email notifications)
8. XMLRPC disable toggle
9. Username enumeration prevention

### Phase 2 — WAF + Rate Limiting
Block attacks before they reach WordPress.

10. Rate limiter (transient-based sliding window)
11. WAF rule engine + built-in ruleset
12. WAF log page
13. mu-plugin dropin for early blocking (optional install)

### Phase 3 — File Integrity + Malware Scanner
Detect compromises.

14. File integrity baseline + scheduled scan
15. WordPress.org core checksum verification
16. Malware signature scanner (chunked, cron-based)
17. Quarantine system
18. Scan results page

### Phase 4 — Country Blocking + Activity Log
Complete the free feature set.

19. GeoIP integration (MaxMind GeoLite2)
20. Country block/allow rules
21. Activity log (user actions, plugin changes, etc.)
22. REST API protection toggles
23. Log search, filter, export

### Phase 5 — Pro Features
Monetize.

24. Two-factor authentication (TOTP)
25. Custom WAF rules editor
26. Email/Slack notifications
27. Weekly security digest
28. Extended log retention
29. License integration with LicenseForge

---

## Technical Notes

### Performance Considerations
- The hot path (every request) must be fast. Blocklist check should be a transient lookup, not a DB query.
- WAF pattern matching should use `preg_match()` with a combined regex (one match call, not N separate calls).
- File scanning must be chunked — process 500 files per cron run, resume on next run.
- Malware scanning must skip binary files and files over 2MB.
- Log table will grow fast — auto-purge is essential. Index on `created_at` for efficient cleanup.

### Security of the Security Plugin
- The plugin itself must follow all the same security patterns as other Forge plugins:
  - Nonce on every form
  - `manage_options` capability check on every admin action
  - `sanitize_text_field()` / `absint()` on all inputs
  - `esc_html()` / `esc_attr()` on all outputs
  - `ABSPATH` check on every file
- WAF rules file must not be user-editable in free (prevents attackers from disabling rules)
- 2FA secrets must be encrypted at rest (same pattern as BackForge credential encryption)
- mu-plugin dropin must be minimal and hardened — it runs before WordPress is fully loaded

### WordPress.org Compliance
- No external API calls in free version (GeoIP uses a local database file)
- No tracking or analytics
- GPL v2+ license
- `sfs_` prefix on everything
- All strings internationalized
- No `exec()`, `shell_exec()`, `system()` calls
