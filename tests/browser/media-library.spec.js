import { expect, test } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth.js';
import { VIEWPORTS } from './fixtures/viewports.js';
import { loadFixture } from './fixtures/env.js';

/* Permanent MEDIA-3 acceptance surface. The harness owns the isolated DB.
 * Fixtures that cannot be produced by clicking through the UI (a row that
 * genuinely belongs to a different site, a row with a real registered
 * usage, and enough rows to cross the real pagination threshold) are
 * seeded once by browser-harness:setup, through CustomerMediaService —
 * never a direct DB write — and handed to this file via fixture.json,
 * exactly like the page/admin fixtures every other browser spec uses. */

// A local 1366x768 breakpoint for this spec only. VIEWPORTS.desktop (1440)
// is shared by unrelated Builder/Canvas/Extension SDK specs and must not
// change; VIEWPORTS.tablet (768) and VIEWPORTS.mobile (390) already match
// what this suite needs.
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
    // The admin layout swaps navigation surface at the real CSS breakpoint
    // (public/css/customer-admin-layout.css, min-width:768px): the desktop
    // sidebar's group-toggle button below that width, the <details> mobile
    // nav (its own flat link list, no group toggle) at/above it.
    const viewport = page.viewportSize();
    if (viewport && viewport.width < 768) {
        await page.locator('details.admin-mobile-nav summary').click();
        await page.getByRole('link', { name: 'کتابخانه رسانه' }).click();
    } else {
        // Scoped to the sidebar group *toggle button* specifically: the
        // same label text also appears (non-interactively) in the mobile
        // nav's group marker, which a plain text locator matches too.
        await page.getByRole('button', { name: 'وبسایت', exact: true }).click();
        const link = page.getByRole('link', { name: 'کتابخانه رسانه' });
        await expect(link).toBeVisible();
        await link.click();
    }
    await expect(page.getByRole('heading', { name: 'کتابخانه رسانه' })).toBeVisible();
}

async function upload(page, name, mimeType, buffer, title) {
    await page.locator('input[name=file]').setInputFiles({ name, mimeType, buffer });
    await page.locator('input[name=title]').fill(title);
    await page.getByRole('button', { name: 'بارگذاری رسانه' }).click();
    await expect(page.getByText(title, { exact: true })).toBeVisible();
}

async function search(page, q) {
    await page.locator('input[name=q]').fill(q);
    await page.getByRole('button', { name: 'فیلتر' }).click();
}

async function filterCategory(page, value) {
    await page.locator('select[name=category]').selectOption(value);
    await page.getByRole('button', { name: 'فیلتر' }).click();
}

/** @returns {string} the XSRF token from the current browser context, for direct (non-form) requests. */
async function xsrfToken(page) {
    const cookies = await page.context().cookies();
    const cookie = cookies.find((c) => c.name === 'XSRF-TOKEN');
    if (!cookie) throw new Error('XSRF-TOKEN cookie not present; cannot make a forged request.');
    return decodeURIComponent(cookie.value);
}

/**
 * Tracks console/page/network noise for the duration of one test.
 * `state.ignore`, if set, is a predicate the test itself provides to mark
 * specific, deliberately-provoked failures (e.g. an intentional 404 from a
 * forged-access attempt) as expected rather than a regression.
 */
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

test.describe('MEDIA-3 customer media library', () => {
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

    test('image upload appears, decodes a real preview, and metadata persists after reload', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        const t = tag();
        await upload(page, `${t}.png`, 'image/png', png, `${t}-image`);

        const card = page.locator('article.media-card', { hasText: `${t}-image` });
        const image = card.locator('img');
        await expect(image).toBeVisible();
        await expect.poll(() => image.evaluate((el) => el.naturalWidth > 0)).toBe(true);

        await card.getByRole('link', { name: 'جزئیات' }).click();
        await expect(page.getByRole('heading', { name: 'جزئیات رسانه' })).toBeVisible();
        await page.locator('input[name=alt_text]').fill(`${t}-alt`);
        await page.locator('textarea[name=caption]').fill(`${t}-caption`);
        await page.getByRole('button', { name: 'ذخیره اطلاعات' }).click();
        await expect(page.getByRole('alert').or(page.getByRole('status'))).toContainText('اطلاعات رسانه به‌روزرسانی شد');

        await page.reload();
        await expect(page.locator('input[name=title]')).toHaveValue(`${t}-image`);
        await expect(page.locator('input[name=alt_text]')).toHaveValue(`${t}-alt`);
        await expect(page.locator('textarea[name=caption]')).toHaveValue(`${t}-caption`);
        const detailImage = page.locator('img[alt]').first();
        await expect.poll(() => detailImage.evaluate((el) => el.naturalWidth > 0)).toBe(true);
    });

    test('video upload appears and exposes a real, correctly served preview control', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        const t = tag();
        await upload(page, `${t}.mp4`, 'video/mp4', mp4(), `${t}-video`);

        const card = page.locator('article.media-card', { hasText: `${t}-video` });
        await expect(card.locator('video')).toBeAttached();
        await card.getByRole('link', { name: 'جزئیات' }).click();

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
        await upload(page, `${t}.wav`, 'audio/wav', wav(), `${t}-audio`);

        const card = page.locator('article.media-card', { hasText: `${t}-audio` });
        await card.getByRole('link', { name: 'جزئیات' }).click();

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
        await upload(page, `${t}.pdf`, 'application/pdf', pdf, `${t}-pdf`);

        const card = page.locator('article.media-card', { hasText: `${t}-pdf` });
        await card.getByRole('link', { name: 'جزئیات' }).click();

        const openLink = page.getByRole('link', { name: 'باز کردن PDF' });
        await expect(openLink).toBeVisible();
        await expect(openLink).toHaveAttribute('target', '_blank');
        await expect(openLink).toHaveAttribute('rel', /noopener/);
        const href = await openLink.getAttribute('href');
        const resp = await page.request.get(href);
        expect(resp.status()).toBe(200);
        expect(resp.headers()['content-type']).toMatch(/application\/pdf/);
    });

    test('search matches the known title and excludes an unrelated one', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        const a = tag(); const b = tag();
        await upload(page, `${a}.png`, 'image/png', png, `${a}-needle`);
        await upload(page, `${b}.png`, 'image/png', png, `${b}-other`);

        await search(page, `${a}-needle`);
        await expect(page.getByText(`${a}-needle`, { exact: true })).toBeVisible();
        await expect(page.getByText(`${b}-other`, { exact: true })).toHaveCount(0);
    });

    test('category filters show only the matching category', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        const img = tag(); const pdfTag = tag();
        await upload(page, `${img}.png`, 'image/png', png, `${img}-image`);
        await upload(page, `${pdfTag}.pdf`, 'application/pdf', pdf, `${pdfTag}-pdf`);

        await filterCategory(page, 'image');
        await expect(page.getByText(`${img}-image`, { exact: true })).toBeVisible();
        await expect(page.getByText(`${pdfTag}-pdf`, { exact: true })).toHaveCount(0);

        await filterCategory(page, 'document');
        await expect(page.getByText(`${pdfTag}-pdf`, { exact: true })).toBeVisible();
        await expect(page.getByText(`${img}-image`, { exact: true })).toHaveCount(0);

        await filterCategory(page, '');
        await expect(page.getByText(`${img}-image`, { exact: true })).toBeVisible();
        await expect(page.getByText(`${pdfTag}-pdf`, { exact: true })).toBeVisible();
    });

    test('pagination crosses the real threshold via real next/previous navigation', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        await search(page, fixture.pagination_media_title_prefix);

        const cards = page.locator('article.media-card');
        await expect(cards).toHaveCount(24);
        const firstPageFirstTitle = await cards.first().locator('strong').innerText();

        const next = page.locator('a[rel="next"]');
        await expect(next).toBeVisible();
        await next.click();
        await expect(cards.first()).toBeVisible();
        const secondPageCount = await cards.count();
        expect(secondPageCount).toBeGreaterThan(0);
        expect(secondPageCount).toBeLessThanOrEqual(24);
        await expect(cards.first().locator('strong')).not.toHaveText(firstPageFirstTitle);

        const prev = page.locator('a[rel="prev"]');
        await expect(prev).toBeVisible();
        await prev.click();
        await expect(cards).toHaveCount(24);
        await expect(cards.first().locator('strong')).toHaveText(firstPageFirstTitle);
    });

    test('deleting unused media removes it immediately and it stays removed after reload', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        const t = tag();
        await upload(page, `${t}.png`, 'image/png', png, `${t}-unused`);

        const card = page.locator('article.media-card', { hasText: `${t}-unused` });
        await card.getByRole('link', { name: 'جزئیات' }).click();
        await expect(page.locator('button:has-text("حذف رسانه")')).toBeEnabled();

        page.once('dialog', (d) => d.accept());
        await page.locator('button:has-text("حذف رسانه")').click();
        await expect(page).toHaveURL(/\/admin\/media(\?|$)/);
        await expect(page.getByRole('alert').or(page.getByRole('status'))).toContainText('رسانه حذف شد');
        await expect(page.getByText(`${t}-unused`, { exact: true })).toHaveCount(0);

        await page.reload();
        await expect(page.getByText(`${t}-unused`, { exact: true })).toHaveCount(0);
    });

    test('deleting used media is rejected by the UI and, if forced, by the server; the record remains', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        await page.goto(`/admin/media/${fixture.used_media_uuid}`);
        // The title only appears as an editable <input> value on this page,
        // not as plain text content.
        await expect(page.locator('input[name=title]')).toHaveValue(fixture.used_media_title);

        const deleteButton = page.locator('button:has-text("حذف رسانه")');
        await expect(deleteButton).toBeDisabled();

        // The disabled attribute is the real, correct product behavior a
        // normal user meets. To prove the server enforces the same rule
        // independent of client-side state, force the same real <form>
        // through past the disabled guard and submit it for real.
        page.once('dialog', (d) => d.accept());
        await deleteButton.evaluate((el) => { el.disabled = false; });
        await deleteButton.click();
        await expect(page).toHaveURL(/\/admin\/media(\?|$)/);
        await expect(page.getByRole('alert')).toContainText('این رسانه در حال استفاده است و حذف نشد');

        await page.goto(`/admin/media/${fixture.used_media_uuid}`);
        await expect(page.locator('input[name=title]')).toHaveValue(fixture.used_media_title);
    });

    test('cross-site media never appears in the index or in search results', async ({ page }) => {
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await openLibrary(page);
        await expect(page.getByText(fixture.foreign_media_title, { exact: true })).toHaveCount(0);

        await search(page, fixture.foreign_media_title);
        await expect(page.getByText(fixture.foreign_media_title, { exact: true })).toHaveCount(0);
        await expect(page.locator('article.media-card')).toHaveCount(0);
    });

    test('forged access to another site\'s media UUID is denied for GET, PATCH, and DELETE', async ({ page }) => {
        // The 404s this test deliberately provokes are the assertion, not
        // noise: Chromium logs a console error for any failed top-level
        // navigation, including an intentional 404. Real regressions
        // (5xx, JS exceptions, unrelated failed requests) still fail below.
        diag.ignore = (message) => /404/.test(message);
        attachDiagnostics(page, diag);
        await page.setViewportSize(DESKTOP_1366);
        await loginAsAdmin(page);

        const url = `/admin/media/${fixture.foreign_media_uuid}`;
        const getResp = await page.goto(url);
        expect(getResp.status()).toBe(404);

        const token = await xsrfToken(page);
        const patchResp = await page.request.fetch(url, {
            method: 'PATCH',
            headers: { 'X-XSRF-TOKEN': token, Accept: 'text/html' },
            form: { title: 'forged-should-not-apply' },
            maxRedirects: 0,
        });
        expect(patchResp.status()).toBe(404);

        const deleteResp = await page.request.fetch(url, {
            method: 'DELETE',
            headers: { 'X-XSRF-TOKEN': token, Accept: 'text/html' },
            maxRedirects: 0,
        });
        expect(deleteResp.status()).toBe(404);
    });

    for (const [label, viewport] of [['768', VIEWPORTS.tablet], ['390', VIEWPORTS.mobile]]) {
        test(`responsive usability at ${label}px: navigation, search, filter, and edit remain reachable`, async ({ page }) => {
            attachDiagnostics(page, diag);
            await page.setViewportSize(viewport);
            await openLibrary(page);

            const t = tag();
            await upload(page, `${t}.png`, 'image/png', png, `${t}-responsive`);

            await search(page, `${t}-responsive`);
            const card = page.locator('article.media-card', { hasText: `${t}-responsive` });
            await expect(card).toBeVisible();
            await filterCategory(page, '');

            await card.getByRole('link', { name: 'جزئیات' }).click();
            const titleInput = page.locator('input[name=title]');
            await expect(titleInput).toBeVisible();
            await expect(titleInput).toBeInViewport();
            await titleInput.fill(`${t}-responsive-edited`);
            // "Reachable" means scrollable-to and clickable, not necessarily
            // already inside the initial viewport — a form taller than one
            // screen is normal mobile behavior, not an off-screen defect.
            const saveButton = page.getByRole('button', { name: 'ذخیره اطلاعات' });
            await saveButton.scrollIntoViewIfNeeded();
            await expect(saveButton).toBeInViewport();
            await saveButton.click();
            await expect(page.locator('input[name=title]')).toHaveValue(`${t}-responsive-edited`);

            const deleteButton = page.locator('button:has-text("حذف رسانه")');
            await deleteButton.scrollIntoViewIfNeeded();
            await expect(deleteButton).toBeInViewport();

            if (label === '390') {
                const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
                expect(overflow, 'no meaningful horizontal overflow at 390px').toBeLessThanOrEqual(1);
            }
        });
    }
});
