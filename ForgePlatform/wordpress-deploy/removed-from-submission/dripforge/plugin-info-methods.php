<?php
/**
 * REMOVED FROM SUBMISSION — WordPress.org provides these features automatically.
 *
 * These methods were used for self-hosted plugin info display (View Details modal,
 * custom icon in plugin list). Once on WordPress.org, the repo handles all of this
 * via the /assets/ SVN directory and the plugin API.
 *
 * To re-enable for non-WordPress.org deployments, add these back to the
 * NL_Drip_Engine::init() method and include the methods below in the class.
 */

// Add to init():
// add_filter('plugins_api', [$this, 'plugin_info'], 20, 3);
// add_filter('plugin_row_meta', [$this, 'plugin_row_meta'], 10, 2);
// add_filter('site_transient_update_plugins', [$this, 'inject_plugin_icon']);
// add_filter('transient_update_plugins', [$this, 'inject_plugin_icon']);
// add_action('admin_enqueue_scripts', [$this, 'load_thickbox']);

/*
public function load_thickbox($hook) {
    if ($hook === 'plugins.php') {
        add_thickbox();
    }
}

public function inject_plugin_icon($transient) {
    if (!is_object($transient)) return $transient;

    $plugin_file = NLDE_PLUGIN_BASENAME;
    $icon_url = NLDE_PLUGIN_URL . 'assets/icon-256x256.png';

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
        'changelog' => '<h4>1.2.0</h4><ul><li>Templates page</li><li>Guide page</li></ul><h4>1.1.0</h4><ul><li>Rebranded to DripForge</li></ul><h4>1.0.0</h4><ul><li>Initial release</li></ul>',
    ];

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

public function plugin_row_meta($links, $file) {
    if ($file === NLDE_PLUGIN_BASENAME) {
        $links[] = '<a href="' . esc_url(admin_url('plugin-install.php?tab=plugin-information&plugin=nl-drip-engine&TB_iframe=true&width=600&height=550')) . '" class="thickbox open-plugin-details-modal">View details</a>';
    }
    return $links;
}
*/
