import { expect, test } from '@playwright/test';
import { VIEWPORTS } from './fixtures/viewports.js';
import { loginAsAdmin } from './helpers/auth.js';
import {
    openCanvasEditor,
    paletteAddButton,
    propField,
    publicPage,
    publishButton,
    reloadCanvasEditor,
    saveButton,
    switchToPage,
    waitForPublish,
    waitForSaveBlocks,
} from './helpers/canvas.js';

/**
 * Phase 0.2c (saas-platform COMMERCIAL_WIDGET_ROADMAP.md, ADR-062): this
 * host's Canvas now authors the canonical block definitions directly. The
 * controls those definitions bring must work here:
 *
 *   - section.padding: a spacing-scale select that shows the stored value
 *     after a reload (the select used to show its first option);
 *   - accordion.items: the guarded JSON list editor (an invalid draft is
 *     never written);
 *   - video.url: a media field with no <img> preview;
 *   - cta.background: a colour the public render applies.
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

const homeBlocks = (page) => page.evaluate(() => window.Alpine.$data(document.querySelector('[x-data]')).pages['home'].blocks);

/** Palette categories start collapsed; open the block's one first when needed. */
async function addFromPalette(page, type, category) {
    const button = paletteAddButton(page, type);
    if (!(await button.isVisible())) await page.getByTestId(`tb-palette-category-${category}`).click();
    return waitForSaveBlocks(page, () => button.click());
}

test.describe('Phase 0.2c host Canvas controls', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test('harmonized controls author, survive reload and render publicly', async ({ page }) => {
        const errors = collectConsoleErrors(page);
        // The video file itself is not under test — only its authoring and markup.
        await page.route('**/storage/demo/intro.mp4', (route) => route.fulfill({ status: 200, contentType: 'video/mp4', body: '' }));
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'home');
        const before = (await homeBlocks(page)).map((b) => b.id);

        // Each add auto-saves; wait for it so a stale save cannot land after ours.
        await addFromPalette(page, 'cta', 'marketing');
        await propField(page, 'title').fill('پیشنهاد ویژه این هفته');
        await propField(page, 'background').fill('#0ea5e9');

        await addFromPalette(page, 'video', 'content');
        await page.getByTestId('tb-media-value-url').fill('/storage/demo/intro.mp4');
        await expect(page.getByTestId('tb-media-preview-url')).toHaveCount(0);

        await addFromPalette(page, 'accordion', 'layout');
        const items = propField(page, 'items');
        await items.fill('[{"title": "ارسال"');
        await expect(page.getByText('JSON نامعتبر است؛ تا اصلاح نشود ذخیره نمی‌شود.')).toBeVisible();
        await items.fill('[{"title": "زمان ارسال سفارش چقدر است؟", "blocks": []}]');
        await expect(page.getByText('ویرایش پیشرفته (فهرست JSON)')).toBeVisible();

        await addFromPalette(page, 'section', 'layout');
        const padding = propField(page, 'padding');
        await expect(padding.locator('option')).toHaveText(['بدون فاصله', 'خیلی کم', 'کم', 'متوسط', 'زیاد', 'خیلی زیاد']);
        await padding.selectOption('lg');
        // The child list is authored in the children panel, never as a field.
        await expect(propField(page, 'blocks')).toHaveCount(0);

        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();

        await reloadCanvasEditor(page);
        await switchToPage(page, 'home');
        const added = (await homeBlocks(page)).filter((b) => !before.includes(b.id));
        const byType = Object.fromEntries(added.map((b) => [b.type, b]));
        expect(Object.keys(byType).sort()).toEqual(['accordion', 'cta', 'section', 'video']);
        expect(byType.section.props.padding).toBe('lg');
        expect(byType.cta.props.background).toBe('#0ea5e9');
        expect(byType.video.props.url).toBe('/storage/demo/intro.mp4');
        expect(byType.accordion.props.items).toEqual([{ title: 'زمان ارسال سفارش چقدر است؟', blocks: [] }]);

        // The reloaded select shows the stored value, not its first option.
        await page.evaluate((id) => {
            const d = window.Alpine.$data(document.querySelector('[x-data]'));
            d.selectBlock(d.pages['home'].blocks.findIndex((b) => b.id === id));
        }, byType.section.id);
        await expect(propField(page, 'padding')).toHaveValue('lg');

        await waitForPublish(page, () => publishButton(page).click());
        await publicPage(page, null);
        await expect(page.locator(`#tb-${byType.section.id}`)).toHaveClass(/tb-padding-lg/);
        const cta = page.locator(`#tb-${byType.cta.id}`);
        await expect(cta).toHaveCSS('background-color', 'rgb(14, 165, 233)');
        await expect(page.locator(`#tb-${byType.video.id} video`)).toHaveAttribute('src', '/storage/demo/intro.mp4');
        await expect(page.locator('summary', { hasText: 'زمان ارسال سفارش چقدر است؟' })).toBeVisible();
        for (const [name, viewport] of Object.entries(VIEWPORTS)) {
            await page.setViewportSize(viewport);
            await cta.scrollIntoViewIfNeeded();
            const box = await cta.boundingBox();
            expect(box && box.width <= viewport.width, `CTA fits the ${name} viewport`).toBe(true);
            await cta.screenshot({ path: `tests/browser/.runtime/phase02c-cta-${name}.png` });
        }

        // Leave the shared fixture page as found for the specs that count its blocks.
        await page.setViewportSize(VIEWPORTS.desktop);
        await openCanvasEditor(page);
        await switchToPage(page, 'home');
        await page.evaluate((ids) => {
            const d = window.Alpine.$data(document.querySelector('[x-data]'));
            for (const id of ids) {
                const idx = d.pages['home'].blocks.findIndex((b) => b.id === id);
                if (idx < 0) continue;
                d.selectBlock(idx);
                d.removeSelected();
            }
        }, added.map((b) => b.id));
        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();
        await waitForPublish(page, () => publishButton(page).click());
        expect((await homeBlocks(page)).map((b) => b.id)).toEqual(before);

        expect(realErrorsOnly(errors)).toHaveLength(0);
    });
});
