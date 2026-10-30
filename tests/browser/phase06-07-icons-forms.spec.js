import { expect, test } from '@playwright/test';
import { VIEWPORTS } from './fixtures/viewports.js';
import { loginAsAdmin } from './helpers/auth.js';
import {
    openCanvasEditor,
    paletteAddButton,
    propField,
    publicPage,
    publishButton,
    reloadCanvasEditor,
    saveButton,
    switchToPage,
    waitForPublish,
    waitForSaveBlocks,
} from './helpers/canvas.js';

/**
 * Phases 0.6 / 0.7 (saas-platform COMMERCIAL_WIDGET_ROADMAP.md, ADR-065 and
 * ADR-066) on this host:
 *
 *   - the shared SVG icon picker authors a top-level icon (icon_box) and a
 *     list-item icon (trust_badges), and the live page draws the SVGs;
 *   - the live newsletter block posts to this host's real
 *     newsletter.subscribe route: Persian validation, success and
 *     duplicate toasts;
 *   - the live search block is a real GET form (scope + results route) and
 *     opens the search dialog, full screen on phones.
 */

function collectConsoleErrors(page) {
    const errors = [];
    page.on('console', (msg) => {
        if (msg.type() === 'error') errors.push(`${msg.text()} (${msg.location().url})`);
    });
    page.on('pageerror', (err) => errors.push(String(err)));
    page.on('dialog', (dialog) => dialog.accept());
    return errors;
}

function realErrorsOnly(errors) {
    return errors.filter(
        (e) =>
            !/favicon|ERR_INTERNET_DISCONNECTED/i.test(e) &&
            !/(notification-center|system-alerts)\/unread-count|user-tickets\/check-new/i.test(e),
    );
}

const homeBlocks = (page) => page.evaluate(() => window.Alpine.$data(document.querySelector('[x-data]')).pages['home'].blocks);

async function addFromPalette(page, type, category) {
    const button = paletteAddButton(page, type);
    if (!(await button.isVisible())) await page.getByTestId(`tb-palette-category-${category}`).click();
    return waitForSaveBlocks(page, () => button.click());
}

test.describe('Phase 0.6 / 0.7 host icons and site forms', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test('icon picker authors SVG icons; newsletter and search work on the live page', async ({ page }) => {
        test.setTimeout(240_000);
        const errors = collectConsoleErrors(page);
        await loginAsAdmin(page);
        await openCanvasEditor(page);
        await switchToPage(page, 'home');
        const before = (await homeBlocks(page)).map((b) => b.id);

        // ── Icon picker: top level ──────────────────────────────────────
        await addFromPalette(page, 'icon_box', 'content');
        await propField(page, 'title').fill('ارسال سریع');
        await propField(page, 'title').dispatchEvent('input');
        const trigger = page.getByTestId('tb-icon-trigger-icon');
        await expect(trigger).toContainText('ستاره');
        await trigger.click();
        await page.getByTestId('tb-icon-search-icon').fill('ارسال');
        await page.getByTestId('tb-icon-option-icon-truck').click();
        await expect(trigger).toContainText('ارسال');
        await expect(trigger).toBeFocused();

        // ── Icon picker: list item ─────────────────────────────────────
        await addFromPalette(page, 'trust_badges', 'marketing');
        const itemTrigger = page.getByTestId('tb-icon-trigger-badges-0-icon');
        await expect(itemTrigger).toContainText('ارسال');
        await itemTrigger.click();
        await page.getByTestId('tb-icon-cat-badges-0-icon-communication').click();
        await page.getByTestId('tb-icon-option-badges-0-icon-phone-call').click();
        await expect(itemTrigger).toContainText('تماس تلفنی');

        await addFromPalette(page, 'newsletter_form', 'marketing');
        await addFromPalette(page, 'search_bar', 'utility');

        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();
        await reloadCanvasEditor(page);
        await switchToPage(page, 'home');
        const added = (await homeBlocks(page)).filter((b) => !before.includes(b.id));
        const byType = Object.fromEntries(added.map((b) => [b.type, b]));
        expect(byType.icon_box.props.icon).toBe('truck');
        const badges = byType.trust_badges.props.badges;
        expect((typeof badges === 'string' ? JSON.parse(badges) : badges)[0].icon).toBe('phone-call');

        await waitForPublish(page, () => publishButton(page).click());
        await publicPage(page, null);

        // ── Live icons ─────────────────────────────────────────────────
        await expect(page.locator(`#tb-${byType.icon_box.id} svg.tb-icon-svg-truck`)).toBeVisible();
        await expect(page.locator(`#tb-${byType.trust_badges.id} .tb-trust-badge`).first().locator('svg.tb-icon-svg-phone-call')).toBeVisible();

        // ── Live newsletter: real route ────────────────────────────────
        const form = page.locator(`#tb-${byType.newsletter_form.id} form[data-tb-newsletter]`);
        await expect(form).toHaveAttribute('action', '/newsletter/subscribe');
        const field = form.locator('[name="contact"]');
        const toast = page.locator('.tb-toast');
        await field.fill('not-an-email');
        await field.press('Enter');
        await expect(form.locator('.tb-newsletter-status')).toContainText('معتبر نیست');
        const email = `phase07-${Date.now()}@example.com`;
        await page.waitForTimeout(1100);
        await field.fill(email);
        const [first] = await Promise.all([
            page.waitForResponse((r) => r.url().endsWith('/newsletter/subscribe')),
            field.press('Enter'),
        ]);
        expect(first.status()).toBe(201);
        await expect(toast.last()).toHaveText('عضویت شما در خبرنامه ثبت شد. سپاس!');
        await page.waitForTimeout(1100);
        await field.fill(email.toUpperCase());
        const [second] = await Promise.all([
            page.waitForResponse((r) => r.url().endsWith('/newsletter/subscribe')),
            field.press('Enter'),
        ]);
        expect(second.status()).toBe(200);
        await expect(toast.last()).toHaveText('این ایمیل یا شماره قبلاً در خبرنامه ثبت شده است.');

        // ── Live search ────────────────────────────────────────────────
        const search = page.locator(`#tb-${byType.search_bar.id}`);
        const routed = await search.getAttribute('data-tb-search');
        if (routed) {
            await expect(search).toHaveAttribute('method', 'get');
            for (const [name, viewport] of Object.entries(VIEWPORTS)) {
                await page.setViewportSize(viewport);
                await search.scrollIntoViewIfNeeded();
                await search.locator('input[type="search"]').click();
                const panel = page.locator('.tb-search-overlay [role="dialog"]');
                await expect(panel).toBeVisible();
                if (name === 'mobile') {
                    const box = await panel.boundingBox();
                    expect(Math.round(box.width)).toBe(viewport.width);
                }
                await page.waitForTimeout(300); // let the open transition finish
                await page.screenshot({ path: `tests/browser/.runtime/phase07-search-${name}.png` });
                await page.keyboard.press('Escape');
                await expect(panel).toBeHidden();
            }
        } else {
            // This fixture site has neither shop, blog nor advanced search:
            // nothing to search, so the block stays the inert form.
            await expect(search).toHaveAttribute('action', '#');
            test.info().annotations.push({ type: 'note', description: 'fixture site has no search routes; dialog covered by saas-platform phase07-site-forms.spec.ts' });
        }

        for (const [name, viewport] of Object.entries(VIEWPORTS)) {
            await page.setViewportSize(viewport);
            await form.scrollIntoViewIfNeeded();
            const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
            expect(overflow, `no horizontal overflow at ${name}`).toBeLessThanOrEqual(0);
            await page.locator(`#tb-${byType.trust_badges.id}`).screenshot({ path: `tests/browser/.runtime/phase06-trust-${name}.png` });
            await page.locator(`#tb-${byType.newsletter_form.id}`).screenshot({ path: `tests/browser/.runtime/phase07-newsletter-${name}.png` });
        }

        // Leave the shared fixture page as found.
        await page.setViewportSize(VIEWPORTS.desktop);
        await openCanvasEditor(page);
        await switchToPage(page, 'home');
        await page.evaluate((ids) => {
            const d = window.Alpine.$data(document.querySelector('[x-data]'));
            for (const id of ids) {
                const idx = d.pages['home'].blocks.findIndex((b) => b.id === id);
                if (idx < 0) continue;
                d.selectBlock(idx);
                d.removeSelected();
            }
        }, added.map((b) => b.id));
        expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();
        await waitForPublish(page, () => publishButton(page).click());
        expect((await homeBlocks(page)).map((b) => b.id)).toEqual(before);

        expect(realErrorsOnly(errors)).toHaveLength(0);
    });
});
