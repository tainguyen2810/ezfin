<?php

namespace App\Modules\Newsletter\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Thư xác nhận đăng ký (double opt-in). Không bấm thì không có gì được lưu. */
class ConfirmSubscriptionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $confirmUrl, public ?int $term, public bool $weekly) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Xác nhận nhận email từ EzFin');
    }

    public function content(): Content
    {
        return new Content(markdown: 'newsletter::mail.confirm');
    }
}
