import { BASE_URL } from '../fixtures/env.js';

/**
 * Phase 3E-H-D0 — minimal reusable Builder helpers/selectors. Intentionally
 * narrow: only what the D0 smoke test needs. The full H-D page-object suite
 * (palette-by-type, container nesting, asset assertions, etc.) is built in
 * the Extension SDK browser-verification phase, on top of these.
 */

/**
 * Opens the real Builder route and waits for the canvas to be genuinely
 * populated. If the page already has persisted blocks, init() fires an
 * async POST to /builder/preview to render them into the iframe — waiting
 * only for DOM attachment (not that round trip) leaves a real race where
 * the canvas is still empty. If the page has zero blocks, init() writes
 * the empty-state HTML synchronously and no such request is ever made, so
 * the wait below is best-effort (a short timeout, not a hard requirement).
 *
 * @param {import('@playwright/test').Page} page
 */
export async function openBuilder(page, pageId) {
    const previewResponse = page
        .waitForResponse((res) => res.url().includes('/builder/preview') && res.request().method() === 'POST', { timeout: 5_000 })
        .catch(() => null);
    await page.goto(`${BASE_URL}/builder/edit/${pageId}`, { waitUntil: 'domcontentloaded' });
    await page.locator('#live-canvas').waitFor({ state: 'attached' });
    await previewResponse;
}

export function saveButton(page) {
    return page.locator('[data-testid="tb-save-button"]');
}

export function addBlockButton(page, blockType) {
    return page.locator(`[data-testid="tb-add-block-${blockType}"]`);
}

export function propField(page, propKey) {
    return page.locator(`[data-testid="tb-prop-${propKey}"]`);
}

export function successToast(page) {
    return page.getByText('ذخیره شد');
}

export function blockCount(page) {
    return page.locator('[data-testid="tb-block-count"]');
}

/** Waits for a real `PUT /builder/page/{id}` save request and returns its response. */
export async function waitForSave(page, action) {
    const [response] = await Promise.all([
        page.waitForResponse((res) => res.url().includes('/builder/page/') && res.request().method() === 'PUT'),
        action(),
    ]);
    return response;
}

/** Reloads the Builder and waits for the live-canvas preview round trip to complete. */
export async function reloadBuilder(page) {
    const previewResponse = page.waitForResponse((res) => res.url().includes('/builder/preview') && res.request().method() === 'POST');
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.locator('#live-canvas').waitFor({ state: 'attached' });
    await previewResponse;
}

export function canvasFrame(page) {
    return page.frameLocator('#live-canvas');
}

/** @param {import('@playwright/test').Page} page */
export async function publicPage(page, slug) {
    await page.goto(`${BASE_URL}/page/${slug}`, { waitUntil: 'domcontentloaded' });
}
