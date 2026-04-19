# BackForge Command — Add-on Implementation Plan

*Created: April 17, 2026*

---

## Overview

BackForge Command is an add-on plugin for BackForge Pro that provides centralized multi-site backup management. It allows agencies, freelancers, and site managers to control backups across all their WordPress sites from a single dashboard.

**Product positioning:** "Manage all your BackForge sites from one place."

---

## Product Structure

```
Customer's Hub Site (e.g., agency-dashboard.com)
├── BackForge (Free)              ← Base plugin
├── BackForge Pro                 ← Pro features + API endpoints
└── BackForge Command (Add-on)    ← Hub dashboard UI

Customer's Managed Sites (e.g., client1.com, client2.com)
├── BackForge (Free)              ← Base plugin
└── BackForge Pro                 ← Pro features + API endpoints
```

### Requirements
- BackForge Pro must be installed and licensed on the **hub site**
- BackForge Pro must be installed and licensed on each **managed site**
- The Command add-on only installs on the hub site
- Managed sites don't need the Command add-on — they just need Pro (which provides the API)

### Pricing Model

| Option | Price | Includes |
|--------|-------|----------|
| Command Add-on (standalone) | $99/yr | Unlimited connected sites |
| Agency Pro tier | $199/yr | Includes Command add-on free |

This creates a natural upgrade path:
- Personal ($49) → manages 1 site, no command center needed
- Professional ($99) → manages up to 5 sites, can add Command for $99
- Agency ($199) → unlimited sites + Command included

---

## Architecture

### Two Components

**1. API Endpoints (built into BackForge Pro)**
Every BackForge Pro site exposes a REST API that can be called remotely. This is the "agent" that runs on each managed site.

**2. Command Dashboard (the add-on plugin)**
Installs on one hub site. Provides the UI to register sites, view status, trigger actions, and monitor all connected sites.

### Communication Flow

```
Hub Site (Command Add-on)                    Managed Site (Pro)
        │                                           │
        │  POST /backforge/v1/backup/start          │
        │ ─────────────────────────────────────────► │  → Triggers background backup
        │                                           │
        │  GET /backforge/v1/backup/status           │
        │ ─────────────────────────────────────────► │  → Returns current backup progress
        │                                           │
        │  GET /backforge/v1/backups                 │
        │ ─────────────────────────────────────────► │  → Returns list of all backups
        │                                           │
        │  POST /backforge/v1/restore                │
        │ ─────────────────────────────────────────► │  → Triggers restore from backup
        │                                           │
        │  GET /backforge/v1/health                  │
        │ ─────────────────────────────────────────► │  → Returns site health info
        │                                           │
        │  POST /backforge/v1/backup/delete          │
        │ ─────────────────────────────────────────► │  → Deletes a backup
        │                                           │
```

### Authentication

Each managed site generates a unique **API key** in its BackForge Pro settings. The hub site stores this key when registering the site. All API requests include the key in an `X-BackForge-Key` header.

```
X-BackForge-Key: bf_live_a1b2c3d4e5f6g7h8i9j0...
```

API keys are:
- 64-character random strings prefixed with `bf_live_`
- Generated via `wp_generate_password(64, false)`
- Stored encrypted in the managed site's wp_options (same AES-256-CBC as credentials)
- Can be regenerated at any time (invalidates old key)
- One key per site (not per user)

---

## Part 1: API Endpoints (BackForge Pro)

These endpoints get added to BackForge Pro. They are the foundation — needed regardless of whether the user has the Command add-on.

### Endpoint Specifications

#### POST /backforge/v1/backup/start
Triggers a background backup on the managed site.

**Request:** `X-BackForge-Key` header
**Response:**
```json
{
    "success": true,
    "message": "Backup started.",
    "timestamp": "2026-04-17-195920"
}
```

#### GET /backforge/v1/backup/status
Returns the current backup status (running, complete, error, or idle).

**Response:**
```json
{
    "running": true,
    "step": "upload_files",
    "message": "Uploading files to S3...",
    "progress": 65,
    "started": 1776455960,
    "steps": [
        "Database exported: 36 tables, 14,322 rows (616 KB)",
        "Files archived: 13,551 files (1 GB)",
        "Database uploaded to S3."
    ]
}
```
Or when idle:
```json
{
    "running": false,
    "last_backup": "2026-04-17 19:59:20",
    "last_error": ""
}
```

#### GET /backforge/v1/backups
Returns a list of all backups in S3 for this site.

**Response:**
```json
{
    "backups": [
        {
            "timestamp": "2026-04-17-195920",
            "date": "2026-04-17 19:59:20",
            "total_size": 1073741824,
            "total_size_formatted": "1 GB",
            "files": [
                { "key": "backups/site/2026-04-17-195920-db.sql.gz", "size": 645120, "storage_class": "STANDARD" },
                { "key": "backups/site/2026-04-17-195920-files.zip", "size": 1073096704, "storage_class": "STANDARD" },
                { "key": "backups/site/2026-04-17-195920-manifest.json", "size": 2048, "storage_class": "STANDARD" }
            ]
        }
    ],
    "total_count": 5,
    "total_storage": 5368709120,
    "total_storage_formatted": "5 GB"
}
```

#### POST /backforge/v1/restore
Triggers a restore from a specific backup.

**Request body:**
```json
{
    "timestamp": "2026-04-17-195920",
    "type": "full",
    "old_url": "",
    "new_url": ""
}
```
**Response:**
```json
{
    "success": true,
    "message": "Restore started."
}
```

#### POST /backforge/v1/backup/delete
Deletes a backup by timestamp.

**Request body:**
```json
{
    "timestamp": "2026-04-17-195920"
}
```

#### GET /backforge/v1/health
Returns site health information for the dashboard overview.

**Response:**
```json
{
    "site_url": "https://client1.com",
    "site_name": "Client Site 1",
    "wordpress_version": "6.7.1",
    "php_version": "8.2.15",
    "backforge_version": "1.0.0",
    "backforge_pro_version": "1.0.0",
    "last_backup": "2026-04-17 19:59:20",
    "last_backup_ago": "2 hours ago",
    "last_error": "",
    "schedule_enabled": true,
    "schedule_frequency": "daily",
    "next_scheduled": "2026-04-18 03:00:00",
    "total_backups": 5,
    "total_storage": 5368709120,
    "total_storage_formatted": "5 GB",
    "s3_connected": true,
    "encryption_enabled": false,
    "incremental_enabled": true
}
```

### API Key Management (Pro Settings)

Add to the BackForge Pro License page:

```
┌─ Remote API Access ────────────────────────────────────┐
│                                                         │
│  API Key: bf_live_a1b2****************************j0    │
│                                                         │
│  [Regenerate Key]  [Copy Key]                          │
│                                                         │
│  Status: ● Enabled                                     │
│  Last used: Apr 17, 2026 at 7:59 PM from 192.168.1.5  │
│                                                         │
│  ☐ Restrict to specific IP addresses                   │
│  (Enter comma-separated IPs for extra security)        │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### Security Measures

1. **API key required** on every request
2. **Rate limiting** — 60 requests per minute per key
3. **IP restriction** (optional) — only accept requests from specified IPs
4. **HTTPS enforced** — reject requests over plain HTTP
5. **Key rotation** — regenerate key at any time, old key immediately invalid
6. **Audit log** — log all API requests with IP, action, and timestamp
7. **Capability check** — API key maps to `manage_options` capability
8. **No sensitive data in responses** — never return credentials, encryption passwords, or raw file contents

---

## Part 2: Command Add-on Plugin

### File Structure

```
backforge-command/
├── backforge-command.php              # Main file — dependency check, bootstrap
├── includes/
│   ├── class-bfc-plugin.php           # Main class — init, menus, cron
│   ├── class-bfc-sites.php            # Site registration, connection testing
│   ├── class-bfc-api-client.php       # HTTP client for calling managed site APIs
│   └── class-bfc-dashboard.php        # Dashboard data aggregation
├── admin/
│   ├── views/
│   │   ├── dashboard.php              # Main command center dashboard
│   │   ├── sites-list.php             # List of connected sites
│   │   ├── site-detail.php            # Single site detail + actions
│   │   └── settings.php               # Command add-on settings
│   ├── css/
│   │   └── command.css                # Dark SaaS UI (extends BackForge styles)
│   └── js/
│       └── command.js                 # Dashboard interactions, polling, actions
└── languages/
    └── backforge-command.pot
```

### Database

One custom table for registered sites:

```sql
CREATE TABLE {prefix}bfc_sites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_url VARCHAR(500) NOT NULL,
    site_name VARCHAR(255),
    api_key_encrypted TEXT NOT NULL,
    status ENUM('active','unreachable','error','paused') DEFAULT 'active',
    last_health JSON,
    last_checked DATETIME,
    last_backup DATETIME,
    last_error TEXT,
    sort_order INT DEFAULT 0,
    group_name VARCHAR(100) DEFAULT '',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    UNIQUE KEY idx_url (site_url(191)),
    KEY idx_status (status),
    KEY idx_group (group_name)
);
```

### Admin Menu Structure

```
BackForge
├── Dashboard          ← existing
├── Backups            ← existing
├── Command Center     ← NEW (add-on)
│   ├── Overview       ← all sites at a glance
│   ├── Sites          ← manage connected sites
│   └── Alerts         ← backup failures, overdue backups
├── Logs               ← existing
├── License            ← existing (Pro)
└── Storage            ← existing (Pro)
```

### Dashboard UI Design

#### Command Center Overview

```
┌─────────────────────────────────────────────────────────────────┐
│  ☁️  BackForge Command Center                    [Add Site]     │
└─────────────────────────────────────────────────────────────────┘

┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐
│ 12       │  │ 11       │  │ 1        │  │ $2.34    │
│ Sites    │  │ Healthy  │  │ Alert    │  │ Est.Cost │
│ Connected│  │          │  │          │  │ /month   │
└──────────┘  └──────────┘  └──────────┘  └──────────┘

┌─ All Sites ─────────────────────────────────────────────────────┐
│                                                                  │
│  🟢 client1.com          Last backup: 2h ago    1.2 GB  [⟳] [⋮]│
│  🟢 client2.com          Last backup: 5h ago    890 MB  [⟳] [⋮]│
│  🟢 mysite.com           Last backup: 1d ago    2.1 GB  [⟳] [⋮]│
│  🟡 staging.client3.com  Last backup: 3d ago    450 MB  [⟳] [⋮]│
│  🔴 oldsite.com          Last backup: FAILED    1.8 GB  [⟳] [⋮]│
│                                                                  │
│  [⟳] = Trigger backup now                                       │
│  [⋮] = More actions (view backups, restore, settings, remove)   │
│                                                                  │
└──────────────────────────────────────────────────────────────────┘
```

#### Site Status Indicators

| Status | Color | Meaning |
|--------|-------|---------|
| 🟢 Healthy | Green | Last backup within schedule, no errors |
| 🟡 Warning | Amber | Backup overdue (>2x schedule interval) or API slow |
| 🔴 Error | Red | Last backup failed or site unreachable |
| ⚪ Paused | Gray | Monitoring paused by user |

#### Site Detail View

Clicking a site opens a detail view showing:
- Site health info (WP version, PHP, BackForge version)
- Backup history (same card layout as local backups page)
- Storage usage and cost estimate
- Schedule configuration
- Quick actions: Backup Now, Restore, View Logs
- Connection status and last API response time

#### Add Site Flow

```
Step 1: Enter site URL
┌─────────────────────────────────────────┐
│  Site URL: [https://client1.com      ]  │
│  API Key:  [bf_live_a1b2c3d4...      ]  │
│                                         │
│  [Test Connection]  [Add Site]          │
└─────────────────────────────────────────┘

Step 2: Test connection verifies:
  ✅ Site is reachable
  ✅ BackForge Pro is installed
  ✅ API key is valid
  ✅ API version is compatible

Step 3: Site added to dashboard
```

### Background Monitoring

A wp-cron job runs every 15 minutes on the hub site:

1. Iterates all registered sites
2. Calls `GET /backforge/v1/health` on each
3. Updates `last_health`, `last_checked`, `last_backup` in the database
4. If a site is unreachable 3 times in a row → status = 'unreachable'
5. If last backup is overdue → status = 'warning' (shown as amber)
6. If last backup failed → status = 'error' (shown as red)

### Alert System

Alerts are generated by the monitoring cron and displayed on the Command Center:

| Alert Type | Trigger | Severity |
|------------|---------|----------|
| Backup overdue | Last backup > 2x schedule interval | Warning |
| Backup failed | Last backup status = error | Error |
| Site unreachable | 3 consecutive failed health checks | Error |
| Storage threshold | Total storage > configurable limit | Warning |
| Version mismatch | BackForge version differs from hub | Info |

Alerts can optionally trigger:
- Email notification to the hub admin
- Slack/webhook notification (reuses Pro notification system)

### Bulk Actions

From the Command Center overview:
- **Backup All** — triggers backup on all connected sites simultaneously
- **Backup Selected** — checkbox selection + backup button
- **Group actions** — backup all sites in a group (e.g., "Production", "Staging", "Client Sites")

### Site Groups

Sites can be organized into groups for easier management:
- Default groups: "Ungrouped"
- Custom groups: "Production", "Staging", "Client Sites", etc.
- Filter the dashboard by group
- Bulk actions per group

---

## Implementation Order

### Phase 1: API Endpoints (BackForge Pro)
*Estimated effort: 1 session*

1. Add API key generation + management to Pro License page
2. Register REST API routes in BackForge Pro
3. Implement authentication middleware (key validation, rate limiting)
4. Implement endpoints: health, backup/start, backup/status, backups, backup/delete
5. Implement restore endpoint
6. Add API request logging

### Phase 2: Command Add-on — Core
*Estimated effort: 1-2 sessions*

1. Create plugin skeleton with dependency check (requires BackForge Pro)
2. Create database table on activation
3. Build site registration (add, test, remove)
4. Build API client class (HTTP wrapper with key auth)
5. Build dashboard overview page (site list with status)
6. Build site detail page (health info, backup list, actions)
7. Implement Backup Now action (trigger remote backup + poll status)

### Phase 3: Command Add-on — Monitoring & Alerts
*Estimated effort: 1 session*

1. Build background health check cron (every 15 minutes)
2. Implement status calculation logic (healthy/warning/error)
3. Build alerts page
4. Connect alerts to Pro notification system (email/Slack)
5. Add bulk actions (backup all, backup selected)

### Phase 4: Command Add-on — Polish
*Estimated effort: 1 session*

1. Site groups (create, assign, filter)
2. Dark SaaS UI matching BackForge brand
3. Responsive design
4. Connection diagnostics (latency, API version check)
5. Export/import site list
6. Documentation

---

## Security Considerations

### API Key Security
- Keys stored encrypted (AES-256-CBC) on both hub and managed sites
- Keys never logged or exposed in error messages
- Key regeneration invalidates immediately
- Optional IP whitelist per site

### Network Security
- HTTPS required for all API communication
- Request timeout: 30 seconds for health checks, 60 seconds for actions
- Retry logic with exponential backoff (3 retries)
- No sensitive data in API responses (no credentials, no file contents)

### Access Control
- Command add-on requires `manage_options` capability
- API endpoints require valid API key
- Each API key is scoped to one site
- Audit trail of all remote actions

### Data Privacy
- Hub site only stores: site URL, encrypted API key, cached health data
- No backup data passes through the hub — backups go directly to S3
- Health data is metadata only (versions, sizes, timestamps)
- Managed sites can revoke access at any time by regenerating their API key

---

## Competitive Positioning

### vs MainWP
- MainWP is a full site management platform (updates, security, performance)
- BackForge Command is focused exclusively on backup management
- Lighter, simpler, purpose-built for the backup workflow
- No "worker plugin" needed — BackForge Pro IS the worker

### vs ManageWP
- ManageWP is SaaS — your data goes through their servers
- BackForge Command is self-hosted — direct site-to-site communication
- No monthly SaaS fee, no vendor dependency
- Backup data stays in your S3 bucket, never touches a third party

### vs InfiniteWP
- Similar self-hosted model, but InfiniteWP is broader (updates, themes, plugins)
- BackForge Command is laser-focused on backup orchestration
- Cleaner UI, modern dark SaaS design
- Built on the same S3-native architecture as BackForge

### Unique Value Proposition
"The only self-hosted backup command center that talks directly to your S3 bucket. No SaaS middleman. No vendor lock-in. Just your sites, your backups, your control."

---

## Pricing Strategy

### Standalone Purchase
| Tier | Price | Sites |
|------|-------|-------|
| Command Add-on | $99/yr | Unlimited connected sites |

### Bundle with Agency
| Tier | Price | Includes |
|------|-------|----------|
| Agency Pro | $199/yr | Unlimited site licenses + Command add-on |

### Why This Works
- Personal users ($49) don't need it — they have 1 site
- Professional users ($99, 5 sites) might want it — $99 add-on is reasonable
- Agency users ($199, unlimited) get it free — biggest incentive to go Agency
- The add-on creates a reason to upgrade from Professional to Agency

---

## Future Expansion (Post-Launch)

These are NOT in the initial build but are natural extensions:

1. **Scheduled bulk backups** — "Backup all production sites every night at 2 AM"
2. **Backup comparison** — compare two sites' backups (useful for staging vs production)
3. **One-click cloning** — backup Site A, restore to Site B (migration workflow)
4. **Uptime monitoring** — ping sites every 5 minutes, alert on downtime
5. **Update management** — show available WordPress/plugin updates across all sites
6. **Client reports** — generate PDF reports showing backup status for client billing
7. **White-label** — remove BackForge branding for agency resale
8. **Mobile app** — push notifications for backup failures (very future)

---

## Technical Notes

### WordPress REST API Namespace
```
backforge/v1/          ← API endpoints on managed sites
```

### Plugin Slug
```
backforge-command       ← Add-on plugin slug
```

### Text Domain
```
backforge-command       ← For i18n
```

### Class Prefix
```
BFC_                    ← BackForge Command
```

### Database Table Prefix
```
bfc_sites               ← Registered sites
```

### Hooks for Extensibility
```php
// Filters
apply_filters( 'bfc_health_check_interval', 900 );          // Default 15 min
apply_filters( 'bfc_overdue_threshold_multiplier', 2 );      // 2x schedule = overdue
apply_filters( 'bfc_api_timeout', 30 );                      // Request timeout
apply_filters( 'bfc_max_retries', 3 );                       // Retry count

// Actions
do_action( 'bfc_site_added', $site_id, $site_url );
do_action( 'bfc_site_removed', $site_id, $site_url );
do_action( 'bfc_backup_triggered', $site_id );
do_action( 'bfc_alert_created', $alert_type, $site_id );
do_action( 'bfc_health_check_complete', $results );
```

---

## Dependencies

```
BackForge Command Add-on
    └── Requires: BackForge Pro (on hub site)
        └── Requires: BackForge Free (on hub site)

Each Managed Site
    └── Requires: BackForge Pro (provides API endpoints)
        └── Requires: BackForge Free
```

---

## Next Steps

1. ⬜ Build API endpoints into BackForge Pro (Phase 1)
2. ⬜ Build Command add-on plugin skeleton (Phase 2)
3. ⬜ Build dashboard UI with dark SaaS theme (Phase 2)
4. ⬜ Build monitoring cron + alerts (Phase 3)
5. ⬜ Build bulk actions + site groups (Phase 4)
6. ⬜ Add Command add-on as a product in LicenseForge
7. ⬜ Create pricing page and marketing copy
8. ⬜ Test with 3+ connected sites
