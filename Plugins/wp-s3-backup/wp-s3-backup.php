<?php
/**
 * Plugin Name: WP S3 Backup
 * Plugin URI:  https://github.com/ekewaka/wp-s3-backup
 * Description: Automatically back up your WordPress database and files to Amazon S3. No AWS SDK required — uses direct S3 REST API with Signature V4 authentication.
 * Version:     1.0.0
 * Author:      Edward Fong
 * Author URI:  https://ekewaka.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-s3-backup
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPS3B_VERSION', '1.0.0' );
define( 'WPS3B_PLUGIN_FILE', __FILE__ );
define( 'WPS3B_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPS3B_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPS3B_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'WPS3B_TEMP_DIR', WP_CONTENT_DIR . '/wps3b-temp' );

require_once WPS3B_PLUGIN_DIR . 'includes/class-wps3b-crypto.php';
require_once WPS3B_PLUGIN_DIR . 'includes/class-wps3b-logger.php';
require_once WPS3B_PLUGIN_DIR . 'includes/class-wps3b-s3-client.php';
require_once WPS3B_PLUGIN_DIR . 'includes/class-wps3b-backup-engine.php';
require_once WPS3B_PLUGIN_DIR . 'includes/class-wps3b-backup-manager.php';
require_once WPS3B_PLUGIN_DIR . 'includes/class-wps3b-settings.php';
require_once WPS3B_PLUGIN_DIR . 'includes/class-wps3b-plugin.php';

register_activation_hook( __FILE__, array( 'WPS3B_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WPS3B_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'WPS3B_Plugin', 'init' ) );
