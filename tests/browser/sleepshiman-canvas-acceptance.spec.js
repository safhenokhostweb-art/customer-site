import { test, expect } from '@playwright/test';
import { BASE_URL, loadFixture } from './fixtures/env.js';
import { loginAsAdmin } from './helpers/auth.js';
import {
    canvasIframe,
    openCanvasEditor,
    openTreeTab,
    publishButton,
    saveButton,
    switchToPage,
    treeNode,
    waitForPublish,
    waitForSaveBlocks,
} from './helpers/canvas.js';

test('SleepShiman repeater edit survives save, reload, publish, and public render', async ({ page }) => {
    const fixture = loadFixture();
    const editedValue = '۹۹۹۹';

    await loginAsAdmin(page);
    await openCanvasEditor(page);
    await switchToPage(page, 'sleepshiman-acceptance');
    await openTreeTab(page);
    await treeNode(page, 'sleepshiman-stats-acceptance').click();

    const valueField = page.locator('[data-testid="tb-structured-field-items-0-value"]');
    await expect(valueField).toBeVisible();
    await valueField.fill(editedValue);
    await expect(canvasIframe(page).getByText(editedValue).first()).toBeVisible();

    expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();
    await page.reload({ waitUntil: 'domcontentloaded' });
    await switchToPage(page, 'sleepshiman-acceptance');
    await openTreeTab(page);
    await treeNode(page, 'sleepshiman-stats-acceptance').click();
    await expect(page.locator('[data-testid="tb-structured-field-items-0-value"]')).toHaveValue(editedValue);

    expect((await waitForPublish(page, () => publishButton(page).click())).ok()).toBeTruthy();
    await page.waitForLoadState('networkidle');
    try {
        await page.goto(`${BASE_URL}/page/${fixture.sleepshiman_page_slug}`, { waitUntil: 'domcontentloaded' });
    } catch {
        await page.goto(`${BASE_URL}/page/${fixture.sleepshiman_page_slug}`, { waitUntil: 'domcontentloaded' });
    }
    await expect(page.getByText(editedValue).first()).toBeVisible();
    expect(await page.locator('[data-tb-logical-group="items"]').count()).toBeGreaterThan(0);
    expect(await page.locator('body').evaluate((body) => body.scrollWidth <= body.clientWidth + 1)).toBeTruthy();
});
