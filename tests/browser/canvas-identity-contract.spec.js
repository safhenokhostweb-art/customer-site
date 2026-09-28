import { expect, test } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth.js';
import { openCanvasEditor } from './helpers/canvas.js';

/**
 * Phase 6.1-B — the shared Canvas Identity Contract (theme-runtime package,
 * consumed here through the package's own canvas.editor-bootstrap view) must
 * be live inside the REAL customer Canvas editor: window.CanvasIdentity
 * present, version-gated at 1, exposing the canonical traversal API, and
 * reconciling selections exactly like the platform's own editor. The
 * bootstrap's fail-fast gate throws before the editor's own scripts run if
 * the contract is missing or mismatched — so a clean boot with these
 * fingerprints present is the end-to-end proof of the include-order
 * contract (runtime -> identity -> editor code).
 */

const EXPECTED_API = [
    'isValidNodeId',
    'validateTreeIdentity',
    'findById',
    'pathForId',
    'nodeAtPath',
    'parentForId',
    'flatten',
    'entryForId',
    'reconcileSelection',
];

test.describe('Canvas Identity Contract (theme-runtime editor-bootstrap)', () => {
    test('editor boots with contract v1, full traversal API, and id-stable reconciliation', async ({ page }) => {
        const pageErrors = [];
        page.on('pageerror', (err) => pageErrors.push(String(err)));

        await loginAsAdmin(page);
        await openCanvasEditor(page);

        const contract = await page.evaluate(() => {
            const identity = window.CanvasIdentity;
            if (!identity) return null;
            const tree = [
                { id: 'section', type: 'section', children: [{ id: 'heading', type: 'heading' }] },
            ];
            return {
                version: identity.version,
                api: Object.keys(identity),
                rootParentId: identity.parentForId(tree, 'section')?.id ?? 'page',
                headingPath: Array.from(identity.pathForId(tree, 'heading')),
                reconciled: identity.reconcileSelection(tree, 'heading'),
                rejected: identity.reconcileSelection(tree, 'deleted'),
                runtimeVersion: window.ThemeCanvasRuntime?.version ?? null,
            };
        });

        expect(contract, 'window.CanvasIdentity must exist after editor boot').not.toBeNull();
        expect(contract.version).toBe(1);
        expect(contract.runtimeVersion).toBe(1);
        for (const fn of EXPECTED_API) {
            expect(contract.api, `missing contract API: ${fn}`).toContain(fn);
        }
        // ROOT_ID: root nodes traverse to the synthetic 'page' parent.
        expect(contract.rootParentId).toBe('page');
        expect(contract.headingPath).toEqual([0, 0]);
        expect(contract.reconciled).toBe('heading');
        expect(contract.rejected).toBeNull();

        // Only contract-specific failures matter here (the editor surface has
        // its own unrelated error budget): the fail-fast gate throwing, or a
        // script error inside the contract/bootstrap itself.
        const contractErrors = pageErrors.filter((e) =>
            /CanvasIdentity|ThemeCanvasRuntime contract|editor-bootstrap/i.test(e),
        );
        expect(contractErrors, `contract errors: ${contractErrors.join(' | ')}`).toEqual([]);
    });

    test('public Canvas render exposes runtime identity without editor authority', async ({ page }) => {
        const pageErrors = [];
        page.on('pageerror', (err) => pageErrors.push(String(err)));

        // Dedicated DB-backed Page Builder fixture. Its slug is deliberately
        // outside the active Canvas Theme, so /page/{slug} delegates through
        // Front\PageController and exercises Smart Public Rendering itself.
        await page.goto('/page/browser-harness-control-page', { waitUntil: 'domcontentloaded' });

        const contract = await page.evaluate(() => ({
            identityVersion: window.CanvasIdentity?.version ?? null,
            runtimeVersion: window.ThemeCanvasRuntime?.version ?? null,
            interactionDispatcher: typeof window.ThemeInteractionDispatcher === 'object',
            editorPresent: typeof window.CanvasEditor !== 'undefined',
            identifiedBlocks: document.querySelectorAll('[data-tb-block-id][data-tb-block-type]').length,
            controlHeading: Boolean(document.querySelector('[data-tb-block-id="control_heading"]')),
            editorHooks: document.querySelectorAll('[data-tb-editable], [data-tb-edit-action], [data-tb-control-id]').length,
        }));

        expect(contract.identityVersion).toBe(1);
        expect(contract.runtimeVersion).toBe(1);
        expect(contract.interactionDispatcher).toBe(true);
        expect(contract.editorPresent).toBe(false);
        expect(contract.identifiedBlocks).toBeGreaterThan(0);
        expect(contract.controlHeading).toBe(true);
        expect(contract.editorHooks).toBe(0);

        const contractErrors = pageErrors.filter((error) =>
            /CanvasIdentity|ThemeCanvasRuntime|ThemeInteractionDispatcher/i.test(error),
        );
        expect(contractErrors, `public runtime errors: ${contractErrors.join(' | ')}`).toEqual([]);
    });
});
