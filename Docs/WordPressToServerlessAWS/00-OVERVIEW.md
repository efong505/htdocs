# WordPress to Serverless AWS Migration — Master Plan

## Project Overview

Migration of three WordPress sites from XAMPP/traditional hosting to fully serverless AWS architecture, modeled after the existing **christianconservativestoday.com** serverless platform.

## Sites to Migrate

| # | Site | Domain | Theme | Complexity | Status |
|---|------|--------|-------|------------|--------|
| 1 | bitbybit | edwardfong.onthewifi.com/bitbybit | Inspiro + Child | Low | 🔄 In Progress |
| 2 | next-level | nextlevelwebdevelopers.com | X Theme | Medium | ⏳ Pending |
| 3 | altoa | altoa (TBD) | Multiple (bc-consulting, lyrielle, x1, pro) | High | ⏳ Pending |

## Target AWS Architecture (All Sites)

```
┌─────────┐     ┌──────────┐     ┌────────────┐     ┌─────┐
│  User    │────▶│ Route 53 │────▶│ CloudFront │────▶│ S3  │
└─────────┘     └──────────┘     └─────┬──────┘     └─────┘
                                       │              (Static Site)
                                       ▼
                                ┌─────────────┐     ┌────────┐
                                │ API Gateway  │────▶│ Lambda │
                                └─────────────┘     └───┬────┘
                                                        │
                                        ┌───────────────┼───────────────┐
                                        ▼               ▼               ▼
                                  ┌──────────┐   ┌───────────┐   ┌─────────┐
                                  │ DynamoDB  │   │    SES    │   │ Cognito │
                                  └──────────┘   └───────────┘   └─────────┘
                                  (Data Store)    (Email)         (Auth)
```

## AWS Services Used

| Service | Purpose | Free Tier |
|---------|---------|-----------|
| S3 | Static site hosting (HTML/CSS/JS/media) | 5 GB storage, 20K GET requests |
| CloudFront | CDN, HTTPS, caching | 1 TB transfer/month, 10M requests |
| Route 53 | DNS management | $0.50/hosted zone/month |
| API Gateway | REST API endpoints | 1M calls/month |
| Lambda | Backend logic (forms, dynamic content) | 1M requests, 400K GB-seconds |
| DynamoDB | NoSQL database for dynamic content | 25 GB storage, 25 RCU/WCU |
| SES | Transactional email (contact forms) | 62K emails/month (from Lambda) |
| Cognito | User authentication (if needed) | 50K MAU free |
| ACM | SSL/TLS certificates | Free |

## Migration Order & Rationale

1. **bitbybit** — Portfolio/showcase site with custom post type (Modules). Minimal dynamic features. Ideal first migration.
2. **next-level** — Has contact forms, booking, WooCommerce, bbPress. Medium complexity.
3. **altoa** — Full WooCommerce store, BuddyPress community, multiple payment integrations. Most complex.

## Infrastructure as Code

**Primary: Terraform (HCL)** — All infrastructure is defined in `.tf` files under `bitbybit-serverless/infrastructure/`.

CloudFormation (`template.yaml`) is kept alongside as an alternative but Terraform is the tool we use for deployments.

### Terraform Files

| File | Resources |
|------|-----------|
| `main.tf` | Provider config, backend (S3 state) |
| `variables.tf` | Input variables (domain, hosted zone, email) |
| `s3_cloudfront.tf` | S3 bucket, CloudFront distribution, OAC |
| `dns_ssl.tf` | ACM certificate, Route 53 DNS record |
| `lambda_api.tf` | Lambda function, IAM role, API Gateway |
| `outputs.tf` | Stack outputs (bucket name, CF domain, API endpoint) |

### Deploy Commands

```bash
cd bitbybit-serverless/infrastructure
cp terraform.tfvars.example terraform.tfvars   # fill in your values
terraform init
terraform plan
terraform apply
```

## Documents in This Directory

| File | Description |
|------|-------------|
| `00-OVERVIEW.md` | This file — master plan and architecture |
| `01-BITBYBIT-MIGRATION.md` | Detailed migration plan for bitbybit |
| `02-NEXTLEVEL-MIGRATION.md` | Detailed migration plan for next-level (TBD) |
| `03-ALTOA-MIGRATION.md` | Detailed migration plan for altoa (TBD) |
| `04-AWS-INFRASTRUCTURE.md` | Shared AWS infrastructure setup (IAM, billing, etc.) |
| `05-COST-ANALYSIS.md` | Cost comparison: WordPress hosting vs. AWS serverless |
| `06-MIGRATION-SCRIPT-GUIDE.md` | How to use the automated migration scripts on any site |

## Estimated Cost Savings

| Scenario | WordPress Hosting (3 sites) | AWS Serverless (3 sites) | Savings |
|----------|----------------------------|--------------------------|---------|
| Low traffic (<1K visits/mo) | $30–90/mo | $1–5/mo | 85–95% |
| Medium traffic (1K–10K/mo) | $100–300/mo | $5–20/mo | 90–95% |
| High traffic (10K+/mo) | $300+/mo | Scales per-request | Variable |
