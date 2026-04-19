# WP S3 Backup — Restore Guide

## Overview

This guide covers two methods for restoring your WordPress site from an S3 backup:

1. **Automated Restore** — One-click restore from the plugin's Backups page
2. **Manual Restore** — Download files from S3 and restore using standard tools

Both methods use the same backup files:

| File | Contents | Used For |
|------|----------|----------|
| `db.sql.gz` | Gzipped SQL dump of all WordPress tables | Database restore |
| `files.zip` | Zip of entire `wp-content/` directory | Theme, plugin, and media restore |
| `manifest.json` | Backup metadata, checksums, compatibility info | Pre-restore verification |

---

## Method 1: Automated One-Click Restore (Plugin)

The plugin provides a single "Restore" button per backup that restores the entire site (database + files) in one operation. Individual files (db.sql.gz, files.zip, manifest.json) are shown as download links, but the Restore button handles everything together.

### Steps

1. Go to **S3 Backup → Backups** in the WordPress admin
2. Find the backup you want to restore
3. Click **Restore** next to the backup
4. The plugin downloads and reads `manifest.json` first
5. A confirmation screen shows:
   - Backup date and time
   - WordPress version of the backup vs. current
   - PHP version of the backup vs. current
   - Number of database tables and rows
   - Number of files
   - Any compatibility warnings
6. Click **Confirm Restore** to proceed
7. The plugin:
   - Puts the site in maintenance mode
   - Downloads `db.sql.gz` from S3
   - Downloads `files.zip` from S3
   - Imports the database (drops existing tables, recreates from backup)
   - Extracts files over `wp-content/`
   - Takes the site out of maintenance mode
   - Logs the result
8. You'll see a success message with a summary
9. Go to **Settings → Permalinks** and click **Save Changes** to regenerate rewrite rules

### What Gets Replaced

| Component | Behavior |
|-----------|----------|
| Database tables (wp_ prefix) | Dropped and recreated from backup |
| wp-content/themes/ | Overwritten with backup versions |
| wp-content/plugins/ | Overwritten with backup versions |
| wp-content/uploads/ | Overwritten with backup versions |
| wp-config.php | **NOT touched** — your current database credentials are preserved |
| WordPress core files | **NOT touched** — only wp-content is restored |

### Important Notes

- The restore replaces your **entire database** — all posts, pages, users, settings, and plugin data
- The restore replaces your **entire wp-content directory** — all themes, plugins, and uploads
- `wp-config.php` is preserved so database connection settings remain correct
- After restore, you may need to log in again (session cookies are invalidated)
- If the backup was from a different URL, you'll need to update `WP_HOME` and `WP_SITEURL` in `wp-config.php`

### Error Handling

If the restore fails partway through:
- The site may be in maintenance mode — delete `.maintenance` from the WordPress root
- The database may be partially restored — re-run the restore or use the manual method
- Check **S3 Backup → Logs** for error details

---

## Method 2: Manual Restore

Use this method if:
- The plugin is not installed or not working
- WordPress admin is inaccessible
- You need to restore to a different server
- You want more control over the process

### Step 1: Download Backup Files from S3

**Option A: From the plugin**

Go to **S3 Backup → Backups** and click the download links for each file.

**Option B: From AWS Console**

1. Log into the [AWS S3 Console](https://s3.console.aws.amazon.com/)
2. Navigate to your bucket → `backups/your-site/`
3. Download the three files for the backup date you want

**Option C: Using AWS CLI**

```bash
# List available backups
aws s3 ls s3://wp-s3-backup-ekewaka-backups/backups/your-site/ --profile ekewaka

# Download a specific backup
aws s3 cp s3://wp-s3-backup-ekewaka-backups/backups/your-site/2026-04-15-051735-db.sql.gz . --profile ekewaka
aws s3 cp s3://wp-s3-backup-ekewaka-backups/backups/your-site/2026-04-15-051736-files.zip . --profile ekewaka
aws s3 cp s3://wp-s3-backup-ekewaka-backups/backups/your-site/2026-04-15-051824-manifest.json . --profile ekewaka
```

### Step 2: Check the Manifest (Optional but Recommended)

Open `manifest.json` and verify:

```json
{
    "wordpress_version": "6.8",
    "php_version": "8.2.20",
    "table_prefix": "wp_",
    "site_url": "https://edwardfong.onthewifi.com/ekewaka",
    "backup_contents": {
        "database": {
            "checksum": "sha256:abc123...",
            "tables": 29,
            "rows": 11901
        },
        "files": {
            "checksum": "sha256:def456...",
            "total_files": 13477
        }
    }
}
```

Check that:
- The `table_prefix` matches your target site
- The `wordpress_version` is compatible with your target PHP version
- The `site_url` is what you expect (you may need to update URLs after restore)

### Step 3: Restore the Database

**Option A: Using phpMyAdmin**

1. Open phpMyAdmin (locally: `http://localhost/phpmyadmin`)
2. Select your WordPress database
3. Click the **Import** tab
4. Click **Choose File** and select the `db.sql.gz` file (phpMyAdmin handles gzip automatically)
5. Click **Go**
6. Wait for the import to complete

**Option B: Using MySQL command line**

```bash
# Decompress first
gunzip 2026-04-15-051735-db.sql.gz

# Import (replace values with your database credentials)
mysql -u root -p your_database_name < 2026-04-15-051735-db.sql
```

**Option C: Using MySQL command line (without decompressing)**

```bash
gunzip -c 2026-04-15-051735-db.sql.gz | mysql -u root -p your_database_name
```

### Step 4: Restore the Files

**Option A: Using command line**

```bash
# Navigate to your WordPress installation
cd /path/to/wordpress/

# Back up current wp-content (optional safety measure)
mv wp-content wp-content-old

# Extract the backup
unzip 2026-04-15-051736-files.zip

# The zip contains a wp-content/ folder, so it extracts in place
```

**Option B: Using FTP**

1. Extract `files.zip` on your local machine
2. Connect to your server via FTP/SFTP
3. Navigate to your WordPress installation directory
4. Upload the extracted `wp-content/` folder, overwriting existing files

**Option C: Using cPanel File Manager**

1. Upload `files.zip` to your WordPress root directory
2. Right-click the zip file → **Extract**
3. It will extract the `wp-content/` folder in place

### Step 5: Post-Restore Tasks

1. **Regenerate permalinks:** Go to **Settings → Permalinks** → click **Save Changes**

2. **Update URLs (if restoring to a different domain):** Add to `wp-config.php`:
   ```php
   define( 'WP_HOME', 'https://your-new-domain.com' );
   define( 'WP_SITEURL', 'https://your-new-domain.com' );
   ```

3. **Clear caches:** If you have a caching plugin, clear all caches

4. **Verify:** Browse the site and check:
   - Homepage loads correctly
   - Media/images display
   - Plugins are active and working
   - Theme customizations are intact

### Step 6: Verify Backup Integrity (Optional)

Compare the SHA-256 checksums from the manifest against the downloaded files:

**On Linux/Mac:**
```bash
sha256sum 2026-04-15-051735-db.sql.gz
sha256sum 2026-04-15-051736-files.zip
```

**On Windows (PowerShell):**
```powershell
Get-FileHash .\2026-04-15-051735-db.sql.gz -Algorithm SHA256
Get-FileHash .\2026-04-15-051736-files.zip -Algorithm SHA256
```

Compare the output with the `checksum` values in `manifest.json`.

---

## Restoring to a Different Server

If you're migrating to a new server:

1. Install a fresh WordPress on the new server
2. Install the WP S3 Backup plugin (or restore manually)
3. Download the backup files from S3
4. Restore the database (Step 3 above)
5. Restore the files (Step 4 above)
6. Update `wp-config.php` with the new server's database credentials:
   ```php
   define( 'DB_NAME', 'new_database_name' );
   define( 'DB_USER', 'new_database_user' );
   define( 'DB_PASSWORD', 'new_database_password' );
   define( 'DB_HOST', 'localhost' );
   ```
7. If the domain changed, update URLs:
   ```php
   define( 'WP_HOME', 'https://new-domain.com' );
   define( 'WP_SITEURL', 'https://new-domain.com' );
   ```
8. Regenerate permalinks

---

## Disaster Recovery Scenario

If your WordPress site is completely down and you need to restore from scratch:

1. **Set up a new server** with PHP, MySQL, and Apache/Nginx
2. **Install WordPress** fresh
3. **Get your backup files** from S3 using AWS CLI or the AWS Console (you don't need WordPress to access S3)
4. **Import the database** via MySQL command line
5. **Extract files.zip** into the WordPress directory
6. **Update wp-config.php** with correct database credentials
7. **Update URLs** if the domain changed
8. **Regenerate permalinks**

The key advantage of S3 backups: your backups survive even if your hosting provider goes down completely. As long as you have your AWS credentials, you can recover.
