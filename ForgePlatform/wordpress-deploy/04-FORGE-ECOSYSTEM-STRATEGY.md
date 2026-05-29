# Forge Ecosystem — WordPress.org Strategy

*How the free WordPress.org plugins serve as the entry point to the broader Forge ecosystem.*

---

## The Funnel

```
WordPress.org Discovery (free plugins)
         │
         ▼
Install & Use (BackForge / ShieldForge / DripForge)
         │
         ▼
See "Forge" branding → Visit ekewaka.com
         │
         ▼
Discover the ecosystem → See Pro upgrades
         │
         ▼
Purchase Pro via LicenseForge on ekewaka.com
```

---

## Brand Touchpoints Within WordPress.org Rules

WordPress.org has strict rules about self-promotion. Here's what's allowed:

### ✅ Allowed
- **Consistent "Forge" branding** in plugin name and admin UI
- **"Part of the Forge Product Family"** — one line in readme description
- **Author URI** pointing to ekewaka.com (where full ecosystem is presented)
- **Plugin URI** pointing to individual product pages on ekewaka.com
- **Pro upgrade page** within each plugin (for THAT plugin's Pro only)
- **Forge-branded admin UI** (dark theme, consistent design language)
- **"Powered by Forge"** footer in plugin admin pages

### ❌ Not Allowed
- Cross-promoting other Forge plugins via admin notices
- Listing other Forge products in the plugin description
- Requiring other Forge plugins as dependencies
- Persistent nag banners for other products
- Redirecting to external sites on activation
- Collecting user data for marketing

---

## Submission Order Strategy

**Recommended order:**

1. **BackForge** (wp-s3-backup) — Submit first
   - Most mature, already has complete readme with external services disclosure
   - Unique value prop (no SDK, direct REST API) differentiates from competitors
   - Backup plugins have high search volume

2. **DripForge** (nl-drip-engine) — Submit second
   - Self-hosted email marketing is a growing niche
   - Zero SaaS fees messaging resonates with WordPress audience
   - Less competition than backup space

3. **ShieldForge** (sf-security) — Submit third
   - Security is the most competitive category
   - Having the other two approved first builds reviewer trust
   - Country blocking (Phase 7) would be a major differentiator when added

---

## Cross-Plugin Brand Recognition

When users install any Forge plugin, they see:
- Dark SaaS-style admin UI (distinctive from typical WordPress admin)
- Consistent color accents per product (teal=BackForge, crimson=ShieldForge, etc.)
- "Forge" in the product name
- Professional design that signals "this is part of something bigger"

This creates organic curiosity → users search "Forge WordPress" or visit ekewaka.com.

---

## ekewaka.com Product Pages (Required Before Submission)

Each plugin's Plugin URI should point to a dedicated page:

- `ekewaka.com/backforge` — BackForge product page
- `ekewaka.com/shieldforge` — ShieldForge product page
- `ekewaka.com/dripforge` — DripForge product page

These pages should include:
- Feature overview
- Screenshots
- "Free on WordPress.org" download button
- Pro features comparison table
- Link to documentation
- Subtle cross-links to other Forge products ("Explore the Forge Ecosystem")

---

## Long-Term WordPress.org Presence

### Reviews & Ratings
- After each plugin has 100+ active installs, reach out to users for reviews
- Respond to all support forum threads promptly (builds trust and visibility)
- Keep "Tested up to" current with each WordPress release

### Support Forum Strategy
- Monitor the WordPress.org support forum for each plugin
- Respond within 24 hours
- Be helpful even for tangential WordPress questions (builds reputation)
- Link to documentation rather than writing long forum replies

### Update Cadence
- Push at least one update per quarter (shows plugin is maintained)
- Update "Tested up to" with each major WordPress release
- Add small features or improvements to keep changelog active

---

## Revenue Path

```
Free Plugin (WordPress.org)
    → Pro Upgrade ($49-99/year per plugin)
        → Sold via LicenseForge on ekewaka.com
            → License validated via REST API
                → Pro plugin extends free via hooks
```

This is the standard WordPress plugin business model (used by Wordfence, UpdraftPlus, etc.) and is fully compliant with WordPress.org guidelines.
