[CmdletBinding()]
param(
    [string]$VuMatrix = '10,30,100,300,709',
    [int]$MaxWaitSeconds = 300,
    [int]$PollIntervalSeconds = 2,
    [string]$BaseUrl = 'http://localhost:8080',
    [int]$DiagnosticIntervalSeconds = 1
)

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$root = (Resolve-Path (Join-Path $PSScriptRoot '..\..')).Path
$script = Join-Path $root 'tests\load\k6\login-waiting-room-batch.js'
$timestamp = Get-Date -Format 'yyyy-MM-dd_HH-mm-ss'
$reportRoot = Join-Path $root "tests\load\results\${timestamp}_waiting_room_batch"

if (-not (Test-Path $script)) { throw "K6 script not found: $script" }
if ($DiagnosticIntervalSeconds -lt 1) { throw 'DiagnosticIntervalSeconds must be >= 1.' }

$vus = @($VuMatrix -split ',' | ForEach-Object { $_.Trim() } | Where-Object { $_ -ne '' } | ForEach-Object { [int]$_ })
if ($vus.Count -eq 0) { throw 'VuMatrix must contain at least one positive integer.' }
foreach ($vu in $vus) { if ($vu -lt 1 -or $vu -gt 709) { throw "VU value must be between 1 and 709. Received: $vu" } }

New-Item -ItemType Directory -Force -Path $reportRoot | Out-Null

Write-Host '========================================================================'
Write-Host 'E-UJIAN WAITING ROOM MULTI-USER LOGIN TEST + BOTTLENECK SAMPLING'
Write-Host '========================================================================'
Write-Host "BASE_URL              : $BaseUrl"
Write-Host "VU matrix             : $($vus -join ', ')"
Write-Host "Max queue wait        : ${MaxWaitSeconds}s"
Write-Host "Poll interval         : ${PollIntervalSeconds}s"
Write-Host "Diagnostic interval   : ${DiagnosticIntervalSeconds}s"
Write-Host "Report                : $reportRoot"
Write-Host ''

try {
    $health = Invoke-WebRequest -Uri "$BaseUrl/login" -Method GET -UseBasicParsing -TimeoutSec 10
    if ($health.StatusCode -ne 200) { throw "GET /login returned HTTP $($health.StatusCode)" }
    Write-Host '[OK] GET /login HTTP 200'
} catch { throw "Application check failed: $($_.Exception.Message)" }

function Get-SummaryMetric {
    param(
        [Parameter(Mandatory=$true)][object]$Metrics,
        [Parameter(Mandatory=$true)][string]$MetricName,
        [Parameter(Mandatory=$true)][string]$ValueName,
        [object]$Default = 0
    )

    if ($null -eq $Metrics) { return $Default }
    $metricProperty = $Metrics.PSObject.Properties[$MetricName]
    if ($null -eq $metricProperty -or $null -eq $metricProperty.Value) { return $Default }

    $metric = $metricProperty.Value
    $valueProperty = $metric.PSObject.Properties[$ValueName]
    if ($null -ne $valueProperty -and $null -ne $valueProperty.Value) { return $valueProperty.Value }

    $valuesProperty = $metric.PSObject.Properties['values']
    if ($null -ne $valuesProperty -and $null -ne $valuesProperty.Value) {
        $nested = $valuesProperty.Value.PSObject.Properties[$ValueName]
        if ($null -ne $nested -and $null -ne $nested.Value) { return $nested.Value }
    }

    return $Default
}

function Start-BottleneckSampler {
    param(
        [Parameter(Mandatory=$true)][string]$OutputCsv,
        [Parameter(Mandatory=$true)][int]$IntervalSeconds
    )

    $job = Start-Job -ArgumentList $OutputCsv, $IntervalSeconds -ScriptBlock {
        param($CsvPath, $Interval)

        $ErrorActionPreference = 'SilentlyContinue'
        $first = $true
        $dir = Split-Path -Parent $CsvPath
        New-Item -ItemType Directory -Force -Path $dir | Out-Null

        while ($true) {
            $ts = Get-Date -Format 'yyyy-MM-ddTHH:mm:ss.fffK'
            $dockerRows = @()
            $phpWorkers = 0
            $phpMaster = 0
            $phpMaxChildren = ''
            $phpStartServers = ''
            $phpMinSpare = ''
            $phpMaxSpare = ''
            $mysqlThreadsConnected = ''
            $mysqlThreadsRunning = ''
            $mysqlThreadsCreated = ''
            $mysqlConnections = ''
            $mysqlSlowQueries = ''
            $mysqlQuestions = ''
            $mysqlUptime = ''

            try {
                $stats = & docker stats e-ujian-nginx e-ujian-php e-ujian-mysql --no-stream --format '{{json .}}' 2>$null
                foreach ($line in @($stats)) {
                    if ([string]::IsNullOrWhiteSpace($line)) { continue }
                    try {
                        $dockerRows += ($line | ConvertFrom-Json)
                    } catch {}
                }
            } catch {}

            try {
                $procLines = & docker exec e-ujian-php sh -c 'for p in /proc/[0-9]*; do if [ -r "$p/cmdline" ]; then c=$(tr "\000" " " < "$p/cmdline" 2>/dev/null); case "$c" in *php-fpm*) echo "$c";; esac; fi; done' 2>$null
                foreach ($p in @($procLines)) {
                    if ([string]::IsNullOrWhiteSpace($p)) { continue }
                    if ($p -match 'master process') { $phpMaster++ } elseif ($p -match 'php-fpm: pool') { $phpWorkers++ }
                }
            } catch {}

            try {
                $fpm = & docker exec e-ujian-php sh -c 'grep -E "^[[:space:]]*pm\.(max_children|start_servers|min_spare_servers|max_spare_servers)[[:space:]]*=" /usr/local/etc/php-fpm.d/www.conf 2>/dev/null' 2>$null
                foreach ($line in @($fpm)) {
                    if ($line -match 'pm\.max_children\s*=\s*(\d+)') { $phpMaxChildren = $Matches[1] }
                    if ($line -match 'pm\.start_servers\s*=\s*(\d+)') { $phpStartServers = $Matches[1] }
                    if ($line -match 'pm\.min_spare_servers\s*=\s*(\d+)') { $phpMinSpare = $Matches[1] }
                    if ($line -match 'pm\.max_spare_servers\s*=\s*(\d+)') { $phpMaxSpare = $Matches[1] }
                }
            } catch {}

            try {
                $mysql = & docker exec e-ujian-mysql sh -c 'mysql -uroot -plocal_root_password euji_ujian_db -Nse "SHOW GLOBAL STATUS WHERE Variable_name IN (\"Threads_connected\",\"Threads_running\",\"Threads_created\",\"Connections\",\"Slow_queries\",\"Questions\",\"Uptime\");" 2>/dev/null' 2>$null
                foreach ($line in @($mysql)) {
                    $parts = $line -split "\t", 2
                    if ($parts.Count -ne 2) { continue }
                    switch ($parts[0]) {
                        'Threads_connected' { $mysqlThreadsConnected = $parts[1] }
                        'Threads_running' { $mysqlThreadsRunning = $parts[1] }
                        'Threads_created' { $mysqlThreadsCreated = $parts[1] }
                        'Connections' { $mysqlConnections = $parts[1] }
                        'Slow_queries' { $mysqlSlowQueries = $parts[1] }
                        'Questions' { $mysqlQuestions = $parts[1] }
                        'Uptime' { $mysqlUptime = $parts[1] }
                    }
                }
            } catch {}

            $nginx = $dockerRows | Where-Object { $_.Name -eq 'e-ujian-nginx' } | Select-Object -First 1
            $php = $dockerRows | Where-Object { $_.Name -eq 'e-ujian-php' } | Select-Object -First 1
            $mysqlContainer = $dockerRows | Where-Object { $_.Name -eq 'e-ujian-mysql' } | Select-Object -First 1

            $row = [PSCustomObject]@{
                timestamp = $ts
                nginx_cpu = if ($nginx) { $nginx.CPUPerc } else { '' }
                nginx_memory = if ($nginx) { $nginx.MemUsage } else { '' }
                php_cpu = if ($php) { $php.CPUPerc } else { '' }
                php_memory = if ($php) { $php.MemUsage } else { '' }
                php_pids = if ($php) { $php.PIDs } else { '' }
                php_fpm_master = $phpMaster
                php_fpm_workers = $phpWorkers
                php_fpm_max_children = $phpMaxChildren
                php_fpm_start_servers = $phpStartServers
                php_fpm_min_spare_servers = $phpMinSpare
                php_fpm_max_spare_servers = $phpMaxSpare
                mysql_cpu = if ($mysqlContainer) { $mysqlContainer.CPUPerc } else { '' }
                mysql_memory = if ($mysqlContainer) { $mysqlContainer.MemUsage } else { '' }
                mysql_pids = if ($mysqlContainer) { $mysqlContainer.PIDs } else { '' }
                mysql_threads_connected = $mysqlThreadsConnected
                mysql_threads_running = $mysqlThreadsRunning
                mysql_threads_created = $mysqlThreadsCreated
                mysql_connections = $mysqlConnections
                mysql_slow_queries = $mysqlSlowQueries
                mysql_questions = $mysqlQuestions
                mysql_uptime = $mysqlUptime
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

    return $job
}

function Stop-BottleneckSampler {
    param(
        [Parameter(Mandatory=$true)][System.Management.Automation.Job]$Job,
        [Parameter(Mandatory=$true)][string]$SlowlogPath
    )

    try { Stop-Job -Job $Job -ErrorAction SilentlyContinue | Out-Null } catch {}
    try { Remove-Job -Job $Job -Force -ErrorAction SilentlyContinue } catch {}

    try {
        docker exec e-ujian-php sh -c 'if [ -f /tmp/e-ujian-php-fpm-slowlog-v5.log ]; then cat /tmp/e-ujian-php-fpm-slowlog-v5.log; fi' 2>$null | Set-Content -Path $SlowlogPath -Encoding UTF8
    } catch {
        Set-Content -Path $SlowlogPath -Value "Unable to collect PHP-FPM slowlog: $($_.Exception.Message)" -Encoding UTF8
    }
}

$rows = @()

foreach ($vu in $vus) {
    $dir = Join-Path $reportRoot ("vu_{0:D3}" -f $vu)
    New-Item -ItemType Directory -Force -Path $dir | Out-Null

    $summary = Join-Path $dir 'summary.json'
    $console = Join-Path $dir 'k6-output.txt'
    $diagnostics = Join-Path $dir 'bottleneck-samples.csv'
    $slowlog = Join-Path $dir 'php-fpm-slowlog.txt'
    $fpmConfig = Join-Path $dir 'php-fpm-config.txt'
    $mysqlStatus = Join-Path $dir 'mysql-status-final.txt'

    $env:BASE_URL = $BaseUrl
    $env:TOTAL_USERS = [string]$vu
    $env:MAX_WAIT_SECONDS = [string]$MaxWaitSeconds
    $env:POLL_INTERVAL_SECONDS = [string]$PollIntervalSeconds
    $env:K6_SUMMARY_FILE = $summary

    Write-Host ''
    Write-Host '========================================================================'
    Write-Host "BATCH $vu USERS / WAITING ROOM"
    Write-Host '========================================================================'
    Write-Host "[K6] k6 run --summary-export $summary $script"
    Write-Host "[DIAG] Sampling Nginx + PHP-FPM + MySQL every ${DiagnosticIntervalSeconds}s"

    try {
        docker exec e-ujian-php sh -c 'php-fpm -tt 2>&1' 2>$null | Set-Content -Path $fpmConfig -Encoding UTF8
    } catch {}

    $sampler = Start-BottleneckSampler -OutputCsv $diagnostics -IntervalSeconds $DiagnosticIntervalSeconds
    $k6Exit = 0

    try {
        & k6 run --summary-export $summary $script 2>&1 | Tee-Object -FilePath $console
        if ($LASTEXITCODE -ne 0) { $k6Exit = $LASTEXITCODE }
    } finally {
        Stop-BottleneckSampler -Job $sampler -SlowlogPath $slowlog
    }

    try {
        docker exec e-ujian-mysql sh -c 'mysql -uroot -plocal_root_password euji_ujian_db -e "SHOW GLOBAL STATUS WHERE Variable_name IN (\"Threads_connected\",\"Threads_running\",\"Threads_created\",\"Connections\",\"Slow_queries\",\"Questions\",\"Uptime\"); SHOW FULL PROCESSLIST;" 2>/dev/null' 2>$null | Set-Content -Path $mysqlStatus -Encoding UTF8
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

            if ($null -ne $metrics) {
                $loginSuccessRate = [double](Get-SummaryMetric $metrics 'login_success_rate' 'rate' 0)
                $httpFailedRate = [double](Get-SummaryMetric $metrics 'http_failed_rate' 'rate' 0)
                $queueReady = [int](Get-SummaryMetric $metrics 'queue_ready' 'count' 0)
                $queueExpired = [int](Get-SummaryMetric $metrics 'queue_expired' 'count' 0)
                $authSuccess = [int](Get-SummaryMetric $metrics 'auth_success' 'count' 0)
                $authFailure = [int](Get-SummaryMetric $metrics 'auth_failure' 'count' 0)
                $loginP95 = [double](Get-SummaryMetric $metrics 'login_duration' 'p(95)' 0)
                $loginP99 = [double](Get-SummaryMetric $metrics 'login_duration' 'p(99)' 0)
                $queueP95 = [double](Get-SummaryMetric $metrics 'queue_wait_duration' 'p(95)' 0)
                $authP95 = [double](Get-SummaryMetric $metrics 'auth_duration' 'p(95)' 0)
            }

            if ($queueReady -eq 0 -and $authSuccess -eq 0 -and (Test-Path $console)) {
                $text = Get-Content $console -Raw
                $patterns = @{
                    loginSuccessRate = 'LOGIN SUCCESS RATE\s*:\s*([0-9.,]+)%'
                    httpFailedRate = 'HTTP FAILED RATE\s*:\s*([0-9.,]+)%'
                    queueReady = 'QUEUE READY\s*:\s*(\d+)'
                    queueExpired = 'QUEUE EXPIRED\s*:\s*(\d+)'
                    authSuccess = 'AUTH SUCCESS\s*:\s*(\d+)'
                    authFailure = 'AUTH FAILURE\s*:\s*(\d+)'
                    queueP95 = 'QUEUE WAIT P95\s*:\s*([0-9.,]+)\s*ms'
                    authP95 = 'AUTH P95\s*:\s*([0-9.,]+)\s*ms'
                    loginP95 = 'LOGIN P95\s*:\s*([0-9.,]+)\s*ms'
                    loginP99 = 'LOGIN P99\s*:\s*([0-9.,]+)\s*ms'
                }

                foreach ($key in $patterns.Keys) {
                    $match = [regex]::Match($text, $patterns[$key], [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
                    if (-not $match.Success) { continue }
                    $value = $match.Groups[1].Value.Replace(',', '.')
                    switch ($key) {
                        'loginSuccessRate' { $loginSuccessRate = [double]$value / 100 }
                        'httpFailedRate' { $httpFailedRate = [double]$value / 100 }
                        'queueReady' { $queueReady = [int]$value }
                        'queueExpired' { $queueExpired = [int]$value }
                        'authSuccess' { $authSuccess = [int]$value }
                        'authFailure' { $authFailure = [int]$value }
                        'queueP95' { $queueP95 = [double]$value }
                        'authP95' { $authP95 = [double]$value }
                        'loginP95' { $loginP95 = [double]$value }
                        'loginP99' { $loginP99 = [double]$value }
                    }
                }
            }
        } catch {
            Write-Warning "Could not parse summary for VU=$vu : $($_.Exception.Message)"
        }
    } else {
        Write-Warning "K6 summary was not generated for VU=$vu"
    }

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
        bottleneck_samples = $diagnostics
        php_fpm_slowlog = $slowlog
        mysql_status = $mysqlStatus
        report_dir = $dir
    }

    Write-Host "[RESULT] VU=$vu exit=$k6Exit success_rate=$([math]::Round($loginSuccessRate * 100, 2))% queue_ready=$queueReady queue_expired=$queueExpired auth_success=$authSuccess auth_failure=$authFailure queue_p95=${queueP95}ms auth_p95=${authP95}ms login_p95=${loginP95}ms login_p99=${loginP99}ms"
    Write-Host "[DATA] Bottleneck samples : $diagnostics"
    Write-Host "[DATA] PHP-FPM slowlog    : $slowlog"
    Write-Host "[DATA] PHP-FPM config      : $fpmConfig"
    Write-Host "[DATA] MySQL final status  : $mysqlStatus"
}

$csv = Join-Path $reportRoot 'waiting-room-summary.csv'
$rows | Export-Csv -Path $csv -NoTypeInformation -Encoding UTF8

Write-Host ''
Write-Host '========================================================================'
Write-Host 'WAITING ROOM TEST COMPLETE'
Write-Host '========================================================================'
Write-Host "Report : $reportRoot"
Write-Host "Summary: $csv"
$rows | Format-Table -AutoSize
Write-Host ''
Write-Host '[DIAGNOSTIC] Each batch automatically captured:'
Write-Host '  - Docker CPU/RAM/PIDs for Nginx, PHP and MySQL'
Write-Host '  - PHP-FPM process count and pm.max_children configuration'
Write-Host '  - MySQL Threads_connected / Threads_running / Connections / Slow_queries'
Write-Host '  - PHP-FPM slowlog'
Write-Host '  - MySQL final process/status snapshot'
