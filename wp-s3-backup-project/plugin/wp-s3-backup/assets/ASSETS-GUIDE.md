# Plugin Assets Guide

## WordPress.org Plugin Assets

When submitting to wordpress.org, you need these image assets in the `assets/` directory of your SVN repository (not in the plugin zip itself).

### Required Images

| File | Size | Purpose |
|------|------|---------|
| `banner-772x250.png` | 772×250px | Plugin page header banner |
| `banner-1544x500.png` | 1544×500px | Retina/HiDPI banner |
| `icon-128x128.png` | 128×128px | Plugin icon (standard) |
| `icon-256x256.png` | 256×256px | Plugin icon (retina) |

### Screenshots

Screenshots are referenced in `readme.txt` by number:

```
== Screenshots ==
1. Settings page with AWS configuration and status bar
2. Backup list showing files stored in S3 with storage class badges
3. Activity log with live refresh
4. Restore confirmation with compatibility check
5. Pro features preview
```

Name them:
- `screenshot-1.png` — Settings page
- `screenshot-2.png` — Backups page with badges and usage summary
- `screenshot-3.png` — Logs page with auto-refresh
- `screenshot-4.png` — Restore confirmation screen
- `screenshot-5.png` — Settings page showing Pro feature placeholders

### Design Recommendations

**Banner:**
- Background: gradient from #2271b1 (WordPress blue) to #1e3a5f (dark blue)
- Logo: cloud upload icon + "WP S3 Backup" text
- Tagline: "Lightweight WordPress Backups to Amazon S3"
- Keep it clean — no busy patterns

**Icon:**
- Simple cloud with an up arrow
- Colors: #2271b1 background, white icon
- Should be recognizable at 128px

### Creating the Images

You can create these using:
- **Figma** (free) — best for clean vector designs
- **Canva** (free) — quick and easy with templates
- **GIMP** (free) — if you prefer desktop software

### Screenshot Capture Tips

1. Use a clean WordPress install with no other admin notices
2. Set browser width to ~1200px for consistent screenshots
3. Capture just the plugin content area (not the full browser)
4. Add sample data (a few backups with different storage classes)
5. Show the Pro badges — they demonstrate the upgrade path
