import { defineConfig, devices } from '@playwright/test';
import { BASE_URL } from './tests/browser/fixtures/env.js';

/**
 * Phase 3E-H-D0 — canonical interactive browser-test configuration for
 * customer-site. Chromium only (H-D scope); database/server bootstrap is
 * handled explicitly by globalSetup/globalTeardown rather than Playwright's
 * built-in webServer option, so DB provisioning is guaranteed to finish
 * before the server (and any test) starts. See tests/browser/README.md.
 */
export default defineConfig({
    testDir: './tests/browser',
    outputDir: './tests/browser/.runtime/test-results',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    workers: 1,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never', outputFolder: 'tests/browser/.runtime/report' }]] : 'list',
    timeout: 60_000,
    expect: { timeout: 10_000 },
    globalSetup: './tests/browser/global-setup.js',
    globalTeardown: './tests/browser/global-teardown.js',
    use: {
        baseURL: BASE_URL,
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        video: 'off',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
});
