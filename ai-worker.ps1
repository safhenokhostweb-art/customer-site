# AI Queue Worker - SaaS Platform
param()
$SERVER_URL    = "http://safhenokhost.ir"
$QUEUE_TOKEN   = "ai-queue-secret-2026-XK9"
$DEEPSEEK_KEY  = "sk-22cbabc9aa284678b5755e004aa150fa"
$DEEPSEEK_URL  = "https://api.deepseek.com/v1/chat/completions"
$POLL_INTERVAL = 15

function Invoke-JsonApi {
    param([string]$Uri, [string]$Method = "GET", [string]$Token = "", [string]$Body = "")
    try {
        $req         = [System.Net.HttpWebRequest]::Create($Uri)
        $req.Method  = $Method
        $req.Accept  = "application/json"
        $req.Timeout = 30000
        if ($Token -ne "") { $req.Headers["Authorization"] = "Bearer $Token" }
        if ($Body -ne "") {
            $bytes = [System.Text.Encoding]::UTF8.GetBytes($Body)
            $req.ContentType   = "application/json; charset=utf-8"
            $req.ContentLength = $bytes.Length
            $s = $req.GetRequestStream()
            $s.Write($bytes, 0, $bytes.Length)
            $s.Close()
        }
        $resp   = $req.GetResponse()
        $reader = [System.IO.StreamReader]::new($resp.GetResponseStream(), [System.Text.Encoding]::UTF8)
        $text   = $reader.ReadToEnd()
        $reader.Close()
        return @{ ok = $true; body = $text }
    } catch {
        $errMsg = $_.Exception.Message
        try {
            $errResp = $_.Exception.Response
            if ($errResp) {
                $r2 = [System.IO.StreamReader]::new($errResp.GetResponseStream())
                $errMsg = $errMsg + " | " + $r2.ReadToEnd()
            }
        } catch {}
        return @{ ok = $false; error = $errMsg }
    }
}

function Get-PendingJobs {
    $r = Invoke-JsonApi -Uri "$SERVER_URL/api/ai-queue/pending" -Token $QUEUE_TOKEN
    if (-not $r.ok) {
        Write-Host "  WARN poll: $($r.error)"
        return @()
    }
    $d = $r.body | ConvertFrom-Json
    return $d.jobs
}

function Send-Result {
    param([int]$JobId, [bool]$Success, [string]$Content, [string]$ErrMsg)
    if ($Success) {
        $esc  = $Content -replace '\\', '\\' -replace '"', '\"' -replace "`r`n", '\n' -replace "`n", '\n'
        $body = '{"success":true,"result":"' + $esc + '","provider":"deepseek-local"}'
    } else {
        $esc  = $ErrMsg -replace '"', '\"'
        $body = '{"success":false,"error":"' + $esc + '"}'
    }
    $r = Invoke-JsonApi -Uri "$SERVER_URL/api/ai-queue/$JobId/complete" -Method "POST" -Token $QUEUE_TOKEN -Body $body
    if (-not $r.ok) { Write-Host "  ERROR send: $($r.error)" }
    return $r.ok
}

function Call-DeepSeek {
    param([string]$UserPrompt, [bool]$JsonMode)
    $sys  = "You are a professional writer. Write ALL content in Persian (Farsi) language only."
    $sysE = $sys -replace '"', '\"'
    $usrE = $UserPrompt -replace '"', '\"' -replace "`r`n", '\n' -replace "`n", '\n'
    $fmt  = if ($JsonMode) { ',"response_format":{"type":"json_object"}' } else { "" }
    $body = '{"model":"deepseek-chat","messages":[{"role":"system","content":"' + $sysE + '"},{"role":"user","content":"' + $usrE + '"}],"max_tokens":2000' + $fmt + '}'
    $r    = Invoke-JsonApi -Uri $DEEPSEEK_URL -Method "POST" -Token $DEEPSEEK_KEY -Body $body
    if (-not $r.ok) { return @{ ok=$false; error=$r.error } }
    $d = $r.body | ConvertFrom-Json
    return @{ ok=$true; content=$d.choices[0].message.content }
}

function Process-Job {
    param($Job)
    $pr    = $Job.prompt
    $steps = if ($Job.step_count -gt 0) { [int]$Job.step_count } else { 5 }
    if ($Job.mode -eq "steps") {
        $u = "Write a $($Job.type) guide with $steps steps in Persian about: $pr. Return JSON only: {`"title`":`"title here`",`"excerpt`":`"summary`",`"steps`":[{`"title`":`"step title`",`"body`":`"step detail`"}]}"
        return Call-DeepSeek -UserPrompt $u -JsonMode $true
    } else {
        $u = "Write a complete professional article in Persian about: $pr. Include introduction, body with headings, and conclusion."
        return Call-DeepSeek -UserPrompt $u -JsonMode $false
    }
}

Write-Host "AI Worker started. Poll interval: ${POLL_INTERVAL}s"
while ($true) {
    $ts   = Get-Date -Format "HH:mm:ss"
    $jobs = Get-PendingJobs
    if ($jobs -and $jobs.Count -gt 0) {
        Write-Host "[$ts] $($jobs.Count) jobs found"
        foreach ($job in $jobs) {
            Write-Host "  #$($job.id) $($job.type)/$($job.mode): $($job.prompt)"
            $res = Process-Job -Job $job
            if ($res.ok) {
                $ok = Send-Result -JobId ([int]$job.id) -Success $true -Content $res.content -ErrMsg ""
                Write-Host "  result sent: $ok"
            } else {
                Send-Result -JobId ([int]$job.id) -Success $false -Content "" -ErrMsg $res.error | Out-Null
                Write-Host "  failed: $($res.error)"
            }
        }
    } else {
        Write-Host "[$ts] idle"
    }
    Start-Sleep -Seconds $POLL_INTERVAL
}
