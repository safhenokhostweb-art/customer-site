<?php

namespace Tests\Unit\ThemeCanvas;

use Tests\TestCase;

/**
 * Commercial Productization fase-4 — a real, confirmed bug found via an
 * actual local end-to-end theme install using a genuine external POST
 * (curl, simulating saas-platform's real server-to-server call), not
 * Laravel's own internal test HTTP client: `builder/api/theme/install`
 * (and `/unprotect`) are documented as "uses X-Provision-Secret header,
 * no auth middleware" — a pure server-to-server contract authenticated by
 * ProvisionSecretMiddleware's timing-safe shared-secret check, never a
 * browser session. No CSRF token was ever obtainable for a real caller,
 * so every real call failed with 419 before this fix. Laravel's own
 * TestCase HTTP client never reproduced this (its `post()` helper does
 * not exercise CSRF the way a real external request does) — which is
 * exactly why a genuine local install, not another mocked/internal test,
 * was needed to find it. This test asserts the fix directly against the
 * kernel's own CSRF-except configuration, the most direct regression
 * guard for this exact class of gap.
 */
class ProvisionEndpointCsrfExemptionTest extends TestCase
{
    public function test_theme_provision_endpoints_are_exempt_from_csrf_verification(): void
    {
        // Middleware::validateCsrfTokens(except: [...]) (bootstrap/app.php)
        // ultimately calls the static ValidateCsrfToken::except(), which
        // accumulates into the base VerifyCsrfToken::$neverVerify static
        // property — not an instance property, and not the framework's
        // (unused-by-default) $except property either.
        $ref = new \ReflectionProperty(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class, 'neverVerify');
        $ref->setAccessible(true);
        $except = $ref->getValue();

        $this->assertNotEmpty($except, 'no CSRF exceptions configured at all — the provision endpoints would 419 on every real external call');

        $matchesInstall = false;
        foreach ($except as $pattern) {
            if (\Illuminate\Support\Str::is($pattern, 'builder/api/theme/install')) {
                $matchesInstall = true;
                break;
            }
        }
        $this->assertTrue($matchesInstall, "'builder/api/theme/install' must be CSRF-exempt — it is a real server-to-server endpoint with no browser session to source a token from.");
    }
}
