# ShieldForge — Project Status

*Last updated: June 2025*

---

## Brand

| | |
|---|---|
| **Product Family** | Forge |
| **Security Plugin** | ShieldForge |
| **Security Pro** | ShieldForge Pro |
| **Working slug** | `sf-security` |
| **Target slug** | `shieldforge` |
| **Prefix** | `sfs_` |

---

## Phase Status

| Phase | Feature | Status |
|-------|---------|--------|
| 1 | Login Hardening & Brute Force Protection | ✅ Complete |
| 2 | IP Blocklist & Rate Limiting | ✅ Complete |
| 3 | Web Application Firewall (WAF) | ✅ Complete |
| 4 | File Integrity Monitoring | ⬜ Not started |
| 5 | Malware Scanner | ⬜ Not started |
| 6 | Two-Factor Authentication (Pro) | ⬜ Not started |
| 7 | Country Blocking (GeoIP) | ⬜ Not started |
| 8 | Activity Log & REST API Protection | ⬜ Not started |

---

## Documentation

| Doc | Title | Status |
|-----|-------|--------|
| 01 | Project Plan | ✅ Complete |
| 02 | Firewall & WAF Technical Guide | ✅ Complete |
| 03 | Project Status (this) | ✅ Complete |
| 04 | Monetization Strategy | ✅ Complete |

---

## Remaining Before Phase 1 Build

- [x] Create plugin scaffold (entry point, activation, tables)
- [x] Set up admin menu with Forge dark UI
- [x] Create dashboard page skeleton
- [x] Create settings page skeleton
- [x] Implement login attempt tracking
- [x] Implement auto-lockout logic
- [x] Implement IP blocklist (manual)
- [x] Implement security event log
- [x] Build dashboard stats + recent events view
- [ ] Test on PHP 7.4, 8.0, 8.1, 8.2, 8.3
- [ ] Test on WordPress 6.0 through latest

---

## Dependencies on Other Forge Products

- **LicenseForge** — Pro license validation (same pattern as BackForge Pro)
- **BackForge** — No direct dependency, but recommended companion (backup before security changes)
- **DripForge** — No dependency

---

## Notes

- Install Wordfence free on AccuWebHost sites as interim protection until ShieldForge Phase 1 is ready
- ShieldForge is a post-migration project — no deadline pressure
- Phase 1 (login hardening + blocklist) is the MVP — ship it as soon as it's solid
- Country blocking in free tier is the key differentiator vs. Wordfence/Sucuri/iThemes
