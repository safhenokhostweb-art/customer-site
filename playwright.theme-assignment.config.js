import { defineConfig, devices } from '@playwright/test';

export default defineConfig({
    testDir: './tests/browser/theme-assignment-real',
    outputDir: './tests/browser/.runtime/theme-assignment-results',
    fullyParallel: false,
    workers: 1,
    retries: 0,
    reporter: 'list',
    timeout: 120_000,
    expect: { timeout: 15_000 },
    globalSetup: './tests/browser/theme-assignment-real/global-setup.js',
    globalTeardown: './tests/browser/theme-assignment-real/global-teardown.js',
    use: {
        baseURL: 'http://127.0.0.1:8791',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'off',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
