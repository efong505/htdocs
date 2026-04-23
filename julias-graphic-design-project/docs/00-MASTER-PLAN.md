# Julia's Graphic Design — Site Overhaul & Marketing Funnel Plan
## Master Overview Document
**Date:** July 2026
**Site:** https://juliasgraphicdesign.com
**Local Dev:** http://localhost/julias-graphic-design

---

## Project Summary

This project modernizes the Julia's Graphic Design website from its 2016-era state into a professional, revenue-generating site for 2026. The overhaul covers four areas: site cleanup, content modernization, a lead-generation drip funnel, and WooCommerce store optimization.

Julia's core differentiator: she's a **fine artist first** who translates hand-drawn art into digital graphic design. Most graphic designers can't draw — Julia can. The site needs to showcase this, not bury it under walls of marketing text.

---

## Current State Assessment

### What's Working
- Real artist differentiator (hand-drawn → digital workflow)
- WooCommerce store with 22 products (t-shirts, stickers, prints, mugs)
- Testimonials with real social proof
- Art gallery exists
- Domain is established (juliasgraphicdesign.com)

### What's Broken
- **Theme:** Twenty Seventeen child theme — visually dated for a graphic design business
- **Homepage:** Wall of text, 2016 direct-mail style copy, broken iContact form
- **Services page:** Empty numbered list with no descriptions or portfolio
- **About page:** References 2016 graduation, outdated location
- **Blog:** All posts from 2017-2018, no fresh content
- **Plugins:** ~30 installed, many dead/disabled (CoursePress, e-newsletter, revslider, etc.)
- **Orphan pages:** Course1, Course2, Tuition, Tv, Music, Tech News, Advertise, Mpcart, etc.
- **Lead capture:** iContact embed is likely dead
- **Tagline:** "A Direct Response Centered Graphic Design One Stop?" — awkward, unclear
- **Copy tone:** Most content is Ed's marketing voice, not Julia's artist voice

---

## Target Audience

1. **Small business owners** who need professional graphic design (logos, business cards, marketing materials, packaging)
2. **Art collectors/gift buyers** who want to purchase Julia's original art, prints, and merchandise
3. **Entrepreneurs** launching businesses who need complete brand identity packages

---

## Site Architecture (Proposed)

### Primary Navigation
```
Home | Services | Portfolio | Shop | About | Blog | Contact
```

### Pages to Keep & Update
| Page | Action |
|------|--------|
| Home (ID 654) | Complete rewrite |
| Services (ID 149) | Complete rewrite |
| About Julia & Her Art (ID 150) | Major update for 2026 |
| Art Gallery (ID 332) | Keep, rename to "Portfolio" |
| Testimonials (ID 13) | Update, add to homepage |
| FAQ (ID 9) | Rewrite for 2026 |
| Contact Us (ID 19) | Rewrite |
| Shop (ID 279) | Keep (WooCommerce) |
| Blog (ID 4368) | Keep |

### Pages to Delete/Unpublish
- Course1, Course2, Tuition (CoursePress remnants)
- Tv, Music, Tech News, Advertise (unrelated content)
- Mpcart, Mpcheckout, Mporderstatus, Mpproducts, Mpstore (MarketPress remnants)
- Service Business Cards, Contact2 (duplicates)
- Activity, Members, Groups, Membership List (BuddyPress remnants)
- Signup, Registration, Protected Content, Thank-You Page (plugin remnants)
- Account, My Account (duplicate WooCommerce)
- Listings, My Listings (directory plugin remnants)
- Store (duplicate of Shop)
- Home (ID 148) — old homepage, replaced by ID 654
- Sample Visual Site Map
- Cornerstone Draft
- News (ID 23) — replaced by Blog

### New Pages to Create
| Page | Purpose |
|------|---------|
| /free-guide/ | Lead magnet signup (Drip Engine) |
| /free-guide-download/ | Thank you + PDF download |

---

## Deliverables

| # | Deliverable | Document | Status |
|---|------------|----------|--------|
| 1 | Site Audit & Cleanup Plan | `01-SITE-CLEANUP.md` | ✅ Complete |
| 2 | Homepage Rewrite | `02-HOMEPAGE-REWRITE.md` | ✅ Complete |
| 3 | Services Page Rewrite | `03-SERVICES-REWRITE.md` | ✅ Complete |
| 4 | About Page Update | `04-ABOUT-UPDATE.md` | ✅ Complete |
| 5 | Lead Magnet & Signup Page | `05-LEAD-MAGNET.md` | ✅ Complete |
| 6 | 7-Email Drip Sequence | `06-DRIP-SEQUENCE.md` | ✅ Complete |
| 7 | Blog Content Plan | `07-BLOG-CONTENT-PLAN.md` | ✅ Complete |

---

## Implementation Order

### Phase 1: Clean Up (Do First)
1. Delete/unpublish orphan pages
2. Deactivate and remove dead plugins
3. Update tagline and site description
4. Fix wp-config.php for local/live URL handling

### Phase 2: Content Rewrite
5. Rewrite Homepage
6. Rewrite Services page
7. Update About page
8. Rewrite Contact page
9. Update FAQ page

### Phase 3: Lead Generation Funnel
10. Install NL Drip Engine plugin
11. Create lead magnet PDF content
12. Create signup page (/free-guide/)
13. Create download page (/free-guide-download/)
14. Configure 7-email drip sequence
15. Add CTAs across site (homepage, sidebar, blog posts, shop)

### Phase 4: Store & Blog
16. Feature products on homepage
17. Organize shop collections
18. Write 3 new blog posts
19. Update old blog posts with current info

---

## Key Decisions Needed

1. **Theme:** Stay with Twenty Seventeen child (with heavy CSS customization) or switch to a modern theme (Astra, Kadence, GeneratePress)? **Recommendation:** Switch to Kadence — free, modern, fast, great for visual portfolios
2. **Etsy integration:** Is the Etsy shop still active? If so, link to it. If not, remove the plugin
3. **Phone numbers:** Are the 520 numbers still active? (Tucson area code but Julia is in Albuquerque now)
4. **WooCommerce payments:** Is Stripe still the payment processor? Is it configured?
5. **Lead magnet topic:** Proposed: "The Business Owner's Guide to Graphics That Actually Sell" — confirm or suggest alternative

---

## Files in This Documentation Set

- `00-MASTER-PLAN.md` — This file
- `01-SITE-CLEANUP.md` — Plugin audit, page cleanup, technical fixes
- `02-HOMEPAGE-REWRITE.md` — New homepage copy and structure
- `03-SERVICES-REWRITE.md` — Services page with descriptions and CTAs
- `04-ABOUT-UPDATE.md` — Updated About page for 2026
- `05-LEAD-MAGNET.md` — Lead magnet concept, signup page, download page
- `06-DRIP-SEQUENCE.md` — All 7 email drafts with timing
- `07-BLOG-CONTENT-PLAN.md` — New blog post topics and outlines
