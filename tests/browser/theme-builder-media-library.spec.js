import { expect, test } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth.js';
import { VIEWPORTS } from './fixtures/viewports.js';
import { loadFixture, BASE_URL } from './fixtures/env.js';
import {
    openCanvasEditor,
    reloadCanvasEditor,
    switchToPage,
    paletteAddButton,
    saveButton,
    publishButton,
    waitForSaveBlocks,
    waitForPublish,
    publicPage,
    openTreeTab,
    treeNode,
} from './helpers/canvas.js';

/**
 * MEDIA-4 permanent acceptance surface: the Theme Builder's media picker
 * converged onto the canonical Core Media Library. Reuses the existing
 * Canvas editor harness (tests/browser/helpers/canvas.js) — no second
 * server, no second DB bootstrap, no disconnected fixture.
 *
 * Known, evidenced, out-of-scope exclusions (not media-architecture gaps):
 *  - Header/Footer (__header__/__footer__ virtual pages): nav_header/
 *    nav_footer are not registered in BlockRegistry, so the Canvas
 *    editor's props panel renders no fields at all for them today —
 *    pre-existing, unrelated to media identity. Not exercised here.
 *  - hero.background_image and the `video` block: renderHero() and
 *    renderVideo() (the latter absent entirely) don't apply/render these
 *    props on the public page today — pre-existing rendering gaps, also
 *    unrelated to media identity. section.background_image (which DOES
 *    render) is exercised instead to prove the same mechanism end-to-end.
 */

const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL8WQAAAABJRU5ErkJggg==', 'base64');

function tag() {
    return `tb-media-qa-${Date.now()}-${Math.floor(Math.random() * 100000)}`;
}

function attachDiagnostics(page, state) {
    page.on('console', (m) => { if (m.type() === 'error') state.errors.push(`console: ${m.text()}`); });
    page.on('pageerror', (e) => state.errors.push(`pageerror: ${String(e)}`));
    page.on('response', (r) => { if (r.status() >= 500) state.errors.push(`response ${r.status()}: ${r.url()}`); });
}

/** Uploads `title` through the main Media Library (وبسایت → کتابخانه رسانه) and returns its uuid. */
async function uploadThroughMainLibrary(page, title) {
    await page.goto(`${BASE_URL}/admin/media`, { waitUntil: 'domcontentloaded' });
    await page.locator('input[name=file]').setInputFiles({ name: `${title}.png`, mimeType: 'image/png', buffer: png });
    await page.locator('input[name=title]').fill(title);
    await page.getByRole('button', { name: 'بارگذاری رسانه' }).click();
    const card = page.locator('article.media-card', { hasText: title });
    await expect(card).toBeVisible();
    await card.getByRole('link', { name: 'جزئیات' }).click();
    const uuid = (await page.url()).split('/media/')[1];
    return uuid;
}

async function openPickerFor(page, propKey) {
    await page.locator(`[data-testid="tb-media-picker-${propKey}"]`).click();
    await expect(page.locator('[role="dialog"]', { hasText: 'مدیریت رسانه' })).toBeVisible();
}

/**
 * Waits for the real, debounced network round-trip the search input
 * triggers — not a fixed sleep past the 400ms debounce, which raced the
 * actual response and could leave a stale grid under fast-clicking.
 * Matches on the query string itself so an earlier, still-in-flight
 * unfiltered fetch (from opening the picker) can never be mistaken for it.
 */
async function searchPicker(page, q) {
    const [response] = await Promise.all([
        page.waitForResponse((res) => res.url().includes('theme-design/canvas/media') && res.url().includes(`q=${encodeURIComponent(q)}`)),
        page.locator('[data-testid="tb-media-search"]').fill(q),
    ]);
    // The response resolving only proves the network round-trip finished —
    // wait for Alpine's own reactive state to reflect it too, so a click
    // right after this call can never land on a stale, about-to-be-replaced
    // grid item.
    await page.waitForFunction(() => {
        const el = document.querySelector('[x-data]');
        return window.Alpine?.$data(el)?.mediaModal?.loading === false;
    });
    return response;
}

/**
 * Builder integrity final gate (2026-09-12): the category filter used a
 * fixed 200ms sleep instead of this same real network+reactive-state wait
 * — a genuine timing race under load (5/5 pass in isolation, confirmed
 * failing only under full-suite contention), not an application defect.
 * Same real-wait pattern as searchPicker() above, for the category select.
 */
async function selectPickerCategory(page, category) {
    const [response] = await Promise.all([
        page.waitForResponse((res) => res.url().includes('theme-design/canvas/media') && res.url().includes(`category=${encodeURIComponent(category)}`)),
        page.locator('[data-testid="tb-media-category"]').selectOption(category),
    ]);
    await page.waitForFunction(() => {
        const el = document.querySelector('[x-data]');
        return window.Alpine?.$data(el)?.mediaModal?.loading === false;
    });
    return response;
}

/**
 * Publishes and waits for the real condition that matters: the editor's
 * own post-publish `setTimeout(() => window.location.reload(), 800)`
 * (pre-existing behavior, unrelated to this suite) actually completing —
 * not just the /publish fetch response. Navigating away before that
 * self-reload fires races it and aborts one of the two navigations.
 */
async function publishAndSettle(page) {
    const resp = await waitForPublish(page, () => publishButton(page).click());
    await page.waitForEvent('load', { timeout: 10_000 });
    await page.locator('[data-testid="tb-canvas-save-button"]').waitFor({ state: 'visible', timeout: 15_000 });
    return resp;
}

async function confirmPicker(page) {
    await page.locator('[data-testid="tb-media-confirm"]').click();
}

test.describe('MEDIA-4 Theme Builder media library convergence', () => {
    let fixture;
    test.beforeAll(() => { fixture = loadFixture(); });

    let diag;
    test.beforeEach(() => { diag = { errors: [] }; });
    test.afterEach(({}, testInfo) => {
        if (testInfo.status !== testInfo.expectedStatus) return;
        expect(diag.errors, `unexpected console/page/network errors during "${testInfo.title}"`).toEqual([]);
    });

    test('Flow X: media uploaded in the main library is findable and selectable from the Builder picker, saves, reloads, and publishes correctly', async ({ page }) => {
        test.setTimeout(120_000); // multi-step flow against the single-threaded harness server
        attachDiagnostics(page, diag);
        await page.setViewportSize({ width: 1366, height: 768 });
        await loginAsAdmin(page);
        const title = tag();
        await uploadThroughMainLibrary(page, title);

        await openCanvasEditor(page);
        await switchToPage(page, 'home');
        await waitForSaveBlocks(page, () => paletteAddButton(page, 'image').click());

        await openPickerFor(page, 'url');
        await searchPicker(page, title);
        await page.locator(`[data-testid^="tb-media-item-"]`).first().click();
        await confirmPicker(page);
        await expect(page.locator('[data-testid="tb-media-preview-url"]')).toBeVisible();

        const saveResponse = await waitForSaveBlocks(page, () => saveButton(page).click());
        expect(saveResponse.ok()).toBeTruthy();

        await reloadCanvasEditor(page);
        await switchToPage(page, 'home');
        const loadedBlocks = await page.evaluate(() => {
            const el = document.querySelector('[x-data]');
            return window.Alpine.$data(el).pages['home'].blocks;
        });
        const imageBlock = loadedBlocks.find((b) => b.type === 'image');
        expect(imageBlock?.props?.url?.media_id).toBeTruthy();

        // Re-select the block through the real tree UI (not just reading
        // state) to prove the property panel also re-resolves the
        // canonical value to a real preview after a genuine reload.
        await openTreeTab(page);
        await treeNode(page, imageBlock.id).click();
        await expect(page.locator('[data-testid="tb-media-preview-url"]')).toBeVisible();

        await publishAndSettle(page);
        await publicPage(page, null);
        const img = page.locator('img.tb-img').last();
        await expect(img).toBeVisible();
        await expect.poll(() => img.evaluate((el) => el.naturalWidth > 0)).toBe(true);
    });

    test('Flow Y: media uploaded from the Builder picker appears, is reusable, and is searchable in the main library', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize({ width: 1366, height: 768 });
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'home');
        await waitForSaveBlocks(page, () => paletteAddButton(page, 'image').click());

        await openPickerFor(page, 'url');
        await page.locator('[data-testid="tb-media-upload-tab"]').click();
        const title = tag();
        await page.locator('[data-testid="tb-media-file-input"]').setInputFiles({ name: `${title}.png`, mimeType: 'image/png', buffer: png });
        await expect(page.locator('[data-testid="tb-media-confirm"]')).toBeVisible();
        await confirmPicker(page);
        await waitForSaveBlocks(page, () => saveButton(page).click());

        // Reusable immediately: open the picker again and find the same
        // upload by its own generated filename (the library is shared with
        // pre-seeded fixture media, so searching, not a raw total count, is
        // the real proof of reuse).
        await openPickerFor(page, 'url');
        await searchPicker(page, title);
        await expect(page.locator('[data-testid^="tb-media-item-"]')).toHaveCount(1);

        await page.goto(`${BASE_URL}/admin/media`, { waitUntil: 'domcontentloaded' });
        await page.locator('input[name=q]').fill(title);
        await page.getByRole('button', { name: 'فیلتر' }).click();
        await expect(page.locator('article.media-card')).toHaveCount(1);
    });

    test('background image on a section block: picks, saves, reloads, and renders as real CSS on the public page', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize({ width: 1366, height: 768 });
        await loginAsAdmin(page);
        const title = tag();
        await uploadThroughMainLibrary(page, title);

        await openCanvasEditor(page);
        await switchToPage(page, 'home');
        await waitForSaveBlocks(page, () => paletteAddButton(page, 'section').click());

        await openPickerFor(page, 'background_image');
        await searchPicker(page, title);
        await page.locator(`[data-testid^="tb-media-item-"]`).first().click();
        await confirmPicker(page);
        const saveResponse = await waitForSaveBlocks(page, () => saveButton(page).click());
        expect(saveResponse.ok()).toBeTruthy();

        await publishAndSettle(page);
        await publicPage(page, null);
        const section = page.locator('.tb-section[style*="background-image"]').last();
        await expect(section).toBeVisible();
    });

    test('background image on a hero block: picks, saves, reloads, and renders as real CSS on the public page', async ({ page }) => {
        // Audit finding, fixed this task: hero.background_image was an
        // exposed media control whose value the real render path (the
        // Element SDK's HeroElement, gated by its own getSchema()) silently
        // dropped before ever reaching CSS output. HeroElement.php now
        // declares the field and applies it.
        test.setTimeout(90_000);
        attachDiagnostics(page, diag);
        await page.setViewportSize({ width: 1366, height: 768 });
        await loginAsAdmin(page);
        const title = tag();
        await uploadThroughMainLibrary(page, title);

        await openCanvasEditor(page);
        await switchToPage(page, 'home');
        await waitForSaveBlocks(page, () => paletteAddButton(page, 'hero').click());

        await openPickerFor(page, 'background_image');
        await searchPicker(page, title);
        await page.locator(`[data-testid^="tb-media-item-"]`).first().click();
        await confirmPicker(page);
        const saveResponse = await waitForSaveBlocks(page, () => saveButton(page).click());
        expect(saveResponse.ok()).toBeTruthy();

        await publishAndSettle(page);
        await publicPage(page, null);
        const hero = page.locator('.tb-hero[style*="background-image"]').last();
        await expect(hero).toBeVisible();
    });

    test('gallery repeater: pick, replace, and remove an item image; structured data round-trips', async ({ page }) => {
        test.setTimeout(120_000); // three save/reload cycles against the single-threaded harness server
        attachDiagnostics(page, diag);
        await page.setViewportSize({ width: 1366, height: 768 });
        await loginAsAdmin(page);
        const titleA = tag(); const titleB = tag();
        await uploadThroughMainLibrary(page, titleA);
        await uploadThroughMainLibrary(page, titleB);

        await openCanvasEditor(page);
        await switchToPage(page, 'home');
        await waitForSaveBlocks(page, () => paletteAddButton(page, 'gallery').click());
        await page.locator('[data-testid="tb-structured-add-items"]').click();

        await page.locator('[data-testid="tb-structured-media-items-0-url"]').click();
        await expect(page.locator('[role="dialog"]', { hasText: 'مدیریت رسانه' })).toBeVisible();
        await searchPicker(page, titleA);
        await page.locator(`[data-testid^="tb-media-item-"]`).first().click();
        await confirmPicker(page);
        await expect(page.locator('[data-testid="tb-structured-preview-items-0-url"]')).toBeVisible();

        const saveResponse = await waitForSaveBlocks(page, () => saveButton(page).click());
        expect(saveResponse.ok()).toBeTruthy();

        await reloadCanvasEditor(page);
        await switchToPage(page, 'home');
        let blocks = await page.evaluate(() => {
            const el = document.querySelector('[x-data]');
            return window.Alpine.$data(el).pages['home'].blocks;
        });
        let gallery = blocks.find((b) => b.type === 'gallery');
        const originalId = gallery.props.items[0].url.media_id;
        expect(originalId).toBeTruthy();

        // A reload leaves no block selected — re-select through the real
        // tree UI before interacting with its props panel again.
        await openTreeTab(page);
        await treeNode(page, gallery.id).click();

        // Replace with the second upload.
        await page.locator('[data-testid="tb-structured-media-items-0-url"]').click();
        await searchPicker(page, titleB);
        await page.locator(`[data-testid^="tb-media-item-"]`).first().click();
        await confirmPicker(page);
        await waitForSaveBlocks(page, () => saveButton(page).click());
        await reloadCanvasEditor(page);
        await switchToPage(page, 'home');
        blocks = await page.evaluate(() => {
            const el = document.querySelector('[x-data]');
            return window.Alpine.$data(el).pages['home'].blocks;
        });
        gallery = blocks.filter((b) => b.type === 'gallery').pop();
        const replacedId = gallery.props.items[0].url.media_id;
        expect(replacedId).toBeTruthy();
        expect(replacedId).not.toBe(originalId);

        await openTreeTab(page);
        await treeNode(page, gallery.id).click();

        // Remove.
        await page.locator('[data-testid="tb-structured-clear-items-0-url"]').click();
        await waitForSaveBlocks(page, () => saveButton(page).click());
        await reloadCanvasEditor(page);
        await switchToPage(page, 'home');
        blocks = await page.evaluate(() => {
            const el = document.querySelector('[x-data]');
            return window.Alpine.$data(el).pages['home'].blocks;
        });
        gallery = blocks.filter((b) => b.type === 'gallery').pop();
        // '' and null both mean "no media selected" to the resolver
        // (CustomerMediaResolver::resolveValue) and the renderer's own
        // empty-URL placeholder — the clear action's real contract.
        expect(gallery.props.items[0].url || '').toBe('');
    });

    test('team_cards repeater (JSON storage): member image round-trips through save/reload', async ({ page }) => {
        test.setTimeout(90_000);
        attachDiagnostics(page, diag);
        await page.setViewportSize({ width: 1366, height: 768 });
        await loginAsAdmin(page);
        const title = tag();
        await uploadThroughMainLibrary(page, title);

        await openCanvasEditor(page);
        await switchToPage(page, 'home');
        // team_cards lives in the "محتوای پویا" (dynamic) palette category,
        // collapsed by default — unlike layout/content, which the earlier
        // tests' block types (image/section/gallery) live in and which are
        // expanded by default.
        await page.locator('[data-testid="tb-palette-category-dynamic"]').click();
        await waitForSaveBlocks(page, () => paletteAddButton(page, 'team_cards').click());
        await page.locator('[data-testid="tb-structured-add-members"]').click();

        await page.locator('[data-testid="tb-structured-media-members-0-image"]').click();
        await searchPicker(page, title);
        await page.locator(`[data-testid^="tb-media-item-"]`).first().click();
        await confirmPicker(page);
        const saveResponse = await waitForSaveBlocks(page, () => saveButton(page).click());
        expect(saveResponse.ok()).toBeTruthy();

        await reloadCanvasEditor(page);
        await switchToPage(page, 'home');
        const blocks = await page.evaluate(() => {
            const el = document.querySelector('[x-data]');
            return window.Alpine.$data(el).pages['home'].blocks;
        });
        const team = blocks.filter((b) => b.type === 'team_cards').pop();
        const members = typeof team.props.members === 'string' ? JSON.parse(team.props.members) : team.props.members;
        expect(members[0].image.media_id).toBeTruthy();
    });

    test('picker search and category filter narrow the real result set', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize({ width: 1366, height: 768 });
        await loginAsAdmin(page);
        const needle = tag(); const other = tag();
        await uploadThroughMainLibrary(page, needle);
        await uploadThroughMainLibrary(page, other);

        await openCanvasEditor(page);
        await switchToPage(page, 'home');
        await waitForSaveBlocks(page, () => paletteAddButton(page, 'image').click());
        await openPickerFor(page, 'url');

        await searchPicker(page, needle);
        await expect(page.locator('[data-testid^="tb-media-item-"]')).toHaveCount(1);

        // Real reactive-state wait for the debounced clear itself (q=''),
        // so it can never resolve after — and silently overwrite — the
        // category-filtered fetch that follows. Alpine state, not a URL
        // guess: fetchMedia() omits the 'q' param entirely once
        // mediaModal.q is falsy, so there is no stable query string to
        // match on for "the cleared request" specifically.
        await page.locator('[data-testid="tb-media-search"]').fill('');
        await page.waitForFunction(() => {
            const el = document.querySelector('[x-data]');
            const modal = window.Alpine?.$data(el)?.mediaModal;
            return modal?.q === '' && modal?.loading === false;
        });
        await selectPickerCategory(page, 'document');
        await expect(page.locator('[data-testid^="tb-media-item-"]')).toHaveCount(0);
    });

    test('a forged foreign media_id is accepted into the mutable draft but rejected at publish (real HTTP), leaving the theme instance safe', async ({ page }) => {
        diag.ignore = () => true; // this test asserts on raw HTTP responses only, not page state
        attachDiagnostics(page, diag);
        await loginAsAdmin(page);

        const cookies = await page.context().cookies();
        const token = decodeURIComponent(cookies.find((c) => c.name === 'XSRF-TOKEN').value);
        const forgedBlocks = [{ id: 'forged-1', type: 'image', props: { url: { media_id: fixture.foreign_media_uuid } } }];

        const draftResp = await page.request.put(`${BASE_URL}/admin/theme-design/canvas/save-blocks`, {
            headers: { 'X-XSRF-TOKEN': token, Accept: 'application/json' },
            data: { page_key: 'home', blocks: forgedBlocks },
        });
        expect(draftResp.ok()).toBeTruthy();

        const publishResp = await page.request.post(`${BASE_URL}/admin/theme-design/canvas/publish`, {
            headers: { 'X-XSRF-TOKEN': token, Accept: 'application/json' },
        });
        expect(publishResp.ok()).toBeFalsy();
    });

    for (const [label, viewport] of [['768', VIEWPORTS.tablet], ['390', VIEWPORTS.mobile]]) {
        test(`media picker is usable at ${label}px: search, cards, select, and upload all reachable`, async ({ page }) => {
            attachDiagnostics(page, diag);
            await loginAsAdmin(page);
            const title = tag();
            await uploadThroughMainLibrary(page, title);

            // Reach the editor and add the block at desktop width first —
            // the Canvas editor's own page-tab/toolbar responsive layout is
            // a separate, pre-existing concern from the media picker this
            // test is actually verifying. Resize down only for the picker
            // interaction itself.
            await page.setViewportSize({ width: 1366, height: 768 });
            await openCanvasEditor(page);
            await switchToPage(page, 'home');
            await waitForSaveBlocks(page, () => paletteAddButton(page, 'image').click());

            await page.setViewportSize(viewport);
            await openPickerFor(page, 'url');

            const search = page.locator('[data-testid="tb-media-search"]');
            await expect(search).toBeVisible();
            await expect(search).toBeInViewport();
            await searchPicker(page, title);
            const card = page.locator('[data-testid^="tb-media-item-"]').first();
            await expect(card).toBeVisible();
            await card.click();
            const confirmBtn = page.locator('[data-testid="tb-media-confirm"]');
            await expect(confirmBtn).toBeInViewport();
            await confirmBtn.click();

            if (label === '390') {
                const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
                expect(overflow, 'no meaningful horizontal overflow at 390px').toBeLessThanOrEqual(1);
            }
        });
    }
});
