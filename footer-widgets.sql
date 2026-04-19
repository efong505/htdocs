-- ============================================================
-- Task 13: Set up footer widgets
-- Footer-1: About / Company Info
-- Footer-2: Quick Links
-- Footer-3: Services
-- Footer-4: Contact Info
-- ============================================================

-- Create text widget instances
-- First check what the next widget number should be
-- We'll use text widget IDs 10, 11, 12, 13

-- Footer 1: About Us
UPDATE nextl_options SET option_value = REPLACE(option_value, 
  '"widget_text";',
  '"widget_text";'
) WHERE option_name = 'sidebars_widgets';

-- Insert the text widget data
-- We need to update the widget_text option to add our new widgets
-- First get current widget_text data and add to it

-- Create/update widget_text option with our footer widgets
INSERT INTO nextl_options (option_name, option_value, autoload) 
VALUES ('widget_custom_html', 'a:5:{i:1;a:3:{s:5:\"title\";s:8:\"About Us\";s:7:\"content\";s:299:\"<p>NextLevel Web Developers is a family-owned web design and marketing company based in Albuquerque, NM. We build revenue-generating websites backed by 30+ years of business experience.</p><p><strong>Phone:</strong> 520-392-5923<br><strong>Email:</strong> info@nextlevelwebdevelopers.com</p>\";s:0:\"\";}i:2;a:3:{s:5:\"title\";s:11:\"Quick Links\";s:7:\"content\";s:380:\"<ul style=\"list-style:none;padding:0;margin:0;\"><li><a href=\"/next-level/\">Home</a></li><li><a href=\"/next-level/about/\">About</a></li><li><a href=\"/next-level/services/\">Services</a></li><li><a href=\"/next-level/combo-packages/\">Combo Packages</a></li><li><a href=\"/next-level/pricing/\">Pricing</a></li><li><a href=\"/next-level/our-work/\">Our Work</a></li><li><a href=\"/next-level/faq/\">FAQ</a></li></ul>\";s:0:\"\";}i:3;a:3:{s:5:\"title\";s:12:\"Our Services\";s:7:\"content\";s:434:\"<ul style=\"list-style:none;padding:0;margin:0;\"><li><a href=\"/next-level/combo-packages/\">All-In-One Combo Packages</a></li><li><a href=\"/next-level/wordpress-care-packages/\">WordPress Care Packages</a></li><li><a href=\"/next-level/marketing-onlineoffline-services/\">Marketing Services</a></li><li><a href=\"/next-level/content-creation/\">Content Creation</a></li><li><a href=\"/next-level/request-for-proposal/\">Request a Proposal</a></li></ul>\";s:0:\"\";}i:4;a:3:{s:5:\"title\";s:10:\"Contact Us\";s:7:\"content\";s:310:\"<p><strong>NextLevel Web Developers</strong><br>5905 Berquist PL NW<br>Albuquerque, NM 87105</p><p><strong>Phone:</strong> <a href=\"tel:5203925923\">520-392-5923</a><br><strong>Email:</strong> <a href=\"mailto:info@nextlevelwebdevelopers.com\">info@nextlevelwebdevelopers.com</a></p><p>Mon-Fri: 9am - 7pm</p>\";s:0:\"\";}s:12:\"_multiwidget\";i:1;}')
ON DUPLICATE KEY UPDATE option_value = VALUES(option_value);

-- Now assign widgets to footer areas
UPDATE nextl_options SET option_value = 'a:12:{s:19:\"wp_inactive_widgets\";a:10:{i:0;s:7:\"pages-3\";i:1;s:10:\"archives-5\";i:2;s:14:\"recent-posts-5\";i:3;s:11:\"tag_cloud-3\";i:4;s:8:\"search-2\";i:5;s:14:\"recent-posts-2\";i:6;s:17:\"recent-comments-2\";i:7;s:10:\"archives-2\";i:8;s:12:\"categories-2\";i:9;s:6:\"meta-2\";}s:12:\"sidebar-main\";a:6:{i:0;s:8:\"search-3\";i:1;s:14:\"recent-posts-3\";i:2;s:17:\"recent-comments-3\";i:3;s:10:\"archives-3\";i:4;s:12:\"categories-3\";i:5;s:6:\"meta-3\";}s:8:\"header-1\";a:0:{}s:8:\"header-2\";a:1:{i:0;s:14:\"recent-posts-7\";}s:8:\"header-3\";a:0:{}s:8:\"header-4\";a:0:{}s:8:\"footer-1\";a:1:{i:0;s:13:\"custom_html-1\";}s:8:\"footer-2\";a:1:{i:0;s:13:\"custom_html-2\";}s:8:\"footer-3\";a:1:{i:0;s:13:\"custom_html-3\";}s:8:\"footer-4\";a:1:{i:0;s:13:\"custom_html-4\";}s:16:\"ups-sidebar-teat\";a:0:{}s:13:\"array_version\";i:3;}' WHERE option_name = 'sidebars_widgets';

-- === VERIFICATION ===
SELECT '=== FOOTER WIDGET ASSIGNMENTS ===' AS section;
SELECT option_value FROM nextl_options WHERE option_name = 'sidebars_widgets';

SELECT '=== CUSTOM HTML WIDGET DATA ===' AS section;
SELECT option_value FROM nextl_options WHERE option_name = 'widget_custom_html';
