# Email Drip Marketing System — Two Options
## Document 01

---

## Option A: Custom WordPress Plugin (NL Drip Engine)

### Overview
A custom-built WordPress plugin that handles subscriber management, drip sequence automation, and email delivery — all within your WordPress installation.

### Features
- Subscriber signup forms (shortcode-based)
- Subscriber management dashboard in WP Admin
- Drip sequence builder (set email content + delay in days)
- Email template system with merge tags ({first_name}, {site_name}, etc.)
- SMTP integration for reliable delivery
- Basic analytics (sent, opened, clicked)
- CSV export of subscribers
- Unsubscribe handling (CAN-SPAM compliant)

### Requirements
- WordPress 5.0+
- PHP 7.4+
- SMTP service for email delivery (see SMTP Options below)
- WP Cron enabled (or real cron job for reliability)

### SMTP Service Options

| Service | Free Tier | Cost After Free | Deliverability |
|---------|-----------|----------------|----------------|
| **Amazon SES** | 62,000/month (from EC2) | $0.10/1,000 emails | Excellent |
| **SendGrid** | 100/day | $19.95/mo for 50K | Excellent |
| **Brevo (Sendinblue)** | 300/day | $25/mo for 20K | Good |
| **Gmail SMTP** | 500/day | Free (Google Workspace) | Fair |

**Recommendation:** Amazon SES — cheapest long-term, best deliverability, and you're already in the AWS ecosystem.

### Plugin Architecture

```
nl-drip-engine/
├── nl-drip-engine.php          (Main plugin file)
├── includes/
│   ├── class-subscriber.php    (Subscriber CRUD)
│   ├── class-drip-sequence.php (Sequence management)
│   ├── class-email-sender.php  (SMTP/sending logic)
│   ├── class-analytics.php     (Open/click tracking)
│   └── class-cron.php          (Scheduled email processing)
├── admin/
│   ├── class-admin-menu.php    (WP Admin pages)
│   ├── views/                  (Admin page templates)
│   └── assets/                 (Admin CSS/JS)
├── public/
│   ├── class-signup-form.php   (Frontend form shortcode)
│   └── assets/                 (Frontend CSS/JS)
└── templates/
    └── email/                  (Email HTML templates)
```

### Database Tables

```sql
-- Subscribers
nl_subscribers (id, email, first_name, last_name, status, subscribed_at, unsubscribed_at)

-- Drip Sequences
nl_sequences (id, name, description, status, created_at)

-- Sequence Emails
nl_sequence_emails (id, sequence_id, position, subject, body, delay_days)

-- Send Log
nl_send_log (id, subscriber_id, email_id, sent_at, opened_at, clicked_at, status)
```

### Setup Instructions

#### Step 1: Install the Plugin
1. Upload the `nl-drip-engine` folder to `/wp-content/plugins/`
2. Activate in WP Admin → Plugins

#### Step 2: Configure SMTP (Amazon SES Example)
1. Sign up for AWS account at https://aws.amazon.com
2. Go to Amazon SES console → Verified Identities
3. Verify your sending domain (edwardfong.onthewifi.com or your business domain)
4. Create SMTP credentials in SES → SMTP Settings
5. In WP Admin → NL Drip Engine → Settings, enter:
   - SMTP Host: `email-smtp.us-west-2.amazonaws.com` (use your region)
   - SMTP Port: `587`
   - SMTP Username: (from SES)
   - SMTP Password: (from SES)
   - From Email: `ed@nextlevelwebdevelopers.com`
   - From Name: `Ed Fong | Next Level Web Developers`

#### Step 3: Create a Drip Sequence
1. Go to WP Admin → NL Drip Engine → Sequences
2. Click "Add New Sequence"
3. Name it: "Survival Kit Drip"
4. Add emails with delays:
   - Email 1: Day 0 (Welcome + Download Link)
   - Email 2: Day 2
   - Email 3: Day 4
   - Email 4: Day 7
   - Email 5: Day 10
   - Email 6: Day 14
   - Email 7: Day 17

#### Step 4: Add Signup Form to Pages
Use the shortcode on any page or post:
```
[nl_signup_form sequence="survival-kit-drip" button_text="Get My Free Survival Kit" redirect="/survival-kit-download/"]
```

#### Step 5: Set Up Cron (Recommended)
For reliable email delivery, add a real cron job instead of relying on WP Cron:

**On Linux/cPanel:**
```
*/5 * * * * wget -q -O - https://edwardfong.onthewifi.com/next-level/wp-cron.php?doing_wp_cron > /dev/null 2>&1
```

**On XAMPP (Windows, for testing):**
Use Windows Task Scheduler to hit the WP Cron URL every 5 minutes.

### Pros
- Full control over data and functionality
- No monthly fees (just SMTP costs — pennies)
- No third-party dependency
- Customizable to your exact needs
- Subscriber data stays on your server

### Cons
- You maintain the code (bugs, updates, security)
- No pre-built visual email designer
- Analytics are basic compared to dedicated platforms
- Initial setup is more involved

---

## Option B: MailerLite (Third-Party Service)

### Overview
MailerLite is a dedicated email marketing platform with a generous free tier. It handles everything — forms, automation, email design, delivery, and analytics — as a cloud service.

### Free Tier Includes
- Up to 1,000 subscribers
- 12,000 emails/month
- Automation workflows (drip sequences)
- Signup forms and popups
- Landing pages
- Email templates with drag-and-drop editor
- Basic analytics (opens, clicks, unsubscribes)

### Paid Tier ($10/month for 500 subscribers)
- Unlimited emails
- Auto-resend campaigns
- Advanced analytics
- Remove MailerLite branding
- A/B testing

### Setup Instructions

#### Step 1: Create Account
1. Go to https://www.mailerlite.com
2. Sign up with your business email
3. Verify your domain (they'll walk you through DNS records)
4. Complete account approval (they review new accounts — takes 24-48 hours)

#### Step 2: Set Up Subscriber Group
1. Go to Subscribers → Groups
2. Create group: "Survival Kit Leads"

#### Step 3: Create the Signup Form
1. Go to Forms → Embedded Forms
2. Click "Create Form"
3. Design the form:
   - Fields: First Name, Email
   - Button text: "Get My Free Survival Kit"
   - Success message: Redirect to your download page
4. Assign to group: "Survival Kit Leads"
5. Copy the embed code

#### Step 4: Embed Form in WordPress
**Option A — HTML Block:**
1. Edit your signup page in WordPress
2. Add a Custom HTML block
3. Paste the MailerLite embed code

**Option B — MailerLite Plugin:**
1. Install "MailerLite — Signup Forms" plugin from WP Plugin Directory
2. Connect with your MailerLite API key (found in Account → Integrations → API)
3. Use the plugin's widget/shortcode to place forms

#### Step 5: Build the Automation (Drip Sequence)
1. Go to Automations → Create Automation
2. Trigger: "When subscriber joins group: Survival Kit Leads"
3. Add steps:
   ```
   Trigger: Joins "Survival Kit Leads"
       ↓
   Email 1: Welcome + Download Link (immediately)
       ↓
   Delay: 2 days
       ↓
   Email 2: 3 Mistakes Businesses Make
       ↓
   Delay: 2 days
       ↓
   Email 3: What Professional Web Presence Costs
       ↓
   Delay: 3 days
       ↓
   Email 4: Case Study
       ↓
   Delay: 3 days
       ↓
   Email 5: DIY vs Hiring a Pro
       ↓
   Delay: 4 days
       ↓
   Email 6: FAQ About Our Services
       ↓
   Delay: 3 days
       ↓
   Email 7: CTA → Request for Proposal
   ```
4. Activate the automation

#### Step 6: Test
1. Subscribe with a test email
2. Verify welcome email arrives with download link
3. Verify drip emails arrive on schedule (you can shorten delays for testing)

### Pros
- Zero coding required
- Professional email templates with drag-and-drop editor
- Excellent deliverability (they manage sender reputation)
- Advanced analytics out of the box
- A/B testing on paid plan
- Landing page builder included
- Automatic CAN-SPAM compliance

### Cons
- Free tier limited to 1,000 subscribers
- Monthly cost as list grows ($10+/month)
- Data lives on third-party servers
- Less customization than a custom solution
- Account approval process (24-48 hours)
- MailerLite branding on free tier

---

## Comparison Summary

| Feature | Option A: Custom Plugin | Option B: MailerLite |
|---------|------------------------|---------------------|
| **Cost** | SMTP only (~$1/mo) | Free up to 1K subs, then $10+/mo |
| **Setup Time** | 2-3 hours | 30-60 minutes |
| **Maintenance** | You maintain | They maintain |
| **Email Designer** | Basic HTML templates | Drag-and-drop visual editor |
| **Deliverability** | Depends on SMTP config | Excellent out of the box |
| **Analytics** | Basic (opens, clicks) | Advanced (heatmaps, geo, device) |
| **Data Ownership** | 100% yours | On their servers |
| **Customization** | Unlimited | Limited to their features |
| **Scalability** | Unlimited (SMTP limits only) | Tied to pricing tiers |
| **CAN-SPAM** | Manual implementation | Automatic |
| **A/B Testing** | Not included | Paid plan |
| **Learning Curve** | Low (WP Admin) | Low (their dashboard) |

---

## Recommendation

**For starting out:** Go with **Option A (Custom Plugin)** if you want full control and minimal ongoing costs. Since I'm building it for you, the setup burden is handled. Pair it with Amazon SES and you'll pay pennies per month.

**If you prefer hands-off:** Go with **Option B (MailerLite)** if you want a polished visual editor and don't want to think about email infrastructure. The free tier is plenty to start.

**Hybrid approach:** Start with the custom plugin. If your list grows past 5,000+ subscribers and you need advanced features (A/B testing, advanced segmentation), migrate to MailerLite or similar at that point.
