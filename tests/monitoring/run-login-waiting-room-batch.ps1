[CmdletBinding()]
param(
    [string]$VuMatrix = '10,30,100,300,709',
    [int]$MaxWaitSeconds = 300,
    [int]$PollIntervalSeconds = 2,
    [string]$BaseUrl = 'http://localhost:8080'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$root = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$script = Join-Path $root 'tests\load\k6\login-waiting-room-batch.js'
$timestamp = Get-Date -Format 'yyyy-MM-dd_HH-mm-ss'
$reportRoot = Join-Path $root "tests\load\results\${timestamp}_waiting_room_batch"

if (-not (Test-Path $script)) { throw "K6 script not found: $script" }

$vus = @($VuMatrix -split ',' | ForEach-Object { $_.Trim() } | Where-Object { $_ -ne '' } | ForEach-Object { [int]$_ })
if ($vus.Count -eq 0) { throw 'VuMatrix must contain at least one positive integer.' }
foreach ($vu in $vus) { if ($vu -lt 1 -or $vu -gt 709) { throw "VU value must be between 1 and 709. Received: $vu" } }

New-Item -ItemType Directory -Force -Path $reportRoot | Out-Null

Write-Host '========================================================================'
Write-Host 'E-UJIAN WAITING ROOM MULTI-USER LOGIN TEST'
Write-Host '========================================================================'
Write-Host "BASE_URL              : $BaseUrl"
Write-Host "VU matrix             : $($vus -join ', ')"
Write-Host "Max queue wait        : ${MaxWaitSeconds}s"
Write-Host "Poll interval         : ${PollIntervalSeconds}s"
Write-Host "Report                : $reportRoot"
Write-Host ''

try {
    $health = Invoke-WebRequest -Uri "$BaseUrl/login" -Method GET -UseBasicParsing -TimeoutSec 10
    if ($health.StatusCode -ne 200) { throw "GET /login returned HTTP $($health.StatusCode)" }
    Write-Host '[OK] GET /login HTTP 200'
} catch { throw "Application check failed: $($_.Exception.Message)" }

function Get-SummaryMetric {
    param(
        [Parameter(Mandatory=$true)][object]$Metrics,
        [Parameter(Mandatory=$true)][string]$MetricName,
        [Parameter(Mandatory=$true)][string]$ValueName,
        [object]$Default = 0
    )

    if ($null -eq $Metrics) { return $Default }
    $metricProperty = $Metrics.PSObject.Properties[$MetricName]
    if ($null -eq $metricProperty -or $null -eq $metricProperty.Value) { return $Default }

    $metric = $metricProperty.Value
    $valueProperty = $metric.PSObject.Properties[$ValueName]
    if ($null -ne $valueProperty -and $null -ne $valueProperty.Value) {
        return $valueProperty.Value
    }

    $valuesProperty = $metric.PSObject.Properties['values']
    if ($null -ne $valuesProperty -and $null -ne $valuesProperty.Value) {
        $nested = $valuesProperty.Value.PSObject.Properties[$ValueName]
        if ($null -ne $nested -and $null -ne $nested.Value) { return $nested.Value }
    }

    return $Default
}

function Get-SummaryValue {
    param(
        [Parameter(Mandatory=$true)][object]$Metrics,
        [Parameter(Mandatory=$true)][string[]]$Names,
        [object]$Default = 0
    )

    foreach ($name in $Names) {
        $property = $Metrics.PSObject.Properties[$name]
        if ($null -ne $property -and $null -ne $property.Value) { return $property.Value }
    }
    return $Default
}

$rows = @()

foreach ($vu in $vus) {
    $dir = Join-Path $reportRoot ("vu_{0:D3}" -f $vu)
    New-Item -ItemType Directory -Force -Path $dir | Out-Null

    $summary = Join-Path $dir 'summary.json'
    $console = Join-Path $dir 'k6-output.txt'
    $env:BASE_URL = $BaseUrl
    $env:TOTAL_USERS = [string]$vu
    $env:MAX_WAIT_SECONDS = [string]$MaxWaitSeconds
    $env:POLL_INTERVAL_SECONDS = [string]$PollIntervalSeconds
    $env:K6_SUMMARY_FILE = $summary

    Write-Host ''
    Write-Host '========================================================================'
    Write-Host "BATCH $vu USERS / WAITING ROOM"
    Write-Host '========================================================================'
    Write-Host "[K6] k6 run --summary-export $summary $script"

    $k6Exit = 0
    & k6 run --summary-export $summary $script 2>&1 | Tee-Object -FilePath $console
    if ($LASTEXITCODE -ne 0) { $k6Exit = $LASTEXITCODE }

    $loginSuccessRate = 0.0
    $httpFailedRate = 0.0
    $queueReady = 0
    $queueExpired = 0
    $authSuccess = 0
    $authFailure = 0
    $loginP95 = 0.0
    $loginP99 = 0.0
    $queueP95 = 0.0
    $authP95 = 0.0

    if (Test-Path $summary) {
        try {
            $obj = Get-Content $summary -Raw | ConvertFrom-Json
            $metrics = $null
            if ($null -ne $obj.PSObject.Properties['metrics']) { $metrics = $obj.metrics }

            if ($null -ne $metrics) {
                # K6 summary-export stores rate/count metrics under metrics.<name>.values.
                $loginSuccessRate = [double](Get-SummaryMetric $metrics 'login_success_rate' 'rate' 0)
                $httpFailedRate = [double](Get-SummaryMetric $metrics 'http_failed_rate' 'rate' 0)
                $queueReady = [int](Get-SummaryMetric $metrics 'queue_ready' 'count' 0)
                $queueExpired = [int](Get-SummaryMetric $metrics 'queue_expired' 'count' 0)
                $authSuccess = [int](Get-SummaryMetric $metrics 'auth_success' 'count' 0)
                $authFailure = [int](Get-SummaryMetric $metrics 'auth_failure' 'count' 0)

                $loginP95 = [double](Get-SummaryMetric $metrics 'login_duration' 'p(95)' 0)
                $loginP99 = [double](Get-SummaryMetric $metrics 'login_duration' 'p(99)' 0)
                $queueP95 = [double](Get-SummaryMetric $metrics 'queue_wait_duration' 'p(95)' 0)
                $authP95 = [double](Get-SummaryMetric $metrics 'auth_duration' 'p(95)' 0)
            }

            # Fallback: if the custom metrics are not present in summary-export,
            # parse the machine-readable values printed by the K6 script.
            if ($queueReady -eq 0 -and $authSuccess -eq 0 -and (Test-Path $console)) {
                $text = Get-Content $console -Raw
                $patterns = @{
                    loginSuccessRate = 'LOGIN SUCCESS RATE\s*:\s*([0-9.,]+)%'
                    httpFailedRate = 'HTTP FAILED RATE\s*:\s*([0-9.,]+)%'
                    queueReady = 'QUEUE READY\s*:\s*(\d+)'
                    queueExpired = 'QUEUE EXPIRED\s*:\s*(\d+)'
                    authSuccess = 'AUTH SUCCESS\s*:\s*(\d+)'
                    authFailure = 'AUTH FAILURE\s*:\s*(\d+)'
                    queueP95 = 'QUEUE WAIT P95\s*:\s*([0-9.,]+)\s*ms'
                    authP95 = 'AUTH P95\s*:\s*([0-9.,]+)\s*ms'
                    loginP95 = 'LOGIN P95\s*:\s*([0-9.,]+)\s*ms'
                    loginP99 = 'LOGIN P99\s*:\s*([0-9.,]+)\s*ms'
                }

                $cultureInvariant = [System.Globalization.CultureInfo]::InvariantCulture
                foreach ($key in $patterns.Keys) {
                    $match = [regex]::Match($text, $patterns[$key], [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
                    if (-not $match.Success) { continue }
                    $value = $match.Groups[1].Value.Replace(',', '.')
                    switch ($key) {
                        'loginSuccessRate' { $loginSuccessRate = [double]$value / 100 }
                        'httpFailedRate' { $httpFailedRate = [double]$value / 100 }
                        'queueReady' { $queueReady = [int]$value }
                        'queueExpired' { $queueExpired = [int]$value }
                        'authSuccess' { $authSuccess = [int]$value }
                        'authFailure' { $authFailure = [int]$value }
                        'queueP95' { $queueP95 = [double]$value }
                        'authP95' { $authP95 = [double]$value }
                        'loginP95' { $loginP95 = [double]$value }
                        'loginP99' { $loginP99 = [double]$value }
                    }
                }
            }
        } catch {
            Write-Warning "Could not parse summary for VU=$vu : $($_.Exception.Message)"
        }
    } else {
        Write-Warning "K6 summary was not generated for VU=$vu"
    }

    $rows += [PSCustomObject]@{
        vus = $vu
        k6_exit_code = $k6Exit
        login_success_rate = $loginSuccessRate
        http_failed_rate = $httpFailedRate
        queue_ready = $queueReady
        queue_expired = $queueExpired
        auth_success = $authSuccess
        auth_failure = $authFailure
        queue_p95_ms = $queueP95
        auth_p95_ms = $authP95
        login_p95_ms = $loginP95
        login_p99_ms = $loginP99
        report_dir = $dir
    }

    Write-Host "[RESULT] VU=$vu exit=$k6Exit success_rate=$([math]::Round($loginSuccessRate * 100, 2))% queue_ready=$queueReady queue_expired=$queueExpired auth_success=$authSuccess auth_failure=$authFailure queue_p95=${queueP95}ms auth_p95=${authP95}ms login_p95=${loginP95}ms login_p99=${loginP99}ms"
}

$csv = Join-Path $reportRoot 'waiting-room-summary.csv'
$rows | Export-Csv -Path $csv -NoTypeInformation -Encoding UTF8

Write-Host ''
Write-Host '========================================================================'
Write-Host 'WAITING ROOM TEST COMPLETE'
Write-Host '========================================================================'
Write-Host "Report : $reportRoot"
Write-Host "Summary: $csv"
$rows | Format-Table -AutoSize
