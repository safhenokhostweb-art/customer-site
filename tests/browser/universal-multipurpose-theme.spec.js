import { execFileSync } from 'node:child_process';
import { Buffer } from 'node:buffer';
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

const pagePaths = [
    '/', '/page/about', '/page/services', '/page/portfolio', '/page/blog',
    '/page/shop', '/page/learning', '/page/contact', '/page/account',
    '/page/utilities', '/page/builder-coverage',
];

test.describe('Builder integrity + universal multipurpose Theme', () => {
    test.beforeAll(() => {
        execFileSync('php', [
            'artisan', 'browser-harness:setup', '--universal', '--json',
            `--source-theme-id=${process.env.QA_THEME_ID || ''}`,
            `--source-release-id=${process.env.QA_THEME_RELEASE_ID || ''}`,
            `--source-version=${process.env.QA_THEME_RELEASE_VERSION || '1.0.0'}`,
        ], { cwd: process.cwd(), env: serverEnv(), encoding: 'utf8' });
    });

    test('complete site, media, palette, persistence, public render, and responsive gate', async ({ page }) => {
        test.setTimeout(180_000);
        const consoleErrors = [];
        const pageErrors = [];
        const failedRequests = [];
        const monitor = (target) => {
            target.on('console', (message) => {
                if (message.type() === 'error') consoleErrors.push(message.text());
            });
            target.on('pageerror', (error) => pageErrors.push(String(error)));
            target.on('requestfailed', (request) => {
                const errorText = request.failure()?.errorText || '';
                const isSupersededLivePreview = errorText === 'net::ERR_ABORTED'
                    && request.url().includes('/admin/theme-design/canvas/live-preview');

                if (!/favicon|unread-count|check-new/i.test(request.url()) && !isSupersededLivePreview) {
                    failedRequests.push(`${request.method()} ${request.url()} ${errorText}`);
                }
            });
        };
        monitor(page);

        await loginAsAdmin(page);
        try {
            await openCanvasEditor(page);
        } catch (error) {
            throw new Error(`${error}\nconsole=${JSON.stringify(consoleErrors)}\npageErrors=${JSON.stringify(pageErrors)}\nfailedRequests=${JSON.stringify(failedRequests)}`);
        }

        // Canonical palette accounting is visible in the real UI.
        await expect(page.locator('[data-testid^="tb-palette-add-"]')).toHaveCount(96);

        // Existing Media Library selection -> save -> reload -> public render.
        await switchToPage(page, 'portfolio');
        await openTreeTab(page);
        await treeNode(page, 'qa_image').click();
        await page.getByTestId('tb-media-picker-url').click();
        await page.getByTestId('tb-media-item-qa-existing-b.svg').click();
        await page.getByTestId('tb-media-confirm').click();
        await expect(page.getByTestId('tb-media-value-url')).toHaveValue(/\/storage\/theme-media\/qa-existing-b\.svg$/);
        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();

        await reloadCanvasEditor(page);
        await switchToPage(page, 'portfolio');
        await openTreeTab(page);
        await treeNode(page, 'qa_image').click();
        await expect(page.getByTestId('tb-media-value-url')).toHaveValue(/\/storage\/theme-media\/qa-existing-b\.svg$/);

        // A real multipart upload from the computer, selected through the
        // same picker, then persisted across save/reload.
        await page.getByTestId('tb-media-picker-url').click();
        await page.getByTestId('tb-media-upload-tab').click();
        const uploadResponse = page.waitForResponse((response) =>
            response.url().includes('/theme-design/canvas/media/upload') && response.request().method() === 'POST'
        );
        await page.getByTestId('tb-media-file-input').setInputFiles({
            name: 'qa-upload-from-computer.svg',
            mimeType: 'image/svg+xml',
            buffer: Buffer.from('<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180"><rect width="320" height="180" fill="#dc2626"/></svg>'),
        });
        expect((await uploadResponse).ok()).toBeTruthy();
        await page.getByTestId('tb-media-confirm').click();
        const uploadedUrl = await page.getByTestId('tb-media-value-url').inputValue();
        expect(uploadedUrl).toContain('/storage/theme-media/');
        expect(uploadedUrl).not.toContain('qa-existing-');
        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();
        await reloadCanvasEditor(page);
        await switchToPage(page, 'portfolio');
        await openTreeTab(page);
        await treeNode(page, 'qa_image').click();
        await expect(page.getByTestId('tb-media-value-url')).toHaveValue(uploadedUrl);

        // Replacement uses an existing library asset and round-trips again.
        await page.getByTestId('tb-media-picker-url').click();
        await page.getByTestId('tb-media-item-qa-existing-a.svg').click();
        await page.getByTestId('tb-media-confirm').click();
        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();
        await reloadCanvasEditor(page);
        await switchToPage(page, 'portfolio');
        await openTreeTab(page);
        await treeNode(page, 'qa_image').click();
        await expect(page.getByTestId('tb-media-value-url')).toHaveValue(/\/storage\/theme-media\/qa-existing-a\.svg$/);

        // Global Style palette references on two semantically distinct fields.
        await switchToPage(page, 'home');
        await openTreeTab(page);
        await treeNode(page, 'qa_hero_split').click();
        await page.getByTestId('tb-theme-color-background-primary').click();
        await expect(page.getByTestId('tb-prop-background')).toHaveValue('var(--tb-primary)');
        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();

        await switchToPage(page, 'builder-coverage');
        await openTreeTab(page);
        await treeNode(page, 'qa_hero_gradient').click();
        await page.getByTestId('tb-theme-color-text_color-secondary').click();
        await expect(page.getByTestId('tb-prop-text_color')).toHaveValue('var(--tb-secondary)');
        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();

        // Publish through the real Canvas write path before public checks.
        expect((await waitForPublish(page, () => publishButton(page).click())).ok()).toBeTruthy();

        // Keep the live editor/iframe lifecycle isolated from public-site
        // navigation so a pending editor render can never supersede goto().
        const sitePage = await page.context().newPage();
        monitor(sitePage);

        for (const viewport of [
            { width: 1366, height: 900 },
            { width: 768, height: 1024 },
            { width: 390, height: 844 },
        ]) {
            await sitePage.setViewportSize(viewport);
            for (const path of pagePaths) {
                const response = await sitePage.goto(path, { waitUntil: 'load' });
                expect(response?.ok(), `${viewport.width}px ${path}`).toBeTruthy();
                await expect(sitePage.locator('body')).not.toContainText('[invalid]');
                expect(await sitePage.locator('[data-reason="invalid_schema"]').count(), `${viewport.width}px ${path}`).toBe(0);
                const overflow = await sitePage.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
                expect(overflow, `horizontal overflow at ${viewport.width}px ${path}`).toBeLessThanOrEqual(1);
            }
        }

        await sitePage.setViewportSize({ width: 1366, height: 900 });
        await sitePage.goto('/page/portfolio', { waitUntil: 'domcontentloaded' });
        await expect(sitePage.locator('img[src$="/storage/theme-media/qa-existing-a.svg"]')).toBeVisible();
        await sitePage.goto('/page/builder-coverage', { waitUntil: 'domcontentloaded' });
        await expect(sitePage.locator('[style*="color:var(--tb-secondary)"]')).toBeVisible();

        expect(consoleErrors, `console errors: ${JSON.stringify(consoleErrors)}`).toEqual([]);
        expect(pageErrors, `page errors: ${JSON.stringify(pageErrors)}`).toEqual([]);
        expect(failedRequests, `failed requests: ${JSON.stringify(failedRequests)}`).toEqual([]);
    });
});
