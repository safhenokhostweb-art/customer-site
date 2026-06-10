# ================================================================
# ai-worker.ps1 — Local AI Queue Worker
# پردازش درخواست‌های هوش مصنوعی از سرور — روی ویندوز اجرا می‌شود
# Usage: .\ai-worker.ps1
# ================================================================

$SERVER_URL    = "http://safhenokhost.ir"          # آدرس سایت مشتری
$QUEUE_TOKEN   = "ai-queue-secret-2026-XK9"        # باید با AI_QUEUE_TOKEN در .env سرور یکی باشد
$DEEPSEEK_KEY  = "sk-22cbabc9aa284678b5755e004aa150fa"
$DEEPSEEK_URL  = "https://api.deepseek.com/v1/chat/completions"
$POLL_INTERVAL = 15   # هر ۱۵ ثانیه poll می‌کند

$SYSTEM_PROMPTS = @{
    "article"         = "یک نویسنده حرفه‌ای فارسی هستی که مقالات جذاب و مفید می‌نویسی."
    "training"        = "یک مدرس حرفه‌ای هستی که آموزش‌های گام‌به‌گام واضح و کاربردی می‌نویسی."
    "workflow"        = "یک متخصص مدیریت فرآیند هستی که فرآیندهای کاری را به صورت شفاف مستند می‌کنی."
    "procedure"       = "یک متخصص مستندسازی هستی که روال‌ها و مراحل کاری را گام‌به‌گام می‌نویسی."
    "product_content" = "یک کپی‌رایتر حرفه‌ای هستی که توضیحات محصول جذاب و مزیت‌محور می‌نویسی."
    "help"            = "یک متخصص پشتیبانی فنی هستی که راهنماهای کاربری ساده می‌نویسی."
}

$TYPE_NAMES = @{
    "article"         = "مقاله"
    "training"        = "آموزش"
    "workflow"        = "فرآیند کاری"
    "procedure"       = "روال و مراحل"
    "product_content" = "محتوای محصول"
    "help"            = "راهنمای پنل"
}

function Call-DeepSeek($systemPrompt, $userPrompt, $jsonMode = $false) {
    $payload = @{
        model       = "deepseek-chat"
        messages    = @(
            @{ role = "system"; content = $systemPrompt },
            @{ role = "user";   content = $userPrompt   }
        )
        max_tokens  = 4096
        temperature = 0.7
    }
    if ($jsonMode) {
        $payload.response_format = @{ type = "json_object" }
    }

    $headers = @{
        "Authorization" = "Bearer $DEEPSEEK_KEY"
        "Content-Type"  = "application/json"
    }

    try {
        $response = Invoke-RestMethod -Uri $DEEPSEEK_URL `
            -Method POST `
            -Headers $headers `
            -Body ($payload | ConvertTo-Json -Depth 5) `
            -TimeoutSec 60
        return @{ success = $true; content = $response.choices[0].message.content }
    } catch {
        return @{ success = $false; error = $_.Exception.Message }
    }
}

function Process-Job($job) {
    $type       = $job.type
    $mode       = $job.mode
    $prompt     = $job.prompt
    $stepCount  = if ($job.step_count) { $job.step_count } else { 5 }
    $typeName   = if ($TYPE_NAMES[$type]) { $TYPE_NAMES[$type] } else { $type }
    $systemPr   = if ($SYSTEM_PROMPTS[$type]) { $SYSTEM_PROMPTS[$type] } else { "یک نویسنده حرفه‌ای فارسی هستی." }
    $systemPr  += " محتوا را به زبان فارسی روان، حرفه‌ای و کاربردی بنویس."

    if ($mode -eq "steps") {
        $userPrompt = "موضوع: $prompt`n`nلطفاً یک $typeName کامل با $stepCount مرحله بنویس.`nخروجی را به صورت JSON با این ساختار برگردان:`n{`n  `"title`": `"عنوان اصلی`",`n  `"excerpt`": `"خلاصه یک پاراگراف`",`n  `"steps`": [`n    {`"title`": `"عنوان مرحله`", `"body`": `"توضیح کامل مرحله`"}`n  ]`n}"
        $result = Call-DeepSeek $systemPr $userPrompt $true
    } else {
        $userPrompt = "یک $typeName کامل و حرفه‌ای درباره موضوع زیر بنویس:`n`n$prompt`n`nمحتوا باید شامل مقدمه، بدنه اصلی با جزئیات کافی، و نتیجه‌گیری باشد. از تیترهای واضح و پاراگراف‌بندی مناسب استفاده کن."
        $result = Call-DeepSeek $systemPr $userPrompt
    }

    return $result
}

function Send-Result($jobId, $result) {
    $headers = @{
        "Authorization" = "Bearer $QUEUE_TOKEN"
        "Content-Type"  = "application/json"
        "Accept"        = "application/json"
    }

    $payload = if ($result.success) {
        @{ success = $true; result = $result.content; provider = "deepseek-local" }
    } else {
        @{ success = $false; error = $result.error }
    }

    try {
        Invoke-RestMethod -Uri "$SERVER_URL/api/ai-queue/$jobId/complete" `
            -Method POST `
            -Headers $headers `
            -Body ($payload | ConvertTo-Json) `
            -TimeoutSec 15 | Out-Null
        return $true
    } catch {
        Write-Host "  [ERROR] Send result failed: $($_.Exception.Message)" -ForegroundColor Red
        return $false
    }
}

function Get-PendingJobs {
    $headers = @{
        "Authorization" = "Bearer $QUEUE_TOKEN"
        "Accept"        = "application/json"
    }

    try {
        $response = Invoke-RestMethod -Uri "$SERVER_URL/api/ai-queue/pending" `
            -Method GET `
            -Headers $headers `
            -TimeoutSec 10
        return $response.jobs
    } catch {
        Write-Host "  [WARN] Poll failed: $($_.Exception.Message)" -ForegroundColor Yellow
        return @()
    }
}

# ──────────────────────────────────────────────────────────────────────────────
# Main Loop
# ──────────────────────────────────────────────────────────────────────────────

Write-Host ""
Write-Host "╔══════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║       AI Queue Worker — Running           ║" -ForegroundColor Cyan
Write-Host "║  Ctrl+C to stop                          ║" -ForegroundColor Cyan
Write-Host "╚══════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""
Write-Host "Server : $SERVER_URL" -ForegroundColor Gray
Write-Host "Poll   : every $POLL_INTERVAL seconds" -ForegroundColor Gray
Write-Host ""

while ($true) {
    $timestamp = Get-Date -Format "HH:mm:ss"
    Write-Host "[$timestamp] Polling..." -NoNewline -ForegroundColor DarkGray

    $jobs = Get-PendingJobs

    if ($jobs -and $jobs.Count -gt 0) {
        Write-Host " $($jobs.Count) job(s) found" -ForegroundColor Green

        foreach ($job in $jobs) {
            Write-Host "  → Processing job #$($job.id) [$($job.type)/$($job.mode)]: $($job.prompt.Substring(0, [Math]::Min(50, $job.prompt.Length)))..." -ForegroundColor White

            $result = Process-Job $job
            $sent   = Send-Result $job.id $result

            if ($result.success -and $sent) {
                Write-Host "    ✓ Done" -ForegroundColor Green
            } else {
                Write-Host "    ✗ Failed: $($result.error)" -ForegroundColor Red
            }
        }
    } else {
        Write-Host " no jobs" -ForegroundColor DarkGray
    }

    Start-Sleep -Seconds $POLL_INTERVAL
}
