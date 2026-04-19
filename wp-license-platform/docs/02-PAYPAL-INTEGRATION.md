# WP License Platform — PayPal Integration Guide

## Overview

The platform uses PayPal's REST API v2 for payment processing. No PayPal SDK is bundled — we use direct REST calls via `wp_remote_request()`, the same approach as the S3 client in WP S3 Backup.

---

## PayPal Setup

### 1. Create a PayPal Developer Account

1. Go to [developer.paypal.com](https://developer.paypal.com)
2. Log in with your PayPal business account
3. Go to **Apps & Credentials**
4. Create a new app (or use the default sandbox app for testing)

### 2. Get API Credentials

You need two sets of credentials:

| Environment | Purpose |
|-------------|---------|
| **Sandbox** | Testing with fake money |
| **Live** | Real payments |

Each environment gives you:
- **Client ID** — public identifier (safe to expose in JavaScript)
- **Client Secret** — private key (never expose, store encrypted)

### 3. Configure Webhooks

In the PayPal developer dashboard:
1. Go to your app → **Webhooks**
2. Add webhook URL: `https://ekewaka.com/wp-json/wplp/v1/paypal-webhook`
3. Subscribe to events:
   - `CHECKOUT.ORDER.APPROVED`
   - `PAYMENT.CAPTURE.COMPLETED`
   - `PAYMENT.CAPTURE.REFUNDED`
   - `BILLING.SUBSCRIPTION.ACTIVATED`
   - `BILLING.SUBSCRIPTION.CANCELLED`
   - `BILLING.SUBSCRIPTION.EXPIRED`
   - `PAYMENT.SALE.COMPLETED` (for subscriptions)

---

## Authentication: OAuth 2.0

Every PayPal API call requires a Bearer token. The token is obtained by exchanging your Client ID + Secret.

### Get Access Token

```
POST https://api-m.sandbox.paypal.com/v1/oauth2/token
Authorization: Basic base64(client_id:client_secret)
Content-Type: application/x-www-form-urlencoded

grant_type=client_credentials
```

Response:
```json
{
    "access_token": "A21AAF...",
    "token_type": "Bearer",
    "expires_in": 32400
}
```

### PHP Implementation

```php
class WPLP_PayPal {

    private $client_id;
    private $client_secret;
    private $is_sandbox;
    private $access_token;
    private $token_expires;

    public function __construct( $client_id, $client_secret, $is_sandbox = true ) {
        $this->client_id     = $client_id;
        $this->client_secret = $client_secret;
        $this->is_sandbox    = $is_sandbox;
    }

    private function get_base_url() {
        return $this->is_sandbox
            ? 'https://api-m.sandbox.paypal.com'
            : 'https://api-m.paypal.com';
    }

    private function get_access_token() {
        // Use cached token if still valid
        if ( $this->access_token && $this->token_expires > time() ) {
            return $this->access_token;
        }

        // Also check transient cache
        $cached = get_transient( 'wplp_paypal_token' );
        if ( $cached ) {
            $this->access_token = $cached;
            return $cached;
        }

        $response = wp_remote_post( $this->get_base_url() . '/v1/oauth2/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ),
            'body'    => 'grant_type=client_credentials',
            'timeout' => 30,
        ));

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $body['access_token'] ) ) {
            return new WP_Error( 'wplp_paypal_auth', 'Could not obtain PayPal access token.' );
        }

        $this->access_token  = $body['access_token'];
        $this->token_expires = time() + ( $body['expires_in'] - 60 ); // 60s buffer

        // Cache for slightly less than expiry
        set_transient( 'wplp_paypal_token', $this->access_token, $body['expires_in'] - 120 );

        return $this->access_token;
    }
}
```

---

## Payment Flow

### Step 1: Create Order (Server-side)

When the customer clicks "Buy Now", your server creates a PayPal order:

```
POST /v2/checkout/orders
Authorization: Bearer {access_token}
Content-Type: application/json

{
    "intent": "CAPTURE",
    "purchase_units": [{
        "reference_id": "WPLP-20260415-001",
        "description": "WP S3 Backup Pro - Professional (5 sites)",
        "amount": {
            "currency_code": "USD",
            "value": "99.00",
            "breakdown": {
                "item_total": {
                    "currency_code": "USD",
                    "value": "82.50"
                },
                "tax_total": {
                    "currency_code": "USD",
                    "value": "16.50"
                }
            }
        },
        "items": [{
            "name": "WP S3 Backup Pro - Professional",
            "quantity": "1",
            "unit_amount": {
                "currency_code": "USD",
                "value": "82.50"
            },
            "tax": {
                "currency_code": "USD",
                "value": "16.50"
            },
            "category": "DIGITAL_GOODS"
        }]
    }],
    "application_context": {
        "return_url": "https://ekewaka.com/checkout/thank-you/",
        "cancel_url": "https://ekewaka.com/checkout/cancelled/",
        "brand_name": "WP S3 Backup",
        "shipping_preference": "NO_SHIPPING",
        "user_action": "PAY_NOW"
    }
}
```

### PHP Implementation

```php
public function create_order( $order_data ) {
    $token = $this->get_access_token();
    if ( is_wp_error( $token ) ) return $token;

    $response = wp_remote_post( $this->get_base_url() . '/v2/checkout/orders', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ),
        'body'    => wp_json_encode( $order_data ),
        'timeout' => 30,
    ));

    if ( is_wp_error( $response ) ) return $response;

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $body['id'] ) ) {
        return new WP_Error( 'wplp_paypal_order', 'Could not create PayPal order.' );
    }

    return $body; // Contains 'id' and 'links' (approval URL)
}
```

### Step 2: Customer Approves (Client-side)

The PayPal JavaScript SDK renders the payment button and handles the approval:

```html
<!-- On your checkout page -->
<div id="paypal-button-container"></div>

<script src="https://www.paypal.com/sdk/js?client-id=YOUR_CLIENT_ID&currency=USD"></script>
<script>
paypal.Buttons({
    createOrder: function() {
        // Call your server to create the order
        return fetch('/wp-json/wplp/v1/create-order', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                product_id: 1,
                tier_id: 2,
                nonce: wplp_checkout.nonce
            })
        })
        .then(res => res.json())
        .then(data => data.paypal_order_id);
    },
    onApprove: function(data) {
        // Call your server to capture the payment
        return fetch('/wp-json/wplp/v1/capture-order', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                paypal_order_id: data.orderID,
                nonce: wplp_checkout.nonce
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                window.location.href = data.redirect_url;
            }
        });
    },
    onError: function(err) {
        console.error('PayPal error:', err);
        alert('Payment failed. Please try again.');
    }
}).render('#paypal-button-container');
</script>
```

### Step 3: Capture Payment (Server-side)

After the customer approves, your server captures the payment:

```
POST /v2/checkout/orders/{order_id}/capture
Authorization: Bearer {access_token}
Content-Type: application/json
```

### PHP Implementation

```php
public function capture_order( $paypal_order_id ) {
    $token = $this->get_access_token();
    if ( is_wp_error( $token ) ) return $token;

    $response = wp_remote_post(
        $this->get_base_url() . '/v2/checkout/orders/' . $paypal_order_id . '/capture',
        array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ),
            'body'    => '{}',
            'timeout' => 30,
        )
    );

    if ( is_wp_error( $response ) ) return $response;

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $body['status'] ) || 'COMPLETED' !== $body['status'] ) {
        return new WP_Error( 'wplp_capture', 'Payment capture failed.' );
    }

    return $body;
}
```

### Step 4: Post-Payment Processing

After successful capture:

```php
// In your capture handler:
function handle_successful_payment( $paypal_data, $order ) {
    // 1. Update order status
    $order->update_status( 'completed' );
    $order->set_paypal_capture_id( $paypal_data['purchase_units'][0]['payments']['captures'][0]['id'] );

    // 2. Create customer (if new)
    $customer = WPLP_Customer::find_or_create( $paypal_data['payer']['email_address'], array(
        'first_name' => $paypal_data['payer']['name']['given_name'],
        'last_name'  => $paypal_data['payer']['name']['surname'],
    ));

    // 3. Generate license key
    $license = WPLP_License::create( array(
        'order_id'      => $order->id,
        'customer_id'   => $customer->id,
        'product_id'    => $order->product_id,
        'tier_id'       => $order->tier_id,
        'sites_allowed' => $order->tier->sites_allowed,
        'expires_at'    => date( 'Y-m-d H:i:s', strtotime( '+1 year' ) ),
    ));

    // 4. Store VAT evidence
    WPLP_VAT::store_evidence( $order->id, $paypal_data );

    // 5. Send confirmation email with license key
    WPLP_Email::send_purchase_confirmation( $customer, $order, $license );

    // 6. Generate invoice
    WPLP_Invoice::generate( $order );
}
```

---

## Webhook Handling

PayPal sends webhooks for asynchronous events (refunds, subscription changes).

### Webhook Verification

Every webhook must be verified to prevent spoofing:

```php
public function verify_webhook( $headers, $body ) {
    $token = $this->get_access_token();

    $verification = wp_remote_post( $this->get_base_url() . '/v1/notifications/verify-webhook-signature', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
        ),
        'body' => wp_json_encode( array(
            'auth_algo'         => $headers['PAYPAL-AUTH-ALGO'] ?? '',
            'cert_url'          => $headers['PAYPAL-CERT-URL'] ?? '',
            'transmission_id'   => $headers['PAYPAL-TRANSMISSION-ID'] ?? '',
            'transmission_sig'  => $headers['PAYPAL-TRANSMISSION-SIG'] ?? '',
            'transmission_time' => $headers['PAYPAL-TRANSMISSION-TIME'] ?? '',
            'webhook_id'        => get_option( 'wplp_paypal_webhook_id' ),
            'webhook_event'     => json_decode( $body, true ),
        )),
        'timeout' => 30,
    ));

    $result = json_decode( wp_remote_retrieve_body( $verification ), true );
    return isset( $result['verification_status'] ) && 'SUCCESS' === $result['verification_status'];
}
```

### Webhook Events

```php
public function handle_webhook( $event ) {
    switch ( $event['event_type'] ) {
        case 'PAYMENT.CAPTURE.COMPLETED':
            // Payment successful — create license (backup for if capture callback failed)
            break;

        case 'PAYMENT.CAPTURE.REFUNDED':
            // Refund processed — revoke license
            $capture_id = $event['resource']['id'];
            $order = WPLP_Order::find_by_capture_id( $capture_id );
            if ( $order ) {
                $order->update_status( 'refunded' );
                WPLP_License::revoke_by_order( $order->id );
            }
            break;

        case 'BILLING.SUBSCRIPTION.CANCELLED':
        case 'BILLING.SUBSCRIPTION.EXPIRED':
            // Subscription ended — expire license at end of current period
            break;
    }
}
```

---

## Refund Processing

### From Admin Panel

```php
public function refund_order( $order_id ) {
    $order = WPLP_Order::find( $order_id );
    $token = $this->get_access_token();

    $response = wp_remote_post(
        $this->get_base_url() . '/v2/payments/captures/' . $order->paypal_capture_id . '/refund',
        array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $token,
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'amount' => array(
                    'value'         => $order->total,
                    'currency_code' => $order->currency,
                ),
                'note_to_payer' => 'Refund for ' . $order->order_number,
            )),
            'timeout' => 30,
        )
    );

    if ( ! is_wp_error( $response ) ) {
        $order->update_status( 'refunded' );
        WPLP_License::revoke_by_order( $order->id );
        WPLP_Email::send_refund_confirmation( $order );
    }

    return $response;
}
```

---

## Sandbox Testing

### Test Accounts

PayPal sandbox provides test buyer and seller accounts:
1. Go to [developer.paypal.com](https://developer.paypal.com) → Sandbox → Accounts
2. Use the default Personal account as the buyer
3. Use the default Business account as the seller

### Test Credit Cards

PayPal sandbox accepts these test card numbers:
- Visa: `4032039317984658`
- Mastercard: `5425233430109903`
- Amex: `374245455400126`

### Switching to Live

When ready for production:
1. Change credentials from sandbox to live in plugin settings
2. Update webhook URL to use live endpoint
3. Change `is_sandbox` to `false`
4. Test with a real $1 purchase, then refund it
