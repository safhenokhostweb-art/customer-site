import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { expect, test } from '@playwright/test';
import { loginAsAdmin } from '../helpers/auth.js';
import { VIEWPORTS } from '../fixtures/viewports.js';

const __dirname = dirname(fileURLToPath(import.meta.url));
const customerRoot = join(__dirname, '..', '..', '..');
const platformRoot = join(customerRoot, '..', 'saas-platform');
const runtimeDir = join(customerRoot, 'tests', 'browser', '.runtime');
const fixture = JSON.parse(readFileSync(join(runtimeDir, 'real-theme-assignment-fixture.json'), 'utf8'));
const platformBase = 'http://127.0.0.1:8792';

const platformEnv = () => ({
    ...process.env,
    APP_ENV: 'testing',
    DB_CONNECTION: 'mysql',
    DB_DATABASE: 'saas_platform_theme_assignment_staging',
    THEME_ASSIGNMENT_SITE_A: String(fixture.site_a_id),
    THEME_ASSIGNMENT_SITE_B: String(fixture.site_b_id),
});

function state() {
    const output = execFileSync('php', [join(platformRoot, 'tests', 'browser', 'theme-assignment', 'state.php')], {
        cwd: platformRoot,
        env: platformEnv(),
        encoding: 'utf8',
    });
    const line = output.split(/\r?\n/).find((value) => value.startsWith('THEME_ASSIGNMENT_STATE:'));
    if (!line) throw new Error(`Assignment state was not emitted.\n${output}`);
    return JSON.parse(line.slice('THEME_ASSIGNMENT_STATE:'.length));
}

function syncCustomerLicense(key) {
    execFileSync('php', [join(customerRoot, 'tests', 'browser', 'scripts', 'sync-real-platform-license.php')], {
        cwd: customerRoot,
        env: { ...process.env, ...serverEnvForScript(), THEME_ASSIGNMENT_LICENSE_KEY: key },
        encoding: 'utf8',
    });
}

function serverEnvForScript() {
    return {
        APP_ENV: 'testing',
        DB_CONNECTION: 'mysql',
        DB_DATABASE: 'customer_site_browser',
        CACHE_STORE: 'array',
        QUEUE_CONNECTION: 'sync',
    };
}

async function loginPlatform(page) {
    await page.goto(`${platformBase}/login`, { waitUntil: 'domcontentloaded' });
    await page.locator('#login-mobile').fill(fixture.admin_mobile);
    await page.locator('#login-password').fill(fixture.admin_password);
    await page.locator('button[type="submit"]').click();
    await page.waitForURL(/\/admin(\/|$)/);
}

async function grant(page, siteId, themeId) {
    await page.goto(`${platformBase}/admin/theme-marketplace/licenses`, { waitUntil: 'domcontentloaded' });
    await page.getByRole('button', { name: 'اعطای دستی' }).click();
    const form = page.locator('#modal-grant form');
    await form.locator('input[name="site_id"]').fill(String(siteId));
    await form.locator('input[name="theme_id"]').fill(String(themeId));
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
        form.locator('button[type="submit"]').click(),
    ]);
}

async function responsiveShots(page, prefix) {
    for (const [name, viewport] of Object.entries({ desktop: VIEWPORTS.desktop, tablet: VIEWPORTS.tablet, mobile: VIEWPORTS.mobile })) {
        await page.setViewportSize(viewport);
        await page.screenshot({ path: join(runtimeDir, `${prefix}-${name}.png`), fullPage: true });
    }
}

test('real Platform assignment, isolated Marketplace entitlement and customer-confirmed install', async ({ browser, request }) => {
    const platformContext = await browser.newContext({ viewport: VIEWPORTS.desktop });
    const platformPage = await platformContext.newPage();
    await loginPlatform(platformPage);

    const beforeB = await request.get(`${platformBase}/api/theme-marketplace/my-licenses`, {
        headers: { Authorization: `Bearer ${fixture.site_b_license_key}`, 'X-Tenant-Domain': 'site-b.local', Accept: 'application/json' },
    });
    expect(beforeB.ok()).toBeTruthy();
    expect((await beforeB.json()).licenses).toEqual([]);

    await grant(platformPage, fixture.site_a_id, fixture.theme_a_id);
    await expect(platformPage.getByText('Assigned Real Theme', { exact: true }).first()).toBeVisible();
    await responsiveShots(platformPage, 'theme-assignment-admin');

    let assignmentState = state();
    expect(assignmentState.site_a_platform_licenses).toBe(1);
    expect(assignmentState.site_a_theme_licenses).toBe(1);
    expect(assignmentState.site_a_assignments).toBe(1);
    expect(assignmentState.site_b_theme_licenses).toBe(0);
    expect(assignmentState.site_b_assignments).toBe(0);
    syncCustomerLicense(assignmentState.site_a_license_key);

    const customerContext = await browser.newContext({ viewport: VIEWPORTS.desktop });
    const customerPage = await customerContext.newPage();
    await loginAsAdmin(customerPage);
    await customerPage.goto('/admin/theme-marketplace', { waitUntil: 'domcontentloaded' });
    await expect(customerPage.getByText('Assigned Real Theme', { exact: true }).first()).toBeVisible();
    await responsiveShots(customerPage, 'theme-assignment-marketplace');

    await customerPage.goto('/admin/theme-marketplace/my-licenses', { waitUntil: 'domcontentloaded' });
    const installForm = customerPage.locator(`form[action$="/admin/theme-marketplace/${fixture.theme_a_id}/install"]`).first();
    await expect(installForm).toBeVisible();
    await responsiveShots(customerPage, 'theme-assignment-confirmation');
    const csrf = await installForm.locator('input[name="_token"]').inputValue();
    const rejected = await customerPage.request.post(`/admin/theme-marketplace/${fixture.theme_a_id}/install`, {
        form: { _token: csrf },
        maxRedirects: 0,
    });
    expect(rejected.status()).toBe(302);

    await customerPage.goto('/', { waitUntil: 'domcontentloaded' });
    await expect(customerPage.getByText('Assigned Real Theme is active')).toHaveCount(0);

    await customerPage.goto('/admin/theme-marketplace/my-licenses', { waitUntil: 'domcontentloaded' });
    const confirmedForm = customerPage.locator(`form[action$="/admin/theme-marketplace/${fixture.theme_a_id}/install"]`).first();
    await confirmedForm.locator('input[name="confirm_overwrite"]').check();
    customerPage.once('dialog', (dialog) => dialog.accept());
    await Promise.all([
        customerPage.waitForNavigation({ waitUntil: 'domcontentloaded' }),
        confirmedForm.locator('button[type="submit"]').click(),
    ]);
    await customerPage.goto('/', { waitUntil: 'domcontentloaded' });
    await expect(customerPage.getByText('Assigned Real Theme is active')).toBeVisible();
    await customerPage.reload({ waitUntil: 'domcontentloaded' });
    await expect(customerPage.getByText('Assigned Real Theme is active')).toBeVisible();
    await responsiveShots(customerPage, 'theme-assignment-active');

    await grant(platformPage, fixture.site_a_id, fixture.theme_a_id);
    assignmentState = state();
    expect(assignmentState.site_a_platform_licenses).toBe(1);
    expect(assignmentState.site_a_theme_licenses).toBe(1);
    expect(assignmentState.site_a_assignments).toBe(1);

    await grant(platformPage, fixture.site_a_id, fixture.theme_b_id);
    assignmentState = state();
    expect(assignmentState.site_a_platform_licenses).toBe(1);
    expect(assignmentState.site_a_theme_licenses).toBe(2);
    expect(assignmentState.site_a_assignments).toBe(2);
    expect(assignmentState.site_b_theme_licenses).toBe(0);
    expect(assignmentState.site_b_assignments).toBe(0);
    expect(assignmentState.cross_site_theme_licenses).toBe(0);

    await platformContext.close();
    await customerContext.close();
});
