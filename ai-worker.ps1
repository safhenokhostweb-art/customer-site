# AI Queue Worker - SaaS Platform
# Usage: .\ai-worker.ps1

$SERVER_URL    = "http://safhenokhost.ir"
$QUEUE_TOKEN   = "ai-queue-secret-2026-XK9"
$DEEPSEEK_KEY  = "sk-22cbabc9aa284678b5755e004aa150fa"
$DEEPSEEK_URL  = "https://api.deepseek.com/v1/chat/completions"
$POLL_INTERVAL = 15

$SYSTEM_PROMPTS = @{
    "article"         = "You are a professional Persian writer. Write engaging and useful content in fluent Persian."
    "training"        = "You are a professional trainer. Write clear, step-by-step Persian tutorials."
    "workflow"        = "You are a process management expert. Document workflows clearly in Persian."
    "procedure"       = "You are a documentation expert. Write detailed step-by-step procedures in Persian."
    "product_content" = "You are a professional copywriter. Write compelling Persian product descriptions."
    "help"            = "You are a technical support expert. Write simple Persian user guides."
}

$TYPE_NAMES = @{
    "article"         = "article"
    "training"        = "training"
    "workflow"        = "workflow"
    "procedure"       = "procedure"
    "product_content" = "product content"
    "help"            = "help guide"
}

function Call-DeepSeek {
    param($SystemPrompt, $UserPrompt, $JsonMode = $false)

    $payload = @{
        model       = "deepseek-chat"
        messages    = @(
            @{ role = "system"; content = $SystemPrompt },
            @{ role = "user";   content = $UserPrompt }
        )
        max_tokens  = 4096
        temperature = 0.7
    }
    if ($JsonMode) {
        $payload.response_format = @{ type = "json_object" }
    }

    $headers = @{
        "Authorization" = "Bearer $DEEPSEEK_KEY"
        "Content-Type"  = "application/json"
    }

    try {
        $body     = $payload | ConvertTo-Json -Depth 5
        $response = Invoke-RestMethod -Uri $DEEPSEEK_URL `
            -Method POST -Headers $headers -Body $body -TimeoutSec 60
        return @{ success = $true; content = $response.choices[0].message.content }
    } catch {
        return @{ success = $false; error = $_.Exception.Message }
    }
}

function Process-Job {
    param($Job)

    $type      = $Job.type
    $mode      = $Job.mode
    $prompt    = $Job.prompt
    $stepCount = if ($Job.step_count) { $Job.step_count } else { 5 }
    $typeName  = if ($TYPE_NAMES[$type]) { $TYPE_NAMES[$type] } else { $type }
    $sysPr     = if ($SYSTEM_PROMPTS[$type]) { $SYSTEM_PROMPTS[$type] } else { "You are a professional Persian writer." }
    $sysPr    += " Always write content in fluent, professional Persian (Farsi)."

    if ($mode -eq "steps") {
        $userPr = "Topic: $prompt`n`nWrite a complete $typeName with $stepCount steps in Persian.`nReturn JSON with this structure:`n{`n  `"title`": `"main title in Persian`",`n  `"excerpt`": `"one paragraph summary in Persian`",`n  `"steps`": [`n    {`"title`": `"step title in Persian`", `"body`": `"full step description in Persian`"}`n  ]`n}`nMake sure ALL text is in Persian language."
        $result = Call-DeepSeek -SystemPrompt $sysPr -UserPrompt $userPr -JsonMode $true
    } else {
        $userPr = "Write a complete, professional $typeName in Persian about:`n`n$prompt`n`nInclude an introduction, detailed main body, and conclusion. Use clear headings and proper paragraphs. ALL content must be in Persian."
        $result = Call-DeepSeek -SystemPrompt $sysPr -UserPrompt $userPr
    }

    return $result
}

function Send-Result {
    param($JobId, $Result)

    $headers = @{
        "Authorization" = "Bearer $QUEUE_TOKEN"
        "Content-Type"  = "application/json"
        "Accept"        = "application/json"
    }

    $payload = if ($Result.success) {
        @{ success = $true; result = $Result.content; provider = "deepseek-local" }
    } else {
        @{ success = $false; error = $Result.error }
    }

    try {
        $body = $payload | ConvertTo-Json
        Invoke-RestMethod -Uri "$SERVER_URL/api/ai-queue/$JobId/complete" `
            -Method POST -Headers $headers -Body $body -TimeoutSec 15 | Out-Null
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
            -Method GET -Headers $headers -TimeoutSec 10
        return $response.jobs
    } catch {
        Write-Host "  [WARN] Poll failed: $($_.Exception.Message)" -ForegroundColor Yellow
        return @()
    }
}

# ── Main Loop ─────────────────────────────────────────────────────────────────

Write-Host "AI Queue Worker - Running (Ctrl+C to stop)" -ForegroundColor Cyan
Write-Host "Server : $SERVER_URL" -ForegroundColor Gray
Write-Host "Poll   : every $POLL_INTERVAL seconds" -ForegroundColor Gray
Write-Host ""

while ($true) {
    $ts   = Get-Date -Format "HH:mm:ss"
    $jobs = Get-PendingJobs

    if ($jobs -and $jobs.Count -gt 0) {
        Write-Host "[$ts] $($jobs.Count) job(s)" -ForegroundColor Green
        foreach ($job in $jobs) {
            $preview = $job.prompt.Substring(0, [Math]::Min(40, $job.prompt.Length))
            Write-Host "  -> Job #$($job.id) [$($job.type)]: $preview..." -ForegroundColor White
            $result = Process-Job -Job $job
            $sent   = Send-Result -JobId $job.id -Result $result
            if ($result.success -and $sent) {
                Write-Host "     Done" -ForegroundColor Green
            } else {
                Write-Host "     Failed: $($result.error)" -ForegroundColor Red
            }
        }
    } else {
        Write-Host "[$ts] no jobs" -ForegroundColor DarkGray
    }

    Start-Sleep -Seconds $POLL_INTERVAL
}
