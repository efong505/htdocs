# WordPress.org Plugin Submission Guide

*Step-by-step instructions for submitting Forge plugins to the WordPress.org repository.*

---

## Table of Contents

1. [Pre-Submission Checklist](#pre-submission-checklist)
2. [Submission Process](#submission-process)
3. [After Approval](#after-approval)
4. [SVN Deployment with TortoiseSVN](#svn-deployment-with-tortoisesvn)
5. [Ongoing Updates](#ongoing-updates)

---

## Pre-Submission Checklist

Before submitting, every plugin MUST pass these checks:

### Required Files
- [ ] Main plugin file with valid plugin header
- [ ] `readme.txt` in WordPress.org format (use validator: https://wordpress.org/plugins/developers/readme-validator/)
- [ ] `uninstall.php` for clean removal
- [ ] `LICENSE` file (GPL-2.0-or-later)

### Plugin Header Requirements
```php
/**
 * Plugin Name: Your Plugin Name
 * Plugin URI:  https://your-site.com/plugin
 * Description: Short description (max 150 chars recommended)
 * Version:     1.0.0
 * Author:      Your Name
 * Author URI:  https://your-site.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: your-text-domain
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */
```

### Code Requirements (Common Rejection Reasons)
- [ ] No hardcoded secrets, keys, or passwords
- [ ] No `file_get_contents()` on remote URLs (use `wp_remote_get()`)
- [ ] No `curl_*` functions (use `wp_remote_*()`)
- [ ] No `eval()`, `exec()`, `shell_exec()`, `system()`, `passthru()`
- [ ] No obfuscated or encoded code
- [ ] No tracking/analytics/phoning home without disclosure
- [ ] No bundled libraries available in WordPress core (jQuery, React, etc.)
- [ ] No premium upsell nag screens that can't be dismissed
- [ ] All user-facing strings wrapped in `__()` or `_e()` with text domain
- [ ] All inputs sanitized (`sanitize_text_field()`, `absint()`, etc.)
- [ ] All outputs escaped (`esc_html()`, `esc_attr()`, `esc_url()`)
- [ ] All forms use nonces (`wp_nonce_field()` / `check_admin_referer()`)
- [ ] All AJAX handlers check nonces and capabilities
- [ ] Every PHP file starts with `if (!defined('ABSPATH')) exit;`
- [ ] Unique prefix on ALL functions, classes, options, hooks, constants
- [ ] No direct database queries without `$wpdb->prepare()`
- [ ] No deprecated WordPress functions
- [ ] `readme.txt` Stable tag matches plugin header Version
- [ ] Description doesn't promise features not yet implemented
- [ ] No debug/development files included in submission

### External Services Disclosure
If your plugin communicates with any external service, you MUST:
- Disclose it in `readme.txt` under `== External Services ==`
- Link to the service's Terms of Service
- Link to the service's Privacy Policy
- Explain what data is sent and when

### Assets for WordPress.org (Prepared Separately)
These go in the `/assets/` directory of SVN (NOT in the plugin zip):
- `banner-772x250.png` — Plugin page banner (required)
- `banner-1544x500.png` — HiDPI banner (recommended)
- `icon-128x128.png` — Plugin icon
- `icon-256x256.png` — HiDPI plugin icon
- `screenshot-1.png` (etc.) — Screenshots referenced in readme.txt

---

## Submission Process

### Step 1: Validate readme.txt
1. Go to https://wordpress.org/plugins/developers/readme-validator/
2. Paste your `readme.txt` content
3. Fix any errors or warnings

### Step 2: Create Plugin ZIP
1. Create a clean ZIP of the plugin folder
2. The ZIP should extract to a single folder named after the plugin slug
3. Do NOT include:
   - `.git` directories
   - `node_modules`
   - `.DS_Store` / `Thumbs.db`
   - Development/debug files
   - `.psd` source files
   - Any file not needed for the plugin to function

**Example (from command line):**
```
cd c:\xampp\htdocs\ForgePlatform\wp-s3-backup-project\plugin
# Remove any debug files first
del wp-s3-backup\wps3b-debug.php
# Create ZIP
powershell Compress-Archive -Path wp-s3-backup -DestinationPath wp-s3-backup.zip
```

### Step 3: Submit to WordPress.org
1. Log in at https://wordpress.org/plugins/developers/add/
2. Upload your plugin ZIP
3. Fill in the submission notes (see `02-SUBMISSION-EMAILS.md` for copy)
4. Submit

### Step 4: Wait for Review
- Initial review typically takes **5-10 business days** (can be longer)
- You'll receive an email at your WordPress.org account email
- If issues are found, they'll list specific problems to fix
- You can reply to the email with corrections and resubmit
- Once approved, you'll get SVN access

---

## After Approval

When approved, you'll receive:
- An SVN repository URL: `https://plugins.svn.wordpress.org/your-plugin-slug/`
- Instructions to commit your first version

### SVN Repository Structure
```
your-plugin-slug/
├── trunk/          ← Current development version (latest code)
├── tags/
│   ├── 1.0.0/     ← Tagged release (matches Stable tag in readme.txt)
│   └── 1.0.1/     ← Next release
└── assets/         ← Banner, icon, screenshots (NOT in the plugin zip)
```

---

## SVN Deployment with TortoiseSVN

### First-Time Setup (After Approval)

1. **Create a local working directory:**
   ```
   C:\SVN\wp-s3-backup\
   ```

2. **Checkout the SVN repo:**
   - Right-click the folder → TortoiseSVN → Checkout
   - URL: `https://plugins.svn.wordpress.org/wp-s3-backup/`
   - Checkout directory: `C:\SVN\wp-s3-backup`
   - Click OK (will create trunk/, tags/, assets/ folders)

3. **Copy plugin files to trunk:**
   - Copy all plugin files into `trunk/`
   - Do NOT copy the parent folder — files go directly in trunk

4. **Add assets:**
   - Copy banner/icon PNGs into `assets/`

5. **Commit trunk:**
   - Right-click `trunk/` → TortoiseSVN → Add (select all new files)
   - Right-click `trunk/` → TortoiseSVN → Commit
   - Message: `Initial release v1.0.0`
   - Enter your WordPress.org username and password when prompted

6. **Create the tag:**
   - Right-click `tags/` → New Folder → `1.0.0`
   - Copy everything from `trunk/` into `tags/1.0.0/`
   - Right-click `tags/1.0.0/` → TortoiseSVN → Add
   - Right-click the repo root → TortoiseSVN → Commit
   - Message: `Tagging version 1.0.0`

7. **Commit assets:**
   - Right-click `assets/` → TortoiseSVN → Add
   - Commit with message: `Add plugin assets`

### Updating an Existing Plugin

1. **Update your working copy:**
   - Right-click repo folder → TortoiseSVN → Update

2. **Replace files in trunk:**
   - Delete old files, copy new files into `trunk/`
   - Or overwrite changed files directly

3. **Commit trunk:**
   - Right-click → TortoiseSVN → Commit
   - Message: `Update to v1.0.1 — [brief changelog]`

4. **Create new tag:**
   - Create `tags/1.0.1/` folder
   - Copy trunk contents into it
   - Add and commit

**Important:** The `Stable tag` in `readme.txt` (in trunk) tells WordPress.org which tag to serve. Always update this to match your latest tag.

---

## Ongoing Updates

### Version Bump Checklist
1. Update version in main plugin file header
2. Update version in any `define('VERSION', ...)` constant
3. Update `Stable tag` in `readme.txt`
4. Update `Tested up to` in `readme.txt` if WordPress has updated
5. Add changelog entry in `readme.txt`
6. Commit to trunk, then create new tag

### Response Times
- Plugin updates appear on WordPress.org within **5-15 minutes** of SVN commit
- Asset changes (banners, icons) may take up to **1 hour** to propagate

---

## Plugin-Specific Notes

### BackForge (wp-s3-backup)
- Slug: `wp-s3-backup`
- **CRITICAL:** Remove `wps3b-debug.php` before submission — contains hardcoded key
- External service disclosure for AWS S3 already in readme.txt ✅
- Plugin URI should point to ekewaka.com/backforge (update from GitHub URL)

### ShieldForge (sf-security)
- Slug: `sf-security`
- **CRITICAL:** Needs `readme.txt` created (see `03-PLUGIN-FIXES.md`)
- **CRITICAL:** Remove "country blocking" and "file integrity monitoring" from description — not yet implemented
- No external services — no disclosure needed ✅

### DripForge (nl-drip-engine)
- Slug: `nl-drip-engine`
- **CRITICAL:** Fix version mismatch — readme says 1.1.1, plugin header says 1.2.0
- **CRITICAL:** Remove `.psd` file from assets folder before submission
- No external services (SMTP is user-configured) — no disclosure needed ✅
