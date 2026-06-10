# AI Worker Auto-Launcher
# این فایل را به Startup اضافه کنید تا worker هنگام ورود به ویندوز خودکار شروع شود

$workerPath = "c:\laragon\www\customer-site\ai-worker.ps1"
$logPath    = "c:\laragon\www\customer-site\ai-worker.log"

# اگر worker در حال اجرا است، خارج شو
$running = Get-Process powershell -ErrorAction SilentlyContinue | Where-Object {
    (Get-WmiObject Win32_Process -Filter "ProcessId=$($_.Id)" -ErrorAction SilentlyContinue).CommandLine -like "*ai-worker.ps1*"
}
if ($running) {
    Write-Host "AI Worker is already running (PID $($running.Id))"
    exit 0
}

Write-Host "Starting AI Worker..."
Start-Process powershell -ArgumentList "-NoProfile -WindowStyle Minimized -File `"$workerPath`"" -PassThru | ForEach-Object {
    Write-Host "AI Worker started (PID $($_.Id))"
}
