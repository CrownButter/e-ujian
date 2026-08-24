$ErrorActionPreference = 'Stop'

# Shell-safe PHP-FPM slowlog diagnostic runner.
$scriptRoot = $PSScriptRoot
$phpContainer = if ($env:PHP_CONTAINER) { $env:PHP_CONTAINER } else { 'e-ujian-php' }
$slowlogTimeout = if ($env:PHP_FPM_SLOWLOG_TIMEOUT) { $env:PHP_FPM_SLOWLOG_TIMEOUT } else { '1s' }
$startedAt = Get-Date

function Invoke-Php([string]$command) {
    try { return (& docker exec $phpContainer sh -c $command 2>&1 | Out-String).TrimEnd() }
    catch { return "ERROR: $($_.Exception.Message)" }
}

function Invoke-PhpScript([string]$script) {
    $encoded = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($script))
    return Invoke-Php "printf '%s' '$encoded' | base64 -d | sh"
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
  if grep -q '^\[www\]' "$f" 2>/dev/null; then printf '%s\n' "$f"; exit 0; fi
done
exit 1
'@
$poolPath = Invoke-PhpScript $findPoolScript
$poolPath = ($poolPath -split "`r?`n" | Where-Object { $_ -and $_ -notmatch '^ERROR:' } | Select-Object -First 1)
if (-not $poolPath) { throw 'Unable to locate PHP-FPM [www] pool configuration.' }
Write-Host "PHP-FPM pool config : $poolPath" -ForegroundColor Green

$backupPath = "/tmp/e-ujian-php-fpm-www.$([DateTimeOffset]::UtcNow.ToUnixTimeSeconds()).bak"
$patchB64 = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes("; E-UJIAN-SLOWLOG-BEGIN`nrequest_slowlog_timeout = $slowlogTimeout`nslowlog = /proc/self/fd/2`nrequest_slowlog_trace_depth = 30`n; E-UJIAN-SLOWLOG-END`n"))

$backupScript = @"
set -e
cp "$poolPath" "$backupPath"
sed -i '/; E-UJIAN-SLOWLOG-BEGIN/,/; E-UJIAN-SLOWLOG-END/d' "$poolPath"
printf '%s' '$patchB64' | base64 -d >> "$poolPath"

# Validate using the process exit code. Do not classify normal php-fpm -tt
# NOTICE output as an error: some NOTICE lines contain words such as
# "unknown value" even when the overall configuration test succeeds.
if command -v php-fpm >/dev/null 2>&1; then
  php-fpm -tt 2>&1
  rc=$?
elif command -v php-fpm8.3 >/dev/null 2>&1; then
  php-fpm8.3 -tt 2>&1
  rc=$?
elif command -v php-fpm8.2 >/dev/null 2>&1; then
  php-fpm8.2 -tt 2>&1
  rc=$?
elif command -v php-fpm8.1 >/dev/null 2>&1; then
  php-fpm8.1 -tt 2>&1
  rc=$?
else
  echo 'ERROR: no PHP-FPM binary found'
  rc=127
fi
printf '\n__PHP_FPM_TT_RC=%s\n' "$rc"
exit "$rc"
"@
$result = Invoke-PhpScript $backupScript
$rcMatch = [regex]::Match($result, '__PHP_FPM_TT_RC=(\d+)')
$validationRc = if ($rcMatch.Success) { [int]$rcMatch.Groups[1].Value } else { 255 }

if ($validationRc -ne 0) {
    Invoke-Php "cp '$backupPath' '$poolPath'"
    throw "PHP-FPM configuration validation failed (exit code $validationRc):`n$result"
}

Invoke-Php 'kill -USR2 1'
Start-Sleep -Seconds 2
Write-Host 'PHP-FPM slowlog enabled.' -ForegroundColor Green

$runnerExit = 1
try {
    & powershell.exe -ExecutionPolicy Bypass -File (Join-Path $scriptRoot 'run-login-with-monitoring.ps1')
    $runnerExit = $LASTEXITCODE
} finally {
    $finishedAt = Get-Date
    $reportRoot = Join-Path $scriptRoot '..\load\results'
    $latestReport = Get-ChildItem -Path (Resolve-Path $reportRoot) -Directory | Sort-Object LastWriteTime -Descending | Select-Object -First 1
    if ($latestReport) {
        $slowlogSince = $startedAt.ToUniversalTime().ToString('yyyy-MM-ddTHH:mm:ssZ')
        try { docker logs --since $slowlogSince $phpContainer 2>&1 | Set-Content -Encoding UTF8 (Join-Path $latestReport.FullName 'php-fpm-slowlog.txt') }
        catch { "docker logs failed: $($_.Exception.Message)" | Set-Content -Encoding UTF8 (Join-Path $latestReport.FullName 'php-fpm-slowlog.txt') }
        [ordered]@{ enabled_at=$startedAt.ToString('o'); finished_at=$finishedAt.ToString('o'); php_container=$phpContainer; pool_config=$poolPath; slowlog_timeout=$slowlogTimeout; slowlog_destination='/proc/self/fd/2'; evidence_file='php-fpm-slowlog.txt'; runner_exit_code=$runnerExit } | ConvertTo-Json | Set-Content -Encoding UTF8 (Join-Path $latestReport.FullName 'php-fpm-slowlog.json')
    }
    try { Invoke-Php "cp '$backupPath' '$poolPath'"; Invoke-Php 'kill -USR2 1'; Write-Host 'PHP-FPM original pool configuration restored.' -ForegroundColor Green }
    catch { Write-Host "WARNING: restore failed: $($_.Exception.Message)" -ForegroundColor Red }
}
if ($runnerExit -ne 0) { exit $runnerExit }
