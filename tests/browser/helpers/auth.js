import { ADMIN_MOBILE, ADMIN_PASSWORD, BASE_URL } from '../fixtures/env.js';

/**
 * Phase 3E-H-D0 — the admin layout (resources/views/admin/layout.blade.php)
 * fires several 30s-interval background polls (unread notification count,
 * ticket-notifier "check new") on every admin page load, immediately and
 * repeatedly. Against PHP's built-in single-threaded dev server used by
 * this harness, one such poll can fully block the next real request for
 * several seconds — irrelevant background noise, not part of what any
 * given test is verifying. Blocking them here is a Playwright-level test
 * concern only; no application code changes.
 *
 * @param {import('@playwright/test').Page} page
 */
export async function blockNoisyAdminPolling(page) {
    await page.route(/\/admin\/(notification-center|system-alerts)\/unread-count/, (route) => route.abort());
    await page.route(/\/admin\/user-tickets\/check-new/, (route) => route.abort());
}

/**
 * Phase 3E-H-D0 — logs in through the REAL login form (no auth bypass, no
 * storageState substitute). Navigates to /login, fills the real mobile +
 * password fields, submits, and waits for the real post-login redirect to
 * the admin dashboard.
 *
 * @param {import('@playwright/test').Page} page
 */
export async function loginAsAdmin(page) {
    await blockNoisyAdminPolling(page);
    // 'domcontentloaded' rather than the default 'load': the server itself
    // responds quickly, but 'load' waits for every subresource (fonts,
    // etc.) and any one slow/hanging asset request stalls the whole
    // navigation — the well-known Playwright flakiness pattern for
    // asset-heavy pages, unrelated to whether the page is actually usable.
    await page.goto(`${BASE_URL}/login`, { waitUntil: 'domcontentloaded' });
    await page.locator('#login-mobile').fill(ADMIN_MOBILE);
    await page.locator('#login-password').fill(ADMIN_PASSWORD);
    await page.locator('button[type="submit"]').click();
    await page.waitForURL(/\/admin(\/|$)/, { timeout: 15_000 });
}
