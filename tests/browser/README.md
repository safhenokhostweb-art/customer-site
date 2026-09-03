# Browser test harness (Playwright)

Phase 3E-H-D0. This is the canonical, reusable interactive browser test
harness for customer-site — Theme Builder / Extension SDK verification,
responsive checks, and future critical-UI regression all belong here.

It is separate from saas-platform's `scripts/headless/` Playwright scripts,
which are a narrow DOM/screenshot extractor for the Theme Reverse-Engineering
quality gate, not an interactive test framework.

## What it does

- `global-setup.js` provisions an **isolated** MySQL database
  (`customer_site_browser` by default — never the dev or PHPUnit database),
  via the real `php artisan browser-harness:setup` command: migrates fresh,
  seeds one deterministic admin user and one Builder-editable page, and
  ensures `storage/installed.lock` exists.
- It then starts a dedicated `php artisan serve` process on a fixed local
  port (`8791` by default), waits for it to answer, and hands off to the
  tests.
- `global-teardown.js` stops that server process.
- Tests log in through the **real** login form, open the **real** Builder
  route, and interact with the **real** UI — no auth bypass, no diagnostics
  substitute.

## Prerequisites

- `php` on `PATH` (same requirement `composer.json`'s own `setup` script
  already has).
- MySQL reachable with the same credentials as your local `.env`
  (`DB_HOST`/`DB_USERNAME`/`DB_PASSWORD`) — only the database name differs.
- Built frontend assets: `npm run build` (the harness serves the app in
  `local` env without a live Vite dev server, reading `public/build`).
- Playwright's Chromium browser: `npx playwright install chromium`.

## Running

```bash
npm run build
npx playwright install chromium   # first time only
npm run test:browser              # headless, CI-friendly
npm run test:browser:headed       # visible browser
npm run test:browser:debug        # Playwright inspector
```

Exit code is `0` on success. Non-interactive; no manual browser clicking
required.

## Configuration

All overridable via environment variables (see
`tests/browser/fixtures/env.js`):

| Variable               | Default                    |
|-------------------------|-----------------------------|
| `BROWSER_PORT`          | `8791`                      |
| `BROWSER_BASE_URL`      | `http://127.0.0.1:8791`     |
| `BROWSER_DB_DATABASE`   | `customer_site_browser`     |

## Artifacts

Screenshots (on failure, or explicitly captured by a test), traces (on
first retry), and the HTML report are written under
`tests/browser/.runtime/`, which is git-ignored. Nothing here is committed
as a golden artifact.

## Adding stable selectors

The Builder UI (`resources/views/builder/edit.blade.php`) is Alpine.js-driven
with few pre-existing stable selectors. Where one was missing for a critical
control, a semantically-neutral `data-testid` attribute was added (e.g.
`tb-save-button`, `tb-add-block-{type}`, `tb-prop-{key}`) rather than relying
on brittle CSS chains or the (Persian) visible text alone. Prefer reusing
these over adding new ones; if a new control genuinely has no stable
selector, add another narrowly-scoped `data-testid` rather than a CSS chain.

## Scope

`smoke.spec.js` proves the harness itself works (login → Builder → one real
built-in-block interaction → save → reload → persisted). It intentionally
does not exercise the Extension SDK (`ext:*` blocks, container nesting,
asset emission, unavailable/re-enable lifecycle, stored-XSS, responsive
Extension scenarios) — that is Phase 3E-H-D, built on top of this harness.
