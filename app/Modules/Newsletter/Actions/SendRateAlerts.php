<?php

namespace App\Modules\Newsletter\Actions;

use App\Modules\Compare\Actions\BestSavingsRates;
use App\Modules\Newsletter\Mail\RateAlertMail;
use App\Modules\Newsletter\Models\RateAlert;
use App\Modules\Newsletter\Models\Subscriber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Chạy hằng ngày: so mức lãi cao nhất thị trường từng kỳ hạn với mốc của mỗi người đăng ký.
 * Tăng thì gửi một email (gộp mọi kỳ hạn của người đó) và nâng mốc; giảm thì hạ mốc để lần tăng sau vẫn được báo.
 * Mốc được cập nhật trong cùng transaction với việc xếp thư vào hàng đợi, nên chạy lại không gửi trùng.
 */
class SendRateAlerts
{
    /** Chênh lệch tối thiểu (điểm %) để coi là tăng; tránh báo vì làm tròn. */
    public const MIN_INCREASE = 0.01;

    public function __construct(private readonly BestSavingsRates $rates) {}

    /** @return int số email đã xếp gửi */
    public function handle(): int
    {
        $terms = RateAlert::query()->distinct()->pluck('term_months')->map(fn ($term) => (int) $term)->all();
        if ($terms === []) {
            return 0;
        }

        $top = $this->rates->topByTerm($terms);
        $sent = 0;

        Subscriber::query()->whereHas('alerts')->chunkById(200, function ($subscribers) use ($top, &$sent) {
            foreach ($subscribers as $subscriber) {
                $sent += DB::transaction(function () use ($subscriber, $top) {
                    $increases = [];

                    foreach ($subscriber->alerts()->lockForUpdate()->get() as $alert) {
                        $current = $top[$alert->term_months] ?? null;
                        if ($current === null) {
                            continue;
                        }

                        $baseline = $alert->baseline_rate === null ? null : (float) $alert->baseline_rate;

                        if ($baseline !== null && $current['rate'] >= $baseline + self::MIN_INCREASE) {
                            $increases[] = ['term' => $alert->term_months, 'from' => $baseline, 'to' => $current['rate'], 'institution' => $current['institution']];
                            $alert->forceFill(['baseline_rate' => $current['rate'], 'last_notified_at' => now()])->save();
                        } elseif ($baseline === null || $current['rate'] < $baseline) {
                            $alert->forceFill(['baseline_rate' => $current['rate']])->save();
                        }
                    }

                    if ($increases === []) {
                        return 0;
                    }

                    Mail::to($subscriber->email)->queue((new RateAlertMail($subscriber, $increases))->afterCommit());

                    return 1;
                });
            }
        });

        return $sent;
    }
}
