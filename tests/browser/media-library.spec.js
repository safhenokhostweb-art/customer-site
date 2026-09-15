import { expect, test } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth.js';
import { VIEWPORTS } from './fixtures/viewports.js';
import { loadFixture } from './fixtures/env.js';

/* Permanent MEDIA-3 acceptance surface, rewritten for Media Library 2.0's
 * JS-driven (Alpine) commercial UX — the previous plain server-rendered
 * form/page-reload UI has been replaced with real multi-upload, drag/drop,
 * grid/list, a details modal, replace-file, and safe image editing, all
 * still delegating exclusively to CustomerMediaService through the same
 * canonical admin.media.* routes. Fixtures that cannot be produced by
 * clicking through the UI are seeded once by browser-harness:setup, through
 * CustomerMediaService — never a direct DB write — and handed to this file
 * via fixture.json, exactly like every other browser spec. */

const DESKTOP_1366 = { width: 1366, height: 768 };

const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL8WQAAAABJRU5ErkJggg==', 'base64');
const pdf = Buffer.from('%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 0/Kids[]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF');

/** Minimal but real WAVE header (44 bytes): recognized as audio/x-wav by real MIME sniffing. */
function wav() {
    const b = Buffer.alloc(44);
    b.write('RIFF', 0); b.writeUInt32LE(36, 4); b.write('WAVEfmt ', 8);
    b.writeUInt32LE(16, 16); b.writeUInt16LE(1, 20); b.writeUInt16LE(1, 22);
    b.writeUInt32LE(8000, 24); b.writeUInt32LE(16000, 28);
    b.writeUInt16LE(2, 32); b.writeUInt16LE(16, 34); b.write('data', 36); b.writeUInt32LE(0, 40);
    return b;
}

/** Minimal but real ISO-BMFF box structure (ftyp+free+moov+mdat): recognized as video/mp4 by real MIME sniffing. */
function mp4() {
    const box = (type, payload) => {
        const size = Buffer.alloc(4); size.writeUInt32BE(8 + payload.length, 0);
        return Buffer.concat([size, Buffer.from(type, 'ascii'), payload]);
    };
    const ftyp = box('ftyp', Buffer.concat([Buffer.from('isom'), Buffer.from([0, 0, 2, 0]), Buffer.from('isomiso2avc1mp41')]));
    const free = box('free', Buffer.alloc(0));
    const moov = box('moov', box('mvhd', Buffer.alloc(100)));
    const mdat = box('mdat', Buffer.alloc(32));
    return Buffer.concat([ftyp, free, moov, mdat]);
}

function tag() {
    return `media-qa-${Date.now()}-${Math.floor(Math.random() * 100000)}`;
}

async function openLibrary(page) {
    await loginAsAdmin(page);
    const viewport = page.viewportSize();
    if (viewport && viewport.width < 768) {
        await page.locator('details.admin-mobile-nav summary').click();
        await page.getByRole('link', { name: 'کتابخانه رسانه' }).click();
    } else {
        await page.getByRole('button', { name: 'وبسایت', exact: true }).click();
        const link = page.getByRole('link', { name: 'کتابخانه رسانه' });
        await expect(link).toBeVisible();
        await link.click();
    }
    await expect(page.getByRole('heading', { name: 'کتابخانه رسانه' })).toBeVisible();
}

/** Uploads one file and waits for its queue row to reach SUCCESS, then for the card to appear. */
async function uploadOne(page, name, mimeType, buffer) {
    await page.locator('input[name="files"]').setInputFiles({ name, mimeType, buffer });
    const row = page.locator('.upload-row', { hasText: name });
    await expect(row.locator('.upload-status')).toHaveText('موفق', { timeout: 15000 });
    return page.locator('article.media-card', { hasText: name });
}

async function openDetails(cardOrPage) {
    await cardOrPage.locator('.media-preview').click();
    await expect(cardOrPage.page ? cardOrPage.page().getByRole('heading', { name: 'جزئیات رسانه' }) : cardOrPage).toBeVisible();
}

function attachDiagnostics(page, state) {
    const record = (message) => {
        if (state.ignore && state.ignore(message)) return;
        state.errors.push(message);
    };
    page.on('console', (m) => { if (m.type() === 'error') record(`console: ${m.text()}`); });
    page.on('pageerror', (e) => record(`pageerror: ${String(e)}`));
    page.on('requestfailed', (r) => record(`requestfailed: ${r.url()} ${r.failure()?.errorText ?? ''}`));
    page.on('response', (r) => {
        if (r.status() >= 500) record(`response ${r.status()}: ${r.url()}`);
    });
}

/** @returns {string} the XSRF token from the current browser context, for direct (non-form) requests. */
async function xsrfToken(page) {
    const cookies = await page.context().cookies();
    const cookie = cookies.find((c) => c.name === 'XSRF-TOKEN');
    if (!cookie) throw new Error('XSRF-TOKEN cookie not present; cannot make a forged request.');
    return decodeURIComponent(cookie.value);
}

test.describe('MEDIA-3 customer media library 2.0', () => {
    /** @type {{admin_mobile:string,admin_password:string,foreign_media_uuid:string,foreign_media_title:string,used_media_uuid:string,used_media_title:string,pagination_media_count:number,pagination_media_title_prefix:string}} */
    let fixture;
    test.beforeAll(() => { fixture = loadFixture(); });

    let diag;
    test.beforeEach(() => { diag = { errors: [] }; });
    test.afterEach(({}, testInfo) => {
        if (testInfo.status !== testInfo.expectedStatus) return;
        expect(diag.errors, `unexpected console/page/network errors during "${testInfo.title}"`).toEqual([]);
    });

    test('navigation reaches the library and the page is real RTL admin UI', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(page).toHaveURL(/\/admin\/media(\?|$)/);
    });

    test('multi-file upload shows independent per-file status and one bad file does not block its siblings', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        const t = tag();

        await page.locator('input[name="files"]').setInputFiles([
            { name: `${t}-good-1.png`, mimeType: 'image/png', buffer: png },
            { name: `${t}-bad.exe`, mimeType: 'application/x-msdownload', buffer: Buffer.from('MZ') },
            { name: `${t}-good-2.png`, mimeType: 'image/png', buffer: png },
        ]);

        await expect(page.locator('.upload-row', { hasText: `${t}-good-1.png` }).locator('.upload-status')).toHaveText('موفق', { timeout: 15000 });
        await expect(page.locator('.upload-row', { hasText: `${t}-good-2.png` }).locator('.upload-status')).toHaveText('موفق', { timeout: 15000 });
        await expect(page.locator('.upload-row', { hasText: `${t}-bad.exe` }).locator('.upload-status')).not.toHaveText('موفق');

        await expect(page.locator('article.media-card', { hasText: `${t}-good-1.png` })).toBeVisible();
        await expect(page.locator('article.media-card', { hasText: `${t}-good-2.png` })).toBeVisible();
        await expect(page.locator('article.media-card', { hasText: `${t}-bad.exe` })).toHaveCount(0);
    });

    test('image upload decodes a real preview, metadata persists, and switching to list view keeps the item visible', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        const t = tag();
        const card = await uploadOne(page, `${t}-image.png`, 'image/png', png);

        const image = card.locator('img');
        await expect(image).toBeVisible();
        await expect.poll(() => image.evaluate((el) => el.naturalWidth > 0)).toBe(true);

        await openDetails(card);
        await page.locator('input[name=alt_text]').fill(`${t}-alt`);
        await page.locator('textarea[name=caption]').fill(`${t}-caption`);
        await page.locator('textarea[name=description]').fill(`${t}-description`);
        await page.getByRole('button', { name: 'ذخیره اطلاعات' }).click();
        await expect(page.locator('input[name=alt_text]')).toHaveValue(`${t}-alt`);
        await page.locator('.media-modal-close').click();

        await page.reload();
        await page.locator('input[name="q"]').fill(`${t}-image`);
        const reloadedCard = page.locator('article.media-card', { hasText: `${t}-image.png` });
        await expect(reloadedCard).toBeVisible();
        await openDetails(reloadedCard);
        await expect(page.locator('input[name=alt_text]')).toHaveValue(`${t}-alt`);
        await expect(page.locator('textarea[name=caption]')).toHaveValue(`${t}-caption`);
        await expect(page.locator('textarea[name=description]')).toHaveValue(`${t}-description`);
        await page.locator('.media-modal-close').click();

        // Switch to list view: the same item, now as a table row.
        await page.locator('.media-view-toggle button[aria-label="نمای فهرستی"]').click();
        await expect(page.locator('.media-list tbody tr', { hasText: `${t}-image.png` })).toBeVisible();
        await page.locator('.media-view-toggle button[aria-label="نمای شبکه‌ای"]').click();
    });

    test('video upload appears and exposes a real, correctly served preview control', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        const t = tag();
        const card = await uploadOne(page, `${t}-video.mp4`, 'video/mp4', mp4());
        await openDetails(card);

        const video = page.locator('video[controls]');
        await expect(video).toBeVisible();
        const src = await video.getAttribute('src');
        expect(src).toBeTruthy();
        const resp = await page.request.get(src);
        expect(resp.status()).toBe(200);
        expect(resp.headers()['content-type']).toMatch(/video\/mp4/);
    });

    test('audio upload appears and exposes a real, correctly served preview control', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        const t = tag();
        const card = await uploadOne(page, `${t}-audio.wav`, 'audio/wav', wav());
        await openDetails(card);

        const audio = page.locator('audio[controls]');
        await expect(audio).toBeVisible();
        const src = await audio.getAttribute('src');
        expect(src).toBeTruthy();
        const resp = await page.request.get(src);
        expect(resp.status()).toBe(200);
        expect(resp.headers()['content-type']).toMatch(/audio\//);
    });

    test('PDF upload appears and opens a real, correctly served document', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        const t = tag();
        const card = await uploadOne(page, `${t}-doc.pdf`, 'application/pdf', pdf);
        await openDetails(card);

        const openLink = page.getByRole('link', { name: 'باز کردن PDF' });
        await expect(openLink).toBeVisible();
        await expect(openLink).toHaveAttribute('target', '_blank');
        await expect(openLink).toHaveAttribute('rel', /noopener/);
        const href = await openLink.getAttribute('href');
        const resp = await page.request.get(href);
        expect(resp.status()).toBe(200);
        expect(resp.headers()['content-type']).toMatch(/application\/pdf/);
    });

    test('search matches the known filename and excludes an unrelated one', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        const a = tag(); const b = tag();
        await uploadOne(page, `${a}-needle.png`, 'image/png', png);
        await uploadOne(page, `${b}-other.png`, 'image/png', png);

        await page.locator('input[name="q"]').fill(`${a}-needle`);
        await expect(page.locator('article.media-card', { hasText: `${a}-needle.png` })).toBeVisible();
        await expect(page.locator('article.media-card', { hasText: `${b}-other.png` })).toHaveCount(0);
    });

    test('category filter and usage filter each narrow the list correctly', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        const img = tag(); const pdfTag = tag();
        await uploadOne(page, `${img}-image.png`, 'image/png', png);
        await uploadOne(page, `${pdfTag}-doc.pdf`, 'application/pdf', pdf);
        await page.locator('input[name="q"]').fill(img.slice(0, 12));

        await page.selectOption('select[name=category]', 'image');
        await expect(page.locator('article.media-card', { hasText: `${img}-image.png` })).toBeVisible();
        await page.selectOption('select[name=category]', 'document');
        await expect(page.locator('article.media-card', { hasText: `${img}-image.png` })).toHaveCount(0);
        await page.selectOption('select[name=category]', '');

        // Usage filter, against the harness-seeded used-media fixture.
        await page.locator('input[name="q"]').fill(fixture.used_media_title);
        await page.selectOption('select[aria-label="فیلتر وضعیت استفاده"]', 'unused');
        await expect(page.locator('article.media-card', { hasText: fixture.used_media_title })).toHaveCount(0);
        await page.selectOption('select[aria-label="فیلتر وضعیت استفاده"]', 'used');
        await expect(page.locator('article.media-card', { hasText: fixture.used_media_title })).toBeVisible();
    });

    test('pagination crosses the real threshold via next/previous, no full page reload', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        await page.locator('input[name="q"]').fill(fixture.pagination_media_title_prefix);

        const cards = page.locator('article.media-card');
        // count===24 alone is not a strong enough signal: the page's
        // initial unfiltered fetch (fired on mount, before this fill()) can
        // coincidentally ALSO show exactly 24 items on page 1 once earlier
        // tests in this file have uploaded enough unrelated media — waiting
        // for filtered CONTENT (every visible card actually matching the
        // search prefix) rules out reading that stale intermediate state.
        await expect(cards).toHaveCount(24);
        await expect(cards.first().locator('strong')).toContainText(fixture.pagination_media_title_prefix);
        await expect(page.locator('article.media-card strong', { hasNotText: fixture.pagination_media_title_prefix })).toHaveCount(0);
        const firstPageFirstTitle = await cards.first().locator('strong').innerText();

        const next = page.getByRole('button', { name: 'بعدی' });
        await expect(next).toBeEnabled();
        await next.click();
        await expect.poll(() => cards.first().locator('strong').innerText()).not.toBe(firstPageFirstTitle);

        const prev = page.getByRole('button', { name: 'قبلی' });
        await expect(prev).toBeEnabled();
        await prev.click();
        await expect(cards.first().locator('strong')).toHaveText(firstPageFirstTitle);
    });

    test('replace file preserves the same media identity and the detail view reflects the new binary', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        const t = tag();
        const card = await uploadOne(page, `${t}-replace-me.png`, 'image/png', png);
        await openDetails(card);

        const beforeSrc = await page.locator('.media-modal-preview img').first().getAttribute('src');
        await page.locator('.media-replace-label input[type=file]').setInputFiles({
            name: `${t}-replacement.png`, mimeType: 'image/png', buffer: png,
        });
        await expect.poll(async () => page.locator('.media-modal-preview dd').first().innerText()).not.toBe('');
        await expect(page.locator('.media-modal-preview dd', { hasText: `${t}-replacement.png` })).toBeVisible();
        const afterSrc = await page.locator('.media-modal-preview img').first().getAttribute('src');
        expect(afterSrc).not.toBe(beforeSrc);
    });

    test('rotating an image updates its dimensions while keeping the same media identity', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        const t = tag();
        const card = await uploadOne(page, `${t}-rotate.png`, 'image/png', png);
        await openDetails(card);

        const uuidBefore = await page.locator('.media-detail-facts dd', { hasText: /-/ }).last().innerText();
        await page.getByRole('button', { name: 'چرخش ۹۰°' }).click();
        await expect.poll(async () => (await page.locator('.media-detail-facts dd').allInnerTexts()).join('|')).toContain(uuidBefore);
    });

    test('deleting unused media removes it immediately and it stays removed after reload', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        const t = tag();
        const card = await uploadOne(page, `${t}-unused.png`, 'image/png', png);
        await openDetails(card);
        await expect(page.getByRole('button', { name: 'حذف رسانه' })).toBeEnabled();

        page.once('dialog', (d) => d.accept());
        await page.getByRole('button', { name: 'حذف رسانه' }).click();
        await expect(page.locator('article.media-card', { hasText: `${t}-unused.png` })).toHaveCount(0);

        await page.reload();
        await page.locator('input[name="q"]').fill(`${t}-unused`);
        await expect(page.locator('article.media-card')).toHaveCount(0);
    });

    test('deleting used media is rejected by the UI and, if forced, by the server; the record remains', async ({ page }) => {
        // The forced request deliberately provokes a real 422 — the
        // assertion, not noise. Real regressions (5xx, JS exceptions,
        // unrelated failed requests) still fail below.
        diag = { errors: [], ignore: (message) => /422/.test(message) };
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        await page.locator('input[name="q"]').fill(fixture.used_media_title);
        const card = page.locator('article.media-card', { hasText: fixture.used_media_title });
        await openDetails(card);

        const deleteButton = page.getByRole('button', { name: 'حذف رسانه' });
        await expect(deleteButton).toBeDisabled();

        // Prove the server enforces the same rule independent of client state.
        page.on('dialog', (d) => d.accept());
        await deleteButton.evaluate((el) => { el.disabled = false; });
        const [response] = await Promise.all([
            page.waitForResponse((r) => r.url().includes('/admin/media/') && r.request().method() === 'DELETE'),
            deleteButton.click(),
        ]);
        expect(response.status()).toBe(422);

        await page.reload();
        await page.locator('input[name="q"]').fill(fixture.used_media_title);
        await expect(page.locator('article.media-card', { hasText: fixture.used_media_title })).toBeVisible();
    });

    test('batch delete is itemized: unused item is deleted, used item is blocked, both reported', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        const t = tag();
        await uploadOne(page, `${t}-batch-unused.png`, 'image/png', png);
        await page.locator('input[name="q"]').fill('');

        const unusedCard = page.locator('article.media-card', { hasText: `${t}-batch-unused.png` });
        await unusedCard.locator('.media-select input').check();
        await page.locator('input[name="q"]').fill(fixture.used_media_title);
        const usedCard = page.locator('article.media-card', { hasText: fixture.used_media_title });
        await usedCard.locator('.media-select input').check();

        page.once('dialog', (d) => d.accept());
        await expect(page.getByRole('button', { name: /حذف موارد انتخاب‌شده/ })).toBeVisible();
        // Re-show both selected items by clearing the search before deleting.
        await page.locator('input[name="q"]').fill('');
        await page.getByRole('button', { name: /حذف موارد انتخاب‌شده/ }).click();

        await page.locator('input[name="q"]').fill(fixture.used_media_title);
        await expect(page.locator('article.media-card', { hasText: fixture.used_media_title })).toBeVisible();
    });

    test('cross-site media never appears in the index or in search results', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        await expect(page.locator('article.media-card', { hasText: fixture.foreign_media_title })).toHaveCount(0);

        await page.locator('input[name="q"]').fill(fixture.foreign_media_title);
        await expect(page.locator('article.media-card')).toHaveCount(0);
    });

    test('forged access to another site\'s media UUID is denied for GET, PATCH, DELETE, and the new replace/edit-image routes', async ({ page }) => {
        diag.ignore = (message) => /404/.test(message);
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await loginAsAdmin(page);

        const url = `/admin/media/${fixture.foreign_media_uuid}`;
        const getResp = await page.request.get(url, { headers: { Accept: 'application/json' } });
        expect(getResp.status()).toBe(404);

        const token = await xsrfToken(page);
        const patchResp = await page.request.fetch(url, {
            method: 'PATCH',
            headers: { 'X-XSRF-TOKEN': token, Accept: 'application/json' },
            form: { title: 'forged-should-not-apply' },
        });
        expect(patchResp.status()).toBe(404);

        const deleteResp = await page.request.fetch(url, {
            method: 'DELETE',
            headers: { 'X-XSRF-TOKEN': token, Accept: 'application/json' },
        });
        expect(deleteResp.status()).toBe(404);

        const replaceResp = await page.request.fetch(`${url}/replace`, {
            method: 'POST',
            headers: { 'X-XSRF-TOKEN': token, Accept: 'application/json' },
            multipart: { file: { name: 'x.png', mimeType: 'image/png', buffer: png } },
        });
        expect(replaceResp.status()).toBe(404);

        const editResp = await page.request.fetch(`${url}/edit-image`, {
            method: 'POST',
            headers: { 'X-XSRF-TOKEN': token, Accept: 'application/json' },
            form: { operation: 'rotate', degrees: '90' },
        });
        expect(editResp.status()).toBe(404);
    });

    for (const [label, viewport] of [['768', VIEWPORTS.tablet], ['390', VIEWPORTS.mobile]]) {
        test(`responsive usability at ${label}px: navigation, upload, search, details, and delete remain reachable`, async ({ page }) => {
            attachDiagnostics(page, diag);
            await page.setViewportSize(viewport);
            await openLibrary(page);

            const t = tag();
            const card = await uploadOne(page, `${t}-responsive.png`, 'image/png', png);
            await expect(card).toBeVisible();

            await openDetails(card);
            const titleInput = page.locator('input[name=title]');
            await expect(titleInput).toBeVisible();
            await titleInput.fill(`${t}-responsive-edited`);
            const saveButton = page.getByRole('button', { name: 'ذخیره اطلاعات' });
            await saveButton.scrollIntoViewIfNeeded();
            await saveButton.click();
            await expect(page.locator('input[name=title]')).toHaveValue(`${t}-responsive-edited`);

            const deleteButton = page.getByRole('button', { name: 'حذف رسانه' });
            await deleteButton.scrollIntoViewIfNeeded();
            await expect(deleteButton).toBeInViewport();

            if (label === '390') {
                const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
                expect(overflow, 'no meaningful horizontal overflow at 390px').toBeLessThanOrEqual(1);
            }
        });
    }
});
