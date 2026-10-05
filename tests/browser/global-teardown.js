import { execFileSync } from 'node:child_process';
import { existsSync, renameSync, rmSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { stopServer } from './helpers/server.js';
import { stopFakePlatform } from './helpers/fake-platform.js';
import { serverEnv } from './fixtures/env.js';

const __dirname = dirname(fileURLToPath(import.meta.url));
const pidPath = join(__dirname, '.runtime', 'server.pid');
const hotPath = join(__dirname, '..', '..', 'public', 'hot');
const hotBackupPath = join(__dirname, '.runtime', 'public-hot.backup');
const foodMartFixtureMedia = join(__dirname, '..', '..', 'public', 'uploads', 'foodmart-binding-fixtures');
const repoRoot = join(__dirname, '..', '..');

/** Phase 3E-H-D0/H-D — stops whichever server process is currently running (baseline or last-restarted). */
export default async function globalTeardown() {
    stopServer();
    stopFakePlatform();
    execFileSync('php', [join(__dirname, 'scripts', 'cleanup-foodmart.php')], {
        cwd: repoRoot,
        env: serverEnv(),
        encoding: 'utf8',
    });
    rmSync(pidPath, { force: true });
    rmSync(foodMartFixtureMedia, { recursive: true, force: true });
    if (existsSync(hotBackupPath) && !existsSync(hotPath)) {
        renameSync(hotBackupPath, hotPath);
    }
}
