# Triển khai trực tuyến (Nhánh A: PHP/MySQL)

Phụ lục 05 của thông báo đồ án yêu cầu triển khai bản tối giản ngay từ đầu, vì các hạn chế của hosting miễn phí chỉ lộ ra khi làm thật. Tài liệu này là quy trình đề xuất cho dự án này. Những chỗ ghi **[phải tự kiểm chứng]** là thông tin phụ thuộc nhà cung cấp và thay đổi theo thời gian: đừng chép từ tài liệu này vào báo cáo, hãy lấy từ trang của nhà cung cấp tại thời điểm triển khai (Mục 4.5).

## Bước 0: kiểm tra phiên bản PHP trước tiên (quan trọng nhất)

`composer.json` ghi `php: ^8.3`, nhưng **`composer.lock` hiện khóa các gói Symfony 8.x yêu cầu PHP >= 8.4.1** (16 gói). Nghĩa là thư mục `vendor/` sinh ra từ `composer.lock` **không chạy được trên PHP 8.3**. (Báo cáo và `DANH_SACH_CAN_DIEN.md` đang ghi "PHP >= 8.3": cần sửa thành 8.4.1 hoặc xử lý như dưới.)

1. Vào bảng điều khiển hosting, xem danh sách phiên bản PHP chọn được. **[phải tự kiểm chứng]**
2. Nếu có PHP >= 8.4.1: dùng bình thường.
3. Nếu chỉ có PHP thấp hơn, có hai hướng:
   - **Hạ phiên bản thư viện** (chưa được kiểm thử trong dự án này): trên máy có Internet chạy `composer config platform.php 8.3.0` rồi `composer update`, sau đó chạy lại **toàn bộ** `php artisan test`. Nếu Composer báo không tìm được bộ phiên bản tương thích, hướng này không khả thi.
   - **Đổi nhà cung cấp** sang nền tảng miễn phí tương đương có PHP/MySQL và PHP >= 8.4.1 (Phụ lục 05 cho phép "nền tảng lưu trữ miễn phí tương đương"). Nên báo giảng viên trước khi đổi.

## Bước 1: chuẩn bị trên máy cục bộ

```bash
composer install --no-dev --optimize-autoloader     # tạo vendor/ (dùng đúng phiên bản PHP của hosting)
php artisan key:generate --show                       # in APP_KEY, chép vào .env của máy chủ (không commit)
php artisan config:clear && php artisan view:clear    # đừng tải lên cache cấu hình của máy bạn
```

Chuẩn bị `.env` cho máy chủ từ `.env.production.example`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`, `SESSION_SECURE_COOKIE=true`, `CACHE_STORE=file`, thông tin MySQL của hosting, `SLA_CRON_KEY=<chuỗi ngẫu nhiên dài>`. **Không** đưa tệp này vào Git.

## Bước 2: tạo cơ sở dữ liệu

1. Tạo CSDL MySQL trong bảng điều khiển, ghi lại máy chủ, tên CSDL, người dùng, mật khẩu (lưu trong tài liệu nội bộ của nhóm).
2. Mở **phpMyAdmin** → chọn CSDL → *Import* lần lượt:
   1. `database/sql/schema.sql` (tạo 25 bảng và ghi trạng thái migration)
   2. `database/sql/seed.sql` (3 tài khoản demo, danh mục, phiếu và tài sản mẫu, mức SLA, ngày nghỉ)
3. Nếu vượt giới hạn dung lượng nhập, chia nhỏ tệp theo bảng. **[phải tự kiểm chứng giới hạn]**

Hai tệp SQL được sinh từ MySQL 8 thật và đã nạp thử vào CSDL trống. Nếu hosting dùng MariaDB hoặc MySQL cũ, kiểm tra lại cột kiểu `json` (bảng `audit_logs`) và kiểu `enum`.

## Bước 3: tải mã nguồn lên bằng FTP

Hosting miễn phí thường không có SSH hay dòng lệnh, nên phải tải cả `vendor/` lên.

1. Tải **toàn bộ dự án** (kèm `vendor/`, không kèm `.git`, `node_modules`, `tests`, `.env` của máy bạn) vào thư mục `htdocs`.
2. Sao chép `deploy/infinityfree/root.htaccess` thành `htdocs/.htaccess` (chuyển mọi yêu cầu vào `public/` và chặn tệp ngoài `public/`).
3. Tạo `htdocs/.env` từ `.env.production.example` đã điền (Bước 1).
4. Cấp quyền ghi (755 hoặc 775 qua phần mềm FTP) cho: `storage/` (cả thư mục con: `framework/cache`, `framework/sessions`, `framework/views`, `logs`, `app/private`) và `bootstrap/cache/`.
5. Bật **chứng thư số miễn phí (SSL)** trong bảng điều khiển; việc cấp có thể mất một lúc. Kiểm tra `https://TEN-MIEN` không còn cảnh báo.

## Bước 4: kiểm thử khói (Phụ lục 05, bước 8)

- Mở trang chủ (trang giới thiệu công khai) qua HTTPS.
- Đăng nhập lần lượt 3 vai trò; thực hiện một nghiệp vụ ghi (tạo phiếu) và một nghiệp vụ đọc (xem báo cáo ở Admin).
- Mở một URL không tồn tại và `GET /api/v1/tickets/999999`: phải ra thông báo lỗi chung, không lộ đường dẫn tệp (BM9).
- **Đổi mật khẩu cả ba tài khoản demo** (Quản trị → Người dùng) trước khi công khai URL, rồi ghi mật khẩu mới vào Phụ lục B. Không công bố mật khẩu quản trị trên giao diện công khai.
- Kiểm tra từ hai thiết bị khác nhau và từ mạng bên ngoài (Phụ lục 04 mục D).

## Bước 5: quét SLA khi hosting không có cron

Lệnh `php artisan schedule:work`/`sla:escalate` cần dòng lệnh. Thay bằng điểm cuối nội bộ (đã có sẵn trong mã):

```text
POST https://TEN-MIEN/api/v1/internal/sla-escalate
Header: X-Cron-Key: <giá trị SLA_CRON_KEY>
```

Cho một dịch vụ lập lịch bên ngoài (hoặc trình lập lịch của hosting nếu có **[phải tự kiểm chứng]**) gọi mỗi 5 phút. Kiểm tra bằng `curl -i -X POST -H "X-Cron-Key: ..." https://TEN-MIEN/api/v1/internal/sla-escalate` → `{"success":true,...,"data":{"escalated":N}}`. Sai khóa → 403; chưa đặt `SLA_CRON_KEY` → 404. Nếu không có dịch vụ lập lịch nào dùng được, ghi rõ trong mục 6.6 rằng quét SLA phải kích hoạt thủ công.

## Hạn chế cần ghi trung thực (Mục 4.5 và 6.6)

- Không có dòng lệnh: không chạy được `migrate`, `composer` trên máy chủ; mọi thay đổi CSDL về sau phải nhập SQL qua phpMyAdmin.
- Thư mục `vendor/` phải tải lên (số tệp lớn, tải lâu; có thể chạm giới hạn số tệp **[phải tự kiểm chứng]**).
- Hosting miễn phí có thể có cơ chế chống bot làm `curl`/Postman gặp trang thử thách JavaScript thay vì JSON; nếu gặp, thử API bằng trình duyệt hoặc ghi rõ vào báo cáo.
- Không có thư điện tử theo tên miền: `MAIL_MAILER=log` nên liên kết đặt lại mật khẩu chỉ ghi vào `storage/logs/laravel.log`; muốn gửi thư thật phải dùng dịch vụ SMTP bên ngoài.
- Giao diện nạp Tailwind/Font Awesome từ CDN: cần Internet phía người xem.

## Phương án dự phòng (Phụ lục 05, mục C)

Giữ bản sao lưu CSDL (xuất từ phpMyAdmin) và bản dựng cục bộ bằng Docker (README) để nghiệm thu được nếu hosting gặp sự cố. Sự cố của nhà cung cấp **không** là lý do miễn nghĩa vụ nộp sản phẩm hoạt động được. Hệ thống phải chạy liên tục từ lúc nộp đến khi công bố điểm.
