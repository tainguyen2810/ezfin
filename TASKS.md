# TASKS — Phase 1: Public Website & Compare

Trạng thái: `Chưa làm` · `Đang làm` · `Chờ duyệt` (PR mở) · `Xong` (đã merge, đạt tiêu chí).

| # | Việc | Tiêu chí nghiệm thu | Trạng thái |
| --- | --- | --- | --- |
| 1 | Khởi tạo repo, Laravel, Tailwind/Vite, Pest, CLAUDE.md, TASKS.md, CHANGELOG.md, .env.example | Chạy được trên WAMP theo hướng dẫn; CI test xanh | Xong |
| 2 | Khung module, route theo tên miền (ezfin.vn / app.ezfin.vn), feature flag | Tắt flag thì menu ẩn và URL trả 404; có test | Chờ duyệt |
| 3 | Design token, font Be Vietnam Pro tự host, component dùng chung, trang `/_ui` | Đủ component và trạng thái; ảnh Playwright 360/768/1280px không tràn ngang | Chưa làm |
| 4 | Đăng nhập admin (Filament), role, 2FA, audit log | Không đủ quyền nhận 403; sửa/xóa ghi audit; giới hạn đăng nhập sai | Chưa làm |
| 5 | Quản lý ngân hàng/tổ chức tài chính | CRUD, upload logo, slug duy nhất; test validation | Chưa làm |
| 6 | Quản lý sản phẩm thẻ tín dụng | Không xuất bản khi thiếu nguồn hoặc ngày xác minh; có test | Chưa làm |
| 7 | Danh sách và bộ lọc thẻ (public) | Lọc theo ngân hàng, phí, nhu cầu, thu nhập; trạng thái lọc trên URL; test bộ lọc | Chưa làm |
| 8 | So sánh tối đa 3 thẻ | Khay so sánh giữ lựa chọn khi chuyển trang; đọc được trên 360px | Chưa làm |
| 9 | Trang chi tiết thẻ | Ngày cập nhật, nguồn, công bố liên kết, sản phẩm tương tự, schema hợp lệ | Chưa làm |
| 10 | Nhập lãi suất tiết kiệm: form + import CSV | Báo đúng dòng lỗi, không ghi dở dang; import lại không tạo trùng | Chưa làm |
| 11 | So sánh lãi suất theo số tiền và kỳ hạn | Xếp theo tiền lãi thực nhận; khớp bộ số liệu kiểm thử | Chưa làm |
| 12 | Tài khoản ngân hàng: danh sách, chi tiết | Như task 7 và 9 | Chưa làm |
| 13 | Bộ máy tính toán (PHP) + khung trang công cụ | Công thức có test số liệu tính tay; chạy được khi tắt JS; URL chia sẻ giữ kết quả | Chưa làm |
| 14 | 5 công cụ: lãi kép, lãi tiền gửi, tiết kiệm định kỳ, khoản vay, trả góp | Nêu giả định, kiểm tra đầu vào, bảng + biểu đồ; trả góp phân biệt lãi phẳng/dư nợ giảm dần | Chưa làm |
| 15 | CMS bài viết, chuyên mục, tác giả, duyệt bài | Biên tập viên không tự xuất bản; lưu lịch sử sửa; lọc XSS | Chưa làm |
| 16 | SEO nền: meta, canonical, sitemap, robots, breadcrumb, OG, 301 | Sitemap chỉ gồm trang đã xuất bản; app và `/di/` là noindex | Chưa làm |
| 17 | Trang chủ, footer, trang pháp lý, phương pháp đánh giá | Không nút giả, số liệu giả; mọi link hoạt động | Chưa làm |
| 18 | Chuyển hướng `/di/` + nhật ký click | Mã click không đoán được; lọc bot; rate limit; `rel="sponsored nofollow"` | Chưa làm |
| 19 | Deploy staging trên aaPanel | HTTPS, cron, queue worker, backup hằng ngày; đã khôi phục thử thành công | Chưa làm |
| 20 | Nghiệm thu Phase 1 | Toàn bộ test xanh; Lighthouse mobile ≥ 90 (trang chủ, so sánh lãi suất, lãi kép); chủ dự án duyệt trên staging | Chưa làm |

## Nhật ký phiên

### 29/10/2026 — Task 2

- Xong: lớp `ModuleServiceProvider` (tự nạp route theo tên miền, view, migration), 5 module khung (Core, Compare, Planner, Content, Partner), menu `Navigation` lọc theo route + feature flag, Pennant dùng làm công tắc toàn hệ thống, flag tắt → 404, middleware `noindex` cho tên miền ứng dụng, lệnh `php artisan ezfin:feature`.
- Test đã chạy: `vendor/bin/pest` (17 test xanh), `vendor/bin/pint --test`.
- Chưa giải quyết: không.
- File quan trọng: `config/ezfin.php`, `app/Support/Modules/ModuleServiceProvider.php`, `app/Support/Navigation/*`, `app/Providers/AppServiceProvider.php`.
- Tiếp theo: task 3 (design system, trang `/_ui`).

### 29/10/2026 — Task 1

- Xong: khung Laravel 13 (PHP 8.3), Pest, CI (PHP 8.3/8.4 + MySQL 8), locale vi, tài liệu cài đặt/triển khai/nâng cấp.
- Test đã chạy: `vendor/bin/pest` (3 test xanh, SQLite), `vendor/bin/pint --test`, `npm run build`.
- Chưa giải quyết: chưa chạy thử trên WAMP thật của chủ dự án; CI MySQL sẽ chạy khi mở PR.
- File quan trọng: `composer.json`, `config/app.php`, `.env.example`, `.github/workflows/ci.yml`, `docs/`.
- Tiếp theo: task 2 (module, route theo tên miền, feature flag).
