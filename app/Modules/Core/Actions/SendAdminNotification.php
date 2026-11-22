<?php

namespace App\Modules\Core\Actions;

use App\Models\User;
use App\Modules\Core\Models\NotificationLog;
use App\Modules\Core\Notifications\Audience;
use App\Modules\Core\Notifications\Channel;
use App\Modules\Core\Notifications\TemplatedMail;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as Notifier;
use InvalidArgumentException;
use Throwable;

/**
 * Gửi một thông báo cho quản trị viên theo cài đặt trong trang Thông báo: kênh đang bật, vai trò nhận, email nhận thêm,
 * nội dung từ mẫu. Lỗi gửi email không làm hỏng việc đang chạy, chỉ ghi log và nhật ký gửi.
 */
class SendAdminNotification
{
    public function __construct(private readonly NotificationCenter $center) {}

    /**
     * @param  array<string, scalar|null>  $variables
     * @param  string  $status  Màu trên chuông: info, success, warning, danger.
     * @return bool Có gửi ít nhất một kênh.
     */
    public function handle(
        string $key,
        array $variables,
        ?string $actionUrl = null,
        string $status = 'info',
        ?string $icon = null,
    ): bool {
        $type = $this->center->type($key);

        if ($type->audience !== Audience::Admin) {
            throw new InvalidArgumentException("$key không phải thông báo quản trị");
        }

        $users = $this->recipients($key);
        $sent = false;

        foreach ($this->center->channels($key) as $channel) {
            $message = $this->center->render($key, $channel, $variables);

            if ($channel === Channel::Database) {
                if ($users->isEmpty()) {
                    continue;
                }

                $notification = Notification::make()->title($message->subject)->body($message->body)->status($status);

                if ($icon !== null) {
                    $notification->icon($icon);
                }

                if ($actionUrl !== null && $message->actionLabel !== '') {
                    $notification->actions([Action::make('open')->label($message->actionLabel)->url($actionUrl)]);
                }

                $notification->sendToDatabase($users);
                $this->log($key, $channel, $users->count(), null);
                $sent = true;

                continue;
            }

            $extra = $this->center->extraEmails($key);
            $count = $users->count() + count($extra);

            if ($count === 0) {
                continue;
            }

            try {
                $mail = new TemplatedMail($message, $actionUrl);
                Notifier::send($users, $mail);

                if ($extra !== []) {
                    Notifier::route('mail', $extra)->notify($mail);
                }

                $this->log($key, $channel, $count, null);
                $sent = true;
            } catch (Throwable $e) {
                Log::warning('Admin notification email failed', ['key' => $key, 'error' => $e->getMessage()]);
                $this->log($key, $channel, $count, $e->getMessage());
            }
        }

        return $sent;
    }

    /** @return Collection<int, User> */
    public function recipients(string $key): Collection
    {
        $roles = $this->center->recipientRoles($key);

        if ($roles === []) {
            return new Collection;
        }

        return User::query()->whereHas('roles', fn ($query) => $query->whereIn('name', $roles))->get();
    }

    private function log(string $key, Channel $channel, int $recipients, ?string $error): void
    {
        NotificationLog::record($key, $channel, $recipients, $error);
    }
}
