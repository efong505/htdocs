# LicenseForge — Creative & Branding Handoff

*Use this document as context for ChatGPT (or any AI) when creating images, branding assets, website pages, marketing materials, social media content, or any visual/creative work for LicenseForge.*

---

## 1. Product Identity

| Field | Value |
|-------|-------|
| **Product Name** | LicenseForge |
| **Full Title** | LicenseForge – WordPress Plugin Licensing & Sales |
| **Tagline (Primary)** | "Sell direct. Keep your revenue." |
| **Tagline (Secondary)** | "Your platform. Your customers. Your data." |
| **Product Family** | Forge (ForgeWP) |
| **Pro Version** | LicenseForge Pro |
| **Category** | WordPress Plugin — Digital Product Sales & Licensing |
| **WordPress.org Slug (target)** | `licenseforge` |
| **Current working slug** | `wp-license-platform` |
| **Developer** | Edward Fong |
| **Website** | ekewaka.com |

---

## 2. What LicenseForge Does (Plain Language)

LicenseForge is a WordPress plugin that turns any WordPress site into a complete digital product store with PayPal payment processing, license key management, global VAT compliance, secure file downloads, and a customer portal. It's the self-hosted alternative to Freemius, LemonSqueezy, and Gumroad — with zero per-transaction fees.

**In one sentence:** LicenseForge lets WordPress plugin and theme developers sell their products directly from their own site with built-in checkout, licensing, VAT compliance, and customer portal — no SaaS middleman, no revenue share.

### Key Differentiators
- **Zero per-transaction fees** — no 5–10% platform cut (only PayPal's standard 2.9% + $0.30)
- **All-in-one** — checkout, licensing, VAT, portal, downloads, invoices, emails in ONE plugin (competitors need 3–10 extensions)
- **Self-hosted** — your customer data, your server, no vendor lock-in
- **Built-in EU/UK/AU VAT compliance** — two-piece evidence, VIES validation, reverse charge (competitors charge extra for this)
- **Lightweight** — ~100KB total, no WooCommerce dependency, no bloated framework
- **REST API for license validation** — your Pro plugins call it directly to verify keys
- **The meta advantage** — LicenseForge sells itself (the checkout you see IS the product)

### Free Features
- 1 product with up to 3 pricing tiers
- PayPal checkout (one-time payments)
- License key generation and management
- REST API (validate, activate, deactivate)
- Basic customer management
- Customer portal (licenses, downloads)
- Purchase confirmation email
- Secure file downloads (token-based, time-limited, one-time use)
- Pricing table shortcode
- Encrypted credential storage
- Rate-limited API (30 req/min per IP)

### Pro Features ($99–$299/year)
- Unlimited products and pricing tiers
- PayPal subscriptions (recurring billing)
- Full VAT compliance (EU/UK/AU rates, two-piece evidence, VIES validation, reverse charge)
- PDF invoice generation
- Renewal reminder emails (30/7/1 day before expiry)
- License expired emails
- Refund processing (one-click from admin)
- Sales reports and analytics (revenue, orders, by product/tier/country)
- CSV export (orders, licenses, customers)
- Webhook notifications (send events to external systems)
- Custom email templates (HTML editor)
- White-label (Agency tier)
- Multi-site network support (Agency tier)
- Priority support

---

## 3. Brand Identity & Visual Design

### Color Palette

| Role | Hex | RGB | Usage |
|------|-----|-----|-------|
| **Primary Accent (Indigo)** | `#6366F1` | 99, 102, 241 | Buttons, links, active states, brand color |
| **Primary Dark (Indigo)** | `#4F46E5` | 79, 70, 229 | Gradient endpoints, darker indigo |
| **Primary Light** | `#8B5CF6` | 139, 92, 246 | Hover states, lighter accent |
| **Glow** | `rgba(99, 102, 241, 0.15)` | — | Hover effects, focus rings |
| **Glow Strong** | `rgba(99, 102, 241, 0.30)` | — | Button shadows, emphasis |
| **Background (Primary)** | `#0F172A` | 15, 23, 42 | Main page background, dark navy |
| **Background (Card)** | `#1E293B` | 30, 41, 59 | Card surfaces, elevated elements |
| **Background (Card Hover)** | `#263548` | 38, 53, 72 | Hover state for cards |
| **Success (Green)** | `#22C55E` | 34, 197, 94 | Completed orders, active licenses |
| **Warning (Amber)** | `#F59E0B` | 245, 158, 11 | Pending orders, expiring licenses |
| **Error (Red)** | `#EF4444` | 239, 68, 68 | Failed payments, revoked licenses |
| **Text (Primary)** | `#E5E7EB` | 229, 231, 235 | Body text on dark backgrounds |
| **Text (Muted)** | `#94A3B8` | 148, 163, 184 | Secondary text, labels |
| **Text (Dim)** | `#64748B` | 100, 116, 139 | Tertiary text, descriptions |
| **Border** | `#334155` | 51, 65, 85 | Card borders, dividers |
| **White** | `#FFFFFF` | 255, 255, 255 | Headings, emphasis text |

### Gradient Definitions
- **Primary Button:** `linear-gradient(135deg, #6366F1, #4F46E5)`
- **Danger Button:** `linear-gradient(135deg, #EF4444, #DC2626)`
- **Pro Badge:** `linear-gradient(135deg, #6366F1, #8B5CF6)`
- **Glow Line:** `linear-gradient(90deg, transparent, #6366F1, transparent)`

### Glow Effects
- **Indigo Glow (Subtle):** `0 0 20px rgba(99, 102, 241, 0.15)`
- **Indigo Glow (Strong):** `0 0 20px rgba(99, 102, 241, 0.30)`
- **Button Hover Glow:** `0 4px 16px rgba(99, 102, 241, 0.30)`
- **Success Dot Glow:** `0 0 6px #22C55E`

### Typography
- **Font Family:** -apple-system, BlinkMacSystemFont, "Inter", "Segoe UI", Roboto, sans-serif
- Same weight/size system as BackForge (consistency across Forge family)

### Border Radius
- **Cards:** 12px
- **Buttons:** 8px
- **Inputs:** 6px
- **Badges/Pills:** 20px

### Design Principles
- **Dark-first** — same dark navy/slate foundation as all Forge products
- **Indigo = brand** — indigo is the primary accent (distinct from BackForge's teal)
- **Professional/commerce feel** — this is a business tool for selling products
- **Card-based layout** — consistent with Forge family
- **Status colors are semantic** — green = active/completed, amber = pending/expiring, red = failed/revoked

---

## 4. UI Component Patterns

### Admin Dashboard
- Stat cards: Total Revenue, Orders This Month, Active Licenses, Total Customers
- Revenue chart (line or bar, monthly)
- Recent orders table
- Quick actions: Add Product, View Orders

### Product Management
- Product list with name, slug, version, status badge, tier count
- Product edit: name, slug, description, version, file upload (zip)
- Tier management: inline table with name, price, billing period, sites allowed, featured toggle

### Order List
- Table: Order #, Customer, Product, Tier, Total, Status badge, Date
- Status badges: green = completed, amber = pending, red = failed/refunded
- Click to view order detail (customer info, payment IDs, VAT evidence, license key)

### License List
- Table: License Key, Product, Tier, Customer, Status, Sites (active/allowed), Expires
- Click license → detail view showing all activated site URLs with last check-in time
- Status badges: green = active, amber = expiring soon, red = expired/revoked

### Customer Portal (Public-Facing)
- Clean, light-themed design (works on any WordPress theme)
- Dashboard: welcome message, active license count, recent orders
- Licenses page: license cards with key, status, sites, expiry, deactivate buttons
- Downloads page: product cards with download button (generates time-limited token URL)
- Invoices page: order history table with download invoice link

### Checkout Page (Public-Facing)
- Pricing tier selection (radio buttons or cards)
- Country selector (for VAT calculation)
- VAT number field (optional, for B2B reverse charge)
- Real-time tax calculation (AJAX on country change)
- Order summary (subtotal, VAT, total)
- PayPal button (PayPal JS SDK)
- Clean, trustworthy design — no dark theme here, needs to feel "safe to buy"

### Pricing Table (Embeddable Shortcode)
- 3-column tier comparison
- Featured/recommended tier highlighted
- Price, billing period, sites allowed, feature list per tier
- CTA button per tier linking to checkout with tier pre-selected

---

## 5. Brand Voice & Messaging

### Tone
- **Empowering** — take control of your sales, stop paying platform fees
- **Business-savvy** — speaks to developers who understand margins and revenue
- **Direct and confident** — "keep your revenue" is a bold, clear promise
- **Anti-middleman** — positioned against SaaS platforms that take a cut

### Key Messages

| Audience | Message |
|----------|---------|
| **Solo plugin developers** | "Sell your first plugin without giving away 7% to a platform. One WordPress plugin handles everything." |
| **Theme developers** | "License keys, secure downloads, customer portal — all self-hosted. No WooCommerce needed." |
| **Agencies** | "White-label licensing for client products. Unlimited products, full VAT compliance, professional invoices." |
| **Developers leaving Freemius** | "Same features, zero revenue share. Your customers, your data, your margins." |

### Competitor Positioning

| vs. | Our angle |
|-----|-----------|
| **Freemius** | "Same licensing features, zero 7% cut. At $50K/year in sales, save $3,500/year." |
| **LemonSqueezy** | "No 5% + $0.50 per transaction. Self-hosted. Your customer data stays yours." |
| **Gumroad** | "No 10% fee. Built specifically for WordPress products, not generic digital goods." |
| **EDD + extensions** | "All-in-one for $99/yr vs $499+/yr in EDD extensions. Lighter, modern, purpose-built." |
| **WooCommerce** | "No WooCommerce overhead. Purpose-built for digital products with licensing. 100KB vs 20MB." |

### The "Meta" Marketing Angle
LicenseForge sells itself using itself. The checkout page customers use to buy LicenseForge Pro IS powered by LicenseForge. This is the ultimate proof of concept — "If it's good enough for us to run our own business on, it's good enough for yours."

### Words We Use
- Self-hosted, zero fees, keep your revenue, own your data, all-in-one
- License keys, customer portal, VAT compliant, secure downloads
- No middleman, no revenue share, no vendor lock-in
- Professional, legitimate, independent

### Words We Avoid
- "Enterprise" (unless actually enterprise features)
- "Marketplace" (we're not a marketplace, we're a self-hosted platform)
- "Simple" (it's comprehensive, not simple — but it IS easy to set up)
- "Free alternative to X" (we have a free tier, but we're not positioning as "free")

---

## 6. Target Audience Personas

### Persona 1: Solo Plugin Developer ("Tom")
- Built his first WordPress plugin, wants to sell a Pro version
- Currently considering Freemius or LemonSqueezy
- Makes $500–2,000/month in plugin sales
- Cares about: keeping margins, professional appearance, easy setup
- Pain: 7% to Freemius = $420–1,680/year gone

### Persona 2: Theme Developer ("Mia")
- Sells 2–3 WordPress themes
- Currently using EDD with 4 paid extensions ($400+/year)
- Needs: license keys, update delivery, customer portal
- Cares about: simplicity, cost, reliable licensing
- Pain: EDD extension stack is expensive and complex

### Persona 3: Agency Product Lead ("Carlos")
- Agency sells white-label plugins to clients
- Needs: multi-product, white-label, VAT compliance for EU clients
- Currently using WooCommerce + 8 extensions
- Cares about: professional invoices, VAT compliance, scalability
- Pain: WooCommerce is overkill, extensions cost $800+/year

### Persona 4: Developer Leaving a SaaS Platform ("Nina")
- Currently on Freemius/LemonSqueezy, frustrated with fees
- Makes $5,000+/month, losing $350–500/month to platform fees
- Wants: same features, self-hosted, zero ongoing percentage
- Cares about: migration ease, feature parity, reliability
- Pain: $4,200–6,000/year in platform fees

---

## 7. Asset Requirements

### WordPress.org Plugin Assets
| Asset | Size | Notes |
|-------|------|-------|
| Plugin Icon | 256×256px (SVG preferred) | Shown in plugin directory search results |
| Plugin Banner | 772×250px | Shown at top of plugin page |
| Plugin Banner (Hi-DPI) | 1544×500px | Retina version |
| Screenshots | 1200×900px (recommended) | Numbered, showing key features |

### Website Assets
| Asset | Purpose |
|-------|---------|
| Logo (full) | Header, about page — "LicenseForge" wordmark with icon |
| Logo (icon only) | Favicon, app icon, small spaces |
| Hero illustration | Landing page — conceptual (key + store + independence) |
| Feature illustrations | One per major feature section |
| Pricing page graphics | Tier comparison visual |
| Cost savings calculator graphic | Visual showing savings vs SaaS platforms over time |
| Checkout screenshot | Show the actual checkout experience |
| Portal screenshot | Show the customer portal experience |

### Social Media
| Platform | Asset Size |
|----------|-----------|
| Twitter/X header | 1500×500px |
| Twitter/X post | 1200×675px |
| Open Graph (default) | 1200×630px |
| WordPress.org banner | 772×250px |

---

## 8. Visual Metaphors & Imagery Direction

### Preferred Imagery Concepts
- **Key / lock** — license keys, access control, security
- **Store / shop front** — selling products, commerce
- **Shield / vault** — secure downloads, protected files
- **Receipt / invoice** — professional commerce, VAT compliance
- **Independence / freedom** — breaking free from SaaS platforms
- **Forge / anvil** — shared Forge family identity (crafting your own platform)
- **Growth chart** — revenue, business growth, keeping more of your money
- **Handshake / direct connection** — direct relationship with customers (no middleman)

### Style Direction
- **Clean and modern** — same dark aesthetic as Forge family
- **Dark mode for admin** — dark navy backgrounds, indigo accents
- **Light/neutral for public pages** — checkout and portal should feel trustworthy and clean
- **Professional/business feel** — this is a commerce tool, should feel "serious"
- **Subtle gradients** — indigo-to-dark-indigo, not flashy
- **No stock photos of shopping carts** — use abstract/conceptual instead
- **Data visualizations** — revenue charts, savings comparisons

### Logo Direction
- Should incorporate the "license/key" concept (key, lock, certificate, or abstract geometric)
- Indigo as primary color on dark background
- Must work at small sizes (WordPress admin menu = 20×20px)
- Should feel "professional/commerce" not "playful"
- Wordmark: clean sans-serif, consistent with Forge family
- Distinct from BackForge (teal/cloud) — this is indigo/key

### Differentiation from Other Forge Products
- BackForge = teal, cloud/shield/protection (backup safety)
- DripForge = TBD, flow/drops/sequence (email nurture)
- LicenseForge = indigo, key/store/commerce (selling products)
- Same dark foundation, different accent and metaphor

---

## 9. Pricing & Tiers

| Tier | Price | Products | Target |
|------|-------|----------|--------|
| **Free** | $0 | 1 product | Solo developers starting out |
| **Developer** | $99/year | 5 products | Established plugin/theme developers |
| **Business** | $199/year | 25 products | Small agencies, multi-product sellers |
| **Agency** | $299/year | Unlimited | Large agencies, white-label resellers |

**Positioning:** Developer tier ($99) is the recommended/featured tier.

**Cost comparison angle (the killer pitch):**

| Platform | Cost at $50K/year sales | 3-Year Total |
|----------|------------------------|-------------|
| Freemius (7%) | $3,500/year | $10,500 |
| LemonSqueezy (5% + $0.50) | $3,000/year | $9,000 |
| Gumroad (10%) | $5,000/year | $15,000 |
| EDD + extensions | $499/year | $1,497 |
| **LicenseForge** | **$99/year** | **$297** |

"At $50K/year in sales, LicenseForge saves you $8,703 over 3 years vs. LemonSqueezy."

---

## 10. Competitive Landscape (For Positioning)

### Direct Competitors

| Competitor | Model | Cost | Our Advantage |
|-----------|-------|------|---------------|
| Freemius | SaaS, rev-share | 7% per transaction | Zero rev-share, self-hosted |
| LemonSqueezy | SaaS, MoR | 5% + $0.50/tx | Self-hosted, your data, no ongoing % |
| Paddle | SaaS, MoR | 5% + $0.50/tx | Same as LemonSqueezy |
| Gumroad | SaaS | 10% | Fraction of the cost, WordPress-native |
| EDD | WordPress plugin | $99–499/yr + extensions | All-in-one (no 3–5 paid extensions needed) |
| WooCommerce | WordPress plugin | Free + $300–800/yr extensions | Purpose-built, lightweight, no WooCommerce overhead |
| Appsero | SaaS | $20+/mo per 500 licenses | Self-hosted, no per-license pricing |

### Feature Comparison Table (For Marketing Pages)

| Feature | LicenseForge | Freemius | EDD | WooCommerce |
|---------|-------------|----------|-----|-------------|
| Self-hosted | ✅ | ❌ | ✅ | ✅ |
| All-in-one (no extensions) | ✅ | ✅ | ❌ | ❌ |
| Per-transaction fees | 0% | 7% | 0% | 0% |
| Built-in VAT compliance | ✅ | ✅ | ❌ (paid addon) | ❌ (paid addon) |
| Built-in license API | ✅ | ✅ | ❌ (paid addon) | ❌ (paid addon) |
| Customer portal | ✅ | ✅ | ❌ (paid addon) | ✅ |
| Plugin size | ~100KB | N/A (SaaS) | ~5MB+ | ~20MB+ |
| Your customer data | ✅ | ❌ | ✅ | ✅ |
| HMRC two-piece evidence | ✅ | ❌ | ❌ | ❌ |

---

## 11. Forge Product Family Context

LicenseForge is the **commerce backbone** of the Forge ecosystem. It's what sells all the other Forge Pro products.

| Product | Purpose | Accent Color | Relationship to LicenseForge |
|---------|---------|-------------|------------------------------|
| **BackForge** | S3 Backup & Restore | Teal (`#14B8A6`) | BackForge Pro is sold via LicenseForge |
| **LicenseForge** | Plugin Licensing & Sales | Indigo (`#6366F1`) | Sells itself + all other Forge Pro products |
| **DripForge** | Email Drip Automation | TBD | DripForge Pro will be sold via LicenseForge |
| **ShieldForge** | WordPress Security | TBD | ShieldForge Pro will be sold via LicenseForge |

### The Ecosystem Flywheel
```
LicenseForge (deployed on ekewaka.com)
    │
    ├── Sells: BackForge Pro
    ├── Sells: DripForge Pro
    ├── Sells: ShieldForge Pro
    └── Sells: LicenseForge Pro (itself!)
```

Every Forge product's Pro version validates its license against LicenseForge's REST API. LicenseForge is the central hub.

**Shared brand traits:**
- Dark SaaS UI aesthetic
- "Forge" suffix naming
- Self-hosted, no SaaS dependency
- Lightweight, no bloat philosophy
- WordPress-native (hooks, filters, WP APIs)
- Free + Pro model

---

## 12. SEO & Discovery Keywords

### Primary Keywords
- wordpress license manager
- sell wordpress plugins
- wordpress software licensing
- wordpress digital product store
- wordpress plugin licensing

### Secondary Keywords
- self-hosted license management wordpress
- freemius alternative wordpress
- sell digital products wordpress without woocommerce
- wordpress paypal checkout plugin
- wordpress vat compliance plugin
- license key wordpress plugin

### Long-tail
- "how to sell wordpress plugins from your own site"
- "freemius alternative self-hosted no revenue share"
- "wordpress plugin licensing without woocommerce"
- "sell wordpress themes with license keys"
- "wordpress eu vat compliance digital goods"
- "best way to sell wordpress plugins 2026"

---

## 13. Content Ideas (For Website/Blog)

### Landing Page Sections
1. Hero: Tagline + one-line description + CTA + savings calculator preview
2. "The Problem" — SaaS platforms take 5–10% of every sale, forever
3. "The Solution" — all-in-one self-hosted licensing + sales
4. Cost savings comparison — visual chart (LicenseForge vs Freemius vs LemonSqueezy over 3 years)
5. Feature grid — 8–10 features with icons
6. How it works — 4 steps (Install → Add Product → Set Pricing → Start Selling)
7. "Eat your own dog food" — "We use LicenseForge to sell LicenseForge"
8. Comparison table — vs top 4 competitors
9. Pricing — 4 tiers (Free, Developer, Business, Agency)
10. Testimonials / case studies
11. FAQ
12. CTA — "Start Selling Free"

### Blog Post Ideas
- "How to Sell Your WordPress Plugin (Complete Guide)"
- "Stop Paying 7% to Freemius — Self-Host Your Licensing"
- "EDD vs LicenseForge — Which is Better for Plugin Developers?"
- "WordPress VAT Compliance for Digital Goods (EU/UK/AU)"
- "How I Built a $50K/Year Plugin Business Without SaaS Fees"
- "PayPal Integration for WordPress Without WooCommerce"
- "The True Cost of Selling WordPress Plugins on Each Platform"

---

## 14. Technical Architecture (For Developer-Facing Content)

```
Your WordPress Site (ekewaka.com)
┌─────────────────────────────────────────────────────────┐
│  LicenseForge Plugin                                    │
│                                                         │
│  Admin Panel          Public Pages       REST API       │
│  ┌─────────────┐    ┌─────────────┐   ┌────────────┐  │
│  │ Products    │    │ Checkout    │   │ /validate  │  │
│  │ Orders      │    │ Pricing     │   │ /activate  │  │
│  │ Licenses    │    │ Portal      │   │ /deactivate│  │
│  │ Customers   │    │ Downloads   │   │ /webhook   │  │
│  │ Settings    │    │ Thank You   │   │            │  │
│  └─────────────┘    └─────────────┘   └────────────┘  │
│                                              ↑          │
│  7 Custom DB Tables                          │          │
│  (products, tiers, customers,                │          │
│   orders, licenses, activations,             │          │
│   vat_evidence)                              │          │
└──────────────────────────────────────────────┼──────────┘
                    │                          │
         ┌──────────┤                          │
         ▼          ▼                          ▼
    ┌─────────┐ ┌─────────┐          ┌──────────────┐
    │ PayPal  │ │ GeoIP   │          │ Customer's   │
    │ REST API│ │ (VAT)   │          │ WordPress    │
    │         │ │         │          │ Site         │
    │ Payment │ │ Country │          │              │
    │ Capture │ │ Lookup  │          │ Pro Plugin   │
    │ Webhook │ │         │          │ calls API    │
    └─────────┘ └─────────┘          └──────────────┘
```

### Key Technical Details
- PayPal REST API v2 (OAuth 2.0, no SDK)
- License key format: `{PREFIX}-{XXXX}-{XXXX}-{XXXX}` (32-char alphabet, collision-free)
- Token-based downloads (1-hour expiry, one-time use)
- Rate limiting: 30 requests/minute per IP on license API
- VAT: 27 EU states + UK + Norway + Switzerland + Australia
- VIES API for EU VAT number validation
- Two-piece evidence: billing country + IP geolocation
- Daily cron: expire past-due licenses, send renewal reminders
- Credentials encrypted at rest (AES-256-CBC with WordPress salts)

---

## 15. Screenshot Descriptions (For WordPress.org)

1. **Admin Dashboard** — Revenue stat cards, recent orders table, quick action buttons
2. **Product Management** — Product list with tiers, file upload, version management
3. **Order List** — Orders with status badges, customer info, payment details
4. **License Management** — License list with keys, status, sites active/allowed, expiry dates
5. **License Detail** — Single license showing all activated site URLs with deactivation option
6. **Settings** — PayPal credentials (masked), business info, VAT configuration
7. **Checkout Page** — Public checkout with tier selection, country/VAT fields, PayPal button, order summary
8. **Customer Portal** — Customer dashboard showing licenses, downloads, and order history
9. **Pricing Table** — Embeddable pricing shortcode showing 3 tiers with features and CTAs

---

## 16. Customer Journey Visualization

```
Developer discovers LicenseForge
         │
         ▼
Installs free version from WordPress.org
         │
         ▼
Adds their first product + pricing tiers
         │
         ▼
Configures PayPal (sandbox first)
         │
         ▼
Creates checkout + pricing pages (shortcodes)
         │
         ▼
Makes first sale → license auto-generated
         │
         ▼
Customer enters key in Pro plugin → validated via REST API
         │
         ▼
Developer sees revenue growing, hits 1-product limit
         │
         ▼
Upgrades to LicenseForge Developer ($99/yr)
         │
         ▼
Adds more products, enables VAT, enables subscriptions
         │
         ▼
Business scales — zero additional platform fees
```

---

## 17. Trust & Credibility Elements

For a product that handles payments and licensing, trust is critical. Include these on the website:

- **"Powered by LicenseForge"** badge on checkout (proves it works)
- **Security highlights:** AES-256 encryption, PayPal (no card data stored), rate limiting
- **VAT compliance:** "HMRC-ready two-piece evidence" — appeals to EU/UK sellers
- **Open source core:** GPL v2+, code is auditable, no obfuscation
- **Self-hosted guarantee:** "Your customer data never leaves your server"
- **Money-back guarantee:** 30-day refund policy
- **Active development:** Show commit activity, version history, roadmap

---

*End of LicenseForge Creative Handoff*
