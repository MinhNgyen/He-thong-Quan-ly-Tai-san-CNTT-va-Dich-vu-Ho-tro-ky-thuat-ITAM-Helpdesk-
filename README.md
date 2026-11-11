# ITAM & Helpdesk

Hệ thống **Quản lý Tài sản CNTT và Dịch vụ Hỗ trợ kỹ thuật** (đề tài 24, học phần CSE702051 - Thiết kế Web nâng cao). Xây dựng bằng Laravel 13 (PHP) và MySQL, kiến trúc ba tầng Controller - Service - Repository, có REST API `/api/v1` xác thực Bearer token.

> Dữ liệu mô phỏng phục vụ mục đích học tập. Toàn bộ dữ liệu mẫu là dữ liệu giả lập.

## Chức năng chính

| Nhóm | Nội dung |
|---|---|
| Phiếu hỗ trợ | Tạo, phân công, nhận việc, bình luận, luồng trạng thái có kiểm soát (gồm *Chờ người dùng phản hồi*), tệp đính kèm |
| **SLA theo giờ làm việc** | Hạn xử lý tính theo giờ hành chính (08:00-17:00, thứ Hai-thứ Sáu, trừ ngày nghỉ); tạm dừng khi chờ người dùng; tự leo thang khi quá hạn |
| Tài sản IT | CRUD, **cây tài sản cha-con** (truy vấn đệ quy), bàn giao / thu hồi, bảo trì |
| Báo cáo | 6 báo cáo tổng hợp (JOIN nhiều bảng, hàm tổng hợp), biểu đồ cột, xuất CSV |
| Quản trị | Người dùng (khóa/mở khóa, đổi vai trò), danh mục (phòng ban, danh mục tài sản/sự cố), **nhật ký hệ thống** chỉ ghi thêm |
| Tài khoản | Đổi mật khẩu, quên/đặt lại mật khẩu, khóa tạm thời sau 5 lần đăng nhập sai |
| Thông báo | Thông báo trong ứng dụng khi được phân công, đổi trạng thái, bình luận, quá hạn SLA |
| Công khai | Trang giới thiệu, cam kết SLA, hướng dẫn (có thẻ meta SEO, sitemap, robots) |
| Bảo mật | CSRF, XSS (mã hóa đầu ra + CSP), tiêu đề bảo mật HTTP, chống IDOR bằng Policy, giới hạn tần suất, tải tệp kiểm tra chữ ký thật |

Ba vai trò: **Admin**, **Technician** (nhân viên IT), **Employee** (nhân viên).

## Yêu cầu

- **Docker Desktop** (Docker Compose v2), cổng `8000` và `3307` còn trống. Không cần cài PHP, Composer hay MySQL.
- Hoặc chạy không Docker: **PHP >= 8.4.1** (bản khóa trong `composer.lock` dùng Symfony 8), Composer 2, MySQL 8, các extension: `mbstring`, `xml`, `curl`, `zip`, `fileinfo`, `pdo_mysql`.

## Chạy lần đầu (Docker, khoảng 10 phút)

```bash
# 1. Tạo file cấu hình và ĐẶT mật khẩu CSDL của riêng bạn
cp .env.example .env
#    mở .env, sửa DB_PASSWORD và DB_ROOT_PASSWORD

# 2. Khởi động (lần đầu cài thư viện, tạo APP_KEY, migrate và seed dữ liệu mẫu)
docker compose up --build -d
docker compose logs -f app        # chờ dòng "Server running on [http://0.0.0.0:8000]"

# 3. Mở trình duyệt: http://localhost:8000
```

> Nếu bạn đã chạy bản cũ: `docker compose down -v` (xóa volume CSDL cũ) rồi chạy lại các bước trên.

### Chạy không Docker

```bash
cp .env.example .env              # sửa DB_HOST=127.0.0.1, DB_USERNAME, DB_PASSWORD, DB_DATABASE
composer install
php artisan key:generate
php artisan migrate --seed        # hoặc nhập database/sql/schema.sql rồi database/sql/seed.sql
php artisan serve                 # http://127.0.0.1:8000
```

## Tài khoản demo (do `UserSeeder` tạo, chỉ dùng cục bộ/thử nghiệm)

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Admin | `admin@example.com` | `Password123!` |
| Technician | `technician@example.com` | `Password123!` |
| Employee | `employee@example.com` | `Password123!` |

Trên hệ thống trực tuyến **hãy đổi mật khẩu** (Quản trị → Người dùng) và ghi vào Phụ lục B của báo cáo.

## Lệnh thường dùng

```bash
# Kiểm thử tự động (147 test, dùng SQLite trong bộ nhớ, không đụng CSDL thật)
docker compose exec app php artisan test

# Quét phiếu quá hạn SLA và leo thang (mỗi phiếu đúng một lần); lịch chạy: mỗi 5 phút
docker compose exec app php artisan sla:escalate
docker compose exec app php artisan schedule:work            # chạy bộ lập lịch khi phát triển

# Dữ liệu lớn để đo hiệu năng (mặc định 5.000 phiếu + 5.000 tài sản), đo thời gian phản hồi
docker compose exec app php artisan db:seed --class=BulkDemoSeeder
docker compose exec app php artisan perf:benchmark --runs=30 --cold --markdown

# Dựng lại CSDL từ đầu
docker compose exec app php artisan migrate:fresh --seed
```

Hướng dẫn kiểm thử đầy đủ từng bước (bảo mật BM1-BM12, hiệu năng, truy cập, API, Git) ở [`docs/HUONG_DAN_KIEM_THU.md`](docs/HUONG_DAN_KIEM_THU.md).

## Cấu trúc thư mục

```text
app/
  Http/Controllers/   Tầng điều khiển (nhận request, gọi Service, trả view/JSON)
  Http/Requests/      Kiểm tra dữ liệu vào phía máy chủ (FormRequest)
  Http/Middleware/    SecurityHeaders, RoleMiddleware, ApiTokenAuth
  Services/           Tầng nghiệp vụ (TicketService, SlaService, AuthService, AssetLifecycleService...)
  Repositories/       Tầng truy cập dữ liệu (mọi truy vấn CSDL nằm ở đây)
  Policies/           Phân quyền mức đối tượng (TicketPolicy, TicketAttachmentPolicy)
  Support/            BusinessCalendar (thuật toán giờ làm việc), CatalogRegistry
config/sla.php        Giờ làm việc, mức SLA mặc định
database/
  migrations/ seeders/
  sql/schema.sql      Lược đồ CSDL đầy đủ (nhập vào CSDL rỗng)
  sql/seed.sql        Dữ liệu mẫu (nhập sau schema.sql)
docs/
  openapi.yaml        Đặc tả OpenAPI 3 (nạp vào https://editor.swagger.io)
  ITAM-Helpdesk.postman_collection.json
  HUONG_DAN_KIEM_THU.md · TRIEN_KHAI_TRUC_TUYEN.md · BAO_CAO_CAP_NHAT.md
  perf/explain.sql    Các truy vấn EXPLAIN cho mục hiệu năng
routes/web.php · routes/api.php   API nằm dưới /api/v1
tests/                Unit + Feature (147 test)
```

## REST API

Mọi điểm cuối nằm dưới `/api/v1`, JSON, xác thực `Authorization: Bearer <token>`. Lỗi trả về thống nhất `{ "success": false, "message": "...", "errors": {...} }`. Xem [`docs/openapi.yaml`](docs/openapi.yaml) hoặc nhập bộ Postman trong `docs/`.

```bash
curl -s -X POST http://localhost:8000/api/v1/login -H "Content-Type: application/json" \
     -d '{"email":"admin@example.com","password":"Password123!"}'
```

## Biến môi trường quan trọng

Xem `.env.example` (cục bộ) và `.env.production.example` (trực tuyến). **Không** đưa `.env` hay mật khẩu thật vào Git. Khi triển khai: `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`.

## Triển khai trực tuyến

Xem [`docs/TRIEN_KHAI_TRUC_TUYEN.md`](docs/TRIEN_KHAI_TRUC_TUYEN.md). **Lưu ý:** thư viện khóa trong `composer.lock` yêu cầu PHP >= 8.4.1; hãy kiểm tra hosting có phiên bản này trước khi bắt đầu.
