import { expect, test } from '@playwright/test';
import { loadFixture } from './fixtures/env.js';
import { VIEWPORTS } from './fixtures/viewports.js';
import { loginAsAdmin } from './helpers/auth.js';
import { addBlockButton, openBuilder, propField, saveButton } from './helpers/builder.js';

test.describe('Phase 3E-H-D0 — browser harness smoke', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test('real login, real Builder route, real UI interaction, real save/reload', async ({ page }) => {
        const consoleErrors = [];
        page.on('console', (msg) => {
            if (msg.type() === 'error') consoleErrors.push(`${msg.text()} (${msg.location().url})`);
        });
        page.on('pageerror', (err) => consoleErrors.push(String(err)));
        page.on('dialog', (dialog) => dialog.accept());

        const fixture = loadFixture();

        // 1-4: real login through the real form.
        await loginAsAdmin(page);
        await expect(page).toHaveURL(/\/admin(\/|$)/);

        // 5-7: real Builder route opens; palette and canvas render.
        await openBuilder(page, fixture.page_id);
        await expect(page.locator('#live-canvas')).toBeVisible();
        const headingButton = addBlockButton(page, 'heading');
        await expect(headingButton).toBeVisible();

        // 8: save control exists.
        await expect(saveButton(page)).toBeVisible();

        // 15: one harmless real interaction — add a built-in heading block,
        // edit its text prop, save, then prove the change survives a real
        // server reload (not just in-memory Alpine state).
        const uniqueText = `D0 smoke ${Date.now()}`;
        await headingButton.click();

        const textField = propField(page, 'text').locator('input, textarea');
        await expect(textField).toBeVisible();
        await textField.fill(uniqueText);

        const [saveResponse] = await Promise.all([
            page.waitForResponse((res) => res.url().includes('/builder/page/') && res.request().method() === 'PUT'),
            saveButton(page).click(),
        ]);
        expect(saveResponse.ok()).toBeTruthy();

        const previewResponse = page.waitForResponse((res) => res.url().includes('/builder/preview') && res.request().method() === 'POST');
        await page.reload({ waitUntil: 'domcontentloaded' });
        await page.locator('#live-canvas').waitFor({ state: 'attached' });
        await addBlockButton(page, 'heading').waitFor({ state: 'visible' });
        // The canvas iframe's own content is populated asynchronously after
        // load, via a POST to /builder/preview?mode=builder (refreshCanvas())
        // — wait for that specific round trip, not just DOM attachment.
        await previewResponse;

        // Confirm the saved text round-tripped through the real database
        // into the live canvas iframe — the real behavior a real admin
        // would see, not an internal-state proxy for it.
        const canvasFrame = page.frameLocator('#live-canvas');
        await expect(canvasFrame.getByText(uniqueText)).toBeVisible({ timeout: 15_000 });
        await expect(page.locator('[data-testid="tb-block-count"]')).toHaveText('1 ویجت');

        // 10: no unexpected page-level fatal / uncaught application error.
        // The only excluded noise is net::ERR_FAILED for the two
        // admin-polling endpoints this test deliberately aborts via
        // blockNoisyAdminPolling() — everything else, including the
        // previously-masked BOM-JSON SyntaxError, is now a real failure if
        // it occurs.
        const realErrors = consoleErrors.filter(
            (e) =>
                !/favicon|ERR_INTERNET_DISCONNECTED/i.test(e) &&
                !/(notification-center|system-alerts)\/unread-count|user-tickets\/check-new/i.test(e),
        );
        expect(realErrors, `Unexpected console/page errors: ${JSON.stringify(realErrors)}`).toHaveLength(0);

        await page.screenshot({ path: 'tests/browser/.runtime/smoke-authenticated-builder.png', fullPage: true });
    });
});
