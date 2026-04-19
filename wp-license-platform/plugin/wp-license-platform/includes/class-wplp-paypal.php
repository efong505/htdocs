<?php
/**
 * PayPal REST API v2 client — OAuth, orders, captures, refunds, webhooks.
 * No SDK — direct REST calls via wp_remote_request().
 *
 * @package WP_License_Platform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPLP_PayPal {

	private $client_id;
	private $client_secret;
	private $is_sandbox;

	public function __construct( $client_id = '', $client_secret = '', $is_sandbox = true ) {
		if ( empty( $client_id ) ) {
			$settings          = get_option( 'wplp_settings', array() );
			$encrypted_creds   = get_option( 'wplp_paypal_credentials', '' );
			$this->is_sandbox  = ! empty( $settings['paypal_sandbox'] );

			if ( $encrypted_creds ) {
				$decrypted = WPLP_Crypto::decrypt( $encrypted_creds );
				$creds     = $decrypted ? json_decode( $decrypted, true ) : array();
				$this->client_id     = $creds['client_id'] ?? '';
				$this->client_secret = $creds['client_secret'] ?? '';
			}
		} else {
			$this->client_id     = $client_id;
			$this->client_secret = $client_secret;
			$this->is_sandbox    = $is_sandbox;
		}
	}

	private function get_base_url() {
		return $this->is_sandbox
			? 'https://api-m.sandbox.paypal.com'
			: 'https://api-m.paypal.com';
	}

	public function is_configured() {
		return ! empty( $this->client_id ) && ! empty( $this->client_secret );
	}

	// ─── OAuth ───────────────────────────────────────

	private function get_access_token() {
		$cached = get_transient( 'wplp_paypal_token' );
		if ( $cached ) {
			return $cached;
		}

		$response = wp_remote_post( $this->get_base_url() . '/v1/oauth2/token', array(
			'headers' => array(
				'Authorization' => 'Basic ' . base64_encode( $this->client_id . ':' . $this->client_secret ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				'Content-Type'  => 'application/x-www-form-urlencoded',
			),
			'body'    => 'grant_type=client_credentials',
			'timeout' => 30,
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['access_token'] ) ) {
			return new WP_Error( 'wplp_paypal_auth', __( 'Could not obtain PayPal access token.', 'wp-license-platform' ) );
		}

		set_transient( 'wplp_paypal_token', $body['access_token'], $body['expires_in'] - 120 );
		return $body['access_token'];
	}

	private function api_request( $method, $endpoint, $body = null ) {
		$token = $this->get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$args = array(
			'method'  => $method,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'timeout' => 30,
		);

		if ( $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $this->get_base_url() . $endpoint, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 400 ) {
			$message = isset( $data['message'] ) ? $data['message'] : 'PayPal API error';
			return new WP_Error( 'wplp_paypal_error', $message );
		}

		return $data;
	}

	// ─── Orders ──────────────────────────────────────

	public function create_order( $order_data ) {
		return $this->api_request( 'POST', '/v2/checkout/orders', $order_data );
	}

	public function capture_order( $paypal_order_id ) {
		return $this->api_request( 'POST', '/v2/checkout/orders/' . $paypal_order_id . '/capture', new stdClass() );
	}

	public function get_order( $paypal_order_id ) {
		return $this->api_request( 'GET', '/v2/checkout/orders/' . $paypal_order_id );
	}

	// ─── Refunds ─────────────────────────────────────

	public function refund_capture( $capture_id, $amount = null, $currency = 'USD', $note = '' ) {
		$body = array();
		if ( $amount ) {
			$body['amount'] = array(
				'value'         => number_format( $amount, 2, '.', '' ),
				'currency_code' => $currency,
			);
		}
		if ( $note ) {
			$body['note_to_payer'] = $note;
		}
		return $this->api_request( 'POST', '/v2/payments/captures/' . $capture_id . '/refund', $body ?: new stdClass() );
	}

	// ─── Webhooks ────────────────────────────────────

	public function verify_webhook( $headers, $raw_body ) {
		$token = $this->get_access_token();
		if ( is_wp_error( $token ) ) {
			return false;
		}

		$webhook_id = get_option( 'wplp_paypal_webhook_id', '' );
		if ( empty( $webhook_id ) ) {
			return false;
		}

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
				'webhook_id'        => $webhook_id,
				'webhook_event'     => json_decode( $raw_body, true ),
			) ),
			'timeout' => 30,
		) );

		if ( is_wp_error( $verification ) ) {
			return false;
		}

		$result = json_decode( wp_remote_retrieve_body( $verification ), true );
		return isset( $result['verification_status'] ) && 'SUCCESS' === $result['verification_status'];
	}

	// ─── Test Connection ─────────────────────────────

	public function test_connection() {
		$token = $this->get_access_token();
		if ( is_wp_error( $token ) ) {
			return $token;
		}
		return true;
	}

	// ─── Build Order Payload ─────────────────────────

	public static function build_order_payload( $product_name, $tier_name, $subtotal, $tax_amount, $total, $currency, $reference_id, $return_url, $cancel_url ) {
		return array(
			'intent'         => 'CAPTURE',
			'purchase_units' => array(
				array(
					'reference_id' => $reference_id,
					'description'  => $product_name . ' - ' . $tier_name,
					'amount'       => array(
						'currency_code' => $currency,
						'value'         => number_format( $total, 2, '.', '' ),
						'breakdown'     => array(
							'item_total' => array(
								'currency_code' => $currency,
								'value'         => number_format( $subtotal, 2, '.', '' ),
							),
							'tax_total' => array(
								'currency_code' => $currency,
								'value'         => number_format( $tax_amount, 2, '.', '' ),
							),
						),
					),
					'items' => array(
						array(
							'name'        => $product_name . ' - ' . $tier_name,
							'quantity'    => '1',
							'unit_amount' => array(
								'currency_code' => $currency,
								'value'         => number_format( $subtotal, 2, '.', '' ),
							),
							'tax' => array(
								'currency_code' => $currency,
								'value'         => number_format( $tax_amount, 2, '.', '' ),
							),
							'category' => 'DIGITAL_GOODS',
						),
					),
				),
			),
			'application_context' => array(
				'return_url'          => $return_url,
				'cancel_url'          => $cancel_url,
				'brand_name'          => get_bloginfo( 'name' ),
				'shipping_preference' => 'NO_SHIPPING',
				'user_action'         => 'PAY_NOW',
			),
		);
	}
}
