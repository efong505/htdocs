# Portal Implementation Overview

## Project Summary

A dynamic landing page portal served via XAMPP at `edwardfong.onthewifi.com` that acts as a central hub linking to all sites hosted in the `htdocs` directory. The portal includes a password-protected admin panel for managing site links backed by MySQL.

---

## Architecture

```
c:\xampp\htdocs\
├── portal/                  ← Portal application
│   ├── assets/
│   │   └── style.css        ← Dark modern theme
│   ├── admin.php            ← Admin panel (login + CRUD interface)
│   ├── api.php              ← AJAX backend for site management
│   ├── config.php           ← Database connection & auth credentials
│   ├── index.php            ← Public landing page
│   └── setup.php            ← One-time database setup script
├── bitbybit/                ← WordPress site
├── dashboard-backup/        ← Backup of original XAMPP dashboard
├── Portal Docs/             ← This documentation
└── index.php                ← Root redirect → /portal/
```

---

## Database

- **Database name**: `portal_db`
- **Table**: `sites`

| Column      | Type         | Description                          |
|-------------|--------------|--------------------------------------|
| id          | INT (PK)     | Auto-increment ID                    |
| title       | VARCHAR(255) | Display name of the site             |
| description | TEXT         | Short description shown on the card  |
| url         | VARCHAR(500) | Relative or absolute URL to the site |
| icon        | VARCHAR(100) | Font Awesome icon class (e.g. fa-code) |
| color       | VARCHAR(7)   | Hex accent color (e.g. #6366f1)      |
| sort_order  | INT          | Lower numbers display first          |
| is_active   | TINYINT(1)   | 1 = visible on public page, 0 = hidden |
| created_at  | TIMESTAMP    | Auto-set on creation                 |

---

## Portal Files

### config.php
- Database connection settings (host, name, user, password)
- Admin login credentials (`ADMIN_USER`, `ADMIN_PASS`)
- `db_connect()` — returns a singleton PDO connection
- `check_auth()` — session-based authentication check

### setup.php
- Creates the `portal_db` database and `sites` table
- Run once at `http://localhost/portal/setup.php`
- Safe to re-run (uses `CREATE IF NOT EXISTS`)

### api.php
- `GET ?action=list` — public endpoint, returns active sites as JSON
- `GET ?action=admin_list` — authenticated, returns all sites
- `POST action=add` — add a new site
- `POST action=update` — update an existing site
- `POST action=delete` — delete a site by ID
- `POST action=reorder` — bulk update sort order

### index.php (portal)
- Fetches sites from `api.php?action=list` via JavaScript
- Renders cards with icon, title, description, and accent color
- Dark theme with gradient hero, glow effects, hover animations

### admin.php
- Login form (session-based auth)
- Add/Edit form with fields: title, URL, description, icon, color, sort order, active toggle
- Live icon preview as you type
- Sites list with edit/delete actions
- Inline status badges (Active/Inactive)

---

## URLs

| URL | Purpose |
|-----|---------|
| `http://localhost/` | Redirects to `/portal/` |
| `http://localhost/portal/` | Public landing page |
| `http://localhost/portal/admin.php` | Admin panel |
| `http://localhost/portal/setup.php` | Database setup (run once) |
| `http://localhost/portal/api.php?action=list` | Public API endpoint |

---

## Admin Credentials (Default)

- **Username**: `admin`
- **Password**: `changeme123`
- **Location**: `c:\xampp\htdocs\portal\config.php`

> **IMPORTANT**: Change these before making the site publicly accessible.

---

## What Was Changed

### Root index.php
- **Before**: Redirected to `/dashboard/`
- **After**: Redirects to `/portal/`
- **Backup**: Original XAMPP dashboard backed up to `c:\xampp\htdocs\dashboard-backup\`

### WordPress (bitbybit) Fixes
During the implementation, the following issues were discovered and fixed:

1. **Database GUIDs**: 165 post GUIDs in `d9_posts` still referenced the old staging domain `bitbybitcoding-com.stackstaging.com`. These were updated to `localhost/bitbybit` via SQL.

2. **Theme template files**: Two child theme files had the old domain hardcoded:
   - `wp-content/themes/inspiro-child/index.php` — line 24
   - `wp-content/themes/inspiro-child/archive-modules.php` — line 18

   Both were updated to use `wp_get_attachment_url(99)` so the image URL is pulled dynamically from the database, making it portable across domains.
