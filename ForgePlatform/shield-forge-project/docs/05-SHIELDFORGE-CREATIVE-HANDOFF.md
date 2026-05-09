# ShieldForge — Creative & Branding Handoff

*Use this document as context for ChatGPT (or any AI) when creating images, branding assets, website pages, marketing materials, social media content, or any visual/creative work for ShieldForge.*

---

## 1. Product Identity

| Field | Value |
|-------|-------|
| **Product Name** | ShieldForge |
| **Full Title** | ShieldForge – WordPress Security, Firewall & Login Protection |
| **Tagline (Primary)** | "Your security. Your server. Zero cloud dependency." |
| **Tagline (Secondary)** | "Lightweight WordPress security that runs entirely on your hosting." |
| **Product Family** | Forge (ForgeWP) |
| **Pro Version** | ShieldForge Pro |
| **Category** | WordPress Plugin — Security & Firewall |
| **WordPress.org Slug (target)** | `shieldforge` |
| **Current working slug** | `sf-security` |
| **Developer** | Edward Fong |
| **Website** | ekewaka.com |

---

## 2. What ShieldForge Does (Plain Language)

ShieldForge is a self-hosted WordPress security plugin that protects sites from brute force attacks, malware, and common web vulnerabilities — without sending your data to a third-party cloud service. It includes a web application firewall (WAF), login hardening, IP blocklist, rate limiting, file integrity monitoring, country blocking, and an activity log — all running locally on your server.

**In one sentence:** ShieldForge protects your WordPress site with a firewall, brute force protection, country blocking, and malware scanning — all self-hosted with zero cloud dependency.

### Key Differentiators
- **Country blocking in the free version** — Wordfence, Sucuri, and iThemes all gate this behind Pro ($99–199/yr)
- **Zero cloud dependency** — all scanning, blocking, and logging happens locally on your server
- **Lightweight** — designed for shared hosting, won't slow your site down
- **Privacy-first** — your security data never leaves your server (no "phone home")
- **WordPress-native** — uses wp-cron, WP database, standard hooks — no Composer, no SDK
- **Forge UI** — dark SaaS admin interface consistent with BackForge and DripForge

### Free Features
- Login hardening + brute force protection (auto-lockout with escalation)
- IP blocklist (manual + auto-block on lockout, CIDR ranges)
- Rate limiting (per-endpoint sliding window)
- Web Application Firewall with built-in ruleset (SQLi, XSS, traversal, RCE, WP-specific, bad bots)
- File integrity monitoring (SHA-256 checksums, scheduled scans, WordPress.org core verification)
- Country blocking via GeoIP (MaxMind GeoLite2 local database)
- Activity log (30-day retention, login events, blocks, user changes)
- Username enumeration prevention
- XMLRPC disable toggle
- REST API user endpoint blocking
- Dashboard with security overview and stats
- Email notification on lockout

### Pro Features ($39–$149/year)
- Two-factor authentication (TOTP — Google Authenticator, Authy, 1Password)
- Malware scanner with quarantine
- Custom WAF rules editor
- WAF learning mode (log-only before enforcing)
- Extended activity log (unlimited retention)
- Content change tracking (posts, pages, options)
- Real-time notifications (email + Slack/webhook)
- Weekly security digest email
- Multi-site network support
- White-label (remove ShieldForge branding)
- Priority support

---

## 3. Brand Identity & Visual Design

### Color Palette

ShieldForge's accent color should convey **protection, strength, and alertness**. Recommended: **Red-Orange / Crimson** — the color of shields, alerts, and security warnings. Alternatively, **Steel Blue** for a more defensive/armor feel.

**Option A — Crimson/Red-Orange (Protection/Alert):**

| Role | Hex | Usage |
|------|-----|-------|
| **Primary Accent** | `#E11D48` | Buttons, links, active states, brand color |
| **Primary Dark** | `#BE123C` | Gradient endpoints |
| **Glow** | `rgba(225, 29, 72, 0.15)` | Hover effects, focus rings |

**Option B — Steel Blue (Armor/Defense):**

| Role | Hex | Usage |
|------|-----|-------|
| **Primary Accent** | `#3B82F6` | Buttons, links, active states |
| **Primary Dark** | `#2563EB` | Gradient endpoints |
| **Glow** | `rgba(59, 130, 246, 0.15)` | Hover effects, focus rings |

**Shared Forge Colors (same across all products):**

| Role | Hex | Usage |
|------|-----|-------|
| **Background (Primary)** | `#0F172A` | Main page background, dark navy |
| **Background (Card)** | `#1E293B` | Card surfaces, elevated elements |
| **Background (Card Hover)** | `#263548` | Hover state for cards |
| **Pro Accent (Indigo)** | `#6366F1` | Pro badges, pro features, upsell |
| **Success (Green)** | `#22C55E` | Secure states, passed checks, allowed |
| **Warning (Amber)** | `#F59E0B` | Warnings, suspicious activity |
| **Error/Danger (Red)** | `#EF4444` | Blocked, critical alerts, attacks |
| **Text (Primary)** | `#E5E7EB` | Body text on dark backgrounds |
| **Text (Muted)** | `#94A3B8` | Secondary text, labels |
| **Text (Dim)** | `#64748B` | Tertiary text, descriptions |
| **Border** | `#334155` | Card borders, dividers |
| **White** | `#FFFFFF` | Headings, emphasis text |

### Severity Color System (Security-Specific)
| Severity | Color | Hex | Usage |
|----------|-------|-----|-------|
| **Critical** | Red | `#EF4444` | Active attacks, malware found, critical vulnerabilities |
| **High** | Orange | `#F97316` | Brute force attempts, suspicious files |
| **Medium** | Amber | `#F59E0B` | Rate limit hits, failed logins, warnings |
| **Low/Info** | Blue | `#3B82F6` | Informational events, successful logins |
| **Secure** | Green | `#22C55E` | All checks passed, no issues found |

### Typography
- **Font Family:** -apple-system, BlinkMacSystemFont, "Inter", "Segoe UI", Roboto, sans-serif
- Same weight/size system as all Forge products

### Border Radius
- **Cards:** 12px
- **Buttons:** 8px
- **Inputs:** 6px
- **Badges/Pills:** 20px

### Design Principles
- **Dark-first** — same dark navy/slate foundation as all Forge products
- **Security feels serious** — the UI should feel protective and authoritative, not playful
- **Real-time data** — dashboards should feel "alive" with recent events, counters, timestamps
- **Color = severity** — red for critical, amber for warning, green for secure (consistent, predictable)
- **Card-based layout** — consistent with Forge family
- **Indigo = premium** — Pro features use indigo accent

---

## 4. UI Component Patterns

### Security Dashboard
- **Threat summary cards:** Attacks blocked today, Active lockouts, Failed logins (24h), Security score
- **Live event feed:** Scrolling list of recent security events with severity badges and timestamps
- **Quick status indicators:** Firewall (on/off), Rate limiter (on/off), Country blocking (on/off), File monitor (last scan)
- **Threat chart:** Bar or line chart showing blocked attacks over last 7 days

### Firewall Log
- Table: Timestamp, IP, Rule matched, URI, Action taken (blocked/logged), Country flag
- Severity color-coded rows (red border for critical, amber for medium)
- Filter by: rule category, severity, date range, IP
- Expandable rows showing full request details

### IP Blocklist
- Two tabs: Blocked IPs / Allowed IPs
- Table: IP/CIDR, Reason, Source (manual/auto/import), Expires, Actions
- Add form: IP input, reason, permanent/temporary toggle with duration
- Bulk actions: Remove, Extend, Export CSV
- Import CSV button

### Login Security
- Recent login attempts table: IP, Username, Success/Fail, Timestamp
- Active lockouts list with countdown timers
- Settings: max attempts, lockout duration, escalation rules, whitelist IPs
- Chart: Failed vs successful logins (last 7 days)

### File Integrity Monitor
- Scan results: Changed files, New files, Deleted files (with counts)
- File list: Path, Status (modified/new/deleted), Last known hash, Current hash
- Actions per file: View diff (if possible), Mark as safe, Quarantine
- Last scan time, Next scan time, "Scan Now" button

### Malware Scanner (Pro)
- Scan progress bar during active scan
- Results: Suspicious files with matched pattern, severity, file path
- Actions: Quarantine, Mark safe, View file content (read-only)
- Quarantine manager: list of quarantined files with restore option

### Activity Log
- Filterable event log: Event type, User, IP, Details, Timestamp
- Event types: login, lockout, block, WAF, file change, user change, plugin change, settings change
- Search by IP, username, event type, date range
- Export CSV

### Country Blocking
- Country selector (multi-select with search)
- Mode toggle: Block selected countries / Allow only selected countries
- Apply to: Entire site, Login page only, Admin only, REST API only
- Visual: world map with blocked countries highlighted (optional, nice-to-have)

---

## 5. Brand Voice & Messaging

### Tone
- **Protective and authoritative** — "we've got your back"
- **No fear-mongering** — don't scare users into buying Pro (unlike some competitors)
- **Technical but clear** — security is complex, explain it simply
- **Privacy-respecting** — emphasize that data stays local, no cloud surveillance
- **Honest about scope** — "ShieldForge handles application-level security. For DDoS, you need Cloudflare."

### Key Messages

| Audience | Message |
|----------|---------|
| **WordPress site owners** | "Protect your site from brute force attacks, malware, and hackers — without paying $200/year or sending your data to the cloud." |
| **Privacy-conscious users** | "Your security data stays on your server. No cloud scanning, no phoning home, no third-party data sharing." |
| **Developers/agencies** | "Lightweight security that won't slow down client sites. Country blocking free. WAF included. Forge UI." |
| **Shared hosting users** | "Designed for shared hosting — no server-level access needed. Works with any WordPress host." |

### Competitor Positioning

| vs. | Our angle |
|-----|-----------|
| **Wordfence** | "Country blocking free (Wordfence charges $119/yr). No cloud dependency. Lighter on resources." |
| **Sucuri** | "Self-hosted, not SaaS. No $199/yr subscription. Your data stays on your server." |
| **iThemes Security** | "Built-in WAF included free. Country blocking included free. Modern dark UI." |
| **All-In-One Security** | "More comprehensive WAF ruleset. Better UI. Part of the Forge ecosystem." |

### The "Country Blocking Free" Hook
This is the #1 marketing differentiator. Every major competitor charges $99–199/year for country blocking. ShieldForge includes it in the free version. This alone will drive WordPress.org installs and word-of-mouth.

**Marketing line:** "Country blocking shouldn't cost $119/year. ShieldForge includes it free."

### Words We Use
- Self-hosted, local, private, lightweight, no cloud, zero dependency
- Protected, secured, hardened, monitored, blocked
- Firewall, WAF, brute force, lockout, rate limit
- Your server, your data, your rules

### Words We Avoid
- "Unhackable" or "100% secure" (nothing is)
- "Enterprise-grade" (unless actually enterprise)
- "AI-powered" (we use pattern matching, not AI)
- Fear-based language ("Your site WILL be hacked if...")
- "Military-grade encryption" (meaningless marketing term)

---

## 6. Target Audience Personas

### Persona 1: Small Site Owner ("Lisa")
- Runs a blog or small business site on shared hosting
- Got hacked once or is worried about it
- Non-technical, wants "install and forget"
- Cares about: simplicity, not slowing down her site, free protection
- Pain: Wordfence is confusing and resource-heavy on her $5/mo hosting

### Persona 2: Privacy-Conscious Developer ("Sam")
- Runs personal and client sites
- Doesn't want security data sent to third-party clouds
- Comfortable with technical settings
- Cares about: privacy, control, lightweight tools, no vendor lock-in
- Pain: every security plugin phones home or requires a cloud account

### Persona 3: Freelance Developer ("Kai")
- Manages 10–20 client WordPress sites
- Needs consistent security across all sites
- Wants country blocking (most attacks come from specific regions)
- Cares about: multi-site management, lightweight, professional
- Pain: paying $119/site/year for Wordfence Premium across 15 sites = $1,785/year

### Persona 4: Agency Security Lead ("Diana")
- Agency manages 50+ client sites
- Needs white-label, 2FA enforcement, malware scanning
- Currently using a mix of Wordfence free + manual hardening
- Cares about: consistency, reporting, client-facing professionalism
- Pain: no single lightweight solution covers all needs without bloat

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
| Logo (full) | Header, about page — "ShieldForge" wordmark with icon |
| Logo (icon only) | Favicon, app icon, small spaces |
| Hero illustration | Landing page — conceptual (shield + firewall + WordPress) |
| Feature illustrations | One per major feature (WAF, login, country blocking, scanner) |
| Threat visualization | Dashboard-style graphic showing attacks being blocked |
| Comparison table graphic | ShieldForge vs competitors feature matrix |

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
- **Shield** — the primary metaphor (protection, defense, blocking)
- **Firewall / barrier** — blocking attacks, filtering traffic
- **Lock / padlock** — secured, hardened, protected
- **Radar / monitoring** — scanning, detecting, watching
- **Armor / fortress** — strength, resilience, impenetrable
- **Forge / anvil** — shared Forge family identity (crafting your own security)
- **Globe with blocked regions** — country blocking visualization
- **Binary / code matrix** — technical security, pattern matching

### Style Direction
- **Dark and serious** — security should feel authoritative, not playful
- **Dark mode** — dark navy backgrounds, colored accents (same Forge foundation)
- **Sharp/angular geometric shapes** — shields, hexagons, angular patterns
- **Subtle red/amber accents** — convey alertness without alarm
- **Green for "secure" states** — reassuring when everything is OK
- **No skull-and-crossbones or hacker imagery** — avoid clichés and fear-mongering
- **No padlock stock photos** — use abstract/geometric instead
- **Data visualization aesthetic** — dashboards, charts, real-time feeds

### Logo Direction
- Should incorporate the "shield" concept prominently
- Combined with "forge" identity (strength, crafting, building)
- Must work at small sizes (WordPress admin menu = 20×20px)
- Should feel "protective and strong" not "scary"
- Color: primary accent (crimson or steel blue) on dark background
- Wordmark: clean sans-serif, consistent with Forge family
- The shield shape itself could BE the icon (simple, recognizable)

### Differentiation from Other Forge Products
- BackForge = teal, cloud/backup (safety through backups)
- DripForge = TBD, flow/drops (email nurture)
- LicenseForge = indigo, key/commerce (selling products)
- ShieldForge = crimson or steel blue, shield/armor (active protection)
- Same dark foundation, different accent and metaphor

---

## 9. Pricing & Tiers

| Tier | Price | Sites | Target |
|------|-------|-------|--------|
| **Personal** | $39/year | 1 site | Individual site owners |
| **Professional** | $79/year | 5 sites | Freelancers, small agencies |
| **Agency** | $149/year | Unlimited | Agencies managing many clients |

**Positioning:** Professional tier ($79) is the recommended/featured tier.

**Cost comparison angle:**

| Competitor | Price per site/year | 5 sites cost |
|-----------|-------------------|-------------|
| Wordfence Premium | $119/site | $595/year |
| Sucuri | $199/site | $995/year |
| iThemes Security Pro | $99 (1 site) | $299 (10 sites) |
| **ShieldForge Pro** | **$79 (5 sites)** | **$79/year** |

"Protect 5 sites for less than Wordfence charges for one."

---

## 10. Competitive Landscape (For Positioning)

### Feature Comparison

| Feature | ShieldForge Free | Wordfence Free | Sucuri Free | iThemes Free |
|---------|-----------------|---------------|-------------|-------------|
| WAF | ✅ | ✅ (delayed rules) | ❌ | ❌ |
| Brute Force Protection | ✅ | ✅ | ✅ | ✅ |
| Country Blocking | ✅ | ❌ (Pro only) | ❌ (Pro only) | ❌ (Pro only) |
| File Integrity | ✅ | ✅ | ✅ (limited) | ✅ |
| IP Blocklist | ✅ | ✅ (delayed) | ❌ | ❌ |
| Rate Limiting | ✅ | Partial | ❌ | ❌ |
| Activity Log | ✅ | ✅ | ✅ | ✅ |
| REST API Protection | ✅ | Partial | ❌ | Partial |
| No Cloud Dependency | ✅ | ❌ | ❌ | ❌ |
| Dark SaaS UI | ✅ | ❌ | ❌ | ❌ |
| Lightweight | ✅ | ❌ (heavy) | Partial | ✅ |

### Market Context
- WordPress security plugin market is dominated by Wordfence (4M+ installs)
- Users frequently complain about Wordfence being resource-heavy and confusing
- Sucuri's free plugin is limited — real features require their $199/yr cloud service
- iThemes Security was acquired by SolidWP, rebranded, and lost community trust
- There's an opening for a lightweight, privacy-first, modern-UI security plugin

---

## 11. Forge Product Family Context

ShieldForge completes the "run your WordPress business without SaaS dependencies" story.

| Product | Purpose | Accent Color | Relationship |
|---------|---------|-------------|--------------|
| **BackForge** | S3 Backup & Restore | Teal (`#14B8A6`) | "Back up before security changes" |
| **LicenseForge** | Plugin Licensing & Sales | Indigo (`#6366F1`) | Sells ShieldForge Pro |
| **DripForge** | Email Drip Automation | TBD | "Protect your subscriber data" |
| **ShieldForge** | WordPress Security | TBD (crimson or steel blue) | Protects all other Forge products |

### The Full Forge Stack Story
A site owner using the complete Forge ecosystem has:
- **BackForge** — automated backups to S3
- **ShieldForge** — firewall, brute force protection, malware scanning
- **DripForge** — email list building and drip sequences
- **LicenseForge** — selling digital products with licensing

All self-hosted. All lightweight. All under one design language. No SaaS dependencies.

### Cross-Sell Opportunities
- BackForge users → "Secure the site you're backing up"
- ShieldForge users → "Back up before making security changes → BackForge"
- DripForge users → "Protect your subscriber data → ShieldForge"
- LicenseForge users → "Protect your revenue-generating site → ShieldForge"

### Bundle Pricing (Future)
| Bundle | Includes | Price |
|--------|----------|-------|
| Forge Starter | BackForge Pro + ShieldForge Pro | $69/yr |
| Forge Business | BackForge + ShieldForge + DripForge Pro | $99/yr |
| Forge Agency | All Pro plugins, unlimited sites | $249/yr |

---

## 12. SEO & Discovery Keywords

### Primary Keywords
- wordpress security plugin
- wordpress firewall plugin
- wordpress brute force protection
- wordpress malware scanner
- wordpress login protection

### Secondary Keywords
- wordpress country blocking free
- self-hosted wordpress security
- lightweight wordpress firewall
- wordpress waf plugin
- wordpress ip blocker
- wordpress file integrity monitoring

### Long-tail
- "wordpress security plugin with free country blocking"
- "lightweight alternative to wordfence"
- "wordpress security without cloud dependency"
- "best free wordpress firewall plugin"
- "wordpress brute force protection shared hosting"
- "wordfence alternative lightweight"

---

## 13. Content Ideas (For Website/Blog)

### Landing Page Sections
1. Hero: Tagline + one-line description + CTA + dark dashboard screenshot
2. "The Problem" — security plugins are bloated, expensive, and send your data to the cloud
3. "Country Blocking Free" callout — highlight the #1 differentiator prominently
4. Feature grid — 8 features with icons (WAF, brute force, country blocking, file monitor, etc.)
5. How it works — 3 steps (Install → Configure → Protected)
6. Comparison table — vs Wordfence, Sucuri, iThemes
7. "Privacy First" section — your data stays on your server
8. Pricing — 3 tiers
9. FAQ
10. CTA — "Protect Your Site Free"

### Blog Post Ideas
- "WordPress Security Checklist for 2026 (Free Guide)"
- "Wordfence vs ShieldForge: Lightweight Security Compared"
- "How to Block Countries in WordPress for Free"
- "Why Your Security Plugin Shouldn't Phone Home"
- "WordPress WAF Explained: How Firewalls Protect Your Site"
- "The True Cost of WordPress Security: SaaS vs Self-Hosted"
- "WordPress Brute Force Protection Without Slowing Your Site"

---

## 14. Technical Architecture (For Developer-Facing Content)

```
Incoming HTTP Request
         │
         ▼
┌─────────────────────────────────────────┐
│  ShieldForge Security Layers            │
│                                         │
│  1. [IP Blocklist] ← transient cache    │
│     blocked → 403                       │
│         │                               │
│  2. [Rate Limiter] ← sliding window     │
│     exceeded → 429 + temp block         │
│         │                               │
│  3. [Country Check] ← local GeoIP DB   │
│     blocked country → 403               │
│         │                               │
│  4. [WAF Rules] ← pattern matching     │
│     SQLi/XSS/traversal/RCE → 403       │
│         │                               │
│  5. Request passes → WordPress loads    │
│         │                               │
│  6. [Login Hardening] ← authenticate    │
│  7. [REST API Protection] ← rest_init  │
│  8. [File Monitor] ← wp-cron scan      │
│  9. [Activity Log] ← action hooks      │
└─────────────────────────────────────────┘
```

### Key Technical Details
- Hot path (every request): blocklist + rate limit + country + WAF — all transient-cached, minimal DB queries
- WAF uses combined regex per category (6 regex calls instead of 50+)
- File integrity: SHA-256 checksums, chunked scanning (500 files/cron run)
- Malware scanner: signature-based, skips binary/images, quarantine to protected directory
- GeoIP: MaxMind GeoLite2 local database (no external API calls)
- Rate limiter: transient-based sliding window, per-endpoint configurable limits
- Optional mu-plugin dropin for earliest possible IP blocking (before WordPress loads)
- 6 custom database tables (log, blocklist, login_attempts, lockouts, file_checksums, two_factor)

---

## 15. Screenshot Descriptions (For WordPress.org)

1. **Security Dashboard** — Dark UI with threat summary cards (attacks blocked, active lockouts, failed logins), live event feed, and status indicators
2. **Firewall Log** — WAF blocked requests with severity badges, matched rules, IPs, URIs, and country flags
3. **Login Security** — Recent login attempts table, active lockouts with countdown, failed/success chart
4. **IP Blocklist** — Block/allow list management with CIDR support, auto-block entries, expiry dates
5. **File Integrity Monitor** — Scan results showing changed/new/deleted files with SHA-256 verification
6. **Country Blocking** — Country selector with block/allow mode, apply-to options (login, admin, REST API, entire site)
7. **Activity Log** — Filterable event log with severity colors, event types, user info, and timestamps
8. **Settings** — General security settings with Forge dark UI, feature toggles, notification configuration

---

## 16. Security Dashboard Visualization (For Design Reference)

The dashboard should feel like a "security command center" — real-time, data-rich, authoritative.

```
┌─────────────────────────────────────────────────────────────┐
│  🛡️  ShieldForge Security                    [Scan Now]     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  │
│  │ 🔴 247   │  │ 🟡 3     │  │ 🔵 1,204 │  │ 🟢 A+    │  │
│  │ Attacks  │  │ Active   │  │ Requests │  │ Security │  │
│  │ Blocked  │  │ Lockouts │  │ Today    │  │ Score    │  │
│  │ (24h)    │  │          │  │          │  │          │  │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘  │
│                                                             │
│  ┌─ Recent Security Events ─────────────────────────────┐  │
│  │ 🔴 CRITICAL  WAF Block: SQLi attempt    2m ago  🇷🇺  │  │
│  │ 🟡 WARNING   Failed login: admin        5m ago  🇨🇳  │  │
│  │ 🟡 WARNING   Rate limited               8m ago  🇧🇷  │  │
│  │ 🔵 INFO      Successful login: edward   1h ago  🇺🇸  │  │
│  │ 🔴 CRITICAL  Country blocked            1h ago  🇰🇵  │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌─ Protection Status ──────────────────────────────────┐  │
│  │ 🟢 Firewall: Active    🟢 Rate Limiter: Active      │  │
│  │ 🟢 Login Protection: On  🟢 Country Blocking: 12    │  │
│  │ 🟢 File Monitor: Clean (scanned 2h ago)              │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

---

## 17. Trust & Credibility Elements

For a security product, trust is paramount. Include these on the website:

- **"No Cloud" badge** — prominently display that data never leaves the server
- **Open source core** — GPL v2+, code is auditable, no obfuscation
- **WAF ruleset transparency** — rules are visible PHP arrays, not encrypted black boxes
- **Performance benchmarks** — show that ShieldForge adds < 5ms to request time
- **WordPress.org listing** — free version reviewed and approved by WordPress.org team
- **Forge ecosystem** — part of a family of trusted, established plugins
- **Security practices** — the plugin itself follows all WordPress security best practices
- **No false scarcity** — don't claim "your site is under attack RIGHT NOW" to drive sales

---

*End of ShieldForge Creative Handoff*
