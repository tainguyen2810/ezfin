<?php

namespace App\Modules\Personal\Notifications;

use App\Modules\Core\Actions\NotificationCenter;
use App\Modules\Core\Notifications\Channel;
use App\Modules\Personal\Support\PersonalNotifications;
use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Thư xác nhận email sau khi đăng ký. Gửi qua hàng đợi để lỗi SMTP không làm hỏng
 * việc đăng ký. Khách vẫn dùng ứng dụng ngay, chưa xác nhận chỉ thấy dòng nhắc.
 */
class VerifyEmail extends BaseVerifyEmail implements ShouldQueue
{
    use Queueable;

    public function __construct()
    {
        $this->afterCommit();
    }

    private string $name = '';

    public function toMail($notifiable)
    {
        $this->name = (string) ($notifiable->name ?? '');

        return parent::toMail($notifiable);
    }

    /** Chữ theo mẫu trong trang quản trị Thông báo; link và câu hiệu lực cố định. */
    protected function buildMailMessage($url): MailMessage
    {
        $minutes = config('auth.verification.expire', 60);
        $copy = app(NotificationCenter::class)->render(PersonalNotifications::VERIFY_EMAIL, Channel::Mail, ['ten' => $this->name, 'so_phut_hieu_luc' => $minutes]);

        $mail = (new MailMessage)->subject($copy->subject);

        if ($copy->greeting !== '') {
            $mail->greeting($copy->greeting);
        }

        return $mail->line($copy->bodyHtml())
            ->action($copy->actionLabel, $url)
            ->line('Link có hiệu lực '.$minutes.' phút. Nếu bạn không tạo tài khoản, hãy bỏ qua thư này.')
            ->salutation('EzFin');
    }
}
