import { BASE_URL } from '../fixtures/env.js';

/**
 * Phase 3F-C — minimal reusable Canvas editor helpers/selectors, the Canvas
 * counterpart of helpers/builder.js. Scoped to what the Extension SDK →
 * Canvas authoring bridge browser verification needs: page-tab switching,
 * tree-view selection, the real save-blocks/publish endpoints, and the
 * same-origin live-preview iframe.
 */

/** Opens the real Canvas editor route. */
export async function openCanvasEditor(page) {
    await page.goto(`${BASE_URL}/admin/theme-design/canvas/edit`, { waitUntil: 'domcontentloaded' });
    await page.locator('[data-testid="tb-canvas-save-button"]').waitFor({ state: 'visible', timeout: 15_000 });
}

/** Switches to the given page-key tab (e.g. 'home', 'ext-bridge', '__header__'). */
export async function switchToPage(page, pageKey) {
    await page.locator(`[data-testid="tb-canvas-page-tab-${pageKey}"]`).click();
}

export async function openTreeTab(page) {
    await page.locator('[data-testid="tb-canvas-tree-tab"]').click();
}

export function treeNode(page, blockId) {
    return page.locator(`[data-testid="tb-tree-node-${blockId}"]`);
}

export function paletteAddButton(page, blockType) {
    return page.locator(`[data-testid="tb-palette-add-${blockType}"]`);
}

export async function openAddChildModal(page) {
    await page.locator('[data-testid="tb-open-add-child-modal"]').click();
}

export function addChildButton(page, blockType) {
    return page.locator(`[data-testid="tb-add-child-${blockType}"]`);
}

export function propField(page, propKey) {
    return page.locator(`[data-testid="tb-prop-${propKey}"]`);
}

export function saveButton(page) {
    return page.locator('[data-testid="tb-canvas-save-button"]');
}

export function publishButton(page) {
    return page.locator('[data-testid="tb-canvas-publish-button"]');
}

/** Waits for a real `PUT theme-design/canvas/save-blocks` request and returns its response. */
export async function waitForSaveBlocks(page, action) {
    const [response] = await Promise.all([
        page.waitForResponse((res) => res.url().includes('theme-design/canvas/save-blocks') && res.request().method() === 'PUT'),
        action(),
    ]);
    return response;
}

/** Waits for a real `POST theme-design/canvas/publish` request and returns its response. */
export async function waitForPublish(page, action) {
    const [response] = await Promise.all([
        page.waitForResponse((res) => res.url().includes('theme-design/canvas/publish') && res.request().method() === 'POST'),
        action(),
    ]);
    return response;
}

export function canvasIframe(page) {
    return page.frameLocator('#tb-live-iframe');
}

/** Reloads the Canvas editor and waits for it to be genuinely usable again. */
export async function reloadCanvasEditor(page) {
    await page.reload({ waitUntil: 'domcontentloaded' });
    await page.locator('[data-testid="tb-canvas-save-button"]').waitFor({ state: 'visible', timeout: 15_000 });
}

/** @param {import('@playwright/test').Page} page */
export async function publicPage(page, slug) {
    await page.goto(slug ? `${BASE_URL}/page/${slug}` : `${BASE_URL}/`, { waitUntil: 'domcontentloaded' });
}
