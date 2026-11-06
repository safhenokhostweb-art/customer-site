import { expect, test } from '@playwright/test';
import { VIEWPORTS } from './fixtures/viewports.js';
import { BASE_URL } from './fixtures/env.js';
import { loginAsAdmin } from './helpers/auth.js';
import { canvasIframe, switchToPage } from './helpers/canvas.js';

/**
 * Add-child search + the children policy in the AI Dev HUD.
 *
 *   1. The add-child modal: the search field has focus on open; typing
 *      filters by title, type key and category label; no match shows
 *      «ویجتی با این عنوان یافت نشد»; × clears; Escape closes; Enter
 *      inserts the first match.
 *   2. With ?ai_dev=1: an unknown child inside the seeded section is a
 *      [slot-error] both by the client policy and by the server's own
 *      "[child N]" message; a leaf given children is a [children-policy]
 *      error. Bar, «سلامت فرزندان» card and the copied children_structure
 *      all say so. Nothing is saved: the section is restored.
 */

const SECTION = 'canvas_section_1';
const editor = (page, fn) => page.evaluate(`(() => { const s = window.Alpine.$data(document.querySelector('[x-data="canvasEditor()"]')); return (${fn})(s); })()`);

function collectConsoleErrors(page) {
    const errors = [];
    page.on('console', (msg) => { if (msg.type() === 'error') errors.push(`${msg.text()} (${msg.location().url})`); });
    page.on('pageerror', (err) => errors.push(String(err)));
    return errors;
}
const realErrorsOnly = (errors) => errors.filter((e) =>
    !/favicon|ERR_INTERNET_DISCONNECTED/i.test(e) && !/(notification-center|system-alerts)\/unread-count|user-tickets\/check-new/i.test(e));

async function openWithSection(page, query = '') {
    await page.goto(`${BASE_URL}/admin/theme-design/canvas/edit${query}`, { waitUntil: 'domcontentloaded' });
    await page.locator('[data-testid="tb-canvas-save-button"]').waitFor({ state: 'visible', timeout: 15_000 });
    await switchToPage(page, 'home');
    await expect.poll(() => editor(page, '(s) => s.iframeReady'), { timeout: 20_000 }).toBe(true);
    await canvasIframe(page).locator(`[data-tb-block-id="${SECTION}"]`).first().click({ position: { x: 20, y: 10 } });
    await expect.poll(() => editor(page, '(s) => s.selectedBlock?.id ?? null')).toBe(SECTION);
}

test.describe('Add-child search and the children policy', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test('the add-child modal filters live, clears, closes on Escape and inserts on Enter', async ({ page }) => {
        test.setTimeout(120_000);
        const errors = collectConsoleErrors(page);
        await loginAsAdmin(page);
        await openWithSection(page);
        const before = await editor(page, `(s) => JSON.parse(JSON.stringify(s.selectedBlock.children ?? []))`);

        const modal = page.getByTestId('tb-add-child-dialog');
        const search = page.getByTestId('tb-add-child-search');
        const card = (type) => page.getByTestId(`tb-add-child-${type}`);
        await page.getByTestId('tb-open-add-child-modal').click();
        await expect(modal).toBeVisible();
        await expect(search).toBeFocused();
        await expect(card('heading')).toBeVisible();
        await expect(card('image')).toBeVisible();

        await search.pressSequentially('عنوان');                    // title
        await expect(card('heading')).toBeVisible();
        await expect(card('image')).toBeHidden();

        await search.fill('flex');                                   // official type key
        await expect(card('flex_container')).toBeVisible();
        await expect(card('heading')).toBeHidden();

        await search.fill('چیدمان');                                 // category label
        await expect(card('section')).toBeVisible();
        await expect(card('heading')).toBeHidden();

        await search.fill('كانتينر');                                // Arabic ي/ك typed: same as Persian
        await expect(card('flex_container')).toBeVisible();

        await search.fill('zzzz ناموجود');
        await expect(page.getByTestId('tb-add-child-empty')).toHaveText('ویجتی با این عنوان یافت نشد');
        await expect(card('heading')).toBeHidden();

        await page.getByTestId('tb-add-child-search-clear').click();
        await expect(search).toHaveValue('');
        await expect(search).toBeFocused();
        await expect(page.getByTestId('tb-add-child-empty')).toBeHidden();
        await expect(card('heading')).toBeVisible();
        await page.screenshot({ path: 'tests/browser/.runtime/add-child-search.png' });

        await search.fill('عنوان');
        await page.keyboard.press('Escape');
        await expect(modal).toBeHidden();

        // Reopening starts clean and focused; Enter inserts the first match.
        await page.getByTestId('tb-open-add-child-modal').click();
        await expect(search).toHaveValue('');
        await expect(search).toBeFocused();
        await search.fill('عنوان');
        await page.keyboard.press('Enter');
        await expect(modal).toBeHidden();
        await expect.poll(() => editor(page, `(s) => (s.getBlockByPath(s.findBlockPath('${SECTION}')).children ?? []).map((c) => c.type)`))
            .toEqual([...before.map((c) => c.type), 'heading']);

        // Restore the seeded section exactly and save it back.
        await editor(page, `(s) => { const sec = s.getBlockByPath(s.findBlockPath('${SECTION}')); sec.children = ${JSON.stringify(before)}; }`);
        await editor(page, '(s) => s.saveCurrentPage()');
        await expect.poll(() => editor(page, '(s) => !s.saving && !s.isDirty')).toBe(true);
        expect(realErrorsOnly(errors), realErrorsOnly(errors).join('\n')).toHaveLength(0);
    });

    test('Dev HUD: slot errors and the children policy go red in the bar, the card and the prompt', async ({ page, context }) => {
        test.setTimeout(120_000);
        const errors = collectConsoleErrors(page);
        await context.grantPermissions(['clipboard-read', 'clipboard-write'], { origin: new URL(BASE_URL).origin });
        const clip = () => page.evaluate(() => navigator.clipboard.readText()).then((t) => t.replace(/\r\n/g, '\n'));
        await loginAsAdmin(page);
        await openWithSection(page, '?ai_dev=1');
        const original = await editor(page, `(s) => JSON.parse(JSON.stringify(s.selectedBlock.children ?? []))`);
        const bar = page.locator('[data-ai-dev-hud]');
        const card = page.locator('[data-ai-dev-children]');
        await page.locator('[data-testid="tb-canvas-health-tab"]').click();
        await expect(card).toBeVisible();
        await expect(card.locator('[data-ai-dev-children-accepts]')).toHaveText('بله (kind: section)');
        await expect(bar.locator('[data-ai-dev-policy-badge]')).toBeHidden();

        try {
            // An unknown child in slot N, re-rendered by the server (its own "[child N]" message).
            const slot = original.length;
            await editor(page, `(s) => { s.selectedBlock.children = [...(s.selectedBlock.children ?? []), { type: 'no_such_widget', id: 'zz_bad', props: {} }]; s.schedulePropUpdate(); }`);
            await expect(bar.locator('[data-ai-dev-policy-badge]')).toHaveText('[slot-error]');
            await expect(bar.locator('[data-ai-dev-state]')).toHaveText('خطای ساختار فرزندان');
            const row = card.locator(`[data-ai-dev-slot="${slot}"]`);
            await expect(row).toHaveAttribute('data-tone', 'error');
            await expect(row).toContainText('[slot-error] unknown block type «no_such_widget»');
            await expect(row).toContainText('[slot-error] Unknown element type: no_such_widget.', { timeout: 15_000 }); // the server's own message
            await page.screenshot({ path: 'tests/browser/.runtime/dev-hud-children-policy.png' });

            await bar.locator('[data-ai-dev-copy]').click();
            await expect.poll(clip).toContain('- **ساختار فرزندان (children_structure):**');
            const prompt = await clip();
            expect(prompt).toContain(`  * والد: \`section (ID: ${SECTION})\` | kind: \`section\` | پذیرش فرزند: بله`);
            expect(prompt).toContain(`  * [${slot}] \`no_such_widget\` (ID: zz_bad): \`[slot-error] unknown block type «no_such_widget»\``);
            expect(prompt).toContain('`[slot-error] slot ' + slot + ' no_such_widget#zz_bad: unknown block type «no_such_widget»`');

            // A leaf given children: [children-policy], on the leaf and on its child.
            // No re-render on purpose: the preview still shows the server's old
            // "[child N] Unknown element type: no_such_widget", which must not be
            // pinned on the heading now in that slot.
            await editor(page, `(s) => {
                const sec = s.getBlockByPath(s.findBlockPath('${SECTION}'));
                sec.children = [...${JSON.stringify(original)}, { type: 'heading', id: 'zz_leaf', props: { text: 'برگ' }, children: [{ type: 'text', id: 'zz_orphan', props: {} }] }];
                s.selectBlock(s.findBlockPath('zz_leaf'));
            }`);
            await expect(bar.locator('[data-ai-dev-policy-badge]')).toHaveText('[children-policy]');
            await expect(card.locator('[data-ai-dev-children-accepts]')).toHaveText('خیر (kind: widget)');
            await expect(card.locator('[data-ai-dev-slot="0"]')).toContainText('«heading» accepts no children (kind widget): this child is never rendered');

            await editor(page, `(s) => s.selectBlock(s.findBlockPath('zz_orphan'))`);
            await expect(bar).toBeVisible(); // anchored to the nearest rendered ancestor
            await expect(page.locator('[data-ai-dev-health]')).toContainText('in parent heading#zz_leaf slot 0: «heading» accepts no children');
        } finally {
            await editor(page, `(s) => { const sec = s.getBlockByPath(s.findBlockPath('${SECTION}')); sec.children = ${JSON.stringify(original)}; s.selectBlock(s.findBlockPath('${SECTION}')); s.schedulePropUpdate(); }`);
            await expect(bar.locator('[data-ai-dev-policy-badge]')).toBeHidden({ timeout: 15_000 });
            await editor(page, '(s) => { s.isDirty = false; }');
        }
        expect(realErrorsOnly(errors), realErrorsOnly(errors).join('\n')).toHaveLength(0);
    });
});
