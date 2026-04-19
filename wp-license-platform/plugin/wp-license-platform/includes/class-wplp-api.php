<?php
/**
 * REST API endpoints — license validation, checkout, webhooks.
 *
 * @package WP_License_Platform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPLP_API {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes() {
		$ns = 'wplp/v1';

		// License endpoints (public — called by Pro plugins)
		register_rest_route( $ns, '/validate', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'validate_license' ),
			'permission_callback' => '__return_true',
		) );
		register_rest_route( $ns, '/activate', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'activate_license' ),
			'permission_callback' => '__return_true',
		) );
		register_rest_route( $ns, '/deactivate', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'deactivate_license' ),
			'permission_callback' => '__return_true',
		) );

		// Checkout endpoints (public — called by checkout JS)
		register_rest_route( $ns, '/create-order', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'create_order' ),
			'permission_callback' => '__return_true',
		) );
		register_rest_route( $ns, '/capture-order', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'capture_order' ),
			'permission_callback' => '__return_true',
		) );
		register_rest_route( $ns, '/calculate-tax', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'calculate_tax' ),
			'permission_callback' => '__return_true',
		) );

		// PayPal webhook
		register_rest_route( $ns, '/paypal-webhook', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'paypal_webhook' ),
			'permission_callback' => '__return_true',
		) );
	}

	// ─── Rate Limiting ───────────────────────────────

	private static function check_rate_limit() {
		$ip    = WPLP_VAT::get_customer_ip();
		$key   = 'wplp_rate_' . md5( $ip );
		$count = (int) get_transient( $key );

		if ( $count >= 30 ) {
			return false;
		}

		set_transient( $key, $count + 1, MINUTE_IN_SECONDS );
		return true;
	}

	// ─── License Endpoints ───────────────────────────

	public static function validate_license( $request ) {
		if ( ! self::check_rate_limit() ) {
			return new WP_REST_Response( array( 'code' => 'rate_limited', 'message' => 'Too many requests.' ), 429 );
		}

		$key      = sanitize_text_field( $request->get_param( 'license_key' ) );
		$site_url = esc_url_raw( $request->get_param( 'site_url' ) );

		if ( empty( $key ) ) {
			return new WP_REST_Response( array( 'valid' => false, 'reason' => 'missing_key' ), 400 );
		}

		$license = WPLP_License::find_by_key( $key );
		if ( ! $license ) {
			return new WP_REST_Response( array( 'valid' => false, 'reason' => 'not_found' ), 200 );
		}

		if ( 'active' !== $license->status ) {
			return new WP_REST_Response( array( 'valid' => false, 'reason' => $license->status ), 200 );
		}

		if ( $license->expires_at && strtotime( $license->expires_at ) < time() ) {
			global $wpdb;
			$wpdb->update(
				$wpdb->prefix . 'wplp_licenses',
				array( 'status' => 'expired', 'updated_at' => current_time( 'mysql' ) ),
				array( 'id' => $license->id )
			);
			return new WP_REST_Response( array( 'valid' => false, 'reason' => 'expired', 'expires' => $license->expires_at ), 200 );
		}

		if ( ! empty( $site_url ) ) {
			$activated = WPLP_License::is_site_activated( $license->id, $site_url );
			if ( ! $activated && $license->sites_allowed > 0 && $license->sites_active >= $license->sites_allowed ) {
				return new WP_REST_Response( array(
					'valid' => false, 'reason' => 'site_limit_reached',
					'sites_allowed' => (int) $license->sites_allowed,
					'sites_active'  => (int) $license->sites_active,
				), 200 );
			}
		}

		$tier = WPLP_DB::get_tier( $license->tier_id );

		return new WP_REST_Response( array(
			'valid'         => true,
			'license_key'   => $license->license_key,
			'tier'          => $tier ? $tier->name : 'unknown',
			'sites_allowed' => (int) $license->sites_allowed,
			'sites_active'  => (int) $license->sites_active,
			'expires'       => $license->expires_at,
		), 200 );
	}

	public static function activate_license( $request ) {
		if ( ! self::check_rate_limit() ) {
			return new WP_REST_Response( array( 'success' => false, 'reason' => 'rate_limited' ), 429 );
		}

		$key      = sanitize_text_field( $request->get_param( 'license_key' ) );
		$site_url = esc_url_raw( $request->get_param( 'site_url' ) );
		$license  = WPLP_License::find_by_key( $key );

		if ( ! $license || 'active' !== $license->status ) {
			return new WP_REST_Response( array( 'success' => false, 'reason' => 'invalid_license' ), 200 );
		}

		$result = WPLP_License::activate_site( $license->id, $site_url );
		if ( is_wp_error( $result ) ) {
			return new WP_REST_Response( array( 'success' => false, 'reason' => $result->get_error_message() ), 200 );
		}

		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	public static function deactivate_license( $request ) {
		$key      = sanitize_text_field( $request->get_param( 'license_key' ) );
		$site_url = esc_url_raw( $request->get_param( 'site_url' ) );
		$license  = WPLP_License::find_by_key( $key );

		if ( ! $license ) {
			return new WP_REST_Response( array( 'success' => false, 'reason' => 'not_found' ), 200 );
		}

		WPLP_License::deactivate_site( $license->id, $site_url );
		return new WP_REST_Response( array( 'success' => true ), 200 );
	}

	// ─── Checkout Endpoints ──────────────────────────

	public static function calculate_tax( $request ) {
		$tier_id    = absint( $request->get_param( 'tier_id' ) );
		$country    = sanitize_text_field( $request->get_param( 'country' ) );
		$vat_number = sanitize_text_field( $request->get_param( 'vat_number' ) );

		$tier = WPLP_DB::get_tier( $tier_id );
		if ( ! $tier ) {
			return new WP_REST_Response( array( 'error' => 'Invalid tier.' ), 400 );
		}

		$vat = WPLP_VAT::calculate( $tier->price, $country, $vat_number );

		return new WP_REST_Response( array(
			'subtotal'       => number_format( $tier->price, 2, '.', '' ),
			'tax_rate'       => $vat['rate'],
			'tax_amount'     => number_format( $vat['amount'], 2, '.', '' ),
			'total'          => number_format( $tier->price + $vat['amount'], 2, '.', '' ),
			'reverse_charge' => $vat['reverse_charge'],
			'currency'       => $tier->currency,
		), 200 );
	}

	public static function create_order( $request ) {

		$tier_id    = absint( $request->get_param( 'tier_id' ) );
		$country    = sanitize_text_field( $request->get_param( 'billing_country' ) );
		$vat_number = sanitize_text_field( $request->get_param( 'vat_number' ) );

		$tier    = WPLP_DB::get_tier( $tier_id );
		$product = WPLP_DB::get_product( $tier->product_id );
		if ( ! $tier || ! $product ) {
			return new WP_REST_Response( array( 'error' => 'Invalid product or tier.' ), 400 );
		}

		$vat      = WPLP_VAT::calculate( $tier->price, $country, $vat_number );
		$total    = $tier->price + $vat['amount'];
		$settings = get_option( 'wplp_settings', array() );

		// Create internal order
		$order = WPLP_Order::create( array(
			'product_id'      => $product->id,
			'tier_id'         => $tier->id,
			'subtotal'        => $tier->price,
			'tax_amount'      => $vat['amount'],
			'tax_rate'        => $vat['rate'],
			'tax_country'     => $country,
			'total'           => $total,
			'currency'        => $tier->currency,
			'ip_address'      => WPLP_VAT::get_customer_ip(),
			'billing_country' => $country,
		) );

		// Create PayPal order
		$paypal  = new WPLP_PayPal();
		$payload = WPLP_PayPal::build_order_payload(
			$product->name,
			$tier->display_name,
			$tier->price,
			$vat['amount'],
			$total,
			$tier->currency,
			$order->order_number,
			home_url( '/checkout/thank-you/?order=' . $order->id ),
			home_url( '/checkout/?cancelled=1' )
		);

		$pp_order = $paypal->create_order( $payload );
		if ( is_wp_error( $pp_order ) ) {
			WPLP_Order::update_status( $order->id, 'failed' );
			return new WP_REST_Response( array( 'error' => $pp_order->get_error_message() ), 500 );
		}

		// Store PayPal order ID
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'wplp_orders',
			array( 'paypal_order_id' => $pp_order['id'] ),
			array( 'id' => $order->id )
		);

		return new WP_REST_Response( array(
			'paypal_order_id' => $pp_order['id'],
			'order_id'        => $order->id,
		), 200 );
	}

	public static function capture_order( $request ) {
		$paypal_order_id = sanitize_text_field( $request->get_param( 'paypal_order_id' ) );
		$order_id       = absint( $request->get_param( 'order_id' ) );

		$order = WPLP_Order::find( $order_id );
		if ( ! $order || $order->paypal_order_id !== $paypal_order_id ) {
			return new WP_REST_Response( array( 'error' => 'Order mismatch.' ), 400 );
		}

		$paypal = new WPLP_PayPal();
		$result = $paypal->capture_order( $paypal_order_id );
		if ( is_wp_error( $result ) ) {
			WPLP_Order::update_status( $order_id, 'failed' );
			return new WP_REST_Response( array( 'error' => $result->get_error_message() ), 500 );
		}

		// Extract capture ID and payer info
		$capture_id = $result['purchase_units'][0]['payments']['captures'][0]['id'] ?? '';
		$payer      = $result['payer'] ?? array();

		WPLP_Order::update_status( $order_id, 'completed' );
		WPLP_Order::set_paypal_capture( $order_id, $capture_id );

		// Create/find customer
		$email     = $payer['email_address'] ?? '';
		$customer  = WPLP_Customer::find_or_create( $email, array(
			'first_name'   => $payer['name']['given_name'] ?? '',
			'last_name'    => $payer['name']['surname'] ?? '',
			'country_code' => $order->billing_country,
		) );

		// Update order with customer
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'wplp_orders',
			array( 'customer_id' => $customer->id ),
			array( 'id' => $order_id )
		);

		// Create license
		$tier    = WPLP_DB::get_tier( $order->tier_id );
		$license = WPLP_License::create( array(
			'order_id'      => $order_id,
			'customer_id'   => $customer->id,
			'product_id'    => $order->product_id,
			'tier_id'       => $order->tier_id,
			'sites_allowed' => $tier ? $tier->sites_allowed : 1,
		) );

		// Store VAT evidence
		WPLP_VAT::store_evidence( $order_id, $order->billing_country );

		// Generate invoice
		WPLP_Invoice::generate_number( $order );

		// Send email
		$order = WPLP_Order::find( $order_id );
		WPLP_Email::send_purchase_confirmation( $customer, $order, $license );

		return new WP_REST_Response( array(
			'success'      => true,
			'redirect_url' => home_url( '/checkout/thank-you/?order=' . $order_id ),
			'license_key'  => $license->license_key,
		), 200 );
	}

	// ─── PayPal Webhook ──────────────────────────────

	public static function paypal_webhook( $request ) {
		$raw_body = $request->get_body();
		$headers  = $request->get_headers();

		// Flatten headers (WP REST API uppercases and prefixes them)
		$pp_headers = array();
		foreach ( $headers as $key => $values ) {
			$pp_key = strtoupper( str_replace( '_', '-', $key ) );
			$pp_headers[ $pp_key ] = is_array( $values ) ? $values[0] : $values;
		}

		$paypal = new WPLP_PayPal();
		if ( ! $paypal->verify_webhook( $pp_headers, $raw_body ) ) {
			return new WP_REST_Response( array( 'error' => 'Invalid webhook signature.' ), 403 );
		}

		$event = json_decode( $raw_body, true );
		$type  = $event['event_type'] ?? '';

		switch ( $type ) {
			case 'PAYMENT.CAPTURE.REFUNDED':
				$capture_id = $event['resource']['links'][0]['href'] ?? '';
				// Extract capture ID from refund resource
				if ( preg_match( '/captures\/([A-Z0-9]+)\/refund/', $capture_id, $m ) ) {
					$order = WPLP_Order::find_by_capture_id( $m[1] );
					if ( $order ) {
						WPLP_Order::update_status( $order->id, 'refunded' );
						WPLP_License::revoke_by_order( $order->id );
						WPLP_Email::send_refund_confirmation( $order );
					}
				}
				break;
		}

		return new WP_REST_Response( array( 'received' => true ), 200 );
	}
}
