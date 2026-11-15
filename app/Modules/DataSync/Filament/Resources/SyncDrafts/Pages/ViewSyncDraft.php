<?php

namespace App\Modules\DataSync\Filament\Resources\SyncDrafts\Pages;

use App\Modules\DataSync\Actions\ApproveDraft;
use App\Modules\DataSync\Enums\DraftStatus;
use App\Modules\DataSync\Filament\Resources\SyncDrafts\SyncDraftResource;
use App\Modules\DataSync\Models\SyncDraft;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ViewSyncDraft extends ViewRecord
{
    protected static string $resource = SyncDraftResource::class;

    public function getTitle(): string
    {
        return 'Dữ liệu từ '.$this->getRecord()->source->name;
    }

    protected function getHeaderActions(): array
    {
        $canReview = fn (SyncDraft $record) => $record->status === DraftStatus::Pending && SyncDraftResource::canEdit($record);

        return [
            Action::make('approve')->label('Duyệt và ghi vào dữ liệu')->icon(Heroicon::OutlinedCheck)->color('success')
                ->visible($canReview)
                ->requiresConfirmation()
                ->modalDescription('Số liệu sẽ được ghi thành lần công bố mới và hiển thị trên website. Lần công bố cũ vẫn giữ làm lịch sử.')
                ->action(function (SyncDraft $record, ApproveDraft $approve) {
                    try {
                        $message = $approve->handle($record, auth()->user());
                    } catch (Throwable $e) {
                        Notification::make()->danger()->title('Chưa duyệt được')->body($e->getMessage())->persistent()->send();

                        return;
                    }
                    Notification::make()->success()->title('Đã duyệt')->body($message)->send();
                    $this->redirect(SyncDraftResource::getUrl('index'));
                }),
            Action::make('reject')->label('Từ chối')->icon(Heroicon::OutlinedXMark)->color('danger')
                ->visible($canReview)
                ->schema([Textarea::make('note')->label('Lý do (không bắt buộc)')->maxLength(1000)])
                ->action(function (SyncDraft $record, array $data, ApproveDraft $approve) {
                    $approve->reject($record, auth()->user(), $data['note'] ?? null);
                    Notification::make()->success()->title('Đã từ chối bản dữ liệu này')->send();
                    $this->redirect(SyncDraftResource::getUrl('index'));
                }),
        ];
    }
}
