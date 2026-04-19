# Altoa — WordPress to Serverless AWS Migration Plan

> **Status: ⏳ Pending** — Will begin after next-level migration is complete.

## Current Site Analysis

- **Theme**: Multiple installed (bc-consulting, bc-store, lyrielle, pro, x1, twentytwentyfive)
- **Database**: MySQL on XAMPP

### Key Features to Migrate

| Feature | Plugin | Serverless Replacement |
|---------|--------|----------------------|
| E-commerce | WooCommerce + extensions | Lambda + DynamoDB + Stripe/PayPal |
| Community | BuddyPress | Lambda + DynamoDB + Cognito |
| Contact forms | Contact Form 7 | API Gateway + Lambda + SES |
| Email marketing | Mailchimp for WP/WooCommerce | Lambda calling Mailchimp API |
| Payments | WooCommerce Payments, PayPal | Lambda + payment provider APIs |
| Shipping | Flexible Shipping | Lambda shipping calculator |
| SEO | Yoast SEO, All in One SEO | Meta tags at build time |
| Analytics | MonsterInsights | GA4 snippet |
| Caching | LiteSpeed Cache | CloudFront |
| Forms | WPForms Lite | API Gateway + Lambda + SES |
| PDF viewer | PDF Embedder | S3-hosted PDFs + JS viewer |
| Newsletters | MailPoet | Lambda + SES or Mailchimp API |
| Polls | Poll WP | Lambda + DynamoDB |

### Complexity Notes

- Most complex migration — full e-commerce + community platform
- BuddyPress (user profiles, activity streams, messaging) requires significant Lambda + DynamoDB + Cognito work
- Multiple payment integrations (WooCommerce Payments, PayPal) need careful API migration
- Multiple themes installed suggests the site may have gone through redesigns — need to identify active theme

## Infrastructure

Will follow the same Terraform pattern as bitbybit and next-level, with additional `.tf` files for:
- Full WooCommerce replacement (DynamoDB product/order tables, cart/checkout/shipping Lambda functions)
- BuddyPress replacement (Cognito user pools, DynamoDB activity/messaging tables)
- Multiple payment provider integrations (Lambda + PayPal/Stripe APIs)
- Newsletter system (Lambda + SES or Mailchimp API)

## Detailed Plan

_To be written after next-level migration is validated._
