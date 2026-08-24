$ErrorActionPreference = 'Stop'

# ============================================================
# E-UJIAN K6 LOAD TEST + DOCKER/TCP RESOURCE REPORT
# Windows / MobaXterm compatible
# ============================================================

$scriptRoot = $PSScriptRoot
$repoRoot = (Resolve-Path (Join-Path $scriptRoot '..\..')).Path
$composeFile = Join-Path $scriptRoot 'docker-compose.monitoring.yml'
$k6Mode = if ($env:K6_MODE) { $env:K6_MODE } else { 'burst' }
$k6ScriptName = if ($k6Mode -eq 'ramp') { 'login-ramp-001-709.js' } else { 'login-001-709.js' }
$k6Script = Join-Path $repoRoot "tests\load\k6\$k6ScriptName"
$reportRoot = Join-Path $repoRoot 'tests\load\results'

$timestamp = Get-Date -Format 'yyyy-MM-dd_HH-mm-ss'
$vus = if ($env:VUS) { [int]$env:VUS } else { 709 }
$iterations = if ($env:ITERATIONS) { [int]$env:ITERATIONS } else { $vus }
$baseUrl = if ($env:BASE_URL) { $env:BASE_URL } else { 'http://localhost:8080' }
$tcpPort = if ($env:TCP_PORT) { [int]$env:TCP_PORT } else { 8080 }

if ($vus -lt 1 -or $vus -gt 709) { throw 'VUS must be between 1 and 709.' }
if ($k6Mode -eq 'burst' -and $iterations -ne $vus) { throw 'ITERATIONS must equal VUS for burst mode.' }
if (-not (Test-Path $k6Script)) { throw "K6 script not found: $k6Script" }

$reportDir = Join-Path $reportRoot "${timestamp}_${vus}vu_${k6Mode}"
$dockerCsv = Join-Path $reportDir 'docker-stats.csv'
$tcpCsv = Join-Path $reportDir 'tcp-monitor.csv'
$containerCsv = Join-Path $reportDir 'container-monitor.csv'
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
Write-Host "MODE       : $k6Mode"
Write-Host "BASE_URL   : $baseUrl"
Write-Host "VUS        : $vus"
if ($k6Mode -eq 'burst') { Write-Host "ITERATIONS : $iterations (1 per VU)" }
Write-Host "K6 SCRIPT  : $k6ScriptName"
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

'timestamp,port,listening,established,time_wait,syn_sent,syn_received,close_wait,fin_wait_1,fin_wait_2' | Set-Content -Encoding UTF8 $tcpCsv
'timestamp,name,status,running,restarts' | Set-Content -Encoding UTF8 $containerCsv

$monitorJob = Start-Job -ScriptBlock {
    param($csv, $tcpPath, $containerPath, $peakPath, $tcpPort)

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

    function GetTcpCounts($port) {
        $states = @('Listen','Established','TimeWait','SynSent','SynReceived','CloseWait','FinWait1','FinWait2')
        $counts = @{}
        foreach ($state in $states) { $counts[$state] = 0 }
        try {
            $connections = Get-NetTCPConnection -LocalPort $port -ErrorAction Stop
            foreach ($connection in $connections) {
                $state = [string]$connection.State
                if ($counts.ContainsKey($state)) { $counts[$state]++ }
            }
        } catch { }
        return $counts
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
                $peaks[$name] = [ordered]@{ cpu_peak_percent=0.0; cpu_peak_at=$null; memory_peak_mb=0.0; memory_peak_at=$null; memory_peak_percent=0.0; memory_peak_percent_at=$null; net_rx_peak_mb=0.0; net_rx_peak_at=$null; net_tx_peak_mb=0.0; net_tx_peak_at=$null; pids_peak=0; pids_peak_at=$null }
            }
            $p = $peaks[$name]
            if ($cpu -gt $p.cpu_peak_percent) { $p.cpu_peak_percent=$cpu; $p.cpu_peak_at=$sampleTime }
            if ($memMb -gt $p.memory_peak_mb) { $p.memory_peak_mb=$memMb; $p.memory_peak_at=$sampleTime }
            if ($memPercent -gt $p.memory_peak_percent) { $p.memory_peak_percent=$memPercent; $p.memory_peak_percent_at=$sampleTime }
            if ($rx -gt $p.net_rx_peak_mb) { $p.net_rx_peak_mb=$rx; $p.net_rx_peak_at=$sampleTime }
            if ($tx -gt $p.net_tx_peak_mb) { $p.net_tx_peak_mb=$tx; $p.net_tx_peak_at=$sampleTime }
            if ($pids -gt $p.pids_peak) { $p.pids_peak=$pids; $p.pids_peak_at=$sampleTime }
        }

        $tcp = GetTcpCounts $tcpPort
        "$sampleTime,$tcpPort,$($tcp.Listen),$($tcp.Established),$($tcp.TimeWait),$($tcp.SynSent),$($tcp.SynReceived),$($tcp.CloseWait),$($tcp.FinWait1),$($tcp.FinWait2)" | Add-Content -Encoding UTF8 $tcpPath

        try {
            $containers = docker ps -a --format '{{.Names}}|{{.Status}}' 2>$null
            foreach ($line in $containers) {
                $parts = $line -split '\|', 2
                if ($parts.Count -lt 2) { continue }
                $name = $parts[0]
                $status = $parts[1].Replace(',',';')
                $running = if ($status -like 'Up *') { 1 } else { 0 }
                $inspect = docker inspect -f '{{.RestartCount}}' $name 2>$null
                if (-not $inspect) { $inspect = 0 }
                "$sampleTime,$name,$status,$running,$inspect" | Add-Content -Encoding UTF8 $containerPath
            }
        } catch { }

        Start-Sleep -Milliseconds 500
    }
} -ArgumentList $dockerCsv, $tcpCsv, $containerCsv, $peakFile, $tcpPort

$start = Get-Date
$k6ExitCode = 1

try {
    Write-Host ''
    if ($k6Mode -eq 'ramp') {
        Write-Host "Running K6 RAMP-UP: 0 -> $vus VUs..." -ForegroundColor Yellow
        $env:K6_SUMMARY_FILE = $summaryFile
        & k6 run -e "BASE_URL=$baseUrl" -e "VUS=$vus" $k6Script
    } else {
        Write-Host "Running K6 BURST: $vus VUs / 1 iteration per VU..." -ForegroundColor Yellow
        $env:K6_SUMMARY_FILE = $summaryFile
        & k6 run -e "BASE_URL=$baseUrl" -e "VUS=$vus" -e "ITERATIONS=$iterations" $k6Script
    }
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
    mode = $k6Mode
    started_at = $start.ToString('o')
    finished_at = $end.ToString('o')
    duration_seconds = $durationSeconds
    base_url = $baseUrl
    vus = $vus
    iterations = if ($k6Mode -eq 'burst') { $iterations } else { $null }
    iterations_per_vu = if ($k6Mode -eq 'burst') { 1 } else { $null }
    executor = if ($k6Mode -eq 'burst') { 'per-vu-iterations' } else { 'ramping-vus' }
    account_range = "001-$('{0:D3}' -f $vus)"
    tcp_port = $tcpPort
    k6_script = $k6ScriptName
    k6_exit_code = $k6ExitCode
}
$metadata | ConvertTo-Json -Depth 5 | Set-Content -Encoding UTF8 $runMetadata

Copy-Item $dockerCsv (Join-Path $reportDir 'docker-stats-raw.csv') -Force

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
Write-Host "TCP CSV          : $tcpCsv" -ForegroundColor Green
Write-Host "Container CSV    : $containerCsv" -ForegroundColor Green
Write-Host "Peak resources   : $peakFile" -ForegroundColor Green
Write-Host ''

if ($k6ExitCode -ne 0) { exit $k6ExitCode }
