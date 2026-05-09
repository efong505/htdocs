<?php
/**
 * Client-side AES-256-CBC backup encryption before S3 upload.
 *
 * @package WP_S3_Backup_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPS3B_Pro_Encryption {

	const CIPHER    = 'aes-256-cbc';
	const CHUNK_SIZE = 8192;

	public static function init() {
		$settings = WPS3B_Pro::get_settings();
		if ( $settings['encryption_enabled'] && ! empty( $settings['encryption_password'] ) ) {
			add_action( 'wps3b_before_upload', array( __CLASS__, 'encrypt_file' ) );
		}
	}

	/**
	 * Encrypt a file in-place before upload.
	 */
	public static function encrypt_file( $file_path ) {
		$settings = WPS3B_Pro::get_settings();
		$password = $settings['encryption_password'];
		if ( empty( $password ) || ! file_exists( $file_path ) ) {
			return;
		}

		$key = hash( 'sha256', $password, true );
		$iv  = openssl_random_pseudo_bytes( 16 );

		$input  = fopen( $file_path, 'rb' );
		$output = fopen( $file_path . '.enc', 'wb' );
		if ( ! $input || ! $output ) {
			return;
		}

		// Write IV as first 16 bytes
		fwrite( $output, $iv );

		while ( ! feof( $input ) ) {
			$chunk = fread( $input, self::CHUNK_SIZE );
			if ( false === $chunk || '' === $chunk ) {
				break;
			}
			$encrypted = openssl_encrypt( $chunk, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv );
			// Write chunk length (4 bytes) + encrypted data for proper decryption
			fwrite( $output, pack( 'N', strlen( $encrypted ) ) );
			fwrite( $output, $encrypted );
		}

		fclose( $input );
		fclose( $output );

		// Replace original with encrypted version
		unlink( $file_path );
		rename( $file_path . '.enc', $file_path );
	}

	/**
	 * Decrypt a file (used during restore).
	 */
	public static function decrypt_file( $file_path, $password ) {
		if ( empty( $password ) || ! file_exists( $file_path ) ) {
			return new WP_Error( 'wps3b_decrypt', __( 'Missing password or file.', 'wp-s3-backup-pro' ) );
		}

		$key   = hash( 'sha256', $password, true );
		$input = fopen( $file_path, 'rb' );
		if ( ! $input ) {
			return new WP_Error( 'wps3b_decrypt', __( 'Cannot open encrypted file.', 'wp-s3-backup-pro' ) );
		}

		// Read IV (first 16 bytes)
		$iv = fread( $input, 16 );
		if ( strlen( $iv ) !== 16 ) {
			fclose( $input );
			return new WP_Error( 'wps3b_decrypt', __( 'Invalid encrypted file format.', 'wp-s3-backup-pro' ) );
		}

		$output = fopen( $file_path . '.dec', 'wb' );

		while ( ! feof( $input ) ) {
			$len_data = fread( $input, 4 );
			if ( false === $len_data || strlen( $len_data ) < 4 ) {
				break;
			}
			$len   = unpack( 'N', $len_data )[1];
			$chunk = fread( $input, $len );
			$decrypted = openssl_decrypt( $chunk, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv );
			if ( false === $decrypted ) {
				fclose( $input );
				fclose( $output );
				@unlink( $file_path . '.dec' );
				return new WP_Error( 'wps3b_decrypt', __( 'Decryption failed — wrong password?', 'wp-s3-backup-pro' ) );
			}
			fwrite( $output, $decrypted );
		}

		fclose( $input );
		fclose( $output );

		unlink( $file_path );
		rename( $file_path . '.dec', $file_path );
		return true;
	}
}
