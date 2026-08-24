$ErrorActionPreference = 'Stop'

$scriptRoot = $PSScriptRoot
$v4 = Join-Path $scriptRoot 'run-login-diagnostic-v4.ps1'

if (-not (Test-Path $v4)) {
    throw "Diagnostic runner not found: $v4"
}

$levels = if ($env:SWEEP_VUS) {
    @($env:SWEEP_VUS -split ',' | ForEach-Object { [int]$_.Trim() })
} else {
    @(1, 10, 25, 50, 100)
}

$iterations = if ($env:SWEEP_ITERATIONS) { [int]$env:SWEEP_ITERATIONS } else { 100 }
$mode = if ($env:SWEEP_MODE) { $env:SWEEP_MODE } else { 'ramp' }
$delaySeconds = if ($env:SWEEP_DELAY_SECONDS) { [int]$env:SWEEP_DELAY_SECONDS } else { 5 }
$root = Join-Path (Resolve-Path (Join-Path $scriptRoot '..\load\results')) ("sweep_" + (Get-Date -Format 'yyyy-MM-dd_HH-mm-ss'))
New-Item -ItemType Directory -Path $root -Force | Out-Null

Write-Host ''
Write-Host '============================================================' -ForegroundColor Cyan
Write-Host ' E-UJIAN LOGIN CONCURRENCY SWEEP' -ForegroundColor Cyan
Write-Host '============================================================' -ForegroundColor Cyan
Write-Host "Levels      : $($levels -join ', ') VUs"
Write-Host "Iterations  : $iterations"
Write-Host "Mode        : $mode"
Write-Host "Sweep report: $root"
Write-Host ''

$summary = New-Object System.Collections.Generic.List[object]

foreach ($vus in $levels) {
    $started = Get-Date
    Write-Host ''
    Write-Host '------------------------------------------------------------' -ForegroundColor Yellow
    Write-Host " STARTING $vus VU TEST" -ForegroundColor Yellow
    Write-Host '------------------------------------------------------------' -ForegroundColor Yellow

    $env:VUS = [string]$vus
    $env:ITERATIONS = [string]$iterations
    $env:K6_MODE = $mode

    $exitCode = 1
    try {
        & powershell.exe -ExecutionPolicy Bypass -File $v4
        $exitCode = if ($null -eq $LASTEXITCODE) { 0 } else { [int]$LASTEXITCODE }
    }
    catch {
        Write-Host "Runner exception at $vus VU: $($_.Exception.Message)" -ForegroundColor Red
        $exitCode = 1
    }

    $ended = Get-Date
    $durationSeconds = [math]::Round(($ended - $started).TotalSeconds, 1)

    $resultRoot = Join-Path $scriptRoot '..\load\results'
    $candidates = @(Get-ChildItem -Path $resultRoot -Directory -Filter "*_${vus}vu_${mode}" -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending)
    $latest = if ($candidates.Count -gt 0) { $candidates[0] } else { $null }

    $k6Summary = $null
    $diagnosticSummary = $null
    if ($latest) {
        $json = Join-Path $latest.FullName 'summary.json'
        if (Test-Path $json) {
            try { $k6Summary = Get-Content $json -Raw | ConvertFrom-Json } catch {}
        }
        $diag = Join-Path $latest.FullName 'diagnostic-summary.json'
        if (Test-Path $diag) {
            try { $diagnosticSummary = Get-Content $diag -Raw | ConvertFrom-Json } catch {}
        }
    }

    $row = [ordered]@{
        vus = $vus
        iterations_configured = $iterations
        mode = $mode
        runner_exit_code = $exitCode
        duration_seconds = $durationSeconds
        report_directory = if ($latest) { $latest.FullName } else { '' }
        total_http_requests = if ($k6Summary) { $k6Summary.metrics.http_reqs.values.count } else { $null }
        http_failed_rate = if ($k6Summary) { $k6Summary.metrics.http_req_failed.values.rate } else { $null }
        login_p95_ms = if ($k6Summary) { $k6Summary.metrics.login_duration.values.'p(95)' } else { $null }
        login_p99_ms = if ($k6Summary) { $k6Summary.metrics.login_duration.values.'p(99)' } else { $null }
        slow_requests = if ($diagnosticSummary) { $diagnosticSummary.slow_requests } else { $null }
        ptrace_denied = if ($diagnosticSummary) { $diagnosticSummary.ptrace_denied } else { $null }
    }
    $summary.Add([pscustomobject]$row)

    $safe = $row | ConvertTo-Json -Depth 5
    $safe | Set-Content -Path (Join-Path $root ("{0}vu-result.json" -f $vus)) -Encoding UTF8

    Write-Host "Finished $vus VU | exit=$exitCode | duration=${durationSeconds}s" -ForegroundColor Green

    if ($vus -ne $levels[-1]) {
        Write-Host "Waiting $delaySeconds seconds before next level..."
        Start-Sleep -Seconds $delaySeconds
    }
}

$csv = Join-Path $root 'sweep-summary.csv'
$summary | Export-Csv -Path $csv -NoTypeInformation -Encoding UTF8
$summary | ConvertTo-Json -Depth 6 | Set-Content -Path (Join-Path $root 'sweep-summary.json') -Encoding UTF8

Write-Host ''
Write-Host '============================================================' -ForegroundColor Cyan
Write-Host ' SWEEP FINISHED' -ForegroundColor Cyan
Write-Host '============================================================' -ForegroundColor Cyan
$summary | Format-Table -AutoSize
Write-Host "CSV report : $csv"
Write-Host "JSON report: $(Join-Path $root 'sweep-summary.json')"
