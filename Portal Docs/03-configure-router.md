# Configuring Router Port Forwarding

## Why This Is Needed

When someone visits `edwardfong.onthewifi.com`, the request reaches your router's public IP. The router needs to know to forward that traffic to your XAMPP PC on your local network. Without port forwarding, the request stops at the router.

---

## Step 1: Find Your PC's Local IP Address

1. Open **Command Prompt** (Win + R → type `cmd` → Enter)
2. Run:
   ```
   ipconfig
   ```
3. Look for your active network adapter (usually **Ethernet** or **Wi-Fi**)
4. Note the **IPv4 Address** — it will look like `192.168.1.x` or `192.168.0.x`
5. Write this down — you'll need it for the port forwarding rule

---

## Step 2: Set a Static Local IP (Recommended)

Your router assigns local IPs dynamically via DHCP, which means your PC's IP could change. To prevent the port forwarding rule from breaking:

### Option A: Reserve IP in Router (Preferred)
1. Log into your router admin panel (usually `192.168.1.1` or `192.168.0.1`)
2. Find **DHCP Reservation** or **Address Reservation** (often under LAN or DHCP settings)
3. Add a reservation:
   - **MAC Address**: Your PC's MAC (find it with `ipconfig /all` → look for "Physical Address")
   - **IP Address**: The current IP from Step 1 (e.g., `192.168.1.100`)
4. Save

### Option B: Set Static IP on Windows
1. Open **Settings → Network & Internet → Change adapter options**
2. Right-click your active adapter → **Properties**
3. Select **Internet Protocol Version 4 (TCP/IPv4)** → **Properties**
4. Select **Use the following IP address**:
   - **IP address**: `192.168.1.100` (or your preferred IP, outside DHCP range)
   - **Subnet mask**: `255.255.255.0`
   - **Default gateway**: `192.168.1.1` (your router's IP)
   - **Preferred DNS**: `8.8.8.8`
   - **Alternate DNS**: `8.8.4.4`
5. Click **OK**

---

## Step 3: Configure Port Forwarding

The exact steps vary by router brand, but the general process is the same:

1. Open a browser and go to your router's admin page:
   - Common addresses: `192.168.1.1`, `192.168.0.1`, `10.0.0.1`
   - Check the sticker on your router for the address and login credentials
2. Log in with your router admin credentials
3. Navigate to **Port Forwarding** (may be under Advanced, NAT, Firewall, or Virtual Servers)
4. Add a new port forwarding rule:

| Field | Value |
|-------|-------|
| Service Name | `Apache HTTP` (or any label) |
| Protocol | **TCP** |
| External Port | `80` |
| Internal Port | `80` |
| Internal IP | Your PC's local IP (e.g., `192.168.1.100`) |
| Enable | **Yes** |

5. **(Optional)** Add a second rule for HTTPS:

| Field | Value |
|-------|-------|
| Service Name | `Apache HTTPS` |
| Protocol | **TCP** |
| External Port | `443` |
| Internal Port | `443` |
| Internal IP | Your PC's local IP (e.g., `192.168.1.100`) |
| Enable | **Yes** |

6. Save and apply changes

---

## Step 4: Verify Port Forwarding

1. Make sure Apache is running in XAMPP
2. From your phone (on mobile data, NOT Wi-Fi) or ask someone external, visit:
   ```
   http://edwardfong.onthewifi.com
   ```
3. You should see the portal landing page
4. You can also use an online port checker like [canyouseeme.org](https://canyouseeme.org) to verify port 80 is open

---

## Common Router Interfaces

### Netgear
Advanced → Advanced Setup → Port Forwarding / Port Triggering

### TP-Link
Advanced → NAT Forwarding → Virtual Servers

### Linksys
Security → Apps and Gaming → Single Port Forwarding

### ASUS
WAN → Virtual Server / Port Forwarding

### Xfinity/Comcast Gateway
Advanced → Port Forwarding

---

## Troubleshooting

| Issue | Solution |
|-------|----------|
| Can't access router admin page | Try `192.168.0.1` or `10.0.0.1`. Check router sticker for correct address. |
| Port forwarding not working | Verify Apache is running. Check Windows Firewall (see `03-windows-firewall.md`). |
| Works locally but not externally | Port forwarding rule may be wrong. Double-check the internal IP. |
| ISP blocking port 80 | Some ISPs block port 80 on residential connections. Try using port 8080 externally mapped to port 80 internally. |
| Double NAT issue | If your modem and router are separate devices, you may need to port forward on both, or put the modem in bridge mode. |
