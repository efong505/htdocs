# XAMPP MySQL (MariaDB) Troubleshooting Guide

## The Problem

MySQL/MariaDB in XAMPP crashes on startup after an unclean shutdown (unexpected restart, sleep, power loss). XAMPP shows:

```
Error: MySQL shutdown unexpectedly.
```

---

## Quick Fix Steps

### Step 1: Delete Corrupted Aria Logs

The most common cause. Open cmd and run:

```cmd
del c:\xampp\mysql\data\aria_log.00000001
del c:\xampp\mysql\data\aria_log_control
```

> **Do NOT delete** the `.bak` versions — those are your safety net.

### Step 2: Delete Stale PID File

```cmd
del c:\xampp\mysql\data\mysql.pid
del c:\xampp\mysql\data\DESKTOP-GBLL5U4.pid
```

### Step 3: Try Starting MySQL

Open XAMPP Control Panel and click Start on MySQL.

---

## If It Still Won't Start

### Check the Error Log

```cmd
powershell -command "Get-Content 'c:\xampp\mysql\data\mysql_error.log' -Tail 30"
```

Look for these specific errors:

| Error | Fix |
|-------|-----|
| `Aria recovery failed` | Delete aria_log files (Step 1) |
| `Could not open mysql.plugin table` | Restore mysql folder from backup (see Nuclear Option below) |
| `Plugin 'Aria' registration as a STORAGE ENGINE failed` | Delete aria_log files (Step 1) |
| `Cannot open datafile for read-only: '.\mysql\*.ibd'` | Restore mysql folder from backup (see Nuclear Option below) |
| `Can't open and lock privilege tables` | Restore mysql folder from backup (see Nuclear Option below) |
| `Server socket created on IP: '::'` then dies | PID file issue — verify `my.ini` has absolute pid_file path |
| Port 3306 in use | Run `netstat -ano | findstr ":3306"` and kill the blocking process |

> **Important:** If you see InnoDB `.ibd` errors or "privilege tables" errors after deleting aria logs, the damage is deeper than Aria. Skip straight to the Nuclear Option.

### Run aria_chk (If Aria Tables Are Corrupt)

```cmd
c:\xampp\mysql\bin\aria_chk -r c:\xampp\mysql\data\mysql\plugin
c:\xampp\mysql\bin\aria_chk -r c:\xampp\mysql\data\mysql\servers
```

### Verify my.ini PID Path

Open `c:\xampp\mysql\bin\my.ini` and confirm this line exists:

```ini
pid_file="C:/xampp/mysql/data/mysql.pid"
```

If it says `pid_file="mysql.pid"` (relative), change it to the absolute path above.

---

## Nuclear Option: Reset MySQL System Tables

Use this when you see InnoDB `.ibd` errors or "privilege tables" errors. Your WordPress databases are NOT affected — only the `mysql` system database (user accounts, stats) gets reset.

```cmd
:: 1. Rename corrupted folder
rename c:\xampp\mysql\data\mysql mysql_broken

:: 2. If mysql_broken already exists from a previous fix:
rename c:\xampp\mysql\data\mysql mysql_broken2

:: 3. Restore from XAMPP backup
xcopy c:\xampp\mysql\backup\mysql c:\xampp\mysql\data\mysql\ /E /I /Q

:: 4. Clean up aria logs and pid
del c:\xampp\mysql\data\aria_log.00000001 2>nul
del c:\xampp\mysql\data\aria_log_control 2>nul
del c:\xampp\mysql\data\mysql.pid 2>nul

:: 5. Start MySQL from XAMPP Control Panel
```

> **Warning:** This resets user accounts to XAMPP defaults (root with no password). Your WordPress databases (in their own folders under `data/`) are untouched.

---

## Prevention

### Option A: Always Stop MySQL Before Shutdown

Get in the habit of stopping MySQL in XAMPP before shutting down or sleeping.

### Option B: Install as Windows Service (Recommended)

This lets Windows gracefully stop MySQL before any restart:

```cmd
:: Run as Administrator
c:\xampp\mysql\bin\mysqld.exe --install mysql --defaults-file="c:\xampp\mysql\bin\my.ini"
```

- Starts automatically on boot
- Stops gracefully on shutdown/restart
- Manage with `net start mysql` / `net stop mysql`
- No longer need XAMPP Control Panel for MySQL

To remove the service later:

```cmd
:: Run as Administrator
c:\xampp\mysql\bin\mysqld.exe --remove mysql
```

---

## File Reference

| File | Purpose |
|------|---------|
| `c:\xampp\mysql\data\mysql_error.log` | Error log — check this first |
| `c:\xampp\mysql\data\aria_log.*` | Aria engine transaction logs |
| `c:\xampp\mysql\data\aria_log_control` | Aria checkpoint record |
| `c:\xampp\mysql\data\ibdata1` | InnoDB system tablespace |
| `c:\xampp\mysql\data\ib_logfile0/1` | InnoDB redo logs |
| `c:\xampp\mysql\data\*.pid` | Process ID file |
| `c:\xampp\mysql\bin\my.ini` | MySQL configuration |
| `c:\xampp\mysql\backup\` | XAMPP's default backup of system tables |
