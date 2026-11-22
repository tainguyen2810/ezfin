<?php

namespace App\Modules\DataSync\Support;

use App\Modules\Core\Actions\NotificationCenter;
use App\Modules\Core\Actions\SendAdminNotification;
use App\Modules\DataSync\Filament\Resources\SyncDrafts\SyncDraftResource;
use App\Modules\DataSync\Filament\Resources\SyncSources\SyncSourceResource;
use App\Modules\DataSync\Models\SyncDraft;
use App\Modules\DataSync\Models\SyncSource;
use Illuminate\Support\Str;

/**
 * Báo cho quản trị viên khi nguồn lỗi/quá hạn/chạy lại được hoặc có dữ liệu chờ duyệt. Kênh (chuông, email), người nhận,
 * nội dung và khoảng nhắc lại chỉnh trong trang quản trị Thông báo (xem AlertTypes cho mặc định).
 * Nguồn lỗi liên tục chỉ nhắc lại sau số giờ đã cài để không spam.
 */
class AdminAlerts
{
    public function __construct(
        private readonly SendAdminNotification $sender,
        private readonly NotificationCenter $center,
    ) {}

    public function failed(SyncSource $source): void
    {
        if ($this->recentlyAlerted($source, AlertTypes::FAILED)) {
            return;
        }

        $this->send($source, AlertTypes::FAILED, 'danger', [
            'loi' => Str::limit((string) $source->last_error, 500),
            'so_lan_loi' => $source->consecutive_failures,
        ]);
    }

    public function stale(SyncSource $source): void
    {
        if ($this->recentlyAlerted($source, AlertTypes::STALE)) {
            return;
        }

        $this->send($source, AlertTypes::STALE, 'warning', [
            'so_gio' => $source->kind->staleAfterHours(),
            'lan_thanh_cong_cuoi' => $source->last_success_at ? 'lần thành công cuối lúc '.$source->last_success_at->format('H:i d/m/Y') : 'chưa thành công lần nào',
        ]);
    }

    public function recovered(SyncSource $source): void
    {
        if ($source->last_alerted_at === null) {
            return;
        }

        $this->send($source, AlertTypes::RECOVERED, 'success');
        $source->forceFill(['last_alerted_at' => null])->save();
    }

    public function draftWaiting(SyncSource $source, SyncDraft $draft): void
    {
        $warnings = count($draft->warnings ?? []);

        $this->sender->handle(
            AlertTypes::DRAFT_WAITING,
            $this->variables($source) + ['so_canh_bao' => $warnings, 'ghi_chu_canh_bao' => $warnings ? "Có $warnings cảnh báo cần xem kỹ." : ''],
            SyncDraftResource::getUrl('view', ['record' => $draft]),
            $warnings ? 'warning' : 'info',
            'heroicon-o-inbox-arrow-down',
        );
    }

    private function recentlyAlerted(SyncSource $source, string $key): bool
    {
        $hours = $this->center->repeatHours($key) ?? 24;

        return $source->last_alerted_at !== null && $source->last_alerted_at->gt(now()->subHours($hours));
    }

    /** @param  array<string, scalar|null>  $variables */
    private function send(SyncSource $source, string $key, string $status, array $variables = []): void
    {
        $sent = $this->sender->handle($key, $this->variables($source) + $variables, SyncSourceResource::getUrl('index'), $status);

        if ($sent && $status !== 'success') {
            $source->forceFill(['last_alerted_at' => now()])->save();
        }
    }

    /** @return array{ten_nguon: string, loai_du_lieu: string} */
    private function variables(SyncSource $source): array
    {
        return ['ten_nguon' => $source->name, 'loai_du_lieu' => $source->kind->getLabel()];
    }
}
