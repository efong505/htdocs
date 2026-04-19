# Security Hardening for Public Access

## Overview

XAMPP is designed for local development, not production. Before exposing it to the internet, several security measures must be applied. This document covers what was implemented and what you should do manually.

---

## What Was Already Implemented

### 1. Directory Listing Disabled

**File changed**: `C:\xampp\apache\conf\httpd.conf`

The `Indexes` option was removed from the htdocs `<Directory>` block:

```
# Before (insecure)
Options Indexes FollowSymLinks Includes ExecCGI

# After (secure)
Options -Indexes +FollowSymLinks +Includes +ExecCGI
```

This prevents visitors from browsing folder contents when there's no index file. Without this, anyone could see every file and folder in your htdocs.

### 2. Root .htaccess Lockdown

**File created**: `C:\xampp\htdocs\.htaccess`

Blocks public access to:
- `/dashboard-backup/` — backup of original XAMPP dashboard
- `/wp-temp/` — temporary WordPress files
- `/Portal Docs/` — this documentation
- `/xampp/` — XAMPP info pages
- `/img/` — XAMPP default images
- `.zip`, `.ini`, `.log`, `.htaccess`, `.htpasswd` files
- `bitnami.css`, `applications.html`
- Server version signature hidden

### 3. phpMyAdmin Already Restricted

**File**: `C:\xampp\apache\conf\extra\httpd-xampp.conf`

phpMyAdmin already has `Require local` which means it's only accessible from the server itself (localhost). External visitors will get a 403 Forbidden error. **No changes needed.**

---

## What You Must Do Manually

### 4. Set a MySQL Root Password

This is critical. Right now anyone with local access (or any compromised PHP script) can access your entire database with no password.

**Step 1**: Open Command Prompt and run:

```cmd
cd c:\xampp\mysql\bin && mysql.exe -u root
```

**Step 2**: Set the password (replace `YOUR_STRONG_PASSWORD` with an actual strong password):

```sql
ALTER USER 'root'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';
ALTER USER 'root'@'127.0.0.1' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';
ALTER USER 'root'@'::1' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';
FLUSH PRIVILEGES;
EXIT;
```

**Step 3**: Update phpMyAdmin config to use the new password:

Open `C:\xampp\phpMyAdmin\config.inc.php` and find:

```php
$cfg['Servers'][$i]['password'] = '';
```

Change to:

```php
$cfg['Servers'][$i]['password'] = 'YOUR_STRONG_PASSWORD';
```

**Step 4**: Update WordPress wp-config.php:

Open `C:\xampp\htdocs\bitbybit\wp-config.php` and change:

```php
define('DB_PASSWORD', '');
```

To:

```php
define('DB_PASSWORD', 'YOUR_STRONG_PASSWORD');
```

**Step 5**: Update the portal config:

Open `C:\xampp\htdocs\portal\config.php` and change:

```php
define('DB_PASS', '');
```

To:

```php
define('DB_PASS', 'YOUR_STRONG_PASSWORD');
```

**Step 6**: Verify everything still works:
- Visit `http://localhost/portal/` — portal should load
- Visit `http://localhost/bitbybit/` — WordPress should load
- Visit `http://localhost/phpmyadmin/` — should be able to log in with new password

> **IMPORTANT**: Use the SAME password in all four places (MySQL, phpMyAdmin, wp-config.php, portal config.php). Write it down somewhere safe.

---

### 5. Change Portal Admin Password

Open `C:\xampp\htdocs\portal\config.php` and change:

```php
define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'changeme123');
```

To something strong:

```php
define('ADMIN_USER', 'your_username');
define('ADMIN_PASS', 'your_strong_password');
```

---

### 6. Hide Server Version Information

Add this to `C:\xampp\apache\conf\httpd.conf` (at the bottom, or find the existing directive):

```
ServerTokens Prod
```

This changes the `Server` HTTP header from `Apache/2.4.x (Win64) OpenSSL/x.x.x PHP/x.x.x` to just `Apache`, giving attackers less information.

---

### 7. Restart Apache After All Changes

After making all the above changes, restart Apache for them to take effect:

1. Open the **XAMPP Control Panel**
2. Click **Stop** next to Apache
3. Click **Start** next to Apache

Or from Command Prompt:

```cmd
c:\xampp\apache\bin\httpd.exe -k restart
```

---

## Security Checklist

- [ ] Directory listing disabled in httpd.conf
- [ ] Root .htaccess blocking sensitive directories and files
- [ ] MySQL root password set
- [ ] phpMyAdmin config updated with new password
- [ ] WordPress wp-config.php updated with new password
- [ ] Portal config.php updated with new DB password
- [ ] Portal admin password changed from default
- [ ] Apache restarted after all changes
- [ ] Server version hidden (ServerTokens Prod)

---

## What's Exposed vs. Blocked

After hardening, here's what external visitors can and cannot access:

### Accessible (Intended)
| URL | Content |
|-----|---------|
| `/` | Redirects to `/portal/` |
| `/portal/` | Public landing page |
| `/portal/admin.php` | Admin login (password protected) |
| `/bitbybit/` | WordPress site |
| `/bitbybit/wp-admin/` | WordPress admin (password protected) |
| Any other site you add to htdocs and link in the portal | |

### Blocked (403 Forbidden)
| URL | Why |
|-----|-----|
| `/phpmyadmin/` | `Require local` in httpd-xampp.conf |
| `/dashboard-backup/` | .htaccess rewrite rule |
| `/wp-temp/` | .htaccess rewrite rule |
| `/Portal Docs/` | .htaccess rewrite rule |
| `/xampp/` | .htaccess rewrite rule |
| `/img/` | .htaccess rewrite rule |
| Any `.zip`, `.ini`, `.log` file | .htaccess FilesMatch rule |
| Directory listings (any folder without index) | `-Indexes` in httpd.conf |

---

## Additional Recommendations

1. **Keep software updated**: WordPress, plugins, themes, XAMPP, PHP — all should be kept current
2. **Use strong passwords everywhere**: WordPress admin, portal admin, MySQL root
3. **Regular backups**: Use the All-in-One WP Migration plugin (already installed) to create periodic backups
4. **Monitor access logs**: Check `C:\xampp\apache\logs\access.log` periodically for suspicious activity
5. **Consider HTTPS**: For a more secure setup, look into Let's Encrypt with a reverse proxy, or Cloudflare's free SSL proxy
6. **Disable unused XAMPP services**: If you don't need FileZilla FTP or Mercury Mail, make sure they're stopped in the XAMPP Control Panel
