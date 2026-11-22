<?php

namespace App\Modules\Newsletter\Mail;

use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Core\Actions\NotificationCenter;
use App\Modules\Core\Notifications\Channel;
use App\Modules\Core\Notifications\RenderedMessage;
use App\Modules\Newsletter\Models\Subscriber;
use App\Modules\Newsletter\Support\NewsletterNotifications;
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

    private ?RenderedMessage $copy = null;

    /** Chữ của thư theo mẫu trong trang quản trị Thông báo. */
    public function copy(): RenderedMessage
    {
        $first = $this->increases[0];
        $summary = count($this->increases) === 1
            ? 'Lãi suất tiết kiệm '.$first['term'].' tháng tăng lên '.\Format::rate($first['to'])
            : 'Lãi suất tiết kiệm tăng ở '.count($this->increases).' kỳ hạn bạn theo dõi';

        return $this->copy ??= app(NotificationCenter::class)->render(NewsletterNotifications::RATE_ALERT, Channel::Mail, [
            'tom_tat' => $summary,
            'ky_han' => $first['term'],
            'lai_moi' => \Format::rate($first['to']),
            'so_ky_han' => count($this->increases),
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->copy()->subject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'newsletter::mail.rate-alert', with: ['copy' => $this->copy()]);
    }
}
