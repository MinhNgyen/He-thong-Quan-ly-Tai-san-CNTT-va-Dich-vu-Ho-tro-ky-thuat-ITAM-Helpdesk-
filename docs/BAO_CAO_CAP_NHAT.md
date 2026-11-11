# Cập nhật báo cáo cho khớp với mã nguồn mới

Mã nguồn đã thay đổi nhiều so với phiên bản mà `BaoCao_DoAn_ITAM_Helpdesk.docx` mô tả. Tài liệu này liệt kê **mục nào của báo cáo cần sửa** và cung cấp **đoạn văn nền để dán** (hãy đọc lại bằng lời của nhóm: thông báo đồ án yêu cầu mọi thành viên giải trình được mã nguồn). Mình **không** sửa trực tiếp tệp `.docx` vì tệp có hình, trường tự động và định dạng theo Phụ lục 02.

Nguyên tắc: chỉ ghi những gì nhóm đã chạy và quan sát được. Các ô bằng chứng (ảnh, số đo, số commit, URL) vẫn phải do nhóm tự lấy theo `HUONG_DAN_KIEM_THU.md`.

## 1. Những điểm báo cáo hiện tại đang SAI hoặc LỖI THỜI

| Vị trí | Hiện ghi | Cần sửa thành |
|---|---|---|
| Mục 4.5, `DANH_SACH_CAN_DIEN.md` | PHP >= 8.3 (vì Laravel 13.31) | **PHP >= 8.4.1**: `composer.lock` khóa 16 gói Symfony 8.x yêu cầu PHP 8.4.1 trở lên. Xem `TRIEN_KHAI_TRUC_TUYEN.md`, Bước 0 |
| Mục 6.2 và ma trận 6.5 | 60 ca kiểm thử tự động | **147** ca (615 phép kiểm tra), chạy đạt trên cả SQLite và MySQL 8 |
| Mục 3.3, Phụ lục C | API ở `/api/...`, 15 điểm cuối | Tiền tố **`/api/v1`**, 20 route (xem mục 4 dưới đây); thay toàn văn Phụ lục C bằng `docs/openapi.yaml` mới |
| Mục 6.6 | Liệt kê K6-K10, SLA... là chưa làm | Cập nhật theo mục 6 dưới đây (nhiều hạng mục đã hoàn thành) |
| Kết luận | "SLA là hướng phát triển" | SLA theo giờ làm việc **đã hiện thực** (mục 3.6) |
| Mục 5.1/5.2 | Biện pháp mới ở mức khai báo | Có thêm tiêu đề bảo mật, khóa tài khoản, nhật ký, tải tệp an toàn: dùng bảng ở mục 5 dưới đây |

## 2. Mục 2.2: yêu cầu chức năng bổ sung (đánh mã tiếp theo YCCN hiện có)

- Đồng hồ SLA theo giờ làm việc; tạm dừng khi phiếu *Chờ người dùng phản hồi*; leo thang khi quá hạn (vai trò: Admin, Technician, Employee)
- Cây tài sản cha-con nhiều cấp và truy vấn toàn bộ tài sản con cháu
- Bàn giao / thu hồi tài sản; lập và hoàn thành bảo trì
- Tệp đính kèm cho phiếu (JPG, PNG, PDF, TXT; tối đa 5 MB; tối đa 10 tệp/phiếu)
- Báo cáo tổng hợp (6 báo cáo) và xuất CSV
- Nhật ký hệ thống và màn hình tra cứu cho Admin
- Thông báo trong ứng dụng cho sự kiện nghiệp vụ
- Đổi mật khẩu; quên và đặt lại mật khẩu; khóa tạm thời tài khoản sau 5 lần đăng nhập sai
- Quản trị người dùng (tạo, đổi vai trò, khóa/mở khóa); quản trị danh mục (phòng ban, danh mục tài sản, danh mục sự cố)
- Trang công khai (giới thiệu, cam kết SLA, hướng dẫn), sitemap, robots
- API: làm mới token, cây tài sản con, điểm cuối quét SLA cho trình lập lịch

Yêu cầu phi chức năng (Mục 2.3) có thể thêm chỉ tiêu đo được, ví dụ: *hạn SLA đúng theo giờ làm việc trong 100% ca kiểm thử của `BusinessCalendarTest` (13 ca)*; *tài khoản bị khóa 15 phút sau 5 lần sai liên tiếp*; *phản hồi lỗi API theo một cấu trúc JSON thống nhất*.

## 3. Mục 3.2 và 3.6: thiết kế dữ liệu và thuật toán đặc thù

### 3.2: thay đổi lược đồ (cập nhật ERD, từ điển dữ liệu, Phụ lục D)

Bảng mới (7): `audit_logs`, `sla_policies`, `holidays`, `escalations`, `ticket_attachments`, `notifications`, `password_reset_tokens`.
Cột mới: `users.failed_login_attempts`, `users.locked_until`; `tickets.sla_due_at`, `tickets.sla_paused_at`, `tickets.sla_remaining_minutes`, `tickets.sla_escalated_at`; `assets.parent_id` (khóa ngoại tự tham chiếu, `ON DELETE SET NULL`). Cột `tickets.status` thêm giá trị `waiting_user`.
Chỉ mục mới (6): `idx_tickets_status_created`, `idx_tickets_assignee_status`, `idx_tickets_creator_created`, `idx_tickets_sla_scan`, `idx_assets_category_status`, `idx_assets_status`.
Lược đồ đầy đủ: `database/sql/schema.sql` (25 bảng).

### 3.6: bài toán đặc thù của đề tài 24 (nội dung nền)

> **Bài toán.** Đồng hồ cam kết dịch vụ (SLA) không thể tính bằng cách cộng số giờ trực tiếp vào thời điểm tạo phiếu, vì sẽ sai khi phiếu tạo cuối ngày, cuối tuần hoặc ngày nghỉ, và vì đồng hồ phải dừng khi phiếu chờ người dùng. Hệ thống tính theo **phút làm việc**: mỗi ngày làm việc có khoảng 08:00-17:00 (múi giờ Asia/Ho_Chi_Minh), thứ Hai đến thứ Sáu, trừ các ngày trong bảng `holidays`. Thời gian lưu theo UTC, chỉ quy đổi sang giờ Việt Nam khi xác định ranh giới ngày làm việc.
>
> **Mã giả: cộng phút làm việc** (`App\Support\BusinessCalendar::addBusinessMinutes`)
>
> ```text
> addBusinessMinutes(from, minutes):
>     cursor <- from (giờ địa phương); remaining <- minutes
>     lặp tối đa MAX_SCAN_DAYS lần:
>         nếu cursor không phải ngày làm việc:  cursor <- 08:00 ngày kế; tiếp tục
>         m <- phút trong ngày của cursor
>         nếu m < 08:00:  cursor <- 08:00 cùng ngày; m <- 08:00
>         nếu m >= 17:00: cursor <- 08:00 ngày kế; tiếp tục
>         còn_lại_trong_ngày <- 17:00 - m
>         nếu remaining <= còn_lại_trong_ngày: trả về cursor + remaining
>         remaining <- remaining - còn_lại_trong_ngày; cursor <- 08:00 ngày kế
> ```
>
> Độ phức tạp O(số ngày làm việc trải qua), có chặn trên `MAX_SCAN_DAYS = 3660`. Ví dụ nghiệm thu: phiếu tạo 17:00 thứ Sáu với cam kết 4 giờ làm việc đến hạn lúc **12:00 thứ Hai** (kiểm chứng bởi `test_friday_17h_plus_4_business_hours_is_monday_noon` và `SlaTest`).
>
> **Tạm dừng và tiếp tục** (`SlaService::pause/resume`): khi chuyển sang *Chờ người dùng phản hồi*, hệ thống lưu `sla_remaining_minutes = phút làm việc giữa (bây giờ) và (hạn)`, ghi `sla_paused_at` và xóa `sla_due_at`. Khi người gửi phiếu bình luận, trạng thái tự về *Đang xử lý* và `sla_due_at = addBusinessMinutes(bây giờ, sla_remaining_minutes)`. Thời gian chờ không bị tính.
>
> **Leo thang đúng một lần** (`SlaService::escalateOverdue`, lệnh `sla:escalate` hoặc điểm cuối nội bộ): chọn phiếu chưa xong, không ở trạng thái chờ, đã quá hạn và chưa leo thang; với mỗi phiếu, trong một giao dịch thực hiện `UPDATE tickets SET sla_escalated_at = ? WHERE id = ? AND sla_escalated_at IS NULL`. Chỉ tiến trình nào cập nhật được 1 dòng mới ghi bản ghi `escalations`, lịch sử, thông báo và nhật ký, nên chạy song song hoặc chạy lặp không tạo trùng.
>
> **Cây tài sản** (`AssetRepository::descendants`): dùng CTE đệ quy `WITH RECURSIVE`, tham số được ràng buộc, chặn độ sâu 50. Khi gán tài sản cha, `AssetService::wouldCreateCycle` loại bỏ trường hợp chọn chính nó hoặc một tài sản con cháu của nó.
>
> **Nhận phiếu và chuyển trạng thái tài sản tránh tranh chấp.** Nhận phiếu dùng `UPDATE ... WHERE assigned_to IS NULL`; bàn giao tài sản dùng `UPDATE assets SET status='in_use' WHERE id=? AND status='available'` trong giao dịch; cập nhật phiếu khóa dòng bằng `SELECT ... FOR UPDATE`.

**Kết quả quan sát thật (có thể trích):** chạy 10 yêu cầu nhận cùng một phiếu song song vào máy chủ thật với MySQL 8, kết quả đúng 1 yêu cầu thành công (200), 9 yêu cầu bị từ chối (403), CSDL ghi đúng 1 người nhận và 1 dòng lịch sử. Lưu ý trung thực: cả 9 lần thua đều bị chặn ở bước kiểm tra quyền, nên nhánh thua ở câu `UPDATE` có điều kiện chưa được quan sát; không nên khẳng định đã thử tải cao. Nhóm nên tự chạy lại phép thử này và ghi số liệu của mình.

## 4. Mục 3.3 và Phụ lục C: API

20 route dưới `/api/v1`: `login`, `me`, `logout`, `refresh-token`, `tickets` (GET, POST), `tickets/options`, `tickets/{id}` (GET, PUT, PATCH), `tickets/{id}/comments`, `tickets/{id}/claim`, `assets` (GET, POST), `assets/{id}` (GET, PUT, PATCH, DELETE), `assets/{id}/descendants`, `internal/sla-escalate`.

- Quy ước lỗi thống nhất `{ "success": false, "message": "...", "errors": {...} }` cho 401, 403, 404, 405, 422, 429, 500; ở 404/405 không lộ tên model hay đường dẫn; khi `APP_DEBUG=false` lỗi 500 trả thông báo chung.
- Quản lý phiên bản bằng đường dẫn `/api/v1`.
- Làm mới token: `POST /refresh-token` cấp token mới và thu hồi token cũ.
- Giới hạn tần suất: đăng nhập 5 lần/phút/IP, API 60 yêu cầu/phút/token (hai bộ đếm riêng).
- Toàn văn: `docs/openapi.yaml`. Nhớ nạp vào Swagger Editor để kiểm tra (chưa có công cụ tự động trong repo).

## 5. Mục 5.1-5.3: bảng đối chiếu biện pháp ↔ mã nguồn (điền cột "vị trí")

| Mã | Biện pháp | Vị trí trong mã |
|---|---|---|
| BM1 | Eloquent / truy vấn tham số hóa, không ghép chuỗi | `app/Repositories/*` (ví dụ `AssetRepository::descendants` dùng `?`) |
| BM2 | Blade mã hóa đầu ra `{{ }}`; CSP | mọi view; `app/Http/Middleware/SecurityHeaders.php`, `config/security.php` |
| BM3 | Thẻ `@csrf`; `SameSite=lax` | form Blade; `config/session.php`, `SESSION_SAME_SITE` |
| BM4 | Policy mức đối tượng | `app/Policies/TicketPolicy.php`, `TicketAttachmentPolicy.php`; thông báo chỉ tìm trong `$user->notifications()` |
| BM5 | Kiểm soát quyền phía máy chủ | `RoleMiddleware` trên route; `routes/web.php`, `routes/api.php` |
| BM6 | Băm mật khẩu bcrypt (`$2y$12$`) | cast `hashed` của model `User` |
| BM7 | Phiên: tạo lại mã phiên, `HttpOnly`, `Secure`, hủy khi đăng xuất | `AuthController`, `.env.production.example` |
| BM8 | Chữ ký tệp thật, giới hạn kích thước, tên UUID, lưu ngoài thư mục web | `app/Services/AttachmentService.php` |
| BM9 | `APP_DEBUG=false`; lỗi API chung | `bootstrap/app.php`, `.env.production.example` |
| BM10 | Giới hạn tần suất; khóa tài khoản 15 phút sau 5 lần sai; nhật ký | `AppServiceProvider::configureRateLimiting`, `AuthService` |
| BM11 | Cấu hình qua `.env`; `.env` trong `.gitignore`; `compose.yaml` đọc mật khẩu từ `.env` | `.gitignore`, `compose.yaml` |
| BM12 | Tiêu đề bảo mật, HSTS khi HTTPS, chuyển hướng HTTPS | `SecurityHeaders.php`, `public/.htaccess` |

Hạn chế cần nêu thẳng: CSP còn `'unsafe-inline'` và `'unsafe-eval'` do Tailwind CDN.

## 6. Mục 6.5 và 6.6: ma trận hoàn thành và hạn chế

### Đã hoàn thành so với bản trước (chuyển sang "Hoàn thành" trong ma trận 6.5)

K6 tải tệp · K7 báo cáo và CSV · K8 nhật ký hệ thống · K9 thông báo · K10 trang công khai · đổi và khôi phục mật khẩu · khóa tài khoản · quản trị người dùng và danh mục · bàn giao/thu hồi/bảo trì tài sản · tiêu đề bảo mật HTTP · `schema.sql`/`seed.sql` · API `/api/v1` · SLA theo giờ làm việc · cây tài sản đệ quy.

### Hạn chế còn tồn tại (ghi trung thực ở 6.6)

1. **Chưa đo độ bao phủ mã** (cần PCOV/Xdebug); số liệu bao phủ phải để trống hoặc ghi "không đo".
2. **Phụ thuộc CDN** (Tailwind, Font Awesome): cần Internet; CSP buộc phải nới `'unsafe-inline'`, `'unsafe-eval'`.
3. **Thông báo chỉ trong ứng dụng**; liên kết đặt lại mật khẩu mặc định ghi vào log (`MAIL_MAILER=log`) thay vì gửi thư thật.
4. **Quét SLA phụ thuộc bộ lập lịch**: trên hosting không có cron phải nhờ dịch vụ bên ngoài gọi điểm cuối nội bộ.
5. **Nhật ký chỉ ghi thêm ở mức ứng dụng** (model chặn sửa/xóa), chưa có ràng buộc ở mức CSDL (trigger hoặc quyền riêng).
6. **Chưa thử tải cao**: kiểm thử tranh chấp mới ở mức 10 yêu cầu song song, chưa có bài thử 50-200 yêu cầu.
7. **Ngày nghỉ chưa có giao diện quản trị**: bảng `holidays` nhập qua seeder/SQL; dữ liệu mẫu chỉ mang tính minh họa.
8. **Chưa có xác thực hai yếu tố**, chưa có API tải tệp đính kèm (tệp chỉ qua giao diện web).
9. **Truy vấn quét SLA** có thể không dùng chỉ mục khi phần lớn phiếu thỏa điều kiện (quan sát bằng `EXPLAIN` trên dữ liệu giả lập); không khẳng định đã tối ưu truy vấn này.
10. **Chưa kiểm tra bằng mắt trên trình duyệt thật** bản giao diện mới trong quá trình phát triển này: nhóm cần tự duyệt từng màn hình và chụp ảnh (Hình 3.5 đến 3.8, Phụ lục A).

## 7. Lỗi thật đã phát hiện và sửa trong quá trình kiểm thử (có thể đưa vào Chương 6 như bằng chứng kiểm thử hiệu quả)

| # | Lỗi | Cách phát hiện | Khắc phục |
|---|---|---|---|
| 1 | Các `throttle:N,M` không đặt tên dùng **chung một bộ đếm theo IP**: gọi API đã xác thực làm cạn lượt đăng nhập (mới đăng nhập 2 lần đã báo hết lượt) | Chạy bộ Postman bằng newman trên máy chủ thật, nhận 429 bất thường; xác nhận bằng `curl` đọc tiêu đề `X-RateLimit-Remaining` | Bộ giới hạn có tên riêng (`login`, `api-token`, `register`...) mỗi loại một khóa; test hồi quy trong `SecurityTest` |
| 2 | Quan hệ `AssetCategory::assets()` và `TicketCategory::tickets()` dùng sai khóa ngoại mặc định (`asset_category_id`, `ticket_category_id`) trong khi cột thật là `category_id` | Test xóa danh mục đang dùng (`UserAdminTest`) | Chỉ rõ khóa ngoại `category_id` |
| 3 | `migrate:rollback` của migration chỉ mục **thất bại trên MySQL** ("needed in a foreign key constraint") và để lại trạng thái dở dang | Chạy rollback/migrate nhiều vòng trên MySQL 8 | `down()` tạo lại chỉ mục đơn trước khi xóa chỉ mục tổng hợp; `up()` dọn chỉ mục thừa; thử 3 vòng trên MySQL và SQLite |
| 4 | N+1 truy vấn bảng ngày nghỉ khi liệt kê phiếu kèm SLA (17 truy vấn cho 10 phiếu) | `perf:benchmark` hiển thị số truy vấn | Nạp ngày nghỉ một lần mỗi request (`SlaService` đăng ký `scoped`): còn 9 truy vấn |
| 5 | Migration enum viết lại cột gốc sẽ không áp dụng cho CSDL đã triển khai | Rà soát khi thêm trạng thái `waiting_user` | Migration mới dùng `->change()`, thử nâng cấp và rollback trên MySQL |

## 8. Số liệu hiệu năng minh họa (chỉ để bạn biết hướng kết quả; KHÔNG chép vào báo cáo)

Trên máy của mình (MySQL 8, 20.000 phiếu, 20.000 tài sản, 15 lần đo, xóa cache mỗi lần), việc có chỉ mục giúp điểm cuối lọc phiếu giảm từ khoảng 23 ms xuống 7 ms, lọc tài sản từ 17 ms xuống 9 ms, bảng điều khiển từ 54 ms xuống 32 ms; danh sách không lọc không đổi. Máy và dữ liệu của nhóm sẽ cho số khác: **hãy tự đo** theo mục 4 của `HUONG_DAN_KIEM_THU.md` và chỉ báo cáo số của nhóm.

## 9. Phụ lục F (khai báo công cụ AI)

Công cụ AI đã hỗ trợ viết một phần lớn mã nguồn bổ sung và tài liệu này. Khai báo trung thực phạm vi (SLA, nhật ký, báo cáo, tệp đính kèm, người dùng/danh mục, test, tài liệu), ghi thành viên nào đã đọc và chạy lại, và mức tự kiểm chứng. Mọi thành viên phải giải trình được các mô-đun này khi được hỏi.
