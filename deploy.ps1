# ================================================================
# deploy.ps1 — Customer Site Deploy Script
# Usage: .\deploy.ps1 "توضیح تغییر"
# vendor از لوکال آپلود می‌شود — سرور نیازی به دانلود ندارد
# ================================================================

param([string]$Message = "deploy: $(Get-Date -Format 'yyyy-MM-dd HH:mm')")

$SSH_KEY    = "$env:USERPROFILE\.ssh\deploy_saas"
$SERVER     = "root@87.107.12.81"
$PORT       = "9011"
$DIR        = "c:\laragon\www\customer-site"
$ZIPFILE    = "$env:TEMP\deploy_customer_$(Get-Date -Format 'yyyyMMddHHmmss').zip"
$PHP        = "C:\laragon\bin\php\php-8.4.16-nts-Win32-vs17-x64\php.exe"
$COMPOSER   = "C:\laragon\bin\composer\composer.phar"

Set-Location $DIR

Write-Host "`n=== Customer Site Deploy ===" -ForegroundColor Cyan
Write-Host "Message: $Message" -ForegroundColor Gray

# ── ۱. Git commit + push ──────────────────────────────────────
Write-Host "`n[1/4] Git commit & push..." -ForegroundColor Yellow
git add -A
$status = git status --porcelain
if ($status) {
    git commit -m $Message
    git push origin main
    Write-Host "     Pushed to GitHub" -ForegroundColor Green
} else {
    Write-Host "     No changes to commit" -ForegroundColor Gray
}

# ── ۲. Build vendor + Archive ────────────────────────────────
Write-Host "`n[2/4] Building vendor & creating archive..." -ForegroundColor Yellow
& $PHP $COMPOSER install --no-dev --optimize-autoloader --no-scripts --no-interaction --quiet

# Sync local saas-platform packages into vendor (path-repositories are not resolved by composer on server)
$PLATFORM_DIR = "c:\laragon\www\saas-platform"
$localPackages = @(
    'packages\saas-platform\core-utils'
)
foreach ($pkg in $localPackages) {
    $src = Join-Path $PLATFORM_DIR $pkg
    $parts  = $pkg -replace '\\','/' -split '/'
    $vendor = Join-Path $DIR "vendor\$($parts[-2])\$($parts[-1])"
    if ((Test-Path $src) -and (Test-Path $vendor)) {
        Write-Host "     Syncing $pkg → vendor" -ForegroundColor DarkGray
        robocopy $src $vendor /E /XD .git /NFL /NDL /NJH /NJS /NC /NS /NP | Out-Null
    }
}

if (Test-Path $ZIPFILE) { Remove-Item $ZIPFILE }

# Remove Vite dev-server hot file before packaging — it must NEVER reach production
$hotFile = Join-Path $DIR "public\hot"
$hotBackup = $null
if (Test-Path $hotFile) {
    $hotBackup = Get-Content $hotFile -Raw
    Remove-Item $hotFile -Force
    Write-Host "     Removed public/hot before archive" -ForegroundColor DarkGray
}

$exclude = @('.git', 'node_modules', 'tests', 'storage', '.env', '.env.local', '.env.testing')
$items   = Get-ChildItem -Path $DIR | Where-Object { $_.Name -notin $exclude }

# حذف فایل‌های کش لوکال قبل از آرشیو تا کانفیگ سرور بازنویسی نشود
$localCache = @('bootstrap\cache\config.php', 'bootstrap\cache\routes-v7.php', 'bootstrap\cache\events.php', 'bootstrap\cache\services.php')
foreach ($cf in $localCache) {
    $cfPath = Join-Path $DIR $cf
    if (Test-Path $cfPath) { Remove-Item $cfPath -Force }
}

Compress-Archive -Path ($items | ForEach-Object { $_.FullName }) -DestinationPath $ZIPFILE -Force
Write-Host "     Archive: $ZIPFILE ($('{0:N1}' -f ((Get-Item $ZIPFILE).Length / 1MB)) MB)" -ForegroundColor Green

# Restore hot file for local dev if it existed
if ($hotBackup) {
    Set-Content $hotFile $hotBackup -NoNewline
}

# ── ۳. Upload به سرور ────────────────────────────────────────
Write-Host "`n[3/4] Uploading to server..." -ForegroundColor Yellow
scp -P $PORT -i $SSH_KEY -o StrictHostKeyChecking=no $ZIPFILE "${SERVER}:/tmp/deploy_customer.zip"
Write-Host "     Uploaded" -ForegroundColor Green

# ── ۴. اجرای deploy روی سرور ─────────────────────────────────
Write-Host "`n[4/4] Running deploy on server..." -ForegroundColor Yellow
ssh -i $SSH_KEY -p $PORT -o StrictHostKeyChecking=no -o ServerAliveInterval=30 $SERVER "bash /var/www/deploy.sh customer"

Write-Host "`n=== Deploy Complete ===" -ForegroundColor Green
