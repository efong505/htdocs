# Go-Live Checklist & Portal Admin Guide

## Go-Live Checklist

Complete these steps in order when you're ready to make the site publicly accessible.

### Pre-Launch

- [ ] **1. Install & configure DUC** — See `02-configure-duc.md`
  - DUC is running and showing your current IP
  - `edwardfong.onthewifi.com` resolves to your public IP (`nslookup edwardfong.onthewifi.com`)
  - DUC is set to start on boot

- [ ] **2. Set a static local IP** — See `03-configure-router.md` Step 2
  - Your PC has a reserved or static IP on your local network

- [ ] **3. Configure port forwarding** — See `03-configure-router.md` Step 3
  - Port 80 (TCP) forwarded to your PC's local IP
  - Port 443 (TCP) forwarded if using HTTPS

- [ ] **4. Allow Apache through Windows Firewall** — See `04-windows-firewall.md`
  - `httpd.exe` allowed for both Private and Public networks

- [ ] **5. Update WordPress wp-config.php** — See `05-wordpress-config.md` Step 1
  - `WP_HOME` set to `http://edwardfong.onthewifi.com/bitbybit`
  - `WP_SITEURL` set to `http://edwardfong.onthewifi.com/bitbybit`

- [ ] **6. Run database search & replace** — See `05-wordpress-config.md` Step 2
  - All `localhost/bitbybit` references replaced with `edwardfong.onthewifi.com/bitbybit`

- [ ] **7. Set MySQL root password** — See `08-mysql-password.md`
  - Password set in MySQL for all root hosts
  - `bitbybit/wp-config.php` updated with new password
  - `portal/config.php` updated with new password
  - `phpMyAdmin/config.inc.php` updated with new password

- [ ] **8. Change portal admin password** — See `08-mysql-password.md` (also in `portal/config.php`)
  - Change `ADMIN_PASS` from `changeme123` to a strong password

- [ ] **9. Security hardening** — See `07-security-hardening.md`
  - Directory listing disabled in httpd.conf
  - Root .htaccess blocking sensitive directories and files
  - `ServerTokens Prod` added to httpd.conf
  - phpMyAdmin restricted to local access only (already configured)

- [ ] **10. SSL certificate** — See `09-ssl-certificate.md`
  - Win-ACME installed and certificate generated
  - Apache `httpd-ssl.conf` updated with new cert paths
  - WordPress URLs updated from `http://` to `https://`
  - Database search-and-replace completed for HTTPS

### Verification

- [ ] From a device NOT on your Wi-Fi (e.g., phone on mobile data), visit:
  - `https://edwardfong.onthewifi.com` → should show the portal
  - `https://edwardfong.onthewifi.com/bitbybit` → should show WordPress site
  - `https://edwardfong.onthewifi.com/portal/admin.php` → should show admin login
- [ ] Images load correctly (no broken images or old URLs)
- [ ] WordPress admin dashboard works at `/bitbybit/wp-admin`

---

## Portal Admin Guide

### Accessing the Admin Panel

- **URL**: `http://localhost/portal/admin.php` (or `http://edwardfong.onthewifi.com/portal/admin.php` when public)
- **Default credentials**: `admin` / `changeme123` (defined in `portal/config.php`)

### Adding a Site

1. Go to the admin panel
2. Fill in the form:
   - **Title** (required): The name displayed on the card (e.g., `Bit By Bit Coding`)
   - **URL** (required): Relative path or full URL (e.g., `/bitbybit` or `https://example.com`)
   - **Description**: Short text shown below the title
   - **Icon**: A Font Awesome icon class — browse at [fontawesome.com/icons](https://fontawesome.com/icons)
     - Examples: `fa-code`, `fa-graduation-cap`, `fa-laptop-code`, `fa-globe`, `fa-rocket`
   - **Accent Color**: Pick from the color picker — this colors the icon background and hover effects
   - **Sort Order**: Lower numbers appear first (0, 1, 2, etc.)
   - **Active**: Toggle on/off — inactive sites won't show on the public page
3. Click **Save**

### Editing a Site

1. Find the site in the **Managed Sites** list
2. Click the **pencil icon** (edit button)
3. The form scrolls to the top and populates with the site's current values
4. Make your changes
5. Click **Save**
6. Click **Cancel** to discard changes and reset the form

### Deleting a Site

1. Find the site in the **Managed Sites** list
2. Click the **trash icon** (delete button)
3. Confirm the deletion in the popup

### Useful Font Awesome Icons

| Icon Class | Preview Description |
|------------|-------------------|
| `fa-code` | Code brackets `</>` |
| `fa-laptop-code` | Laptop with code |
| `fa-graduation-cap` | Graduation cap |
| `fa-globe` | Globe |
| `fa-rocket` | Rocket |
| `fa-book` | Book |
| `fa-palette` | Paint palette |
| `fa-database` | Database cylinder |
| `fa-shield-halved` | Security shield |
| `fa-store` | Storefront |
| `fa-blog` | Blog text |
| `fa-camera` | Camera |
| `fa-music` | Music note |
| `fa-gamepad` | Game controller |

Full list: [https://fontawesome.com/icons?d=gallery&s=solid&m=free](https://fontawesome.com/icons?d=gallery&s=solid&m=free)

---

## File Reference

| Document | Contents |
|----------|----------|
| `01-overview.md` | Project architecture, database schema, file descriptions, what was changed |
| `02-configure-duc.md` | Installing and configuring No-IP DUC |
| `03-configure-router.md` | Port forwarding setup with static IP and router brand guides |
| `04-windows-firewall.md` | Allowing Apache through Windows Defender Firewall or Avast |
| `05-wordpress-config.md` | Updating wp-config.php and running database search & replace |
| `06-go-live-and-admin.md` | This file — go-live checklist and portal admin usage guide |
| `07-security-hardening.md` | Security lockdowns: directory listing, .htaccess, server info, checklist |
| `08-mysql-password.md` | MySQL password management, how to change it, all config locations, lockout recovery |
| `09-ssl-certificate.md` | SSL setup with Win-ACME/Let's Encrypt, Apache config, HTTPS migration |
| `10-favicon.md` | Adding favicons using custom images or SVG emojis |

---

## Quick Reference Commands

**Check your local IP:**
```cmd
ipconfig
```

**Check if hostname resolves:**
```cmd
nslookup edwardfong.onthewifi.com
```

**Check if port 80 is listening:**
```cmd
netstat -an | findstr :80
```

**MySQL command line access:**
```cmd
cd c:\xampp\mysql\bin && mysql.exe -u root -pYOUR_PASSWORD bitbybit_db
```

**Restart Apache from command line:**
```cmd
c:\xampp\apache\bin\httpd.exe -k restart
```
