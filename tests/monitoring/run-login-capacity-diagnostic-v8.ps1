[CmdletBinding()]
param(
    [int[]]$VusList = @(10, 20, 25, 30),
    [int]$DurationSeconds = 60,
    [string]$BaseUrl = 'http://localhost:8080',
    [string]$PhpContainer = 'e-ujian-php',
    [string]$MysqlContainer = 'e-ujian-mysql',
    [string]$MysqlDatabase = 'euji_ujian_db',
    [string]$MysqlUser = 'root',
    [string]$MysqlPassword = 'local_root_password',
    [string]$K6Script = './tests/load/k6/login-capacity-diagnostic.js',
    [int]$SampleIntervalSeconds = 1
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$ProjectRoot = (Get-Location).Path
$Timestamp = Get-Date -Format 'yyyy-MM-dd_HH-mm-ss'
$ReportRoot = Join-Path $ProjectRoot "tests/load/results/${Timestamp}_capacity_diagnostic_v8"
New-Item -ItemType Directory -Path $ReportRoot -Force | Out-Null

function Write-Section([string]$Text) {
    Write-Host ''
    Write-Host ('=' * 72)
    Write-Host $Text
    Write-Host ('=' * 72)
}

function Test-Command([string]$Name) {
    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "Command '$Name' tidak ditemukan di PATH."
    }
}

function Get-AuthTimingLineCount {
    $result = & docker exec $PhpContainer sh -c "if [ -f /var/www/html/writable/logs/auth-timing.csv ]; then wc -l < /var/www/html/writable/logs/auth-timing.csv; else echo 0; fi" 2>&1
    if ($LASTEXITCODE -ne 0) { return 0 }
    $n = 0
    if ([int]::TryParse(($result | Select-Object -Last 1).ToString().Trim(), [ref]$n)) { return $n }
    return 0
}

function Get-AuthTimingDelta([int]$StartLine, [string]$OutputFile) {
    $start = $StartLine + 1
    $content = & docker exec $PhpContainer sh -c "if [ -f /var/www/html/writable/logs/auth-timing.csv ]; then tail -n +$start /var/www/html/writable/logs/auth-timing.csv; fi" 2>&1
    if ($LASTEXITCODE -eq 0) {
        $content | Set-Content -Path $OutputFile -Encoding utf8
    } else {
        'AUTH_TIMING_UNAVAILABLE' | Set-Content -Path $OutputFile -Encoding utf8
    }
}

function Start-ResourceMonitor([string]$OutputFile, [int]$Seconds, [int]$Interval) {
    $job = Start-Job -ArgumentList $OutputFile,$Seconds,$Interval,$PhpContainer,$MysqlContainer,$MysqlUser,$MysqlPassword,$MysqlDatabase -ScriptBlock {
        param($File,$Duration,$Interval,$Php,$Mysql,$User,$Password,$Database)
        $ErrorActionPreference = 'Continue'
        'timestamp,type,name,data' | Set-Content -Path $File -Encoding utf8
        $end = (Get-Date).AddSeconds($Duration + 3)

        while ((Get-Date) -lt $end) {
            $ts = (Get-Date).ToString('o')

            $stats = & docker stats $Php $Mysql --no-stream --format '{{.Name}}|{{.CPUPerc}}|{{.MemUsage}}|{{.MemPerc}}|{{.NetIO}}|{{.BlockIO}}|{{.PIDs}}' 2>$null
            foreach ($line in $stats) {
                if ([string]::IsNullOrWhiteSpace($line)) { continue }
                $p = $line -split '\|', 7
                if ($p.Count -eq 7) {
                    $data = "cpu=$($p[1]);memory=$($p[2]);memory_percent=$($p[3]);net=$($p[4]);block_io=$($p[5]);pids=$($p[6])"
                    Add-Content -Path $File -Value "$ts,DOCKER,$($p[0]),$($data.Replace(',',' '))" -Encoding utf8
                }
            }

            $phpPs = & docker exec $Php sh -c "ps -eo stat=,pcpu=,pmem=,comm= | grep php-fpm | grep -v grep" 2>$null
            $phpCount = @($phpPs).Count
            Add-Content -Path $File -Value "$ts,PHP_FPM,processes,process_lines=$phpCount;details=$((($phpPs -join ' ') -replace ',',' '))" -Encoding utf8

            $mysqlSql = "SHOW GLOBAL STATUS WHERE Variable_name IN ('Threads_connected','Threads_running','Threads_created','Threads_cached','Max_used_connections','Connections','Queries','Slow_queries');"
            $mysqlOut = & docker exec $Mysql mysql -u$User -p$Password -Nse $mysqlSql 2>$null
            $pairs = @()
            foreach ($row in $mysqlOut) {
                $parts = $row -split '\t', 2
                if ($parts.Count -eq 2) { $pairs += "$($parts[0])=$($parts[1])" }
            }
            if ($pairs.Count -gt 0) {
                Add-Content -Path $File -Value "$ts,MYSQL_STATUS,status,$(($pairs -join ';').Replace(',',' '))" -Encoding utf8
            }

            Start-Sleep -Seconds ([Math]::Max(1,$Interval))
        }
    }
    return $job
}

function Stop-ResourceMonitor($Job) {
    if ($null -eq $Job) { return }
    Stop-Job -Job $Job -ErrorAction SilentlyContinue | Out-Null
    Receive-Job -Job $Job -ErrorAction SilentlyContinue | Out-Null
    Remove-Job -Job $Job -Force -ErrorAction SilentlyContinue
}

function Get-JsonNumber($Object, [string]$Path, $Default = $null) {
    if ($null -eq $Object) { return $Default }
    $current = $Object
    foreach ($part in ($Path -split '\.')) {
        if ($null -eq $current) { return $Default }
        $property = $current.PSObject.Properties[$part]
        if ($null -eq $property) { return $Default }
        $current = $property.Value
    }
    if ($null -eq $current) { return $Default }
    try { return [double]$current } catch { return $Default }
}

Test-Command 'docker'
Test-Command 'k6'

if (-not (Test-Path $K6Script)) {
    throw "K6 script tidak ditemukan: $K6Script"
}

Write-Section 'E-UJIAN LOGIN CAPACITY ROOT-CAUSE DIAGNOSTIC V8'
Write-Host "BASE_URL          : $BaseUrl"
Write-Host "PHP container     : $PhpContainer"
Write-Host "MySQL container   : $MysqlContainer"
Write-Host "VUS matrix        : $($VusList -join ', ')"
Write-Host "Duration / batch  : ${DurationSeconds}s"
Write-Host "Sample interval   : ${SampleIntervalSeconds}s"
Write-Host "Report             : $ReportRoot"

$summaryRows = @()

foreach ($vus in $VusList) {
    if ($vus -lt 1 -or $vus -gt 709) { throw "VUS $vus di luar range 1-709." }

    $batch = Join-Path $ReportRoot ("vu_{0:D3}" -f $vus)
    New-Item -ItemType Directory -Path $batch -Force | Out-Null

    Write-Section "BATCH $vus VU / ${DurationSeconds}s"

    $authStartLine = Get-AuthTimingLineCount
    $monitorFile = Join-Path $batch 'resource-samples.csv'
    $authDeltaFile = Join-Path $batch 'auth-timing-delta.csv'
    $k6OutputFile = Join-Path $batch 'k6-output.txt'
    $k6SummaryFile = Join-Path $batch 'summary.json'
    $k6RawSummaryFile = Join-Path $batch 'k6-summary-export.json'
    $hostSnapshot = Join-Path $batch 'docker-info.txt'

    @(
        "timestamp=$(Get-Date -Format o)"
        "vus=$vus"
        "duration_seconds=$DurationSeconds"
        "base_url=$BaseUrl"
        (& docker version 2>&1 | Select-Object -First 20)
        (& docker ps --format '{{.Names}}|{{.Status}}' 2>&1)
    ) | Set-Content -Path $hostSnapshot -Encoding utf8

    $monitorJob = Start-ResourceMonitor -OutputFile $monitorFile -Seconds ($DurationSeconds + 5) -Interval $SampleIntervalSeconds
    Start-Sleep -Seconds 2

    $env:BASE_URL = $BaseUrl
    $env:VUS = [string]$vus
    $env:DURATION = "${DurationSeconds}s"
    $env:K6_SUMMARY_FILE = $k6SummaryFile

    $k6Args = @('run','--summary-export',$k6RawSummaryFile,$K6Script)
    Write-Host "[K6] k6 $($k6Args -join ' ')"

    $k6Lines = & k6 @k6Args 2>&1
    $k6Exit = $LASTEXITCODE
    $k6Lines | Tee-Object -FilePath $k6OutputFile | Out-Host

    Stop-ResourceMonitor $monitorJob
    Get-AuthTimingDelta -StartLine $authStartLine -OutputFile $authDeltaFile

    $summary = $null
    if (Test-Path $k6SummaryFile) {
        try { $summary = Get-Content $k6SummaryFile -Raw | ConvertFrom-Json } catch { $summary = $null }
    }

    $httpFailed = Get-JsonNumber $summary 'result.http_failed'
    $loginP95 = Get-JsonNumber $summary 'metrics.login_duration.values.p(95)'
    $loginP99 = Get-JsonNumber $summary 'metrics.login_duration.values.p(99)'
    $authP95 = Get-JsonNumber $summary 'metrics.auth_duration.values.p(95)'
    $loginSuccess = Get-JsonNumber $summary 'result.login_success_rate'

    $summaryRows += [pscustomobject]@{
        vus = $vus
        duration_seconds = $DurationSeconds
        k6_exit_code = $k6Exit
        login_success_rate = $loginSuccess
        http_failed_rate = $httpFailed
        login_p95_ms = $loginP95
        login_p99_ms = $loginP99
        auth_p95_ms = $authP95
        report_dir = $batch
    }

    Write-Host "[RESULT] VU=$vus exit=$k6Exit login_p95=${loginP95}ms login_p99=${loginP99}ms auth_p95=${authP95}ms http_failed=$httpFailed"
    Write-Host "[DATA] $batch"
}

$summaryCsv = Join-Path $ReportRoot 'root-cause-summary.csv'
$summaryRows | Export-Csv -Path $summaryCsv -NoTypeInformation -Encoding utf8

@(
    'E-UJIAN LOGIN ROOT-CAUSE DIAGNOSTIC V8'
    "Generated: $(Get-Date -Format o)"
    "Base URL: $BaseUrl"
    "VUS: $($VusList -join ', ')"
    "Duration: ${DurationSeconds}s per batch"
    ''
    'Collected per batch:'
    '- K6 custom summary.json and raw k6-summary-export.json'
    '- K6 raw console output'
    '- Docker CPU / memory / network / block I/O / PIDs'
    '- PHP-FPM process snapshot'
    '- MySQL Threads_connected / Threads_running / connections / queries / slow queries'
    '- CodeIgniter auth-timing.csv delta'
    ''
    'The wrapper intentionally keeps the K6 handleSummary JSON separate from --summary-export JSON.'
    'This avoids the two K6 output mechanisms overwriting the same file.'
    'Use auth-timing.csv together with resource-samples.csv and K6 metrics to identify whether latency is dominated by PHP-FPM, MySQL, bcrypt, unit queries, session handling, or request/network overhead.'
) | Set-Content -Path (Join-Path $ReportRoot 'README.txt') -Encoding utf8

Remove-Item Env:BASE_URL -ErrorAction SilentlyContinue
Remove-Item Env:VUS -ErrorAction SilentlyContinue
Remove-Item Env:DURATION -ErrorAction SilentlyContinue
Remove-Item Env:K6_SUMMARY_FILE -ErrorAction SilentlyContinue

Write-Section 'DIAGNOSTIC COMPLETE'
Write-Host "Report : $ReportRoot"
Write-Host "Summary: $summaryCsv"
$summaryRows | Format-Table -AutoSize
Write-Host ''
Write-Host 'Wrapper selesai. K6 threshold tidak digunakan untuk menghentikan matrix; exit code per batch tetap dicatat.'
