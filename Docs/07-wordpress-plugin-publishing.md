# Publishing a WordPress Plugin to WordPress.org
## Using TortoiseSVN — Step-by-Step Tutorial

---

## Prerequisites
- ✅ TortoiseSVN installed (you have this)
- ✅ WordPress.org account (https://wordpress.org/support/register/)
- ✅ Plugin code ready and tested
- ✅ readme.txt file (required — see format below)

---

## Phase 1: Submit Your Plugin for Review

### Step 1: Prepare Your Plugin
Before submitting, ensure your plugin meets WordPress.org guidelines:

1. **No premium upsells or external service requirements** in the free version
2. **GPL v2 or later license** (already in our plugin header)
3. **No tracking/phoning home** without user consent
4. **No obfuscated code**
5. **Must be useful to the broader WordPress community**
6. **Must use WordPress coding standards** (reasonable compliance)

### Step 2: Create readme.txt
This is the most important file — it generates your plugin's page on wordpress.org.

Create `readme.txt` in the plugin root folder:

```
=== NL Drip Engine ===
Contributors: nextlevelwebdev
Donate link: https://nextlevelwebdevelopers.com
Tags: email marketing, drip campaign, email automation, lead generation, newsletter
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Email drip marketing automation for WordPress. Capture leads, build sequences, and nurture subscribers with timed email campaigns.

== Description ==

NL Drip Engine lets you capture leads, build automated email sequences, and nurture subscribers — all from your WordPress dashboard. No third-party service required.

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
3. Go to NL Drip Engine → Settings to configure your sender info and SMTP
4. Go to NL Drip Engine → Sequences to create your first drip campaign
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

= 1.0.0 =
Initial release.
```

### Step 3: Take Screenshots
WordPress.org displays screenshots on your plugin page. Save them as:
- `screenshot-1.png` — Dashboard
- `screenshot-2.png` — Sequence builder
- `screenshot-3.png` — Settings page
- `screenshot-4.png` — Frontend form

These go in the `assets/` folder of your SVN repository (not the plugin zip).

### Step 4: Submit for Review
1. Go to https://wordpress.org/plugins/developers/add/
2. Log in with your WordPress.org account
3. Upload a ZIP of your plugin (just the plugin folder, zipped)
4. Fill in the plugin name: "NL Drip Engine"
5. Provide a brief description
6. Submit

**Review takes 1-7 business days.** They'll email you with approval or requested changes.

---

## Phase 2: After Approval — SVN Setup with TortoiseSVN

Once approved, WordPress.org gives you an SVN repository URL like:
```
https://plugins.svn.wordpress.org/nl-drip-engine/
```

### Step 5: Checkout the SVN Repository

1. Create a folder on your computer, e.g. `C:\wp-plugins\nl-drip-engine-svn\`
2. Right-click the folder → **SVN Checkout**
3. Enter the repository URL: `https://plugins.svn.wordpress.org/nl-drip-engine/`
4. Click OK
5. Enter your WordPress.org username and password when prompted

This creates the standard SVN structure:
```
nl-drip-engine-svn/
├── assets/          ← Plugin page images (banner, icon, screenshots)
├── branches/        ← Not typically used
├── tags/            ← Tagged releases (1.0.0, 1.0.1, etc.)
└── trunk/           ← Current development version
```

### Step 6: Copy Plugin Files to Trunk

1. Copy ALL your plugin files into the `trunk/` folder:
   ```
   trunk/
   ├── admin/
   ├── assets/          ← Plugin's own assets (CSS, JS)
   ├── includes/
   ├── public/
   ├── templates/
   ├── nl-drip-engine.php
   └── readme.txt
   ```

2. Right-click the `trunk/` folder → **TortoiseSVN → Add**
3. Select all new files → OK

### Step 7: Add Plugin Page Assets

These go in the TOP-LEVEL `assets/` folder (NOT trunk/assets/):

```
assets/
├── banner-772x250.png       ← Plugin page banner (required)
├── banner-1544x500.png      ← Retina banner (optional)
├── icon-128x128.png         ← Plugin icon (recommended)
├── icon-256x256.png         ← Retina icon (recommended)
├── screenshot-1.png         ← Screenshot 1
├── screenshot-2.png         ← Screenshot 2
├── screenshot-3.png         ← Screenshot 3
└── screenshot-4.png         ← Screenshot 4
```

1. Copy your banner and icon images into the `assets/` folder
2. Right-click `assets/` → **TortoiseSVN → Add**

### Step 8: Commit to SVN

1. Right-click the root `nl-drip-engine-svn/` folder
2. **TortoiseSVN → Commit**
3. Enter a commit message: "Initial release v1.0.0"
4. Make sure all files are checked
5. Click OK
6. Enter your WordPress.org credentials

### Step 9: Create a Tag (Release)

Tags are how WordPress.org knows which version to serve. The `Stable tag` in readme.txt must match.

1. Right-click `trunk/` → **TortoiseSVN → Branch/Tag**
2. To URL: `https://plugins.svn.wordpress.org/nl-drip-engine/tags/1.0.0`
3. Message: "Tagging version 1.0.0"
4. Click OK

### Step 10: Verify

1. Wait 5-15 minutes for WordPress.org to process
2. Visit `https://wordpress.org/plugins/nl-drip-engine/`
3. Your plugin page should be live with banner, icon, description, and download button

---

## Phase 3: Updating Your Plugin (Future Releases)

### To release version 1.0.1:

1. Make your code changes in `trunk/`
2. Update version number in:
   - `nl-drip-engine.php` (plugin header `Version: 1.0.1`)
   - `readme.txt` (`Stable tag: 1.0.1`)
   - `NLDE_VERSION` constant
3. Add changelog entry in `readme.txt`
4. Right-click `trunk/` → **TortoiseSVN → Commit**
5. Message: "Version 1.0.1 - description of changes"
6. Create tag: Branch/Tag → `tags/1.0.1`
7. Commit the tag

WordPress.org will automatically detect the new version and push updates to all users.

---

## Common TortoiseSVN Operations

| Task | How |
|------|-----|
| **Add new files** | Right-click → TortoiseSVN → Add |
| **Delete files** | Right-click → TortoiseSVN → Delete |
| **See changes** | Right-click → TortoiseSVN → Check for modifications |
| **Revert changes** | Right-click → TortoiseSVN → Revert |
| **Update from server** | Right-click → SVN Update |
| **View log** | Right-click → TortoiseSVN → Show log |
| **Resolve conflicts** | Right-click → TortoiseSVN → Resolve |

---

## Important Notes

- **Never commit credentials** — our plugin stores SMTP passwords in wp_options, which is fine. But never hardcode credentials in plugin files.
- **readme.txt is king** — WordPress.org generates your entire plugin page from this file. Keep it updated.
- **Stable tag must match** — The `Stable tag` in readme.txt must exactly match a folder name in `tags/`.
- **Assets are separate** — Plugin page images (banner, icon, screenshots) go in the SVN `assets/` folder, NOT in the plugin zip.
- **Be patient** — After committing, it can take 5-15 minutes for changes to appear on wordpress.org.
- **Plugin review guidelines** — Full list at https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/

---

## Quick Reference: File Locations

| File | Where it goes | Purpose |
|------|--------------|---------|
| `nl-drip-engine.php` | `trunk/` | Main plugin file |
| `readme.txt` | `trunk/` | Plugin page content |
| All plugin code | `trunk/` | The actual plugin |
| `banner-772x250.png` | `assets/` (SVN root) | Plugin page banner |
| `icon-256x256.png` | `assets/` (SVN root) | Plugin list icon |
| `screenshot-*.png` | `assets/` (SVN root) | Plugin page screenshots |
