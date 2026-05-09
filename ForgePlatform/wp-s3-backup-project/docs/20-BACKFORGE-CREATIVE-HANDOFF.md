# BackForge — Creative & Branding Handoff

*Use this document as context for ChatGPT (or any AI) when creating images, branding assets, website pages, marketing materials, social media content, or any visual/creative work for BackForge.*

---

## 1. Product Identity

| Field | Value |
|-------|-------|
| **Product Name** | BackForge |
| **Full Title** | BackForge – S3 Backup for WordPress |
| **Tagline (Primary)** | "Your backups. Your bucket. Your control." |
| **Tagline (Secondary)** | "Direct S3 backups. Zero dependencies." |
| **Product Family** | Forge (ForgeWP) |
| **Pro Version** | BackForge Pro |
| **Category** | WordPress Plugin — Backup & Restore |
| **WordPress.org Slug** | `backforge-s3-backup` |
| **Developer** | Edward Fong |
| **Website** | ekewaka.com |

---

## 2. What BackForge Does (Plain Language)

BackForge is a lightweight WordPress plugin that creates full site backups (database + files) and uploads them directly to Amazon S3. It uses the S3 REST API with AWS Signature V4 signing — no AWS SDK, no Composer dependencies, no bloat. The entire plugin is ~50KB.

**In one sentence:** BackForge lets WordPress site owners back up their entire site to their own Amazon S3 bucket with one click, on a schedule, and restore it just as easily.

### Key Differentiators
- **No AWS SDK** — direct REST API calls, keeping the plugin tiny (~50KB vs competitors at 5–30MB)
- **Your S3 bucket** — data never passes through third-party servers
- **Works on cheap shared hosting** — no heavy dependencies or memory requirements
- **Encrypted credentials** — AES-256-CBC at rest (competitors store plaintext)
- **Free version is genuinely useful** — full backup + restore, not crippled
- **S3-compatible** — also works with Backblaze B2, Wasabi, DigitalOcean Spaces, MinIO

### Free Features
- Full database backup (gzipped SQL)
- Full wp-content file backup (zip)
- One-click manual backup with real-time progress
- Scheduled backups (daily/weekly/monthly)
- One-click full site restore with compatibility checks
- Multipart upload for large files (10MB chunks)
- Activity log with live auto-refresh
- Encrypted credential storage
- Backup manifest with SHA-256 checksums
- S3-compatible endpoint support

### Pro Features ($49–$199/year)
- Selective restore (DB only or files only)
- URL replacement on restore (serialization-safe)
- Cross-site restore (restore from another site's backups)
- Upload & restore from local files
- Incremental backups (only changed files)
- Client-side AES-256 encryption before upload
- Storage class management (Standard → Glacier)
- Cost estimate calculator
- Email & Slack/webhook notifications
- Custom schedules (hourly, every 4/6 hours, time-of-day)
- White-label (Agency tier)

---

## 3. Brand Identity & Visual Design

### Color Palette

| Role | Hex | RGB | Usage |
|------|-----|-----|-------|
| **Background (Primary)** | `#0F172A` | 15, 23, 42 | Main page background, dark navy |
| **Background (Card)** | `#1E293B` | 30, 41, 59 | Card surfaces, elevated elements |
| **Background (Card Hover)** | `#263548` | 38, 53, 72 | Hover state for cards |
| **Primary Accent (Teal)** | `#14B8A6` | 20, 184, 166 | Buttons, links, active states, brand color |
| **Primary Dark (Teal)** | `#0D7377` | 13, 115, 119 | Gradient endpoints, darker teal |
| **Pro Accent (Indigo)** | `#6366F1` | 99, 102, 241 | Pro badges, pro features, upsell elements |
| **Pro Light** | `#8B5CF6` | 139, 92, 246 | Pro hover states |
| **Success (Green)** | `#22C55E` | 34, 197, 94 | Success states, healthy indicators |
| **Warning (Amber)** | `#F59E0B` | 245, 158, 11 | Warnings, attention needed |
| **Error (Red)** | `#EF4444` | 239, 68, 68 | Errors, destructive actions |
| **Text (Primary)** | `#E5E7EB` | 229, 231, 235 | Body text on dark backgrounds |
| **Text (Muted)** | `#94A3B8` | 148, 163, 184 | Secondary text, labels |
| **Text (Dim)** | `#64748B` | 100, 116, 139 | Tertiary text, descriptions |
| **Border** | `#334155` | 51, 65, 85 | Card borders, dividers |
| **Border (Light)** | `#475569` | 71, 85, 105 | Hover borders |
| **White** | `#FFFFFF` | 255, 255, 255 | Headings, emphasis text |

### Gradient Definitions
- **Primary Button:** `linear-gradient(135deg, #14B8A6, #0D7377)`
- **Danger Button:** `linear-gradient(135deg, #EF4444, #DC2626)`
- **Pro Badge:** `linear-gradient(135deg, #6366F1, #8B5CF6)`
- **Teal Glow Line:** `linear-gradient(90deg, transparent, #14B8A6, transparent)`

### Glow Effects
- **Teal Glow (Subtle):** `0 0 20px rgba(20, 184, 166, 0.15)`
- **Teal Glow (Strong):** `0 0 20px rgba(20, 184, 166, 0.30)`
- **Pro Glow:** `0 0 12px rgba(99, 102, 241, 0.15)`
- **Button Hover Glow:** `0 4px 16px rgba(20, 184, 166, 0.30)`
- **Success Dot Glow:** `0 0 6px #22C55E`
- **Error Dot Glow:** `0 0 6px #EF4444`

### Typography
- **Font Family:** -apple-system, BlinkMacSystemFont, "Inter", "Segoe UI", Roboto, sans-serif
- **Headings:** Bold/Extra-bold (700–800), white (#FFFFFF)
- **Body:** Regular (400), light gray (#E5E7EB)
- **Labels:** Semibold (600), 12–13px, uppercase with letter-spacing for stat labels
- **Values/Numbers:** Extra-bold (800), 24px+, white

### Border Radius
- **Cards:** 12px
- **Buttons:** 8px
- **Inputs:** 6px
- **Badges/Pills:** 20px (fully rounded)

### Design Principles
- **Dark-first:** The entire UI is dark mode — navy/slate backgrounds with teal accents
- **Glow accents:** Subtle teal glow effects on hover, focus, and active states
- **Card-based layout:** Everything is organized in cards with consistent padding and borders
- **Progressive disclosure:** Simple by default, complexity revealed on demand
- **Teal = action/brand:** Teal is used for all primary interactive elements
- **Indigo = premium:** Indigo/purple is exclusively for Pro features and upsell
- **Status colors are semantic:** Green = healthy, Amber = warning, Red = error, Gray = inactive

---

## 4. UI Component Patterns

### Stat Cards (Dashboard)
- 4-column responsive grid
- Each card: colored icon (40×40px rounded square) → large value (24px bold white) → small label (12px uppercase muted)
- Subtle teal glow line appears at top on hover
- Cards: last backup time, next scheduled, S3 storage used, error count

### Settings Cards
- 2-column grid on desktop, 1-column on mobile
- Card header: teal dashicon + bold white title
- Card body: form fields with dark inputs
- Dark inputs with teal focus glow ring

### Backup List
- Vertical stack of backup cards
- Each card: date header → file rows (DB=blue icon, Files=amber icon, Manifest=purple icon) → action footer
- File rows show: icon, filename, size, storage class badge
- Actions: Restore button (teal), Delete button (red outline)

### Buttons
- **Primary:** Teal gradient, white text, glow shadow, lifts 1px on hover
- **Danger:** Red gradient, white text, red glow shadow
- **Outline:** Transparent with border, turns teal on hover with glow
- **Small variant:** Reduced padding for inline actions

### Status Indicators
- Pill-shaped badges with glowing dot
- Green pill + green dot = healthy/active
- Amber pill + amber dot = warning
- Red pill + red dot = error
- Gray pill + gray dot = inactive/off

### Progress Bar
- 8px height, rounded, dark track
- Teal gradient fill with glow shadow
- Animated width transition

### Empty States
- Centered layout with large icon (48px, dim color)
- Bold white title
- Muted description text
- Primary CTA button below

---

## 5. Brand Voice & Messaging

### Tone
- **Confident but not arrogant** — we know our product is good, we don't need to trash competitors
- **Technical but accessible** — developers appreciate precision, but non-devs should understand too
- **Direct and honest** — no marketing fluff, no "revolutionary" or "game-changing"
- **Slightly opinionated** — we believe in lightweight, self-hosted, no-bloat solutions

### Key Messages

| Audience | Message |
|----------|---------|
| **WordPress site owners** | "Protect your site with automatic backups to your own S3 bucket. Set it up once, forget about it." |
| **Developers/DevOps** | "Direct S3 REST API with SigV4 signing. No SDK, no Composer, no bloat. 50KB total." |
| **Agencies** | "Manage backups across all client sites. Selective restore, URL replacement, white-label." |
| **Cost-conscious users** | "Your S3 bucket, your pricing. No monthly SaaS fees. Pay only for what you store." |

### Competitor Positioning

| vs. | Our angle |
|-----|-----------|
| **UpdraftPlus** | "Lighter, faster, no SDK bloat. Your data stays in your bucket." |
| **BlogVault** | "No SaaS lock-in. No monthly fees. You own the infrastructure." |
| **BackupBuddy/Solid** | "Modern architecture. Direct API. Works on any hosting." |
| **All-in-One Migration** | "Purpose-built for S3. Real scheduled backups, not just migration." |

### Words We Use
- Lightweight, direct, secure, encrypted, reliable, self-hosted, no-bloat
- Your bucket, your data, your control
- One-click, set-and-forget, automatic

### Words We Avoid
- Revolutionary, game-changing, best-in-class, enterprise-grade (unless actually enterprise)
- "Just works" (overused), "blazing fast" (cliché)
- Anything that sounds like SaaS marketing speak

---

## 6. Target Audience Personas

### Persona 1: Solo Site Owner ("Sarah")
- Runs a WooCommerce store or blog
- Non-technical, wants "set it and forget it"
- Cares about: reliability, simplicity, cost
- Pain: current backup plugin is bloated/expensive/confusing

### Persona 2: Freelance Developer ("Marcus")
- Manages 3–10 client WordPress sites
- Comfortable with AWS, wants control
- Cares about: lightweight tools, no vendor lock-in, multi-site management
- Pain: paying per-site SaaS fees for each client

### Persona 3: Agency Technical Lead ("Priya")
- Manages 20+ sites for clients
- Needs white-label, selective restore, URL replacement for migrations
- Cares about: efficiency, reliability, professional presentation
- Pain: restore failures, migration headaches, client-facing branding

### Persona 4: DevOps/Cloud Engineer ("Jake")
- Runs WordPress on AWS infrastructure
- Wants Terraform-managed backup infra, direct S3 integration
- Cares about: no SDK bloat, IAM least privilege, infrastructure as code
- Pain: plugins that bundle massive SDKs or phone home

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
| Logo (full) | Header, about page — "BackForge" wordmark with icon |
| Logo (icon only) | Favicon, app icon, small spaces |
| Hero illustration | Landing page hero — conceptual (cloud + shield + WordPress) |
| Feature illustrations | One per major feature section |
| Pricing page graphics | Tier comparison visual |
| Testimonial section | Customer photos/avatars (placeholder or stock) |

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
- **Forge/anvil/craftsmanship** — the "Forge" brand implies building, crafting, strength
- **Shield/vault/lock** — security, protection, encrypted backups
- **Cloud with upward arrow** — uploading to S3, cloud storage
- **Gear/cog** — automation, scheduled backups, "set and forget"
- **Checkmark/shield combo** — verified, protected, backed up
- **Minimalist line art** — matches the "lightweight" positioning
- **Dark backgrounds with teal accents** — consistent with the admin UI

### Style Direction
- **Clean and modern** — not cluttered, not "enterprise gray"
- **Dark mode aesthetic** — dark navy backgrounds, glowing teal accents
- **Geometric/angular** — sharp lines, not rounded/bubbly
- **Subtle gradients** — teal-to-dark-teal, not rainbow
- **No stock photos of people at computers** — use abstract/conceptual instead
- **Isometric illustrations** are acceptable if they match the dark palette

### Logo Direction
- Should incorporate the "forge" concept (anvil, hammer, spark, or abstract geometric)
- Teal as primary color on dark background
- Must work at small sizes (WordPress admin menu = 20×20px)
- Should feel "technical" not "playful"
- Wordmark: clean sans-serif, possibly with a subtle teal accent on one letter

---

## 9. Pricing & Tiers

| Tier | Price | Sites | Target |
|------|-------|-------|--------|
| **Personal** | $49/year | 1 site | Individual site owners |
| **Professional** | $99/year | 5 sites | Freelancers, small agencies |
| **Agency** | $199/year | Unlimited | Agencies, larger businesses |

**Positioning:** Middle tier (Professional) is the recommended/featured tier.

**Comparison angle:** "UpdraftPlus Premium starts at $70/yr for 2 sites. BackForge Pro gives you 5 sites for $99/yr with features they charge extra for."

---

## 10. Competitive Landscape (For Positioning)

| Competitor | Installs | Price | Our Advantage |
|-----------|----------|-------|---------------|
| UpdraftPlus | 3M+ | $70–195/yr | Lighter (50KB vs 15MB), no SDK, encrypted creds |
| All-in-One Migration | 5M+ | Free/Pro | Purpose-built for S3, real scheduling, restore |
| Duplicator | 1M+ | $79/yr | Direct S3 API, no installer complexity |
| BlogVault | 200K+ | $89–299/yr | Self-hosted, no SaaS fees, your data |
| BackWPup | 500K+ | $99/yr | Modern UI, better restore, no bloat |

---

## 11. Forge Product Family Context

BackForge is part of the **Forge** product family — a suite of self-hosted WordPress tools for developers and site owners who value control, performance, and independence.

| Product | Purpose | Accent Color |
|---------|---------|-------------|
| **BackForge** | S3 Backup & Restore | Teal (`#14B8A6`) |
| **LicenseForge** | Plugin Licensing & Sales | Indigo (`#6366F1`) |
| **DripForge** | Email Drip Automation | (TBD) |
| **ShieldForge** | WordPress Security | (TBD) |

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
- wordpress backup plugin
- wordpress s3 backup
- backup wordpress to amazon s3
- wordpress backup to s3

### Secondary Keywords
- direct s3 backup wordpress
- wordpress backup shared hosting
- lightweight wordpress backup
- wordpress backup no sdk
- wordpress backup encrypted
- wordpress restore from s3

### Long-tail
- "how to backup wordpress to s3 without sdk"
- "best free wordpress s3 backup plugin"
- "wordpress backup plugin for shared hosting"
- "backup wordpress to your own s3 bucket"

---

## 13. Content Ideas (For Website/Blog)

### Landing Page Sections
1. Hero: Tagline + one-line description + CTA + dark UI screenshot
2. "Why BackForge?" — 3 key differentiators with icons
3. Feature grid — 6–8 features with icons and short descriptions
4. How it works — 3 steps (Install → Connect S3 → Backup)
5. Comparison table — vs top 3 competitors
6. Pricing — 3 tiers
7. FAQ — 5–6 common questions
8. CTA — "Get Started Free" button

### Blog Post Ideas
- "How to Back Up WordPress to Amazon S3 (Free Guide)"
- "WordPress Backup Security: Why Encryption Matters"
- "UpdraftPlus vs BackForge: Which S3 Backup Plugin?"
- "Setting Up WordPress Backups with Terraform"
- "Why Your Backup Plugin Shouldn't Bundle the AWS SDK"
- "WordPress Backup Best Practices for 2026"

---

## 14. Technical Architecture (For Developer-Facing Content)

```
WordPress Site                          AWS
┌─────────────────────┐                ┌──────────────────┐
│  BackForge Plugin   │   HTTPS/SigV4  │  S3 Bucket       │
│  ┌───────────────┐  │ ──────────────►│  ├── db.sql.gz   │
│  │ Backup Engine │  │                │  ├── files.zip   │
│  │ S3 Client     │  │                │  └── manifest.json│
│  │ Crypto        │  │                └──────────────────┘
│  │ Restore       │  │
│  └───────────────┘  │                ┌──────────────────┐
│                     │                │  IAM User        │
│  Credentials:       │                │  (least privilege)│
│  AES-256-CBC        │                └──────────────────┘
│  encrypted at rest  │
└─────────────────────┘
```

- No AWS SDK (0 dependencies)
- Direct S3 REST API with AWS Signature V4
- All HTTP via WordPress `wp_remote_request()`
- Multipart upload for files > 25MB (10MB chunks)
- Database export via `$wpdb` (no mysqldump/exec)
- File compression via PHP `ZipArchive`
- Credentials encrypted with AES-256-CBC using WordPress salts

---

## 15. Screenshot Descriptions (For WordPress.org)

1. **Dashboard** — Dark SaaS UI showing stat cards (last backup, next scheduled, storage used, status indicators)
2. **Backup List** — Card-based backup list with file type icons, storage class badges, download/restore/delete actions
3. **Settings** — Card-based settings layout with AWS credentials, S3 configuration, schedule options
4. **Backup Progress** — Real-time step-by-step backup progress with checkmarks and progress bar
5. **Restore Confirmation** — Pre-restore compatibility check showing PHP version, WP version, URL comparison
6. **Activity Log** — Log viewer with colored level badges and auto-refresh indicator
7. **Upgrade Page** — Pro feature cards with pricing tiers

---

*End of BackForge Creative Handoff*
