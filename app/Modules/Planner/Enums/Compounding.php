<?php

namespace App\Modules\Planner\Enums;

/** Kỳ ghép lãi: tiền lãi được cộng vào gốc sau mỗi kỳ. */
enum Compounding: string
{
    case Monthly = 'hang-thang';
    case Quarterly = 'hang-quy';
    case Yearly = 'hang-nam';

    public function months(): int
    {
        return match ($this) {
            self::Monthly => 1,
            self::Quarterly => 3,
            self::Yearly => 12,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'Hằng tháng',
            self::Quarterly => 'Hằng quý',
            self::Yearly => 'Hằng năm',
        };
    }
}
