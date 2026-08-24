$ErrorActionPreference = 'Stop'

# ============================================================
# E-UJIAN K6 LOAD TEST + PHP-FPM SLOWLOG DIAGNOSTICS
# ============================================================
# Temporarily enables PHP-FPM request slowlog, runs the existing
# monitoring runner, captures the slowlog into the result folder,
# then restores the exact original pool configuration.
# Default threshold: 1 second.
# ============================================================

$scriptRoot = $PSScriptRoot
$phpContainer = if ($env:PHP_CONTAINER) { $env:PHP_CONTAINER } else { 'e-ujian-php' }
$slowlogTimeout = if ($env:PHP_FPM_SLOWLOG_TIMEOUT) { $env:PHP_FPM_SLOWLOG_TIMEOUT } else { '1s' }
$startedAt = Get-Date
$slowlogPath = '/tmp/e-ujian-php-fpm-slowlog.log'

function Invoke-Php([string]$command) {
    return (& docker exec $phpContainer sh -c $command 2>&1 | Out-String).TrimEnd()
}

Write-Host ''
Write-Host '============================================================' -ForegroundColor Cyan
Write-Host ' E-UJIAN PHP-FPM SLOWLOG DIAGNOSTIC RUNNER' -ForegroundColor Cyan
Write-Host '============================================================' -ForegroundColor Cyan
Write-Host "PHP container       : $phpContainer"
Write-Host "Slowlog threshold   : $slowlogTimeout"
Write-Host "Slowlog file        : $slowlogPath"
Write-Host ''

$containerExists = docker ps --format '{{.Names}}' | Where-Object { $_ -eq $phpContainer }
if (-not $containerExists) {
    throw "PHP container is not running: $phpContainer"
}

# Locate the active www pool configuration without assuming one PHP image layout.
$poolPath = Invoke-Php "for f in /usr/local/etc/php-fpm.d/*.conf /etc/php*/fpm/pool.d/*.conf /etc/php/*/fpm/pool.d/*.conf; do [ -f \"`$f\" ] || continue; grep -q '^\[www\]' \"`$f\" 2>/dev/null && echo \"`$f\" && break; done"
$poolPath = ($poolPath -split "`r?`n" | Where-Object { $_ -and $_ -notmatch '^ERROR:' } | Select-Object -First 1)

if (-not $poolPath) {
    throw 'Unable to locate PHP-FPM [www] pool configuration inside the container.'
}

Write-Host "PHP-FPM pool config : $poolPath" -ForegroundColor Green

$backupPath = "/tmp/e-ujian-php-fpm-www.conf.$([DateTimeOffset]::UtcNow.ToUnixTimeSeconds()).bak"
$marker = '; E-UJIAN-SLOWLOG-BEGIN'
$endMarker = '; E-UJIAN-SLOWLOG-END'

Invoke-Php "cp '$poolPath' '$backupPath'"
Invoke-Php "rm -f '$slowlogPath'"

$patch = @"
$marker
request_slowlog_timeout = $slowlogTimeout
slowlog = $slowlogPath
request_slowlog_trace_depth = 30
$endMarker
"@
$patchB64 = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($patch))

# The pool file is backed up first, so this diagnostic block is always reversible.
$applyCommand = "echo '$patchB64' | base64 -d >> '$poolPath'"
$applyResult = Invoke-Php $applyCommand
if ($applyResult -match '(?i)(error|failed)') {
    Invoke-Php "cp '$backupPath' '$poolPath'"
    throw "Failed to patch PHP-FPM pool config: $applyResult"
}

$testConfig = Invoke-Php 'php-fpm -tt 2>&1 || php-fpm8.3 -tt 2>&1 || php-fpm8.2 -tt 2>&1 || php-fpm8.1 -tt 2>&1'
if ($testConfig -match '(?i)(ERROR|failed to|syntax error)') {
    Invoke-Php "cp '$backupPath' '$poolPath'"
    throw "PHP-FPM configuration validation failed.`n$testConfig"
}

# Graceful reload. In the current Docker setup PHP-FPM is the container's PID 1.
Invoke-Php 'kill -USR2 1'
Start-Sleep -Seconds 2

Write-Host 'PHP-FPM slowlog enabled.' -ForegroundColor Green

$runnerExit = 1
try {
    Write-Host ''
    Write-Host 'Running the standard monitoring/load-test runner...' -ForegroundColor Yellow
    & powershell.exe -ExecutionPolicy Bypass -File (Join-Path $scriptRoot 'run-login-with-monitoring.ps1')
    $runnerExit = $LASTEXITCODE
} finally {
    $finishedAt = Get-Date

    $reportRoot = Join-Path $scriptRoot '..\load\results'
    $latestReport = Get-ChildItem -Path (Resolve-Path $reportRoot) -Directory |
        Sort-Object LastWriteTime -Descending |
        Select-Object -First 1

    if ($latestReport) {
        $slowlogFile = Join-Path $latestReport.FullName 'php-fpm-slowlog.txt'
        $containerLogFile = Join-Path $latestReport.FullName 'php-fpm-container-log.txt'

        try {
            $slowlogContent = Invoke-Php "cat '$slowlogPath' 2>/dev/null || true"
            if ([string]::IsNullOrWhiteSpace($slowlogContent)) {
                $slowlogContent = '# No PHP-FPM slowlog entries were generated.'
            }
            $slowlogContent | Set-Content -Encoding UTF8 $slowlogFile
        } catch {
            "slowlog capture failed: $($_.Exception.Message)" | Set-Content -Encoding UTF8 $slowlogFile
        }

        try {
            $slowlogSince = $startedAt.ToUniversalTime().ToString('yyyy-MM-ddTHH:mm:ssZ')
            docker logs --since $slowlogSince $phpContainer 2>&1 | Set-Content -Encoding UTF8 $containerLogFile
        } catch {
            "docker logs failed: $($_.Exception.Message)" | Set-Content -Encoding UTF8 $containerLogFile
        }

        $diagMeta = [ordered]@{
            enabled_at = $startedAt.ToString('o')
            finished_at = $finishedAt.ToString('o')
            php_container = $phpContainer
            pool_config = $poolPath
            slowlog_timeout = $slowlogTimeout
            slowlog_destination = $slowlogPath
            evidence_file = 'php-fpm-slowlog.txt'
            container_log_file = 'php-fpm-container-log.txt'
            runner_exit_code = $runnerExit
        }
        $diagMeta | ConvertTo-Json -Depth 5 | Set-Content -Encoding UTF8 (Join-Path $latestReport.FullName 'php-fpm-slowlog.json')
    }

    try {
        # Restore the exact original pool configuration and reload again.
        Invoke-Php "cp '$backupPath' '$poolPath'"
        Invoke-Php 'kill -USR2 1'
        Invoke-Php "rm -f '$backupPath' '$slowlogPath'"
        Write-Host 'PHP-FPM original pool configuration restored.' -ForegroundColor Green
    } catch {
        Write-Host "WARNING: failed to restore PHP-FPM configuration: $($_.Exception.Message)" -ForegroundColor Red
    }
}

if ($runnerExit -ne 0) {
    exit $runnerExit
}
