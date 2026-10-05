import { test, expect } from '@playwright/test';
import { mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { loginAsAdmin } from './helpers/auth.js';
import { openCanvasEditor, openTreeTab, switchToPage, treeNode, canvasIframe, saveButton } from './helpers/canvas.js';

const here = dirname(fileURLToPath(import.meta.url));
const evidenceDir = join(here, '.runtime', 'foodmart-canvas-completion');
const foodMartAssets = join(here, '..', '..', '..', 'saas-platform', 'public', 'theme-assets', 'foodmart', '1.0.0');

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

async function showBlocksTab(page) {
    await page.evaluate(() => {
        const root = document.querySelector('[x-data="canvasEditor()"]');
        if (root?._x_dataStack?.[0]) root._x_dataStack[0].leftPanelTab = 'blocks';
    });
}

async function waitForCanvasReload(page, previousUrl) {
    await expect.poll(() => {
        const current = page.frames().find((candidate) => candidate.url().includes('/theme-design/canvas/live-preview'));
        return current?.url() !== previousUrl && current?.url();
    }).toBeTruthy();
    await expect(canvasIframe(page).locator('.banner-blocks')).toBeVisible();
    return page.frames().find((candidate) => candidate.url().includes('/theme-design/canvas/live-preview'));
}

test.describe('FoodMart Canvas completion gate', () => {
    test.beforeAll(() => mkdirSync(evidenceDir, { recursive: true }));

    test('hero lifecycle, module insertion, persistence, and full preview', async ({ page }) => {
        await loginAsAdmin(page);
        await page.setViewportSize({ width: 1440, height: 1000 });
        await openCanvasEditor(page);
        await switchToPage(page, 'foodmart-binding');
        await openTreeTab(page);

        await expect(canvasIframe(page).locator('body')).toBeVisible();
        await expect.poll(() => page.frames().some((candidate) => candidate.url().includes('/theme-design/canvas/live-preview'))).toBe(true);
        const frame = page.frames().find((candidate) => candidate.url().includes('/theme-design/canvas/live-preview'));
        expect(frame).toBeTruthy();
        await installFoodMartRuntime(frame);

        await treeNode(page, 'fm-hero').click();
        await expect(page.locator('[data-testid="tb-repeater-slides"]')).toBeVisible();
        await canvasIframe(page).locator('.main-swiper .swiper-slide h3').first().click();
        await expect(page.locator('[data-testid="tb-repeater-item-slides-0"]')).toHaveClass(/border-indigo-500/);
        await page.screenshot({ path: join(evidenceDir, '01-hero-meaningful-controls.png') });

        await page.locator('[data-testid="tb-structured-field-slides-0-title"]').fill('Edited existing hero slide');
        await expect(canvasIframe(page).getByText('Edited existing hero slide')).toBeVisible();
        await page.screenshot({ path: join(evidenceDir, '02-hero-existing-slide-edited.png') });

        const beforeAddUrl = frame.url();
        await page.locator('[data-testid="tb-structured-add-slides"]').click();
        await expect(page.locator('[data-testid="tb-repeater-item-slides-1"]')).toBeVisible();
        await installFoodMartRuntime(await waitForCanvasReload(page, beforeAddUrl));
        await expect(canvasIframe(page).locator('.banner-blocks .main-swiper.swiper-initialized .swiper-wrapper > .swiper-slide')).toHaveCount(2);
        await expect(canvasIframe(page).getByText('New FoodMart slide')).toBeVisible();
        await page.locator('[data-testid="tb-structured-field-slides-1-title"]').fill('Second safe hero slide');
        await expect(canvasIframe(page).getByText('Second safe hero slide')).toBeVisible();
        await page.screenshot({ path: join(evidenceDir, '03-hero-added-slide-intact.png') });

        const beforeMoveUrl = page.frames().find((candidate) => candidate.url().includes('/theme-design/canvas/live-preview')).url();
        await page.locator('[data-testid="tb-structured-up-slides-1"]').click();
        await expect(page.locator('[data-testid="tb-structured-field-slides-0-title"]')).toHaveValue('Second safe hero slide');
        await waitForCanvasReload(page, beforeMoveUrl);
        const beforeRemoveUrl = page.frames().find((candidate) => candidate.url().includes('/theme-design/canvas/live-preview')).url();
        await page.locator('[data-testid="tb-structured-remove-slides-0"]').click();
        await installFoodMartRuntime(await waitForCanvasReload(page, beforeRemoveUrl));
        await expect(canvasIframe(page).locator('.main-swiper.swiper-initialized .swiper-slide')).toHaveCount(1);

        await treeNode(page, 'fm-binding-products').click();
        await expect(page.locator('[data-testid="tb-prop-section_title"]')).toBeVisible();
        await page.locator('[data-testid="tb-prop-section_title"]').fill('Editable product heading');
        await expect(canvasIframe(page).getByText('Editable product heading')).toBeVisible();
        await page.screenshot({ path: join(evidenceDir, '04-product-block-edited.png') });

        await showBlocksTab(page);
        await page.locator('[data-testid="tb-module-capability-products-collection"]').click();
        await expect(page.locator('[data-testid="tb-module-presentation-modal"]')).toBeVisible();
        await page.screenshot({ path: join(evidenceDir, '05-add-module-ui.png') });
        await page.screenshot({ path: join(evidenceDir, '06-products-presentation-selection.png') });
        await page.locator('[data-testid="tb-module-presentation-foodmart_trending_products"] [data-testid="tb-module-insert-after"]').click();
        await expect(page.locator('[data-testid="tb-data-binding"]')).toBeVisible();
        await expect(page.locator('[data-testid="tb-binding-enabled"]')).toBeChecked();
        await expect(canvasIframe(page).getByText('Browser Sale Apple').first()).toBeVisible();
        await page.screenshot({ path: join(evidenceDir, '07-products-inserted-foodmart.png') });

        await showBlocksTab(page);
        await page.locator('[data-testid="tb-module-capability-product_categories-collection"]').click();
        await page.locator('[data-testid="tb-module-presentation-foodmart_categories"] [data-testid="tb-module-insert-after"]').click();
        await expect(canvasIframe(page).getByText('Browser Fresh Fruit').first()).toBeVisible();
        await page.screenshot({ path: join(evidenceDir, '08-categories-inserted-foodmart.png') });

        await saveButton(page).click();
        await page.reload({ waitUntil: 'domcontentloaded' });
        await switchToPage(page, 'foodmart-binding');
        await openTreeTab(page);
        await expect(page.locator('[data-block-type="foodmart_trending_products"]')).toHaveCount(2);
        await expect(page.locator('[data-block-type="foodmart_categories"]')).toHaveCount(2);
        await treeNode(page, 'fm-hero').click();
        await expect(page.locator('[data-testid="tb-structured-field-slides-0-title"]')).toHaveValue('Edited existing hero slide');
        await page.screenshot({ path: join(evidenceDir, '10-save-reload-result.png') });

        const popupPromise = page.waitForEvent('popup');
        await page.locator('[data-testid="tb-full-preview-link"]').click();
        const fullPreview = await popupPromise;
        await fullPreview.waitForLoadState('domcontentloaded');
        await installFoodMartRuntime(fullPreview);
        await expect(fullPreview.locator('.banner-blocks')).toBeVisible();
        await expect(fullPreview.locator('.main-swiper')).toHaveClass(/swiper-initialized/);
        await expect(fullPreview.locator('.category-carousel').first()).toHaveClass(/swiper-initialized/);
        await expect(fullPreview.locator('.products-carousel').first()).toHaveClass(/swiper-initialized/);
        expect(await fullPreview.locator('script[src*="cdn.tailwindcss.com"]').count()).toBe(0);
        expect(await fullPreview.locator('[data-tb-block-id]').count()).toBe(0);

        await fullPreview.setViewportSize({ width: 390, height: 900 });
        await fullPreview.locator('[data-bs-target="#offcanvasSearch"]:visible').first().click();
        await expect(fullPreview.locator('#offcanvasSearch')).toHaveClass(/show/);
        await fullPreview.locator('#offcanvasSearch input').fill('apple');
        await fullPreview.locator('#offcanvasSearch .btn-close').click();
        await fullPreview.locator('[data-bs-target="#offcanvasCart"]:visible').first().click();
        await expect(fullPreview.locator('#offcanvasCart')).toHaveClass(/show/);
        await fullPreview.locator('#offcanvasCart .btn-close').click();

        const categoryWrapper = fullPreview.locator('.category-carousel .swiper-wrapper').first();
        const categoryBefore = await categoryWrapper.getAttribute('style');
        await fullPreview.locator('.category-carousel-next').first().click();
        await expect.poll(() => categoryWrapper.getAttribute('style')).not.toBe(categoryBefore);
        const quantity = fullPreview.locator('.foodmart-add-to-cart input[name="quantity"]').first();
        await quantity.fill('2');
        await expect(quantity).toHaveValue('2');

        for (const width of [390, 768, 991, 1200, 1440]) {
            await fullPreview.setViewportSize({ width, height: 900 });
            expect(await fullPreview.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBe(true);
        }
        await fullPreview.screenshot({ path: join(evidenceDir, '09-full-preview-canonical-runtime.png'), fullPage: true });
        await fullPreview.close();
    });
});
