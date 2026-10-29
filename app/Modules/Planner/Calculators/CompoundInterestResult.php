<?php

namespace App\Modules\Planner\Calculators;

final readonly class CompoundInterestResult
{
    /** @param  list<array{year: int, contributed: int, interest: int, totalInterest: int, balance: int}>  $years  contributed = gốc + góp thêm đến cuối năm; interest = lãi trong năm */
    public function __construct(
        public int $balance,
        public int $contributed,
        public int $interest,
        public array $years,
    ) {}
}
