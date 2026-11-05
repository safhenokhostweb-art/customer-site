import { expect, test } from '@playwright/test';
import { VIEWPORTS } from './fixtures/viewports.js';
import { loginAsAdmin } from './helpers/auth.js';
import {
    addChildButton, canvasIframe, openAddChildModal, openCanvasEditor, openTreeTab,
    reloadCanvasEditor, saveButton, switchToPage, treeNode, waitForSaveBlocks,
} from './helpers/canvas.js';

/**
 * saas-platform ADR-079 — the Motion Graphics widget in the customer Canvas:
 *  1. module:motion nested in a container validates and renders (it used to
 *     placeholder the container: "[child 0] Unknown element type: module:motion");
 *  2. the inspector builds the selected motion's own runtime-parameter
 *     controls from the manifest, and edits persist and reach the render.
 * The motion manifest/SVG come from tests/browser/scripts/fake-platform-server.js.
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

test.describe('Canvas editor — Motion Graphics widget (ADR-079)', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test('a nested motion gets its own parameter controls, and edits persist and render', async ({ page }) => {
        const errors = collectConsoleErrors(page);
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'home');
        await openTreeTab(page);

        await treeNode(page, 'canvas_section_1').click();
        await openAddChildModal(page);
        await addChildButton(page, 'module:motion').click();

        const motionNode = page.locator('[data-testid^="tb-tree-node-"]').filter({ hasText: 'موشن گرافیک' }).first();
        await expect(motionNode).toBeVisible({ timeout: 10_000 });
        await motionNode.click();

        // Picker lists the manifest's asset; choosing it reveals its controls.
        await page.locator('[data-testid="tb-motion-pick-motion_asset_id"]').click();
        await page.locator('[data-testid="tb-motion-asset-e2e-hero-motion"]').click();
        await page.locator('[data-testid="tb-motion-confirm"]').click();

        const title = page.locator('[data-testid="tb-mp-window_title"]');
        await expect(title).toBeVisible({ timeout: 10_000 });
        await expect(title).toHaveValue('داشبورد هوشمند سازمان');
        await expect(page.locator('[data-testid="tb-mp-primary"]')).toBeVisible();
        await expect(page.locator('[data-testid="tb-mp-share"]')).toBeVisible();
        await expect(page.locator('[data-testid="tb-mp-loop"]')).toHaveCount(1);
        // The malformed definition ("Bad Key") never reaches the inspector.
        await expect(page.locator('[data-testid="tb-motion-params"] label', { hasText: 'dropped by the reader' })).toHaveCount(0);

        await title.fill('پنل مدیریت آریا');
        await page.locator('[data-testid="tb-mp-speed"]').selectOption('fast');
        await page.waitForTimeout(600); // inspector debounce

        const response = await waitForSaveBlocks(page, () => saveButton(page).click());
        expect(response.ok()).toBeTruthy();

        // Persistence: reload, reselect, the override is still there.
        await reloadCanvasEditor(page);
        await switchToPage(page, 'home');
        await openTreeTab(page);
        await page.locator('[data-testid^="tb-tree-node-"]').filter({ hasText: 'موشن گرافیک' }).first().click();
        await expect(page.locator('[data-testid="tb-mp-window_title"]')).toHaveValue('پنل مدیریت آریا', { timeout: 10_000 });
        await expect(page.locator('[data-testid="tb-mp-speed"]')).toHaveValue('fast');

        // Render: the section is not placeholdered and the motion URL carries
        // the edited parameters (only declared, non-default ones).
        const frame = canvasIframe(page);
        const img = frame.locator('img.tb-motion-svg').first();
        await expect(img).toBeVisible({ timeout: 15_000 });
        const src = decodeURIComponent(await img.getAttribute('src'));
        expect(src).toContain('/motion/e2e-hero/v2/animation.svg?');
        expect(src).toContain('window_title=پنل مدیریت آریا');
        expect(src).toContain('speed=fast');
        expect(src).not.toContain('primary=');
        await expect(img).toHaveAttribute('alt', 'موشن آزمایشی هیرو');
        await expect(frame.getByText('Unknown element type')).toHaveCount(0);

        await page.screenshot({ path: 'tests/browser/.runtime/adr079-canvas-motion-widget.png' });

        // Clean up the shared fixture: remove the inserted motion block.
        await page.locator('[data-testid^="tb-tree-node-"]').filter({ hasText: 'موشن گرافیک' }).first().click();
        await page.keyboard.press('Delete');
        await waitForSaveBlocks(page, () => saveButton(page).click());

        const realErrors = errors.filter(
            (e) =>
                !/favicon|ERR_INTERNET_DISCONNECTED/i.test(e) &&
                !/(notification-center|system-alerts)\/unread-count|user-tickets\/check-new/i.test(e),
        );
        expect(realErrors, `Unexpected console/page errors: ${JSON.stringify(realErrors)}`).toHaveLength(0);
    });
});
