# Next Level Web Developers — WordPress to Serverless AWS Migration Plan

> **Status: ⏳ Pending** — Will begin after bitbybit migration is complete.

## Current Site Analysis

- **URL**: nextlevelwebdevelopers.com
- **Theme**: X Theme
- **Database**: MySQL on XAMPP

### Key Features to Migrate

| Feature | Plugin | Serverless Replacement |
|---------|--------|----------------------|
| Contact forms | Contact Form 7 | API Gateway + Lambda + SES |
| E-commerce | WooCommerce | Lambda + DynamoDB + Stripe/PayPal |
| Booking system | Booking plugin | Lambda + DynamoDB + EventBridge |
| Forums | bbPress | Lambda + DynamoDB or external service |
| Email marketing | Mailchimp for WP | Lambda calling Mailchimp API |
| Sliders | RevSlider, Master Slider | Static HTML/CSS/JS sliders |
| SEO | Yoast SEO | Meta tags built at compile time |
| Analytics | MonsterInsights | GA4 snippet in HTML |
| Caching | WP Super Cache | CloudFront |
| Membership | Theme My Login | Cognito |
| Payments | WooCommerce + PayPal | Lambda + PayPal/Stripe API |

### Complexity Notes

- WooCommerce migration is the biggest lift — product catalog, cart, checkout all need Lambda + DynamoDB
- bbPress forums require user auth (Cognito) and real-time-ish data (DynamoDB Streams or AppSync)
- Booking system needs scheduling logic (EventBridge Scheduler)

## Infrastructure

Will follow the same Terraform pattern as bitbybit (`main.tf`, `s3_cloudfront.tf`, `dns_ssl.tf`, `lambda_api.tf`) with additional `.tf` files for:
- WooCommerce replacement (DynamoDB product tables, cart/checkout Lambda functions)
- Booking system (EventBridge Scheduler + DynamoDB)
- bbPress replacement (DynamoDB + Cognito auth)

## Detailed Plan

_To be written after bitbybit migration is validated._
