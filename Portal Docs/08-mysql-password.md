# MySQL Root Password Management

## Overview

The MySQL root password must be configured in every application that connects to the database. When you change the password, **all** of these locations must be updated with the same password. When you add a new site to htdocs that uses MySQL, it will also need this password in its config.

---

## Current Locations That Use the MySQL Password

| # | File | Setting |
|---|------|---------|
| 1 | MySQL server itself | The actual root account password |
| 2 | `c:\xampp\htdocs\bitbybit\wp-config.php` | `define('DB_PASSWORD', '...');` |
| 3 | `c:\xampp\htdocs\portal\config.php` | `define('DB_PASS', '...');` |
| 4 | `c:\xampp\phpMyAdmin\config.inc.php` | `$cfg['Servers'][$i]['password'] = '...';` |

> **Every new site you add to htdocs that connects to MySQL will need the root password in its database configuration file.**

---

## How to Change the MySQL Root Password

**Important**: Always change MySQL first, then update the config files. If you update the files first, your sites will break until you finish.

### Step 1: Change the MySQL Password

Open Command Prompt and run:

```cmd
cd c:\xampp\mysql\bin && mysql.exe -u root -pYOUR_CURRENT_PASSWORD
```

Then run these SQL commands (replace `YOUR_NEW_PASSWORD` with your new password):

```sql
SET PASSWORD FOR 'root'@'localhost' = PASSWORD('YOUR_NEW_PASSWORD');
SET PASSWORD FOR 'root'@'127.0.0.1' = PASSWORD('YOUR_NEW_PASSWORD');
SET PASSWORD FOR 'root'@'::1' = PASSWORD('YOUR_NEW_PASSWORD');
FLUSH PRIVILEGES;
EXIT;
```

### Step 2: Update WordPress

Open `c:\xampp\htdocs\bitbybit\wp-config.php` and find:

```php
define('DB_PASSWORD', 'OLD_PASSWORD');
```

Change to:

```php
define('DB_PASSWORD', 'YOUR_NEW_PASSWORD');
```

### Step 3: Update the Portal

Open `c:\xampp\htdocs\portal\config.php` and find:

```php
define('DB_PASS', 'OLD_PASSWORD');
```

Change to:

```php
define('DB_PASS', 'YOUR_NEW_PASSWORD');
```

### Step 4: Update phpMyAdmin

Open `c:\xampp\phpMyAdmin\config.inc.php` and find:

```php
$cfg['Servers'][$i]['password'] = 'OLD_PASSWORD';
```

Change to:

```php
$cfg['Servers'][$i]['password'] = 'YOUR_NEW_PASSWORD';
```

### Step 5: Update Any Other Sites

If you've added other sites to htdocs that use MySQL, update their database config files too. Common locations by platform:

| Platform | Config File | Setting |
|----------|------------|---------|
| WordPress | `wp-config.php` | `define('DB_PASSWORD', '...');` |
| Laravel | `.env` | `DB_PASSWORD=...` |
| Custom PHP | Varies (often `config.php` or `db.php`) | Look for password, DB_PASS, etc. |

### Step 6: Restart Apache

Open the XAMPP Control Panel and restart Apache, or run:

```cmd
c:\xampp\apache\bin\httpd.exe -k restart
```

### Step 7: Verify

Test each site in your browser:
- `http://localhost/portal/` — portal loads
- `http://localhost/bitbybit/` — WordPress loads
- `http://localhost/phpmyadmin/` — can log in

---

## Adding a New Site to htdocs

When you create a new site that needs MySQL:

1. Create a new database for it (don't reuse existing databases):
   ```cmd
   cd c:\xampp\mysql\bin && mysql.exe -u root -pYOUR_PASSWORD -e "CREATE DATABASE new_site_db;"
   ```

2. In the new site's database config file, use:
   - **Host**: `localhost`
   - **Username**: `root`
   - **Password**: Your MySQL root password
   - **Database**: The database name you just created

3. Add the site to the portal via the admin panel at `/portal/admin.php`

---

## phpMyAdmin Authentication Mode

phpMyAdmin is configured with `auth_type = config`, which means it auto-logs in using the password stored in `config.inc.php`. There is no login prompt.

This is safe because phpMyAdmin is restricted to **local access only** (`Require local` in `httpd-xampp.conf`). External visitors get a 403 Forbidden error.

If you want a login prompt instead (e.g., multiple people use your PC), change in `c:\xampp\phpMyAdmin\config.inc.php`:

```php
// Auto-login (current)
$cfg['Servers'][$i]['auth_type'] = 'config';

// Login prompt (more secure)
$cfg['Servers'][$i]['auth_type'] = 'cookie';
```

With `cookie` mode, you'll log in with username `root` and your password each time.

---

## If You Get Locked Out

If you forget the password or something breaks:

1. Stop MySQL from the XAMPP Control Panel
2. Open Command Prompt as Administrator
3. Start MySQL without authentication:
   ```cmd
   cd c:\xampp\mysql\bin && mysqld.exe --skip-grant-tables
   ```
4. Open a second Command Prompt and connect:
   ```cmd
   cd c:\xampp\mysql\bin && mysql.exe -u root
   ```
5. Reset the password:
   ```sql
   FLUSH PRIVILEGES;
   SET PASSWORD FOR 'root'@'localhost' = PASSWORD('YOUR_NEW_PASSWORD');
   EXIT;
   ```
6. Close both Command Prompts
7. Start MySQL normally from the XAMPP Control Panel
8. Update all config files with the new password
