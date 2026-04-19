# WP License Platform — License System Guide

## Overview

The license system generates, validates, and manages license keys for your digital products. It provides a REST API that your Pro plugins call to verify if a customer has a valid license.

---

## License Key Format

```
{PRODUCT_PREFIX}-{BLOCK1}-{BLOCK2}-{BLOCK3}

Examples:
WPS3B-A7K2-M9X4-P3R8     (WP S3 Backup Pro)
FUTPB-B3N7-Q2W5-T8Y1     (Future Plugin B Pro)
```

### Generation

```php
class WPLP_License {

    /**
     * Generate a unique license key.
     *
     * @param string $prefix Product prefix (e.g., 'WPS3B').
     * @return string License key.
     */
    public static function generate_key( $prefix = 'WPLP' ) {
        $chars  = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // No I,O,0,1 to avoid confusion
        $blocks = array();

        for ( $b = 0; $b < 3; $b++ ) {
            $block = '';
            for ( $i = 0; $i < 4; $i++ ) {
                $block .= $chars[ wp_rand( 0, strlen( $chars ) - 1 ) ];
            }
            $blocks[] = $block;
        }

        $key = strtoupper( $prefix ) . '-' . implode( '-', $blocks );

        // Ensure uniqueness
        global $wpdb;
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}wplp_licenses WHERE license_key = %s",
            $key
        ));

        if ( $exists > 0 ) {
            return self::generate_key( $prefix ); // Recursive retry (extremely rare)
        }

        return $key;
    }

    /**
     * Create a new license.
     *
     * @param array $data License data.
     * @return object|WP_Error License object or error.
     */
    public static function create( $data ) {
        global $wpdb;

        $product = $wpdb->get_row( $wpdb->prepare(
            "SELECT slug FROM {$wpdb->prefix}wplp_products WHERE id = %d",
            $data['product_id']
        ));

        // Generate prefix from product slug (first 5 chars uppercase)
        $prefix = strtoupper( substr( preg_replace( '/[^a-z0-9]/', '', $product->slug ), 0, 5 ) );
        $key    = self::generate_key( $prefix );

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'wplp_licenses',
            array(
                'license_key'   => $key,
                'order_id'      => absint( $data['order_id'] ),
                'customer_id'   => absint( $data['customer_id'] ),
                'product_id'    => absint( $data['product_id'] ),
                'tier_id'       => absint( $data['tier_id'] ),
                'status'        => 'active',
                'sites_allowed' => absint( $data['sites_allowed'] ),
                'sites_active'  => 0,
                'expires_at'    => $data['expires_at'] ?? null,
                'created_at'    => current_time( 'mysql' ),
                'updated_at'    => current_time( 'mysql' ),
            ),
            array( '%s', '%d', '%d', '%d', '%d', '%s', '%d', '%d', '%s', '%s', '%s' )
        );

        if ( ! $inserted ) {
            return new WP_Error( 'wplp_license_create', 'Could not create license.' );
        }

        return self::find( $wpdb->insert_id );
    }

    /**
     * Find a license by ID.
     */
    public static function find( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wplp_licenses WHERE id = %d",
            $id
        ));
    }

    /**
     * Find a license by key.
     */
    public static function find_by_key( $key ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wplp_licenses WHERE license_key = %s",
            sanitize_text_field( $key )
        ));
    }
}
```

---

## REST API Endpoints

All endpoints are under `/wp-json/wplp/v1/`.

### POST /wplp/v1/validate

Called by Pro plugins to check if a license key is valid.

**Request:**
```json
{
    "license_key": "WPS3B-A7K2-M9X4-P3R8",
    "site_url": "https://customer-site.com",
    "product": "wp-s3-backup-pro",
    "version": "1.0.0"
}
```

**Response (valid):**
```json
{
    "valid": true,
    "license_key": "WPS3B-A7K2-M9X4-P3R8",
    "tier": "professional",
    "sites_allowed": 5,
    "sites_active": 2,
    "expires": "2027-04-15T00:00:00Z",
    "product": "wp-s3-backup-pro"
}
```

**Response (invalid):**
```json
{
    "valid": false,
    "reason": "expired",
    "expires": "2026-04-15T00:00:00Z"
}
```

**Response (over limit):**
```json
{
    "valid": false,
    "reason": "site_limit_reached",
    "sites_allowed": 1,
    "sites_active": 1
}
```

### Implementation

```php
class WPLP_API {

    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes() {
        register_rest_route( 'wplp/v1', '/validate', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'validate' ),
            'permission_callback' => '__return_true', // Public endpoint
        ));

        register_rest_route( 'wplp/v1', '/activate', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'activate' ),
            'permission_callback' => '__return_true',
        ));

        register_rest_route( 'wplp/v1', '/deactivate', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'deactivate' ),
            'permission_callback' => '__return_true',
        ));

        register_rest_route( 'wplp/v1', '/paypal-webhook', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'paypal_webhook' ),
            'permission_callback' => '__return_true',
        ));

        register_rest_route( 'wplp/v1', '/create-order', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'create_order' ),
            'permission_callback' => '__return_true',
        ));

        register_rest_route( 'wplp/v1', '/capture-order', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'capture_order' ),
            'permission_callback' => '__return_true',
        ));

        register_rest_route( 'wplp/v1', '/calculate-tax', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'calculate_tax' ),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * Validate a license key.
     */
    public static function validate( $request ) {
        $key      = sanitize_text_field( $request->get_param( 'license_key' ) );
        $site_url = esc_url_raw( $request->get_param( 'site_url' ) );
        $product  = sanitize_text_field( $request->get_param( 'product' ) );

        if ( empty( $key ) ) {
            return new WP_REST_Response( array(
                'valid'  => false,
                'reason' => 'missing_key',
            ), 400 );
        }

        $license = WPLP_License::find_by_key( $key );

        if ( ! $license ) {
            return new WP_REST_Response( array(
                'valid'  => false,
                'reason' => 'not_found',
            ), 200 );
        }

        // Check status
        if ( 'active' !== $license->status ) {
            return new WP_REST_Response( array(
                'valid'  => false,
                'reason' => $license->status, // expired, revoked, suspended
            ), 200 );
        }

        // Check expiry
        if ( $license->expires_at && strtotime( $license->expires_at ) < time() ) {
            // Auto-expire
            global $wpdb;
            $wpdb->update(
                $wpdb->prefix . 'wplp_licenses',
                array( 'status' => 'expired', 'updated_at' => current_time( 'mysql' ) ),
                array( 'id' => $license->id )
            );

            return new WP_REST_Response( array(
                'valid'   => false,
                'reason'  => 'expired',
                'expires' => $license->expires_at,
            ), 200 );
        }

        // Check site limit (only if site_url provided and not already activated)
        if ( ! empty( $site_url ) ) {
            $is_activated = WPLP_License::is_site_activated( $license->id, $site_url );
            if ( ! $is_activated && $license->sites_allowed > 0 && $license->sites_active >= $license->sites_allowed ) {
                return new WP_REST_Response( array(
                    'valid'         => false,
                    'reason'        => 'site_limit_reached',
                    'sites_allowed' => (int) $license->sites_allowed,
                    'sites_active'  => (int) $license->sites_active,
                ), 200 );
            }
        }

        // Get tier info
        global $wpdb;
        $tier = $wpdb->get_row( $wpdb->prepare(
            "SELECT name FROM {$wpdb->prefix}wplp_product_tiers WHERE id = %d",
            $license->tier_id
        ));

        return new WP_REST_Response( array(
            'valid'         => true,
            'license_key'   => $license->license_key,
            'tier'          => $tier ? $tier->name : 'unknown',
            'sites_allowed' => (int) $license->sites_allowed,
            'sites_active'  => (int) $license->sites_active,
            'expires'       => $license->expires_at,
            'product'       => $product,
        ), 200 );
    }

    /**
     * Activate a site against a license.
     */
    public static function activate( $request ) {
        $key      = sanitize_text_field( $request->get_param( 'license_key' ) );
        $site_url = esc_url_raw( $request->get_param( 'site_url' ) );

        $license = WPLP_License::find_by_key( $key );
        if ( ! $license || 'active' !== $license->status ) {
            return new WP_REST_Response( array( 'success' => false, 'reason' => 'invalid_license' ), 200 );
        }

        $result = WPLP_License::activate_site( $license->id, $site_url );
        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array( 'success' => false, 'reason' => $result->get_error_message() ), 200 );
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    /**
     * Deactivate a site from a license.
     */
    public static function deactivate( $request ) {
        $key      = sanitize_text_field( $request->get_param( 'license_key' ) );
        $site_url = esc_url_raw( $request->get_param( 'site_url' ) );

        $license = WPLP_License::find_by_key( $key );
        if ( ! $license ) {
            return new WP_REST_Response( array( 'success' => false, 'reason' => 'invalid_license' ), 200 );
        }

        WPLP_License::deactivate_site( $license->id, $site_url );
        return new WP_REST_Response( array( 'success' => true ), 200 );
    }
}
```

### Site Activation/Deactivation

```php
/**
 * Activate a site against a license.
 */
public static function activate_site( $license_id, $site_url ) {
    global $wpdb;

    $license = self::find( $license_id );

    // Check if already activated
    if ( self::is_site_activated( $license_id, $site_url ) ) {
        // Update last_checked timestamp
        $wpdb->update(
            $wpdb->prefix . 'wplp_activations',
            array( 'last_checked' => current_time( 'mysql' ) ),
            array( 'license_id' => $license_id, 'site_url' => $site_url )
        );
        return true;
    }

    // Check site limit
    if ( $license->sites_allowed > 0 && $license->sites_active >= $license->sites_allowed ) {
        return new WP_Error( 'site_limit', 'Site activation limit reached.' );
    }

    // Add activation
    $wpdb->insert(
        $wpdb->prefix . 'wplp_activations',
        array(
            'license_id'   => $license_id,
            'site_url'     => esc_url_raw( $site_url ),
            'activated_at' => current_time( 'mysql' ),
            'last_checked' => current_time( 'mysql' ),
        )
    );

    // Increment active count
    $wpdb->query( $wpdb->prepare(
        "UPDATE {$wpdb->prefix}wplp_licenses SET sites_active = sites_active + 1, updated_at = %s WHERE id = %d",
        current_time( 'mysql' ),
        $license_id
    ));

    return true;
}

/**
 * Deactivate a site from a license.
 */
public static function deactivate_site( $license_id, $site_url ) {
    global $wpdb;

    $deleted = $wpdb->delete(
        $wpdb->prefix . 'wplp_activations',
        array( 'license_id' => $license_id, 'site_url' => $site_url )
    );

    if ( $deleted ) {
        $wpdb->query( $wpdb->prepare(
            "UPDATE {$wpdb->prefix}wplp_licenses SET sites_active = GREATEST(sites_active - 1, 0), updated_at = %s WHERE id = %d",
            current_time( 'mysql' ),
            $license_id
        ));
    }
}

/**
 * Check if a site is already activated.
 */
public static function is_site_activated( $license_id, $site_url ) {
    global $wpdb;
    return (bool) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}wplp_activations WHERE license_id = %d AND site_url = %s",
        $license_id,
        esc_url_raw( $site_url )
    ));
}
```

---

## License Lifecycle

```
Purchase completed
    │
    ▼
License created (status: active, expires: +1 year)
    │
    ▼
Customer enters key in Pro plugin
    │
    ▼
Pro plugin calls /validate → valid
    │
    ▼
Pro plugin calls /activate → site registered
    │
    ▼
Daily: Pro plugin calls /validate → still valid (cached)
    │
    ├── 30 days before expiry → renewal reminder email
    ├── 7 days before expiry → second reminder
    ├── 1 day before expiry → final reminder
    │
    ▼
Expiry date reached
    │
    ├── If renewed → new expiry date, status stays active
    │
    └── If not renewed → status: expired
        │
        ▼
    Pro plugin calls /validate → invalid (reason: expired)
        │
        ▼
    Pro features disabled (gracefully, no data loss)
```

---

## Cron Jobs

```php
// Check for expiring licenses daily
add_action( 'wplp_daily_license_check', function() {
    global $wpdb;

    // Expire licenses past their date
    $wpdb->query( $wpdb->prepare(
        "UPDATE {$wpdb->prefix}wplp_licenses
         SET status = 'expired', updated_at = %s
         WHERE status = 'active' AND expires_at IS NOT NULL AND expires_at < %s",
        current_time( 'mysql' ),
        current_time( 'mysql' )
    ));

    // Send renewal reminders
    $reminders = array( 30, 7, 1 ); // days before expiry
    foreach ( $reminders as $days ) {
        $target_date = gmdate( 'Y-m-d', strtotime( "+{$days} days" ) );
        $licenses = $wpdb->get_results( $wpdb->prepare(
            "SELECT l.*, c.email, c.first_name, p.name as product_name
             FROM {$wpdb->prefix}wplp_licenses l
             JOIN {$wpdb->prefix}wplp_customers c ON l.customer_id = c.id
             JOIN {$wpdb->prefix}wplp_products p ON l.product_id = p.id
             WHERE l.status = 'active'
             AND DATE(l.expires_at) = %s",
            $target_date
        ));

        foreach ( $licenses as $license ) {
            WPLP_Email::send_renewal_reminder( $license, $days );
        }
    }
});
```

---

## Security

### API Rate Limiting

Prevent brute-force license key guessing:

```php
public static function check_rate_limit( $ip ) {
    $key   = 'wplp_rate_' . md5( $ip );
    $count = (int) get_transient( $key );

    if ( $count >= 20 ) { // Max 20 requests per minute
        return false;
    }

    set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
    return true;
}
```

### Key Entropy

With the format `PREFIX-XXXX-XXXX-XXXX` using 32 characters (A-Z minus I,O + 2-9):
- 32^12 = ~1.15 × 10^18 possible keys
- Brute-force at 20 attempts/minute = ~109 billion years
- Effectively unguessable
