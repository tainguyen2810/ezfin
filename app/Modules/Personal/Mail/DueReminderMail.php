<?php

namespace App\Modules\Personal\Mail;

use App\Models\User;
use App\Modules\Personal\Models\RecurringTransaction;
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

    public function envelope(): Envelope
    {
        $first = $this->items->first();
        $subject = $this->items->count() === 1
            ? 'Sắp đến hạn: '.$first->name.' ngày '.$first->next_due_on->format('d/m/Y')
            : 'Sắp đến hạn: '.$this->items->count().' khoản thu chi định kỳ';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'personal::mail.due-reminder');
    }
}
