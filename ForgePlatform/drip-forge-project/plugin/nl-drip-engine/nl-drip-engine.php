<?php
/**
 * Plugin Name: DripForge
 * Plugin URI: https://ekewaka.com/dripforge
 * Description: Self-hosted email drip marketing automation for WordPress. Capture leads, build timed sequences, and nurture subscribers — zero SaaS fees.
 * Version: 1.2.0
 * Author: Ekewaka
 * Author URI: https://ekewaka.com
 * License: GPL v2 or later
 * Text Domain: nl-drip-engine
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

        // Plugin details modal & row meta
        add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
        add_filter('plugin_row_meta', [$this, 'plugin_row_meta'], 10, 2);

        // Inject icon into plugin list
        add_filter('site_transient_update_plugins', [$this, 'inject_plugin_icon']);
        add_filter('transient_update_plugins', [$this, 'inject_plugin_icon']);

        // Ensure Thickbox is loaded on plugins page
        add_action('admin_enqueue_scripts', [$this, 'load_thickbox']);
    }

    public function load_thickbox($hook) {
        if ($hook === 'plugins.php') {
            add_thickbox();
        }
    }

    public function inject_plugin_icon($transient) {
        if (!is_object($transient)) return $transient;

        $plugin_file = NLDE_PLUGIN_BASENAME;
        $icon_url = NLDE_PLUGIN_URL . 'assets/icon-256x256.png';

        // Add to no_update list so the icon shows even without an update available
        if (!isset($transient->no_update[$plugin_file])) {
            $transient->no_update[$plugin_file] = (object) [
                'id'          => 'nl-drip-engine/nl-drip-engine.php',
                'slug'        => 'nl-drip-engine',
                'plugin'      => $plugin_file,
                'new_version' => NLDE_VERSION,
                'url'         => 'https://ekewaka.com/dripforge',
                'package'     => '',
                'icons'       => [
                    '1x'      => $icon_url,
                    '2x'      => $icon_url,
                    'default' => $icon_url,
                ],
                'banners'     => [
                    'low'     => NLDE_PLUGIN_URL . 'assets/banner-772x250.png',
                    'high'    => NLDE_PLUGIN_URL . 'assets/banner-772x250.png',
                ],
            ];
        }

        return $transient;
    }

    /**
     * Provide plugin info for the "View Details" modal
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information' || !isset($args->slug) || $args->slug !== 'nl-drip-engine') {
            return $result;
        }

        $info = new stdClass();
        $info->name          = 'DripForge';
        $info->slug          = 'nl-drip-engine';
        $info->version       = NLDE_VERSION;
        $info->author        = '<a href="https://ekewaka.com">Ekewaka</a>';
        $info->author_profile = 'https://ekewaka.com';
        $info->requires      = '5.0';
        $info->tested        = '6.7';
        $info->requires_php  = '7.4';
        $info->version       = NLDE_VERSION;
        $info->last_updated  = date('Y-m-d');
        $info->homepage      = 'https://ekewaka.com/dripforge';

        $info->sections = [
            'description' => '<h3>Self-Hosted Email Drip Marketing for WordPress</h3>'
                . '<p>DripForge lets you capture leads, build automated email sequences, and nurture subscribers — all from your WordPress dashboard with zero SaaS fees.</p>'
                . '<h4>Features</h4>'
                . '<ul>'
                . '<li>Subscriber management with search, filter, and CSV export</li>'
                . '<li>Drip sequence builder with timed email delivery</li>'
                . '<li>Merge tags for personalized emails ({first_name}, {site_name}, etc.)</li>'
                . '<li>SMTP integration (Amazon SES, SendGrid, Brevo, Gmail)</li>'
                . '<li>Open and click tracking analytics</li>'
                . '<li>Honeypot spam protection on signup forms</li>'
                . '<li>CAN-SPAM compliant unsubscribe handling</li>'
                . '<li>Shortcode-based signup forms for any page or post</li>'
                . '</ul>',
            'installation' => '<ol>'
                . '<li>Upload the <code>nl-drip-engine</code> folder to <code>/wp-content/plugins/</code></li>'
                . '<li>Activate the plugin in WP Admin → Plugins</li>'
                . '<li>Go to DripForge → Settings to configure SMTP</li>'
                . '<li>Create a sequence and add your emails</li>'
                . '<li>Use <code>[nl_signup_form sequence="your-slug"]</code> on any page</li>'
                . '</ol>',
            'changelog' => '<h4>1.2.0</h4><ul><li>WYSIWYG editor for sequence emails</li><li>Send Test Email button in settings</li><li>Send Now button per sequence email</li><li>Manual send to individual subscribers</li><li>Debug Drip Queue tool</li><li>Fixed backslash escaping on email save</li><li>Fixed edit button loading in sequence editor</li></ul><h4>1.1.0</h4><ul><li>Rebranded to DripForge (Forge Product Family)</li><li>Updated author and plugin URI</li></ul><h4>1.0.0</h4><ul><li>Initial release</li></ul>',
        ];

        // Local banner and icon
        $info->banners = [
            'low'  => NLDE_PLUGIN_URL . 'assets/banner-772x250.png',
            'high' => NLDE_PLUGIN_URL . 'assets/banner-772x250.png',
        ];
        $info->icons = [
            '1x' => NLDE_PLUGIN_URL . 'assets/icon-256x256.png',
            '2x' => NLDE_PLUGIN_URL . 'assets/icon-256x256.png',
        ];

        return $info;
    }

    /**
     * Add "View Details" link to plugin row
     */
    public function plugin_row_meta($links, $file) {
        if ($file === NLDE_PLUGIN_BASENAME) {
            $links[] = '<a href="' . esc_url(admin_url('plugin-install.php?tab=plugin-information&plugin=nl-drip-engine&TB_iframe=true&width=600&height=550')) . '" class="thickbox open-plugin-details-modal">View details</a>';
        }
        return $links;
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
