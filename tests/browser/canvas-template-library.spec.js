import { execFileSync } from 'node:child_process';
import { expect, test } from '@playwright/test';
import { serverEnv } from './fixtures/env.js';
import { loginAsAdmin } from './helpers/auth.js';
import { openCanvasEditor, switchToPage } from './helpers/canvas.js';

/**
 * Canvas overhaul Unit 5 (ADR-075): one-click template insertion in the
 * real customer editor. A section template lands on the page and a header
 * template on the virtual __header__ page (replacing it after
 * confirmation); each is saved and shown in the live iframe at once, with
 * the library's images loaded and its links stored as entity references
 * the site resolves to live permalinks.
 */
async function insertFromLibrary(page, id) {
    await page.getByTestId('tb-open-templates').click();
    const card = page.getByTestId(`tpl-card-${id}`);
    await expect(card).toBeVisible({ timeout: 30_000 });
    const saved = page.waitForResponse((r) => r.url().includes('/canvas/save-blocks') && r.request().method() === 'PUT', { timeout: 60_000 });
    await card.click();
    const response = await saved;
    expect(response.ok()).toBeTruthy();
    await expect(page.getByText('قالب درج شد')).toBeVisible({ timeout: 30_000 });
    return response.request().postDataJSON();
}

async function libraryImagesLoaded(frame) {
    return frame.locator('body').evaluate(async () => {
        const imgs = [...document.querySelectorAll('img')].filter((i) => i.src.includes('/theme-runtime/library/'));
        imgs.forEach((i) => { i.loading = 'eager'; });
        await Promise.all(imgs.map((i) => (i.complete ? null : new Promise((r) => { i.onload = i.onerror = r; }))));
        return imgs.filter((i) => i.naturalWidth > 0).length;
    });
}

test.describe.serial('Canvas template library (Unit 5)', () => {
    test.beforeAll(() => {
        execFileSync('php', ['artisan', 'browser-harness:setup', '--universal', '--json'], { cwd: process.cwd(), env: serverEnv(), encoding: 'utf8' });
    });

    test('a hero lands on the page and a header template replaces the header, both rendered at once', async ({ page }) => {
        test.setTimeout(300_000);
        const pageErrors = [];
        page.on('pageerror', (e) => pageErrors.push(String(e)));
        page.on('dialog', (dialog) => dialog.accept());

        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'home');

        const hero = await insertFromLibrary(page, 'lib:hero-split-office');
        expect(hero.page_key).toBe('home');
        expect(JSON.stringify(hero.blocks)).toContain('"entity":"route"');
        const frame = page.frameLocator('#tb-live-iframe');
        await expect(frame.getByText('کسب‌وکار خود را با اطمینان آنلاین کنید')).toBeVisible({ timeout: 60_000 });
        expect(await libraryImagesLoaded(frame)).toBeGreaterThan(0);

        const header = await insertFromLibrary(page, 'lib:header-glass');
        expect(header.page_key).toBe('__header__');
        expect(header.blocks.map((b) => b.type)).toEqual(['nav_header']);
        await expect(frame.locator('a', { hasText: 'مشاوره رایگان' }).first()).toBeAttached({ timeout: 60_000 });

        expect(pageErrors).toEqual([]);
    });
});
