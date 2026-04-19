# BackForge Command Add-on — Session Handoff

## Context

This is a handoff document for starting a new session to build the **BackForge Command** add-on plugin. This add-on provides centralized multi-site backup management from a single WordPress dashboard.

## What Already Exists

### BackForge Ecosystem (all built and working)

**BackForge (Free)** — `c:\xampp\htdocs\wp-s3-backup-project\plugin\wp-s3-backup\`
- WordPress backup plugin using direct S3 REST API (no SDK)
- S3-compatible endpoints (AWS, Backblaze B2, Wasabi, DigitalOcean Spaces, MinIO)
- Background backup via wp-cron with real-time progress polling
- Dark SaaS admin UI with teal accents (#0F172A / #1E293B / #14B8A6)
- Card-based layout, integrated backup progress card, AJAX delete

**BackForge Pro** — `c:\xampp\htdocs\wp-s3-backup-project\plugin\wp-s3-backup-pro\`
- 14 features: license system, notifications, encryption, storage management, cost estimates, selective restore, URL replacement, cross-site restore, upload & restore, custom schedules, incremental backups, S3 prefix browser
- Extends free via hooks/filters, gated behind license validation

**LicenseForge** — `c:\xampp\htdocs\wp-license-platform\plugin\wp-license-platform\`
- Self-hosted license & sales platform with PayPal, VAT compliance, customer portal
- REST API for license validation/activation/deactivation
- Deployed on ekewaka site with test license active

### Documentation
- `docs/13-PROJECT-STATUS.md` — Complete feature reference for all plugins
- `docs/14-COMPETITIVE-RESEARCH-NAMING.md` — Market research and brand strategy
- `docs/15-COMMAND-ADDON-PLAN.md` — **Full implementation plan for this add-on**

### Memory Bank
- `.amazonq/rules/memory-bank/` — Guidelines, product overview, structure, tech stack

## What Needs to Be Built

### Phase 1: API Endpoints (add to BackForge Pro)
Add REST API endpoints to BackForge Pro so each Pro site can be managed remotely:

```
POST /backforge/v1/backup/start    — trigger background backup
GET  /backforge/v1/backup/status   — get current backup progress
GET  /backforge/v1/backups         — list all backups
POST /backforge/v1/restore         — trigger restore
POST /backforge/v1/backup/delete   — delete a backup
GET  /backforge/v1/health          — site health info
```

Authentication via API key in `X-BackForge-Key` header. Key management UI on the Pro License page.

### Phase 2: Command Add-on Plugin
New plugin: `backforge-command/`

- Installs on one "hub" site
- Registers managed sites by URL + API key
- Dashboard showing all sites with status (healthy/warning/error)
- Per-site detail view with backup history and actions
- Trigger backup/restore on any connected site
- Background monitoring cron (every 15 min)

### Phase 3: Alerts & Bulk Actions
- Alert system for overdue/failed/unreachable sites
- Email/Slack notifications via Pro's existing system
- Bulk backup (all sites or selected)
- Site groups for organization

## Key Technical Details

- Dark SaaS UI must match BackForge brand (see `admin/css/admin.css` for the design system)
- All CSS uses `--bf-*` custom properties
- Class prefix for Command: `BFC_`
- Database table: `{prefix}bfc_sites`
- Plugin requires BackForge Pro on hub site
- Managed sites require BackForge Pro (for API endpoints)
- API keys: 64-char random, `bf_live_` prefix, stored encrypted

## Session Prompt

```
I'm building the BackForge Command add-on — a multi-site backup management 
dashboard for the BackForge WordPress backup plugin ecosystem.

The full implementation plan is at:
c:\xampp\htdocs\wp-s3-backup-project\docs\15-COMMAND-ADDON-PLAN.md

The existing plugins are at:
- Free: c:\xampp\htdocs\wp-s3-backup-project\plugin\wp-s3-backup\
- Pro:  c:\xampp\htdocs\wp-s3-backup-project\plugin\wp-s3-backup-pro\

Project status doc: c:\xampp\htdocs\wp-s3-backup-project\docs\13-PROJECT-STATUS.md
Memory bank: c:\xampp\htdocs\.amazonq\rules\memory-bank\

Please read the implementation plan and start with Phase 1 — adding the 
REST API endpoints to BackForge Pro.
```
