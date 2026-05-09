<?php
/**
 * Order management — creation, status updates, queries.
 *
 * @package WP_License_Platform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPLP_Order {

	public static function find( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT o.*, p.name as product_name, t.display_name as tier_name
			 FROM {$wpdb->prefix}wplp_orders o
			 LEFT JOIN {$wpdb->prefix}wplp_products p ON o.product_id = p.id
			 LEFT JOIN {$wpdb->prefix}wplp_product_tiers t ON o.tier_id = t.id
			 WHERE o.id = %d", $id
		) );
	}

	public static function find_by_paypal_order( $paypal_order_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wplp_orders WHERE paypal_order_id = %s",
			$paypal_order_id
		) );
	}

	public static function find_by_capture_id( $capture_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wplp_orders WHERE paypal_capture_id = %s",
			$capture_id
		) );
	}

	public static function generate_order_number() {
		global $wpdb;
		$date  = gmdate( 'Ymd' );
		$count = (int) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}wplp_orders WHERE order_number LIKE %s",
			'WPLP-' . $date . '-%'
		) );
		return sprintf( 'WPLP-%s-%03d', $date, $count + 1 );
	}

	public static function create( $data ) {
		global $wpdb;

		$order_number = self::generate_order_number();

		$wpdb->insert( $wpdb->prefix . 'wplp_orders', array(
			'order_number'    => $order_number,
			'customer_id'     => absint( $data['customer_id'] ?? 0 ),
			'product_id'      => absint( $data['product_id'] ),
			'tier_id'         => absint( $data['tier_id'] ),
			'status'          => 'pending',
			'subtotal'        => floatval( $data['subtotal'] ),
			'tax_amount'      => floatval( $data['tax_amount'] ?? 0 ),
			'tax_rate'        => floatval( $data['tax_rate'] ?? 0 ),
			'tax_country'     => sanitize_text_field( $data['tax_country'] ?? '' ),
			'total'           => floatval( $data['total'] ),
			'currency'        => sanitize_text_field( $data['currency'] ?? 'USD' ),
			'paypal_order_id' => sanitize_text_field( $data['paypal_order_id'] ?? '' ),
			'ip_address'      => sanitize_text_field( $data['ip_address'] ?? '' ),
			'billing_country' => sanitize_text_field( $data['billing_country'] ?? '' ),
			'created_at'      => current_time( 'mysql' ),
			'updated_at'      => current_time( 'mysql' ),
		) );

		return self::find( $wpdb->insert_id );
	}

	public static function update_status( $id, $status ) {
		global $wpdb;
		return $wpdb->update(
			$wpdb->prefix . 'wplp_orders',
			array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $id )
		);
	}

	public static function set_paypal_capture( $id, $capture_id ) {
		global $wpdb;
		return $wpdb->update(
			$wpdb->prefix . 'wplp_orders',
			array( 'paypal_capture_id' => $capture_id, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $id )
		);
	}

	public static function get_all( $status = '', $limit = 50, $offset = 0 ) {
		global $wpdb;
		$where = '';
		$args  = array();
		if ( $status ) {
			$where = 'WHERE o.status = %s';
			$args[] = $status;
		}
		$args[] = $limit;
		$args[] = $offset;

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT o.*, p.name as product_name, t.display_name as tier_name, c.email as customer_email
			 FROM {$wpdb->prefix}wplp_orders o
			 LEFT JOIN {$wpdb->prefix}wplp_products p ON o.product_id = p.id
			 LEFT JOIN {$wpdb->prefix}wplp_product_tiers t ON o.tier_id = t.id
			 LEFT JOIN {$wpdb->prefix}wplp_customers c ON o.customer_id = c.id
			 {$where}
			 ORDER BY o.created_at DESC LIMIT %d OFFSET %d",
			...$args
		) );
	}

	public static function get_customer_orders( $customer_id, $limit = 20 ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT o.*, p.name as product_name, t.display_name as tier_name
			 FROM {$wpdb->prefix}wplp_orders o
			 LEFT JOIN {$wpdb->prefix}wplp_products p ON o.product_id = p.id
			 LEFT JOIN {$wpdb->prefix}wplp_product_tiers t ON o.tier_id = t.id
			 WHERE o.customer_id = %d
			 ORDER BY o.created_at DESC LIMIT %d",
			$customer_id, $limit
		) );
	}
}
