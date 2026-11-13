<?php

namespace App\Modules\Newsletter\Actions;

use App\Modules\Compare\Actions\BestSavingsRates;
use App\Modules\Content\Actions\LatestArticles;
use App\Modules\Newsletter\Mail\WeeklyDigestMail;
use App\Modules\Newsletter\Models\Subscriber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Laravel\Pennant\Feature;

/**
 * Bản tin tuần (thứ Hai): lãi suất cao nhất các kỳ hạn phổ biến, thay đổi so với tuần trước, bài viết mới.
 * Mỗi người tối đa một bản mỗi tuần (weekly_sent_at); không có số liệu và không có bài mới thì không gửi.
 */
class SendWeeklyDigest
{
    public const TERMS = [1, 3, 6, 12, 24];

    public const ARTICLES = 5;

    /** @return int số email đã xếp gửi */
    public function handle(): int
    {
        $content = $this->content();
        if ($content['rates'] === [] && $content['articles'] === []) {
            return 0;
        }

        $weekStart = now()->startOfWeek();
        $sent = 0;

        Subscriber::query()->where('weekly_digest', true)
            ->where(fn ($query) => $query->whereNull('weekly_sent_at')->orWhere('weekly_sent_at', '<', $weekStart))
            ->chunkById(200, function ($subscribers) use ($content, $weekStart, &$sent) {
                foreach ($subscribers as $subscriber) {
                    $sent += DB::transaction(function () use ($subscriber, $content, $weekStart) {
                        $fresh = Subscriber::query()->lockForUpdate()->find($subscriber->id);
                        if (! $fresh || ! $fresh->weekly_digest || ($fresh->weekly_sent_at && $fresh->weekly_sent_at->gte($weekStart))) {
                            return 0;
                        }

                        $fresh->forceFill(['weekly_sent_at' => now()])->save();
                        Mail::to($fresh->email)->queue((new WeeklyDigestMail($fresh, $content['rates'], $content['articles']))->afterCommit());

                        return 1;
                    });
                }
            });

        return $sent;
    }

    /**
     * @return array{rates: list<array{term: int, rate: float, institution: string, previous: ?float}>, articles: list<array{title: string, url: string, excerpt: ?string}>}
     */
    public function content(): array
    {
        $rates = [];
        if (Feature::active('compare')) {
            $best = app(BestSavingsRates::class);
            $now = $best->topByTerm(self::TERMS);
            $before = $best->topByTerm(self::TERMS, today()->subDays(7));

            foreach ($now as $term => $top) {
                $rates[] = [
                    'term' => $term,
                    'rate' => $top['rate'],
                    'institution' => $top['institution']->short_name ?: $top['institution']->name,
                    'previous' => $before[$term]['rate'] ?? null,
                ];
            }
        }

        $articles = [];
        if (Feature::active('content')) {
            $articles = app(LatestArticles::class)->handle(self::ARTICLES)
                ->filter(fn ($article) => $article->published_at?->gte(now()->subDays(7)))
                ->map(fn ($article) => ['title' => $article->title, 'url' => $article->url(), 'excerpt' => $article->excerpt])
                ->values()->all();
        }

        return ['rates' => $rates, 'articles' => $articles];
    }
}
