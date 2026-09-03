import { expect, test } from '@playwright/test';
import { BASE_URL, loadFixture } from './fixtures/env.js';
import { WITH_DEMO_SUITE, WITH_NO_EXTENSIONS, WITH_THROWING_SUITE } from './fixtures/extension-env.js';
import { VIEWPORTS } from './fixtures/viewports.js';
import { loginAsAdmin } from './helpers/auth.js';
import {
    addBlockButton,
    blockCount,
    canvasFrame,
    openBuilder,
    propField,
    publicPage,
    reloadBuilder,
    saveButton,
    waitForSave,
} from './helpers/builder.js';
import { restartServer } from './helpers/server.js';

/**
 * Phase 3E-H-D — Extension SDK V1 real-browser verification, built on the
 * D0 harness. Exercises the REAL production path: real login, real
 * /builder/edit/{id} route, real palette/canvas/controls, real PUT save,
 * real reload, real /builder/preview, and the real public /page/{slug}
 * route — for the test-only acme/demo-suite fixture
 * (tests/Support/BrowserExtension), registered only via
 * BROWSER_TEST_EXTENSION_PROVIDERS on this harness's own server process.
 */

function collectConsoleErrors(page) {
    const errors = [];
    page.on('console', (msg) => {
        if (msg.type() === 'error') errors.push(`${msg.text()} (${msg.location().url})`);
    });
    page.on('pageerror', (err) => errors.push(String(err)));
    page.on('dialog', (dialog) => dialog.accept());
    return errors;
}

function realErrorsOnly(errors) {
    return errors.filter(
        (e) =>
            !/favicon|ERR_INTERNET_DISCONNECTED/i.test(e) &&
            !/(notification-center|system-alerts)\/unread-count|user-tickets\/check-new/i.test(e),
    );
}

test.describe('Extension SDK — demo_card real path', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test.beforeAll(async () => {
        await restartServer(WITH_DEMO_SUITE);
    });

    test('demo_card appears exactly once in the real palette, correctly labeled, no duplicates', async ({ page }) => {
        const errors = collectConsoleErrors(page);
        const fixture = loadFixture();

        await loginAsAdmin(page);
        await openBuilder(page, fixture.extension_page_id);

        const demoCardButtons = page.locator('[data-testid="tb-add-block-ext:acme:demo_card"]');
        await expect(demoCardButtons).toHaveCount(1);
        await expect(demoCardButtons).toContainText('Acme Demo Card');

        // Existing built-in/canonical entries remain present and unique —
        // the extension-only adapter must never duplicate them.
        await expect(page.locator('[data-testid="tb-add-block-heading"]')).toHaveCount(1);
        await expect(page.locator('[data-testid="tb-add-block-text"]')).toHaveCount(1);

        await page.screenshot({ path: 'tests/browser/.runtime/hd-palette.png' });
        expect(realErrorsOnly(errors)).toHaveLength(0);
    });

    test('demo_card: real authoring, save, full reload, preview, public page', async ({ page }) => {
        const errors = collectConsoleErrors(page);
        const fixture = loadFixture();

        await loginAsAdmin(page);
        await openBuilder(page, fixture.extension_page_id);

        const cardButton = addBlockButton(page, 'ext:acme:demo_card');
        await cardButton.click();

        const uniqueTitle = `Demo Title ${Date.now()}`;
        const uniqueContent = `<p>Demo content ${Date.now()}</p>`;

        const titleField = propField(page, 'title').locator('input, textarea');
        await expect(titleField).toBeVisible();
        await titleField.fill(uniqueTitle);

        const contentField = propField(page, 'content').locator('input, textarea');
        await expect(contentField).toBeVisible();
        await contentField.fill(uniqueContent);

        const saveResponse = await waitForSave(page, () => saveButton(page).click());
        expect(saveResponse.ok()).toBeTruthy();

        // Full reload — real server round trip, not in-memory Alpine state.
        await reloadBuilder(page);
        await expect(blockCount(page)).toHaveText('1 ویجت');

        const frame = canvasFrame(page);
        await expect(frame.getByText(uniqueTitle)).toBeVisible({ timeout: 15_000 });
        await expect(frame.locator('.ext-acme-demo-card-content')).toContainText('Demo content');

        // Real preview endpoint (same round trip reloadBuilder awaited) —
        // no [invalid]/[error] placeholder anywhere in the canvas.
        const canvasHtml = await page.locator('#live-canvas').evaluate((el) => el.contentDocument?.body?.innerHTML ?? '');
        expect(canvasHtml).not.toContain('tb-placeholder-invalid');
        expect(canvasHtml).not.toContain('tb-placeholder-error');

        // Real public page — same saved content, no Builder-only dependency.
        await publicPage(page, fixture.extension_page_slug);
        await expect(page.locator('.ext-acme-demo-card')).toBeVisible();
        await expect(page.locator('.ext-acme-demo-card h3')).toHaveText(uniqueTitle);
        await expect(page.locator('.ext-acme-demo-card-content')).toContainText('Demo content');

        await page.screenshot({ path: 'tests/browser/.runtime/hd-public-page.png' });
        expect(realErrorsOnly(errors)).toHaveLength(0);
    });

    test('demo_card asset (HasAssets): emitted, real network 200, deduped, isolated from a control page', async ({ page }) => {
        const errors = collectConsoleErrors(page);
        const fixture = loadFixture();

        const assetRequests = [];
        page.on('response', (res) => {
            if (res.url().includes('/vendor/acme/demo-card.css')) assetRequests.push(res.status());
        });

        await loginAsAdmin(page);
        await openBuilder(page, fixture.extension_page_id);

        // Page already has one demo_card from the previous test; add a
        // second so we can prove dedup, not just single-block emission.
        await addBlockButton(page, 'ext:acme:demo_card').click();
        await propField(page, 'title').locator('input, textarea').fill('Second card');
        await propField(page, 'content').locator('input, textarea').fill('<p>second</p>');
        const saveResponse = await waitForSave(page, () => saveButton(page).click());
        expect(saveResponse.ok()).toBeTruthy();

        await publicPage(page, fixture.extension_page_slug);
        await expect(page.locator('.ext-acme-demo-card')).toHaveCount(2);

        const cssLinks = page.locator('link[href*="/vendor/acme/demo-card.css"]');
        await expect(cssLinks).toHaveCount(1); // deduped: 2 cards, 1 asset tag
        expect(assetRequests).toContain(200);

        // Isolation: a page that never used the extension must not emit it.
        assetRequests.length = 0;
        await publicPage(page, fixture.control_page_slug);
        await expect(page.locator('link[href*="/vendor/acme/demo-card.css"]')).toHaveCount(0);
        expect(assetRequests).toHaveLength(0);

        await page.screenshot({ path: 'tests/browser/.runtime/hd-asset-dedup.png' });
        expect(realErrorsOnly(errors)).toHaveLength(0);
    });

    test('demo_card stored-XSS: malicious title/content neutralized through real save → reload → preview → public page', async ({ page }) => {
        const errors = collectConsoleErrors(page);
        const fixture = loadFixture();
        let dialogFired = false;
        page.on('dialog', () => { dialogFired = true; });

        await loginAsAdmin(page);
        await openBuilder(page, fixture.extension_page_id);
        await addBlockButton(page, 'ext:acme:demo_card').click();

        // title is plain-escaped; content goes through the canonical
        // HtmlSanitizer (rich HTML, but no scripts/handlers/javascript: URLs).
        await propField(page, 'title').locator('input, textarea').fill('<img src=x onerror=alert(1)>XSS-TITLE');
        await propField(page, 'content').locator('input, textarea').fill(
            '<p>safe</p><a href="javascript:alert(2)" onmouseover="alert(3)">click</a><script>alert(4)</script><img src=x onerror=alert(5)>',
        );

        const saveResponse = await waitForSave(page, () => saveButton(page).click());
        expect(saveResponse.ok()).toBeTruthy();
        await reloadBuilder(page);

        // waitForSave/reloadBuilder resolve on the network response, which
        // can race the app's own synchronous doc.write() into the iframe —
        // wait for real rendered content before reading innerHTML. Scoped
        // to the extension's own rendered card — the canvas editor shell
        // legitimately embeds its own <script> for block selection, which
        // is not part of what this test is checking.
        await expect(canvasFrame(page).getByText('XSS-TITLE')).toBeVisible({ timeout: 15_000 });
        // Real DOM attribute/element queries, not raw substring matching:
        // the escaped title text legitimately CONTAINS the literal string
        // "onerror=" as inert text (e.g. "&lt;img ... onerror=...&gt;"),
        // which is exactly the safe, correct behavior — the check must
        // confirm no element actually CARRIES a live onerror/onmouseover
        // attribute or javascript: href, not merely absence of a substring.
        const cardProbe = await page.locator('#live-canvas').evaluate(() => {
            const doc = document.getElementById('live-canvas').contentDocument;
            // Earlier tests in this file leave other .ext-acme-demo-card
            // elements on the same shared page — select the one this test
            // itself created, not positionally the first/last.
            const card = Array.from(doc.querySelectorAll('.ext-acme-demo-card')).find((el) => el.textContent.includes('XSS-TITLE'));
            return {
                html: card?.outerHTML ?? '',
                hasLiveHandler: !!card?.querySelector('[onerror], [onmouseover], [onclick]:not(.bldr-blk):not(.btb)'),
                hasScriptTag: !!card?.querySelector('script'),
                hasJsUrlHref: Array.from(card?.querySelectorAll('a[href]') ?? []).some((a) => a.getAttribute('href')?.startsWith('javascript:')),
                hasImgTag: !!card?.querySelector('img'),
            };
        });
        expect(cardProbe.hasLiveHandler, 'a live event-handler attribute survived sanitization').toBe(false);
        expect(cardProbe.hasScriptTag, 'a live <script> tag survived sanitization').toBe(false);
        expect(cardProbe.hasJsUrlHref, 'a javascript: URL survived sanitization').toBe(false);
        expect(cardProbe.hasImgTag, 'an unsanitized <img> tag survived').toBe(false);
        expect(cardProbe.html).toContain('XSS-TITLE'); // title text itself survives, just escaped
        expect(cardProbe.html).toContain('safe'); // allowed content survives

        await publicPage(page, fixture.extension_page_slug);
        await expect(page.locator('.ext-acme-demo-card', { hasText: 'XSS-TITLE' })).toBeVisible();
        const publicProbe = await page.evaluate(() => {
            const card = Array.from(document.querySelectorAll('.ext-acme-demo-card')).find((el) => el.textContent.includes('XSS-TITLE'));
            return {
                html: card?.outerHTML ?? '',
                hasLiveHandler: !!card?.querySelector('[onerror], [onmouseover]'),
                hasScriptTag: !!card?.querySelector('script'),
                hasJsUrlHref: Array.from(card?.querySelectorAll('a[href]') ?? []).some((a) => a.getAttribute('href')?.startsWith('javascript:')),
                hasImgTag: !!card?.querySelector('img'),
            };
        });
        expect(publicProbe.hasLiveHandler, 'a live event-handler attribute survived on the public page').toBe(false);
        expect(publicProbe.hasScriptTag, 'a live <script> tag survived on the public page').toBe(false);
        expect(publicProbe.hasJsUrlHref, 'a javascript: URL survived on the public page').toBe(false);
        expect(publicProbe.hasImgTag, 'an unsanitized <img> tag survived on the public page').toBe(false);
        expect(publicProbe.html).toContain('safe');

        expect(dialogFired, 'a dialog fired — an XSS payload executed').toBe(false);
        await page.screenshot({ path: 'tests/browser/.runtime/hd-xss-public.png' });
        expect(realErrorsOnly(errors)).toHaveLength(0);
    });
});

test.describe('Extension SDK — demo_container (palette + standalone; nesting blocked, see report)', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test.beforeAll(async () => {
        await restartServer(WITH_DEMO_SUITE);
    });

    test('demo_container appears in the real palette and can be added standalone (no children) without a fatal', async ({ page }) => {
        const errors = collectConsoleErrors(page);
        const fixture = loadFixture();

        await loginAsAdmin(page);
        await openBuilder(page, fixture.extension_page_id);

        const containerButton = page.locator('[data-testid="tb-add-block-ext:acme:demo_container"]');
        await expect(containerButton).toHaveCount(1);
        await expect(containerButton).toContainText('Acme Demo Container');

        await containerButton.click();
        const saveResponse = await waitForSave(page, () => saveButton(page).click());
        expect(saveResponse.ok()).toBeTruthy();

        await reloadBuilder(page);
        const iframeHtml = await page.locator('#live-canvas').evaluate((el) => el.contentDocument?.body?.innerHTML ?? '');
        expect(iframeHtml).toContain('ext-acme-demo-container');
        expect(iframeHtml).not.toContain('tb-placeholder-error');
        expect(iframeHtml).not.toContain('tb-placeholder-invalid');

        await page.screenshot({ path: 'tests/browser/.runtime/hd-container-standalone.png' });
        expect(realErrorsOnly(errors)).toHaveLength(0);
    });
});

test.describe('Extension SDK — throwing renderer safety', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test.beforeAll(async () => {
        await restartServer(WITH_THROWING_SUITE);
    });

    test('a registered extension whose renderer throws does not fatal the page; the neighboring built-in block still renders', async ({ page }) => {
        const errors = collectConsoleErrors(page);
        const fixture = loadFixture();

        await loginAsAdmin(page);
        // The control page already carries one built-in heading block —
        // proving that block survives is the neighboring-block proof.
        await openBuilder(page, fixture.control_page_id);

        const throwingButton = page.locator('[data-testid="tb-add-block-ext:acme:demo_throwing"]');
        await expect(throwingButton).toHaveCount(1);
        await throwingButton.click();

        const saveResponse = await waitForSave(page, () => saveButton(page).click());
        expect(saveResponse.ok()).toBeTruthy();

        await reloadBuilder(page);
        await expect(page.locator('[data-testid="tb-block-count"]')).toHaveText('2 ویجت');

        const iframeHtml = await page.locator('#live-canvas').evaluate((el) => el.contentDocument?.body?.innerHTML ?? '');
        // Safe fallback, not a page-level fatal; no stack trace exposed.
        expect(iframeHtml).toContain('Element failed to render');
        expect(iframeHtml).not.toContain('RuntimeException');
        expect(iframeHtml).not.toContain('Acme demo throwing element always fails');
        expect(iframeHtml).not.toMatch(/Stack trace|\.php:\d+/);
        // Neighboring built-in block still rendered.
        expect(iframeHtml).toContain('Control page');

        await page.screenshot({ path: 'tests/browser/.runtime/hd-throwing-renderer.png' });
        expect(realErrorsOnly(errors)).toHaveLength(0);
    });
});

test.describe('Extension SDK — unavailable/re-enable lifecycle + new-injection rejection', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    // Deliberately one long, sequential test rather than several independent
    // ones: this scenario's whole point is a sequence of real server
    // restarts (provider available → unavailable → available again) against
    // the SAME persisted page — splitting it into separate tests would
    // either lose that sequencing or require re-deriving it via fragile
    // shared state between test files.
    test('a persisted ext:* block survives the provider becoming unavailable, rejects a brand-new unregistered injection, and recovers on re-enable', async ({ page }) => {
        const errors = collectConsoleErrors(page);
        const fixture = loadFixture();

        // ── A. provider enabled: author and save demo_card normally ──────
        await restartServer(WITH_DEMO_SUITE);
        await loginAsAdmin(page);
        await openBuilder(page, fixture.lifecycle_page_id);

        await addBlockButton(page, 'ext:acme:demo_card').click();
        const originalTitle = `Lifecycle ${Date.now()}`;
        await propField(page, 'title').locator('input, textarea').fill(originalTitle);
        await propField(page, 'content').locator('input, textarea').fill('<p>lifecycle content</p>');
        await addBlockButton(page, 'heading').click();
        await propField(page, 'text').locator('input, textarea').fill('Lifecycle heading v1');

        let saveResponse = await waitForSave(page, () => saveButton(page).click());
        expect(saveResponse.ok()).toBeTruthy();
        await reloadBuilder(page);
        await expect(page.locator('[data-testid="tb-block-count"]')).toHaveText('2 ویجت');

        // ── B. new request/run with provider unavailable: open same page ─
        await restartServer(WITH_NO_EXTENSIONS);
        await loginAsAdmin(page); // real new session against the restarted server
        await openBuilder(page, fixture.lifecycle_page_id);
        await expect(page.locator('[data-testid="tb-block-count"]')).toHaveText('2 ویجت'); // not destroyed

        await expect(canvasFrame(page).locator('.tb-placeholder')).toBeVisible({ timeout: 15_000 }); // safe placeholder, not a fatal
        await expect(canvasFrame(page).getByText('Lifecycle heading v1')).toBeVisible(); // unrelated built-in block still renders fine
        const unavailableHtml = await page.locator('#live-canvas').evaluate((el) => el.contentDocument?.body?.innerHTML ?? '');

        // Unrelated built-in edit + save must still succeed while the
        // extension block is unavailable, and must not destroy it.
        const updatedHeading = 'Lifecycle heading v2 (edited while unavailable)';
        // Re-add the same heading's content by locating it via the canvas — simplest reliable path: re-open its prop panel is not directly addressable without clicking the canvas block, which the editor does support for top-level blocks via bld_select. Click the heading block wrapper in the iframe.
        const frame = page.frameLocator('#live-canvas');
        await frame.locator('.bldr-blk', { hasText: 'Lifecycle heading v1' }).click();
        await propField(page, 'text').locator('input, textarea').fill(updatedHeading);

        saveResponse = await waitForSave(page, () => saveButton(page).click());
        expect(saveResponse.ok()).toBeTruthy();

        await reloadBuilder(page);
        await expect(page.locator('[data-testid="tb-block-count"]')).toHaveText('2 ویجت'); // still 2 — extension block survived this save too
        await expect(canvasFrame(page).getByText(updatedHeading)).toBeVisible({ timeout: 15_000 });
        const afterEditHtml = await page.locator('#live-canvas').evaluate((el) => el.contentDocument?.body?.innerHTML ?? '');
        expect(afterEditHtml).toContain('tb-placeholder'); // still safely unavailable, not destroyed

        // ── Trust boundary: a brand-new, never-before-persisted unregistered
        // ext:* block must be rejected, via the real HTTP save endpoint in
        // this same authenticated browser context (not a raw curl/PHPUnit
        // call) — while the legitimate unavailable block and the edit above
        // both still persist.
        const csrfToken = await page.evaluate(() => CSRF_TOKEN);
        const currentBlocksBefore = await page.evaluate(() => {
            const root = document.querySelector('[x-data]');
            return window.Alpine.$data(root).blocks;
        });
        const injectionPayload = {
            blocks: [
                ...currentBlocksBefore,
                { id: 'injected_evil', type: 'ext:evil:unregistered_block', props: { anything: 'x' } },
            ],
        };
        const injectResponse = await page.request.put(`${BASE_URL}/builder/page/${fixture.lifecycle_page_id}`, {
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
            data: injectionPayload,
        });
        expect(injectResponse.ok()).toBeTruthy();

        await reloadBuilder(page);
        const afterInjectionBlocks = await page.evaluate(() => {
            const root = document.querySelector('[x-data]');
            return window.Alpine.$data(root).blocks;
        });
        expect(afterInjectionBlocks.some((b) => b.type === 'ext:evil:unregistered_block'), 'a brand-new unregistered ext:* block was persisted').toBe(false);
        expect(afterInjectionBlocks).toHaveLength(2); // old legitimate unavailable block + heading survive; new injection dropped
        expect(afterInjectionBlocks.find((b) => b.type === 'ext:acme:demo_card')?.props?.title).toBe(originalTitle);

        // ── C. re-enable: original block renders again, unmodified ───────
        await restartServer(WITH_DEMO_SUITE);
        await loginAsAdmin(page);
        await openBuilder(page, fixture.lifecycle_page_id);
        await expect(page.locator('[data-testid="tb-block-count"]')).toHaveText('2 ویجت');

        const reenabledFrame = canvasFrame(page);
        await expect(reenabledFrame.getByText(originalTitle)).toBeVisible({ timeout: 15_000 });
        const reenabledHtml = await page.locator('#live-canvas').evaluate((el) => el.contentDocument?.body?.innerHTML ?? '');
        expect(reenabledHtml).not.toContain('tb-placeholder-invalid');
        expect(reenabledHtml).toContain('lifecycle content');
        expect(reenabledHtml).toContain(updatedHeading);

        await page.screenshot({ path: 'tests/browser/.runtime/hd-unavailable.png' });
        await page.screenshot({ path: 'tests/browser/.runtime/hd-reenabled.png' });
        expect(realErrorsOnly(errors)).toHaveLength(0);
    });
});

test.describe('Extension SDK — responsive', () => {
    test.beforeAll(async () => {
        await restartServer(WITH_DEMO_SUITE);
    });

    test('desktop (1440x900): full authoring path for demo_card', async ({ page }) => {
        await page.setViewportSize(VIEWPORTS.desktop);
        const errors = collectConsoleErrors(page);
        const fixture = loadFixture();

        await loginAsAdmin(page);
        await openBuilder(page, fixture.extension_page_id);

        await expect(page.locator('[data-testid="tb-add-block-ext:acme:demo_card"]')).toBeVisible();
        await addBlockButton(page, 'ext:acme:demo_card').click();
        const title = `Desktop ${Date.now()}`;
        await propField(page, 'title').locator('input, textarea').fill(title);
        await propField(page, 'content').locator('input, textarea').fill('<p>desktop content</p>');

        const saveResponse = await waitForSave(page, () => saveButton(page).click());
        expect(saveResponse.ok()).toBeTruthy();
        await reloadBuilder(page);
        await expect(canvasFrame(page).getByText(title)).toBeVisible({ timeout: 15_000 });

        await page.screenshot({ path: 'tests/browser/.runtime/hd-responsive-desktop.png' });
        expect(realErrorsOnly(errors)).toHaveLength(0);
    });

    test('tablet (768x1024): palette/controls/editor accessible, extension renders', async ({ page }) => {
        await page.setViewportSize(VIEWPORTS.tablet);
        const errors = collectConsoleErrors(page);
        const fixture = loadFixture();

        await loginAsAdmin(page);
        await openBuilder(page, fixture.extension_page_id);

        const cardButton = page.locator('[data-testid="tb-add-block-ext:acme:demo_card"]');
        await expect(cardButton).toBeVisible();
        await cardButton.click();

        const title = `Tablet ${Date.now()}`;
        const titleField = propField(page, 'title').locator('input, textarea');
        await expect(titleField).toBeVisible();
        await titleField.fill(title);
        await propField(page, 'content').locator('input, textarea').fill('<p>tablet content</p>');

        const saveButtonEl = saveButton(page);
        await expect(saveButtonEl).toBeVisible();
        const saveResponse = await waitForSave(page, () => saveButtonEl.click());
        expect(saveResponse.ok()).toBeTruthy();

        await reloadBuilder(page);
        await expect(canvasFrame(page).getByText(title)).toBeVisible({ timeout: 15_000 });

        await page.screenshot({ path: 'tests/browser/.runtime/hd-responsive-tablet.png' });
        expect(realErrorsOnly(errors)).toHaveLength(0);
    });

    test('mobile (390x844): public page renders the extension correctly', async ({ page }) => {
        await page.setViewportSize(VIEWPORTS.mobile);
        const errors = collectConsoleErrors(page);
        const fixture = loadFixture();

        await publicPage(page, fixture.extension_page_slug);
        await expect(page.locator('.ext-acme-demo-card').first()).toBeVisible();

        await page.screenshot({ path: 'tests/browser/.runtime/hd-responsive-mobile-public.png' });
        expect(realErrorsOnly(errors)).toHaveLength(0);
    });

    test('mobile (390x844): Builder editor itself — smoke-level load check only (authoring not asserted)', async ({ page }) => {
        await page.setViewportSize(VIEWPORTS.mobile);
        const errors = collectConsoleErrors(page);
        const fixture = loadFixture();

        await loginAsAdmin(page);
        await openBuilder(page, fixture.extension_page_id);
        // Smoke-level only: the editor's own responsive layout is a
        // pre-existing, Extension-SDK-unrelated product characteristic —
        // see the final report's PRE_EXISTING_PRODUCT_LIMITATION vs
        // EXTENSION_SPECIFIC_REGRESSION classification. This just proves
        // the route doesn't fatal at this viewport.
        await expect(page.locator('#live-canvas')).toBeAttached();

        await page.screenshot({ path: 'tests/browser/.runtime/hd-responsive-mobile-builder.png' });
        expect(realErrorsOnly(errors)).toHaveLength(0);
    });
});

test.describe('Extension SDK — accessibility smoke', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test.beforeAll(async () => {
        await restartServer(WITH_DEMO_SUITE);
    });

    test('extension controls have labels, no duplicate IDs, no obvious focus trap, keyboard reachable', async ({ page }) => {
        const errors = collectConsoleErrors(page);
        const fixture = loadFixture();

        await loginAsAdmin(page);
        await openBuilder(page, fixture.extension_page_id);
        await addBlockButton(page, 'ext:acme:demo_card').click();

        // Every extension prop control has a real, visible label — the
        // same existing Builder convention every built-in control uses
        // (label text bound to schemaDef.label||propKey).
        const titleLabel = propField(page, 'title').locator('label');
        const contentLabel = propField(page, 'content').locator('label');
        await expect(titleLabel).toBeVisible();
        await expect(contentLabel).toBeVisible();
        await expect(titleLabel).not.toHaveText('');
        await expect(contentLabel).not.toHaveText('');

        // Keyboard reachability: Tab from the title field must reach the
        // content field without anything trapping focus.
        await propField(page, 'title').locator('input, textarea').focus();
        await page.keyboard.press('Tab');
        const focusedIsContentField = await page.evaluate(() => {
            const active = document.activeElement;
            return !!active?.closest('[data-testid="tb-prop-content"]');
        });
        expect(focusedIsContentField, 'Tab from the title field did not reach the content field — possible focus trap').toBe(true);

        // No duplicate element IDs introduced by the extension's own
        // rendered markup (the canvas iframe is a separate document, so
        // uniqueness is checked within it, matching how the app itself
        // scopes IDs).
        const duplicateIds = await page.locator('#live-canvas').evaluate((el) => {
            const doc = el.contentDocument;
            const ids = Array.from(doc.querySelectorAll('[id]')).map((n) => n.id);
            const seen = new Set();
            const dupes = new Set();
            for (const id of ids) {
                if (seen.has(id)) dupes.add(id);
                seen.add(id);
            }
            return Array.from(dupes);
        });
        expect(duplicateIds, `duplicate DOM ids in the canvas: ${JSON.stringify(duplicateIds)}`).toHaveLength(0);

        await page.screenshot({ path: 'tests/browser/.runtime/hd-accessibility-smoke.png' });
        expect(realErrorsOnly(errors)).toHaveLength(0);
    });
});
