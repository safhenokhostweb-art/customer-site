import { execFileSync } from 'node:child_process';
import { expect, test } from '@playwright/test';
import { serverEnv } from './fixtures/env.js';
import { loginAsAdmin } from './helpers/auth.js';

// A genuine, minimal, decodable 1x1 PNG — same pattern used by
// theme-builder-media-library.spec.js / media-library.spec.js.
const onePixelPng = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL8WQAAAABJRU5ErkJggg==', 'base64');
import {
    openCanvasEditor,
    openTreeTab,
    propField,
    publishButton,
    saveButton,
    switchToPage,
    treeNode,
    waitForPublish,
    waitForSaveBlocks,
} from './helpers/canvas.js';

/**
 * TASK 3/3 Phase 6.5C — real browser certification of the converged
 * website-management golden journeys, through the actual Canvas editor UI
 * (not the service/HTTP layer in isolation — see
 * tests/Feature/ThemeCanvas/HeaderMenuIntegrationTest.php and
 * BuilderInternalLinkingTest.php for the equivalent HTTP-level proofs
 * these pair with).
 *
 * Root cause of Phase 6.5B's incomplete browser proof, found and fixed
 * this phase (BLD-29): ThemeDesignController's "__header__"/"__footer__"
 * virtual Canvas pages always read/wrote the flat
 * header_blocks/footer_blocks field, while
 * BuilderLayoutService::getHeaderBlocks()/getFooterBlocks() (what the
 * public render actually uses) resolves the site's ACTIVE header/footer
 * VARIANT instead whenever a Theme (like the Universal Theme) ships
 * variants — two silently disconnected arrays. Editing "the header" in
 * Canvas saved successfully but never affected the live site. Fixed via
 * BuilderLayoutService::resolveActiveHeaderVariantKey()/resolveActiveFooterVariantKey(),
 * used by both the read and write side of the virtual pages now.
 */
let fixture;

test.describe.serial('Golden journeys (Phase 6.5C)', () => {
    test.beforeAll(() => {
        const output = execFileSync('php', [
            'artisan', 'browser-harness:setup', '--universal', '--json',
        ], { cwd: process.cwd(), env: serverEnv(), encoding: 'utf8' });
        const jsonLine = output.split('\n').find((l) => l.startsWith('BROWSER_HARNESS_JSON:'));
        fixture = JSON.parse(jsonLine.replace('BROWSER_HARNESS_JSON:', ''));

        const seedScript = "$page = \\App\\Models\\Page::firstOrCreate(['slug' => 'e2e-journey-target'], ['title' => 'صفحه هدف سفر مشتری', 'body' => '<p>x</p>', 'is_published' => true]); "
            + "$menu = \\App\\Models\\Menu::firstOrCreate(['location' => 'primary', 'site_id' => null], ['name' => 'اصلی']); "
            + "\\App\\Models\\MenuItem::updateOrCreate(['menu_id' => $menu->id, 'title' => 'صفحه هدف سفر مشتری'], ['type' => \\App\\Models\\MenuItem::TYPE_INTERNAL_PAGE, 'reference_id' => $page->id, 'order' => 0]); "
            + "$footerMenu = \\App\\Models\\Menu::firstOrCreate(['location' => 'footer', 'site_id' => null], ['name' => 'فوتر']); "
            + "\\App\\Models\\MenuItem::updateOrCreate(['menu_id' => $footerMenu->id, 'title' => 'صفحه هدف سفر مشتری'], ['type' => \\App\\Models\\MenuItem::TYPE_INTERNAL_PAGE, 'reference_id' => $page->id, 'order' => 0]);";
        execFileSync('php', ['artisan', 'tinker', '--execute', seedScript], { cwd: process.cwd(), env: serverEnv(), encoding: 'utf8' });
    });

    // ── JOURNEY D — Header selects canonical Menu → preview/public ─────

    test('JOURNEY D: setting the header nav_source to Menu through the real Canvas UI makes the public header a real, clickable Menu item', async ({ page }) => {
        test.setTimeout(90_000);
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, '__header__');
        await openTreeTab(page);

        await treeNode(page, 'qa_nav_header_5f79aa').click();
        const navSourceField = propField(page, 'nav_source');
        await expect(navSourceField).toBeVisible();
        await navSourceField.selectOption('menu');
        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();
        expect((await waitForPublish(page, () => publishButton(page).click())).ok()).toBeTruthy();

        await page.waitForLoadState('networkidle');
        try {
            await page.goto('/', { waitUntil: 'domcontentloaded' });
        } catch {
            await page.goto('/', { waitUntil: 'domcontentloaded' });
        }

        const headerLink = page.locator('.tb-nav-header-links a', { hasText: 'صفحه هدف سفر مشتری' });
        await expect(headerLink).toBeVisible();
        const href = await headerLink.getAttribute('href');
        expect(href).toContain('/page/e2e-journey-target');

        await headerLink.click();
        await page.waitForURL(/\/page\/e2e-journey-target/);
        await expect(page.locator('body')).toContainText('صفحه هدف سفر مشتری');
    });

    // ── FOOTER: Navigation → footer Menu/slot → public footer ──────────

    test('FOOTER JOURNEY: setting the footer nav_source to Menu through the real Canvas UI makes the public footer a real, clickable Menu item', async ({ page }) => {
        test.setTimeout(90_000);
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, '__footer__');
        await openTreeTab(page);

        const footerNode = page.locator('[data-testid^="tb-tree-node-qa_nav_footer"]').first();
        await footerNode.click();
        const navSourceField = propField(page, 'nav_source');
        await expect(navSourceField).toBeVisible();
        await navSourceField.selectOption('menu');
        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();
        expect((await waitForPublish(page, () => publishButton(page).click())).ok()).toBeTruthy();

        await page.waitForLoadState('networkidle');
        try {
            await page.goto('/', { waitUntil: 'domcontentloaded' });
        } catch {
            await page.goto('/', { waitUntil: 'domcontentloaded' });
        }

        const footerLink = page.locator('.tb-nav-footer-links a', { hasText: 'صفحه هدف سفر مشتری' });
        await expect(footerLink).toBeVisible();
        const href = await footerLink.getAttribute('href');
        expect(href).toContain('/page/e2e-journey-target');
    });

    // ── JOURNEY A — Create Page -> Menu -> public header click ─────────
    // Runs AFTER Journey D in this serial file, so the header is already
    // nav_source=menu (real shared state within one describe.serial block —
    // the same pattern universal-theme-capabilities.spec.js's own builder
    // lifecycle test already uses). No direct DB mutation for the
    // behavior under test, no controller shortcut, no copied URL: every
    // step below goes through the real admin UI.

    test('JOURNEY A: create a Page, add it to the primary Menu through the real page picker, see it in the public header, click through to the correct Page', async ({ page }) => {
        test.setTimeout(90_000);
        await loginAsAdmin(page);

        // 1. Create the Page through the real Pages admin UI.
        await page.goto('/admin/pages/create', { waitUntil: 'domcontentloaded' });
        await page.locator('#page-title').fill('صفحه ساخته‌شده در سفر آزمایشی');
        await page.locator('input[name="slug"]').fill('e2e-journey-created-page');
        await page.getByRole('button', { name: 'ذخیره' }).click();
        await page.waitForURL(/\/admin\/pages/);

        // 2. Add it to the real primary Menu through the real "add item" ->
        // internal_page -> search-and-pick UI (no manual URL entry).
        await page.goto('/admin/menus', { waitUntil: 'domcontentloaded' });
        await page.locator('tr', { hasText: 'اصلی' }).locator('a', { hasText: 'ویرایش و مدیریت آیتم‌ها' }).click();
        await page.waitForURL(/\/admin\/menus\/\d+\/edit/);

        await page.locator('#new-item-title').fill('صفحه ساخته‌شده در سفر آزمایشی');
        await page.locator('#new-item-type').selectOption('internal_page');
        await expect(page.locator('#new-ref-page')).toBeVisible();

        const pickerInput = page.locator('#new-ref-page .page-picker-input');
        await pickerInput.fill('سفر آزمایشی');
        await page.waitForTimeout(500);
        await page.locator('#new-ref-page .page-picker-result', { hasText: 'سفر آزمایشی' }).first().click();

        await page.getByRole('button', { name: 'افزودن' }).click();
        await page.waitForURL(/\/admin\/menus\/\d+\/edit/);
        await expect(page.locator('body')).toContainText('صفحه ساخته‌شده در سفر آزمایشی');

        // 3. Public header — no manual URL entry anywhere in this journey.
        await page.waitForLoadState('networkidle');
        try {
            await page.goto('/', { waitUntil: 'domcontentloaded' });
        } catch {
            await page.goto('/', { waitUntil: 'domcontentloaded' });
        }

        const headerLink = page.locator('.tb-nav-header-links a', { hasText: 'صفحه ساخته‌شده در سفر آزمایشی' });
        await expect(headerLink).toBeVisible();
        await headerLink.click();
        await page.waitForURL(/\/page\/e2e-journey-created-page/);
        await expect(page.locator('body')).toContainText('صفحه ساخته‌شده در سفر آزمایشی');
    });

    // ── JOURNEY C — rename a Menu item's display label; Page identity survives ──
    // Reuses the real Menu item Journey A just created through the real UI
    // (shared state within this one describe.serial file).

    test('JOURNEY C: renaming a Menu item\'s display label through the real Menu edit UI never changes which Page it points to', async ({ page }) => {
        test.setTimeout(60_000);
        await loginAsAdmin(page);

        // Deterministic targeting (data-item-id) instead of text matching —
        // two similarly-worded items now exist in this Menu (the one
        // seeded in beforeAll and the one Journey A just created), and
        // this proof is specifically about ONE exact MenuItem row.
        const itemId = execFileSync('php', ['artisan', 'tinker', '--execute',
            "echo \\App\\Models\\MenuItem::where('title', 'صفحه ساخته‌شده در سفر آزمایشی')->sole()->id;",
        ], { cwd: process.cwd(), env: serverEnv(), encoding: 'utf8' }).trim().split('\n').pop().trim();

        await page.goto('/admin/menus', { waitUntil: 'domcontentloaded' });
        await page.locator('tr', { hasText: 'اصلی' }).locator('a', { hasText: 'ویرایش و مدیریت آیتم‌ها' }).click();
        await page.waitForURL(/\/admin\/menus\/\d+\/edit/);

        const itemLi = page.locator(`li[data-item-id="${itemId}"]`);
        await itemLi.getByRole('button', { name: 'ویرایش', exact: true }).click();

        const titleField = itemLi.locator('input[name="title"]');
        await titleField.fill('برچسب تغییرنام‌یافته');
        await itemLi.locator('form[action*="items"] button[type="submit"]', { hasText: 'ذخیره' }).click();
        await page.waitForURL(/\/admin\/menus\/\d+\/edit/);
        await expect(page.locator('body')).toContainText('برچسب تغییرنام‌یافته');

        await page.waitForLoadState('networkidle');
        try {
            await page.goto('/', { waitUntil: 'domcontentloaded' });
        } catch {
            await page.goto('/', { waitUntil: 'domcontentloaded' });
        }

        // New label, but the SAME underlying Page (stable Page::id
        // identity — MenuItem::resolveUrl() never re-derived a different
        // target just because the display label changed).
        const renamedLink = page.locator('.tb-nav-header-links a', { hasText: 'برچسب تغییرنام‌یافته' });
        await expect(renamedLink).toBeVisible();
        const href = await renamedLink.getAttribute('href');
        expect(href).toContain('/page/e2e-journey-created-page');
    });

    // ── INTERNAL LINK JOURNEY — Builder button -> internal Page -> publish -> click ──

    test('INTERNAL LINK JOURNEY: pointing a Builder button at an internal Page through the real link picker resolves and is clickable on the public render', async ({ page }) => {
        test.setTimeout(90_000);
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'builder-coverage');
        await openTreeTab(page);

        await treeNode(page, 'qa_button_65af7f').click();

        await page.locator('[data-testid="tb-link-page-toggle-url"]').click();
        const searchInput = page.locator('[data-testid="tb-link-page-search-url"]');
        await expect(searchInput).toBeVisible();
        await searchInput.fill('e2e-journey-target');
        await page.waitForTimeout(500);
        await page.locator('[data-testid^="tb-link-page-result-"]').first().click();

        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();
        expect((await waitForPublish(page, () => publishButton(page).click())).ok()).toBeTruthy();

        await page.waitForLoadState('networkidle');
        try {
            await page.goto('/page/builder-coverage', { waitUntil: 'domcontentloaded' });
        } catch {
            await page.goto('/page/builder-coverage', { waitUntil: 'domcontentloaded' });
        }

        const buttonLink = page.locator('#tb-qa_button_65af7f');
        await expect(buttonLink).toBeVisible();
        const href = await buttonLink.getAttribute('href');
        expect(href).toContain('/page/e2e-journey-target');
    });

    // ── LOGO MEDIA JOURNEY — Settings -> canonical Media Library upload -> public header render ──
    //
    // Real defect found (via this exact journey, in an earlier draft) and
    // fixed: the Universal Theme's header is the Canvas `nav_header` block,
    // whose schema only ever supported a TEXT logo — the real, canonical
    // Settings > Logo upload (CustomerMediaService) had no rendering path
    // into the live header at all. Fixed via a safe, additive fallback
    // (BlockRenderer::renderNavHeader(): an un-set block-level logo_text
    // now falls back to the site's real global logo image, the same
    // <x-site-brand> resolution every other real render path already
    // uses) — zero behavior change for the canonical preset, which already
    // authors its own logo_text.

    test('LOGO MEDIA JOURNEY: uploading a site logo through the real settings form stores it via CustomerMediaService and it becomes the real public header logo once the block-level text logo is cleared', async ({ page }) => {
        test.setTimeout(90_000);
        await loginAsAdmin(page);

        // 1. Upload through the real canonical Media Library write path.
        await page.goto('/admin/settings', { waitUntil: 'domcontentloaded' });
        await page.locator('input[name="logo"]').setInputFiles({ name: 'logo.png', mimeType: 'image/png', buffer: onePixelPng });
        await page.getByRole('button', { name: 'آپلود لوگو' }).click();
        await page.waitForURL(/\/admin\/settings/);
        await expect(page.locator('text=لوگوی فعلی')).toBeVisible();

        // 2. Clear the header block's own text logo through the real Canvas
        // UI, so it falls back to the real site logo just uploaded.
        await openCanvasEditor(page);
        await switchToPage(page, '__header__');
        await openTreeTab(page);
        await treeNode(page, 'qa_nav_header_5f79aa').click();
        const logoField = propField(page, 'logo_text');
        await logoField.fill('');
        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();
        expect((await waitForPublish(page, () => publishButton(page).click())).ok()).toBeTruthy();

        await page.waitForLoadState('networkidle');
        try {
            await page.goto('/', { waitUntil: 'domcontentloaded' });
        } catch {
            await page.goto('/', { waitUntil: 'domcontentloaded' });
        }

        const logoImg = page.locator('.tb-nav-header-logo img');
        await expect(logoImg).toBeVisible();
        const src = await logoImg.getAttribute('src');
        expect(src).toBeTruthy();
    });

    // ── JOURNEY E — Appearance change through canonical authority → public render ──

    test('JOURNEY E: changing the global primary color through the real Canvas UI is the ONE authority that reaches the public render, and syncs the legacy compatibility store', async ({ page }) => {
        test.setTimeout(90_000);
        await loginAsAdmin(page);
        await openCanvasEditor(page);

        await page.getByRole('button', { name: 'رنگ‌ها' }).click();
        const primaryColorField = page.locator('label:has-text("رنگ اصلی") + div input[type="text"]');
        await expect(primaryColorField).toBeVisible();
        await primaryColorField.fill('#1a2b3c');
        await primaryColorField.dispatchEvent('input');
        await page.getByRole('button', { name: 'اعمال رنگ‌ها' }).click();
        await expect(page.getByText('رنگ‌ها ذخیره شد')).toBeVisible();

        expect((await waitForPublish(page, () => publishButton(page).click())).ok()).toBeTruthy();

        await page.waitForLoadState('networkidle');
        try {
            await page.goto('/', { waitUntil: 'domcontentloaded' });
        } catch {
            await page.goto('/', { waitUntil: 'domcontentloaded' });
        }

        // ONE authority reaches the public render: the real --tb-primary
        // token (Canvas global_styles, via ThemeTokenNormalizer). The
        // legacy ThemeSetting compatibility store is kept in sync by the
        // SAME publish action automatically (see
        // ThemeDesignController::syncThemeSettingFromCanvasGlobalStyles(),
        // proven directly by AppearanceConvergenceTest) — the customer
        // never sees or touches it as a second surface while Canvas is
        // active (also proven by AppearanceConvergenceTest), so it is
        // deliberately not re-asserted through the UI here.
        const html = await page.content();
        expect(html).toContain('--tb-primary: #1a2b3c');
    });

    test('FAVICON MEDIA JOURNEY: uploading a favicon through the real settings form stores it via CustomerMediaService and renders it on the real Canvas public layout', async ({ page }) => {
        test.setTimeout(60_000);
        await loginAsAdmin(page);

        await page.goto('/admin/settings', { waitUntil: 'domcontentloaded' });
        await page.locator('input[name="favicon"]').setInputFiles({ name: 'favicon.png', mimeType: 'image/png', buffer: onePixelPng });
        await page.getByRole('button', { name: 'آپلود آیکن' }).click();
        await page.waitForURL(/\/admin\/settings/);

        // Real Media Library identity, not an ad-hoc upload path — the
        // "current icon" preview only renders when CustomerMediaService
        // actually resolved a real stored media row.
        await expect(page.locator('text=آیکن فعلی')).toBeVisible();

        await page.goto('/', { waitUntil: 'domcontentloaded' });
        const faviconLink = page.locator('link[rel="icon"]');
        await expect(faviconLink).toHaveCount(1);
        const href = await faviconLink.getAttribute('href');
        expect(href).toBeTruthy();
        expect(href).not.toBe('');
    });
});
