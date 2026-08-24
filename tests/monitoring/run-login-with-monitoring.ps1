$ErrorActionPreference = 'Stop'

# ============================================================
# E-UJIAN K6 LOAD TEST + DOCKER/TCP/PHP-FPM PROFILING
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
$vus = if ($env:VUS) { [int]$env:VUS } else { 20 }
$iterations = if ($env:ITERATIONS) { [int]$env:ITERATIONS } else { $vus }
$baseUrl = if ($env:BASE_URL) { $env:BASE_URL } else { 'http://localhost:8080' }
$tcpPort = if ($env:TCP_PORT) { [int]$env:TCP_PORT } else { 8080 }
$phpContainer = if ($env:PHP_CONTAINER) { $env:PHP_CONTAINER } else { 'e-ujian-php' }

if ($vus -lt 1 -or $vus -gt 709) { throw 'VUS must be between 1 and 709.' }
if ($k6Mode -eq 'burst' -and $iterations -ne $vus) { throw 'ITERATIONS must equal VUS for burst mode.' }
if (-not (Test-Path $k6Script)) { throw "K6 script not found: $k6Script" }

$reportDir = Join-Path $reportRoot "${timestamp}_${vus}vu_${k6Mode}"
$dockerCsv = Join-Path $reportDir 'docker-stats.csv'
$tcpCsv = Join-Path $reportDir 'tcp-monitor.csv'
$containerCsv = Join-Path $reportDir 'container-monitor.csv'
$phpFpmCsv = Join-Path $reportDir 'php-fpm-workers.csv'
$phpFpmBefore = Join-Path $reportDir 'php-fpm-config-before.txt'
$phpFpmAfter = Join-Path $reportDir 'php-fpm-config-after.txt'
$phpProcessBefore = Join-Path $reportDir 'php-processes-before.txt'
$phpProcessAfter = Join-Path $reportDir 'php-processes-after.txt'
$phpRuntimeBefore = Join-Path $reportDir 'php-runtime-before.txt'
$phpRuntimeAfter = Join-Path $reportDir 'php-runtime-after.txt'
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
Write-Host "PHP        : $phpContainer"
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

function Invoke-PhpExec([string]$command) {
    try {
        return (& docker exec $phpContainer sh -c $command 2>&1 | Out-String).TrimEnd()
    } catch {
        return "ERROR: $($_.Exception.Message)"
    }
}

Write-Host "Capturing PHP-FPM configuration/process state from $phpContainer ..." -ForegroundColor Cyan
Set-Content -Encoding UTF8 $phpFpmBefore -Value (Invoke-PhpExec 'php-fpm -tt 2>&1 || php-fpm8.3 -tt 2>&1 || php-fpm8.2 -tt 2>&1 || php-fpm8.1 -tt 2>&1')
Set-Content -Encoding UTF8 $phpProcessBefore -Value (Invoke-PhpExec 'ps -eo pid,ppid,stat,etime,pcpu,pmem,args --sort=-pcpu')
Set-Content -Encoding UTF8 $phpRuntimeBefore -Value (Invoke-PhpExec 'php -i | grep -E "^(PHP Version|memory_limit|opcache.enable|opcache.memory_consumption|realpath_cache_size|session.save_handler|session.save_path)" || true')

Set-Content -Encoding UTF8 $dockerCsv 'timestamp,name,cpu_percent,memory_mb,memory_percent,net_rx_mb,net_tx_mb,pids'
Set-Content -Encoding UTF8 $tcpCsv 'timestamp,port,listening,established,time_wait,syn_sent,syn_received,close_wait,fin_wait_1,fin_wait_2'
Set-Content -Encoding UTF8 $containerCsv 'timestamp,name,status,running,restarts'
Set-Content -Encoding UTF8 $phpFpmCsv 'timestamp,total_php_processes,php_fpm_master,php_fpm_workers,php_worker_r,php_worker_s,php_worker_d,php_worker_z'

$monitorJob = Start-Job -ScriptBlock {
    param($dockerCsvPath, $tcpPath, $containerPath, $phpPath, $tcpPortValue, $phpContainerName)

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
            foreach ($connection in (Get-NetTCPConnection -LocalPort $port -ErrorAction Stop)) {
                $state = [string]$connection.State
                if ($counts.ContainsKey($state)) { $counts[$state]++ }
            }
        } catch { }
        return $counts
    }

    function GetPhpFpmSnapshot($container) {
        $result = [ordered]@{ total=0; master=0; workers=0; r=0; s=0; d=0; z=0 }
        try {
            $lines = docker exec $container sh -c 'ps -eo stat,args' 2>$null
            foreach ($line in $lines) {
                if ($line -notmatch 'php-fpm') { continue }
                $result.total++
                $parts = $line -split '\s+', 2
                $stat = if ($parts.Count -gt 0) { $parts[0] } else { '' }
                if ($line -match 'master process') { $result.master++; continue }
                if ($line -match 'pool ') {
                    $result.workers++
                    $state = if ($stat.Length -gt 0) { $stat.Substring(0,1) } else { '' }
                    if ($state -eq 'R') { $result.r++ }
                    elseif ($state -eq 'S') { $result.s++ }
                    elseif ($state -eq 'D') { $result.d++ }
                    elseif ($state -eq 'Z') { $result.z++ }
                }
            }
        } catch { }
        return $result
    }

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
            "$sampleTime,$name,$cpu,$memMb,$memPercent,$rx,$tx,$pids" | Add-Content -Encoding UTF8 $dockerCsvPath
        }

        $tcp = GetTcpCounts $tcpPortValue
        "$sampleTime,$tcpPortValue,$($tcp.Listen),$($tcp.Established),$($tcp.TimeWait),$($tcp.SynSent),$($tcp.SynReceived),$($tcp.CloseWait),$($tcp.FinWait1),$($tcp.FinWait2)" | Add-Content -Encoding UTF8 $tcpPath

        $php = GetPhpFpmSnapshot $phpContainerName
        "$sampleTime,$($php.total),$($php.master),$($php.workers),$($php.r),$($php.s),$($php.d),$($php.z)" | Add-Content -Encoding UTF8 $phpPath

        try {
            foreach ($line in (docker ps -a --format '{{.Names}}|{{.Status}}' 2>$null)) {
                $parts = $line -split '\|', 2
                if ($parts.Count -lt 2) { continue }
                $name = $parts[0]
                $status = $parts[1].Replace(',',';')
                $running = if ($status -like 'Up *') { 1 } else { 0 }
                $restarts = docker inspect -f '{{.RestartCount}}' $name 2>$null
                if (-not $restarts) { $restarts = 0 }
                "$sampleTime,$name,$status,$running,$restarts" | Add-Content -Encoding UTF8 $containerPath
            }
        } catch { }

        Start-Sleep -Milliseconds 750
    }
} -ArgumentList $dockerCsv, $tcpCsv, $containerCsv, $phpFpmCsv, $tcpPort, $phpContainer

$start = Get-Date
$k6ExitCode = 1
try {
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

Write-Host 'Capturing PHP-FPM post-test diagnostics...' -ForegroundColor Cyan
Set-Content -Encoding UTF8 $phpFpmAfter -Value (Invoke-PhpExec 'php-fpm -tt 2>&1 || php-fpm8.3 -tt 2>&1 || php-fpm8.2 -tt 2>&1 || php-fpm8.1 -tt 2>&1')
Set-Content -Encoding UTF8 $phpProcessAfter -Value (Invoke-PhpExec 'ps -eo pid,ppid,stat,etime,pcpu,pmem,args --sort=-pcpu')
Set-Content -Encoding UTF8 $phpRuntimeAfter -Value (Invoke-PhpExec 'php -i | grep -E "^(PHP Version|memory_limit|opcache.enable|opcache.memory_consumption|realpath_cache_size|session.save_handler|session.save_path)" || true')

# The container sends PHP-FPM error output to stderr. docker logs is the reliable
# source from the host for max_children/worker errors.
try {
    docker logs --tail 500 $phpContainer 2>&1 | Set-Content -Encoding UTF8 (Join-Path $reportDir 'php-fpm-log-tail.txt')
} catch {
    "docker logs failed: $($_.Exception.Message)" | Set-Content -Encoding UTF8 (Join-Path $reportDir 'php-fpm-log-tail.txt')
}

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
    php_container = $phpContainer
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
Write-Host "PHP-FPM CSV      : $phpFpmCsv" -ForegroundColor Green
Write-Host "Peak resources   : $peakFile" -ForegroundColor Green
Write-Host ''

if ($k6ExitCode -ne 0) { exit $k6ExitCode }
