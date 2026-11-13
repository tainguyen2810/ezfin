<?php

namespace App\Modules\Newsletter\Mail;

use App\Modules\Newsletter\Models\Subscriber;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Bản tin tuần: lãi suất cao nhất theo kỳ hạn và bài viết mới. */
class WeeklyDigestMail extends NewsletterMail
{
    /**
     * @param  list<array{term: int, rate: float, institution: string, previous: ?float}>  $rates
     * @param  list<array{title: string, url: string, excerpt: ?string}>  $articles
     */
    public function __construct(Subscriber $subscriber, public array $rates, public array $articles)
    {
        parent::__construct($subscriber);
    }

    public function envelope(): Envelope
    {
        $twelve = collect($this->rates)->firstWhere('term', 12);

        return new Envelope(subject: 'Bản tin EzFin tuần '.now()->format('d/m/Y')
            .($twelve ? ': lãi 12 tháng cao nhất '.\Format::rate($twelve['rate']) : ''));
    }

    public function content(): Content
    {
        return new Content(markdown: 'newsletter::mail.weekly');
    }
}
