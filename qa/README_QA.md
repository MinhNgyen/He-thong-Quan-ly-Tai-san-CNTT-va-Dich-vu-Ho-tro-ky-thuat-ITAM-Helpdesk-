# Bộ hỗ trợ kiểm thử Bước 6 → cuối

Chạy trong **PowerShell tại thư mục gốc project** sau khi Docker đã bật:

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\qa\RUN_ALL_SAFE.ps1
```

Script sẽ chạy tự động các nhóm test cho cây/vòng đời tài sản, báo cáo, attachment, quản trị user, password, security và API; đồng thời thu thập một số bằng chứng local cho BM3, BM6, BM11, BM12-local và API descendants. Log nằm trong `qa_output/`.

## Hiệu năng

Chỉ chạy khi bạn sẵn sàng đo benchmark:

```powershell
.\qa\03_run_performance.ps1
```

Script nạp 20.000 dòng giả lập, đo sau index, rollback migration index cuối cùng, đo trước index, chạy EXPLAIN, rồi **tự migrate lại trong `finally`**.

## Newman

```powershell
.\qa\04_run_api_newman.ps1
```

Collection hiện có 21 request và 9 `pm.test`.

## Việc vẫn phải làm bằng giao diện

- Bước 6: chụp cây `SRV001` và thử tạo vòng lặp cha-con bị chặn.
- Bước 7: bàn giao → thử bàn giao lần hai → thu hồi → bảo trì → thử lần hai → hoàn thành.
- TC-34: HTTPS/2 thiết bị (chỉ khi đã deploy).
- TC-36: responsive width 360 px.
- Báo cáo CSV: mở CSV bằng Excel để chụp tiếng Việt và số liệu.
- Attachment: upload file hợp lệ / >5 MB / file giả ảnh và kiểm tra quyền bằng 2 Employee nếu có.
- BM2 XSS, BM4 IDOR, BM5 web-role, BM7 cookie, BM9 production error, BM10 lockout UI/audit, BM12 HTTPS/HSTS.
- Lighthouse, axe, keyboard, contrast, mobile, Swagger Editor và ảnh Postman GUI.

## Fix đã áp dụng

- `resources/views/assets/form.blade.php`: dùng đúng `$editing`, thêm `category_id`, các field AssetRequest hỗ trợ và bỏ dấu Markdown ``` bị dán nhầm.
- `lang/vi/validation.php`: thêm thông báo validation tiếng Việt để không còn hiện `validation.required` thô.
