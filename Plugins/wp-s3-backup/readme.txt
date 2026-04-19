=== WP S3 Backup ===
Contributors: ekewaka
Tags: backup, s3, amazon, aws, database
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically back up your WordPress database and files to Amazon S3. No AWS SDK required.

== Description ==

WP S3 Backup creates full site backups (database + files) and uploads them directly to your Amazon S3 bucket. No third-party services, no file size limits, no premium upsells.

**Features:**

* Full database export (PHP-native, no mysqldump required)
* wp-content directory backup (themes, plugins, uploads)
* Scheduled automatic backups (daily, weekly, monthly)
* Manual one-click backups
* AWS credentials encrypted at rest using AES-256-CBC
* Lightweight — no AWS SDK bundled (~50KB total)
* Direct S3 REST API with Signature V4 authentication
* Multipart upload for large files (>100MB)
* Configurable file exclusions
* Backup manifest with SHA-256 checksums
* Pre-signed download URLs (download directly from S3)
* Activity log with last 100 entries

**Requirements:**

* An AWS account with an S3 bucket
* IAM user credentials with S3 access
* PHP 7.4+ with openssl and zip extensions

**Terraform Configuration:**

An optional Terraform configuration is available to create the S3 bucket and IAM user with least-privilege permissions. See the plugin documentation for details.

== Installation ==

1. Upload the `wp-s3-backup` folder to `/wp-content/plugins/`
2. Activate through the Plugins menu
3. Go to S3 Backup > Settings
4. Enter your AWS credentials and bucket name
5. Click Test Connection to verify
6. Enable scheduled backups or click Backup Now

== Frequently Asked Questions ==

= Do I need the AWS SDK? =

No. The plugin uses direct S3 REST API calls with AWS Signature V4 signing. No external libraries are required.

= How are my AWS credentials stored? =

Credentials are encrypted using AES-256-CBC with a key derived from your WordPress security salts (AUTH_KEY and AUTH_SALT in wp-config.php). They are never stored in plaintext.

= What happens if my site goes down? =

Your backups are safely stored in S3. You can download them directly from the AWS Console or using the AWS CLI.

= How much does S3 storage cost? =

For a typical site with 500MB daily backups: approximately $0.50/month. With weekly backups: approximately $0.10/month.

= Can I back up multiple sites to the same bucket? =

Yes. Each site uses a unique path prefix (based on the site URL) within the bucket. You can also customize the prefix in settings.

= What file types are excluded by default? =

Cache directories, other backup plugin folders (ai1wm-backups, updraft), node_modules, and the upgrade directory. You can customize exclusions in settings.

== Changelog ==

= 1.0.0 =
* Initial release
* Database export with gzip compression
* File backup with ZipArchive
* S3 upload with SigV4 authentication
* Multipart upload for large files
* Scheduled backups via wp-cron
* Encrypted credential storage
* Backup listing, download, and deletion
* Activity logging

== Upgrade Notice ==

= 1.0.0 =
Initial release.

== External Services ==

This plugin sends backup data to Amazon Web Services (AWS) S3 storage. Data is transmitted only when you configure AWS credentials and initiate a backup (manually or via schedule).

* Service: Amazon S3 (https://aws.amazon.com/s3/)
* Data sent: Database dump and wp-content files (as backup archives)
* When: On manual backup or scheduled backup
* Privacy Policy: https://aws.amazon.com/privacy/
* Terms of Service: https://aws.amazon.com/service-terms/

No data is sent to any other third-party service. The plugin does not collect analytics, usage data, or any information about your site.

== Screenshots ==

1. Settings page with AWS configuration and status bar
2. Backup list showing files stored in S3
3. Activity log with backup history
