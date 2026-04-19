# Configuring No-IP DUC (Dynamic Update Client)

## What DUC Does

Your home internet connection has a dynamic IP address that changes periodically. DUC runs in the background on your PC and automatically updates No-IP's DNS servers whenever your IP changes, so `edwardfong.onthewifi.com` always points to your current IP.

---

## Step-by-Step Installation

### 1. Install DUC

1. Locate the DUC installer you downloaded (typically `DUCSetup_vX_X_X.exe`)
2. Run the installer as Administrator
3. Follow the installation wizard — default settings are fine
4. Choose to launch DUC when installation completes

### 2. Sign In

1. When DUC opens, enter your **No-IP account email** and **password**
2. Click **Sign In**

### 3. Select Your Hostname

1. After signing in, DUC will show a list of your configured hostnames
2. Check the box next to **`edwardfong.onthewifi.com`**
3. Click **Save**

### 4. Verify It's Working

1. DUC will show your current public IP address in the main window
2. The status should show a green indicator or say "IP is current"
3. Open a command prompt and run:
   ```
   nslookup edwardfong.onthewifi.com
   ```
4. The returned IP address should match what DUC displays

### 5. Configure DUC to Start Automatically

1. In DUC, go to **File → Preferences** (or the settings/gear icon)
2. Enable **"Start on system startup"** or **"Run as a Windows service"**
3. This ensures DUC runs even after a reboot

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| DUC shows "Offline" | Check your internet connection. Restart DUC. |
| IP not updating | Make sure the hostname checkbox is selected. Check No-IP account isn't expired. |
| nslookup returns wrong IP | Wait a few minutes for DNS propagation. DUC updates can take 1-5 minutes. |
| DUC not starting on boot | Re-enable the startup option. Check Windows Task Manager → Startup tab. |

---

## Important Notes

- **Free No-IP accounts** require you to confirm your hostname every 30 days via email. If you miss the confirmation, the hostname will be deleted.
- DUC must be running at all times for the hostname to stay updated.
- If you restart your PC, make sure DUC starts automatically (see Step 5).
