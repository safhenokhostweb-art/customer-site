import { rmSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { stopServer } from './helpers/server.js';

const __dirname = dirname(fileURLToPath(import.meta.url));
const pidPath = join(__dirname, '.runtime', 'server.pid');

/** Phase 3E-H-D0/H-D — stops whichever server process is currently running (baseline or last-restarted). */
export default async function globalTeardown() {
    stopServer();
    rmSync(pidPath, { force: true });
}
