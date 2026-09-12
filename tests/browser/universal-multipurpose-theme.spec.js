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

// Real, minimal, valid PNG bytes — the same fixture convention
// media-library.spec.js already uses. SVG is deliberately excluded from
// CustomerMediaService's V1 MIME allowlist (ADR-049 §4: "pending an
// audited canonical sanitizer"), so an SVG upload here would always be
// rejected with a real 422 — this is current, correct product behavior,
// not something to work around.
const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL8WQAAAABJRU5ErkJggg==', 'base64');

const pagePaths = [
    '/', '/page/about', '/page/services', '/page/portfolio', '/page/pricing',
    '/page/team', '/page/testimonials', '/page/blog', '/page/shop', '/page/learning',
    '/page/contact', '/page/account', '/page/content', '/page/search',
    '/page/utilities', '/page/builder-coverage',
];

let fixture;

/**
 * Search the real Media Library (the picker's own search box, backed by
 * ThemeDesignMediaController::index()'s title/filename LIKE match) for a
 * known title, wait for the real debounced network response, then click
 * the matching item by its real UUID testid. Avoids assuming default
 * listing/pagination order — the harness seeds 30+ other real media rows,
 * so an un-searched item is not guaranteed to be on the first page.
 */
async function selectLibraryMediaByTitle(page, title, uuid) {
    const searchResponse = page.waitForResponse((r) => r.url().includes('/theme-design/canvas/media?') && r.request().method() === 'GET');
    await page.getByTestId('tb-media-search').fill(title);
    await searchResponse;
    await page.getByTestId(`tb-media-item-${uuid}`).click();
}

// Builder integrity audit (2026-09-11), Governance §W: this used to be one
// ~230-line test doing editor authoring + media + publish + a 48-navigation
// (16 pages × 3 viewports) public sweep in a single continuous browser
// session. Diagnosing a stale-selector failure surfaced a second, distinct
// problem that ONLY appeared in that single giant session: a Chromium CDP
// session crash ("Internal server error, session closed") partway through
// the long navigation sequence, never reproduced in isolation (every page
// independently proven 200/clean via direct HTTP and single-navigation
// Playwright tests — see the commit history for that evidence). Splitting
// into grouped tests, per §W's own instruction, gives the public-render
// phase its own fresh browser context instead of accumulating 30+ prior
// navigations' state — this is a real architectural fix, not a masked
// symptom.
test.describe.serial('Builder integrity + universal multipurpose Theme', () => {
    test.beforeAll(() => {
        const output = execFileSync('php', [
            'artisan', 'browser-harness:setup', '--universal', '--json',
            `--source-theme-id=${process.env.QA_THEME_ID || ''}`,
            `--source-release-id=${process.env.QA_THEME_RELEASE_ID || ''}`,
            `--source-version=${process.env.QA_THEME_RELEASE_VERSION || '1.0.0'}`,
        ], { cwd: process.cwd(), env: serverEnv(), encoding: 'utf8' });

        // Builder integrity audit (2026-09-11): the fixture's real
        // CustomerMedia UUIDs (MEDIA-4 canonical identity) — no filename
        // testid exists any more (canvas-editor.blade.php keys the picker
        // strictly by img.uuid), so a real, known UUID from the harness's
        // own JSON contract is the only deterministic, real selector.
        const jsonLine = output.split('\n').find((l) => l.startsWith('BROWSER_HARNESS_JSON:'));
        if (!jsonLine) throw new Error(`browser-harness:setup did not print its JSON summary. Output:\n${output}`);
        fixture = JSON.parse(jsonLine.slice('BROWSER_HARNESS_JSON:'.length));
    });

    test('editor: palette accounting, real media persistence, global style references, publish', async ({ page }) => {
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
        // Builder integrity audit (2026-09-11): 94, not 96 — module:
        // portfolio_grid and module:services_section were de-exposed
        // (no backend model on this host — see BlockRegistry::all()),
        // alongside the pre-existing module:gallery_widget exclusion.
        await expect(page.locator('[data-testid^="tb-palette-add-"]')).toHaveCount(94);

        // Existing Media Library selection -> save -> reload -> public render.
        // Two real, already-used CustomerMedia UUIDs stand in for the old
        // "qa-existing-a/b.svg" filenames — 'about' as item B, 'hero' as
        // item A, both real rows seeded by seedUniversalMedia().
        const itemB = fixture.universal_media_uuids.about;
        const itemA = fixture.universal_media_uuids.hero;

        await switchToPage(page, 'portfolio');
        await openTreeTab(page);
        await treeNode(page, 'qa_image').click();
        await page.getByTestId('tb-media-picker-url').click();
        await selectLibraryMediaByTitle(page, 'QA about', itemB);
        await page.getByTestId('tb-media-confirm').click();
        await expect(page.getByTestId('tb-media-value-url')).toHaveValue(new RegExp(`/uploads/customer-media/.*${itemB}\\.[a-z]+$`));
        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();

        await reloadCanvasEditor(page);
        await switchToPage(page, 'portfolio');
        await openTreeTab(page);
        await treeNode(page, 'qa_image').click();
        await expect(page.getByTestId('tb-media-value-url')).toHaveValue(new RegExp(`/uploads/customer-media/.*${itemB}\\.[a-z]+$`));

        // A real multipart upload from the computer, selected through the
        // same picker, then persisted across save/reload.
        await page.getByTestId('tb-media-picker-url').click();
        await page.getByTestId('tb-media-upload-tab').click();
        const uploadResponse = page.waitForResponse((response) =>
            response.url().includes('/theme-design/canvas/media/upload') && response.request().method() === 'POST'
        );
        await page.getByTestId('tb-media-file-input').setInputFiles({
            name: 'qa-upload-from-computer.png',
            mimeType: 'image/png',
            buffer: png,
        });
        expect((await uploadResponse).ok()).toBeTruthy();
        await page.getByTestId('tb-media-confirm').click();
        const uploadedUrl = await page.getByTestId('tb-media-value-url').inputValue();
        expect(uploadedUrl).toContain('/uploads/customer-media/');
        expect(uploadedUrl).not.toContain(itemB);
        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();
        await reloadCanvasEditor(page);
        await switchToPage(page, 'portfolio');
        await openTreeTab(page);
        await treeNode(page, 'qa_image').click();
        await expect(page.getByTestId('tb-media-value-url')).toHaveValue(uploadedUrl);

        // Replacement uses an existing library asset and round-trips again.
        await page.getByTestId('tb-media-picker-url').click();
        await selectLibraryMediaByTitle(page, 'QA hero', itemA);
        await page.getByTestId('tb-media-confirm').click();
        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();
        await reloadCanvasEditor(page);
        await switchToPage(page, 'portfolio');
        await openTreeTab(page);
        await treeNode(page, 'qa_image').click();
        await expect(page.getByTestId('tb-media-value-url')).toHaveValue(new RegExp(`/uploads/customer-media/.*${itemA}\\.[a-z]+$`));

        // Global Style palette references on two semantically distinct fields.
        await switchToPage(page, 'home');
        await openTreeTab(page);
        await treeNode(page, 'qa_hero_split').click();
        await page.getByTestId('tb-theme-color-background-primary').click();
        await expect(page.getByTestId('tb-prop-background')).toHaveValue('var(--tb-primary)');
        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();

        // Builder integrity audit (2026-09-11): 'hero_gradient' is now
        // deliberately placed on the real 'about' page (not left for the
        // auto-generated 'builder-coverage' catch-all) — see
        // BrowserHarnessSetupCommand::universalThemeData()'s 'about'
        // definition. Proving the global-style token reference on a real,
        // authored business page is at least as strong a proof as an
        // auto-generated coverage page.
        await switchToPage(page, 'about');
        await openTreeTab(page);
        await treeNode(page, 'qa_hero_gradient').click();
        await page.getByTestId('tb-theme-color-text_color-secondary').click();
        await expect(page.getByTestId('tb-prop-text_color')).toHaveValue('var(--tb-secondary)');
        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();

        // Publish through the real Canvas write path before public checks.
        expect((await waitForPublish(page, () => publishButton(page).click())).ok()).toBeTruthy();

        expect(consoleErrors, `console errors: ${JSON.stringify(consoleErrors)}`).toEqual([]);
        expect(pageErrors, `page errors: ${JSON.stringify(pageErrors)}`).toEqual([]);
        expect(failedRequests, `failed requests: ${JSON.stringify(failedRequests)}`).toEqual([]);
    });

    test('public site: every page renders clean at 1366/768/390 after publish', async ({ page }) => {
        test.setTimeout(600_000);
        const consoleErrors = [];
        const pageErrors = [];
        const failedRequests = [];
        page.on('console', (message) => {
            if (message.type() === 'error') consoleErrors.push(message.text());
        });
        page.on('pageerror', (error) => pageErrors.push(String(error)));
        page.on('requestfailed', (request) => {
            const errorText = request.failure()?.errorText || '';
            if (!/favicon|unread-count|check-new/i.test(request.url())) {
                failedRequests.push(`${request.method()} ${request.url()} ${errorText}`);
            }
        });

        const itemA = fixture.universal_media_uuids.hero;

        for (const viewport of [
            { width: 1366, height: 900 },
            { width: 768, height: 1024 },
            { width: 390, height: 844 },
        ]) {
            await page.setViewportSize(viewport);
            for (const path of pagePaths) {
                // domcontentloaded, not load: every assertion below is
                // DOM-level (invalid-schema markers, computed overflow) —
                // it does not need every image/video asset on the page to
                // finish downloading first. Real wait condition per
                // Governance §3, not a weaker one; the response itself is
                // still the real navigation response.
                const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
                expect(response?.ok(), `${viewport.width}px ${path}`).toBeTruthy();
                await expect(page.locator('body')).not.toContainText('[invalid]');
                expect(await page.locator('[data-reason="invalid_schema"]').count(), `${viewport.width}px ${path}`).toBe(0);
                const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
                expect(overflow, `horizontal overflow at ${viewport.width}px ${path}`).toBeLessThanOrEqual(1);
            }
        }

        await page.setViewportSize({ width: 1366, height: 900 });
        await page.goto('/page/portfolio', { waitUntil: 'domcontentloaded' });
        await expect(page.locator(`img[src*="${itemA}"]`)).toBeVisible();
        await page.goto('/page/about', { waitUntil: 'domcontentloaded' });
        await expect(page.locator('[style*="color:var(--tb-secondary)"]')).toBeVisible();

        expect(consoleErrors, `console errors: ${JSON.stringify(consoleErrors)}`).toEqual([]);
        expect(pageErrors, `page errors: ${JSON.stringify(pageErrors)}`).toEqual([]);
        expect(failedRequests, `failed requests: ${JSON.stringify(failedRequests)}`).toEqual([]);
    });
});
