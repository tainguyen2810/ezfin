<?php

namespace App\Modules\Planner\Calculators;

use App\Modules\Planner\Enums\RepaymentMethod;

final readonly class RepaymentSchedule
{
    /**
     * @param  list<array{month: int, payment: int, principal: int, interest: int, balance: int}>  $months  balance = dư nợ sau khi trả
     * @param  ?string  $effectiveRate  chỉ với lãi phẳng: lãi suất dư nợ giảm dần tương đương, %/năm
     */
    public function __construct(
        public int $amount,
        public RepaymentMethod $method,
        public int $interest,
        public int $total,
        public array $months,
        public ?string $effectiveRate = null,
    ) {}

    public function firstPayment(): int
    {
        return $this->months[0]['payment'];
    }

    public function maxPayment(): int
    {
        return max(array_column($this->months, 'payment'));
    }
}
