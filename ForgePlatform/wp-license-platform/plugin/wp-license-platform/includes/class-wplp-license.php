<?php
/**
 * License key management — generation, validation, activation, deactivation.
 *
 * @package WP_License_Platform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WPLP_License {

	public static function generate_key( $prefix = 'WPLP' ) {
		$chars  = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
		$blocks = array();
		for ( $b = 0; $b < 3; $b++ ) {
			$block = '';
			for ( $i = 0; $i < 4; $i++ ) {
				$block .= $chars[ wp_rand( 0, strlen( $chars ) - 1 ) ];
			}
			$blocks[] = $block;
		}
		$key = strtoupper( $prefix ) . '-' . implode( '-', $blocks );

		global $wpdb;
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}wplp_licenses WHERE license_key = %s", $key
		) );
		return $exists > 0 ? self::generate_key( $prefix ) : $key;
	}

	public static function create( $data ) {
		global $wpdb;

		$product = WPLP_DB::get_product( $data['product_id'] );
		$prefix  = $product ? strtoupper( substr( $product->license_prefix ?: $product->slug, 0, 5 ) ) : 'WPLP';
		$key     = self::generate_key( $prefix );

		$billing = 'annual';
		$tier    = WPLP_DB::get_tier( $data['tier_id'] );
		if ( $tier ) {
			$billing = $tier->billing_period;
		}

		$expires_at = null;
		if ( 'annual' === $billing ) {
			$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( '+1 year' ) );
		} elseif ( 'monthly' === $billing ) {
			$expires_at = gmdate( 'Y-m-d H:i:s', strtotime( '+1 month' ) );
		}
		// lifetime = null (never expires)

		if ( ! empty( $data['expires_at'] ) ) {
			$expires_at = $data['expires_at'];
		}

		$wpdb->insert( $wpdb->prefix . 'wplp_licenses', array(
			'license_key'   => $key,
			'order_id'      => absint( $data['order_id'] ),
			'customer_id'   => absint( $data['customer_id'] ),
			'product_id'    => absint( $data['product_id'] ),
			'tier_id'       => absint( $data['tier_id'] ),
			'status'        => 'active',
			'sites_allowed' => absint( $data['sites_allowed'] ?? 1 ),
			'sites_active'  => 0,
			'expires_at'    => $expires_at,
			'created_at'    => current_time( 'mysql' ),
			'updated_at'    => current_time( 'mysql' ),
		) );

		return self::find( $wpdb->insert_id );
	}

	public static function find( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wplp_licenses WHERE id = %d", $id
		) );
	}

	public static function find_by_key( $key ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wplp_licenses WHERE license_key = %s",
			sanitize_text_field( $key )
		) );
	}

	public static function activate_site( $license_id, $site_url ) {
		global $wpdb;
		$license  = self::find( $license_id );
		$site_url = esc_url_raw( $site_url );

		if ( self::is_site_activated( $license_id, $site_url ) ) {
			$wpdb->update(
				$wpdb->prefix . 'wplp_activations',
				array( 'last_checked' => current_time( 'mysql' ) ),
				array( 'license_id' => $license_id, 'site_url' => $site_url )
			);
			return true;
		}

		if ( $license->sites_allowed > 0 && $license->sites_active >= $license->sites_allowed ) {
			return new WP_Error( 'site_limit', __( 'Site activation limit reached.', 'wp-license-platform' ) );
		}

		$wpdb->insert( $wpdb->prefix . 'wplp_activations', array(
			'license_id'   => $license_id,
			'site_url'     => $site_url,
			'activated_at' => current_time( 'mysql' ),
			'last_checked' => current_time( 'mysql' ),
		) );

		$wpdb->query( $wpdb->prepare(
			"UPDATE {$wpdb->prefix}wplp_licenses SET sites_active = sites_active + 1, updated_at = %s WHERE id = %d",
			current_time( 'mysql' ), $license_id
		) );

		return true;
	}

	public static function deactivate_site( $license_id, $site_url ) {
		global $wpdb;
		$deleted = $wpdb->delete(
			$wpdb->prefix . 'wplp_activations',
			array( 'license_id' => $license_id, 'site_url' => esc_url_raw( $site_url ) )
		);
		if ( $deleted ) {
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$wpdb->prefix}wplp_licenses SET sites_active = GREATEST(sites_active - 1, 0), updated_at = %s WHERE id = %d",
				current_time( 'mysql' ), $license_id
			) );
		}
	}

	public static function is_site_activated( $license_id, $site_url ) {
		global $wpdb;
		return (bool) $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}wplp_activations WHERE license_id = %d AND site_url = %s",
			$license_id, esc_url_raw( $site_url )
		) );
	}

	public static function get_activations( $license_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}wplp_activations WHERE license_id = %d ORDER BY activated_at",
			$license_id
		) );
	}

	public static function revoke_by_order( $order_id ) {
		global $wpdb;
		$wpdb->update(
			$wpdb->prefix . 'wplp_licenses',
			array( 'status' => 'revoked', 'updated_at' => current_time( 'mysql' ) ),
			array( 'order_id' => $order_id )
		);
	}

	public static function get_customer_licenses( $customer_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT l.*, p.name as product_name, t.display_name as tier_name
			 FROM {$wpdb->prefix}wplp_licenses l
			 LEFT JOIN {$wpdb->prefix}wplp_products p ON l.product_id = p.id
			 LEFT JOIN {$wpdb->prefix}wplp_product_tiers t ON l.tier_id = t.id
			 WHERE l.customer_id = %d ORDER BY l.created_at DESC",
			$customer_id
		) );
	}

	public static function get_all( $status = '', $limit = 50, $offset = 0 ) {
		global $wpdb;
		$where = '';
		$args  = array();
		if ( $status ) {
			$where  = 'WHERE l.status = %s';
			$args[] = $status;
		}
		$args[] = $limit;
		$args[] = $offset;

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT l.*, p.name as product_name, t.display_name as tier_name, c.email as customer_email
			 FROM {$wpdb->prefix}wplp_licenses l
			 LEFT JOIN {$wpdb->prefix}wplp_products p ON l.product_id = p.id
			 LEFT JOIN {$wpdb->prefix}wplp_product_tiers t ON l.tier_id = t.id
			 LEFT JOIN {$wpdb->prefix}wplp_customers c ON l.customer_id = c.id
			 {$where}
			 ORDER BY l.created_at DESC LIMIT %d OFFSET %d",
			...$args
		) );
	}
}
