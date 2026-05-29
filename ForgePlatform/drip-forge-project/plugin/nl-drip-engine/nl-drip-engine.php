<?php
/**
 * Plugin Name: DripForge
 * Plugin URI: https://ekewaka.com/dripforge
 * Description: Self-hosted email drip marketing automation for WordPress. Capture leads, build timed sequences, and nurture subscribers — zero SaaS fees.
 * Version: 1.2.0
 * Author: Ekewaka
 * Author URI: https://ekewaka.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nl-drip-engine
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

define('NLDE_VERSION', '1.2.0');
define('NLDE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NLDE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NLDE_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Includes
require_once NLDE_PLUGIN_DIR . 'includes/class-subscriber.php';
require_once NLDE_PLUGIN_DIR . 'includes/class-drip-sequence.php';
require_once NLDE_PLUGIN_DIR . 'includes/class-email-sender.php';
require_once NLDE_PLUGIN_DIR . 'includes/class-analytics.php';
require_once NLDE_PLUGIN_DIR . 'includes/class-cron.php';
require_once NLDE_PLUGIN_DIR . 'includes/class-templates.php';
require_once NLDE_PLUGIN_DIR . 'admin/class-admin-menu.php';
require_once NLDE_PLUGIN_DIR . 'public/class-signup-form.php';

class NL_Drip_Engine {

    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        add_action('plugins_loaded', [$this, 'init']);
    }

    public function init() {
        NLDE_Admin_Menu::instance();
        NLDE_Signup_Form::instance();
        NLDE_Cron::instance();
    }

    public function activate() {
        $this->create_tables();
        $this->set_default_options();
        NLDE_Cron::schedule_events();
        flush_rewrite_rules();
    }

    public function deactivate() {
        NLDE_Cron::clear_events();
    }

    private function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        $sql = "
        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}nlde_subscribers (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(255) NOT NULL,
            first_name VARCHAR(100) DEFAULT '',
            last_name VARCHAR(100) DEFAULT '',
            status ENUM('active','unsubscribed','bounced') DEFAULT 'active',
            ip_address VARCHAR(45) DEFAULT '',
            subscribed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            unsubscribed_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email (email),
            KEY status (status)
        ) $charset;

        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}nlde_sequences (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            description TEXT DEFAULT '',
            status ENUM('active','paused','draft') DEFAULT 'draft',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset;

        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}nlde_sequence_emails (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            sequence_id BIGINT UNSIGNED NOT NULL,
            position INT UNSIGNED NOT NULL DEFAULT 0,
            subject VARCHAR(255) NOT NULL,
            body LONGTEXT NOT NULL,
            delay_days INT UNSIGNED NOT NULL DEFAULT 0,
            status ENUM('active','paused') DEFAULT 'active',
            PRIMARY KEY (id),
            KEY sequence_id (sequence_id),
            KEY position (position)
        ) $charset;

        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}nlde_subscriber_sequences (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            subscriber_id BIGINT UNSIGNED NOT NULL,
            sequence_id BIGINT UNSIGNED NOT NULL,
            current_position INT UNSIGNED DEFAULT 0,
            status ENUM('active','completed','paused') DEFAULT 'active',
            enrolled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY sub_seq (subscriber_id, sequence_id),
            KEY status (status)
        ) $charset;

        CREATE TABLE IF NOT EXISTS {$wpdb->prefix}nlde_send_log (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            subscriber_id BIGINT UNSIGNED NOT NULL,
            sequence_email_id BIGINT UNSIGNED NOT NULL,
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            opened_at DATETIME DEFAULT NULL,
            clicked_at DATETIME DEFAULT NULL,
            status ENUM('sent','opened','clicked','failed','bounced') DEFAULT 'sent',
            tracking_hash VARCHAR(64) DEFAULT '',
            PRIMARY KEY (id),
            KEY subscriber_id (subscriber_id),
            KEY sequence_email_id (sequence_email_id),
            KEY tracking_hash (tracking_hash),
            KEY status (status)
        ) $charset;
        ";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('nlde_db_version', NLDE_VERSION);
    }

    private function set_default_options() {
        $defaults = [
            'nlde_from_name'    => get_bloginfo('name'),
            'nlde_from_email'   => get_option('admin_email'),
            'nlde_smtp_enabled' => '0',
            'nlde_smtp_host'    => '',
            'nlde_smtp_port'    => '587',
            'nlde_smtp_user'    => '',
            'nlde_smtp_pass'    => '',
            'nlde_smtp_secure'  => 'tls',
            'nlde_unsubscribe_page' => '',
        ];
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
}

NL_Drip_Engine::instance();
