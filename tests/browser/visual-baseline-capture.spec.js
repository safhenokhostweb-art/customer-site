import { execFileSync } from 'node:child_process';
import { mkdirSync } from 'node:fs';
import { test } from '@playwright/test';
import { serverEnv } from './fixtures/env.js';

/**
 * TASK 3/3 Phase 6 — deterministic BEFORE/AFTER screenshot capture for the
 * coordinator's visual product review. Not a certification test (no
 * assertions) — installs the real canonical product exactly like the
 * other browser specs, then captures a fixed page/viewport matrix.
 *
 * Usage: SHOT_LABEL=before npx playwright test visual-baseline-capture.spec.js
 *        SHOT_LABEL=after  npx playwright test visual-baseline-capture.spec.js
 */
const label = process.env.SHOT_LABEL || 'unlabeled';
const outDir = `tests/browser/.runtime/visual-${label}`;
mkdirSync(outDir, { recursive: true });

const pages = [
    ['home', '/'],
    ['home-service', '/page/home-service'],
    ['home-ecommerce', '/page/home-ecommerce'],
    ['home-personal', '/page/home-personal'],
    ['home-magazine', '/page/home-magazine'],
    ['home-education', '/page/home-education'],
    ['about', '/page/about'],
    ['shop', '/page/shop'],
];

const viewports = [
    ['1366', { width: 1366, height: 900 }],
    ['768', { width: 768, height: 1024 }],
    ['390', { width: 390, height: 844 }],
];

test.describe.serial(`Visual capture (${label})`, () => {
    test.beforeAll(() => {
        execFileSync('php', [
            'artisan', 'browser-harness:setup', '--universal', '--json',
        ], { cwd: process.cwd(), env: serverEnv(), encoding: 'utf8' });
    });

    for (const [pageName, path] of pages) {
        for (const [vpName, viewport] of viewports) {
            test(`${pageName} @ ${vpName}`, async ({ page }) => {
                test.setTimeout(30_000);
                await page.setViewportSize(viewport);
                await page.goto(path, { waitUntil: 'domcontentloaded' });
                await page.waitForTimeout(600); // let in-flight same-origin images/fonts settle
                await page.screenshot({ path: `${outDir}/${pageName}__${vpName}.png`, fullPage: true });
            });
        }
    }
});
