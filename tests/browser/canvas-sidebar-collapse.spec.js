import { expect, test } from '@playwright/test';
import { BASE_URL } from './fixtures/env.js';
import { VIEWPORTS } from './fixtures/viewports.js';
import { loginAsAdmin } from './helpers/auth.js';

/**
 * Canvas editor sidebar collapse: floating edge handles, keyboard toggles,
 * last-width restoration and open/closed persistence across a reload.
 */

const RIGHT_PANEL = '[data-testid="tb-right-sidebar-panel"]';
const LEFT_PANEL = '[data-testid="tb-left-sidebar-panel"]';
const RIGHT_HANDLE = '[data-testid="tb-collapse-right-sidebar"]';
const LEFT_HANDLE = '[data-testid="tb-collapse-left-sidebar"]';
const RESIZERS = {
    right: '[data-testid="tb-resize-right-sidebar"]',
    left: '[data-testid="tb-resize-left-sidebar"]',
};

const widthOf = (page, selector) => page.locator(selector).evaluate((el) => el.getBoundingClientRect().width);

async function resetSidebarStorage(page) {
    await page.evaluate(() => {
        Object.keys(localStorage)
            .filter((key) => key.startsWith('tb-canvas-') && key.includes('sidebar'))
            .forEach((key) => localStorage.removeItem(key));
    });
    await page.reload({ waitUntil: 'domcontentloaded' });
    await expect(page.locator('[data-testid="tb-canvas-save-button"]')).toBeVisible({ timeout: 15_000 });
}

test.describe('Canvas editor — collapsible sidebars', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test.beforeEach(async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto(`${BASE_URL}/admin/theme-design/canvas/edit`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('[data-testid="tb-canvas-save-button"]')).toBeVisible({ timeout: 15_000 });
        await resetSidebarStorage(page);
    });

    test('floating handles collapse each sidebar, grow the canvas, and persist across reload', async ({ page }) => {
        const rightBefore = await widthOf(page, RIGHT_PANEL);
        const leftBefore = await widthOf(page, LEFT_PANEL);
        const canvasBefore = await widthOf(page, '[data-testid="tb-canvas-panel"]');
        expect(rightBefore).toBeGreaterThan(300);
        expect(leftBefore).toBeGreaterThan(300);

        await page.locator(RIGHT_HANDLE).click();
        await expect.poll(() => widthOf(page, RIGHT_PANEL)).toBeLessThan(5);
        await expect.poll(() => widthOf(page, '[data-testid="tb-canvas-panel"]')).toBeGreaterThan(canvasBefore);
        expect(await page.evaluate(() => localStorage.getItem('tb-canvas-right-sidebar-open'))).toBe('0');
        expect(await widthOf(page, LEFT_PANEL)).toBeGreaterThan(300);

        await page.locator(LEFT_HANDLE).click();
        await expect.poll(() => widthOf(page, LEFT_PANEL)).toBeLessThan(5);
        expect(await page.evaluate(() => localStorage.getItem('tb-canvas-left-sidebar-open'))).toBe('0');

        // Reopening restores the exact width the panel had before collapsing.
        await page.locator(RIGHT_HANDLE).click();
        await expect.poll(async () => Math.abs(await widthOf(page, RIGHT_PANEL) - rightBefore)).toBeLessThan(2);
        expect(await page.evaluate(() => localStorage.getItem('tb-canvas-right-sidebar-open'))).toBe('1');

        // Closed state survives a full reload.
        await page.locator(LEFT_HANDLE).click();
        await expect.poll(() => widthOf(page, LEFT_PANEL)).toBeGreaterThan(300);
        await page.locator(LEFT_HANDLE).click();
        await expect.poll(() => widthOf(page, LEFT_PANEL)).toBeLessThan(5);
        await page.reload({ waitUntil: 'domcontentloaded' });
        await expect(page.locator('[data-testid="tb-canvas-save-button"]')).toBeVisible({ timeout: 15_000 });
        await expect.poll(() => widthOf(page, LEFT_PANEL)).toBeLessThan(5);
        await expect.poll(async () => Math.abs(await widthOf(page, RIGHT_PANEL) - rightBefore)).toBeLessThan(2);
    });

    test('keyboard: Ctrl+B toggles the block library, Ctrl+Alt+B the properties panel, F both', async ({ page }) => {
        await page.evaluate(() => {
            if (document.activeElement instanceof HTMLElement) document.activeElement.blur();
        });

        await page.keyboard.press('Control+b');
        await expect.poll(() => widthOf(page, RIGHT_PANEL)).toBeLessThan(5);
        expect(await widthOf(page, LEFT_PANEL)).toBeGreaterThan(300);

        await page.keyboard.press('Control+b');
        await expect.poll(() => widthOf(page, RIGHT_PANEL)).toBeGreaterThan(300);

        await page.keyboard.press('Control+Alt+b');
        await expect.poll(() => widthOf(page, LEFT_PANEL)).toBeLessThan(5);
        expect(await widthOf(page, RIGHT_PANEL)).toBeGreaterThan(300);

        await page.keyboard.press('Control+Alt+b');
        await expect.poll(() => widthOf(page, LEFT_PANEL)).toBeGreaterThan(300);

        await page.keyboard.press('f');
        await expect.poll(() => widthOf(page, RIGHT_PANEL)).toBeLessThan(5);
        await expect.poll(() => widthOf(page, LEFT_PANEL)).toBeLessThan(5);
        expect(await page.evaluate(() => localStorage.getItem('tb-canvas-right-sidebar-open'))).toBe('0');
        expect(await page.evaluate(() => localStorage.getItem('tb-canvas-left-sidebar-open'))).toBe('0');

        await page.keyboard.press('f');
        await expect.poll(() => widthOf(page, RIGHT_PANEL)).toBeGreaterThan(300);
        await expect.poll(() => widthOf(page, LEFT_PANEL)).toBeGreaterThan(300);
    });

    test('a resized width is preserved through collapse and reopen', async ({ page }) => {
        const before = await widthOf(page, RIGHT_PANEL);
        const resizer = page.locator(RESIZERS.right);
        const box = await resizer.boundingBox();
        // RTL: dragging the splitter towards the canvas (leftwards) widens
        // the block library.
        await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2);
        await page.mouse.down();
        await page.mouse.move(box.x + box.width / 2 - 70, box.y + box.height / 2, { steps: 5 });
        await page.mouse.up();

        const resized = await widthOf(page, RIGHT_PANEL);
        expect(resized).toBeGreaterThan(before + 40);

        await page.locator(RIGHT_HANDLE).click();
        await expect.poll(() => widthOf(page, RIGHT_PANEL)).toBeLessThan(5);
        await page.locator(RIGHT_HANDLE).click();
        await expect.poll(async () => Math.abs(await widthOf(page, RIGHT_PANEL) - resized)).toBeLessThan(2);
    });
});
