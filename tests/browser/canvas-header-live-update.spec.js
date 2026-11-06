import { expect, test } from '@playwright/test';
import { VIEWPORTS } from './fixtures/viewports.js';
import { BASE_URL } from './fixtures/env.js';
import { loginAsAdmin } from './helpers/auth.js';
import { switchToPage } from './helpers/canvas.js';

/**
 * Live edits of a nav_header reach the customer preview without a reload.
 *
 * The header's markup opens with its own <style>. ThemeCanvasRuntime.
 * replaceNode() used to take the first element of the new markup as the
 * block, so the swap failed and edits showed only after a reload. Now the
 * root is the element carrying the block id and the style travels with it:
 * two live edits show up, the header keeps exactly one <style>, and the
 * preview is never reloaded. The header is removed again and the page saved.
 */

const editor = (page, fn) => page.evaluate(`(() => { const s = window.Alpine.$data(document.querySelector('[x-data="canvasEditor()"]')); return (${fn})(s); })()`);

function collectConsoleErrors(page) {
    const errors = [];
    page.on('console', (msg) => { if (msg.type() === 'error') errors.push(`${msg.text()} (${msg.location().url})`); });
    page.on('pageerror', (err) => errors.push(String(err)));
    return errors;
}
const realErrorsOnly = (errors) => errors.filter((e) =>
    !/favicon|ERR_INTERNET_DISCONNECTED/i.test(e) && !/(notification-center|system-alerts)\/unread-count|user-tickets\/check-new/i.test(e));

test.describe('nav_header live update in the customer preview', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test('prop edits swap the header and its style in place, without a reload', async ({ page }) => {
        test.setTimeout(120_000);
        const errors = collectConsoleErrors(page);
        await loginAsAdmin(page);
        await page.goto(`${BASE_URL}/admin/theme-design/canvas/edit`, { waitUntil: 'domcontentloaded' });
        await page.locator('[data-testid="tb-canvas-save-button"]').waitFor({ state: 'visible', timeout: 15_000 });
        await switchToPage(page, 'home');
        await expect.poll(() => editor(page, '(s) => s.iframeReady'), { timeout: 20_000 }).toBe(true);

        const stamp = await editor(page, '(s) => s.iframeTimestamp');
        await editor(page, `(s) => s.addBlock('nav_header')`);
        await expect.poll(() => editor(page, `(s) => !s.quickSaving && s.iframeTimestamp !== ${stamp} && s.iframeReady && s.selectedBlock?.type === "nav_header"`), { timeout: 20_000 }).toBe(true);
        const id = await editor(page, '(s) => s.selectedBlock.id');
        const frame = page.frameLocator('#tb-live-iframe');
        const header = frame.locator(`#tb-${id}`);
        await expect(header).toHaveAttribute('data-tb-block-id', id);
        const loaded = await editor(page, '(s) => s.iframeTimestamp');
        // The header's own <style>s: right before its root, or tagged by a live update.
        const ownStyles = () => frame.locator('body').evaluate((_b, bid) => {
            const root = document.getElementById('tb-' + bid);
            const tagged = document.querySelectorAll(`style[data-tb-block-asset="${bid}"]`).length;
            let untagged = 0;
            for (let el = root && root.previousElementSibling; el && el.tagName === 'STYLE' && !el.hasAttribute('data-tb-block-asset'); el = el.previousElementSibling) untagged++;
            return { tagged, untagged, roots: document.querySelectorAll(`[data-tb-block-id="${bid}"]:not(style)`).length };
        }, id);

        try {
            await editor(page, `(s) => { s.selectedBlock.props.cta_text = 'اولین ویرایش زنده'; s.selectedBlock.props.cta_url = '/contact'; s.schedulePropUpdate(); }`);
            await expect(header.locator('.tb-nav-header-cta')).toHaveText('اولین ویرایش زنده', { timeout: 15_000 });
            expect(await ownStyles()).toEqual({ tagged: 1, untagged: 0, roots: 1 });

            await editor(page, `(s) => { s.selectedBlock.props.cta_text = 'دومین ویرایش زنده'; s.schedulePropUpdate(); }`);
            await expect(header.locator('.tb-nav-header-cta')).toHaveText('دومین ویرایش زنده', { timeout: 15_000 });
            expect(await ownStyles(), 'still exactly one style for the header').toEqual({ tagged: 1, untagged: 0, roots: 1 });
            await expect(header).toHaveClass(/tb-selected/);
            expect(await editor(page, '(s) => s.iframeTimestamp'), 'no reload: the edit was live').toBe(loaded);
            await page.screenshot({ path: 'tests/browser/.runtime/header-live-update.png' });
        } finally {
            await editor(page, `(s) => { s.selectBlock(s.findBlockPath('${id}')); s.removeSelected(); }`);
            await expect.poll(() => editor(page, `(s) => !s.findBlockPath('${id}')`)).toBe(true);
            await editor(page, '(s) => s.saveCurrentPage()');
            await expect.poll(() => editor(page, '(s) => !s.saving && !s.quickSaving && !s.isDirty'), { timeout: 15_000 }).toBe(true);
        }
        expect(realErrorsOnly(errors), realErrorsOnly(errors).join('\n')).toHaveLength(0);
    });
});
