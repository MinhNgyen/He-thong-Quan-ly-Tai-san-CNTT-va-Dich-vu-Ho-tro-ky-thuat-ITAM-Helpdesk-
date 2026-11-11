$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $Root
$Out = Join-Path $Root 'qa_output\01_tests'
New-Item -ItemType Directory -Force -Path $Out | Out-Null

function Run-TestGroup {
    param([string]$Name, [string[]]$Files)
    $log = Join-Path $Out ($Name + '.txt')
    Write-Host "`n=== $Name ===" -ForegroundColor Cyan
    $args = @('compose','exec','app','php','artisan','test') + $Files
    & docker @args 2>&1 | Tee-Object -FilePath $log
    if ($LASTEXITCODE -ne 0) {
        throw "Nhóm $Name thất bại. Xem $log"
    }
}

Write-Host 'Kiểm tra Docker...' -ForegroundColor Yellow
& docker compose ps
if ($LASTEXITCODE -ne 0) { throw 'Docker Compose chưa sẵn sàng.' }

Run-TestGroup 'step06_asset_lifecycle' @('tests/Feature/AssetLifecycleTest.php')
Run-TestGroup 'step09_report' @('tests/Feature/ReportTest.php')
Run-TestGroup 'step10_attachment' @('tests/Feature/AttachmentTest.php')
Run-TestGroup 'step11_user_admin' @('tests/Feature/UserAdminTest.php')
Run-TestGroup 'step12_password' @('tests/Feature/PasswordTest.php')
Run-TestGroup 'step13_security' @('tests/Feature/SecurityTest.php')
Run-TestGroup 'step16_api' @('tests/Feature/AssetApiTest.php','tests/Feature/TicketApiTest.php')

Write-Host "`nHOÀN TẤT. Log nằm tại: $Out" -ForegroundColor Green
Write-Host 'Bạn có thể mở từng file .txt và chụp phần PASS + Tests/Assertions.' -ForegroundColor Green
