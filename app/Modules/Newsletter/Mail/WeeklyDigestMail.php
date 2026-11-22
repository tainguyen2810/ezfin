<?php

namespace App\Modules\Newsletter\Mail;

use App\Modules\Core\Actions\NotificationCenter;
use App\Modules\Core\Notifications\Channel;
use App\Modules\Core\Notifications\RenderedMessage;
use App\Modules\Newsletter\Models\Subscriber;
use App\Modules\Newsletter\Support\NewsletterNotifications;
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

    private ?RenderedMessage $copy = null;

    /** Chữ của thư theo mẫu trong trang quản trị Thông báo. */
    public function copy(): RenderedMessage
    {
        $twelve = collect($this->rates)->firstWhere('term', 12);
        $rate = $twelve ? \Format::rate($twelve['rate']) : '';

        return $this->copy ??= app(NotificationCenter::class)->render(NewsletterNotifications::WEEKLY, Channel::Mail, [
            'ngay' => now()->format('d/m/Y'),
            'lai_12_thang' => $rate,
            'ghi_chu_lai_12_thang' => $rate === '' ? '' : ': lãi 12 tháng cao nhất '.$rate,
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->copy()->subject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'newsletter::mail.weekly', with: ['copy' => $this->copy()]);
    }
}
