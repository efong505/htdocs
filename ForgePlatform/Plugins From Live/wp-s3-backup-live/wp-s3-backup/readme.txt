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
* One-click full site restore (database + files in one operation)
* Pre-restore compatibility checking (PHP version, WP version, URL, table prefix)
* AWS credentials encrypted at rest using AES-256-CBC
* Lightweight — no AWS SDK bundled (~50KB total)
* Direct S3 REST API with Signature V4 authentication
* Multipart upload for large files (10MB chunks)
* Storage class badges on each backup file (Standard, Glacier, etc.)
* Storage usage summary with per-class breakdown
* Delete backups directly from S3
* Configurable file exclusions
* Backup manifest with SHA-256 checksums
* Pre-signed download URLs (download directly from S3)
* Activity log with live auto-refresh
* Pro feature previews with upgrade path

**Requirements:**

* An AWS account with an S3 bucket
* IAM user credentials with S3 access
* PHP 7.4+ with openssl and zip extensions

**Terraform Configuration:**

An optional Terraform configuration is available to create the S3 bucket and IAM user with least-privilege permissions. See the plugin documentation for details.

== Installation ==

**Option A: Using Terraform (recommended)**

A Terraform configuration is included to create the S3 bucket and IAM user automatically.

1. Install [Terraform](https://developer.hashicorp.com/terraform/install) (v1.10+)
2. Navigate to the `terraform/` directory included with the plugin documentation
3. Run `terraform init` then `terraform apply -var="bucket_suffix=your-site-name"`
4. Get your credentials from the Terraform output:
   * `terraform output iam_access_key_id` — your Access Key ID
   * `terraform output -raw iam_secret_access_key` — your Secret Access Key
   * `terraform output bucket_name` — your S3 bucket name
   * `terraform output bucket_region` — your S3 region
5. Upload the `wp-s3-backup` folder to `/wp-content/plugins/`
6. Activate through the Plugins menu
7. Go to S3 Backup > Settings
8. Enter the four values from step 4
9. Click Test Connection to verify
10. Enable scheduled backups or click Backup Now

**Option B: Manual AWS Setup**

If you prefer to set up AWS manually:

1. Create an S3 bucket in the AWS Console
2. Enable versioning and server-side encryption on the bucket
3. Block all public access on the bucket
4. Create an IAM user with the following policy (replace BUCKET_NAME):
   `{"Version":"2012-10-17","Statement":[{"Effect":"Allow","Action":["s3:ListBucket"],"Resource":"arn:aws:s3:::BUCKET_NAME"},{"Effect":"Allow","Action":["s3:PutObject","s3:GetObject","s3:DeleteObject","s3:AbortMultipartUpload","s3:ListMultipartUploadParts"],"Resource":"arn:aws:s3:::BUCKET_NAME/*"}]}`
5. Create an access key for the IAM user
6. Upload the `wp-s3-backup` folder to `/wp-content/plugins/`
7. Activate through the Plugins menu
8. Go to S3 Backup > Settings
9. Enter your Access Key ID, Secret Access Key, bucket name, and region
10. Click Test Connection to verify
11. Enable scheduled backups or click Backup Now

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

= How do I restore from a backup? =

**Automated:** Go to S3 Backup > Backups, find the backup you want, and click Restore. The plugin will download the files from S3, show a compatibility check, and restore your database and files after you confirm.

**Manual:** Download the backup files from the Backups page (or directly from S3), then:

1. Import `db.sql.gz` via phpMyAdmin (it handles gzip automatically) or MySQL command line: `gunzip -c db.sql.gz | mysql -u root -p your_database`
2. Extract `files.zip` and upload the `wp-content/` folder to your WordPress installation via FTP
3. Go to Settings > Permalinks and click Save Changes

See the full Restore Guide in the plugin documentation for detailed instructions including server migration and disaster recovery.

= What happens during a restore? =

The automated restore process:

1. Downloads and checks `manifest.json` for compatibility
2. Shows you what will be replaced and any warnings
3. Puts the site in maintenance mode
4. Downloads and imports the database (replaces all wp_ tables)
5. Downloads and extracts files over wp-content/
6. Takes the site out of maintenance mode

Your `wp-config.php` is never touched, so database credentials are preserved.

= Can I restore to a different server? =

Yes. Download the backup files from S3 (via the plugin, AWS Console, or AWS CLI), then restore manually on the new server. You'll need to update `wp-config.php` with the new database credentials and add `WP_HOME`/`WP_SITEURL` constants if the domain changed.

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
* Activity logging with live refresh
* One-click restore from S3 backups
* Pre-restore compatibility checking
* Maintenance mode during restore

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

1. Settings page with AWS configuration, status bar, and Pro feature placeholders
2. Backup list showing files stored in S3 with storage class badges and usage summary
3. Activity log with live auto-refresh
4. One-click restore confirmation with compatibility check and warnings
5. Pro upgrade badges showing available advanced features
