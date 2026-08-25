[CmdletBinding()]
param(
    [string]$VuMatrix = '30',
    [int]$MaxWaitSeconds = 60,
    [int]$PollIntervalSeconds = 2,
    [string]$BaseUrl = 'http://localhost:8080',
    [int]$DiagnosticIntervalSeconds = 1,
    [string]$PhpContainer = 'e-ujian-php',
    [string]$MysqlContainer = 'e-ujian-mysql',
    [string]$NginxContainer = 'e-ujian-nginx',
    [string]$MysqlUser = 'root',
    [string]$MysqlPassword = 'local_root_password'
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$root = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$k6Script = Join-Path $root 'tests\load\k6\login-waiting-room-batch.js'
$timestamp = Get-Date -Format 'yyyy-MM-dd_HH-mm-ss'
$reportRoot = Join-Path $root "tests\load\results\${timestamp}_waiting_room_batch_v2"

if (-not (Test-Path $k6Script)) { throw "K6 script not found: $k6Script" }
if ($DiagnosticIntervalSeconds -lt 1) { throw 'DiagnosticIntervalSeconds must be >= 1.' }

$vus = @(
    $VuMatrix -split ',' |
        ForEach-Object { $_.Trim() } |
        Where-Object { $_ -ne '' } |
        ForEach-Object { [int]$_ }
)

if ($vus.Count -eq 0) { throw 'VuMatrix must contain at least one integer.' }
foreach ($vu in $vus) {
    if ($vu -lt 1 -or $vu -gt 709) { throw "VU must be between 1 and 709. Received: $vu" }
}

New-Item -ItemType Directory -Force -Path $reportRoot | Out-Null

Write-Host '========================================================================'
Write-Host 'E-UJIAN WAITING ROOM BOTTLENECK DIAGNOSTIC V2'
Write-Host '========================================================================'
Write-Host "BASE_URL              : $BaseUrl"
Write-Host "VU matrix             : $($vus -join ', ')"
Write-Host "Max queue wait        : ${MaxWaitSeconds}s"
Write-Host "Poll interval         : ${PollIntervalSeconds}s"
Write-Host "Diagnostic interval   : ${DiagnosticIntervalSeconds}s"
Write-Host "PHP container         : $PhpContainer"
Write-Host "MySQL container       : $MysqlContainer"
Write-Host "Nginx container       : $NginxContainer"
Write-Host "Report                : $reportRoot"
Write-Host ''

function Test-Command {
    param([string]$Name)
    $cmd = Get-Command $Name -ErrorAction SilentlyContinue
    if ($null -eq $cmd) { throw "$Name was not found in PATH." }
}

Test-Command 'docker'
Test-Command 'k6'

$containers = @($PhpContainer, $MysqlContainer, $NginxContainer)
foreach ($container in $containers) {
    $state = (& docker inspect -f '{{.State.Status}}' $container 2>$null | Out-String).Trim()
    if ($LASTEXITCODE -ne 0 -or $state -ne 'running') {
        throw "Container '$container' is not running. State='$state'"
    }
}
Write-Host '[OK] Docker containers running.'

try {
    $health = Invoke-WebRequest -Uri "$BaseUrl/login" -Method GET -UseBasicParsing -TimeoutSec 10
    if ($health.StatusCode -ne 200) { throw "HTTP $($health.StatusCode)" }
    Write-Host '[OK] GET /login HTTP 200'
} catch {
    throw "Application check failed: $($_.Exception.Message)"
}

function Invoke-DockerText {
    param([string[]]$Arguments)
    try {
        $result = & docker @Arguments 2>&1
        return (($result | ForEach-Object { [string]$_ }) -join "`n")
    } catch {
        return ''
    }
}

function Get-K6Metric {
    param(
        [object]$Metrics,
        [string]$MetricName,
        [string]$ValueName,
        [object]$Default = 0
    )

    if ($null -eq $Metrics) { return $Default }
    $property = $Metrics.PSObject.Properties[$MetricName]
    if ($null -eq $property -or $null -eq $property.Value) { return $Default }

    $metric = $property.Value
    $value = $metric.PSObject.Properties[$ValueName]
    if ($null -ne $value -and $null -ne $value.Value) { return $value.Value }

    $values = $metric.PSObject.Properties['values']
    if ($null -ne $values -and $null -ne $values.Value) {
        $nested = $values.Value.PSObject.Properties[$ValueName]
        if ($null -ne $nested -and $null -ne $nested.Value) { return $nested.Value }
    }

    return $Default
}

function Start-DiagnosticSampler {
    param(
        [string]$OutputCsv,
        [int]$IntervalSeconds,
        [string]$Php,
        [string]$Mysql,
        [string]$Nginx,
        [string]$DbUser,
        [string]$DbPassword
    )

    return Start-Job -ArgumentList @(
        $OutputCsv,
        $IntervalSeconds,
        $Php,
        $Mysql,
        $Nginx,
        $DbUser,
        $DbPassword
    ) -ScriptBlock {
        param($CsvPath, $Interval, $PhpContainer, $MysqlContainer, $NginxContainer, $DbUser, $DbPassword)

        $ErrorActionPreference = 'SilentlyContinue'
        $first = $true
        New-Item -ItemType Directory -Force -Path (Split-Path -Parent $CsvPath) | Out-Null

        while ($true) {
            $timestamp = Get-Date -Format 'yyyy-MM-ddTHH:mm:ss.fffK'

            $dockerStats = @{}
            $statsLines = @(& docker stats $NginxContainer $PhpContainer $MysqlContainer --no-stream --format '{{.Name}}|{{.CPUPerc}}|{{.MemUsage}}|{{.PIDs}}' 2>$null)
            foreach ($line in $statsLines) {
                if ([string]::IsNullOrWhiteSpace([string]$line)) { continue }
                $parts = ([string]$line) -split '\|', 4
                if ($parts.Count -ge 4) {
                    $dockerStats[$parts[0]] = $parts
                }
            }

            $phpWorkers = 0
            $phpMaster = 0
            $procOutput = @(& docker exec $PhpContainer sh -c 'for p in /proc/[0-9]*; do if [ -r "$p/cmdline" ]; then c=$(tr "\000" " " < "$p/cmdline" 2>/dev/null); case "$c" in *php-fpm*) echo "$c";; esac; fi; done' 2>$null)
            foreach ($line in $procOutput) {
                $text = [string]$line
                if ($text -match 'master process') { $phpMaster++ }
                elseif ($text -match 'php-fpm: pool') { $phpWorkers++ }
            }

            $fpmMaxChildren = ''
            $fpmStartServers = ''
            $fpmMinSpare = ''
            $fpmMaxSpare = ''
            $fpmOutput = @(& docker exec $PhpContainer sh -c 'grep -E "^[[:space:]]*pm\.(max_children|start_servers|min_spare_servers|max_spare_servers)[[:space:]]*=" /usr/local/etc/php-fpm.d/www.conf 2>/dev/null' 2>$null)
            foreach ($line in $fpmOutput) {
                $text = [string]$line
                if ($text -match 'pm\.max_children\s*=\s*(\d+)') { $fpmMaxChildren = $Matches[1] }
                elseif ($text -match 'pm\.start_servers\s*=\s*(\d+)') { $fpmStartServers = $Matches[1] }
                elseif ($text -match 'pm\.min_spare_servers\s*=\s*(\d+)') { $fpmMinSpare = $Matches[1] }
                elseif ($text -match 'pm\.max_spare_servers\s*=\s*(\d+)') { $fpmMaxSpare = $Matches[1] }
            }

            $mysqlStatus = @{}
            $statusQuery = 'SHOW GLOBAL STATUS WHERE Variable_name IN (''Threads_connected'',''Threads_running'',''Threads_created'',''Connections'',''Slow_queries'',''Questions'',''Uptime'');'
            $mysqlOutput = @(& docker exec $MysqlContainer mysql "-u$DbUser" "-p$DbPassword" -Nse $statusQuery 2>$null)
            foreach ($line in $mysqlOutput) {
                $parts = ([string]$line) -split "`t", 2
                if ($parts.Count -eq 2) { $mysqlStatus[$parts[0]] = $parts[1] }
            }

            function StatValue([hashtable]$Map, [string]$Name, [int]$Index) {
                if ($Map.ContainsKey($Name)) { return $Map[$Name][$Index] }
                return ''
            }

            $nginx = if ($dockerStats.ContainsKey($NginxContainer)) { $dockerStats[$NginxContainer] } else { $null }
            $php = if ($dockerStats.ContainsKey($PhpContainer)) { $dockerStats[$PhpContainer] } else { $null }
            $mysql = if ($dockerStats.ContainsKey($MysqlContainer)) { $dockerStats[$MysqlContainer] } else { $null }

            $row = [PSCustomObject]@{
                timestamp = $timestamp
                nginx_cpu = if ($null -ne $nginx) { $nginx[1] } else { '' }
                nginx_memory = if ($null -ne $nginx) { $nginx[2] } else { '' }
                nginx_pids = if ($null -ne $nginx) { $nginx[3] } else { '' }
                php_cpu = if ($null -ne $php) { $php[1] } else { '' }
                php_memory = if ($null -ne $php) { $php[2] } else { '' }
                php_pids = if ($null -ne $php) { $php[3] } else { '' }
                php_fpm_master = $phpMaster
                php_fpm_workers = $phpWorkers
                php_fpm_max_children = $fpmMaxChildren
                php_fpm_start_servers = $fpmStartServers
                php_fpm_min_spare_servers = $fpmMinSpare
                php_fpm_max_spare_servers = $fpmMaxSpare
                mysql_cpu = if ($null -ne $mysql) { $mysql[1] } else { '' }
                mysql_memory = if ($null -ne $mysql) { $mysql[2] } else { '' }
                mysql_pids = if ($null -ne $mysql) { $mysql[3] } else { '' }
                mysql_threads_connected = if ($mysqlStatus.ContainsKey('Threads_connected')) { $mysqlStatus['Threads_connected'] } else { '' }
                mysql_threads_running = if ($mysqlStatus.ContainsKey('Threads_running')) { $mysqlStatus['Threads_running'] } else { '' }
                mysql_threads_created = if ($mysqlStatus.ContainsKey('Threads_created')) { $mysqlStatus['Threads_created'] } else { '' }
                mysql_connections = if ($mysqlStatus.ContainsKey('Connections')) { $mysqlStatus['Connections'] } else { '' }
                mysql_slow_queries = if ($mysqlStatus.ContainsKey('Slow_queries')) { $mysqlStatus['Slow_queries'] } else { '' }
                mysql_questions = if ($mysqlStatus.ContainsKey('Questions')) { $mysqlStatus['Questions'] } else { '' }
                mysql_uptime = if ($mysqlStatus.ContainsKey('Uptime')) { $mysqlStatus['Uptime'] } else { '' }
            }

            if ($first) {
                $row | Export-Csv -Path $CsvPath -NoTypeInformation -Encoding UTF8
                $first = $false
            } else {
                $row | Export-Csv -Path $CsvPath -NoTypeInformation -Encoding UTF8 -Append
            }

            Start-Sleep -Seconds $Interval
        }
    }
}

function Stop-DiagnosticSampler {
    param([System.Management.Automation.Job]$Job)
    if ($null -eq $Job) { return }
    try { Stop-Job -Job $Job -ErrorAction SilentlyContinue | Out-Null } catch {}
    try { Remove-Job -Job $Job -Force -ErrorAction SilentlyContinue } catch {}
}

$rows = @()

foreach ($vu in $vus) {
    $dir = Join-Path $reportRoot ('vu_{0:D3}' -f $vu)
    New-Item -ItemType Directory -Force -Path $dir | Out-Null

    $summary = Join-Path $dir 'summary.json'
    $console = Join-Path $dir 'k6-output.txt'
    $samples = Join-Path $dir 'bottleneck-samples.csv'
    $fpmConfig = Join-Path $dir 'php-fpm-config.txt'
    $slowlog = Join-Path $dir 'php-fpm-slowlog.txt'
    $mysqlFinal = Join-Path $dir 'mysql-status-final.txt'

    $env:BASE_URL = $BaseUrl
    $env:TOTAL_USERS = [string]$vu
    $env:MAX_WAIT_SECONDS = [string]$MaxWaitSeconds
    $env:POLL_INTERVAL_SECONDS = [string]$PollIntervalSeconds

    Write-Host ''
    Write-Host '========================================================================'
    Write-Host "BATCH $vu USERS / WAITING ROOM"
    Write-Host '========================================================================'
    Write-Host "[DIAG] PHP-FPM + MySQL + Docker sampling every ${DiagnosticIntervalSeconds}s"

    try {
        $fpmText = Invoke-DockerText @('exec', $PhpContainer, 'php-fpm', '-tt')
        Set-Content -Path $fpmConfig -Value $fpmText -Encoding UTF8
    } catch {}

    $sampler = $null
    try {
        $sampler = Start-DiagnosticSampler -OutputCsv $samples -IntervalSeconds $DiagnosticIntervalSeconds -Php $PhpContainer -Mysql $MysqlContainer -Nginx $NginxContainer -DbUser $MysqlUser -DbPassword $MysqlPassword

        Write-Host "[K6] k6 run --summary-export $summary $k6Script"
        & k6 run --summary-export $summary $k6Script 2>&1 | Tee-Object -FilePath $console
        $k6Exit = $LASTEXITCODE
    } finally {
        Stop-DiagnosticSampler -Job $sampler
    }

    try {
        $slow = Invoke-DockerText @('exec', $PhpContainer, 'sh', '-c', 'if [ -f /tmp/e-ujian-php-fpm-slowlog-v5.log ]; then cat /tmp/e-ujian-php-fpm-slowlog-v5.log; fi')
        Set-Content -Path $slowlog -Value $slow -Encoding UTF8
    } catch {}

    try {
        $statusQuery = 'SHOW GLOBAL STATUS WHERE Variable_name IN (''Threads_connected'',''Threads_running'',''Threads_created'',''Connections'',''Slow_queries'',''Questions'',''Uptime''); SHOW FULL PROCESSLIST;'
        $finalStatus = Invoke-DockerText @('exec', $MysqlContainer, 'mysql', "-u$MysqlUser", "-p$MysqlPassword", '-e', $statusQuery)
        Set-Content -Path $mysqlFinal -Value $finalStatus -Encoding UTF8
    } catch {}

    $loginSuccessRate = 0.0
    $httpFailedRate = 0.0
    $queueReady = 0
    $queueExpired = 0
    $authSuccess = 0
    $authFailure = 0
    $loginP95 = 0.0
    $loginP99 = 0.0
    $queueP95 = 0.0
    $authP95 = 0.0

    if (Test-Path $summary) {
        try {
            $obj = Get-Content $summary -Raw | ConvertFrom-Json
            $metrics = $null
            if ($null -ne $obj.PSObject.Properties['metrics']) { $metrics = $obj.metrics }
            $loginSuccessRate = [double](Get-K6Metric $metrics 'login_success_rate' 'rate' 0)
            $httpFailedRate = [double](Get-K6Metric $metrics 'http_failed_rate' 'rate' 0)
            $queueReady = [int](Get-K6Metric $metrics 'queue_ready' 'count' 0)
            $queueExpired = [int](Get-K6Metric $metrics 'queue_expired' 'count' 0)
            $authSuccess = [int](Get-K6Metric $metrics 'auth_success' 'count' 0)
            $authFailure = [int](Get-K6Metric $metrics 'auth_failure' 'count' 0)
            $loginP95 = [double](Get-K6Metric $metrics 'login_duration' 'p(95)' 0)
            $loginP99 = [double](Get-K6Metric $metrics 'login_duration' 'p(99)' 0)
            $queueP95 = [double](Get-K6Metric $metrics 'queue_wait_duration' 'p(95)' 0)
            $authP95 = [double](Get-K6Metric $metrics 'auth_duration' 'p(95)' 0)
        } catch {
            Write-Warning "Could not parse k6 summary: $($_.Exception.Message)"
        }
    }

    $sampleCount = 0
    $phpCpuMax = 0.0
    $mysqlCpuMax = 0.0
    $phpWorkersMax = 0
    $mysqlThreadsRunningMax = 0

    if (Test-Path $samples) {
        try {
            $sampleRows = @(Import-Csv $samples)
            $sampleCount = $sampleRows.Count
            foreach ($sample in $sampleRows) {
                $cpuText = ([string]$sample.php_cpu).Replace('%','').Trim()
                $cpu = 0.0
                if ([double]::TryParse($cpuText, [ref]$cpu) -and $cpu -gt $phpCpuMax) { $phpCpuMax = $cpu }

                $mysqlCpuText = ([string]$sample.mysql_cpu).Replace('%','').Trim()
                $mysqlCpu = 0.0
                if ([double]::TryParse($mysqlCpuText, [ref]$mysqlCpu) -and $mysqlCpu -gt $mysqlCpuMax) { $mysqlCpuMax = $mysqlCpu }

                $workers = 0
                if ([int]::TryParse([string]$sample.php_fpm_workers, [ref]$workers) -and $workers -gt $phpWorkersMax) { $phpWorkersMax = $workers }

                $threads = 0
                if ([int]::TryParse([string]$sample.mysql_threads_running, [ref]$threads) -and $threads -gt $mysqlThreadsRunningMax) { $mysqlThreadsRunningMax = $threads }
            }
        } catch {
            Write-Warning "Could not parse bottleneck samples: $($_.Exception.Message)"
        }
    }

    $findings = New-Object System.Collections.Generic.List[string]
    if ($loginP95 -ge 5000) { $findings.Add('Login p95 >= 5s.') }
    if ($phpWorkersMax -gt 0 -and $phpWorkersMax -ge 20) { $findings.Add('PHP-FPM workers reached 20 or more.') }
    if ($phpCpuMax -ge 90) { $findings.Add('PHP container reached >=90% CPU.') }
    if ($mysqlThreadsRunningMax -ge 10) { $findings.Add('MySQL Threads_running reached >=10.') }
    if ($mysqlCpuMax -ge 50) { $findings.Add('MySQL container reached >=50% CPU.') }
    if ($queueP95 -ge 3000) { $findings.Add('Waiting-room queue p95 >=3s.') }
    if ($authP95 -ge 2000) { $findings.Add('Auth p95 >=2s.') }
    if ($findings.Count -eq 0) { $findings.Add('No configured bottleneck threshold crossed.') }

    $rows += [PSCustomObject]@{
        vus = $vu
        k6_exit_code = $k6Exit
        login_success_rate = $loginSuccessRate
        http_failed_rate = $httpFailedRate
        queue_ready = $queueReady
        queue_expired = $queueExpired
        auth_success = $authSuccess
        auth_failure = $authFailure
        queue_p95_ms = $queueP95
        auth_p95_ms = $authP95
        login_p95_ms = $loginP95
        login_p99_ms = $loginP99
        diagnostic_samples = $sampleCount
        php_cpu_max = $phpCpuMax
        php_fpm_workers_max = $phpWorkersMax
        mysql_cpu_max = $mysqlCpuMax
        mysql_threads_running_max = $mysqlThreadsRunningMax
        findings = ($findings -join ' | ')
        report_dir = $dir
    }

    Write-Host "[RESULT] VU=$vu exit=$k6Exit success=$([math]::Round($loginSuccessRate * 100,2))% queue_ready=$queueReady auth_success=$authSuccess queue_p95=${queueP95}ms auth_p95=${authP95}ms login_p95=${loginP95}ms login_p99=${loginP99}ms"
    Write-Host "[RESOURCE] samples=$sampleCount php_cpu_max=$phpCpuMax% php_workers_max=$phpWorkersMax mysql_cpu_max=$mysqlCpuMax% mysql_threads_running_max=$mysqlThreadsRunningMax"
    Write-Host "[DATA] $samples"
}

$summaryCsv = Join-Path $reportRoot 'waiting-room-bottleneck-summary.csv'
$rows | Export-Csv -Path $summaryCsv -NoTypeInformation -Encoding UTF8

Write-Host ''
Write-Host '========================================================================'
Write-Host 'DIAGNOSTIC COMPLETE'
Write-Host '========================================================================'
Write-Host "Report : $reportRoot"
Write-Host "Summary: $summaryCsv"
$rows | Format-Table -AutoSize
Write-Host ''
Write-Host 'Automatic capture per batch:'
Write-Host '  - Docker CPU/RAM/PIDs: Nginx, PHP-FPM, MySQL'
Write-Host '  - PHP-FPM worker count and pm.max_children'
Write-Host '  - MySQL Threads_connected / Threads_running / Connections / Slow_queries'
Write-Host '  - PHP-FPM slowlog'
Write-Host '  - MySQL final PROCESSLIST/status'
Write-Host '  - K6 login / queue / auth latency'
