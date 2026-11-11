$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

& (Join-Path $PSScriptRoot '01_run_targeted_tests.ps1')
& (Join-Path $PSScriptRoot '02_collect_local_security.ps1')

Write-Host "`nĐã chạy xong phần an toàn, không rollback migration." -ForegroundColor Green
Write-Host "Nếu muốn đo hiệu năng, chạy riêng: .\qa\03_run_performance.ps1" -ForegroundColor Yellow
Write-Host "Nếu muốn chạy Newman, chạy riêng: .\qa\04_run_api_newman.ps1" -ForegroundColor Yellow
