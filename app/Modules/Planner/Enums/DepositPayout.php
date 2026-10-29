<?php

namespace App\Modules\Planner\Enums;

enum DepositPayout: string
{
    case EndOfTerm = 'cuoi-ky';
    case Monthly = 'hang-thang';

    public function label(): string
    {
        return match ($this) {
            self::EndOfTerm => 'Cuối kỳ',
            self::Monthly => 'Hằng tháng',
        };
    }
}
