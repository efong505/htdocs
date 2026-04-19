# Cost Analysis: WordPress Hosting vs. AWS Serverless

## Current WordPress Costs (Estimated)

Assuming shared or managed WordPress hosting for 3 sites:

| Item | Shared Hosting | Managed WP Hosting |
|------|---------------|-------------------|
| Hosting (3 sites) | $10–30/mo | $75–300/mo |
| SSL Certificates | Free (Let's Encrypt) | Included |
| CDN (Cloudflare) | Free–$20/mo | Sometimes included |
| Email (SMTP) | $0–10/mo | $0–10/mo |
| Backups | $0–10/mo | Usually included |
| Security (Wordfence, etc.) | Free–$100/yr | Sometimes included |
| **Total** | **$10–50/mo** | **$75–320/mo** |

## AWS Serverless Costs (Estimated)

### Per-Site Breakdown (Low Traffic: <1,000 visits/month)

| Service | Usage | Monthly Cost |
|---------|-------|-------------|
| S3 | 500 MB storage, 10K requests | $0.02 |
| CloudFront | 2 GB transfer, 20K requests | $0.00 (free tier) |
| Route 53 | 1 hosted zone | $0.50 |
| Lambda | 1K invocations (contact form) | $0.00 (free tier) |
| API Gateway | 1K requests | $0.00 (free tier) |
| DynamoDB | <1 GB, minimal reads | $0.00 (free tier) |
| SES | 100 emails | $0.00 (free tier from Lambda) |
| ACM | 1 certificate | $0.00 (always free) |
| **Site Total** | | **~$0.52/mo** |

### 3 Sites Combined

| Traffic Level | Monthly Cost | Annual Cost |
|--------------|-------------|-------------|
| Low (<1K visits/mo each) | $1.50–5.00 | $18–60 |
| Medium (1K–10K visits/mo each) | $5–20 | $60–240 |
| High (10K–50K visits/mo each) | $15–50 | $180–600 |

### Free Tier Breakdown (First 12 Months)

| Service | Free Tier Allowance |
|---------|-------------------|
| S3 | 5 GB storage, 20K GET, 2K PUT |
| CloudFront | 1 TB transfer, 10M requests/mo |
| Lambda | 1M requests, 400K GB-seconds/mo |
| API Gateway | 1M REST API calls/mo |
| DynamoDB | 25 GB storage, 25 WCU, 25 RCU |
| SES | 62K emails/mo (from Lambda) |

### After Free Tier Expires

Costs increase slightly but remain minimal for low-traffic sites:

| Service | Post-Free-Tier Cost (Low Traffic) |
|---------|----------------------------------|
| S3 | $0.02–0.05/mo |
| CloudFront | $0.10–0.50/mo |
| Lambda | $0.00–0.01/mo |
| API Gateway | $0.00–0.01/mo |
| DynamoDB | $0.25–1.00/mo |
| Route 53 | $0.50/zone/mo (never free) |

## Cost Comparison Summary

| Scenario | WordPress (3 sites) | AWS Serverless (3 sites) | Annual Savings |
|----------|---------------------|--------------------------|----------------|
| Budget shared hosting | $120–600/yr | $18–60/yr | $100–540/yr |
| Managed WP hosting | $900–3,840/yr | $60–240/yr | $840–3,600/yr |

## Non-Cost Benefits

| Benefit | WordPress | AWS Serverless |
|---------|-----------|---------------|
| Security patches | Manual or plugin-managed | No server to patch |
| DDoS protection | Plugin-based (Wordfence) | CloudFront Shield Standard (free) |
| Scaling | Limited by server | Automatic, unlimited |
| Uptime | Depends on host | 99.99% SLA (CloudFront) |
| SSL | Let's Encrypt (renew manually) | ACM (auto-renew, free) |
| Backups | Plugin or host-managed | S3 versioning (automatic) |
| Speed | Depends on caching plugins | Edge-cached globally via CloudFront |
| Maintenance | WP core + plugin updates | Zero server maintenance |

## Use AWS Pricing Calculator for Precise Estimates

For detailed, customized pricing: https://calculator.aws
