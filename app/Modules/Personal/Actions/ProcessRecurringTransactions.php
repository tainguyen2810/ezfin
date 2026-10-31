<?php

namespace App\Modules\Personal\Actions;

use App\Modules\Personal\Enums\RecurringMode;
use App\Modules\Personal\Models\RecurringTransaction;
use Illuminate\Support\Carbon;

/**
 * Chạy hằng ngày: khoản tự ghi đã đến hạn thì ghi giao dịch (kể cả các kỳ bị lỡ khi lệnh không chạy);
 * khoản chỉ nhắc hạn đã qua hạn thì chuyển sang kỳ sau. Khoản chờ xác nhận giữ nguyên đến khi người dùng bấm.
 */
class ProcessRecurringTransactions
{
    /** Giới hạn số kỳ ghi bù cho mỗi khoản trong một lần chạy. */
    private const MAX_CATCH_UP = 60;

    public function __construct(private RecordRecurringOccurrence $occurrence) {}

    /** @return int số giao dịch đã tạo */
    public function handle(Carbon $today): int
    {
        $created = 0;

        RecurringTransaction::withoutGlobalScope('owner')
            ->where('mode', RecurringMode::Auto->value)
            ->whereNotNull('next_due_on')
            ->whereDate('next_due_on', '<=', $today->toDateString())
            ->orderBy('id')
            ->each(function (RecurringTransaction $item) use ($today, &$created) {
                $created += $this->catchUp($item, $today);
            });

        RecurringTransaction::withoutGlobalScope('owner')
            ->where('mode', RecurringMode::Remind->value)
            ->whereNotNull('next_due_on')
            ->whereDate('next_due_on', '<', $today->toDateString())
            ->orderBy('id')
            ->each(function (RecurringTransaction $item) use ($today) {
                for ($i = 0; $i < self::MAX_CATCH_UP && $item->next_due_on?->lt($today); $i++) {
                    if (! $this->occurrence->skip($item, $item->next_due_on)) {
                        break;
                    }
                }
            });

        return $created;
    }

    /** Ghi các kỳ đã đến hạn của một khoản. @return int số giao dịch đã tạo */
    public function catchUp(RecurringTransaction $item, Carbon $today): int
    {
        $created = 0;
        for ($i = 0; $i < self::MAX_CATCH_UP && $item->next_due_on?->lte($today); $i++) {
            if (! $this->occurrence->record($item, $item->next_due_on)) {
                break;
            }
            $created++;
        }

        return $created;
    }
}
