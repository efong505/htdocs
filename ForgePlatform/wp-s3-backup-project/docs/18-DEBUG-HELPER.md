# BackForge Debug Helper

## Overview

The Debug Helper is a WordPress mu-plugin (`wp-content/mu-plugins/wps3b-debug.php`) that provides troubleshooting tools for BackForge. It loads before regular plugins, so it works even when BackForge or other plugins are broken.

## Location

- **File:** `wp-content/mu-plugins/wps3b-debug.php`
- **Admin page:** BackForge → Debug
- **Emergency URL:** `/wp-admin/admin-ajax.php?action=wps3b_emergency_reset&key=backforge2026`

## Features

### 1. WordPress Debug Mode Toggle

A button to turn `WP_DEBUG` on or off without manually editing `wp-config.php`.

- **When ON:** PHP errors, warnings, and notices are logged to `wp-content/debug.log` (not displayed on screen)
- **When OFF:** Errors are suppressed (production mode)
- Also manages `WP_DEBUG_LOG` (enables log file) and `WP_DEBUG_DISPLAY` (always false — errors go to log, not screen)
- The page shows the last entries from `debug.log` in a collapsible section
- A "Clear Debug Log" button empties the log file

**Use this when:**
- A plugin update causes a fatal error and you need to see what happened
- You're testing restore/backup and want to see PHP errors
- You need to check for deprecation warnings before a WordPress.org submission

### 2. Restore Status Viewer & Reset

Shows the current `wps3b_restore_status` option as raw data, including:
- Running state, current step, progress percentage
- Timestamp, prefix, restore type
- Completed steps list
- Chunked extraction offset and zip path
- Error messages

**"Clear Restore Status" button:**
- Deletes the `wps3b_restore_status` option
- Clears the `wps3b_run_background_restore` scheduled cron event
- Use when a restore is stuck and the Cancel button doesn't work

### 3. Backup Status Viewer & Reset

Same as restore but for `wps3b_backup_status`. Shows backup progress and allows clearing a stuck backup.

### 4. Maintenance Mode Status & Reset

Shows whether the `.maintenance` file exists in the WordPress root.

**"Delete .maintenance File" button:**
- Removes the file that puts WordPress in "Briefly unavailable for scheduled maintenance" mode
- Use when a restore or update created the file and didn't clean it up, causing 503 errors

### 5. Temp Files Viewer & Cleanup

Lists files in `wp-content/wps3b-temp/` with their sizes. This is where BackForge stores:
- Downloaded `.sql.gz` database dumps during restore
- Downloaded `.zip` file archives during restore
- Uploaded backup files for Upload & Restore

**"Clear Temp Files" button:**
- Deletes all files in the temp directory (except `.htaccess` and `index.php`)
- Use to free disk space after a failed restore leaves large files behind

### 6. Emergency Reset URL

A special URL that clears ALL BackForge state in one request:

```
/wp-admin/admin-ajax.php?action=wps3b_emergency_reset&key=backforge2026
```

**What it clears:**
- `wps3b_restore_status` option
- `wps3b_backup_status` option
- `wps3b_run_background_restore` cron event
- `wps3b_run_background_backup` cron event
- `.maintenance` file
- All temp files

**When to use:**
- The site is returning 503 errors and you can't access the admin
- A fatal error prevents the Debug page from loading
- You need to reset everything quickly

**Security:** Protected by a hardcoded key (`backforge2026`). You must be logged in as an admin (WordPress checks the AJAX nonce cookie). Change the key in the mu-plugin if deploying to production.

## Installation

1. Copy `wps3b-debug.php` to `wp-content/mu-plugins/`
2. No activation needed — mu-plugins load automatically
3. Access via BackForge → Debug in the admin menu

## Removal

Delete `wp-content/mu-plugins/wps3b-debug.php`. Remove before WordPress.org submission — this is a development tool, not part of the plugin package.

## Notes

- The WP_DEBUG toggle modifies `wp-config.php` directly. It requires the file to be writable by PHP.
- The debug log viewer shows the last ~5KB of the log file. For large logs, download the file via FTP/file manager.
- The emergency reset URL should be changed or removed before going live with real customers.
