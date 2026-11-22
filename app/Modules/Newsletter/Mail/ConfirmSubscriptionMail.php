<?php

namespace App\Modules\Newsletter\Mail;

use App\Modules\Core\Actions\NotificationCenter;
use App\Modules\Core\Notifications\Channel;
use App\Modules\Core\Notifications\RenderedMessage;
use App\Modules\Newsletter\Actions\RequestSubscription;
use App\Modules\Newsletter\Support\NewsletterNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Thư xác nhận đăng ký (double opt-in). Không bấm thì không có gì được lưu. */
class ConfirmSubscriptionMail extends Mailable
{
    use Queueable, SerializesModels;

    private ?RenderedMessage $copy = null;

    public function __construct(public string $confirmUrl, public ?int $term, public bool $weekly) {}

    /** Chữ của thư theo mẫu trong trang quản trị Thông báo. */
    public function copy(): RenderedMessage
    {
        return $this->copy ??= app(NotificationCenter::class)->render(NewsletterNotifications::CONFIRM, Channel::Mail, [
            'so_ngay_hieu_luc' => RequestSubscription::LINK_DAYS,
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->copy()->subject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'newsletter::mail.confirm', with: ['copy' => $this->copy()]);
    }
}
