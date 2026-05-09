<?php
/**
 * Handles encryption and decryption of sensitive data (AWS credentials).
 *
 * Uses AES-256-CBC with a key derived from WordPress security salts.
 * The encryption key comes from wp-config.php (AUTH_KEY + AUTH_SALT),
 * so even if the database is compromised, credentials remain encrypted.
 *
 * @package WP_S3_Backup
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPS3B_Crypto {

	const METHOD = 'aes-256-cbc';

	/**
	 * Derive a 256-bit encryption key from WordPress salts.
	 *
	 * SHA-256 produces exactly 32 bytes, which is the key size for AES-256.
	 * We combine AUTH_KEY and AUTH_SALT for additional entropy.
	 *
	 * @return string Raw 32-byte key.
	 */
	private static function get_key() {
		return hash( 'sha256', AUTH_KEY . AUTH_SALT, true );
	}

	/**
	 * Encrypt a plaintext string.
	 *
	 * Generates a random IV, encrypts with AES-256-CBC, then returns
	 * base64( IV + ciphertext ) for safe storage in wp_options.
	 *
	 * @param string $plaintext The value to encrypt.
	 * @return string|false Base64-encoded encrypted value, or false on failure.
	 */
	public static function encrypt( $plaintext ) {
		if ( empty( $plaintext ) ) {
			return '';
		}

		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return false;
		}

		$key    = self::get_key();
		$iv_len = openssl_cipher_iv_length( self::METHOD );
		$iv     = openssl_random_pseudo_bytes( $iv_len );

		$ciphertext = openssl_encrypt( $plaintext, self::METHOD, $key, OPENSSL_RAW_DATA, $iv );

		if ( false === $ciphertext ) {
			return false;
		}

		// Prepend IV to ciphertext so we can extract it during decryption
		return base64_encode( $iv . $ciphertext ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	/**
	 * Decrypt an encrypted string.
	 *
	 * Extracts the IV from the first 16 bytes, then decrypts the rest.
	 *
	 * @param string $encrypted Base64-encoded encrypted value from encrypt().
	 * @return string|false Decrypted plaintext, or false on failure.
	 */
	public static function decrypt( $encrypted ) {
		if ( empty( $encrypted ) ) {
			return '';
		}

		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return false;
		}

		$key    = self::get_key();
		$iv_len = openssl_cipher_iv_length( self::METHOD );

		$decoded = base64_decode( $encrypted ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $decoded || strlen( $decoded ) <= $iv_len ) {
			return false;
		}

		$iv         = substr( $decoded, 0, $iv_len );
		$ciphertext = substr( $decoded, $iv_len );

		return openssl_decrypt( $ciphertext, self::METHOD, $key, OPENSSL_RAW_DATA, $iv );
	}
}
