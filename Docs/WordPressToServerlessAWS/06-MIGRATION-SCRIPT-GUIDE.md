# WordPress to Serverless AWS — Migration Script Guide

## Overview

This guide covers how to use the automated migration scripts to convert any WordPress site into a static site and optionally deploy it to AWS serverless infrastructure.

The scripts live in `c:\xampp\htdocs\bitbybit-serverless\scripts\` and work on any WordPress site in your XAMPP `htdocs` directory.

## Prerequisites

- XAMPP installed with Apache and MySQL running
- WordPress site(s) in `c:\xampp\htdocs\<site-name>\`
- The site must be accessible via its configured URL (check `wp-config.php` for `WP_HOME`)
- For AWS deployment: AWS CLI configured, Terraform installed

## Quick Start

### Export Only (No AWS Deployment)

```batch
cd c:\xampp\htdocs\bitbybit-serverless\scripts
migrate.bat <site-folder-name>
```

This exports the WordPress site as static HTML and opens it for local preview.

### Full Migration (Export + Deploy to AWS)

```batch
migrate.bat <site-folder-name> <domain> <hosted-zone-id> <contact-email>
```

This exports, deploys infrastructure, uploads to S3, and invalidates CloudFront.

## Examples

```batch
REM Export bitbybit for local preview only
migrate.bat bitbybit

REM Export and deploy bitbybit to AWS
migrate.bat bitbybit bitbybit.com Z1234567890 you@email.com

REM Export next-level
migrate.bat next-level

REM Export altoa
migrate.bat altoa
```

## What the Scripts Do

### migrate.bat (Master Script)

Orchestrates the entire pipeline:

1. **Validates** the WordPress installation exists
2. **Calls** `static-export.php` to crawl and export the site
3. **Runs** `terraform apply` to create AWS infrastructure (if domain provided)
4. **Uploads** static files to S3 via `aws s3 sync`
5. **Invalidates** CloudFront cache

### static-export.php (Export Engine)

The core export script. Can be run standalone:

```batch
set PATH=c:\xampp\php;%PATH%
php static-export.php <wordpress-root-path> <output-directory>
```

What it does:

1. **Loads WordPress** from the specified root path to access its API
2. **Discovers all URLs** using WordPress functions:
   - `get_pages()` — all published pages
   - `get_posts()` — all published posts (including custom post types like Modules)
   - `get_categories()` — category archive pages
   - `get_tags()` — tag archive pages
   - `get_users()` — author archive pages
3. **Fetches each page** via cURL from the live site (gets the exact HTML WordPress renders, including all theme styling, plugin output, widgets, etc.)
4. **Extracts and downloads assets** from the HTML:
   - CSS files (stylesheets)
   - JavaScript files
   - Images (src, srcset)
   - Fonts (referenced in CSS @font-face rules)
   - Favicons and icons
5. **Copies the uploads directory** (`wp-content/uploads/`) directly for all media
6. **Rewrites URLs** in all HTML files to work from the export directory
7. **Creates a 404 page** by requesting a non-existent URL

### fix-paths.php (URL Fixer)

Standalone script to fix asset paths in already-exported HTML files:

```batch
set PATH=c:\xampp\php;%PATH%
php fix-paths.php <export-dir> <base-url>
```

## Adding a New WordPress Site

### Step 1: Set Up the Site in XAMPP

1. Copy or install the WordPress site into `c:\xampp\htdocs\<site-name>\`
2. Import the database into MySQL via phpMyAdmin
3. Update `wp-config.php` with:
   - Correct database name, user, password
   - `WP_HOME` and `WP_SITEURL` pointing to the accessible URL
4. Verify the site loads in your browser

### Step 2: Export

```batch
cd c:\xampp\htdocs\bitbybit-serverless\scripts
migrate.bat <site-name>
```

### Step 3: Preview

Open in browser: `http://localhost/<site-name>-static/`

### Step 4: Deploy (When Ready)

```batch
migrate.bat <site-name> yourdomain.com YOUR_HOSTED_ZONE_ID your@email.com
```

## How URL Rewriting Works

The export script rewrites URLs so the static site works both locally and on AWS:

| Original URL | Exported URL (Local Preview) | On S3/CloudFront |
|---|---|---|
| `https://edwardfong.onthewifi.com/bitbybit/` | `/bitbybit-static/` | `/` (root) |
| `https://edwardfong.onthewifi.com/bitbybit/wp-content/themes/...` | `/bitbybit-static/wp-content/themes/...` | `/wp-content/themes/...` |
| `https://edwardfong.onthewifi.com/bitbybit/about/` | `/bitbybit-static/about/` | `/about/` |

For AWS deployment, the `migrate.bat` script handles the final URL rewriting to strip the local path prefix before uploading to S3.

## Handling Dynamic Features

Static export captures the HTML as-is. Dynamic features need serverless replacements:

| WordPress Feature | Static Export Behavior | Serverless Replacement |
|---|---|---|
| Contact forms | Form HTML is exported but won't submit | Lambda + API Gateway + SES (already in Terraform) |
| Search | Search form exported but won't work | Client-side search (Pagefind/Lunr.js) |
| Comments | Existing comments are exported, new ones won't work | Disqus or Giscus |
| Login/auth | Login pages exported but non-functional | Cognito (if needed) |
| WooCommerce | Product pages exported, cart/checkout won't work | Lambda + DynamoDB + Stripe |

## Troubleshooting

### "Not a WordPress installation"
- Check that `wp-config.php` exists in the site directory
- Verify the path: `c:\xampp\htdocs\<site-name>\wp-config.php`

### Export runs but no styling
- Make sure the WordPress site is accessible via its configured URL
- Check `WP_HOME` in `wp-config.php` — the site must be reachable at that URL
- Verify Apache is running in XAMPP

### MySQL won't start
- Check for stale processes: `tasklist /FI "IMAGENAME eq mysqld.exe"`
- Check port 3306: `netstat -ano | findstr ":3306"`
- Check error log: `c:\xampp\mysql\data\mysql_error.log`
- Common fix: rename `aria_log.*` files in `c:\xampp\mysql\data\` and restart

### Export has 0 pages
- The site URL in `wp-config.php` must be reachable via cURL
- If using HTTPS with self-signed cert, the script handles this (SSL verify is disabled)
- Check that MySQL is running and the site loads in browser first

### Assets missing after export
- Run the export again — some assets may have timed out
- Check if the asset is loaded via JavaScript (the crawler only catches assets in HTML)
- Manually copy missing files from `wp-content/` to the export directory

## File Locations

```
c:\xampp\htdocs\
├── bitbybit\                    ← Original WordPress site
├── bitbybit-static\             ← Exported static site (preview here)
├── bitbybit-serverless\
│   ├── scripts\
│   │   ├── migrate.bat          ← Master migration script
│   │   ├── static-export.php    ← Export engine
│   │   ├── fix-paths.php        ← URL path fixer
│   │   └── sync-media.bat       ← S3 media upload helper
│   ├── infrastructure\
│   │   ├── main.tf              ← Terraform provider/backend
│   │   ├── variables.tf         ← Input variables
│   │   ├── s3_cloudfront.tf     ← S3 + CloudFront
│   │   ├── dns_ssl.tf           ← ACM + Route 53
│   │   ├── lambda_api.tf        ← Lambda + API Gateway
│   │   └── outputs.tf           ← Stack outputs
│   └── lambda\
│       └── contact-form\
│           └── index.py         ← Contact form Lambda handler
└── Docs\
    └── WordPressToServerlessAWS\
        ├── 00-OVERVIEW.md
        ├── 01-BITBYBIT-MIGRATION.md
        ├── 02-NEXTLEVEL-MIGRATION.md
        ├── 03-ALTOA-MIGRATION.md
        ├── 04-AWS-INFRASTRUCTURE.md
        ├── 05-COST-ANALYSIS.md
        └── 06-MIGRATION-SCRIPT-GUIDE.md  ← This file
```
