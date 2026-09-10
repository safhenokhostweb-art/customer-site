import { execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, renameSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { serverEnv } from './fixtures/env.js';
import { startServer } from './helpers/server.js';

const __dirname = dirname(fileURLToPath(import.meta.url));
const repoRoot = join(__dirname, '..', '..');
const runtimeDir = join(__dirname, '.runtime');
const fixturePath = join(runtimeDir, 'fixture.json');
const hotPath = join(repoRoot, 'public', 'hot');
const hotBackupPath = join(runtimeDir, 'public-hot.backup');

/**
 * Phase 3E-H-D0 — runs once before the whole browser test run:
 *  1. provisions the isolated browser-test database + deterministic
 *     admin/page fixtures via the real `browser-harness:setup` Artisan
 *     command (never touches the development or PHPUnit test database),
 *  2. starts the PHP built-in server with no extension providers (the D0
 *     baseline — matches production defaults exactly),
 *  3. waits until it actually answers before handing control to the tests.
 *
 * Phase 3E-H-D: server start/stop itself now lives in helpers/server.js so
 * individual Extension SDK tests can restart the server with
 * BROWSER_TEST_EXTENSION_PROVIDERS set — this file's own job stays exactly
 * D0's: provision once, start the baseline (no-extension) server once.
 *
 * Deliberately does NOT rely on Playwright's built-in `webServer` option,
 * so database provisioning is guaranteed to finish before the server (and
 * therefore before any test) starts — no ordering ambiguity between the
 * two.
 */
export default async function globalSetup() {
    mkdirSync(runtimeDir, { recursive: true });

    // A developer Vite marker must not make the isolated browser server
    // request assets from a dev server that is not part of this harness.
    // Preserve it byte-for-byte and restore it in global-teardown.js.
    if (existsSync(hotPath) && !existsSync(hotBackupPath)) {
        renameSync(hotPath, hotBackupPath);
    }

    const env = serverEnv();

    // The database must exist before `artisan` boots at all — Laravel's own
    // boot process queries it before any command's handle() runs.
    execFileSync('php', [join(__dirname, 'scripts', 'ensure-database.php')], {
        cwd: repoRoot,
        env,
        encoding: 'utf8',
    });

    const setupOutput = execFileSync('php', ['artisan', 'browser-harness:setup', '--json'], {
        cwd: repoRoot,
        env,
        encoding: 'utf8',
    });

    const jsonLine = setupOutput.split('\n').find((l) => l.startsWith('BROWSER_HARNESS_JSON:'));
    if (!jsonLine) {
        throw new Error(`browser-harness:setup did not print its JSON summary. Output:\n${setupOutput}`);
    }
    const fixture = JSON.parse(jsonLine.slice('BROWSER_HARNESS_JSON:'.length));
    writeFileSync(fixturePath, JSON.stringify(fixture, null, 2));

    await startServer();
}
