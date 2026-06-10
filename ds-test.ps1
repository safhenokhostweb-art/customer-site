$start = Get-Date
try {
    $payload = @{
        model    = "deepseek-chat"
        messages = @(@{ role="user"; content="Say hello in 5 words" })
        max_tokens = 20
    } | ConvertTo-Json -Depth 5

    $resp = Invoke-RestMethod -Uri 'https://api.deepseek.com/v1/chat/completions' `
        -Method POST `
        -Headers @{ 'Authorization'='Bearer sk-22cbabc9aa284678b5755e004aa150fa'; 'Content-Type'='application/json' } `
        -Body $payload `
        -TimeoutSec 30
    $elapsed = ((Get-Date) - $start).TotalSeconds
    Add-Content -Path 'c:\laragon\www\customer-site\ds-test.log' -Value "OK in $($elapsed)s: $($resp.choices[0].message.content)"
} catch {
    $elapsed = ((Get-Date) - $start).TotalSeconds
    Add-Content -Path 'c:\laragon\www\customer-site\ds-test.log' -Value "ERR in $($elapsed)s: $($_.Exception.Message)"
}