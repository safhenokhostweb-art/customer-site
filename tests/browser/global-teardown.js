import { existsSync, renameSync, rmSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { stopServer } from './helpers/server.js';

const __dirname = dirname(fileURLToPath(import.meta.url));
const pidPath = join(__dirname, '.runtime', 'server.pid');
const hotPath = join(__dirname, '..', '..', 'public', 'hot');
const hotBackupPath = join(__dirname, '.runtime', 'public-hot.backup');

/** Phase 3E-H-D0/H-D — stops whichever server process is currently running (baseline or last-restarted). */
export default async function globalTeardown() {
    stopServer();
    rmSync(pidPath, { force: true });
    if (existsSync(hotBackupPath) && !existsSync(hotPath)) {
        renameSync(hotBackupPath, hotPath);
    }
}
