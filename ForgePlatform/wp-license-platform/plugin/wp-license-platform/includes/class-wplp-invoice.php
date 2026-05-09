<?php
/**
 * Invoice generation — sequential numbering and HTML rendering.
 *
 * @package WP_License_Platform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPLP_Invoice {

	public static function generate_number( $order ) {
		$number = 'INV-' . gmdate( 'Ymd', strtotime( $order->created_at ) ) . '-' . str_pad( $order->id, 4, '0', STR_PAD_LEFT );

		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'wplp_orders',
			array( 'invoice_number' => $number ),
			array( 'id' => $order->id )
		);

		return $number;
	}

	public static function render_html( $order_id ) {
		$order    = WPLP_Order::find( $order_id );
		$customer = WPLP_Customer::find( $order->customer_id );
		$settings = get_option( 'wplp_settings', array() );

		if ( empty( $order->invoice_number ) ) {
			self::generate_number( $order );
			$order = WPLP_Order::find( $order_id );
		}

		ob_start();
		include WPLP_PLUGIN_DIR . 'templates/invoices/invoice-template.php';
		return ob_get_clean();
	}
}
