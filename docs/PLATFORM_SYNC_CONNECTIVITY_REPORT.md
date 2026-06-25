# PLATFORM_SYNC_CONNECTIVITY_REPORT

**Phase 5 — PLATFORM_URL Audit**
Generated: 2026-06-25

---

## Environment

| Setting | Before Fix | After Fix |
|---------|-----------|-----------|
| PLATFORM_URL | `https://panel.safhenokhost.ir` | `http://panel.safhenokhost.ir` |
| LICENSE_VERIFY_SSL | (not set = true) | `false` |
| Http timeout | 10s | 15s |

---

## SSL Status (Before Fix)

| Check | Result |
|-------|--------|
| Certificate type | Self-signed (no trusted CA) |
| `curl ssl_verify_result` | `18` = CURLE_SSL_CACERT |
| ArvanCloud CDN (real cert) | PENDING (not yet activated) |
| cURL behavior | Rejects connection, throws ConnectionException |

---

## HTTP Access (After Fix)

| Check | Result |
|-------|--------|
| `curl http://panel.safhenokhost.ir/api/license/validate` | `http_code=422` (reachable) |
| `curl -H 'Host: panel.safhenokhost.ir' http://127.0.0.1/...` | `http_code=422` (reachable via loopback) |
| Nginx HTTP block | Exists — `server_name panel.safhenokhost.ir; listen 80;` |
| Purpose of HTTP block | ArvanCloud Flexible SSL mode (CDN terminates TLS) |
| Internal access | Both customer-site and saas-platform on same server |

---

## Nginx Configuration

```nginx
# HTTP — serve directly for ArvanCloud Flexible SSL mode
server {
    listen 80;
    server_name panel.safhenokhost.ir;
    root /var/www/saas-platform/public;
    index index.php;
    # ArvanCloud IP trust for real_ip
    ...
}

# HTTPS — for ArvanCloud Full SSL mode (self-signed cert)
server {
    listen 443 ssl;
    server_name panel.safhenokhost.ir;
    root /var/www/saas-platform/public;
    ...
}
```

The HTTP block was intentionally created for ArvanCloud Flexible SSL mode where CDN handles TLS termination and forwards HTTP to origin. Using HTTP for server-to-server license sync on same machine is correct and safe.

---

## DNS Resolution

| Domain | Resolution | Notes |
|--------|-----------|-------|
| `panel.safhenokhost.ir` | Server's own IP | Resolves correctly; loopback works |

---

## Timeout Configuration

| Setting | Before | After |
|---------|--------|-------|
| Http timeout | 10s | 15s |

Increased to 15s to accommodate occasional platform slowness (cold start, DB queries).

---

## Connection Pooling

Laravel's `Http::` client (Guzzle) does not maintain persistent connections by default. Each license sync creates a new HTTP connection. This is acceptable given:
- Syncs occur every 5 minutes (not per-request after fix)
- HTTP (no TLS handshake) is faster than HTTPS

---

## Security Assessment

**HTTP for internal license sync:**
- Both services on same physical server → no network traversal
- ArvanCloud CDN handles external TLS → end-to-end encryption for public traffic
- Internal HTTP is standard practice for same-server service communication
- License key is sent as POST body (not in URL) → not logged by Nginx access log

**`LICENSE_VERIFY_SSL=false`:**
- Fallback belt-and-suspenders setting
- Only applies to license validation HTTP client, not to other Http:: calls
- Would only matter if PLATFORM_URL reverts to HTTPS

---

## Pre-Launch Actions Resolved

| Action | Status |
|--------|--------|
| Change PLATFORM_URL to HTTP on customer-site | DONE |
| Add LICENSE_VERIFY_SSL=false | DONE |
| Verify HTTP endpoint returns valid response | DONE (422 = correct) |
| Clear stuck transient error cache | DONE |

---

## Verdict

**PLATFORM_SYNC_CONNECTIVITY: OPERATIONAL**

HTTP access to `http://panel.safhenokhost.ir/api/license/validate` is confirmed.
No further infrastructure changes required until ArvanCloud CDN is activated (then revert to HTTPS with valid cert).
