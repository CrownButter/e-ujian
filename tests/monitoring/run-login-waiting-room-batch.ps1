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
            $result = $null
            $metrics = $null
            if ($null -ne $obj.PSObject.Properties['result']) { $result = $obj.result }
            if ($null -ne $obj.PSObject.Properties['metrics']) { $metrics = $obj.metrics }

            function Get-ValueOrDefault([object]$Object, [string]$Name, [object]$Default) {
                if ($null -eq $Object) { return $Default }
                $property = $Object.PSObject.Properties[$Name]
                if ($null -eq $property -or $null -eq $property.Value) { return $Default }
                return $property.Value
            }

            if ($null -ne $result) {
                $loginSuccessRate = [double](Get-ValueOrDefault $result 'login_success_rate' 0)
                $httpFailedRate = [double](Get-ValueOrDefault $result 'http_failed_rate' 0)
                $queueReady = [int](Get-ValueOrDefault $result 'queue_ready' 0)
                $queueExpired = [int](Get-ValueOrDefault $result 'queue_expired' 0)
                $authSuccess = [int](Get-ValueOrDefault $result 'auth_success' 0)
                $authFailure = [int](Get-ValueOrDefault $result 'auth_failure' 0)
            }

            if ($null -ne $metrics) {
                $loginMetric = Get-ValueOrDefault $metrics 'login_duration' $null
                if ($null -ne $loginMetric) {
                    $values = Get-ValueOrDefault $loginMetric 'values' $null
                    if ($null -ne $values) {
                        $loginP95 = [double](Get-ValueOrDefault $values 'p(95)' 0)
                        $loginP99 = [double](Get-ValueOrDefault $values 'p(99)' 0)
                    }
                }

                $queueMetric = Get-ValueOrDefault $metrics 'queue_wait_duration' $null
                if ($null -ne $queueMetric) {
                    $values = Get-ValueOrDefault $queueMetric 'values' $null
                    if ($null -ne $values) { $queueP95 = [double](Get-ValueOrDefault $values 'p(95)' 0) }
                }

                $authMetric = Get-ValueOrDefault $metrics 'auth_duration' $null
                if ($null -ne $authMetric) {
                    $values = Get-ValueOrDefault $authMetric 'values' $null
                    if ($null -ne $values) { $authP95 = [double](Get-ValueOrDefault $values 'p(95)' 0) }
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

    Write-Host "[RESULT] VU=$vu exit=$k6Exit success_rate=$([math]::Round($loginSuccessRate * 100, 2))% queue_ready=$queueReady queue_expired=$queueExpired queue_p95=${queueP95}ms auth_p95=${authP95}ms login_p95=${loginP95}ms login_p99=${loginP99}ms"
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
