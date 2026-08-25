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
$k6Script = Join-Path $root 'tests\load\k6\login-waiting-room-bottleneck.js'
$timestamp = Get-Date -Format 'yyyy-MM-dd_HH-mm-ss'
$reportRoot = Join-Path $root "tests\load\results\${timestamp}_waiting_room_bottleneck_v2"

if (-not (Test-Path $k6Script)) { throw "K6 script not found: $k6Script" }
if ($DiagnosticIntervalSeconds -lt 1) { throw 'DiagnosticIntervalSeconds must be >= 1.' }

$vus = @($VuMatrix -split ',' | ForEach-Object { $_.Trim() } | Where-Object { $_ -ne '' } | ForEach-Object { [int]$_ })
if ($vus.Count -eq 0) { throw 'VuMatrix must contain at least one integer.' }
foreach ($vu in $vus) { if ($vu -lt 1 -or $vu -gt 709) { throw "VU must be between 1 and 709. Received: $vu" } }

New-Item -ItemType Directory -Force -Path $reportRoot | Out-Null

function Require-Command([string]$Name) {
    if ($null -eq (Get-Command $Name -ErrorAction SilentlyContinue)) { throw "$Name was not found in PATH." }
}
Require-Command 'docker'
Require-Command 'k6'

foreach ($container in @($PhpContainer, $MysqlContainer, $NginxContainer)) {
    $state = (& docker inspect -f '{{.State.Status}}' $container 2>$null | Out-String).Trim()
    if ($LASTEXITCODE -ne 0 -or $state -ne 'running') { throw "Container '$container' is not running. State='$state'" }
}

$health = Invoke-WebRequest -Uri "$BaseUrl/login" -Method GET -UseBasicParsing -TimeoutSec 10
if ($health.StatusCode -ne 200) { throw "GET /login returned HTTP $($health.StatusCode)" }

Write-Host '========================================================================'
Write-Host 'E-UJIAN FOCUSED LOGIN BOTTLENECK DIAGNOSTIC V2'
Write-Host '========================================================================'
Write-Host "BASE_URL            : $BaseUrl"
Write-Host "VU matrix           : $($vus -join ', ')"
Write-Host "Max queue wait      : ${MaxWaitSeconds}s"
Write-Host "Poll interval       : ${PollIntervalSeconds}s"
Write-Host "Sample interval     : ${DiagnosticIntervalSeconds}s"
Write-Host "Report              : $reportRoot"
Write-Host '[OK] Docker, k6, containers and GET /login are ready.'

function Invoke-DockerSafe {
    param([string[]]$Arguments)
    $saved = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $output = & docker @Arguments 2>&1
        return [pscustomobject]@{ Code = $LASTEXITCODE; Lines = @($output | ForEach-Object { [string]$_ }) }
    } finally { $ErrorActionPreference = $saved }
}

function Get-K6Metric {
    param([object]$Metrics,[string]$MetricName,[string]$ValueName,[object]$Default = 0)
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

function Parse-FpmConfig([string]$Text) {
    $cfg = [ordered]@{ max_children=''; start_servers=''; min_spare_servers=''; max_spare_servers=''; max_requests='' }
    foreach ($line in ($Text -split "`r?`n")) {
        if ($line -match 'pm\.max_children\s*=\s*(\d+)') { $cfg.max_children = $Matches[1] }
        elseif ($line -match 'pm\.start_servers\s*=\s*(\d+)') { $cfg.start_servers = $Matches[1] }
        elseif ($line -match 'pm\.min_spare_servers\s*=\s*(\d+)') { $cfg.min_spare_servers = $Matches[1] }
        elseif ($line -match 'pm\.max_spare_servers\s*=\s*(\d+)') { $cfg.max_spare_servers = $Matches[1] }
        elseif ($line -match 'pm.max_requests\s*=\s*(\d+)') { $cfg.max_requests = $Matches[1] }
    }
    return [pscustomobject]$cfg
}

function Start-EvidenceSampler {
    param([string]$CsvPath,[int]$Interval,[string]$Php,[string]$Mysql,[string]$Nginx,[string]$DbUser,[string]$DbPassword)

    return Start-Job -ArgumentList @($CsvPath,$Interval,$Php,$Mysql,$Nginx,$DbUser,$DbPassword) -ScriptBlock {
        param($Path,$Every,$PhpContainer,$MysqlContainer,$NginxContainer,$DbUser,$DbPassword)
        $ErrorActionPreference = 'SilentlyContinue'
        New-Item -ItemType Directory -Force -Path (Split-Path -Parent $Path) | Out-Null
        $first = $true

        while ($true) {
            $ts = Get-Date -Format 'yyyy-MM-ddTHH:mm:ss.fffK'

            $stats = @{}
            $statsLines = @(& docker stats $NginxContainer $PhpContainer $MysqlContainer --no-stream --format '{{.Name}}|{{.CPUPerc}}|{{.MemUsage}}|{{.PIDs}}' 2>$null)
            foreach ($line in $statsLines) {
                $parts = ([string]$line) -split '\|', 4
                if ($parts.Count -eq 4) { $stats[$parts[0]] = $parts }
            }

            $proc = @(& docker exec $PhpContainer sh -c 'for p in /proc/[0-9]*; do if [ -r "$p/cmdline" ]; then c=$(tr "\000" " " < "$p/cmdline" 2>/dev/null); case "$c" in *php-fpm*) echo "$c";; esac; fi; done' 2>$null)
            $master = 0
            $workers = 0
            foreach ($line in $proc) {
                $text = [string]$line
                if ($text -match 'master process') { $master++ }
                elseif ($text -match 'php-fpm: pool') { $workers++ }
            }

            $fpmOutput = @(& docker exec $PhpContainer php-fpm -tt 2>$null)
            $cfgMax='';$cfgStart='';$cfgMin='';$cfgMaxSpare='';$cfgMaxReq=''
            foreach ($line in $fpmOutput) {
                $text=[string]$line
                if ($text -match 'pm\.max_children\s*=\s*(\d+)') { $cfgMax=$Matches[1] }
                elseif ($text -match 'pm\.start_servers\s*=\s*(\d+)') { $cfgStart=$Matches[1] }
                elseif ($text -match 'pm\.min_spare_servers\s*=\s*(\d+)') { $cfgMin=$Matches[1] }
                elseif ($text -match 'pm\.max_spare_servers\s*=\s*(\d+)') { $cfgMaxSpare=$Matches[1] }
                elseif ($text -match 'pm\.max_requests\s*=\s*(\d+)') { $cfgMaxReq=$Matches[1] }
            }

            $query = "SHOW GLOBAL STATUS WHERE Variable_name IN ('Threads_connected','Threads_running','Threads_created','Connections','Slow_queries','Questions','Uptime','Threads_cached','Max_used_connections');"
            $mysqlOut = @(& docker exec $MysqlContainer mysql "-u$DbUser" "-p$DbPassword" -Nse $query 2>$null)
            $mysql=@{}
            foreach ($line in $mysqlOut) {
                $parts=([string]$line)-split "`t",2
                if($parts.Count -eq 2){$mysql[$parts[0]]=$parts[1]}
            }

            $n=if($stats.ContainsKey($NginxContainer)){$stats[$NginxContainer]}else{$null}
            $p=if($stats.ContainsKey($PhpContainer)){$stats[$PhpContainer]}else{$null}
            $m=if($stats.ContainsKey($MysqlContainer)){$stats[$MysqlContainer]}else{$null}

            $row=[pscustomobject]@{
                timestamp=$ts
                nginx_cpu=if($n){$n[1]}else{''}
                nginx_memory=if($n){$n[2]}else{''}
                nginx_pids=if($n){$n[3]}else{''}
                php_cpu=if($p){$p[1]}else{''}
                php_memory=if($p){$p[2]}else{''}
                php_pids=if($p){$p[3]}else{''}
                php_fpm_master=$master
                php_fpm_workers=$workers
                php_fpm_max_children=$cfgMax
                php_fpm_start_servers=$cfgStart
                php_fpm_min_spare_servers=$cfgMin
                php_fpm_max_spare_servers=$cfgMaxSpare
                php_fpm_max_requests=$cfgMaxReq
                mysql_cpu=if($m){$m[1]}else{''}
                mysql_memory=if($m){$m[2]}else{''}
                mysql_pids=if($m){$m[3]}else{''}
                mysql_threads_connected=if($mysql.ContainsKey('Threads_connected')){$mysql['Threads_connected']}else{''}
                mysql_threads_running=if($mysql.ContainsKey('Threads_running')){$mysql['Threads_running']}else{''}
                mysql_threads_created=if($mysql.ContainsKey('Threads_created')){$mysql['Threads_created']}else{''}
                mysql_connections=if($mysql.ContainsKey('Connections')){$mysql['Connections']}else{''}
                mysql_slow_queries=if($mysql.ContainsKey('Slow_queries')){$mysql['Slow_queries']}else{''}
                mysql_questions=if($mysql.ContainsKey('Questions')){$mysql['Questions']}else{''}
                mysql_threads_cached=if($mysql.ContainsKey('Threads_cached')){$mysql['Threads_cached']}else{''}
                mysql_max_used_connections=if($mysql.ContainsKey('Max_used_connections')){$mysql['Max_used_connections']}else{''}
                mysql_uptime=if($mysql.ContainsKey('Uptime')){$mysql['Uptime']}else{''}
            }

            if($first){$row|Export-Csv -Path $Path -NoTypeInformation -Encoding UTF8;$first=$false}
            else{$row|Export-Csv -Path $Path -NoTypeInformation -Encoding UTF8 -Append}
            Start-Sleep -Seconds $Every
        }
    }
}

function Stop-EvidenceSampler([System.Management.Automation.Job]$Job) {
    if ($null -eq $Job) { return }
    try { Stop-Job -Job $Job -ErrorAction SilentlyContinue | Out-Null } catch {}
    try { Remove-Job -Job $Job -Force -ErrorAction SilentlyContinue } catch {}
}

$rows=@()
foreach($vu in $vus){
    $dir=Join-Path $reportRoot ('vu_{0:D3}' -f $vu)
    New-Item -ItemType Directory -Force -Path $dir | Out-Null
    $summary=Join-Path $dir 'summary.json'
    $console=Join-Path $dir 'k6-output.txt'
    $samples=Join-Path $dir 'bottleneck-samples.csv'
    $fpmConfigPath=Join-Path $dir 'php-fpm-config.txt'
    $slowlogPath=Join-Path $dir 'php-fpm-slowlog.txt'
    $mysqlFinalPath=Join-Path $dir 'mysql-status-final.txt'
    $nginxConfigPath=Join-Path $dir 'nginx-config.txt'

    $env:BASE_URL=$BaseUrl
    $env:TOTAL_USERS=[string]$vu
    $env:MAX_WAIT_SECONDS=[string]$MaxWaitSeconds
    $env:POLL_INTERVAL_SECONDS=[string]$PollIntervalSeconds

    Write-Host ''
    Write-Host '========================================================================'
    Write-Host "BATCH $vu USERS"
    Write-Host '========================================================================'
    Write-Host "[DIAG] Sampling every ${DiagnosticIntervalSeconds}s"

    $fpmText=Invoke-DockerSafe @('exec',$PhpContainer,'php-fpm','-tt')
    Set-Content -Path $fpmConfigPath -Value ($fpmText.Lines -join "`n") -Encoding UTF8
    $nginxText=Invoke-DockerSafe @('exec',$NginxContainer,'nginx','-T')
    Set-Content -Path $nginxConfigPath -Value ($nginxText.Lines -join "`n") -Encoding UTF8

    $sampler=$null
    try{
        $sampler=Start-EvidenceSampler -CsvPath $samples -Interval $DiagnosticIntervalSeconds -Php $PhpContainer -Mysql $MysqlContainer -Nginx $NginxContainer -DbUser $MysqlUser -DbPassword $MysqlPassword
        Write-Host "[K6] k6 run --summary-export $summary $k6Script"
        & k6 run --summary-export $summary $k6Script 2>&1 | Tee-Object -FilePath $console
        $k6Exit=$LASTEXITCODE
    }finally{Stop-EvidenceSampler -Job $sampler}

    $slowText=Invoke-DockerSafe @('exec',$PhpContainer,'sh','-c','if [ -f /tmp/e-ujian-php-fpm-slowlog-v5.log ]; then cat /tmp/e-ujian-php-fpm-slowlog-v5.log; fi')
    Set-Content -Path $slowlogPath -Value ($slowText.Lines -join "`n") -Encoding UTF8
    $finalQuery="SHOW GLOBAL STATUS WHERE Variable_name IN ('Threads_connected','Threads_running','Threads_created','Connections','Slow_queries','Questions','Uptime','Threads_cached','Max_used_connections'); SHOW FULL PROCESSLIST;"
    $mysqlFinal=Invoke-DockerSafe @('exec',$MysqlContainer,'mysql',"-u$MysqlUser","-p$MysqlPassword",'-e',$finalQuery)
    Set-Content -Path $mysqlFinalPath -Value ($mysqlFinal.Lines -join "`n") -Encoding UTF8

    $loginSuccessRate=0.0;$httpFailedRate=0.0;$queueReady=0;$queueExpired=0;$authSuccess=0;$authFailure=0;$loginP95=0.0;$loginP99=0.0;$queueP95=0.0;$authP95=0.0
    if(Test-Path $summary){
        try{
            $obj=Get-Content $summary -Raw|ConvertFrom-Json
            $metrics=if($null -ne $obj.PSObject.Properties['metrics']){$obj.metrics}else{$null}
            $loginSuccessRate=[double](Get-K6Metric $metrics 'login_success_rate' 'rate' 0)
            $httpFailedRate=[double](Get-K6Metric $metrics 'http_failed_rate' 'rate' 0)
            $queueReady=[int](Get-K6Metric $metrics 'queue_ready' 'count' 0)
            $queueExpired=[int](Get-K6Metric $metrics 'queue_expired' 'count' 0)
            $authSuccess=[int](Get-K6Metric $metrics 'auth_success' 'count' 0)
            $authFailure=[int](Get-K6Metric $metrics 'auth_failure' 'count' 0)
            $loginP95=[double](Get-K6Metric $metrics 'login_duration' 'p(95)' 0)
            $loginP99=[double](Get-K6Metric $metrics 'login_duration' 'p(99)' 0)
            $queueP95=[double](Get-K6Metric $metrics 'queue_wait_duration' 'p(95)' 0)
            $authP95=[double](Get-K6Metric $metrics 'auth_duration' 'p(95)' 0)
        }catch{Write-Warning "Could not parse k6 summary: $($_.Exception.Message)"}
    }

    $sampleRows=@()
    if(Test-Path $samples){try{$sampleRows=@(Import-Csv $samples)}catch{}}
    $phpCpuMax=0.0;$mysqlCpuMax=0.0;$nginxCpuMax=0.0;$phpWorkersMax=0;$mysqlThreadsRunningMax=0;$mysqlThreadsConnectedMax=0
    foreach($s in $sampleRows){
        $x=0.0;if([double]::TryParse((([string]$s.php_cpu).Replace('%','').Trim()),[ref]$x)-and$x-gt$phpCpuMax){$phpCpuMax=$x}
        $x=0.0;if([double]::TryParse((([string]$s.mysql_cpu).Replace('%','').Trim()),[ref]$x)-and$x-gt$mysqlCpuMax){$mysqlCpuMax=$x}
        $x=0.0;if([double]::TryParse((([string]$s.nginx_cpu).Replace('%','').Trim()),[ref]$x)-and$x-gt$nginxCpuMax){$nginxCpuMax=$x}
        $x=0;if([int]::TryParse([string]$s.php_fpm_workers,[ref]$x)-and$x-gt$phpWorkersMax){$phpWorkersMax=$x}
        $x=0;if([int]::TryParse([string]$s.mysql_threads_running,[ref]$x)-and$x-gt$mysqlThreadsRunningMax){$mysqlThreadsRunningMax=$x}
        $x=0;if([int]::TryParse([string]$s.mysql_threads_connected,[ref]$x)-and$x-gt$mysqlThreadsConnectedMax){$mysqlThreadsConnectedMax=$x}
    }

    $cfg=Parse-FpmConfig ($fpmText.Lines -join "`n")
    $configuredMax=if($cfg.max_children -match '^\d+$'){[int]$cfg.max_children}else{0}
    $workerPeakPct=if($configuredMax -gt 0 -and $phpWorkersMax -gt 0){[math]::Round(($phpWorkersMax/$configuredMax)*100,2)}else{0}

    $finding='INSUFFICIENT-WORKER-DATA'
    if($phpWorkersMax -gt 0 -and $configuredMax -gt 0 -and $phpWorkersMax -ge $configuredMax){$finding='FPM-CHILD-LIMIT-REACHED'}
    elseif($queueP95 -gt $authP95 -and $queueP95 -gt 1000){$finding='WAITING-ROOM-QUEUE-DOMINATES'}
    elseif($authP95 -gt 2000 -and $mysqlThreadsRunningMax -le 5){$finding='AUTH-APPLICATION-LATENCY-MYSQL-NOT-SATURATED'}
    elseif($phpCpuMax -ge 200 -and $mysqlThreadsRunningMax -le 5){$finding='PHP-CPU-PRESSURE-MYSQL-NOT-SATURATED'}

    $rows += [pscustomobject]@{
        vus=$vu;k6_exit_code=$k6Exit;login_success_rate=$loginSuccessRate;http_failed_rate=$httpFailedRate;queue_ready=$queueReady;queue_expired=$queueExpired;auth_success=$authSuccess;auth_failure=$authFailure;queue_p95_ms=$queueP95;auth_p95_ms=$authP95;login_p95_ms=$loginP95;login_p99_ms=$loginP99;peak_php_cpu_percent=$phpCpuMax;peak_php_fpm_workers=$phpWorkersMax;configured_php_fpm_max_children=$configuredMax;php_fpm_worker_peak_percent=$workerPeakPct;peak_mysql_cpu_percent=$mysqlCpuMax;peak_mysql_threads_running=$mysqlThreadsRunningMax;peak_mysql_threads_connected=$mysqlThreadsConnectedMax;peak_nginx_cpu_percent=$nginxCpuMax;finding=$finding;report_dir=$dir
    }

    Write-Host "[RESULT] VU=$vu exit=$k6Exit success=$([math]::Round($loginSuccessRate*100,2))% queue_p95=${queueP95}ms auth_p95=${authP95}ms login_p95=${loginP95}ms login_p99=${loginP99}ms"
    Write-Host "[RESOURCE] PHP_CPU_MAX=${phpCpuMax}% PHP_FPM_WORKERS_MAX=$phpWorkersMax/$configuredMax MYSQL_THREADS_RUNNING_MAX=$mysqlThreadsRunningMax MYSQL_THREADS_CONNECTED_MAX=$mysqlThreadsConnectedMax NGINX_CPU_MAX=${nginxCpuMax}%"
    Write-Host "[FINDING] $finding"
}

$summaryCsv=Join-Path $reportRoot 'bottleneck-verdict.csv'
$rows|Export-Csv -Path $summaryCsv -NoTypeInformation -Encoding UTF8
Write-Host ''
Write-Host '========================================================================'
Write-Host 'FOCUSED BOTTLENECK DIAGNOSTIC COMPLETE'
Write-Host '========================================================================'
Write-Host "Report : $reportRoot"
Write-Host "Verdict: $summaryCsv"
$rows|Format-Table -AutoSize
