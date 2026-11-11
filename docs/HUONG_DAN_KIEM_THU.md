# Hướng dẫn kiểm thử và thu thập bằng chứng

Tài liệu này chỉ cách **tự chạy** từng loại kiểm thử và **chụp bằng chứng** để điền vào báo cáo (các ô `[CẦN ĐIỀN]`). Mọi số liệu và ảnh phải lấy từ chính hệ thống của nhóm: thông báo đồ án yêu cầu đối chiếu với lịch sử repository và có thể hỏi giải trình. Chỉ kiểm thử trên hệ thống do nhóm phát triển.

Quy ước lệnh: dùng Docker thì thêm tiền tố `docker compose exec app` trước `php artisan ...`. Ảnh chụp màn hình phải thấy rõ **URL, thời điểm, vai trò đăng nhập** (Phụ lục 02).

---

## 0. Chuẩn bị

```bash
cp .env.example .env            # đặt DB_PASSWORD, DB_ROOT_PASSWORD
docker compose up --build -d
docker compose exec app php artisan migrate:fresh --seed
```

Tài khoản: `admin@example.com`, `technician@example.com`, `employee@example.com`, mật khẩu `Password123!`.
Mở **3 cửa sổ ẩn danh (hoặc 3 trình duyệt)** để đăng nhập song song ba vai trò khi thử truy cập chéo.

---

## 1. Kiểm thử tự động (Mục 6.2, Hình 6.x)

```bash
docker compose exec app php artisan test
```

Kết quả mong đợi: `Tests: 147 passed`. Chụp toàn bộ màn hình terminal (có dòng lệnh và dòng tổng kết).

| Muốn chứng minh | Lệnh |
|---|---|
| Thuật toán SLA theo giờ làm việc | `php artisan test tests/Unit/BusinessCalendarTest.php tests/Feature/SlaTest.php` |
| Bảo mật (headers, khóa tài khoản, giới hạn tần suất) | `php artisan test tests/Feature/SecurityTest.php` |
| Nhật ký hệ thống | `php artisan test tests/Feature/AuditLogTest.php` |
| Tải tệp an toàn | `php artisan test tests/Feature/AttachmentTest.php` |
| Phân quyền / truy cập chéo (IDOR) | `php artisan test tests/Feature/RolePermissionTest.php tests/Feature/TicketTest.php` |
| Báo cáo, CSV | `php artisan test tests/Feature/ReportTest.php` |

Chạy lại trên **MySQL thật** (kiểm tra chéo): tạo CSDL riêng có tên kết thúc `_test`, sửa thông tin đăng nhập trong `phpunit.mysql.xml`, rồi
`php artisan test -c phpunit.mysql.xml`. (`tests/TestCase.php` chỉ cho phép MySQL khi tên CSDL kết thúc `_test`, để không bao giờ xóa nhầm CSDL thật.)

**Độ bao phủ (nếu muốn):** cần PCOV hoặc Xdebug. `php artisan test --coverage`. Image Docker mặc định chưa có PCOV; nếu không đo được thì ghi rõ "không đo" trong báo cáo, đừng tự điền số.

---

## 2. Kiểm thử chức năng thủ công (Phụ lục G, TC-34 đến TC-36 và các ca mới)

Điền cột *kết quả thực tế* theo đúng những gì bạn quan sát, kể cả khi lệch kỳ vọng.

### TC-SLA-1: hạn SLA tính theo giờ làm việc (tiêu chí nghiệm thu đề tài 24)

```bash
docker compose exec app php artisan tinker --execute='$c = new App\Support\BusinessCalendar("Asia/Ho_Chi_Minh"); echo $c->addBusinessMinutes(Carbon\CarbonImmutable::parse("2026-11-06 17:00","Asia/Ho_Chi_Minh"), 240)->setTimezone("Asia/Ho_Chi_Minh")->format("Y-m-d H:i l");'
```

Kỳ vọng: `2026-11-09 12:00 Monday` (phiếu 17:00 thứ Sáu + 4 giờ làm việc = 12:00 thứ Hai). Chụp kết quả cùng `php artisan test --filter=BusinessCalendarTest` (13 ca, gồm cuối tuần, ngày nghỉ, qua ngày).

### TC-SLA-2: tạm dừng và chạy tiếp đồng hồ

1. Employee tạo phiếu mức *Cao*. Ghi lại **Hạn xử lý** ở khung "Cam kết SLA" (chi tiết phiếu).
2. Admin: phân công cho Technician → *Đang xử lý* → đổi sang *Chờ người dùng phản hồi*. Khung SLA phải hiện **"Tạm dừng"** và số phút còn lại.
3. Chờ một lúc (hoặc để qua đêm), rồi Employee **bình luận** vào phiếu.
4. Kỳ vọng: trạng thái tự về *Đang xử lý*, SLA "Đang chạy", hạn mới = thời điểm phản hồi + số phút làm việc còn lại (không tính thời gian chờ). Chụp 3 ảnh: trước, lúc tạm dừng, sau khi tiếp tục.

### TC-SLA-3: quá hạn và leo thang đúng một lần

```bash
# ép một phiếu đang mở thành quá hạn
docker compose exec app php artisan tinker --execute='App\Models\Ticket::whereIn("status",["open","assigned","in_progress"])->limit(1)->update(["sla_due_at"=>now()->subHour(),"sla_escalated_at"=>null]);'
docker compose exec app php artisan sla:escalate     # kỳ vọng: Đã leo thang 1 phiếu quá hạn SLA.
docker compose exec app php artisan sla:escalate     # kỳ vọng: Đã leo thang 0 phiếu quá hạn SLA.
```

Kiểm tra thêm: Admin thấy thông báo (chuông) "Phiếu quá hạn SLA"; Admin → Nhật ký hệ thống có `ticket.sla_escalated`; lịch sử phiếu có dòng `sla_escalated`.

### TC-TREE: cây tài sản và truy vấn đệ quy

1. Admin → Tài sản IT → mở `SRV001` (dữ liệu mẫu có cây 3 cấp: máy chủ, khay lưu trữ, ổ đĩa). Mục "Cây tài sản con" liệt kê đủ các nút kèm độ sâu.
2. API: `GET /api/v1/assets/{id}/descendants` (Bearer của Admin) trả danh sách phẳng có `depth`.
3. Sửa `SRV001` và chọn cha là một tài sản con của nó → phải bị từ chối: "không được là chính nó hoặc con cháu".

### TC-ASSET-LIFE: bàn giao, thu hồi, bảo trì

Admin mở chi tiết tài sản *Sẵn sàng* → Bàn giao cho Employee (trạng thái → *Đang sử dụng*, có dòng lịch sử) → thử bàn giao lần hai (bị từ chối) → Thu hồi (→ *Sẵn sàng*). Lập bảo trì (→ *Bảo trì*), thử lập lần hai (bị từ chối), Hoàn thành (→ *Sẵn sàng*).

### TC-34, TC-35, TC-36 (danh sách cần điền)

Ba ca này là các ca thủ công còn để trống trong Phụ lục G của báo cáo; thực hiện theo mô tả trong chính báo cáo, ghi kết quả thật.

### TC-REPORT: báo cáo và CSV

Admin → Báo cáo: thấy 6 báo cáo kèm biểu đồ cột. Bấm **Xuất CSV** ở từng báo cáo, mở bằng Excel: tiếng Việt hiển thị đúng, số liệu khớp bảng. Employee/Technician truy cập `/reports` phải nhận **403**.

### TC-FILE: tệp đính kèm

Employee mở phiếu của mình → tải lên ảnh PNG/JPG, PDF (thành công) → thử đổi tên `shell.php` thành `anh.jpg` rồi tải lên (bị từ chối vì kiểm tra theo nội dung) → tệp > 5 MB (bị từ chối). Employee khác mở đường dẫn tải xuống → **403**.

### TC-USER: quản trị người dùng

Admin tạo tài khoản mới, khóa tài khoản đó → tài khoản đăng nhập lại phải thất bại; mở khóa → đăng nhập được. Admin tự hạ vai trò/khóa chính mình phải bị chặn.

### TC-PASSWORD: đổi và khôi phục mật khẩu

Đổi mật khẩu với mật khẩu hiện tại sai (lỗi) và đúng (thành công). Quên mật khẩu: thư được ghi vào `storage/logs/laravel.log` (`MAIL_MAILER=log`); mở liên kết trong log để đặt lại.

---

## 3. Kiểm thử bảo mật BM1-BM12 (Mục 5.3, Phụ lục 09)

Với mỗi mục: **(a)** chụp kịch bản/kết quả, **(b)** chụp đoạn mã chứng minh biện pháp, **(c)** ghi vị trí tệp. Cột "trước/sau khắc phục" của đồ án: nếu hệ thống đã an toàn từ đầu thì ghi "không có lỗi để khắc phục" và dùng kịch bản tấn công làm bằng chứng; đừng bịa số liệu "trước".

| Mã | Cách thử | Kỳ vọng / bằng chứng |
|---|---|---|
| **BM1** SQL injection | `curl -s "http://localhost:8000/api/v1/tickets?q=' OR 1=1 --" -H "Authorization: Bearer TOKEN"`; trong tinker: `DB::enableQueryLog(); App\Models\Ticket::where("title","like","%x OR 1=1 --%")->get(); print_r(DB::getQueryLog());` | Truy vấn dùng placeholder `?`, giá trị nằm trong `bindings`; không trả thêm dữ liệu. Chụp mã trong `app/Repositories/*`. |
| **BM2** XSS | Employee tạo phiếu có tiêu đề `<script>alert(1)</script>`; mở phiếu và danh sách | Hiển thị nguyên văn dạng chữ, không chạy script. Chụp "xem nguồn trang" thấy `&lt;script&gt;`. Chụp tiêu đề `Content-Security-Policy`. |
| **BM3** CSRF | `curl -i -X POST http://localhost:8000/tickets -d "title=x"` (không có token) | **419 Page Expired**. Chụp `@csrf` trong form và cookie `SameSite=Lax`. |
| **BM4** IDOR | Employee A ghi lại ID phiếu của Employee B, mở `/tickets/{id}`; thử tải `/attachments/{id}` của phiếu người khác; `POST /notifications/{id của người khác}/read` | **403** (phiếu, tệp) và **404** (thông báo). Chụp cả hai tài khoản. |
| **BM5** Kiểm soát truy cập | Employee gọi `/admin/users`, `/reports`, `/admin/audit-logs`, `GET /api/v1/assets` | **403**. Technician gọi `/admin/users` → 403. |
| **BM6** Băm mật khẩu | `docker compose exec mysql mysql -u itam -p itam_helpdesk -e "SELECT email, LEFT(password,7) FROM users"` | Giá trị bắt đầu `$2y$12$` (bcrypt). Chụp kết quả. |
| **BM7** Phiên | DevTools → Application → Cookies: xem cờ `HttpOnly`, `SameSite`, `Secure` (Secure chỉ có trên HTTPS trực tuyến). So sánh giá trị cookie phiên **trước và sau** đăng nhập (đã đổi). Đăng xuất rồi dùng nút Back để thử truy cập | Chụp bảng cookie; nêu `SESSION_LIFETIME`, `SESSION_SECURE_COOKIE=true` trong `.env` trực tuyến. |
| **BM8** Tải tệp | Làm theo TC-FILE | Từ chối tệp giả ảnh và tệp quá lớn; tệp thật nằm ở `storage/app/private/attachments/...` với tên UUID; truy cập `http://.../storage/attachments/...` → 404. |
| **BM9** Lỗi | Trên bản trực tuyến (`APP_DEBUG=false`): mở `/phieu-khong-ton-tai` và `GET /api/v1/tickets/999999` | Trang/JSON lỗi chung, **không** có đường dẫn tệp hay SQL. Chụp trang lỗi **trên môi trường trực tuyến** (yêu cầu của bảng Phụ lục 09). |
| **BM10** Dò mật khẩu | Đăng nhập sai liên tiếp 5 lần với cùng một email rồi đăng nhập đúng; ngoài ra gọi `POST /api/v1/login` 6 lần trong 1 phút | Tài khoản khóa tạm 15 phút (thông báo "tạm thời bị khóa"); lần 6 trong phút trả **429**. Admin → Nhật ký hệ thống có `auth.login_failed`, `auth.account_locked`, `auth.login_blocked`. |
| **BM11** Bí mật trong mã | `git log -p --all \| grep -iE "password\|secret\|api[_-]?key" \| head`; `git ls-files \| grep -E "^\.env$"` (phải rỗng); tùy chọn quét bằng gitleaks: `docker run --rm -v "$PWD:/repo" zricethezav/gitleaks:latest detect -s /repo` | Không có giá trị bí mật thật. `.env` nằm trong `.gitignore`. Ghi chú: mật khẩu demo `Password123!` là dữ liệu giả lập đã công khai, không phải bí mật thật. |
| **BM12** Tiêu đề bảo mật | `curl -sI https://TEN-MIEN/login` | Có `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, `Content-Security-Policy`, và `Strict-Transport-Security` (chỉ khi qua HTTPS). Chụp kết quả. Tùy chọn: https://securityheaders.com |

> **Hạn chế cần nêu trung thực:** CSP hiện cho phép `'unsafe-inline'` và `'unsafe-eval'` vì giao diện nạp Tailwind từ CDN (`cdn.tailwindcss.com`). Ghi vào mục 6.6 là hạn chế còn tồn tại; hướng khắc phục là biên dịch Tailwind bằng Vite để siết `script-src 'self'`.

---

## 4. Hiệu năng (Mục 5.4, Bảng 5.5, Hình 5.1)

Yêu cầu: bảng đo trước/sau trên **tối thiểu 5 điểm cuối**, nêu công cụ, số lần đo, điều kiện đo.

```bash
# 1) Nạp dữ liệu lớn (giả lập). Đổi số lượng bằng BULK_ROWS
docker compose exec -e BULK_ROWS=20000 app php artisan db:seed --class=BulkDemoSeeder

# 2) SAU (có chỉ mục + cache): đo 30 lần mỗi điểm cuối, xóa cache giữa các lần để đo "lạnh"
docker compose exec app php artisan perf:benchmark --runs=30 --cold --markdown

# 3) TRƯỚC: bỏ các chỉ mục (migration 2026_11_08_000008) rồi đo lại với cùng điều kiện
docker compose exec app php artisan migrate:rollback --step=1
docker compose exec app php artisan perf:benchmark --runs=30 --cold --markdown

# 4) Khôi phục chỉ mục
docker compose exec app php artisan migrate
```

Lệnh in bảng gồm: min / trung vị / p95 / max (ms) và **số truy vấn SQL** của từng điểm cuối, kèm quy mô dữ liệu và loại CSDL. Dán bảng Markdown vào Bảng 5.5; vẽ Hình 5.1 từ cột trung vị.

**Kế hoạch thực thi (EXPLAIN):**

```bash
docker compose exec -T mysql mysql -u itam -p itam_helpdesk -t < docs/perf/explain.sql
```

Chạy hai lần (trước = đã rollback chỉ mục, sau = đã migrate) và chụp cột `type`, `key`, `rows`. Quan sát cần thấy: truy vấn 1 và 2 chuyển từ `ALL` sang `ref`/`range` có `key` là `idx_tickets_*`. Truy vấn 5 (quét SLA) có thể vẫn `ALL` nếu phần lớn phiếu thỏa điều kiện: **hãy ghi đúng điều quan sát được**.

**Bộ nhớ đệm:** `TicketRepository::countByStatus()` và `AssetRepository::countByStatus()` được lưu đệm 60 giây (khóa `tickets.status_counts`, `assets.status_counts`) và bị xóa ngay khi có thao tác ghi. Để minh họa: đo `GET /` với `--cold` (xóa cache) và không có `--cold` (cache ấm) rồi so sánh số truy vấn SQL.

**Khắc phục truy vấn lặp (N+1):** `perf:benchmark` hiển thị số truy vấn; danh sách phiếu dùng eager loading nên không tăng theo số dòng. Có thể chứng minh bằng cách đo `GET /api/v1/tickets` với 10 và 100 dòng/trang: số truy vấn không đổi.

> Số đo phụ thuộc máy; **không** chép số của người khác. Ghi rõ cấu hình máy, phiên bản MySQL, số bản ghi.

---

## 5. Khả năng truy cập và SEO (Mục 5.5)

1. **Lighthouse** (Chrome DevTools → Lighthouse → Accessibility + SEO + Best Practices): chạy trên `/`, `/cam-ket-sla`, `/huong-dan`, `/login` (trang công khai) và chụp điểm. Lưu báo cáo (nút Save as HTML/JSON).
2. **axe DevTools** (tiện ích trình duyệt): quét `/login` và một trang sau đăng nhập; chụp danh sách lỗi còn lại (nếu có, ghi vào hạn chế).
3. **Bàn phím:** chỉ dùng `Tab`, `Shift+Tab`, `Enter`, `Space`: đăng nhập, tạo phiếu, mở chi tiết, bình luận. Kiểm tra: có chỉ báo tiêu điểm (viền xanh), liên kết "Bỏ qua điều hướng" xuất hiện khi nhấn Tab đầu tiên.
4. **Độ tương phản:** dùng tiện ích chọn màu của DevTools hoặc https://webaim.org/resources/contrastchecker/ với các cặp màu chữ-nền chính (tỷ lệ tối thiểu 4,5:1 cho chữ thường).
5. **Di động:** DevTools → bật chế độ thiết bị (iPhone SE, Pixel) → kiểm tra các trang chính không vỡ bố cục.
6. **Siêu dữ liệu:** xem nguồn trang công khai: mỗi trang có `<title>` và `<meta name="description">` riêng, `canonical`, một thẻ `<h1>`; `/sitemap.xml` và `/robots.txt` mở được.

> Giao diện nạp Tailwind và Font Awesome từ CDN: khi chấm cần có Internet. Nên có phương án dự phòng (ghi ở mục 6.6).

---

## 6. Kiểm thử API (Mục 6.4, Hình 6.1)

**Postman:** nhập `docs/ITAM-Helpdesk.postman_collection.json`, đặt biến `baseUrl` (cục bộ `http://localhost:8000/api/v1`, trực tuyến `https://TEN-MIEN/api/v1`), bấm **Run collection** và chụp kết quả (21 request, 9 kiểm tra). Lưu ý: đăng nhập giới hạn 5 lần/phút/IP; nếu chạy liên tiếp nhiều lần gặp 429, chờ 1 phút.

**Dòng lệnh (newman):**

```bash
npx newman run docs/ITAM-Helpdesk.postman_collection.json --env-var "baseUrl=http://localhost:8000/api/v1"
```

**Swagger Editor:** mở https://editor.swagger.io → File → Import file → `docs/openapi.yaml`. Không có lỗi đỏ; thử một vài điểm cuối bằng "Try it out" (đặt Bearer token ở nút Authorize). Chụp màn hình. Đây là bước cần làm vì `openapi.yaml` được biên soạn từ routes, chưa có công cụ nào kiểm tra tự động trong repo.

**Mã trạng thái cần thể hiện:** 200, 201, 401 (thiếu/sai token), 403 (sai vai trò), 404 (không tồn tại), 422 (dữ liệu sai), 429 (vượt giới hạn).

---

## 7. Quản lý mã nguồn (Mục 4.6, Bảng 4.6, Hình 4.2)

```bash
git shortlog -sn --all                      # số commit mỗi người
# dòng thêm/xóa mỗi người:
git log --all --format='AUTHOR:%an' --numstat | awk '/^AUTHOR:/{a=substr($0,8)} NF==3 && $1 ~ /^[0-9]+$/ {add[a]+=$1; del[a]+=$2} END{for(k in add) printf "%-25s +%d  -%d\n", k, add[k], del[k]}'
```

Chụp thêm GitHub → Insights → Contributors. Nếu nhiều thành viên dùng tên/email Git khác nhau cho cùng một người, gộp bằng tệp `.mailmap`. Đối chiếu số liệu với Phiếu phân công: thành viên không có dấu vết đóng góp sẽ bị đánh giá riêng.

---

## 8. Bảng đối chiếu: ô cần điền ↔ cách lấy bằng chứng

| Ô trong báo cáo | Lấy ở mục |
|---|---|
| 6.2 (php artisan test, coverage) | 1 |
| TC-34/35/36, ca kiểm thử mới | 2 |
| 5.3 (BM1-BM12) | 3 |
| 5.4 (Bảng 5.5, Hình 5.1) | 4 |
| 5.5 (Lighthouse/axe, bàn phím, di động) | 5 |
| Hình 6.1 (Postman), Phụ lục C (OpenAPI) | 6 |
| 4.6 (Bảng 4.6, Hình 4.2) | 7 |
| 4.5 (URL HTTPS, hạn mức hosting) | `docs/TRIEN_KHAI_TRUC_TUYEN.md` |

Sau khi cập nhật báo cáo trong Word: `Ctrl+A` rồi `F9` để cập nhật mục lục, danh mục hình, danh mục bảng; xuất lại PDF với "Create bookmarks using headings".
