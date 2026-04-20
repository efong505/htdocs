<?php
/**
 * Public checkout page — product selection, VAT, PayPal button.
 *
 * @package WP_License_Platform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPLP_Checkout {

	public static function init() {
		add_shortcode( 'wplp_checkout', array( __CLASS__, 'render_checkout' ) );
		add_shortcode( 'wplp_thank_you', array( __CLASS__, 'render_thank_you' ) );
		add_shortcode( 'wplp_pricing', array( __CLASS__, 'render_pricing' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
	}

	public static function enqueue_scripts() {
		if ( ! is_singular() ) {
			return;
		}

		global $post;
		if ( ! $post ) {
			return;
		}

		$has_checkout = has_shortcode( $post->post_content, 'wplp_checkout' );
		$has_pricing  = has_shortcode( $post->post_content, 'wplp_pricing' );

		$has_portal = preg_match( '/\[wplp_(portal|licenses|downloads|invoices|thank_you)/', $post->post_content );

		if ( ! $has_checkout && ! $has_pricing && ! $has_portal ) {
			return;
		}

		// CSS loads on all LicenseForge pages
		wp_enqueue_style( 'wplp-public', WPLP_PLUGIN_URL . 'public/css/public.css', array(), WPLP_VERSION );

		// PayPal SDK only loads on checkout
		if ( ! $has_checkout ) {
			return;
		}

		$settings = get_option( 'wplp_settings', array() );
		$sandbox  = ! empty( $settings['paypal_sandbox'] );

		$encrypted = get_option( 'wplp_paypal_credentials', '' );
		$client_id = '';
		if ( $encrypted ) {
			$decrypted = WPLP_Crypto::decrypt( $encrypted );
			$creds     = $decrypted ? json_decode( $decrypted, true ) : array();
			$client_id = $creds['client_id'] ?? '';
		}

		if ( empty( $client_id ) ) {
			return;
		}

		$sdk_url = 'https://www.paypal.com/sdk/js?client-id=' . $client_id . '&currency=USD&intent=capture';

		wp_enqueue_script( 'paypal-sdk', $sdk_url, array(), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_enqueue_script( 'wplp-checkout', WPLP_PLUGIN_URL . 'public/js/checkout.js', array( 'jquery', 'paypal-sdk' ), WPLP_VERSION, true );
		wp_enqueue_style( 'wplp-public', WPLP_PLUGIN_URL . 'public/css/public.css', array(), WPLP_VERSION );

		wp_localize_script( 'wplp-checkout', 'wplp_checkout', array(
			'api_url' => rest_url( 'wplp/v1/' ),
			'nonce'   => wp_create_nonce( 'wplp_checkout' ),
		) );
	}

	public static function render_checkout( $atts ) {
		$atts = shortcode_atts( array( 'product' => '' ), $atts );

		$product = null;
		$tiers   = array();

		if ( ! empty( $atts['product'] ) ) {
			$product = WPLP_DB::get_product_by_slug( sanitize_title( $atts['product'] ) );
		} else {
			$products = WPLP_DB::get_products();
			$product  = ! empty( $products ) ? $products[0] : null;
		}

		if ( ! $product ) {
			return '<p>' . esc_html__( 'No products available.', 'wp-license-platform' ) . '</p>';
		}

		$tiers = WPLP_DB::get_tiers( $product->id );
		if ( empty( $tiers ) ) {
			return '<p>' . esc_html__( 'No pricing tiers configured.', 'wp-license-platform' ) . '</p>';
		}

		$countries = WPLP_VAT::get_country_list();

		ob_start();
		include WPLP_PLUGIN_DIR . 'public/views/checkout.php';
		return ob_get_clean();
	}

	public static function render_thank_you() {
		$order_id = absint( $_GET['order'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order    = $order_id ? WPLP_Order::find( $order_id ) : null;
		$license  = null;

		if ( $order ) {
			global $wpdb;
			$license = $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}wplp_licenses WHERE order_id = %d",
				$order->id
			) );
		}

		ob_start();
		include WPLP_PLUGIN_DIR . 'public/views/thank-you.php';
		return ob_get_clean();
	}

	public static function render_pricing( $atts ) {
		$atts = shortcode_atts( array( 'product' => '' ), $atts );

		if ( empty( $atts['product'] ) ) {
			return '<p>' . esc_html__( 'Please specify a product slug.', 'wp-license-platform' ) . '</p>';
		}

		$product = WPLP_DB::get_product_by_slug( sanitize_title( $atts['product'] ) );
		if ( ! $product ) {
			return '';
		}

		$tiers       = WPLP_DB::get_tiers( $product->id );
		$checkout_url = '';

		// Find checkout page
		global $wpdb;
		$checkout_page = $wpdb->get_var(
			"SELECT ID FROM {$wpdb->posts} WHERE post_content LIKE '%[wplp_checkout%' AND post_status = 'publish' LIMIT 1"
		);
		if ( $checkout_page ) {
			$checkout_url = get_permalink( $checkout_page );
		}

		ob_start();
		include WPLP_PLUGIN_DIR . 'public/views/pricing-table.php';
		return ob_get_clean();
	}
}
