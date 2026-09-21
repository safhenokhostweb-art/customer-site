import { expect, test } from '@playwright/test';
import { loginAsAdmin } from './helpers/auth.js';
import { VIEWPORTS } from './fixtures/viewports.js';

test.describe('Theme assignment and customer-confirmed installation', () => {
    test.use({ viewport: VIEWPORTS.desktop });

    test('catalog, acquisition, confirmation, activation, reload, responsive views and sibling visibility', async ({ page }) => {
        const errors = [];
        page.on('pageerror', (error) => errors.push(String(error)));
        page.on('console', (message) => {
            if (message.type() === 'error' && !/favicon|unread-count|check-new/i.test(message.text())) errors.push(message.text());
        });

        await loginAsAdmin(page);
        await page.goto('/admin/theme-marketplace', { waitUntil: 'networkidle' });
        await expect(page.getByText('Assigned Browser Theme', { exact: true }).first()).toBeVisible();
        await expect(page.getByText('Independent Browser Theme', { exact: true }).first()).toBeVisible();

        const purchaseForm = page.locator('form[action$="/admin/theme-marketplace/101/purchase"]').first();
        await Promise.all([page.waitForURL(/theme-marketplace\/my-licenses/), purchaseForm.locator('button').click()]);
        await expect(page.getByText('Assigned Browser Theme', { exact: true }).first()).toBeVisible();

        const installForm = page.locator('form[action$="/admin/theme-marketplace/101/install"]').first();
        const csrf = await installForm.locator('input[name="_token"]').inputValue();
        const rejected = await page.request.post('/admin/theme-marketplace/101/install', {
            form: { _token: csrf },
            maxRedirects: 0,
        });
        expect(rejected.status()).toBe(302);

        await page.goto('/');
        await expect(page.getByText('Assigned Browser Theme is active')).toHaveCount(0);

        await page.goto('/admin/theme-marketplace/my-licenses', { waitUntil: 'networkidle' });
        const confirmedForm = page.locator('form[action$="/admin/theme-marketplace/101/install"]').first();
        await confirmedForm.locator('input[name="confirm_overwrite"]').check();
        page.once('dialog', (dialog) => dialog.accept());
        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle' }),
            confirmedForm.locator('button[type="submit"]').click(),
        ]);

        await page.goto('/', { waitUntil: 'networkidle' });
        await expect(page.getByText('Assigned Browser Theme is active')).toBeVisible();
        await page.reload({ waitUntil: 'networkidle' });
        await expect(page.getByText('Assigned Browser Theme is active')).toBeVisible();

        const shots = [
            ['desktop', VIEWPORTS.desktop],
            ['tablet', VIEWPORTS.tablet],
            ['mobile', VIEWPORTS.mobile],
        ];
        for (const [name, viewport] of shots) {
            await page.setViewportSize(viewport);
            await expect(page.getByText('Assigned Browser Theme is active')).toBeVisible();
            await page.screenshot({ path: `tests/browser/.runtime/theme-assignment-${name}.png`, fullPage: true });
        }

        await page.goto('/admin/theme-marketplace', { waitUntil: 'networkidle' });
        await expect(page.getByText('Independent Browser Theme', { exact: true }).first()).toBeVisible();
        expect(errors, `Unexpected browser errors: ${JSON.stringify(errors)}`).toEqual([]);
    });
});
