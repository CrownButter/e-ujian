$ErrorActionPreference = 'Stop'

# ============================================================
# E-UJIAN K6 LOAD TEST + DOCKER RESOURCE REPORT
# ============================================================

$scriptRoot = $PSScriptRoot
$repoRoot = Resolve-Path (Join-Path $scriptRoot '..\..')
$composeFile = Join-Path $scriptRoot 'docker-compose.monitoring.yml'
$k6Script = Join-Path $repoRoot 'tests\load\k6\login-001-709.js'
$reportRoot = Join-Path $repoRoot 'tests\load\results'
$timestamp = Get-Date -Format 'yyyy-MM-dd_HH-mm-ss'
$reportDir = Join-Path $reportRoot $timestamp
$dockerCsv = Join-Path $reportDir 'docker-stats.csv'
$summaryFile = Join-Path $reportDir 'summary.json'

New-Item -ItemType Directory -Force -Path $reportDir | Out-Null

$env:BASE_URL = if ($env:BASE_URL) { $env:BASE_URL } else { 'http://localhost:8080' }
$env:K6_SUMMARY_FILE = $summaryFile

Write-Host ''
Write-Host '============================================================' -ForegroundColor Cyan
Write-Host ' E-UJIAN LOAD TEST' -ForegroundColor Cyan
Write-Host '============================================================' -ForegroundColor Cyan
Write-Host "Timestamp : $timestamp"
Write-Host "BASE_URL  : $env:BASE_URL"
Write-Host "Report    : $reportDir"
Write-Host ''

# Start monitoring stack if available.
if (Test-Path $composeFile) {
    Write-Host 'Starting monitoring containers...' -ForegroundColor Cyan
    docker compose -f $composeFile up -d
}

# Verify application before starting 709 VUs.
Write-Host "Checking $env:BASE_URL/login ..." -ForegroundColor Cyan
try {
    $probe = Invoke-WebRequest -Uri "$env:BASE_URL/login" -Method GET -UseBasicParsing -TimeoutSec 10
    Write-Host "Application responded with HTTP $($probe.StatusCode)." -ForegroundColor Green
} catch {
    Write-Host "Application is not reachable: $($_.Exception.Message)" -ForegroundColor Red
    Write-Host 'Set BASE_URL to the actual published application port.' -ForegroundColor Yellow
    exit 1
}

# Docker stats sampler. Samples every second until K6 exits.
' timestamp,name,cpu_percent,memory_mb,memory_percent,net_rx_mb,net_tx_mb ' | ForEach-Object { $_.Trim() } | Set-Content -Encoding UTF8 $dockerCsv
$monitorJob = Start-Job -ScriptBlock {
    param($csv)
    while ($true) {
        $timestamp = Get-Date -Format 'yyyy-MM-dd HH:mm:ss.fff'
        $lines = docker stats --no-stream --format '{{.Name}}|{{.CPUPerc}}|{{.MemUsage}}|{{.MemPerc}}|{{.NetIO}}' 2>$null
        foreach ($line in $lines) {
            $parts = $line -split '\|', 5
            if ($parts.Count -lt 5) { continue }
            $name = $parts[0]
            $cpu = [double](($parts[1] -replace '%','').Replace(',','.'))
            $memUsage = $parts[2].Split('/')[0].Trim()
            $memPercent = [double](($parts[3] -replace '%','').Replace(',','.'))
            $net = $parts[4].Split('/')
            $rx = 0.0; $tx = 0.0
            if ($net.Count -ge 2) {
                $rxText = $net[0].Trim(); $txText = $net[1].Trim()
                function ToMB($v) {
                    if ($v -match '([0-9.]+)\s*GB') { return [double]$Matches[1] * 1024 }
                    if ($v -match '([0-9.]+)\s*MB') { return [double]$Matches[1] }
                    if ($v -match '([0-9.]+)\s*KB') { return [double]$Matches[1] / 1024 }
                    return 0.0
                }
                $rx = ToMB $rxText; $tx = ToMB $txText
            }
            $memoryMb = 0.0
            if ($memUsage -match '([0-9.]+)\s*GiB') { $memoryMb = [double]$Matches[1] * 1024 }
            elseif ($memUsage -match '([0-9.]+)\s*GB') { $memoryMb = [double]$Matches[1] * 1024 }
            elseif ($memUsage -match '([0-9.]+)\s*MiB') { $memoryMb = [double]$Matches[1] }
            elseif ($memUsage -match '([0-9.]+)\s*MB') { $memoryMb = [double]$Matches[1] }
            elseif ($memUsage -match '([0-9.]+)\s*KiB') { $memoryMb = [double]$Matches[1] / 1024 }
            elseif ($memUsage -match '([0-9.]+)\s*KB') { $memoryMb = [double]$Matches[1] / 1024 }

            "$timestamp,$name,$cpu,$memoryMb,$memPercent,$rx,$tx" | Add-Content -Encoding UTF8 $csv
        }
        Start-Sleep -Seconds 1
    }
} -ArgumentList $dockerCsv

try {
    Write-Host ''
    Write-Host 'Running 709 concurrent logins...' -ForegroundColor Cyan
    & k6 run $k6Script
    $k6ExitCode = $LASTEXITCODE
} finally {
    if ($monitorJob) {
        Stop-Job $monitorJob -ErrorAction SilentlyContinue
        Remove-Job $monitorJob -Force -ErrorAction SilentlyContinue
    }
}

# Keep raw copies for reproducibility.
Copy-Item $dockerCsv (Join-Path $reportDir 'docker-stats-raw.csv') -Force

# Generate standalone report.
$generator = Join-Path $scriptRoot 'generate-report.py'
if (Get-Command python -ErrorAction SilentlyContinue) {
    python $generator $reportDir
} elseif (Get-Command py -ErrorAction SilentlyContinue) {
    py $generator $reportDir
} else {
    Write-Host 'Python was not found. Raw data is still available.' -ForegroundColor Yellow
}

Write-Host ''
Write-Host '============================================================' -ForegroundColor Green
Write-Host ' TEST FINISHED' -ForegroundColor Green
Write-Host '============================================================' -ForegroundColor Green
Write-Host "Report : $reportDir" -ForegroundColor Green
Write-Host "HTML   : $(Join-Path $reportDir 'summary.html')" -ForegroundColor Green
Write-Host ''

if ($k6ExitCode -ne 0) {
    exit $k6ExitCode
}
