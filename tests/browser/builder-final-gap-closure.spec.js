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

// Real, minimal, valid PNG bytes — the same fixture convention
// media-library.spec.js already uses. SVG is deliberately excluded from
// CustomerMediaService's V1 MIME allowlist (ADR-049 §4: "pending an
// audited canonical sanitizer"), so an SVG upload here would always be
// rejected with a real 422 — this is current, correct product behavior,
// not something to work around.
const png = Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL8WQAAAABJRU5ErkJggg==', 'base64');

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

let fixture;

/**
 * Search the real Media Library (the picker's own search box, backed by
 * ThemeDesignMediaController::index()'s title/filename LIKE match) for a
 * known title, wait for the real debounced network response, then click
 * the matching item by its real UUID testid. Avoids assuming default
 * listing/pagination order — the harness seeds 30+ other real media rows,
 * so an un-searched item is not guaranteed to be on the first page.
 */
async function selectLibraryMediaByTitle(page, title, uuid) {
    const searchResponse = page.waitForResponse((r) => r.url().includes('/theme-design/canvas/media?') && r.request().method() === 'GET');
    await page.getByTestId('tb-media-search').fill(title);
    await searchResponse;
    await page.getByTestId(`tb-media-item-${uuid}`).click();
}

test.describe.serial('Builder final gap closure', () => {
    test.beforeAll(() => {
        const output = execFileSync('php', [
            'artisan', 'browser-harness:setup', '--universal', '--json',
            `--source-theme-id=${process.env.QA_THEME_ID || ''}`,
            `--source-release-id=${process.env.QA_THEME_RELEASE_ID || ''}`,
            `--source-version=${process.env.QA_THEME_RELEASE_VERSION || '1.0.0'}`,
        ], { cwd: process.cwd(), env: serverEnv(), encoding: 'utf8' });

        // Builder integrity audit (2026-09-11): real CustomerMedia UUIDs
        // (MEDIA-4 canonical identity) from the harness's own JSON
        // contract — the picker's testid is strictly img.uuid, no
        // filename identity exists any more.
        const jsonLine = output.split('\n').find((l) => l.startsWith('BROWSER_HARNESS_JSON:'));
        if (!jsonLine) throw new Error(`browser-harness:setup did not print its JSON summary. Output:\n${output}`);
        fixture = JSON.parse(jsonLine.slice('BROWSER_HARNESS_JSON:'.length));
    });

    test('compound gallery and team media use the canonical picker and preserve shapes', async ({ page }) => {
        test.setTimeout(240_000);
        const monitor = actionableErrors(page);
        const itemB = fixture.universal_media_uuids.about;
        const itemA = fixture.universal_media_uuids.hero;
        await loginAsAdmin(page);
        await openCanvasEditor(page);

        await switchToPage(page, 'home');
        await openTreeTab(page);
        await treeNode(page, 'qa_gallery').click();
        await expect(page.getByTestId('tb-repeater-items')).toBeVisible();
        await expect(page.getByTestId('tb-prop-items')).toHaveCount(0);

        // Builder integrity audit (2026-09-11): the real Theme content now
        // seeds 3 real gallery images (not the old fixture's 1), so the
        // add/pick/upload/reorder/replace/remove sequence below is index-
        // relative to the actual starting count rather than hardcoded —
        // it proves the exact same repeater CRUD lifecycle regardless of
        // how many real items a page starts with. Real wait for each
        // item's thumbnail to finish its async {media_id}->URL resolution
        // (mediaSrc()'s single-item lookup, MEDIA-4) before counting — the
        // preview list populates reactively per item, not all at once the
        // instant the tree node click resolves.
        await expect(page.locator('[data-testid^="tb-structured-preview-items-"]').first()).toBeVisible();
        await page.waitForLoadState('networkidle');
        const startCount = await page.locator('[data-testid^="tb-structured-preview-items-"]').count();
        const newIdx1 = startCount;
        const newIdx2 = startCount + 1;

        // Existing library item + real upload + ordering + replacement +
        // removal, all through the visual repeater and shared media modal.
        await page.getByTestId('tb-structured-add-items').click();
        await page.getByTestId(`tb-structured-media-items-${newIdx1}-url`).click();
        await selectLibraryMediaByTitle(page, 'QA about', itemB);
        await page.getByTestId('tb-media-confirm').click();
        await page.getByTestId('tb-structured-add-items').click();
        await page.getByTestId(`tb-structured-media-items-${newIdx2}-url`).click();
        await page.getByTestId('tb-media-upload-tab').click();
        const upload = page.waitForResponse((response) => response.url().includes('/theme-design/canvas/media/upload') && response.request().method() === 'POST');
        await page.getByTestId('tb-media-file-input').setInputFiles({
            name: 'qa-compound-gallery.png',
            mimeType: 'image/png',
            buffer: png,
        });
        const uploadResponse = await upload;
        expect(uploadResponse.ok()).toBeTruthy();
        const uploadedGalleryUuid = (await uploadResponse.json()).uuid;
        expect(uploadedGalleryUuid).toBeTruthy();
        await page.getByTestId('tb-media-confirm').click();
        const uploadedGalleryUrl = await page.getByTestId(`tb-structured-preview-items-${newIdx2}-url`).getAttribute('src');
        expect(uploadedGalleryUrl).toContain('/uploads/customer-media/');
        expect(uploadedGalleryUrl).toContain(uploadedGalleryUuid);
        // Moves the just-uploaded item (newIdx2) up to newIdx1, swapping
        // with the item picked from the library there — the uploaded item
        // ends up at newIdx1, the picked-from-library one at newIdx2.
        await page.getByTestId(`tb-structured-up-items-${newIdx2}`).click();
        await page.getByTestId('tb-structured-media-items-0-url').click();
        await selectLibraryMediaByTitle(page, 'QA about', itemB);
        await page.getByTestId('tb-media-confirm').click();
        // Removes the item at newIdx2 (the library pick, now redundant
        // with item 0's own replacement) — proves removal, leaving the
        // uploaded item as the real final addition.
        await page.getByTestId(`tb-structured-remove-items-${newIdx2}`).click();

        const gallerySave = await waitForSaveBlocks(page, () => saveButton(page).click());
        expect(gallerySave.ok()).toBeTruthy();
        const galleryPayload = gallerySave.request().postDataJSON();
        const savedGallery = galleryPayload.blocks.find((block) => block.type === 'gallery');
        expect(savedGallery.props.items).toHaveLength(startCount + 1);
        expect(savedGallery.props.items[0].qa_unknown).toBe('preserve-gallery');
        // Canonical MEDIA-4 shape: a real {media_id} reference, not a raw
        // URL/filename string.
        expect(savedGallery.props.items[0].url).toEqual({ media_id: itemB });
        expect(savedGallery.props.items[newIdx1].url).toEqual({ media_id: uploadedGalleryUuid });

        await reloadCanvasEditor(page);
        await switchToPage(page, 'home');
        await openTreeTab(page);
        await treeNode(page, 'qa_gallery').click();
        await expect(page.locator('[data-testid^="tb-structured-preview-items-"]')).toHaveCount(startCount + 1);

        await switchToPage(page, 'about');
        await openTreeTab(page);
        await treeNode(page, 'qa_team_cards').click();
        await expect(page.getByTestId('tb-repeater-members')).toBeVisible();
        await expect(page.getByTestId('tb-prop-members')).toHaveCount(0);
        await page.getByTestId('tb-structured-field-members-0-name').fill('عضو ویرایش‌شده');
        await page.getByTestId('tb-structured-media-members-0-image').click();
        await selectLibraryMediaByTitle(page, 'QA hero', itemA);
        await page.getByTestId('tb-media-confirm').click();
        await page.getByTestId('tb-structured-media-members-0-image').click();
        await page.getByTestId('tb-media-upload-tab').click();
        const teamUpload = page.waitForResponse((response) => response.url().includes('/theme-design/canvas/media/upload') && response.request().method() === 'POST');
        await page.getByTestId('tb-media-file-input').setInputFiles({
            name: 'qa-compound-team.png',
            mimeType: 'image/png',
            buffer: png,
        });
        const teamUploadResponse = await teamUpload;
        expect(teamUploadResponse.ok()).toBeTruthy();
        const uploadedTeamUuid = (await teamUploadResponse.json()).uuid;
        expect(uploadedTeamUuid).toBeTruthy();
        await page.getByTestId('tb-media-confirm').click();
        const uploadedTeamUrl = await page.getByTestId('tb-structured-preview-members-0-image').getAttribute('src');
        expect(uploadedTeamUrl).toContain(uploadedTeamUuid);

        const teamSave = await waitForSaveBlocks(page, () => saveButton(page).click());
        expect(teamSave.ok()).toBeTruthy();
        const teamPayload = teamSave.request().postDataJSON();
        const savedTeam = teamPayload.blocks.find((block) => block.type === 'team_cards');
        expect(typeof savedTeam.props.members).toBe('string');
        const members = JSON.parse(savedTeam.props.members);
        expect(members[0]).toMatchObject({
            name: 'عضو ویرایش‌شده',
            image: { media_id: uploadedTeamUuid },
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

    test('all 94 blocks insert through their real palette UI and survive save reload render', async ({ page }) => {
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
        // Builder integrity audit (2026-09-11): 94, not 96 — see the
        // matching count update in universal-multipurpose-theme.spec.js.
        await expect(palette).toHaveCount(94);
        const expectedTypes = (await palette.evaluateAll((buttons) => buttons.map((button) => button.dataset.testid.replace('tb-palette-add-', '')))).sort();
        expect(new Set(expectedTypes).size).toBe(94);

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
        expect(savedBlocks).toHaveLength(94);
        expect(savedBlocks.map((block) => block.type).sort()).toEqual(expectedTypes);

        await reloadCanvasEditor(page);
        await switchToPage(page, 'ui-96-gate');
        await openTreeTab(page);
        await expect(treeNodes).toHaveCount(94);
        const reloadedTypes = (await treeNodes.evaluateAll((nodes) => nodes.map((node) => node.dataset.blockType))).sort();
        expect(reloadedTypes).toEqual(expectedTypes);

        const frame = canvasIframe(page);
        await expect(frame.locator('body')).not.toContainText('[invalid]');
        await expect(frame.locator('[data-reason="invalid_schema"]')).toHaveCount(0);
        await expect(frame.locator('[data-reason="render_error"]')).toHaveCount(0);
        expect(expectedTypes.filter((type) => type.startsWith('module:'))).toHaveLength(32);
        expect(expectedTypes.filter((type) => !type.startsWith('module:'))).toHaveLength(62);
        expect(monitor.errors).toEqual([]);
    });
});
