<?php

namespace App\Modules\Planner\Calculators;

final readonly class RecurringSavingsResult
{
    /** @param  list<array{month: int, deposited: int, interest: int, balance: int}>  $months  lãi cộng dồn đến cuối tháng */
    public function __construct(
        public int $deposited,
        public int $interest,
        public int $total,
        public array $months,
    ) {}
}
