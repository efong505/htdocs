<?php
/**
 * Plugin Name: ShieldForge
 * Plugin URI:  https://ekewaka.com/shieldforge
 * Description: Self-hosted WordPress security with firewall, brute force protection, country blocking, and file integrity monitoring — zero cloud dependency.
 * Version:     1.0.0
 * Author:      Ekewaka
 * Author URI:  https://ekewaka.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sf-security
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

define('SFS_VERSION', '1.0.0');
define('SFS_PLUGIN_FILE', __FILE__);
define('SFS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SFS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SFS_PLUGIN_BASENAME', plugin_basename(__FILE__));

require_once SFS_PLUGIN_DIR . 'includes/class-sfs-logger.php';
require_once SFS_PLUGIN_DIR . 'includes/class-sfs-blocklist.php';
require_once SFS_PLUGIN_DIR . 'includes/class-sfs-rate-limiter.php';
require_once SFS_PLUGIN_DIR . 'includes/class-sfs-login.php';
require_once SFS_PLUGIN_DIR . 'includes/class-sfs-firewall.php';
require_once SFS_PLUGIN_DIR . 'includes/class-sfs-settings.php';
require_once SFS_PLUGIN_DIR . 'includes/class-sfs-plugin.php';

register_activation_hook(__FILE__, ['SFS_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['SFS_Plugin', 'deactivate']);

add_action('plugins_loaded', ['SFS_Plugin', 'init'], 1);
