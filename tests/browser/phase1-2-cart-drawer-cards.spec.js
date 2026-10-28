import { expect, test } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { serverEnv } from './fixtures/env.js';
import { VIEWPORTS } from './fixtures/viewports.js';
import { loginAsAdmin } from './helpers/auth.js';
import {
    openCanvasEditor,
    paletteAddButton,
    publicPage,
    publishButton,
    saveButton,
    switchToPage,
    waitForPublish,
    waitForSaveBlocks,
} from './helpers/canvas.js';

/**
 * Phases 1/2 (saas-platform COMMERCIAL_WIDGET_ROADMAP.md, ADR-067) on this
 * host's real routes: the shared product card's instant add-to-cart posts
 * to shop.cart.add, the header cart badge updates and the mini-cart drawer
 * (shop.cart.mini) lists the line, which shop.cart.remove deletes again; on
 * phones the host menu shows the header block's own links.
 */

function php(code) {
    return execFileSync('php', ['-r', `require 'vendor/autoload.php'; $app = require 'bootstrap/app.php'; $app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap(); ${code}`], {
        cwd: process.cwd(), env: serverEnv(), encoding: 'utf8',
    }).trim();
}

const homeBlocks = (page) => page.evaluate(() => window.Alpine.$data(document.querySelector('[x-data]')).pages['home'].blocks);

async function addFromPalette(page, type, category) {
    const button = paletteAddButton(page, type);
    if (!(await button.isVisible())) await page.getByTestId(`tb-palette-category-${category}`).click();
    return waitForSaveBlocks(page, () => button.click());
}

function realErrorsOnly(errors) {
    return errors.filter((e) => !/favicon|ERR_INTERNET_DISCONNECTED|unread-count|check-new|saas-platform\.test/i.test(e));
}

test.describe('Phase 1/2 real cart drawer and product cards', () => {
    test.use({ viewport: VIEWPORTS.desktop });
    let productId;

    test.beforeAll(() => {
        productId = php(`echo App\\Models\\Product::create(['title' => 'کیف آزمایشی فاز ۲', 'slug' => 'phase2-bag-' . time(), 'price' => 1000000, 'sale_price' => 790000, 'stock' => 4, 'low_stock_threshold' => 5, 'status' => App\\Models\\Product::STATUS_PUBLISHED])->id;`);
    });
    test.afterAll(() => {
        if (productId) php(`App\\Models\\Product::whereKey(${Number(productId)})->delete();`);
    });

    test('add to cart from the card, drawer from the header, remove, phone menu', async ({ page }) => {
        test.setTimeout(240_000);
        const errors = [];
        page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text()); });
        page.on('pageerror', (e) => errors.push(String(e)));

        const licensed = php(`echo App\\Helpers\\SiteHelper::shopEnabled() ? 'yes' : 'no';`);
        test.skip(licensed !== 'yes', 'the browser fixture site has no shop module');

        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'home');
        const before = (await homeBlocks(page)).map((b) => b.id);
        await addFromPalette(page, 'product_grid', 'ecommerce');
        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();
        const added = (await homeBlocks(page)).filter((b) => !before.includes(b.id));
        await waitForPublish(page, () => publishButton(page).click());

        await publicPage(page, null);
        const grid = page.locator(`#tb-${added[0].id}`);
        const card = grid.locator('.tb-product-card', { hasText: 'کیف آزمایشی فاز ۲' });
        await card.scrollIntoViewIfNeeded();
        await expect(card.locator('.tb-badge')).toContainText('۲۱٪ تخفیف');
        await expect(card.locator('.tb-product-stock--low')).toContainText('تنها ۴ عدد باقی مانده');
        await card.screenshot({ path: 'tests/browser/.runtime/phase2-card-desktop.png' });

        const [addRes] = await Promise.all([
            page.waitForResponse((r) => r.url().endsWith('/shop/cart/add')),
            card.locator('[data-tb-add-to-cart]').click(),
        ]);
        expect(addRes.status()).toBe(200);
        const drawer = page.locator('.tb-cart-drawer [role="dialog"]');
        await expect(drawer).toBeVisible();
        const line = page.locator('.tb-cart-drawer__item', { hasText: 'کیف آزمایشی فاز ۲' });
        await expect(line).toBeVisible();
        await expect(line.locator('.tb-cart-drawer__price')).toContainText('۷۹۰٬۰۰۰');
        await expect(page.locator('.tb-toast').last()).toHaveText('به سبد اضافه شد.');
        await page.keyboard.press('Escape');
        await expect(drawer).toBeHidden();

        const trigger = page.locator('[data-tb-cart-open]').first();
        if (await trigger.count()) {
            await expect(trigger.locator('[data-tb-cart-count]')).not.toHaveText('۰');
            await trigger.click();
            await expect(drawer).toBeVisible();
            const [rm] = await Promise.all([
                page.waitForResponse((r) => r.url().endsWith('/shop/cart/remove')),
                page.locator('.tb-cart-drawer__item', { hasText: 'کیف آزمایشی فاز ۲' }).locator('[data-tb-cart-remove]').click(),
            ]);
            expect(rm.status()).toBe(200);
            await expect(page.locator('.tb-cart-drawer__item', { hasText: 'کیف آزمایشی فاز ۲' })).toHaveCount(0);
            await page.keyboard.press('Escape');
        }

        for (const [name, viewport] of Object.entries(VIEWPORTS)) {
            await page.setViewportSize(viewport);
            const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
            expect(overflow, `no horizontal overflow at ${name}`).toBeLessThanOrEqual(0);
            await grid.screenshot({ path: `tests/browser/.runtime/phase2-grid-${name}.png` });
        }

        // Phone menu: the block's own links when the header is a nav_header.
        await page.setViewportSize(VIEWPORTS.mobile);
        const menuBtn = page.locator('#tb-mobile-menu-btn');
        if (await menuBtn.isVisible() && await page.locator('.tb-header-nav-wrap .tb-nav-header-links a').count()) {
            await menuBtn.click();
            await expect(page.locator('[data-tb-mobile-nav] a').first()).toBeVisible();
        }

        // Leave the shared fixture page as found.
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
