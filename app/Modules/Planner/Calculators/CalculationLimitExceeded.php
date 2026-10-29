<?php

namespace App\Modules\Planner\Calculators;

use DomainException;

/** Kết quả vượt giới hạn tính toán (InterestMath::MAX_AMOUNT). */
class CalculationLimitExceeded extends DomainException
{
    public function __construct()
    {
        parent::__construct('Kết quả vượt quá 1 triệu tỷ đồng. Hãy giảm số tiền, lãi suất hoặc thời gian.');
    }
}
