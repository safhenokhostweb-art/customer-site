import { expect, test } from '@playwright/test';
import { VIEWPORTS } from './fixtures/viewports.js';
import { loginAsAdmin } from './helpers/auth.js';
import {
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

/**
 * Phase 3F-D — real-browser regression for the heading.level validation
 * defect (ADR-044's deferred item A): neither Canvas's nor Production
 * Builder's `<select>` control for `level` uses Alpine's `.number`
 * modifier, so an actual user selection arrives as a string ('2'), which
 * ElementSchemaRegistry::assertType() previously rejected outright — and
 * because validate() recurses into children and fails the whole subtree on
 * any child failure, a nested heading silently invalidated its entire
 * parent container. This test exercises the REAL <select> dropdown (not a
 * JSON injection) against the pre-existing built-in `section` container
 * already seeded on the 'home' Canvas page (see 3F-B's fixture) — this bug
 * predates and is independent of the Extension SDK bridge, so no extension
 * provider is required for this spec.
 *
 * Note: unlike Extension SDK blocks (whose tree node text is the
 * extension's own declared name), a built-in heading's tree node always
 * shows the generic type label ("عنوان"), never its per-instance authored
 * text — so heading nodes are identified here by position/count, not text,
 * and the authored content is verified on the public render instead, where
 * it's real rendered text.
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

test.describe('Canvas heading.level validation fix', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test('top-level heading: real <select> level change survives save/reload/publish/public render', async ({ page }) => {
        const errors = collectConsoleErrors(page);
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'home');
        // Note: left-panel palette ("بلوک‌ها") and tree ("ساختار") tabs are
        // mutually exclusive — palette clicks below run while that tab is
        // still the default active one.

        // addBlock() auto-saves in the background as a side effect of
        // adding a block (with its still-default props) — a pre-existing
        // Canvas editor behavior, unrelated to this fix. Waiting for that
        // request to land before editing avoids a real race where the
        // earlier, stale auto-save could otherwise reach the server AFTER
        // this test's own explicit (edited) save and silently overwrite it.
        await waitForSaveBlocks(page, () => paletteAddButton(page, 'heading').click()); // auto-selected
        await propField(page, 'text').fill('Top Level Heading');
        // Real <select> interaction — this is exactly what produces the
        // string-shaped value that used to break validation.
        await propField(page, 'level').selectOption('3');

        const saveResponse = await waitForSaveBlocks(page, () => saveButton(page).click());
        expect(saveResponse.ok()).toBeTruthy();

        await reloadCanvasEditor(page);
        await switchToPage(page, 'home');

        // Before the fix, the heading itself would already fail validation
        // at the outer gate (block-level [invalid]) — proving both the
        // pre-existing section (3F-B fixture) and the new heading are
        // present (2 top-level nodes) is proof the block round-tripped.
        // Read the real, freshly-server-loaded Alpine state directly
        // (not a DB query — the same client-side state the page itself
        // just rendered from) rather than the level <select>'s own
        // DISPLAYED value: incidentally discovered while writing this test,
        // that widget has a separate, pre-existing Canvas UI bug (an Alpine
        // x-model/x-for ordering quirk) where it can fail to show its
        // correct initially-selected option on first render even though
        // the underlying bound data is completely correct — unrelated to,
        // and out of scope for, this validation-boundary fix.
        const loadedBlocks = await page.evaluate(() => {
            const el = document.querySelector('[x-data]');
            return window.Alpine.$data(el).pages['home'].blocks;
        });
        expect(loadedBlocks).toHaveLength(2);
        const heading = loadedBlocks.find((b) => b.type === 'heading');
        expect(heading?.props?.level).toBe('3');
        expect(heading?.props?.text).toBe('Top Level Heading');

        await waitForPublish(page, () => publishButton(page).click());

        await publicPage(page, null); // 'home' is the Canvas home page, served at '/'
        await expect(page.locator('h3', { hasText: 'Top Level Heading' })).toBeVisible();

        expect(realErrorsOnly(errors)).toHaveLength(0);
    });

    test('nested heading inside the pre-existing built-in section: parent subtree is preserved, not dropped', async ({ page }) => {
        const errors = collectConsoleErrors(page);
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'home');
        await openTreeTab(page);

        // The section seeded by the 3F-B fixture (canvas_section_1).
        const sectionNode = treeNode(page, 'canvas_section_1');
        await expect(sectionNode).toBeVisible();
        await sectionNode.click();
        await openAddChildModal(page);
        // See the top-level test's note: wait for the add's own background
        // auto-save before editing, to avoid it landing after (and
        // overwriting) this test's own explicit edited save.
        await waitForSaveBlocks(page, () => addChildButton(page, 'heading').click()); // auto-selects the new heading child
        await propField(page, 'text').fill('Nested Heading Level 4');
        await propField(page, 'level').selectOption('4');

        const saveResponse = await waitForSaveBlocks(page, () => saveButton(page).click());
        expect(saveResponse.ok()).toBeTruthy();

        await reloadCanvasEditor(page);
        await switchToPage(page, 'home');
        await openTreeTab(page);

        // Before the fix: the whole section (parent) would fail schema
        // validation because of the nested string-level child and render
        // as [invalid] — proving the section is STILL present, and now has
        // at least one real child, is the exact repro. A non-zero count
        // (not an exact "(1)") is asserted: canvas_section_1 is a shared
        // fixture node other browser spec files (e.g. 3F-B's own
        // canvas-container.spec.js) may also add a child to when run
        // together in the same overall invocation.
        await expect(treeNode(page, 'canvas_section_1')).toBeVisible({ timeout: 10_000 });
        await treeNode(page, 'canvas_section_1').click();
        const childrenBadge = page.locator('text=/فرزندان \\([1-9]\\d*\\)/');
        await expect(childrenBadge).toBeVisible({ timeout: 10_000 });

        await waitForPublish(page, () => publishButton(page).click());

        await publicPage(page, null);
        const nestedHeading = page.locator('h4', { hasText: 'Nested Heading Level 4' });
        await expect(nestedHeading).toBeVisible();
        // Confirms nesting, not merely presence: the heading is a real DOM
        // descendant of the section it was authored inside.
        await expect(page.locator('.tb-section', { has: nestedHeading }).first()).toBeVisible();

        expect(realErrorsOnly(errors)).toHaveLength(0);
    });

    test('representative valid levels (1, 2, 3) render the correct heading tag', async ({ page }) => {
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'home');

        for (const level of ['1', '2', '3']) {
            await waitForSaveBlocks(page, () => paletteAddButton(page, 'heading').click());
            await propField(page, 'text').fill(`Level ${level} Heading`);
            await propField(page, 'level').selectOption(level);
            await waitForSaveBlocks(page, () => saveButton(page).click());
        }

        await waitForPublish(page, () => publishButton(page).click());
        await publicPage(page, null);

        for (const level of ['1', '2', '3']) {
            await expect(page.locator(`h${level}`, { hasText: `Level ${level} Heading` })).toBeVisible();
        }
    });
});
