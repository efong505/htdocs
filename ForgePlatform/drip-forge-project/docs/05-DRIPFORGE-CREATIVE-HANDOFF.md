# DripForge — Creative & Branding Handoff

*Use this document as context for ChatGPT (or any AI) when creating images, branding assets, website pages, marketing materials, social media content, or any visual/creative work for DripForge.*

---

## 1. Product Identity

| Field | Value |
|-------|-------|
| **Product Name** | DripForge |
| **Full Title** | DripForge – Email Drip Sequences for WordPress |
| **Tagline (Primary)** | "Your list. Your emails. Your server." |
| **Tagline (Secondary)** | "Zero subscriber fees. Unlimited everything." |
| **Product Family** | Forge (ForgeWP) |
| **Pro Version** | DripForge Pro |
| **Category** | WordPress Plugin — Email Marketing Automation |
| **WordPress.org Slug (target)** | `dripforge` |
| **Developer** | Edward Fong |
| **Website** | ekewaka.com |

---

## 2. What DripForge Does (Plain Language)

DripForge is a self-hosted WordPress plugin for email drip marketing automation. It lets site owners capture leads via embeddable signup forms, build timed email sequences, and nurture subscribers — all from the WordPress dashboard with zero SaaS fees and no subscriber limits.

**In one sentence:** DripForge lets WordPress site owners build automated email drip sequences that send timed, personalized emails to subscribers without paying per-contact SaaS fees.

### Key Differentiators
- **Zero subscriber fees** — no per-contact pricing like Mailchimp/ConvertKit ($0 whether you have 100 or 100,000 subscribers)
- **Self-hosted** — your subscriber data stays on your server, not a third-party platform
- **SMTP-agnostic** — works with Amazon SES ($0.10/1000 emails), SendGrid, Brevo, Gmail, or any SMTP provider
- **WordPress-native** — uses wp-cron, wp_mail, wp_options — no external dependencies or frameworks
- **Lightweight** — no bloated React admin, no bundled frameworks, just PHP + vanilla JS
- **Drip-focused** — does one thing well (timed sequences), not a bloated "all-in-one" CRM

### Free Features
- Unlimited subscribers (no caps, ever)
- Unlimited drip sequences
- Unlimited emails per sequence
- SMTP configuration (any provider)
- Open and click tracking analytics
- Shortcode-based signup forms with AJAX + honeypot spam protection
- Merge tags for personalized emails ({first_name}, {email}, {site_name}, etc.)
- CAN-SPAM compliant unsubscribe handling
- Subscriber search, filter, and CSV export
- HTML email template with responsive design
- wp-cron processing (every 5 minutes, 50 emails/batch)
- Email preview with full HTML render
- Per-sequence and overview analytics (sent, opened, clicked, rates)

### Pro Features ($39–$149/year)
- Visual email builder (block-based drag-and-drop)
- Conditional sequences (branching logic: if opened → send X, else → send Y)
- Subscriber tagging system
- A/B subject line testing
- CSV subscriber import
- Double opt-in (confirmation email)
- Advanced analytics (charts, time-series, per-subscriber timeline)
- Custom email templates (multiple designs)
- Scheduled sends (specific time of day)
- Webhook triggers (Zapier/Make integration)
- Multi-site support
- White-label
- Priority support

---

## 3. Brand Identity & Visual Design

### Color Palette

DripForge shares the Forge family's dark SaaS aesthetic. The primary accent color is **TBD** but should be distinct from BackForge's teal and LicenseForge's indigo. Recommended direction: **warm orange/amber** or **emerald green** to represent email/communication/growth.

**Option A — Amber/Orange (Communication/Energy):**

| Role | Hex | Usage |
|------|-----|-------|
| **Primary Accent** | `#F59E0B` | Buttons, links, active states |
| **Primary Dark** | `#D97706` | Gradient endpoints |
| **Glow** | `rgba(245, 158, 11, 0.15)` | Hover effects, focus rings |

**Option B — Emerald Green (Growth/Nurture):**

| Role | Hex | Usage |
|------|-----|-------|
| **Primary Accent** | `#10B981` | Buttons, links, active states |
| **Primary Dark** | `#059669` | Gradient endpoints |
| **Glow** | `rgba(16, 185, 129, 0.15)` | Hover effects, focus rings |

**Shared Forge Colors (same across all products):**

| Role | Hex | Usage |
|------|-----|-------|
| **Background (Primary)** | `#0F172A` | Main page background, dark navy |
| **Background (Card)** | `#1E293B` | Card surfaces, elevated elements |
| **Background (Card Hover)** | `#263548` | Hover state for cards |
| **Pro Accent (Indigo)** | `#6366F1` | Pro badges, pro features, upsell |
| **Success (Green)** | `#22C55E` | Success states, sent confirmations |
| **Warning (Amber)** | `#F59E0B` | Warnings, pending states |
| **Error (Red)** | `#EF4444` | Errors, bounced, failed sends |
| **Text (Primary)** | `#E5E7EB` | Body text on dark backgrounds |
| **Text (Muted)** | `#94A3B8` | Secondary text, labels |
| **Text (Dim)** | `#64748B` | Tertiary text, descriptions |
| **Border** | `#334155` | Card borders, dividers |
| **White** | `#FFFFFF` | Headings, emphasis text |

### Typography
- **Font Family:** -apple-system, BlinkMacSystemFont, "Inter", "Segoe UI", Roboto, sans-serif
- Same weight/size system as BackForge (consistency across Forge family)

### Border Radius
- **Cards:** 12px
- **Buttons:** 8px
- **Inputs:** 6px
- **Badges/Pills:** 20px

### Design Principles (Forge-Wide)
- **Dark-first** — dark navy/slate backgrounds with colored accents
- **Glow accents** — subtle glow effects on hover, focus, and active states
- **Card-based layout** — everything organized in cards
- **Accent color = action** — primary accent for all interactive elements
- **Indigo = premium** — indigo/purple exclusively for Pro features
- **Status colors are semantic** — green = sent/active, amber = pending/warning, red = bounced/error

---

## 4. UI Component Patterns

### Dashboard Stat Cards
- 4-column responsive grid
- Cards: Total Subscribers, Emails Sent, Open Rate (%), Click Rate (%)
- Each card: colored icon → large value → small label
- Accent glow line on hover

### Sequence List
- Table or card list showing: sequence name, status badge (active/draft/paused), email count, subscriber count
- Status badges: green = active, gray = draft, amber = paused
- Actions: Edit, Delete

### Sequence Editor
- Two sections: Sequence settings (name, slug, status) + Email list
- Email list: ordered cards showing position number, subject, delay in days, sent/open/click stats
- Add email form: subject, body (textarea with HTML), delay days
- Performance table per email

### Subscriber List
- Searchable, filterable table
- Columns: Email, Name, Status, Subscribed Date, Actions
- Status badges: green = active, red = unsubscribed, gray = bounced
- Bulk actions: Delete, Export CSV

### Signup Form (Frontend)
- Clean, minimal form: name field (optional) + email field + submit button
- Inline success/error messages
- Customizable via shortcode attributes
- Should look good on any theme (light or dark)

### Email Preview
- Full-width HTML render in new tab
- Sticky preview bar at top showing: subject, email #, day delay, merge tag notice
- Email template: dark header, white body, gray footer, 600px max width

---

## 5. Brand Voice & Messaging

### Tone
- **Empowering** — you own your list, you control your emails
- **Anti-SaaS** — positioned against per-subscriber pricing models
- **Practical** — focused on the specific use case (drip sequences), not trying to be everything
- **Honest about scope** — "DripForge does drip sequences. That's it. And it does them well."

### Key Messages

| Audience | Message |
|----------|---------|
| **Bloggers/creators** | "Build your email list and nurture subscribers with automated drip sequences. No monthly fees, no subscriber limits." |
| **Small business owners** | "Set up a lead nurture sequence once, let it run forever. New subscribers get your best content automatically." |
| **Developers** | "Self-hosted, SMTP-agnostic, WordPress-native. Use SES at $0.10/1000 emails. No SDK, no framework bloat." |
| **Cost-conscious users** | "Mailchimp charges $50/mo for 5,000 contacts. DripForge: $0/mo for unlimited contacts. You just pay your SMTP provider." |

### Competitor Positioning

| vs. | Our angle |
|-----|-----------|
| **Mailchimp** | "Stop paying per subscriber. Own your list." |
| **ConvertKit** | "Same drip sequences, zero monthly fees. Self-hosted." |
| **FluentCRM** | "Simpler. Lighter. Drip-focused, not a bloated CRM." |
| **MailPoet** | "No proprietary sending service. Use any SMTP you want." |
| **Newsletter plugin** | "Modern UI, better tracking, Forge ecosystem integration." |

### Words We Use
- Self-hosted, zero fees, unlimited, lightweight, automated, nurture, drip, sequence
- Your list, your data, your server
- Set-and-forget, hands-off, automated

### Words We Avoid
- "Marketing automation platform" (too enterprise)
- "CRM" (we're not a CRM)
- "AI-powered" (we don't use AI)
- "All-in-one" (we're intentionally focused)

---

## 6. Target Audience Personas

### Persona 1: Content Creator ("Alex")
- Runs a blog or YouTube channel with a WordPress site
- Wants to build an email list and send a welcome sequence
- Currently using Mailchimp free tier, hitting the 500 contact limit
- Cares about: simplicity, no monthly fees, "set it and forget it"
- Pain: Mailchimp wants $13/mo now that the list is growing

### Persona 2: Small Business Owner ("Rachel")
- Runs a service business (coaching, consulting, design)
- Wants a lead magnet → drip sequence → sales funnel
- Has a WordPress site, comfortable with plugins
- Cares about: lead nurture, conversion, professional emails
- Pain: paying $50+/mo for ConvertKit when she only sends 1 sequence

### Persona 3: Freelance Developer ("Dev")
- Builds WordPress sites for clients
- Wants to offer email automation as part of the package
- Needs white-label, multi-site, reliable delivery
- Cares about: lightweight, no vendor lock-in, client-friendly
- Pain: recommending SaaS tools that clients then pay monthly for

### Persona 4: Course Creator ("Jordan")
- Sells online courses, needs onboarding email sequences
- Wants conditional logic (if they opened email 3, send the upsell)
- Currently on ConvertKit at $49/mo
- Cares about: automation, analytics, professional templates
- Pain: paying per subscriber when most are on a free drip sequence

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
| Logo (full) | Header, about page — "DripForge" wordmark with icon |
| Logo (icon only) | Favicon, app icon, small spaces |
| Hero illustration | Landing page — conceptual (email + automation + growth) |
| Feature illustrations | One per major feature section |
| Pricing page graphics | Tier comparison visual |
| Cost comparison graphic | DripForge vs SaaS monthly costs over time |

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
- **Water drops / drip** — the "drip" metaphor is central (timed, consistent, nurturing)
- **Envelope / email** — email marketing, inbox, sending
- **Funnel / flow** — lead capture → nurture → conversion
- **Growth / plant / seedling** — nurturing leads into customers
- **Automation / gears / clock** — set-and-forget, timed sequences
- **Chain / sequence / timeline** — connected steps, ordered emails
- **Forge/anvil** — shared Forge family identity (crafting, building)

### Style Direction
- **Clean and modern** — same dark aesthetic as BackForge
- **Dark mode** — dark navy backgrounds, colored accents
- **Minimalist line art or geometric** — not cluttered
- **Subtle gradients** — accent color gradients, not rainbow
- **No stock photos of people checking email** — use abstract/conceptual
- **Timeline/sequence visualizations** — show the drip concept visually

### Logo Direction
- Should incorporate the "drip" concept (water drop, flow, or sequence dots)
- Combined with "forge" identity (strength, crafting)
- Must work at small sizes (WordPress admin menu = 20×20px)
- Should feel "marketing/communication" but not "corporate"
- Wordmark: clean sans-serif, consistent with Forge family

### Differentiation from BackForge
- BackForge = cloud/shield/protection (backup safety)
- DripForge = flow/drops/sequence/growth (email nurture)
- Different accent color but same dark foundation
- Same card-based UI patterns, different content

---

## 9. Pricing & Tiers

| Tier | Price | Sites | Target |
|------|-------|-------|--------|
| **Starter** | $39/year | 1 site | Bloggers, small sites |
| **Pro** | $79/year | 5 sites | Freelancers, small agencies |
| **Agency** | $149/year | Unlimited | Agencies, larger businesses |

**Positioning:** Priced below BackForge because the email plugin market is more price-sensitive. Middle tier (Pro) is the recommended/featured tier.

**Cost comparison angle:** "Mailchimp charges $50/mo for 5,000 contacts = $600/year. DripForge Pro: $79/year for unlimited contacts on 5 sites. Plus SES sending at $0.10/1000 emails."

---

## 10. Competitive Landscape (For Positioning)

### WordPress Plugins

| Competitor | Installs | Price | Our Advantage |
|-----------|----------|-------|---------------|
| MailPoet | 700K+ | Free + $10/mo sending | No proprietary sending service, use any SMTP |
| Newsletter | 300K+ | Free + €65/yr Pro | Modern UI, better tracking, Forge ecosystem |
| FluentCRM | 50K+ | Free + $103/yr | Simpler, lighter, drip-focused not bloated CRM |
| MC4WP | 4M+ | Free + Mailchimp SaaS | Self-hosted, no SaaS dependency |

### SaaS Competitors

| Service | Cost at 5K contacts | Our Advantage |
|---------|-------------------|---------------|
| Mailchimp | $50/mo ($600/yr) | $0/yr + $0.50/mo SES costs |
| ConvertKit | $49/mo ($588/yr) | Self-hosted, no platform lock-in |
| Drip | $89/mo ($1,068/yr) | Fraction of the cost, same drip functionality |
| ActiveCampaign | $49/mo ($588/yr) | No per-subscriber pricing ever |

---

## 11. Forge Product Family Context

DripForge is part of the **Forge** product family — a suite of self-hosted WordPress tools for developers and site owners who value control, performance, and independence.

| Product | Purpose | Accent Color |
|---------|---------|-------------|
| **BackForge** | S3 Backup & Restore | Teal (`#14B8A6`) |
| **LicenseForge** | Plugin Licensing & Sales | Indigo (`#6366F1`) |
| **DripForge** | Email Drip Automation | TBD (amber or emerald) |
| **ShieldForge** | WordPress Security | TBD |

### Cross-Sell Opportunities
- BackForge users → "Back up the site that's generating your leads"
- LicenseForge users → "Nurture your plugin customers with drip sequences"
- DripForge users → "Protect your subscriber data with BackForge backups"
- DripForge users → "Sell digital products to your list with LicenseForge"

**Shared brand traits:**
- Dark SaaS UI aesthetic
- "Forge" suffix naming
- Self-hosted, no SaaS dependency
- Lightweight, no bloat philosophy
- WordPress-native (hooks, filters, WP APIs)
- Free + Pro model
- Sold via LicenseForge

---

## 12. SEO & Discovery Keywords

### Primary Keywords
- email drip wordpress
- drip sequence plugin
- wordpress email automation
- self-hosted email marketing wordpress

### Secondary Keywords
- wordpress drip campaign
- email sequence plugin wordpress
- free email automation wordpress
- wordpress autoresponder plugin
- email nurture sequence wordpress

### Long-tail
- "how to build email drip sequence wordpress free"
- "self-hosted alternative to mailchimp wordpress"
- "wordpress email automation without monthly fees"
- "best free drip email plugin wordpress"
- "send drip emails with amazon ses wordpress"

---

## 13. Content Ideas (For Website/Blog)

### Landing Page Sections
1. Hero: Tagline + one-line description + CTA + dark UI screenshot
2. "Why DripForge?" — 3 key differentiators (zero fees, self-hosted, SMTP-agnostic)
3. Cost comparison — visual showing DripForge vs SaaS costs over 12 months
4. Feature grid — 6–8 features with icons and short descriptions
5. How it works — 3 steps (Install → Create Sequence → Capture Leads)
6. Use cases — Lead magnet, Welcome series, Onboarding, Sales funnel
7. Pricing — 3 tiers
8. FAQ
9. CTA — "Get Started Free"

### Blog Post Ideas
- "How to Build an Email Drip Sequence in WordPress (Free)"
- "Mailchimp vs Self-Hosted: Why I Stopped Paying Per Subscriber"
- "WordPress Email Automation with Amazon SES ($0.10/1000 Emails)"
- "FluentCRM vs DripForge: Which Self-Hosted Email Plugin?"
- "The True Cost of Email Marketing: SaaS vs Self-Hosted"
- "5 Drip Sequence Templates That Convert (With Examples)"

---

## 14. Technical Architecture (For Developer-Facing Content)

```
WordPress Site
┌─────────────────────────────────────────────┐
│  DripForge Plugin                           │
│                                             │
│  Visitor → [Signup Form] → Subscriber DB    │
│                                ↓            │
│  wp-cron (5 min) → [Drip Engine]            │
│                        ↓                    │
│  Merge Tags → HTML Template → wp_mail()     │
│                                ↓            │
│                         SMTP Provider       │
│                    (SES/SendGrid/Brevo)      │
│                                             │
│  Tracking: Open pixel + Click redirect      │
│  Analytics: Sent, Opened, Clicked, Rates    │
└─────────────────────────────────────────────┘
```

- 5 custom database tables (subscribers, sequences, emails, enrollments, send log)
- wp-cron every 5 minutes, 50 emails/batch, rate-limited
- Merge tags: {first_name}, {last_name}, {email}, {site_name}, {unsubscribe_link}
- Open tracking via 1×1 pixel, click tracking via redirect
- CAN-SPAM compliant unsubscribe with hash verification
- SMTP-agnostic: hooks into phpmailer_init temporarily per send

---

## 15. Screenshot Descriptions (For WordPress.org)

1. **Dashboard** — Stat cards showing total subscribers, emails sent, open rate, click rate + quick start guide
2. **Sequences List** — Sequence list with status badges (active/draft/paused), email counts, subscriber counts
3. **Sequence Editor** — Sequence settings + email list with position numbers, subjects, delays, and per-email stats
4. **Subscriber List** — Searchable subscriber table with status badges, filter options, and CSV export button
5. **Signup Form** — Frontend signup form embedded on a page via shortcode (clean, minimal design)
6. **Email Preview** — Full HTML email render showing the dark header, body content with merge tags replaced, and footer
7. **Settings** — SMTP configuration with provider quick-setup guides (SES, SendGrid, Brevo)
8. **Analytics** — Per-sequence performance table showing sent/opened/clicked/rates per email

---

## 16. Email Template Visual (For Design Reference)

The default email template that DripForge wraps around all outgoing emails:

```
┌─────────────────────────────────────────┐
│  ████████████████████████████████████████ │  ← Dark header (#1A2332)
│  Site Name (white, centered)             │     16px padding
├─────────────────────────────────────────┤
│                                          │
│  Email body content here.                │  ← White background
│                                          │     600px max-width
│  Hi {first_name},                        │     16px font, 1.6 line-height
│                                          │     40px padding
│  Your personalized drip email            │
│  content goes here...                    │
│                                          │
├─────────────────────────────────────────┤
│  Site Name • site-url.com                │  ← Light gray footer (#F8F8F8)
│  Unsubscribe                             │     12px text, centered
│  [1×1 tracking pixel]                    │
└─────────────────────────────────────────┘
```

---

*End of DripForge Creative Handoff*
