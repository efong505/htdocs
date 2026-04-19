# WP S3 Backup — Credential Security Guide

## Overview

AWS credentials (Access Key ID and Secret Access Key) are sensitive. If exposed, an attacker could access your S3 bucket. This document explains how the plugin protects credentials at every stage.

---

## Credential Lifecycle

```
User enters credentials in Settings page
            │
            ▼
Plugin validates format (AKIA... / 40-char secret)
            │
            ▼
Plugin encrypts with AES-256-CBC using WordPress salts
            │
            ▼
Encrypted blob stored in wp_options table
            │
            ▼
On backup run: decrypt in memory, use for S3 signing, discard
            │
            ▼
Settings page shows masked values (AKIA****WXYZ)
```

---

## Encryption Implementation

### Key Derivation

We derive a 256-bit encryption key from WordPress's security salts:

```php
class WPS3B_Crypto {

    private static function get_encryption_key() {
        // Combine two WordPress salts for the key
        $raw_key = AUTH_KEY . AUTH_SALT;
        // SHA-256 produces exactly 32 bytes (256 bits) for AES-256
        return hash( 'sha256', $raw_key, true );
    }
}
```

**Why WordPress salts?**
- They're unique per installation (generated during setup)
- They're stored in `wp-config.php`, not in the database
- If the database is compromised, the attacker still can't decrypt without `wp-config.php`
- They're already used by WordPress for cookie authentication

### Encryption

```php
public static function encrypt( $plaintext ) {
    if ( empty( $plaintext ) ) {
        return '';
    }

    $key    = self::get_encryption_key();
    $method = 'aes-256-cbc';
    $iv_len = openssl_cipher_iv_length( $method );
    $iv     = openssl_random_pseudo_bytes( $iv_len );

    $ciphertext = openssl_encrypt( $plaintext, $method, $key, OPENSSL_RAW_DATA, $iv );

    if ( false === $ciphertext ) {
        return false;
    }

    // Prepend IV to ciphertext and base64 encode for safe storage
    return base64_encode( $iv . $ciphertext );
}
```

### Decryption

```php
public static function decrypt( $encrypted ) {
    if ( empty( $encrypted ) ) {
        return '';
    }

    $key    = self::get_encryption_key();
    $method = 'aes-256-cbc';
    $iv_len = openssl_cipher_iv_length( $method );

    $decoded = base64_decode( $encrypted );
    if ( false === $decoded ) {
        return false;
    }

    $iv         = substr( $decoded, 0, $iv_len );
    $ciphertext = substr( $decoded, $iv_len );

    $plaintext = openssl_decrypt( $ciphertext, $method, $key, OPENSSL_RAW_DATA, $iv );

    return $plaintext;
}
```

### What's Stored in the Database

```
wp_options table:
┌─────────────────────┬──────────────────────────────────────────────┐
│ option_name          │ option_value                                 │
├─────────────────────┼──────────────────────────────────────────────┤
│ wps3b_aws_credentials│ eyJhY2Nlc3Nfa2V5IjoiYkdsa... (base64 blob) │
└─────────────────────┴──────────────────────────────────────────────┘
```

The stored value is: `base64( IV + AES-256-CBC( JSON({access_key, secret_key}) ) )`

An attacker with database access sees only the encrypted blob. Without `wp-config.php` (which contains `AUTH_KEY` and `AUTH_SALT`), they cannot decrypt it.

---

## Settings Page Security

### Input Handling

```php
// Saving credentials
public function save_credentials( $access_key, $secret_key ) {
    // Validate format
    if ( ! preg_match( '/^AKIA[A-Z0-9]{16}$/', $access_key ) ) {
        return new WP_Error( 'invalid_key', 'Invalid Access Key ID format' );
    }

    if ( strlen( $secret_key ) !== 40 ) {
        return new WP_Error( 'invalid_secret', 'Invalid Secret Access Key format' );
    }

    // Encrypt
    $encrypted = WPS3B_Crypto::encrypt( wp_json_encode( array(
        'access_key' => sanitize_text_field( $access_key ),
        'secret_key' => sanitize_text_field( $secret_key ),
    ) ) );

    // Store
    update_option( 'wps3b_aws_credentials', $encrypted );
}
```

### Display Masking

After credentials are saved, the settings page never shows the full values:

```php
public function get_masked_access_key() {
    $creds = $this->get_credentials();
    if ( ! $creds ) return '';

    $key = $creds['access_key'];
    // Show first 4 and last 4 characters: AKIA****WXYZ
    return substr( $key, 0, 4 ) . str_repeat( '*', strlen( $key ) - 8 ) . substr( $key, -4 );
}

public function get_masked_secret_key() {
    $creds = $this->get_credentials();
    if ( ! $creds ) return '';

    // Show only last 4 characters: ****abcd
    return str_repeat( '*', 36 ) . substr( $creds['secret_key'], -4 );
}
```

### Form Security

```php
// In the settings form
wp_nonce_field( 'wps3b_save_settings', 'wps3b_nonce' );

// In the save handler
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Unauthorized' );
}

if ( ! check_admin_referer( 'wps3b_save_settings', 'wps3b_nonce' ) ) {
    wp_die( 'Invalid nonce' );
}
```

### Credential Update Logic

When the settings form is submitted:
- If the access key field contains `****`, it means the user didn't change it — keep the existing encrypted value
- If the field contains a new value (no asterisks), encrypt and save the new one
- This prevents the masked display value from overwriting the real credential

```php
if ( strpos( $submitted_access_key, '****' ) === false && ! empty( $submitted_access_key ) ) {
    // User entered a new key — save it
    $this->save_credentials( $submitted_access_key, $submitted_secret_key );
} else {
    // User didn't change credentials — keep existing
}
```

---

## Clean Removal

When the plugin is uninstalled (deleted, not just deactivated), all credentials are removed:

```php
// uninstall.php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'wps3b_settings' );
delete_option( 'wps3b_aws_credentials' );
delete_option( 'wps3b_last_backup' );
delete_option( 'wps3b_last_error' );

// Remove temp directory
$temp_dir = WP_CONTENT_DIR . '/wps3b-temp';
if ( is_dir( $temp_dir ) ) {
    array_map( 'unlink', glob( $temp_dir . '/*' ) );
    rmdir( $temp_dir );
}
```

---

## Threat Model

| Threat | Mitigation |
|--------|------------|
| Database breach | Credentials encrypted with key from wp-config.php (not in DB) |
| wp-config.php exposed | Credentials still encrypted in DB; attacker needs both |
| Man-in-the-middle | All S3 traffic over HTTPS/TLS 1.2+ |
| XSS on settings page | All outputs escaped; nonce validation on forms |
| Unauthorized access | `manage_options` capability check on all endpoints |
| Credential in logs | Plugin never logs credentials; only masked values in UI |
| Credential in backups | The encrypted blob is in the DB dump, but useless without wp-config.php salts |
| Plugin update overwrites | Credentials in wp_options survive plugin updates |
| Brute force decryption | AES-256-CBC with 256-bit key; computationally infeasible |

---

## What Happens If WordPress Salts Change?

If someone regenerates the salts in `wp-config.php`:
- All existing encrypted credentials become undecryptable
- The plugin detects this (decryption returns garbage/false)
- Settings page shows "Credentials invalid — please re-enter"
- User re-enters credentials, which are encrypted with the new salts

This is the same behavior as WordPress cookies — changing salts logs everyone out.
