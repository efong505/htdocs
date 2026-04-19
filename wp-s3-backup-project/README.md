# WP S3 Backup — Project Documentation

A WordPress plugin that creates full site backups (database + files) and uploads them to Amazon S3. Includes Terraform configuration for AWS infrastructure and comprehensive documentation for monetization.

---

## Documentation

| # | Document | Description |
|---|----------|-------------|
| 1 | [Project Plan](docs/01-PROJECT-PLAN.md) | Architecture, plugin design, file structure, implementation phases, testing plan |
| 2 | [S3 Client Technical Guide](docs/02-S3-CLIENT-TECHNICAL-GUIDE.md) | AWS Signature V4 signing, S3 REST API operations, multipart upload, error handling |
| 3 | [Backup Engine Guide](docs/03-BACKUP-ENGINE-GUIDE.md) | Database export, file zipping, manifest generation, temp file management |
| 4 | [Credential Security Guide](docs/04-CREDENTIAL-SECURITY-GUIDE.md) | AES-256-CBC encryption, key derivation, masking, threat model |
| 5 | [Terraform Guide](docs/05-TERRAFORM-GUIDE.md) | S3 bucket, IAM user, lifecycle rules, cost estimate, usage instructions |
| 6 | [WordPress.org Submission Guide](docs/06-WORDPRESS-ORG-SUBMISSION-GUIDE.md) | Plugin repository requirements, readme.txt format, submission process |
| 7 | [Restore Guide](docs/07-RESTORE-GUIDE.md) | Automated one-click restore, manual restore, server migration, disaster recovery |
| 8 | [Monetization Strategy](docs/08-MONETIZATION-STRATEGY.md) | Market analysis, freemium model, pricing, revenue projections, marketing, launch roadmap |
| 9 | [Payment Processing Guide](docs/09-PAYMENT-PROCESSING-GUIDE.md) | PayPal vs LemonSqueezy comparison, tax implications, implementation options |
| 10 | [Pro License Integration](docs/10-PRO-LICENSE-INTEGRATION.md) | How the Pro plugin connects to the WP License Platform, API endpoints, feature gating |
| 11 | [Pro Plugin Handoff](docs/11-PRO-PLUGIN-HANDOFF.md) | Complete build guide for the Pro plugin — architecture, code samples, hooks, build order, session prompt |

---

## Project Structure

```
wp-s3-backup-project/
├── README.md
├── docs/
│   ├── 01-PROJECT-PLAN.md
│   ├── 02-S3-CLIENT-TECHNICAL-GUIDE.md
│   ├── 03-BACKUP-ENGINE-GUIDE.md
│   ├── 04-CREDENTIAL-SECURITY-GUIDE.md
│   ├── 05-TERRAFORM-GUIDE.md
│   ├── 06-WORDPRESS-ORG-SUBMISSION-GUIDE.md
│   ├── 07-RESTORE-GUIDE.md
│   ├── 08-MONETIZATION-STRATEGY.md
│   ├── 09-PAYMENT-PROCESSING-GUIDE.md
│   └── 10-PRO-LICENSE-INTEGRATION.md
├── terraform/
│   ├── bootstrap/
│   │   └── main.tf              ← Creates S3 state bucket
│   ├── main.tf                   ← S3 backup bucket + IAM user
│   ├── variables.tf
│   ├── outputs.tf
│   └── terraform.tfvars
└── plugin/
    └── wp-s3-backup/
        ├── wp-s3-backup.php      ← Main plugin file
        ├── uninstall.php         ← Clean removal
        ├── readme.txt            ← WordPress.org format
        ├── LICENSE
        ├── includes/
        │   ├── class-wps3b-plugin.php         ← Activation, cron, menus
        │   ├── class-wps3b-settings.php       ← Settings page, AJAX
        │   ├── class-wps3b-crypto.php         ← AES-256-CBC encryption
        │   ├── class-wps3b-logger.php         ← Activity log with render
        │   ├── class-wps3b-s3-client.php      ← S3 REST API + SigV4
        │   ├── class-wps3b-backup-engine.php  ← DB export + zip
        │   ├── class-wps3b-backup-manager.php ← Orchestrates backup flow
        │   └── class-wps3b-restore.php        ← One-click full site restore
        ├── admin/
        │   ├── views/
        │   │   ├── settings-page.php  ← Settings + Pro feature placeholders
        │   │   ├── backups-page.php   ← Backup list + badges + restore
        │   │   └── logs-page.php      ← Live-refresh activity log
        │   ├── css/
        │   │   └── admin.css
        │   └── js/
        │       └── admin.js
        ├── assets/
        │   └── ASSETS-GUIDE.md        ← Banner, icon, screenshot specs
        └── languages/
```

---

## Quick Start

### 1. Create AWS infrastructure
```bash
cd terraform/bootstrap
terraform init && terraform apply          # Creates state bucket

cd ../
terraform init && terraform apply          # Creates backup bucket + IAM user
```

### 2. Get credentials
```bash
terraform output iam_access_key_id
terraform output -raw iam_secret_access_key
terraform output bucket_name
terraform output bucket_region
```

### 3. Install the plugin
Copy `plugin/wp-s3-backup/` to `wp-content/plugins/` and activate.

### 4. Configure
Enter the Terraform output values in **S3 Backup → Settings**, click **Test Connection**, then **Save Settings**.

---

## Feature Summary

### Free Plugin (Built ✅)

| Feature | Status |
|---------|--------|
| Full database backup (gzipped SQL) | ✅ |
| Full wp-content file backup (zip) | ✅ |
| Upload to S3 with SigV4 auth (no SDK) | ✅ |
| Multipart upload for large files (10MB chunks) | ✅ |
| Encrypted credential storage (AES-256-CBC) | ✅ |
| Manual one-click backup | ✅ |
| Scheduled backups (daily/weekly/monthly) | ✅ |
| View backups in S3 with storage class badges | ✅ |
| Storage usage summary (total size, per-class breakdown) | ✅ |
| Download backups from S3 (pre-signed URLs) | ✅ |
| Delete backups from S3 | ✅ |
| One-click full site restore (database + files) | ✅ |
| Pre-restore compatibility check | ✅ |
| Backup manifest with SHA-256 checksums | ✅ |
| Activity log with live auto-refresh | ✅ |
| Pro feature placeholders with upgrade badges | ✅ |

### Pro Plugin (Needs to be built)

| Feature | Status |
|---------|--------|
| Selective restore (DB only or files only) | ⬜ |
| Restore to different URL (serialization-safe) | ⬜ |
| Staging restore (subdirectory) | ⬜ |
| Restore history with rollback | ⬜ |
| Incremental backups | ⬜ |
| Change storage class (Standard ↔ Glacier) | ⬜ |
| Cost estimate calculator | ⬜ |
| Bulk operations (multi-select delete/class change) | ⬜ |
| Email notifications | ⬜ |
| Slack/webhook notifications | ⬜ |
| Backup encryption (client-side AES) | ⬜ |
| Custom schedules (hourly, specific time) | ⬜ |
| Multi-site network support | ⬜ |
| White-label | ⬜ |
| License key validation | ⬜ |

### Infrastructure (Built ✅)

| Component | Status |
|-----------|--------|
| S3 backup bucket (encrypted, versioned, public access blocked) | ✅ |
| S3 lifecycle rules (30d → Glacier IR, 90d → delete) | ✅ |
| IAM user with least-privilege policy | ✅ |
| S3 state bucket for Terraform | ✅ |
| Native S3 state locking (use_lockfile) | ✅ |
| AWS profile support | ✅ |

---

## How Backups Work

```
1. Backup triggered (manual button or wp-cron schedule)
2. Database exported via $wpdb → gzipped SQL file (temp)
3. wp-content/ zipped via ZipArchive (temp)
4. Manifest JSON generated with checksums (temp)
5. All 3 files uploaded to S3 (multipart for files >25MB)
6. Temp files cleaned up
7. Success logged

Backups exist ONLY in S3 — no local copies are kept.
The Backups page queries S3 live to list what's there.
Delete removes from S3 (versioning keeps 30-day soft-delete).
```

---

## Related Projects

| Project | Purpose | Status |
|---------|---------|--------|
| **wp-s3-backup** (this) | Free backup plugin + Terraform | ✅ Complete |
| **wp-s3-backup-pro** | Pro features plugin (extends free) | ⬜ Needs building |
| **wp-license-platform** | Payment processing, licensing, VAT compliance | ⬜ Next project |

The Pro plugin and license platform are separate projects. See [10-PRO-LICENSE-INTEGRATION.md](docs/10-PRO-LICENSE-INTEGRATION.md) for how they connect.
