import { execFileSync, spawn } from 'node:child_process';
import { existsSync, mkdirSync, openSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { serverEnv } from '../fixtures/env.js';
import { killWhatIsListening, startServer, waitForServer } from '../helpers/server.js';

const __dirname = dirname(fileURLToPath(import.meta.url));
const customerRoot = join(__dirname, '..', '..', '..');
const platformRoot = join(customerRoot, '..', 'saas-platform');
const runtimeDir = join(customerRoot, 'tests', 'browser', '.runtime');
const fixturePath = join(runtimeDir, 'real-theme-assignment-fixture.json');
const platformPidPath = join(runtimeDir, 'real-platform.pid');

function platformEnv() {
    return {
        ...process.env,
        APP_ENV: 'testing',
        APP_URL: 'http://127.0.0.1:8792',
        DB_CONNECTION: 'mysql',
        DB_DATABASE: 'saas_platform_theme_assignment_staging',
        CACHE_STORE: 'file',
        SESSION_DRIVER: 'file',
        QUEUE_CONNECTION: 'sync',
        MAIL_MAILER: 'array',
    };
}

async function startPlatform() {
    killWhatIsListening(8792);
    const publicPath = join(platformRoot, 'public');
    const serverScript = existsSync(join(platformRoot, 'server.php'))
        ? join(platformRoot, 'server.php')
        : join(platformRoot, 'vendor', 'laravel', 'framework', 'src', 'Illuminate', 'Foundation', 'resources', 'server.php');
    const logFd = openSync(join(runtimeDir, 'real-platform.log'), 'a');
    const processHandle = spawn('php', ['-S', '127.0.0.1:8792', serverScript], {
        cwd: publicPath,
        env: platformEnv(),
        stdio: ['ignore', logFd, logFd],
        detached: false,
    });
    writeFileSync(platformPidPath, String(processHandle.pid));
    await waitForServer('127.0.0.1', 8792, '/login', 45_000);
}

export default async function globalSetup() {
    mkdirSync(runtimeDir, { recursive: true });

    const platformOutput = execFileSync('php', [join(platformRoot, 'tests', 'browser', 'theme-assignment', 'setup.php')], {
        cwd: platformRoot,
        env: platformEnv(),
        encoding: 'utf8',
        maxBuffer: 20 * 1024 * 1024,
    });
    const platformLine = platformOutput.split(/\r?\n/).find((line) => line.startsWith('THEME_ASSIGNMENT_FIXTURE:'));
    if (!platformLine) throw new Error(`Platform fixture was not emitted.\n${platformOutput}`);
    const platformFixture = JSON.parse(platformLine.slice('THEME_ASSIGNMENT_FIXTURE:'.length));

    const customerEnv = serverEnv();
    execFileSync('php', [join(customerRoot, 'tests', 'browser', 'scripts', 'ensure-database.php')], {
        cwd: customerRoot,
        env: customerEnv,
        encoding: 'utf8',
    });
    const customerOutput = execFileSync('php', ['artisan', 'browser-harness:setup', '--json'], {
        cwd: customerRoot,
        env: customerEnv,
        encoding: 'utf8',
        maxBuffer: 20 * 1024 * 1024,
    });
    const customerLine = customerOutput.split(/\r?\n/).find((line) => line.startsWith('BROWSER_HARNESS_JSON:'));
    if (!customerLine) throw new Error(`Customer fixture was not emitted.\n${customerOutput}`);

    writeFileSync(fixturePath, JSON.stringify({
        ...platformFixture,
        customer: JSON.parse(customerLine.slice('BROWSER_HARNESS_JSON:'.length)),
    }, null, 2));

    await startPlatform();
    await startServer();
}
