# WP S3 Backup — WordPress.org Plugin Submission Guide

## Overview

This document covers the requirements and process for submitting the plugin to the WordPress.org plugin repository.

---

## Submission Checklist

### Code Requirements

- [ ] All PHP files start with `if ( ! defined( 'ABSPATH' ) ) exit;`
- [ ] All functions/classes/constants prefixed with `wps3b_` or `WPS3B_`
- [ ] All database options prefixed with `wps3b_`
- [ ] All hooks (actions/filters) prefixed with `wps3b_`
- [ ] All user inputs sanitized (`sanitize_text_field`, `absint`, etc.)
- [ ] All outputs escaped (`esc_html`, `esc_attr`, `esc_url`, etc.)
- [ ] All form submissions use nonces
- [ ] All admin pages check `current_user_can( 'manage_options' )`
- [ ] No use of `exec()`, `shell_exec()`, `system()`, `proc_open()`
- [ ] No use of `eval()` or `create_function()`
- [ ] No obfuscated or encoded code
- [ ] No external tracking or analytics
- [ ] No upsells or premium features locked behind paywalls
- [ ] GPL v2+ compatible license
- [ ] All strings translatable with `__()` / `_e()` / `esc_html__()`

### External Service Disclosure

Since the plugin connects to AWS S3, we must disclose this:

**In readme.txt:**
```
== External Services ==

This plugin sends backup data to Amazon Web Services (AWS) S3 storage.
Data is transmitted only when you configure AWS credentials and initiate
a backup (manually or via schedule).

* Service: Amazon S3 (https://aws.amazon.com/s3/)
* Data sent: Database dump and wp-content files (as backup archives)
* When: On manual backup or scheduled backup
* Privacy Policy: https://aws.amazon.com/privacy/
* Terms of Service: https://aws.amazon.com/service-terms/

No data is sent to any other third-party service. The plugin does not
collect analytics, usage data, or any information about your site.
```

**In the settings page:**
```php
<p class="description">
    <?php esc_html_e(
        'This plugin uploads backup files to Amazon S3. By using this plugin, '
        . 'you agree to Amazon Web Services Terms of Service and Privacy Policy.',
        'wp-s3-backup'
    ); ?>
    <a href="https://aws.amazon.com/service-terms/" target="_blank">
        <?php esc_html_e( 'AWS Terms', 'wp-s3-backup' ); ?>
    </a> |
    <a href="https://aws.amazon.com/privacy/" target="_blank">
        <?php esc_html_e( 'AWS Privacy', 'wp-s3-backup' ); ?>
    </a>
</p>
```

### readme.txt Format

```
=== WP S3 Backup ===
Contributors: yourusername
Tags: backup, s3, amazon, aws, database
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically back up your WordPress database and files to Amazon S3.

== Description ==

WP S3 Backup creates full site backups (database + files) and uploads
them directly to your Amazon S3 bucket. No third-party services, no
file size limits, no premium upsells.

**Features:**

* Full database export (PHP-native, no mysqldump required)
* wp-content directory backup (themes, plugins, uploads)
* Scheduled automatic backups (daily, weekly, monthly)
* Manual one-click backups
* AWS credentials encrypted at rest
* Lightweight — no AWS SDK bundled (~50KB)
* Direct S3 REST API with Signature V4 authentication
* Multipart upload for large files
* Configurable file exclusions
* Backup manifest with checksums
* Pre-signed download URLs

**Requirements:**

* An AWS account with an S3 bucket
* IAM user credentials with S3 access
* PHP 7.4+ with openssl and zip extensions

== Installation ==

1. Upload the plugin to `/wp-content/plugins/wp-s3-backup/`
2. Activate through the Plugins menu
3. Go to Settings > WP S3 Backup
4. Enter your AWS credentials and bucket name
5. Click Test Connection to verify
6. Enable scheduled backups or click Backup Now

For AWS setup instructions, see the
[Terraform guide](https://github.com/yourusername/wp-s3-backup-terraform).

== Frequently Asked Questions ==

= Do I need the AWS SDK? =

No. The plugin uses direct S3 REST API calls with AWS Signature V4
signing. No external libraries are required.

= How are my AWS credentials stored? =

Credentials are encrypted using AES-256-CBC with a key derived from
your WordPress security salts (AUTH_KEY and AUTH_SALT in wp-config.php).
They are never stored in plaintext.

= What happens if my site goes down? =

Your backups are safely stored in S3. You can download them directly
from the AWS Console or using the AWS CLI.

= How much does S3 storage cost? =

For a typical site with 500MB daily backups: approximately $0.50/month.
With weekly backups: approximately $0.10/month.

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release.

== External Services ==

This plugin sends backup data to Amazon Web Services (AWS) S3 storage.
Data is transmitted only when you configure AWS credentials and initiate
a backup (manually or via schedule).

* Service: Amazon S3 (https://aws.amazon.com/s3/)
* Data sent: Database dump and wp-content files
* When: On manual or scheduled backup
* Privacy Policy: https://aws.amazon.com/privacy/
* Terms of Service: https://aws.amazon.com/service-terms/

== Screenshots ==

1. Settings page with AWS configuration
2. Backup list showing files in S3
3. Manual backup in progress
4. Activity log
```

---

## Submission Process

### 1. Prepare the Plugin

```bash
# Create a clean zip without development files
cd wp-s3-backup/
zip -r ../wp-s3-backup.zip . \
  -x "*.git*" \
  -x "*.DS_Store" \
  -x "node_modules/*" \
  -x "tests/*" \
  -x "*.md" \
  -x "composer.*"
```

### 2. Submit for Review

1. Go to https://wordpress.org/plugins/developers/add/
2. Log in with your wordpress.org account
3. Upload the zip file
4. Fill in the plugin details
5. Submit

### 3. Review Process

- WordPress.org reviewers will check the code manually
- Typical review time: 1-5 business days
- They may request changes — respond promptly
- Common rejection reasons:
  - Sanitization/escaping issues
  - Missing nonces
  - External service not disclosed
  - Prefixing issues
  - GPL compatibility issues

### 4. After Approval

- You'll get SVN access to your plugin's repository
- Upload your code via SVN (not zip)
- Set up assets (banner, icon, screenshots) in the `assets/` directory
- Tag your first release

---

## Common Review Feedback & Fixes

### "Please sanitize all input data"

```php
// Bad
$bucket = $_POST['bucket_name'];

// Good
$bucket = isset( $_POST['bucket_name'] )
    ? sanitize_text_field( wp_unslash( $_POST['bucket_name'] ) )
    : '';
```

### "Please escape all output"

```php
// Bad
echo $bucket_name;

// Good
echo esc_html( $bucket_name );
```

### "Please use nonces"

```php
// In form
wp_nonce_field( 'wps3b_save_settings', 'wps3b_nonce' );

// In handler
if ( ! wp_verify_nonce( $_POST['wps3b_nonce'], 'wps3b_save_settings' ) ) {
    wp_die( 'Security check failed' );
}
```

### "Please check capabilities"

```php
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'You do not have permission to access this page.' );
}
```

### "External service must be disclosed"

Add the External Services section to readme.txt (shown above).

---

## If WordPress.org Rejects the Plugin

The plugin can still be distributed as:
- A GitHub release (users download zip and install manually)
- Self-hosted with a custom update server
- Distributed via your own website

The code quality and security standards should be the same regardless of distribution method.
