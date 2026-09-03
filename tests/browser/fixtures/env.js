// Phase 3E-H-D0 — shared constants for the customer-site Playwright browser
// harness. These credentials/identifiers only ever exist in the isolated
// browser-test database (see BrowserHarnessSetupCommand), never in
// development or production data, so they are safe to keep in source.

import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));

/**
 * Reads the real, server-assigned fixture identifiers (admin mobile/page id)
 * written by global-setup.js after browser-harness:setup ran — never a
 * hardcoded DB id.
 */
export function loadFixture() {
    const path = join(__dirname, '..', '.runtime', 'fixture.json');
    return JSON.parse(readFileSync(path, 'utf8'));
}

export const BROWSER_PORT = Number(process.env.BROWSER_PORT || 8791);
export const BASE_URL = process.env.BROWSER_BASE_URL || `http://127.0.0.1:${BROWSER_PORT}`;
export const DB_DATABASE = process.env.BROWSER_DB_DATABASE || 'customer_site_browser';

export const ADMIN_MOBILE = '09120000001';
export const ADMIN_PASSWORD = 'browser-harness-pass';

export const BUILDER_PAGE_SLUG = 'browser-harness-page';
export const BUILDER_PAGE_TITLE = 'Browser Harness Page';

/** Environment variables injected into the `php artisan serve` child process. */
export function serverEnv() {
    return {
        ...process.env,
        APP_ENV: 'local',
        DB_DATABASE: DB_DATABASE,
        OWNER_SITE: 'true',
        SESSION_DRIVER: 'file',
        CACHE_STORE: 'array',
        QUEUE_CONNECTION: 'sync',
        MAIL_MAILER: 'array',
    };
}
