<?php

namespace App\Modules\Core\Filament\Resources\NotificationSettings\Pages;

use App\Modules\Core\Filament\Resources\NotificationSettings\NotificationSettingResource;
use App\Modules\Core\Models\NotificationSetting;
use Filament\Resources\Pages\ListRecords;

class ListNotificationSettings extends ListRecords
{
    protected static string $resource = NotificationSettingResource::class;

    protected ?string $subheading = 'Chọn kênh, người nhận và sửa nội dung từng loại thông báo. Chưa sửa gì thì hệ thống dùng nội dung mặc định.';

    public function mount(): void
    {
        NotificationSetting::syncCatalog();

        parent::mount();
    }
}
