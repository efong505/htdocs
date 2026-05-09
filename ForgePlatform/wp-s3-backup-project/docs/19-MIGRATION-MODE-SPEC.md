# BackForge v2.0 — Migration Mode Spec

*Created: June 2025*

---

## Table of Contents

1. [Overview](#overview)
2. [What Exists Today](#what-exists-today)
3. [What's Missing for Migration](#whats-missing-for-migration)
4. [Version Strategy](#version-strategy)
5. [Feature 1: Background Restore](#feature-1-background-restore)
6. [Feature 2: Migration Mode UI](#feature-2-migration-mode-ui)
7. [Feature 3: Enhanced Environment Check](#feature-3-enhanced-environment-check)
8. [Feature 4: Post-Restore Validation](#feature-4-post-restore-validation)
9. [Feature 5: Chunked Restore with Resume](#feature-5-chunked-restore-with-resume)
10. [Implementation Order](#implementation-order)
11. [File Changes Summary](#file-changes-summary)
12. [What Stays in Pro](#what-stays-in-pro)

---

## Overview

BackForge v2.0 makes the plugin the best way to migrate a WordPress site between hosts. The goal:

> Install WordPress → Install BackForge → Enter S3 credentials → Pick a backup → Your entire site is restored and migrated in one click, URLs and all.

This eliminates manual zip/unzip, phpMyAdmin imports, serialized URL headaches, and "did I forget something" anxiety.

---

## What Exists Today

### Free Plugin (v1.0.0)
- Full site backup (DB + files + manifest) to S3
- One-click restore (DB + files) — runs synchronously in a single HTTP request
- Pre-restore compatibility check (PHP version, WP version, table prefix, URL mismatch warning)
- Maintenance mode during restore
- Temp file cleanup

### Pro Plugin (v1.0.0)
- ✅ Serialization-safe URL search-replace (`search_replace_urls()` in `class-wps3b-pro-restore.php`)
- ✅ Selective restore (DB only / files only / full)
- ✅ Cross-site restore (browse another site's S3 prefix)
- ✅ Upload & restore (upload .sql.gz / .zip from local machine)
- ✅ Background restore via wp-cron (external + upload modes already use it)
- ✅ Encrypted backup support (AES-256-CBC decrypt on restore)

### Known Blocker (from doc 13)
> Restore times out on hosted servers (503). Restoring large backups on hosted environments with LiteSpeed/nginx causes 503 timeout errors. The restore runs synchronously in a single HTTP request which exceeds server timeout limits (30-60s). **Fix needed: Background restore via wp-cron.**

---

## What's Missing for Migration

| Gap | Impact | Where to Build |
|-----|--------|----------------|
| Background restore in free plugin | 503 timeouts on any shared host — restore is unusable in production | Free |
| Migration Mode UI flow | No guided "I'm migrating from another host" experience | Free (basic) + Pro (full) |
| URL replacement in free plugin | After restoring from localhost to production, every URL is broken | Move to Free |
| Enhanced environment check | Doesn't check disk space, PHP extensions, memory limit, max_execution_time | Free |
| Post-restore validation | User has no confirmation that the restore actually worked | Free |
| Chunked restore with resume | Large sites (1GB+) fail even with background processing if a single step exceeds memory | Pro |

---

## Version Strategy

| Version | Scope | Target |
|---------|-------|--------|
| **v1.5.0 (Free)** | Background restore + basic migration flow + URL replacement moved to free + enhanced env check + post-restore validation | Pre-migration prep |
| **v2.0.0 (Free + Pro)** | Full Migration Mode UI + chunked restore with resume (Pro) + migration wizard | Post-migration, when sites are live |

### Why v1.5 first?
You need to migrate by June 30th. The background restore fix alone makes BackForge usable on AccuWebHost. URL replacement in free means you don't need Pro installed just to fix URLs after a cross-host restore. Ship v1.5, migrate your sites manually, then build v2.0 at your own pace.

### What moves from Pro to Free in v1.5?
- **URL search-replace** — this is table stakes for any restore that crosses domains. Keeping it Pro-only means the free plugin's restore is broken for the most common use case (migration). Move it to free.
- **Background restore** — the free plugin's synchronous restore is a production blocker. This isn't a premium feature, it's a bug fix.

### What stays Pro?
- Selective restore (DB only / files only)
- Cross-site restore (browse another prefix)
- Upload & restore
- Encrypted backup decrypt
- Chunked restore with resume
- Migration wizard (guided multi-step flow)

---

## Feature 1: Background Restore

### Problem
Free plugin restore runs in a single HTTP request. Shared hosts kill it after 30-60 seconds.

### Solution
Port the background restore pattern already used in Pro's external/upload restore to the free plugin's standard restore flow.

### How It Works
1. User clicks "Restore" → confirmation page (existing)
2. On confirm, instead of running restore inline, write a `wps3b_restore_status` option with `running: true` and `step: queued`
3. Schedule `wps3b_run_background_restore` as a single wp-cron event
4. `spawn_cron()` to trigger immediately
5. Redirect to backups page — JS polls `wps3b_restore_status` option via AJAX every 2 seconds
6. Background cron handler runs steps sequentially:
   - `download_db` → download .sql.gz from S3 to temp
   - `import_db` → gunzip + execute SQL statements
   - `download_files` → download .zip from S3 to temp
   - `extract_files` → unzip to wp-content
   - `url_replace` → run serialization-safe search-replace (if old URL ≠ new URL)
   - `cleanup` → delete temp files, flush rewrite rules
   - `validate` → post-restore checks
7. Each step updates the status option with progress percentage + message
8. On completion or failure, set `running: false`

### UI
- Progress overlay on backups page (same pattern as AJAX backup progress)
- Step-by-step status: spinning → checkmark
- Error state with retry option

### Files to Modify
- `class-wps3b-restore.php` — refactor to background mode
- `class-wps3b-settings.php` — add AJAX status polling endpoint
- `admin/js/admin.js` — add restore progress polling
- `admin/views/backups-page.php` — add restore progress UI + URL replacement fields

---

## Feature 2: Migration Mode UI

### Free (v1.5) — Simple URL Replacement on Restore
Add two fields to the restore confirmation page:
- **Old Site URL** (pre-filled from backup manifest's `site_url`)
- **New Site URL** (pre-filled from current `home_url()`)
- If they differ, run search-replace after DB import automatically
- Checkbox: "Replace URLs in database" (checked by default when URLs differ)

### Pro (v2.0) — Full Migration Wizard
A dedicated admin page: **BackForge → Migrate**

**Step 1: Source**
- Radio: "From this site's S3 backups" / "From another site's S3 prefix" / "Upload backup files"
- If another prefix: text field for S3 path prefix → AJAX browse

**Step 2: Select Backup**
- List available backups grouped by timestamp
- Show: date, DB size, files size, WP version, PHP version (from manifest)

**Step 3: Configure**
- Restore type: Full / DB only / Files only
- Old URL → New URL (pre-filled from manifest + current site)
- Environment compatibility check results (auto-run)
- Warnings displayed inline (yellow) vs. blockers (red)

**Step 4: Restore**
- One-click start
- Background progress with step-by-step status
- Post-restore validation results

**Step 5: Done**
- Checklist of what was restored
- Links: visit site, check permalinks, verify admin
- "Run validation again" button

### Files to Create (Pro v2.0)
- `admin/views/migrate-page.php` — wizard UI
- `admin/js/pro-migrate.js` — wizard step logic
- `admin/css/pro-migrate.css` — wizard styles

---

## Feature 3: Enhanced Environment Check

### Current Checks (Free)
- PHP version match
- WordPress version match
- Table prefix match
- URL mismatch warning

### New Checks to Add

| Check | Severity | How |
|-------|----------|-----|
| Disk space vs. backup size | Blocker | `disk_free_space()` vs. manifest total size × 2.5 (need room for download + extract) |
| PHP memory_limit | Warning | Compare to 256M minimum recommended |
| PHP max_execution_time | Warning | Flag if < 120 and backup > 100MB |
| ZipArchive extension | Blocker | `class_exists('ZipArchive')` |
| OpenSSL extension | Blocker (if encrypted) | `function_exists('openssl_decrypt')` |
| gzip extension | Blocker | `function_exists('gzopen')` |
| wp-content writable | Blocker | `is_writable(WP_CONTENT_DIR)` |
| MySQL version | Warning | Compare backup manifest vs. current |
| Active plugins conflict | Info | List plugins in backup that aren't installed on target |
| PHP version direction | Warning | Flag if downgrading PHP (e.g., backup on 8.2, restoring on 7.4) |

### Implementation
- Extend existing `WPS3B_Restore::check_compatibility()` method
- Return structured array: `['blockers' => [...], 'warnings' => [...], 'info' => [...]]`
- Blockers prevent restore button from being clickable
- Warnings show yellow notices but allow proceed
- Info items are collapsible

### Manifest Additions
Add to backup manifest (backward-compatible — old manifests just won't have these):
```json
{
    "php_extensions": ["ZipArchive", "openssl", "gd", "curl", "mbstring"],
    "memory_limit": "256M",
    "mysql_version": "10.4.32-MariaDB",
    "total_backup_size": 157286400,
    "wp_content_size": 145000000,
    "db_size": 12286400
}
```

### Files to Modify
- `class-wps3b-restore.php` — expand `check_compatibility()`
- `class-wps3b-backup-engine.php` — add new fields to manifest generation
- `admin/views/backups-page.php` — render blocker/warning/info sections

---

## Feature 4: Post-Restore Validation

After restore completes, automatically run these checks and display results:

| Check | Method | Pass Criteria |
|-------|--------|---------------|
| Site loads | `wp_remote_get(home_url())` | HTTP 200 |
| Admin accessible | `wp_remote_get(admin_url())` | HTTP 200 or 302 (login redirect) |
| siteurl correct | `get_option('siteurl')` | Matches expected new URL |
| home correct | `get_option('home')` | Matches expected new URL |
| Active plugins load | Check for fatal errors in `error_log` | No new fatals since restore start |
| Cron working | `wp_next_scheduled('wps3b_scheduled_backup')` | Returns timestamp if schedule was active |
| S3 connectivity | Run connection test | Success |
| Permalinks | Flush and check `.htaccess` exists | File exists and writable |

### Display
- Checklist on the restore completion screen
- Green check / red X / yellow warning per item
- "Re-run validation" button
- Link to Settings → Permalinks (always — WordPress needs a manual save after DB restore)

### Files to Modify
- `class-wps3b-restore.php` — add `validate_restore()` method
- `admin/js/admin.js` — render validation results after restore progress completes
- `admin/views/backups-page.php` — validation results section

---

## Feature 5: Chunked Restore with Resume (Pro)

### Problem
Even with background processing, a single step can exceed memory. Importing a 500MB database in one pass, or extracting a 2GB zip, will hit `memory_limit` on shared hosting.

### Solution
Break each restore step into chunks that can be resumed if interrupted.

### Database Import Chunking
- Read .sql.gz in batches of 1000 statements
- After each batch, update progress in `wps3b_restore_status` with current byte offset
- If the cron job dies (timeout/memory), the next cron run reads the offset and resumes
- Use `gzseek()` to resume from last known position

### File Extraction Chunking
- Extract zip entries in batches of 100 files
- Track last extracted index in `wps3b_restore_status`
- Resume from that index on next cron run

### Resume Logic
```
On cron fire:
  1. Read wps3b_restore_status
  2. If step = 'import_db' and offset > 0 → resume from offset
  3. If step = 'extract_files' and file_index > 0 → resume from index
  4. If step completed → advance to next step
  5. If all steps done → run validation
```

### Status Option Schema
```php
[
    'running'      => true,
    'timestamp'    => '2025-06-15-143022',
    'step'         => 'import_db',        // current step
    'progress'     => 45,                 // percentage
    'message'      => 'Importing database (4,500 of 10,000 statements)...',
    'chunk_offset' => 4500,               // resume point
    'steps'        => [                   // completed steps
        'download_db'    => ['status' => 'done', 'time' => 12],
        'import_db'      => ['status' => 'running', 'time' => 0],
    ],
    'error'        => '',
    'started'      => 1718472622,
    'retries'      => 0,                  // auto-retry count
]
```

### Auto-Retry
- If a step fails, increment `retries` and reschedule cron
- Max 3 retries per step
- After 3 failures, mark restore as failed with error message

### Files to Create/Modify (Pro)
- `class-wps3b-pro-restore.php` — add chunked import/extract methods
- `class-wps3b-pro.php` — register background restore cron handler override

---

## Implementation Order

### Phase 1 — v1.5.0 (ship before migration)
Priority: make restore work on hosted servers.

1. **Background restore in free plugin** — port the pattern from Pro's external restore
2. **URL replacement in free plugin** — move `search_replace_urls()` and `recursive_unserialize_replace()` from Pro to `class-wps3b-restore.php`
3. **URL fields on restore confirmation** — old URL (from manifest) + new URL (from current site)
4. **Enhanced environment check** — disk space, memory, extensions, writable checks
5. **Post-restore validation** — checklist after restore completes
6. **Manifest additions** — PHP extensions, memory limit, MySQL version, sizes

### Phase 2 — v2.0.0 (post-migration)
Polish and Pro features.

7. **Migration wizard UI** (Pro) — dedicated Migrate page with step-by-step flow
8. **Chunked restore with resume** (Pro) — database + file extraction chunking
9. **Auto-retry on failure** (Pro) — resilient restore for unreliable hosts

---

## File Changes Summary

### Free Plugin — v1.5.0

| File | Change |
|------|--------|
| `wp-s3-backup.php` | Bump version to 1.5.0 |
| `class-wps3b-restore.php` | Add background restore, URL search-replace (moved from Pro), enhanced env check, post-restore validation |
| `class-wps3b-backup-engine.php` | Add PHP extensions, memory_limit, MySQL version, sizes to manifest |
| `class-wps3b-settings.php` | Add AJAX endpoint for restore status polling |
| `class-wps3b-plugin.php` | Register background restore cron hook |
| `admin/js/admin.js` | Add restore progress polling + validation display |
| `admin/views/backups-page.php` | Restore progress UI, URL fields, enhanced compatibility display, validation checklist |
| `admin/css/admin.css` | Restore progress styles, validation checklist styles |
| `readme.txt` | Changelog for v1.5.0 |

### Pro Plugin — v2.0.0

| File | Change |
|------|--------|
| `wp-s3-backup-pro.php` | Bump version to 2.0.0 |
| `class-wps3b-pro-restore.php` | Remove `search_replace_urls()` (now in free), add chunked restore, auto-retry |
| `class-wps3b-pro.php` | Register migrate page, override restore cron with chunked version |
| `admin/views/migrate-page.php` | New — migration wizard UI |
| `admin/js/pro-migrate.js` | New — wizard step logic |
| `admin/css/pro-migrate.css` | New — wizard styles |
| `admin/js/pro-admin.js` | Update for chunked restore progress |

---

## What Stays in Pro

To be clear — here's the free vs. Pro split after v2.0:

### Free (BackForge)
- Full site backup to S3
- Full site restore (background, non-chunked)
- URL search-replace on restore
- Enhanced environment check
- Post-restore validation
- Scheduled backups
- Activity log

### Pro (BackForge Pro)
- Selective restore (DB only / files only)
- Cross-site restore (another S3 prefix)
- Upload & restore (local files)
- Migration wizard (guided multi-step UI)
- Chunked restore with resume
- Auto-retry on failure
- Encrypted backups
- Email/Slack notifications
- Storage class management
- Cost estimates
- Incremental backups
- Custom schedules
