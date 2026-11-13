<?php

namespace App\Modules\Newsletter\Console;

use App\Modules\Newsletter\Actions\SendWeeklyDigest;
use Illuminate\Console\Command;
use Laravel\Pennant\Feature;

/** Sáng thứ Hai (routes/console.php): gửi bản tin tuần. Chạy lại trong tuần không gửi trùng. */
class WeeklyDigestCommand extends Command
{
    protected $signature = 'ezfin:newsletter-weekly';

    protected $description = 'Gửi bản tin tuần: lãi suất cao nhất và bài viết mới';

    public function handle(SendWeeklyDigest $digest): int
    {
        if (! Feature::active('newsletter')) {
            $this->line('Nhận tin (flag newsletter) đang tắt, không gửi.');

            return self::SUCCESS;
        }

        $this->info('Đã xếp gửi '.$digest->handle().' bản tin tuần.');

        return self::SUCCESS;
    }
}
