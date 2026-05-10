<#
.SYNOPSIS
  Invokes saas-platform/scripts/sync-cursor-rules.ps1 (single implementation).

.DESCRIPTION
  Saa repo path resolution:
  1) CURSOR_RULES_SAAS_PLATFORM_ROOT
  2) cursor-rules-sync.local.json in this repo: "saasPlatformRoot"
  3) Sibling ../saas-platform
#>
param(
    [switch]$Reverse,
    [switch]$DeleteOrphans
)

$ErrorActionPreference = 'Stop'

function Get-EnvPath([string]$name) {
    foreach ($scope in @('Process', 'User', 'Machine')) {
        $v = [Environment]::GetEnvironmentVariable($name, $scope)
        if ($v -and $v.Trim()) { return $v.Trim() }
    }
    return $null
}

function Read-JsonPath([string]$file, [string]$property) {
    if (-not (Test-Path -LiteralPath $file)) { return $null }
    try {
        $j = Get-Content -LiteralPath $file -Raw -Encoding UTF8 | ConvertFrom-Json
        $val = $j.$property
        if ($val -and $val.ToString().Trim()) { return $val.ToString().Trim() }
    } catch {
        Write-Warning "Invalid JSON: $file - $_"
    }
    return $null
}

$customerRoot = Split-Path -Parent $PSScriptRoot
$parentDir = Split-Path -Parent $customerRoot

$mainRoot = Get-EnvPath 'CURSOR_RULES_SAAS_PLATFORM_ROOT'
if (-not $mainRoot) {
    $mainRoot = Read-JsonPath (Join-Path $customerRoot 'cursor-rules-sync.local.json') 'saasPlatformRoot'
}
if (-not $mainRoot) {
    $mainRoot = Join-Path $parentDir 'saas-platform'
}

$mainScript = Join-Path $mainRoot 'scripts\sync-cursor-rules.ps1'

if (-not (Test-Path -LiteralPath $mainScript)) {
    $cfgPath = Join-Path $customerRoot 'cursor-rules-sync.local.json'
    throw (
        "Main script not found: $mainScript`n`n" +
        "Set CURSOR_RULES_SAAS_PLATFORM_ROOT (env) or create $cfgPath with saasPlatformRoot.`n" +
        'See docs/CURSOR-RULES-SYNC.md'
    )
}

$extra = @()
if ($DeleteOrphans) {
    $extra += '-DeleteOrphans'
}

if ($Reverse) {
    & $mainScript -Reverse @extra
} else {
    & $mainScript @extra
}
