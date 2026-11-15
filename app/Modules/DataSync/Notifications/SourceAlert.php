<?php

namespace App\Modules\DataSync\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email cảnh báo nguồn dữ liệu tự động gặp lỗi / đã chạy lại được. */
class SourceAlert extends Notification
{
    use Queueable;

    public function __construct(
        public readonly string $subject,
        public readonly string $body,
        public readonly string $actionUrl,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('[EzFin] '.$this->subject)
            ->greeting('Cảnh báo dữ liệu tự động')
            ->line($this->body)
            ->action('Mở trang Nguồn dữ liệu', $this->actionUrl)
            ->line('Email này gửi tới quản trị viên và người vận hành dữ liệu của EzFin.');
    }
}
