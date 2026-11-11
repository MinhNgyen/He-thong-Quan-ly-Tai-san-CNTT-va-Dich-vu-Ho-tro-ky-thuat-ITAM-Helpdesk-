$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $Root
$Out = Join-Path $Root 'qa_output\03_performance'
New-Item -ItemType Directory -Force -Path $Out | Out-Null

function Run-Logged {
    param([string]$Name, [scriptblock]$Block)
    $log = Join-Path $Out ($Name + '.txt')
    Write-Host "`n=== $Name ===" -ForegroundColor Cyan
    & $Block 2>&1 | Tee-Object -FilePath $log
    if ($LASTEXITCODE -ne 0) { throw "$Name thất bại. Xem $log" }
}

Write-Host 'Script này nạp dữ liệu benchmark và tạm rollback migration index cuối cùng; cuối script sẽ migrate lại.' -ForegroundColor Yellow

Run-Logged '01_bulk_seed' { docker compose exec -e BULK_ROWS=20000 app php artisan db:seed --class=BulkDemoSeeder }
Run-Logged '02_after_index' { docker compose exec app php artisan perf:benchmark --runs=30 --cold --markdown }

$rolledBack = $false
try {
    Run-Logged '03_rollback_index' { docker compose exec app php artisan migrate:rollback --step=1 }
    $rolledBack = $true
    Run-Logged '04_before_index' { docker compose exec app php artisan perf:benchmark --runs=30 --cold --markdown }

    $beforeExplain = Join-Path $Out '05_explain_before.txt'
    Write-Host "`n=== EXPLAIN trước index ===" -ForegroundColor Cyan
    Get-Content '.\docs\perf\explain.sql' | docker compose exec -T mysql sh -lc 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -t' 2>&1 | Tee-Object -FilePath $beforeExplain
    if ($LASTEXITCODE -ne 0) { throw 'EXPLAIN trước index thất bại.' }
}
finally {
    if ($rolledBack) {
        Write-Host "`nKhôi phục migration/index..." -ForegroundColor Yellow
        docker compose exec app php artisan migrate --force
        if ($LASTEXITCODE -ne 0) { Write-Error 'KHÔNG khôi phục được migration. Hãy chạy docker compose exec app php artisan migrate ngay.' }
    }
}

$afterExplain = Join-Path $Out '06_explain_after.txt'
Write-Host "`n=== EXPLAIN sau index ===" -ForegroundColor Cyan
Get-Content '.\docs\perf\explain.sql' | docker compose exec -T mysql sh -lc 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -t' 2>&1 | Tee-Object -FilePath $afterExplain

Write-Host "`nHOÀN TẤT. Log nằm tại: $Out" -ForegroundColor Green
