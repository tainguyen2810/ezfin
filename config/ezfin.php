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
