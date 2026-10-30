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
- Module mới: tạo `app/Modules/<Tên>/<Tên>ServiceProvider.php` kế thừa `App\Support\Modules\ModuleServiceProvider` (đặt `$feature`, `publicNavigation()`), thêm vào `config/ezfin.php` (`modules`, `features`). Route: `routes/public.php` (ezfin.vn) và `routes/app.php` (app.ezfin.vn, noindex). View namespace = tên module dạng kebab.
- Feature flag là công tắc toàn hệ thống (Pennant, scope null), không dùng cho quyền theo gói Free/Pro. Xem/đổi: `php artisan ezfin:feature [tên] [on|off]`.
- Module chỉ gọi nhau qua Action/Service công khai, không đọc thẳng bảng của module khác.
- Một codebase cho `ezfin.vn` (public, SEO) và `app.ezfin.vn` (ứng dụng, noindex). Admin ở đường dẫn bí mật trên `app` + 2FA (`config('ezfin.admin_2fa')`: production luôn bắt buộc, `APP_ENV=local` mặc định tắt).
- Admin (Filament 5): resource/widget đặt trong `app/Modules/<Tên>/Filament/{Resources,Widgets}`, tự được nạp. Mỗi resource có Policy theo vai trò `App\Modules\Core\Enums\Role`; đặt `$hasTitleCaseModelLabel = false`. Model nghiệp vụ dùng trait `Auditable` để ghi nhật ký. Tạo tài khoản: `php artisan ezfin:admin`, hoặc trang cài đặt `/cai-dat` (`Core\Support\Installer`, chạy ngoài nhóm middleware `web`, tự khóa khi đã có super-admin).
- Công cụ tính (`/cong-cu/<slug>`): công thức trong `app/Modules/Planner/Calculators` (tiền là số nguyên đồng, lãi suất qua `InterestMath::rateUnits`); trang là class kế thừa `App\Modules\Planner\Tools\Tool`, đăng ký trong `PlannerServiceProvider`, view `planner::tools.<slug>.form` và `.result`. Đầu vào đọc từ query string (form GET) để chạy khi tắt JS và chia sẻ được.
- Bài viết (module Content): nội dung HTML luôn lọc qua `ArticleHtml::sanitize` (gán vào `body` là tự lọc); in ra bằng `{!! !!}` chỉ với trường đã lọc. Đổi trạng thái qua `Actions\ArticleWorkflow`; model chặn người không có quyền `publish` xuất bản hoặc sửa bài đang hiển thị. Lịch sử sửa tự ghi ở `article_revisions`.
- SEO: module khai báo URL cho sitemap bằng `sitemap()` trong ServiceProvider (chỉ trang đã xuất bản, dùng scope `indexable()` của trait `HasSeoMeta`). Trang công khai truyền `:seo`, `:image`, `type` cho `x-layouts.public`; model có trang công khai dùng `HasSeoMeta` và thêm `SeoFields::section()` vào form. Đổi URL của nội dung đã xuất bản thì gọi `Core\Actions\RecordRedirect`. `EZFIN_INDEXABLE=true` chỉ trên production.
- Link đối tác: không bao giờ in URL đối tác trực tiếp; dùng `Partner\Actions\PartnerLinks` (trả rỗng khi tắt flag `partner`) lấy `$link->url()` (`/di/{mã}`), nút mang `rel="sponsored nofollow noopener"` và trang phải có `<x-ui.disclosure />`.
- Trang chủ (`Core\Http\Controllers\HomeController`) ghép dữ liệu từ module qua Action công khai, kiểm tra feature flag, ẩn mục chưa có dữ liệu. Trang pháp lý dùng `<x-core::legal-page>`, danh sách ở `CoreServiceProvider::LEGAL_PAGES` (footer, sitemap). Đổi cách xếp hạng thì sửa luôn trang Phương pháp đánh giá.
- File tải lên: ổ `public`, URL tương đối `/storage/...` (chạy được trên cả hai tên miền); cần URL tuyệt đối thì bọc `url()`. Không nhận SVG do người dùng tải lên.
- Ứng dụng quản lý tiền (module Personal, flag `personal`, app.ezfin.vn): model dữ liệu cá nhân dùng trait `Personal\Concerns\BelongsToUser` (tự lọc theo người đăng nhập, tự gán `user_id`) + Policy kiểm tra `isOwnedBy`; mỗi màn hình có test người A không xem/sửa được dữ liệu người B. Không dùng `Auditable` cho dữ liệu cá nhân. Màn hình dùng `<x-personal::shell title="...">`; thêm mục menu ở `Personal\Support\AppMenu`. Dữ liệu mẫu để xem giao diện (không chạy trên production): `php artisan ezfin:demo-data [email] [--fresh]`.
- Chỉ tạo schema cho phase hiện tại. Tiền `DECIMAL(18,2)`, lãi suất `DECIMAL(7,4)`; không dùng float cho tiền.

## Giao diện

- Dựng màn hình từ component `x-ui.*` (`resources/views/components/ui`) và layout `x-layouts.public` / `x-layouts.app`. Thiếu component thì thêm vào đó và vào trang mẫu `/_ui`, không viết lại markup rời rạc.
- Định dạng hiển thị luôn qua `Format::money()`, `Format::rate()`, `Format::date()`.
- Màu và bo góc lấy từ token trong `resources/css/app.css` (hướng "Bạc hà": `navy-*` là mực xanh đậm, `primary-*` xanh bạc hà, `highlight` xanh chanh); không viết mã màu rời trong view.
- Icon: `<x-ui.icon name="..." />`, file SVG trong `resources/svg/icons` (Heroicons outline).
- Trước khi mở PR có giao diện: `npm run build` rồi `npm run test:ui` (cần site chạy ở `ezfin.test`).

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

Cài trên WAMP: `docs/cai-dat-wamp.md`. Triển khai, staging, backup, nâng cấp trên aaPanel: `docs/trien-khai-aapanel.md`. Sau mỗi lần cài hoặc deploy: `php artisan ezfin:check`.

## Quy trình làm việc

- Mỗi task trong `TASKS.md` = một pull request có test. Chỉ đánh dấu xong khi đạt tiêu chí nghiệm thu và test xanh.
- Không thay đổi công nghệ, database hay kiến trúc lớn mà không giải thích và được duyệt.
- Cuối mỗi phiên, ghi vào `TASKS.md` (mục Nhật ký): đã xong gì, test đã chạy, lỗi chưa giải quyết, file quan trọng đã đổi, bước tiếp theo.
