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
 * Phase 0.5 (saas-platform COMMERCIAL_WIDGET_ROADMAP.md, ADR-064): this
 * host's Canvas colour control shares the platform's value model
 * (theme-runtime CanvasColor): theme swatches stored as var(--tb-…, fallback),
 * opacity as color-mix()/rgba(), explicit transparent. The live page applies
 * them, and shared renderers print counters in Persian.
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

test.describe('Phase 0.5 host colour control', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test('swatches, opacity and transparent author, survive reload and render publicly', async ({ page }) => {
        const errors = collectConsoleErrors(page);
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'home');
        const before = (await homeBlocks(page)).map((b) => b.id);

        await addFromPalette(page, 'counter', 'content');
        await propField(page, 'value').fill('5000');
        await propField(page, 'value').dispatchEvent('input');

        await addFromPalette(page, 'cta', 'marketing');
        await propField(page, 'title').fill('پیشنهاد ویژه');
        const value = propField(page, 'background');
        const swatch = page.getByTestId('tb-theme-color-background-primary');
        await swatch.click();
        await expect(value).toHaveValue('var(--tb-primary, #4f46e5)');
        await expect(swatch).toHaveAttribute('aria-pressed', 'true');
        await page.getByTestId('tb-color-alpha-background').fill('40');
        await expect(value).toHaveValue('color-mix(in srgb, var(--tb-primary, #4f46e5) 40%, transparent)');
        await page.getByTestId('tb-color-transparent-background').click();
        await expect(value).toHaveValue('transparent');
        await expect(page.getByTestId('tb-color-alpha-background')).toBeDisabled();
        await page.getByTestId('tb-color-background').locator('input[type="color"]').fill('#0ea5e9');
        await expect(value).toHaveValue('#0ea5e9');
        await page.getByTestId('tb-color-alpha-background').fill('50');
        await expect(value).toHaveValue('rgba(14, 165, 233, 0.5)');

        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();
        await reloadCanvasEditor(page);
        await switchToPage(page, 'home');
        const added = (await homeBlocks(page)).filter((b) => !before.includes(b.id));
        const byType = Object.fromEntries(added.map((b) => [b.type, b]));
        expect(byType.cta.props.background).toBe('rgba(14, 165, 233, 0.5)');

        await waitForPublish(page, () => publishButton(page).click());
        await publicPage(page, null);
        const cta = page.locator(`#tb-${byType.cta.id}`);
        await expect(cta).toHaveCSS('background-color', 'rgba(14, 165, 233, 0.5)');
        await expect(page.locator(`#tb-${byType.counter.id} .tb-stat-value`)).toContainText('۵٬۰۰۰');
        for (const [name, viewport] of Object.entries(VIEWPORTS)) {
            await page.setViewportSize(viewport);
            await cta.scrollIntoViewIfNeeded();
            const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
            expect(overflow, `no horizontal overflow at ${name}`).toBeLessThanOrEqual(0);
            await cta.screenshot({ path: `tests/browser/.runtime/phase05-cta-${name}.png` });
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
