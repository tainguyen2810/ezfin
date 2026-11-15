<?php

namespace App\Modules\DataSync\Filament\Resources\SyncDrafts\Pages;

use App\Modules\DataSync\Actions\ApproveDraft;
use App\Modules\DataSync\Enums\DraftStatus;
use App\Modules\DataSync\Filament\Resources\SyncDrafts\SyncDraftResource;
use App\Modules\DataSync\Models\SyncDraft;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListSyncDrafts extends ListRecords
{
    protected static string $resource = SyncDraftResource::class;

    protected function getHeaderActions(): array
    {
        $pending = fn () => SyncDraft::query()->where('status', DraftStatus::Pending)->count();

        return [
            Action::make('approveAll')->label(fn () => 'Duyệt tất cả ('.$pending().')')->icon(Heroicon::OutlinedCheckBadge)->color('success')
                ->visible(fn () => SyncDraftResource::canEditAny())
                ->requiresConfirmation()
                ->modalHeading('Duyệt tất cả dữ liệu đang chờ?')
                ->modalDescription('Toàn bộ bản đang chờ duyệt (hồ sơ ngân hàng, thẻ, khoản vay, lãi suất...) sẽ được ghi vào dữ liệu sản phẩm và hiện trên website. Nên xem qua các bản có cảnh báo trước. Bản nào lỗi sẽ được giữ lại.')
                ->action(fn (ApproveDraft $approve) => SyncDraftResource::notifyBulk(
                    $approve->handleMany(SyncDraft::query()->with('source')->where('status', DraftStatus::Pending)->get(), auth()->user())
                )),
        ];
    }
}
