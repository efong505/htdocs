# Site Cleanup Plan
## Document 01

---

## Plugin Audit

### Currently Active (8 plugins)
| Plugin | Verdict | Notes |
|--------|---------|-------|
| Akismet | ✅ Keep | Spam protection — essential |
| Child Theme Configurator | ⚠️ Remove after theme switch | Only needed if staying on child theme |
| Contact Form 7 | ✅ Keep | Powers contact page |
| Etsy Shop | ❓ Review | Is the Etsy shop still active? Remove if not |
| WPBakery (js_composer) | ⚠️ Review | Homepage uses VC shortcodes — need to migrate content before removing |
| Responsive Lightbox | ✅ Keep | Used by gallery |
| Wordfence | ✅ Keep | Security — essential |
| Yoast SEO | ✅ Keep | SEO — essential |

### Installed but Inactive (disabled with " off" suffix or not in active list)
| Plugin | Verdict | Notes |
|--------|---------|-------|
| All-in-One WP Migration | 🗑️ Remove | Backup tool, not needed with BackForge |
| CoursePress | 🗑️ Remove | Created orphan pages, not used |
| e-newsletter | 🗑️ Remove | Replaced by NL Drip Engine |
| Facebook for WooCommerce | ❓ Review | Is FB shop integration active? |
| Jetpack | ⚠️ Review | Heavy plugin — what features are being used? |
| LiteSpeed Cache | ⚠️ Review | Only useful if server runs LiteSpeed (XAMPP uses Apache) |
| Mailchimp for WooCommerce | 🗑️ Remove | Conflicts with Drip Engine approach |
| P3 Profiler | 🗑️ Remove | Outdated profiling tool |
| Photo Gallery | ⚠️ Review | Is this powering the Art Gallery page? |
| Reports | 🗑️ Remove | WPMU Dev plugin, likely unused |
| RevSlider | 🗑️ Remove | Heavy, outdated slider plugin |
| Social Marketing | 🗑️ Remove | WPMU Dev plugin, outdated |
| Ultimate Branding | 🗑️ Remove | WPMU Dev plugin, likely unused |
| Upfront Builder | 🗑️ Remove | WPMU Dev theme builder, not in use |
| VaultPress | 🗑️ Remove | Replaced by BackForge |
| WooCommerce | ✅ Keep | Powers the shop |
| WooCommerce Gateway Stripe | ✅ Keep | Payment processing |
| WooCommerce Legacy REST API | ⚠️ Review | May be needed by FB/Mailchimp integrations |
| WooCommerce Services | ✅ Keep | Shipping labels, etc. |
| Woo Update Manager | ✅ Keep | WooCommerce updates |
| WP Hummingbird | ⚠️ Review | Performance plugin — keep if actively configured |
| WP Smush Pro | ⚠️ Review | Image optimization — useful for art site |
| X Video Lock | 🗑️ Remove | Not relevant |
| Cornerstone (disabled) | 🗑️ Remove | Page builder from X Theme, not active |

### Plugins to Add
| Plugin | Purpose |
|--------|---------|
| NL Drip Engine | Email drip sequences for lead funnel |
| Kadence Theme (if switching) | Modern theme with portfolio capabilities |

---

## Pages to Delete/Unpublish

### CoursePress Remnants
- Course1 (ID 4367)
- Course2 (ID 4369)
- Tuition (ID 4366)

### MarketPress Remnants
- Mpcart (ID 4372)
- Mpcheckout (ID 4374)
- Mporderstatus (ID 4375)
- Mpproducts (ID 4371)
- Mpstore (ID 4373)

### BuddyPress/Membership Remnants
- Activity (ID 212)
- Members (ID 213)
- Groups (ID 230)
- Membership List (ID 448)
- Signup (ID 509)
- Registration (ID 450)
- Protected Content (ID 449)
- Thank-You Page (ID 451)

### Unrelated Content Pages
- Tv (ID 4395)
- Music (ID 4397)
- Tech News (ID 4396)
- Advertise (ID 4398)

### Duplicates
- Home (ID 148) — old homepage, replaced by ID 654
- Contact2 (ID 4365)
- Service Business Cards (ID 4370)
- Store (ID 544) — duplicate of Shop
- Account (ID 453) — duplicate of My Account
- News (ID 23) — replaced by Blog
- Sample Visual Site Map (ID 4092)
- Cornerstone Draft (ID 3900)

### WooCommerce (Keep)
- Cart (ID 280)
- Checkout (ID 281)
- My Account (ID 282)
- Shop (ID 279)

---

## Technical Fixes

### 1. Site Tagline
**Current:** "A Direct Response Centered Graphic Design One Stop?"
**Proposed:** "Fine Art Meets Graphic Design — Custom Graphics, Illustration & Art Prints"

### 2. Site Title
**Current:** "Julia's Store"
**Proposed:** "Julia's Graphic Design"

### 3. Broken iContact Form
The homepage contains an embedded iContact script:
```html
<script src="//app.icontact.com/icp/core/mycontacts/signup/designer/form/automatic?id=5&cid=350539&lid=124940"></script>
```
This needs to be removed and replaced with the NL Drip Engine signup form.

### 4. WPBakery Shortcode Migration
The active homepage (ID 654) uses WPBakery shortcodes (`[vc_row]`, `[vc_column]`, etc.). Before removing WPBakery:
1. Extract the actual text content from the shortcodes
2. Recreate the page using the block editor (Gutenberg) or new theme's page builder
3. Then deactivate WPBakery

### 5. Encoding Issues
Multiple pages have broken characters (ÿ, ?, ?) from encoding problems. All rewritten content will use clean UTF-8.

### 6. Email Addresses
Review and update:
- juliabfong@gmail.com — still active?
- juliasgraphicdesign@gmail.com — still active?
- hawaiinintucson@gmail.com — typo? Should be hawaiianintucson?

### 7. Phone Numbers
- (520) 392-5924 (Julia) — still active? 520 is Tucson area code
- (520) 392-5923 (Ed) — still active?
- Update to current numbers if changed

---

## Cleanup SQL (Reference)

To unpublish orphan pages in bulk:
```sql
UPDATE ej_posts
SET post_status = 'draft'
WHERE ID IN (
    4367, 4369, 4366,           -- CoursePress
    4372, 4374, 4375, 4371, 4373, -- MarketPress
    212, 213, 230, 448, 509, 450, 449, 451, -- BuddyPress/Membership
    4395, 4397, 4396, 4398,     -- Unrelated
    148, 4365, 4370, 544, 453, 23, 4092, 3900 -- Duplicates
)
AND post_status = 'publish';
```

Note: Draft instead of delete so content can be reviewed before permanent removal.
