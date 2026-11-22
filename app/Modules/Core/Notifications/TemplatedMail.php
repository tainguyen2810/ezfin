<?php

namespace App\Modules\Core\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Email dựng từ mẫu thông báo (tiêu đề, lời chào, nội dung, nút) đã thay biến. */
class TemplatedMail extends Notification
{
    use Queueable;

    public function __construct(
        public readonly RenderedMessage $message,
        public readonly ?string $actionUrl = null,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject($this->message->subject)->line($this->message->bodyHtml())->salutation('EzFin');

        if ($this->message->greeting !== '') {
            $mail->greeting($this->message->greeting);
        }

        if ($this->actionUrl !== null && $this->message->actionLabel !== '') {
            $mail->action($this->message->actionLabel, $this->actionUrl);
        }

        return $mail;
    }
}
