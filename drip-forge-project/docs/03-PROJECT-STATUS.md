# DripForge — Project Status

*Last updated: Based on current codebase analysis*

---

## Brand

| | |
|---|---|
| **Product Family** | Forge (ForgeWP) |
| **Plugin Name** | DripForge |
| **WordPress.org Title** | DripForge – Email Drip Sequences for WordPress |
| **Slug (target)** | `dripforge` |
| **Current working slug** | `nl-drip-engine` |
| **Current prefix** | `nlde_` / `NLDE_` |
| **Target prefix** | `df_` / `DF_` (or `dripforge_` / `DripForge_`) |

---

## Project Location

```
Current:  c:\xampp\htdocs\next-level\wp-content\plugins\nl-drip-engine\
Target:   c:\xampp\htdocs\drip-forge-project\plugin\dripforge\
Docs:     c:\xampp\htdocs\drip-forge-project\docs\
```

---

## Features (All Implemented ✅)

### Subscriber Management
- Create, re-subscribe, search, filter, paginate, delete, CSV export
- IP detection with Cloudflare/proxy support
- Statuses: active, unsubscribed, bounced

### Drip Sequences
- Create/edit/delete sequences (draft, active, paused)
- Add/edit/delete emails per sequence (subject, body, delay, position)
- Auto-enrollment via signup form shortcode
- Position-based advancement after successful send
- Completion tracking

### Email Sending
- `wp_mail()` with optional SMTP override
- Configurable From Name / From Email
- HTML email template (dark header, white body, gray footer)
- Merge tags: `{first_name}`, `{last_name}`, `{email}`, `{site_name}`, `{site_url}`, `{unsubscribe_link}`, `{rfp_link}`, `{download_link}`
- Rate limiting: 50/batch, 1s pause per 10 sends
- 5-minute cron interval

### Analytics
- Open tracking (1×1 pixel)
- Click tracking (redirect with hash)
- Per-email stats: sent, opened, clicked, open rate
- Overview dashboard: subscribers, sent, open rate, click rate

### Signup Forms
- Shortcode: `[nl_signup_form]` with sequence, button_text, redirect, show_name, placeholders, class
- AJAX submission
- Honeypot spam protection
- Inline success/error messages

### Compliance
- CAN-SPAM unsubscribe with hash verification
- Unsubscribe confirmation page

### Admin UI
- Dashboard with stat cards + quick start + merge tag reference
- Subscriber list with search/filter/pagination/delete/export
- Sequence list with status badges
- Sequence editor with performance table + email builder
- Email preview (full HTML in new tab)
- Settings: sender info + SMTP config + provider setup guides
- Plugin info modal with custom banner/icon on plugins page

---

## Database Tables

| Table | Purpose |
|---|---|
| `nlde_subscribers` | Email, name, status, IP, timestamps |
| `nlde_sequences` | Name, slug, description, status |
| `nlde_sequence_emails` | Subject, body, delay, position per sequence |
| `nlde_subscriber_sequences` | Enrollment tracking (subscriber × sequence) |
| `nlde_send_log` | Send record with tracking hash, open/click timestamps |

---

## Known Issues & Technical Notes

1. **SMTP password stored in plaintext** — `nlde_smtp_pass` is stored as plain text in `wp_options`. Should be encrypted with AES-256-CBC using WordPress salts (same pattern as BackForge/LicenseForge).

2. **No test email button** — Admin must create a sequence and enroll themselves to test. Should add a "Send Test Email" button on the settings page.

3. **Hardcoded merge tags** — `{rfp_link}` and `{download_link}` are specific to the Next Level Web Developers site. These should be made configurable or replaced with a generic custom merge tag system.

4. **No subscriber import** — CSV export exists but no CSV import. Common need when migrating from Mailchimp/ConvertKit.

5. **No double opt-in** — Single opt-in only. Double opt-in (confirmation email) is best practice for GDPR and deliverability.

6. **Click tracking not auto-injected** — Links in email body are not automatically wrapped with click tracking. The admin must manually use the tracking URL format. Should auto-wrap `<a href>` tags.

7. **No bounce handling** — Bounced status exists in the schema but nothing sets it. Would need webhook integration with the SMTP provider (SES SNS notifications, SendGrid Event Webhook).

8. **Admin UI is light theme** — Doesn't match the Forge dark SaaS brand. Needs the dark navy + teal accent treatment like BackForge.

9. **Text domain mismatch** — Currently `nl-drip-engine`, needs to become `dripforge` for Forge brand.

10. **No uninstall.php** — Plugin doesn't clean up tables or options on uninstall.

---

## Remaining Tasks

### Before Forge Rebrand
- [ ] Copy plugin to `drip-forge-project/plugin/dripforge/`
- [ ] Rename all `nlde_` prefixes to `df_` (or `dripforge_`)
- [ ] Rename all `NLDE_` class prefixes
- [ ] Update text domain to `dripforge`
- [ ] Update plugin headers (name, URI, author)
- [ ] Update table names (migration from old tables if needed)
- [ ] Update shortcode from `[nl_signup_form]` to `[dripforge_form]`

### Security Improvements
- [ ] Encrypt SMTP credentials (AES-256-CBC with WordPress salts)
- [ ] Add uninstall.php (drop tables, delete options)
- [ ] Mask SMTP password on settings page after save

### Feature Improvements
- [ ] Send test email button on settings page
- [ ] Make custom merge tags configurable (replace hardcoded rfp_link/download_link)
- [ ] Auto-wrap links with click tracking
- [ ] CSV subscriber import
- [ ] Double opt-in option

### UI/UX
- [ ] Dark SaaS admin UI (Forge brand — teal accent)
- [ ] Match BackForge/LicenseForge design system
- [ ] Responsive improvements

### WordPress.org Submission
- [ ] readme.txt in WordPress.org format
- [ ] External service disclosure (SMTP providers)
- [ ] Screenshot assets
- [ ] Banner and icon in Forge brand style
- [ ] Security audit
- [ ] Test on PHP 7.4–8.3 and WordPress 6.0–latest

---

## Forge Product Family Status

| Product | Status | Location |
|---|---|---|
| **BackForge** (Free) | ✅ Complete | `wp-s3-backup-project/plugin/wp-s3-backup/` |
| **BackForge Pro** | ✅ Complete | `wp-s3-backup-project/plugin/wp-s3-backup-pro/` |
| **LicenseForge** | ✅ Complete | `wp-license-platform/plugin/wp-license-platform/` |
| **DripForge** | ✅ Core complete, rebrand pending | `next-level/wp-content/plugins/nl-drip-engine/` |
