import { expect, test } from '@playwright/test';
import { BASE_URL } from './fixtures/env.js';
import { VIEWPORTS } from './fixtures/viewports.js';
import { loginAsAdmin } from './helpers/auth.js';
import { addBlockButton, propField, publicPage, saveButton, waitForSave } from './helpers/builder.js';

/**
 * 2026-09-11 systemic root-cause regression — proves the real
 * authoring → save → reload → preview → public-render invariant end to
 * end for both Page Builder and the Theme Design Canvas, using real
 * browser UI interaction against the real routes (no DB injection, no
 * controller-only shortcut for the save action itself).
 *
 * Page creation via the real `/builder/api/pages` endpoint (through an
 * authenticated Playwright session) is fixture setup, the same convention
 * BrowserHarnessSetupCommand already uses for every other fixture page —
 * the SAVE action under test always goes through the real UI form control.
 */

const PAGE_MARKER = 'PAGE_AUTOMATED_RUNTIME_PROBE_20260911';
const THEME_MARKER = 'THEME_AUTOMATED_RUNTIME_PROBE_20260911';

// Slug already seeded as an empty canvas-theme page by
// BrowserHarnessSetupCommand (canvasThemeData.pages['ext-bridge']).
const COLLISION_SLUG = 'ext-bridge';
const SAFE_SLUG = 'root-cause-probe-ok';

/** Reads the real CSRF token the authenticated session's own admin pages carry, the same way the app's own JS does. */
async function csrfToken(page) {
    return page.evaluate(() => document.querySelector('meta[name="csrf-token"]')?.content);
}

test.describe('Builder save -> public render — systemic invariant (2026-09-11)', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test('Page Builder: creating a page at a canvas-theme-owned slug is rejected, not silently shadowed', async ({ page }) => {
        await loginAsAdmin(page);

        // Root-cause fix under regression: PageBuilderController::createPage()
        // now rejects a slug the active canvas theme already owns (422),
        // instead of silently succeeding into a page that could never be
        // publicly reached — ThemeCanvasPageController::show() resolves the
        // canvas-owned page for that slug first, unconditionally.
        const token = await csrfToken(page);
        const attempt = await page.request.post(`${BASE_URL}/builder/api/pages`, {
            data: { title: 'Root Cause Probe Collision', slug: COLLISION_SLUG },
            headers: { 'X-CSRF-TOKEN': token ?? '' },
        });
        expect(attempt.status()).toBe(422);
        const body = await attempt.json();
        expect(body.success).toBe(false);
        expect(body.error).toContain(COLLISION_SLUG);

        // No Page row was created for the rejected slug.
        const list = await page.request.get(`${BASE_URL}/builder/api/pages`);
        const { pages } = await list.json();
        expect(pages.some((p) => p.slug === COLLISION_SLUG)).toBe(false);
    });

    test('Page Builder: real save -> reload -> public render works end to end for a non-colliding page with multiple block types', async ({ page }) => {
        await loginAsAdmin(page);

        const token = await csrfToken(page);
        const created = await page.request.post(`${BASE_URL}/builder/api/pages`, {
            data: { title: 'Root Cause Probe Target', slug: SAFE_SLUG },
            headers: { 'X-CSRF-TOKEN': token ?? '' },
        });
        expect(created.ok()).toBeTruthy();
        const { page: createdPage } = await created.json();
        const pageId = createdPage.id;
        const slug = createdPage.slug;
        expect(slug).toBe(SAFE_SLUG);

        // createPage() creates unpublished pages by default (matching the
        // always-published browser-harness-page fixture is what makes
        // smoke.spec.js's own page reachable) — publish it as setup, not
        // as part of what's under test here.
        const published = await page.request.put(`${BASE_URL}/builder/api/page/${pageId}/settings`, {
            data: { is_published: true },
            headers: { 'X-CSRF-TOKEN': token ?? '' },
        });
        expect(published.ok()).toBeTruthy();

        // Real UI: open Builder, add heading + text + button (the "header
        // exists, body is blank" class of defect — Part I), set the
        // heading's marker text, save through the real PUT endpoint.
        await page.goto(`${BASE_URL}/builder/edit/${pageId}`, { waitUntil: 'domcontentloaded' });
        await page.locator('#live-canvas').waitFor({ state: 'attached' });

        await addBlockButton(page, 'heading').click();
        const headingText = propField(page, 'text').locator('input, textarea');
        await expect(headingText).toBeVisible();
        await headingText.fill(PAGE_MARKER);

        await addBlockButton(page, 'text').click();
        await addBlockButton(page, 'button').click();

        const saveResponse = await waitForSave(page, () => saveButton(page).click());
        expect(saveResponse.ok()).toBeTruthy();

        // Reload Builder — real persistence round trip.
        const previewResponse = page.waitForResponse((res) => res.url().includes('/builder/preview') && res.request().method() === 'POST');
        await page.reload({ waitUntil: 'domcontentloaded' });
        await page.locator('#live-canvas').waitFor({ state: 'attached' });
        await previewResponse;
        await expect(page.frameLocator('#live-canvas').getByText(PAGE_MARKER)).toBeVisible({ timeout: 15_000 });
        await expect(page.locator('[data-testid="tb-block-count"]')).toHaveText('3 ویجت');

        // The exact public page for this exact Page.id/slug shows the
        // marker — proving the fix: a non-colliding Page Builder page
        // reaches the real public render, not just Builder-internal state.
        await publicPage(page, slug);
        await expect(page.getByText(PAGE_MARKER)).toBeVisible({ timeout: 10_000 });
    });

    test('Theme Design Canvas: draft save does not reach public render; publish does', async ({ page }) => {
        await loginAsAdmin(page);

        await page.goto(`${BASE_URL}/admin/theme-design/canvas/edit`, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('[data-testid="tb-canvas-save-button"]')).toBeVisible({ timeout: 15_000 });

        await page.locator('[data-testid="tb-canvas-page-tab-home"]').click();
        await page.locator('[data-testid="tb-palette-add-heading"]').click();
        // addBlock() (palette path) selects the new block but does not
        // switch the right-panel tab the way selectBlock() (tree click)
        // does — the Content tab must be opened explicitly to reach props.
        await page.getByText('محتوا', { exact: true }).click();

        // Unlike Page Builder's propField() helper, the Canvas editor puts
        // data-testid directly on the <input>/<textarea> itself, not on a
        // wrapping element.
        const textField = page.locator('[data-testid="tb-prop-text"]').first();
        await expect(textField).toBeVisible();
        await textField.fill(THEME_MARKER);
        await textField.blur();

        const saveResponse = page.waitForResponse((res) => res.url().includes('theme-design/canvas/save-blocks') && res.request().method() === 'PUT');
        await page.locator('[data-testid="tb-canvas-save-button"]').click();
        expect((await saveResponse).ok()).toBeTruthy();

        // Real reload of the editor — the draft persisted. The Canvas
        // editor's own live-preview is rendered inside an iframe
        // (#tb-live-iframe), so it must be located via frameLocator, not
        // the top-level page.
        await page.reload({ waitUntil: 'domcontentloaded' });
        await page.locator('[data-testid="tb-canvas-page-tab-home"]').click();
        await expect(page.frameLocator('#tb-live-iframe').getByText(THEME_MARKER)).toBeVisible({ timeout: 10_000 });

        // The real public homepage does NOT show the marker yet — Save
        // alone (canvas_draft_data) never reaches active_canvas_theme_data.
        await page.goto(`${BASE_URL}/`, { waitUntil: 'domcontentloaded' });
        await expect(page.getByText(THEME_MARKER)).toHaveCount(0);

        // Real Publish through the real UI control.
        await page.goto(`${BASE_URL}/admin/theme-design/canvas/edit`, { waitUntil: 'domcontentloaded' });
        const publishResponse = page.waitForResponse((res) => res.url().includes('theme-design/canvas/publish') && res.request().method() === 'POST');
        // Filtered to the main frame only — the live-preview iframe
        // (#tb-live-iframe) navigates independently and would otherwise
        // satisfy this wait before the actual top-level reload happens.
        const selfReload = page
            .waitForEvent('framenavigated', { predicate: (frame) => frame === page.mainFrame(), timeout: 5_000 })
            .catch(() => {});
        await page.locator('[data-testid="tb-canvas-publish-button"]').click();
        expect((await publishResponse).ok()).toBeTruthy();

        // publishDraft() itself triggers window.location.reload() ~800ms
        // after success (canvas-editor.blade.php) — wait for that
        // self-initiated navigation to actually happen before this test
        // navigates away itself, or the two race (net::ERR_ABORTED).
        await selfReload;
        await page.waitForLoadState('domcontentloaded').catch(() => {});

        // Now the real public homepage shows the marker.
        await page.goto(`${BASE_URL}/`, { waitUntil: 'domcontentloaded' });
        await expect(page.getByText(THEME_MARKER)).toBeVisible({ timeout: 10_000 });
    });
});
