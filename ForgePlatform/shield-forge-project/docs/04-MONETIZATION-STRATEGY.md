# ShieldForge — Monetization Strategy

*Created: June 2025*

---

## Table of Contents

1. [Revenue Model](#revenue-model)
2. [Free vs Pro Feature Split](#free-vs-pro-feature-split)
3. [Pricing](#pricing)
4. [WordPress.org Strategy](#wordpressorg-strategy)
5. [Cross-Sell Opportunities](#cross-sell-opportunities)
6. [Growth Strategy](#growth-strategy)

---

## Revenue Model

Same model as BackForge: free plugin on WordPress.org drives awareness, Pro plugin sold via LicenseForge on ekewaka.com.

```
WordPress.org (free)
    │
    │ user installs, sees Pro features locked
    ▼
ekewaka.com/shieldforge (pricing page)
    │
    │ purchase via LicenseForge checkout
    ▼
License key → activates Pro features
    │
    │ annual renewal
    ▼
Recurring revenue
```

---

## Free vs Pro Feature Split

### Guiding Principle
Free must be genuinely useful — not a crippled demo. The free plugin should be the best free security plugin available. Pro adds convenience, advanced features, and professional tools.

### Free (ShieldForge)
Everything a small site owner needs:
- Login hardening + brute force protection
- IP blocklist (manual + auto)
- Rate limiting
- WAF with built-in ruleset
- File integrity monitoring
- Activity log (30-day retention)
- Username enumeration prevention
- XMLRPC disable
- REST API user endpoint blocking
- Country blocking (GeoIP)
- Dashboard with security overview
- Email notification on lockout

### Pro (ShieldForge Pro)
What agencies and power users pay for:
- Two-factor authentication (TOTP)
- Malware scanner with quarantine
- Custom WAF rules editor
- WAF learning mode
- Extended activity log (unlimited retention)
- Content change tracking (posts, pages, options)
- Real-time notifications (email + Slack/webhook)
- Weekly security digest email
- Multi-site network support
- White-label (remove ShieldForge branding)
- Priority support

### Why This Split Works
- **Country blocking in free** is the hook — Wordfence, Sucuri, and iThemes all gate this behind Pro. Offering it free generates word-of-mouth.
- **2FA in Pro** is justified — it requires per-user setup, backup codes, recovery flows. It's a premium feature with real support overhead.
- **Malware scanner in Pro** is justified — signature maintenance, quarantine management, and false positive handling require ongoing work.
- **Custom WAF rules in Pro** — power users who need custom rules are running businesses and will pay.

---

## Pricing

### Tiers (via LicenseForge)

| Tier | Price | Sites | Target |
|------|-------|-------|--------|
| Personal | $39/year | 1 site | Individual site owners |
| Professional | $79/year | 5 sites | Freelancers, small agencies |
| Agency | $149/year | Unlimited | Agencies managing many clients |

### Why Lower Than Competitors
- Wordfence Premium: $119/yr per site
- Sucuri: $199/yr per site
- iThemes Security Pro: $99/yr

ShieldForge undercuts on price while offering country blocking free (which competitors charge for). The lower price point reduces purchase friction and the self-hosted model means no ongoing infrastructure costs per customer.

### Lifetime Option (Future)
Consider a lifetime deal for early adopters:
- Personal Lifetime: $149
- Professional Lifetime: $299
- Agency Lifetime: $499

This generates upfront cash and builds a loyal user base. Limit to first 100 customers.

---

## WordPress.org Strategy

### Listing Optimization
- **Title:** ShieldForge — WordPress Security, Firewall & Login Protection
- **Tags:** security, firewall, malware, brute force, login protection
- **Short description:** Lightweight WordPress security with firewall, brute force protection, country blocking, and file integrity monitoring — all self-hosted, zero cloud dependency.

### Key Differentiators to Highlight
1. Country blocking included free (competitors charge for this)
2. No cloud dependency — your security data stays on your server
3. Lightweight — designed for shared hosting, won't slow your site
4. Part of the Forge family — works alongside BackForge and DripForge

### Screenshot Strategy
1. Dashboard with security stats (dark UI)
2. Firewall log showing blocked attacks
3. Login security settings
4. IP blocklist management
5. File integrity scan results
6. Country blocking map/settings

---

## Cross-Sell Opportunities

### Forge Bundle
Offer a discounted bundle of all Forge Pro plugins:

| Bundle | Includes | Price | Savings |
|--------|----------|-------|---------|
| Forge Starter | BackForge Pro + ShieldForge Pro | $69/yr | Save $19 |
| Forge Business | BackForge Pro + ShieldForge Pro + DripForge Pro | $99/yr | Save $38+ |
| Forge Agency | All Pro plugins, unlimited sites | $249/yr | Save $100+ |

### In-Plugin Cross-Promotion
- ShieldForge dashboard: "Protect your backups too → BackForge"
- BackForge dashboard: "Secure your site → ShieldForge"
- DripForge dashboard: "Protect your subscriber data → ShieldForge"

Keep it subtle — a small card at the bottom of the dashboard, not a nag banner.

---

## Growth Strategy

### Phase 1: Launch (Month 1-3)
- Submit to WordPress.org
- Write 3-5 blog posts on ekewaka.com (WordPress security tips, comparison posts)
- Post in WordPress Facebook groups, Reddit r/wordpress, WP Tavern comments
- Reach out to WordPress security bloggers for reviews

### Phase 2: Content Marketing (Month 3-6)
- "WordPress Security Checklist" lead magnet (delivered via DripForge, naturally)
- Monthly security tips email sequence
- YouTube walkthrough videos
- Guest posts on WordPress blogs

### Phase 3: Partnerships (Month 6-12)
- Affiliate program via LicenseForge (20% commission)
- Hosting partner integrations (pre-installed on partner hosting)
- WordPress agency partnerships (bulk licensing)

### Phase 4: Expansion (Year 2)
- ShieldForge SaaS dashboard (optional cloud monitoring across multiple sites)
- Threat intelligence feed (aggregate anonymized attack data from opt-in users)
- WordPress multisite network admin
