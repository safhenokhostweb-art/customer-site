import { execFileSync } from 'node:child_process';
import { expect, test } from '@playwright/test';
import { serverEnv } from './fixtures/env.js';
import { loginAsAdmin } from './helpers/auth.js';
import { openCanvasEditor, paletteAddButton, saveButton, switchToPage, waitForSaveBlocks } from './helpers/canvas.js';

/**
 * Canvas overhaul Unit 4 (ADR-074): the unified entity link picker in the
 * real editor. A button's link field opens the shared picker, the search
 * reads the live database (a page created moments ago is offered), and the
 * pick is saved as an entity reference rather than a pasted URL.
 */
test.describe.serial('Canvas entity link picker (Unit 4)', () => {
    test.beforeAll(() => {
        execFileSync('php', ['artisan', 'browser-harness:setup', '--universal', '--json'], { cwd: process.cwd(), env: serverEnv(), encoding: 'utf8' });
        const seed = "\\App\\Models\\Page::firstOrCreate(['slug' => 'e2e-entity-target'], ['title' => 'صفحه هدف پیوند موجودیت', 'body' => '<p>x</p>', 'is_published' => true]);";
        execFileSync('php', ['artisan', 'tinker', '--execute', seed], { cwd: process.cwd(), env: serverEnv(), encoding: 'utf8' });
    });

    test('a button link picks a live page through the shared picker and saves an entity reference', async ({ page }) => {
        test.setTimeout(300_000);
        const consoleErrors = [];
        page.on('pageerror', (e) => consoleErrors.push(String(e)));

        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'home');

        // A fresh button from the palette (categories start collapsed); it is
        // selected on insert, so its inspector shows the link field. The
        // harness database is rebuilt per run, so the insert does not leak.
        const addButton = paletteAddButton(page, 'button');
        if (!(await addButton.isVisible())) {
            await page.locator('[data-testid="tb-palette-category-content"]').click();
        }
        await addButton.click();

        await page.locator('[data-testid="link-pick-url"]').click();
        const picker = page.locator('[data-testid="tb-link-picker"]');
        await expect(picker).toBeVisible();
        // The single-threaded harness server can take a while per request.
        await expect(page.locator('[data-testid="tb-link-picker-tab-page"]')).toBeVisible({ timeout: 30_000 });

        await page.locator('[data-testid="tb-link-picker-search"]').fill('هدف پیوند');
        const result = page.locator('[data-testid^="tb-link-picker-result-page-"]').first();
        await expect(result).toContainText('صفحه هدف پیوند موجودیت', { timeout: 30_000 });
        await expect(result).toContainText('/page/e2e-entity-target');
        await result.click();

        await expect(picker).toBeHidden();
        const chip = page.locator('[data-testid="link-chip-url"]');
        await expect(chip).toContainText('صفحه هدف پیوند موجودیت');

        const response = await waitForSaveBlocks(page, () => saveButton(page).click());
        expect(response.ok()).toBeTruthy();
        const body = response.request().postData() || '';
        expect(body).toContain('"type":"entity"');
        expect(body).toContain('"entity":"page"');

        // Clearing returns the field to a URL input.
        await chip.getByRole('button', { name: 'حذف لینک' }).click();
        await expect(page.locator('[data-testid="link-pick-url"]')).toBeVisible();

        expect(consoleErrors).toEqual([]);
    });
});
