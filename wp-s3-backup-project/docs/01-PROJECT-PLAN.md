# WP S3 Backup — Project Plan

## Table of Contents

1. [Project Overview](#project-overview)
2. [WordPress Plugin Repository Requirements](#wordpress-plugin-repository-requirements)
3. [Architecture](#architecture)
4. [Plugin Design](#plugin-design)
5. [Terraform Infrastructure](#terraform-infrastructure)
6. [Security Model](#security-model)
7. [File Structure](#file-structure)
8. [Implementation Phases](#implementation-phases)
9. [Testing Plan](#testing-plan)

---

## Project Overview

**Plugin Name:** WP S3 Backup
**Slug:** wp-s3-backup
**Description:** A WordPress plugin that creates full site backups (database + files) and uploads them to Amazon S3. Supports scheduled automatic backups, manual backups, retention management, and one-click restore.

### What It Does
- Creates a SQL dump of the WordPress database
- Zips the `wp-content` directory (themes, plugins, uploads)
- Uploads both to an S3 bucket
- Runs on a configurable schedule via wp-cron
- Lists and manages backups stored in S3
- Allows downloading backups from S3
- Encrypts AWS credentials at rest in the database

### What It Does NOT Do
- Does not modify WordPress core files
- Does not require shell access or `exec()` / `mysqldump` binary (uses PHP-native database export)
- Does not store backups locally (uploads directly to S3, temp files are cleaned up)
- Does not manage S3 lifecycle rules (that's handled by Terraform separately)

---

## WordPress Plugin Repository Requirements

To be accepted on wordpress.org, the plugin must comply with these guidelines:

### Must Follow
- **GPL v2+ license** — all code must be GPL compatible
- **No external service calls without disclosure** — we connect to AWS S3, so this must be clearly disclosed in the readme and settings page
- **No tracking/analytics** — no phoning home
- **No obfuscated code** — all PHP must be human-readable
- **No bundled libraries that duplicate WordPress functions** — use WP HTTP API where possible
- **Sanitize all inputs** — `sanitize_text_field()`, `absint()`, etc.
- **Escape all outputs** — `esc_html()`, `esc_attr()`, `esc_url()`, etc.
- **Use nonces** for all form submissions and AJAX calls
- **Capability checks** — only administrators can access settings
- **Prefix everything** — all functions, classes, options, hooks must use `wps3b_` prefix
- **No direct file access** — all PHP files must check `ABSPATH`
- **Internationalization** — all strings must be translatable with `__()` and `_e()`
- **readme.txt** — must follow wordpress.org format exactly

### Cannot Do
- Cannot bundle the AWS SDK as a Composer dependency (too large, ~30MB)
- Cannot use `exec()`, `shell_exec()`, `system()`, or `proc_open()`
- Cannot store credentials in plaintext files
- Cannot make external HTTP requests without user consent

### AWS SDK Approach
Instead of bundling the full AWS SDK (~30MB, 5000+ files), we'll use **direct S3 REST API calls** with AWS Signature V4 signing. This:
- Keeps the plugin lightweight (~50KB)
- Avoids Composer dependency issues
- Is fully compliant with wordpress.org guidelines
- Uses WordPress's built-in `wp_remote_request()` HTTP API

---

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    WordPress Site                        │
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │            WP S3 Backup Plugin                   │   │
│  │                                                   │   │
│  │  ┌──────────┐  ┌──────────┐  ┌───────────────┐  │   │
│  │  │ Settings │  │ Backup   │  │ S3 Client     │  │   │
│  │  │ Page     │  │ Engine   │  │ (REST API +   │  │   │
│  │  │          │  │          │  │  SigV4 Auth)  │  │   │
│  │  └────┬─────┘  └────┬─────┘  └───────┬───────┘  │   │
│  │       │              │                │           │   │
│  │  ┌────▼─────┐  ┌────▼─────┐  ┌───────▼───────┐  │   │
│  │  │ Crypto   │  │ WP-Cron  │  │ WP HTTP API   │  │   │
│  │  │ (encrypt │  │ Schedule │  │ (wp_remote_*) │  │   │
│  │  │  creds)  │  │          │  │               │  │   │
│  │  └──────────┘  └──────────┘  └───────────────┘  │   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
└─────────────────────────┬───────────────────────────────┘
                          │ HTTPS (AWS SigV4)
                          ▼
┌─────────────────────────────────────────────────────────┐
│                    AWS Account                           │
│                                                         │
│  ┌──────────────────────────────────────────────────┐  │
│  │  S3 Bucket: wp-s3-backup-{account-id}            │  │
│  │                                                    │  │
│  │  /backups/{site-name}/                             │  │
│  │    ├── 2026-06-15-120000-db.sql.gz                │  │
│  │    ├── 2026-06-15-120000-files.zip                │  │
│  │    ├── 2026-06-15-120000-manifest.json            │  │
│  │    ├── 2026-06-14-120000-db.sql.gz                │  │
│  │    └── ...                                         │  │
│  │                                                    │  │
│  │  Lifecycle Rules (Terraform-managed):              │  │
│  │    - 30 days → S3 Glacier Instant Retrieval       │  │
│  │    - 90 days → Delete                              │  │
│  │                                                    │  │
│  │  Bucket Policy:                                    │  │
│  │    - Server-side encryption (AES-256)              │  │
│  │    - Block all public access                       │  │
│  │    - Versioning enabled                            │  │
│  └──────────────────────────────────────────────────┘  │
│                                                         │
│  ┌──────────────────────────────────────────────────┐  │
│  │  IAM User: wp-s3-backup-user                      │  │
│  │                                                    │  │
│  │  Policy: Only s3:PutObject, s3:GetObject,         │  │
│  │          s3:DeleteObject, s3:ListBucket            │  │
│  │          on the specific bucket                    │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

---

## Plugin Design

### Core Classes

#### 1. `WPS3B_Plugin` (main entry point)
- Registers activation/deactivation hooks
- Loads all other classes
- Registers wp-cron schedule
- Adds admin menu

#### 2. `WPS3B_Settings`
- Renders the settings page
- Handles form submission with nonce verification
- Validates and sanitizes all inputs
- Encrypts AWS credentials before storing
- Provides "Test Connection" functionality

#### 3. `WPS3B_Crypto`
- Encrypts/decrypts AWS credentials using AES-256-CBC
- Uses WordPress `AUTH_KEY` + `AUTH_SALT` as the encryption key
- Credentials are never stored in plaintext in the database

#### 4. `WPS3B_Backup_Engine`
- **Database export:** Uses `$wpdb` to iterate all tables, generates SQL INSERT statements in batches, gzip compresses the output
- **File backup:** Uses PHP's `ZipArchive` to create a zip of `wp-content/` with configurable exclusions
- **Manifest:** Creates a JSON file with backup metadata (WP version, PHP version, plugin list, timestamp, checksums)
- Cleans up temp files after upload

#### 5. `WPS3B_S3_Client`
- Implements AWS Signature V4 signing (no SDK needed)
- Supports multipart upload for large files (>100MB)
- Operations: PutObject, GetObject, DeleteObject, ListObjectsV2, CreateMultipartUpload, UploadPart, CompleteMultipartUpload
- Uses `wp_remote_request()` for all HTTP calls

#### 6. `WPS3B_Backup_Manager`
- Lists backups from S3
- Handles backup deletion
- Generates pre-signed download URLs
- Triggered by wp-cron for scheduled backups

#### 7. `WPS3B_Restore` (future phase)
- Downloads backup from S3
- Extracts files
- Imports database
- (This is complex — may be Phase 2)

### Database Options (wp_options)

| Option Name | Value | Encrypted |
|-------------|-------|-----------|
| `wps3b_settings` | Serialized settings array | No |
| `wps3b_aws_credentials` | Encrypted access key + secret | Yes |
| `wps3b_last_backup` | Timestamp of last successful backup | No |
| `wps3b_last_error` | Last error message if any | No |

### Settings Fields

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| AWS Access Key ID | text (masked after save) | — | IAM user access key |
| AWS Secret Access Key | password (masked after save) | — | IAM user secret key |
| S3 Bucket Name | text | — | Target bucket |
| S3 Region | select | us-east-1 | AWS region |
| S3 Path Prefix | text | backups/{site-name} | Folder path in bucket |
| Backup Frequency | select | daily | daily/twicedaily/weekly/monthly |
| Enable Scheduled Backups | checkbox | off | Toggle automatic backups |
| Exclude Paths | textarea | cache,node_modules,ai1wm-backups | Comma-separated paths to skip |
| Backup Database | checkbox | on | Include database in backup |
| Backup Files | checkbox | on | Include wp-content in backup |

### Admin Pages

1. **Settings** — Configure AWS credentials, schedule, and options
2. **Backups** — List all backups in S3 with download/delete actions
3. **Logs** — View recent backup activity and errors

### Hooks & Filters (for extensibility)

```php
// Actions
do_action( 'wps3b_before_backup' );
do_action( 'wps3b_after_backup', $manifest );
do_action( 'wps3b_backup_failed', $error );
do_action( 'wps3b_before_upload', $file_path );
do_action( 'wps3b_after_upload', $s3_key );

// Filters
apply_filters( 'wps3b_exclude_paths', $paths );
apply_filters( 'wps3b_exclude_tables', $tables );
apply_filters( 'wps3b_s3_path_prefix', $prefix );
apply_filters( 'wps3b_backup_filename', $filename );
apply_filters( 'wps3b_max_file_size', 5368709120 ); // 5GB default
```

---

## Terraform Infrastructure

### Resources Created

| Resource | Name | Purpose |
|----------|------|---------|
| S3 Bucket | `wp-s3-backup-{var.suffix}` | Stores backups |
| S3 Bucket Versioning | — | Protects against accidental overwrites |
| S3 Bucket Encryption | AES-256 (SSE-S3) | Encrypts data at rest |
| S3 Public Access Block | All blocked | Prevents public exposure |
| S3 Lifecycle Rule | 30d → Glacier, 90d → Delete | Automatic cost optimization |
| IAM User | `wp-s3-backup-user` | Dedicated service account |
| IAM Access Key | — | Credentials for the plugin |
| IAM Policy | Scoped to bucket only | Least privilege access |

### Variables

| Variable | Type | Default | Description |
|----------|------|---------|-------------|
| `bucket_suffix` | string | — | Unique suffix for bucket name |
| `aws_region` | string | us-east-1 | AWS region |
| `glacier_transition_days` | number | 30 | Days before moving to Glacier |
| `expiration_days` | number | 90 | Days before deletion |
| `tags` | map | {} | Resource tags |

### Outputs

| Output | Description |
|--------|-------------|
| `bucket_name` | S3 bucket name to enter in plugin settings |
| `bucket_region` | Region to enter in plugin settings |
| `iam_access_key_id` | Access key for plugin settings |
| `iam_secret_access_key` | Secret key for plugin settings (sensitive) |

---

## Security Model

### Credential Storage
1. User enters AWS Access Key ID and Secret Access Key in the settings page
2. Plugin encrypts both using AES-256-CBC with a key derived from WordPress's `AUTH_KEY` and `AUTH_SALT`
3. Encrypted credentials are stored in `wp_options` table
4. On each backup run, credentials are decrypted in memory only
5. Settings page shows masked values (e.g., `AKIA****WXYZ`) — never displays the full key after initial save

### Encryption Implementation
```
Encryption Key = hash('sha256', AUTH_KEY . AUTH_SALT)
IV = random 16 bytes (stored alongside ciphertext)
Ciphertext = openssl_encrypt(plaintext, 'aes-256-cbc', key, 0, iv)
Stored Value = base64(iv . ciphertext)
```

### AWS IAM Policy (Least Privilege)
```json
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "s3:PutObject",
        "s3:GetObject",
        "s3:DeleteObject",
        "s3:ListBucket",
        "s3:AbortMultipartUpload",
        "s3:ListMultipartUploadParts"
      ],
      "Resource": [
        "arn:aws:s3:::BUCKET_NAME",
        "arn:aws:s3:::BUCKET_NAME/*"
      ]
    }
  ]
}
```

### WordPress Security
- All form submissions use nonces (`wp_nonce_field` / `check_admin_referer`)
- All settings access requires `manage_options` capability
- All AJAX endpoints verify nonce + capability
- All user inputs sanitized with appropriate WordPress functions
- All outputs escaped with `esc_html()`, `esc_attr()`, `esc_url()`
- Direct file access blocked with `ABSPATH` check in every PHP file

### Data in Transit
- All S3 communication over HTTPS (TLS 1.2+)
- AWS Signature V4 authenticates every request
- No credentials sent in URL query strings (always in headers)

---

## File Structure

```
wp-s3-backup/
├── wp-s3-backup.php              # Main plugin file (entry point)
├── uninstall.php                  # Clean removal (delete options on uninstall)
├── readme.txt                     # WordPress.org readme
├── LICENSE                        # GPL v2
├── includes/
│   ├── class-wps3b-plugin.php         # Main plugin class
│   ├── class-wps3b-settings.php       # Settings page & validation
│   ├── class-wps3b-crypto.php         # Credential encryption/decryption
│   ├── class-wps3b-backup-engine.php  # Database dump & file zip
│   ├── class-wps3b-s3-client.php      # S3 REST API with SigV4
│   ├── class-wps3b-backup-manager.php # List, delete, download backups
│   └── class-wps3b-logger.php         # Backup activity logging
├── admin/
│   ├── views/
│   │   ├── settings-page.php          # Settings form HTML
│   │   ├── backups-page.php           # Backup list HTML
│   │   └── logs-page.php              # Log viewer HTML
│   ├── css/
│   │   └── admin.css                  # Admin styles
│   └── js/
│       └── admin.js                   # Test connection, UI interactions
└── languages/
    └── wp-s3-backup.pot               # Translation template
```

---

## Implementation Phases

### Phase 1: Core Plugin (MVP) ✅ COMPLETE
- [x] Plugin scaffolding (main file, autoloading, activation/deactivation)
- [x] Settings page with AWS credential input
- [x] Credential encryption/decryption
- [x] S3 client with SigV4 signing
- [x] Test Connection functionality
- [x] Database export (PHP-native, gzipped)
- [x] File backup (ZipArchive, with exclusions)
- [x] Manifest file generation
- [x] Upload to S3 (single part for files <25MB)
- [x] Manual "Backup Now" button
- [x] Backup listing from S3
- [x] Backup deletion from S3
- [x] Pre-signed download URLs
- [x] Activity logging

### Phase 2: Scheduling & Polish ✅ COMPLETE
- [x] WP-Cron scheduled backups
- [x] Multipart upload for large files (>25MB, 10MB chunks)
- [x] Storage class badges on each file
- [x] Storage usage summary (total size, per-class breakdown)
- [x] Live auto-refresh on logs page
- [x] Pro feature placeholders with upgrade badges in settings
- [x] Pro banner and hints on backups page
- [x] wps3b_is_pro_active() helper function for Pro detection

### Phase 3: WordPress.org Submission ✅ READY
- [x] readme.txt with full documentation and restore FAQ
- [x] Screenshot specifications (ASSETS-GUIDE.md)
- [ ] Actual screenshot images (capture from running plugin)
- [ ] Banner and icon images (create in Figma/Canva)
- [x] Translation template (.pot file location)
- [ ] Code review against wordpress.org guidelines
- [ ] Security audit
- [x] uninstall.php for clean removal
- [ ] Submit to wordpress.org review queue

### Phase 4: Restore ✅ COMPLETE
- [x] One-click full site restore (database + files)
- [x] Download backup from S3
- [x] Database import (line-by-line via $wpdb)
- [x] File extraction (ZipArchive with path traversal protection)
- [x] Pre-restore compatibility check (PHP, WP version, URL, table prefix)
- [x] Maintenance mode during restore
- [x] Restore confirmation screen with warnings
- [x] Temp file cleanup on success and failure

### Phase 5: Pro Plugin (Future — separate project)
- [ ] Selective restore (DB only or files only)
- [ ] Restore to different URL with serialization-safe replacement
- [ ] Staging restore to subdirectory
- [ ] Restore history with pre-restore snapshots
- [ ] Incremental backups
- [ ] Storage class management (change classes)
- [ ] Cost estimate calculator
- [ ] Bulk operations
- [ ] Email notifications
- [ ] Slack/webhook notifications
- [ ] Backup encryption (client-side AES)
- [ ] Custom schedules
- [ ] Multi-site support
- [ ] White-label
- [ ] License key validation against WP License Platform

---

## Testing Plan

### Unit Tests
- Credential encryption round-trip
- SigV4 signature generation against known test vectors
- Database export SQL generation
- Manifest JSON structure validation

### Integration Tests
- Upload file to S3 and verify
- List objects in S3
- Delete object from S3
- Generate pre-signed URL and verify access
- Full backup cycle (export → upload → list → download → verify)

### Manual Tests
- Fresh install on WordPress 6.x
- Settings save/load with credential masking
- Test Connection with valid/invalid credentials
- Manual backup with small site (<50MB)
- Manual backup with large site (>500MB)
- Scheduled backup fires on time
- Backup list displays correctly
- Download from S3 works
- Delete from S3 works
- Plugin deactivation clears cron
- Plugin uninstall removes all options
- Multisite compatibility check

### Security Tests
- Verify credentials are encrypted in database
- Verify nonce validation on all forms
- Verify capability checks on all endpoints
- Verify no XSS in settings page
- Verify no SQL injection in database export
- Verify temp files are cleaned up after backup
