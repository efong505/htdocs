=== WP License Platform ===
Contributors: ekewaka
Tags: license, paypal, digital products, vat, ecommerce
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sell WordPress plugins and themes from your own website. PayPal checkout, license key management, VAT compliance, and customer portal — all in one plugin. Zero per-transaction fees.

== Description ==

WP License Platform turns your WordPress website into a complete digital product store. Sell plugins, themes, and any downloadable product with professional PayPal checkout, automatic license key management, global VAT compliance, and a self-service customer portal.

**Unlike SaaS platforms that charge 5-10% per transaction, WP License Platform has zero per-transaction fees.** You only pay PayPal's standard processing fee (2.9% + $0.30).

= Why WP License Platform? =

* **Zero platform fees** — SaaS alternatives charge 5-10% per sale. At $50K/year in sales, that's $2,500-5,000 saved annually.
* **Self-hosted** — Your data stays on your server. No vendor lock-in. No third-party accessing your customer data.
* **All-in-one** — No need for 5-10 separate plugins. Payment, licensing, VAT, emails, portal — everything in one lightweight plugin.
* **Lightweight** — Under 100KB total. No bloated SDKs or frameworks bundled.

= Features =

**Payment Processing**
* PayPal REST API v2 integration (credit cards + PayPal accounts)
* Professional checkout page with real-time tax calculation
* Webhook handling for payment confirmations and refunds
* One-click refund processing from admin panel
* PayPal sandbox mode for testing

**License Key Management**
* Auto-generated unique license keys (e.g., WPS3B-A7K2-M9X4-P3R8)
* Per-product key prefixes for easy identification
* Site activation limits (1, 5, 25, or unlimited sites per license)
* REST API for your Pro plugins to validate licenses
* Rate-limited API to prevent brute-force guessing
* Automatic expiry with configurable license duration

**VAT Compliance**
* Built-in EU and UK VAT rates (27 EU countries + UK + Norway + Switzerland)
* Two-piece location evidence collection (HMRC requirement for digital goods)
* IP geolocation for automatic country detection
* EU VAT number validation via VIES API
* B2B reverse charge for valid EU VAT numbers
* VAT breakdown shown on checkout and invoices

**Customer Portal**
* Self-service dashboard — customers manage their own licenses
* View and copy license keys
* Download product files (token-based secure downloads)
* Deactivate sites to free up license slots
* View order history and invoices
* Shortcode-based — works with any WordPress theme

**Automated Emails**
* Purchase confirmation with license key
* Renewal reminders (30, 7, and 1 day before expiry)
* License expired notification
* Refund confirmation

**Admin Panel**
* Product management with multiple pricing tiers
* Order management with status tracking
* License management with activation details
* Dashboard with revenue, orders, licenses, and customer stats
* PayPal connection testing
* Encrypted credential storage (AES-256-CBC)

**Developer-Friendly**
* REST API endpoints for license validation, activation, and deactivation
* Shortcodes for checkout, portal, and pricing tables
* Clean database schema with custom tables (not wp_options)
* Follows WordPress coding standards
* Fully translatable

= Use Cases =

* Sell premium WordPress plugins with license keys
* Sell premium WordPress themes with activation limits
* Sell any downloadable digital product
* Manage licenses for SaaS products built on WordPress
* Replace expensive SaaS platforms (Freemius, LemonSqueezy, Gumroad)

= Requirements =

* PayPal Business account (for API credentials)
* PHP 7.4+ with openssl extension
* WordPress 6.0+

== Installation ==

1. Upload the `wp-license-platform` folder to `/wp-content/plugins/`
2. Activate through the Plugins menu
3. Go to **License Platform → Settings** and enter your PayPal API credentials
4. Go to **License Platform → Products** and create your first product with pricing tiers
5. Create WordPress pages with these shortcodes:
   * Checkout page: `[wplp_checkout]`
   * Customer portal: `[wplp_portal]`
   * Pricing table: `[wplp_pricing product="your-product-slug"]`
6. Test with PayPal sandbox mode enabled
7. Disable sandbox mode when ready for live payments

= Getting PayPal API Credentials =

1. Go to [developer.paypal.com](https://developer.paypal.com)
2. Log in with your PayPal Business account
3. Go to Apps & Credentials
4. Create a new app (or use the default)
5. Copy the Client ID and Client Secret
6. Set up a webhook pointing to: `https://your-site.com/wp-json/wplp/v1/paypal-webhook`

== Frequently Asked Questions ==

= How is this different from WooCommerce? =

WooCommerce is a general ecommerce platform designed for physical and digital products. It requires 5-10 extensions ($300-800/year) to handle licensing, VAT, and subscriptions. WP License Platform is purpose-built for digital product licensing — everything is included in one plugin.

= How is this different from Freemius or LemonSqueezy? =

Those are SaaS platforms that charge 5-7% per transaction and host your data on their servers. WP License Platform is self-hosted with zero platform fees. You only pay PayPal's standard 2.9% + $0.30.

= Do I need a PayPal Business account? =

Yes. A PayPal Business account is required to access the REST API for processing payments.

= How do my Pro plugins validate licenses? =

Your Pro plugins make a POST request to `https://your-site.com/wp-json/wplp/v1/validate` with the license key and site URL. The API returns whether the license is valid, the tier, sites allowed, and expiry date.

= Is VAT compliance mandatory? =

If you sell digital goods to consumers in the EU or UK, yes. The plugin handles this automatically — it detects the customer's country, applies the correct VAT rate, and stores the required evidence.

= Can I sell multiple products? =

The free version supports 1 product with up to 3 pricing tiers. The Pro version supports unlimited products and tiers.

= What happens if my site goes down? =

Pro plugins cache the license validation result for 7 days. If your site is temporarily unavailable, customers' Pro features continue working during the grace period.

== Changelog ==

= 1.0.0 =
* Initial release
* PayPal REST API v2 checkout integration
* License key generation and management
* REST API for license validation, activation, deactivation
* EU/UK VAT compliance with two-piece evidence
* Customer portal with license management and downloads
* Automated emails (purchase, renewal, expiry, refund)
* HTML invoice generation
* Admin dashboard with stats
* Product and tier management
* Encrypted credential storage

== Upgrade Notice ==

= 1.0.0 =
Initial release.

== External Services ==

This plugin connects to the following external services:

**PayPal REST API**
* Purpose: Payment processing (creating orders, capturing payments, refunds)
* When: During checkout and when processing refunds
* URL: https://api-m.paypal.com (live) or https://api-m.sandbox.paypal.com (sandbox)
* Privacy Policy: https://www.paypal.com/privacy
* Terms: https://www.paypal.com/legalhub

**ip-api.com**
* Purpose: IP geolocation for VAT country detection (one of two required evidence pieces)
* When: During checkout to determine customer's country
* URL: http://ip-api.com/json/
* Privacy Policy: https://ip-api.com/docs/legal
* Note: Only the customer's IP address is sent. No personal data is transmitted.

**EU VIES API**
* Purpose: Validating EU VAT numbers for B2B reverse charge
* When: When a customer enters an EU VAT number during checkout
* URL: https://ec.europa.eu/taxation_customs/vies/
* Privacy Policy: https://ec.europa.eu/info/privacy-policy_en

No other external services are used. No analytics, tracking, or telemetry data is collected.

== Screenshots ==

1. Dashboard with welcome guide, feature overview, and quick start steps
2. Product management with pricing tier editor
3. Checkout page with tier selection, VAT calculation, and PayPal button
4. Customer portal showing licenses with activation management
5. Settings page with PayPal configuration and business information
6. Orders list with status badges and tax breakdown
7. Upgrade to Pro page with feature cards and pricing
