<?php
/**
 * Transactional email service.
 *
 * @package WP_License_Platform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPLP_Email {

	private static function send( $to, $subject, $template, $data ) {
		$data['site_name'] = get_bloginfo( 'name' );
		$data['site_url']  = get_site_url();

		ob_start();
		extract( $data ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		include WPLP_PLUGIN_DIR . 'templates/emails/' . $template . '.php';
		$body = ob_get_clean();

		$headers = array( 'Content-Type: text/html; charset=UTF-8' );
		return wp_mail( $to, $subject, $body, $headers );
	}

	public static function send_purchase_confirmation( $customer, $order, $license ) {
		self::send(
			$customer->email,
			sprintf( '[%s] Your purchase of %s', get_bloginfo( 'name' ), $order->product_name ),
			'purchase-confirmation',
			array( 'customer' => $customer, 'order' => $order, 'license' => $license )
		);
	}

	public static function send_renewal_reminder( $license, $days_remaining ) {
		$customer = WPLP_Customer::find( $license->customer_id );
		if ( ! $customer ) {
			return;
		}

		self::send(
			$customer->email,
			sprintf( '[%s] Your %s license expires in %d days', get_bloginfo( 'name' ), $license->product_name, $days_remaining ),
			'renewal-reminder',
			array( 'customer' => $customer, 'license' => $license, 'days_remaining' => $days_remaining )
		);
	}

	public static function send_license_expired( $license ) {
		$customer = WPLP_Customer::find( $license->customer_id );
		if ( ! $customer ) {
			return;
		}

		self::send(
			$customer->email,
			sprintf( '[%s] Your %s license has expired', get_bloginfo( 'name' ), $license->product_name ),
			'license-expired',
			array( 'customer' => $customer, 'license' => $license )
		);
	}

	public static function send_refund_confirmation( $order ) {
		$customer = WPLP_Customer::find( $order->customer_id );
		if ( ! $customer ) {
			return;
		}

		self::send(
			$customer->email,
			sprintf( '[%s] Refund processed for order %s', get_bloginfo( 'name' ), $order->order_number ),
			'refund-confirmation',
			array( 'customer' => $customer, 'order' => $order )
		);
	}
}
