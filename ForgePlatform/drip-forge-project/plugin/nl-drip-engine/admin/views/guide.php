<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap nlde-wrap">
    <h1>DripForge — Guide
        <a href="#" onclick="window.print();return false;" class="nlde-btn nlde-btn-secondary" style="margin-left:12px;font-size:13px;">🖨️ Print / Save as PDF</a>
    </h1>

    <div class="nlde-guide-toc nlde-card">
        <h2>Table of Contents</h2>
        <ol>
            <li><a href="#getting-started">Getting Started</a></li>
            <li><a href="#creating-sequence">Creating a Sequence</a></li>
            <li><a href="#writing-emails">Writing Effective Emails</a></li>
            <li><a href="#merge-tags">Using Merge Tags</a></li>
            <li><a href="#signup-forms">Embedding Signup Forms</a></li>
            <li><a href="#smtp-setup">SMTP Configuration</a></li>
            <li><a href="#analytics">Understanding Analytics</a></li>
            <li><a href="#best-practices">Best Practices</a></li>
            <li><a href="#troubleshooting">Troubleshooting</a></li>
        </ol>
    </div>

    <!-- 1. Getting Started -->
    <div class="nlde-card" id="getting-started">
        <h2>1. Getting Started</h2>
        <p>DripForge sends automated email sequences to subscribers on a timed schedule. Here's the basic flow:</p>
        <ol>
            <li><strong>Configure SMTP</strong> — Go to <a href="<?php echo admin_url('admin.php?page=nlde-settings'); ?>">Settings</a> and set up your email sending provider.</li>
            <li><strong>Create a sequence</strong> — A sequence is a series of emails sent on a schedule (e.g., Day 0, Day 2, Day 5).</li>
            <li><strong>Add emails</strong> — Write each email in the sequence with a subject, body, and delay in days.</li>
            <li><strong>Embed a signup form</strong> — Place the shortcode on any page or post.</li>
            <li><strong>Set to Active</strong> — Flip the sequence status to "Active" and subscribers start receiving emails automatically.</li>
        </ol>
    </div>

    <!-- 2. Creating a Sequence -->
    <div class="nlde-card" id="creating-sequence">
        <h2>2. Creating a Sequence</h2>
        <h3>Step by Step</h3>
        <ol>
            <li>Go to <a href="<?php echo admin_url('admin.php?page=nlde-sequences'); ?>">DripForge → Sequences</a></li>
            <li>Enter a name (e.g., "Welcome Series") and optional description</li>
            <li>Click <strong>Create Sequence</strong> — you'll be taken to the editor</li>
            <li>Add emails one at a time using the form at the bottom</li>
            <li>Set the <strong>delay in days</strong> for each email (Day 0 = immediately on signup)</li>
            <li>When ready, change the status from "Draft" to <strong>"Active"</strong></li>
        </ol>

        <h3>Understanding Delay Days</h3>
        <table class="nlde-table" style="max-width:500px;">
            <thead><tr><th>Delay</th><th>When It Sends</th></tr></thead>
            <tbody>
                <tr><td>0</td><td>Immediately after signup</td></tr>
                <tr><td>1</td><td>1 day after signup</td></tr>
                <tr><td>3</td><td>3 days after signup</td></tr>
                <tr><td>7</td><td>1 week after signup</td></tr>
            </tbody>
        </table>
        <p style="margin-top:12px;font-size:13px;color:#666;">Emails are checked every 5 minutes via WordPress cron. Actual delivery may vary by a few minutes.</p>

        <h3>Tip: Use Templates</h3>
        <p>Don't want to start from scratch? Go to <a href="<?php echo admin_url('admin.php?page=nlde-templates'); ?>">DripForge → Templates</a> and import a pre-built sequence with one click.</p>
    </div>

    <!-- 3. Writing Effective Emails -->
    <div class="nlde-card" id="writing-emails">
        <h2>3. Writing Effective Emails</h2>

        <h3>Subject Lines That Get Opened</h3>
        <ul>
            <li><strong>Be specific:</strong> "Your 3-step website checklist" beats "Newsletter #4"</li>
            <li><strong>Create curiosity:</strong> "The mistake 90% of site owners make"</li>
            <li><strong>Use their name:</strong> "{first_name}, your free guide is ready"</li>
            <li><strong>Keep it short:</strong> 6-10 words is the sweet spot for mobile</li>
            <li><strong>Avoid spam triggers:</strong> Skip ALL CAPS, excessive punctuation!!!, and words like "FREE!!!"</li>
        </ul>

        <h3>Email Body Tips</h3>
        <ul>
            <li><strong>One goal per email</strong> — don't try to do everything in one message</li>
            <li><strong>Write like a human</strong> — conversational tone, short paragraphs, no corporate speak</li>
            <li><strong>Front-load value</strong> — put the good stuff at the top, not behind a wall of text</li>
            <li><strong>Clear call to action</strong> — every email should have one obvious next step</li>
            <li><strong>HTML is supported</strong> — use &lt;a href=""&gt; for links, &lt;strong&gt; for bold</li>
        </ul>

        <h3>Recommended Sequence Pacing</h3>
        <table class="nlde-table" style="max-width:600px;">
            <thead><tr><th>Sequence Type</th><th>Emails</th><th>Spacing</th></tr></thead>
            <tbody>
                <tr><td>Welcome Series</td><td>3-5</td><td>Day 0, 2, 5, 8, 12</td></tr>
                <tr><td>Lead Magnet Follow-up</td><td>4-6</td><td>Day 0, 2, 5, 8, 12, 15</td></tr>
                <tr><td>Email Course</td><td>5-7</td><td>Daily (Day 0-6)</td></tr>
                <tr><td>Product Launch</td><td>4-5</td><td>Day 0, 3, 5, 7, 7 (urgency)</td></tr>
                <tr><td>Re-engagement</td><td>2-3</td><td>Day 0, 4, 7</td></tr>
            </tbody>
        </table>
    </div>

    <!-- 4. Merge Tags -->
    <div class="nlde-card" id="merge-tags">
        <h2>4. Using Merge Tags</h2>
        <p>Merge tags are placeholders that get replaced with real data when the email is sent.</p>
        <table class="nlde-table">
            <thead><tr><th>Tag</th><th>Replaced With</th><th>Example Output</th></tr></thead>
            <tbody>
                <tr><td><code>{first_name}</code></td><td>Subscriber's first name (or "there" if empty)</td><td>John</td></tr>
                <tr><td><code>{last_name}</code></td><td>Subscriber's last name</td><td>Doe</td></tr>
                <tr><td><code>{email}</code></td><td>Subscriber's email address</td><td>john@example.com</td></tr>
                <tr><td><code>{site_name}</code></td><td>Your WordPress site name</td><td><?php echo esc_html(get_bloginfo('name')); ?></td></tr>
                <tr><td><code>{site_url}</code></td><td>Your site URL</td><td><?php echo esc_html(home_url()); ?></td></tr>
                <tr><td><code>{unsubscribe_link}</code></td><td>One-click unsubscribe URL</td><td>(auto-generated)</td></tr>
                <tr><td><code>{rfp_link}</code></td><td>Request for Proposal page</td><td><?php echo esc_html(home_url('/request-for-proposal/')); ?></td></tr>
                <tr><td><code>{download_link}</code></td><td>Download page</td><td><?php echo esc_html(home_url('/survival-kit-download/')); ?></td></tr>
            </tbody>
        </table>
        <p style="margin-top:12px;font-size:13px;color:#666;"><strong>Tip:</strong> Always include <code>{unsubscribe_link}</code> in your emails for CAN-SPAM compliance. DripForge adds it automatically in the email footer, but you can also place it in the body.</p>
    </div>

    <!-- 5. Signup Forms -->
    <div class="nlde-card" id="signup-forms">
        <h2>5. Embedding Signup Forms</h2>

        <h3>Basic Shortcode</h3>
        <div class="nlde-merge-tags">
            <code>[nl_signup_form sequence="your-sequence-slug"]</code>
        </div>

        <h3>All Parameters</h3>
        <table class="nlde-table">
            <thead><tr><th>Parameter</th><th>Default</th><th>Description</th></tr></thead>
            <tbody>
                <tr><td><code>sequence</code></td><td>(required)</td><td>The slug of the sequence to enroll subscribers in</td></tr>
                <tr><td><code>button_text</code></td><td>Subscribe</td><td>Text on the submit button</td></tr>
                <tr><td><code>redirect</code></td><td>(none)</td><td>URL to redirect after signup</td></tr>
                <tr><td><code>show_name</code></td><td>yes</td><td>Show the first name field ("yes" or "no")</td></tr>
                <tr><td><code>placeholder_name</code></td><td>First Name</td><td>Placeholder text for name field</td></tr>
                <tr><td><code>placeholder_email</code></td><td>Email Address</td><td>Placeholder text for email field</td></tr>
                <tr><td><code>class</code></td><td>(none)</td><td>Extra CSS class on the form wrapper</td></tr>
            </tbody>
        </table>

        <h3>Examples</h3>
        <div class="nlde-merge-tags">
            <strong>Lead magnet with redirect:</strong><br>
            <code>[nl_signup_form sequence="survival-kit" button_text="Get My Free Kit" redirect="/thank-you/"]</code>
            <br><br>
            <strong>Email-only (no name field):</strong><br>
            <code>[nl_signup_form sequence="newsletter" show_name="no" button_text="Join the List"]</code>
            <br><br>
            <strong>Custom styling:</strong><br>
            <code>[nl_signup_form sequence="welcome" class="my-custom-form"]</code>
        </div>

        <h3>Where to Place Forms</h3>
        <ul>
            <li>Landing pages (dedicated signup page)</li>
            <li>Blog post footers (content upgrade)</li>
            <li>Sidebar widgets (use a Text/HTML widget)</li>
            <li>Pop-up plugins (paste shortcode into popup content)</li>
        </ul>
    </div>

    <!-- 6. SMTP Setup -->
    <div class="nlde-card" id="smtp-setup">
        <h2>6. SMTP Configuration</h2>
        <p>For reliable email delivery, configure SMTP in <a href="<?php echo admin_url('admin.php?page=nlde-settings'); ?>">Settings</a>. Without SMTP, emails go through WordPress's default <code>wp_mail()</code> which often lands in spam.</p>

        <h3>Recommended Providers</h3>
        <table class="nlde-table" style="max-width:700px;">
            <thead><tr><th>Provider</th><th>Free Tier</th><th>Host</th><th>Port</th></tr></thead>
            <tbody>
                <tr><td>Amazon SES</td><td>62,000/month (from EC2)</td><td><code>email-smtp.[region].amazonaws.com</code></td><td>587</td></tr>
                <tr><td>SendGrid</td><td>100/day</td><td><code>smtp.sendgrid.net</code></td><td>587</td></tr>
                <tr><td>Brevo</td><td>300/day</td><td><code>smtp-relay.brevo.com</code></td><td>587</td></tr>
                <tr><td>Gmail</td><td>500/day</td><td><code>smtp.gmail.com</code></td><td>587</td></tr>
            </tbody>
        </table>
        <p style="margin-top:12px;font-size:13px;color:#666;">All providers use TLS encryption on port 587. See the <a href="<?php echo admin_url('admin.php?page=nlde-settings'); ?>">Settings page</a> for step-by-step setup guides.</p>
    </div>

    <!-- 7. Analytics -->
    <div class="nlde-card" id="analytics">
        <h2>7. Understanding Analytics</h2>

        <h3>Dashboard Stats</h3>
        <ul>
            <li><strong>Active Subscribers</strong> — people currently receiving emails</li>
            <li><strong>Total Subscribers</strong> — all subscribers including unsubscribed</li>
            <li><strong>Emails Sent</strong> — total emails delivered (excludes failed)</li>
            <li><strong>Open Rate</strong> — percentage of sent emails that were opened (tracked via pixel)</li>
            <li><strong>Click Rate</strong> — percentage of sent emails where a link was clicked</li>
        </ul>

        <h3>Per-Sequence Stats</h3>
        <p>On each sequence's edit page, the Performance table shows per-email stats: sent count, opens, clicks, and open rate. Use this to identify which emails need improvement.</p>

        <h3>What's a Good Rate?</h3>
        <table class="nlde-table" style="max-width:400px;">
            <thead><tr><th>Metric</th><th>Good</th><th>Great</th></tr></thead>
            <tbody>
                <tr><td>Open Rate</td><td>20-30%</td><td>30%+</td></tr>
                <tr><td>Click Rate</td><td>2-5%</td><td>5%+</td></tr>
                <tr><td>Unsubscribe Rate</td><td>&lt; 1%</td><td>&lt; 0.5%</td></tr>
            </tbody>
        </table>
    </div>

    <!-- 8. Best Practices -->
    <div class="nlde-card" id="best-practices">
        <h2>8. Best Practices</h2>
        <ul>
            <li><strong>Always test first</strong> — Subscribe yourself and go through the full sequence before going live</li>
            <li><strong>Start with Draft</strong> — Build your entire sequence in Draft mode, review all emails, then flip to Active</li>
            <li><strong>Use Day 0 wisely</strong> — The first email (sent immediately) gets the highest open rate. Make it count.</li>
            <li><strong>Don't email too often</strong> — Daily emails burn out subscribers fast. Every 2-3 days is the sweet spot for most sequences.</li>
            <li><strong>Segment by sequence</strong> — Create different sequences for different lead magnets or audiences</li>
            <li><strong>Monitor your stats</strong> — If open rates drop below 15%, your subject lines need work. If click rates are low, your CTAs need work.</li>
            <li><strong>Clean your list</strong> — Periodically remove bounced subscribers to maintain sender reputation</li>
            <li><strong>Set up a real cron job</strong> — WordPress's built-in cron only fires on page visits. For reliable timing, set up a server cron job that hits <code><?php echo esc_html(home_url('/wp-cron.php')); ?></code> every 5 minutes.</li>
        </ul>
    </div>

    <!-- 9. Troubleshooting -->
    <div class="nlde-card" id="troubleshooting">
        <h2>9. Troubleshooting</h2>

        <h3>Emails aren't sending</h3>
        <ul>
            <li>Check that the sequence status is <strong>Active</strong> (not Draft or Paused)</li>
            <li>Check that the subscriber status is <strong>Active</strong></li>
            <li>Verify SMTP credentials in Settings — try sending a test email from another SMTP plugin</li>
            <li>Check your server's error log for SMTP connection errors</li>
            <li>Make sure WordPress cron is running — visit your site or set up a real server cron</li>
        </ul>

        <h3>Emails going to spam</h3>
        <ul>
            <li>Use a proper SMTP provider (not WordPress default mail)</li>
            <li>Set up SPF, DKIM, and DMARC records for your sending domain</li>
            <li>Avoid spam trigger words in subject lines</li>
            <li>Make sure your "From" email matches your domain</li>
        </ul>

        <h3>Subscriber signed up but isn't receiving emails</h3>
        <ul>
            <li>Check that the subscriber is enrolled in the sequence (Subscribers page → check their status)</li>
            <li>Verify the signup form shortcode has the correct <code>sequence</code> slug</li>
            <li>Check that the sequence has emails with the correct delay days</li>
        </ul>

        <h3>Open/click tracking not working</h3>
        <ul>
            <li>Open tracking uses a 1x1 pixel — some email clients block images by default</li>
            <li>Click tracking requires links in the email body — plain text URLs won't be tracked</li>
            <li>Apple Mail Privacy Protection inflates open rates (this is normal)</li>
        </ul>
    </div>
</div>
