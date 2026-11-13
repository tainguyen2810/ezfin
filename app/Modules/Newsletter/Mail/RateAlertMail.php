<?php

namespace App\Modules\Newsletter\Mail;

use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Newsletter\Models\Subscriber;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Báo lãi suất tiết kiệm cao nhất của kỳ hạn bạn theo dõi vừa tăng. */
class RateAlertMail extends NewsletterMail
{
    /** @param  list<array{term: int, from: float, to: float, institution: FinancialInstitution}>  $increases */
    public function __construct(Subscriber $subscriber, public array $increases)
    {
        parent::__construct($subscriber);
    }

    public function envelope(): Envelope
    {
        $first = $this->increases[0];
        $subject = count($this->increases) === 1
            ? 'Lãi suất tiết kiệm '.$first['term'].' tháng tăng lên '.\Format::rate($first['to'])
            : 'Lãi suất tiết kiệm tăng ở '.count($this->increases).' kỳ hạn bạn theo dõi';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'newsletter::mail.rate-alert');
    }
}
