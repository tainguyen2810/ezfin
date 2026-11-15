<?php

namespace App\Modules\DataSync\Support;

use App\Models\User;
use App\Modules\Core\Enums\Role;
use App\Modules\DataSync\Filament\Resources\SyncDrafts\SyncDraftResource;
use App\Modules\DataSync\Filament\Resources\SyncSources\SyncSourceResource;
use App\Modules\DataSync\Models\SyncDraft;
use App\Modules\DataSync\Models\SyncSource;
use App\Modules\DataSync\Notifications\SourceAlert;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as Notifier;
use Illuminate\Support\Str;
use Throwable;

/**
 * Báo cho quản trị viên (quản trị cao nhất, vận hành dữ liệu): chuông thông báo trong trang quản trị luôn hoạt động;
 * email gửi thêm khi nguồn lỗi/quá hạn/chạy lại được (lỗi gửi email không làm hỏng lần chạy, chỉ ghi log).
 * Nguồn lỗi liên tục chỉ nhắc lại sau REPEAT_HOURS để không spam.
 */
class AdminAlerts
{
    public const REPEAT_HOURS = 24;

    public function failed(SyncSource $source): void
    {
        if ($source->last_alerted_at !== null && $source->last_alerted_at->gt(now()->subHours(self::REPEAT_HOURS))) {
            return;
        }

        $this->send(
            $source,
            "Không lấy được dữ liệu: {$source->name} ({$source->kind->getLabel()})",
            Str::limit((string) $source->last_error, 500)." (lỗi liên tiếp {$source->consecutive_failures} lần)",
            'danger',
        );
    }

    public function stale(SyncSource $source): void
    {
        if ($source->last_alerted_at !== null && $source->last_alerted_at->gt(now()->subHours(self::REPEAT_HOURS))) {
            return;
        }

        $since = $source->last_success_at ? 'lần thành công cuối lúc '.$source->last_success_at->format('H:i d/m/Y') : 'chưa thành công lần nào';
        $this->send($source, "Quá hạn chưa cập nhật: {$source->name} ({$source->kind->getLabel()})",
            "Đã quá {$source->kind->staleAfterHours()} giờ chưa lấy được dữ liệu mới ({$since}). Kiểm tra lịch chạy (cron) hoặc nguồn.", 'warning');
    }

    public function recovered(SyncSource $source): void
    {
        if ($source->last_alerted_at === null) {
            return;
        }

        $this->send($source, "Đã lấy lại được dữ liệu: {$source->name}", 'Nguồn hoạt động bình thường trở lại.', 'success');
        $source->forceFill(['last_alerted_at' => null])->save();
    }

    /** Chỉ chuông thông báo (không email) để quản trị viên vào duyệt. */
    public function draftWaiting(SyncSource $source, SyncDraft $draft): void
    {
        $warnings = $draft->warnings ? ' Có '.count($draft->warnings).' cảnh báo cần xem kỹ.' : '';

        Notification::make()
            ->title("Dữ liệu mới chờ duyệt: {$source->name}")
            ->body($source->kind->getLabel().'.'.$warnings)
            ->icon('heroicon-o-inbox-arrow-down')
            ->color($draft->warnings ? 'warning' : 'info')
            ->actions([Action::make('review')->label('Xem và duyệt')->url(SyncDraftResource::getUrl('view', ['record' => $draft]))])
            ->sendToDatabase($this->recipients());
    }

    private function send(SyncSource $source, string $title, string $body, string $color): void
    {
        $recipients = $this->recipients();
        $url = SyncSourceResource::getUrl('index');

        Notification::make()
            ->title($title)
            ->body($body)
            ->status($color)
            ->actions([Action::make('open')->label('Xem nguồn dữ liệu')->url($url)])
            ->sendToDatabase($recipients);

        try {
            Notifier::send($recipients, new SourceAlert($title, $body, $url));
        } catch (Throwable $e) {
            Log::warning('Data sync alert email failed', ['source' => $source->key, 'error' => $e->getMessage()]);
        }

        if ($color !== 'success') {
            $source->forceFill(['last_alerted_at' => now()])->save();
        }
    }

    /** @return Collection<int, User> */
    public function recipients(): Collection
    {
        return User::query()->whereHas('roles', fn ($query) => $query->whereIn('name', [Role::SuperAdmin->value, Role::DataOperator->value]))->get();
    }
}
