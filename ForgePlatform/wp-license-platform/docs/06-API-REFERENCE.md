# WP License Platform — API Reference

## Base URL

```
https://ekewaka.com/wp-json/wplp/v1/
```

All endpoints accept JSON request bodies and return JSON responses.

---

## Public Endpoints (called by Pro plugins)

### POST /validate

Validates a license key. Called by Pro plugins daily (cached).

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| license_key | string | Yes | The license key to validate |
| site_url | string | Yes | The site URL requesting validation |
| product | string | No | Product slug for cross-reference |
| version | string | No | Plugin version for compatibility tracking |

**Success Response (200):**
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

**Invalid Response (200):**
```json
{
    "valid": false,
    "reason": "expired|revoked|suspended|not_found|site_limit_reached"
}
```

**Rate Limited (429):**
```json
{
    "code": "rate_limited",
    "message": "Too many requests. Try again in 60 seconds."
}
```

---

### POST /activate

Registers a site against a license key. Called once when the customer enters their key.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| license_key | string | Yes | The license key |
| site_url | string | Yes | The site URL to activate |
| product | string | No | Product slug |

**Success Response (200):**
```json
{
    "success": true
}
```

**Failure Response (200):**
```json
{
    "success": false,
    "reason": "invalid_license|site_limit_reached"
}
```

---

### POST /deactivate

Removes a site from a license key. Called when the customer deactivates from the plugin settings or the customer portal.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| license_key | string | Yes | The license key |
| site_url | string | Yes | The site URL to deactivate |

**Success Response (200):**
```json
{
    "success": true
}
```

---

## Checkout Endpoints (called by checkout page JavaScript)

### POST /create-order

Creates a PayPal order for checkout. Called when the customer clicks the PayPal button.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| product_id | int | Yes | Product ID |
| tier_id | int | Yes | Pricing tier ID |
| billing_country | string | Yes | ISO country code for VAT |
| vat_number | string | No | EU VAT number for reverse charge |
| nonce | string | Yes | WordPress nonce |

**Response (200):**
```json
{
    "paypal_order_id": "5O190127TN364715T",
    "order_id": 42
}
```

---

### POST /capture-order

Captures payment after PayPal approval. Called after the customer approves in the PayPal popup.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| paypal_order_id | string | Yes | PayPal order ID from create-order |
| order_id | int | Yes | Internal order ID |
| nonce | string | Yes | WordPress nonce |

**Response (200):**
```json
{
    "success": true,
    "redirect_url": "https://ekewaka.com/checkout/thank-you/?order=42",
    "license_key": "WPS3B-A7K2-M9X4-P3R8"
}
```

---

### POST /calculate-tax

Calculates VAT/tax for a given country and tier. Called in real-time as the customer selects their country.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| tier_id | int | Yes | Pricing tier ID |
| country | string | Yes | ISO country code |
| vat_number | string | No | EU VAT number |

**Response (200):**
```json
{
    "subtotal": "99.00",
    "tax_rate": 20,
    "tax_amount": "19.80",
    "total": "118.80",
    "reverse_charge": false,
    "currency": "USD"
}
```

---

## Webhook Endpoint

### POST /paypal-webhook

Receives PayPal webhook notifications. Verified using PayPal's webhook signature verification API.

This endpoint is called by PayPal, not by your plugins. It handles:
- `PAYMENT.CAPTURE.COMPLETED` — backup confirmation of successful payment
- `PAYMENT.CAPTURE.REFUNDED` — revoke license on refund
- `BILLING.SUBSCRIPTION.CANCELLED` — mark license for expiry
- `BILLING.SUBSCRIPTION.EXPIRED` — expire license

---

## Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `missing_key` | 400 | No license key provided |
| `not_found` | 200 | License key doesn't exist |
| `expired` | 200 | License has expired |
| `revoked` | 200 | License was revoked (refund) |
| `suspended` | 200 | License temporarily suspended |
| `site_limit_reached` | 200 | All site slots are in use |
| `invalid_license` | 200 | License is not active |
| `rate_limited` | 429 | Too many requests from this IP |
| `invalid_nonce` | 403 | Invalid WordPress nonce (checkout endpoints) |

---

## Authentication

- **License endpoints** (/validate, /activate, /deactivate): No authentication required — the license key itself is the credential. Rate-limited to prevent brute force.
- **Checkout endpoints** (/create-order, /capture-order, /calculate-tax): Protected by WordPress nonces.
- **Webhook endpoint** (/paypal-webhook): Verified using PayPal's webhook signature verification.
- **Admin endpoints**: Protected by WordPress capabilities (`manage_wplp`).
