<?php

namespace App\Modules\Personal\Enums;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/** Chu kỳ của khoản định kỳ. Kỳ thứ n luôn tính từ ngày bắt đầu nên ngày 31 không bị trôi dần về 28. */
enum RecurringFrequency: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';

    public function label(): string
    {
        return match ($this) {
            self::Weekly => 'Hằng tuần',
            self::Monthly => 'Hằng tháng',
            self::Quarterly => 'Hằng quý',
            self::Yearly => 'Hằng năm',
        };
    }

    /** Ngày của kỳ thứ $n (kỳ 0 = ngày bắt đầu). Tháng thiếu ngày thì lùi về ngày cuối tháng. */
    public function occurrence(CarbonInterface $start, int $n): Carbon
    {
        $start = Carbon::parse($start)->startOfDay();

        return match ($this) {
            self::Weekly => $start->addWeeks($n),
            self::Monthly => $start->addMonthsNoOverflow($n),
            self::Quarterly => $start->addMonthsNoOverflow(3 * $n),
            self::Yearly => $start->addYearsNoOverflow($n),
        };
    }

    /** Số kỳ trung bình mỗi tháng, để ước tính thu/chi cố định hằng tháng. */
    public function perMonth(): float
    {
        return match ($this) {
            self::Weekly => 52 / 12,
            self::Monthly => 1,
            self::Quarterly => 1 / 3,
            self::Yearly => 1 / 12,
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $f) => [$f->value => $f->label()])->all();
    }
}
