<?php

namespace App\Modules\Personal\Console;

use App\Modules\Personal\Actions\ProcessRecurringTransactions;
use App\Modules\Personal\Actions\SendDueReminders;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Laravel\Pennant\Feature;

/** Chạy hằng ngày (routes/console.php): ghi giao dịch định kỳ đến hạn, rồi gửi email nhắc hạn. */
class RecurringCommand extends Command
{
    protected $signature = 'ezfin:recurring {--date= : Ngày chạy (Y-m-d), mặc định hôm nay}';

    protected $description = 'Ghi giao dịch định kỳ đến hạn và gửi email nhắc hạn thanh toán';

    public function handle(ProcessRecurringTransactions $process, SendDueReminders $reminders): int
    {
        if (! Feature::active('personal')) {
            $this->line('Ứng dụng quản lý tiền đang tắt (flag personal), không làm gì.');

            return self::SUCCESS;
        }

        $today = $this->option('date') ? Carbon::createFromFormat('Y-m-d', $this->option('date'))->startOfDay() : today();

        $created = $process->handle($today);
        $sent = $reminders->handle($today);

        $this->info("Ngày {$today->format('d/m/Y')}: đã ghi $created giao dịch định kỳ, gửi $sent email nhắc hạn.");

        return self::SUCCESS;
    }
}
