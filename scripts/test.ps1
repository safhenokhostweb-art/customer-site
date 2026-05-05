Param(
    [string]$PhpBin = "",
    [string]$ProjectRoot = ""
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($ProjectRoot)) {
    $ProjectRoot = (Resolve-Path "$PSScriptRoot/..").Path
}

if ([string]::IsNullOrWhiteSpace($PhpBin)) {
    if ($env:PHP_BIN) {
        $PhpBin = $env:PHP_BIN
    } elseif (Get-Command php -ErrorAction SilentlyContinue) {
        $PhpBin = "php"
    } elseif (Test-Path "C:\laragon\bin\php\php-8.4.16-nts-Win32-vs17-x64\php.exe") {
        $PhpBin = "C:\laragon\bin\php\php-8.4.16-nts-Win32-vs17-x64\php.exe"
    } else {
        throw "PHP binary not found. Set PHP_BIN or pass -PhpBin."
    }
}

Push-Location $ProjectRoot
try {
    & $PhpBin artisan config:clear
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

    & $PhpBin artisan test
    exit $LASTEXITCODE
}
finally {
    Pop-Location
}
