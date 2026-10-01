import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { serverEnv } from './fixtures/env.js';
import { loginAsAdmin } from './helpers/auth.js';
import { openCanvasEditor, openTreeTab, treeNode, propField, canvasIframe, saveButton, waitForSaveBlocks, publishButton, waitForPublish } from './helpers/canvas.js';

test('default homepage saves, reloads, previews and renders public chrome responsively', async ({ page }) => {
    test.setTimeout(180_000);
    execFileSync('php', [fileURLToPath(new URL('./scripts/seed-default-homepage.php', import.meta.url))], { env: serverEnv() });
    const errors = [];
    function trackErrors(surface) {
        surface.on('pageerror', error => errors.push(error.message));
        surface.on('console', message => { if (message.type() === 'error') errors.push(message.text()); });
        surface.on('requestfailed', request => errors.push(`${request.url()}: ${request.failure()?.errorText}`));
    }
    trackErrors(page);
    await loginAsAdmin(page);
    await openCanvasEditor(page);
    await openTreeTab(page);
    await treeNode(page, 'default_hero').click();
    const title = 'رشد کسب‌وکار — آزمون صفحه اصلی';
    await propField(page, 'title').fill(title);
    await expect(canvasIframe(page).getByText(title, { exact: true })).toBeVisible();
    const projectBlocks = nodes => nodes.filter(node => (node.dataset.tbBlockId ?? node.id.replace(/^tb-/, '')).startsWith('default_')).map(node => ({
        id: node.dataset.tbBlockId ?? node.id.replace(/^tb-/, ''),
        slot: node.dataset.layoutSlot ?? null,
        heading: node.querySelector('h1,h2,h3')?.textContent?.trim() ?? '',
        links: Array.from(node.querySelectorAll('a')).map(link => [link.textContent.trim(), link.getAttribute('href')]),
        cards: Array.from(node.querySelectorAll('.tb-feature-card')).map(card => card.textContent.trim()),
    }));
    await expect(canvasIframe(page).locator('[data-layout-slot="header"]')).toHaveCount(1);
    await expect(canvasIframe(page).locator('[data-layout-slot="footer"]')).toHaveCount(1);
    const liveProjection = await canvasIframe(page).locator('[data-tb-block-id],[data-layout-slot]').evaluateAll(projectBlocks);
    expect((await waitForSaveBlocks(page, () => saveButton(page).click())).ok()).toBeTruthy();
    await page.reload({ waitUntil: 'domcontentloaded' });
    await openTreeTab(page);
    await treeNode(page, 'default_hero').click();
    await expect(propField(page, 'title')).toHaveValue(title);
    const previewUrl = await page.locator('[data-testid="tb-full-preview-link"]').getAttribute('href');
    expect((await waitForPublish(page, () => publishButton(page).click())).ok()).toBeTruthy();
    // Publishing reloads the editor; inspect the independent preview/public
    // surface without racing that deliberate navigation.
    const rendered = await page.context().newPage();
    trackErrors(rendered);
    for (const url of [previewUrl, '/']) {
        await rendered.goto(url, { waitUntil: 'networkidle' });
        await expect(rendered.getByText(title, { exact: true })).toBeVisible();
        await expect(rendered.locator('[data-layout-slot="header"]')).toHaveCount(1);
        await expect(rendered.locator('[data-layout-slot="footer"]')).toHaveCount(1);
        await expect(rendered.locator('.tb-feature-card')).toHaveCount(4);
        await expect(rendered.locator('.tb-dynamic-product-grid')).toHaveAttribute('data-state', 'empty');
        const order = await rendered.locator('[data-tb-block-id]').evaluateAll(nodes => nodes.map(node => node.dataset.tbBlockId));
        expect(order.filter(id => id.startsWith('default_'))).toEqual(['default_header', 'default_hero', 'default_services', 'default_products', 'default_footer']);
        expect(await rendered.locator('[data-tb-block-id],[data-layout-slot]').evaluateAll(projectBlocks)).toEqual(liveProjection);
        for (const width of [1366, 768, 390]) {
            await rendered.setViewportSize({ width, height: 900 });
            expect(await rendered.locator('body').evaluate(body => body.scrollWidth <= document.documentElement.clientWidth + 1)).toBeTruthy();
            await expect(rendered.getByText(title, { exact: true })).toBeVisible();
        }
    }
    expect(errors).toEqual([]);
});
