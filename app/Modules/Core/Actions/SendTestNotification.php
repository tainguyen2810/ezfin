<?php

namespace App\Modules\Core\Actions;

use App\Models\User;
use App\Modules\Core\Models\NotificationLog;
use App\Modules\Core\Notifications\Channel;
use App\Modules\Core\Notifications\TemplatedMail;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Throwable;

/**
 * Gửi thử mẫu đang soạn (chưa lưu cũng được) cho chính người đang sửa, với dữ liệu mẫu của loại thông báo.
 * Lỗi email (SMTP chưa cấu hình...) được ném ra để màn hình báo rõ lý do.
 */
class SendTestNotification
{
    public function __construct(private readonly NotificationCenter $center) {}

    /**
     * @param  list<Channel>  $channels
     * @param  array<string, array{subject: string, greeting: string, body: string, action_label: string}>  $templates  Mẫu đang soạn theo kênh.
     */
    public function handle(string $key, User $user, array $channels, array $templates = []): void
    {
        $type = $this->center->type($key);
        $url = url('/');

        foreach ($channels as $channel) {
            if (! $type->supports($channel)) {
                continue;
            }

            $message = $this->center->render($key, $channel, $type->samples(), $templates[$channel->value] ?? null);

            if ($channel === Channel::Database) {
                Notification::make()->title('[Gửi thử] '.$message->subject)->body($message->body)->info()
                    ->actions($message->actionLabel !== '' ? [Action::make('open')->label($message->actionLabel)->url($url)] : [])
                    ->sendToDatabase($user);
                NotificationLog::record($key, $channel, 1, test: true);

                continue;
            }

            try {
                $user->notifyNow(new TemplatedMail($message, $url));
                NotificationLog::record($key, $channel, 1, test: true);
            } catch (Throwable $e) {
                NotificationLog::record($key, $channel, 1, $e->getMessage(), test: true);

                throw $e;
            }
        }
    }
}
