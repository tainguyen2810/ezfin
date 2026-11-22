<?php

namespace App\Modules\Personal\Mail;

use App\Models\User;
use App\Modules\Core\Actions\NotificationCenter;
use App\Modules\Core\Notifications\Channel;
use App\Modules\Core\Notifications\RenderedMessage;
use App\Modules\Personal\Models\RecurringTransaction;
use App\Modules\Personal\Support\PersonalNotifications;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/** Email nhắc khoản định kỳ sắp đến hạn. */
class DueReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param  Collection<int, RecurringTransaction>  $items */
    public function __construct(public User $user, public Collection $items, public Carbon $today) {}

    private ?RenderedMessage $copy = null;

    /** Chữ của thư theo mẫu trong trang quản trị Thông báo. */
    public function copy(): RenderedMessage
    {
        $first = $this->items->first();
        $summary = $this->items->count() === 1
            ? $first->name.' ngày '.$first->next_due_on->format('d/m/Y')
            : $this->items->count().' khoản thu chi định kỳ';

        return $this->copy ??= app(NotificationCenter::class)->render(PersonalNotifications::DUE_REMINDER, Channel::Mail, [
            'ten' => $this->user->name,
            'tom_tat' => $summary,
            'so_khoan' => $this->items->count(),
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->copy()->subject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'personal::mail.due-reminder', with: ['copy' => $this->copy()]);
    }
}
