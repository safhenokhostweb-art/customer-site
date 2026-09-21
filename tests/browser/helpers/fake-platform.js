import { execFileSync, spawn } from 'node:child_process';
import { existsSync, mkdirSync, openSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { killWhatIsListening, waitForServer } from './server.js';

const __dirname = dirname(fileURLToPath(import.meta.url));
const browserDir = join(__dirname, '..');
const runtimeDir = join(browserDir, '.runtime');
const pidPath = join(runtimeDir, 'fake-platform.pid');
const port = Number(process.env.BROWSER_PLATFORM_PORT || 8792);

export async function startFakePlatform() {
    mkdirSync(runtimeDir, { recursive: true });
    killWhatIsListening(port);
    const logFd = openSync(join(runtimeDir, 'fake-platform.log'), 'a');
    const child = spawn(process.execPath, [join(browserDir, 'scripts', 'fake-platform-server.js')], {
        env: { ...process.env, BROWSER_PLATFORM_PORT: String(port) },
        stdio: ['ignore', logFd, logFd],
        detached: process.platform !== 'win32',
    });
    writeFileSync(pidPath, String(child.pid));
    await waitForServer('127.0.0.1', port, '/health', 15_000);
}

export function stopFakePlatform() {
    if (!existsSync(pidPath)) return;
    const pid = Number(readFileSync(pidPath, 'utf8').trim());
    if (!pid) return;
    try {
        if (process.platform === 'win32') execFileSync('taskkill', ['/F', '/T', '/PID', String(pid)], { stdio: 'ignore' });
        else process.kill(-pid, 'SIGTERM');
    } catch {
        // already stopped
    }
}
