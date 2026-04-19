# WP License Platform — VAT Compliance Guide

## Overview

When selling digital goods (software) to customers in the EU, UK, and certain other jurisdictions, you must charge Value Added Tax (VAT) at the customer's local rate. This guide covers the legal requirements and technical implementation.

---

## Legal Requirements

### Who Must Charge VAT on Digital Goods?

If you sell digital products (plugins, themes, SaaS) to consumers in:
- **EU countries** — you must charge VAT at the customer's country rate
- **UK** — you must charge UK VAT (20%)
- **Australia** — GST (10%) if over AUD 75,000 threshold
- **Canada** — GST/HST varies by province
- **US** — sales tax varies by state (digital goods taxability varies)

### HMRC (UK) Requirements for Digital Goods

1. **Two pieces of location evidence** — you must collect at least two non-contradictory pieces of evidence to determine the customer's country:
   - Billing address
   - IP address geolocation
   - Bank country (from payment processor)
   - Country code of SIM card (mobile)
   - Location of fixed land line

2. **Store evidence for 10 years** — HMRC requires you to keep the evidence records

3. **Apply correct rate** — charge the VAT rate of the customer's country, not yours

4. **Issue VAT invoices** — invoices must show VAT amount, rate, and your VAT number (if registered)

### EU VAT One-Stop Shop (OSS)

Instead of registering for VAT in every EU country, you can use the **OSS scheme**:
- Register in one EU country
- File a single quarterly return covering all EU sales
- Pay all EU VAT through one portal

If you're not EU-based, use the **non-Union OSS** (register in any EU country).

### B2B Reverse Charge

If an EU business customer provides a valid VAT number:
- You do NOT charge VAT
- The customer accounts for VAT themselves (reverse charge)
- You must validate the VAT number via the VIES system

---

## VAT Rates by Country

### EU Countries (as of 2024)

| Country | Code | Standard Rate |
|---------|------|--------------|
| Austria | AT | 20% |
| Belgium | BE | 21% |
| Bulgaria | BG | 20% |
| Croatia | HR | 25% |
| Cyprus | CY | 19% |
| Czech Republic | CZ | 21% |
| Denmark | DK | 25% |
| Estonia | EE | 22% |
| Finland | FI | 25.5% |
| France | FR | 20% |
| Germany | DE | 19% |
| Greece | GR | 24% |
| Hungary | HU | 27% |
| Ireland | IE | 23% |
| Italy | IT | 22% |
| Latvia | LV | 21% |
| Lithuania | LT | 21% |
| Luxembourg | LU | 17% |
| Malta | MT | 18% |
| Netherlands | NL | 21% |
| Poland | PL | 23% |
| Portugal | PT | 23% |
| Romania | RO | 19% |
| Slovakia | SK | 23% |
| Slovenia | SI | 22% |
| Spain | ES | 21% |
| Sweden | SE | 25% |

### Other Countries

| Country | Code | Rate | Notes |
|---------|------|------|-------|
| United Kingdom | GB | 20% | Post-Brexit, separate from EU |
| Norway | NO | 25% | Not EU but charges VAT on digital |
| Switzerland | CH | 8.1% | If over CHF 100,000 threshold |
| Australia | AU | 10% GST | If over AUD 75,000 threshold |
| Canada | CA | 5-15% | Varies by province (GST/HST) |
| Japan | JP | 10% | If over JPY 10M threshold |

### US Sales Tax

US sales tax on digital goods varies by state. Some states tax digital goods, others don't. For simplicity, many small sellers:
- Only collect in states where they have nexus (physical presence or economic nexus)
- Use a service like TaxJar for automated calculation
- Or don't collect US sales tax until reaching economic nexus thresholds

**For starting out:** Focus on EU/UK VAT first (mandatory), add US sales tax later if needed.

---

## Technical Implementation

### VAT Rate Lookup

```php
class WPLP_VAT {

    /**
     * EU + UK + other VAT rates.
     * Updated periodically — check rates annually.
     */
    private static $rates = array(
        // EU
        'AT' => 20,   'BE' => 21,   'BG' => 20,   'HR' => 25,
        'CY' => 19,   'CZ' => 21,   'DK' => 25,   'EE' => 22,
        'FI' => 25.5, 'FR' => 20,   'DE' => 19,   'GR' => 24,
        'HU' => 27,   'IE' => 23,   'IT' => 22,   'LV' => 21,
        'LT' => 21,   'LU' => 17,   'MT' => 18,   'NL' => 21,
        'PL' => 23,   'PT' => 23,   'RO' => 19,   'SK' => 23,
        'SI' => 22,   'ES' => 21,   'SE' => 25,
        // Non-EU
        'GB' => 20,   'NO' => 25,   'CH' => 8.1,
    );

    /**
     * EU country codes for OSS/reverse charge logic.
     */
    private static $eu_countries = array(
        'AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR',
        'DE','GR','HU','IE','IT','LV','LT','LU','MT','NL',
        'PL','PT','RO','SK','SI','ES','SE',
    );

    /**
     * Get the VAT rate for a country.
     *
     * @param string $country_code ISO 3166-1 alpha-2.
     * @return float VAT rate as percentage (e.g., 20.0), or 0 if no VAT.
     */
    public static function get_rate( $country_code ) {
        $code = strtoupper( $country_code );
        return isset( self::$rates[ $code ] ) ? self::$rates[ $code ] : 0;
    }

    /**
     * Check if a country is in the EU.
     */
    public static function is_eu_country( $country_code ) {
        return in_array( strtoupper( $country_code ), self::$eu_countries, true );
    }

    /**
     * Calculate VAT amount.
     *
     * @param float  $price        Price before tax.
     * @param string $country_code Customer's country.
     * @param string $vat_number   EU VAT number (if provided).
     * @return array{rate: float, amount: float, reverse_charge: bool}
     */
    public static function calculate( $price, $country_code, $vat_number = '' ) {
        $code = strtoupper( $country_code );

        // B2B reverse charge: valid EU VAT number = no VAT
        if ( ! empty( $vat_number ) && self::is_eu_country( $code ) ) {
            $valid = self::validate_vat_number( $vat_number );
            if ( $valid ) {
                return array(
                    'rate'           => 0,
                    'amount'         => 0,
                    'reverse_charge' => true,
                );
            }
        }

        $rate   = self::get_rate( $code );
        $amount = round( $price * ( $rate / 100 ), 2 );

        return array(
            'rate'           => $rate,
            'amount'         => $amount,
            'reverse_charge' => false,
        );
    }
}
```

### IP Geolocation

We need the customer's country from their IP address as one piece of VAT evidence. Use a free API:

```php
/**
 * Get country code from IP address.
 * Uses ip-api.com (free, no key needed, 45 requests/minute).
 *
 * @param string $ip IP address.
 * @return string|false ISO country code or false.
 */
public static function get_country_from_ip( $ip ) {
    // Check cache first
    $cached = get_transient( 'wplp_geoip_' . md5( $ip ) );
    if ( $cached ) {
        return $cached;
    }

    $response = wp_remote_get( 'http://ip-api.com/json/' . $ip . '?fields=countryCode', array(
        'timeout' => 5,
    ));

    if ( is_wp_error( $response ) ) {
        return false;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( empty( $body['countryCode'] ) ) {
        return false;
    }

    $country = strtoupper( $body['countryCode'] );

    // Cache for 24 hours
    set_transient( 'wplp_geoip_' . md5( $ip ), $country, DAY_IN_SECONDS );

    return $country;
}

/**
 * Get the customer's real IP address.
 */
public static function get_customer_ip() {
    $headers = array(
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_X_FORWARDED_FOR',  // Proxy
        'HTTP_X_REAL_IP',        // Nginx
        'REMOTE_ADDR',           // Direct
    );

    foreach ( $headers as $header ) {
        if ( ! empty( $_SERVER[ $header ] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
            // X-Forwarded-For can contain multiple IPs — take the first
            if ( strpos( $ip, ',' ) !== false ) {
                $ip = trim( explode( ',', $ip )[0] );
            }
            if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
                return $ip;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}
```

### EU VAT Number Validation (VIES)

The EU provides a free SOAP API to validate VAT numbers:

```php
/**
 * Validate an EU VAT number via the VIES system.
 *
 * @param string $vat_number Full VAT number including country prefix (e.g., DE123456789).
 * @return bool True if valid.
 */
public static function validate_vat_number( $vat_number ) {
    $vat_number = strtoupper( preg_replace( '/[^A-Z0-9]/', '', $vat_number ) );

    if ( strlen( $vat_number ) < 4 ) {
        return false;
    }

    $country = substr( $vat_number, 0, 2 );
    $number  = substr( $vat_number, 2 );

    // Use the EU VIES REST API
    $response = wp_remote_get(
        'https://ec.europa.eu/taxation_customs/vies/rest-api/ms/' . $country . '/vat/' . $number,
        array( 'timeout' => 10 )
    );

    if ( is_wp_error( $response ) ) {
        return false;
    }

    $body = json_decode( wp_remote_retrieve_body( $response ), true );
    return isset( $body['isValid'] ) && true === $body['isValid'];
}
```

### Two-Piece Evidence Storage

```php
/**
 * Store VAT evidence for an order.
 * HMRC requires at least two non-contradictory pieces.
 *
 * @param int   $order_id Order ID.
 * @param array $data     Payment/checkout data.
 */
public static function store_evidence( $order_id, $data ) {
    global $wpdb;
    $table = $wpdb->prefix . 'wplp_vat_evidence';

    // Evidence 1: Billing address country
    $billing_country = '';
    if ( ! empty( $data['billing_country'] ) ) {
        $billing_country = strtoupper( $data['billing_country'] );
        $wpdb->insert( $table, array(
            'order_id'      => $order_id,
            'evidence_type' => 'billing_address',
            'country_code'  => $billing_country,
            'raw_data'      => wp_json_encode( array(
                'country' => $billing_country,
                'source'  => 'checkout_form',
            )),
            'created_at'    => current_time( 'mysql' ),
        ));
    }

    // Evidence 2: IP geolocation
    $ip      = self::get_customer_ip();
    $ip_country = self::get_country_from_ip( $ip );
    if ( $ip_country ) {
        $wpdb->insert( $table, array(
            'order_id'      => $order_id,
            'evidence_type' => 'ip_geolocation',
            'country_code'  => $ip_country,
            'raw_data'      => wp_json_encode( array(
                'ip'      => $ip,
                'country' => $ip_country,
                'source'  => 'ip-api.com',
            )),
            'created_at'    => current_time( 'mysql' ),
        ));
    }

    // Evidence 3: PayPal payer country (if available)
    if ( ! empty( $data['payer']['address']['country_code'] ) ) {
        $paypal_country = strtoupper( $data['payer']['address']['country_code'] );
        $wpdb->insert( $table, array(
            'order_id'      => $order_id,
            'evidence_type' => 'paypal_account',
            'country_code'  => $paypal_country,
            'raw_data'      => wp_json_encode( array(
                'country' => $paypal_country,
                'source'  => 'paypal_payer_address',
            )),
            'created_at'    => current_time( 'mysql' ),
        ));
    }
}
```

### Checkout Flow with VAT

```
1. Customer selects product + tier
2. Customer enters billing country (dropdown)
3. Optional: Customer enters EU VAT number (B2B)
4. JavaScript calls AJAX endpoint to calculate VAT
5. Price updates in real-time:
   - Subtotal: $99.00
   - VAT (20% UK): $19.80
   - Total: $118.80
6. Customer clicks PayPal button
7. PayPal order created with tax breakdown
8. Payment captured
9. VAT evidence stored (billing address + IP + PayPal country)
10. Invoice generated with VAT details
```

### AJAX VAT Calculation

```php
// REST API endpoint for real-time VAT calculation
register_rest_route( 'wplp/v1', '/calculate-tax', array(
    'methods'  => 'POST',
    'callback' => function( $request ) {
        $country    = sanitize_text_field( $request->get_param( 'country' ) );
        $vat_number = sanitize_text_field( $request->get_param( 'vat_number' ) );
        $tier_id    = absint( $request->get_param( 'tier_id' ) );

        $tier  = WPLP_DB::get_tier( $tier_id );
        $price = $tier->price;

        $vat = WPLP_VAT::calculate( $price, $country, $vat_number );

        return array(
            'subtotal'       => number_format( $price, 2 ),
            'tax_rate'       => $vat['rate'],
            'tax_amount'     => number_format( $vat['amount'], 2 ),
            'total'          => number_format( $price + $vat['amount'], 2 ),
            'reverse_charge' => $vat['reverse_charge'],
            'currency'       => $tier->currency,
        );
    },
    'permission_callback' => '__return_true',
));
```

---

## Invoice Requirements

VAT invoices must include:

| Field | Required For |
|-------|-------------|
| Your business name and address | All |
| Your VAT number (if registered) | EU/UK sales |
| Customer name and address | All |
| Customer VAT number | B2B reverse charge |
| Invoice number (sequential) | All |
| Invoice date | All |
| Description of goods | All |
| Quantity | All |
| Unit price (excl. VAT) | All |
| VAT rate | EU/UK sales |
| VAT amount | EU/UK sales |
| Total (incl. VAT) | All |
| "Reverse charge" notation | B2B reverse charge |
| Currency | All |

---

## Compliance Checklist

- [ ] Collect billing country on checkout form
- [ ] Detect IP country via geolocation
- [ ] Store both as VAT evidence in database
- [ ] Apply correct VAT rate based on customer country
- [ ] Validate EU VAT numbers via VIES API
- [ ] Apply reverse charge for valid B2B EU purchases
- [ ] Generate invoices with all required fields
- [ ] Store evidence for 10 years (database retention)
- [ ] Show VAT breakdown on checkout (subtotal + VAT + total)
- [ ] Include VAT in PayPal order (tax_total in amount breakdown)
- [ ] Admin report showing VAT collected per country per quarter
