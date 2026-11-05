import { expect, test } from '@playwright/test';
import { VIEWPORTS } from './fixtures/viewports.js';
import { BASE_URL } from './fixtures/env.js';
import { loginAsAdmin } from './helpers/auth.js';
import { canvasIframe, openCanvasEditor, switchToPage } from './helpers/canvas.js';

/**
 * WU-1 Inspect Mode + WU-2 Design HUD in the customer Canvas editor
 * (theme-runtime::canvas.inspect, the same include as the platform editor):
 *
 *   - the toolbar button and Ctrl+I (editor chrome and inside the preview)
 *     toggle one state; a text field keeps Ctrl+I; the state survives a
 *     preview reload;
 *   - hovering the seeded section labels it with its data-widget-type,
 *     clicking opens the action bar, "کپی شناسه" copies the type;
 *   - turning Inspect on moves nothing (outline only);
 *   - the HUD chip reports the preview's audit, and a planted overflow at
 *     the mobile preview width is reported and copied as a fix task.
 *
 * Nothing is saved: the planted node lives only in the preview DOM.
 */

function collectConsoleErrors(page) {
    const errors = [];
    page.on('console', (msg) => {
        if (msg.type() === 'error') errors.push(`${msg.text()} (${msg.location().url})`);
    });
    page.on('pageerror', (err) => errors.push(String(err)));
    return errors;
}

function realErrorsOnly(errors) {
    return errors.filter(
        (e) =>
            !/favicon|ERR_INTERNET_DISCONNECTED/i.test(e) &&
            !/(notification-center|system-alerts)\/unread-count|user-tickets\/check-new/i.test(e),
    );
}

const editor = (page, fn) => page.evaluate(`(() => { const s = window.Alpine.$data(document.querySelector('[x-data="canvasEditor()"]')); return (${fn})(s); })()`);
const editorState = (page) => editor(page, `(s) => ({ inspectOn: s.inspectOn, iframeReady: s.iframeReady,
    audit: s.auditSummary ? { tone: s.auditSummary.tone, counts: s.auditSummary.counts, width: s.auditSummary.width } : null })`);

test.describe('Canvas Inspect Mode + Design HUD (customer host)', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test('toggle, label, action bar, clipboard, reload and live HUD', async ({ page, context }) => {
        test.setTimeout(120_000);
        const errors = collectConsoleErrors(page);
        await context.grantPermissions(['clipboard-read', 'clipboard-write'], { origin: new URL(BASE_URL).origin });
        const clip = () => page.evaluate(() => navigator.clipboard.readText());

        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'home');
        await expect.poll(async () => (await editorState(page)).iframeReady, { timeout: 20_000 }).toBe(true);

        const toggle = page.locator('[data-tb-inspect-toggle]');
        const frame = canvasIframe(page);
        const widget = frame.locator('[data-tb-block-id="canvas_section_1"][data-widget-type="section"]');
        await expect(widget).toHaveCount(1);
        const box = () => widget.evaluate((el) => {
            const r = el.getBoundingClientRect();
            return { x: r.left + window.scrollX, y: r.top + window.scrollY, w: r.width, h: r.height };
        });
        const before = await box();

        // Toolbar button.
        await expect(toggle).toHaveAttribute('aria-pressed', 'false');
        await toggle.click();
        await expect(toggle).toHaveAttribute('aria-pressed', 'true');
        await expect(frame.locator('html[data-tb-inspect]')).toHaveCount(1);
        expect(await box(), 'outline only: the widget does not move or resize').toEqual(before);

        // Hover label, then select and copy.
        await widget.hover({ position: { x: 20, y: 10 } });
        await expect(frame.locator('[data-tb-inspect-ui] >> css=.box.hover.on .type')).toHaveText('section');
        await widget.click({ position: { x: 20, y: 10 } });
        const bar = frame.locator('[data-tb-inspect-ui] >> css=.bar.on');
        await expect(bar.locator('button[data-action="copy-type"]')).toHaveText('کپی شناسه');
        await expect(bar.locator('button[data-action="copy-prompt"]')).toHaveCount(1);
        await bar.locator('button[data-action="copy-type"]').click();
        await expect.poll(clip).toBe('section');

        // HUD: the chip reports the preview's audit.
        await expect.poll(async () => (await editorState(page)).audit?.tone ?? null, { timeout: 15_000 }).not.toBeNull();
        const chip = page.locator('[data-tb-audit-chip]');
        await expect(chip).toBeVisible();

        // A planted 800 px nowrap line, then the mobile device: the resize
        // re-queues every widget (a DOM append alone queues nothing, by design).
        await widget.evaluate((el) => {
            const p = document.createElement('p');
            p.setAttribute('data-planted', '1');
            p.style.cssText = 'white-space:nowrap;display:inline-block;width:800px';
            p.textContent = '۰۹۱۲ ۳۴۵ ۶۷۸۹';
            el.appendChild(p);
        });
        await editor(page, `(s) => s.setDevice('mobile')`);
        await expect.poll(() => frame.locator('body').evaluate(() => document.documentElement.clientWidth)).toBeLessThan(500);
        await expect.poll(() => widget.evaluate((el) => window.TbAudit.findings(el).map((f) => f.code)), { timeout: 15_000 })
            .toEqual(expect.arrayContaining([expect.stringMatching(/^WIDGET_(CONTENT_)?OVERFLOW$/)]));
        await expect(chip).toHaveAttribute('data-tone', /^(error|warn)$/);
        expect((await editorState(page)).audit.width, 'the HUD measured the mobile preview').toBeLessThan(500);
        await widget.click({ position: { x: 10, y: 10 } });
        await bar.locator('button[data-action="copy-fix-task"]').click();
        await expect.poll(clip).toMatch(/WIDGET_(CONTENT_)?OVERFLOW/);
        expect(await clip()).toContain('section#canvas_section_1');
        await page.screenshot({ path: 'tests/browser/.runtime/inspect-hud-customer-mobile.png' });
        await editor(page, `(s) => s.setDevice('desktop')`);

        // Ctrl+I from inside the preview turns it off and the toolbar follows.
        await widget.click({ position: { x: 20, y: 10 } });
        await page.keyboard.press('Control+KeyI');
        await expect(toggle).toHaveAttribute('aria-pressed', 'false');
        await expect(frame.locator('html[data-tb-inspect]')).toHaveCount(0);
        await expect(chip).toBeHidden();

        // Ctrl+I from the editor chrome turns it on again.
        await page.evaluate(() => { document.activeElement?.blur(); window.focus(); });
        await page.keyboard.press('Control+KeyI');
        await expect(toggle).toHaveAttribute('aria-pressed', 'true');

        // A text field keeps Ctrl+I.
        await page.locator('input[x-model="librarySearch"]').focus();
        await page.keyboard.press('Control+KeyI');
        await expect(toggle).toHaveAttribute('aria-pressed', 'true');

        // The state survives a preview reload (which also drops the planted node).
        await editor(page, `(s) => s.reloadIframe()`);
        await expect(frame.locator('html[data-tb-inspect]')).toHaveCount(1, { timeout: 20_000 });
        await expect(frame.locator('[data-planted]')).toHaveCount(0);

        await page.screenshot({ path: 'tests/browser/.runtime/inspect-hud-customer-1440.png' });
        await toggle.click();
        await expect(frame.locator('html[data-tb-inspect]')).toHaveCount(0);
        expect(realErrorsOnly(errors), realErrorsOnly(errors).join('\n')).toHaveLength(0);
    });
});
