# WP License Platform — Customer Portal & Email Guide

## Overview

The customer portal is a set of WordPress pages (using shortcodes) where customers can manage their licenses, download products, view invoices, and update their account.

---

## Portal Pages

Create these WordPress pages and add the shortcodes:

| Page | Shortcode | Purpose |
|------|-----------|---------|
| /portal/ | `[wplp_portal]` | Dashboard — overview of licenses and recent orders |
| /portal/licenses/ | `[wplp_licenses]` | List all licenses with status, sites, expiry |
| /portal/downloads/ | `[wplp_downloads]` | Download product files |
| /portal/invoices/ | `[wplp_invoices]` | View and download PDF invoices |
| /checkout/ | `[wplp_checkout]` | Product checkout with PayPal |
| /checkout/thank-you/ | `[wplp_thank_you]` | Post-purchase confirmation |
| /pricing/ | `[wplp_pricing]` | Embeddable pricing table |

### Portal Dashboard Shortcode

```php
class WPLP_Portal {

    public static function init() {
        add_shortcode( 'wplp_portal', array( __CLASS__, 'render_dashboard' ) );
        add_shortcode( 'wplp_licenses', array( __CLASS__, 'render_licenses' ) );
        add_shortcode( 'wplp_downloads', array( __CLASS__, 'render_downloads' ) );
        add_shortcode( 'wplp_invoices', array( __CLASS__, 'render_invoices' ) );
        add_shortcode( 'wplp_checkout', array( __CLASS__, 'render_checkout' ) );
        add_shortcode( 'wplp_thank_you', array( __CLASS__, 'render_thank_you' ) );
        add_shortcode( 'wplp_pricing', array( __CLASS__, 'render_pricing' ) );
    }

    public static function render_dashboard() {
        if ( ! is_user_logged_in() ) {
            return self::login_form();
        }

        $customer = WPLP_Customer::find_by_wp_user( get_current_user_id() );
        if ( ! $customer ) {
            return '<p>' . esc_html__( 'No purchases found for your account.', 'wp-license-platform' ) . '</p>';
        }

        ob_start();
        include WPLP_PLUGIN_DIR . 'public/views/portal-dashboard.php';
        return ob_get_clean();
    }

    private static function login_form() {
        if ( isset( $_GET['wplp_login'] ) ) {
            return wp_login_form( array( 'echo' => false, 'redirect' => get_permalink() ) );
        }
        return '<p>' . sprintf(
            esc_html__( 'Please %s to access your account.', 'wp-license-platform' ),
            '<a href="' . esc_url( add_query_arg( 'wplp_login', '1' ) ) . '">' . esc_html__( 'log in', 'wp-license-platform' ) . '</a>'
        ) . '</p>';
    }
}
```

### Portal Dashboard View

```php
<!-- public/views/portal-dashboard.php -->
<div class="wplp-portal">
    <h2><?php printf( esc_html__( 'Welcome, %s', 'wp-license-platform' ), esc_html( $customer->first_name ) ); ?></h2>

    <div class="wplp-portal-grid">
        <div class="wplp-portal-card">
            <h3><?php esc_html_e( 'Active Licenses', 'wp-license-platform' ); ?></h3>
            <span class="wplp-portal-count"><?php echo esc_html( $active_count ); ?></span>
            <a href="<?php echo esc_url( get_permalink( $licenses_page_id ) ); ?>">
                <?php esc_html_e( 'Manage Licenses', 'wp-license-platform' ); ?>
            </a>
        </div>

        <div class="wplp-portal-card">
            <h3><?php esc_html_e( 'Downloads', 'wp-license-platform' ); ?></h3>
            <a href="<?php echo esc_url( get_permalink( $downloads_page_id ) ); ?>">
                <?php esc_html_e( 'Download Products', 'wp-license-platform' ); ?>
            </a>
        </div>

        <div class="wplp-portal-card">
            <h3><?php esc_html_e( 'Invoices', 'wp-license-platform' ); ?></h3>
            <a href="<?php echo esc_url( get_permalink( $invoices_page_id ) ); ?>">
                <?php esc_html_e( 'View Invoices', 'wp-license-platform' ); ?>
            </a>
        </div>
    </div>

    <h3><?php esc_html_e( 'Recent Orders', 'wp-license-platform' ); ?></h3>
    <table class="wplp-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Order', 'wp-license-platform' ); ?></th>
                <th><?php esc_html_e( 'Product', 'wp-license-platform' ); ?></th>
                <th><?php esc_html_e( 'Date', 'wp-license-platform' ); ?></th>
                <th><?php esc_html_e( 'Total', 'wp-license-platform' ); ?></th>
                <th><?php esc_html_e( 'Status', 'wp-license-platform' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $recent_orders as $order ) : ?>
            <tr>
                <td><?php echo esc_html( $order->order_number ); ?></td>
                <td><?php echo esc_html( $order->product_name ); ?></td>
                <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $order->created_at ) ) ); ?></td>
                <td><?php echo esc_html( '$' . number_format( $order->total, 2 ) ); ?></td>
                <td><span class="wplp-status-<?php echo esc_attr( $order->status ); ?>"><?php echo esc_html( ucfirst( $order->status ) ); ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
```

### Licenses Page View

```php
<!-- public/views/portal-licenses.php -->
<div class="wplp-portal">
    <h2><?php esc_html_e( 'Your Licenses', 'wp-license-platform' ); ?></h2>

    <?php foreach ( $licenses as $license ) : ?>
    <div class="wplp-license-card">
        <div class="wplp-license-header">
            <h3><?php echo esc_html( $license->product_name ); ?> — <?php echo esc_html( ucfirst( $license->tier_name ) ); ?></h3>
            <span class="wplp-status-<?php echo esc_attr( $license->status ); ?>">
                <?php echo esc_html( ucfirst( $license->status ) ); ?>
            </span>
        </div>

        <div class="wplp-license-key">
            <code><?php echo esc_html( $license->license_key ); ?></code>
            <button class="wplp-copy-btn" data-key="<?php echo esc_attr( $license->license_key ); ?>">
                <?php esc_html_e( 'Copy', 'wp-license-platform' ); ?>
            </button>
        </div>

        <div class="wplp-license-details">
            <span><?php printf( esc_html__( 'Sites: %d / %d', 'wp-license-platform' ), $license->sites_active, $license->sites_allowed ); ?></span>
            <span><?php printf( esc_html__( 'Expires: %s', 'wp-license-platform' ), esc_html( date_i18n( get_option( 'date_format' ), strtotime( $license->expires_at ) ) ) ); ?></span>
        </div>

        <?php if ( ! empty( $license->activations ) ) : ?>
        <div class="wplp-activations">
            <h4><?php esc_html_e( 'Active Sites', 'wp-license-platform' ); ?></h4>
            <ul>
                <?php foreach ( $license->activations as $activation ) : ?>
                <li>
                    <?php echo esc_html( $activation->site_url ); ?>
                    <form method="post" style="display:inline;">
                        <?php wp_nonce_field( 'wplp_deactivate_site' ); ?>
                        <input type="hidden" name="license_id" value="<?php echo esc_attr( $license->id ); ?>" />
                        <input type="hidden" name="site_url" value="<?php echo esc_attr( $activation->site_url ); ?>" />
                        <button type="submit" name="wplp_deactivate_site" class="wplp-btn-small">
                            <?php esc_html_e( 'Deactivate', 'wp-license-platform' ); ?>
                        </button>
                    </form>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
```

---

## File Downloads

Product files (Pro plugin zips) are served through a token-based download system to prevent unauthorized access.

### Download URL Generation

```php
/**
 * Generate a secure, time-limited download URL.
 *
 * @param int $license_id License ID.
 * @param int $product_id Product ID.
 * @return string Download URL.
 */
public static function get_download_url( $license_id, $product_id ) {
    $token = wp_generate_password( 32, false );
    $expiry = time() + HOUR_IN_SECONDS;

    set_transient( 'wplp_download_' . $token, array(
        'license_id' => $license_id,
        'product_id' => $product_id,
        'expiry'     => $expiry,
    ), HOUR_IN_SECONDS );

    return add_query_arg( array(
        'wplp_download' => $token,
    ), home_url( '/' ) );
}

/**
 * Handle download request.
 */
public static function handle_download() {
    if ( empty( $_GET['wplp_download'] ) ) return;

    $token = sanitize_text_field( wp_unslash( $_GET['wplp_download'] ) );
    $data  = get_transient( 'wplp_download_' . $token );

    if ( ! $data || $data['expiry'] < time() ) {
        wp_die( esc_html__( 'Download link has expired.', 'wp-license-platform' ) );
    }

    // Verify license is still active
    $license = WPLP_License::find( $data['license_id'] );
    if ( ! $license || 'active' !== $license->status ) {
        wp_die( esc_html__( 'License is no longer active.', 'wp-license-platform' ) );
    }

    // Get product file
    global $wpdb;
    $product = $wpdb->get_row( $wpdb->prepare(
        "SELECT file_path FROM {$wpdb->prefix}wplp_products WHERE id = %d",
        $data['product_id']
    ));

    if ( ! $product || ! file_exists( $product->file_path ) ) {
        wp_die( esc_html__( 'File not found.', 'wp-license-platform' ) );
    }

    // Delete the token (one-time use)
    delete_transient( 'wplp_download_' . $token );

    // Serve the file
    header( 'Content-Type: application/zip' );
    header( 'Content-Disposition: attachment; filename="' . basename( $product->file_path ) . '"' );
    header( 'Content-Length: ' . filesize( $product->file_path ) );
    header( 'Cache-Control: no-cache, must-revalidate' );
    readfile( $product->file_path );
    exit;
}
```

---

## Email Templates

### Purchase Confirmation

```php
class WPLP_Email {

    public static function send_purchase_confirmation( $customer, $order, $license ) {
        $subject = sprintf(
            '[%s] Your purchase of %s',
            get_bloginfo( 'name' ),
            $order->product_name
        );

        $body = self::render_template( 'purchase-confirmation', array(
            'customer' => $customer,
            'order'    => $order,
            'license'  => $license,
            'site_url' => get_site_url(),
        ));

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $customer->email, $subject, $body, $headers );
    }

    private static function render_template( $template, $data ) {
        extract( $data );
        ob_start();
        include WPLP_PLUGIN_DIR . 'templates/emails/' . $template . '.php';
        return ob_get_clean();
    }
}
```

### Email Template: Purchase Confirmation

```html
<!-- templates/emails/purchase-confirmation.php -->
<div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif;">
    <h2>Thank you for your purchase!</h2>

    <p>Hi <?php echo esc_html( $customer->first_name ); ?>,</p>

    <p>Your order <strong><?php echo esc_html( $order->order_number ); ?></strong> has been completed.</p>

    <table style="width:100%;border-collapse:collapse;margin:20px 0;">
        <tr style="background:#f5f5f5;">
            <td style="padding:10px;border:1px solid #ddd;"><strong>Product</strong></td>
            <td style="padding:10px;border:1px solid #ddd;"><?php echo esc_html( $order->product_name ); ?></td>
        </tr>
        <tr>
            <td style="padding:10px;border:1px solid #ddd;"><strong>License Key</strong></td>
            <td style="padding:10px;border:1px solid #ddd;"><code><?php echo esc_html( $license->license_key ); ?></code></td>
        </tr>
        <tr style="background:#f5f5f5;">
            <td style="padding:10px;border:1px solid #ddd;"><strong>Sites Allowed</strong></td>
            <td style="padding:10px;border:1px solid #ddd;"><?php echo esc_html( $license->sites_allowed ); ?></td>
        </tr>
        <tr>
            <td style="padding:10px;border:1px solid #ddd;"><strong>Expires</strong></td>
            <td style="padding:10px;border:1px solid #ddd;"><?php echo esc_html( date_i18n( 'F j, Y', strtotime( $license->expires_at ) ) ); ?></td>
        </tr>
        <tr style="background:#f5f5f5;">
            <td style="padding:10px;border:1px solid #ddd;"><strong>Total</strong></td>
            <td style="padding:10px;border:1px solid #ddd;">$<?php echo esc_html( number_format( $order->total, 2 ) ); ?> <?php echo esc_html( $order->currency ); ?></td>
        </tr>
    </table>

    <h3>Next Steps</h3>
    <ol>
        <li>Download the Pro plugin from your <a href="<?php echo esc_url( $site_url . '/portal/downloads/' ); ?>">customer portal</a></li>
        <li>Install and activate the plugin on your WordPress site</li>
        <li>Go to the plugin settings and enter your license key</li>
    </ol>

    <p>If you have any questions, reply to this email.</p>
</div>
```

### Renewal Reminder Email

```html
<!-- templates/emails/renewal-reminder.php -->
<div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif;">
    <h2>Your license expires soon</h2>

    <p>Hi <?php echo esc_html( $customer->first_name ); ?>,</p>

    <p>Your license for <strong><?php echo esc_html( $license->product_name ); ?></strong> expires on
    <strong><?php echo esc_html( date_i18n( 'F j, Y', strtotime( $license->expires_at ) ) ); ?></strong>
    (<?php echo esc_html( $days_remaining ); ?> days from now).</p>

    <p>Renew now to keep your Pro features active:</p>

    <p style="text-align:center;margin:30px 0;">
        <a href="<?php echo esc_url( $renewal_url ); ?>"
           style="background:#2271b1;color:#fff;padding:12px 30px;text-decoration:none;border-radius:5px;font-size:16px;">
            Renew Now
        </a>
    </p>

    <p>If you don't renew:</p>
    <ul>
        <li>Pro features will be disabled (your data is safe)</li>
        <li>The free version continues to work normally</li>
        <li>You can renew at any time to reactivate</li>
    </ul>
</div>
```

---

## Invoice Generation

### HTML-to-PDF Approach

Instead of bundling a PDF library (large, complex), we generate an HTML invoice that can be:
1. Printed to PDF from the browser (Print → Save as PDF)
2. Converted server-side using DomPDF if installed

```php
class WPLP_Invoice {

    public static function generate( $order ) {
        // Invoice number: INV-YYYYMMDD-ORDERID
        $invoice_number = 'INV-' . gmdate( 'Ymd', strtotime( $order->created_at ) ) . '-' . $order->id;

        // Store invoice number on order
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'wplp_orders',
            array( 'invoice_number' => $invoice_number ),
            array( 'id' => $order->id )
        );

        return $invoice_number;
    }

    public static function render_html( $order_id ) {
        $order    = WPLP_Order::find( $order_id );
        $customer = WPLP_Customer::find( $order->customer_id );
        $settings = get_option( 'wplp_settings', array() );

        ob_start();
        include WPLP_PLUGIN_DIR . 'templates/invoices/invoice-template.php';
        return ob_get_clean();
    }
}
```

### Invoice Template

The invoice template includes all VAT-required fields:
- Your business name, address, VAT number
- Customer name, address, VAT number (if B2B)
- Sequential invoice number
- Date
- Line items with unit price, quantity, VAT rate, VAT amount
- Subtotal, VAT total, grand total
- "Reverse charge" notation for B2B EU sales
- Currency

---

## Pricing Table Shortcode

Embeddable pricing table for any page:

```php
public static function render_pricing( $atts ) {
    $atts = shortcode_atts( array(
        'product' => '',  // Product slug
    ), $atts );

    if ( empty( $atts['product'] ) ) {
        return '<p>Please specify a product slug.</p>';
    }

    global $wpdb;
    $product = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wplp_products WHERE slug = %s AND status = 'active'",
        sanitize_title( $atts['product'] )
    ));

    if ( ! $product ) return '';

    $tiers = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wplp_product_tiers WHERE product_id = %d AND status = 'active' ORDER BY sort_order",
        $product->id
    ));

    ob_start();
    include WPLP_PLUGIN_DIR . 'public/views/pricing-table.php';
    return ob_get_clean();
}
```

Usage on any page:
```
[wplp_pricing product="wp-s3-backup-pro"]
```
