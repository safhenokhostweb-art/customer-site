import { expect, test } from '@playwright/test';
import { BASE_URL } from './fixtures/env.js';
import { VIEWPORTS } from './fixtures/viewports.js';
import { loginAsAdmin } from './helpers/auth.js';

/**
 * Phase 3F-B — real browser verification of the customer-site Canvas
 * editor (resources/views/admin/theme-design/canvas-editor.blade.php),
 * proving the new declarative container-capability metadata (replacing
 * that file's own hardcoded isContainerType() JS array) did not change the
 * pre-existing, unmodified-this-phase nested authoring behavior. This does
 * NOT exercise 3F-C (Extension SDK → Canvas bridge) — only built-in
 * container capability.
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

test.describe('Canvas editor — container capability (3F-B)', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test('existing built-in section container remains recognized; real child authoring, save, and reload work', async ({ page }) => {
        const errors = collectConsoleErrors(page);

        await loginAsAdmin(page);
        await page.goto(`${BASE_URL}/admin/theme-design/canvas/edit`, { waitUntil: 'domcontentloaded' });

        // Real Canvas editor loads without a fatal.
        await expect(page.locator('[data-testid="tb-canvas-save-button"]')).toBeVisible({ timeout: 15_000 });

        // The editor defaults to the virtual __header__ page tab — switch
        // to the fixture's real 'home' page, which carries the section.
        await page.locator('[data-testid="tb-canvas-page-tab-home"]').click();

        // Switch to the structure/tree tab and select the fixture's
        // pre-existing built-in `section` container block.
        await page.locator('[data-testid="tb-canvas-tree-tab"]').click();
        const sectionNode = page.locator('[data-testid="tb-tree-node-canvas_section_1"]');
        await expect(sectionNode).toBeVisible();
        await sectionNode.click();

        // The container-children panel only renders when isContainerType()
        // (now server-driven, not the old hardcoded array) returns true for
        // this real built-in type — its mere presence is itself part of
        // the proof.
        const addChildButton = page.locator('[data-testid="tb-open-add-child-modal"]');
        await expect(addChildButton).toBeVisible({ timeout: 10_000 });
        await addChildButton.click();

        // Real child authoring: add a built-in heading as a child of the section.
        const addHeadingChild = page.locator('[data-testid="tb-add-child-heading"]');
        await expect(addHeadingChild).toBeVisible();
        await addHeadingChild.click();

        // The "no children" empty-state message must disappear once a real
        // child has been added.
        await expect(page.getByText('هیچ بلوک فرزندی وجود ندارد')).toHaveCount(0);

        // Real save through the real PUT endpoint.
        const saveResponse = page.waitForResponse((res) => res.url().includes('theme-design/canvas/save-blocks') && res.request().method() === 'PUT');
        await page.locator('[data-testid="tb-canvas-save-button"]').click();
        const response = await saveResponse;
        expect(response.ok()).toBeTruthy();

        // Real full reload — proves persistence, not in-memory Alpine state.
        await page.reload({ waitUntil: 'domcontentloaded' });
        await expect(page.locator('[data-testid="tb-canvas-save-button"]')).toBeVisible({ timeout: 15_000 });
        await page.locator('[data-testid="tb-canvas-page-tab-home"]').click(); // reload resets to the default __header__ tab
        await page.locator('[data-testid="tb-canvas-tree-tab"]').click();

        // The section's child must have survived the reload — proof of
        // real persisted nested content, and that the section is still
        // recognized as a container after reload (tree shows nested depth).
        const childNode = page.locator('[data-testid^="tb-tree-node-"]').filter({ hasText: 'عنوان' });
        await expect(childNode.first()).toBeVisible({ timeout: 10_000 });

        await page.screenshot({ path: 'tests/browser/.runtime/3f-b-canvas-container.png' });

        // Same self-inflicted, documented exclusion as the D0/H-D harness:
        // loginAsAdmin() deliberately aborts these two admin-layout polling
        // endpoints (irrelevant background noise against the single-
        // threaded dev server), which Chromium still logs as a console
        // error for the aborted request itself.
        const realErrors = errors.filter(
            (e) =>
                !/favicon|ERR_INTERNET_DISCONNECTED/i.test(e) &&
                !/(notification-center|system-alerts)\/unread-count|user-tickets\/check-new/i.test(e),
        );
        expect(realErrors, `Unexpected console/page errors: ${JSON.stringify(realErrors)}`).toHaveLength(0);
    });
});
