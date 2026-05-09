<?php
if (!defined('ABSPATH')) exit;

class NLDE_Admin_Menu {

    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'register_menus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_init', [$this, 'handle_actions']);
        add_action('wp_ajax_nlde_preview_email', [$this, 'preview_email']);
        add_action('wp_ajax_nlde_send_test_email', [$this, 'send_test_email']);
        add_action('wp_ajax_nlde_debug_drip', [$this, 'debug_drip_queue']);
        add_action('wp_ajax_nlde_manual_send', [$this, 'manual_send']);
        add_action('wp_ajax_nlde_send_sequence_email_now', [$this, 'send_sequence_email_now']);
        add_action('wp_ajax_nlde_dismiss_guide', [$this, 'dismiss_guide']);
    }

    public function register_menus() {
        add_menu_page(
            'DripForge', 'DripForge', 'manage_options',
            'nlde-dashboard', [$this, 'page_dashboard'],
            'dashicons-email-alt', 30
        );
        add_submenu_page('nlde-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'nlde-dashboard', [$this, 'page_dashboard']);
        add_submenu_page('nlde-dashboard', 'Subscribers', 'Subscribers', 'manage_options', 'nlde-subscribers', [$this, 'page_subscribers']);
        add_submenu_page('nlde-dashboard', 'Sequences', 'Sequences', 'manage_options', 'nlde-sequences', [$this, 'page_sequences']);
        add_submenu_page('nlde-dashboard', 'Templates', 'Templates', 'manage_options', 'nlde-templates', [$this, 'page_templates']);
        add_submenu_page('nlde-dashboard', 'Guide', 'Guide', 'manage_options', 'nlde-guide', [$this, 'page_guide']);
        add_submenu_page('nlde-dashboard', 'Settings', 'Settings', 'manage_options', 'nlde-settings', [$this, 'page_settings']);
        add_submenu_page('nlde-dashboard', 'Activity', 'Activity', 'manage_options', 'nlde-activity', [$this, 'page_activity']);
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'nlde-') !== false) {
            wp_enqueue_style('nlde-admin', NLDE_PLUGIN_URL . 'admin/assets/nlde-admin.css', [], NLDE_VERSION);
        }

        // Inject banner into plugin details modal on plugins page
        if ($hook === 'plugins.php') {
            $banner_url = esc_url(NLDE_PLUGIN_URL . 'assets/banner-772x250.png');
            $icon_url = esc_url(NLDE_PLUGIN_URL . 'assets/icon-256x256.png');
            wp_add_inline_script('jquery', "
                jQuery(document).on('click', '[data-slug=\"nl-drip-engine\"], .open-plugin-details-modal[href*=\"nl-drip-engine\"]', function() {
                    setTimeout(function() {
                        var modal = jQuery('#TB_iframeContent');
                        if (modal.length) {
                            modal.on('load', function() {
                                var doc = this.contentDocument || this.contentWindow.document;
                                var banner = doc.querySelector('#plugin-information-title');
                                if (banner && !doc.querySelector('.nlde-banner')) {
                                    var img = doc.createElement('img');
                                    img.src = '{$banner_url}';
                                    img.className = 'nlde-banner';
                                    img.style.cssText = 'width:100%;height:auto;display:block;';
                                    banner.parentNode.insertBefore(img, banner);
                                }
                            });
                        }
                    }, 100);
                });
            ");

            // Also add icon next to plugin name in the list via CSS
            wp_add_inline_style('list-tables', "
                .plugins tr[data-plugin='nl-drip-engine/nl-drip-engine.php'] .plugin-title strong::before {
                    content: '';
                    display: inline-block;
                    width: 24px;
                    height: 24px;
                    background: url('{$icon_url}') no-repeat center center;
                    background-size: contain;
                    vertical-align: middle;
                    margin-right: 6px;
                }
            ");
        }
    }

    public function handle_actions() {
        if (!current_user_can('manage_options')) return;
        if (!isset($_POST['nlde_action'])) return;
        if (!wp_verify_nonce($_POST['nlde_nonce'] ?? '', 'nlde_admin_action')) return;

        $action = sanitize_text_field($_POST['nlde_action']);

        switch ($action) {
            case 'save_settings':
                $this->save_settings();
                break;
            case 'create_sequence':
                $this->create_sequence();
                break;
            case 'update_sequence':
                $this->update_sequence();
                break;
            case 'delete_sequence':
                $this->delete_sequence();
                break;
            case 'save_email':
                $this->save_email();
                break;
            case 'delete_email':
                $this->delete_email();
                break;
            case 'delete_subscriber':
                $this->delete_subscriber();
                break;
            case 'export_subscribers':
                NLDE_Subscriber::export_csv();
                break;
            case 'import_template':
                $this->import_template();
                break;
        }
    }

    // --- Pages ---

    public function page_dashboard() {
        $stats = NLDE_Analytics::get_overview_stats();
        $open_rate = $stats['total_sent'] > 0 ? round(($stats['total_opened'] / $stats['total_sent']) * 100, 1) : 0;
        $click_rate = $stats['total_sent'] > 0 ? round(($stats['total_clicked'] / $stats['total_sent']) * 100, 1) : 0;
        include NLDE_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    public function page_subscribers() {
        $page = max(1, (int) ($_GET['paged'] ?? 1));
        $search = sanitize_text_field($_GET['s'] ?? '');
        $status = sanitize_text_field($_GET['status'] ?? '');
        $subscribers = NLDE_Subscriber::get_all(['page' => $page, 'per_page' => 20, 'search' => $search, 'status' => $status]);
        $total = NLDE_Subscriber::count($status);
        $total_pages = ceil($total / 20);
        include NLDE_PLUGIN_DIR . 'admin/views/subscribers.php';
    }

    public function page_sequences() {
        $view = sanitize_text_field($_GET['view'] ?? 'list');
        $id = (int) ($_GET['id'] ?? 0);

        if ($view === 'edit' && $id) {
            $sequence = NLDE_Drip_Sequence::get($id);
            $emails = NLDE_Drip_Sequence::get_emails($id);
            $stats = NLDE_Analytics::get_sequence_stats($id);
            include NLDE_PLUGIN_DIR . 'admin/views/sequence-edit.php';
        } else {
            $sequences = NLDE_Drip_Sequence::get_all();
            include NLDE_PLUGIN_DIR . 'admin/views/sequences.php';
        }
    }

    public function page_templates() {
        $templates = NLDE_Templates::get_all();
        include NLDE_PLUGIN_DIR . 'admin/views/templates.php';
    }

    public function page_guide() {
        include NLDE_PLUGIN_DIR . 'admin/views/guide.php';
    }

    public function page_settings() {
        include NLDE_PLUGIN_DIR . 'admin/views/settings.php';
    }

    public function page_activity() {
        include NLDE_PLUGIN_DIR . 'admin/views/activity.php';
    }

    // --- Actions ---

    private function save_settings() {
        $fields = ['nlde_from_name', 'nlde_from_email', 'nlde_smtp_enabled', 'nlde_smtp_host', 'nlde_smtp_port', 'nlde_smtp_user', 'nlde_smtp_pass', 'nlde_smtp_secure'];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_option($field, sanitize_text_field($_POST[$field]));
            }
        }
        wp_redirect(admin_url('admin.php?page=nlde-settings&saved=1'));
        exit;
    }

    private function create_sequence() {
        $id = NLDE_Drip_Sequence::create([
            'name' => $_POST['name'] ?? 'New Sequence',
            'description' => $_POST['description'] ?? '',
            'status' => 'draft',
        ]);
        wp_redirect(admin_url('admin.php?page=nlde-sequences&view=edit&id=' . $id));
        exit;
    }

    private function update_sequence() {
        $id = (int) ($_POST['sequence_id'] ?? 0);
        if ($id) {
            NLDE_Drip_Sequence::update($id, [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'status' => $_POST['status'] ?? 'draft',
            ]);
        }
        wp_redirect(admin_url('admin.php?page=nlde-sequences&view=edit&id=' . $id . '&updated=1'));
        exit;
    }

    private function delete_sequence() {
        $id = (int) ($_POST['sequence_id'] ?? 0);
        if ($id) NLDE_Drip_Sequence::delete($id);
        wp_redirect(admin_url('admin.php?page=nlde-sequences&deleted=1'));
        exit;
    }

    private function save_email() {
        $email_id = (int) ($_POST['email_id'] ?? 0);
        $data = [
            'sequence_id' => (int) ($_POST['sequence_id'] ?? 0),
            'position'    => (int) ($_POST['position'] ?? 0),
            'subject'     => $_POST['subject'] ?? '',
            'body'        => $_POST['body'] ?? '',
            'delay_days'  => (int) ($_POST['delay_days'] ?? 0),
        ];

        if ($email_id) {
            NLDE_Drip_Sequence::update_email($email_id, $data);
        } else {
            NLDE_Drip_Sequence::add_email($data);
        }
        wp_redirect(admin_url('admin.php?page=nlde-sequences&view=edit&id=' . $data['sequence_id'] . '&updated=1'));
        exit;
    }

    private function delete_email() {
        $email_id = (int) ($_POST['email_id'] ?? 0);
        $sequence_id = (int) ($_POST['sequence_id'] ?? 0);
        if ($email_id) NLDE_Drip_Sequence::delete_email($email_id);
        wp_redirect(admin_url('admin.php?page=nlde-sequences&view=edit&id=' . $sequence_id . '&updated=1'));
        exit;
    }

    private function import_template() {
        $slug = sanitize_text_field($_POST['template_slug'] ?? '');
        $result = NLDE_Templates::import($slug);
        if (is_wp_error($result)) {
            wp_redirect(admin_url('admin.php?page=nlde-templates&error=1'));
        } else {
            wp_redirect(admin_url('admin.php?page=nlde-templates&imported=1&seq_id=' . $result));
        }
        exit;
    }

    private function delete_subscriber() {
        $id = (int) ($_POST['subscriber_id'] ?? 0);
        if ($id) NLDE_Subscriber::delete($id);
        wp_redirect(admin_url('admin.php?page=nlde-subscribers&deleted=1'));
        exit;
    }

    public function debug_drip_queue() {
        check_ajax_referer('nlde_test_email', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $pending = NLDE_Drip_Sequence::get_pending_sends();

        if (empty($pending)) {
            // Check why it's empty
            global $wpdb;
            $enrollments = $wpdb->get_results("SELECT ss.*, s.email, seq.name as seq_name, seq.status as seq_status FROM {$wpdb->prefix}nlde_subscriber_sequences ss JOIN {$wpdb->prefix}nlde_subscribers s ON ss.subscriber_id = s.id JOIN {$wpdb->prefix}nlde_sequences seq ON ss.sequence_id = seq.id ORDER BY ss.id DESC LIMIT 5");
            $emails = $wpdb->get_results("SELECT id, sequence_id, position, subject, delay_days, status FROM {$wpdb->prefix}nlde_sequence_emails ORDER BY sequence_id, position LIMIT 20");
            wp_send_json_error([
                'message' => 'No pending emails found.',
                'enrollments' => $enrollments,
                'sequence_emails' => $emails,
                'server_time' => current_time('mysql'),
            ]);
        } else {
            $sent = 0;
            foreach ($pending as $item) {
                if (NLDE_Email_Sender::send_sequence_email($item)) $sent++;
            }
            wp_send_json_success('Processed ' . count($pending) . ' pending, sent ' . $sent);
        }
    }

    public function send_test_email() {
        check_ajax_referer('nlde_test_email', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $to = sanitize_email($_POST['email'] ?? '');
        if (!is_email($to)) {
            wp_send_json_error('Invalid email address.');
        }

        $subject = 'DripForge Test Email';
        $body = 'This is a test email from <strong>DripForge</strong> on ' . esc_html(get_bloginfo('name')) . '.<br><br>If you received this, your SMTP settings are working correctly! 🎉';

        $sent = NLDE_Email_Sender::send($to, $subject, $body);

        if ($sent) {
            wp_send_json_success('Test email sent to ' . $to);
        } else {
            wp_send_json_error('Failed to send. Check your SMTP settings.');
        }
    }

    public function send_sequence_email_now() {
        check_ajax_referer('nlde_test_email', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $email_id = (int) ($_POST['email_id'] ?? 0);
        $to = sanitize_email($_POST['to'] ?? '');

        if (!$email_id || !is_email($to)) {
            wp_send_json_error('Valid email ID and recipient required.');
        }

        $email = NLDE_Drip_Sequence::get_email($email_id);
        if (!$email) {
            wp_send_json_error('Sequence email not found.');
        }

        global $wpdb;
        $subscriber = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}nlde_subscribers WHERE email = %s", $to
        ));

        $tracking_hash = wp_generate_password(32, false);

        if ($subscriber) {
            $sub_obj = (object) [
                'id' => $subscriber->id,
                'email' => $subscriber->email,
                'first_name' => $subscriber->first_name,
                'last_name' => $subscriber->last_name,
                'tracking_hash' => $tracking_hash,
            ];
        } else {
            $sub_obj = (object) [
                'id' => 0,
                'email' => $to,
                'first_name' => '',
                'last_name' => '',
                'tracking_hash' => $tracking_hash,
            ];
        }

        $sent = NLDE_Email_Sender::send($to, $email->subject, $email->body, $sub_obj);

        // Log the send so tracking works
        if ($subscriber) {
            $wpdb->insert($wpdb->prefix . 'nlde_send_log', [
                'subscriber_id'     => $subscriber->id,
                'sequence_email_id' => $email_id,
                'status'            => $sent ? 'sent' : 'failed',
                'tracking_hash'     => $tracking_hash,
            ]);
        }

        if ($sent) {
            wp_send_json_success('Sent "' . $email->subject . '" to ' . $to);
        } else {
            wp_send_json_error('Failed to send. Check SMTP settings.');
        }
    }

    public function manual_send() {
        check_ajax_referer('nlde_test_email', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $to = sanitize_email($_POST['email'] ?? '');
        $subject = sanitize_text_field(wp_unslash($_POST['subject'] ?? ''));
        $body = wp_kses_post(wp_unslash($_POST['body'] ?? ''));

        if (!is_email($to) || empty($subject)) {
            wp_send_json_error('Email and subject are required.');
        }

        $sent = NLDE_Email_Sender::send($to, $subject, $body);

        if ($sent) {
            wp_send_json_success('Sent to ' . $to);
        } else {
            wp_send_json_error('Failed to send. Check SMTP settings.');
        }
    }

    public function dismiss_guide() {
        check_ajax_referer('nlde_dismiss_guide', 'nonce');
        if (current_user_can('manage_options')) {
            update_user_meta(get_current_user_id(), 'nlde_dismiss_inline_guide', '1');
        }
        wp_send_json_success();
    }

    public function preview_email() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_ajax_referer('nlde_preview', '_wpnonce');

        $email_id = (int) ($_GET['email_id'] ?? 0);
        if (!$email_id) wp_die('Invalid email ID');

        $email = NLDE_Drip_Sequence::get_email($email_id);
        if (!$email) wp_die('Email not found');

        // Create a fake subscriber for preview
        $preview_subscriber = (object) [
            'id'            => 0,
            'email'         => get_option('admin_email'),
            'first_name'    => 'John',
            'last_name'     => 'Doe',
            'tracking_hash' => 'preview',
        ];

        // Use the email sender to build the full HTML with template
        $subject = NLDE_Email_Sender::preview_replace_tags($email->subject, $preview_subscriber);
        $body = NLDE_Email_Sender::preview_replace_tags($email->body, $preview_subscriber);
        $html = NLDE_Email_Sender::preview_wrap_template($body, $preview_subscriber);

        // Output full preview page
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        echo '<title>Preview: ' . esc_html($subject) . '</title>';
        echo '<style>body{margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;}';
        echo '.preview-bar{background:#1a2332;color:#fff;padding:12px 20px;font-size:14px;position:sticky;top:0;z-index:100;}';
        echo '.preview-bar strong{color:#0d7377;}</style></head><body>';
        echo '<div class="preview-bar">📧 <strong>Preview</strong> — Subject: <strong>' . esc_html($subject) . '</strong> &nbsp;|&nbsp; Email #' . ($email->position + 1) . ' &nbsp;|&nbsp; Day ' . $email->delay_days . ' &nbsp;|&nbsp; Merge tags replaced with sample data</div>';
        echo $html;
        echo '</body></html>';
        exit;
    }
}
