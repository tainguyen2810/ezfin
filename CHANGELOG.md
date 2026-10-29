# Changelog

Ghi các thay đổi quan trọng theo phase/task. Mới nhất ở trên.

## Chưa phát hành

### Phase 1 — Task 4: Quản trị, phân quyền, 2FA, nhật ký

- Trang quản trị Filament tại `app.ezfin.vn/<EZFIN_ADMIN_PATH>`, giao diện tiếng Việt, font Be Vietnam Pro, noindex.
- Vai trò nội bộ: Quản trị cao nhất, Biên tập nội dung, Vận hành dữ liệu, Đối tác và tài chính. Tài khoản không có vai trò bị từ chối (403).
- Bắt buộc xác thực 2 lớp bằng ứng dụng (Google Authenticator...), có mã khôi phục.
- Khóa tạm sau 5 lần đăng nhập sai trong 1 phút.
- Nhật ký hoạt động chỉ đọc: tạo/sửa/xóa, đăng nhập, đăng nhập sai, đăng xuất, bị khóa. Không ghi mật khẩu hay khóa 2FA.
- Lệnh `php artisan ezfin:admin` tạo tài khoản quản trị đầu tiên.

### Phase 1 — Task 3: Hệ thống giao diện

- Design token navy/xanh dương/xanh lá, font Be Vietnam Pro tự host, Alpine.js.
- Component dùng chung: nút, ô nhập, ô nhập tiền, select, toggle, badge, card, thẻ số liệu, thanh tiến độ, trạng thái tải/trống/lỗi, alert, modal, toast, tab, breadcrumb, bảng sắp xếp, nguồn dữ liệu, công bố liên kết, thẻ sản phẩm, khay so sánh, bộ lọc.
- Layout website công khai và ứng dụng khách hàng; trang mẫu `/_ui` (không có trên production).
- CI thêm kiểm tra giao diện Playwright ở 360/768/1280px.

### Phase 1 — Task 2: Module, tên miền, feature flag

- Modular monolith: module trong `app/Modules/<Tên>`, đăng ký qua `config/ezfin.php`.
- Route theo tên miền: `routes/public.php` → `ezfin.vn`, `routes/app.php` → `app.ezfin.vn` (noindex).
- Laravel Pennant làm công tắc module toàn hệ thống; module tắt → 404 và ẩn khỏi menu. Lệnh `php artisan ezfin:feature`.
- Markets, chứng khoán, quỹ, Personal, Pro mặc định tắt.

### Phase 1 — Task 1: Khởi tạo dự án

- Khởi tạo Laravel 13 trên PHP 8.3, khóa platform PHP 8.3 trong composer.
- Locale `vi`, múi giờ `Asia/Ho_Chi_Minh`, `.env.example` cho WAMP/MySQL.
- Chuyển test sang Pest; CI GitHub Actions chạy Pint + Pest trên PHP 8.3 và 8.4 với MySQL 8.
- Trang tạm "đang xây dựng" (noindex) thay trang welcome mặc định.
- Thêm `CLAUDE.md`, `TASKS.md`, hướng dẫn cài WAMP và triển khai/nâng cấp trên aaPanel.
