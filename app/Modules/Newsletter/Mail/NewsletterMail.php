<?php

namespace App\Modules\Newsletter\Mail;

use App\Modules\Newsletter\Models\Subscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * Thư gửi cho người đã đăng ký: luôn có header List-Unsubscribe (RFC 8058, hủy một chạm từ hộp thư)
 * và link quản lý/hủy ở cuối thư.
 */
abstract class NewsletterMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Subscriber $subscriber) {}

    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe' => '<'.$this->subscriber->unsubscribeUrl().'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }
}
