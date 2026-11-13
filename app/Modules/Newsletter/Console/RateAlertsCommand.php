<?php

namespace App\Modules\Newsletter\Console;

use App\Modules\Newsletter\Actions\SendRateAlerts;
use Illuminate\Console\Command;
use Laravel\Pennant\Feature;

/** Hằng ngày (routes/console.php): gửi email báo lãi suất tiết kiệm cao nhất tăng. */
class RateAlertsCommand extends Command
{
    protected $signature = 'ezfin:rate-alerts';

    protected $description = 'Gửi email báo khi lãi suất tiết kiệm cao nhất của kỳ hạn người dùng theo dõi tăng';

    public function handle(SendRateAlerts $alerts): int
    {
        if (! Feature::active('newsletter') || ! Feature::active('compare')) {
            $this->line('Nhận tin (flag newsletter) hoặc so sánh (flag compare) đang tắt, không gửi.');

            return self::SUCCESS;
        }

        $this->info('Đã xếp gửi '.$alerts->handle().' email báo lãi suất tăng.');

        return self::SUCCESS;
    }
}
