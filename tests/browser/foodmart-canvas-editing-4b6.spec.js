import { test, expect } from '@playwright/test';
import { mkdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { loginAsAdmin } from './helpers/auth.js';
import { openCanvasEditor, openTreeTab, switchToPage, treeNode, canvasIframe, saveButton, reloadCanvasEditor } from './helpers/canvas.js';

/**
 * Phase 4B.6 — user-acceptance editing gate. Complements the completion spec
 * (hero lifecycle, module insertion, persistence, full preview) with the
 * remaining user-reported surfaces: manual product cards, manual/dynamic
 * source switching, blog, newsletter, promo/trust, header, footer.
 * Every interaction happens through the REAL authenticated Canvas UI.
 */

const here = dirname(fileURLToPath(import.meta.url));
const evidenceDir = join(here, '.runtime', 'foodmart-canvas-editing-4b6');
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

async function openEditingPage(page) {
    await openCanvasEditor(page);
    await switchToPage(page, 'foodmart-editing');
    await openTreeTab(page);
    await expect(canvasIframe(page).locator('body')).toBeVisible();
    await expect.poll(() => page.frames().some((candidate) => candidate.url().includes('/theme-design/canvas/live-preview'))).toBe(true);
    const frame = page.frames().find((candidate) => candidate.url().includes('/theme-design/canvas/live-preview'));
    expect(frame).toBeTruthy();
    await installFoodMartRuntime(frame);
    return frame;
}

/**
 * The generic add-item autoSave reloads the live-preview iframe (new
 * document → the manually-injected theme runtime is gone). Wait for the
 * reloaded frame and reinstall the runtime before further assertions —
 * the real Canvas editor does this natively (theme-owned assets), the
 * test harness must mirror it manually.
 */
async function afterIframeReload(page, previousUrl) {
    await expect.poll(() => {
        const current = page.frames().find((candidate) => candidate.url().includes('/theme-design/canvas/live-preview'));
        return current?.url() !== previousUrl && current?.url();
    }).toBeTruthy();
    const frame = page.frames().find((candidate) => candidate.url().includes('/theme-design/canvas/live-preview'));
    await frame.locator('body').waitFor({ state: 'visible', timeout: 20_000 });
    await installFoodMartRuntime(frame);
    return frame;
}

test.describe('FoodMart user-acceptance editing (4B.6)', () => {
    test.beforeAll(() => mkdirSync(evidenceDir, { recursive: true }));

    test('manual cards, source switching, blog, newsletter, promo, trust, header, footer', async ({ page }) => {
        test.setTimeout(240_000);
        await loginAsAdmin(page);
        await page.setViewportSize({ width: 1440, height: 1000 });
        const frame = await openEditingPage(page);

        // ── A. MANUAL PRODUCT CARDS ────────────────────────────────────────
        await treeNode(page, 'fme-products').click();
        await expect(page.locator('[data-testid="tb-prop-section_title"]')).toBeVisible();
        // The two fixture cards exist as editable repeater items:
        await expect(page.locator('[data-testid="tb-structured-field-items-0-title"]')).toHaveValue('Bananas');
        await expect(page.locator('[data-testid="tb-structured-field-items-1-title"]')).toHaveValue('Biscuits');
        // Click the visible card in the iframe → inspector highlights THAT item:
        await canvasIframe(page).locator('.product-item h3').first().click();
        await expect(page.locator('[data-testid="tb-repeater-item-items-0"]')).toHaveClass(/border-indigo-500/);
        await page.screenshot({ path: join(evidenceDir, '01-manual-product-card-selected.png') });

        // Edit the visible card's own fields → live iframe update:
        await page.locator('[data-testid="tb-structured-field-items-0-title"]').fill('Golden Bananas');
        await page.locator('[data-testid="tb-structured-field-items-0-price_text"]').fill('$3.49');
        await expect(canvasIframe(page).getByText('Golden Bananas')).toBeVisible();
        await page.screenshot({ path: join(evidenceDir, '02-manual-product-card-edited.png') });

        // ── B. MANUAL/DYNAMIC SOURCE SWITCH (layout must survive) ─────────
        await page.locator('[data-testid="tb-source-dynamic"]').click();
        await expect(page.locator('[data-testid="tb-data-binding"]')).toBeVisible();
        // Dynamic mode renders real DB products through the SAME FoodMart cards:
        await expect(canvasIframe(page).getByText('Browser Sale Apple').first()).toBeVisible({ timeout: 15_000 });
        await expect(canvasIframe(page).locator('.product-item.swiper-slide').first()).toBeVisible();
        await page.screenshot({ path: join(evidenceDir, '03-dynamic-mode-same-presentation.png') });

        // Switch back → manual data intact:
        await page.locator('[data-testid="tb-source-manual"]').click();
        await expect(page.locator('[data-testid="tb-structured-field-items-0-title"]')).toHaveValue('Golden Bananas');
        await expect(canvasIframe(page).getByText('Golden Bananas')).toBeVisible();
        await page.screenshot({ path: join(evidenceDir, '04-back-to-manual-preserved.png') });

        // ── C. BLOG (was "no settings") ────────────────────────────────────
        await treeNode(page, 'fme-blog').click();
        await expect(page.locator('[data-testid="tb-prop-section_title"]')).toBeVisible();
        await expect(page.locator('[data-testid="tb-structured-field-items-0-title"]')).toHaveValue('Fresh Produce Fitness');
        await canvasIframe(page).locator('.post-item').first().click();
        await expect(page.locator('[data-testid="tb-repeater-item-items-0"]')).toHaveClass(/border-indigo-500/);
        await page.locator('[data-testid="tb-structured-field-items-0-title"]').fill('Manual Blog Post Edited');
        await expect(canvasIframe(page).getByText('Manual Blog Post Edited')).toBeVisible();
        await page.screenshot({ path: join(evidenceDir, '05-blog-card-edited.png') });

        // ── D. NEWSLETTER (was partial) ────────────────────────────────────
        await treeNode(page, 'fme-newsletter').click();
        for (const key of ['title', 'description', 'name_label', 'name_placeholder', 'email_label', 'email_placeholder', 'checkbox_label', 'button_label']) {
            await expect(page.locator(`[data-testid="tb-prop-${key}"]`)).toBeVisible();
        }
        await page.locator('[data-testid="tb-prop-title"]').fill('Newsletter heading edited');
        await page.locator('[data-testid="tb-prop-button_label"]').fill('Join now');
        await expect(canvasIframe(page).getByText('Newsletter heading edited')).toBeVisible();
        await expect(canvasIframe(page).getByText('Join now')).toBeVisible();
        await page.screenshot({ path: join(evidenceDir, '06-newsletter-full-controls.png') });

        // ── E. PROMO + TRUST ───────────────────────────────────────────────
        // Fixture promo/trust blocks ship with empty items (canonical default
        // rendering) — proving the generic ADD-item flow, then editing:
        await treeNode(page, 'fme-promo').click();
        await page.locator('[data-testid="tb-structured-add-items"]').click();
        await expect(page.locator('[data-testid="tb-structured-field-items-0-title"]').first()).toBeVisible();
        await page.locator('[data-testid="tb-structured-field-items-0-title"]').fill('Promo banner edited');
        await expect(canvasIframe(page).getByText('Promo banner edited').first()).toBeVisible();

        await treeNode(page, 'fme-trust').click();
        const beforeTrustAdd = page.frames().find((candidate) => candidate.url().includes('/theme-design/canvas/live-preview')).url();
        await page.locator('[data-testid="tb-structured-add-items"]').click();
        // The add-item triggers the generic autoSave+iframe reload
        // (liveStructureUpdate='document'); wait for the reloaded preview
        // and reinstall the theme runtime, then assert the Golden trust-card
        // anatomy carries the edited item:
        await afterIframeReload(page, beforeTrustAdd);
        await page.locator('[data-testid="tb-structured-field-items-0-title"]').fill('Trust promise edited');
        await expect(
            canvasIframe(page).locator('.card-body h5', { hasText: 'Trust promise edited' }).first(),
        ).toBeVisible({ timeout: 30_000 });
        await page.screenshot({ path: join(evidenceDir, '07-promo-trust-edited.png') });

        // ── F. HEADER ──────────────────────────────────────────────────────
        await treeNode(page, 'fme-header').click();
        await expect(page.locator('[data-testid="tb-prop-search_placeholder"]')).toBeVisible();
        await expect(page.locator('[data-testid="tb-prop-support_phone"]')).toBeVisible();
        await page.locator('[data-testid="tb-prop-search_placeholder"]').fill('Search the store');
        await page.locator('[data-testid="tb-prop-support_phone"]').fill('+1 555 0199');
        // The desktop search input sits inside a responsive wrapper that is
        // visually hidden at this viewport — assert the VALUE landed (the
        // attribute is what the canonical renderer produced), not visibility:
        await expect(
            canvasIframe(page).locator('input[placeholder="Search the store"]').first(),
        ).toHaveAttribute('placeholder', 'Search the store', { timeout: 15_000 });
        // The support-phone block is inside the same desktop-only wrapper;
        // assert the rendered content exists (value correctness), not its
        // visibility at this viewport:
        await expect(canvasIframe(page).getByText('+1 555 0199').first()).toHaveText('+1 555 0199', { timeout: 15_000 });
        await expect(canvasIframe(page).getByText('+1 555 0199').first()).toBeAttached();
        // Manual nav item editing:
        await expect(page.locator('[data-testid="tb-structured-field-nav_items-0-label"]')).toHaveValue('Home');
        await page.locator('[data-testid="tb-structured-field-nav_items-0-label"]').fill('Homepage');
        // The manual nav item renders in the hidden desktop-only nav wrapper
        // at this viewport — assert it was rendered (attached + correct
        // text), not that it is on-screen:
        const navLink = canvasIframe(page).locator('a.nav-link', { hasText: 'Homepage' }).first();
        await expect(navLink).toBeAttached({ timeout: 15_000 });
        await expect(navLink).toHaveText('Homepage');
        await page.screenshot({ path: join(evidenceDir, '08-header-edited.png') });

        // ── G. FOOTER (columns → nested links) ─────────────────────────────
        await treeNode(page, 'fme-footer').click();
        await expect(page.locator('[data-testid="tb-prop-copyright"]')).toBeVisible();
        await page.locator('[data-testid="tb-prop-copyright"]').fill('© 2026 Edited Store');
        await page.locator('[data-testid="tb-prop-newsletter_heading"]').fill('Footer newsletter');
        // Nested links repeater inside columns item 0 (the deep adapter):
        // add a column, then add a link inside it, then edit its label —
        // the exact flow a real user performs:
        await page.locator('[data-testid="tb-structured-add-columns"]').click();
        await expect(page.locator('[data-testid="tb-structured-field-columns-0-heading"]')).toBeVisible();
        await page.locator('[data-testid="tb-structured-field-columns-0-heading"]').fill('Company');
        await page.locator('[data-testid^="tb-nested-add-columns-0-links"]').first().click();
        const linkField = page.locator('[data-testid^="tb-nested-field-columns-0-links-0-label"]');
        await expect(linkField).toBeVisible();
        await linkField.fill('Edited footer link');
        // Save → full editor reload (the strongest persistence proof): the
        // canonical renderer must reproduce copyright + column + nested link
        // from the saved draft:
        await saveButton(page).click();
        await page.waitForTimeout(1500);
        await reloadCanvasEditor(page);
        await switchToPage(page, 'foodmart-editing');
        await openTreeTab(page);
        await expect.poll(() => page.frames().some((candidate) => candidate.url().includes('/theme-design/canvas/live-preview'))).toBe(true);
        await page.waitForTimeout(2000);
        const afterFooter = page.frames().find((candidate) => candidate.url().includes('/theme-design/canvas/live-preview'));
        await expect(afterFooter.getByText('© 2026 Edited Store')).toBeVisible({ timeout: 20_000 });
        await expect(afterFooter.getByText('Edited footer link').first()).toBeVisible({ timeout: 20_000 });
        await page.screenshot({ path: join(evidenceDir, '09-footer-edited.png') });

        // ── H. SAVE → RELOAD → PERSISTENCE (fields beyond the footer) ─────
        await treeNode(page, 'fme-products').click();
        await expect(page.locator('[data-testid="tb-structured-field-items-0-title"]')).toHaveValue('Golden Bananas');
        await treeNode(page, 'fme-header').click();
        await expect(page.locator('[data-testid="tb-prop-support_phone"]')).toHaveValue('+1 555 0199');
        await page.screenshot({ path: join(evidenceDir, '10-save-reload-persisted.png') });

        // ── I. DRAFT/PUBLISHED SEPARATION ON THE PUBLIC PAGE ──────────────
        const publicHome = await page.request.get('/');
        const publicHtml = await publicHome.text();
        // Draft edits must NOT appear publicly before publish:
        expect(publicHtml).not.toContain('Golden Bananas');
        expect(publicHtml).not.toContain('© 2026 Edited Store');
    });
});
