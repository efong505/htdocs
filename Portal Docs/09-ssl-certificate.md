# SSL Certificate Setup with Win-ACME (Let's Encrypt)

## Overview

Win-ACME is a free Windows client for Let's Encrypt that automatically obtains and renews SSL certificates. This allows your site to be served over HTTPS with a valid certificate (no browser warnings).

## Prerequisites

- Port **80** and **443** forwarded on your router (see `03-configure-router.md`)
- Apache allowed through firewall (see `04-windows-firewall.md`)
- DUC running and hostname resolving (see `02-configure-duc.md`)
- Apache running in XAMPP

---

## Step 1: Download & Extract Win-ACME

1. Go to [https://www.win-acme.com/](https://www.win-acme.com/)
2. Download the latest **64-bit** release (zip file)
3. Extract it to `C:\win-acme\` (or wherever you prefer)

---

## Step 2: Create the SSL Directory

Create the directory where certificates will be stored:

```cmd
mkdir C:\xampp\apache\conf\ssl
```

---

## Step 3: Run Win-ACME

1. Open **Command Prompt as Administrator**
2. Run:
   ```cmd
   cd C:\win-acme && wacs.exe
   ```

---

## Step 4: Menu Selections

Follow these prompts in order:

**Main menu:**
```
N: Create certificate (default settings)
```

**How shall we determine the domain(s)?**
```
2: Manual input
```

**Host:**
```
edwardfong.onthewifi.com
```

**Friendly name:**
```
[Press Enter to accept default]
```

**How shall we prove domain ownership?**
```
1: Save verification files on (network) path
```

**Path:**
```
C:\xampp\htdocs
```

> Let's Encrypt will verify ownership by reaching `http://edwardfong.onthewifi.com/.well-known/acme-challenge/` — this folder must be in your web root.

**What kind of private key?**
```
2: RSA key
```

**How shall we store the certificate?**
```
2: PEM encoded files (Apache, nginx, etc.)
```

**Path to store PEM files:**
```
C:\xampp\apache\conf\ssl
```

**Password for private key .pem file:**
```
[Press Enter for no password]
```

> Leave blank. Apache doesn't handle password-protected keys well — it would prompt on every startup.

**What installation steps?**
```
3: No (additional) installation steps
```

Win-ACME will now contact Let's Encrypt, validate your domain, and generate the certificate files.

---

## Step 5: Configure Apache for SSL

After win-acme generates the certificates, check what files were created:

```cmd
dir C:\xampp\apache\conf\ssl
```

You should see files like:
- `edwardfong.onthewifi.com-chain.pem` (certificate + chain)
- `edwardfong.onthewifi.com-key.pem` (private key)

Open `C:\xampp\apache\conf\extra\httpd-ssl.conf` and find:

```
SSLCertificateFile "conf/ssl.crt/server.crt"
SSLCertificateKeyFile "conf/ssl.key/server.key"
```

Change to (adjust filenames to match what's in your ssl folder):

```
SSLCertificateFile "conf/ssl/edwardfong.onthewifi.com-chain.pem"
SSLCertificateKeyFile "conf/ssl/edwardfong.onthewifi.com-key.pem"
```

---

## Step 6: Restart Apache

Stop and Start Apache in the XAMPP Control Panel.

---

## Step 7: Verify

Visit `https://edwardfong.onthewifi.com` — you should see a valid SSL padlock with no browser warnings.

---

## Step 8: Update WordPress URLs to HTTPS

After SSL is working, update each WordPress site to use `https://` instead of `http://`.

### wp-config.php

Update `WP_HOME` and `WP_SITEURL` in each site's wp-config.php. Example for bitbybit:

```php
define('WP_HOME','https://edwardfong.onthewifi.com/bitbybit');
define('WP_SITEURL','https://edwardfong.onthewifi.com/bitbybit');
```

### Database Search & Replace

Run a search-and-replace to update all internal links and image URLs. See `05-wordpress-config.md` for detailed instructions. The search/replace values would be:

- **Search for**: `http://localhost/bitbybit`
- **Replace with**: `https://edwardfong.onthewifi.com/bitbybit`

Repeat for each site (altoa, mysite, etc.) with their respective paths.

---

## Auto-Renewal

Win-ACME automatically creates a **Windows Scheduled Task** that renews the certificate every 60 days (certificates expire at 90 days). You don't need to do anything manually.

To verify the scheduled task exists:
1. Open **Task Scheduler** (Win + S → search "Task Scheduler")
2. Look for a task named **win-acme** or **letsencrypt**
3. It should be set to run periodically

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Validation fails | Let's Encrypt can't reach your server on port 80. Check DUC, port forwarding, and firewall. |
| Browser shows "Not Secure" after setup | Apache may still be using the old self-signed cert. Double-check the file paths in `httpd-ssl.conf`. |
| Apache won't start after SSL changes | Check the error log at `C:\xampp\apache\logs\error.log`. Common cause: wrong file path in `httpd-ssl.conf`. |
| Certificate expired | Check that the win-acme scheduled task is running. You can manually renew by running `wacs.exe --renew` |
| Port 80 must stay open | Let's Encrypt uses port 80 for renewal validation even after SSL is set up. Don't close it. |
| Sites redirect to localhost after enabling SSL | Update `WP_HOME` and `WP_SITEURL` in wp-config.php and run database search-and-replace (see Step 8). |

---

## Important Notes

- **Port 80 is still required** — Let's Encrypt needs it for certificate renewal validation
- **Free No-IP accounts** require hostname confirmation every 30 days — if the hostname expires, renewal will fail
- **After enabling HTTPS**, all WordPress sites need their URLs updated from `http://` to `https://` (Step 8)
- **Mixed content warnings** can occur if some images/links still use `http://` — the database search-and-replace in Step 8 fixes this
