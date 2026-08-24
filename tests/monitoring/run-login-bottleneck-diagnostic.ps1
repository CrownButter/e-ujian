[CmdletBinding()]
param(
    [string]$BaseUrl = 'http://localhost:8080',
    [string]$K6Script = './tests/load/k6/login-bottleneck-diagnostic.js',
    [string]$PhpContainer = 'e-ujian-php',
    [string]$MysqlContainer = 'e-ujian-mysql',
    [string]$NginxContainer = 'e-ujian-nginx',
    [int[]]$VuMatrix = @(10,15,20,25,30),
    [int]$DurationSeconds = 60,
    [int]$SampleIntervalSeconds = 1
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$root = (Get-Location).Path
$stamp = Get-Date -Format 'yyyy-MM-dd_HH-mm-ss_login_bottleneck'
$reportRoot = Join-Path $root "tests/load/results/$stamp"
New-Item -ItemType Directory -Path $reportRoot -Force | Out-Null

function Write-Line([string]$Text = '') { Write-Host $Text }

# Native programs can emit harmless warnings on stderr. PowerShell may surface
# those as NativeCommandError while the native process still exits with code 0.
# We therefore capture native stderr without allowing it to abort the wrapper,
# then make success/failure decisions strictly from $LASTEXITCODE.
function Invoke-Native([string]$FilePath, [string[]]$NativeArgs) {
    $previousPreference = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try {
        $output = & $FilePath @NativeArgs 2>&1
        $exitCode = $LASTEXITCODE
        return [pscustomobject]@{
            Output = @($output)
            ExitCode = $exitCode
        }
    }
    finally {
        $ErrorActionPreference = $previousPreference
    }
}

function Run-Docker([string[]]$DockerArgs, [switch]$AllowNonZero) {
    $result = Invoke-Native -FilePath 'docker' -NativeArgs $DockerArgs
    if (-not $AllowNonZero -and $result.ExitCode -ne 0) {
        $rendered = ($DockerArgs -join ' ')
        throw "Docker command failed (exit=$($result.ExitCode)): docker $rendered`n$($result.Output -join "`n")"
    }
    return $result
}

function Save-Text([string]$Path, [object[]]$Lines) {
    ($Lines | ForEach-Object { [string]$_ }) | Set-Content -Path $Path -Encoding UTF8
}

function Sample-DockerStats([string]$Path) {
    $result = Run-Docker @('stats','--no-stream','--format','{{.Name}}|{{.CPUPerc}}|{{.MemUsage}}|{{.MemPerc}}|{{.NetIO}}|{{.BlockIO}}|{{.PIDs}}')
    $ts = (Get-Date).ToString('o')
    foreach ($line in $result.Output) {
        if ([string]::IsNullOrWhiteSpace([string]$line)) { continue }
        Add-Content -Path $Path -Value ($ts + '|' + [string]$line) -Encoding UTF8
    }
}

function Sample-PhpFpm([string]$Path) {
    $ts = (Get-Date).ToString('o')
    $config = Run-Docker @('exec',$PhpContainer,'sh','-c','grep -R "^[[:space:]]*pm\.[a-z_]*[[:space:]]*=" /usr/local/etc/php-fpm.d /usr/local/etc/php-fpm.conf 2>/dev/null || true')
    foreach ($line in $config.Output) {
        if (-not [string]::IsNullOrWhiteSpace([string]$line)) {
            Add-Content $Path "$ts|CONFIG|$line" -Encoding UTF8
        }
    }

    $proc = Run-Docker @('exec',$PhpContainer,'sh','-c','ps -eo pid,stat,cmd 2>/dev/null | grep "[p]hp-fpm" || true')
    $count = @($proc.Output | Where-Object { -not [string]::IsNullOrWhiteSpace([string]$_) }).Count
    Add-Content $Path "$ts|PROCESS_COUNT|$count" -Encoding UTF8
    foreach ($line in $proc.Output) {
        if (-not [string]::IsNullOrWhiteSpace([string]$line)) {
            Add-Content $Path "$ts|PROCESS|$line" -Encoding UTF8
        }
    }
}

function Sample-MySql([string]$Path) {
    $ts = (Get-Date).ToString('o')
    $sql = "SHOW GLOBAL STATUS WHERE Variable_name IN ('Threads_connected','Threads_running','Threads_created','Threads_cached','Connections','Max_used_connections','Slow_queries','Queries','Questions');"
    $result = Run-Docker @('exec',$MysqlContainer,'mysql','-uroot','-plocal_root_password','euji_ujian_db','-Nse',$sql)
    foreach ($line in $result.Output) {
        if (-not [string]::IsNullOrWhiteSpace([string]$line)) {
            Add-Content $Path "$ts|STATUS|$line" -Encoding UTF8
        }
    }

    $process = Run-Docker @('exec',$MysqlContainer,'mysql','-uroot','-plocal_root_password','euji_ujian_db','-e','SHOW PROCESSLIST;')
    Add-Content $Path "$ts|PROCESSLIST_BEGIN" -Encoding UTF8
    foreach ($line in $process.Output) {
        if (-not [string]::IsNullOrWhiteSpace([string]$line)) {
            Add-Content $Path "$ts|PROCESSLIST|$line" -Encoding UTF8
        }
    }
}

function Sample-Nginx([string]$Path) {
    $ts = (Get-Date).ToString('o')
    try {
        $r = Invoke-WebRequest -Uri ($BaseUrl.TrimEnd('/') + '/nginx_status') -UseBasicParsing -TimeoutSec 2
        Add-Content $Path "$ts|STATUS|HTTP $($r.StatusCode)|$($r.Content -replace "`r|`n",' ')" -Encoding UTF8
    }
    catch {
        Add-Content $Path "$ts|STATUS|UNAVAILABLE|$($_.Exception.Message)" -Encoding UTF8
    }

    $proc = Run-Docker @('exec',$NginxContainer,'sh','-c','ps -eo pid,stat,cmd 2>/dev/null | grep "[n]ginx" || true')
    foreach ($line in $proc.Output) {
        if (-not [string]::IsNullOrWhiteSpace([string]$line)) {
            Add-Content $Path "$ts|PROCESS|$line" -Encoding UTF8
        }
    }
}

function Sample-AuthTiming([string]$Path) {
    $result = Run-Docker @('exec',$PhpContainer,'sh','-c','if [ -f /var/www/html/writable/logs/auth-timing.csv ]; then tail -n 200 /var/www/html/writable/logs/auth-timing.csv; fi')
    $ts = (Get-Date).ToString('o')
    foreach ($line in $result.Output) {
        if (-not [string]::IsNullOrWhiteSpace([string]$line)) {
            Add-Content $Path "$ts|$line" -Encoding UTF8
        }
    }
}

function Get-Metric([object]$Values, [string]$Name) {
    if ($null -eq $Values) { return 0.0 }
    $property = $Values.PSObject.Properties[$Name]
    if ($null -eq $property) { return 0.0 }
    try { return [double]$property.Value } catch { return 0.0 }
}

Write-Line '========================================================================'
Write-Line 'E-UJIAN LOGIN BOTTLENECK DIAGNOSTIC V9.2'
Write-Line '========================================================================'
Write-Line "BASE_URL          : $BaseUrl"
Write-Line "VUS matrix        : $($VuMatrix -join ', ')"
Write-Line "Duration / batch  : ${DurationSeconds}s"
Write-Line "Sample interval   : ${SampleIntervalSeconds}s"
Write-Line "Report            : $reportRoot"
Write-Line ''

Write-Line '[CHECK] Docker CLI'
$dockerVersion = Invoke-Native -FilePath 'docker' -NativeArgs @('version','--format','{{.Client.Version}}|{{.Server.Version}}')
if ($dockerVersion.ExitCode -ne 0) {
    throw "Docker CLI/daemon tidak siap.`n$($dockerVersion.Output -join "`n")"
}
Write-Line "[OK] Docker: $($dockerVersion.Output -join ' ')"

Write-Line '[CHECK] Docker containers'
$containerResult = Run-Docker @('ps','--format','{{.Names}}|{{.Status}}|{{.Ports}}')
$containerStatus = $containerResult.Output
Save-Text (Join-Path $reportRoot 'docker-containers.txt') $containerStatus
$required = @($PhpContainer,$MysqlContainer,$NginxContainer)
foreach ($name in $required) {
    if (-not (@($containerStatus) -match "^$([regex]::Escape($name))\|")) {
        throw "Container tidak ditemukan/running: $name"
    }
}
Write-Line '[OK] Required containers running.'

try {
    $loginCheck = Invoke-WebRequest -Uri ($BaseUrl.TrimEnd('/') + '/login') -UseBasicParsing -TimeoutSec 10
    Write-Line "[OK] GET /login HTTP $($loginCheck.StatusCode)"
}
catch {
    throw "GET /login gagal: $($_.Exception.Message)"
}

$preflight = Join-Path $reportRoot 'preflight'
New-Item -ItemType Directory -Path $preflight -Force | Out-Null
Sample-DockerStats (Join-Path $preflight 'docker-stats.txt')
Sample-PhpFpm (Join-Path $preflight 'php-fpm.txt')
Sample-MySql (Join-Path $preflight 'mysql.txt')
Sample-Nginx (Join-Path $preflight 'nginx.txt')

$results = @()

foreach ($vus in $VuMatrix) {
    $dir = Join-Path $reportRoot ('vu_{0:D3}' -f $vus)
    New-Item -ItemType Directory -Path $dir -Force | Out-Null
    $dockerFile = Join-Path $dir 'docker-stats.txt'
    $phpFile = Join-Path $dir 'php-fpm.txt'
    $mysqlFile = Join-Path $dir 'mysql.txt'
    $nginxFile = Join-Path $dir 'nginx.txt'
    $authFile = Join-Path $dir 'auth-timing.txt'
    $k6Out = Join-Path $dir 'k6-output.txt'
    $summary = Join-Path $dir 'summary.json'

    Write-Line ''
    Write-Line '========================================================================'
    Write-Line "BATCH $vus VU / ${DurationSeconds}s"
    Write-Line '========================================================================'

    $env:BASE_URL = $BaseUrl
    $env:VUS = [string]$vus
    $env:DURATION = "${DurationSeconds}s"
    $env:K6_SUMMARY_FILE = $summary

    $k6Args = @('run','--summary-export',$summary,$K6Script)
    Write-Line ('[K6] k6 ' + ($k6Args -join ' '))

    $psi = New-Object System.Diagnostics.ProcessStartInfo
    $psi.FileName = 'k6'
    foreach ($arg in $k6Args) { [void]$psi.ArgumentList.Add($arg) }
    $psi.UseShellExecute = $false
    $psi.RedirectStandardOutput = $true
    $psi.RedirectStandardError = $true
    $psi.CreateNoWindow = $true
    $p = New-Object System.Diagnostics.Process
    $p.StartInfo = $psi
    [void]$p.Start()

    while (-not $p.HasExited) {
        Sample-DockerStats $dockerFile
        Sample-PhpFpm $phpFile
        Sample-MySql $mysqlFile
        Sample-Nginx $nginxFile
        Sample-AuthTiming $authFile
        Start-Sleep -Seconds $SampleIntervalSeconds
    }
    $stdout = $p.StandardOutput.ReadToEnd()
    $stderr = $p.StandardError.ReadToEnd()
    $p.WaitForExit()
    Save-Text $k6Out (@($stdout,$stderr))

    $exitCode = $p.ExitCode
    $summaryObj = $null
    if (Test-Path $summary) {
        try { $summaryObj = Get-Content $summary -Raw | ConvertFrom-Json } catch { $summaryObj = $null }
    }

    $loginSuccessRate = 0.0
    $httpFailedRate = 0.0
    $loginP95 = 0.0
    $loginP99 = 0.0
    $authP95 = 0.0

    if ($summaryObj -and $summaryObj.result) {
        $loginSuccessRate = Get-Metric $summaryObj.result 'login_success_rate'
        $httpFailedRate = Get-Metric $summaryObj.result 'http_failed_rate'
        $loginP95 = Get-Metric $summaryObj.result 'login_p95_ms'
        $loginP99 = Get-Metric $summaryObj.result 'login_p99_ms'
        $authP95 = Get-Metric $summaryObj.result 'auth_p95_ms'
    }

    $results += [pscustomobject]@{
        vus = $vus
        duration_seconds = $DurationSeconds
        k6_exit_code = $exitCode
        login_success_rate = $loginSuccessRate
        http_failed_rate = $httpFailedRate
        login_p95_ms = $loginP95
        login_p99_ms = $loginP99
        auth_p95_ms = $authP95
        report_dir = $dir
    }

    Write-Line "[RESULT] VU=$vus exit=$exitCode login_p95=${loginP95}ms login_p99=${loginP99}ms auth_p95=${authP95}ms http_failed=$httpFailedRate"
}

$csv = Join-Path $reportRoot 'bottleneck-summary.csv'
$results | Export-Csv -Path $csv -NoTypeInformation -Encoding UTF8

$md = Join-Path $reportRoot 'bottleneck-report.md'
$lines = @()
$lines += '# E-UJIAN Login Bottleneck Diagnostic V9.2'
$lines += ''
$lines += "Generated: $(Get-Date -Format o)"
$lines += ''
$lines += '## Test matrix'
$lines += ''
$lines += '| VU | Login success | HTTP failed | Login p95 (ms) | Login p99 (ms) | Auth p95 (ms) | Exit |'
$lines += '|---:|---:|---:|---:|---:|---:|---:|'
foreach ($x in $results) {
    $lines += ('| {0} | {1:P2} | {2:P2} | {3:N2} | {4:N2} | {5:N2} | {6} |' -f $x.vus,$x.login_success_rate,$x.http_failed_rate,$x.login_p95_ms,$x.login_p99_ms,$x.auth_p95_ms,$x.k6_exit_code)
}
$lines += ''
$lines += '## Interpretation guide'
$lines += ''
$lines += '- Native stderr warnings from Docker/MySQL are captured but do not abort the wrapper when the native process exits with code 0.'
$lines += '- PHP-FPM: inspect `php-fpm.txt` for worker count and `pm.*` configuration.'
$lines += '- MySQL: inspect `mysql.txt` for Threads_connected, Threads_running, Max_used_connections, Slow_queries and process list.'
$lines += '- Nginx: inspect `nginx.txt`; unavailable status is recorded rather than inferred.'
$lines += '- Docker: inspect `docker-stats.txt` for CPU, memory, network I/O, block I/O and PID growth.'
$lines += '- Application: `auth-timing.txt` contains the existing Auth.php timing fields.'
$lines += ''
$lines += '## Conclusion'
$lines += ''
$lines += 'No application or infrastructure tuning is performed by this diagnostic. Use the correlated samples to identify the first saturated layer before making another optimization.'
$lines | Set-Content -Path $md -Encoding UTF8

Write-Line ''
Write-Line '========================================================================'
Write-Line 'DIAGNOSTIC COMPLETE'
Write-Line '========================================================================'
Write-Line "Report : $reportRoot"
Write-Line "Summary: $csv"
$results | Format-Table -AutoSize
