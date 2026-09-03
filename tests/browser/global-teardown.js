import { execFileSync } from 'node:child_process';
import { existsSync, readFileSync, rmSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const pidPath = join(__dirname, '.runtime', 'server.pid');

/** Phase 3E-H-D0 — stops the dedicated `php artisan serve` process started by global-setup.js. */
export default async function globalTeardown() {
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

    rmSync(pidPath, { force: true });
}
