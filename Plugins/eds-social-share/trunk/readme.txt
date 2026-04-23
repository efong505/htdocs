=== Ed's Social Share ===
Contributors:  waianaeboy702
Donate link: https://www.paypal.com/biz/fund?id=87QSX24B66BHL
Tags: Social Sharing, Shortcode, Icons, Social Icons, Eds Social Share
Requires at least: 6.8
<<<<<<< .mine
Tested up to: 6.8
Stable tag: 2.1
||||||| .r3280170
Tested up to: 6.8
Stable tag: 2.0
=======
Tested up to: 6.9
Stable tag: 3.0
>>>>>>> .r3514119
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add beautiful social share buttons for Facebook, X, LinkedIn, TikTok, WhatsApp, Threads, and more with a simple shortcode. 

== Description ==

The Ultimate Share Shortcode Plugin to share all of your sites on social media.

**Supported Platforms:**

*   Facebook, X (Twitter), Instagram, LinkedIn, Pinterest, YouTube, GitHub
*   **NEW:** TikTok, Threads, WhatsApp, Reddit, Telegram, Truth Social
*   Email, Print

**Features:**

*   Simple shortcode — just add `[social_share]` anywhere
*   Toggle each platform on/off in settings
*   **NEW:** Custom profile URLs for Instagram, GitHub, YouTube, TikTok, Threads
*   **NEW:** Global Twitter/X handle setting
*   **NEW:** Icon size option — small, medium, large
*   **NEW:** Styles only load when shortcode is used (better performance)
*   **NEW:** Built-in Open Graph meta tags — control the image, title, and description shown when your pages are shared
*   **NEW:** Per-page share image override via meta box
*   **NEW:** Default share image setting with media library upload
*   Smart conflict detection — auto-disables Open Graph if Yoast, RankMath, AIOSEO, or Jetpack is active
*   Beautiful animated hover effects with tooltips
*   Fully responsive


== Installation ==

1. Unzip the plugin file
2. Upload the folder `eds-social-share` and it's contents to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Ed's Social Share → Plugin Options to enable your platforms
5. Add `[social_share]` to the location you want the social share bar to be
6. Enjoy Ed's Social Share Plugin

= Shortcode Options =

* `text` — Header text above icons (default: "Share Us"). Example: `[social_share text="Share This!"]`
* `color` — Header text color. Example: `[social_share color="white"]`
* `twitter` — Twitter/X @handle for via tag. Example: `[social_share twitter="myhandle"]`
* `size` — Icon size: small, medium, large. Example: `[social_share size="large"]`

You can combine options: `[social_share text="Share Me" twitter="myhandle" color="green" size="large"]`

= Open Graph Setup =

1. Go to Ed's Social Share → Plugin Options
2. Scroll to "Open Graph / Social Share Image"
3. Check "Enable Open Graph Tags"
4. Upload a default share image (recommended: 1200×630 pixels)
5. Save

To set a custom share image for a specific page or post:

1. Edit the page/post
2. Find the "Social Share Image" meta box in the sidebar
3. Enter an image URL or click "Select Image" to use the media library
4. Update the page

Image priority: Per-page image → Featured image → Default image from settings

== Screenshots ==

1. Screenshot of Ed's Social Share How To Use
2. Screenshot of Ed's Social Share in Admin Sidebar 
3. Screenshot of using shortcode on the back end
4. Screenshot of Cover
5. Screenshot of Social Share Icons available to toggle on and off.

== Changelog ==

= 1.0.1 =
* Updated tested versions to include WordPress 6.0.1 
* Improved security

= 2.0 =
* Updated tested versions to include WordPress 6.8 
* Updated Twitter to X Icon
* Upgraded Fontawesome version to  Font Awesome Free 6.7

<<<<<<< .mine
= 2.1 =
* Security fix: Sanitized and escaped all shortcode attribute outputs to prevent Stored XSS (CVE-2026-2501)
* Tested up to WordPress 6.8

||||||| .r3280170
=======
= 2.1 =
* Security fix: Sanitized and escaped all shortcode attribute outputs to prevent Stored XSS (CVE-2026-2501)
* Applied esc_attr() to color and twitter shortcode attributes
* Applied esc_html() to text shortcode attribute
* Tested up to WordPress 6.8

= 3.0 =
* NEW: TikTok, Threads, WhatsApp, Reddit, Telegram, Truth Social platform support
* NEW: Custom profile URLs for Instagram, GitHub, YouTube, TikTok, Threads
* NEW: Global Twitter/X handle setting in options
* NEW: Icon size attribute (small, medium, large)
* NEW: Built-in Open Graph meta tags (og:title, og:description, og:image, og:url)
* NEW: Twitter Card meta tags (summary_large_image)
* NEW: Default share image setting with media library upload
* NEW: Per-page share image override via "Social Share Image" meta box
* NEW: Smart conflict detection — auto-disables OG if Yoast, RankMath, AIOSEO, or Jetpack is active
* NEW: Styles only load when shortcode is present on the page
* Improved: Sanitization callbacks on all settings
* Improved: All share links open in new tab with rel="noopener noreferrer"
* Improved: Cleaner code structure with constants
* Updated: X (Twitter) branding and colors
* Updated: Facebook brand color to current #1877F2

>>>>>>> .r3514119
== Frequently Asked Questions ==

= How do I control the image shown when my page is shared? =

Enable Open Graph in Plugin Options and upload a default share image (1200×630 recommended). You can also set a custom image per page/post using the "Social Share Image" meta box in the editor sidebar. The plugin uses this priority: per-page image → featured image → default image.

= Will this conflict with Yoast SEO or other SEO plugins? =

No. The Open Graph feature automatically disables itself if Yoast SEO, RankMath, All in One SEO, or Jetpack is detected.

= How often will this plugin be updated =

We check our plugin frequently so as soon as there are updates to WordPress we make the necessary updates.

== Upgrade Notice ==

= 1.0.1 =
This upgrade gets rid of `untested WordPress version` notice on the plugin page for users who've upgraded to WordPress 4.3

= 2.1 =
Security patch - fixes Stored XSS vulnerability in shortcode attributes. Update immediately.

= 3.0 =
Major update — TikTok, Threads, WhatsApp, Reddit, Telegram, Truth Social. Open Graph share images. Custom profile URLs. Icon sizes.

