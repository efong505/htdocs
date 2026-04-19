<?php
/**
 * Encryption helper for sensitive data (PayPal credentials).
 * Same approach as WP S3 Backup — AES-256-CBC with WordPress salts.
 *
 * @package WP_License_Platform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPLP_Crypto {

	const METHOD = 'aes-256-cbc';

	private static function get_key() {
		return hash( 'sha256', AUTH_KEY . AUTH_SALT, true );
	}

	public static function encrypt( $plaintext ) {
		if ( empty( $plaintext ) || ! function_exists( 'openssl_encrypt' ) ) {
			return $plaintext;
		}
		$key    = self::get_key();
		$iv_len = openssl_cipher_iv_length( self::METHOD );
		$iv     = openssl_random_pseudo_bytes( $iv_len );
		$cipher = openssl_encrypt( $plaintext, self::METHOD, $key, OPENSSL_RAW_DATA, $iv );
		return $cipher ? base64_encode( $iv . $cipher ) : false; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	}

	public static function decrypt( $encrypted ) {
		if ( empty( $encrypted ) || ! function_exists( 'openssl_decrypt' ) ) {
			return $encrypted;
		}
		$key     = self::get_key();
		$iv_len  = openssl_cipher_iv_length( self::METHOD );
		$decoded = base64_decode( $encrypted ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( false === $decoded || strlen( $decoded ) <= $iv_len ) {
			return false;
		}
		$iv   = substr( $decoded, 0, $iv_len );
		$data = substr( $decoded, $iv_len );
		return openssl_decrypt( $data, self::METHOD, $key, OPENSSL_RAW_DATA, $iv );
	}
}
