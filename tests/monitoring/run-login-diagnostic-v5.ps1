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
        $output = & docker @Arguments 2>&1 | Out-String
        [pscustomobject]@{ Output = $output.TrimEnd(); ExitCode = $LASTEXITCODE }
    } finally { $ErrorActionPreference = $old }
}

function Invoke-DockerExec([string[]]$Arguments) {
    return Invoke-Docker (@('exec', $phpContainer) + $Arguments)
}

function Save-Text([string]$Name, [string]$Text) {
    $Text | Set-Content -Path (Join-Path $reportDir $Name) -Encoding UTF8
}

function Capture-Exec([string]$Name, [string[]]$Arguments) {
    $r = Invoke-DockerExec $Arguments
    Save-Text $Name $r.Output
    return $r
}

function Capture-Error([string]$Name, [string]$Message) {
    Save-Text "collector-error-$Name.txt" $Message
}

Write-Host ''
Write-Host '============================================================' -ForegroundColor Cyan
Write-Host ' E-UJIAN LOGIN DIAGNOSTIC RUNNER V5' -ForegroundColor Cyan
Write-Host '============================================================' -ForegroundColor Cyan
Write-Host "PHP container : $phpContainer"
Write-Host "VUS           : $vus"
Write-Host "MODE          : $mode"
Write-Host "Report        : $reportDir"
Write-Host ''

$names = @(docker ps --format '{{.Names}}' 2>$null)
if ($names -notcontains $phpContainer) { throw "PHP container is not running: $phpContainer" }

# This Docker image has a known pool path. Verify it through docker exec.
$poolPath = '/usr/local/etc/php-fpm.d/docker.conf'
$poolCheck = Invoke-DockerExec @('test', '-f', $poolPath)
if ($poolCheck.ExitCode -ne 0) { throw "Known PHP-FPM pool config was not found: $poolPath`n$($poolCheck.Output)" }
Write-Host "PHP-FPM pool : $poolPath" -ForegroundColor Green

$backupPath = '/tmp/e-ujian-v5-pool-backup.conf'
$slowlogPath = '/tmp/e-ujian-php-fpm-slowlog-v5.log'
$slowlogTimeout = if ($env:PHP_FPM_SLOWLOG_TIMEOUT) { $env:PHP_FPM_SLOWLOG_TIMEOUT } else { '1s' }

$backup = Invoke-Docker @('cp', "$phpContainer`:$poolPath", $backupPath)
if ($backup.ExitCode -ne 0) { throw "Pool backup failed: $($backup.Output)" }

$read = Invoke-DockerExec @('cat', $poolPath)
if ($read.ExitCode -ne 0 -or -not $read.Output) { throw "Could not read $poolPath`n$($read.Output)" }
$original = $read.Output

$clean = $original -replace '(?ms)^; E-UJIAN-V5-SLOWLOG-BEGIN.*?^; E-UJIAN-V5-SLOWLOG-END\s*', ''
$patch = "`n; E-UJIAN-V5-SLOWLOG-BEGIN`nrequest_slowlog_timeout = $slowlogTimeout`nslowlog = $slowlogPath`nrequest_slowlog_trace_depth = 30`n; E-UJIAN-V5-SLOWLOG-END`n"
$newConfig = $clean.TrimEnd() + $patch

$hostConfig = Join-Path $env:TEMP "e-ujian-v5-$([guid]::NewGuid().ToString('N')).conf"
$newConfig | Set-Content -Path $hostConfig -Encoding UTF8
try {
    $install = Invoke-Docker @('cp', $hostConfig, "$phpContainer`:$poolPath")
    if ($install.ExitCode -ne 0) { throw "Pool config install failed: $($install.Output)" }
} finally { Remove-Item $hostConfig -Force -ErrorAction SilentlyContinue }

# Zero-byte slowlog from host; no shell redirection.
$emptyLog = Join-Path $env:TEMP "e-ujian-v5-empty-$([guid]::NewGuid().ToString('N')).log"
New-Item -ItemType File -Path $emptyLog -Force | Out-Null
try {
    $clear = Invoke-Docker @('cp', $emptyLog, "$phpContainer`:$slowlogPath")
    if ($clear.ExitCode -ne 0) { throw "Slowlog initialization failed: $($clear.Output)" }
} finally { Remove-Item $emptyLog -Force -ErrorAction SilentlyContinue }

$validation = Capture-Exec 'php-fpm-validation-before.txt' @('php-fpm', '-tt')
if ($validation.ExitCode -ne 0) {
    Invoke-Docker @('cp', $backupPath, "$phpContainer`:$poolPath") | Out-Null
    throw "PHP-FPM validation failed: $($validation.Output)"
}

$reloadOutput = docker kill --signal USR2 $phpContainer 2>&1 | Out-String
$reloadCode = $LASTEXITCODE
if ($reloadCode -ne 0) {
    Invoke-Docker @('cp', $backupPath, "$phpContainer`:$poolPath") | Out-Null
    throw "PHP-FPM reload failed: $($reloadOutput.TrimEnd())"
}
Start-Sleep -Seconds 2
Write-Host 'PHP-FPM slowlog enabled.' -ForegroundColor Green

$runnerExit = 1
try {
    & powershell.exe -ExecutionPolicy Bypass -File (Join-Path $scriptRoot 'run-login-with-monitoring.ps1')
    $runnerExit = if ($null -eq $LASTEXITCODE) { 0 } else { $LASTEXITCODE }
}
finally {
    try { Capture-Exec 'php-fpm-slowlog.txt' @('cat', $slowlogPath) | Out-Null } catch { Capture-Error 'slowlog' $_.Exception.Message }
    try { Capture-Exec 'php-fpm-pool-config-after.txt' @('cat', $poolPath) | Out-Null } catch { Capture-Error 'pool' $_.Exception.Message }
    try { Capture-Exec 'php-version.txt' @('php', '-v') | Out-Null } catch { Capture-Error 'php-version' $_.Exception.Message }
    try { Capture-Exec 'php-fpm-version.txt' @('php-fpm', '-v') | Out-Null } catch { Capture-Error 'php-fpm-version' $_.Exception.Message }
    try { Capture-Exec 'php-fpm-validation-after.txt' @('php-fpm', '-tt') | Out-Null } catch { Capture-Error 'validation-after' $_.Exception.Message }
    try { Capture-Exec 'php-processes.txt' @('ps', '-eo', 'pid,ppid,state,etime,%cpu,%mem,rss,args') | Out-Null } catch { Capture-Error 'processes' $_.Exception.Message }
    try { Capture-Exec 'php-top.txt' @('top', '-b', '-n', '1') | Out-Null } catch { Capture-Error 'top' $_.Exception.Message }
    try { $logs = docker logs $phpContainer 2>&1 | Select-Object -Last 5000; Save-Text 'php-container-log-tail.txt' (($logs | Out-String).TrimEnd()) } catch { Capture-Error 'container-log' $_.Exception.Message }
    try { $stats = docker stats $phpContainer --no-stream --format 'name={{.Name}} cpu={{.CPUPerc}} mem={{.MemUsage}} net={{.NetIO}} block={{.BlockIO}} pids={{.PIDs}}' 2>&1 | Out-String; Save-Text 'php-final-stats.txt' $stats.TrimEnd() } catch { Capture-Error 'php-stats' $_.Exception.Message }

    foreach ($service in @('e-ujian-redis','e-ujian-mysql')) {
        if (@(docker ps --format '{{.Names}}' 2>$null) -contains $service) {
            try { $s = docker stats $service --no-stream --format 'name={{.Name}} cpu={{.CPUPerc}} mem={{.MemUsage}} net={{.NetIO}} block={{.BlockIO}} pids={{.PIDs}}' 2>&1 | Out-String; Save-Text "$service-stats.txt" $s.TrimEnd() } catch { Capture-Error "$service-stats" $_.Exception.Message }
        }
    }

    try {
        $slowFile = Join-Path $reportDir 'php-fpm-slowlog.txt'
        $lines = if (Test-Path $slowFile) { @(Get-Content $slowFile) } else { @() }
        [ordered]@{
            generated_at = (Get-Date).ToUniversalTime().ToString('o')
            container = $phpContainer
            pool_config = $poolPath
            slowlog_path = $slowlogPath
            slowlog_timeout = $slowlogTimeout
            slowlog_lines = $lines.Count
            slow_requests = @($lines | Where-Object { $_ -match 'executing too slow' }).Count
            ptrace_denied = @($lines | Where-Object { $_ -match 'ptrace|Operation not permitted' }).Count
            runner_exit_code = $runnerExit
        } | ConvertTo-Json | Set-Content -Encoding UTF8 (Join-Path $reportDir 'diagnostic-summary.json')
    } catch { Capture-Error 'summary' $_.Exception.Message }

    try {
        $restore = Invoke-Docker @('cp', $backupPath, "$phpContainer`:$poolPath")
        if ($restore.ExitCode -ne 0) { throw "Restore copy failed: $($restore.Output)" }
        $restoreReload = docker kill --signal USR2 $phpContainer 2>&1 | Out-String
        if ($LASTEXITCODE -ne 0) { throw "Restore reload failed: $($restoreReload.TrimEnd())" }
        Write-Host 'PHP-FPM original pool configuration restored.' -ForegroundColor Green
    } catch { Write-Host "WARNING: restore failed: $($_.Exception.Message)" -ForegroundColor Red }
}

Write-Host ''
Write-Host "Report: $reportDir"
if ($runnerExit -ne 0) { exit $runnerExit }
