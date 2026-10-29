# TASKS — Phase 1: Public Website & Compare

Trạng thái: `Chưa làm` · `Đang làm` · `Chờ duyệt` (PR mở) · `Xong` (đã merge, đạt tiêu chí).

| # | Việc | Tiêu chí nghiệm thu | Trạng thái |
| --- | --- | --- | --- |
| 1 | Khởi tạo repo, Laravel, Tailwind/Vite, Pest, CLAUDE.md, TASKS.md, CHANGELOG.md, .env.example | Chạy được trên WAMP theo hướng dẫn; CI test xanh | Xong |
| 2 | Khung module, route theo tên miền (ezfin.vn / app.ezfin.vn), feature flag | Tắt flag thì menu ẩn và URL trả 404; có test | Xong |
| 3 | Design token, font Be Vietnam Pro tự host, component dùng chung, trang `/_ui` | Đủ component và trạng thái; ảnh Playwright 360/768/1280px không tràn ngang | Xong |
| 4 | Đăng nhập admin (Filament), role, 2FA, audit log | Không đủ quyền nhận 403; sửa/xóa ghi audit; giới hạn đăng nhập sai | Xong |
| 5 | Quản lý ngân hàng/tổ chức tài chính | CRUD, upload logo, slug duy nhất; test validation | Chờ duyệt |
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

### 29/10/2026 — Task 5

- Xong: bảng `financial_institutions` (module Compare: loại, tên ngắn, tên đầy đủ, slug duy nhất, logo, website, đang hoạt động), màn hình quản trị "Ngân hàng và tổ chức tài chính" (thêm, sửa, xóa, tìm kiếm, lọc theo loại/trạng thái), tự gợi ý slug không dấu khi tạo, upload logo PNG/JPG/WebP ≤ 1 MB (chặn SVG vì có thể chứa script), tự xóa file logo cũ khi thay hoặc xóa, ghi nhật ký. Quyền: Quản trị cao nhất và Vận hành dữ liệu nhập liệu, Biên tập chỉ xem, chỉ Quản trị cao nhất được xóa.
- Sửa: URL ổ `public` đổi sang đường dẫn tương đối `/storage` (đặt lại bằng `FILESYSTEM_PUBLIC_URL`). Trước đó ảnh lưu ở `ezfin.vn/storage/...` nên trang quản trị trên `app.ezfin.vn` không tải lại được logo khi sửa (lỗi CORS).
- Test đã chạy: `vendor/bin/pest` (82 test xanh, 20 test mới), `vendor/bin/pint --test`, Playwright: tạo 3 tổ chức có logo qua giao diện, thử lỗi slug trùng và website http, trang danh sách/sửa ở 390px và 1366px không tràn ngang, không lỗi JS.
- Chưa giải quyết: chặn xóa tổ chức đang có sản phẩm sẽ thêm ở task 6 (khóa ngoại `restrictOnDelete`). Chưa nhập dữ liệu ngân hàng thật: cần nguồn chính thức và ngày xác minh.
- File quan trọng: `app/Modules/Compare/{Models/FinancialInstitution.php,Enums/InstitutionType.php,Policies,Filament/Resources/FinancialInstitutions}`, `config/filesystems.php`, `tests/Feature/Compare/FinancialInstitutionAdminTest.php`.
- Tiếp theo: task 6 (quản lý sản phẩm thẻ tín dụng).

### 29/10/2026 — Task 4

- Xong: Filament 5 tại `app.ezfin.vn/<EZFIN_ADMIN_PATH>` (noindex), 4 vai trò nội bộ (Quản trị cao nhất, Biên tập nội dung, Vận hành dữ liệu, Đối tác và tài chính) bằng spatie/laravel-permission, 2FA bằng ứng dụng xác thực bắt buộc + mã khôi phục, khóa tạm sau 5 lần đăng nhập sai/phút, audit log (tạo/sửa/xóa, đăng nhập, đăng nhập sai, đăng xuất, bị khóa; không bao giờ ghi mật khẩu, khóa 2FA), màn hình quản lý người dùng và xem nhật ký (chỉ Quản trị cao nhất), lệnh `php artisan ezfin:admin`, ảnh đại diện chữ cái tạo tại chỗ (không gọi ui-avatars.com).
- Test đã chạy: `vendor/bin/pest` (62 test xanh, 28 test mới cho quản trị), `vendor/bin/pint --test`, `npm run build`, chụp Playwright màn hình đăng nhập, 2FA, người dùng, nhật ký ở 390px và 1366px (không tràn ngang, không lỗi JS).
- Chưa giải quyết: quyền chi tiết của từng vai trò (biên tập, vận hành dữ liệu, tài chính) sẽ gắn vào policy khi có module tương ứng (task 5, 6, 10, 15); hiện các vai trò này chỉ vào được bảng điều khiển. Email đặt lại mật khẩu admin chưa bật, cần SMTP Brevo (task 19).
- File quan trọng: `app/Providers/Filament/AdminPanelProvider.php`, `app/Modules/Core/{Enums/Role.php,Models/AuditLog.php,Concerns/Auditable.php,Policies,Filament,Console/CreateAdminCommand.php}`, `app/Models/User.php`, `tests/Feature/Admin/*`.
- Tiếp theo: task 5 (quản lý ngân hàng/tổ chức tài chính).

### 29/10/2026 — Task 3

- Xong: design token (Tailwind 4, biến CSS), font Be Vietnam Pro tự host, Alpine.js, 27 component Blade trong `resources/views/components/ui`, layout website (`x-layouts.public`) và ứng dụng (`x-layouts.app`, sidebar + thanh dưới có nút +), `Format` (tiền, %, ngày kiểu Việt Nam), icon Heroicons nhúng, trang mẫu `/_ui`, `/_ui/so-sanh`, `/_ui/ung-dung` (chỉ ngoài production), phân trang tiếng Việt.
- Test đã chạy: `vendor/bin/pest` (34 test xanh), `npm run test:ui` (4 trang × 360/768/1280px: không tràn ngang, không lỗi JS), thử tay ngăn kéo bộ lọc, menu mobile, modal.
- Chưa giải quyết: biểu đồ (Chart.js) để task 13; chuỗi validation tiếng Việt thêm khi làm form thật.
- File quan trọng: `resources/css/app.css`, `resources/js/app.js`, `resources/views/components/**`, `app/Support/Format.php`, `tests/browser/ui-smoke.mjs`.
- Tiếp theo: task 4 (đăng nhập admin Filament, role, 2FA, audit log).

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
