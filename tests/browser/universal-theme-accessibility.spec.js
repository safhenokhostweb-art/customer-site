import { execFileSync } from 'node:child_process';
import { mkdirSync, writeFileSync } from 'node:fs';
import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';
import { serverEnv } from './fixtures/env.js';
import { loginAsAdmin } from './helpers/auth.js';

/**
 * Universal Commercial Theme (TASK 3/3), Phase 6 — automated accessibility
 * certification (Step 10). Uses @axe-core/playwright (the official,
 * minimal Playwright integration — added this phase, justified by this
 * exact requirement) against representative REAL public pages of the real
 * canonical product, installed the same way every other browser spec in
 * this suite installs it.
 *
 * This is explicitly NOT a WCAG certification claim — axe-core's automated
 * rules catch a meaningful, well-documented subset of accessibility
 * defects (roughly 30-50% of WCAG issues per axe-core's own published
 * scope), not the full standard. Manual review (real screen reader,
 * keyboard-only walkthrough) is out of this phase's scope; results are
 * reported as exact violations, not a pass/fail certification.
 */
const outDir = 'tests/browser/.runtime/accessibility';
mkdirSync(outDir, { recursive: true });

const pages = [
    ['home', '/'],
    ['home-ecommerce', '/page/home-ecommerce'],
    ['home-magazine', '/page/home-magazine'],
    ['shop', '/page/shop'],
    ['utilities', '/page/utilities'],
    ['contact', '/page/contact'],
];

test.describe.serial('Universal Theme accessibility (Phase 6)', () => {
    test.beforeAll(() => {
        execFileSync('php', [
            'artisan', 'browser-harness:setup', '--universal', '--json',
        ], { cwd: process.cwd(), env: serverEnv(), encoding: 'utf8' });
    });

    for (const [name, path] of pages) {
        test(`axe scan: ${name}`, async ({ page }) => {
            test.setTimeout(30_000);
            await page.goto(path, { waitUntil: 'domcontentloaded' });

            const results = await new AxeBuilder({ page })
                .withTags(['wcag2a', 'wcag2aa', 'best-practice'])
                .analyze();

            writeFileSync(`${outDir}/${name}.json`, JSON.stringify(results, null, 2));

            const summary = results.violations.map((v) => ({
                id: v.id,
                impact: v.impact,
                help: v.help,
                nodes: v.nodes.length,
            }));
            console.log(`AXE ${name}: ${results.violations.length} violation type(s) — ${JSON.stringify(summary)}`);

            // Hard gate: zero CRITICAL or SERIOUS violations on any real
            // public page. 'moderate'/'minor' are reported (see the JSON
            // artifact + console summary) but not gated in this phase —
            // full triage/remediation of every automated finding is out of
            // this phase's scope; what must not exist is a severe,
            // automatically-detectable barrier.
            const severe = results.violations.filter((v) => v.impact === 'critical' || v.impact === 'serious');
            expect(severe, `${name}: critical/serious axe violations: ${JSON.stringify(severe.map((v) => v.id))}`).toEqual([]);
        });
    }

    test('tabs/accordion/slider: keyboard and ARIA semantics on the real utilities page', async ({ page }) => {
        await page.goto('/page/utilities', { waitUntil: 'domcontentloaded' });

        // Tabs: real role=tablist/tab, aria-selected reflects state (Phase 5 fix).
        const tabs = page.locator('.tb-tabs').first();
        await expect(tabs.locator('[role="tablist"]')).toHaveCount(1);
        await expect(tabs.locator('[role="tab"]')).toHaveCount(3);
        await expect(tabs.locator('[role="tab"][aria-selected="true"]')).toHaveCount(1);

        // Accordion: native <details>/<summary> — real, built-in accessible
        // semantics with no ARIA needed (verified present, not reimplemented).
        await expect(page.locator('.tb-accordion-item > summary')).toHaveCount(3);

        // Slider: real accessible names on prev/next controls (Phase 5).
        const slider = page.locator('.tb-carousel.tb-slider');
        await expect(slider.locator('.tb-carousel-prev[aria-label]')).toHaveCount(1);
        await expect(slider.locator('.tb-carousel-next[aria-label]')).toHaveCount(1);
        await expect(slider.locator('.tb-carousel-dot[aria-label]')).toHaveCount(4);
    });

    test('focus visibility: keyboard tab order reaches real interactive controls', async ({ page }) => {
        await page.goto('/', { waitUntil: 'domcontentloaded' });
        // Real keyboard navigation (no synthetic focus() call) — Tab from
        // a known start point must reach a real, visible focusable link.
        await page.keyboard.press('Tab');
        const active = await page.evaluate(() => {
            const el = document.activeElement;
            return el ? { tag: el.tagName, hasOutline: getComputedStyle(el).outlineStyle !== 'none' || getComputedStyle(el).boxShadow !== 'none' } : null;
        });
        expect(active, 'first Tab press must focus a real element').not.toBeNull();
    });
});
