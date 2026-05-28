# Forge Ecosystem — Unified Brand & Architecture Handoff

*Master context document for AI-assisted development, creative work, infrastructure planning, and ecosystem decision-making.*

---

## 1. Forge Ecosystem Overview

Forge is a business systems and infrastructure ecosystem focused on ownership, operational resilience, lightweight tooling, and modern serverless architecture.

### The Products

| Product | Domain | Purpose | Status |
|---------|--------|---------|--------|
| **BackForge** | Data Protection | WordPress backup to Amazon S3 | ✅ Complete (Free + Pro) |
| **LicenseForge** | Commerce | Self-hosted plugin licensing & digital product sales | ✅ Complete |
| **DripForge** | Communication | Self-hosted email drip sequence automation | ✅ Complete (rebrand pending) |
| **ShieldForge** | Security | Self-hosted WordPress firewall & hardening | 📋 Planning complete |

### The Platform (Future)

| Component | Purpose | Status |
|-----------|---------|--------|
| **ForgePlatform** | Unified serverless AWS infrastructure | 📋 Architecture defined |
| **Forge Cloud** | Optional SaaS layer for multi-site management | 🔮 Future concept |

### What Forge Is

Forge is a suite of self-hosted WordPress tools and supporting infrastructure for operators who value control, performance, and independence. Each product solves one operational concern completely. Together, they form a coherent stack that eliminates SaaS dependency for core business operations.

### What Forge Is Not

- Not a SaaS platform (products are self-hosted WordPress plugins)
- Not a marketplace (products are sold directly, not through a third-party store)
- Not a framework (each product is standalone, no shared runtime dependency)
- Not enterprise software (targets solo operators, freelancers, and small agencies)

---

## 2. Core Forge Philosophy

### Ownership Over Dependency

Every Forge product exists because a SaaS alternative takes something from the operator: revenue share, data custody, platform control, or operational autonomy. Forge returns that ownership.

| What operators lose to SaaS | What Forge returns |
|-----------------------------|-------------------|
| 5–10% revenue share (Freemius, LemonSqueezy) | Zero per-transaction fees (LicenseForge) |
| Per-subscriber pricing (Mailchimp, ConvertKit) | Unlimited subscribers, zero monthly fees (DripForge) |
| Cloud-dependent backups (BlogVault) | Your S3 bucket, your data (BackForge) |
| Cloud-dependent security scanning (Sucuri) | Local scanning, zero data exfiltration (ShieldForge) |

### Clarity Over Bloat

Each product does one thing well. No feature creep into adjacent domains. No bundled frameworks. No Composer dependencies. No React admin panels. The constraint is intentional — lightweight tools are faster, more reliable, and easier to maintain.

| Metric | Forge Target | Typical Competitor |
|--------|-------------|-------------------|
| Plugin size | 50–150KB | 5–30MB |
| External dependencies | 0 | 10–50 packages |
| Admin JS framework | Vanilla JS | React/Vue (500KB+) |
| PHP dependencies | WordPress APIs only | Composer autoload |

### Infrastructure Over Patchwork

Forge products share architectural patterns, security models, and design language. They are not random tools stitched together — they are a coherent system built on the same principles, by the same developer, for the same audience.

### Systems Over Chaos

Forge favors repeatable, documented, infrastructure-as-code approaches. Terraform manages AWS resources. GitHub Actions manages deployment. Documentation is numbered and sequential. Every decision is recorded.

---

## 3. Shared Operational Principles

These principles apply to every Forge product without exception:

### Security Model
- AES-256-CBC credential encryption using WordPress salts
- Nonce verification on every form submission and AJAX call
- `manage_options` capability check on every admin action
- Input sanitization: `sanitize_text_field()`, `absint()`, `sanitize_email()`
- Output escaping: `esc_html()`, `esc_attr()`, `esc_url()`
- Direct access prevention: `if (!defined('ABSPATH')) exit;` on every PHP file
- No `exec()`, `shell_exec()`, `system()`, or `proc_open()`
- Prepared statements for all database queries

### WordPress.org Compliance
- GPL v2+ license
- No bundled SDKs or Composer dependencies
- No tracking, analytics, or phoning home
- No obfuscated code
- All strings internationalized with `__()` and `_e()`
- Unique prefix on all functions, classes, options, hooks
- readme.txt in WordPress.org format

### Code Conventions
- Class naming: `PREFIX_Class_Name` (PascalCase with uppercase prefix)
- File naming: `class-prefix-name.php` (lowercase hyphenated)
- Function/hook prefix: unique per product (`wps3b_`, `nlde_`, `wplp_`, `sfs_`)
- Tab indentation (WordPress coding standard)
- PHPDoc blocks on all classes and methods
- One class per file, one responsibility per class

### Data Handling
- Credentials encrypted at rest, decrypted only in memory
- Temp files cleaned up on success AND failure
- No plaintext secrets in database or filesystem
- Protected directories with `.htaccess` + `index.php`

---

## 4. Shared Infrastructure Philosophy

### Current State: WordPress-Native

All Forge products currently run as WordPress plugins on standard hosting. Infrastructure requirements are minimal:
- PHP 7.4+ with OpenSSL and ZipArchive
- MySQL/MariaDB via WordPress
- wp-cron for scheduling
- `wp_remote_request()` for external HTTP

### Future State: AWS Serverless Platform

The ForgePlatform project defines a unified serverless infrastructure:

| Layer | Technology | Purpose |
|-------|-----------|---------|
| Frontend | S3 + CloudFront | Static site hosting (product pages, portal) |
| API | API Gateway + Lambda | License validation, checkout, portal, leads |
| Database | DynamoDB | Products, customers, orders, licenses, leads |
| Email | SES | Transactional emails, drip sequences |
| Notifications | SNS | Event-driven notifications |
| Infrastructure | Terraform | All resource provisioning |
| CI/CD | GitHub Actions | Automated deployment |
| State | S3 + DynamoDB | Terraform state management |

### Infrastructure Principles
- **Modular Terraform** — reusable modules per resource type, composed per environment
- **Environment separation** — dev/staging/prod with independent state files
- **Least privilege** — IAM policies scoped to specific resources per function
- **No servers to manage** — fully serverless, pay-per-use
- **Infrastructure as documentation** — Terraform IS the architecture diagram

---

## 5. Shared Visual Branding Direction

### Design System Foundation

All Forge products share a dark SaaS aesthetic with product-specific accent colors.

#### Shared Colors (Every Product)

| Role | Hex | Usage |
|------|-----|-------|
| Background (Primary) | `#0F172A` | Page background |
| Background (Card) | `#1E293B` | Elevated surfaces |
| Background (Hover) | `#263548` | Card hover state |
| Text (Primary) | `#E5E7EB` | Body text |
| Text (Muted) | `#94A3B8` | Secondary text |
| Text (Dim) | `#64748B` | Tertiary text |
| Border | `#334155` | Dividers, card edges |
| Border (Light) | `#475569` | Hover borders |
| White | `#FFFFFF` | Headings, emphasis |
| Success | `#22C55E` | Positive states |
| Warning | `#F59E0B` | Attention states |
| Error | `#EF4444` | Negative states |
| Pro Accent | `#6366F1` | Pro badges, upsell (all products) |

#### Product Accent Colors

| Product | Primary Accent | Dark Variant | Glow |
|---------|---------------|-------------|------|
| **BackForge** | `#14B8A6` (Teal) | `#0D7377` | `rgba(20,184,166,0.15)` |
| **LicenseForge** | `#6366F1` (Indigo) | `#4F46E5` | `rgba(99,102,241,0.15)` |
| **DripForge** | TBD (Amber or Emerald) | TBD | TBD |
| **ShieldForge** | TBD (Crimson or Steel Blue) | TBD | TBD |

#### Shared Typography
- Font stack: `-apple-system, BlinkMacSystemFont, "Inter", "Segoe UI", Roboto, sans-serif`
- Headings: 700–800 weight, white
- Body: 400 weight, `#E5E7EB`
- Labels: 600 weight, 12px, uppercase, letter-spacing
- Values/stats: 800 weight, 24px+, white

#### Shared Geometry
- Cards: 12px radius
- Buttons: 8px radius
- Inputs: 6px radius
- Badges/pills: 20px radius (fully rounded)

### Brand Marks

Each product needs:
- Wordmark (product name in clean sans-serif)
- Icon mark (works at 20×20px for WordPress admin menu)
- Combined mark (icon + wordmark)

All marks must work on dark backgrounds. The icon should reference both the product's domain AND the "forge" concept.

| Product | Icon Concept |
|---------|-------------|
| BackForge | Cloud + shield (backup protection) |
| LicenseForge | Key + certificate (licensing/commerce) |
| DripForge | Drop + flow (email sequences) |
| ShieldForge | Shield + armor (security) |
| Forge (family) | Anvil or geometric forge mark |

---

## 6. Shared UI/UX Philosophy

### Layout Patterns (Every Product)

- **`.bf-wrap` isolation** — all Forge UI wrapped in a container that isolates from WordPress admin styles
- **Card-based layout** — every section is a card with header, body, optional footer
- **2-column grid** on desktop, 1-column on mobile
- **Stat cards** at top of dashboard pages (4-column responsive grid)
- **Status indicators** — pill-shaped badges with glowing dot (green/amber/red/gray)
- **Empty states** — centered icon + title + description + CTA button

### Interaction Patterns

- **Glow on hover** — subtle accent-color glow on interactive elements
- **Lift on hover** — buttons translate -1px on hover
- **Teal glow line** — 2px gradient line appears at top of stat cards on hover
- **Dark inputs** — dark background inputs with accent-color focus ring
- **Gradient buttons** — primary buttons use accent gradient, not flat color

### Information Hierarchy

1. Dashboard stat cards (immediate status at a glance)
2. Primary action button (top-right of page header)
3. Card-based content sections
4. Tables for list data (dark-themed, striped)
5. Pro feature hints (indigo-tinted cards with upgrade CTA)

### Responsive Behavior

| Breakpoint | Layout |
|-----------|--------|
| ≥960px | 2-column card grid, 4-column stats |
| 768–959px | 2-column with smaller gaps |
| <768px | 1-column stacked, 2-column stats |
| <600px | 1-column everything, stacked header |

---

## 7. Product Relationship Map

### Dependency Graph

```
BackForge Pro ──validates license──▶ LicenseForge REST API
DripForge Pro ──validates license──▶ LicenseForge REST API
ShieldForge Pro ──validates license──▶ LicenseForge REST API
LicenseForge Pro ──validates license──▶ LicenseForge REST API (itself)
```

LicenseForge is the commerce hub. Every Pro product depends on it for license validation.

### Complementary Relationships

```
BackForge ◄──────── "Back up before security changes" ────────► ShieldForge
    │                                                                │
    │ "Back up the site                          "Protect the site   │
    │  generating leads"                          generating leads"  │
    │                                                                │
    ▼                                                                ▼
DripForge ◄──── "Nurture customers after purchase" ────► LicenseForge
```

### Data Flow Between Products

- **LicenseForge → All Pro plugins:** License validation via REST API
- **BackForge → ShieldForge:** Recommended to back up before security changes
- **DripForge → LicenseForge:** Email sequences can nurture post-purchase customers
- **ShieldForge → All:** Protects the WordPress installation running all other Forge plugins

### No Runtime Dependencies

Products do NOT require each other to function. A user can install BackForge alone without LicenseForge, DripForge, or ShieldForge. The relationships are complementary, not dependent (except Pro license validation).

---

## 8. Shared Platform Services

### Current: LicenseForge as Central Hub

LicenseForge (deployed on ekewaka.com) currently provides:
- Product catalog management
- PayPal checkout processing
- License key generation and validation
- Customer account management
- Secure file downloads (Pro plugin zips)
- Customer portal (licenses, downloads, invoices)
- EU/UK/AU VAT compliance
- Transactional emails

### Future: ForgePlatform Serverless Services

The planned AWS serverless platform will provide:

| Service | Replaces | Technology |
|---------|----------|-----------|
| License API | LicenseForge REST endpoints | API Gateway + Lambda + DynamoDB |
| Checkout | LicenseForge PayPal integration | Lambda + PayPal API |
| Customer Portal | LicenseForge shortcode pages | S3 static site + Portal API |
| Lead Capture | DripForge signup forms | Lambda + DynamoDB |
| Drip Engine | DripForge wp-cron processing | Lambda (scheduled) + SES |
| Email Delivery | wp_mail + SMTP | SES direct |
| File Downloads | LicenseForge token-based downloads | S3 pre-signed URLs |
| Analytics | Individual plugin dashboards | DynamoDB + Lambda aggregation |

### Migration Path

The WordPress plugins remain the primary products. The serverless platform is an optional enhancement layer — not a replacement. Plugins continue to work standalone. The platform adds:
- Centralized management across multiple sites
- Higher reliability (no wp-cron dependency)
- Better email deliverability (SES direct)
- Scalable license validation (Lambda, not WordPress PHP)

---

## 9. Shared Licensing & Customer Portal Concepts

### License Key Format

All Forge Pro products use the same key format:
```
{PRODUCT_PREFIX}-{XXXX}-{XXXX}-{XXXX}

Examples:
WPS3B-A7K2-M9X4-P3R8   (BackForge Pro)
DRIPF-B3N7-Q2W5-T8Y1   (DripForge Pro)
SHLDF-C4M8-R6K2-V9P3   (ShieldForge Pro)
LICNF-D5T9-W3J7-X2N6   (LicenseForge Pro)
```

- 32-character alphabet (A-Z minus I,O + 2-9 — avoids ambiguous characters)
- Collision-free generation with DB uniqueness check
- Prefix derived from product slug (first 5 chars)

### License Lifecycle (All Products)

```
Purchase → Key Generated → Customer Enters Key → Plugin Validates → Pro Unlocked
                                                        │
                                                  Daily re-check
                                                  (24h transient cache)
                                                        │
                                              7-day grace if API unreachable
                                                        │
                                              30/7/1 day renewal reminders
                                                        │
                                              Expiry → Pro features disabled
                                              (gracefully, no data loss)
```

### Customer Portal (Current: WordPress Shortcodes)

| Page | Shortcode | Function |
|------|-----------|----------|
| Dashboard | `[wplp_portal]` | License count, recent orders |
| Licenses | `[wplp_licenses]` | Key display, site deactivation |
| Downloads | `[wplp_downloads]` | Token-based file download |
| Invoices | `[wplp_invoices]` | Order history, invoice download |

### Customer Portal (Future: Static SPA on ForgePlatform)

Same functionality, delivered as a static site calling Lambda APIs:
- JWT-based authentication
- Client-side routing
- Pre-signed S3 URLs for downloads
- Real-time license management

---

## 10. Shared Terraform & Serverless Architecture Philosophy

### Terraform Principles

- **Modules over monoliths** — each AWS resource type is a reusable module
- **Environment isolation** — dev/staging/prod are separate root configs with separate state
- **Bootstrap pattern** — state bucket created separately before main infrastructure
- **Variables on everything** — no hardcoded values in resource definitions
- **Outputs for integration** — every module outputs what downstream modules need
- **Sensitive marking** — credentials and keys marked `sensitive = true`
- **State locking** — S3 native lockfile or DynamoDB lock table

### Module Library (Shared Across Projects)

| Module | Purpose |
|--------|---------|
| `s3-static-site` | S3 bucket + policy for static hosting |
| `cloudfront` | Distribution + OAC + cache policies |
| `api-gateway` | REST API + stages + custom domain |
| `lambda-function` | Function + role + log group |
| `dynamodb-table` | Table + GSIs + autoscaling |
| `ses-identity` | Domain verification + DKIM |
| `acm-certificate` | SSL cert + DNS validation |
| `dns-record` | Route53 record |

### Lambda Principles

- **One function per action** — not monolithic handlers
- **Shared utilities** — common code in a `shared/` directory (DB helpers, response formatting, auth)
- **Python preferred** — consistent with existing Lambda work (bitbybit-serverless)
- **Minimal dependencies** — avoid heavy frameworks, use boto3 (included in Lambda runtime)
- **Environment variables** — configuration via env vars, not hardcoded
- **Structured logging** — JSON logs for CloudWatch parsing

### DynamoDB Principles

- **Single-table design** — one table per environment with composite PK/SK
- **Access pattern driven** — design keys around query patterns, not entity relationships
- **GSIs for secondary access** — "all licenses for customer", "all orders this month"
- **TTL for expiry** — automatic cleanup of expired tokens, sessions, rate limit entries
- **No scan operations** — every query uses a key condition


---

## 11. Shared API & Platform Concepts

### API Design Principles

- **REST over GraphQL** — simpler, cacheable, WordPress-compatible
- **JSON request/response** — no XML, no form-encoded for API endpoints
- **Versioned namespaces** — `/{product}/v1/` (e.g., `/licenseforge/v1/validate`)
- **Meaningful HTTP status codes** — 200 success, 400 bad request, 403 forbidden, 429 rate limited
- **Rate limiting on all public endpoints** — transient-based or DynamoDB TTL-based
- **No authentication on license validation** — the key itself is the credential
- **Nonce protection on checkout endpoints** — WordPress nonces for browser-initiated calls
- **Webhook signature verification** — PayPal webhook signatures verified server-side

### Current API Surface (LicenseForge)

| Endpoint | Method | Purpose | Auth |
|----------|--------|---------|------|
| `/wplp/v1/validate` | POST | Validate license key | Key = credential |
| `/wplp/v1/activate` | POST | Register site against key | Key = credential |
| `/wplp/v1/deactivate` | POST | Remove site from key | Key = credential |
| `/wplp/v1/create-order` | POST | Initiate PayPal checkout | WP nonce |
| `/wplp/v1/capture-order` | POST | Complete payment | WP nonce |
| `/wplp/v1/calculate-tax` | POST | Real-time VAT calculation | WP nonce |
| `/wplp/v1/paypal-webhook` | POST | Payment event handler | PayPal signature |

### Future API Surface (ForgePlatform Lambda)

| Domain | Endpoints | Auth |
|--------|-----------|------|
| `/licensing/` | validate, activate, deactivate | Key = credential |
| `/checkout/` | create-order, capture-order, webhook, calculate-tax | Session/nonce |
| `/portal/` | login, session, licenses, downloads, orders, account | JWT token |
| `/leads/` | subscribe, confirm, unsubscribe | Public (rate-limited) |
| `/drip/` | process-queue, track-open, track-click | Internal/pixel |
| `/notifications/` | send-purchase, send-renewal, send-expired | Internal trigger |
| `/admin/` | stats, customers | IAM/admin auth |

### API Evolution Path

WordPress REST API → API Gateway + Lambda. The contract (request/response format) stays identical. Pro plugins don't need to change — only the URL they call changes (filterable via WordPress hook).

---

## 12. Customer Journey Across Products

### Entry Points

| Entry | Product | Path |
|-------|---------|------|
| WordPress.org search "backup" | BackForge | Install free → use → hit Pro limit → upgrade |
| WordPress.org search "security" | ShieldForge | Install free → see Pro features → upgrade |
| WordPress.org search "email drip" | DripForge | Install free → grow list → want Pro features |
| Blog post "sell WordPress plugins" | LicenseForge | Read guide → install → start selling |
| Forge ecosystem page | Any | Discover family → choose entry product |

### Cross-Product Discovery

Once a user installs any Forge product, they discover the ecosystem through:
- Subtle dashboard card: "Explore the Forge family" (not a nag, a small footer card)
- Upgrade page mentions companion products
- Documentation cross-references
- Shared design language creates familiarity ("this looks like that other plugin I use")

### Typical Customer Journeys

**Journey A: Site Owner**
```
BackForge (free) → BackForge Pro → ShieldForge (free) → ShieldForge Pro
"I backed up my site, now I want to protect it"
```

**Journey B: Plugin Developer**
```
LicenseForge (free) → LicenseForge Pro → BackForge (free) → BackForge Pro
"I'm selling my plugin, now I need to protect the site that runs my business"
```

**Journey C: Marketing-Focused Owner**
```
DripForge (free) → DripForge Pro → BackForge (free) → ShieldForge (free)
"I'm building my list, now I need to protect my subscriber data and back it up"
```

**Journey D: Agency**
```
BackForge Pro (Agency) → ShieldForge Pro (Agency) → DripForge Pro (Agency) → LicenseForge Pro (Agency)
"I need the full stack for all my client sites"
```

### Bundle Opportunity

| Bundle | Products | Price | Savings |
|--------|----------|-------|---------|
| Forge Starter | BackForge Pro + ShieldForge Pro | $69/yr | ~$9 |
| Forge Business | BackForge + ShieldForge + DripForge Pro | $99/yr | ~$58 |
| Forge Agency | All Pro, unlimited sites | $249/yr | ~$300+ |

---

## 13. Ecosystem Positioning

### Market Position Statement

Forge is the self-hosted operational stack for WordPress professionals who refuse to pay SaaS rent on their own infrastructure.

### Positioning Matrix

| Axis | Forge Position | Competitor Position |
|------|---------------|-------------------|
| Hosting model | Self-hosted | Cloud/SaaS |
| Revenue model | One-time annual license | Per-transaction % or per-subscriber |
| Data custody | Customer's server | Vendor's cloud |
| Dependency | Zero external dependencies | SDK/API/cloud required |
| Weight | 50–150KB per plugin | 5–30MB per plugin |
| UI | Modern dark SaaS | WordPress default or dated |
| Audience | Operators who value control | Users who value convenience |

### The Forge Operator

The ideal Forge customer is an **operator** — someone who:
- Runs their own WordPress infrastructure (even if it's shared hosting)
- Prefers owning tools over renting services
- Values cost predictability over feature maximalism
- Understands that lightweight tools are more reliable
- Appreciates good design but prioritizes function
- Manages 1–50 sites (solo to small agency)

### What Forge Is Competing Against

Not other WordPress plugins specifically — Forge competes against the **SaaS dependency model**:
- Monthly fees that scale with usage
- Vendor lock-in that makes migration painful
- Data custody that removes operator control
- Platform risk (vendor changes terms, raises prices, shuts down)

---

## 14. Long-Term Platform Direction

### Phase 1: WordPress Plugins (Current)

All products are standalone WordPress plugins. LicenseForge on ekewaka.com handles commerce. This works for the current scale and requires zero infrastructure beyond WordPress hosting.

### Phase 2: Serverless Platform (Next)

ForgePlatform on AWS provides:
- Static marketing site (S3 + CloudFront)
- License validation API (Lambda + DynamoDB) — higher reliability than WordPress
- Customer portal (static SPA + API)
- Lead capture and drip engine (Lambda + SES)
- Centralized analytics

WordPress plugins continue to work standalone. The platform is an enhancement layer.

### Phase 3: Multi-Site Management (Future)

Optional dashboard for managing Forge products across multiple WordPress sites:
- View backup status across all sites (BackForge)
- View security status across all sites (ShieldForge)
- Manage drip sequences across sites (DripForge)
- Centralized license management (LicenseForge)

This is the "Forge Cloud" concept — still self-hostable (Terraform deploys it to YOUR AWS account), but provides the multi-site visibility that agencies need.

### Phase 4: Ecosystem Expansion (Long-Term)

Potential future Forge products (brand-extensible):
- **DeployForge** — WordPress deployment automation (git push → staging → production)
- **CacheForge** — Lightweight WordPress caching with CloudFront integration
- **FormForge** — Self-hosted form builder with spam protection
- **StageForge** — One-click staging environments

Each follows the same principles: self-hosted, lightweight, no SaaS dependency, Forge UI, sold via LicenseForge.

---

## 15. Future Forge Cloud / Platform Possibilities

### Concept: Forge Cloud

A Terraform-deployable AWS infrastructure that operators deploy to their own AWS account. Not a SaaS — a self-hosted cloud platform.

```
Operator's AWS Account
├── S3: Static portal site
├── CloudFront: CDN for portal
├── API Gateway: Unified API
├── Lambda: Business logic
├── DynamoDB: Data storage
├── SES: Email delivery
├── Route53: DNS
└── ACM: SSL certificates
```

### What Forge Cloud Would Provide

| Capability | Benefit |
|-----------|---------|
| Centralized dashboard | See all sites' backup/security/email status in one place |
| Reliable license validation | Lambda-based, not dependent on WordPress uptime |
| Email delivery | SES direct, better deliverability than wp_mail |
| Customer portal | Fast static site, not WordPress page load |
| Analytics | Aggregated data across all products and sites |
| Webhook relay | Receive events from all sites, route to Slack/email |

### Deployment Model

```
git clone forge-platform
cd infrastructure/environments/prod
terraform init
terraform apply
# → Your Forge Cloud is live on your AWS account
```

Operators own the infrastructure. No Forge servers involved. No data leaves their AWS account. The Terraform modules ARE the product.

### Pricing Model (Conceptual)

- Free: Deploy yourself using open-source Terraform modules
- Pro: Pre-built, tested, documented modules + support + updates
- Managed: Forge deploys and maintains it for you (highest tier, future)

---

## 16. Tone & Messaging Guidance

### Voice Characteristics

| Attribute | Description |
|-----------|-------------|
| **Calm** | No urgency, no countdown timers, no "act now" pressure |
| **Technical** | Speaks to operators who understand infrastructure |
| **Confident** | Knows the product is good without needing to shout |
| **Direct** | Says what it means without marketing padding |
| **Honest** | Acknowledges limitations, doesn't overclaim |
| **Respectful** | Treats the reader as intelligent and capable |

### Language Rules

**Use:**
- Self-hosted, lightweight, ownership, control, infrastructure
- Direct, zero-dependency, WordPress-native, operator
- Reliable, predictable, documented, repeatable

**Avoid:**
- Revolutionary, game-changing, disruptive, next-generation
- AI-powered, blockchain, cutting-edge (unless literally true)
- "Just works" (overused), "blazing fast" (cliché)
- Fear-based: "Your site WILL be hacked", "You're losing money every day"
- Superlatives: best-in-class, world-class, enterprise-grade
- Hype: "The future of...", "Reimagining...", "Disrupting..."

### Messaging by Context

| Context | Tone | Example |
|---------|------|---------|
| Product page | Confident, clear | "Back up your WordPress site to your own S3 bucket. No SDK. No bloat. 50KB." |
| Documentation | Technical, precise | "The S3 client implements AWS Signature V4 signing using wp_remote_request()." |
| Comparison page | Factual, fair | "Wordfence Premium: $119/yr per site. ShieldForge Pro: $79/yr for 5 sites." |
| Error message | Helpful, calm | "Connection to S3 failed. Check your credentials and bucket region." |
| Upgrade prompt | Informative, not pushy | "Pro adds selective restore and URL replacement. Learn more →" |

### The Anti-Pattern

Forge explicitly rejects these common plugin marketing patterns:
- Nag banners in WordPress admin
- Fake urgency ("Only 3 seats left!")
- Dark patterns in upgrade flows
- Aggressive upsell on every page
- Hiding useful features behind Pro artificially
- Sending marketing emails without explicit opt-in

---

## 17. Design Language Consistency

### What Must Be Identical Across All Products

| Element | Specification |
|---------|--------------|
| Background colors | `#0F172A` / `#1E293B` / `#263548` |
| Text colors | `#E5E7EB` / `#94A3B8` / `#64748B` |
| Border color | `#334155` |
| Card radius | 12px |
| Button radius | 8px |
| Input radius | 6px |
| Font stack | System fonts (Inter preferred) |
| Stat card layout | Icon → value → label |
| Status pill shape | 20px radius, dot + text |
| Pro badge | Indigo gradient, uppercase, 10px |
| Empty state | Centered icon + title + text + CTA |
| Page header | Title with icon-square + action buttons right |
| Wrap class | `.bf-wrap` (isolates from WP admin) |

### What Varies Per Product

| Element | Varies How |
|---------|-----------|
| Accent color | Teal / Indigo / TBD / TBD |
| Icon in header | Product-specific dashicon |
| Stat card content | Product-specific metrics |
| Card content | Product-specific features |
| Pro features | Product-specific upsell |

### CSS Architecture

Each product has a single `admin.css` file (no build tools, no preprocessors) structured as:
```
Variables → Base/Wrap → Cards → Dashboard → Product-specific → Pro → Utilities → Animations → Responsive
```

CSS custom properties use the `--bf-` namespace (BackForge was first, namespace kept for consistency). Future products may use `--forge-` if a shared stylesheet is extracted.

---

## 18. How the Products Reinforce Each Other

### Technical Reinforcement

- **Shared security model** — same encryption, nonce, capability patterns across all products
- **Shared UI components** — same card, button, badge, status patterns (reduces learning curve)
- **Shared hooks architecture** — all products use `do_action()` / `apply_filters()` for Pro extensibility
- **Shared Pro pattern** — separate Pro plugin extends free via hooks, validates via LicenseForge API
- **Shared credential pattern** — AES-256-CBC with WordPress salts, masked display after save

### Business Reinforcement

- **Cross-sell surface area** — each product's user base is a warm audience for others
- **Bundle pricing** — discount for multiple products incentivizes ecosystem adoption
- **Shared customer portal** — one LicenseForge account manages all Forge Pro licenses
- **Shared support knowledge** — understanding one Forge product's patterns helps with all others
- **Shared brand trust** — positive experience with one product transfers to the family

### Narrative Reinforcement

The Forge story gets stronger with each product:
- 1 product: "A good WordPress plugin"
- 2 products: "A developer with a consistent approach"
- 3 products: "A coherent product family"
- 4 products: "A complete operational stack"
- Platform: "An infrastructure ecosystem"

Each product validates the philosophy. Each product proves the pattern works. The ecosystem is more credible than any individual product.

---

## 19. AI Context Guidance for Future AI-Assisted Development

### How to Use This Document

This document (and the individual product handoff docs) should be provided as context when:
- Generating code for any Forge product
- Creating marketing copy, website pages, or social media content
- Designing UI components or page layouts
- Planning infrastructure or architecture decisions
- Making product decisions (feature placement, pricing, naming)
- Creating visual assets (logos, illustrations, screenshots)

### Key Context for Code Generation

When generating code for any Forge product:
- Follow WordPress coding standards (tabs, PHPDoc, prefix everything)
- Use the product's specific prefix (`wps3b_`, `nlde_`/`df_`, `wplp_`, `sfs_`)
- No Composer, no external dependencies, no SDK
- Security: nonce + capability + sanitize + escape on everything
- Use `wp_remote_request()` for HTTP, `$wpdb` for database
- Encrypt credentials with AES-256-CBC using WordPress salts
- Every PHP file starts with `if (!defined('ABSPATH')) exit;`

### Key Context for Creative Work

When generating visual or creative assets:
- Dark backgrounds (`#0F172A`) with product-specific accent color
- Clean, geometric, modern — not playful or corporate
- No stock photos — use abstract/conceptual illustrations
- Consistent with the Forge family (same foundation, different accent)
- Professional but not enterprise — approachable but not casual

### Key Context for Infrastructure Work

When planning or generating infrastructure:
- Terraform with reusable modules
- AWS serverless (Lambda, DynamoDB, S3, CloudFront, API Gateway, SES)
- Environment separation (dev/staging/prod)
- Least privilege IAM
- No servers to manage
- Python for Lambda functions
- Single-table DynamoDB design

### Key Context for Product Decisions

When making product or feature decisions:
- Does it maintain self-hosted independence?
- Does it add bloat or keep things lightweight?
- Does it belong in free or Pro? (Free must be genuinely useful)
- Does it follow the existing patterns of the ecosystem?
- Does it create SaaS dependency? (If yes, reject it)
- Would an operator choose this over a SaaS alternative?

### Document Relationships

| Document | Location | Purpose |
|----------|----------|---------|
| This document | `ForgePlatform/docs/ecosystem/forge-ecosystem-handoff.md` | Ecosystem master context |
| BackForge Handoff | `wp-s3-backup-project/docs/20-BACKFORGE-CREATIVE-HANDOFF.md` | BackForge-specific creative context |
| DripForge Handoff | `drip-forge-project/docs/05-DRIPFORGE-CREATIVE-HANDOFF.md` | DripForge-specific creative context |
| LicenseForge Handoff | `wp-license-platform/docs/08-LICENSEFORGE-CREATIVE-HANDOFF.md` | LicenseForge-specific creative context |
| ShieldForge Handoff | `shield-forge-project/docs/05-SHIELDFORGE-CREATIVE-HANDOFF.md` | ShieldForge-specific creative context |
| Platform Architecture | `ForgePlatform/06-platform-architecture.md` | Serverless platform direction |
| Master Context | `ForgePlatform/docs/00-master-context.md` | High-level ecosystem summary |

### Priority Hierarchy for Decisions

When context conflicts, resolve in this order:
1. Security (never compromise)
2. WordPress.org compliance (required for distribution)
3. Lightweight/no-dependency principle (core philosophy)
4. Ecosystem consistency (shared patterns)
5. Product-specific needs (individual product requirements)
6. User convenience (nice-to-have)

---

*End of Forge Ecosystem Unified Handoff*
