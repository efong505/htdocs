# BitByBit — WordPress to Serverless AWS Migration Plan

## Current Site Analysis

- **URL**: edwardfong.onthewifi.com/bitbybit
- **Database**: `bitbybit_db` (MySQL, table prefix `d9_`)
- **Theme**: Inspiro (parent) + Inspiro Child
- **WordPress Version**: Standard WP installation on XAMPP

### Plugins Installed

| Plugin | Purpose | Serverless Replacement |
|--------|---------|----------------------|
| Advanced Custom Fields | Custom field data on posts | Static JSON / DynamoDB |
| All-in-One WP Migration | Backup/migration | Not needed (S3 versioning) |
| Better Search Replace | DB find/replace | Not needed |
| Child Theme Configurator | Theme customization | Built into static CSS |
| Custom Post Type UI | "Modules" CPT | Static pages or DynamoDB items |
| Duplicate Page | Content management | Not needed |
| Instagram Widget by WPZOOM | Instagram feed | Client-side JS widget or Lambda |
| Jetpack | Stats, security, CDN | CloudFront + CloudWatch |
| One Click Demo Import | Demo content | Not needed |
| Social Icons Widget by WPZOOM | Social media links | Static HTML/CSS |
| Tuxedo Big File Uploads | Large file uploads | S3 direct upload |
| WPZOOM Forms | Contact forms | API Gateway + Lambda + SES |
| WPZOOM Portfolio | Portfolio display | Static HTML pages |
| WPZOOM Video Popup Block | Video lightbox | Client-side JS library |

### Custom Post Type: Modules

The child theme registers a "Modules" CPT with:
- Title, editor, thumbnail, excerpt, comments, custom fields
- Taxonomies: category, post_tag, portfolio
- Archive page at `/modules/` with custom template
- Single page template with previous post navigation

### Media Uploads

```
uploads/
├── 2024/ (Apr, May, Sep, Oct, Nov, Dec)
├── 2025/ (Feb, Apr, May, Jun, Jul)
└── 2026/ (Apr)
```

---

## Migration Strategy

### Phase 1: Content Export & Static Site Generation

#### Step 1: Export WordPress Content

Use the export script at `bitbybit-serverless/scripts/export-content.php`:

```bash
cd bitbybit-serverless
php scripts/export-content.php
```

This exports all posts, pages, and modules as markdown files with frontmatter into `bitbybit-serverless/content/`.

Alternatively, use the WordPress REST API (already enabled for Modules CPT):
```
GET /wp-json/wp/v2/modules
GET /wp-json/wp/v2/posts
GET /wp-json/wp/v2/pages
```

#### Step 2: Static Site Framework

**Using: Astro** (lightweight, fast, perfect for portfolio sites)

#### Step 3: Site Structure

```
bitbybit-serverless/site/
├── src/
│   ├── pages/
│   │   ├── index.astro          # Homepage
│   │   ├── modules/
│   │   │   ├── index.astro      # Modules archive (replaces archive-modules.php)
│   │   │   └── [slug].astro     # Single module (replaces single-modules.php)
│   │   └── [...slug].astro      # Dynamic pages
│   ├── layouts/
│   │   └── BaseLayout.astro     # Header + Footer (replaces header.php/footer.php)
│   ├── components/
│   │   ├── ContactForm.astro    # Replaces WPZOOM Forms
│   │   ├── SocialIcons.astro    # Replaces Social Icons Widget
│   │   ├── VideoPopup.astro     # Replaces Video Popup Block
│   │   └── InstagramFeed.astro  # Replaces Instagram Widget
│   ├── content/
│   │   ├── modules/             # Markdown files for each Module post
│   │   └── posts/               # Markdown files for blog posts
│   └── styles/
│       └── global.css           # Ported from Inspiro theme + child overrides
├── public/
│   └── media/                   # All wp-content/uploads migrated here
├── astro.config.mjs
└── package.json
```

### Phase 2: AWS Infrastructure via Terraform

All infrastructure is defined in `bitbybit-serverless/infrastructure/*.tf`.

#### Terraform Files

| File | What It Creates |
|------|----------------|
| `main.tf` | AWS provider, S3 backend for state |
| `variables.tf` | Input vars: domain_name, hosted_zone_id, contact_email |
| `s3_cloudfront.tf` | S3 bucket (versioned, private), CloudFront distribution with OAC |
| `dns_ssl.tf` | ACM certificate (DNS-validated), Route 53 A record alias |
| `lambda_api.tf` | Lambda contact form, IAM role, API Gateway HTTP API |
| `outputs.tf` | Bucket name, CloudFront ID/domain, API endpoint |

#### Deploy Infrastructure

```bash
cd bitbybit-serverless/infrastructure

# 1. Create your tfvars
cp terraform.tfvars.example terraform.tfvars
# Edit terraform.tfvars with your actual values:
#   domain_name    = "yourdomain.com"
#   hosted_zone_id = "Z1234567890"
#   contact_email  = "you@email.com"

# 2. Initialize Terraform (downloads AWS provider, sets up S3 backend)
terraform init

# 3. Preview what will be created
terraform plan

# 4. Apply — creates all AWS resources
terraform apply

# 5. Note the outputs:
#   site_bucket_name          = "yourdomain.com-site"
#   cloudfront_distribution_id = "E1234567890"
#   cloudfront_domain_name     = "d1234567890.cloudfront.net"
#   api_endpoint               = "https://abc123.execute-api.us-east-1.amazonaws.com"
```

#### What Terraform Creates

```
terraform apply
  → S3 bucket (private, versioned, OAC policy)
  → CloudFront distribution (HTTPS, caching, API passthrough at /api/*)
  → ACM certificate (auto DNS validation via Route 53)
  → Route 53 A record (alias → CloudFront)
  → Lambda function (Python 3.12, contact form → SES)
  → IAM role (Lambda execution + SES send)
  → API Gateway HTTP API (POST /api/contact)
  → Lambda permission (API Gateway → Lambda invoke)
```

### Phase 3: Media Migration

```bash
cd bitbybit-serverless/scripts
sync-media.bat yourdomain.com-site
```

Or manually:
```bash
aws s3 sync ../../bitbybit/wp-content/uploads s3://yourdomain.com-site/media/ \
  --cache-control "max-age=31536000,public" \
  --exclude "*.php"
```

### Phase 4: Build & Deploy Site

```bash
cd bitbybit-serverless/site

# Install dependencies
npm install

# Build static site
npm run build

# Deploy to S3 (use bucket name from terraform output)
aws s3 sync ./dist/ s3://yourdomain.com-site --delete

# Invalidate CloudFront cache (use distribution ID from terraform output)
aws cloudfront create-invalidation \
  --distribution-id E1234567890 \
  --paths "/*"
```

### Phase 5: Verify & Go Live

1. Visit `https://d1234567890.cloudfront.net` to test via CloudFront domain
2. Verify all pages render correctly
3. Test contact form submission
4. Check media/images load from S3
5. Once verified, the Route 53 record already points your domain to CloudFront

---

## WordPress Feature → Serverless Mapping

| WordPress Feature | How It Works Now | Serverless Replacement |
|---|---|---|
| Homepage | Inspiro theme full-screen video hero | Static HTML + CSS + video hosted on S3 |
| Modules archive | `archive-modules.php` queries DB | Pre-built static HTML page listing all modules |
| Single module | `single-modules.php` with WP Loop | Pre-built static HTML per module from markdown |
| Contact form | WPZOOM Forms plugin | HTML form → API Gateway → Lambda → SES |
| Instagram feed | WPZOOM Instagram Widget | Client-side JS using Instagram Basic Display API |
| Social icons | WPZOOM Social Icons Widget | Static SVG icons in HTML |
| Video popups | WPZOOM Video Popup Block | Lightweight JS library (e.g., GLightbox) |
| Portfolio | WPZOOM Portfolio plugin | Static gallery pages built at compile time |
| Comments | WordPress comments | Remove, or use Disqus/Giscus (static-friendly) |
| Search | WordPress default search | Client-side search (Pagefind or Lunr.js) |
| SEO | None installed | Meta tags built into static HTML at build time |
| Analytics | None installed | Google Analytics 4 snippet in base layout |
| Caching | LiteSpeed Cache | CloudFront handles all caching automatically |

---

## Deployment Pipeline (CI/CD)

```
GitHub Push → GitHub Actions → Build (Astro) → Deploy to S3 → Invalidate CloudFront
```

```yaml
# .github/workflows/deploy.yml
name: Deploy BitByBit
on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with:
          node-version: 20
      - run: npm ci
      - run: npm run build
      - uses: aws-actions/configure-aws-credentials@v4
        with:
          aws-access-key-id: ${{ secrets.AWS_ACCESS_KEY_ID }}
          aws-secret-access-key: ${{ secrets.AWS_SECRET_ACCESS_KEY }}
          aws-region: us-east-1
      - run: aws s3 sync ./dist/ s3://${{ secrets.S3_BUCKET }} --delete
      - run: |
          aws cloudfront create-invalidation \
            --distribution-id ${{ secrets.CF_DISTRIBUTION_ID }} \
            --paths "/*"
```

> **Note:** Infrastructure changes are managed separately via `terraform apply`, not through CI/CD.
> Only the static site content is deployed via GitHub Actions.

---

## Checklist

- [ ] Export all WordPress content (posts, pages, modules, media)
- [ ] Set up Astro project with Inspiro-inspired design
- [ ] Configure `terraform.tfvars` with real values
- [ ] Run `terraform init && terraform apply`
- [ ] Migrate media to S3 via `sync-media.bat`
- [ ] Build static pages for all modules
- [ ] Build and deploy Astro site to S3
- [ ] Test CloudFront domain
- [ ] Test contact form (Lambda + SES)
- [ ] Set up GitHub Actions CI/CD pipeline
- [ ] DNS cutover — go live
- [ ] Decommission WordPress installation
