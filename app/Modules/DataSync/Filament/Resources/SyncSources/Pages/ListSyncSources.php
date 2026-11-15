<?php

namespace App\Modules\DataSync\Filament\Resources\SyncSources\Pages;

use App\Modules\DataSync\Filament\Resources\SyncSources\SyncSourceResource;
use App\Modules\DataSync\Support\SourceRegistry;
use Filament\Resources\Pages\ListRecords;

class ListSyncSources extends ListRecords
{
    protected static string $resource = SyncSourceResource::class;

    protected ?string $subheading = 'Hệ thống tự lấy dữ liệu từ trang chính thức theo lịch. Dữ liệu mới vào mục "Chờ duyệt"; nguồn lỗi được báo qua chuông thông báo và email.';

    public function mount(): void
    {
        parent::mount();

        // Nguồn mới thêm trong mã nguồn hiện ngay, không phải đợi lần chạy lịch kế tiếp.
        app(SourceRegistry::class)->sync();
    }
}
