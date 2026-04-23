# WP S3 Backup Pro — Build Handoff Document

## Purpose

This document provides everything needed to build the `wp-s3-backup-pro` plugin in a future session. It covers the architecture, every feature to implement, the hooks to use, file structure, and step-by-step build order.

**Prerequisites before building Pro:**
1. The free `wp-s3-backup` plugin must be complete (✅ done)
2. The `wp-license-platform` must be built and deployed on your website (⬜ next project)
3. A product entry for "WP S3 Backup Pro" must exist in the license platform

---

## Architecture

The Pro plugin is a **separate WordPress plugin** that extends the free version. It does NOT modify the free plugin's code — it hooks into the free plugin's actions and filters.

```
Customer's WordPress Site
├── wp-s3-backup/              ← Free plugin (from wordpress.org)
│   ├── Fires actions: wps3b_before_backup, wps3b_after_backup, etc.
│   ├── Applies filters: wps3b_exclude_paths, wps3b_exclude_tables, etc.
│   ├── Checks: wps3b_is_pro_active() → true if Pro is installed + licensed
│   └── Shows Pro placeholders when Pro is not active
│
└── wp-s3-backup-pro/          ← Pro plugin (from your website)
    ├── Requires: wp-s3-backup to be active
    ├── Validates license against your API daily
    ├── Hooks into free plugin's actions/filters
    └── Adds Pro-only admin UI elements
```

---

## File Structure

```
wp-s3-backup-pro/
├── wp-s3-backup-pro.php              # Main file — dependency check, license init
├── includes/
│   ├── class-wps3b-pro.php               # Main Pro class — registers all features
│   ├── class-wps3b-pro-license.php       # License validation against your API
│   ├── class-wps3b-pro-incremental.php   # Incremental backup engine
│   ├── class-wps3b-pro-notifications.php # Email + Slack + webhook notifications
│   ├── class-wps3b-pro-encryption.php    # Client-side AES-256 backup encryption
│   ├── class-wps3b-pro-restore.php       # Selective restore, URL replacement, staging
│   ├── class-wps3b-pro-storage.php       # Storage class management, cost estimate
│   └── class-wps3b-pro-schedule.php      # Custom schedules (hourly, specific time)
├── admin/
│   ├── views/
│   │   ├── license-page.php              # License key entry + status
│   │   ├── notifications-settings.php    # Email/Slack config (injected into settings)
│   │   └── storage-management.php        # Storage class change UI
│   ├── css/
│   │   └── pro-admin.css
│   └── js/
│       └── pro-admin.js
└── languages/
    └── wp-s3-backup-pro.pot
```

---

## Main Plugin File

```php
<?php
/**
 * Plugin Name: WP S3 Backup Pro
 * Description: Advanced features for WP S3 Backup — incremental backups, notifications, storage management, and more.
 * Version:     1.0.0
 * Author:      Edward Fong
 * Requires Plugins: wp-s3-backup
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Check if free plugin is active
if ( ! defined( 'WPS3B_VERSION' ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        esc_html_e( 'WP S3 Backup Pro requires the free WP S3 Backup plugin to be active.', 'wp-s3-backup-pro' );
        echo '</p></div>';
    });
    return;
}

define( 'WPS3B_PRO_VERSION', '1.0.0' );
define( 'WPS3B_PRO_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPS3B_PRO_URL', plugin_dir_url( __FILE__ ) );

// Load classes
require_once WPS3B_PRO_DIR . 'includes/class-wps3b-pro-license.php';
require_once WPS3B_PRO_DIR . 'includes/class-wps3b-pro.php';

add_action( 'plugins_loaded', array( 'WPS3B_Pro', 'init' ), 20 );
```

---

## License Validation

### class-wps3b-pro-license.php

```php
class WPS3B_Pro_License {

    // Default license platform API URL (configurable in settings)
    const DEFAULT_API_URL = 'https://ekewaka.com/wp-json/wplp/v1/';
    const CACHE_KEY = 'wps3b_pro_license';
    const CACHE_TTL = DAY_IN_SECONDS;
    const GRACE_DAYS = 7;

    /**
     * Get the API URL — configurable in settings, falls back to default.
     * This allows:
     * - Testing against a local/staging License Platform
     * - Changing the URL without a plugin update
     */
    public static function get_api_url() {
        $url = get_option( 'wps3b_pro_api_url', self::DEFAULT_API_URL );
        return trailingslashit( $url );
    }

    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'daily_check' ) );
        add_action( 'admin_init', array( __CLASS__, 'handle_activation' ) );
        add_action( 'admin_init', array( __CLASS__, 'handle_deactivation' ) );
    }

    /**
     * Check if Pro is currently licensed.
     */
    public static function is_licensed() {
        $data = get_transient( self::CACHE_KEY );
        if ( ! $data || empty( $data['valid'] ) ) {
            // Check grace period
            $last_valid = get_option( 'wps3b_pro_last_valid', 0 );
            if ( $last_valid && ( time() - $last_valid ) < ( self::GRACE_DAYS * DAY_IN_SECONDS ) ) {
                return true;
            }
            return false;
        }
        if ( isset( $data['expires'] ) && strtotime( $data['expires'] ) < time() ) {
            return false;
        }
        return true;
    }

    /**
     * Get the license tier (personal, professional, agency).
     */
    public static function get_tier() {
        $data = get_transient( self::CACHE_KEY );
        return $data && isset( $data['tier'] ) ? $data['tier'] : 'free';
    }

    /**
     * Validate license key against the API.
     */
    public static function validate( $key ) {
        $response = wp_remote_post( self::get_api_url() . 'validate', array(
            'timeout' => 15,
            'body' => array(
                'license_key' => $key,
                'site_url'    => get_site_url(),
                'product'     => 'wp-s3-backup-pro',
                'version'     => WPS3B_PRO_VERSION,
            ),
        ));

        if ( is_wp_error( $response ) ) {
            return null; // API unreachable — use cache
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( $body && ! empty( $body['valid'] ) ) {
            set_transient( self::CACHE_KEY, $body, self::CACHE_TTL );
            update_option( 'wps3b_pro_last_valid', time() );
            return $body;
        }

        delete_transient( self::CACHE_KEY );
        return false;
    }

    /**
     * Daily re-validation (runs once per day via transient expiry).
     */
    public static function daily_check() {
        if ( get_transient( self::CACHE_KEY ) ) {
            return; // Still cached, skip
        }
        $key = get_option( 'wps3b_pro_license_key', '' );
        if ( ! empty( $key ) ) {
            self::validate( $key );
        }
    }

    /**
     * Handle license activation form submission.
     */
    public static function handle_activation() {
        if ( ! isset( $_POST['wps3b_pro_activate'] ) ) return;
        check_admin_referer( 'wps3b_pro_license', 'wps3b_pro_nonce' );
        if ( ! current_user_can( 'manage_options' ) ) return;

        $key = sanitize_text_field( wp_unslash( $_POST['wps3b_pro_license_key'] ?? '' ) );
        update_option( 'wps3b_pro_license_key', $key );

        $result = self::validate( $key );
        if ( $result && ! empty( $result['valid'] ) ) {
            // Activate site against the license
            wp_remote_post( self::get_api_url() . 'activate', array(
                'body' => array(
                    'license_key' => $key,
                    'site_url'    => get_site_url(),
                    'product'     => 'wp-s3-backup-pro',
                ),
            ));
            add_settings_error( 'wps3b_pro', 'activated', 'License activated successfully.', 'success' );
        } else {
            add_settings_error( 'wps3b_pro', 'invalid', 'Invalid license key.', 'error' );
        }
    }

    /**
     * Handle license deactivation.
     */
    public static function handle_deactivation() {
        if ( ! isset( $_POST['wps3b_pro_deactivate'] ) ) return;
        check_admin_referer( 'wps3b_pro_license', 'wps3b_pro_nonce' );
        if ( ! current_user_can( 'manage_options' ) ) return;

        $key = get_option( 'wps3b_pro_license_key', '' );
        if ( $key ) {
            wp_remote_post( self::get_api_url() . 'deactivate', array(
                'body' => array(
                    'license_key' => $key,
                    'site_url'    => get_site_url(),
                    'product'     => 'wp-s3-backup-pro',
                ),
            ));
        }

        delete_option( 'wps3b_pro_license_key' );
        delete_transient( self::CACHE_KEY );
        add_settings_error( 'wps3b_pro', 'deactivated', 'License deactivated.', 'success' );
    }

    /**
     * Get masked license key for display.
     */
    public static function get_masked_key() {
        $key = get_option( 'wps3b_pro_license_key', '' );
        if ( empty( $key ) || strlen( $key ) < 10 ) return '';
        return substr( $key, 0, 5 ) . str_repeat( '*', strlen( $key ) - 9 ) . substr( $key, -4 );
    }
}
```

---

## Features to Build (in order)

### 1. License System (build first)
- License key entry page under S3 Backup menu
- **Configurable API URL field** (defaults to your production site, overridable for testing/staging)
- Validation against your API
- Daily re-check with grace period
- Activation/deactivation (must call /deactivate API to update site count on the License Platform)
- This gates all other Pro features

**License page should include these fields:**
```php
<!-- License Key -->
<input type="text" name="wps3b_pro_license_key" value="..." />

<!-- API URL (advanced, collapsed by default) -->
<input type="url" name="wps3b_pro_api_url" 
    value="<?php echo esc_attr( get_option( 'wps3b_pro_api_url', WPS3B_Pro_License::DEFAULT_API_URL ) ); ?>" />
<p class="description">License server URL. Only change if instructed by support.</p>

[Activate] [Deactivate]
```

**Save handler must include:**
```php
if ( isset( $_POST['wps3b_pro_api_url'] ) ) {
    $url = esc_url_raw( wp_unslash( $_POST['wps3b_pro_api_url'] ) );
    if ( ! empty( $url ) ) {
        update_option( 'wps3b_pro_api_url', $url );
    }
}
```

**IMPORTANT:** When deactivating, the Pro plugin MUST call the `/deactivate` API endpoint so the License Platform decrements the site count. If this call fails or is skipped, the site count on the platform will be wrong.

### 2. Email Notifications
**Hook:** `wps3b_after_backup` and `wps3b_backup_failed`

```php
add_action( 'wps3b_after_backup', array( __CLASS__, 'send_success_email' ) );
add_action( 'wps3b_backup_failed', array( __CLASS__, 'send_failure_email' ) );

public static function send_success_email( $manifest ) {
    $to = get_option( 'wps3b_pro_notification_email', get_option( 'admin_email' ) );
    $subject = sprintf( '[%s] Backup completed successfully', get_bloginfo( 'name' ) );
    $body = sprintf(
        "Backup completed at %s\n\nDatabase: %s\nFiles: %s\n",
        $manifest['timestamp'],
        size_format( $manifest['backup_contents']['database']['size'] ?? 0 ),
        size_format( $manifest['backup_contents']['files']['size'] ?? 0 )
    );
    wp_mail( $to, $subject, $body );
}
```

### 3. Slack/Webhook Notifications
**Hook:** Same as email — `wps3b_after_backup` and `wps3b_backup_failed`

```php
public static function send_webhook( $payload ) {
    $url = get_option( 'wps3b_pro_webhook_url', '' );
    if ( empty( $url ) ) return;

    wp_remote_post( $url, array(
        'headers' => array( 'Content-Type' => 'application/json' ),
        'body'    => wp_json_encode( $payload ),
        'timeout' => 10,
    ));
}
```

### 4. Storage Class Management
**New S3 API call:** CopyObject (copy-to-self with new storage class header)

```php
public function change_storage_class( $s3_key, $new_class ) {
    $uri = '/' . ltrim( $s3_key, '/' );
    $headers = $this->sign_request( 'PUT', $uri, '', array(
        'x-amz-copy-source'   => '/' . $this->bucket . $uri,
        'x-amz-storage-class' => $new_class,
    ));

    $url = $this->get_endpoint() . $uri;
    return wp_remote_request( $url, array(
        'method'  => 'PUT',
        'headers' => $headers,
        'timeout' => 60,
    ));
}
```

Valid storage classes: `STANDARD`, `STANDARD_IA`, `INTELLIGENT_TIERING`, `GLACIER`, `GLACIER_IR`, `DEEP_ARCHIVE`

### 5. Cost Estimate Calculator
Uses the storage class and size data already available from `list_objects`:

```php
$cost_per_gb = array(
    'STANDARD'            => 0.023,
    'STANDARD_IA'         => 0.0125,
    'INTELLIGENT_TIERING' => 0.023,  // first 30 days
    'GLACIER_IR'          => 0.004,
    'GLACIER'             => 0.0036,
    'DEEP_ARCHIVE'        => 0.00099,
);

$monthly_cost = 0;
foreach ( $files as $file ) {
    $gb = $file['size'] / ( 1024 * 1024 * 1024 );
    $class = $file['storage_class'];
    $monthly_cost += $gb * ( $cost_per_gb[ $class ] ?? 0.023 );
}
```

### 6. Incremental Backups
**Hook:** `wps3b_before_backup` to modify the backup behavior

Strategy:
1. After each backup, store a manifest of file paths + modification times + checksums
2. On next backup, compare current files against the stored manifest
3. Only include changed/new files in the zip
4. Store the incremental manifest alongside the backup in S3

```php
add_filter( 'wps3b_exclude_paths', array( __CLASS__, 'filter_unchanged_files' ) );
```

This is the most complex Pro feature — build it last.

### 7. Backup Encryption
**Hook:** `wps3b_before_upload`

```php
add_action( 'wps3b_before_upload', array( __CLASS__, 'encrypt_file' ) );

public static function encrypt_file( $file_path ) {
    $password = get_option( 'wps3b_pro_encryption_password', '' );
    if ( empty( $password ) ) return;

    $key = hash( 'sha256', $password, true );
    $iv  = openssl_random_pseudo_bytes( 16 );
    
    $input  = fopen( $file_path, 'rb' );
    $output = fopen( $file_path . '.enc', 'wb' );
    fwrite( $output, $iv );
    
    while ( $chunk = fread( $input, 8192 ) ) {
        fwrite( $output, openssl_encrypt( $chunk, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv ) );
    }
    
    fclose( $input );
    fclose( $output );
    rename( $file_path . '.enc', $file_path );
}
```

### 8. Selective Restore
Override the restore behavior to allow DB-only or files-only:

```php
// Add radio buttons to the restore confirmation page
// Pass the selection to WPS3B_Restore::run() as a parameter
```

### 9. URL Replacement on Restore
Serialization-safe search-and-replace after database import:

```php
// Use a proper serialization-aware replacer
// WordPress serialized data has string length prefixes: s:5:"hello"
// Changing "hello" to "goodbye" requires updating the length to s:7:"goodbye"
// Libraries like interconnectit/search-replace-db handle this
```

### 10. Custom Schedules
Add more wp-cron intervals and a time-of-day picker:

```php
add_filter( 'cron_schedules', function( $schedules ) {
    $schedules['every_6_hours'] = array(
        'interval' => 21600,
        'display'  => 'Every 6 Hours',
    );
    $schedules['hourly'] = array(
        'interval' => 3600,
        'display'  => 'Hourly',
    );
    return $schedules;
});
```

### 11. Chunked Restore with AJAX Progress (AI1WM-style)

The free plugin's restore runs as a single blocking PHP request — no progress feedback until completion. The Pro version should break the restore into chunks with real-time progress updates.

**Architecture:**

```
User clicks Restore
    │
    ▼
AJAX request 1: Download manifest from S3, show confirmation
    │
    ▼
User confirms
    │
    ▼
AJAX request 2: Download db.sql.gz from S3 → store in temp
    │ (progress: "Downloading database... X%")
    ▼
AJAX request 3: Import DB in batches of 1000 statements
    │ (progress: "Importing database... 3,500 / 12,000 statements")
    │ (multiple AJAX calls, each processes 1000 statements)
    ▼
AJAX request 4: Download files.zip from S3 → store in temp
    │ (progress: "Downloading files... X%")
    ▼
AJAX request 5+: Extract files in batches of 500
    │ (progress: "Extracting files... 4,000 / 15,740")
    │ (multiple AJAX calls, each extracts 500 files)
    ▼
AJAX request final: Cleanup temp files, disable maintenance mode
    │ (progress: "Restore complete!")
    ▼
Done
```

**Key implementation details:**

1. **State tracking** — Store restore progress in a transient or custom option:
```php
update_option( 'wps3b_restore_state', array(
    'step'       => 'extract_files',  // current step
    'total'      => 15740,            // total items in current step
    'processed'  => 4000,             // items completed
    'temp_dir'   => '/path/to/temp/', // where downloaded files are
    'timestamp'  => $backup_timestamp,
) );
```

2. **AJAX endpoint** — Each call reads the state, processes a batch, updates state, returns progress:
```php
add_action( 'wp_ajax_wps3b_pro_restore_step', function() {
    check_ajax_referer( 'wps3b_pro_restore' );
    $state = get_option( 'wps3b_restore_state' );
    
    switch ( $state['step'] ) {
        case 'download_db':
            // Download db.sql.gz from S3
            // Update state to 'import_db'
            break;
        case 'import_db':
            // Read next 1000 SQL statements from file
            // Execute them
            // Update processed count
            // If done, move to 'download_files'
            break;
        case 'download_files':
            // Download files.zip from S3
            // Update state to 'extract_files'
            break;
        case 'extract_files':
            // Extract next 500 files from zip
            // Update processed count
            // If done, move to 'cleanup'
            break;
        case 'cleanup':
            // Remove temp files, disable maintenance mode
            // Delete state option
            break;
    }
    
    wp_send_json_success( array(
        'step'      => $state['step'],
        'total'     => $state['total'],
        'processed' => $state['processed'],
        'message'   => $progress_message,
        'complete'  => $is_done,
    ) );
});
```

3. **JavaScript polling** — Client calls the endpoint repeatedly:
```javascript
function runRestoreStep() {
    $.post(ajaxurl, { action: 'wps3b_pro_restore_step', nonce: nonce }, function(res) {
        updateProgressBar(res.data.processed, res.data.total, res.data.message);
        if (!res.data.complete) {
            runRestoreStep(); // Next chunk
        } else {
            showSuccess();
        }
    });
}
```

4. **Timeout protection** — Each AJAX call should complete within 30 seconds. If a batch takes too long, reduce batch size.

5. **Resume capability** — If the browser is closed mid-restore, the state is saved. Returning to the restore page detects the in-progress state and offers to resume or cancel.

This is the same pattern All-in-One WP Migration uses — chunked processing with AJAX polling for progress.

---

## Available Hooks in the Free Plugin

### Actions (fire at key moments)

| Hook | When | Use For |
|------|------|---------|
| `wps3b_before_backup` | Before backup starts | Pre-backup tasks |
| `wps3b_after_backup` | After successful backup (receives $manifest) | Notifications |
| `wps3b_backup_failed` | After backup failure (receives WP_Error) | Failure notifications |
| `wps3b_before_upload` | Before each file upload (receives $file_path) | Encryption |
| `wps3b_after_upload` | After each file upload (receives $s3_key) | Post-upload processing |

### Filters (modify behavior)

| Filter | Default | Use For |
|--------|---------|---------|
| `wps3b_exclude_paths` | Default exclusion list | Custom path selection |
| `wps3b_exclude_tables` | Empty array | Table selection |
| `wps3b_s3_path_prefix` | Auto from site URL | Multi-site prefixes |
| `wps3b_backup_filename` | Timestamp-based | Custom naming |
| `wps3b_max_file_size` | 5GB | Configurable limit |

### Helper Function

```php
// Check if Pro is active (defined in free plugin)
wps3b_is_pro_active()  // Returns true when Pro plugin is installed + licensed
```

---

## Build Order

1. **License system** — must work before anything else
2. **Email notifications** — simplest Pro feature, quick win
3. **Slack/webhook** — similar to email, easy to add
4. **Custom schedules** — small feature, high value
5. **Storage class management** — one new S3 API call
6. **Cost estimate** — pure calculation, no new APIs
7. **Selective restore** — UI changes + parameter passing
8. **Backup encryption** — file processing before upload
9. **URL replacement** — complex serialization handling
10. **Incremental backups** — complex file diffing
11. **Chunked restore with progress** — most complex, refactors restore architecture

---

## Testing Checklist

- [ ] Pro plugin activates only when free plugin is active
- [ ] Pro plugin shows error notice when free plugin is deactivated
- [ ] License key validation works against the API
- [ ] License caching works (doesn't call API on every page load)
- [ ] Grace period works (Pro features stay active for 7 days if API is unreachable)
- [ ] License deactivation frees up the site slot
- [ ] Each Pro feature only works when licensed
- [ ] Pro features degrade gracefully when license expires (no errors, just disabled)
- [ ] Free plugin continues to work normally when Pro is deactivated
- [ ] No conflicts between free and Pro plugin updates

---

## Session Prompt

When starting a new session to build the Pro plugin, provide this context:

```
I have a WordPress plugin called WP S3 Backup (free version) that backs up 
WordPress sites to Amazon S3. The free plugin is complete and located at:
c:\xampp\htdocs\wp-s3-backup-project\plugin\wp-s3-backup\

I need to build the Pro version (wp-s3-backup-pro) as a separate plugin that 
extends the free version. The handoff document is at:
c:\xampp\htdocs\wp-s3-backup-project\docs\11-PRO-PLUGIN-HANDOFF.md

The Pro plugin should:
1. Validate a license key against my license platform API
2. Hook into the free plugin's actions and filters
3. Add: email notifications, Slack webhooks, storage class management, 
   cost estimates, incremental backups, backup encryption, selective restore, 
   URL replacement on restore, custom schedules

The license platform project is at:
c:\xampp\htdocs\wp-license-platform\

Please read the handoff doc and build the Pro plugin.
```
