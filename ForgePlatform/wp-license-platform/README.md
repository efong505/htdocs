# WP License Platform

A WordPress plugin that turns your website into a digital product store with PayPal payment processing, license key management, global VAT compliance, and a customer portal.

Built as a reusable platform — add any digital product (plugins, themes, etc.) without rebuilding infrastructure.

---

## Documentation

| # | Document | Description |
|---|----------|-------------|
| 1 | [Project Plan](docs/01-PROJECT-PLAN.md) | Architecture, database schema, class design, file structure, 7 implementation phases, testing plan |
| 2 | [PayPal Integration](docs/02-PAYPAL-INTEGRATION.md) | OAuth, create/capture orders, JavaScript SDK, webhooks, refunds, sandbox testing |
| 3 | [VAT Compliance](docs/03-VAT-COMPLIANCE.md) | EU/UK/global VAT rates, two-piece evidence (HMRC), IP geolocation, VIES validation, reverse charge, invoice requirements |
| 4 | [License System](docs/04-LICENSE-SYSTEM.md) | Key generation, REST API (validate/activate/deactivate), site tracking, expiry lifecycle, rate limiting |
| 5 | [Customer Portal & Email](docs/05-CUSTOMER-PORTAL-EMAIL.md) | Shortcode pages, license management, secure downloads, email templates, pricing table, invoice generation |
| 6 | [API Reference](docs/06-API-REFERENCE.md) | All REST endpoints with parameters, responses, error codes, authentication |
| 7 | [Monetization Strategy](docs/07-MONETIZATION-STRATEGY.md) | Market analysis, freemium model, pricing, competitive advantages, revenue projections, launch roadmap |

---

## Project Structure

```
wp-license-platform/
├── README.md
├── docs/
│   ├── 01-PROJECT-PLAN.md
│   ├── 02-PAYPAL-INTEGRATION.md
│   ├── 03-VAT-COMPLIANCE.md
│   ├── 04-LICENSE-SYSTEM.md
│   ├── 05-CUSTOMER-PORTAL-EMAIL.md
│   └── 06-API-REFERENCE.md
└── plugin/
    └── wp-license-platform/       ← WordPress plugin (to be built)
        ├── wp-license-platform.php
        ├── uninstall.php
        ├── includes/              ← Core classes
        ├── admin/                 ← Admin UI
        ├── public/                ← Checkout, portal pages
        ├── templates/             ← Email + invoice templates
        └── languages/
```

---

## How It Works

```
1. You add a product (e.g., "WP S3 Backup Pro") with pricing tiers
2. Customer visits your pricing page → clicks "Buy Now"
3. Checkout page collects billing country → calculates VAT
4. Customer pays via PayPal (credit card or PayPal account)
5. Platform generates license key + sends confirmation email
6. Customer installs Pro plugin → enters license key
7. Pro plugin calls /validate API → features unlocked
8. Daily re-validation (cached) → stays active until expiry
9. Renewal reminders sent 30/7/1 days before expiry
10. Customer renews via PayPal → new expiry date
```

---

## Key Features

| Feature | Details |
|---------|---------|
| **Multi-product** | Add unlimited products, each with multiple pricing tiers |
| **PayPal REST API** | Direct integration, no SDK, OAuth 2.0 authentication |
| **Global VAT** | EU, UK, AU rates with two-piece evidence for HMRC compliance |
| **License keys** | Auto-generated, site-limited, time-limited, with activation tracking |
| **REST API** | /validate, /activate, /deactivate for any Pro plugin to call |
| **Customer portal** | Shortcode-based pages for license management and downloads |
| **Secure downloads** | Token-based, time-limited download URLs |
| **Email system** | Purchase confirmation, license delivery, renewal reminders |
| **Invoices** | VAT-compliant HTML invoices with all required fields |
| **Admin panel** | Manage products, orders, licenses, customers, reports |
| **Encrypted credentials** | PayPal secrets stored with AES-256-CBC |
| **Rate limiting** | Prevents brute-force license key guessing |

---

## Implementation Status

| Phase | Status |
|-------|--------|
| Documentation | ✅ Complete |
| Phase 1: Foundation (tables, products, settings) | ✅ Complete |
| Phase 2: PayPal Integration | ✅ Complete |
| Phase 3: License System + API | ✅ Complete |
| Phase 4: VAT Compliance | ✅ Complete |
| Phase 5: Customer Portal + Email | ✅ Complete |
| Phase 6: Invoicing + Reports | ✅ Basic (HTML invoices, admin dashboard stats) |
| Phase 7: Subscriptions | ⬜ Future (PayPal subscription plans) |

---

## Related Projects

| Project | Purpose |
|---------|---------|
| **wp-s3-backup** | Free backup plugin (the first product sold through this platform) |
| **wp-s3-backup-pro** | Pro plugin that validates licenses against this platform |
| **wp-license-platform** (this) | The platform itself |

---

## Quick Start (after building)

1. Install the plugin on your website (ekewaka.com)
2. Go to **License Platform → Settings** and enter PayPal credentials
3. Go to **License Platform → Products** and add "WP S3 Backup Pro"
4. Add pricing tiers (Personal $49, Professional $99, Agency $149)
5. Create WordPress pages with shortcodes: `[wplp_checkout]`, `[wplp_portal]`, `[wplp_pricing product="wp-s3-backup-pro"]`
6. Test with PayPal sandbox
7. Switch to live credentials when ready
