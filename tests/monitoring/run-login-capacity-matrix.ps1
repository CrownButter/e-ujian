[CmdletBinding()]
param(
    [int[]]$Vus = @(10, 20, 30, 40, 50),
    [int]$DurationSeconds = 30,
    [int]$BatchSize = 50,
    [int]$BatchIntervalSeconds = 5,
    [int]$TotalUsers = 709,
    [string]$BaseUrl = 'http://localhost:8080',
    [string]$K6Script = './tests/load/k6/login-batch.js'
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

function Fail([string]$Message) {
    Write-Host "[ERROR] $Message" -ForegroundColor Red
    exit 1
}

if ($Vus.Count -eq 0) { Fail 'Vus tidak boleh kosong.' }
if ($DurationSeconds -lt 1) { Fail 'DurationSeconds harus >= 1.' }
if ($BatchSize -lt 1) { Fail 'BatchSize harus >= 1.' }
if ($BatchIntervalSeconds -lt 1) { Fail 'BatchIntervalSeconds harus >= 1.' }
if ($TotalUsers -lt 1 -or $TotalUsers -gt 709) { Fail 'TotalUsers harus 1-709.' }
foreach ($vu in $Vus) {
    if ($vu -lt 1 -or $vu -gt $TotalUsers) {
        Fail "Setiap VU harus berada di 1-$TotalUsers. Nilai invalid: $vu"
    }
}

$scriptPath = [System.IO.Path]::GetFullPath((Join-Path (Get-Location) $K6Script))
if (-not (Test-Path -LiteralPath $scriptPath -PathType Leaf)) {
    Fail "K6 script tidak ditemukan: $scriptPath"
}

if (-not (Get-Command k6 -ErrorAction SilentlyContinue)) { Fail 'k6 tidak ditemukan di PATH.' }
if (-not (Get-Command docker -ErrorAction SilentlyContinue)) { Fail 'docker tidak ditemukan di PATH.' }

docker info *> $null
if ($LASTEXITCODE -ne 0) { Fail 'Docker daemon tidak tersedia.' }

$requiredContainers = @('e-ujian-nginx', 'e-ujian-php', 'e-ujian-mysql')
foreach ($container in $requiredContainers) {
    $state = docker inspect -f '{{.State.Status}}' $container 2>$null
    if ($LASTEXITCODE -ne 0 -or $state -ne 'running') {
        Fail "Container $container tidak running. Status: $state"
    }
}

try {
    $health = Invoke-WebRequest -Uri ("{0}/login" -f $BaseUrl.TrimEnd('/')) -Method Get -UseBasicParsing -TimeoutSec 15
    if ([int]$health.StatusCode -ne 200) {
        Fail "Application /login mengembalikan HTTP $([int]$health.StatusCode)."
    }
} catch {
    Fail ("Application /login tidak dapat diakses: {0}" -f $_.Exception.Message)
}

$timestamp = Get-Date -Format 'yyyy-MM-dd_HH-mm-ss'
$root = Join-Path (Get-Location) 'tests/load/results'
$matrixDir = Join-Path $root ("{0}_capacity-matrix" -f $timestamp)
New-Item -ItemType Directory -Force -Path $matrixDir | Out-Null

$results = New-Object System.Collections.Generic.List[object]

Write-Host ''
Write-Host '============================================================'
Write-Host ' E-UJIAN LOGIN CAPACITY MATRIX'
Write-Host '============================================================'
Write-Host ("VU MATRIX         : {0}" -f ($Vus -join ', '))
Write-Host ("DURATION          : {0}s per run" -f $DurationSeconds)
Write-Host ("BATCH SIZE        : {0}" -f $BatchSize)
Write-Host ("BATCH INTERVAL    : {0}s" -f $BatchIntervalSeconds)
Write-Host ("TOTAL USERS       : {0}" -f $TotalUsers)
Write-Host ("BASE_URL          : {0}" -f $BaseUrl)
Write-Host ("REPORT ROOT       : {0}" -f $matrixDir)
Write-Host ''

$oldErrorActionPreference = $ErrorActionPreference

foreach ($vu in $Vus) {
    $runTimestamp = Get-Date -Format 'yyyy-MM-dd_HH-mm-ss'
    $runDir = Join-Path $matrixDir ("vu_{0}" -f $vu)
    New-Item -ItemType Directory -Force -Path $runDir | Out-Null

    $summaryFile = Join-Path $runDir 'summary.json'
    $k6OutputFile = Join-Path $runDir 'k6-output.txt'

    Write-Host ''
    Write-Host '------------------------------------------------------------'
    Write-Host (" START {0} VU" -f $vu)
    Write-Host '------------------------------------------------------------'
    Write-Host ("Report : {0}" -f $runDir)

    $env:BASE_URL = $BaseUrl.TrimEnd('/')
    $env:TOTAL_USERS = [string]$TotalUsers
    $env:BATCH_SIZE = [string][Math]::Min($BatchSize, $TotalUsers)
    $env:BATCH_INTERVAL_SECONDS = [string]$BatchIntervalSeconds
    $env:K6_SUMMARY_FILE = $summaryFile

    $k6Args = @(
        'run',
        '--vus', [string]$vu,
        '--duration', ("{0}s" -f $DurationSeconds),
        $scriptPath
    )

    $ErrorActionPreference = 'Continue'
    & k6 @k6Args 2>&1 | Tee-Object -FilePath $k6OutputFile
    $k6ExitCode = $LASTEXITCODE
    $ErrorActionPreference = $oldErrorActionPreference

    $successRate = $null
    $p95 = $null
    $p99 = $null
    $httpP95 = $null
    $httpFailRate = $null
    $authSuccess = $null
    $authFailure = $null

    if (Test-Path -LiteralPath $summaryFile -PathType Leaf) {
        try {
            $summary = Get-Content -LiteralPath $summaryFile -Raw | ConvertFrom-Json
            $successRate = [double]$summary.result.login_success_rate
            $p95 = [double]$summary.metrics.login_duration.values.'p(95)'
            $p99 = [double]$summary.metrics.login_duration.values.'p(99)'
            $httpP95 = [double]$summary.metrics.http_req_duration.values.'p(95)'
            $httpFailRate = [double]$summary.metrics.http_req_failed.values.rate
            $authSuccess = [int]$summary.result.auth_success
            $authFailure = [int]$summary.result.auth_failure
        } catch {
            Write-Host "[WARN] Gagal membaca summary.json untuk ${vu} VU: $($_.Exception.Message)" -ForegroundColor Yellow
        }
    } else {
        Write-Host '[WARN] summary.json tidak ditemukan.' -ForegroundColor Yellow
    }

    $results.Add([pscustomobject]@{
        vus = $vu
        duration_seconds = $DurationSeconds
        login_success_rate = $successRate
        login_p95_ms = $p95
        login_p99_ms = $p99
        http_p95_ms = $httpP95
        http_failed_rate = $httpFailRate
        auth_success = $authSuccess
        auth_failure = $authFailure
        k6_exit_code = $k6ExitCode
        run_directory = $runDir
        started_at = $runTimestamp
    })

    Write-Host ''
    Write-Host ("RESULT {0} VU: success={1} p95={2}ms p99={3}ms auth_fail={4} http_fail={5} exit={6}" -f $vu, $successRate, $p95, $p99, $authFailure, $httpFailRate, $k6ExitCode)
}

$matrixJson = Join-Path $matrixDir 'capacity-matrix.json'
$matrixCsv = Join-Path $matrixDir 'capacity-matrix.csv'
$results | ConvertTo-Json -Depth 6 | Set-Content -LiteralPath $matrixJson -Encoding UTF8
$results | Export-Csv -LiteralPath $matrixCsv -NoTypeInformation -Encoding UTF8

Write-Host ''
Write-Host '============================================================'
Write-Host ' CAPACITY MATRIX COMPLETE'
Write-Host '============================================================'
$results | Format-Table vus, login_success_rate, login_p95_ms, login_p99_ms, auth_failure, http_failed_rate, k6_exit_code -AutoSize
Write-Host ''
Write-Host "Matrix JSON : $matrixJson"
Write-Host "Matrix CSV  : $matrixCsv"
Write-Host ''

# Return non-zero only when the test itself could not execute.
# Threshold failures are preserved in the per-run k6 exit_code and reports.
$executionFailures = @($results | Where-Object { $_.k6_exit_code -ne 0 -and -not (Test-Path -LiteralPath (Join-Path $_.run_directory 'summary.json')) })
if ($executionFailures.Count -gt 0) {
    exit 1
}

exit 0
