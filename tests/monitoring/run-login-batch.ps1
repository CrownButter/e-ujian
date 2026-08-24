[CmdletBinding()]
param(
    [int]$TotalUsers = 709,
    [int]$BatchSize = 50,
    [int]$BatchIntervalSeconds = 5,
    [string]$BaseUrl = 'http://localhost:8080',
    [string]$K6Script = './tests/load/k6/login-batch.js'
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

function Fail([string]$Message) {
    Write-Host "[ERROR] $Message" -ForegroundColor Red
    exit 1
}

if ($TotalUsers -lt 1 -or $TotalUsers -gt 709) {
    Fail "TotalUsers harus 1-709. Nilai: $TotalUsers"
}

if ($BatchSize -lt 1 -or $BatchSize -gt $TotalUsers) {
    Fail "BatchSize harus 1-$TotalUsers. Nilai: $BatchSize"
}

if ($BatchIntervalSeconds -lt 1) {
    Fail "BatchIntervalSeconds harus >= 1. Nilai: $BatchIntervalSeconds"
}

$scriptPath = [System.IO.Path]::GetFullPath((Join-Path (Get-Location) $K6Script))
if (-not (Test-Path -LiteralPath $scriptPath -PathType Leaf)) {
    Fail "K6 script tidak ditemukan: $scriptPath"
}

$timestamp = Get-Date -Format 'yyyy-MM-dd_HH-mm-ss'
$batchCount = [math]::Ceiling($TotalUsers / $BatchSize)
$reportRoot = Join-Path (Get-Location) 'tests/load/results'
$reportDir = Join-Path $reportRoot ("{0}_{1}users_batch{2}_every{3}s" -f $timestamp, $TotalUsers, $BatchSize, $BatchIntervalSeconds)

New-Item -ItemType Directory -Force -Path $reportDir | Out-Null

$summaryFile = Join-Path $reportDir 'summary.json'
$k6OutputFile = Join-Path $reportDir 'k6-output.txt'
$dockerStateFile = Join-Path $reportDir 'docker-state.txt'

Write-Host ''
Write-Host '============================================================'
Write-Host ' E-UJIAN K6 BATCH LOGIN TEST RUNNER'
Write-Host '============================================================'
Write-Host ("Total users       : {0}" -f $TotalUsers)
Write-Host ("Batch size        : {0}" -f $BatchSize)
Write-Host ("Batch interval    : {0}s" -f $BatchIntervalSeconds)
Write-Host ("Batch count       : {0}" -f $batchCount)
Write-Host ("Account range     : 001-{0:D3}" -f $TotalUsers)
Write-Host ("BASE_URL          : {0}" -f $BaseUrl)
Write-Host ("K6 SCRIPT         : {0}" -f $K6Script)
Write-Host ("Report            : {0}" -f $reportDir)
Write-Host ''

Write-Host '[CHECK] Docker containers ...'
try {
    docker info *> $null
    if ($LASTEXITCODE -ne 0) {
        Fail 'Docker daemon tidak tersedia.'
    }
} catch {
    Fail 'Perintah docker tidak dapat dijalankan.'
}

$requiredContainers = @('e-ujian-nginx', 'e-ujian-php', 'e-ujian-mysql')
foreach ($container in $requiredContainers) {
    $state = docker inspect -f '{{.State.Status}}' $container 2>$null
    if ($LASTEXITCODE -ne 0 -or $state -ne 'running') {
        Fail "Container $container tidak running. Status: $state"
    }
}

Write-Host '[OK] Required containers running.'

docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}' | Tee-Object -FilePath $dockerStateFile

Write-Host ''
Write-Host ("[CHECK] GET {0}/login ..." -f $BaseUrl)
try {
    $response = Invoke-WebRequest -Uri ("{0}/login" -f $BaseUrl.TrimEnd('/')) -Method Get -UseBasicParsing -TimeoutSec 15
    Write-Host ("[OK] Application HTTP {0}" -f [int]$response.StatusCode)
} catch {
    Fail ("Application /login tidak dapat diakses: {0}" -f $_.Exception.Message)
}

Write-Host ''
Write-Host '============================================================'
Write-Host ' START K6 BATCH TEST'
Write-Host '============================================================'
Write-Host '[INFO] Semua VU dibuat oleh K6, tetapi HTTP request ditahan'
Write-Host '[INFO] berdasarkan batch sebelum GET /login dikirim.'
Write-Host ''

$env:BASE_URL = $BaseUrl.TrimEnd('/')
$env:TOTAL_USERS = [string]$TotalUsers
$env:BATCH_SIZE = [string]$BatchSize
$env:BATCH_INTERVAL_SECONDS = [string]$BatchIntervalSeconds
$env:K6_SUMMARY_FILE = $summaryFile

$k6Args = @('run', $scriptPath)

& k6 @k6Args 2>&1 | Tee-Object -FilePath $k6OutputFile
$k6ExitCode = $LASTEXITCODE

if ($k6ExitCode -ne 0) {
    Write-Host ''
    Write-Host "[ERROR] K6 selesai dengan exit code $k6ExitCode" -ForegroundColor Red
    Write-Host "[INFO] Output: $k6OutputFile"
    exit $k6ExitCode
}

Write-Host ''
Write-Host '============================================================'
Write-Host ' BATCH TEST COMPLETE'
Write-Host '============================================================'

if (Test-Path -LiteralPath $summaryFile -PathType Leaf) {
    Write-Host "[OK] Summary : $summaryFile"
} else {
    Write-Host '[WARN] summary.json tidak ditemukan.' -ForegroundColor Yellow
}

Write-Host "[OK] K6 output: $k6OutputFile"
Write-Host "[OK] Docker state: $dockerStateFile"
Write-Host ''
Write-Host 'Contoh test default:'
Write-Host 'powershell.exe -ExecutionPolicy Bypass -File ./tests/monitoring/run-login-batch.ps1'
Write-Host ''
Write-Host 'Contoh test 709 user, batch 100 setiap 5 detik:'
Write-Host 'powershell.exe -ExecutionPolicy Bypass -File ./tests/monitoring/run-login-batch.ps1 -TotalUsers 709 -BatchSize 100 -BatchIntervalSeconds 5'
