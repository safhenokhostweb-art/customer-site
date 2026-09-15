import { expect, test } from '@playwright/test';
import { BASE_URL } from './fixtures/env.js';
import { VIEWPORTS } from './fixtures/viewports.js';
import { loginAsAdmin } from './helpers/auth.js';

/**
 * TASK 2A (tabs container authoring fix) — real browser proof of the full
 * canonical Theme Canvas lifecycle for the `tabs` container:
 *
 *   AUTHORING -> SAVE DRAFT -> RELOAD DRAFT -> PREVIEW DRAFT -> PUBLISH -> PUBLIC RENDER
 *
 * against the real Canvas editor UI (resources/views/admin/theme-design/
 * canvas-editor.blade.php) and the real save-blocks/publish endpoints — the
 * same real-UI, real-endpoint convention save-to-public-render.spec.js and
 * canvas-container.spec.js already use for the rest of the Canvas.
 *
 * Selection always goes through the Structure tree (`tb-tree-node-{id}`,
 * present at every depth via flattenTree() — see canvas-editor.blade.php)
 * rather than the container children panel's own "edit" button, because
 * selecting a child moves `selectedBlock` onto that child (the same
 * behavior addBlock()/addChildBlock() already have for every other
 * container), so the tabs-level children list is only visible while `tabs`
 * itself is the selection — the tree lets any block be reselected directly
 * regardless of what is currently selected.
 *
 * Proves, with 3 real tabs: add, rename, reorder (tabs and children), add
 * distinct children to two different tabs, child edit, child reorder, child
 * removal, tab deletion, and — the specific regression this task fixed on
 * the live customer-site render path (see App\Services\ThemeBuilder\
 * BlockRenderer::renderBlockUnsafe()) — that canonical tab_panel content
 * actually reaches both the draft preview iframe and the real public page,
 * with no cross-tab content leakage.
 */

const MARKER_A = 'TABS_LIFECYCLE_PROBE_TAB_A_20260914';
const MARKER_B = 'TABS_LIFECYCLE_PROBE_TAB_B_20260914';
const MARKER_C = 'TABS_LIFECYCLE_PROBE_TAB_C_20260914';
const MARKER_A_EDITED = 'TABS_LIFECYCLE_PROBE_TAB_A_EDITED_20260914';

function collectConsoleErrors(page) {
    const errors = [];
    page.on('console', (msg) => { if (msg.type() === 'error') errors.push(`${msg.text()} (${msg.location().url})`); });
    page.on('pageerror', (err) => errors.push(String(err)));
    page.on('dialog', (dialog) => dialog.accept());
    return errors;
}

/** Opens the palette category containing `blockType` if it isn't already expanded. */
async function ensurePaletteVisible(page, blockType) {
    const btn = page.locator(`[data-testid="tb-palette-add-${blockType}"]`);
    if (await btn.isVisible().catch(() => false)) return btn;
    for (const cat of await page.locator('[data-testid^="tb-palette-category-"]').all()) {
        await cat.click();
        if (await btn.isVisible().catch(() => false)) break;
    }
    return btn;
}

/** Selects any block (any depth) via the Structure tree, then opens the right-panel Content tab. */
async function selectViaTree(page, id) {
    await page.locator('[data-testid="tb-canvas-tree-tab"]').click();
    await page.locator(`[data-testid="tb-tree-node-${id}"]`).click();
    await page.getByRole('button', { name: 'محتوا', exact: true }).click();
}

/** Adds a text child (with the given content) to whichever container is currently selected. */
async function addTextChildWithContent(page, content) {
    await page.locator('[data-testid="tb-open-add-child-modal"]').click();
    await page.locator('[data-testid="tb-add-child-text"]').click();
    const textField = page.locator('[data-testid="tb-prop-content"]').first();
    await expect(textField).toBeVisible();
    await textField.fill(content);
    await textField.blur();
}

async function childIds(page) {
    return page.locator('[data-testid^="tb-child-label-"]').evaluateAll(
        (els) => els.map((el) => el.getAttribute('data-testid').replace('tb-child-label-', '')),
    );
}

async function saveDraft(page) {
    const saveResponse = page.waitForResponse((res) => res.url().includes('theme-design/canvas/save-blocks') && res.request().method() === 'PUT');
    await page.locator('[data-testid="tb-canvas-save-button"]').click();
    expect((await saveResponse).ok()).toBeTruthy();
}

test.describe('Canvas editor — Tabs container full authoring lifecycle (TASK 2A)', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test('insert, add/rename/reorder tabs, per-tab children, save/reload/preview/publish/public-render, isolation on delete', async ({ page }) => {
        const errors = collectConsoleErrors(page);

        await loginAsAdmin(page);
        await page.goto(`${BASE_URL}/admin/theme-design/canvas/edit`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('[data-testid="tb-canvas-save-button"]')).toBeVisible({ timeout: 15_000 });
        await page.locator('[data-testid="tb-canvas-page-tab-home"]').click();

        // ── Insert a real `tabs` container from the real block palette ──
        const paletteTabsBtn = await ensurePaletteVisible(page, 'tabs');
        await expect(paletteTabsBtn).toBeVisible();
        await paletteTabsBtn.click();
        await page.getByRole('button', { name: 'محتوا', exact: true }).click();

        // Capture the new `tabs` block's own id via the Structure tree — it
        // has no children yet, so it is unambiguously the last tree node.
        await page.locator('[data-testid="tb-canvas-tree-tab"]').click();
        const tabsId = (await page.locator('[data-testid^="tb-tree-node-"]').last().getAttribute('data-testid')).replace('tb-tree-node-', '');
        await page.locator(`[data-testid="tb-tree-node-${tabsId}"]`).click();
        await page.getByRole('button', { name: 'محتوا', exact: true }).click();

        const addTabBtn = page.locator('[data-testid="tb-add-tab"]');
        await expect(addTabBtn).toBeVisible();

        // ── ADD Tab A, Tab B, Tab C — repeated clicks, no reselect needed ──
        await addTabBtn.click();
        await addTabBtn.click();
        await addTabBtn.click();
        await expect(page.locator('[data-testid^="tb-child-label-"]')).toHaveCount(3);
        const [idA, idB, idC] = await childIds(page);

        // ── RENAME Tab A and Tab B ──
        await selectViaTree(page, idA);
        const titleField = page.locator('[data-testid="tb-prop-title"]').first();
        await expect(titleField).toBeVisible();
        await titleField.fill('تب ویژگی‌ها');
        await titleField.blur();

        await selectViaTree(page, idB);
        await expect(titleField).toBeVisible();
        await titleField.fill('تب قیمت‌گذاری');
        await titleField.blur();

        // ── ADD real Canvas children to Tab A (2 blocks) and Tab B (1 block), each with distinct content ──
        await selectViaTree(page, idA);
        await addTextChildWithContent(page, MARKER_A);
        await selectViaTree(page, idA);
        await page.locator('[data-testid="tb-open-add-child-modal"]').click();
        await page.locator('[data-testid="tb-add-child-heading"]').click();

        await selectViaTree(page, idB);
        await addTextChildWithContent(page, MARKER_B);

        // ── EDIT a child's content (Tab A's text child) ──
        await selectViaTree(page, idA);
        await expect(page.locator('[data-testid^="tb-child-label-"]')).toHaveCount(2);
        const [tabAChild1, tabAChild2] = await childIds(page);
        await selectViaTree(page, tabAChild1); // the text block added first
        const contentField = page.locator('[data-testid="tb-prop-content"]').first();
        await expect(contentField).toBeVisible();
        await contentField.fill(MARKER_A_EDITED);
        await contentField.blur();

        // ── REORDER Tab A's children (move the heading above the text block) ──
        await selectViaTree(page, idA);
        await page.locator(`[data-testid="tb-child-up-${tabAChild2}"]`).click();
        await expect(page.locator('[data-testid^="tb-child-label-"]')).toHaveCount(2);
        const reordered = await childIds(page);
        expect(reordered[0]).toBe(tabAChild2); // the heading is now first

        // ── REMOVE Tab A's heading child (leaving exactly 1: the text block) ──
        await page.locator(`[data-testid="tb-child-remove-${tabAChild2}"]`).click();
        await expect(page.locator('[data-testid^="tb-child-label-"]')).toHaveCount(1);

        // ── Add Tab C's own distinct child content ──
        await selectViaTree(page, idC);
        await addTextChildWithContent(page, MARKER_C);

        // ── REORDER the tabs themselves: move Tab C above Tab B ──
        await selectViaTree(page, tabsId);
        await expect(page.locator('[data-testid^="tb-child-label-"]')).toHaveCount(3);
        await page.locator(`[data-testid="tb-child-up-${idC}"]`).click();
        const tabOrder = await childIds(page);
        expect(tabOrder).toEqual([idA, idC, idB]);

        // ── DELETE Tab B ──
        await page.locator(`[data-testid="tb-child-remove-${idB}"]`).click();
        await expect(page.locator('[data-testid^="tb-child-label-"]')).toHaveCount(2);
        expect(await childIds(page)).toEqual([idA, idC]);

        // ── SAVE DRAFT (real PUT endpoint) ──
        await saveDraft(page);

        // ── RELOAD DRAFT — proves real persistence, not in-memory Alpine state ──
        await page.reload({ waitUntil: 'domcontentloaded' });
        await expect(page.locator('[data-testid="tb-canvas-save-button"]')).toBeVisible({ timeout: 15_000 });
        await page.locator('[data-testid="tb-canvas-page-tab-home"]').click();

        // ── PREVIEW DRAFT: the live-preview iframe shows Tab A (edited) and Tab C content, NOT Tab B (deleted) ──
        const draftFrame = page.frameLocator('#tb-live-iframe');
        await expect(draftFrame.getByText(MARKER_A_EDITED)).toBeVisible({ timeout: 15_000 });
        await expect(draftFrame.getByText(MARKER_C)).toBeVisible({ timeout: 10_000 });
        await expect(draftFrame.getByText(MARKER_B)).toHaveCount(0);
        await expect(draftFrame.getByText('تب ویژگی‌ها')).toBeVisible();
        await expect(draftFrame.getByText('تب قیمت‌گذاری')).toHaveCount(0); // deleted tab's title must not survive either

        // Cross-tab isolation: Tab A's own rendered panel must not contain Tab C's marker, and vice versa.
        const panelA = draftFrame.locator('.tb-tab-panel', { hasText: MARKER_A_EDITED });
        await expect(panelA).not.toContainText(MARKER_C);
        const panelC = draftFrame.locator('.tb-tab-panel', { hasText: MARKER_C });
        await expect(panelC).not.toContainText(MARKER_A_EDITED);

        // Draft alone must not reach the real public homepage yet.
        await page.goto(`${BASE_URL}/`, { waitUntil: 'domcontentloaded' });
        await expect(page.getByText(MARKER_A_EDITED)).toHaveCount(0);

        // ── PUBLISH (real POST endpoint) ──
        await page.goto(`${BASE_URL}/admin/theme-design/canvas/edit`, { waitUntil: 'domcontentloaded' });
        const publishResponse = page.waitForResponse((res) => res.url().includes('theme-design/canvas/publish') && res.request().method() === 'POST');
        const selfReload = page
            .waitForEvent('framenavigated', { predicate: (frame) => frame === page.mainFrame(), timeout: 5_000 })
            .catch(() => {});
        await page.locator('[data-testid="tb-canvas-publish-button"]').click();
        expect((await publishResponse).ok()).toBeTruthy();
        await selfReload;
        await page.waitForLoadState('domcontentloaded').catch(() => {});

        // ── PUBLIC RENDER: same invariants as the draft preview, now on the real public homepage ──
        await page.goto(`${BASE_URL}/`, { waitUntil: 'domcontentloaded' });
        await expect(page.getByText(MARKER_A_EDITED)).toBeVisible({ timeout: 10_000 });
        await expect(page.getByText(MARKER_C)).toBeVisible({ timeout: 10_000 });
        await expect(page.getByText(MARKER_B)).toHaveCount(0);
        await expect(page.getByText('تب قیمت‌گذاری')).toHaveCount(0);

        const publicPanelA = page.locator('.tb-tab-panel', { hasText: MARKER_A_EDITED });
        await expect(publicPanelA).not.toContainText(MARKER_C);
        const publicPanelC = page.locator('.tb-tab-panel', { hasText: MARKER_C });
        await expect(publicPanelC).not.toContainText(MARKER_A_EDITED);

        const realErrors = errors.filter(
            (e) =>
                !/favicon|ERR_INTERNET_DISCONNECTED/i.test(e) &&
                !/(notification-center|system-alerts)\/unread-count|user-tickets\/check-new/i.test(e),
        );
        expect(realErrors, `Unexpected console/page errors: ${JSON.stringify(realErrors)}`).toHaveLength(0);
    });
});
