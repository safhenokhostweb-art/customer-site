import { execFileSync } from 'node:child_process';
import { expect, test } from '@playwright/test';
import { serverEnv } from './fixtures/env.js';
import { loginAsAdmin } from './helpers/auth.js';
import {
    openCanvasEditor,
    openTreeTab,
    publishButton,
    reloadCanvasEditor,
    saveButton,
    switchToPage,
    treeNode,
    waitForPublish,
    waitForSaveBlocks,
} from './helpers/canvas.js';

// Builder integrity audit (2026-09-11), §I: the user explicitly reported
// image border-radius not applying before. This proves the real,
// currently-wired mechanism end to end: the universal block-level `style`
// object (Alpine `updateStyle()` -> `block.style.borderRadius`) ->
// `applyBlockStyle()`/`buildCssDeclarations()` (canonical, shared by both
// repos) -> a real computed border-radius on the actual public-rendered
// <img>. Not a unit test of the CSS builder in isolation — a real
// AUTHORING -> SAVE -> RELOAD -> PUBLISH -> PUBLIC computed-style proof,
// through the real UI, no test-mode bypass.
test.describe('Builder visual capability: image border-radius', () => {
    test.beforeAll(() => {
        execFileSync('php', [
            'artisan', 'browser-harness:setup', '--universal', '--json',
        ], { cwd: process.cwd(), env: serverEnv(), encoding: 'utf8' });
    });

    test('a real border-radius value set on an image block round-trips to a real computed style on the public page', async ({ page }) => {
        test.setTimeout(120_000);
        await loginAsAdmin(page);
        await openCanvasEditor(page);

        await switchToPage(page, 'portfolio');
        await openTreeTab(page);
        await treeNode(page, 'qa_image').click();

        // Real image already selected by the fixture — the prior selector
        // check is intentionally omitted here (covered by
        // universal-multipurpose-theme.spec.js); this test is scoped to
        // the style capability only.
        await page.getByText('استایل', { exact: true }).click();
        await page.getByText('حاشیه', { exact: true }).click();
        const radiusInput = page.locator('label:has-text("گوشه گرد") + input, label:has-text("گوشه گرد") ~ input').first();
        await radiusInput.fill('24');
        await radiusInput.blur();

        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();

        await reloadCanvasEditor(page);
        await switchToPage(page, 'portfolio');
        await openTreeTab(page);
        await treeNode(page, 'qa_image').click();
        await page.getByText('استایل', { exact: true }).click();
        await page.getByText('حاشیه', { exact: true }).click();
        const radiusInputAfterReload = page.locator('label:has-text("گوشه گرد") + input, label:has-text("گوشه گرد") ~ input').first();
        await expect(radiusInputAfterReload).toHaveValue('24');

        expect((await waitForPublish(page, () => publishButton(page).click())).ok()).toBeTruthy();

        const publicPage = await page.context().newPage();
        await publicPage.goto('/page/portfolio', { waitUntil: 'domcontentloaded' });
        // block.style wraps the block's own HTML in a `.tb-style-wrap` div
        // carrying the real inline border-radius (applyBlockStyle()) —
        // assert the actual browser-computed style, not the saved value.
        const wrap = publicPage.locator('.tb-style-wrap').filter({ has: publicPage.locator('img') }).first();
        await expect(wrap).toBeVisible();
        const computedRadius = await wrap.evaluate((el) => getComputedStyle(el).borderRadius);
        expect(computedRadius).toBe('24px');
    });
});
