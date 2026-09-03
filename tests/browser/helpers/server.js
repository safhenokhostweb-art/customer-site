import { execFileSync, spawn } from 'node:child_process';
import { existsSync, mkdirSync, openSync, readFileSync, writeFileSync } from 'node:fs';
import { get as httpGet } from 'node:http';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { BROWSER_PORT, serverEnv } from '../fixtures/env.js';

const __dirname = dirname(fileURLToPath(import.meta.url));
const repoRoot = join(__dirname, '..', '..', '..');
const runtimeDir = join(__dirname, '..', '.runtime');
const pidPath = join(runtimeDir, 'server.pid');

/**
 * Phase 3E-H-D — shared server lifecycle, factored out of D0's
 * global-setup.js so individual H-D tests can restart the PHP built-in
 * server with a different `BROWSER_TEST_EXTENSION_PROVIDERS` value mid-run
 * (e.g. to prove the unavailable-provider lifecycle: a provider's
 * (un)availability is fixed for the lifetime of one PHP process — the
 * built-in server re-executes the same booted env on every request, so
 * there is no in-process way to toggle it; a real restart is the honest
 * equivalent of "a new deploy without that provider registered").
 *
 * Deliberately does NOT spawn `php artisan serve` — see D0's ADR-042 for
 * why (its child-process wrapper does not reliably die on a forceful
 * Windows kill).
 */

export function killWhatIsListening(port = BROWSER_PORT) {
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

export function waitForServer(host, port, path, timeoutMs) {
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

/**
 * Starts the PHP built-in server with the isolated environment plus any
 * `extraEnv` overrides (e.g. BROWSER_TEST_EXTENSION_PROVIDERS), killing
 * anything already on the port first. Resolves once it actually answers.
 */
export async function startServer(extraEnv = {}) {
    mkdirSync(runtimeDir, { recursive: true });
    killWhatIsListening(BROWSER_PORT);

    const env = { ...serverEnv(), ...extraEnv };
    const publicPath = join(repoRoot, 'public');
    const serverScript = existsSync(join(repoRoot, 'server.php'))
        ? join(repoRoot, 'server.php')
        : join(repoRoot, 'vendor', 'laravel', 'framework', 'src', 'Illuminate', 'Foundation', 'resources', 'server.php');

    const logFd = openSync(join(runtimeDir, 'server.log'), 'a');
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

    await waitForServer('127.0.0.1', BROWSER_PORT, '/login', 30_000);
}

/** Restarts the server with a different environment — the real equivalent of a redeploy with different providers registered. */
export async function restartServer(extraEnv = {}) {
    await startServer(extraEnv);
}

export function stopServer() {
    if (!existsSync(pidPath)) return;
    const pid = Number(readFileSync(pidPath, 'utf8').trim());
    if (!pid) return;
    try {
        if (process.platform === 'win32') {
            execFileSync('taskkill', ['/F', '/T', '/PID', String(pid)], { stdio: 'ignore' });
        } else {
            process.kill(-pid, 'SIGTERM');
        }
    } catch {
        // already exited
    }
}
