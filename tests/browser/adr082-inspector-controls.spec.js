import { expect, test } from '@playwright/test';
import { VIEWPORTS } from './fixtures/viewports.js';
import { BASE_URL } from './fixtures/env.js';
import { loginAsAdmin } from './helpers/auth.js';
import { switchToPage } from './helpers/canvas.js';

/**
 * ADR-082 (saas-platform) in the customer Canvas Inspector, on a real
 * nav_header: the three tabs list the widget's own fields (`panel`), a
 * `condition` shows a field only while it has an effect, the header hides
 * the generic Style sections, and the shared controls (range, per-device
 * values, Normal / Hover / Active, spacing, shadow) write the right keys.
 * The test-only fields live in the client-side schema and are never saved;
 * the header is removed again and the page saved.
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

const tab = (page, label) => page.locator('button', { hasText: new RegExp(`^\\s*${label}\\s*$`) }).first();

test.describe('ADR-082 Inspector controls in the customer Canvas', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test('header tabs, conditions, hidden generic style and the shared controls', async ({ page }) => {
        test.setTimeout(180_000);
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

        try {
            // Content: own fields, conditions
            await editor(page, `(s) => { s.selectedTab = 'content'; }`);
            const content = page.getByTestId('tb-panel-fields-content');
            await expect(content.getByTestId('tb-field-logo_image')).toBeVisible();
            for (const moved of ['bg_color', 'sticky', 'cta_glow_color']) {
                await expect(content.getByTestId(`tb-field-${moved}`), `${moved} is not a content field`).toHaveCount(0);
            }
            await expect(content.getByTestId('tb-field-search_mode')).toHaveCount(0);
            await editor(page, `(s) => s.ctlSetProp('show_search', true)`);
            await expect(content.getByTestId('tb-field-search_mode')).toBeVisible();

            // logo height: range
            await content.getByTestId('tbc-range-value-logo_height').fill('50');
            await content.getByTestId('tbc-range-value-logo_height').press('Tab');
            expect(await editor(page, '(s) => s.selectedBlock.props.logo_height')).toBe(50);

            // Style: header colours only, generic sections and their device bar hidden
            await tab(page, 'استایل').click();
            const style = page.getByTestId('tb-panel-fields-style');
            await expect(style.getByTestId('tb-field-group-bg_color')).toHaveText('رنگ‌های هدر');
            await expect(page.getByTestId('tb-style-breakpoints')).toBeHidden();
            await expect(page.locator('div[x-show="selectedTab === \'style\'"] button', { hasText: /^\s*تایپوگرافی\s*$/ })).toBeHidden();
            await editor(page, `(s) => { s.selectedBlock.style = { bgColor: '#000000', customClass: 'keep-me' }; }`);
            await expect(page.getByTestId('tb-hidden-generic-style')).toBeVisible();
            await page.getByTestId('tb-clear-hidden-generic-style').click();
            await expect(page.getByTestId('tb-hidden-generic-style')).toHaveCount(0);
            expect(await editor(page, '(s) => JSON.parse(JSON.stringify(s.selectedBlock.style))')).toEqual({ customClass: 'keep-me' });
            await expect.poll(() => editor(page, '(s) => !s.saving && !s.quickSaving'), { timeout: 15_000 }).toBe(true);

            // Advanced: behaviour with its condition
            await tab(page, 'پیشرفته').click();
            const advanced = page.getByTestId('tb-panel-fields-advanced');
            await expect(advanced.getByTestId('tb-field-sticky')).toBeVisible();
            await expect(advanced.getByTestId('tb-field-sticky_hide_on_scroll')).toHaveCount(0);
            await editor(page, `(s) => s.ctlSetProp('sticky', true)`);
            await expect(advanced.getByTestId('tb-field-sticky_hide_on_scroll')).toBeVisible();

            // Shared controls on test-only schema fields (client side only)
            await page.evaluate(() => {
                const schema = window.eval('BLOCK_SCHEMAS').nav_header.schema;
                Object.assign(schema, {
                    t_pad: { type: 'spacing', label: 'فاصلهٔ داخلی (تست)', panel: 'style', group: 'تست', responsive: true },
                    t_color: { type: 'color', label: 'رنگ منو', default: '', panel: 'style', group: 'تست', state_group: 't_menu', state: 'normal' },
                    t_color_hover: { type: 'color', label: 'رنگ منو', default: '', panel: 'style', group: 'تست', state_group: 't_menu', state: 'hover' },
                    t_shadow: { type: 'shadow', label: 'سایه (تست)', panel: 'style', group: 'تست' },
                });
            });
            await tab(page, 'استایل').click();
            await style.getByTestId('tbc-spacing-t_pad-block-start').fill('10');
            await style.getByTestId('tbc-spacing-t_pad-block-start').press('Tab');
            expect(await editor(page, '(s) => s.selectedBlock.props.t_pad["inline-end"]')).toBe(10);
            await style.getByTestId('tbc-device-t_pad-tablet').click();
            expect(await editor(page, '(s) => [s.activeBreakpoint, s.device]')).toEqual(['tablet', 'tablet']);
            await style.getByTestId('tbc-spacing-t_pad-block-start').fill('4');
            await style.getByTestId('tbc-spacing-t_pad-block-start').press('Tab');
            expect(await editor(page, '(s) => [s.selectedBlock.props.t_pad["block-start"], s.selectedBlock.props.t_pad_tablet["block-start"]]')).toEqual([10, 4]);
            await style.getByTestId('tbc-device-t_pad-desktop').click();

            await style.getByTestId('tbc-state-t_menu-hover').click();
            await expect(style.getByTestId('tb-field-t_color_hover')).toBeVisible();
            await expect(style.getByTestId('tb-field-t_color')).toHaveCount(0);

            await style.getByTestId('tbc-shadow-t_shadow-lg').click();
            expect(await editor(page, '(s) => s.selectedBlock.props.t_shadow.blur')).toBe(28);
            await page.getByTestId('tb-inspector-scroll').screenshot({ path: 'tests/browser/.runtime/adr082-customer-style.png' });
        } finally {
            await editor(page, `(s) => { s.setDevice('desktop'); s.selectBlock(s.findBlockPath('${id}')); s.removeSelected(); }`);
            await expect.poll(() => editor(page, `(s) => !s.findBlockPath('${id}')`)).toBe(true);
            await editor(page, '(s) => s.saveCurrentPage()');
            await expect.poll(() => editor(page, '(s) => !s.saving && !s.quickSaving && !s.isDirty'), { timeout: 15_000 }).toBe(true);
        }
        expect(realErrorsOnly(errors), realErrorsOnly(errors).join('\n')).toHaveLength(0);
    });

    test('step 3: the header Style tab reaches the live preview through the header own values', async ({ page }) => {
        test.setTimeout(180_000);
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
        try {
            await tab(page, 'استایل').click();
            const style = page.getByTestId('tb-panel-fields-style');
            await expect(style.getByTestId('tb-field-group-min_height')).toHaveText('کانتینر هدر');
            await style.getByTestId('tbc-range-value-min_height').fill('94');
            await style.getByTestId('tbc-range-value-min_height').press('Tab');
            expect(await editor(page, '(s) => s.selectedBlock.props.min_height')).toBe(94);
            const frame = page.frameLocator('#tb-live-iframe');
            await expect.poll(() => frame.locator(`#tb-${id} .tb-nav-header-inner`).evaluate((el) => getComputedStyle(el).minHeight), { timeout: 20_000 }).toBe('94px');
            await expect(frame.locator(`style[data-tb-hdr="tb-${id}"]`)).toHaveCount(1);
            await style.getByTestId('tb-field-pointer').locator('select').selectOption('double');
            await expect.poll(() => frame.locator(`#tb-${id}`).getAttribute('class'), { timeout: 20_000 }).toContain('tb-nav-header--pointer-double');
            await expect(frame.locator(`style[data-tb-hdr="tb-${id}"]`), 'one style after another live swap').toHaveCount(1);
        } finally {
            await editor(page, `(s) => { s.setDevice('desktop'); s.selectBlock(s.findBlockPath('${id}')); s.removeSelected(); }`);
            await expect.poll(() => editor(page, `(s) => !s.findBlockPath('${id}')`)).toBe(true);
            await editor(page, '(s) => s.saveCurrentPage()');
            await expect.poll(() => editor(page, '(s) => !s.saving && !s.quickSaving && !s.isDirty'), { timeout: 15_000 }).toBe(true);
        }
        expect(realErrorsOnly(errors), realErrorsOnly(errors).join('\n')).toHaveLength(0);
    });

    test('step 4: the header Advanced tab shows sticky and drawer options only while they apply', async ({ page }) => {
        test.setTimeout(180_000);
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
        try {
            await tab(page, 'پیشرفته').click();
            const advanced = page.getByTestId('tb-panel-fields-advanced');
            await expect(advanced.getByTestId('tb-field-scroll_threshold'), 'needs sticky').toHaveCount(0);
            await advanced.getByTestId('tb-field-sticky').locator('label').last().click();
            for (const key of ['sticky_devices', 'sticky_offset', 'scroll_threshold', 'sticky_shrink', 'sticky_shrink_height', 'scrolled_bg_color', 'scrolled_shadow']) {
                await expect(advanced.getByTestId(`tb-field-${key}`), key).toBeVisible();
            }
            // A toggle shows its schema default (shrink is on until switched off).
            const shrink = advanced.getByTestId('tb-field-sticky_shrink');
            await expect(shrink.getByTestId('tb-prop-sticky_shrink')).toBeChecked();
            await expect(shrink.locator('label').last().locator('span')).toHaveText('فعال');
            await shrink.locator('label').last().click();
            await expect(advanced.getByTestId('tb-field-sticky_shrink_height'), 'no shrink: no shrink height').toHaveCount(0);

            // A new header's selects show their default, and the drawer options follow the breakpoint.
            const burger = advanced.getByTestId('tb-field-burger_breakpoint').locator('select');
            await expect(burger).toHaveValue('mobile');
            await expect(advanced.getByTestId('tb-field-drawer_side')).toBeVisible();
            await advanced.getByTestId('tb-segmented-drawer_style-full').click();
            await expect(advanced.getByTestId('tb-field-drawer_side'), 'a full-screen menu has no side').toHaveCount(0);
            await burger.selectOption('never');
            await expect(advanced.getByTestId('tb-field-drawer_style'), 'never: no drawer').toHaveCount(0);
            expect(await editor(page, '(s) => [s.selectedBlock.props.sticky, s.selectedBlock.props.sticky_shrink, s.selectedBlock.props.drawer_style, s.selectedBlock.props.burger_breakpoint]'))
                .toEqual([true, false, 'full', 'never']);
            const frame = page.frameLocator('#tb-live-iframe');
            await expect.poll(() => frame.locator(`#tb-${id}`).getAttribute('data-tb-burger'), { timeout: 20_000 }).toBe('never');
        } finally {
            await editor(page, `(s) => { s.setDevice('desktop'); s.selectBlock(s.findBlockPath('${id}')); s.removeSelected(); }`);
            await expect.poll(() => editor(page, `(s) => !s.findBlockPath('${id}')`)).toBe(true);
            await editor(page, '(s) => s.saveCurrentPage()');
            await expect.poll(() => editor(page, '(s) => !s.saving && !s.quickSaving && !s.isDirty'), { timeout: 15_000 }).toBe(true);
        }
        expect(realErrorsOnly(errors), realErrorsOnly(errors).join('\n')).toHaveLength(0);
    });
});
