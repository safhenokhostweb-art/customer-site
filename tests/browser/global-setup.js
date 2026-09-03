import { execFileSync, spawn } from 'node:child_process';
import { existsSync, mkdirSync, openSync, writeFileSync } from 'node:fs';
import { get as httpGet } from 'node:http';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { BROWSER_PORT, serverEnv } from './fixtures/env.js';

const __dirname = dirname(fileURLToPath(import.meta.url));
const repoRoot = join(__dirname, '..', '..');
const runtimeDir = join(__dirname, '.runtime');
const fixturePath = join(runtimeDir, 'fixture.json');
const pidPath = join(runtimeDir, 'server.pid');

/**
 * Phase 3E-H-D0 — runs once before the whole browser test run:
 *  1. provisions the isolated browser-test database + deterministic
 *     admin/page fixtures via the real `browser-harness:setup` Artisan
 *     command (never touches the development or PHPUnit test database),
 *  2. starts a dedicated PHP built-in web server bound to a fixed local
 *     port, using only the isolated environment,
 *  3. waits until it actually answers before handing control to the tests.
 *
 * Deliberately does NOT rely on Playwright's built-in `webServer` option,
 * so database provisioning is guaranteed to finish before the server (and
 * therefore before any test) starts — no ordering ambiguity between the
 * two.
 *
 * Deliberately does NOT spawn `php artisan serve`: that command itself
 * spawns a *child* `php -S` process (Symfony Process, see
 * ServeCommand::startProcess()) and installs its own signal traps to stop
 * it gracefully — on Windows, a forceful kill of the `artisan serve`
 * wrapper does not reliably reach that child, leaking an orphaned server
 * bound to the port on every run. Spawning PHP's built-in server directly
 * (the same command `artisan serve` itself runs under the hood) makes the
 * PID Node gets back the actual server process, with no wrapper layer.
 */
export default async function globalSetup() {
    mkdirSync(runtimeDir, { recursive: true });

    killWhatIsListening(BROWSER_PORT);

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

    const publicPath = join(repoRoot, 'public');
    const serverScript = existsSync(join(repoRoot, 'server.php'))
        ? join(repoRoot, 'server.php')
        : join(repoRoot, 'vendor', 'laravel', 'framework', 'src', 'Illuminate', 'Foundation', 'resources', 'server.php');

    // server.php locates public/index.php via getcwd() (not the -t flag),
    // so the process's own working directory must be `public/` — exactly
    // what Laravel's own ServeCommand does (`new Process(..., public_path(), ...)`).
    //
    // OPcache is off by default for this PHP CLI build, which means every
    // request re-parses the entire framework from disk — observed at 8-11s
    // per request (even a bare login page) versus ~1s warm with OPcache
    // enabled. `-d zend_extension=opcache` loads it just for this process,
    // with default timestamp validation left on so edited files are still
    // picked up between runs.
    const logFd = openSync(join(runtimeDir, 'server.log'), 'w');
    const server = spawn('php', [
        '-d', 'zend_extension=opcache',
        '-d', 'opcache.enable=1',
        '-d', 'opcache.enable_cli=1',
        '-S', `127.0.0.1:${BROWSER_PORT}`,
        serverScript,
    ], {
        cwd: publicPath,
        env,
        stdio: ['ignore', logFd, logFd],
        detached: process.platform !== 'win32',
    });
    writeFileSync(pidPath, String(server.pid));

    // A real HTTP GET (not a bare TCP connect-and-close) — the PHP
    // built-in server handles one connection at a time; a bare TCP probe
    // is itself the exact "closed without sending a request... speculative
    // preconnection" pattern PHP logs, and occasionally left the server
    // needing to reap that connection right as the real browser's first
    // request arrived. A real GET with an explicit `Connection: close`,
    // whose full response body is read before resolving, avoids that and
    // also warms OPcache/bootstraps the app once before the browser's own
    // first navigation.
    await waitForServer('127.0.0.1', BROWSER_PORT, '/login', 30_000);
}

/** Kills whatever process (if any) is already listening on the given local port — leftover from a prior interrupted run. */
function killWhatIsListening(port) {
    if (process.platform === 'win32') {
        let out;
        try {
            out = execFileSync('netstat', ['-ano'], { encoding: 'utf8' });
        } catch {
            return;
        }
        const pids = new Set();
        for (const line of out.split('\n')) {
            if (line.includes(`127.0.0.1:${port} `) && line.includes('LISTENING')) {
                const pid = line.trim().split(/\s+/).pop();
                if (pid && pid !== '0') pids.add(pid);
            }
        }
        for (const pid of pids) {
            try {
                execFileSync('taskkill', ['/F', '/T', '/PID', pid], { stdio: 'ignore' });
            } catch {
                // already gone
            }
        }
    } else {
        try {
            const out = execFileSync('lsof', ['-ti', `tcp:${port}`], { encoding: 'utf8' });
            for (const pid of out.split('\n').filter(Boolean)) {
                try {
                    process.kill(Number(pid), 'SIGKILL');
                } catch {
                    // already gone
                }
            }
        } catch {
            // nothing listening / lsof unavailable
        }
    }
}

function waitForServer(host, port, path, timeoutMs) {
    return new Promise((resolve, reject) => {
        const start = Date.now();
        const attempt = () => {
            const req = httpGet({ host, port, path, headers: { Connection: 'close' }, timeout: 10_000 }, (res) => {
                res.resume(); // drain and let the connection close
                res.on('end', resolve);
            });
            req.on('timeout', () => req.destroy());
            req.on('error', () => {
                if (Date.now() - start > timeoutMs) {
                    reject(new Error(`Browser-test server did not become ready at ${host}:${port}${path} within ${timeoutMs}ms`));
                } else {
                    setTimeout(attempt, 300);
                }
            });
        };
        attempt();
    });
}
