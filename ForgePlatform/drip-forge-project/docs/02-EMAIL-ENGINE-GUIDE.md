# DripForge — Email Engine Technical Guide

## Overview

DripForge sends emails through WordPress's `wp_mail()` function, optionally configured with SMTP credentials for reliable delivery. Every email passes through merge tag replacement, HTML template wrapping, and tracking injection before sending.

---

## Sending Flow

```
wp-cron fires (every 5 minutes)
        │
        ▼
Query pending sends (50 max per batch)
        │
        ▼
For each pending send:
├── Replace merge tags in subject + body
├── Wrap body in HTML email template
├── Inject open tracking pixel
├── Set From headers
├── Configure SMTP (if enabled)
├── Call wp_mail()
├── Log result in send_log with tracking hash
├── Advance subscriber to next position
└── Rate limit: sleep(1) every 10 sends
```

---

## Pending Send Query

The core query joins 4 tables to find emails ready to send:

```
subscriber_sequences (enrollment)
    JOIN sequences (must be active)
    JOIN sequence_emails (position matches, must be active)
    JOIN subscribers (must be active)
    LEFT JOIN send_log (must NOT already exist)
WHERE enrollment_date + delay_days <= now
LIMIT 50
```

This ensures:
- Only active subscribers in active sequences get emails
- Each email is sent exactly once per subscriber
- Delay timing is respected from enrollment date
- Batch size prevents timeout

---

## SMTP Configuration

When `nlde_smtp_enabled` is `1`, the plugin hooks into `phpmailer_init` to override WordPress's default mail transport:

```php
$phpmailer->isSMTP();
$phpmailer->Host       = get_option('nlde_smtp_host');
$phpmailer->SMTPAuth   = true;
$phpmailer->Port       = (int) get_option('nlde_smtp_port', 587);
$phpmailer->Username   = get_option('nlde_smtp_user');
$phpmailer->Password   = get_option('nlde_smtp_pass');
$phpmailer->SMTPSecure = get_option('nlde_smtp_secure', 'tls');
```

The hook is added before `wp_mail()` and removed immediately after to avoid affecting other plugins.

### Supported Providers

| Provider | Host | Port | Encryption |
|---|---|---|---|
| Amazon SES | `email-smtp.{region}.amazonaws.com` | 587 | TLS |
| SendGrid | `smtp.sendgrid.net` | 587 | TLS |
| Brevo | `smtp-relay.brevo.com` | 587 | TLS |
| Gmail | `smtp.gmail.com` | 587 | TLS |

---

## Merge Tags

Replaced in both subject and body before sending:

| Tag | Replacement |
|---|---|
| `{first_name}` | Subscriber's first name (falls back to "there") |
| `{last_name}` | Subscriber's last name |
| `{email}` | Subscriber's email |
| `{site_name}` | `get_bloginfo('name')` |
| `{site_url}` | `home_url()` |
| `{unsubscribe_link}` | Hash-verified unsubscribe URL |
| `{rfp_link}` | `/request-for-proposal/` page URL |
| `{download_link}` | `/survival-kit-download/` page URL |

---

## HTML Email Template

Every email is wrapped in a responsive HTML template:

```
┌─────────────────────────────────────┐
│  [Dark header: Site Name]           │
├─────────────────────────────────────┤
│                                     │
│  [Email body with wpautop()]        │
│                                     │
├─────────────────────────────────────┤
│  [Footer: Site name + URL]          │
│  [Unsubscribe link]                 │
│  [Open tracking pixel]              │
└─────────────────────────────────────┘
```

- Header: `#1a2332` background, white text
- Body: White background, 16px font, 1.6 line height
- Footer: `#f8f8f8` background, 12px gray text
- Max width: 600px, centered
- `wpautop()` converts newlines to `<p>` tags

---

## Tracking

### Open Tracking
A 1×1 transparent GIF pixel is appended to each email. When the recipient's email client loads images, it hits:

```
?nlde_track_open=1&hash={tracking_hash}
```

The plugin intercepts this on `init`, records the open timestamp, and returns the GIF.

### Click Tracking
Links in emails can be wrapped to pass through:

```
?nlde_track_click=1&hash={tracking_hash}&url={encoded_destination}
```

The plugin records the click and redirects to the actual URL.

### Tracking Hash
Each send generates a unique 32-character hash (`wp_generate_password(32, false)`). This hash links the tracking event back to the specific send_log entry.

---

## Unsubscribe Flow

```
Email contains {unsubscribe_link}
        │
        ▼
Link: ?nlde_unsubscribe=1&email={email}&hash={wp_hash(email)}
        │
        ▼
Plugin verifies hash matches email (prevents abuse)
        │
        ▼
Sets subscriber status to 'unsubscribed'
        │
        ▼
Shows confirmation page: "You've been unsubscribed"
```

The hash is generated with `wp_hash($email)` which uses WordPress's `AUTH_SALT`, making it unforgeable without access to `wp-config.php`.

---

## Rate Limiting

- **Batch size:** 50 emails per cron run
- **Throttle:** 1-second sleep every 10 emails
- **Cron interval:** Every 5 minutes
- **Effective max throughput:** ~600 emails/hour

This is conservative for shared hosting. With a dedicated SMTP provider (SES, SendGrid), the SMTP provider's rate limits become the bottleneck, not the plugin.

---

## Email Preview

Admin can preview any sequence email in a new browser tab:

1. Replaces merge tags with sample data (John Doe, admin email)
2. Wraps in the full HTML template
3. Adds a sticky preview bar showing subject, email number, day, and merge tag notice
4. Renders as a full HTML page (not inside WordPress admin)
