=== DripForge ===
Contributors: ekewaka
Donate link: https://ekewaka.com
Tags: email marketing, drip campaign, email automation, lead generation, newsletter
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Self-hosted email drip marketing automation for WordPress. Capture leads, build timed sequences, and nurture subscribers — zero SaaS fees.

== Description ==

DripForge lets you capture leads, build automated email sequences, and nurture subscribers — all from your WordPress dashboard. No third-party service required. Part of the Forge Product Family.

**Features:**

* Subscriber management with search, filter, and CSV export
* Drip sequence builder with timed email delivery
* Merge tags for personalized emails ({first_name}, {site_name}, etc.)
* SMTP integration (Amazon SES, SendGrid, Brevo, Gmail)
* Open and click tracking analytics
* Honeypot spam protection on signup forms
* CAN-SPAM compliant unsubscribe handling
* Shortcode-based signup forms for any page or post
* Responsive HTML email templates
* WP Cron-based automated sending

**Shortcode Usage:**

`[nl_signup_form sequence="your-sequence-slug" button_text="Subscribe" redirect="/thank-you/" show_name="yes"]`

**Shortcode Parameters:**

* `sequence` — The slug of the drip sequence to enroll subscribers in
* `button_text` — Custom button text (default: "Subscribe")
* `redirect` — URL to redirect after signup (optional)
* `show_name` — Show first name field: "yes" or "no" (default: "yes")
* `class` — Additional CSS class for the form wrapper

== Installation ==

1. Upload the `nl-drip-engine` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to DripForge → Settings to configure your sender info and SMTP
4. Go to DripForge → Sequences to create your first drip campaign
5. Add emails to your sequence with subject, body, and delay timing
6. Use the shortcode `[nl_signup_form sequence="your-slug"]` on any page
7. Set your sequence status to "Active" and you're live!

== Frequently Asked Questions ==

= Do I need an SMTP service? =

For testing, the plugin works with WordPress's default mail function. For production use, we strongly recommend configuring SMTP (Amazon SES, SendGrid, or Brevo) for reliable email delivery.

= How often are drip emails sent? =

The plugin checks for pending emails every 5 minutes via WP Cron. For more reliable timing, set up a real server cron job.

= Is this CAN-SPAM compliant? =

Yes. Every email includes an unsubscribe link, and unsubscribed users are immediately removed from all active sequences.

= Can I use multiple sequences? =

Yes. Create as many sequences as you need, each with their own emails and timing.

== Screenshots ==

1. Dashboard with subscriber and email statistics
2. Sequence builder with email management
3. Settings page with SMTP configuration
4. Frontend signup form

== Changelog ==

= 1.1.0 =
* Rebranded to DripForge (Forge Product Family)
* Updated author and plugin URI

= 1.0.0 =
* Initial release
* Subscriber management (add, search, filter, export CSV)
* Drip sequence builder with timed emails
* SMTP integration
* Open and click tracking
* Shortcode-based signup forms
* Honeypot spam protection
* CAN-SPAM compliant unsubscribe

== Upgrade Notice ==

= 1.1.0 =
Rebranded to DripForge. No functional changes — safe to update.

= 1.0.0 =
Initial release.
