import { execFileSync } from 'node:child_process';
import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';
import { serverEnv } from './fixtures/env.js';

/**
 * saas ADR-082 step 5 on the customer host: axe-core (WCAG 2 A/AA and
 * best-practice rules) over nav_header as this host renders and styles it,
 * closed and with the phone drawer open, on a phone and on a desktop with a
 * mega menu focused from the keyboard. Also the empty top bar of the live
 * home page: no burger, no navigation landmark. Automated rules cover part
 * of WCAG only; the keyboard behaviour itself is header-a11y.spec.ts in
 * saas-platform.
 */

const ORIGIN = 'http://tb-header-axe.test';
const links = [['خانه', '/'], ['فروشگاه', '/shop'], ['وبلاگ', '/blog']].map(([label, url]) => ({ label, url }));
const mega = {
    label: 'محصولات', url: '/shop', mega_show_desktop: true, mega_mobile_behavior: 'accordion',
    mega_columns: [{ title: 'دسته‌ها', items: [{ label: 'موبایل', url: '/shop/m' }, { label: 'لپ‌تاپ', url: '/shop/l' }] }],
};
const VARIANTS = {
    full: {
        logo_text: 'برند', nav_links: [...links, mega], bg_color: '#ffffff', text_color: '#0f172a',
        show_search: true, search_mode: 'icon', show_cart: true, show_account: true, phone: '۰۲۱۱۲۳۴۵۶۷۸', cta_text: 'شروع کنید', cta_url: '/start',
        social_links: [{ network: 'bale', url: 'https://ble.ir/x' }], social_show_mobile: true, sticky: true,
    },
    topbar: {
        logo_text: 'برند', nav_links: [{ label: null, url: '/' }, { label: '', url: '/shop' }], menu_align: 'center',
        cta_text: 'شروع کنید', cta_url: '/start', bg_color: '#ffffff', text_color: '#0f172a', menu_aria_label: 'نوار بالا',
    },
};

function render() {
    const source = `
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\\Contracts\\Console\\Kernel::class)->bootstrap();
$out = [];
foreach (json_decode(stream_get_contents(STDIN), true) as $name => $props) {
    $r = new App\\Services\\ThemeBuilder\\BlockRenderer();
    $r->setMode('public');
    $header = $r->renderBlock(['type' => 'nav_header', 'id' => 'hdr', 'props' => $props]);
    $out[$name] = '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>هدر</title><style>'
        . view('theme-runtime::css.blocks')->render() . 'body{margin:0;font-family:Tahoma,sans-serif}</style></head><body>'
        . '<header class="canvas-site-header tb-site-header" id="site-header">' . $header . '</header>'
        . '<main id="main"><h1>صفحه</h1><p style="height:150vh">محتوا</p></main></body></html>';
}
echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
`;
    return JSON.parse(execFileSync('php', ['-r', source], { cwd: process.cwd(), env: serverEnv(), encoding: 'utf8', input: JSON.stringify(VARIANTS), maxBuffer: 32 * 1024 * 1024 }));
}

async function open(page, html, width) {
    await page.unrouteAll({ behavior: 'ignoreErrors' });
    await page.route('**/*', (route) => (new URL(route.request().url()).origin === ORIGIN
        ? route.fulfill({ contentType: 'text/html; charset=utf-8', body: html })
        : route.abort()));
    await page.setViewportSize({ width, height: 760 });
    await page.goto(`${ORIGIN}/`);
}

async function axe(page, label) {
    const results = await new AxeBuilder({ page }).include('#site-header').withTags(['wcag2a', 'wcag2aa', 'best-practice']).analyze();
    const found = results.violations.map((v) => `${v.id} (${v.impact}, ${v.nodes.length}): ${v.nodes.map((n) => n.target.join(' ')).slice(0, 3).join(' ; ')}`);
    expect(found, `${label}: axe violations`).toEqual([]);
    return results.passes.length;
}

test.describe('nav_header accessibility on the customer host (saas ADR-082 step 5)', () => {
    let pages;
    test.beforeAll(() => { pages = render(); });

    test('axe: phone closed and with the drawer open', async ({ page }) => {
        await open(page, pages.full, 360);
        expect(await axe(page, 'phone, closed')).toBeGreaterThan(0);
        await page.locator('#tb-hdr .tb-nav-header-toggle').focus();
        await page.keyboard.press('Enter');
        await expect(page.locator('#tb-hdr-menu-panel')).toHaveAttribute('role', 'dialog');
        await expect(page.locator('#tb-hdr-menu-panel')).toHaveAttribute('aria-label', 'منوی اصلی');
        await axe(page, 'phone, drawer open');
        await page.keyboard.press('Escape');
        await expect(page.locator('#tb-hdr .tb-nav-header-toggle')).toBeFocused();
    });

    test('axe: desktop with the mega menu opened from the keyboard', async ({ page }) => {
        await open(page, pages.full, 1280);
        await axe(page, 'desktop');
        await page.locator('#tb-hdr .tb-mega-menu-trigger').focus();
        await expect(page.locator('#tb-hdr .tb-mega-menu-trigger')).toHaveAttribute('aria-expanded', 'true');
        await axe(page, 'desktop, mega open');
    });

    test('the empty top bar: no burger, no navigation landmark, and still clean', async ({ page }) => {
        for (const width of [360, 1280]) {
            await open(page, pages.topbar, width);
            await expect(page.locator('#tb-hdr .tb-nav-header-toggle')).toHaveCount(0);
            await expect(page.locator('#tb-hdr nav')).toHaveCount(0);
            await axe(page, `top bar ${width}`);
        }
    });
});
