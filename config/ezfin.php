<?php

use App\Modules\Compare\CompareServiceProvider;
use App\Modules\Content\ContentServiceProvider;
use App\Modules\Core\CoreServiceProvider;
use App\Modules\DataSync\DataSyncServiceProvider;
use App\Modules\Markets\MarketsServiceProvider;
use App\Modules\Newsletter\NewsletterServiceProvider;
use App\Modules\Partner\PartnerServiceProvider;
use App\Modules\Personal\PersonalServiceProvider;
use App\Modules\Planner\PlannerServiceProvider;

return [

    /*
    | Tên miền. Một website duy nhất: trang công khai, trang khách hàng (/dang-nhap,
    | /ung-dung/...) và trang quản trị ở đường dẫn bí mật đều nằm trên tên miền chính.
    | `legacy_app`: tên miền phụ cũ (app.ezfin.vn), mọi URL ở đó chuyển 301 về tên miền
    | chính. Để trống nếu không cần.
    */
    'domains' => [
        'public' => env('EZFIN_PUBLIC_DOMAIN', 'ezfin.test'),
        'legacy_app' => env('EZFIN_APP_DOMAIN') ?: null,
    ],

    /** Tiền tố đường dẫn của ứng dụng quản lý tiền (ví, giao dịch...). */
    'app_prefix' => 'ung-dung',

    'admin_path' => env('EZFIN_ADMIN_PATH', 'quan-tri'),

    /*
    | Bắt buộc mọi quản trị viên dùng xác thực 2 lớp. Mặc định tắt ở mọi môi trường:
    | 2FA là tùy chọn, mỗi người tự bật trong trang Hồ sơ. Đặt EZFIN_ADMIN_2FA=true để bắt buộc.
    */
    'admin_2fa' => (bool) env('EZFIN_ADMIN_2FA', false),

    /*
    | Cho phép công cụ tìm kiếm lập chỉ mục website công khai. Chỉ bật trên production;
    | staging và máy dev luôn noindex (robots.txt chặn toàn bộ, mọi trang có meta noindex).
    */
    'indexable' => (bool) env('EZFIN_INDEXABLE', false),

    /*
    | Thông tin chủ sở hữu website, hiện ở chân trang và trang Liên hệ. Ô nào để trống thì không hiện
    | (không dùng thông tin mẫu).
    */
    'company' => [
        'name' => env('EZFIN_COMPANY_NAME'),
        'registration' => env('EZFIN_COMPANY_REGISTRATION'),
        'address' => env('EZFIN_COMPANY_ADDRESS'),
        'email' => env('EZFIN_CONTACT_EMAIL'),
        'phone' => env('EZFIN_CONTACT_PHONE'),
    ],

    /*
    | Đối tác: khóa bí mật cho URL postback chuyển đổi (/postback/{mạng}?token=...). Để trống = tắt postback.
    */
    'partner' => [
        'postback_token' => env('EZFIN_POSTBACK_TOKEN'),
    ],

    /*
    | Lấy dữ liệu tự động: thời gian chờ mỗi request (giây), khoảng nghỉ giữa hai lần gọi cùng một máy chủ (ms).
    | User-agent cố định "EzFinBot/1.0 (+APP_URL)", không chứa thông tin cá nhân.
    */
    'data_sync' => [
        'timeout' => (int) env('EZFIN_SYNC_TIMEOUT', 20),
        'delay_ms' => (int) env('EZFIN_SYNC_DELAY_MS', 1500),
    ],

    /*
    | Module đang cài. Thứ tự là thứ tự đăng ký; Core luôn đứng đầu.
    */
    'modules' => [
        CoreServiceProvider::class,
        CompareServiceProvider::class,
        PlannerServiceProvider::class,
        ContentServiceProvider::class,
        PartnerServiceProvider::class,
        PersonalServiceProvider::class,
        NewsletterServiceProvider::class,
        MarketsServiceProvider::class,
        DataSyncServiceProvider::class,
    ],

    /*
    | Giá trị mặc định của feature flag (bật/tắt toàn hệ thống). Sau lần kiểm tra
    | đầu tiên giá trị được lưu trong bảng `features`; đổi bằng
    | `php artisan ezfin:feature <tên> on|off`.
    */
    'features' => [
        'compare' => true,
        'planner' => true,
        'content' => true,
        'partner' => true,
        'markets' => true,
        'stocks' => false,
        'funds' => false,
        // Ứng dụng quản lý tiền: miễn phí cho mọi tài khoản đã đăng ký.
        'personal' => true,
        'newsletter' => true,
        // Lấy dữ liệu tự động (lãi suất...) từ trang chính thức, lưu bản nháp chờ duyệt.
        'data_sync' => true,
    ],

];
