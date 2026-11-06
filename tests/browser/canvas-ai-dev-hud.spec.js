import { expect, test } from '@playwright/test';
import { VIEWPORTS } from './fixtures/viewports.js';
import { BASE_URL } from './fixtures/env.js';
import { loginAsAdmin } from './helpers/auth.js';
import { canvasIframe, switchToPage } from './helpers/canvas.js';

/**
 * AI Dev HUD (partials/ai-dev-hud) in the customer Canvas editor:
 *
 *   - ?ai_dev=1 turns it on; selecting the seeded section shows the bar over
 *     it (type, «معتبر»), the instruction field and the copy button;
 *   - «کپی پرامپت هوش مصنوعی» puts the structured prompt on the clipboard
 *     (instruction, type, id, parent, files, errors, props) and toasts;
 *   - a real schema error (an invalid prop re-rendered by the server) turns
 *     it red and lands in the prompt and in the «سلامت ویجت» card;
 *   - without the URL flag, Ctrl+Shift+D toggles it, also from the preview;
 *   - read-only: copying sends no request.
 *
 * Nothing is saved: the invalid prop is restored before the test ends.
 */

const SECTION = 'canvas_section_1';

function collectConsoleErrors(page) {
    const errors = [];
    page.on('console', (msg) => { if (msg.type() === 'error') errors.push(`${msg.text()} (${msg.location().url})`); });
    page.on('pageerror', (err) => errors.push(String(err)));
    return errors;
}

const realErrorsOnly = (errors) => errors.filter((e) =>
    !/favicon|ERR_INTERNET_DISCONNECTED/i.test(e) && !/(notification-center|system-alerts)\/unread-count|user-tickets\/check-new/i.test(e));

const editor = (page, fn) => page.evaluate(`(() => { const s = window.Alpine.$data(document.querySelector('[x-data="canvasEditor()"]')); return (${fn})(s); })()`);

async function open(page, query = '') {
    await page.goto(`${BASE_URL}/admin/theme-design/canvas/edit${query}`, { waitUntil: 'domcontentloaded' });
    await page.locator('[data-testid="tb-canvas-save-button"]').waitFor({ state: 'visible', timeout: 15_000 });
    await switchToPage(page, 'home');
    await expect.poll(() => editor(page, '(s) => s.iframeReady'), { timeout: 20_000 }).toBe(true);
}

test.describe('AI Dev HUD (customer Canvas editor)', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test('?ai_dev=1: bar over the selection, structured prompt, schema error goes red, read-only', async ({ page, context }) => {
        test.setTimeout(120_000);
        const errors = collectConsoleErrors(page);
        await context.grantPermissions(['clipboard-read', 'clipboard-write'], { origin: new URL(BASE_URL).origin });
        // The OS clipboard may hand back CRLF (Windows): compare the text, not line endings.
        const clip = () => page.evaluate(() => navigator.clipboard.readText()).then((t) => t.replace(/\r\n/g, '\n'));

        await loginAsAdmin(page);
        await open(page, '?ai_dev=1');
        const bar = page.locator('[data-ai-dev-hud]');
        await expect(page.locator('[data-ai-dev-chip]')).toBeVisible();
        await expect(bar).toBeHidden(); // nothing selected yet

        // Select the seeded section in the preview, as an author would.
        const frame = canvasIframe(page);
        await frame.locator(`[data-tb-block-id="${SECTION}"]`).first().click({ position: { x: 20, y: 10 } });
        await expect(bar).toBeVisible();
        await expect(bar.locator('[data-ai-dev-type]')).toHaveText('section');
        await expect(bar.locator('[data-ai-dev-state]')).toHaveText('معتبر');
        await expect(bar.locator('[data-ai-dev-instruction]')).toHaveAttribute('placeholder', 'دستور رفع باگ یا افزودن امکان جدید...');

        // The bar sits over the section, inside the preview frame.
        const frameBox = await page.locator('#tb-live-iframe').boundingBox();
        const barBox = await bar.boundingBox();
        expect(barBox.left ?? barBox.x).toBeGreaterThanOrEqual(frameBox.x);
        expect(barBox.x + barBox.width).toBeLessThanOrEqual(frameBox.x + frameBox.width + 1);
        expect(barBox.y).toBeGreaterThanOrEqual(frameBox.y);

        // Copy: the structured prompt, no request of its own.
        const props = await editor(page, `(s) => JSON.parse(JSON.stringify(s.selectedBlock.props))`);
        await bar.locator('[data-ai-dev-instruction]').fill('فاصله داخلی موبایل را کمتر کن');
        const requests = [];
        const onRequest = (r) => requests.push(`${r.method()} ${r.url()}`);
        page.on('request', onRequest);
        await bar.locator('[data-ai-dev-copy]').click();
        await expect(page.getByText('کپی شد ✓')).toBeVisible();
        page.off('request', onRequest);
        expect(requests, 'read-only: copying sends nothing').toEqual([]);

        const prompt = await clip();
        const lines = prompt.split('\n');
        expect(lines[0]).toBe('### دستور عیب‌یابی / توسعه ویجت کانواس (Dev HUD)');
        expect(lines).toContain('- **دستور توسعه‌دهنده:** فاصله داخلی موبایل را کمتر کن');
        expect(lines).toContain('  * نوع المان (Type): `section`');
        expect(lines).toContain(`  * شناسه (ID/UUID): \`${SECTION}\``);
        expect(lines).toContain('  * والد (Parent): `page (ID: home)`');
        expect(prompt).toMatch(/\* مسیر احتمالی فایل‌ها: `[^`]*renderSection\(\)[^`]*packages\/saas-platform\/modules\/theme-runtime\/src\/BlockRegistry\.php/);
        expect(lines).toContain('  `هیچ خطایی ثبت نشده است.`');
        const json = prompt.split('  ```json\n')[1].split('\n  ```')[0].split('\n').map((l) => l.slice(2)).join('\n');
        expect(JSON.parse(json)).toEqual(props);
        expect(lines.at(-1)).toBe('- **اقدام مورد نیاز:** لطفاً بر اساس داده‌های ساختاری فوق، فایل‌های مربوطه را بررسی کرده و تغییرات لازم را در کد پیاده کن.');

        // A real schema error: the server re-renders the section as an invalid_schema placeholder.
        const padding = props.padding ?? null;
        await editor(page, `(s) => s.schedulePropUpdate('padding', { x: 1 })`);
        await expect(frame.locator(`#tb-${SECTION}.tb-placeholder[data-reason="invalid_schema"]`)).toHaveCount(1, { timeout: 15_000 });
        await expect(bar.locator('[data-ai-dev-state]')).toHaveText('خطای اسکیما');
        await bar.locator('[data-ai-dev-copy]').click();
        await expect.poll(clip).toContain('  `[schema] Block "section": prop "padding" must be string.`');

        // The «سلامت ویجت» tab shows the same diagnostics.
        await page.locator('[data-testid="tb-canvas-health-tab"]').click();
        const card = page.locator('[data-ai-dev-health]');
        await expect(card).toBeVisible();
        await expect(card.locator('[data-ai-dev-health-state]')).toHaveText('خطای اسکیما');
        await expect(card).toContainText('[schema] Block "section": prop "padding" must be string.');
        await page.screenshot({ path: 'tests/browser/.runtime/ai-dev-hud-schema-error.png' });

        // Restore (never saved) and it is green again.
        await editor(page, `(s) => { s.schedulePropUpdate('padding', ${JSON.stringify(padding)}); s.isDirty = false; }`);
        await expect(bar.locator('[data-ai-dev-state]')).toHaveText('معتبر', { timeout: 15_000 });
        await expect(card.locator('[data-ai-dev-health-state]')).toHaveText('معتبر');
        await editor(page, '(s) => { s.isDirty = false; }');
        expect(realErrorsOnly(errors), realErrorsOnly(errors).join('\n')).toHaveLength(0);
    });

    test('without the URL flag Ctrl+Shift+D toggles it, from the editor and from the preview', async ({ page }) => {
        test.setTimeout(90_000);
        const errors = collectConsoleErrors(page);
        await loginAsAdmin(page);
        await open(page);
        const bar = page.locator('[data-ai-dev-hud]');
        const chip = page.locator('[data-ai-dev-chip]');
        await canvasIframe(page).locator(`[data-tb-block-id="${SECTION}"]`).first().click({ position: { x: 20, y: 10 } });
        await expect(chip).toBeHidden();
        await expect(bar).toBeHidden();

        // Focus is in the preview after the click: its own listener toggles.
        await page.keyboard.press('Control+Shift+KeyD');
        await expect(bar).toBeVisible();
        await expect(chip).toBeVisible();

        await page.evaluate(() => { document.activeElement?.blur(); window.focus(); });
        await page.keyboard.press('Control+Shift+KeyD');
        await expect(bar).toBeHidden();
        await expect(chip).toBeHidden();
        expect(realErrorsOnly(errors), realErrorsOnly(errors).join('\n')).toHaveLength(0);
    });
});
