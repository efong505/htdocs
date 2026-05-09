<?php
/**
 * Customer management — CRUD and WordPress user linking.
 *
 * @package WP_License_Platform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPLP_Customer {

	public static function find( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wplp_customers WHERE id = %d", $id
		) );
	}

	public static function find_by_email( $email ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wplp_customers WHERE email = %s", sanitize_email( $email )
		) );
	}

	public static function find_by_wp_user( $wp_user_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wplp_customers WHERE wp_user_id = %d", $wp_user_id
		) );
	}

	public static function find_or_create( $email, $data = array() ) {
		$existing = self::find_by_email( $email );
		if ( $existing ) {
			// Ensure WP user exists and is linked
			if ( empty( $existing->wp_user_id ) ) {
				self::ensure_wp_user( $existing, $data );
				$existing = self::find( $existing->id ); // Refresh
			}
			return $existing;
		}
		return self::create( array_merge( $data, array( 'email' => $email ) ) );
	}

	/**
	 * Ensure a WordPress user exists for a customer. Create one if missing.
	 */
	private static function ensure_wp_user( $customer, $data = array() ) {
		$email   = $customer->email;
		$wp_user = get_user_by( 'email', $email );

		if ( ! $wp_user && ! empty( $email ) ) {
			$username = self::generate_username( $email );
			$password = wp_generate_password( 16, true, true );

			$wp_user_id = wp_insert_user( array(
				'user_login' => $username,
				'user_email' => $email,
				'user_pass'  => $password,
				'first_name' => $data['first_name'] ?? $customer->first_name ?? '',
				'last_name'  => $data['last_name'] ?? $customer->last_name ?? '',
				'role'       => 'subscriber',
			) );

			if ( ! is_wp_error( $wp_user_id ) ) {
				$wp_user = get_user_by( 'id', $wp_user_id );
			}
		}

		if ( $wp_user ) {
			global $wpdb;
			$wpdb->update(
				$wpdb->prefix . 'wplp_customers',
				array( 'wp_user_id' => $wp_user->ID, 'updated_at' => current_time( 'mysql' ) ),
				array( 'id' => $customer->id )
			);

			// Send password setup email for newly created users
			if ( isset( $wp_user_id ) && ! is_wp_error( $wp_user_id ) ) {
				$reset_key = get_password_reset_key( $wp_user );
				if ( ! is_wp_error( $reset_key ) ) {
					$reset_url  = network_site_url( 'wp-login.php?action=rp&key=' . $reset_key . '&login=' . rawurlencode( $wp_user->user_login ), 'login' );
					$site_name  = get_bloginfo( 'name' );
					$portal_url = '';
					$pages = get_option( 'wplp_pages', array() );
					if ( ! empty( $pages['account'] ) ) {
						$portal_url = get_permalink( $pages['account'] );
					}

					$message  = sprintf( "Welcome to %s!\n\n", $site_name );
					$message .= "An account has been created for you to manage your licenses and downloads.\n\n";
					$message .= sprintf( "Username: %s\n", $wp_user->user_login );
					$message .= sprintf( "Set your password: %s\n\n", $reset_url );
					if ( $portal_url ) {
						$message .= sprintf( "Your account portal: %s\n\n", $portal_url );
					}
					$message .= "Thank you for your purchase!\n";

					wp_mail( $email, sprintf( '[%s] Your account details', $site_name ), $message );
				}
			}
		}
	}

	public static function create( $data ) {
		global $wpdb;

		$email   = sanitize_email( $data['email'] );
		$wp_user = get_user_by( 'email', $email );

		// Auto-create WordPress user if one doesn't exist
		if ( ! $wp_user && ! empty( $email ) ) {
			$username = self::generate_username( $email );
			$password = wp_generate_password( 16, true, true );

			$wp_user_id = wp_insert_user( array(
				'user_login'   => $username,
				'user_email'   => $email,
				'user_pass'    => $password,
				'first_name'   => sanitize_text_field( $data['first_name'] ?? '' ),
				'last_name'    => sanitize_text_field( $data['last_name'] ?? '' ),
				'role'         => 'subscriber',
			) );

			if ( ! is_wp_error( $wp_user_id ) ) {
				$wp_user = get_user_by( 'id', $wp_user_id );

				// Send password setup email
				$reset_key  = get_password_reset_key( $wp_user );
				$reset_url  = network_site_url( 'wp-login.php?action=rp&key=' . $reset_key . '&login=' . rawurlencode( $wp_user->user_login ), 'login' );
				$site_name  = get_bloginfo( 'name' );
				$portal_url = '';

				$pages = get_option( 'wplp_pages', array() );
				if ( ! empty( $pages['account'] ) ) {
					$portal_url = get_permalink( $pages['account'] );
				}

				$message  = sprintf( "Welcome to %s!\n\n", $site_name );
				$message .= "An account has been created for you so you can manage your licenses and downloads.\n\n";
				$message .= sprintf( "Username: %s\n", $wp_user->user_login );
				$message .= sprintf( "Set your password: %s\n\n", $reset_url );
				if ( $portal_url ) {
					$message .= sprintf( "Your account portal: %s\n\n", $portal_url );
				}
				$message .= "Thank you for your purchase!\n";

				wp_mail( $email, sprintf( '[%s] Your account details', $site_name ), $message );
			}
		}

		$wpdb->insert( $wpdb->prefix . 'wplp_customers', array(
			'wp_user_id'   => $wp_user ? $wp_user->ID : null,
			'email'        => $email,
			'first_name'   => sanitize_text_field( $data['first_name'] ?? '' ),
			'last_name'    => sanitize_text_field( $data['last_name'] ?? '' ),
			'company'      => sanitize_text_field( $data['company'] ?? '' ),
			'country_code' => sanitize_text_field( $data['country_code'] ?? '' ),
			'vat_number'   => sanitize_text_field( $data['vat_number'] ?? '' ),
			'created_at'   => current_time( 'mysql' ),
			'updated_at'   => current_time( 'mysql' ),
		) );

		return self::find( $wpdb->insert_id );
	}

	/**
	 * Generate a unique username from an email address.
	 */
	private static function generate_username( $email ) {
		$base = strstr( $email, '@', true );
		$base = sanitize_user( $base, true );
		if ( empty( $base ) ) {
			$base = 'customer';
		}

		$username = $base;
		$i = 1;
		while ( username_exists( $username ) ) {
			$username = $base . $i;
			$i++;
		}
		return $username;
	}

	public static function get_all( $limit = 50, $offset = 0 ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wplp_customers ORDER BY created_at DESC LIMIT %d OFFSET %d",
			$limit, $offset
		) );
	}
}
