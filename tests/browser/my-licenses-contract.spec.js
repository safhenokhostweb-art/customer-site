import { expect, test } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth.js';
import { VIEWPORTS } from './fixtures/viewports.js';

/**
 * Regression for the Production incident: /admin/theme-marketplace/my-licenses
 * returned HTTP 500 ("Undefined array key installed_theme_version") for
 * every theme, because the real Platform API contract never sends that
 * legacy key. The fake platform server this harness already runs against
 * (tests/browser/scripts/fake-platform-server.js) emits the exact real
 * shape — `installed_version: null`, no `installed_theme_version` key at
 * all — so this test exercises the real bug through the real HTTP/Blade
 * stack, not a mock of the fix.
 */
test.describe('my-licenses marketplace contract', () => {
    for (const [name, viewport] of Object.entries(VIEWPORTS)) {
        test(`renders with zero errors on ${name}`, async ({ page }) => {
            test.info().annotations.push({ type: 'viewport', description: name });
            await page.setViewportSize(viewport);

            const pageErrors = [];
            const consoleErrors = [];
            page.on('pageerror', (error) => pageErrors.push(String(error)));
            page.on('console', (message) => {
                if (message.type() === 'error' && !/favicon|unread-count|check-new/i.test(message.text())) {
                    consoleErrors.push(message.text());
                }
            });

            await loginAsAdmin(page);

            // Acquire the free theme so its license appears with
            // installed_version: null (never installed) — the exact state
            // that crashed my-licenses in Production.
            await page.goto('/admin/theme-marketplace', { waitUntil: 'domcontentloaded' });
            const purchaseForm = page.locator('form[action$="/admin/theme-marketplace/101/purchase"]').first();
            if (await purchaseForm.count()) {
                await Promise.all([
                    page.waitForURL(/theme-marketplace\/my-licenses/),
                    purchaseForm.locator('button').click(),
                ]);
            } else {
                await page.goto('/admin/theme-marketplace/my-licenses', { waitUntil: 'domcontentloaded' });
            }

            const status = await page.evaluate(() => document.readyState);
            expect(status).not.toBe('');
            await expect(page.locator('body')).not.toContainText('Undefined array key');
            await expect(page.locator('body')).not.toContainText('ErrorException');
            await expect(page.getByText('Assigned Browser Theme', { exact: true }).first()).toBeVisible();

            await page.screenshot({ path: `tests/browser/.runtime/my-licenses-contract-${name}.png`, fullPage: true });

            expect(pageErrors, `JS page errors on ${name}: ${pageErrors.join(', ')}`).toHaveLength(0);
            expect(consoleErrors, `Console errors on ${name}: ${consoleErrors.join(', ')}`).toHaveLength(0);
        });
    }

    test('installed theme shows its real version, not a crash', async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto('/admin/theme-marketplace', { waitUntil: 'domcontentloaded' });

        const purchaseForm = page.locator('form[action$="/admin/theme-marketplace/202/purchase"]').first();
        if (await purchaseForm.count()) {
            await Promise.all([
                page.waitForURL(/theme-marketplace\/my-licenses/),
                purchaseForm.locator('button').click(),
            ]);
        }

        await page.goto('/admin/theme-marketplace/my-licenses', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('body')).not.toContainText('Undefined array key');
        await expect(page.getByText('Independent Browser Theme', { exact: true }).first()).toBeVisible();
    });
});
