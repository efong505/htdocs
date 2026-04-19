# WP S3 Backup — Pro License Integration Guide

## Overview

This document explains how the WP S3 Backup plugin connects to the WP License Platform for Pro feature activation. The license platform is a **separate project** that handles payment processing, license key management, VAT compliance, and customer portal.

**License Platform Project:** See the `wp-license-platform` project for full documentation on building and deploying the platform.

---

## Architecture

```
┌─────────────────────────────────┐
│ Your Website (ekewaka.com)      │
│                                 │
│ WP License Platform (plugin)    │
│ ├── Product catalog             │
│ ├── Checkout + PayPal           │
│ ├── VAT evidence collection     │
│ ├── License key management      │
│ ├── Customer portal             │
│ └── REST API                    │
│     ├── /wplp/v1/validate       │
│     ├── /wplp/v1/activate       │
│     └── /wplp/v1/deactivate     │
└──────────┬──────────────────────┘
           │ HTTPS (license validation)
           │
┌──────────▼──────────────────────┐
│ Customer's WordPress Site       │
│                                 │
│ wp-s3-backup (free)             │
│ ├── Full backup/restore to S3   │
│ ├── Scheduling                  │
│ ├── Storage class badges        │
│ └── "Upgrade to Pro" links      │
│                                 │
│ wp-s3-backup-pro (paid)         │
│ ├── License key field           │
│ ├── Validates against your API  │
│ └── Unlocks Pro features:       │
│     ├── Selective restore       │
│     ├── URL replacement restore │
│     ├── Staging restore         │
│     ├── Restore history         │
│     ├── Incremental backups     │
│     ├── Email notifications     │
│     ├── Slack/webhook alerts    │
│     ├── Backup encryption       │
│     ├── Custom schedules        │
│     ├── Storage class mgmt      │
│     ├── Multi-site support      │
│     └── White-label             │
└─────────────────────────────────┘
```

---

## How It Works

### 1. Customer Purchases Pro

1. Customer clicks "Upgrade to Pro" in the free plugin
2. Redirected to your website's checkout page (powered by WP License Platform)
3. Pays via PayPal (or credit card)
4. WP License Platform generates a license key
5. Customer receives email with key + download link for `wp-s3-backup-pro.zip`

### 2. Customer Activates Pro

1. Customer installs `wp-s3-backup-pro` plugin (requires `wp-s3-backup` free to be active)
2. Goes to **S3 Backup → Pro License**
3. Enters license key: `WPS3B-XXXX-XXXX-XXXX`
4. Plugin sends validation request to your API
5. API confirms: valid, tier, sites allowed, expiry date
6. Pro features unlocked

### 3. Ongoing Validation

- Plugin re-validates the license once per day (cached)
- If your API is unreachable, the plugin uses the cached result (grace period: 7 days)
- If the license expires or is revoked, Pro features are disabled gracefully (no data loss)

---

## Integration Points in the Free Plugin

The free plugin already has hooks and filters that the Pro plugin uses. No modifications to the free plugin are needed.

### Filters for Pro Features

```php
// The free plugin checks these before showing Pro UI elements
apply_filters( 'wps3b_exclude_paths', $paths );        // Pro: custom path selection
apply_filters( 'wps3b_exclude_tables', $tables );       // Pro: table selection
apply_filters( 'wps3b_s3_path_prefix', $prefix );       // Pro: multi-site prefixes
apply_filters( 'wps3b_backup_filename', $filename );     // Pro: custom naming
apply_filters( 'wps3b_max_file_size', 5368709120 );     // Pro: configurable limit
```

### Actions for Pro Features

```php
// The free plugin fires these at key moments
do_action( 'wps3b_before_backup' );                     // Pro: pre-backup hooks
do_action( 'wps3b_after_backup', $manifest );            // Pro: email/Slack notifications
do_action( 'wps3b_backup_failed', $error );              // Pro: failure notifications
do_action( 'wps3b_before_upload', $file_path );          // Pro: encryption before upload
do_action( 'wps3b_after_upload', $s3_key );              // Pro: post-upload processing
```

### Pro Feature Detection

The free plugin can optionally show "Pro" badges in the UI:

```php
// In the free plugin's settings page
$is_pro = class_exists( 'WPS3B_Pro' ) && WPS3B_Pro::is_licensed();

if ( ! $is_pro ) {
    echo '<span class="wps3b-pro-badge">Pro</span>';
    echo '<a href="https://ekewaka.com/wp-s3-backup-pro/">Upgrade</a>';
}
```

---

## Pro Plugin Structure

The Pro plugin is a separate WordPress plugin that extends the free version:

```
wp-s3-backup-pro/
├── wp-s3-backup-pro.php          # Main file, checks free plugin is active
├── includes/
│   ├── class-wps3b-pro.php           # Main Pro class, feature registration
│   ├── class-wps3b-pro-license.php   # License validation against your API
│   ├── class-wps3b-pro-incremental.php   # Incremental backup engine
│   ├── class-wps3b-pro-notifications.php # Email/Slack notifications
│   ├── class-wps3b-pro-encryption.php    # Client-side backup encryption
│   ├── class-wps3b-pro-restore.php       # Selective restore, URL replacement
│   └── class-wps3b-pro-storage.php       # Storage class management
└── admin/
    └── views/
        └── license-page.php          # License key entry UI
```

### License Validation Code

```php
class WPS3B_Pro_License {

    const API_URL    = 'https://ekewaka.com/wp-json/wplp/v1/';
    const CACHE_KEY  = 'wps3b_license_data';
    const CACHE_TTL  = DAY_IN_SECONDS;

    public static function validate( $license_key ) {
        $response = wp_remote_post( self::API_URL . 'validate', array(
            'timeout' => 15,
            'body'    => array(
                'license_key' => $license_key,
                'site_url'    => get_site_url(),
                'product'     => 'wp-s3-backup-pro',
                'version'     => WPS3B_VERSION,
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            // Use cached result if API is unreachable
            return self::get_cached_license();
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $body && isset( $body['valid'] ) && $body['valid'] ) {
            // Cache the valid license
            set_transient( self::CACHE_KEY, $body, self::CACHE_TTL );
            return $body;
        }

        return false;
    }

    public static function is_licensed() {
        $cached = get_transient( self::CACHE_KEY );
        if ( $cached && isset( $cached['valid'] ) && $cached['valid'] ) {
            // Check expiry
            if ( isset( $cached['expires'] ) && strtotime( $cached['expires'] ) > time() ) {
                return true;
            }
        }
        return false;
    }

    public static function get_tier() {
        $cached = get_transient( self::CACHE_KEY );
        return $cached && isset( $cached['tier'] ) ? $cached['tier'] : 'free';
    }

    private static function get_cached_license() {
        return get_transient( self::CACHE_KEY );
    }
}
```

---

## API Endpoints (Provided by WP License Platform)

### POST /wplp/v1/validate

Validates a license key and returns its status.

**Request:**
```json
{
    "license_key": "WPS3B-XXXX-XXXX-XXXX",
    "site_url": "https://customer-site.com",
    "product": "wp-s3-backup-pro",
    "version": "1.0.0"
}
```

**Response (valid):**
```json
{
    "valid": true,
    "tier": "professional",
    "sites_allowed": 5,
    "sites_active": 2,
    "expires": "2027-04-15T00:00:00Z",
    "customer_email": "customer@example.com"
}
```

**Response (invalid):**
```json
{
    "valid": false,
    "reason": "expired"
}
```

### POST /wplp/v1/activate

Registers a site against a license key (counts toward site limit).

### POST /wplp/v1/deactivate

Removes a site from a license key (frees up a slot).

---

## Adding "Upgrade to Pro" Links in the Free Plugin

The free plugin should have tasteful, non-aggressive Pro upgrade prompts:

### Settings Page
- Next to Pro-only settings, show a locked icon + "Pro" badge
- At the bottom: "Unlock advanced features with WP S3 Backup Pro"

### Backups Page
- Next to storage class badges: "Change storage class — Pro" (grayed out)
- Below the backup list: "Get incremental backups, email notifications, and more"

### Implementation
```php
// Helper function in the free plugin
function wps3b_is_pro_active() {
    return class_exists( 'WPS3B_Pro' )
        && method_exists( 'WPS3B_Pro', 'is_licensed' )
        && WPS3B_Pro::is_licensed();
}

// In a settings field
if ( ! wps3b_is_pro_active() ) {
    printf(
        '<span class="wps3b-pro-badge">Pro</span> <a href="%s" target="_blank">%s</a>',
        esc_url( 'https://ekewaka.com/wp-s3-backup-pro/' ),
        esc_html__( 'Upgrade to unlock', 'wp-s3-backup' )
    );
}
```

---

## Setup Checklist

To enable Pro sales, you need:

1. **WP License Platform** installed on your website (see separate project)
2. **Product created** in the license platform for "WP S3 Backup Pro" with tiers
3. **PayPal connected** in the license platform settings
4. **Pro plugin built** (`wp-s3-backup-pro`) with the features listed above
5. **Pricing page** on your website linking to the checkout
6. **Upgrade links** added to the free plugin pointing to your pricing page

---

## References

- **WP License Platform Project** — Full documentation for building the payment/licensing system
- [08-MONETIZATION-STRATEGY.md](08-MONETIZATION-STRATEGY.md) — Pricing tiers, revenue projections, marketing plan
- [09-PAYMENT-PROCESSING-GUIDE.md](09-PAYMENT-PROCESSING-GUIDE.md) — PayPal vs LemonSqueezy comparison, VAT compliance
