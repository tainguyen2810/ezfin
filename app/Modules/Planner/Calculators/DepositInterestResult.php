<?php

namespace App\Modules\Planner\Calculators;

final readonly class DepositInterestResult
{
    /** @param  list<array{month: int, interest: int, accrued: int}>  $months  interest = lãi trả trong tháng; accrued = lãi cộng dồn */
    public function __construct(
        public int $amount,
        public int $interest,
        public int $total,
        public ?int $monthlyPayout,
        public array $months,
    ) {}
}
