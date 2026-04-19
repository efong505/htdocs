# Updating WordPress for Public Access

## Overview

WordPress stores its site URL as absolute values in the database. When going public, every reference to `localhost/bitbybit` must be updated to `edwardfong.onthewifi.com/bitbybit`.

---

## Step 1: Update wp-config.php

Open `c:\xampp\htdocs\bitbybit\wp-config.php` in a text editor.

Add these two lines **right after the opening `<?php` tag** (before any other code):

```php
define('WP_HOME','http://edwardfong.onthewifi.com/bitbybit');
define('WP_SITEURL','http://edwardfong.onthewifi.com/bitbybit');
```

This overrides the `home` and `siteurl` values in the database, so WordPress knows its public address.

### What These Do

| Constant | Purpose |
|----------|---------|
| `WP_HOME` | The URL visitors use to reach your site (front-end) |
| `WP_SITEURL` | The URL where WordPress core files live (back-end) |

Save the file.

---

## Step 2: Run Database Search & Replace

Even after updating wp-config.php, images, internal links, and other content in the database still reference `localhost/bitbybit`. These need to be updated.

### Option A: Using MySQL Command Line

1. Open Command Prompt
2. Run:

```cmd
cd c:\xampp\mysql\bin && mysql.exe -u root bitbybit_db
```

3. Run these SQL queries one at a time:

```sql
-- Update post content (pages, posts, blocks)
UPDATE d9_posts
SET post_content = REPLACE(post_content, 'http://localhost/bitbybit', 'http://edwardfong.onthewifi.com/bitbybit')
WHERE post_content LIKE '%localhost/bitbybit%';

-- Update post GUIDs (attachment URLs)
UPDATE d9_posts
SET guid = REPLACE(guid, 'http://localhost/bitbybit', 'http://edwardfong.onthewifi.com/bitbybit')
WHERE guid LIKE '%localhost/bitbybit%';

-- Update post meta (custom fields, featured images, etc.)
UPDATE d9_postmeta
SET meta_value = REPLACE(meta_value, 'http://localhost/bitbybit', 'http://edwardfong.onthewifi.com/bitbybit')
WHERE meta_value LIKE '%localhost/bitbybit%';

-- Update options (widgets, theme settings, etc.)
UPDATE d9_options
SET option_value = REPLACE(option_value, 'http://localhost/bitbybit', 'http://edwardfong.onthewifi.com/bitbybit')
WHERE option_value LIKE '%localhost/bitbybit%';

-- Update comments
UPDATE d9_comments
SET comment_content = REPLACE(comment_content, 'http://localhost/bitbybit', 'http://edwardfong.onthewifi.com/bitbybit')
WHERE comment_content LIKE '%localhost/bitbybit%';
```

4. Type `exit` to leave MySQL

### Option B: Using Better Search Replace Plugin (Already Installed)

1. Log into WordPress admin: `http://edwardfong.onthewifi.com/bitbybit/wp-admin`
   - (If you can't log in from the public URL yet, use `http://localhost/bitbybit/wp-admin`)
2. Go to **Tools → Better Search Replace**
3. Fill in:
   - **Search for**: `http://localhost/bitbybit`
   - **Replace with**: `http://edwardfong.onthewifi.com/bitbybit`
   - **Select tables**: Select ALL tables
   - **Check "Replace GUIDs"**: ✅ Yes
   - **Dry Run**: ✅ Check this first to preview changes
4. Click **Run Search/Replace**
5. Review the dry run results
6. If it looks correct, **uncheck Dry Run** and run again for real

### Option C: Using phpMyAdmin

1. Go to `http://localhost/phpmyadmin`
2. Select the `bitbybit_db` database
3. Click the **SQL** tab
4. Paste and run the same SQL queries from Option A

---

## Step 3: Verify

1. Clear your browser cache
2. Visit `http://edwardfong.onthewifi.com/bitbybit`
3. Check that:
   - The site loads correctly
   - Images display properly (not broken)
   - Internal links point to the public domain, not localhost
   - The admin dashboard works at `/wp-admin`

---

## Reverting Back to Localhost

If you need to switch back to local development:

1. Update `wp-config.php`:
```php
define('WP_HOME','http://localhost/bitbybit');
define('WP_SITEURL','http://localhost/bitbybit');
```

2. Run the search & replace in reverse:
```sql
UPDATE d9_posts SET post_content = REPLACE(post_content, 'http://edwardfong.onthewifi.com/bitbybit', 'http://localhost/bitbybit') WHERE post_content LIKE '%edwardfong.onthewifi.com/bitbybit%';
UPDATE d9_posts SET guid = REPLACE(guid, 'http://edwardfong.onthewifi.com/bitbybit', 'http://localhost/bitbybit') WHERE guid LIKE '%edwardfong.onthewifi.com/bitbybit%';
UPDATE d9_postmeta SET meta_value = REPLACE(meta_value, 'http://edwardfong.onthewifi.com/bitbybit', 'http://localhost/bitbybit') WHERE meta_value LIKE '%edwardfong.onthewifi.com/bitbybit%';
UPDATE d9_options SET option_value = REPLACE(option_value, 'http://edwardfong.onthewifi.com/bitbybit', 'http://localhost/bitbybit') WHERE option_value LIKE '%edwardfong.onthewifi.com/bitbybit%';
```

---

## Important Notes

- **Serialized data**: The SQL `REPLACE` method can break serialized data in the `d9_options` table (used by plugins/widgets). The Better Search Replace plugin (Option B) handles serialized data correctly. If you notice broken widgets or plugin settings after using raw SQL, use the plugin to fix them.
- **Theme template files**: The child theme files (`index.php` and `archive-modules.php`) use `wp_get_attachment_url()` which pulls from the database dynamically — no manual changes needed in those files.
- **HTTPS**: If you later add SSL, you'll need to repeat this process replacing `http://` with `https://`.
