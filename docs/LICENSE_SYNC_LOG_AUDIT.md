# LICENSE_SYNC_LOG_AUDIT

**Phase 3 — Log Audit**
Generated: 2026-06-25

---

## Audit Scope

- **Date range:** 2026-06-25 07:10 – 07:17 (7 minutes)
- **Log file:** `/var/www/customer-site/storage/logs/laravel.log`
- **Entries analyzed:** 30 license-related entries

---

## Error Pattern Distribution

| Error | Count | Frequency | Root Cause |
|-------|-------|-----------|------------|
| `cURL error 60: SSL certificate problem: self-signed certificate` | 30 | 100% | Self-signed cert at `https://panel.safhenokhost.ir` |
| `connection_refused` | 0 | 0% | — |
| `timeout` | 0 | 0% | — |
| `5xx HTTP` | 0 | 0% | — |
| `429 rate limit` | 0 | 0% | — |
| `cache miss` | 0 | 0% | — |

**Verdict:** 100% of failures are caused by a single root cause — SSL certificate rejection.

---

## Log Entry Pattern

```
[2026-06-25 07:10:30] production.ERROR: License validation exception 
{
  "message": "cURL error 60: SSL certificate problem: self-signed certificate 
               (see https://curl.haxx.se/libcurl/c/libcurl-errors.html) 
               for https://panel.safhenokhost.ir/api/license/validate",
  "url": "https://panel.safhenokhost.ir/api/license/validate",
  "exception": "Illuminate\\Http\\Client\\ConnectionException"
}
```

---

## Frequency Analysis

**Entries per minute:**

| Minute | Count | Notes |
|--------|-------|-------|
| 07:10 | 2 | Initial burst |
| 07:11 | 4 | 2 entries per page load |
| 07:12 | 4 | — |
| 07:13 | 4 | — |
| 07:14 | 4 | — |
| 07:15 | 4 | — |
| 07:16 | 4 | — |
| 07:17 | 4 | — |

**Pattern:** ~2 log entries per request (likely 2 concurrent requests per page = 2 PHP-FPM workers processing same-time requests)

**Before fix:** Every request triggered sync because `last_validated_at` was never updated on transient failure.

---

## SSL Verification Result

```
curl -s -o /dev/null -w '%{http_code} %{ssl_verify_result}' --max-time 5 https://panel.safhenokhost.ir/api/license/validate
→ 000 18
```

- HTTP code `000` = connection failed
- ssl_verify_result `18` = `CURLE_SSL_CACERT` = SSL certificate cannot be verified against a known CA

**Confirmed:** Self-signed certificate, no trusted CA in chain.

---

## HTTP Endpoint Test (Internal)

```
curl http://panel.safhenokhost.ir/api/license/validate -X POST -d '...'
→ http_code=422
```

HTTP port 80 responds correctly (422 = validation error for test key).
Platform is fully accessible via HTTP on same server.

---

## Cache Audit

**Before fix (stuck entries):**
```
customer_site.cache WHERE key LIKE '%transient_error%'
→ Cleared via php artisan tinker (1+ entries with 30-min TTL)
```

**After fix:**
- TTL reduced to `max(10, revalidate_interval × 2)` = 10 minutes
- `last_validated_at` updated on transient skip → retries only every 5 min
- Banner auto-expires without platform being called

---

## Queue and Scheduler Logs

No license-related queue or scheduler entries found. License sync is synchronous (in middleware), not queued.

---

## Most Common Failure

**`cURL error 60: SSL certificate problem: self-signed certificate`**
- Cause: PLATFORM_URL used HTTPS; platform cert is self-signed; cURL rejects it
- Frequency: Every request (100%)
- Duration of issue: Until 2026-06-25 fix deployment
