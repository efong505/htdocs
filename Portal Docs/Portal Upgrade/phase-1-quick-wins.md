# Phase 1 — Quick Wins

## Overview

Visual enhancements and small functional features that require minimal database changes.

---

## 1. Animated Gradient Background

**Files modified**: `portal/assets/style.css`

Replace the static `.bg-glow` radial gradient with animated floating gradient blobs using CSS keyframe animations. Multiple semi-transparent gradient circles move slowly across the background, creating a living, dynamic feel without impacting performance.

---

## 2. Dark/Light Mode Toggle

**Files modified**: `portal/index.php`, `portal/assets/style.css`

- Add a toggle button (sun/moon icon) in the portal header
- Define CSS custom properties for both themes under `:root` (dark) and `[data-theme="light"]`
- Store the user's preference in `localStorage` so it persists across visits
- All existing colors switch via CSS variables — no layout changes needed

---

## 3. Search/Filter Bar

**Files modified**: `portal/index.php`

- Add a search input above the sites grid
- JavaScript filters cards in real-time as the user types
- Matches against title and description
- Shows a "No results" message when nothing matches
- No backend changes needed — filtering happens client-side on the already-loaded data

---

## 4. Click Analytics

**Files modified**: `portal/index.php`, `portal/admin.php`, `portal/api.php`
**Database changes**: Add `click_count` column to `sites` table

- Each site card link goes through a tracking endpoint (`api.php?action=track&id=X`) that increments the click count then redirects to the actual URL
- Admin panel shows click count per site in the managed sites list
- Optional: reset click count button in admin

### Database Migration

```sql
ALTER TABLE sites ADD COLUMN click_count INT DEFAULT 0 AFTER is_active;
```

---

## 5. Visitor Counter

**Files modified**: `portal/index.php`, `portal/api.php`, `portal/admin.php`
**Database changes**: New `visitors` table

- Tracks unique visitors by IP + date
- Displays total visitor count in the portal footer ("X visitors")
- Admin panel shows visitor stats (today, this week, total)

### Database Migration

```sql
CREATE TABLE visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    visit_date DATE NOT NULL,
    visit_count INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_visit (ip_address, visit_date)
);
```

---

## Implementation Order

1. Database migrations (click_count column + visitors table)
2. Animated gradient background (CSS only)
3. Dark/light mode toggle (CSS + small JS)
4. Search/filter bar (HTML + JS)
5. Click analytics (API + frontend)
6. Visitor counter (API + frontend + admin)

---

## Rollback

If any feature causes issues:
- CSS changes: revert `style.css` to previous version
- Database: `ALTER TABLE sites DROP COLUMN click_count;` and `DROP TABLE visitors;`
- Frontend: remove the added HTML/JS blocks from `index.php` and `admin.php`
