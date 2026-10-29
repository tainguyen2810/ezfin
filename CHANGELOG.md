# Changelog

Ghi các thay đổi quan trọng theo phase/task. Mới nhất ở trên.

## Chưa phát hành

### Phase 1 — Task 9: Trang chi tiết thẻ tín dụng

- Trang `ezfin.vn/the-tin-dung/<tên-thẻ>`: thông số đầy đủ, ưu điểm, hạn chế, nguồn, ngày cập nhật, thêm vào so sánh.
- Gợi ý tối đa 3 thẻ tương tự theo nhóm lợi ích và mức phí, không theo đối tác.
- Dữ liệu có cấu trúc schema.org (CreditCard, BreadcrumbList) và URL chuẩn (canonical) cho máy tìm kiếm; không có đánh giá sao.
- Danh sách và bảng so sánh có link tới trang chi tiết.

### Phase 1 — Task 8: So sánh thẻ tín dụng

- Chọn tối đa 3 thẻ trên danh sách, lựa chọn giữ nguyên khi đổi bộ lọc hoặc trang.
- Trang `ezfin.vn/the-tin-dung/so-sanh`: bảng so sánh phí, thu nhập, lãi suất, miễn lãi, hoàn tiền, phí ngoại tệ, lợi ích, ưu điểm, hạn chế; đánh dấu giá trị tốt nhất theo con số đã công bố, kèm nguồn và ngày xác minh từng thẻ.
- Link so sánh chia sẻ được; trang không cho máy tìm kiếm lập chỉ mục.

### Phase 1 — Task 7: Danh sách thẻ tín dụng

- Trang `ezfin.vn/the-tin-dung`: lọc theo ngân hàng, phí thường niên, nhu cầu, thu nhập; sắp xếp theo tiêu chí người dùng chọn, không theo đối tác.
- Bộ lọc lưu trên URL dạng gọn, chia sẻ được; trang đã lọc không cho máy tìm kiếm lập chỉ mục.
- Mỗi thẻ hiển thị ngày cập nhật, nguồn và cảnh báo khi dữ liệu đến hạn kiểm tra lại.

### Phase 1 — Task 6: Quản lý thẻ tín dụng

- Bảng sản phẩm tài chính dùng chung và bảng thông số thẻ tín dụng (tiền DECIMAL(18,2), tỷ lệ DECIMAL(7,4)).
- Màn hình quản trị thẻ tín dụng: phí, lãi suất, điều kiện thu nhập, hoàn tiền, nhóm lợi ích, ưu điểm, hạn chế, ảnh thẻ.
- Không xuất bản được khi thiếu nguồn chính thức hoặc ngày xác minh; tự hẹn kiểm tra lại sau 3 tháng và đánh dấu thẻ đến hạn.
- Không xóa được ngân hàng còn sản phẩm.

### Phase 1 — Task 5: Ngân hàng và tổ chức tài chính

- Quản trị danh sách ngân hàng, công ty tài chính: tên ngắn, tên đầy đủ, slug duy nhất, logo, website chính thức, trạng thái hoạt động.
- Upload logo PNG/JPG/WebP tối đa 1 MB; file cũ tự xóa khi thay logo hoặc xóa tổ chức.
- Vận hành dữ liệu được nhập liệu, Biên tập chỉ xem, chỉ Quản trị cao nhất được xóa. Mọi thay đổi ghi nhật ký.
- File tải lên dùng đường dẫn `/storage` tương đối để hiển thị đúng trên cả hai tên miền.

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
