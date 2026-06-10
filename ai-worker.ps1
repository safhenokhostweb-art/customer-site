# AI Queue Worker - SaaS Platform v3
param()
$SERVER_URL    = "http://safhenokhost.ir"
$QUEUE_TOKEN   = "ai-queue-secret-2026-XK9"
$DEEPSEEK_KEY  = "sk-22cbabc9aa284678b5755e004aa150fa"
$DEEPSEEK_URL  = "https://api.deepseek.com/v1/chat/completions"
$POLL_INTERVAL = 15

function Http-Post {
    param([string]$Uri, [string]$Token, [string]$Body)
    $req = [System.Net.HttpWebRequest]::Create($Uri)
    $req.Method = "POST"
    $req.Accept = "application/json"
    $req.Timeout = 90000
    if ($Token) { $req.Headers["Authorization"] = "Bearer $Token" }
    $bytes = [System.Text.Encoding]::UTF8.GetBytes($Body)
    $req.ContentType = "application/json; charset=utf-8"
    $req.ContentLength = $bytes.Length
    $s = $req.GetRequestStream()
    $s.Write($bytes, 0, $bytes.Length)
    $s.Close()
    try {
        $resp = $req.GetResponse()
        $r = [System.IO.StreamReader]::new($resp.GetResponseStream(), [System.Text.Encoding]::UTF8)
        return @{ ok=$true; body=$r.ReadToEnd() }
    } catch [System.Net.WebException] {
        $errBody = ""
        try { $errBody = [System.IO.StreamReader]::new($_.Exception.Response.GetResponseStream()).ReadToEnd() } catch {}
        return @{ ok=$false; error="$($_.Exception.Message) | $errBody" }
    }
}

function Http-Get {
    param([string]$Uri, [string]$Token)
    $req = [System.Net.HttpWebRequest]::Create($Uri)
    $req.Method = "GET"
    $req.Accept = "application/json"
    $req.Timeout = 15000
    if ($Token) { $req.Headers["Authorization"] = "Bearer $Token" }
    try {
        $resp = $req.GetResponse()
        $r = [System.IO.StreamReader]::new($resp.GetResponseStream(), [System.Text.Encoding]::UTF8)
        return @{ ok=$true; body=$r.ReadToEnd() }
    } catch [System.Net.WebException] {
        return @{ ok=$false; error=$_.Exception.Message }
    }
}

function Get-Jobs {
    $r = Http-Get -Uri "$SERVER_URL/api/ai-queue/pending" -Token $QUEUE_TOKEN
    if (-not $r.ok) { Write-Host "  poll err: $($r.error)"; return @() }
    return ($r.body | ConvertFrom-Json).jobs
}

function Call-DeepSeek {
    param([string]$Prompt, [string]$Type, [int]$Steps, [bool]$JsonMode)
    $sys = "You are a professional writer. Write ALL content in Persian (Farsi) only."
    $sysE = $sys -replace '"','\"'
    if ($JsonMode) {
        $usr = "Write a $Type guide with $Steps steps in Persian about: $Prompt. Return JSON: {`"title`":`"..`",`"excerpt`":`"..`",`"steps`":[{`"title`":`"..`",`"body`":`"..`"}]}"
    } else {
        $usr = "Write a concise professional article in Persian about: $Prompt. Max 3 paragraphs: intro, body, conclusion."
    }
    $usrE = $usr -replace '"','\"' -replace "`r`n",'\n' -replace "`n",'\n'
    $fmt = if ($JsonMode) { ',"response_format":{"type":"json_object"}' } else { "" }
    $body = '{"model":"deepseek-chat","messages":[{"role":"system","content":"' + $sysE + '"},{"role":"user","content":"' + $usrE + '"}],"max_tokens":800' + $fmt + '}'
    $r = Http-Post -Uri $DEEPSEEK_URL -Token $DEEPSEEK_KEY -Body $body
    if (-not $r.ok) { return @{ ok=$false; error=$r.error } }
    $content = ($r.body | ConvertFrom-Json).choices[0].message.content
    return @{ ok=$true; content=$content }
}

function Send-Done {
    param([int]$JobId, [string]$Content)
    $esc  = $Content -replace '\\','\\' -replace '"','\"' -replace "`r`n",'\n' -replace "`n",'\n'
    $body = '{"success":true,"result":"' + $esc + '","provider":"deepseek-local"}'
    $r = Http-Post -Uri "$SERVER_URL/api/ai-queue/$JobId/complete" -Token $QUEUE_TOKEN -Body $body
    return $r.ok
}

function Send-Fail {
    param([int]$JobId, [string]$Err)
    $esc  = $Err -replace '"','\"' -replace "`r`n",' ' -replace "`n",' '
    $body = '{"success":false,"error":"' + $esc + '"}'
    Http-Post -Uri "$SERVER_URL/api/ai-queue/$JobId/complete" -Token $QUEUE_TOKEN -Body $body | Out-Null
}

Write-Host "AI Worker v3 started"
while ($true) {
    $ts   = Get-Date -Format "HH:mm:ss"
    $jobs = Get-Jobs
    if ($jobs -and $jobs.Count -gt 0) {
        Write-Host "[$ts] $($jobs.Count) job(s)"
        foreach ($job in $jobs) {
            $id    = [int]$job.id
            $steps = if ([int]$job.step_count -gt 0) { [int]$job.step_count } else { 5 }
            $isS   = $job.mode -eq "steps"
            Write-Host "  #$id [$($job.type)/$($job.mode)] $($job.prompt)"
            $res = Call-DeepSeek -Prompt $job.prompt -Type $job.type -Steps $steps -JsonMode $isS
            if ($res.ok) {
                $ok = Send-Done -JobId $id -Content $res.content
                Write-Host "  result sent: $ok"
            } else {
                Send-Fail -JobId $id -Err $res.error
                Write-Host "  failed: $($res.error)"
            }
        }
    } else {
        Write-Host "[$ts] idle"
    }
    Start-Sleep -Seconds $POLL_INTERVAL
}
