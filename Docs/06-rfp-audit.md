# RFP Page Audit & 2026 Recommendations
## Document 06

---

## Current State

**URL:** https://edwardfong.onthewifi.com/next-level/request-for-proposal/  
**Current Content:**
- One stock photo (Unsplash image from 2018)
- One Contact Form 7 form (ID: 2779, title: "Proposal Request Form")
- No headline, no copy, no context

### Issues
1. **No headline or value proposition** — Visitor arrives and sees a photo and a form with no context
2. **No explanation of what happens after submission** — Uncertainty kills conversions
3. **Stock photo is generic** — Doesn't build trust or relate to the service
4. **No social proof** — No testimonials, no portfolio samples, no trust signals
5. **No urgency or incentive** — No reason to fill it out NOW vs later (later = never)
6. **Form title "Proposal Request Form" is generic** — Doesn't feel personalized
7. **Unknown form fields** — Need to audit what Contact Form 7 is actually asking for

---

## 2026 Best Practices for RFP/Lead Capture Pages

### 1. Clear Value-Driven Headline
Don't say "Request for Proposal." Say what they GET.
- ❌ "Request for Proposal"
- ✅ "Tell Us About Your Project — Get a Free Custom Proposal Within 48 Hours"

### 2. Minimal Form Fields
Every additional field reduces conversions by ~4%. Only ask for what you absolutely need for the first conversation.

**Recommended fields:**
- Name (required)
- Email (required)
- Phone (optional)
- Website URL (optional — so you can review their current site)
- "Tell us about your project" (textarea, required)
- Budget range (dropdown: Under $1K, $1K-$3K, $3K-$5K, $5K-$10K, $10K+)
- How did you hear about us? (dropdown, optional)

**Remove if currently present:**
- Company name (you can get this later)
- Address (not needed at this stage)
- Multiple dropdowns for service type (keep it simple)

### 3. Set Expectations
Tell them exactly what happens after they submit:
1. You'll review their submission within 24 hours
2. You'll look at their current website (if they provided a URL)
3. You'll send a custom proposal via email within 48 hours
4. You'll schedule a free call to walk through it

### 4. Social Proof on the Page
- 1-2 short testimonials
- "Trusted by X+ businesses" (if applicable)
- Portfolio thumbnails or client logos
- Star rating or review count

### 5. Remove Friction
- No CAPTCHA if possible (use honeypot spam protection instead)
- Don't require phone number
- Make the submit button action-oriented: "Get My Free Proposal" not "Submit"

### 6. Thank You Page
After submission, redirect to a dedicated thank-you page that:
- Confirms receipt
- Restates the timeline ("You'll hear from us within 48 hours")
- Offers the Survival Kit if they haven't downloaded it yet
- Links to portfolio/case studies to keep them engaged while they wait

---

## Recommended New Page Layout

### Section 1: Hero
- **Headline:** "Let's Build Something That Makes You Money"
- **Subheadline:** "Tell us about your project and get a free custom proposal within 48 hours. No obligation, no hard sell."
- **Background:** Professional but warm — consider using a real photo of Ed or the workspace

### Section 2: What You'll Get
Three columns with icons:
- 🔍 **Website Review** — "We'll analyze your current site and identify quick wins"
- 📋 **Custom Proposal** — "A detailed plan tailored to your goals and budget"
- 📞 **Strategy Call** — "A free call to walk through our recommendations"

### Section 3: The Form
- Clean, well-spaced form with the recommended fields above
- Button: "Get My Free Proposal"
- Below button: "We typically respond within 24 hours"

### Section 4: Social Proof
- Nathan Virk testimonial
- Any other client testimonials available
- "Serving small businesses since 2017"

### Section 5: FAQ (collapsible)
- "Is this really free?" — Yes, completely.
- "How detailed is the proposal?" — It includes specific recommendations, timeline, and pricing.
- "Am I obligated to hire you?" — Absolutely not.
- "How soon will I hear back?" — Within 48 hours, usually sooner.

---

## Contact Form 7 — Recommended Form Code

```
<div class="rfp-form">

<label>Your Name *</label>
[text* your-name placeholder "Full name"]

<label>Email Address *</label>
[email* your-email placeholder "you@example.com"]

<label>Phone Number</label>
[tel your-phone placeholder "(555) 555-5555"]

<label>Your Current Website URL</label>
[url your-website placeholder "https://yoursite.com"]

<label>Tell Us About Your Project *</label>
[textarea* your-project placeholder "What are you looking to accomplish? Any specific goals, features, or timeline?"]

<label>Budget Range</label>
[select budget include_blank "Under $1,000" "$1,000 - $3,000" "$3,000 - $5,000" "$5,000 - $10,000" "$10,000+"]

<label>How Did You Hear About Us?</label>
[select referral include_blank "Google Search" "Social Media" "Referral" "Email" "Survival Kit" "Other"]

[submit class:rfp-submit "Get My Free Proposal"]

</div>
```

### Mail Template
```
From: [your-name] <[your-email]>
Subject: New Proposal Request from [your-name]

Name: [your-name]
Email: [your-email]
Phone: [your-phone]
Website: [your-website]
Budget: [budget]
Referral: [referral]

Project Details:
[your-project]
```

---

## Thank You Page

**URL:** `/proposal-thank-you/`

**Content:**
- Headline: "We Got Your Proposal Request!"
- Body: "Thanks, {name}. We'll review your project details and send you a custom proposal within 48 hours. If you included your website URL, we'll take a look at that too."
- What to expect:
  1. ✅ Proposal request received
  2. ⏳ Website review & proposal creation (24-48 hours)
  3. 📧 Custom proposal delivered to your inbox
  4. 📞 Optional strategy call to discuss
- CTA: "While you wait, check out our recent work → /our-work/"
- Secondary CTA: "Haven't grabbed the free Survival Kit yet? → /survival-kit/"

---

## Implementation Steps

1. Update Contact Form 7 (ID: 2779) with the new form code above
2. Rebuild the RFP page in Cornerstone with the new layout
3. Create the `/proposal-thank-you/` page
4. Configure Contact Form 7 to redirect to thank-you page after submission
5. Update Yoast SEO:
   - Meta Title: "Get a Free Website Proposal | Next Level Web Developers"
   - Meta Description: "Tell us about your project and receive a custom website proposal within 48 hours. Free, no obligation."
6. Test form submission end-to-end
7. Add "Request a Proposal" CTA to navigation menu
