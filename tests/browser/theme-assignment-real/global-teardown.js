import { execFileSync } from 'node:child_process';
import { existsSync, readFileSync, rmSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { stopServer } from '../helpers/server.js';

const __dirname = dirname(fileURLToPath(import.meta.url));
const runtimeDir = join(__dirname, '..', '.runtime');
const platformPidPath = join(runtimeDir, 'real-platform.pid');

export default async function globalTeardown() {
    stopServer();
    if (existsSync(platformPidPath)) {
        const pid = readFileSync(platformPidPath, 'utf8').trim();
        if (pid) {
            try {
                if (process.platform === 'win32') execFileSync('taskkill', ['/F', '/T', '/PID', pid], { stdio: 'ignore' });
                else process.kill(Number(pid), 'SIGTERM');
            } catch {
                // already stopped
            }
        }
    }
    rmSync(platformPidPath, { force: true });
}
