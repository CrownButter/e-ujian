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
$phpFpmRawCsv = Join-Path $reportDir 'php-fpm-workers-raw.csv'
$phpPidCsv = Join-Path $reportDir 'php-fpm-worker-pids.csv'
$phpFpmBefore = Join-Path $reportDir 'php-fpm-config-before.txt'
$phpFpmAfter = Join-Path $reportDir 'php-fpm-config-after.txt'
$phpProcessBefore = Join-Path $reportDir 'php-processes-before.txt'
$phpProcessAfter = Join-Path $reportDir 'php-processes-after.txt'
$phpRuntimeBefore = Join-Path $reportDir 'php-runtime-before.txt'
$phpRuntimeAfter = Join-Path $reportDir 'php-runtime-after.txt'
$summaryFile = Join-Path $reportDir 'summary.json'
$runMetadata = Join-Path $reportDir 'run.json'
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

function Get-PhpFpmProcSnapshot {
    $script = @'
count=0
master=0
running=0
sleeping=0
disk_sleep=0
zombie=0
other=0
cpu_ticks=0
rss_kb=0
pids=""
for p in /proc/[0-9]*; do
    [ -r "$p/cmdline" ] || continue
    cmd=$(tr "\0" " " < "$p/cmdline" 2>/dev/null)
    case "$cmd" in
        *"php-fpm: master process"*)
            master=$((master+1))
            ;;
        *"php-fpm: pool www"*)
            count=$((count+1))
            pid=${p##*/}
            stat_line=$(cat "$p/stat" 2>/dev/null)
            state=$(echo "$stat_line" | awk '{print $3}')
            case "$state" in
                R) running=$((running+1));;
                S) sleeping=$((sleeping+1));;
                D) disk_sleep=$((disk_sleep+1));;
                Z) zombie=$((zombie+1));;
                *) other=$((other+1));;
            esac
            utime=$(echo "$stat_line" | awk '{print $14}')
            stime=$(echo "$stat_line" | awk '{print $15}')
            [ -n "$utime" ] && [ -n "$stime" ] && cpu_ticks=$((cpu_ticks + utime + stime))
            rss=$(awk '/^VmRSS:/ {print $2}' "$p/status" 2>/dev/null)
            [ -n "$rss" ] && rss_kb=$((rss_kb + rss))
            pids="${pids}${pids:+,}$pid"
            ;;
    esac
done
printf '%s|%s|%s|%s|%s|%s|%s|%s|%s|%s\n' "$count" "$master" "$running" "$sleeping" "$disk_sleep" "$zombie" "$other" "$cpu_ticks" "$rss_kb" "$pids"
'@
    $encoded = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($script))
    $result = Invoke-PhpExec "echo $encoded | base64 -d | sh"
    if ($result -match '^([0-9]+)\|([0-9]+)\|([0-9]+)\|([0-9]+)\|([0-9]+)\|([0-9]+)\|([0-9]+)\|([0-9]+)\|([0-9]+)\|(.*)$') {
        return [pscustomobject]@{
            workers = [int]$Matches[1]
            master = [int]$Matches[2]
            running = [int]$Matches[3]
            sleeping = [int]$Matches[4]
            disk_sleep = [int]$Matches[5]
            zombie = [int]$Matches[6]
            other = [int]$Matches[7]
            cpu_ticks = [long]$Matches[8]
            rss_kb = [long]$Matches[9]
            pids = $Matches[10]
        }
    }
    return [pscustomobject]@{ workers=0; master=0; running=0; sleeping=0; disk_sleep=0; zombie=0; other=0; cpu_ticks=0; rss_kb=0; pids='' }
}

Write-Host "Capturing PHP-FPM configuration/process state from $phpContainer ..." -ForegroundColor Cyan
Set-Content -Encoding UTF8 $phpFpmBefore -Value (Invoke-PhpExec 'php-fpm -tt 2>&1 || php-fpm8.3 -tt 2>&1 || php-fpm8.2 -tt 2>&1 || php-fpm8.1 -tt 2>&1')
Set-Content -Encoding UTF8 $phpRuntimeBefore -Value (Invoke-PhpExec 'php -i 2>/dev/null | grep -E "^(PHP Version|memory_limit|opcache.enable|opcache.memory_consumption|realpath_cache_size|session.save_handler|session.save_path)" || true')
Set-Content -Encoding UTF8 $phpProcessBefore -Value (Invoke-PhpExec 'for p in /proc/[0-9]*; do [ -r "$p/cmdline" ] || continue; cmd=$(tr "\0" " " < "$p/cmdline" 2>/dev/null); case "$cmd" in *"php-fpm"*) echo "$p $cmd";; esac; done')

Set-Content -Encoding UTF8 $dockerCsv 'timestamp,name,cpu_percent,memory_mb,memory_percent,net_rx_mb,net_tx_mb,pids'
Set-Content -Encoding UTF8 $tcpCsv 'timestamp,port,listening,established,time_wait,syn_sent,syn_received,close_wait,fin_wait_1,fin_wait_2'
Set-Content -Encoding UTF8 $containerCsv 'timestamp,name,status,running,restarts'
Set-Content -Encoding UTF8 $phpFpmCsv 'timestamp,workers,master,running,sleeping,disk_sleep,zombie,other,cpu_ticks,rss_kb,pid_count'
Set-Content -Encoding UTF8 $phpFpmRawCsv 'timestamp,workers,master,running,sleeping,disk_sleep,zombie,other,cpu_ticks,rss_kb,pids'
Set-Content -Encoding UTF8 $phpPidCsv 'timestamp,pid,state,cpu_ticks,rss_kb,cmdline'

$monitorJob = Start-Job -ScriptBlock {
    param($dockerCsvPath, $tcpPath, $containerPath, $phpPath, $phpRawPath, $phpPidPath, $tcpPortValue, $phpContainerName)

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

        $workerScript = @'
workers=0; master=0; running=0; sleeping=0; disk_sleep=0; zombie=0; other=0; cpu_ticks=0; rss_kb=0; pids="";
for p in /proc/[0-9]*; do
  [ -r "$p/cmdline" ] || continue
  cmd=$(tr "\0" " " < "$p/cmdline" 2>/dev/null)
  case "$cmd" in
    *"php-fpm: master process"*) master=$((master+1));;
    *"php-fpm: pool www"*)
      workers=$((workers+1)); pid=${p##*/}; stat_line=$(cat "$p/stat" 2>/dev/null); state=$(echo "$stat_line" | awk '{print $3}');
      case "$state" in R) running=$((running+1));; S) sleeping=$((sleeping+1));; D) disk_sleep=$((disk_sleep+1));; Z) zombie=$((zombie+1));; *) other=$((other+1));; esac
      u=$(echo "$stat_line" | awk '{print $14}'); s=$(echo "$stat_line" | awk '{print $15}'); [ -n "$u" ] && [ -n "$s" ] && cpu_ticks=$((cpu_ticks+u+s));
      r=$(awk '/^VmRSS:/ {print $2}' "$p/status" 2>/dev/null); [ -n "$r" ] && rss_kb=$((rss_kb+r));
      pids="${pids}${pids:+,}$pid";;
  esac
done
printf '%s|%s|%s|%s|%s|%s|%s|%s|%s|%s\n' "$workers" "$master" "$running" "$sleeping" "$disk_sleep" "$zombie" "$other" "$cpu_ticks" "$rss_kb" "$pids"
'@
        $b64 = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($workerScript))
        $workerResult = (& docker exec $phpContainerName sh -c "echo $b64 | base64 -d | sh" 2>$null | Out-String).TrimEnd()
        if ($workerResult -match '^([0-9]+)\|([0-9]+)\|([0-9]+)\|([0-9]+)\|([0-9]+)\|([0-9]+)\|([0-9]+)\|([0-9]+)\|([0-9]+)\|(.*)$') {
            "$sampleTime,$($Matches[1]),$($Matches[2]),$($Matches[3]),$($Matches[4]),$($Matches[5]),$($Matches[6]),$($Matches[7]),$($Matches[8]),$($Matches[9]),$([regex]::Matches($Matches[10], ',').Count + ($(if ($Matches[10]) {1}else{0})) )" | Add-Content -Encoding UTF8 $phpPath
            "$sampleTime,$($Matches[1]),$($Matches[2]),$($Matches[3]),$($Matches[4]),$($Matches[5]),$($Matches[6]),$($Matches[7]),$($Matches[8]),$($Matches[9]),$($Matches[10])" | Add-Content -Encoding UTF8 $phpRawPath

            foreach ($pid in ($Matches[10] -split ',' | Where-Object { $_ })) {
                $pidScript = "if [ -r /proc/$pid/stat ]; then stat=\$(cat /proc/$pid/stat 2>/dev/null); state=\$(echo \"\$stat\" | awk '{print \$3}'); u=\$(echo \"\$stat\" | awk '{print \$14}'); s=\$(echo \"\$stat\" | awk '{print \$15}'); rss=\$(awk '/^VmRSS:/ {print \$2}' /proc/$pid/status 2>/dev/null); cmd=\$(tr '\0' ' ' < /proc/$pid/cmdline 2>/dev/null); printf '%s|%s|%s|%s|%s\n' \"\$state\" \"\$u\" \"\$s\" \"\$rss\" \"\$cmd\"; fi"
                $pidResult = (& docker exec $phpContainerName sh -c $pidScript 2>$null | Out-String).TrimEnd()
                if ($pidResult -match '^([^|]*)\|([^|]*)\|([^|]*)\|([^|]*)\|(.*)$') {
                    $ticks = 0
                    [long]::TryParse($Matches[2], [ref]$u) | Out-Null
                    $sTicks = 0
                    [long]::TryParse($Matches[3], [ref]$sTicks) | Out-Null
                    $ticks = $u + $sTicks
                    $rss = 0
                    [long]::TryParse($Matches[4], [ref]$rss) | Out-Null
                    "$sampleTime,$pid,$($Matches[1]),$ticks,$rss,$($Matches[5] -replace ',',';')" | Add-Content -Encoding UTF8 $phpPidPath
                }
            }
        }

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

        Start-Sleep -Milliseconds 200
    }
} -ArgumentList $dockerCsv, $tcpCsv, $containerCsv, $phpFpmCsv, $phpFpmRawCsv, $phpPidCsv, $tcpPort, $phpContainer

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
Set-Content -Encoding UTF8 $phpRuntimeAfter -Value (Invoke-PhpExec 'php -i 2>/dev/null | grep -E "^(PHP Version|memory_limit|opcache.enable|opcache.memory_consumption|realpath_cache_size|session.save_handler|session.save_path)" || true')
Set-Content -Encoding UTF8 $phpProcessAfter -Value (Invoke-PhpExec 'for p in /proc/[0-9]*; do [ -r "$p/cmdline" ] || continue; cmd=$(tr "\0" " " < "$p/cmdline" 2>/dev/null); case "$cmd" in *"php-fpm"*) echo "$p $cmd";; esac; done')

try {
    docker logs --tail 500 $phpContainer 2>&1 | Set-Content -Encoding UTF8 (Join-Path $reportDir 'php-fpm-log-tail.txt')
} catch {
    "docker logs failed: $($_.Exception.Message)" | Set-Content -Encoding UTF8 (Join-Path $reportDir 'php-fpm-log-tail.txt')
}

& docker stats --no-stream --format '{{json .}}' | Set-Content -Path (Join-Path $reportDir 'docker-stats-raw.jsonl') -Encoding UTF8

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
    php_fpm_sampling_ms = 200
    php_fpm_detection = '/proc/[pid]/cmdline contains php-fpm: pool www'
    k6_script = $k6ScriptName
    k6_exit_code = $k6ExitCode
}
$metadata | ConvertTo-Json -Depth 5 | Set-Content -Encoding UTF8 $runMetadata

Write-Host ''
Write-Host '============================================================' -ForegroundColor Green
Write-Host ' TEST FINISHED' -ForegroundColor Green
Write-Host '============================================================' -ForegroundColor Green
Write-Host "Report directory : $reportDir" -ForegroundColor Green
Write-Host "PHP-FPM CSV      : $phpFpmCsv" -ForegroundColor Green
Write-Host "PHP-FPM Raw CSV  : $phpFpmRawCsv" -ForegroundColor Green
Write-Host "PHP-FPM PID CSV  : $phpPidCsv" -ForegroundColor Green
Write-Host "Docker CSV       : $dockerCsv" -ForegroundColor Green
Write-Host "TCP CSV          : $tcpCsv" -ForegroundColor Green
Write-Host "Container CSV    : $containerCsv" -ForegroundColor Green
Write-Host "K6 exit code     : $k6ExitCode" -ForegroundColor Green

exit $k6ExitCode
