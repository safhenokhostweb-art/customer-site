import { test, expect } from '@playwright/test';
import { mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { BASE_URL, loadFixture } from './fixtures/env.js';
import { loginAsAdmin } from './helpers/auth.js';
import { openCanvasEditor, openTreeTab, switchToPage, treeNode, canvasIframe, saveButton } from './helpers/canvas.js';

const here = dirname(fileURLToPath(import.meta.url));
const evidenceDir = join(here, '.runtime', 'foodmart-phase4b5');

test.describe('FoodMart Phase 4B.5 Canvas real editing', () => {
    test.beforeAll(() => mkdirSync(evidenceDir, { recursive: true }));

    test('hero editing, binding entity picker, live preview, save/reload persistence', async ({ page }) => {
        const fixture = loadFixture();
        await loginAsAdmin(page);
        await page.setViewportSize({ width: 1440, height: 1000 });

        await openCanvasEditor(page);
        await switchToPage(page, 'foodmart-binding');
        await openTreeTab(page);

        // ── 1. Select the bound products block → binding controls visible ──
        await treeNode(page, 'fm-binding-products').click();
        await expect(page.locator('[data-testid="tb-data-binding"]')).toBeVisible();
        await expect(page.locator('[data-testid="tb-binding-enabled"]')).toBeChecked();
        await expect(page.locator('[data-testid="tb-binding-sort"]')).toBeVisible();
        await expect(page.locator('[data-testid="tb-binding-limit"]')).toBeVisible();
        await expect(page.locator('[data-testid="tb-binding-empty"]')).toBeVisible();

        // ── 2. Manual sort → generic entity picker with human-readable names ──
        await page.locator('[data-testid="tb-binding-sort"]').selectOption('manual');
        await expect(page.locator('[data-testid="tb-binding-entity-options"]')).toBeVisible({ timeout: 15000 });
        const optionTexts = await page.locator('[data-testid="tb-binding-entity-options"] label span').allInnerTexts();
        expect(optionTexts.some((t) => t.includes('Browser Sale Apple'))).toBeTruthy();
        expect(optionTexts.some((t) => t.includes('Browser Hidden Draft'))).toBeFalsy(); // unpublished filtered
        await page.screenshot({ path: join(evidenceDir, 'product-section-binding-controls.png') });

        // ── 3. Select one entity → live iframe update shows only it ──
        await page.locator('[data-testid="tb-binding-entity-options"] label', { hasText: 'Browser Sale Apple' }).locator('input[type="checkbox"]').check();
        await page.waitForTimeout(900);
        const frame = canvasIframe(page);
        await expect(frame.getByText('Browser Sale Apple').first()).toBeVisible();
        await expect(frame.getByText('Browser Bakery Bread')).toHaveCount(0);

        // ── 4. Section title edit → live preview title changes ──
        const titleInput = page.locator('[data-testid="tb-prop-section_title"]');
        await expect(titleInput).toBeVisible();
        await titleInput.fill('پرفروش‌های اثبات ویرایش');
        await page.waitForTimeout(900);
        await expect(frame.getByText('پرفروش‌های اثبات ویرایش').first()).toBeVisible();
        await page.screenshot({ path: join(evidenceDir, 'product-section-after-edit.png') });

        // ── 5. Hero block: repeater controls with media/string fields ──
        await treeNode(page, 'fm-hero').click();
        await expect(page.locator('[data-testid="tb-repeater-slides"]')).toBeVisible();
        await expect(page.locator('[data-testid="tb-repeater-slides"] fieldset').first()).toBeVisible();
        const slideTitle = page.locator('[data-testid="tb-structured-field-slides-0-title"]');
        await slideTitle.fill('بنر اثبات ویرایش زنده');
        await page.waitForTimeout(900);
        await expect(canvasIframe(page).getByText('بنر اثبات ویرایش زنده').first()).toBeVisible();
        await page.screenshot({ path: join(evidenceDir, 'hero-selected-controls.png') });
        await page.screenshot({ path: join(evidenceDir, 'hero-after-live-edit.png') });

        // ── 6. SAVE → reload editor → persistence proof ──
        await saveButton(page).click();
        await expect(page.getByText('ذخیره شد ✓')).toBeVisible({ timeout: 15000 });

        await page.reload({ waitUntil: 'domcontentloaded' });
        await switchToPage(page, 'foodmart-binding');
        await openTreeTab(page);
        await treeNode(page, 'fm-binding-products').click();
        await expect(page.locator('[data-testid="tb-prop-section_title"]')).toHaveValue('پرفروش‌های اثبات ویرایش');
        await expect(page.locator('[data-testid="tb-binding-sort"]')).toHaveValue('manual');
        await expect(page.locator('input[data-testid^="tb-binding-entity-"]:checked').first()).toBeAttached();

        await treeNode(page, 'fm-hero').click();
        await expect(page.locator('[data-testid="tb-structured-field-slides-0-title"]')).toHaveValue('بنر اثبات ویرایش زنده');
        await page.screenshot({ path: join(evidenceDir, 'canvas-after-save-reload.png') });

        // ── 7. Draft/published separation: the saved edits live ONLY in the
        // draft. The public page keeps rendering the published content until
        // an explicit publish — unsaved/saved-draft state never leaks live. ──
        await page.goto(`${BASE_URL}/page/${fixture.foodmart_page_slug}`, { waitUntil: 'domcontentloaded' });
        await expect(page.getByText('Trending Products').first()).toBeVisible();
        await expect(page.getByText('پرفروش‌های اثبات ویرایش')).toHaveCount(0);
        await expect(page.getByText('بنر اثبات ویرایش زنده')).toHaveCount(0);

        // ── 8. Public page shows no editor UI leakage ──
        const publicHtml = await page.content();
        expect(publicHtml).not.toContain('tb-data-binding');
        expect(publicHtml).not.toContain('data-testid="tb-prop-');
        expect(publicHtml).not.toContain('tb-canvas-block');
    });
});
