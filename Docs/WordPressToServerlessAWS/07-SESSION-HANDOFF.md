# BitByBit Migration — Session Handoff Document

## Date: April 13, 2026

---

## What Was Accomplished This Session

### 1. Documentation Created
All docs are in `c:\xampp\htdocs\Docs\WordPressToServerlessAWS\`:
- `00-OVERVIEW.md` — Master plan, architecture diagram, all 3 sites
- `01-BITBYBIT-MIGRATION.md` — Detailed bitbybit migration plan
- `02-NEXTLEVEL-MIGRATION.md` — Next-level placeholder (pending)
- `03-ALTOA-MIGRATION.md` — Altoa placeholder (pending)
- `04-AWS-INFRASTRUCTURE.md` — Shared infra setup, Terraform patterns
- `05-COST-ANALYSIS.md` — WordPress vs serverless cost comparison
- `06-MIGRATION-SCRIPT-GUIDE.md` — How to use the automated migration scripts

### 2. Static Export Script Built
Location: `c:\xampp\htdocs\bitbybit-serverless\scripts\`

| Script | Purpose |
|--------|---------|
| `static-export.php` | Crawls any WordPress site, exports as static HTML with all CSS/JS/images |
| `rewrite-for-deploy.php` | Rewrites local preview URLs to production root-relative paths |
| `fix-paths.php` | Standalone URL path fixer |
| `migrate.bat` | Master one-command migration script (export → Terraform → S3 → CloudFront) |

**How the export works:**
1. Loads WordPress via `wp-load.php` to access its API
2. Uses `get_pages()`, `get_posts()`, `get_categories()`, `get_tags()`, `get_users()` to discover all URLs
3. Fetches each page via cURL from the live site (exact WordPress-rendered HTML)
4. Downloads all CSS, JS, fonts, images found in HTML
5. Copies `wp-content/uploads/` directory
6. Rewrites URLs for the target environment

### 3. AWS Infrastructure Deployed (Terraform)
Location: `c:\xampp\htdocs\bitbybit-serverless\infrastructure\`

**Terraform files:**
| File | Resources |
|------|-----------|
| `main.tf` | Provider (ekewaka profile), S3 backend (`techcross-terraform-state`, key `bitbybit/terraform.tfstate`) |
| `variables.tf` | domain_name, hosted_zone_id, contact_email, aws_profile |
| `s3_cloudfront.tf` | S3 bucket, CloudFront distribution, OAC, CloudFront Function (URL rewrite) |
| `dns_ssl.tf` | ACM certificate, Route 53 DNS record |
| `lambda_api.tf` | Lambda contact form, IAM role, API Gateway HTTP API |
| `outputs.tf` | Bucket name, CF distribution ID, CF domain, API endpoint |
| `url-rewrite.js` | CloudFront Function that appends index.html to directory requests |
| `template.yaml` | CloudFormation alternative (reference only, not used) |

**Deployed resources:**
- S3 bucket: `bitbybitcoding.christianconservativestoday.com-site`
- CloudFront: `E2EOS0SKGKYS3P` (`d3hzb1g7lbf25q.cloudfront.net`)
- ACM cert: `arn:aws:acm:us-east-1:371751795928:certificate/370d6845-4e9f-4d9a-83d0-04db059221ca`
- DNS: `bitbybitcoding.christianconservativestoday.com` → CloudFront (A record alias)
- Lambda: `bitbybitcoding-christianconservativestoday-com-contact`
- API Gateway: `https://f96qmcgv72.execute-api.us-east-1.amazonaws.com`
- IAM Role: `bitbybitcoding-christianconservativestoday-com-lambda-role`
- CloudFront Function: `bitbybitcoding-christianconservativestoday-com-url-rewrite`
- Terraform state: `s3://techcross-terraform-state/bitbybit/terraform.tfstate`

**Hosted Zone:** `Z0541028204EB3LCQMNQD` (christianconservativestoday.com)

### 4. Static Site Deployed & Live
- **URL:** https://bitbybitcoding.christianconservativestoday.com
- **Source files:** `c:\xampp\htdocs\bitbybit-static\`
- 20 pages exported, 61 assets, full wp-content/uploads
- Footer updated: "Powered by ekewaka.com" on all pages
- Navigation links working via CloudFront Function URL rewrite

### 5. MySQL Issue Fixed
- `mysql.db` privilege table was corrupted
- Fixed by replacing with backup from `c:\xampp\mysql\backup\mysql\db.*`
- Also backed up aria_log and ib_logfile files during troubleshooting

### 6. Astro Site (Abandoned)
- Built an Astro static site scaffold in `c:\xampp\htdocs\bitbybit-serverless\site\`
- Copied real Inspiro CSS but couldn't match the WordPress look
- Abandoned in favor of the direct WordPress crawl approach
- Files still exist but are NOT what's deployed

---

## What's Deployed vs What's Local

| Location | Purpose | Status |
|----------|---------|--------|
| `c:\xampp\htdocs\bitbybit\` | Original WordPress site | Running on XAMPP |
| `c:\xampp\htdocs\bitbybit-static\` | Static export (what's on S3) | Deployed to AWS |
| `c:\xampp\htdocs\bitbybit-serverless\` | Scripts, Terraform, Astro (unused) | Tools/infra |
| `c:\xampp\htdocs\Docs\WordPressToServerlessAWS\` | All documentation | Reference |
| S3: `bitbybitcoding.christianconservativestoday.com-site` | Production static files | Live |

---

## How to Update the Live Site

```batch
REM 1. Edit files in bitbybit-static
REM 2. Sync to S3
aws s3 sync c:\xampp\htdocs\bitbybit-static s3://bitbybitcoding.christianconservativestoday.com-site --profile ekewaka

REM 3. Invalidate CloudFront cache
aws cloudfront create-invalidation --distribution-id E2EOS0SKGKYS3P --paths "/*" --profile ekewaka
```

---

## Next Phase: Full Dynamic Backend

### Goal
Replace the static HTML export with a dynamic serverless backend that mirrors the christianconservativestoday.com architecture. The frontend keeps the exact Inspiro theme look, but content is served dynamically from DynamoDB via Lambda functions. An admin panel allows creating/editing posts, modules, and pages without touching WordPress.

### Reference Architecture
The christianconservativestoday.com codebase is at:
`C:\Users\Ed\Documents\Programming\AWS\Downloader\`

Key patterns to replicate:
- **Terraform modules** at `terraform/modules/` (lambda, dynamodb, api-gateway, s3, cloudfront, etc.)
- **Unified API Gateway** with path-based routing (`/articles`, `/admin`, `/auth`, etc.)
- **Lambda functions** in Python with CORS headers, circuit breakers, rate limiting, JWT auth
- **DynamoDB tables** with PAY_PER_REQUEST billing
- **Static HTML pages** in S3 that call API Gateway via JavaScript fetch
- **Admin pages** (admin.html, create-article.html, edit-article.html) for content management
- **Auth system** using JWT tokens with admin-users DynamoDB table

### What Needs to Be Built

#### DynamoDB Tables
| Table | Hash Key | Purpose |
|-------|----------|---------|
| `bitbybit-posts` | `post_id` (S) | Blog posts and modules |
| `bitbybit-pages` | `page_id` (S) | Static pages (about, learn, contact, etc.) |
| `bitbybit-categories` | `category_id` (S) | Post categories |
| `bitbybit-admin-users` | `username` (S) | Admin authentication |
| `bitbybit-media` | `media_id` (S) | Media metadata |

#### Lambda Functions
| Function | Purpose | Pattern From |
|----------|---------|-------------|
| `bitbybit-content-api` | CRUD for posts, pages, modules | `articles_api/index.py` |
| `bitbybit-admin-api` | Admin operations, media upload | `admin_api/index.py` |
| `bitbybit-auth-api` | JWT login/auth | `auth_api/index.py` |
| `bitbybit-contact-api` | Contact form → SES | Already deployed |

#### Frontend Pages to Modify
The existing static HTML pages need JavaScript added to:
1. Fetch content from API Gateway on page load
2. Render posts/modules dynamically from DynamoDB data
3. Keep the exact same Inspiro theme CSS/layout

#### Admin Pages to Create
| Page | Purpose |
|------|---------|
| `admin.html` | Dashboard — list all posts, pages |
| `login.html` | Admin login |
| `create-post.html` | Create new post/module |
| `edit-post.html` | Edit existing post/module |
| `admin-pages.html` | Manage static pages |

#### Terraform Additions
- New DynamoDB tables
- New Lambda functions
- New API Gateway routes on existing gateway
- Or create a separate `bitbybit-api` API Gateway

### Migration Path (WordPress → Dynamic)
1. Export WordPress content to DynamoDB (posts, pages, modules → tables)
2. Build Lambda CRUD APIs
3. Add JavaScript to existing HTML pages to fetch from API
4. Build admin panel
5. Test everything
6. Switch from static to dynamic

### Key Decisions Needed
- **Separate API Gateway or add to existing unified API?** Recommend separate for isolation
- **Reuse existing Terraform modules from christianconservativestoday.com?** Yes — copy the modules directory
- **Same AWS account/profile?** Yes — ekewaka profile, same account
- **Auth system?** Simple JWT like christianconservativestoday.com (admin-users table + auth-api Lambda)

---

## AWS Account Details
- **Profile:** ekewaka
- **Account ID:** 371751795928
- **Region:** us-east-1
- **State bucket:** techcross-terraform-state
- **Hosted zone:** Z0541028204EB3LCQMNQD (christianconservativestoday.com)

## WordPress Site Details
- **Site name:** Bit By Bit Coding
- **WP URL:** http://edwardfong.onthewifi.com/bitbybit (also https)
- **Database:** bitbybit_db (MySQL, table prefix `d9_`)
- **Theme:** Inspiro + Inspiro Child
- **Custom Post Type:** Modules (registered in child theme functions.php)
- **Key plugins:** ACF, Custom Post Type UI, WPZOOM Forms, WPZOOM Portfolio, Social Icons

## Contact
- **Email:** hawaiianintucson@gmail.com
- **Domain:** bitbybitcoding.christianconservativestoday.com
