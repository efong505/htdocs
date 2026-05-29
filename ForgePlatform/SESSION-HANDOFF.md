# Session Handoff — Forge Ecosystem Work

*Created: June 2025*

---

## Session Summary

This session covered work across multiple Forge ecosystem products: DripForge rebrand, BackForge v2.0 migration spec, ShieldForge full build (Phase 1-3), and memory bank/doc updates. All changes are committed and pushed to `github.com/efong505/htdocs.git` on `main`.

---

## 1. DripForge (NL Drip Engine) — Rebrand + Features

### What Was Done

**Rebrand (v1.1.0):**

- Verified both deployed copies (next-level + julias-graphic-design) were identical
- Rebranded from "NL Drip Engine" to "DripForge" across all user-facing text
- Updated: plugin header, admin menu labels, page titles, cron display name, error_log messages, readme.txt, plugin info modal
- Internal prefixes (`nlde_`, `NLDE_`) intentionally kept to avoid breaking existing DB tables/options on live sites
- Version bumped to 1.1.0

**New Features (v1.2.0):**
- **Templates page** — 6 pre-built sequence blueprints (Welcome Series, Lead Magnet Delivery, Customer Onboarding, Re-engagement, Product Launch, Educational Email Course) with one-click import
- **Guide page** — Full 9-section how-to documentation with print/PDF support via `@media print` CSS
- **Inline guide cards** — Dismissible "New to DripForge?" card on dashboard with links to Templates, Guide, Sequences
- **Empty state CTA** — Sequences page shows "Browse Sequence Templates" when no sequences exist

### File Locations
- Canonical: `c:\xampp\htdocs\ForgePlatform\drip-forge-project\plugin\nl-drip-engine\` (in ForgePlatform repo)
- Also at: `c:\xampp\htdocs\drip-forge-project\plugin\nl-drip-engine\` (htdocs working copy)
- Deployed: `next-level/wp-content/plugins/nl-drip-engine/` and `julias-graphic-design/wp-content/plugins/nl-drip-engine/`

### Key Files Added
- `includes/class-templates.php` — Template data + import logic
- `admin/views/templates.php` — Template card grid UI
- `admin/views/guide.php` — Full documentation page

---

## 2. BackForge — v2.0 Migration Mode Spec

### What Was Done
- Created `19-MIGRATION-MODE-SPEC.md` in `wp-s3-backup-project/docs/`
- Specifies how to make BackForge the superior migration tool vs manual zip+DB import

### Key Decisions
- **v1.5.0 (ship before migration):** Background restore, URL search-replace moved to free, enhanced env check, post-restore validation
- **v2.0.0 (post-migration):** Full Migration Wizard UI (Pro), chunked restore with resume (Pro), auto-retry
- URL search-replace moves from Pro to Free — it's table stakes for cross-domain restore
- Background restore moves from Pro to Free — the synchronous restore is a production blocker (503 on hosted servers)

### Migration Strategy (June 30 deadline)
- Recommended: Manual migration to AccuWebHost first, then install BackForge on new host
- BackForge v1.5 makes future migrations one-click

---

## 3. ShieldForge — Full Plugin Build (Phase 1-3)

### What Was Done
- Built the complete ShieldForge v1.0.0 plugin from scratch
- Covers Phase 1 (Login Hardening), Phase 2 (IP Blocklist + Rate Limiting), Phase 3 (WAF)
- Dark Forge UI with crimson accent (`#E11D48`)
- Brand images integrated (hero banner, logo, plugin icon)
- Per-rule WAF toggles with preset configurations and custom profile save/load

### Architecture
```
sf-security/
├── sf-security.php                 ← Entry point
├── uninstall.php                   ← Clean removal
├── assets/                         ← Brand images (hero, logo-full, logo-mark, plugin-banner, feature-grid)
├── includes/
│   ├── class-sfs-plugin.php        ← Activation, tables, menus, assets
│   ├── class-sfs-login.php         ← Brute force, lockouts, XMLRPC, user enum
│   ├── class-sfs-blocklist.php     ← IP block/allow, CIDR, transient cache
│   ├── class-sfs-rate-limiter.php  ← Per-endpoint sliding window
│   ├── class-sfs-firewall.php      ← WAF rules, toggles, presets, profiles
│   ├── class-sfs-logger.php        ← Event logging, stats, purge
│   └── class-sfs-settings.php      ← Settings save, page controllers
├── admin/
│   ├── css/admin.css               ← Dark Forge UI (crimson accent)
│   ├── js/admin.js                 ← AJAX for blocklist, rule toggles, presets
│   └── views/
│       ├── dashboard.php           ← Hero banner, stats, lockouts, activity, status
│       ├── firewall.php            ← Rules with toggles, presets dropdown, profiles
│       ├── blocklist.php           ← IP management, lockouts, allowlist
│       ├── activity-log.php        ← Filterable event log
│       └── settings.php            ← All options in 2-column grid
└── data/ + languages/              ← Empty, ready for future phases
```

### Features Active Out of the Box
- Brute force protection (5 failures in 15 min → lockout, escalating duration)
- Auto-ban after 3 lockouts (permanent blocklist)
- IP blocklist with CIDR range support
- IP allowlist (bypass all checks)
- Rate limiting: general 120/min, login 10/min, xmlrpc 5/min
- WAF with 21 rules (SQLi, XSS, path traversal, RCE, WP-specific, bad bots)
- Per-rule enable/disable toggles via AJAX
- 4 built-in presets (Default, Strict, Minimal, Paranoid)
- Save/load custom WAF profiles
- XMLRPC disabled
- Login error messages hidden
- User enumeration blocked (author param + REST API users endpoint)
- Email notification on lockout
- Activity log with 90-day auto-purge
- Security status checklist on dashboard

### Database Tables (4)
- `{prefix}sfs_log` — Security events
- `{prefix}sfs_blocklist` — IP block/allow entries
- `{prefix}sfs_login_attempts` — Login tracking
- `{prefix}sfs_lockouts` — Active/historical lockouts

### Options Used
- `sfs_waf_rule_overrides` — Per-rule enable/disable state (only stores diffs from default)
- `sfs_waf_profiles` — Saved custom configurations
- `sfs_lockout_threshold`, `sfs_lockout_window`, `sfs_lockout_duration`, `sfs_lockout_escalation`
- `sfs_permanent_ban_after`, `sfs_disable_xmlrpc`, `sfs_hide_login_errors`, `sfs_block_user_enum`
- `sfs_rate_limit`, `sfs_rate_limit_login`, `sfs_waf_enabled`, `sfs_notify_lockout`, `sfs_log_retention_days`

### File Locations
- Canonical: `c:\xampp\htdocs\ForgePlatform\shield-forge-project\plugin\sf-security\`
- Test deployment: `c:\xampp\htdocs\mysite\wp-content\plugins\sf-security\`
- Git: `github.com/efong505/htdocs.git` → `ForgePlatform/shield-forge-project/plugin/sf-security/`

### Remaining Phases (Not Built Yet)
- Phase 4: File Integrity Monitoring (SHA-256 checksums, scheduled scans)
- Phase 5: Malware Scanner (Pro — signature-based, chunked, quarantine)
- Phase 6: Two-Factor Authentication (Pro — TOTP)
- Phase 7: Country Blocking (GeoIP — free, MaxMind GeoLite2 local DB)
- Phase 8: Activity Log expansion + REST API Protection

---

## 4. Infrastructure & Environment

### MySQL Fix
- XAMPP MySQL root had no password, but all wp-config files expected `Hawaiian2012!`
- Fixed by setting the root password: `ALTER USER 'root'@'localhost' IDENTIFIED BY 'Hawaiian2012!';`
- All sites now connect successfully

### Git Repository
- Repo: `github.com/efong505/htdocs.git`
- Branch: `main`
- Latest commits:
  - `883838e` — fix(backforge-pro): Update license validation class
  - `a3e8aed` — docs(shieldforge): Align project docs with Forge positioning
  - `8fd8132` — feat(shieldforge): Add ShieldForge v1.0.0 plugin - Phase 1-3 complete

### ForgePlatform Structure (Correct Location)
```
c:\xampp\htdocs\ForgePlatform\
├── shield-forge-project/
│   ├── docs/ (5 docs — project plan, WAF guide, status, monetization, creative handoff)
│   └── plugin/sf-security/ (the built plugin)
├── drip-forge-project/
│   ├── docs/
│   └── plugin/nl-drip-engine/
├── wp-s3-backup-project/
│   ├── docs/ (19 docs including migration spec)
│   ├── plugin/wp-s3-backup/ + wp-s3-backup-pro/
│   └── terraform/
└── wp-license-platform/
    ├── docs/
    └── plugin/wp-license-platform/
```

There is also a separate ForgePlatform at `C:\Users\Ed\Documents\Post Graduation\Projects\Forge Brand\ForgePlatform\` which contains the main website, API, infrastructure, frontend, marketing docs, and brand assets. The brand images for ShieldForge were sourced from `frontend/src/assets/brand/shieldforge/`.

---

## 5. Forge Ecosystem Direction (Key Context)

The Forge brand has shifted significantly. Key points from the master context:

- **Forge is NOT a plugin company** — it's a "systems and operational infrastructure ecosystem"
- **Products are operational system components**, not standalone plugins
- **Each product solves one operational concern completely:**
  - BackForge = Data Protection Layer (resilience, recovery, continuity)
  - LicenseForge = Commerce Infrastructure Layer (revenue, fulfillment, customer management)
  - DripForge = Communication Automation Layer (nurture, follow-up, conversion)
  - ShieldForge = Security Operations Layer (protection, monitoring, hardening)
- **Philosophical foundation:** Isaiah 54:16 — the smith, the fire, refinement, instruments fit for the work
- **Core diagnosis:** "The Missing Filter" — between inputs and outputs, most businesses have no structured system; the owner becomes the system
- **Framework:** `Inputs → Filters → Workflows → Outputs → Feedback`
- **Platform:** ForgePlatform is a unified serverless infrastructure (S3, CloudFront, API Gateway, Lambda, DynamoDB, SES, Terraform)
- **Not a SaaS** — ownership-first, self-hosted, zero cloud dependency for core features

### Brand Colors
- Forge parent: Dark navy `#0F172A`, slate `#1E293B`
- BackForge: Teal `#14B8A6`
- LicenseForge: Indigo `#6366F1`
- DripForge: TBD
- ShieldForge: Crimson `#E11D48`
- Pro accent: Indigo `#6366F1`

---

## 6. Memory Bank Updates

The `.amazonq/rules/memory-bank/` files were updated during this session but may be out of date with the ForgePlatform direction. The canonical docs are now in `ForgePlatform/docs/` (platform, ecosystem, products, branding, marketing).

---

## 7. Pending / Next Steps

| Item | Priority | Notes |
|------|----------|-------|
| Migrate sites to AccuWebHost | **Critical — June 30 deadline** | Manual migration recommended, then install BackForge |
| Install Wordfence free on AccuWebHost | High | Interim protection until ShieldForge is production-ready |
| WordPress.org plugin submissions | High | All plugins need readme.txt, assets, testing |
| ShieldForge Phase 4 (File Integrity) | Medium | Next build phase |
| ShieldForge Phase 7 (Country Blocking) | Medium | Key differentiator — free feature |
| BackForge v1.5 (Background Restore) | Medium | Port from Pro's external restore pattern |
| BackForge Pro — custom N-day schedule UI | Low | Backend ready, needs settings field for day input |
| DripForge dark SaaS UI | Low | Pending Forge rebrand styling |

---

## 8. Testing Notes

- ShieldForge is deployed on `mysite` (local XAMPP) for testing
- DripForge v1.2.0 is deployed on `next-level` and `julias-graphic-design`
- All sites use MySQL root password `Hawaiian2012!`
- Sites are accessed via `https://edwardfong.onthewifi.com/{site-name}`

---

## 9. Late Additions (BackForge)

### Bulk Delete with Checkboxes (Free — backups-page.php + admin.js + admin.css)
- Each backup card has a checkbox for multi-select
- "Select All" checkbox at top of backup list
- Bulk actions bar appears when items selected (count + "Delete Selected" button)
- Fires parallel AJAX delete requests then reloads page
- Individual per-backup delete still works as before

### Custom N-Day Schedule (Pro — class-wps3b-pro-schedule.php)
- Backend supports `custom_N` frequency (e.g. `custom_3` = every 3 days)
- Registers dynamic cron interval (`every_3_days`, `every_14_days`, etc.)
- Stored in `wps3b_pro_custom_days` option
- **Still needs:** UI input field in Pro settings page for users to enter the number of days
- Existing schedules (hourly, 4h, 6h, daily, weekly, monthly) still work unchanged

### Git Commit
- `35005bb` — feat(backforge): Add bulk delete with checkboxes + custom N-day schedule (Pro)

---

*End of handoff*
