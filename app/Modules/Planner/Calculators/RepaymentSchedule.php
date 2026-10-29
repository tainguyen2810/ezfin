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

    /**
     * Cộng theo năm vay (12 tháng một nhóm, nhóm cuối có thể ngắn hơn).
     *
     * @return list<array{year: int, payment: int, principal: int, interest: int, balance: int}>
     */
    public function byYear(): array
    {
        $chunks = array_chunk($this->months, 12);

        return array_map(fn (array $rows, int $index) => [
            'year' => $index + 1,
            'payment' => array_sum(array_column($rows, 'payment')),
            'principal' => array_sum(array_column($rows, 'principal')),
            'interest' => array_sum(array_column($rows, 'interest')),
            'balance' => end($rows)['balance'],
        ], $chunks, array_keys($chunks));
    }
}
