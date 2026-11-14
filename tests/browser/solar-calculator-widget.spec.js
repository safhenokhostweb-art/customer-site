import { expect, test } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { VIEWPORTS } from './fixtures/viewports.js';
import { BASE_URL, serverEnv } from './fixtures/env.js';
import { loginAsAdmin } from './helpers/auth.js';
import {
    canvasIframe, openCanvasEditor, openTreeTab, paletteAddButton, publishButton,
    reloadCanvasEditor, saveButton, switchToPage, waitForPublish, waitForSaveBlocks,
} from './helpers/canvas.js';
import { restartServer } from './helpers/server.js';

/**
 * saas-platform ADR-085 — the site-specific solar calculator widget:
 *  1. it is offered in the Canvas palette only while the module is enabled
 *     (the harness is the owner site, so MODULE_SOLAR_CALCULATOR_ENABLED
 *     stands in for the license key), its inspector edits persist, and the
 *     editor preview marks quote submission as disabled;
 *  2. on the published page the four steps work for real: calculate, see
 *     sizing and price, submit contact details, open the quote — at
 *     desktop, tablet and mobile widths, without horizontal overflow or
 *     console errors.
 * The server is restarted without the flag afterwards, so other specs keep
 * the baseline palette (builder-final-gap-closure counts it).
 */

const ENABLED = { MODULE_SOLAR_CALCULATOR_ENABLED: 'true' };
const TYPE = 'module:solar_calculator';
const TITLE = 'ماشین‌حساب آزمایشی نیروگاه';

function tinker(code) {
    execFileSync('php', ['artisan', 'tinker', '--execute', code], { cwd: process.cwd(), env: { ...serverEnv(), ...ENABLED }, encoding: 'utf8' });
}

function collectErrors(page) {
    const errors = [];
    page.on('console', (msg) => { if (msg.type() === 'error') errors.push(`${msg.text()} (${msg.location().url})`); });
    page.on('pageerror', (err) => errors.push(String(err)));
    page.on('dialog', (dialog) => dialog.accept());
    return () => errors.filter((e) =>
        !/favicon|ERR_INTERNET_DISCONNECTED/i.test(e)
        && !/(notification-center|system-alerts)\/unread-count|user-tickets\/check-new/i.test(e));
}

async function noHorizontalOverflow(page) {
    const { scroll, width } = await page.evaluate(() => ({ scroll: document.documentElement.scrollWidth, width: window.innerWidth }));
    expect(scroll, 'page must not scroll horizontally').toBeLessThanOrEqual(width + 1);
}

async function runToResults(page) {
    const calc = page.locator('[data-sc-calc]').first();
    await expect(calc).toBeVisible({ timeout: 15_000 });
    await expect(calc.locator('.sc-title')).toHaveText(TITLE);
    await calc.locator('label.sc-type', { hasText: 'هیبرید' }).click();
    await calc.locator('[data-step="1"] [data-sc-next]').click();
    await expect(calc.locator('[data-step="2"]')).toBeVisible();
    await expect(calc.locator('input[name="backup_hours"]')).toBeVisible();
    await calc.locator('input[name="monthly_kwh"]').fill('۴۵۰');
    await calc.locator('select[name="region"]').selectOption('tehran');
    const response = page.waitForResponse((r) => r.url().includes('/solar-calculator/calculate'));
    await calc.locator('[data-sc-calculate]').click();
    expect((await response).status()).toBe(200);
    await expect(calc.locator('[data-step="3"]')).toBeVisible();
    await expect(calc.locator('.sc-card')).toHaveCount(6); // hybrid shows the battery bank
    await expect(calc.locator('[data-sc-price-value]')).toContainText('تومان');
    return calc;
}

test.describe('Solar calculator widget (ADR-085)', () => {
    test.describe.configure({ mode: 'serial' });

    test.beforeAll(async () => {
        await restartServer(ENABLED);
        tinker("App\\Models\\CurrencyExchangeRate::query()->where('code', 'USD')->update(['rate_toman' => 100000, 'is_active' => true]);");
    });

    test.afterAll(async () => {
        await restartServer();
    });

    test('palette → inspector → save → reload → preview → publish', async ({ page }) => {
        test.setTimeout(120_000);
        await page.setViewportSize(VIEWPORTS.desktop);
        const errors = collectErrors(page);
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'home');

        // The «ماژول‌ها» category is expanded by default (clicking would collapse it).
        await paletteAddButton(page, TYPE).click();
        await openTreeTab(page);
        const node = page.locator(`[data-testid^="tb-tree-node-"][data-block-type="${TYPE}"]`).first();
        await expect(node).toBeVisible({ timeout: 10_000 });
        await node.click();

        await expect(page.locator('[data-testid="tb-field-group-anchor_id"]')).toHaveText('نمایش');
        const title = page.locator('[data-testid="tb-field-title"] input').first();
        await title.fill(TITLE);
        await page.waitForTimeout(600); // inspector debounce
        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();

        await reloadCanvasEditor(page);
        await switchToPage(page, 'home');
        await openTreeTab(page);
        await page.locator(`[data-testid^="tb-tree-node-"][data-block-type="${TYPE}"]`).first().click();
        await expect(page.locator('[data-testid="tb-field-title"] input').first()).toHaveValue(TITLE, { timeout: 10_000 });

        const frame = canvasIframe(page);
        await expect(frame.locator('[data-sc-calc] .sc-title')).toHaveText(TITLE, { timeout: 15_000 });
        await expect(frame.locator('.sc-preview-badge')).toBeVisible();

        expect((await waitForPublish(page, () => publishButton(page).click())).ok()).toBeTruthy();
        expect(errors()).toEqual([]);
    });

    test('public flow: calculate, submit and open the quote (desktop)', async ({ page }) => {
        await page.setViewportSize(VIEWPORTS.desktop);
        const errors = collectErrors(page);
        await page.goto(`${BASE_URL}/`, { waitUntil: 'domcontentloaded' });

        const calc = await runToResults(page);
        await noHorizontalOverflow(page);
        await calc.locator('[data-step="3"] [data-sc-next]').click();
        await calc.locator('input[name="name"]').fill('مریم احمدی');
        await calc.locator('input[name="mobile"]').fill('۰۹۱۲۱۲۳۴۵۶۷');
        const submitted = page.waitForResponse((r) => r.url().includes('/solar-calculator/quote') && r.request().method() === 'POST');
        await calc.locator('[data-sc-submit]').click();
        expect((await submitted).status()).toBe(201);
        await expect(calc.locator('[data-sc-success]')).toBeVisible();
        const quoteNo = (await calc.locator('[data-sc-quote-no]').textContent()).trim();
        expect(quoteNo).toMatch(/^SQ-\d{5}$/);

        const href = await calc.locator('[data-sc-view]').getAttribute('href');
        await page.goto(href, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('.doc dd').first()).toHaveText(quoteNo);
        await expect(page.getByText('بانک باتری لیتیومی')).toBeVisible();
        await expect(page.locator('tfoot tr.total')).toContainText('تومان');
        await page.screenshot({ path: 'tests/browser/.runtime/adr085-solar-quote.png', fullPage: true });
        expect(errors()).toEqual([]);
    });

    for (const width of ['tablet', 'mobile']) {
        test(`public flow reaches results without overflow (${width})`, async ({ page }) => {
            await page.setViewportSize(VIEWPORTS[width]);
            const errors = collectErrors(page);
            await page.goto(`${BASE_URL}/`, { waitUntil: 'domcontentloaded' });
            await runToResults(page);
            await noHorizontalOverflow(page);
            await page.locator('[data-sc-calc]').first().screenshot({ path: `tests/browser/.runtime/adr085-solar-results-${width}.png` });
            expect(errors()).toEqual([]);
        });
    }

    test('cleanup: remove the widget from the shared home fixture', async ({ page }) => {
        await page.setViewportSize(VIEWPORTS.desktop);
        collectErrors(page); // also accepts the delete confirmation dialog
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'home');
        await openTreeTab(page);
        const nodes = page.locator(`[data-testid^="tb-tree-node-"][data-block-type="${TYPE}"]`);
        for (let left = await nodes.count(); left > 0; left -= 1) {
            await nodes.first().click();
            await page.locator('button[title="حذف بلوک"]:visible').first().click();
            await expect(nodes).toHaveCount(left - 1);
        }
        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();
        expect((await waitForPublish(page, () => publishButton(page).click())).ok()).toBeTruthy();
        tinker("App\\Modules\\SolarCalculator\\Models\\SolarCalculatorLead::query()->forceDelete(); App\\Models\\CrmContact::query()->where('mobile', '09121234567')->forceDelete();");
    });
});
