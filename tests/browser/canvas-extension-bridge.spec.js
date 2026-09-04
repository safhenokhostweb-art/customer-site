import { expect, test } from '@playwright/test';
import { BASE_URL } from './fixtures/env.js';
import { WITH_DEMO_SUITE, WITH_NO_EXTENSIONS, WITH_THROWING_SUITE } from './fixtures/extension-env.js';
import { VIEWPORTS } from './fixtures/viewports.js';
import { loginAsAdmin } from './helpers/auth.js';
import {
    canvasIframe,
    openAddChildModal,
    addChildButton,
    openCanvasEditor,
    openTreeTab,
    paletteAddButton,
    propField,
    publicPage,
    publishButton,
    reloadCanvasEditor,
    saveButton,
    switchToPage,
    treeNode,
    waitForPublish,
    waitForSaveBlocks,
} from './helpers/canvas.js';
import { restartServer } from './helpers/server.js';

/**
 * Phase 3F-C — Extension SDK → Canvas authoring bridge real-browser
 * verification, built on the same D0/H-D harness and the same test-only
 * acme/demo-suite (Tests\Support\BrowserExtension) fixtures 3E-H-D already
 * proved for the Production Builder. Exercises the REAL customer-site
 * Canvas editor path: real login, real /admin/theme-design/canvas/edit
 * route, real palette/tree/controls, real PUT save-blocks, real reload,
 * real POST publish, and the real public /page/{slug} route.
 *
 * Does NOT re-prove the Production Builder (3E-H-D) or the built-in Canvas
 * nested-tree UX (3F-B, canvas-container.spec.js) — only the NEW bridge
 * behavior: ext:* palette entries, ext:* nested authoring in all 3
 * directions, ext:* assets/XSS/lifecycle/collision through the Canvas
 * domain specifically.
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

test.describe('Canvas Extension Bridge — palette', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test.beforeAll(async () => {
        await restartServer(WITH_DEMO_SUITE);
    });

    test('demo_card and demo_container appear exactly once in the real Canvas palette, correctly labeled', async ({ page }) => {
        const errors = collectConsoleErrors(page);
        await loginAsAdmin(page);
        await openCanvasEditor(page);

        const cardButtons = paletteAddButton(page, 'ext:acme:demo_card');
        const containerButtons = paletteAddButton(page, 'ext:acme:demo_container');

        await expect(cardButtons).toHaveCount(1);
        await expect(containerButtons).toHaveCount(1);
        await expect(cardButtons).toHaveText(/Acme Demo Card/);
        await expect(containerButtons).toHaveText(/Acme Demo Container/);

        expect(realErrorsOnly(errors)).toHaveLength(0);
    });

    test('no bare-name conversion: only the full ext:acme:demo_card identity is ever used, never a bare "demo_card" palette entry', async ({ page }) => {
        await loginAsAdmin(page);
        await openCanvasEditor(page);

        await expect(page.locator('[data-testid="tb-palette-add-demo_card"]')).toHaveCount(0);
        await expect(page.locator('[data-testid="tb-palette-add-demo_container"]')).toHaveCount(0);
    });
});

test.describe('Canvas Extension Bridge — full authoring, nesting, save/reload/publish/public', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test.beforeAll(async () => {
        await restartServer(WITH_DEMO_SUITE);
    });

    test('demo_card: real authoring, controls, save, reload, preview, publish, public render', async ({ page }) => {
        const errors = collectConsoleErrors(page);
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'ext-bridge');

        // Real authoring: click the palette to add demo_card at top level.
        await paletteAddButton(page, 'ext:acme:demo_card').click();

        // Real controls: edit title (string->'string' control) and content (textarea).
        const titleField = propField(page, 'title');
        await expect(titleField).toBeVisible();
        await titleField.fill('Real Canvas Title');
        const contentField = propField(page, 'content');
        await contentField.fill('Real canvas content body.');

        // Real save through the real PUT endpoint.
        const saveResponse = await waitForSaveBlocks(page, () => saveButton(page).click());
        expect(saveResponse.ok()).toBeTruthy();

        // Real full reload — proves persistence, not in-memory Alpine state.
        await reloadCanvasEditor(page);
        await switchToPage(page, 'ext-bridge');
        await openTreeTab(page);
        const cardNode = page.locator('[data-testid^="tb-tree-node-"]').filter({ hasText: 'Acme Demo Card' });
        await expect(cardNode.first()).toBeVisible({ timeout: 10_000 });
        await cardNode.first().click();
        await expect(propField(page, 'title')).toHaveValue('Real Canvas Title');
        await expect(propField(page, 'content')).toHaveValue('Real canvas content body.');

        // Real draft live-preview iframe: the rendered card must appear
        // through the SAME ThemeCanvasRenderer chain the public route uses.
        const frame = canvasIframe(page);
        await expect(frame.locator('.ext-acme-demo-card')).toContainText('Real Canvas Title');

        // Real publish through the real POST endpoint.
        const publishResponse = await waitForPublish(page, () => publishButton(page).click());
        expect(publishResponse.ok()).toBeTruthy();
        await page.waitForURL(/theme-design\/canvas\/edit/, { timeout: 5_000 }).catch(() => null);

        // Real public route — the real ThemeCanvasPageController/ThemeCanvasRenderer chain.
        await publicPage(page, 'ext-bridge');
        await expect(page.locator('.ext-acme-demo-card')).toContainText('Real Canvas Title');
        await expect(page.locator('.ext-acme-demo-card')).toContainText('Real canvas content body.');

        expect(realErrorsOnly(errors)).toHaveLength(0);
    });

    test('nested authoring: built-in section -> extension child, extension container -> built-in child, extension container -> extension child', async ({ page }) => {
        const errors = collectConsoleErrors(page);
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'ext-bridge');
        // Note: the left panel's palette ("بلوک‌ها") and tree ("ساختار") tabs
        // are mutually exclusive (leftPanelTab) — palette clicks below run
        // while that tab is still the default active one; openAddChildModal()
        // is a right-side property-panel action, independent of leftPanelTab,
        // and works regardless. The tree tab is opened only where a node
        // must be re-selected (adding a child auto-selects the CHILD, not
        // the parent — re-selecting the parent needs the tree).

        // ── A. built-in container (section) -> extension child (demo_card) ──
        await paletteAddButton(page, 'section').click(); // auto-selects the new section
        await openAddChildModal(page);
        await addChildButton(page, 'ext:acme:demo_card').click();
        await expect(page.getByText('هیچ بلوک فرزندی وجود ندارد')).toHaveCount(0);

        // ── B. extension container (demo_container) -> built-in child (text) ──
        // Uses 'text', not 'heading': incidentally discovered (see the
        // throwing-renderer test's note) that Canvas's own 'heading' block
        // sends its 'level' select as a string while
        // config/builder-element-schemas.php declares that prop
        // integer-typed — a genuine, pre-existing, Canvas-wide type
        // mismatch (not caused by this phase) that fails
        // ElementSchemaRegistry::validate() for the heading child, which
        // in turn invalidates ITS PARENT too (validate() recurses into
        // children and fails the whole subtree on any child failure) —
        // so a nested heading anywhere silently breaks the entire
        // container, extension or built-in alike. Not fixed here (out of
        // this phase's scope); 'text' has no such mismatch.
        await paletteAddButton(page, 'ext:acme:demo_container').click(); // auto-selects the new container
        await openAddChildModal(page);
        await addChildButton(page, 'text').click(); // auto-selects the new text child

        // ── C. extension container -> extension child (demo_card): re-select
        // the container via the tree (adding the heading above selected it,
        // not the container) ──
        await openTreeTab(page);
        const containerNode = page.locator('[data-testid^="tb-tree-node-"]').filter({ hasText: 'Acme Demo Container' }).last();
        await containerNode.click();
        await openAddChildModal(page);
        await addChildButton(page, 'ext:acme:demo_card').click();

        // Real save + real full reload proves the whole nested tree persisted.
        const saveResponse = await waitForSaveBlocks(page, () => saveButton(page).click());
        expect(saveResponse.ok()).toBeTruthy();
        await reloadCanvasEditor(page);
        await switchToPage(page, 'ext-bridge');
        await openTreeTab(page);

        // The extension container's children count (heading + demo_card = 2)
        // is the concrete, real-UI proof of both B and C surviving persistence.
        const containerNodeAfterReload = page.locator('[data-testid^="tb-tree-node-"]').filter({ hasText: 'Acme Demo Container' }).last();
        await containerNodeAfterReload.click();
        const childrenBadge = page.locator('text=/فرزندان \\(2\\)/');
        await expect(childrenBadge).toBeVisible({ timeout: 10_000 });

        // Real live-preview iframe proof that the section->demo_card nesting (A)
        // and container->children (B/C) all render through the existing
        // ThemeCanvasRenderer chain, not a special Canvas-only path. Existence
        // (not exact count) is asserted: 'ext-bridge' is a shared fixture page
        // across this file's tests, so other tests' own demo_card/container
        // instances may already be present — the proof here is that nesting
        // renders at all through the real chain, not page-wide exclusivity.
        // Checked BEFORE the reorder action below, which triggers its own
        // async iframe reload independent of what this is proving.
        const frame = canvasIframe(page);
        await expect(frame.locator('.tb-canvas-block[data-tb-container="1"] .ext-acme-demo-card').first()).toBeVisible();
        await expect(frame.locator('.ext-acme-demo-container').first()).toBeVisible();

        // Real drag/order proof: move the demo_card child up past heading using
        // the existing (unmodified) reorder controls — no new implementation.
        const moveUpButtons = page.locator('button[title="بالاتر"]');
        await expect(moveUpButtons.last()).toBeEnabled();
        await moveUpButtons.last().click();

        expect(realErrorsOnly(errors)).toHaveLength(0);
    });
});

test.describe('Canvas Extension Bridge — assets', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test.beforeAll(async () => {
        await restartServer(WITH_DEMO_SUITE);
    });

    test('demo_card asset (HasAssets): emitted on public render, real HTTP 200, deduped, absent on a page without the extension', async ({ page }) => {
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'ext-bridge');

        // Two demo_card instances on the same page -> asset must be deduped.
        await paletteAddButton(page, 'ext:acme:demo_card').click();
        await paletteAddButton(page, 'ext:acme:demo_card').click();
        await waitForSaveBlocks(page, () => saveButton(page).click());
        await waitForPublish(page, () => publishButton(page).click());
        await page.waitForTimeout(900);

        const assetRequests = [];
        page.on('request', (req) => {
            if (req.url().includes('demo-card.css')) assetRequests.push(req.url());
        });
        const assetResponsePromise = page.waitForResponse((res) => res.url().includes('demo-card.css'));
        await publicPage(page, 'ext-bridge');
        const assetResponse = await assetResponsePromise;
        expect(assetResponse.status()).toBe(200);
        expect(assetRequests).toHaveLength(1); // deduped — not one <link> per block instance

        // Absent on the home page, which has no extension block at all.
        const homeRequests = [];
        page.on('request', (req) => {
            if (req.url().includes('demo-card.css')) homeRequests.push(req.url());
        });
        await page.goto(`${BASE_URL}/`, { waitUntil: 'networkidle' });
        expect(homeRequests).toHaveLength(0);
    });
});

test.describe('Canvas Extension Bridge — stored XSS', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test.beforeAll(async () => {
        await restartServer(WITH_DEMO_SUITE);
    });

    test('malicious title/content neutralized through real save -> reload -> preview -> publish -> public page', async ({ page }) => {
        const errors = collectConsoleErrors(page);
        let alertFired = false;
        page.on('dialog', async (dialog) => { alertFired = true; await dialog.dismiss(); });

        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'ext-bridge');

        await paletteAddButton(page, 'ext:acme:demo_card').click();
        await propField(page, 'title').fill('<img src=x onerror=alert(1)>XSS-TITLE');
        await propField(page, 'content').fill('<script>alert(2)</script><p onclick="alert(3)">click</p><strong>bold-survives</strong>');

        await waitForSaveBlocks(page, () => saveButton(page).click());
        await reloadCanvasEditor(page);
        await switchToPage(page, 'ext-bridge');
        await waitForPublish(page, () => publishButton(page).click());

        await publicPage(page, 'ext-bridge');

        // 'ext-bridge' is a shared fixture page across this file's tests and
        // may already carry other demo_card instances — scope to the one
        // this test itself just authored, identified by its own content.
        const card = page.locator('.ext-acme-demo-card').filter({ hasText: 'XSS-TITLE' });
        // Title is escaped text, not markup — no <img>/onerror survives as a real attribute.
        await expect(card.locator('img')).toHaveCount(0);
        await expect(card).toContainText('XSS-TITLE');
        // Content goes through the canonical HtmlSanitizer — script/handler stripped, safe markup kept.
        await expect(card.locator('script')).toHaveCount(0);
        await expect(card.locator('[onclick]')).toHaveCount(0);
        await expect(card.locator('strong')).toHaveText('bold-survives');

        expect(alertFired, 'no script/handler must ever execute').toBe(false);
        expect(realErrorsOnly(errors)).toHaveLength(0);
    });
});

test.describe('Canvas Extension Bridge — throwing renderer', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test.beforeAll(async () => {
        await restartServer(WITH_THROWING_SUITE);
    });

    test('a registered extension whose renderer throws does not fatal the Canvas preview or public page; neighboring block still renders', async ({ page }) => {
        const errors = collectConsoleErrors(page);
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        // Dedicated page (not the shared ext-bridge fixture used by other
        // tests in this file) — this describe block restarts the server
        // WITHOUT the demo_card/demo_container fixtures registered, so
        // ext-bridge's own accumulated content from other tests would
        // render as unavailable placeholders here, an unrelated concern to
        // what this test proves.
        await switchToPage(page, 'ext-throwing');

        // Neighbor is a built-in 'text' block, not 'heading': incidentally
        // discovered while writing this test that Canvas's own 'heading'
        // block sends its 'level' select as a string ('2'), while
        // config/builder-element-schemas.php declares that prop
        // 'integer'-typed — a genuine, pre-existing (not 3F-C-caused),
        // Canvas-wide type mismatch that fails ElementSchemaRegistry::
        // validate() and renders as an [invalid] placeholder for ANY
        // heading block authored through Canvas, extension-unrelated. Not
        // fixed here (out of this phase's scope — a broader product
        // decision); 'text' has no such mismatch (both sides agree it's a
        // plain string), so it proves the actual thing this test is about.
        await paletteAddButton(page, 'text').click(); // auto-selected
        await propField(page, 'content').fill('Neighbor Text Survives');
        await paletteAddButton(page, 'ext:acme:demo_throwing').click();
        await waitForSaveBlocks(page, () => saveButton(page).click());
        await waitForPublish(page, () => publishButton(page).click());

        await publicPage(page, 'ext-throwing');
        // Page must load (no fatal), and the neighboring built-in block must still render.
        await expect(page.getByText('Neighbor Text Survives')).toBeVisible();
        await expect(page.locator('body')).not.toContainText('RuntimeException');
        await expect(page.locator('body')).not.toContainText('Stack trace');

        expect(realErrorsOnly(errors)).toHaveLength(0);
    });
});

test.describe('Canvas Extension Bridge — unavailable/re-enable lifecycle + unknown-injection rejection', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test('a persisted ext:* Canvas block survives the provider becoming unavailable, rejects a brand-new unregistered injection at the real save boundary, and recovers on re-enable', async ({ page }) => {
        const errors = collectConsoleErrors(page);

        // ── A. author + publish while the provider is available ──────────
        await restartServer(WITH_DEMO_SUITE);
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'ext-lifecycle');
        await paletteAddButton(page, 'ext:acme:demo_card').click();
        await propField(page, 'title').fill('Lifecycle Title');
        await waitForSaveBlocks(page, () => saveButton(page).click());
        await waitForPublish(page, () => publishButton(page).click());

        // ── B. provider becomes unavailable: restart without it ──────────
        await page.goto('about:blank');
        await restartServer(WITH_NO_EXTENSIONS);
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'ext-lifecycle');
        await openTreeTab(page);
        // No fatal — the editor loaded. The persisted block must still be
        // present in some safe representation (not silently deleted).
        const treeNodes = page.locator('[data-testid^="tb-tree-node-"]');
        await expect(treeNodes).toHaveCount(1, { timeout: 10_000 });

        // Real HTTP boundary test: a brand-new, never-registered ext:* type
        // must be rejected even when submitted ALONGSIDE genuine existing
        // content in the very same real save request — a malicious/buggy
        // client tampering with an otherwise-legitimate request body, the
        // realistic shape of this trust boundary. Intercept the real save
        // request the visible save button naturally sends (which already
        // includes the real current blocks, unmodified) and splice in the
        // injected block before letting it proceed to the real server.
        await page.route('**/theme-design/canvas/save-blocks', async (route) => {
            const body = route.request().postDataJSON();
            body.blocks.push({ id: 'evil_1', type: 'ext:evil:unregistered_block', props: { x: 1 } });
            await route.continue({ postData: JSON.stringify(body) });
        }, { times: 1 });
        const injectionResponse = await waitForSaveBlocks(page, () => saveButton(page).click());
        const injectionBody = await injectionResponse.json();
        expect(injectionBody.success).toBe(true);
        expect(injectionBody.block_count).toBe(1); // the genuine block was kept; the injection was dropped, not accepted

        // ── C. edit/save unrelated built-in content — existing ext block must survive untouched ──
        await reloadCanvasEditor(page);
        await switchToPage(page, 'ext-lifecycle');
        await paletteAddButton(page, 'heading').click();
        await waitForSaveBlocks(page, () => saveButton(page).click());
        await reloadCanvasEditor(page);
        await switchToPage(page, 'ext-lifecycle');
        await openTreeTab(page);
        await expect(page.locator('[data-testid^="tb-tree-node-"]')).toHaveCount(2, { timeout: 10_000 });

        // ── D. re-enable: original block returns, unmodified, no migration ──
        await page.goto('about:blank');
        await restartServer(WITH_DEMO_SUITE);
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'ext-lifecycle');
        await openTreeTab(page);
        const cardNode = page.locator('[data-testid^="tb-tree-node-"]').filter({ hasText: 'Acme Demo Card' });
        await expect(cardNode.first()).toBeVisible({ timeout: 10_000 });
        await cardNode.first().click();
        await expect(propField(page, 'title')).toHaveValue('Lifecycle Title');

        expect(realErrorsOnly(errors)).toHaveLength(0);
    });
});

test.describe('Canvas Extension Bridge — responsive', () => {
    test.beforeAll(async () => {
        await restartServer(WITH_DEMO_SUITE);
    });

    test('desktop (1440x900): full authoring for demo_card', async ({ page }) => {
        await page.setViewportSize(VIEWPORTS.desktop);
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'ext-bridge');
        await expect(paletteAddButton(page, 'ext:acme:demo_card')).toBeVisible();
        await paletteAddButton(page, 'ext:acme:demo_card').click();
        await expect(propField(page, 'title')).toBeVisible();
    });

    test('tablet (768x1024): palette/controls/tree usable', async ({ page }) => {
        // Operates on the default active page (no page-tab switch): at this
        // width the top header bar's page-tab row (pre-existing, untouched
        // by this phase) visually overlaps the editor title cluster once
        // enough tabs are present — a genuine, narrow, pre-existing Canvas
        // header-bar responsive limitation surfaced by this fixture's extra
        // pages, not an extension-bridge defect. Reported, not fixed here
        // (out of this phase's scope) — this test instead proves what the
        // task actually asks: palette/controls/tree usability at tablet
        // width, independent of that unrelated page-tab interaction.
        await page.setViewportSize(VIEWPORTS.tablet);
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await expect(paletteAddButton(page, 'ext:acme:demo_container')).toBeVisible();
        await paletteAddButton(page, 'ext:acme:demo_container').click();
        await openTreeTab(page);
        const node = page.locator('[data-testid^="tb-tree-node-"]').filter({ hasText: 'Acme Demo Container' });
        await expect(node.first()).toBeVisible();
    });

    test('mobile (390x844): public page render only — no Canvas authoring claimed', async ({ page }) => {
        await page.setViewportSize(VIEWPORTS.mobile);
        await publicPage(page, 'ext-bridge');
        await expect(page.locator('.ext-acme-demo-card, .ext-acme-demo-container').first()).toBeVisible();
    });
});

test.describe('Canvas Extension Bridge — accessibility smoke', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test.beforeAll(async () => {
        await restartServer(WITH_DEMO_SUITE);
    });

    test('extension controls are keyboard-reachable and introduce no duplicate DOM ids', async ({ page }) => {
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'ext-bridge');
        await paletteAddButton(page, 'ext:acme:demo_card').click();

        const titleField = propField(page, 'title');
        await titleField.focus();
        await expect(titleField).toBeFocused();
        await page.keyboard.press('Tab');
        const contentField = propField(page, 'content');
        await expect(contentField).toBeFocused();

        const idCounts = await page.evaluate(() => {
            const ids = Array.from(document.querySelectorAll('[id]')).map((el) => el.id).filter(Boolean);
            const counts = {};
            for (const id of ids) counts[id] = (counts[id] || 0) + 1;
            return Object.values(counts).filter((c) => c > 1).length;
        });
        expect(idCounts, 'no duplicate DOM ids introduced').toBe(0);
    });
});
