$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$Root = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
Set-Location $Root
$Out = Join-Path $Root 'qa_output\02_security_local'
New-Item -ItemType Directory -Force -Path $Out | Out-Null

Write-Host 'BM3 - CSRF không có token (kỳ vọng HTTP 419)' -ForegroundColor Cyan
$csrfFile = Join-Path $Out 'BM3_csrf.txt'
& curl.exe -i -s -X POST 'http://localhost:8000/tickets' -d 'title=x' 2>&1 | Tee-Object -FilePath $csrfFile

Write-Host "`nBM6 - Kiểm tra prefix bcrypt trong DB" -ForegroundColor Cyan
$hashFile = Join-Path $Out 'BM6_password_hash.txt'
& docker compose exec -T mysql sh -lc 'mysql -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" "$MYSQL_DATABASE" -e "SELECT email, LEFT(password,7) AS hash_prefix FROM users;"' 2>&1 | Tee-Object -FilePath $hashFile
if ($LASTEXITCODE -ne 0) { Write-Warning "BM6 chưa chạy được; xem $hashFile" }

Write-Host "`nBM11 - .env không được Git track" -ForegroundColor Cyan
$secretFile = Join-Path $Out 'BM11_env_gitignore.txt'
"git ls-files .env (kỳ vọng không có output):" | Set-Content -Encoding UTF8 $secretFile
& git ls-files .env 2>&1 | Tee-Object -FilePath $secretFile -Append
"`ngit check-ignore .env (kỳ vọng .env):" | Add-Content -Encoding UTF8 $secretFile
& git check-ignore .env 2>&1 | Tee-Object -FilePath $secretFile -Append

Write-Host "`nBM12-local - Security headers trên localhost (HSTS chỉ kiểm tra được khi HTTPS)" -ForegroundColor Cyan
$headersFile = Join-Path $Out 'BM12_local_headers.txt'
& curl.exe -sI 'http://localhost:8000/login' 2>&1 | Tee-Object -FilePath $headersFile

Write-Host "`nAPI smoke + TC-TREE descendants" -ForegroundColor Cyan
$apiFile = Join-Path $Out 'TC_TREE_api_descendants.txt'
try {
    $loginBody = @{ email = 'admin@example.com'; password = 'Password123!' } | ConvertTo-Json
    $login = Invoke-RestMethod -Method Post -Uri 'http://localhost:8000/api/v1/login' -ContentType 'application/json' -Body $loginBody
    $token = $login.data.token
    $headers = @{ Authorization = "Bearer $token" }
    $assets = Invoke-RestMethod -Method Get -Uri 'http://localhost:8000/api/v1/assets?q=SRV001' -Headers $headers
    $server = @($assets.data) | Where-Object { $_.asset_code -eq 'SRV001' } | Select-Object -First 1
    if (-not $server) { throw 'Không tìm thấy SRV001. Hãy chạy migrate:fresh --seed.' }
    $tree = Invoke-RestMethod -Method Get -Uri ("http://localhost:8000/api/v1/assets/{0}/descendants" -f $server.id) -Headers $headers
    $tree | ConvertTo-Json -Depth 10 | Set-Content -Encoding UTF8 $apiFile
    Get-Content $apiFile
} catch {
    $_ | Out-String | Set-Content -Encoding UTF8 $apiFile
    Write-Warning "TC-TREE API chưa chạy được. Xem $apiFile"
}

Write-Host "`nHOÀN TẤT. Log nằm tại: $Out" -ForegroundColor Green
