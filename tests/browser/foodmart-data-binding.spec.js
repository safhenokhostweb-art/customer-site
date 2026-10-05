import { test, expect } from '@playwright/test';
import { mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { BASE_URL, loadFixture } from './fixtures/env.js';
import { loginAsAdmin } from './helpers/auth.js';
import { openCanvasEditor, openTreeTab, switchToPage, treeNode, canvasIframe } from './helpers/canvas.js';

const here = dirname(fileURLToPath(import.meta.url));
const repoRoot = join(here, '..', '..');
const foodMartAssets = join(repoRoot, '..', 'saas-platform', 'public', 'theme-assets', 'foodmart', '1.0.0');
const evidenceDir = join(here, '.runtime', 'foodmart-phase4b');

async function installFoodMartRuntime(pageOrFrame) {
    await pageOrFrame.addStyleTag({ path: join(foodMartAssets, 'theme-assets', 'swiper-bundle.min.css') });
    await pageOrFrame.addStyleTag({ path: join(foodMartAssets, 'theme-assets', 'bootstrap-5.3.0-alpha3.min.css') });
    await pageOrFrame.addStyleTag({ path: join(foodMartAssets, 'css', 'vendor.css') });
    await pageOrFrame.addStyleTag({ path: join(foodMartAssets, 'style.css') });
    await pageOrFrame.addScriptTag({ path: join(foodMartAssets, 'js', 'jquery-1.11.0.min.js') });
    await pageOrFrame.addScriptTag({ path: join(foodMartAssets, 'theme-assets', 'swiper-bundle.min.js') });
    await pageOrFrame.addScriptTag({ path: join(foodMartAssets, 'theme-assets', 'bootstrap.bundle.min.js') });
    await pageOrFrame.addScriptTag({ path: join(foodMartAssets, 'js', 'plugins.js') });
    await pageOrFrame.addScriptTag({ path: join(foodMartAssets, 'js', 'script.js') });
}

test.describe('FoodMart Phase 4B real data binding', () => {
    test.beforeAll(() => mkdirSync(evidenceDir, { recursive: true }));

    test('public and Canvas render controlled tenant records with real interactions', async ({ page }) => {
        const fixture = loadFixture();
        await loginAsAdmin(page);

        await page.goto(`${BASE_URL}/page/${fixture.foodmart_page_slug}`, { waitUntil: 'domcontentloaded' });
        await expect(page.getByText('Browser Sale Apple').first()).toBeVisible();
        await expect(page.getByText('Browser Bakery Bread').first()).toBeVisible();
        await expect(page.getByText('Browser Hidden Draft')).toHaveCount(0);
        await expect(page.locator('.category-item')).toHaveCount(3);
        await expect(page.locator('.product-item').first().locator('img')).toHaveJSProperty('complete', true);
        expect(await page.locator('.product-item img').first().evaluate((img) => img.naturalWidth)).toBeGreaterThan(0);

        await page.setViewportSize({ width: 390, height: 844 });
        await installFoodMartRuntime(page);
        await expect(page.locator('.category-carousel')).toHaveClass(/swiper-initialized/);
        await expect(page.locator('.products-carousel')).toHaveClass(/swiper-initialized/);
        const before = await page.locator('.category-carousel .swiper-wrapper').getAttribute('style');
        await page.locator('.category-carousel-next').click();
        await page.waitForTimeout(600);
        const after = await page.locator('.category-carousel .swiper-wrapper').getAttribute('style');
        expect(after).not.toBe(before);

        const saleForm = page.locator(`form.foodmart-add-to-cart:has(input[name="product_id"][value="${fixture.foodmart_product_id}"])`).first();
        const cartResponse = page.waitForResponse((response) => response.url().endsWith('/shop/cart/add') && response.request().method() === 'POST');
        await saleForm.locator('button[type="submit"]').click();
        expect((await cartResponse).status()).toBeLessThan(400);

        await page.setViewportSize({ width: 1440, height: 1000 });
        await page.goto(`${BASE_URL}/page/${fixture.foodmart_page_slug}`, { waitUntil: 'domcontentloaded' });
        await installFoodMartRuntime(page);
        await page.screenshot({ path: join(evidenceDir, 'public-desktop.png'), fullPage: true });
        await page.setViewportSize({ width: 390, height: 844 });
        await page.reload({ waitUntil: 'domcontentloaded' });
        await installFoodMartRuntime(page);
        await expect(page.getByText('Browser Product With An Intentionally Long Name For Responsive Verification').first()).toBeVisible();
        await page.screenshot({ path: join(evidenceDir, 'public-mobile.png'), fullPage: true });

        await page.setViewportSize({ width: 1440, height: 1000 });
        await openCanvasEditor(page);
        await switchToPage(page, 'foodmart-binding');
        await openTreeTab(page);
        await treeNode(page, 'fm-binding-products').click();
        await expect(page.locator('[data-testid="tb-data-binding"]')).toBeVisible();
        await expect(page.locator('[data-testid="tb-binding-enabled"]')).toBeChecked();
        await expect(page.locator('[data-testid="tb-binding-sort"]')).toHaveValue('most_viewed');
        await expect(canvasIframe(page).getByText('Browser Sale Apple').first()).toBeVisible();
        await expect(canvasIframe(page).getByText('Browser Hidden Draft')).toHaveCount(0);
        const previewFrame = page.frames().find((frame) => frame.url().includes('/theme-design/canvas/live-preview'));
        expect(previewFrame).toBeTruthy();
        await installFoodMartRuntime(previewFrame);
        await expect(canvasIframe(page).locator('.category-carousel')).toHaveClass(/swiper-initialized/);
        await page.screenshot({ path: join(evidenceDir, 'canvas-desktop.png'), fullPage: false });
    });
});
