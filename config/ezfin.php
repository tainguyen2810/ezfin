<?php

use App\Modules\Compare\CompareServiceProvider;
use App\Modules\Content\ContentServiceProvider;
use App\Modules\Core\CoreServiceProvider;
use App\Modules\Partner\PartnerServiceProvider;
use App\Modules\Planner\PlannerServiceProvider;

return [

    /*
    | Tên miền. Một codebase phục vụ website công khai và ứng dụng khách hàng;
    | trang quản trị nằm ở đường dẫn bí mật trên tên miền ứng dụng.
    */
    'domains' => [
        'public' => env('EZFIN_PUBLIC_DOMAIN', 'ezfin.test'),
        'app' => env('EZFIN_APP_DOMAIN', 'app.ezfin.test'),
    ],

    'admin_path' => env('EZFIN_ADMIN_PATH', 'quan-tri'),

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
    | Module đang cài. Thứ tự là thứ tự đăng ký; Core luôn đứng đầu.
    */
    'modules' => [
        CoreServiceProvider::class,
        CompareServiceProvider::class,
        PlannerServiceProvider::class,
        ContentServiceProvider::class,
        PartnerServiceProvider::class,
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
        'markets' => false,
        'stocks' => false,
        'funds' => false,
        'personal' => false,
        'pro' => false,
    ],

];
