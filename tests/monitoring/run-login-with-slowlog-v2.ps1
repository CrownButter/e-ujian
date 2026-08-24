$ErrorActionPreference = 'Stop'

$scriptRoot = $PSScriptRoot
$phpContainer = if ($env:PHP_CONTAINER) { $env:PHP_CONTAINER } else { 'e-ujian-php' }
$slowlogTimeout = if ($env:PHP_FPM_SLOWLOG_TIMEOUT) { $env:PHP_FPM_SLOWLOG_TIMEOUT } else { '1s' }
$slowlogPath = '/tmp/e-ujian-php-fpm-slowlog.log'

function Invoke-DockerExec([string[]]$Arguments) {
    $previousPreference = $ErrorActionPreference
    try {
        # Native docker commands commonly write NOTICE/WARNING messages to stderr even
        # when they exit successfully. Do not let PowerShell convert those messages into
        # terminating NativeCommandError exceptions.
        $ErrorActionPreference = 'Continue'
        $output = & docker exec @Arguments 2>&1 | Out-String
        $exitCode = $LASTEXITCODE
        return [pscustomobject]@{ Output = $output.TrimEnd(); ExitCode = $exitCode }
    }
    finally {
        $ErrorActionPreference = $previousPreference
    }
}

function Invoke-ContainerShellScript([string]$Script) {
    $encoded = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($Script))
    return Invoke-DockerExec @($phpContainer, 'sh', '-c', "printf '%s' '$encoded' | base64 -d | sh")
}

function Save-Text([string]$Path, [string]$Text) {
    $Text | Set-Content -Encoding UTF8 $Path
}

function Save-ContainerFile([string]$ContainerPath, [string]$LocalPath) {
    $result = Invoke-DockerExec @($phpContainer, 'cat', $ContainerPath)
    Save-Text $LocalPath $result.Output
    return $result.ExitCode
}

function Save-CollectorError([string]$ReportDir, [string]$Name, [string]$Message) {
    Save-Text (Join-Path $ReportDir $Name) $Message
}

Write-Host ''
Write-Host '============================================================' -ForegroundColor Cyan
Write-Host ' E-UJIAN PHP-FPM SLOWLOG DIAGNOSTIC RUNNER' -ForegroundColor Cyan
Write-Host '============================================================' -ForegroundColor Cyan
Write-Host "PHP container       : $phpContainer"
Write-Host "Slowlog threshold   : $slowlogTimeout"
Write-Host "Slowlog file        : $slowlogPath"
Write-Host ''

$running = @(docker ps --format '{{.Names}}' 2>$null)
if ($running -notcontains $phpContainer) {
    throw "PHP container is not running: $phpContainer"
}

$findPool = @'
set -e
for f in /usr/local/etc/php-fpm.d/*.conf /etc/php*/fpm/pool.d/*.conf /etc/php/*/fpm/pool.d/*.conf; do
    if [ -f "$f" ]; then
        if grep -q '^\[www\]' "$f"; then
            printf '%s\n' "$f"
            exit 0
        fi
    fi
done
exit 1
'@
$poolResult = Invoke-ContainerShellScript $findPool
if ($poolResult.ExitCode -ne 0) {
    throw "Unable to locate PHP-FPM [www] pool configuration. Exit $($poolResult.ExitCode):`n$($poolResult.Output)"
}
$poolPath = ($poolResult.Output -split "`r?`n" | Where-Object { $_ -and $_ -notmatch '^ERROR:' } | Select-Object -First 1).Trim()
if (-not $poolPath) { throw 'Unable to determine PHP-FPM pool configuration path.' }
Write-Host "PHP-FPM pool config : $poolPath" -ForegroundColor Green

$backupPath = '/tmp/e-ujian-php-fpm-www-backup.conf'
$markerBegin = '; E-UJIAN-SLOWLOG-BEGIN'
$markerEnd = '; E-UJIAN-SLOWLOG-END'
$patch = @"
$markerBegin
request_slowlog_timeout = $slowlogTimeout
slowlog = $slowlogPath
request_slowlog_trace_depth = 30
$markerEnd
"@
$patchB64 = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($patch))

$configScript = @"
set -e
cp '$poolPath' '$backupPath'
: > '$slowlogPath'
if grep -q '$markerBegin' '$poolPath'; then
    sed -i '/$markerBegin/,/$markerEnd/d' '$poolPath'
fi
printf '%s' '$patchB64' | base64 -d >> '$poolPath'
php-fpm -tt
"@
$configResult = Invoke-ContainerShellScript $configScript
if ($configResult.ExitCode -ne 0) {
    Invoke-DockerExec @($phpContainer, 'cp', $backupPath, $poolPath) | Out-Null
    throw "PHP-FPM configuration validation failed (exit code $($configResult.ExitCode)):`n$($configResult.Output)"
}

$reload = Invoke-DockerExec @($phpContainer, 'kill', '-USR2', '1')
if ($reload.ExitCode -ne 0) {
    Invoke-DockerExec @($phpContainer, 'cp', $backupPath, $poolPath) | Out-Null
    throw "PHP-FPM reload failed (exit code $($reload.ExitCode)):`n$($reload.Output)"
}
Start-Sleep -Seconds 2
Write-Host 'PHP-FPM slowlog enabled.' -ForegroundColor Green

$runnerExit = 1
try {
    & powershell.exe -ExecutionPolicy Bypass -File (Join-Path $scriptRoot 'run-login-with-monitoring.ps1')
    $runnerExit = if ($null -ne $LASTEXITCODE) { $LASTEXITCODE } else { 0 }
}
finally {
    $reportRoot = Join-Path $scriptRoot '..\load\results'
    $resolvedRoot = Resolve-Path $reportRoot
    $latestReport = Get-ChildItem -Path $resolvedRoot -Directory | Sort-Object LastWriteTime -Descending | Select-Object -First 1

    if ($latestReport) {
        $reportDir = $latestReport.FullName

        try {
            Save-ContainerFile $slowlogPath (Join-Path $reportDir 'php-fpm-slowlog.txt') | Out-Null
        } catch {
            Save-CollectorError $reportDir 'collector-error-slowlog.txt' $_.Exception.Message
        }

        try {
            Save-ContainerFile $poolPath (Join-Path $reportDir 'php-fpm-pool-config-after.txt') | Out-Null
        } catch {
            Save-CollectorError $reportDir 'collector-error-pool.txt' $_.Exception.Message
        }

        try {
            $pidList = Invoke-DockerExec @($phpContainer, 'sh', '-c', 'printf "%s\n" /proc/[0-9]*')
            $rows = New-Object System.Collections.Generic.List[string]
            $rows.Add('timestamp|pid|ppid|state|utime|stime|rss_kb|cmdline')
            $ts = (Get-Date).ToUniversalTime().ToString('o')
            foreach ($procPath in ($pidList.Output -split "`r?`n")) {
                if ($procPath -notmatch '^/proc/[0-9]+$') { continue }
                $pid = Split-Path $procPath -Leaf
                $cmdResult = Invoke-DockerExec @($phpContainer, 'cat', "$procPath/cmdline")
                $cmd = $cmdResult.Output.Replace([char]0, ' ').Trim()
                if ($cmd -notlike '*php-fpm: pool www*') { continue }
                $statResult = Invoke-DockerExec @($phpContainer, 'cat', "$procPath/stat")
                $statusResult = Invoke-DockerExec @($phpContainer, 'cat', "$procPath/status")
                $statText = $statResult.Output.Trim()
                $statusText = $statusResult.Output
                if (-not $statText) { continue }
                $closeParen = $statText.LastIndexOf(')')
                if ($closeParen -lt 0) { continue }
                $afterComm = $statText.Substring($closeParen + 2).Trim()
                $fields = $afterComm -split '\s+'
                $state = if ($fields.Count -gt 0) { $fields[0] } else { '' }
                $ppid = if ($fields.Count -gt 1) { $fields[1] } else { '' }
                $utime = if ($fields.Count -ge 12) { $fields[11] } else { '' }
                $stime = if ($fields.Count -ge 13) { $fields[12] } else { '' }
                $rss = ''
                $rssMatch = [regex]::Match($statusText, '(?m)^VmRSS:\s+(\d+)\s+kB')
                if ($rssMatch.Success) { $rss = $rssMatch.Groups[1].Value }
                $safeCmd = $cmd -replace '[\r\n|]', ' '
                $rows.Add("$ts|$pid|$ppid|$state|$utime|$stime|$rss|$safeCmd")
            }
            Save-Text (Join-Path $reportDir 'php-fpm-worker-pids-diagnostic.txt') ($rows -join "`r`n")
        } catch {
            Save-CollectorError $reportDir 'collector-error-pid.txt' $_.Exception.Message
        }

        try {
            $runtime = New-Object System.Collections.Generic.List[string]
            $runtime.Add('=== PHP VERSION ===')
            $runtime.Add((Invoke-DockerExec @($phpContainer, 'php', '-v')).Output)
            $runtime.Add('=== PHP-FPM VERSION ===')
            $runtime.Add((Invoke-DockerExec @($phpContainer, 'php-fpm', '-v')).Output)
            $runtime.Add('=== PHP-FPM CONFIG TEST ===')
            $runtime.Add((Invoke-DockerExec @($phpContainer, 'php-fpm', '-tt')).Output)
            $runtime.Add('=== FILESYSTEM ===')
            $runtime.Add((Invoke-DockerExec @($phpContainer, 'df', '-h', '/', '/tmp')).Output)
            Save-Text (Join-Path $reportDir 'php-runtime-diagnostic.txt') ($runtime -join "`r`n")
        } catch {
            Save-CollectorError $reportDir 'collector-error-runtime.txt' $_.Exception.Message
        }

        try {
            $log = docker logs $phpContainer 2>&1 | Select-Object -Last 5000
            Save-Text (Join-Path $reportDir 'php-fpm-container-log-tail.txt') (($log | Out-String).TrimEnd())
        } catch {
            Save-CollectorError $reportDir 'collector-error-container-log.txt' $_.Exception.Message
        }

        try {
            $phpStats = docker stats $phpContainer --no-stream --format 'name={{.Name}} cpu={{.CPUPerc}} mem={{.MemUsage}} net={{.NetIO}} block={{.BlockIO}} pids={{.PIDs}}' 2>&1 | Out-String
            Save-Text (Join-Path $reportDir 'php-final-stats.txt') $phpStats.TrimEnd()
        } catch {
            Save-CollectorError $reportDir 'collector-error-php-stats.txt' $_.Exception.Message
        }

        $redisName = if ($env:REDIS_CONTAINER) { $env:REDIS_CONTAINER } else { 'e-ujian-redis' }
        if (@(docker ps --format '{{.Names}}' 2>$null) -contains $redisName) {
            try {
                $redisStats = docker stats $redisName --no-stream --format 'name={{.Name}} cpu={{.CPUPerc}} mem={{.MemUsage}} net={{.NetIO}} block={{.BlockIO}} pids={{.PIDs}}' 2>&1 | Out-String
                Save-Text (Join-Path $reportDir 'redis-final-stats.txt') $redisStats.TrimEnd()
            } catch { Save-CollectorError $reportDir 'collector-error-redis-stats.txt' $_.Exception.Message }
            try {
                $redisInfo = docker exec $redisName redis-cli INFO stats 2>&1 | Out-String
                Save-Text (Join-Path $reportDir 'redis-info-stats.txt') $redisInfo.TrimEnd()
            } catch { Save-CollectorError $reportDir 'collector-error-redis-info.txt' $_.Exception.Message }
        }

        if (@(docker ps --format '{{.Names}}' 2>$null) -contains 'e-ujian-mysql') {
            try {
                $mysqlStats = docker stats 'e-ujian-mysql' --no-stream --format 'name={{.Name}} cpu={{.CPUPerc}} mem={{.MemUsage}} net={{.NetIO}} block={{.BlockIO}} pids={{.PIDs}}' 2>&1 | Out-String
                Save-Text (Join-Path $reportDir 'mysql-final-stats.txt') $mysqlStats.TrimEnd()
            } catch { Save-CollectorError $reportDir 'collector-error-mysql-stats.txt' $_.Exception.Message }
        }

        $slowlogLocal = Join-Path $reportDir 'php-fpm-slowlog.txt'
        $slowlogLines = if (Test-Path $slowlogLocal) { @(Get-Content $slowlogLocal) } else { @() }
        [ordered]@{
            generated_at = (Get-Date).ToUniversalTime().ToString('o')
            php_container = $phpContainer
            pool_config = $poolPath
            slowlog_timeout = $slowlogTimeout
            slowlog_path = $slowlogPath
            slowlog_line_count = $slowlogLines.Count
            ptrace_denied = @($slowlogLines | Where-Object { $_ -match 'ptrace|Operation not permitted' }).Count
            runner_exit_code = $runnerExit
        } | ConvertTo-Json | Set-Content -Encoding UTF8 (Join-Path $reportDir 'php-fpm-slowlog.json')
    }

    try {
        $restore = Invoke-DockerExec @($phpContainer, 'cp', $backupPath, $poolPath)
        if ($restore.ExitCode -ne 0) { throw "restore exit code $($restore.ExitCode): $($restore.Output)" }
        $restoreReload = Invoke-DockerExec @($phpContainer, 'kill', '-USR2', '1')
        if ($restoreReload.ExitCode -ne 0) { throw "reload exit code $($restoreReload.ExitCode): $($restoreReload.Output)" }
        Write-Host 'PHP-FPM original pool configuration restored.' -ForegroundColor Green
    } catch {
        Write-Host "WARNING: restore failed: $($_.Exception.Message)" -ForegroundColor Red
    }
}

if ($runnerExit -ne 0) { exit $runnerExit }
