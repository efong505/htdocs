<?php
if (!defined('ABSPATH')) exit;

class NLDE_Signup_Form {

    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('nl_signup_form', [$this, 'render_form']);
        add_action('wp_ajax_nlde_subscribe', [$this, 'handle_subscribe']);
        add_action('wp_ajax_nopriv_nlde_subscribe', [$this, 'handle_subscribe']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets() {
        global $post;
        if ($post && has_shortcode($post->post_content, 'nl_signup_form')) {
            wp_enqueue_style('nlde-public', NLDE_PLUGIN_URL . 'public/assets/nlde-public.css', [], NLDE_VERSION);
            wp_enqueue_script('nlde-public', NLDE_PLUGIN_URL . 'public/assets/nlde-public.js', ['jquery'], NLDE_VERSION, true);
            wp_localize_script('nlde-public', 'nlde_ajax', [
                'url'   => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('nlde_subscribe'),
            ]);
        }
    }

    public function render_form($atts) {
        $atts = shortcode_atts([
            'sequence'     => '',
            'button_text'  => 'Subscribe',
            'redirect'     => '',
            'show_name'    => 'yes',
            'placeholder_name'  => 'First Name',
            'placeholder_email' => 'Email Address',
            'class'        => '',
        ], $atts);

        ob_start();
        ?>
        <div class="nlde-form-wrap <?php echo esc_attr($atts['class']); ?>">
            <form class="nlde-signup-form" data-sequence="<?php echo esc_attr($atts['sequence']); ?>" data-redirect="<?php echo esc_url($atts['redirect']); ?>">
                <?php if ($atts['show_name'] === 'yes'): ?>
                <div class="nlde-field">
                    <input type="text" name="nlde_first_name" placeholder="<?php echo esc_attr($atts['placeholder_name']); ?>" class="nlde-input">
                </div>
                <?php endif; ?>
                <div class="nlde-field">
                    <input type="email" name="nlde_email" placeholder="<?php echo esc_attr($atts['placeholder_email']); ?>" required class="nlde-input">
                </div>
                <!-- Honeypot -->
                <div style="position:absolute;left:-9999px;" aria-hidden="true">
                    <input type="text" name="nlde_hp" tabindex="-1" autocomplete="off">
                </div>
                <div class="nlde-field">
                    <button type="submit" class="nlde-submit"><?php echo esc_html($atts['button_text']); ?></button>
                </div>
                <div class="nlde-message" style="display:none;"></div>
                <p class="nlde-privacy">We respect your privacy. Unsubscribe anytime.</p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_subscribe() {
        check_ajax_referer('nlde_subscribe', 'nonce');

        // Honeypot check
        if (!empty($_POST['nlde_hp'])) {
            wp_send_json_error(['message' => 'Invalid submission.']);
        }

        $email = sanitize_email($_POST['nlde_email'] ?? '');
        $first_name = sanitize_text_field($_POST['nlde_first_name'] ?? '');
        $sequence_slug = sanitize_text_field($_POST['sequence'] ?? '');

        if (!is_email($email)) {
            wp_send_json_error(['message' => 'Please enter a valid email address.']);
        }

        // Create subscriber
        $subscriber_id = NLDE_Subscriber::create([
            'email'      => $email,
            'first_name' => $first_name,
        ]);

        if (!$subscriber_id) {
            wp_send_json_error(['message' => 'Something went wrong. Please try again.']);
        }

        // Enroll in sequence
        if ($sequence_slug) {
            $sequence = NLDE_Drip_Sequence::get_by_slug($sequence_slug);
            if ($sequence && $sequence->status === 'active') {
                NLDE_Drip_Sequence::enroll_subscriber($subscriber_id, $sequence->id);
            }
        }

        wp_send_json_success(['message' => 'You\'re in! Check your email.']);
    }
}
