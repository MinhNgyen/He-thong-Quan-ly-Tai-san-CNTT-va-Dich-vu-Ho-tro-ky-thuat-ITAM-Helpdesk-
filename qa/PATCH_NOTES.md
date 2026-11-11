# Các thay đổi đã áp dụng sau khi nhận project

## 1. Sửa trang Thêm/Cập nhật tài sản

File: `resources/views/assets/form.blade.php`

- Bỏ hai dòng Markdown fence `````blade`` / ````` `` bị dán vào file Blade.
- Dùng biến `$editing` do `AssetController` truyền sẵn để phân biệt create/update.
- Khi create dùng `route('assets.store')`; khi edit mới dùng `route('assets.update', $asset)` và `@method('PUT')`.
- Bổ sung `category_id` vì `AssetRequest` bắt buộc trường này.
- Đồng bộ field `asset_code` với model/request/database.
- Bổ sung các field nullable mà `AssetRequest` đã hỗ trợ: serial, brand, model, purchase_date, purchase_price, description.
- Giữ `parent_id` để kiểm thử cây tài sản.

## 2. Sửa thông báo `validation.required`

File mới: `lang/vi/validation.php`

Project dùng `APP_LOCALE=vi` và `APP_FALLBACK_LOCALE=vi` nhưng không có file ngôn ngữ validation, vì vậy Laravel có thể hiển thị nguyên key `validation.required`. File mới thêm các thông báo validation tiếng Việt và tên trường thường dùng.

## 3. Bộ script QA

Thư mục `qa/`:

- `RUN_ALL_SAFE.ps1`: chạy nhóm kiểm thử an toàn và thu log local.
- `01_run_targeted_tests.ps1`: các test từ cây/vòng đời tài sản đến API.
- `02_collect_local_security.ps1`: BM3, BM6, BM11, BM12-local và API descendants.
- `03_run_performance.ps1`: benchmark trước/sau index + EXPLAIN, có `finally` để migrate lại.
- `04_run_api_newman.ps1`: chạy Postman collection bằng Newman.

Log sinh ra nằm ở `qa_output/`.

## 4. Kiểm tra tĩnh đã thực hiện

- Toàn bộ PHP trong `app`, `routes`, `database`, `tests`, `config`, `bootstrap`, `lang`: không có lỗi cú pháp.
- Không còn Markdown fence trong `resources/views`.
- Asset form có đủ 4 trường bắt buộc: `asset_code`, `name`, `category_id`, `status`.
- Postman collection parse được: 21 requests, 9 `pm.test`.
- `docs/openapi.yaml` parse được: OpenAPI 3.0.3, 13 path entries.

## 5. Chưa thể chạy trực tiếp trong môi trường ChatGPT

Archive không chứa dependencies trong `vendor` (project dùng Docker volume `vendor_data`) và môi trường ChatGPT không có Docker daemon, nên không thể chạy Laravel/PHPUnit/Docker thật tại đây. Các script trên được chuẩn bị để chạy trực tiếp trên máy Windows của bạn, nơi Docker project đang hoạt động.
