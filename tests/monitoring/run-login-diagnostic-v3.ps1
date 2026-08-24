$ErrorActionPreference = 'Stop'

$scriptRoot = $PSScriptRoot
$phpContainer = if ($env:PHP_CONTAINER) { $env:PHP_CONTAINER } else { 'e-ujian-php' }
$resultsRoot = Join-Path $scriptRoot '..\load\results'
$timestamp = Get-Date -Format 'yyyy-MM-dd_HH-mm-ss'
$mode = if ($env:K6_MODE) { $env:K6_MODE } else { 'ramp' }
$vus = if ($env:VUS) { $env:VUS } else { '100' }
$reportDir = Join-Path (Resolve-Path $resultsRoot) "${timestamp}_${vus}vu_${mode}_diagnostic"
New-Item -ItemType Directory -Path $reportDir -Force | Out-Null

function Invoke-Docker([string[]]$Arguments) {
    $old = $ErrorActionPreference
    try {
        $ErrorActionPreference = 'Continue'
        $text = (& docker @Arguments 2>&1 | Out-String).TrimEnd()
        [pscustomobject]@{ Output = $text; ExitCode = $LASTEXITCODE }
    } finally { $ErrorActionPreference = $old }
}

function Save-Result([string]$Name, [string]$Text) {
    $Text | Set-Content -Path (Join-Path $reportDir $Name) -Encoding UTF8
}

function Save-DockerResult([string]$Name, [string[]]$Arguments) {
    $r = Invoke-Docker $Arguments
    Save-Result $Name $r.Output
    return $r
}

function Record-CollectorError([string]$Name, [string]$Message) {
    Save-Result "collector-error-$Name.txt" $Message
}

Write-Host ''
Write-Host '============================================================' -ForegroundColor Cyan
Write-Host ' E-UJIAN LOGIN DIAGNOSTIC RUNNER V3' -ForegroundColor Cyan
Write-Host '============================================================' -ForegroundColor Cyan
Write-Host "PHP container : $phpContainer"
Write-Host "VUS           : $vus"
Write-Host "MODE          : $mode"
Write-Host "Report        : $reportDir"
Write-Host ''

$containers = @(docker ps --format '{{.Names}}' 2>$null)
if ($containers -notcontains $phpContainer) { throw "PHP container is not running: $phpContainer" }

# Detect the pool config using Docker exec with only grep/find; no sh -c and no shell expressions.
$poolCandidates = @(
    '/usr/local/etc/php-fpm.d/docker.conf',
    '/usr/local/etc/php-fpm.d/www.conf',
    '/etc/php/8.3/fpm/pool.d/www.conf',
    '/etc/php/8.2/fpm/pool.d/www.conf'
)
$poolPath = $null
foreach ($candidate in $poolCandidates) {
    $test = Invoke-Docker @($phpContainer, 'test', '-f', $candidate)
    if ($test.ExitCode -eq 0) { $poolPath = $candidate; break }
}
if (-not $poolPath) {
    $find = Invoke-Docker @($phpContainer, 'find', '/usr/local/etc/php-fpm.d', '/etc/php', '-name', '*.conf')
    Save-Result 'php-fpm-config-files.txt' $find.Output
    throw 'Unable to identify PHP-FPM pool configuration. See php-fpm-config-files.txt.'
}
Write-Host "PHP-FPM pool : $poolPath" -ForegroundColor Green

$backupPath = '/tmp/e-ujian-v3-pool-backup.conf'
$slowlogPath = '/tmp/e-ujian-php-fpm-slowlog-v3.log'
$slowlogTimeout = if ($env:PHP_FPM_SLOWLOG_TIMEOUT) { $env:PHP_FPM_SLOWLOG_TIMEOUT } else { '1s' }

# Backup, patch, and validate without invoking a generated shell script.
$backup = Invoke-Docker @($phpContainer, 'cp', $poolPath, $backupPath)
if ($backup.ExitCode -ne 0) { throw "Could not backup pool config: $($backup.Output)" }

$original = (Invoke-Docker @($phpContainer, 'cat', $poolPath)).Output
$clean = $original -replace '(?ms)^; E-UJIAN-V3-SLOWLOG-BEGIN.*?^; E-UJIAN-V3-SLOWLOG-END\s*', ''
$patch = @"

; E-UJIAN-V3-SLOWLOG-BEGIN
request_slowlog_timeout = $slowlogTimeout
slowlog = $slowlogPath
request_slowlog_trace_depth = 30
; E-UJIAN-V3-SLOWLOG-END
"@
$newConfig = $clean.TrimEnd() + $patch + "`n"

# Docker cp requires a host file, so write a temporary host file and copy it into the container.
$hostConfig = Join-Path $env:TEMP "e-ujian-v3-$([guid]::NewGuid().ToString('N')).conf"
$newConfig | Set-Content -Path $hostConfig -Encoding UTF8
try {
    $copy = Invoke-Docker @($phpContainer, 'cp', $hostConfig, $poolPath)
    if ($copy.ExitCode -ne 0) { throw "Could not install temporary pool config: $($copy.Output)" }
} finally {
    Remove-Item $hostConfig -Force -ErrorAction SilentlyContinue
}

$clearLog = Invoke-Docker @($phpContainer, 'sh', '-c', ': > /tmp/e-ujian-php-fpm-slowlog-v3.log')
if ($clearLog.ExitCode -ne 0) {
    # Fall back to truncating via a host-created empty file.
    $empty = Join-Path $env:TEMP "e-ujian-v3-empty-$([guid]::NewGuid().ToString('N')).log"
    New-Item -ItemType File -Path $empty -Force | Out-Null
    try { Invoke-Docker @($phpContainer, 'cp', $empty, $slowlogPath) | Out-Null } finally { Remove-Item $empty -Force -ErrorAction SilentlyContinue }
}

$validation = Save-DockerResult 'php-fpm-validation.txt' @($phpContainer, 'php-fpm', '-tt')
if ($validation.ExitCode -ne 0) {
    Invoke-Docker @($phpContainer, 'cp', $backupPath, $poolPath) | Out-Null
    throw "PHP-FPM configuration validation failed: $($validation.Output)"
}

$reload = docker kill --signal USR2 $phpContainer 2>&1 | Out-String
$reloadCode = $LASTEXITCODE
if ($reloadCode -ne 0) {
    Invoke-Docker @($phpContainer, 'cp', $backupPath, $poolPath) | Out-Null
    throw "PHP-FPM reload failed: $($reload.TrimEnd())"
}
Start-Sleep -Seconds 2

$runnerExit = 1
try {
    & powershell.exe -ExecutionPolicy Bypass -File (Join-Path $scriptRoot 'run-login-with-monitoring.ps1')
    $runnerExit = if ($null -eq $LASTEXITCODE) { 0 } else { $LASTEXITCODE }
} finally {
    # Collect only direct Docker commands. No generated shell scripts, awk, sed, command substitution, or /proc shell loops.
    try { Save-DockerResult 'php-fpm-slowlog.txt' @($phpContainer, 'cat', $slowlogPath) | Out-Null } catch { Record-CollectorError 'slowlog' $_.Exception.Message }
    try { Save-DockerResult 'php-fpm-pool-config.txt' @($phpContainer, 'cat', $poolPath) | Out-Null } catch { Record-CollectorError 'pool' $_.Exception.Message }
    try { Save-DockerResult 'php-version.txt' @($phpContainer, 'php', '-v') | Out-Null } catch { Record-CollectorError 'php-version' $_.Exception.Message }
    try { Save-DockerResult 'php-fpm-version.txt' @($phpContainer, 'php-fpm', '-v') | Out-Null } catch { Record-CollectorError 'php-fpm-version' $_.Exception.Message }
    try { Save-DockerResult 'php-fpm-validation-after.txt' @($phpContainer, 'php-fpm', '-tt') | Out-Null } catch { Record-CollectorError 'validation-after' $_.Exception.Message }
    try { Save-DockerResult 'php-processes.txt' @($phpContainer, 'ps', '-eo', 'pid,ppid,state,etime,%cpu,%mem,rss,args') | Out-Null } catch { Record-CollectorError 'processes' $_.Exception.Message }
    try { Save-DockerResult 'php-top.txt' @($phpContainer, 'top', '-b', '-n', '1') | Out-Null } catch { Record-CollectorError 'top' $_.Exception.Message }
    try { Save-DockerResult 'php-fpm-log-tail.txt' @($phpContainer, 'cat', '/proc/1/fd/2') | Out-Null } catch { Record-CollectorError 'fpm-log' $_.Exception.Message }
    try {
        $logs = docker logs $phpContainer 2>&1 | Select-Object -Last 5000
        Save-Result 'php-container-log-tail.txt' (($logs | Out-String).TrimEnd())
    } catch { Record-CollectorError 'container-log' $_.Exception.Message }
    try {
        $stats = docker stats $phpContainer --no-stream --format 'name={{.Name}} cpu={{.CPUPerc}} mem={{.MemUsage}} net={{.NetIO}} block={{.BlockIO}} pids={{.PIDs}}' 2>&1 | Out-String
        Save-Result 'php-final-stats.txt' $stats.TrimEnd()
    } catch { Record-CollectorError 'php-stats' $_.Exception.Message }

    foreach ($name in @('e-ujian-redis','e-ujian-mysql')) {
        if (@(docker ps --format '{{.Names}}' 2>$null) -contains $name) {
            try {
                $stats = docker stats $name --no-stream --format 'name={{.Name}} cpu={{.CPUPerc}} mem={{.MemUsage}} net={{.NetIO}} block={{.BlockIO}} pids={{.PIDs}}' 2>&1 | Out-String
                Save-Result "$name-stats.txt" $stats.TrimEnd()
            } catch { Record-CollectorError "$name-stats" $_.Exception.Message }
        }
    }

    try {
        $slow = Join-Path $reportDir 'php-fpm-slowlog.txt'
        $slowLines = if (Test-Path $slow) { @(Get-Content $slow) } else { @() }
        [ordered]@{
            generated_at = (Get-Date).ToUniversalTime().ToString('o')
            container = $phpContainer
            pool_config = $poolPath
            slowlog = $slowlogPath
            slowlog_timeout = $slowlogTimeout
            slowlog_lines = $slowLines.Count
            slow_requests = @($slowLines | Where-Object { $_ -match 'executing too slow' }).Count
            ptrace_denied = @($slowLines | Where-Object { $_ -match 'ptrace|Operation not permitted' }).Count
            runner_exit_code = $runnerExit
        } | ConvertTo-Json | Set-Content -Encoding UTF8 (Join-Path $reportDir 'diagnostic-summary.json')
    } catch { Record-CollectorError 'summary' $_.Exception.Message }

    # Always restore the original configuration.
    try {
        $restore = Invoke-Docker @($phpContainer, 'cp', $backupPath, $poolPath)
        if ($restore.ExitCode -ne 0) { throw $restore.Output }
        $restoreReload = docker kill --signal USR2 $phpContainer 2>&1 | Out-String
        if ($LASTEXITCODE -ne 0) { throw $restoreReload.TrimEnd() }
        Write-Host 'PHP-FPM original pool configuration restored.' -ForegroundColor Green
    } catch { Write-Host "WARNING: restore failed: $($_.Exception.Message)" -ForegroundColor Red }
}

Write-Host "Report: $reportDir"
if ($runnerExit -ne 0) { exit $runnerExit }
