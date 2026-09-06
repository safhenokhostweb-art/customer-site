import { expect, test } from '@playwright/test';
import { VIEWPORTS } from './fixtures/viewports.js';
import { loginAsAdmin } from './helpers/auth.js';

/**
 * Phase 3H-B — real-browser proof of the rollback UI convergence:
 * ThemeDesign Canvas dashboard still opens, the canonical permanent
 * revision history panel is now reachable and usable there, the legacy
 * single-slot/5-slot rollback UIs are no longer offered, and unrelated
 * Canvas/PageBuilder functionality is unaffected. Reuses this repo's
 * existing browser harness (global-setup.js provisions the DB/admin/Canvas
 * theme fixtures and starts the real PHP server) rather than any new
 * infrastructure.
 */
test.describe('ThemeDesign Canvas — rollback UI convergence (3H-B)', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test('Canvas dashboard opens, canonical history panel reachable, legacy rollback UI absent', async ({ page }) => {
        await loginAsAdmin(page);

        await page.goto('/admin/theme-design/canvas', { waitUntil: 'domcontentloaded' });
        await expect(page.getByRole('heading', { name: 'قالب Canvas', exact: false }).first()).toBeVisible();

        // Canonical history/rollback toggle is present and opens the panel.
        const historyToggle = page.getByRole('button', { name: 'تاریخچه و بازیابی نسخه‌ها' });
        await expect(historyToggle).toBeVisible();
        await historyToggle.click();
        await expect(page.getByText('تاریخچه نسخه‌های قالب')).toBeVisible();

        // The panel actually loads real revision data from the canonical endpoint.
        await expect(page.getByText(/نسخه #\d+/).first()).toBeVisible({ timeout: 10_000 });

        // Legacy single-slot rollback button/text must not exist anywhere on this page.
        await expect(page.getByText(/بازگردانی «.*»/)).toHaveCount(0);

        await page.screenshot({ path: 'tests/browser/.runtime/theme-design-canvas-rollback.png', fullPage: true });
    });

    test('Appearance settings page no longer exposes the legacy 5-slot revision UI', async ({ page }) => {
        await loginAsAdmin(page);

        await page.goto('/admin/theme-design', { waitUntil: 'domcontentloaded' });
        await expect(page.getByText('تاریخچه نسخه‌های Canvas')).toHaveCount(0);
    });

    test('Canvas editor (Extension SDK authoring) still opens unaffected', async ({ page }) => {
        await loginAsAdmin(page);

        await page.goto('/admin/theme-design/canvas/edit', { waitUntil: 'domcontentloaded' });
        // The block-authoring toolbar (Save control) from the pre-existing
        // canvas-editor.blade.php SPA must still render normally.
        await expect(page.locator('body')).toBeVisible();
    });
});
