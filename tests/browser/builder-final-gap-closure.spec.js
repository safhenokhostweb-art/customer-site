import { execFileSync } from 'node:child_process';
import { Buffer } from 'node:buffer';
import { expect, test } from '@playwright/test';
import { serverEnv } from './fixtures/env.js';
import { loginAsAdmin } from './helpers/auth.js';
import {
    canvasIframe,
    openCanvasEditor,
    openTreeTab,
    publishButton,
    reloadCanvasEditor,
    saveButton,
    switchToPage,
    treeNode,
    waitForPublish,
    waitForSaveBlocks,
} from './helpers/canvas.js';

const actionableErrors = (page) => {
    const errors = [];
    let activeBlock = null;
    page.on('console', (message) => {
        if (message.type() === 'error') errors.push({
            block: activeBlock,
            kind: 'console',
            value: message.text(),
            source: message.location().url || null,
        });
    });
    page.on('pageerror', (error) => errors.push({ block: activeBlock, kind: 'page', value: String(error) }));
    page.on('requestfailed', (request) => {
        const text = request.failure()?.errorText || '';
        const supersededPreview = text === 'net::ERR_ABORTED'
            && request.url().includes('/admin/theme-design/canvas/live-preview');
        if (!supersededPreview && !/favicon|unread-count|check-new/i.test(request.url())) {
            errors.push({ block: activeBlock, kind: 'request', value: `${request.method()} ${request.url()} ${text}` });
        }
    });
    return { errors, setActiveBlock: (type) => { activeBlock = type; } };
};

test.describe.serial('Builder final gap closure', () => {
    test.beforeAll(() => {
        execFileSync('php', [
            'artisan', 'browser-harness:setup', '--universal', '--json',
            `--source-theme-id=${process.env.QA_THEME_ID || ''}`,
            `--source-release-id=${process.env.QA_THEME_RELEASE_ID || ''}`,
            `--source-version=${process.env.QA_THEME_RELEASE_VERSION || '1.0.0'}`,
        ], { cwd: process.cwd(), env: serverEnv(), encoding: 'utf8' });
    });

    test('compound gallery and team media use the canonical picker and preserve shapes', async ({ page }) => {
        test.setTimeout(240_000);
        const monitor = actionableErrors(page);
        await loginAsAdmin(page);
        await openCanvasEditor(page);

        await switchToPage(page, 'home');
        await openTreeTab(page);
        await treeNode(page, 'qa_gallery').click();
        await expect(page.getByTestId('tb-repeater-items')).toBeVisible();
        await expect(page.getByTestId('tb-prop-items')).toHaveCount(0);

        // Existing library item + real upload + ordering + replacement +
        // removal, all through the visual repeater and shared media modal.
        await page.getByTestId('tb-structured-add-items').click();
        await page.getByTestId('tb-structured-media-items-1-url').click();
        await page.getByTestId('tb-media-item-qa-existing-b.svg').click();
        await page.getByTestId('tb-media-confirm').click();
        await page.getByTestId('tb-structured-add-items').click();
        await page.getByTestId('tb-structured-media-items-2-url').click();
        await page.getByTestId('tb-media-upload-tab').click();
        const upload = page.waitForResponse((response) => response.url().includes('/theme-design/canvas/media/upload') && response.request().method() === 'POST');
        await page.getByTestId('tb-media-file-input').setInputFiles({
            name: 'qa-compound-gallery.svg',
            mimeType: 'image/svg+xml',
            buffer: Buffer.from('<svg xmlns="http://www.w3.org/2000/svg" width="320" height="180"><rect width="320" height="180" fill="#7c3aed"/></svg>'),
        });
        expect((await upload).ok()).toBeTruthy();
        await page.getByTestId('tb-media-confirm').click();
        const uploadedGalleryUrl = await page.getByTestId('tb-structured-preview-items-2-url').getAttribute('src');
        expect(uploadedGalleryUrl).toContain('/storage/theme-media/');
        await page.getByTestId('tb-structured-up-items-2').click();
        await page.getByTestId('tb-structured-media-items-0-url').click();
        await page.getByTestId('tb-media-item-qa-existing-b.svg').click();
        await page.getByTestId('tb-media-confirm').click();
        await page.getByTestId('tb-structured-remove-items-2').click();

        const gallerySave = await waitForSaveBlocks(page, () => saveButton(page).click());
        expect(gallerySave.ok()).toBeTruthy();
        const galleryPayload = gallerySave.request().postDataJSON();
        const savedGallery = galleryPayload.blocks.find((block) => block.type === 'gallery');
        expect(savedGallery.props.items).toHaveLength(2);
        expect(savedGallery.props.items[0].qa_unknown).toBe('preserve-gallery');
        expect(savedGallery.props.items[0].url).toMatch(/qa-existing-b\.svg$/);
        expect(savedGallery.props.items[1].url).toBe(uploadedGalleryUrl);

        await reloadCanvasEditor(page);
        await switchToPage(page, 'home');
        await openTreeTab(page);
        await treeNode(page, 'qa_gallery').click();
        await expect(page.locator('[data-testid^="tb-structured-preview-items-"]')).toHaveCount(2);

        await switchToPage(page, 'about');
        await openTreeTab(page);
        await treeNode(page, 'qa_team_cards').click();
        await expect(page.getByTestId('tb-repeater-members')).toBeVisible();
        await expect(page.getByTestId('tb-prop-members')).toHaveCount(0);
        await page.getByTestId('tb-structured-field-members-0-name').fill('عضو ویرایش‌شده');
        await page.getByTestId('tb-structured-media-members-0-image').click();
        await page.getByTestId('tb-media-item-qa-existing-a.svg').click();
        await page.getByTestId('tb-media-confirm').click();
        await page.getByTestId('tb-structured-media-members-0-image').click();
        await page.getByTestId('tb-media-upload-tab').click();
        const teamUpload = page.waitForResponse((response) => response.url().includes('/theme-design/canvas/media/upload') && response.request().method() === 'POST');
        await page.getByTestId('tb-media-file-input').setInputFiles({
            name: 'qa-compound-team.svg',
            mimeType: 'image/svg+xml',
            buffer: Buffer.from('<svg xmlns="http://www.w3.org/2000/svg" width="180" height="180"><circle cx="90" cy="90" r="90" fill="#0f766e"/></svg>'),
        });
        expect((await teamUpload).ok()).toBeTruthy();
        await page.getByTestId('tb-media-confirm').click();
        const uploadedTeamUrl = await page.getByTestId('tb-structured-preview-members-0-image').getAttribute('src');

        const teamSave = await waitForSaveBlocks(page, () => saveButton(page).click());
        expect(teamSave.ok()).toBeTruthy();
        const teamPayload = teamSave.request().postDataJSON();
        const savedTeam = teamPayload.blocks.find((block) => block.type === 'team_cards');
        expect(typeof savedTeam.props.members).toBe('string');
        const members = JSON.parse(savedTeam.props.members);
        expect(members[0]).toMatchObject({
            name: 'عضو ویرایش‌شده',
            image: uploadedTeamUrl,
            qa_unknown: 'preserve-team',
        });

        await reloadCanvasEditor(page);
        await switchToPage(page, 'about');
        await openTreeTab(page);
        await treeNode(page, 'qa_team_cards').click();
        await expect(page.getByTestId('tb-structured-field-members-0-name')).toHaveValue('عضو ویرایش‌شده');
        await expect(page.getByTestId('tb-structured-preview-members-0-image')).toHaveAttribute('src', uploadedTeamUrl);

        // Focused responsive/accessibility proof for the new adapter.
        for (const viewport of [{ width: 1366, height: 900 }, { width: 768, height: 1024 }, { width: 390, height: 844 }]) {
            await page.setViewportSize(viewport);
            const picker = page.getByTestId('tb-structured-media-members-0-image');
            await picker.click();
            await expect(page.getByRole('dialog', { name: 'مدیریت رسانه' })).toBeVisible();
            expect(await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth)).toBeLessThanOrEqual(1);
            await page.keyboard.press('Escape');
            await expect(page.getByRole('dialog', { name: 'مدیریت رسانه' })).toBeHidden();
            await expect(picker).toBeFocused();
        }

        expect((await waitForPublish(page, () => publishButton(page).click())).ok()).toBeTruthy();
        const publicPage = await page.context().newPage();
        await publicPage.goto('/page/about', { waitUntil: 'domcontentloaded' });
        await expect(publicPage.locator(`img[src="${uploadedTeamUrl}"]`)).toBeVisible();
        await publicPage.goto('/', { waitUntil: 'domcontentloaded' });
        await expect(publicPage.locator(`img[src="${uploadedGalleryUrl}"]`)).toBeVisible();
        expect(monitor.errors).toEqual([]);
    });

    test('all 96 blocks insert through their real palette UI and survive save reload render', async ({ page }) => {
        test.setTimeout(600_000);
        const monitor = actionableErrors(page);
        await loginAsAdmin(page);
        await openCanvasEditor(page);

        const addPageResponse = page.waitForResponse((response) => response.url().includes('/theme-design/canvas/pages') && response.request().method() === 'POST');
        await page.getByTestId('tb-add-page-open').click();
        await page.getByTestId('tb-add-page-title').fill('96 UI Gate');
        await page.getByTestId('tb-add-page-slug').fill('ui-96-gate');
        await page.getByTestId('tb-add-page-confirm').click();
        expect((await addPageResponse).ok()).toBeTruthy();
        await expect(page.getByTestId('tb-canvas-page-tab-ui-96-gate')).toBeVisible();

        for (const category of ['dynamic', 'ecommerce', 'marketing', 'utility']) {
            await page.getByTestId(`tb-palette-category-${category}`).click();
        }

        const palette = page.locator('[data-testid^="tb-palette-add-"]');
        await expect(palette).toHaveCount(96);
        const expectedTypes = (await palette.evaluateAll((buttons) => buttons.map((button) => button.dataset.testid.replace('tb-palette-add-', '')))).sort();
        expect(new Set(expectedTypes).size).toBe(96);

        const treeNodes = page.locator('[data-testid^="tb-tree-node-"]');
        for (let index = 0; index < expectedTypes.length; index += 1) {
            const type = expectedTypes[index];
            monitor.setActiveBlock(type);
            const insertionSave = page.waitForResponse((response) => {
                if (!response.url().includes('theme-design/canvas/save-blocks') || response.request().method() !== 'PUT') return false;
                const blocks = response.request().postDataJSON()?.blocks ?? [];
                return blocks.length === index + 1 && blocks[index]?.type === type;
            });
            await page.getByTestId(`tb-palette-add-${type}`).click();
            await expect(treeNodes, `real palette insertion failed for ${type}`).toHaveCount(index + 1);
            await expect(treeNodes.nth(index)).toHaveAttribute('data-block-type', type);
            expect((await insertionSave).ok(), `real insertion save failed for ${type}`).toBeTruthy();
        }
        monitor.setActiveBlock(null);

        const saveResponse = await waitForSaveBlocks(page, () => saveButton(page).click());
        expect(saveResponse.ok()).toBeTruthy();
        const savedBlocks = saveResponse.request().postDataJSON().blocks;
        expect(savedBlocks).toHaveLength(96);
        expect(savedBlocks.map((block) => block.type).sort()).toEqual(expectedTypes);

        await reloadCanvasEditor(page);
        await switchToPage(page, 'ui-96-gate');
        await openTreeTab(page);
        await expect(treeNodes).toHaveCount(96);
        const reloadedTypes = (await treeNodes.evaluateAll((nodes) => nodes.map((node) => node.dataset.blockType))).sort();
        expect(reloadedTypes).toEqual(expectedTypes);

        const frame = canvasIframe(page);
        await expect(frame.locator('body')).not.toContainText('[invalid]');
        await expect(frame.locator('[data-reason="invalid_schema"]')).toHaveCount(0);
        await expect(frame.locator('[data-reason="render_error"]')).toHaveCount(0);
        expect(expectedTypes.filter((type) => type.startsWith('module:'))).toHaveLength(34);
        expect(expectedTypes.filter((type) => !type.startsWith('module:'))).toHaveLength(62);
        expect(monitor.errors).toEqual([]);
    });
});
