# WP S3 Backup — Backup Engine Technical Guide

## Overview

The backup engine creates two artifacts:
1. **Database dump** — gzipped SQL file containing all WordPress tables
2. **Files archive** — zip of `wp-content/` directory

Plus a **manifest file** — JSON metadata about the backup.

All operations use PHP-native functions — no shell commands (`mysqldump`, `zip` CLI) are used, making it compatible with any hosting environment.

---

## Database Export

### Why Not Use `mysqldump`?

- `exec()` and `shell_exec()` are disabled on most shared hosts
- WordPress.org plugin guidelines prohibit using these functions
- PHP-native approach works everywhere WordPress runs

### How It Works

We use `$wpdb` to:
1. Get a list of all tables with the site's prefix
2. For each table, get the `CREATE TABLE` statement
3. Export rows in batches of 1000 using `SELECT * FROM table LIMIT offset, 1000`
4. Generate `INSERT INTO` statements
5. Gzip compress the output

### Code Flow

```php
public function export_database() {
    global $wpdb;

    // Create temp file with gzip compression
    $temp_file = $this->get_temp_path( 'db.sql.gz' );
    $gz = gzopen( $temp_file, 'wb9' );

    // Write header
    gzwrite( $gz, "-- WP S3 Backup Database Dump\n" );
    gzwrite( $gz, "-- Generated: " . gmdate( 'Y-m-d H:i:s' ) . " UTC\n" );
    gzwrite( $gz, "-- WordPress: " . get_bloginfo( 'version' ) . "\n" );
    gzwrite( $gz, "SET NAMES utf8mb4;\n" );
    gzwrite( $gz, "SET foreign_key_checks = 0;\n\n" );

    // Get all tables
    $tables = $wpdb->get_col( "SHOW TABLES LIKE '{$wpdb->prefix}%'" );

    foreach ( $tables as $table ) {
        // Get CREATE TABLE statement
        $create = $wpdb->get_row( "SHOW CREATE TABLE `{$table}`", ARRAY_N );
        gzwrite( $gz, "DROP TABLE IF EXISTS `{$table}`;\n" );
        gzwrite( $gz, $create[1] . ";\n\n" );

        // Export rows in batches
        $offset = 0;
        $batch  = 1000;

        while ( true ) {
            $rows = $wpdb->get_results(
                $wpdb->prepare( "SELECT * FROM `{$table}` LIMIT %d, %d", $offset, $batch ),
                ARRAY_A
            );

            if ( empty( $rows ) ) break;

            foreach ( $rows as $row ) {
                $values = array_map( function( $v ) use ( $wpdb ) {
                    return null === $v ? 'NULL' : "'" . esc_sql( $v ) . "'";
                }, $row );

                gzwrite( $gz, "INSERT INTO `{$table}` VALUES (" . implode( ',', $values ) . ");\n" );
            }

            $offset += $batch;

            // Prevent timeout
            if ( function_exists( 'set_time_limit' ) ) {
                @set_time_limit( 300 );
            }
        }

        gzwrite( $gz, "\n" );
    }

    gzwrite( $gz, "SET foreign_key_checks = 1;\n" );
    gzclose( $gz );

    return $temp_file;
}
```

### Memory Management

- Rows are fetched in batches of 1000 — never loads the entire table into memory
- Each batch is written to the gzip stream immediately
- `set_time_limit()` is called per table to prevent PHP timeout
- Gzip compression typically reduces SQL file size by 80-90%

### Table Filtering

Users can exclude tables via the `wps3b_exclude_tables` filter:

```php
// In your theme's functions.php or a custom plugin:
add_filter( 'wps3b_exclude_tables', function( $tables ) {
    $tables[] = 'wp_actionscheduler_logs'; // Exclude large log tables
    return $tables;
} );
```

---

## File Backup

### What Gets Backed Up

By default, the entire `wp-content/` directory:
- `themes/`
- `plugins/`
- `uploads/`
- `mu-plugins/` (if exists)
- Any other custom directories

### What Gets Excluded

Default exclusions (configurable in settings):
- `wp-content/cache/`
- `wp-content/ai1wm-backups/`
- `wp-content/updraft/`
- `wp-content/backups-dup-pro/`
- `wp-content/node_modules/`
- `wp-content/upgrade/`
- Any `.tmp` files
- Any `.log` files larger than 1MB

### Code Flow

```php
public function export_files() {
    $temp_file  = $this->get_temp_path( 'files.zip' );
    $source_dir = WP_CONTENT_DIR;
    $excludes   = $this->get_exclude_paths();

    $zip = new ZipArchive();
    if ( $zip->open( $temp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
        throw new Exception( 'Could not create zip archive' );
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( $source_dir, RecursiveDirectoryIterator::SKIP_DOTS ),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ( $iterator as $item ) {
        $relative_path = str_replace( $source_dir . DIRECTORY_SEPARATOR, '', $item->getPathname() );

        // Check exclusions
        if ( $this->is_excluded( $relative_path, $excludes ) ) {
            continue;
        }

        if ( $item->isDir() ) {
            $zip->addEmptyDir( 'wp-content/' . $relative_path );
        } else {
            $zip->addFile( $item->getPathname(), 'wp-content/' . $relative_path );
        }

        // Prevent timeout
        if ( function_exists( 'set_time_limit' ) ) {
            @set_time_limit( 300 );
        }
    }

    $zip->close();
    return $temp_file;
}
```

### Exclusion Matching

```php
private function is_excluded( $path, $excludes ) {
    $normalized = str_replace( '\\', '/', $path );

    foreach ( $excludes as $exclude ) {
        $exclude = trim( $exclude );
        if ( empty( $exclude ) ) continue;

        // Directory match
        if ( strpos( $normalized, $exclude ) === 0 || strpos( $normalized, '/' . $exclude ) !== false ) {
            return true;
        }

        // Extension match (e.g., "*.log")
        if ( strpos( $exclude, '*.' ) === 0 ) {
            $ext = substr( $exclude, 1 );
            if ( substr( $normalized, -strlen( $ext ) ) === $ext ) {
                return true;
            }
        }
    }

    return false;
}
```

### Large Site Handling

For sites with many files (>50,000), the zip process can take a long time. Strategies:

1. **Chunked processing** — Process files in batches, save progress to a transient, continue on next cron tick
2. **Size limit warning** — Estimate backup size before starting and warn if >2GB
3. **Timeout protection** — `set_time_limit()` called periodically

---

## Manifest File

Every backup includes a `manifest.json` with metadata:

```json
{
    "version": "1.0.0",
    "timestamp": "2026-06-15T12:00:00Z",
    "site_url": "https://example.com",
    "site_name": "My WordPress Site",
    "wordpress_version": "6.8",
    "php_version": "8.2.20",
    "mysql_version": "8.0.36",
    "wp_content_dir": "/var/www/html/wp-content",
    "table_prefix": "wp_",
    "active_theme": "portfoliox-child",
    "active_plugins": [
        "all-in-one-wp-migration/all-in-one-wp-migration.php",
        "wp-s3-backup/wp-s3-backup.php"
    ],
    "backup_contents": {
        "database": {
            "file": "2026-06-15-120000-db.sql.gz",
            "size": 5242880,
            "checksum": "sha256:abc123...",
            "tables": 42,
            "total_rows": 125000
        },
        "files": {
            "file": "2026-06-15-120000-files.zip",
            "size": 524288000,
            "checksum": "sha256:def456...",
            "total_files": 15234,
            "excluded_paths": ["cache", "ai1wm-backups"]
        }
    }
}
```

### Why a Manifest?

- **Verification** — checksums let you verify backup integrity after download
- **Compatibility** — before restoring, you can check WP version, PHP version, table prefix
- **Inventory** — know exactly what's in the backup without extracting it
- **Debugging** — if a restore fails, the manifest helps diagnose why

---

## Temp File Management

### Location

Temp files are created in:
```
wp-content/wps3b-temp/
```

This directory is:
- Created on first backup
- Protected with `.htaccess` (deny all) and `index.php` (blank)
- Cleaned up after every backup (success or failure)

### Cleanup

```php
public function cleanup_temp() {
    $temp_dir = WP_CONTENT_DIR . '/wps3b-temp';

    if ( is_dir( $temp_dir ) ) {
        $files = glob( $temp_dir . '/*' );
        foreach ( $files as $file ) {
            if ( is_file( $file ) ) {
                @unlink( $file );
            }
        }
    }
}
```

Cleanup runs:
- After successful upload to S3
- After failed upload (in the `catch` block)
- On a daily cron as a safety net (in case a backup was interrupted)

---

## Backup Flow (Complete)

```
1. User clicks "Backup Now" or wp-cron fires
                    │
2. Create temp directory
                    │
3. Export database ──────► db.sql.gz (temp)
                    │
4. Export files ─────────► files.zip (temp)
                    │
5. Generate manifest ────► manifest.json (temp)
                    │
6. Upload db.sql.gz to S3
                    │
7. Upload files.zip to S3 (multipart if >100MB)
                    │
8. Upload manifest.json to S3
                    │
9. Log success
                    │
10. Clean up temp files
                    │
11. Done
```

### Error at Any Step

```
Error occurs
    │
    ├── Log error message
    ├── Clean up temp files
    ├── Clean up partial S3 uploads (abort multipart)
    └── Send notification (if configured)
```
