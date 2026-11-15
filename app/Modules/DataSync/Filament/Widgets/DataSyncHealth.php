<?php

namespace App\Modules\DataSync\Filament\Widgets;

use App\Modules\DataSync\Enums\DraftStatus;
use App\Modules\DataSync\Enums\SourceHealth;
use App\Modules\DataSync\Filament\Resources\SyncDrafts\SyncDraftResource;
use App\Modules\DataSync\Filament\Resources\SyncSources\SyncSourceResource;
use App\Modules\DataSync\Models\SyncDraft;
use App\Modules\DataSync\Models\SyncSource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Bảng điều khiển: số nguồn lỗi/quá hạn và số bản chờ duyệt, để không bỏ sót cảnh báo. */
class DataSyncHealth extends StatsOverviewWidget
{
    protected static ?int $sort = -10;

    protected ?string $heading = 'Dữ liệu tự động';

    public static function canView(): bool
    {
        return SyncSourceResource::canViewAny();
    }

    protected function getStats(): array
    {
        $sources = SyncSource::query()->where('is_enabled', true)->get();
        $broken = $sources->filter(fn (SyncSource $source) => $source->health()->needsAttention());
        $ok = $sources->filter(fn (SyncSource $source) => $source->health() === SourceHealth::Ok)->count();
        $pending = SyncDraft::query()->where('status', DraftStatus::Pending)->count();

        return [
            Stat::make('Nguồn lỗi hoặc quá hạn', (string) $broken->count())
                ->description($broken->isEmpty() ? 'Không có nguồn nào cần sửa' : $broken->pluck('name')->implode(', '))
                ->color($broken->isEmpty() ? 'success' : 'danger')
                ->url(SyncSourceResource::getUrl('index')),
            Stat::make('Nguồn hoạt động tốt', $ok.'/'.$sources->count())
                ->url(SyncSourceResource::getUrl('index')),
            Stat::make('Chờ duyệt', (string) $pending)
                ->description($pending > 0 ? 'Bấm để xem và duyệt' : 'Không có dữ liệu chờ duyệt')
                ->color($pending > 0 ? 'warning' : 'gray')
                ->url(SyncDraftResource::getUrl('index')),
        ];
    }
}
