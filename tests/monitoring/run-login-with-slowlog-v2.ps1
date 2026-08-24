$ErrorActionPreference = 'Stop'

$scriptRoot = $PSScriptRoot
$phpContainer = if ($env:PHP_CONTAINER) { $env:PHP_CONTAINER } else { 'e-ujian-php' }
$slowlogTimeout = if ($env:PHP_FPM_SLOWLOG_TIMEOUT) { $env:PHP_FPM_SLOWLOG_TIMEOUT } else { '1s' }
$slowlogPath = '/tmp/e-ujian-php-fpm-slowlog.log'
$startedAt = Get-Date

function Invoke-DockerScript([string]$script) {
    $encoded = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($script))
    $output = & docker exec $phpContainer sh -c "printf '%s' '$encoded' | base64 -d | sh" 2>&1 | Out-String
    $exitCode = $LASTEXITCODE
    return [pscustomobject]@{ Output = $output.TrimEnd(); ExitCode = $exitCode }
}

function Invoke-DockerCommand([string]$command) {
    $output = & docker exec $phpContainer sh -c $command 2>&1 | Out-String
    $exitCode = $LASTEXITCODE
    return [pscustomobject]@{ Output = $output.TrimEnd(); ExitCode = $exitCode }
}

function Save-ContainerFile([string]$containerPath, [string]$localPath) {
    $output = & docker exec $phpContainer cat $containerPath 2>&1 | Out-String
    $exitCode = $LASTEXITCODE
    $output | Set-Content -Encoding UTF8 $localPath
    return $exitCode
}

function Save-DiagnosticError([string]$reportDir, [string]$name, [string]$message) {
    $message | Set-Content -Encoding UTF8 (Join-Path $reportDir $name)
}

Write-Host ''
Write-Host '============================================================' -ForegroundColor Cyan
Write-Host ' E-UJIAN PHP-FPM SLOWLOG DIAGNOSTIC RUNNER' -ForegroundColor Cyan
Write-Host '============================================================' -ForegroundColor Cyan
Write-Host "PHP container       : $phpContainer"
Write-Host "Slowlog threshold   : $slowlogTimeout"
Write-Host "Slowlog file        : $slowlogPath"
Write-Host ''

if (-not (docker ps --format '{{.Names}}' | Where-Object { $_ -eq $phpContainer })) {
    throw "PHP container is not running: $phpContainer"
}

$findPoolScript = @'
set -eu
for f in /usr/local/etc/php-fpm.d/*.conf /etc/php*/fpm/pool.d/*.conf /etc/php/*/fpm/pool.d/*.conf; do
  [ -f "$f" ] || continue
  if grep -q '^\[www\]' "$f" 2>/dev/null; then
    printf '%s\n' "$f"
    exit 0
  fi
done
exit 1
'@
$poolResult = Invoke-DockerScript $findPoolScript
if ($poolResult.ExitCode -ne 0) {
    throw "Unable to locate PHP-FPM [www] pool configuration. Exit code $($poolResult.ExitCode):`n$($poolResult.Output)"
}
$poolPath = ($poolResult.Output -split "`r?`n" | Where-Object { $_ -and $_ -notmatch '^ERROR:' } | Select-Object -First 1)
if (-not $poolPath) { throw 'Unable to locate PHP-FPM [www] pool configuration.' }
Write-Host "PHP-FPM pool config : $poolPath" -ForegroundColor Green

$backupPath = "/tmp/e-ujian-php-fpm-www.$([DateTimeOffset]::UtcNow.ToUnixTimeSeconds()).bak"
$patchText = "; E-UJIAN-SLOWLOG-BEGIN`nrequest_slowlog_timeout = $slowlogTimeout`nslowlog = $slowlogPath`nrequest_slowlog_trace_depth = 30`n; E-UJIAN-SLOWLOG-END`n"
$patchB64 = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($patchText))

$configureScript = @"
set -eu
cp -- '$poolPath' '$backupPath'
: > '$slowlogPath'
sed -i '/; E-UJIAN-SLOWLOG-BEGIN/,/; E-UJIAN-SLOWLOG-END/d' '$poolPath'
printf '%s' '$patchB64' | base64 -d >> '$poolPath'
if php-fpm -tt > /tmp/e-ujian-php-fpm-config-test.log 2>&1; then
  cat /tmp/e-ujian-php-fpm-config-test.log
  exit 0
fi
cat /tmp/e-ujian-php-fpm-config-test.log
exit 1
"@
$validation = Invoke-DockerScript $configureScript
if ($validation.ExitCode -ne 0) {
    Invoke-DockerCommand "cp '$backupPath' '$poolPath'" | Out-Null
    throw "PHP-FPM configuration validation failed (exit code $($validation.ExitCode)):`n$($validation.Output)"
}

$reload = Invoke-DockerCommand 'kill -USR2 1'
if ($reload.ExitCode -ne 0) {
    Invoke-DockerCommand "cp '$backupPath' '$poolPath'" | Out-Null
    throw "PHP-FPM reload failed (exit code $($reload.ExitCode)):`n$($reload.Output)"
}
Start-Sleep -Seconds 2
Write-Host 'PHP-FPM slowlog enabled.' -ForegroundColor Green

$runnerExit = 1
$latestReport = $null
try {
    & powershell.exe -ExecutionPolicy Bypass -File (Join-Path $scriptRoot 'run-login-with-monitoring.ps1')
    $runnerExit = if ($null -ne $LASTEXITCODE) { $LASTEXITCODE } else { 0 }
} finally {
    $finishedAt = Get-Date
    $reportRoot = Join-Path $scriptRoot '..\load\results'
    $latestReport = Get-ChildItem -Path (Resolve-Path $reportRoot) -Directory | Sort-Object LastWriteTime -Descending | Select-Object -First 1

    if ($latestReport) {
        $reportDir = $latestReport.FullName

        # Primary evidence: direct file from the PHP container. No docker-log timestamps.
        try {
            Save-ContainerFile $slowlogPath (Join-Path $reportDir 'php-fpm-slowlog.txt') | Out-Null
        } catch {
            Save-DiagnosticError $reportDir 'collector-error-slowlog.txt' "slowlog collection failed: $($_.Exception.Message)"
        }

        try {
            Save-ContainerFile $poolPath (Join-Path $reportDir 'php-fpm-pool-config-after.txt') | Out-Null
        } catch {
            Save-DiagnosticError $reportDir 'collector-error-pool.txt' "pool config collection failed: $($_.Exception.Message)"
        }

        try {
            Save-ContainerFile '/tmp/e-ujian-php-fpm-config-test.log' (Join-Path $reportDir 'php-fpm-config-test.txt') | Out-Null
        } catch {
            Save-DiagnosticError $reportDir 'collector-error-config.txt' "config test collection failed: $($_.Exception.Message)"
        }

        # PID/state diagnostics are collected without ps, awk, grep, or command substitution.
        $procScript = @'
set +e
printf '%s\n' 'timestamp|pid|ppid|state|utime|stime|rss_kb|cmdline'
TS=$(date '+%Y-%m-%dT%H:%M:%S.%3N%z' 2>/dev/null || date)
for p in /proc/[0-9]*; do
  [ -r "$p/cmdline" ] || continue
  cmd=$(tr '\0' ' ' < "$p/cmdline" 2>/dev/null)
  case "$cmd" in
    *"php-fpm: pool www"*)
      pid=${p##*/}
      stat=$(cat "$p/stat" 2>/dev/null)
      ppid=$(printf '%s\n' "$stat" | cut -d' ' -f4 2>/dev/null)
      state=$(printf '%s\n' "$stat" | cut -d' ' -f3 2>/dev/null)
      utime=$(printf '%s\n' "$stat" | cut -d' ' -f14 2>/dev/null)
      stime=$(printf '%s\n' "$stat" | cut -d' ' -f15 2>/dev/null)
      rss=$(awk '/^VmRSS:/ {print $2}' "$p/status" 2>/dev/null)
      printf '%s|%s|%s|%s|%s|%s|%s|%s\n' "$TS" "$pid" "$ppid" "$state" "$utime" "$stime" "$rss" "$cmd"
      ;;
  esac
done
'@
        try {
            $procResult = Invoke-DockerScript $procScript
            if ($procResult.ExitCode -eq 0) {
                $procResult.Output | Set-Content -Encoding UTF8 (Join-Path $reportDir 'php-fpm-worker-pids-diagnostic.txt')
            } else {
                Save-DiagnosticError $reportDir 'collector-error-pid.txt' "PID diagnostic failed (exit $($procResult.ExitCode)): $($procResult.Output)"
            }
        } catch {
            Save-DiagnosticError $reportDir 'collector-error-pid.txt' "PID diagnostic failed: $($_.Exception.Message)"
        }

        # Runtime snapshot without shell pipelines.
        $runtimeScript = @'
set +e
printf '%s\n' '=== PHP VERSION ==='
php -v 2>&1
printf '%s\n' '=== PHP-FPM VERSION ==='
php-fpm -v 2>&1
printf '%s\n' '=== PHP-FPM CONFIG TEST ==='
php-fpm -tt 2>&1
printf '%s\n' '=== PROCESS TREE (PROCFS) ==='
for p in /proc/[0-9]*; do
  [ -r "$p/cmdline" ] || continue
  cmd=$(tr '\0' ' ' < "$p/cmdline" 2>/dev/null)
  case "$cmd" in
    *php-fpm*) printf 'PID=%s CMD=%s\n' "${p##*/}" "$cmd";;
  esac
done
printf '%s\n' '=== FILESYSTEM ==='
df -h / /tmp 2>&1
printf '%s\n' '=== MOUNTS ==='
cat /proc/mounts 2>/dev/null
'@
        try {
            $runtimeResult = Invoke-DockerScript $runtimeScript
            $runtimeResult.Output | Set-Content -Encoding UTF8 (Join-Path $reportDir 'php-runtime-diagnostic.txt')
        } catch {
            Save-DiagnosticError $reportDir 'collector-error-runtime.txt' "runtime diagnostic failed: $($_.Exception.Message)"
        }

        # Container log is a secondary source only; no --since parsing.
        try {
            docker logs $phpContainer 2>&1 | Select-Object -Last 5000 | Set-Content -Encoding UTF8 (Join-Path $reportDir 'php-fpm-container-log-tail.txt')
        } catch {
            Save-DiagnosticError $reportDir 'collector-error-container-log.txt' "container log collection failed: $($_.Exception.Message)"
        }

        # Resource snapshots.
        try {
            docker stats $phpContainer --no-stream --format 'name={{.Name}} cpu={{.CPUPerc}} mem={{.MemUsage}} net={{.NetIO}} block={{.BlockIO}} pids={{.PIDs}}' | Set-Content -Encoding UTF8 (Join-Path $reportDir 'php-final-stats.txt')
        } catch {
            Save-DiagnosticError $reportDir 'collector-error-php-stats.txt' "PHP stats collection failed: $($_.Exception.Message)"
        }

        $redisName = if ($env:REDIS_CONTAINER) { $env:REDIS_CONTAINER } else { 'e-ujian-redis' }
        if (docker ps --format '{{.Names}}' | Where-Object { $_ -eq $redisName }) {
            docker stats $redisName --no-stream --format 'name={{.Name}} cpu={{.CPUPerc}} mem={{.MemUsage}} net={{.NetIO}} block={{.BlockIO}} pids={{.PIDs}}' | Set-Content -Encoding UTF8 (Join-Path $reportDir 'redis-final-stats.txt')
            docker exec $redisName redis-cli INFO stats 2>&1 | Set-Content -Encoding UTF8 (Join-Path $reportDir 'redis-info-stats.txt')
        }
        if (docker ps --format '{{.Names}}' | Where-Object { $_ -eq 'e-ujian-mysql' }) {
            docker stats 'e-ujian-mysql' --no-stream --format 'name={{.Name}} cpu={{.CPUPerc}} mem={{.MemUsage}} net={{.NetIO}} block={{.BlockIO}} pids={{.PIDs}}' | Set-Content -Encoding UTF8 (Join-Path $reportDir 'mysql-final-stats.txt')
        }

        # Save a machine-readable diagnostic manifest.
        $slowlogFile = Join-Path $reportDir 'php-fpm-slowlog.txt'
        $slowlogEntries = 0
        if (Test-Path $slowlogFile) {
            $slowlogEntries = @(Get-Content $slowlogFile | Where-Object { $_ -match 'script_filename|pool www|request slowlog|child' }).Count
        }
        [ordered]@{
            enabled_at = $startedAt.ToString('o')
            finished_at = $finishedAt.ToString('o')
            php_container = $phpContainer
            pool_config = $poolPath
            slowlog_timeout = $slowlogTimeout
            slowlog_destination = $slowlogPath
            slowlog_entry_hint_count = $slowlogEntries
            runner_exit_code = $runnerExit
        } | ConvertTo-Json -Depth 4 | Set-Content -Encoding UTF8 (Join-Path $reportDir 'php-fpm-slowlog.json')
    }

    try {
        $restore = Invoke-DockerCommand "cp '$backupPath' '$poolPath'"
        if ($restore.ExitCode -ne 0) { throw "restore exit code $($restore.ExitCode): $($restore.Output)" }
        $reloadRestore = Invoke-DockerCommand 'kill -USR2 1'
        if ($reloadRestore.ExitCode -ne 0) { throw "reload exit code $($reloadRestore.ExitCode): $($reloadRestore.Output)" }
        Write-Host 'PHP-FPM original pool configuration restored.' -ForegroundColor Green
    } catch {
        Write-Host "WARNING: restore failed: $($_.Exception.Message)" -ForegroundColor Red
    }
}

if ($runnerExit -ne 0) { exit $runnerExit }
