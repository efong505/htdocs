# BackForge & LicenseForge — Continuation Handoff

## Context

This is a handoff document for continuing development of the BackForge and LicenseForge plugin ecosystem. The core plugins are built and functional. This session covers polish, theming, and launch preparation.

## What Already Exists (all working)

### BackForge (Free + Pro)
- Location: `c:\xampp\htdocs\wp-s3-backup-project\plugin\`
- Full dark SaaS admin UI (#0F172A / #1E293B / #14B8A6 teal)
- Background backup with real-time progress polling (wp-cron based, survives navigation)
- Integrated backup progress card with per-file status
- AJAX delete with fade animation
- S3-compatible endpoints (AWS, B2, Wasabi, Spaces, MinIO)
- 14 Pro features including cross-site restore, upload & restore, prefix browser
- Banner images on Dashboard and Upgrade pages
- All deployed and tested on ekewaka local site

### LicenseForge
- Location: `c:\xampp\htdocs\wp-license-platform\plugin\wp-license-platform\`
- Full checkout, licensing, customer portal, PayPal integration, VAT compliance
- AJAX file upload for product downloads
- License detail view with site activations
- **Admin UI still uses OLD light theme** — needs dark SaaS treatment

### Live Test Environment
- Site: `c:\xampp\htdocs\ekewaka\` (https://edwardfong.onthewifi.com/ekewaka)
- Both plugins active with Pro licensed (test key: WPS3B-TEST-DEVL-KEY1)
- Dev API override via mu-plugin: `wp-content/mu-plugins/wps3b-dev-api.php`
- Product "WP S3 Backup Pro" created in LicenseForge with 3 tiers

### Documentation
- `docs/13-PROJECT-STATUS.md` — Complete feature reference
- `docs/14-COMPETITIVE-RESEARCH-NAMING.md` — Market research and Forge brand strategy
- `docs/12-UI-UX-OVERHAUL-PLAN.md` — UI phases plan
- `.amazonq/rules/memory-bank/` — Guidelines, product overview, structure, tech stack

## Tasks for This Session

### 0. Background Restore (CRITICAL — Production Blocker)

Restoring large backups (1GB+) on hosted servers causes 503 timeout errors because the restore runs synchronously in a single HTTP request. This is a production blocker — any hosted environment with standard timeouts (30-60s) will fail.

**Fix:** Convert restore to background wp-cron process, same pattern as background backup.

**Implementation:**
- Click Restore → creates `wps3b_restore_status` option + schedules wp-cron
- Cron runs restore steps: download DB → import DB → download files → extract files → URL replacement → cleanup
- Each step updates the status option with progress
- JS polls every 3 seconds and shows progress (reuse the integrated card pattern from backup)
- User can navigate away — restore continues in background
- Maintenance mode enabled during restore, disabled on completion
- Works for: local restore, cross-site restore (from different prefix), upload restore

**Files to modify:**
- `includes/class-wps3b-restore.php` — add background restore runner
- `includes/class-wps3b-settings.php` — add AJAX restore start/poll/dismiss handlers
- `includes/class-wps3b-plugin.php` — register cron hook
- `admin/views/backups-page.php` — add restore progress UI (similar to backup progress card)
- `admin/js/admin.js` — add restore polling JS
- Pro: `class-wps3b-pro-restore.php` — update selective/external/upload restore to use background process

### 1. Apply Dark SaaS Theme to LicenseForge Admin

LicenseForge admin pages still use the old light WordPress theme. They need the same dark SaaS treatment as BackForge.

**Files to update:**
```
wp-license-platform/plugin/wp-license-platform/
├── admin/
│   ├── css/admin.css              ← Rewrite with dark theme (use BackForge's --bf-* variables as reference, but use --lf-* with indigo/purple accents)
│   ├── views/
│   │   ├── dashboard.php          ← Dark stat cards, recent orders table
│   │   ├── products-list.php      ← Dark table
│   │   ├── product-edit.php       ← Dark cards, file upload styled
│   │   ├── orders-list.php        ← Dark table
│   │   ├── licenses-list.php      ← Dark table + detail view
│   │   ├── settings-page.php      ← Dark cards
│   │   └── upgrade-page.php       ← Dark upgrade page
│   └── class-wplp-admin.php       ← Menu position already at 4
```

**LicenseForge brand colors:**
- Primary: #6366F1 (indigo)
- Accent: #8B5CF6 (purple)
- Highlight: #A78BFA (violet)
- Shared dark base: #0F172A / #1E293B (same as BackForge)
- Success/Warning/Error: same as BackForge

**Design approach:**
- Same dark background, card system, and layout patterns as BackForge
- Replace teal accents with indigo/purple
- Same button styles but with indigo gradient instead of teal
- Same table, form, and status badge patterns

### 2. First-Time Setup Wizard (BackForge)

Show a guided setup flow when credentials are not configured.

**Steps:**
1. Welcome — "Let's connect to Amazon S3"
2. Credentials — Access Key + Secret Key with inline validation
3. Bucket — Bucket name + region + custom endpoint + Test Connection
4. First Backup — "Create your first backup now?"

**Implementation:**
- Full-screen modal overlay within the bf-wrap
- Step indicator (1 → 2 → 3 → 4)
- Each step validates before allowing next
- Stores `wps3b_wizard_completed` option
- Can be dismissed and accessed later

### 3. Toast Notifications (BackForge)

Replace WordPress admin notices with slide-in toast notifications for AJAX responses.

**CSS is already defined** in admin.css (`.bf-toast-container`, `.bf-toast`, animations).

**Need to build:**
- JS toast module: `BFToast.success('message')`, `BFToast.error('message')`
- Auto-dismiss after 5 seconds (errors stay until clicked)
- Stack multiple toasts
- Use for: test connection, backup start, delete, settings save

### 4. PayPal Configuration + Test Purchase Flow

**On ekewaka site:**
1. Get PayPal sandbox credentials from developer.paypal.com
2. Enter in LicenseForge → Settings (sandbox mode enabled)
3. Test the full flow:
   - Visit /pricing → select tier → /checkout
   - Complete PayPal sandbox payment
   - Verify: order created, license generated, email sent
   - Test license activation in BackForge Pro
4. Switch to live PayPal credentials when ready

### 5. WordPress.org Submission Prep

**Rename slugs and text domains:**
- `wp-s3-backup` → `backforge-s3-backup`
- `wp-s3-backup-pro` → `backforge-pro`
- `wp-license-platform` → `licenseforge`
- Update all text domains, function prefixes, option names
- Update plugin headers (Plugin Name, Description, Plugin URI)

**Create readme.txt:**
- WordPress.org format with proper headers
- Short description, long description, FAQ, changelog
- Screenshots section (reference banner images)

**Create assets for WordPress.org SVN:**
```
assets/
├── banner-772x250.png        ← Plugin page banner
├── banner-1544x500.png       ← Retina banner
├── icon-128x128.png          ← Search results icon
├── icon-256x256.png          ← Retina icon
└── screenshot-1.png          ← Dashboard screenshot
```

### 6. Performance Benchmarks for Marketing

### 7. Recurring Billing / Subscription Support (LicenseForge)

Currently one-time purchase only. Needs PayPal Subscriptions API for automatic yearly renewals.

**What's needed:**
- PayPal Subscription Plans (one per tier)
- Subscribe button instead of one-time Pay on checkout
- Webhook handling: `BILLING.SUBSCRIPTION.ACTIVATED`, `PAYMENT.SALE.COMPLETED`, `BILLING.SUBSCRIPTION.CANCELLED`
- Auto-renewal: webhook fires → extend license expiry +1 year → generate invoice
- Cancellation: mark license as "will not renew", stays active until expiry
- Failed payment: dunning email → grace period → suspend license
- Admin UI: show subscription status, cancel/pause controls
- Customer portal: manage subscription, view billing history

### 8. LicenseForge Admin CRUD Improvements

**Already built (this session):**
- Order status change (dropdown, AJAX)
- Order delete
- License status change (dropdown, AJAX)
- License delete (with activations cleanup)
- Auto-page creation on plugin activation (7 pages with shortcodes)
- Page customization in Settings (dropdown selectors with Edit/View buttons)
- Customer WordPress account auto-creation on purchase (subscriber role + password reset email + portal link)
- Checkout tier pre-selection from pricing page URL parameter
- Dark SaaS admin theme (indigo/purple accents) on all LicenseForge pages

**Still needed:**
- Order detail view (click order to see full details, PayPal IDs, VAT evidence)
- License edit (change expiry date, sites allowed, manually extend)
- Customer management (edit, merge, delete)
- Bulk actions (delete selected orders/licenses)
- Search/filter on all list pages

Create comparison data for marketing content:
- Plugin file size: BackForge (~50KB) vs UpdraftPlus (~15MB) vs Duplicator
- Memory usage during backup
- Backup speed comparison
- Number of dependencies (0 vs competitors)

## Known Issues to Fix

1. **Pro API URL** — Currently overridden by mu-plugin for local dev. Production URL is `https://ekewaka.com/wp-json/wplp/v1/`. Remove mu-plugin before going live.
2. **Text domain** — Still uses `wp-s3-backup` everywhere. Needs rename to `backforge-s3-backup` for WordPress.org.
3. **Old backups** — Backups created before the timestamp fix show as separate rows. Only affects existing S3 data, not new backups.

## Session Prompt

```
I'm continuing development of the BackForge and LicenseForge WordPress plugin 
ecosystem. The plugins are built and functional — this session is about polish 
and launch preparation.

Project status: c:\xampp\htdocs\wp-s3-backup-project\docs\13-PROJECT-STATUS.md
Continuation plan: c:\xampp\htdocs\wp-s3-backup-project\docs\17-CONTINUATION-HANDOFF.md
UI plan: c:\xampp\htdocs\wp-s3-backup-project\docs\12-UI-UX-OVERHAUL-PLAN.md
Memory bank: c:\xampp\htdocs\.amazonq\rules\memory-bank\

BackForge: c:\xampp\htdocs\wp-s3-backup-project\plugin\wp-s3-backup\
BackForge Pro: c:\xampp\htdocs\wp-s3-backup-project\plugin\wp-s3-backup-pro\
LicenseForge: c:\xampp\htdocs\wp-license-platform\plugin\wp-license-platform\
Live site: c:\xampp\htdocs\ekewaka\

Priority order:
1. Apply dark SaaS theme to LicenseForge admin (indigo/purple accents)
2. First-time setup wizard for BackForge
3. Toast notifications JS
4. PayPal test purchase flow
5. WordPress.org submission prep
6. Performance benchmarks

Please start with task 1 — the LicenseForge dark theme.
```
