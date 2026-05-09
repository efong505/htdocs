<?php
if (!defined('ABSPATH')) exit;

class NLDE_Email_Sender {

    public static function send($to, $subject, $body, $subscriber = null) {
        $from_name  = get_option('nlde_from_name', get_bloginfo('name'));
        $from_email = get_option('nlde_from_email', get_option('admin_email'));

        // Replace merge tags
        if ($subscriber) {
            $body = self::replace_merge_tags($body, $subscriber);
            $subject = self::replace_merge_tags($subject, $subscriber);
        }

        // Wrap in HTML template
        $html_body = self::wrap_template($body, $subscriber);

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            "From: {$from_name} <{$from_email}>",
            "Reply-To: {$from_email}",
        ];

        // Configure SMTP if enabled
        if (get_option('nlde_smtp_enabled') === '1') {
            add_action('phpmailer_init', [__CLASS__, 'configure_smtp']);
        }

        $sent = wp_mail($to, $subject, $html_body, $headers);

        // Remove SMTP hook after sending
        remove_action('phpmailer_init', [__CLASS__, 'configure_smtp']);

        return $sent;
    }

    public static function send_sequence_email($pending) {
        global $wpdb;

        $tracking_hash = wp_generate_password(32, false);

        $sent = self::send(
            $pending->email,
            $pending->subject,
            $pending->body,
            (object) [
                'id'         => $pending->subscriber_id,
                'email'      => $pending->email,
                'first_name' => $pending->first_name,
                'last_name'  => $pending->last_name,
                'tracking_hash' => $tracking_hash,
            ]
        );

        // Log the send
        $wpdb->insert($wpdb->prefix . 'nlde_send_log', [
            'subscriber_id'     => $pending->subscriber_id,
            'sequence_email_id' => $pending->email_id,
            'status'            => $sent ? 'sent' : 'failed',
            'tracking_hash'     => $tracking_hash,
        ]);

        // Advance to next email in sequence
        if ($sent) {
            NLDE_Drip_Sequence::advance_subscriber($pending->subscriber_id, $pending->sequence_id);
        }

        return $sent;
    }

    public static function configure_smtp($phpmailer) {
        $phpmailer->isSMTP();
        $phpmailer->Host       = get_option('nlde_smtp_host');
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Port       = (int) get_option('nlde_smtp_port', 587);
        $phpmailer->Username   = get_option('nlde_smtp_user');
        $phpmailer->Password   = get_option('nlde_smtp_pass');
        $phpmailer->SMTPSecure = get_option('nlde_smtp_secure', 'tls');
    }

    public static function preview_replace_tags($content, $subscriber) {
        return self::replace_merge_tags($content, $subscriber);
    }

    public static function preview_wrap_template($body, $subscriber = null) {
        return self::wrap_template($body, $subscriber);
    }

    private static function replace_merge_tags($content, $subscriber) {
        $site_name = get_bloginfo('name');
        $site_url  = home_url();
        $unsub_url = add_query_arg([
            'nlde_unsubscribe' => '1',
            'email' => urlencode($subscriber->email),
            'hash'  => wp_hash($subscriber->email),
        ], home_url());

        $tags = [
            '{first_name}'       => $subscriber->first_name ?: 'there',
            '{last_name}'        => $subscriber->last_name ?? '',
            '{email}'            => $subscriber->email,
            '{site_name}'        => $site_name,
            '{site_url}'         => $site_url,
            '{unsubscribe_link}' => $unsub_url,
            '{rfp_link}'         => home_url('/request-for-proposal/'),
            '{download_link}'    => home_url('/survival-kit-download/'),
        ];

        return str_replace(array_keys($tags), array_values($tags), $content);
    }

    private static function wrap_template($body, $subscriber = null) {
        $site_name = esc_html(get_bloginfo('name'));
        $site_url  = esc_url(home_url());
        $unsub_url = '';
        if ($subscriber) {
            $unsub_url = esc_url(add_query_arg([
                'nlde_unsubscribe' => '1',
                'email' => urlencode($subscriber->email),
                'hash'  => wp_hash($subscriber->email),
            ], home_url()));
        }

        // Convert newlines to paragraphs
        $body = wpautop($body);

        // Inject tracking pixel and rewrite links for click tracking
        $tracking_hash = '';
        if ($subscriber && !empty($subscriber->tracking_hash) && $subscriber->tracking_hash !== 'preview') {
            $tracking_hash = $subscriber->tracking_hash;

            // Rewrite links for click tracking
            $body = preg_replace_callback(
                '/<a\s([^>]*?)href=["\']([^"\']+)["\']([^>]*?)>/i',
                function($matches) use ($tracking_hash) {
                    $url = $matches[2];
                    // Skip unsubscribe and tracking URLs
                    if (strpos($url, 'nlde_unsubscribe') !== false || strpos($url, 'nlde_track') !== false) {
                        return $matches[0];
                    }
                    $track_url = home_url('?nlde_track_click=1&hash=' . $tracking_hash . '&url=' . urlencode($url));
                    return '<a ' . $matches[1] . 'href="' . $track_url . '"' . $matches[3] . '>';
                },
                $body
            );
        }

        $tracking_pixel = '';
        if ($tracking_hash) {
            $pixel_url = home_url('?nlde_track_open=1&hash=' . $tracking_hash);
            $tracking_pixel = '<img src="' . $pixel_url . '" width="1" height="1" style="display:none;" alt="">';
        }

        return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:20px 0;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;">
<tr><td style="background:#1a2332;padding:20px 30px;text-align:center;">
<span style="color:#ffffff;font-size:18px;font-weight:bold;">' . $site_name . '</span>
</td></tr>
<tr><td style="padding:30px;color:#333333;font-size:16px;line-height:1.6;">
' . $body . '
</td></tr>
<tr><td style="background:#f8f8f8;padding:20px 30px;text-align:center;font-size:12px;color:#999999;">
<p>' . $site_name . ' | <a href="' . $site_url . '" style="color:#0d7377;">' . $site_url . '</a></p>
' . ($unsub_url ? '<p><a href="' . $unsub_url . '" style="color:#999999;">Unsubscribe</a></p>' : '') . '
' . $tracking_pixel . '
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>';
    }
}
