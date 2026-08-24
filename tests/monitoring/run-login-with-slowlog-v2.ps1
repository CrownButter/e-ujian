$ErrorActionPreference = 'Stop'

$scriptRoot = $PSScriptRoot
$phpContainer = if ($env:PHP_CONTAINER) { $env:PHP_CONTAINER } else { 'e-ujian-php' }
$slowlogTimeout = if ($env:PHP_FPM_SLOWLOG_TIMEOUT) { $env:PHP_FPM_SLOWLOG_TIMEOUT } else { '1s' }
$slowlogPath = '/tmp/e-ujian-php-fpm-slowlog.log'
$startedAt = Get-Date

function Invoke-PhpScript([string]$script) {
    $encoded = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($script))
    $output = & docker exec $phpContainer sh -c "printf '%s' '$encoded' | base64 -d | sh" 2>&1 | Out-String
    $exitCode = $LASTEXITCODE
    [pscustomobject]@{ Output = $output.TrimEnd(); ExitCode = $exitCode }
}

function Invoke-Php([string]$command) {
    $output = & docker exec $phpContainer sh -c $command 2>&1 | Out-String
    $exitCode = $LASTEXITCODE
    [pscustomobject]@{ Output = $output.TrimEnd(); ExitCode = $exitCode }
}

function Save-ContainerFile([string]$containerPath, [string]$localPath) {
    $content = & docker exec $phpContainer sh -c "cat '$containerPath' 2>/dev/null || true" 2>&1 | Out-String
    $exitCode = $LASTEXITCODE
    $content | Set-Content -Encoding UTF8 $localPath
    return $exitCode
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
$poolResult = Invoke-PhpScript $findPoolScript
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
php-fpm -tt > /tmp/e-ujian-php-fpm-config-test.log 2>&1
cat /tmp/e-ujian-php-fpm-config-test.log
"@
$validation = Invoke-PhpScript $configureScript
if ($validation.ExitCode -ne 0) {
    Invoke-Php "cp '$backupPath' '$poolPath'" | Out-Null
    throw "PHP-FPM configuration validation failed (exit code $($validation.ExitCode)):`n$($validation.Output)"
}

$reload = Invoke-Php 'kill -USR2 1'
if ($reload.ExitCode -ne 0) {
    Invoke-Php "cp '$backupPath' '$poolPath'" | Out-Null
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

        # Primary evidence: dedicated PHP-FPM slowlog file. This avoids docker
        # log timestamp parsing and keeps application/container logs separate.
        Save-ContainerFile $slowlogPath (Join-Path $reportDir 'php-fpm-slowlog.txt') | Out-Null

        # Save the exact effective pool configuration and validation output.
        Save-ContainerFile $poolPath (Join-Path $reportDir 'php-fpm-pool-config-after.txt') | Out-Null
        Save-ContainerFile '/tmp/e-ujian-php-fpm-config-test.log' (Join-Path $reportDir 'php-fpm-config-test.txt') | Out-Null

        $runtimeScript = @'
set +e
printf '%s\n' '=== PHP VERSION ==='
php -v
printf '%s\n' '=== PHP-FPM VERSION ==='
php-fpm -v
printf '%s\n' '=== PHP-FPM CONFIG TEST ==='
php-fpm -tt
printf '%s\n' '=== PHP-FPM MASTER/WORKERS ==='
ps -eo pid,ppid,stat,pcpu,pmem,rss,etime,args 2>/dev/null | grep '[p]hp-fpm' || true
printf '%s\n' '=== PHP PROCESS LIMITS ==='
for p in /proc/[0-9]*; do
  [ -r "$p/comm" ] || continue
  comm=$(cat "$p/comm" 2>/dev/null)
  case "$comm" in
    php-fpm|php-fpm8*|php-fpm7*)
      printf 'PID=%s\n' "${p##*/}"
      cat "$p/limits" 2>/dev/null | grep -E 'Max open files|Max processes' || true
      ;;
  esac
done
printf '%s\n' '=== FILESYSTEM ==='
df -h / /tmp /app 2>/dev/null || df -h /
printf '%s\n' '=== MOUNT INFO ==='
cat /proc/mounts 2>/dev/null | grep -E 'overlay|/app|/tmp' || true
'@
        $runtime = Invoke-PhpScript $runtimeScript
        $runtime.Output | Set-Content -Encoding UTF8 (Join-Path $reportDir 'php-runtime-diagnostic.txt')

        # Capture PHP-FPM/container logs separately for correlation. No --since
        # timestamp is used because the dedicated slowlog is the authoritative
        # source and Docker timestamp parsing differs across environments.
        docker logs $phpContainer 2>&1 | Select-Object -Last 5000 | Set-Content -Encoding UTF8 (Join-Path $reportDir 'php-fpm-container-log-tail.txt')

        # Redis and MySQL health at the end of the test, when those containers exist.
        $redisName = if ($env:REDIS_CONTAINER) { $env:REDIS_CONTAINER } else { 'e-ujian-redis' }
        if (docker ps --format '{{.Names}}' | Where-Object { $_ -eq $redisName }) {
            docker stats $redisName --no-stream --format 'name={{.Name}} cpu={{.CPUPerc}} mem={{.MemUsage}} net={{.NetIO}} block={{.BlockIO}}' | Set-Content -Encoding UTF8 (Join-Path $reportDir 'redis-final-stats.txt')
            docker exec $redisName redis-cli INFO stats 2>&1 | Set-Content -Encoding UTF8 (Join-Path $reportDir 'redis-info-stats.txt')
        }

        if (docker ps --format '{{.Names}}' | Where-Object { $_ -eq 'e-ujian-mysql' }) {
            docker stats 'e-ujian-mysql' --no-stream --format 'name={{.Name}} cpu={{.CPUPerc}} mem={{.MemUsage}} net={{.NetIO}} block={{.BlockIO}}' | Set-Content -Encoding UTF8 (Join-Path $reportDir 'mysql-final-stats.txt')
        }

        $slowlogLines = @()
        $slowlogFile = Join-Path $reportDir 'php-fpm-slowlog.txt'
        if (Test-Path $slowlogFile) {
            $slowlogLines = @(Get-Content $slowlogFile)
        }
        $slowlogEntryCount = @($slowlogLines | Where-Object { $_ -match 'script_filename|pool www|request slowlog' }).Count

        [ordered]@{
            enabled_at = $startedAt.ToString('o')
            finished_at = $finishedAt.ToString('o')
            php_container = $phpContainer
            pool_config = $poolPath
            slowlog_timeout = $slowlogTimeout
            slowlog_destination = $slowlogPath
            slowlog_entry_hint_count = $slowlogEntryCount
            evidence_files = @(
                'php-fpm-slowlog.txt',
                'php-fpm-slowlog.json',
                'php-fpm-pool-config-after.txt',
                'php-fpm-config-test.txt',
                'php-runtime-diagnostic.txt',
                'php-fpm-container-log-tail.txt',
                'php-fpm-workers.csv',
                'php-fpm-workers-raw.csv',
                'php-fpm-worker-pids.csv',
                'docker-stats.csv',
                'tcp-monitor.csv',
                'container-monitor.csv',
                'summary.json',
                'run.json'
            )
            runner_exit_code = $runnerExit
        } | ConvertTo-Json -Depth 4 | Set-Content -Encoding UTF8 (Join-Path $reportDir 'php-fpm-slowlog.json')
    }

    try {
        $restore = Invoke-Php "cp '$backupPath' '$poolPath'"
        if ($restore.ExitCode -ne 0) { throw "restore exit code $($restore.ExitCode): $($restore.Output)" }
        $reloadRestore = Invoke-Php 'kill -USR2 1'
        if ($reloadRestore.ExitCode -ne 0) { throw "reload exit code $($reloadRestore.ExitCode): $($reloadRestore.Output)" }
        Write-Host 'PHP-FPM original pool configuration restored.' -ForegroundColor Green
    } catch {
        Write-Host "WARNING: restore failed: $($_.Exception.Message)" -ForegroundColor Red
    }
}

if ($runnerExit -ne 0) { exit $runnerExit }
