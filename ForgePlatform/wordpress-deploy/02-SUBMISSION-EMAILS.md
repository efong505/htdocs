# Submission Emails & Communication Templates

*Copy for WordPress.org plugin submission notes and follow-up communications.*

---

## Initial Submission Notes

These go in the "Notes to the reviewer" field when submitting the plugin ZIP.

---

### BackForge (WP S3 Backup)

**Submission Notes:**

> WP S3 Backup creates full WordPress site backups (database + files) and uploads them directly to the user's own Amazon S3 bucket using the S3 REST API with AWS Signature V4 authentication.
>
> Key technical notes for review:
>
> - No AWS SDK bundled — all S3 communication uses wp_remote_request() with custom SigV4 signing
> - AWS credentials are encrypted at rest using AES-256-CBC (key derived from AUTH_KEY + AUTH_SALT)
> - External service: Amazon S3 only, disclosed in readme.txt with links to AWS privacy policy and ToS
> - No tracking, analytics, or data collection of any kind
> - All AJAX handlers verify nonces and manage_options capability
> - Plugin is fully functional as free — Pro version extends via hooks only
>
> I'm an existing WordPress.org plugin author (eds-font-awesome, eds-social-share, eds-ultimate-list-builder).
>
> Thank you for your time reviewing this plugin.

---

### ShieldForge (SF Security)

**Submission Notes:**

> ShieldForge is a self-hosted WordPress security plugin providing login hardening, brute force protection, IP blocklist management, rate limiting, and a web application firewall (WAF) — all running locally with zero cloud dependency.
>
> Key technical notes for review:
>
> - No external service calls — everything runs on the local WordPress installation
> - No tracking, analytics, or phoning home
> - Security checks run on the 'init' hook with appropriate priority ordering
> - All admin operations require manage_options capability
> - All AJAX handlers verify nonces via check_ajax_referer()
> - Rate limiting uses WordPress transients (no external dependencies)
> - WAF rules use regex pattern matching against request data
> - IP detection supports Cloudflare (CF-Connecting-IP) and proxy (X-Forwarded-For) headers
> - Database tables created via dbDelta() on activation, dropped on uninstall
> - Admin users are always bypassed by security checks to prevent self-lockout
>
> I'm an existing WordPress.org plugin author (eds-font-awesome, eds-social-share, eds-ultimate-list-builder).
>
> Thank you for your time reviewing this plugin.

---

### DripForge (NL Drip Engine)

**Submission Notes:**

> DripForge is a self-hosted email drip marketing automation plugin for WordPress. It lets site owners capture leads via shortcode-based signup forms, build timed email sequences, and automatically deliver them via wp-cron — all without any third-party SaaS service.
>
> Key technical notes for review:
>
> - No external service calls — email sending uses wp_mail() or user-configured SMTP
> - No tracking pixels served to external services — open/click tracking is self-hosted
> - Honeypot spam protection on public forms (no CAPTCHA dependency)
> - CAN-SPAM compliant with unsubscribe handling
> - All admin operations require manage_options capability
> - Public AJAX handler (subscribe) uses nonce verification
> - Database tables created via dbDelta() on activation
> - wp-cron processes emails every 5 minutes in batches of 50
>
> I'm an existing WordPress.org plugin author (eds-font-awesome, eds-social-share, eds-ultimate-list-builder).
>
> Thank you for your time reviewing this plugin.

---

## Follow-Up Email Templates

### If Asked to Fix Issues

**Subject:** Re: [Plugin Name] - Review Feedback

> Hi [Reviewer Name],
>
> Thank you for the review feedback. I've addressed all the issues you identified:
>
> 1. [Issue]: [How you fixed it]
> 2. [Issue]: [How you fixed it]
>
> I've attached the updated plugin ZIP with these corrections.
>
> Please let me know if there's anything else that needs attention.
>
> Best regards,
> Edward Fong
> https://ekewaka.com

---

### If Asking About Review Status (After 14+ Days)

**Subject:** Plugin Review Status — [Plugin Slug]

> Hi WordPress Plugin Review Team,
>
> I submitted [Plugin Name] (slug: [plugin-slug]) for review on [date] and wanted to check on its status. I understand the team handles a high volume of submissions and I appreciate your work.
>
> If there are any issues with my submission that I can proactively address, I'm happy to make corrections.
>
> Thank you for your time.
>
> Best regards,
> Edward Fong
> WordPress.org username: ekewaka

---

## Forge Ecosystem Positioning in Plugin Descriptions

Each plugin's readme.txt should subtly reference the Forge ecosystem without being promotional (WordPress.org doesn't allow advertising other products in descriptions). The approach:

- Use "Part of the Forge Product Family" as a single line in the description
- Link to ekewaka.com (Author URI) where the full ecosystem is presented
- Do NOT list other Forge products in the plugin description
- Do NOT include upsell links to other Forge products within the plugin UI
- The "Forge" branding in the UI itself (BackForge, ShieldForge, DripForge) naturally creates brand recognition

### What's Allowed
- Mentioning "Part of the Forge Product Family" in readme description
- Linking to your author site where other products are listed
- Having consistent Forge branding in the plugin's own admin UI
- A "Pro" upgrade page within the plugin (for that plugin's own Pro version)

### What Will Get Rejected
- Advertising other plugins in the description
- Cross-promoting other plugins via admin notices
- Requiring other Forge plugins to function
- Linking to external purchase pages from within the free plugin (except for that plugin's own Pro)
