# INTERNAL_PLATFORM_COMMUNICATION_REPORT

**Type:** Architecture Audit — Audit Only, No Code Changes
**Date:** 2026-06-25
**Author:** Claude Code (claude-sonnet-4-6)
**Scope:** customer-site ↔ saas-platform internal communication

---

## 1. Current Architecture

### Physical Layout

Both applications live on the same physical server (`87.107.12.81`):

```
┌──────────────────────────────────────────────────────┐
│  Server: 87.107.12.81                                │
│                                                      │
│  ┌──────────────────┐    HTTP (loopback)             │
│  │  customer-site   │ ──────────────────────────►   │
│  │  (PHP-FPM)       │   127.0.0.1:80                │
│  └──────────────────┘         │                     │
│                               ▼                     │
│                      ┌──────────────────┐           │
│                      │   Nginx          │           │
│                      │   server_name:   │           │
│                      │   panel.safh...  │           │
│                      │   listen 80;     │           │
│                      └──────────────────┘           │
│                               │                     │
│                               ▼                     │
│                      ┌──────────────────┐           │
│                      │  saas-platform   │           │
│                      │  (PHP-FPM)       │           │
│                      └──────────────────┘           │
└──────────────────────────────────────────────────────┘

External clients (internet):
  Browser → ArvanCloud CDN (185.143.233.235/234) → HTTPS → same server
```

### Network Path for License Sync

```
customer-site calls: http://panel.safhenokhost.ir/api/license/validate
         │
         ▼ DNS lookup (glibc, /etc/hosts checked first)
/etc/hosts: 127.0.0.1 panel.safhenokhost.ir   ← overrides public DNS
         │
         ▼
Connects to: 127.0.0.1:80 (loopback — stays on same machine)
         │
         ▼
Nginx HTTP block: listen 80; server_name panel.safhenokhost.ir;
         │
         ▼
FastCGI → saas-platform PHP-FPM
         │
         ▼
LicenseController::validateLicense()
→ LicenseServerService::validate()
→ [valid: true, modules: [...]]
```

---

## 2. DNS Resolution Verification

### `/etc/hosts` Override

```
# /etc/hosts on 87.107.12.81
127.0.0.1  panel.safhenokhost.ir
127.0.0.1  safhenokhost.ir
```

**Effect:** On the server itself, `panel.safhenokhost.ir` always resolves to `127.0.0.1` (loopback), regardless of public DNS.

### Public DNS (ArvanCloud)

```
dig +short panel.safhenokhost.ir
→ 185.143.233.235
→ 185.143.234.235
```

These are **ArvanCloud CDN edge node IPs** — they are only reached by external clients (browsers, mobile apps). Server-internal traffic never touches these IPs.

### Traffic Path Comparison

| Source | DNS resolves to | Path | Certificate seen |
|--------|----------------|------|-----------------|
| Browser (external) | 185.143.233.235 (ArvanCloud) | CDN → origin via HTTP/HTTPS | ArvanCloud Let's Encrypt (valid) |
| customer-site (same server) | 127.0.0.1 (/etc/hosts) | Loopback → Nginx directly | Origin self-signed cert |

**Conclusion:** Internal traffic never touches ArvanCloud CDN. `/etc/hosts` forces all calls to loopback.

---

## 3. HTTPS Failure Verification

### Certificate Details

```
Subject : C=IR, ST=Tehran, L=Tehran, O=SafheNokHost, CN=*.safhenokhost.ir
Issuer  : C=IR, ST=Tehran, L=Tehran, O=SafheNokHost, CN=*.safhenokhost.ir
notBefore: 2026-06-16 05:09:06 UTC
notAfter : 2027-06-16 05:09:06 UTC
```

**Self-signed:** Subject equals Issuer — this certificate was not issued by any public trusted CA (Let's Encrypt, DigiCert, etc.). It is a manually generated certificate by SafheNokHost.

### Test Results (from production server)

```
# HTTPS with normal SSL verification (what PHP/cURL does by default):
curl https://panel.safhenokhost.ir/api/license/validate ...
→ HTTP 000  ssl_verify_result=18
   (000 = connection failed before HTTP)
   (18  = CURLE_SSL_CACERT = certificate not in trusted CA store)

# HTTPS with SSL verification disabled:
curl -k https://panel.safhenokhost.ir/api/license/validate ...
→ HTTP 422  in 0.022s  (422 = correctly received by platform)

# HTTP (no TLS, current production config):
curl http://panel.safhenokhost.ir/api/license/validate ...
→ HTTP 422  in 0.031s  (422 = correctly received by platform)
```

**Conclusion:**
- HTTPS with verification = **connection fails** (ssl_verify_result=18)
- HTTPS without verification = **works** (0.022s round-trip)
- HTTP = **works** (0.031s round-trip)
- Both loopback paths are equally fast (~22-31ms on same machine)

### Why ArvanCloud's Valid Certificate Does Not Help

ArvanCloud CDN presents its own Let's Encrypt certificate (`ARVAN_SSL_VERIFIED` audit confirmed: TLS 1.3, 74-day cert). However:

1. ArvanCloud is a **reverse proxy** — it terminates TLS at the CDN edge, then proxies traffic to the origin server
2. Server-internal traffic (via `/etc/hosts` → `127.0.0.1`) **bypasses the CDN** and connects directly to Nginx on port 443
3. Nginx on port 443 presents the **self-signed certificate** (not ArvanCloud's cert)
4. PHP/cURL's SSL verification checks the certificate presented by the TCP endpoint — which is `127.0.0.1:443` → self-signed → rejected

---

## 4. Current Configuration Assessment

### Production `.env` (customer-site)

```ini
PLATFORM_URL=http://panel.safhenokhost.ir
LICENSE_VERIFY_SSL=false
```

### Why `PLATFORM_URL=http://...` Is Correct

| Criterion | Assessment |
|-----------|-----------|
| Reachability | HTTP port 80 responds correctly (HTTP 422 on test key) |
| Nginx HTTP block | Exists — `listen 80; server_name panel.safhenokhost.ir;` — created specifically for ArvanCloud Flexible SSL mode |
| Security | Both apps on same server, loopback only; no network traversal; HTTP is safe for localhost |
| Performance | No TLS handshake overhead; 31ms response on loopback |
| Reliability | No SSL cert expiry, CA bundle, or trust store dependency for internal calls |

### Why `LICENSE_VERIFY_SSL=false` Is Belt-and-Suspenders

This setting was added as a secondary safeguard. It would apply only if `PLATFORM_URL` is reverted to `https://...`. In current state (`http://...`), SSL verification is not invoked at all — no TLS handshake occurs over HTTP. The setting is harmless and provides protection if the URL is accidentally changed back to HTTPS.

### Why Reverting to HTTPS Is Not Safe Now

| Barrier | Detail |
|---------|--------|
| Self-signed cert at origin | Subject = Issuer = SafheNokHost; not in any public CA trust store |
| `/etc/hosts` forces loopback | All traffic hits `127.0.0.1:443` → self-signed cert — CDN cert is never seen |
| PHP/cURL default behavior | SSL verification enabled by default — would fail with error 60 on every request |
| Evidence | 30 production errors in 7 minutes before fix; zero errors in 49+ minutes after fix |

---

## 5. Conditions Required for Future HTTPS Migration

To safely revert to `PLATFORM_URL=https://panel.safhenokhost.ir` with `LICENSE_VERIFY_SSL=true`, **all** of the following must be true:

### Option A — Install a Trusted Certificate on Nginx Origin

1. Install Let's Encrypt certificate directly on Nginx (e.g., via Certbot or acme.sh)
   ```bash
   certbot --nginx -d panel.safhenokhost.ir
   ```
2. Certificate must be issued by a public CA (Let's Encrypt, ZeroSSL, etc.)
3. Auto-renewal must be configured (`certbot renew` cron/systemd timer)
4. Verify: `curl https://panel.safhenokhost.ir/api/license/validate` returns `HTTP 422` (not `HTTP 000`)
5. Then: update `.env` on customer-site: `PLATFORM_URL=https://panel.safhenokhost.ir`, remove `LICENSE_VERIFY_SSL=false`

### Option B — Move customer-site to a Separate Server

1. customer-site deployed to a different server (separate IP)
2. DNS `/etc/hosts` override no longer applies to customer-site
3. customer-site calls `https://panel.safhenokhost.ir` → resolved to ArvanCloud CDN IPs → CDN presents valid Let's Encrypt cert
4. SSL verification succeeds natively — no bypass needed
5. Then: update `.env`: `PLATFORM_URL=https://panel.safhenokhost.ir`, remove `LICENSE_VERIFY_SSL=false`

### Option C — ArvanCloud Origin Certificate

1. Obtain an ArvanCloud Origin Certificate (valid for origin-to-CDN TLS)
2. Install it on Nginx — it is trusted by ArvanCloud CDN edge nodes
3. However: this cert is NOT trusted by public CA stores → PHP/cURL would still reject it
4. **Not a valid solution for server-internal calls via loopback**

### Migration Checklist (for future reference)

```
[ ] Valid CA-issued certificate installed on Nginx origin
[ ] curl https://panel.safhenokhost.ir/api/license/validate → HTTP 422 (not 000)
[ ] openssl verify result: Verify return code: 0 (ok) — NOT 18
[ ] Update PLATFORM_URL=https://panel.safhenokhost.ir
[ ] Remove LICENSE_VERIFY_SSL=false (or set to true)
[ ] Run php artisan config:cache on customer-site
[ ] Reload PHP-FPM
[ ] Monitor /var/www/customer-site/storage/logs/laravel.log for new SSL errors
[ ] Confirm: license.transient_error.* cache entries stay CLEAR
```

---

## 6. Summary

| Check | Result |
|-------|--------|
| `panel.safhenokhost.ir` resolves to localhost on server | ✅ YES — `/etc/hosts: 127.0.0.1 panel.safhenokhost.ir` |
| Internal traffic bypasses ArvanCloud | ✅ YES — loopback never touches CDN edge IPs |
| HTTPS causes self-signed validation failure | ✅ YES — ssl_verify_result=18, HTTP 000 |
| HTTP internal calls work correctly | ✅ YES — HTTP 422 in 31ms |
| Current config is correct production configuration | ✅ YES |

---

## Final Verdict

```
CURRENT_INTERNAL_HTTP_CONFIGURATION_APPROVED
```

`PLATFORM_URL=http://panel.safhenokhost.ir` with `LICENSE_VERIFY_SSL=false` is the **correct and intentional** production configuration for the current server topology. No changes are needed or recommended until a trusted CA certificate is installed on the Nginx origin server.
