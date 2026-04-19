<?php
/**
 * Plugin Name: WP License Platform
 * Plugin URI:  https://ekewaka.com/wp-license-platform
 * Description: Sell digital products with PayPal, license key management, global VAT compliance, and a customer portal. Zero per-transaction fees.
 * Version:     1.0.0
 * Author:      Edward Fong
 * Author URI:  https://ekewaka.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-license-platform
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPLP_VERSION', '1.0.0' );
define( 'WPLP_PLUGIN_FILE', __FILE__ );
define( 'WPLP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPLP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPLP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Core
require_once WPLP_PLUGIN_DIR . 'includes/class-wplp-db.php';
require_once WPLP_PLUGIN_DIR . 'includes/class-wplp-crypto.php';
require_once WPLP_PLUGIN_DIR . 'includes/class-wplp-customer.php';
require_once WPLP_PLUGIN_DIR . 'includes/class-wplp-order.php';
require_once WPLP_PLUGIN_DIR . 'includes/class-wplp-license.php';
require_once WPLP_PLUGIN_DIR . 'includes/class-wplp-paypal.php';
require_once WPLP_PLUGIN_DIR . 'includes/class-wplp-vat.php';
require_once WPLP_PLUGIN_DIR . 'includes/class-wplp-email.php';
require_once WPLP_PLUGIN_DIR . 'includes/class-wplp-invoice.php';
require_once WPLP_PLUGIN_DIR . 'includes/class-wplp-api.php';
require_once WPLP_PLUGIN_DIR . 'includes/class-wplp-checkout.php';
require_once WPLP_PLUGIN_DIR . 'includes/class-wplp-portal.php';
require_once WPLP_PLUGIN_DIR . 'includes/class-wplp-plugin.php';

// Admin
require_once WPLP_PLUGIN_DIR . 'admin/class-wplp-admin.php';

register_activation_hook( __FILE__, array( 'WPLP_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WPLP_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'WPLP_Plugin', 'init' ) );
