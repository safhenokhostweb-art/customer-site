import { expect, test } from '@playwright/test';
import { VIEWPORTS } from './fixtures/viewports.js';
import { loginAsAdmin } from './helpers/auth.js';
import { canvasIframe, openCanvasEditor, publicPage, switchToPage } from './helpers/canvas.js';

/**
 * Phase 0.3 / 0.4 (saas-platform COMMERCIAL_WIDGET_ROADMAP.md, ADR-063): this
 * host's live page and its Canvas editor iframe both ship the shared package
 * block stylesheet (via partials/theme-canvas-style-contract) and the local
 * Vazirmatn faces (via partials/vazirmatn-font-faces-inline), with no request
 * to a font CDN.
 */

/** Reads, inside a document, what the shared sheet and the font faces delivered. */
function inspectDocument() {
    let bento = null;
    for (const sheet of document.styleSheets) {
        let rules;
        try { rules = sheet.cssRules; } catch { continue; }
        for (const rule of rules) {
            if (rule.selectorText === '.tb-bento-grid') bento = rule.style.gridAutoRows;
        }
    }
    const faces = [...document.fonts].filter((f) => f.family.replace(/['"]/g, '') === 'Vazirmatn');
    return {
        bento,
        weights: [...new Set(faces.map((f) => String(f.weight)))].sort(),
        regular: document.fonts.check('400 16px Vazirmatn'),
        overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
    };
}

test.describe('Phase 0.3/0.4 shared block CSS and local fonts', () => {
    test('live page and editor iframe ship the shared sheet and local Vazirmatn', async ({ page }) => {
        const fontCdn = [];
        page.on('request', (r) => { if (/fonts\.(googleapis|gstatic)\.com/.test(r.url())) fontCdn.push(r.url()); });

        for (const [name, viewport] of Object.entries(VIEWPORTS)) {
            await page.setViewportSize(viewport);
            await publicPage(page, null);
            await page.waitForLoadState('networkidle');
            await page.evaluate(() => document.fonts.ready);
            const live = await page.evaluate(inspectDocument);
            expect(live.bento, `${name}: shared sheet on the live page`).toBe('minmax(160px, auto)');
            expect(live.weights, `${name}: local faces`).toEqual(['400', '500', '600', '700', '800']);
            expect(live.regular, `${name}: Vazirmatn 400 loaded`).toBe(true);
            expect(live.overflow, `${name}: no horizontal overflow`).toBeLessThanOrEqual(0);
            await page.screenshot({ path: `tests/browser/.runtime/phase03-live-${name}.png` });
        }

        await page.setViewportSize(VIEWPORTS.desktop);
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'home');
        const frame = canvasIframe(page);
        await frame.locator('body').waitFor();
        const handle = await page.locator('#tb-live-iframe').elementHandle();
        const editorFrame = await handle.contentFrame();
        await editorFrame.waitForLoadState('networkidle');
        const iframe = await editorFrame.evaluate(inspectDocument);
        expect(iframe.bento, 'shared sheet in the editor iframe').toBe('minmax(160px, auto)');
        expect(iframe.weights).toEqual(['400', '500', '600', '700', '800']);

        expect(fontCdn, 'no font CDN request').toEqual([]);
    });
});
