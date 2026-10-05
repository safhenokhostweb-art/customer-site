import { execFileSync } from 'node:child_process';
import { mkdirSync } from 'node:fs';
import { expect, test } from '@playwright/test';
import { serverEnv } from './fixtures/env.js';
import { loginAsAdmin } from './helpers/auth.js';
import { canvasIframe, openCanvasEditor } from './helpers/canvas.js';

/**
 * TASK 3/3 Phase 6 — deterministic BEFORE/AFTER screenshot capture for the
 * coordinator's visual product review. Not a certification test (no
 * assertions) — installs the real canonical product exactly like the
 * other browser specs, then captures a fixed page/viewport matrix.
 *
 * Usage: SHOT_LABEL=before npx playwright test visual-baseline-capture.spec.js
 *        SHOT_LABEL=after  npx playwright test visual-baseline-capture.spec.js
 */
const label = process.env.SHOT_LABEL || 'unlabeled';
const outDir = `tests/browser/.runtime/visual-${label}`;
mkdirSync(outDir, { recursive: true });

const pages = [
    ['home', '/'],
    ['home-service', '/page/home-service'],
    ['home-ecommerce', '/page/home-ecommerce'],
    ['home-personal', '/page/home-personal'],
    ['home-magazine', '/page/home-magazine'],
    ['home-education', '/page/home-education'],
    ['about', '/page/about'],
    ['shop', '/page/shop'],
    ['product-detail', '/shop/leather-crossbody-bag'],
    ['blog', '/blog'],
    ['article', '/blog/guide-online-store-2026'],
    ['saas', '/page/saas'],
    ['pricing', '/page/pricing'],
    ['slider-showcase', '/page/utilities'],
];

const viewports = [
    ['1366', { width: 1366, height: 900 }],
    ['768', { width: 768, height: 1024 }],
    ['390', { width: 390, height: 844 }],
];

async function waitForImages(scope) {
    await scope.locator('body').evaluate(async (body) => {
        const images = [...body.querySelectorAll('img')];
        images.forEach((img) => { img.loading = 'eager'; });
        await Promise.race([
            Promise.all(images.map((img) => img.complete
                ? Promise.resolve()
                : new Promise((resolve) => {
                    img.addEventListener('load', resolve, { once: true });
                    img.addEventListener('error', resolve, { once: true });
                }))),
            new Promise((resolve) => setTimeout(resolve, 10_000)),
        ]);
    });
}

test.describe.serial(`Visual capture (${label})`, () => {
    test.beforeAll(() => {
        execFileSync('php', [
            'artisan', 'browser-harness:setup', '--universal', '--json',
        ], { cwd: process.cwd(), env: serverEnv(), encoding: 'utf8' });
    });

    for (const [pageName, path] of pages) {
        for (const [vpName, viewport] of viewports) {
            test(`${pageName} @ ${vpName}`, async ({ page }) => {
                test.setTimeout(60_000);
                const failedMedia = [];
                page.on('requestfailed', (request) => {
                    if (request.resourceType() === 'image' || request.resourceType() === 'media') {
                        failedMedia.push(`${request.url()} — ${request.failure()?.errorText || 'failed'}`);
                    }
                });
                await page.setViewportSize(viewport);
                await page.goto(path, { waitUntil: 'domcontentloaded' });
                await waitForImages(page);
                const brokenImages = await page.locator('img').evaluateAll((images) => images
                    .filter((img) => !img.complete || img.naturalWidth === 0)
                    .map((img) => img.currentSrc || img.src));
                expect(failedMedia, `failed media requests on ${path}`).toEqual([]);
                expect(brokenImages, `broken images on ${path}`).toEqual([]);
                await page.screenshot({ path: `${outDir}/${pageName}__${vpName}.png`, fullPage: true });
            });
        }
    }

    for (const [vpName, viewport] of viewports) {
        test(`canvas-home @ ${vpName}`, async ({ page }) => {
            test.setTimeout(60_000);
            const failedMedia = [];
            page.on('requestfailed', (request) => {
                if (request.resourceType() === 'image' || request.resourceType() === 'media') {
                    failedMedia.push(`${request.url()} — ${request.failure()?.errorText || 'failed'}`);
                }
            });
            // The Canvas has its own device switcher; keep the editor chrome
            // usable while setting the preview itself to desktop/tablet/mobile.
            await page.setViewportSize({ width: 1366, height: 900 });
            await loginAsAdmin(page);
            await openCanvasEditor(page);
            // At tablet/mobile widths the editor's fixed toolbar can overlap
            // the horizontally scrollable page tab even though the tab is in
            // the DOM. Invoke the real button's click handler directly; this
            // is the same Alpine switchPage action without a coordinate hit.
            await page.locator('[data-testid="tb-canvas-page-tab-home"]').evaluate((button) => button.click());
            const deviceLabel = vpName === '1366' ? 'دسکتاپ' : vpName === '768' ? 'تبلت' : 'موبایل';
            await page.getByRole('button', { name: deviceLabel, exact: true }).click();
            const frame = canvasIframe(page);
            await frame.locator('.tb-slider').waitFor({ state: 'visible', timeout: 20_000 });
            await waitForImages(frame);
            const brokenImages = await frame.locator('img').evaluateAll((images) => images
                .filter((img) => !img.complete || img.naturalWidth === 0)
                .map((img) => img.currentSrc || img.src));
            expect(failedMedia, 'failed media requests in Canvas home').toEqual([]);
            expect(brokenImages, 'broken images in Canvas home').toEqual([]);
            await page.screenshot({ path: `${outDir}/canvas-home__${vpName}.png`, fullPage: true });
        });
    }
});
