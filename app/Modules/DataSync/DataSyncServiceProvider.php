<?php

namespace App\Modules\DataSync;

use App\Modules\Core\Notifications\NotificationCatalog;
use App\Modules\DataSync\Console\ProbeCommand;
use App\Modules\DataSync\Console\SyncCommand;
use App\Modules\DataSync\Models\SyncDraft;
use App\Modules\DataSync\Models\SyncSource;
use App\Modules\DataSync\Policies\DataSyncPolicy;
use App\Modules\DataSync\Support\AlertTypes;
use App\Modules\DataSync\Support\SourceRegistry;
use App\Support\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;

/**
 * Lấy dữ liệu tự động từ trang chính thức của ngân hàng (lãi suất...). Dữ liệu lấy về là bản nháp, quản trị viên duyệt
 * mới ghi vào bảng sản phẩm. Nguồn lỗi (bị chặn, đổi giao diện, số vô lý) hoặc quá hạn được báo qua chuông thông báo
 * trong trang quản trị và email. Tôn trọng robots.txt, giãn cách các lần gọi, user-agent chung không có thông tin cá nhân.
 */
class DataSyncServiceProvider extends ModuleServiceProvider
{
    protected ?string $feature = 'data_sync';

    public function register(): void
    {
        $this->app->singleton(SourceRegistry::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(SyncSource::class, DataSyncPolicy::class);
        Gate::policy(SyncDraft::class, DataSyncPolicy::class);

        $this->app->make(NotificationCatalog::class)->register(...AlertTypes::all());

        if ($this->app->runningInConsole()) {
            $this->commands([SyncCommand::class, ProbeCommand::class]);
        }
    }
}
