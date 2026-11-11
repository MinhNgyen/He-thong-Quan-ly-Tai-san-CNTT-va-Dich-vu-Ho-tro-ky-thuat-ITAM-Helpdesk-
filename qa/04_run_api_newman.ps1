$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $Root
$Out = Join-Path $Root 'qa_output\04_api'
New-Item -ItemType Directory -Force -Path $Out | Out-Null
$log = Join-Path $Out 'newman.txt'

Write-Host 'Chạy Postman collection bằng Newman...' -ForegroundColor Cyan
& npx newman run 'docs/ITAM-Helpdesk.postman_collection.json' --env-var 'baseUrl=http://localhost:8000/api/v1' 2>&1 | Tee-Object -FilePath $log
if ($LASTEXITCODE -ne 0) {
    throw "Newman thất bại. Xem $log. Nếu máy chưa có Newman, npx sẽ hỏi cài package."
}
Write-Host "HOÀN TẤT. Log: $log" -ForegroundColor Green
