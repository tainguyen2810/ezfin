# EzFin — quy tắc dự án

EzFin.vn là nền tảng tài chính cá nhân cho thị trường Việt Nam: so sánh sản phẩm tài chính, công cụ tính toán, ứng dụng quản lý tiền, affiliate. Không phải blog có gắn dashboard.

Tài liệu kiến trúc Phase 0 (đã duyệt 29/10/2026): https://claude.ai/artifact/2zAYuFAd5DGJTuzPQjuRVd
Tiến độ và tiêu chí nghiệm thu: `TASKS.md`. Thay đổi quan trọng: `CHANGELOG.md`.

## Công nghệ

- Laravel 13, PHP 8.3 (production). `composer.json` khóa `config.platform.php = 8.3.0`; CI chạy PHP 8.3 và 8.4 để nâng cấp an toàn.
- MySQL 8 / MariaDB 10.11+ (production và CI). Test local dùng SQLite in-memory.
- Blade + Tailwind CSS 4 + Alpine.js, build bằng Vite. Admin: Filament. Phân quyền: spatie/laravel-permission. Feature flag: Laravel Pennant.
- Queue và cache: driver `database`. Email: SMTP Brevo.
- Test: Pest (nghiệp vụ), Playwright (giao diện mobile/desktop).

## Kiến trúc

- Modular monolith: mỗi module trong `app/Modules/<Tên>` (Models, Actions, Http, Policies, routes, views, migrations, tests), đăng ký qua ServiceProvider, bật/tắt bằng feature flag.
- Module chỉ gọi nhau qua Action/Service công khai, không đọc thẳng bảng của module khác.
- Một codebase cho `ezfin.vn` (public, SEO) và `app.ezfin.vn` (ứng dụng, noindex). Admin ở đường dẫn bí mật trên `app` + 2FA.
- Chỉ tạo schema cho phase hiện tại. Tiền `DECIMAL(18,2)`, lãi suất `DECIMAL(7,4)`; không dùng float cho tiền.

## Nguyên tắc sản phẩm (bắt buộc)

- Không nút giả, dữ liệu giả, đánh giá sao không có thật, dữ liệu "realtime" giả lập.
- Sản phẩm tài chính chỉ xuất bản khi có nguồn và ngày xác minh. Không scrape khi chưa kiểm tra điều khoản.
- Công bố affiliate trên mọi trang có link đối tác. Không khuyến nghị đầu tư cá nhân hóa.
- Mục chưa triển khai: tắt bằng feature flag (ẩn menu, URL 404, không vào sitemap).
- Giao diện tiếng Việt, mobile-first. Tiền dạng `520.000.000 đ`, ngày `dd/mm/yyyy`.

## Chạy dự án

```sh
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate
npm run build        # hoặc npm run dev
vendor/bin/pest      # test
vendor/bin/pint      # format code
```

Cài trên WAMP: `docs/cai-dat-wamp.md`. Triển khai, backup, nâng cấp trên aaPanel: `docs/trien-khai-aapanel.md`.

## Quy trình làm việc

- Mỗi task trong `TASKS.md` = một pull request có test. Chỉ đánh dấu xong khi đạt tiêu chí nghiệm thu và test xanh.
- Không thay đổi công nghệ, database hay kiến trúc lớn mà không giải thích và được duyệt.
- Cuối mỗi phiên, ghi vào `TASKS.md` (mục Nhật ký): đã xong gì, test đã chạy, lỗi chưa giải quyết, file quan trọng đã đổi, bước tiếp theo.
