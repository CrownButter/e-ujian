$ErrorActionPreference = 'Stop'

$scriptRoot = $PSScriptRoot
$phpContainer = if ($env:PHP_CONTAINER) { $env:PHP_CONTAINER } else { 'e-ujian-php' }
$slowlogTimeout = if ($env:PHP_FPM_SLOWLOG_TIMEOUT) { $env:PHP_FPM_SLOWLOG_TIMEOUT } else { '1s' }
$startedAt = Get-Date

function Invoke-PhpScript([string]$script) {
    $encoded = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($script))
    $output = & docker exec $phpContainer sh -c "printf '%s' '$encoded' | base64 -d | sh" 2>&1 | Out-String
    $exitCode = $LASTEXITCODE
    [pscustomobject]@{
        Output = $output.TrimEnd()
        ExitCode = $exitCode
    }
}

function Invoke-Php([string]$command) {
    $output = & docker exec $phpContainer sh -c $command 2>&1 | Out-String
    [pscustomobject]@{
        Output = $output.TrimEnd()
        ExitCode = $LASTEXITCODE
    }
}

Write-Host ''
Write-Host '============================================================' -ForegroundColor Cyan
Write-Host ' E-UJIAN PHP-FPM SLOWLOG DIAGNOSTIC RUNNER' -ForegroundColor Cyan
Write-Host '============================================================' -ForegroundColor Cyan
Write-Host "PHP container       : $phpContainer"
Write-Host "Slowlog threshold   : $slowlogTimeout"
Write-Host ''

if (-not (docker ps --format '{{.Names}}' | Where-Object { $_ -eq $phpContainer })) {
    throw "PHP container is not running: $phpContainer"
}

$findPoolScript = @'
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
$patchText = "; E-UJIAN-SLOWLOG-BEGIN`nrequest_slowlog_timeout = $slowlogTimeout`nslowlog = /proc/self/fd/2`nrequest_slowlog_trace_depth = 30`n; E-UJIAN-SLOWLOG-END`n"
$patchB64 = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($patchText))

$configureScript = @"
set -eu
cp -- '$poolPath' '$backupPath'
sed -i '/; E-UJIAN-SLOWLOG-BEGIN/,/; E-UJIAN-SLOWLOG-END/d' '$poolPath'
printf '%s' '$patchB64' | base64 -d >> '$poolPath'

if php-fpm -tt > /tmp/e-ujian-php-fpm-config-test.log 2>&1; then
  cat /tmp/e-ujian-php-fpm-config-test.log
  exit 0
fi
status=1
cat /tmp/e-ujian-php-fpm-config-test.log
exit "$status"
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
try {
    & powershell.exe -ExecutionPolicy Bypass -File (Join-Path $scriptRoot 'run-login-with-monitoring.ps1')
    $runnerExit = if ($null -ne $LASTEXITCODE) { $LASTEXITCODE } else { 0 }
} finally {
    $finishedAt = Get-Date
    $reportRoot = Join-Path $scriptRoot '..\load\results'
    $latestReport = Get-ChildItem -Path (Resolve-Path $reportRoot) -Directory | Sort-Object LastWriteTime -Descending | Select-Object -First 1
    if ($latestReport) {
        $slowlogSince = $startedAt.ToUniversalTime().ToString('yyyy-MM-ddTHH:mm:ssZ')
        try {
            docker logs --since $slowlogSince $phpContainer 2>&1 | Set-Content -Encoding UTF8 (Join-Path $latestReport.FullName 'php-fpm-slowlog.txt')
        } catch {
            "docker logs failed: $($_.Exception.Message)" | Set-Content -Encoding UTF8 (Join-Path $latestReport.FullName 'php-fpm-slowlog.txt')
        }
        [ordered]@{
            enabled_at = $startedAt.ToString('o')
            finished_at = $finishedAt.ToString('o')
            php_container = $phpContainer
            pool_config = $poolPath
            slowlog_timeout = $slowlogTimeout
            slowlog_destination = '/proc/self/fd/2'
            evidence_file = 'php-fpm-slowlog.txt'
            runner_exit_code = $runnerExit
        } | ConvertTo-Json | Set-Content -Encoding UTF8 (Join-Path $latestReport.FullName 'php-fpm-slowlog.json')
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
