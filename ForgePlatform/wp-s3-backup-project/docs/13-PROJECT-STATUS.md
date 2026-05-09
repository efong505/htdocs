# BackForge Project — Complete Status & Feature Reference

*Last updated: April 17, 2026*

---

## Brand

| | |
|---|---|
| **Product Family** | Forge |
| **Backup Plugin** | BackForge (slug: `backforge-s3-backup`) |
| **Backup Pro** | BackForge Pro (slug: `backforge-pro`) |
| **License Platform** | LicenseForge (slug: `licenseforge`) |
| **Current working slugs** | `wp-s3-backup`, `wp-s3-backup-pro`, `wp-license-platform` |

**Brand Colors (BackForge):**
- Primary Teal: `#14B8A6` / Dark Teal: `#0D7377`
- Navy: `#0F172A` / Slate: `#1E293B`
- Success: `#22C55E` / Warning: `#F59E0B` / Error: `#EF4444`
- Pro accent: `#6366F1`

---

## Project Locations

```
c:\xampp\htdocs\wp-s3-backup-project\
├── plugin\
│   ├── wp-s3-backup\           ← Free plugin (BackForge)
│   └── wp-s3-backup-pro\       ← Pro plugin (BackForge Pro)
├── terraform\                  ← AWS infrastructure
└── docs\                       ← All documentation

c:\xampp\htdocs\wp-license-platform\
└── plugin\
    └── wp-license-platform\    ← LicenseForge

Live site: c:\xampp\htdocs\ekewaka\  (https://edwardfong.onthewifi.com/ekewaka)
```

---

## Plugin 1: BackForge (Free)

### Architecture
- Direct S3 REST API with AWS Signature V4 (no SDK, no Composer)
- S3-compatible endpoint support (Backblaze B2, Wasabi, DigitalOcean Spaces, MinIO)
- All HTTP via `wp_remote_request()`
- Database operations via `$wpdb` with prepared statements
- File operations via `ZipArchive` and native PHP

### Features (all implemented ✅)

**Backup Engine:**
- Full site backup: database (gzipped SQL) + files (wp-content zip) + manifest (JSON)
- Shared timestamp across all 3 files (ensures proper grouping)
- Multipart upload for files > 25MB (10MB chunks)
- Configurable exclusion paths (comma-separated, wildcard support)
- Database table exclusion filter
- Manifest includes: WP version, PHP version, table prefix, active plugins, checksums, row/table counts

**Scheduling:**
- wp-cron: twice daily, daily, weekly, monthly
- Enable/disable toggle
- Auto-reschedule on settings change
- AJAX backup with real-time step-by-step progress (init → export DB → export files → upload DB → upload files → manifest)
- Progress bar with elapsed time counter
- Step-by-step status with spinning → checkmark transitions
- Auto-refresh backup list on completion

**S3 Integration:**
- AWS Signature V4 authentication
- S3-compatible endpoint support (Backblaze B2, Wasabi, DigitalOcean Spaces, MinIO)
- Custom endpoint URL field — leave empty for AWS, fill in for any S3-compatible provider
- Single-part and multipart uploads
- Pre-signed download URLs (1-hour expiry)
- Object listing with storage class detection
- Object deletion
- Connection test (ListBucket with max-keys=1)
- Exponential backoff retry (3 retries)

**Restore:**
- One-click full site restore (database + files)
- Pre-restore compatibility checks (PHP, WP, table prefix, URL mismatches)
- Maintenance mode during restore
- Automatic temp file cleanup (even on failure)

**Security:**
- AWS credentials encrypted at rest (AES-256-CBC with WordPress salts)
- Temp directory protected (.htaccess + index.php)
- All forms nonce-protected
- All actions require `manage_options`
- Masked credential display

**Admin UI (BackForge branded):**
- Dashboard stat cards (last backup, next scheduled, S3 status, errors)
- Card-based settings layout (2-column responsive grid)
- Card-based backup list with file type icons
- Storage class badges (colored pills)
- Empty states with CTAs
- Activity log with auto-refresh
- Pro upgrade page with feature cards and pricing
- Teal gradient buttons, smooth animations

**Hooks for Pro:**
- Actions: `wps3b_before_backup`, `wps3b_after_backup`, `wps3b_backup_failed`, `wps3b_before_upload`, `wps3b_after_upload`, `wps3b_after_backups_list`
- Filters: `wps3b_exclude_paths`, `wps3b_exclude_tables`, `wps3b_s3_path_prefix`, `wps3b_backup_filename`
- Helper: `wps3b_is_pro_active()`

### File Structure
```
wp-s3-backup/
├── wp-s3-backup.php
├── includes/
│   ├── class-wps3b-backup-engine.php
│   ├── class-wps3b-backup-manager.php
│   ├── class-wps3b-crypto.php
│   ├── class-wps3b-logger.php
│   ├── class-wps3b-plugin.php
│   ├── class-wps3b-restore.php
│   ├── class-wps3b-s3-client.php
│   └── class-wps3b-settings.php
├── admin/
│   ├── css/admin.css
│   ├── js/admin.js
│   └── views/
│       ├── settings-page.php
│       ├── backups-page.php
│       ├── logs-page.php
│       └── upgrade-page.php
├── uninstall.php
└── readme.txt
```

---

## Plugin 2: BackForge Pro

### Architecture
- Separate plugin, extends free via hooks/filters
- Defers loading to `plugins_loaded` priority 15 (solves alphabetical load order)
- All features gated behind `WPS3B_Pro_License::is_licensed()`
- License API URL filterable via `wps3b_pro_api_url`

### Features (all implemented ✅)

**1. License System:**
- API validation against LicenseForge REST endpoints
- Daily re-check via transient expiry (24-hour cache)
- 7-day grace period if API unreachable
- Activation/deactivation with site URL tracking
- Masked key display
- Status info (active, grace, expired, none)

**2. Email Notifications:**
- Hooks into `wps3b_after_backup` and `wps3b_backup_failed`
- Configurable recipient email
- Toggle success/failure independently
- Includes backup details (DB size, file count, error message)

**3. Slack/Webhook Notifications:**
- Any HTTP endpoint URL
- Auto-detects Slack webhooks and formats with emoji + bold
- Works with Zapier, Discord, Teams

**4. AES-256-CBC Encryption:**
- Client-side encryption before S3 upload
- Hooks into `wps3b_before_upload`
- Chunked encryption with IV prefix
- Password-based key derivation (SHA-256)
- Decrypt on restore (automatic when encryption enabled)
- Chunk length headers for reliable decryption

**5. Storage Class Management:**
- Change any file's S3 storage class via AJAX
- CopyObject API (copy-to-self with new storage class header)
- Supports: Standard, Standard-IA, Intelligent-Tiering, Glacier IR, Glacier, Deep Archive
- Per-file controls on the Storage page
- Real-time status feedback

**6. Cost Estimates:**
- Monthly cost calculation from actual file sizes and storage classes
- Per-class breakdown table
- Dashboard stat card showing total estimated cost
- Uses current AWS S3 pricing per GB

**7. Selective Restore:**
- Radio buttons: Full Site / Database Only / Files Only
- Shown on restore confirmation page when Pro is active
- Handles encrypted backups (auto-decrypt)

**8. URL Replacement on Restore:**
- Serialization-safe search-and-replace
- Handles WordPress serialized data (updates string length prefixes)
- Recursively processes arrays and objects
- Pre-fills old URL from manifest, new URL from current site
- Works with selective restore (only runs when DB is restored)

**9. Cross-Site Restore (Restore from Another Site):**
- Enter a different S3 path prefix to browse another site's backups
- AJAX-powered: browse → select → configure → restore
- Lists backups grouped by timestamp with file details
- Full restore options: type selection + URL replacement
- Uses same S3 bucket with different prefix

**10. Upload & Restore:**
- Upload backup files (.sql.gz and/or .zip) from local computer
- AJAX upload with progress bar
- Validates file types (only .gz, .zip, .json allowed)
- Files stored in protected temp directory
- Path traversal protection via `realpath()` validation
- Full restore options: type selection + URL replacement
- Automatic temp file cleanup

**11. Custom Schedules:**
- Adds: hourly, every 4 hours, every 6 hours
- Time-of-day picker for daily/weekly
- Overrides free plugin's schedule settings
- Registers custom wp-cron intervals

**12. Incremental Backups:**
- Tracks file mtime + size between backups
- Stores manifest in wp_options (non-autoload)
- First backup is always full
- Subsequent backups exclude unchanged files via `wps3b_exclude_paths` filter
- Logs changed/unchanged file counts
- Reset option to force full backup

**13. Cross-Site Restore (Restore from Another Site):**
- Enter a different S3 path prefix to browse another site's backups
- AJAX-powered: browse → select → configure → restore
- Lists backups grouped by timestamp with file details
- Full restore options: type selection + URL replacement
- Uses same S3 bucket with different prefix

**14. Upload & Restore:**
- Upload backup files (.sql.gz and/or .zip) from local computer
- AJAX upload with progress bar
- Validates file types (only .gz, .zip, .json allowed)
- Files stored in protected temp directory
- Path traversal protection via `realpath()` validation
- Full restore options: type selection + URL replacement
- Automatic temp file cleanup

### File Structure
```
wp-s3-backup-pro/
├── wp-s3-backup-pro.php
├── includes/
│   ├── class-wps3b-pro.php
│   ├── class-wps3b-pro-license.php
│   ├── class-wps3b-pro-notifications.php
│   ├── class-wps3b-pro-encryption.php
│   ├── class-wps3b-pro-storage.php
│   ├── class-wps3b-pro-schedule.php
│   ├── class-wps3b-pro-restore.php
│   └── class-wps3b-pro-incremental.php
├── admin/
│   ├── css/pro-admin.css
│   ├── js/pro-admin.js
│   └── views/
│       ├── license-page.php
│       └── storage-management.php
└── languages/
    └── wp-s3-backup-pro.pot
```

### Pricing
| Tier | Price | Sites |
|------|-------|-------|
| Personal | $49/year | 1 |
| Professional | $99/year | 5 |
| Agency | $199/year | Unlimited |

---

## Plugin 3: LicenseForge

### Architecture
- Self-hosted WordPress plugin on seller's website
- REST API for license validation (called by Pro plugins)
- PayPal REST API v2 (no SDK)
- 7 custom database tables
- Shortcode-based public pages

### Features (all implemented ✅)

**Product Management:**
- Unlimited products with tiers
- AJAX zip file upload with progress bar and validation
- Protected downloads directory (auto-created on activation)
- `.htaccess` + `index.php` + 0644 permissions
- File validation: extension, MIME type, ZipArchive integrity, 100MB max
- Delete validation: `realpath()` inside downloads dir only

**Checkout & Payments:**
- PayPal REST API v2 (Create → Approve → Capture)
- OAuth 2.0 with transient caching
- Sandbox/live toggle
- Credentials encrypted at rest
- Webhook for refunds (signature verified)
- Sequential order numbering

**License Key System:**
- Auto-generated with product prefix (e.g., `WPS3B-A7K2-NP4X-9BHT`)
- Collision-free generation
- Per-site activation tracking (URLs + timestamps + last check-in)
- Site limit enforcement per tier
- Statuses: active, expired, revoked, suspended
- Auto-expiry (annual/monthly/lifetime)
- Daily cron expires past-due licenses
- Admin detail view: click license to see all activated sites

**REST API:**
- `POST /wplp/v1/validate` — validate key, check site, return tier + expiry
- `POST /wplp/v1/activate` — register site URL
- `POST /wplp/v1/deactivate` — remove site URL
- `POST /wplp/v1/create-order` — checkout flow
- `POST /wplp/v1/capture-order` — payment capture
- `POST /wplp/v1/calculate-tax` — real-time VAT
- `POST /wplp/v1/paypal-webhook` — refund handling
- Rate limiting: 30 req/min per IP

**Customer Portal:**
- `[wplp_portal]` — dashboard with license count and recent orders
- `[wplp_licenses]` — licenses with site deactivation
- `[wplp_downloads]` — tokenized downloads (1-hour expiry, one-time use)
- `[wplp_invoices]` — order history
- `[wplp_pricing product="slug"]` — pricing table
- `[wplp_checkout product="slug"]` — full checkout with tier pre-selection from URL
- `[wplp_thank_you]` — post-purchase confirmation
- All portal pages require WordPress login, linked to customer record by email
- Pages auto-created on plugin activation, customizable in LicenseForge → Settings

**Customer Account Auto-Creation:**
- On first purchase, if no WordPress user exists with the customer's PayPal email:
  - WordPress subscriber account auto-created
  - Username generated from email (e.g., `john` from `john@example.com`, numeric suffix if taken)
  - Password reset email sent with setup link + portal URL
- If WordPress user already exists, customer record linked to existing user
- Two emails after purchase: purchase confirmation (license key) + account setup (login credentials)

**EU VAT Compliance:**
- Rates for 27 EU states + UK, Norway, Switzerland
- Real-time calculation during checkout
- VIES VAT number validation
- B2B reverse charge
- Two-piece evidence (billing country + IP geolocation)
- Dedicated evidence table

**Emails:**
- Purchase confirmation (with license key)
- Account setup (username + password reset link + portal URL for new customers)
- Renewal reminder (30, 7, 1 days)
- License expired
- Refund confirmation
- HTML templates via `wp_mail()`

**Admin Panel:**
- Dashboard with revenue, orders, licenses, customers
- Products with inline tier management + file upload
- Orders list with status badges
- Licenses list → click to see activated site URLs
- Settings: business info, PayPal credentials, sandbox toggle

### Database Schema
```
wp_wplp_products        — product catalog
wp_wplp_product_tiers   — pricing tiers
wp_wplp_customers       — customer records
wp_wplp_orders          — transactions
wp_wplp_licenses        — license keys
wp_wplp_activations     — per-site activations
wp_wplp_vat_evidence    — tax compliance
```

### Pages Created
| Page | URL | Shortcode |
|------|-----|-----------|
| Pricing | `/pricing` | `[wplp_pricing product="wp-s3-backup-pro"]` |
| Checkout | `/checkout` | `[wplp_checkout product="wp-s3-backup-pro"]` |
| Thank You | `/checkout/thank-you` | `[wplp_thank_you]` |
| My Account | `/account` | `[wplp_portal]` |
| My Licenses | `/account/licenses` | `[wplp_licenses]` |
| My Downloads | `/account/downloads` | `[wplp_downloads]` |
| My Invoices | `/account/invoices` | `[wplp_invoices]` |

---

## UI/UX Status

### Phase 1 — Complete ✅
- Full dark mode SaaS UI (#0F172A / #1E293B) isolated within plugin content area
- WordPress default sidebar preserved, custom dark UI in content area
- Dashboard stat cards with teal glow accents and hover animations
- Card-based settings layout (2-column responsive grid)
- Card-based backup list with file type icons (DB=blue, files=amber, manifest=purple)
- Storage class colored pill badges (white text on colored background)
- Empty states with icons and CTAs
- BackForge branding throughout (teal accents, dark navy background)
- Glowing teal top-line on stat cards on hover
- Dark form inputs with teal focus glow ring
- Gradient buttons with hover lift + glow shadow
- Status pills with glowing dot indicators
- Dashboard banner image and Upgrade page banner image
- WordPress notices styled for dark theme
- Tables styled for dark theme
- Pro sections (external restore, upload restore, storage) styled for dark theme
- Responsive grid (stacks on mobile)
- Toast notification CSS readyacks on mobile)
- Toast-ready CSS (animations defined)

### Phase 2 — Partially Complete
- AJAX backup with real-time step-by-step progress ✅
- First-time setup wizard ⬜
- Toast notifications (JS implementation) ⬜
- Animated transitions ⬜

### Phase 3 — Pending
- S3 cost dashboard with charts (Pro)
- Backup calendar view (Pro)
- Site Health integration
- Restore preview/diff

See `docs/12-UI-UX-OVERHAUL-PLAN.md` for full plan.

---

## Known Issues & Technical Notes

1. **Plugin load order**: Pro loads before Free alphabetically. Fixed by deferring to `plugins_loaded` priority 15.
2. **Backup timestamp**: Fixed — timestamp generated once in constructor, shared across all 3 files.
3. **Old backups**: Backups created before the timestamp fix show as separate rows (different timestamps per file). New backups group correctly.
4. **Dev API URL**: Pro plugin defaults to `https://ekewaka.com/wp-json/wplp/v1/`. Local dev override via mu-plugin filter on `wps3b_pro_api_url`.
5. **Text domain**: Still uses `wp-s3-backup` / `wp-s3-backup-pro` / `wp-license-platform`. Rename to `backforge-s3-backup` / `backforge-pro` / `licenseforge` when finalizing for release.
6. **Restore times out on hosted servers (503)**: Restoring large backups (1GB+) on hosted environments with LiteSpeed/nginx causes 503 timeout errors. The restore runs synchronously in a single HTTP request which exceeds server timeout limits (30-60s). **Fix needed: Background restore via wp-cron** — same pattern as background backup. Break restore into: download DB → import DB → download files → extract files, with status polling. This is critical for production use on any hosted environment.

---

## Test License

| Field | Value |
|-------|-------|
| Key | `WPS3B-TEST-DEVL-KEY1` |
| Tier | Professional |
| Sites | 5 allowed |
| Expires | April 15, 2027 |
| Customer | admin@ekewaka.com |

---

## Remaining Tasks

### Before WordPress.org Submission
- [ ] Background restore via wp-cron (production blocker — 503 on hosted servers)
- [ ] Rename plugin slugs and text domains to Forge brand
- [ ] Update plugin headers (name, description, URI)
- [ ] Generate proper .pot translation files
- [ ] Create readme.txt with WordPress.org formatting
- [ ] Create plugin banner and icon assets
- [ ] Security audit
- [ ] Test on PHP 7.4, 8.0, 8.1, 8.2, 8.3
- [ ] Test on WordPress 6.0 through latest

### Before Pro Launch
- [ ] Configure PayPal credentials (sandbox first, then live)
- [ ] Update Pro API URL to production `ekewaka.com`
- [ ] Remove mu-plugin dev override
- [ ] Build final Pro plugin zip for downloads
- [ ] Test full purchase flow end-to-end
- [ ] Set up transactional email (SMTP plugin on ekewaka.com)

### UI Phase 2
- [x] AJAX backup with step-by-step progress
- [ ] First-time setup wizard
- [ ] Toast notification JS
- [ ] Animated transitions
- [ ] Apply dark SaaS theme to LicenseForge admin pages
