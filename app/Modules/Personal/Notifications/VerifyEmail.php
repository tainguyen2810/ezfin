<?php

namespace App\Modules\Personal\Notifications;

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

    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Xác nhận email tài khoản EzFin')
            ->greeting('Chào bạn,')
            ->line('Cảm ơn bạn đã tạo tài khoản EzFin. Bấm nút dưới đây để xác nhận địa chỉ email này là của bạn.')
            ->action('Xác nhận email', $url)
            ->line('Link có hiệu lực '.config('auth.verification.expire', 60).' phút. Nếu bạn không tạo tài khoản, hãy bỏ qua thư này.')
            ->salutation('EzFin');
    }
}
