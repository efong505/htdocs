# Next Level Web Developers — Marketing Funnel Implementation Plan
## Master Overview Document
**Date:** April 2026  
**Site:** https://edwardfong.onthewifi.com/next-level  

---

## Project Summary

This project adds a lead-generation marketing funnel to the Next Level Web Developers website. The funnel captures visitor emails via a free "Web Dev Survival Kit" offer, nurtures leads through a 7-email drip sequence, and drives them to submit a Request for Proposal (RFP).

---

## Funnel Flow

```
Visitor lands on site
        ↓
Sees "Free Web Dev Survival Kit" CTA (homepage, sidebar, pages)
        ↓
Clicks → Signup Page (/survival-kit/)
        ↓
Enters name + email → Submits form
        ↓
Receives Welcome Email + PDF Download Link (Email #1)
        ↓
Drip Sequence (Emails #2–#7 over 17 days)
        ↓
Final CTA → Request for Proposal (/request-for-proposal/)
```

---

## Deliverables

| # | Deliverable | Document | Status |
|---|------------|----------|--------|
| 1 | Email Drip System (2 options) | `01-email-drip-system.md` | ✅ Complete |
| 2 | Signup Page | `02-signup-page.md` | ✅ Complete |
| 3 | Survival Kit Download Page | `03-survival-kit-page.md` | ✅ Complete |
| 4 | Survival Kit Contents | `04-survival-kit-contents.md` | ✅ Complete |
| 5 | 7-Email Drip Sequence | `05-email-drip-sequence.md` | ✅ Complete |
| 6 | RFP Page Audit & Recommendations | `06-rfp-audit.md` | ✅ Complete |

---

## Current Site Context

### Services Offered
- Website design & development
- Content creation
- Marketing (online/offline)
- WordPress care packages
- Combo packages
- Plans & packages (pricing tiers)

### Target Audience
- Small business owners who have websites that aren't generating revenue
- Business owners considering a new website
- Entrepreneurs who need professional web presence

### Key Value Proposition
"Most web developers aren't business owners — we are. We turn your website into your most profitable business asset."

### Active Plugins
- Contact Form 7 (forms)
- Cornerstone + X Theme (page builder)
- ConvertPlug (popups/opt-ins)
- Yoast SEO
- Revolution Slider
- WP Bakery (js_composer — recommend deactivating)

### Existing Pages Relevant to Funnel
- `/request-for-proposal/` — RFP form (Contact Form 7)
- `/plans-packages/` — Pricing
- `/services/` — Service overview
- `/combo-packages/` — Bundle deals
- `/about/` — About page
- `/our-work/` — Portfolio
- `/faq/` — FAQ

---

## Implementation Order

1. Choose email drip system (Option A: Custom Plugin or Option B: MailerLite)
2. Build/configure the drip system
3. Create Survival Kit content (PDF)
4. Create Signup Page
5. Create Download/Thank You Page
6. Set up 7-email drip sequence
7. Update RFP page per audit recommendations
8. Add CTAs across the site (homepage, sidebar, blog posts)
9. Test full funnel end-to-end

---

## Files in This Documentation Set

- `00-master-plan.md` — This file
- `01-email-drip-system.md` — Two options for the drip system with setup instructions
- `02-signup-page.md` — Signup page design and implementation
- `03-survival-kit-page.md` — Download page design and implementation
- `04-survival-kit-contents.md` — Kit items, perceived value, and content
- `05-email-drip-sequence.md` — All 7 email drafts with timing
- `06-rfp-audit.md` — RFP page audit and 2026 recommendations
