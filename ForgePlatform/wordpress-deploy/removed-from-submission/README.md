# Removed From Submission

Files in this folder were removed from the plugin directories before WordPress.org submission but are kept here for reference.

## BackForge
- `wps3b-debug.php` — Debug helper with emergency reset URL. Use as mu-plugin on local/staging sites only. Contains hardcoded key that would cause immediate rejection.
- `ASSETS-GUIDE.md` — Guide for creating WordPress.org banner/icon assets. Reference only.

## DripForge
- `banner-772x250.psd` — Photoshop source file for the plugin banner. Binary/design files don't belong in distributed plugins.
- `plugin-info-methods.php` — The inject_plugin_icon/plugin_info/plugin_row_meta methods removed from the main class. These are only needed for self-hosted (non-WordPress.org) deployments where WordPress can't automatically provide plugin details.
