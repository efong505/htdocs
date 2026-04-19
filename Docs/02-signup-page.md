# Signup Page Design & Implementation
## Document 02

---

## Page Details
- **URL:** `/survival-kit/`
- **Title:** Free Web Dev Survival Kit
- **Purpose:** Capture visitor email in exchange for the free Survival Kit PDF

---

## Page Layout

### Hero Section
- **Background:** Dark overlay on professional business/tech image
- **Headline:** "Your Website Should Be Making You Money — Here's How"
- **Subheadline:** "Download our FREE Web Dev Survival Kit and discover the exact strategies we use to turn underperforming websites into revenue machines."
- **Perceived Value Badge:** "Over $497 in value — Yours FREE"

### Signup Form Section
- **Fields:**
  - First Name (required)
  - Email Address (required)
- **Button:** "Send Me The Free Survival Kit" (bright CTA color)
- **Privacy note:** "We respect your privacy. Unsubscribe anytime."

### What's Inside Section (bullet list with icons)
- ✅ Website Revenue Audit Checklist ($97 value)
- ✅ SEO Quick-Start Guide ($127 value)
- ✅ Social Media Content Calendar Template ($67 value)
- ✅ Website Speed Optimization Checklist ($47 value)
- ✅ "Is My Website Working?" Self-Assessment Scorecard ($79 value)
- ✅ Bonus: 15-Minute Strategy Session Voucher ($80 value)

### Social Proof Section
- Testimonial from Nathan Virk (already on homepage)
- "Trusted by small businesses across Hawaii"

### FAQ Mini-Section
- "Is this really free?" — Yes, 100% free.
- "Will I get spammed?" — No. You'll receive 7 helpful emails over 17 days. Unsubscribe anytime.
- "Who is this for?" — Business owners who want their website to generate more revenue.

---

## Implementation

### Using Cornerstone (X Theme Page Builder)
Since the site uses X Theme + Cornerstone, the page should be built using Cornerstone for visual consistency.

1. In WP Admin → Pages → Add New
2. Title: "Free Web Dev Survival Kit"
3. Slug: `survival-kit`
4. Launch Cornerstone editor
5. Build sections as described above
6. For the signup form:
   - **Option A (Custom Plugin):** Use shortcode `[nl_signup_form sequence="survival-kit-drip"]`
   - **Option B (MailerLite):** Paste MailerLite embed code in a Raw Content element

### Form Behavior
- On submit → redirect to `/survival-kit-download/` (the download page)
- Subscriber is added to the drip sequence
- Welcome email fires immediately with the download link as backup

### CTA Placement Across the Site
Add the Survival Kit CTA in these locations:

1. **Homepage** — New section below the hero, above the fold
2. **Blog sidebar** — Widget with mini signup form
3. **End of every blog post** — "Enjoyed this? Get our free Survival Kit"
4. **ConvertPlug popup** — Exit-intent popup on all pages (plugin already installed)
5. **Navigation bar** — "Free Kit" button in the top menu

---

## SEO (Yoast)
- **Meta Title:** Free Web Dev Survival Kit | Next Level Web Developers
- **Meta Description:** Download our free Web Dev Survival Kit — includes a revenue audit checklist, SEO guide, content calendar, and more. Over $497 in value.
- **Focus Keyphrase:** free web dev survival kit
