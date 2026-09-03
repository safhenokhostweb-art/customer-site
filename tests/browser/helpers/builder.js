import { BASE_URL } from '../fixtures/env.js';

/**
 * Phase 3E-H-D0 — minimal reusable Builder helpers/selectors. Intentionally
 * narrow: only what the D0 smoke test needs. The full H-D page-object suite
 * (palette-by-type, container nesting, asset assertions, etc.) is built in
 * the Extension SDK browser-verification phase, on top of these.
 */

/** @param {import('@playwright/test').Page} page */
export async function openBuilder(page, pageId) {
    await page.goto(`${BASE_URL}/builder/edit/${pageId}`, { waitUntil: 'domcontentloaded' });
    await page.locator('#live-canvas').waitFor({ state: 'attached' });
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
