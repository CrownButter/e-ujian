$ErrorActionPreference = 'Stop'

# ============================================================
# E-UJIAN K6 LOAD TEST + DOCKER RESOURCE REPORT
# Windows / MobaXterm compatible
# ============================================================

$scriptRoot = $PSScriptRoot
$repoRoot = (Resolve-Path (Join-Path $scriptRoot '..\..')).Path
$composeFile = Join-Path $scriptRoot 'docker-compose.monitoring.yml'
$k6Script = Join-Path $repoRoot 'tests\load\k6\login-001-709.js'
$reportRoot = Join-Path $repoRoot 'tests\load\results'

$timestamp = Get-Date -Format 'yyyy-MM-dd_HH-mm-ss'
$vus = if ($env:VUS) { [int]$env:VUS } else { 709 }
$iterations = if ($env:ITERATIONS) { [int]$env:ITERATIONS } else { $vus }
$baseUrl = if ($env:BASE_URL) { $env:BASE_URL } else { 'http://localhost:8080' }

if ($vus -lt 1 -or $vus -gt 709) { throw 'VUS must be between 1 and 709.' }
if ($iterations -ne $vus) { throw 'ITERATIONS must equal VUS. The K6 test now uses exactly 1 iteration per VU.' }

$reportDir = Join-Path $reportRoot "${timestamp}_${vus}vu"
$dockerCsv = Join-Path $reportDir 'docker-stats.csv'
$summaryFile = Join-Path $reportDir 'summary.json'
$runMetadata = Join-Path $reportDir 'run.json'
$peakFile = Join-Path $reportDir 'peak-resources.json'
$summaryHtml = Join-Path $reportDir 'summary.html'

New-Item -ItemType Directory -Force -Path $reportDir | Out-Null

Write-Host ''
Write-Host '============================================================' -ForegroundColor Cyan
Write-Host ' E-UJIAN LOAD TEST' -ForegroundColor Cyan
Write-Host '============================================================' -ForegroundColor Cyan
Write-Host "Timestamp  : $timestamp"
Write-Host "BASE_URL   : $baseUrl"
Write-Host "VUS        : $vus"
Write-Host "ITERATIONS : $iterations (1 per VU)"
Write-Host "Accounts   : 001-$('{0:D3}' -f $vus)"
Write-Host "Report     : $reportDir"
Write-Host ''

Write-Host "Checking $baseUrl/login ..." -ForegroundColor Cyan
try {
    $probe = Invoke-WebRequest -Uri "$baseUrl/login" -Method GET -UseBasicParsing -TimeoutSec 10
    if ($probe.StatusCode -ne 200) { throw "HTTP $($probe.StatusCode)" }
    Write-Host "Application responded with HTTP $($probe.StatusCode)." -ForegroundColor Green
} catch {
    Write-Host "Application is not reachable: $($_.Exception.Message)" -ForegroundColor Red
    exit 1
}

if (Test-Path $composeFile) {
    Write-Host 'Starting Prometheus/cAdvisor/Grafana monitoring stack...' -ForegroundColor Cyan
    docker compose -f $composeFile up -d | Out-Host
}

'timestamp,name,cpu_percent,memory_mb,memory_percent,net_rx_mb,net_tx_mb,pids' | Set-Content -Encoding UTF8 $dockerCsv

$monitorJob = Start-Job -ScriptBlock {
    param($csv, $peakPath)

    function ToMB($value) {
        $v = $value.Trim().Replace(',', '.')
        if ($v -match '^([0-9.]+)\s*GiB$') { return [double]$Matches[1] * 1024 }
        if ($v -match '^([0-9.]+)\s*GB$') { return [double]$Matches[1] * 1024 }
        if ($v -match '^([0-9.]+)\s*MiB$') { return [double]$Matches[1] }
        if ($v -match '^([0-9.]+)\s*MB$') { return [double]$Matches[1] }
        if ($v -match '^([0-9.]+)\s*KiB$') { return [double]$Matches[1] / 1024 }
        if ($v -match '^([0-9.]+)\s*KB$') { return [double]$Matches[1] / 1024 }
        if ($v -match '^([0-9.]+)\s*B$') { return [double]$Matches[1] / 1048576 }
        return 0.0
    }

    $peaks = @{}

    while ($true) {
        $sampleTime = Get-Date -Format 'yyyy-MM-dd HH:mm:ss.fff'
        $lines = docker stats --no-stream --format '{{.Name}}|{{.CPUPerc}}|{{.MemUsage}}|{{.MemPerc}}|{{.NetIO}}|{{.PIDs}}' 2>$null
        foreach ($line in $lines) {
            $parts = $line -split '\|', 6
            if ($parts.Count -lt 6) { continue }

            $name = $parts[0]
            $cpu = 0.0
            [double]::TryParse((($parts[1] -replace '%','').Replace(',','.')), [Globalization.NumberStyles]::Float, [Globalization.CultureInfo]::InvariantCulture, [ref]$cpu) | Out-Null
            $memParts = $parts[2] -split '\s*/\s*', 2
            $memMb = if ($memParts.Count -ge 1) { ToMB $memParts[0] } else { 0 }
            $memPercent = 0.0
            [double]::TryParse((($parts[3] -replace '%','').Replace(',','.')), [Globalization.NumberStyles]::Float, [Globalization.CultureInfo]::InvariantCulture, [ref]$memPercent) | Out-Null
            $netParts = $parts[4] -split '\s*/\s*', 2
            $rx = if ($netParts.Count -ge 1) { ToMB $netParts[0] } else { 0 }
            $tx = if ($netParts.Count -ge 2) { ToMB $netParts[1] } else { 0 }
            $pids = 0
            [int]::TryParse($parts[5], [ref]$pids) | Out-Null

            "$sampleTime,$name,$cpu,$memMb,$memPercent,$rx,$tx,$pids" | Add-Content -Encoding UTF8 $csv

            if (-not $peaks.ContainsKey($name)) {
                $peaks[$name] = [ordered]@{
                    cpu_peak_percent = 0.0
                    cpu_peak_at = $null
                    memory_peak_mb = 0.0
                    memory_peak_at = $null
                    memory_peak_percent = 0.0
                    memory_peak_percent_at = $null
                    net_rx_peak_mb = 0.0
                    net_rx_peak_at = $null
                    net_tx_peak_mb = 0.0
                    net_tx_peak_at = $null
                    pids_peak = 0
                    pids_peak_at = $null
                }
            }

            $p = $peaks[$name]
            if ($cpu -gt $p.cpu_peak_percent) { $p.cpu_peak_percent = $cpu; $p.cpu_peak_at = $sampleTime }
            if ($memMb -gt $p.memory_peak_mb) { $p.memory_peak_mb = $memMb; $p.memory_peak_at = $sampleTime }
            if ($memPercent -gt $p.memory_peak_percent) { $p.memory_peak_percent = $memPercent; $p.memory_peak_percent_at = $sampleTime }
            if ($rx -gt $p.net_rx_peak_mb) { $p.net_rx_peak_mb = $rx; $p.net_rx_peak_at = $sampleTime }
            if ($tx -gt $p.net_tx_peak_mb) { $p.net_tx_peak_mb = $tx; $p.net_tx_peak_at = $sampleTime }
            if ($pids -gt $p.pids_peak) { $p.pids_peak = $pids; $p.pids_peak_at = $sampleTime }
        }
        Start-Sleep -Seconds 1
    }
} -ArgumentList $dockerCsv, $peakFile

$start = Get-Date
$k6ExitCode = 1

try {
    Write-Host ''
    Write-Host "Running K6: $vus VUs / 1 iteration per VU..." -ForegroundColor Yellow
    $env:K6_SUMMARY_FILE = $summaryFile
    & k6 run -e "BASE_URL=$baseUrl" -e "VUS=$vus" -e "ITERATIONS=$iterations" $k6Script
    $k6ExitCode = $LASTEXITCODE
} finally {
    if ($monitorJob) {
        Stop-Job $monitorJob -ErrorAction SilentlyContinue | Out-Null
        Receive-Job $monitorJob -ErrorAction SilentlyContinue | Out-Null
        Remove-Job $monitorJob -Force -ErrorAction SilentlyContinue | Out-Null
    }
}

$end = Get-Date
$durationSeconds = [math]::Round(($end - $start).TotalSeconds, 3)

$metadata = [ordered]@{
    test_name = 'E-UJIAN concurrent login'
    started_at = $start.ToString('o')
    finished_at = $end.ToString('o')
    duration_seconds = $durationSeconds
    base_url = $baseUrl
    vus = $vus
    iterations = $iterations
    iterations_per_vu = 1
    executor = 'per-vu-iterations'
    account_range = "001-$('{0:D3}' -f $vus)"
    k6_exit_code = $k6ExitCode
}
$metadata | ConvertTo-Json -Depth 5 | Set-Content -Encoding UTF8 $runMetadata

Copy-Item $dockerCsv (Join-Path $reportDir 'docker-stats-raw.csv') -Force

# Generate peak summary from the collected CSV as a second source of truth.
$generator = Join-Path $scriptRoot 'generate-report.py'
if (Get-Command python -ErrorAction SilentlyContinue) {
    & python $generator $reportDir
} elseif (Get-Command py -ErrorAction SilentlyContinue) {
    & py $generator $reportDir
} else {
    Write-Host 'Python was not found. Raw data and summary.json are still available.' -ForegroundColor Yellow
}

Write-Host ''
Write-Host '============================================================' -ForegroundColor Green
Write-Host ' TEST FINISHED' -ForegroundColor Green
Write-Host '============================================================' -ForegroundColor Green
Write-Host "Report directory : $reportDir" -ForegroundColor Green
Write-Host "HTML report      : $summaryHtml" -ForegroundColor Green
Write-Host "Docker CSV       : $dockerCsv" -ForegroundColor Green
Write-Host "Peak resources   : $peakFile" -ForegroundColor Green
Write-Host ''

if ($k6ExitCode -ne 0) { exit $k6ExitCode }
