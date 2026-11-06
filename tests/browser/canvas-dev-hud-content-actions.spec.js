import { expect, test } from '@playwright/test';
import { VIEWPORTS } from './fixtures/viewports.js';
import { BASE_URL } from './fixtures/env.js';
import { loginAsAdmin } from './helpers/auth.js';
import { switchToPage } from './helpers/canvas.js';

/**
 * AI Dev HUD: User Content Action vs Core Code Issue, with the deep-link.
 *
 * A header whose Bale quick-access link is "#" and whose CTA has text but no
 * link. The HUD lists both as content gaps (amber, not code), the preview's
 * dead Bale anchor is mapped to social_links[0].url, «تنظیم فیلد در پنل
 * ادیتور» opens the Inspector on that exact input with focus, filling it
 * clears the gap live, the CTA's button lands on cta_url, and the copied
 * prompt carries them under «نواقص محتوایی قابل تنظیم توسط مدیر», not
 * under the code errors. The header is removed again and the page saved.
 */

const editor = (page, fn) => page.evaluate(`(() => { const s = window.Alpine.$data(document.querySelector('[x-data="canvasEditor()"]')); return (${fn})(s); })()`);
const hud = (page, fn) => page.evaluate(`(() => { const h = window.Alpine.store('devHud'); return (${fn})(h); })()`);

function collectConsoleErrors(page) {
    const errors = [];
    page.on('console', (msg) => { if (msg.type() === 'error') errors.push(`${msg.text()} (${msg.location().url})`); });
    page.on('pageerror', (err) => errors.push(String(err)));
    return errors;
}
const realErrorsOnly = (errors) => errors.filter((e) =>
    !/favicon|ERR_INTERNET_DISCONNECTED/i.test(e) && !/(notification-center|system-alerts)\/unread-count|user-tickets\/check-new/i.test(e));

test.describe('AI Dev HUD content actions', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test('dead links are content gaps with a deep-link to the exact Inspector field', async ({ page, context }) => {
        test.setTimeout(150_000);
        const errors = collectConsoleErrors(page);
        await context.grantPermissions(['clipboard-read', 'clipboard-write'], { origin: new URL(BASE_URL).origin });
        const clip = () => page.evaluate(() => navigator.clipboard.readText()).then((t) => t.replace(/\r\n/g, '\n'));

        await loginAsAdmin(page);
        await page.goto(`${BASE_URL}/admin/theme-design/canvas/edit?ai_dev=1`, { waitUntil: 'domcontentloaded' });
        await page.locator('[data-testid="tb-canvas-save-button"]').waitFor({ state: 'visible', timeout: 15_000 });
        await switchToPage(page, 'home');
        await expect.poll(() => editor(page, '(s) => s.iframeReady'), { timeout: 20_000 }).toBe(true);

        // addBlock() saves, then reloads the preview: wait for that reload, or it drops the live edits below.
        const stamp = await editor(page, '(s) => s.iframeTimestamp');
        await editor(page, `(s) => s.addBlock('nav_header')`);
        await expect.poll(() => editor(page, `(s) => !s.quickSaving && s.iframeTimestamp !== ${stamp} && s.iframeReady && s.selectedBlock?.type === "nav_header"`), { timeout: 20_000 }).toBe(true);
        const id = await editor(page, '(s) => s.selectedBlock.id');

        try {
            await editor(page, `(s) => {
                const p = s.selectedBlock.props;
                p.logo_text = 'برند';
                p.nav_links = [{ label: 'خانه', url: '/' }];
                p.social_links = [{ url: '#', label: '', network: 'bale' }, { url: 'https://eitaa.com/shop', label: '', network: 'eitaa' }];
                p.cta_text = 'ثبت سفارش';
                p.cta_url = '';
            }`);
            // Save and reload: a live update cannot swap a nav_header in this preview
            // (ThemeCanvasRuntime.replaceNode() takes the markup's first element, the
            // header's own <style>) — a known runtime bug outside this change.
            const stamp2 = await editor(page, '(s) => s.iframeTimestamp');
            await editor(page, '(s) => s.autoSaveAndReload()');
            await expect.poll(() => editor(page, `(s) => !s.quickSaving && s.iframeTimestamp !== ${stamp2} && s.iframeReady`), { timeout: 20_000 }).toBe(true);
            const frame = page.frameLocator('#tb-live-iframe');
            await expect(frame.locator(`#tb-${id} a.tb-social-icon--bale[href="#"]`)).toHaveCount(1, { timeout: 15_000 });

            // Two content gaps; the Bale one is also the preview's dead anchor; none is a code issue.
            await expect.poll(() => hud(page, '(h) => h.contentIssues.map((i) => i.field && i.field.path)'), { timeout: 15_000 })
                .toEqual(['social_links[0].url', 'cta_url']);
            // The props find the gap at once; the preview's dead anchor joins on the HUD's next refresh.
            await expect.poll(() => hud(page, `(h) => h.contentIssues[0].seenInPreview`), { timeout: 10_000 }).toBe(true);
            expect(await hud(page, `(h) => h.coreIssues.some((i) => /DEAD_LINK/.test(i.message))`)).toBe(false);
            expect(await hud(page, '(h) => h.tone')).not.toBe('error');

            const bar = page.locator('[data-ai-dev-hud]');
            await expect(bar).toBeVisible();
            const fix = bar.locator('[data-ai-dev-fix-field]');
            await expect(fix).toHaveText('تنظیم فیلد در پنل ادیتور');

            // The health tab shows the two groups apart.
            await page.locator('[data-testid="tb-canvas-health-tab"]').click();
            const content = page.locator('[data-ai-dev-content]');
            await expect(content.locator('[data-ai-dev-content-item="social_links[0].url"]')).toContainText('لینک «بله» مقصد ندارد');
            await expect(content.locator('[data-ai-dev-content-item="cta_url"]')).toBeVisible();
            await page.screenshot({ path: 'tests/browser/.runtime/dev-hud-content-actions.png' });

            // Deep-link from the bar: the Bale item's URL input, selected and focused.
            await fix.click();
            const bale = page.locator('#tb-structured-social_links-0-url');
            await expect(bale).toBeFocused();
            await expect(page.getByTestId('tb-repeater-item-social_links-0')).toHaveClass(/border-indigo-500/);
            await expect(bale).toBeInViewport();
            expect(await editor(page, '(s) => s.selectedTab')).toBe('content');
            await page.screenshot({ path: 'tests/browser/.runtime/dev-hud-deep-link.png' });

            // Filling it clears the gap live; the next one is the CTA link.
            await bale.fill('https://ble.ir/shop');
            await expect.poll(() => hud(page, '(h) => h.contentIssues.map((i) => i.field && i.field.path)'), { timeout: 10_000 }).toEqual(['cta_url']);
            await page.locator('[data-testid="tb-canvas-health-tab"]').click();
            await content.locator('[data-ai-dev-content-item="cta_url"] [data-ai-dev-fix]').click();
            await expect(page.getByTestId('tb-prop-cta_url')).toBeFocused();

            // The prompt keeps content gaps out of the code errors.
            await bar.locator('[data-ai-dev-copy]').click();
            await expect.poll(clip).toContain('- **نواقص محتوایی قابل تنظیم توسط مدیر:**');
            const prompt = await clip();
            expect(prompt).toContain('  > این‌ها مقدار محتوایی‌اند و مدیر سایت آن‌ها را در پنل ادیتور پر می‌کند. برایشان سورس PHP/CSS را تغییر نده، مگر اینکه اسکیما یا بایندینگ همان فیلد خراب باشد.');
            expect(prompt).toContain('  * `DEAD_LINK` — فیلد `cta_url`: لینک «ثبت سفارش» مقصد ندارد.');
            const core = prompt.split('- **خطاهای ثبت‌شده در ادیتور:**')[1].split('- **')[0];
            expect(core).not.toContain('DEAD_LINK');
        } finally {
            await editor(page, `(s) => { s.selectBlock(s.findBlockPath('${id}')); s.removeSelected(); }`);
            await expect.poll(() => editor(page, `(s) => !s.findBlockPath('${id}')`)).toBe(true);
            await editor(page, '(s) => s.saveCurrentPage()');
            await expect.poll(() => editor(page, '(s) => !s.saving && !s.quickSaving && !s.isDirty'), { timeout: 15_000 }).toBe(true);
        }
        expect(realErrorsOnly(errors), realErrorsOnly(errors).join('\n')).toHaveLength(0);
    });
});
