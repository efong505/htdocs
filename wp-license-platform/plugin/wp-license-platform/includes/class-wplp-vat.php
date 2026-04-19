<?php
/**
 * VAT compliance — rates, evidence collection, IP geolocation, VIES validation.
 *
 * @package WP_License_Platform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPLP_VAT {

	private static $rates = array(
		'AT' => 20,   'BE' => 21,   'BG' => 20,   'HR' => 25,
		'CY' => 19,   'CZ' => 21,   'DK' => 25,   'EE' => 22,
		'FI' => 25.5, 'FR' => 20,   'DE' => 19,   'GR' => 24,
		'HU' => 27,   'IE' => 23,   'IT' => 22,   'LV' => 21,
		'LT' => 21,   'LU' => 17,   'MT' => 18,   'NL' => 21,
		'PL' => 23,   'PT' => 23,   'RO' => 19,   'SK' => 23,
		'SI' => 22,   'ES' => 21,   'SE' => 25,
		'GB' => 20,   'NO' => 25,   'CH' => 8.1,
	);

	private static $eu_countries = array(
		'AT','BE','BG','HR','CY','CZ','DK','EE','FI','FR',
		'DE','GR','HU','IE','IT','LV','LT','LU','MT','NL',
		'PL','PT','RO','SK','SI','ES','SE',
	);

	public static function get_rate( $country_code ) {
		return self::$rates[ strtoupper( $country_code ) ] ?? 0;
	}

	public static function is_eu_country( $country_code ) {
		return in_array( strtoupper( $country_code ), self::$eu_countries, true );
	}

	public static function get_all_rates() {
		return self::$rates;
	}

	public static function calculate( $price, $country_code, $vat_number = '' ) {
		$code = strtoupper( $country_code );

		if ( ! empty( $vat_number ) && self::is_eu_country( $code ) ) {
			if ( self::validate_vat_number( $vat_number ) ) {
				return array( 'rate' => 0, 'amount' => 0, 'reverse_charge' => true );
			}
		}

		$rate   = self::get_rate( $code );
		$amount = round( $price * ( $rate / 100 ), 2 );

		return array( 'rate' => $rate, 'amount' => $amount, 'reverse_charge' => false );
	}

	public static function validate_vat_number( $vat_number ) {
		$vat_number = strtoupper( preg_replace( '/[^A-Z0-9]/', '', $vat_number ) );
		if ( strlen( $vat_number ) < 4 ) {
			return false;
		}

		$country = substr( $vat_number, 0, 2 );
		$number  = substr( $vat_number, 2 );

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

	public static function get_country_from_ip( $ip ) {
		$cached = get_transient( 'wplp_geoip_' . md5( $ip ) );
		if ( $cached ) {
			return $cached;
		}

		$response = wp_remote_get( 'http://ip-api.com/json/' . $ip . '?fields=countryCode', array(
			'timeout' => 5,
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['countryCode'] ) ) {
			return false;
		}

		$country = strtoupper( $body['countryCode'] );
		set_transient( 'wplp_geoip_' . md5( $ip ), $country, DAY_IN_SECONDS );
		return $country;
	}

	public static function get_customer_ip() {
		$headers = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR' );
		foreach ( $headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				if ( strpos( $ip, ',' ) !== false ) {
					$ip = trim( explode( ',', $ip )[0] );
				}
				if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
					return $ip;
				}
			}
		}
		return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
	}

	public static function store_evidence( $order_id, $billing_country, $ip = '' ) {
		global $wpdb;
		$table = $wpdb->prefix . 'wplp_vat_evidence';

		if ( $billing_country ) {
			$wpdb->insert( $table, array(
				'order_id'      => $order_id,
				'evidence_type' => 'billing_address',
				'country_code'  => strtoupper( $billing_country ),
				'raw_data'      => wp_json_encode( array( 'country' => $billing_country, 'source' => 'checkout_form' ) ),
				'created_at'    => current_time( 'mysql' ),
			) );
		}

		if ( empty( $ip ) ) {
			$ip = self::get_customer_ip();
		}
		$ip_country = self::get_country_from_ip( $ip );
		if ( $ip_country ) {
			$wpdb->insert( $table, array(
				'order_id'      => $order_id,
				'evidence_type' => 'ip_geolocation',
				'country_code'  => $ip_country,
				'raw_data'      => wp_json_encode( array( 'ip' => $ip, 'country' => $ip_country, 'source' => 'ip-api.com' ) ),
				'created_at'    => current_time( 'mysql' ),
			) );
		}
	}

	public static function get_country_list() {
		return array(
			'US' => 'United States', 'GB' => 'United Kingdom', 'CA' => 'Canada', 'AU' => 'Australia',
			'AT' => 'Austria', 'BE' => 'Belgium', 'BG' => 'Bulgaria', 'HR' => 'Croatia',
			'CY' => 'Cyprus', 'CZ' => 'Czech Republic', 'DK' => 'Denmark', 'EE' => 'Estonia',
			'FI' => 'Finland', 'FR' => 'France', 'DE' => 'Germany', 'GR' => 'Greece',
			'HU' => 'Hungary', 'IE' => 'Ireland', 'IT' => 'Italy', 'LV' => 'Latvia',
			'LT' => 'Lithuania', 'LU' => 'Luxembourg', 'MT' => 'Malta', 'NL' => 'Netherlands',
			'PL' => 'Poland', 'PT' => 'Portugal', 'RO' => 'Romania', 'SK' => 'Slovakia',
			'SI' => 'Slovenia', 'ES' => 'Spain', 'SE' => 'Sweden', 'NO' => 'Norway',
			'CH' => 'Switzerland', 'JP' => 'Japan', 'NZ' => 'New Zealand', 'SG' => 'Singapore',
			'BR' => 'Brazil', 'MX' => 'Mexico', 'IN' => 'India', 'KR' => 'South Korea',
		);
	}
}
