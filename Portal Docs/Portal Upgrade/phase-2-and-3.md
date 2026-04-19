# Phase 2 — Admin Upgrades

## Overview

Admin panel improvements that require database schema changes and more complex UI interactions.

---

## 6. Categories/Tags for Sites

**Files modified**: `portal/index.php`, `portal/admin.php`, `portal/api.php`, `portal/assets/style.css`
**Database changes**: New `categories` table, add `category_id` column to `sites` table

- Admin can create/manage categories (e.g., "WordPress Sites", "Projects", "Tools")
- Each site can be assigned to a category
- Public page shows filter buttons by category
- Clicking a category filters the cards

### Database Migration

```sql
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#6366f1',
    sort_order INT DEFAULT 0
);

ALTER TABLE sites ADD COLUMN category_id INT DEFAULT NULL AFTER color;
```

---

## 7. Drag-and-Drop Reorder

**Files modified**: `portal/admin.php`, `portal/api.php`
**Dependencies**: SortableJS library (CDN)

- Replace the numeric sort_order input with drag-and-drop in the admin sites list
- Uses SortableJS (lightweight, no jQuery needed)
- On drop, sends the new order to `api.php?action=reorder` (already exists)
- Sort order numbers update automatically in the database

---

## 8. Notes Field (Admin-Only)

**Files modified**: `portal/admin.php`, `portal/api.php`
**Database changes**: Add `admin_notes` column to `sites` table

- Textarea in the admin edit form for private notes
- Displayed only in admin, never on the public page
- Useful for tracking status like "needs update", "testing", etc.

### Database Migration

```sql
ALTER TABLE sites ADD COLUMN admin_notes TEXT DEFAULT NULL AFTER click_count;
```

---

## 9. Bulk Import (Scan htdocs)

**Files modified**: `portal/admin.php`, `portal/api.php`

- "Scan for Sites" button in admin
- Backend scans `C:\xampp\htdocs\` for directories containing `index.php` or `wp-config.php`
- Excludes known non-site directories (portal, dashboard-backup, Portal Docs, xampp, etc.)
- Shows discovered sites with checkboxes
- Selected sites are added to the portal with default settings
- Detects WordPress sites vs. plain PHP sites

---

## 10. Image Upload for Custom Site Icons

**Files modified**: `portal/index.php`, `portal/admin.php`, `portal/api.php`, `portal/assets/style.css`
**Database changes**: Add `custom_icon` column to `sites` table
**New directory**: `portal/uploads/`

- File upload field in admin form (accepts PNG, JPG, SVG)
- Uploaded images stored in `portal/uploads/`
- If a custom icon is uploaded, it displays instead of the Font Awesome icon
- Falls back to Font Awesome icon if no custom icon is set

### Database Migration

```sql
ALTER TABLE sites ADD COLUMN custom_icon VARCHAR(255) DEFAULT NULL AFTER icon;
```

---

## Implementation Order

1. Database migrations (all ALTER/CREATE statements)
2. Categories/tags
3. Notes field
4. Image upload for icons
5. Drag-and-drop reorder
6. Bulk import

---

# Phase 3 — Advanced Features

## Overview

Background tasks, monitoring, and more complex functionality.

---

## 11. Site Status Indicators (Live Ping)

**Files modified**: `portal/index.php`, `portal/api.php`, `portal/assets/style.css`

- JavaScript on the public page makes a lightweight HEAD request to each site URL
- Displays a green dot (online) or red dot (offline) on each card
- Requests are made client-side with a short timeout (3 seconds)
- Non-blocking — cards render immediately, status dots update asynchronously

---

## 12. Uptime Monitor with History

**Files modified**: `portal/admin.php`, `portal/api.php`
**Database changes**: New `uptime_logs` table
**New file**: `portal/cron.php` (called by Windows Task Scheduler)

- Background script (`cron.php`) pings each active site every 5 minutes
- Logs response time and status (up/down) to the database
- Admin panel shows uptime percentage and response time graph per site
- Alerts section in admin showing recent downtime events

### Database Migration

```sql
CREATE TABLE uptime_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    status ENUM('up', 'down') NOT NULL,
    response_time_ms INT DEFAULT NULL,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_site_checked (site_id, checked_at)
);
```

### Windows Scheduled Task

```cmd
schtasks /create /tn "Portal Uptime Monitor" /tr "C:\xampp\php\php.exe C:\xampp\htdocs\portal\cron.php" /sc minute /mo 5
```

---

## 13. Site Thumbnails/Screenshots

**Files modified**: `portal/index.php`, `portal/admin.php`, `portal/api.php`, `portal/assets/style.css`
**Database changes**: Add `thumbnail` column to `sites` table
**New directory**: `portal/thumbnails/`

Two approaches:

### Option A: Manual Upload
- Admin uploads a screenshot image per site
- Stored in `portal/thumbnails/`
- Displayed as a preview image on the card (hover or always visible)

### Option B: Auto-Generate (requires external service)
- Use a free screenshot API like `https://api.screenshotmachine.com` or `https://image.thum.io`
- Auto-fetch a thumbnail when a site is added
- Cache locally in `portal/thumbnails/`

### Database Migration

```sql
ALTER TABLE sites ADD COLUMN thumbnail VARCHAR(255) DEFAULT NULL AFTER custom_icon;
```

---

## Implementation Order

1. Database migrations
2. Site status indicators (client-side, no DB needed)
3. Site thumbnails (manual upload first)
4. Uptime monitor (DB + cron + admin UI)

---

## All Database Migrations (Combined)

Run all of these when ready to implement all phases:

```sql
USE portal_db;

-- Phase 1
ALTER TABLE sites ADD COLUMN click_count INT DEFAULT 0 AFTER is_active;

CREATE TABLE visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    visit_date DATE NOT NULL,
    visit_count INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_visit (ip_address, visit_date)
);

-- Phase 2
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#6366f1',
    sort_order INT DEFAULT 0
);

ALTER TABLE sites ADD COLUMN category_id INT DEFAULT NULL AFTER color;
ALTER TABLE sites ADD COLUMN custom_icon VARCHAR(255) DEFAULT NULL AFTER icon;
ALTER TABLE sites ADD COLUMN admin_notes TEXT DEFAULT NULL AFTER click_count;

-- Phase 3
ALTER TABLE sites ADD COLUMN thumbnail VARCHAR(255) DEFAULT NULL AFTER custom_icon;

CREATE TABLE uptime_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    status ENUM('up', 'down') NOT NULL,
    response_time_ms INT DEFAULT NULL,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_site_checked (site_id, checked_at)
);
```
