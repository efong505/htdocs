# DripForge — Project Plan

## Brand

| | |
|---|---|
| **Product Family** | Forge (ForgeWP) |
| **Plugin Name** | DripForge |
| **WordPress.org Title** | DripForge – Email Drip Sequences for WordPress |
| **Slug (target)** | dripforge |
| **Current working slug** | nl-drip-engine |
| **Tagline** | "Your list. Your emails. Your server." |

---

## Overview

DripForge is a self-hosted WordPress plugin for email drip marketing automation. It lets site owners capture leads via shortcode-based signup forms, build timed email sequences, and nurture subscribers — all from the WordPress dashboard with zero SaaS fees and no subscriber limits.

### What It Does
- Captures email subscribers via embeddable signup forms (shortcode)
- Builds multi-step drip email sequences with configurable delays
- Sends timed emails automatically via wp-cron (every 5 minutes)
- Tracks opens and clicks per email via tracking pixel and redirect
- Manages subscribers with search, filter, status, and CSV export
- Supports merge tags for personalized emails
- Handles CAN-SPAM compliant unsubscribe via hashed links
- Integrates with any SMTP provider (Amazon SES, SendGrid, Brevo, Gmail)
- Provides per-sequence and overview analytics (sent, opened, clicked, rates)

### What It Does NOT Do
- Does not send via third-party API (uses wp_mail + optional SMTP)
- Does not require external SaaS accounts for core functionality
- Does not impose subscriber limits
- Does not track users across sites or collect analytics for the plugin author

---

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    WordPress Site                        │
│                                                         │
│  ┌─────────────────────────────────────────────────┐   │
│  │              DripForge Plugin                    │   │
│  │                                                   │   │
│  │  ┌──────────┐  ┌──────────┐  ┌───────────────┐  │   │
│  │  │ Signup   │  │ Drip     │  │ Email Sender  │  │   │
│  │  │ Form     │  │ Sequence │  │ (wp_mail +    │  │   │
│  │  │ (public) │  │ Engine   │  │  SMTP config) │  │   │
│  │  └────┬─────┘  └────┬─────┘  └───────┬───────┘  │   │
│  │       │              │                │           │   │
│  │  ┌────▼─────┐  ┌────▼─────┐  ┌───────▼───────┐  │   │
│  │  │Subscriber│  │ WP-Cron  │  │  Analytics    │  │   │
│  │  │ Manager  │  │ (5 min)  │  │  (open/click) │  │   │
│  │  └──────────┘  └──────────┘  └───────────────┘  │   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
└─────────────────────────────────────────────────────────┘
                          │ SMTP (TLS)
                          ▼
              ┌───────────────────────┐
              │  SMTP Provider        │
              │  (SES / SendGrid /    │
              │   Brevo / Gmail)      │
              └───────────────────────┘
```

---

## Database Schema

### 5 Custom Tables

```sql
nlde_subscribers          — email, name, status, IP, timestamps
nlde_sequences            — name, slug, description, status
nlde_sequence_emails      — subject, body, delay_days, position per sequence
nlde_subscriber_sequences — enrollment tracking (subscriber × sequence)
nlde_send_log             — per-send record with tracking hash, open/click timestamps
```

### Key Relationships
```
subscriber ──enrolls in──▶ sequence (via subscriber_sequences)
sequence ──contains──▶ sequence_emails (ordered by position)
subscriber + sequence_email ──produces──▶ send_log entry
```

---

## Core Classes

| Class | File | Responsibility |
|---|---|---|
| `NL_Drip_Engine` | `nl-drip-engine.php` | Singleton entry point, activation, table creation, defaults |
| `NLDE_Subscriber` | `class-subscriber.php` | CRUD, search, filter, CSV export, unsubscribe, re-subscribe |
| `NLDE_Drip_Sequence` | `class-drip-sequence.php` | Sequence + email CRUD, enrollment, pending send query, advancement |
| `NLDE_Email_Sender` | `class-email-sender.php` | wp_mail wrapper, merge tag replacement, HTML template, SMTP config |
| `NLDE_Analytics` | `class-analytics.php` | Open pixel, click redirect, unsubscribe handler, stats queries |
| `NLDE_Cron` | `class-cron.php` | 5-minute schedule, processes pending sends with rate limiting |
| `NLDE_Admin_Menu` | `class-admin-menu.php` | Admin pages, form handlers, email preview |
| `NLDE_Signup_Form` | `class-signup-form.php` | `[nl_signup_form]` shortcode, AJAX subscribe handler, honeypot |

---

## File Structure

```
nl-drip-engine/
├── nl-drip-engine.php              # Entry point, singleton, activation
├── readme.txt
├── includes/
│   ├── class-subscriber.php        # Subscriber CRUD + export
│   ├── class-drip-sequence.php     # Sequences + emails + enrollment
│   ├── class-email-sender.php      # wp_mail, merge tags, SMTP, HTML template
│   ├── class-analytics.php         # Open/click tracking, unsubscribe
│   └── class-cron.php              # 5-min cron, pending send processor
├── admin/
│   ├── class-admin-menu.php        # Admin pages + action handlers
│   ├── assets/
│   │   └── nlde-admin.css          # Admin styles
│   └── views/
│       ├── dashboard.php           # Stats + quick start guide
│       ├── subscribers.php         # Subscriber list + search + export
│       ├── sequences.php           # Sequence list + create form
│       ├── sequence-edit.php       # Sequence settings + email builder
│       └── settings.php            # From name/email, SMTP config
├── public/
│   ├── class-signup-form.php       # Shortcode + AJAX handler
│   └── assets/
│       ├── nlde-public.css         # Signup form styles
│       └── nlde-public.js          # AJAX form submission
├── templates/
│   └── email/                      # (reserved for future templates)
└── assets/
    ├── icon-256x256.png
    └── banner-772x250.png
```

---

## Features (All Implemented ✅)

### Subscriber Management
- Create subscriber with email, first name, last name, IP
- Re-subscribe previously unsubscribed users
- Search by email or name
- Filter by status (active, unsubscribed, bounced)
- Paginated list (20 per page)
- Delete subscriber (cascades to enrollments + send log)
- CSV export of all subscribers
- IP detection with Cloudflare/proxy support

### Drip Sequences
- Create/edit/delete sequences with name, slug, description, status
- Sequence statuses: draft, active, paused
- Add/edit/delete emails within a sequence
- Each email has: subject, body (HTML), delay in days, position
- Auto-enrollment on signup via shortcode `sequence` attribute
- Position-based advancement after each successful send
- Completion status when all emails sent

### Email Sending
- Sends via `wp_mail()` (default) or configurable SMTP
- SMTP support: host, port, username, password, TLS/SSL
- Configurable From Name and From Email
- HTML email template with header, body, footer
- Merge tags: `{first_name}`, `{last_name}`, `{email}`, `{site_name}`, `{site_url}`, `{unsubscribe_link}`, `{rfp_link}`, `{download_link}`
- Rate limiting: 1-second pause every 10 emails
- Batch limit: 50 emails per cron run

### Analytics & Tracking
- Open tracking via 1×1 transparent GIF pixel
- Click tracking via redirect with hash
- Per-email stats: sent, opened, clicked, open rate
- Overview dashboard: total subscribers, active, sent, opened, clicked
- Per-sequence performance table

### Signup Forms
- Shortcode: `[nl_signup_form sequence="slug" button_text="Subscribe" redirect="/thank-you/" show_name="yes"]`
- AJAX submission (no page reload)
- Honeypot spam protection
- Customizable: button text, redirect URL, show/hide name field, placeholder text, CSS class
- Success/error messages inline
- Privacy notice text

### Compliance
- CAN-SPAM unsubscribe link in every email (`{unsubscribe_link}`)
- Hash-verified unsubscribe (prevents abuse)
- Unsubscribe confirmation page
- No tracking without user consent (tracking is server-side, no cookies)

### Admin UI
- Dashboard with stat cards (subscribers, sent, open rate, click rate)
- Quick start guide on dashboard
- Merge tag reference on dashboard
- Subscriber list with search, filter, pagination, delete, CSV export
- Sequence list with status badges, slug display, edit/delete
- Sequence editor with settings, performance table, email list, add/edit form
- Email preview in new tab (full HTML render with merge tags replaced)
- Settings page with SMTP quick setup guides (SES, SendGrid, Brevo)

---

## Implementation Status

### Phase 1: Core (Complete ✅)
- [x] Plugin scaffolding, activation, table creation
- [x] Subscriber CRUD with search/filter/export
- [x] Sequence + email CRUD
- [x] Enrollment and position-based advancement
- [x] Email sending with wp_mail + SMTP
- [x] Merge tag replacement
- [x] HTML email template
- [x] wp-cron processing (5-minute intervals)
- [x] Rate limiting (50/batch, 1s pause per 10)
- [x] Shortcode signup form with AJAX + honeypot
- [x] Open/click tracking
- [x] Unsubscribe handling
- [x] Admin dashboard with analytics
- [x] Email preview
- [x] Plugin info modal with banner/icon

### Phase 2: Forge Rebrand & Polish (Pending)
- [ ] Rename to DripForge (slug, text domain, class prefixes)
- [ ] Dark SaaS admin UI (Forge brand — teal accent like BackForge)
- [ ] SMTP credential encryption (AES-256-CBC like BackForge/LicenseForge)
- [ ] Test email button (send to admin email)
- [ ] Bounce handling improvements
- [ ] A/B subject line testing
- [ ] Subscriber import (CSV)

### Phase 3: Pro Features (Future)
- [ ] Visual email builder (drag-and-drop blocks)
- [ ] Conditional sequences (if opened → send X, else → send Y)
- [ ] Tagging system for subscribers
- [ ] Multiple sequence enrollment
- [ ] Webhook triggers (Zapier/Make integration)
- [ ] Advanced analytics (charts, time-series)
- [ ] Custom email templates
- [ ] Double opt-in
- [ ] Scheduled sends (specific time of day)
- [ ] White-label

---

## Settings (wp_options)

| Option Key | Default | Description |
|---|---|---|
| `nlde_from_name` | Site name | Sender display name |
| `nlde_from_email` | Admin email | Sender email address |
| `nlde_smtp_enabled` | `0` | Enable SMTP transport |
| `nlde_smtp_host` | — | SMTP server hostname |
| `nlde_smtp_port` | `587` | SMTP port |
| `nlde_smtp_user` | — | SMTP username |
| `nlde_smtp_pass` | — | SMTP password |
| `nlde_smtp_secure` | `tls` | Encryption (tls/ssl/none) |
| `nlde_db_version` | `1.0.0` | Database schema version |

---

## Cron Schedule

| Hook | Interval | Purpose |
|---|---|---|
| `nlde_process_drip_emails` | Every 5 minutes | Process pending drip sends |

### Pending Send Query Logic
A send is pending when:
1. Subscriber is `active`
2. Sequence is `active`
3. Email in sequence is `active`
4. Subscriber is enrolled and `active` in the sequence
5. Current position matches the email's position
6. No existing send_log entry for this subscriber + email
7. Enrollment date + delay_days ≤ now

---

## Security

- Nonce verification on all admin form submissions
- `manage_options` capability check on all admin actions
- `ABSPATH` check on all PHP files
- Honeypot field on signup forms
- Hash-verified unsubscribe links (`wp_hash()`)
- Sanitization: `sanitize_email()`, `sanitize_text_field()`, `sanitize_textarea_field()`, `wp_kses_post()`
- Escaping: `esc_html()`, `esc_attr()`, `esc_url()`
- Prepared statements for all database queries
- AJAX nonce verification on subscribe and preview endpoints

---

## Shortcode Reference

```
[nl_signup_form
    sequence="survival-kit-drip"
    button_text="Get My Free Kit"
    redirect="/thank-you/"
    show_name="yes"
    placeholder_name="First Name"
    placeholder_email="Email Address"
    class="my-custom-class"
]
```

| Attribute | Default | Description |
|---|---|---|
| `sequence` | — | Sequence slug to enroll subscriber in |
| `button_text` | `Subscribe` | Submit button text |
| `redirect` | — | URL to redirect after successful signup |
| `show_name` | `yes` | Show first name field (`yes`/`no`) |
| `placeholder_name` | `First Name` | Name field placeholder |
| `placeholder_email` | `Email Address` | Email field placeholder |
| `class` | — | Additional CSS class on wrapper |
