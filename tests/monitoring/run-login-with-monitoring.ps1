$ErrorActionPreference = 'Stop'

$composeFile = Join-Path $PSScriptRoot 'docker-compose.monitoring.yml'

Write-Host 'Starting Prometheus, cAdvisor and Grafana...' -ForegroundColor Cyan
docker compose -f $composeFile up -d

Write-Host ''
Write-Host 'Grafana : http://localhost:3000' -ForegroundColor Green
Write-Host 'Login   : admin / admin' -ForegroundColor Yellow
Write-Host 'Prometheus: http://localhost:9090' -ForegroundColor Green
Write-Host ''

$env:BASE_URL = if ($env:BASE_URL) { $env:BASE_URL } else { 'http://localhost:8080' }
$env:K6_PROMETHEUS_RW_SERVER_URL = 'http://localhost:9090/api/v1/write'
$env:K6_PROMETHEUS_RW_TREND_STATS = 'avg,min,med,p(90),p(95),p(99),max'

Write-Host "Running 709 concurrent logins against $env:BASE_URL ..." -ForegroundColor Cyan

k6 run --out experimental-prometheus-rw (Join-Path $PSScriptRoot '..\load\k6\login-001-709.js')

Write-Host ''
Write-Host 'Test finished. Grafana remains available at http://localhost:3000' -ForegroundColor Green
