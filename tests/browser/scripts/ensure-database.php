<?php

/**
 * Phase 3E-H-D0 — creates the isolated browser-test MySQL database if it
 * doesn't already exist yet, using ONLY Composer's autoloader + Dotenv
 * (never booting the full Laravel framework). This runs before
 * `php artisan browser-harness:setup`, because Laravel's own boot process
 * queries the configured database before any command's handle() method
 * runs — so the database must already exist before `artisan` is invoked at
 * all, not merely before the command's own body executes.
 *
 * DB_DATABASE (and any other DB_* value) is read from the real process
 * environment first, falling back to the project .env — matching Laravel's
 * own env() precedence — so the caller's isolated-database override always
 * wins over whatever the developer's own .env points at.
 */

require __DIR__ . '/../../../vendor/autoload.php';

$root = dirname(__DIR__, 3);
if (file_exists($root . '/.env')) {
    Dotenv\Dotenv::createImmutable($root)->load();
}

$host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? '127.0.0.1');
$port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306');
$user = getenv('DB_USERNAME') ?: ($_ENV['DB_USERNAME'] ?? 'root');
$pass = getenv('DB_PASSWORD') ?: ($_ENV['DB_PASSWORD'] ?? '');
$database = getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? '');

if ($database === '' || !str_contains($database, 'browser')) {
    fwrite(STDERR, "Refusing to run: DB_DATABASE (\"{$database}\") does not look like the dedicated browser-test database.\n");
    exit(1);
}

$pdo = new PDO("mysql:host={$host};port={$port}", $user, $pass);
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

echo "Database \"{$database}\" ensured.\n";
