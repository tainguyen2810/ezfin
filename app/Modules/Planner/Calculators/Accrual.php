<?php

namespace App\Modules\Planner\Calculators;

/**
 * Tiền lãi cộng dồn: phần nguyên (đồng) + phần lẻ trên InterestMath::MONTH_DENOMINATOR.
 * Cộng các phần lãi trước rồi mới làm tròn để không cộng dồn sai số làm tròn.
 */
final readonly class Accrual
{
    public function __construct(
        public int $whole = 0,
        public int $remainder = 0,
    ) {}

    public function add(self $other): self
    {
        $remainder = $this->remainder + $other->remainder;

        return new self(
            $this->whole + $other->whole + intdiv($remainder, InterestMath::MONTH_DENOMINATOR),
            $remainder % InterestMath::MONTH_DENOMINATOR,
        );
    }

    /** Làm tròn nửa lên đến đồng. */
    public function rounded(): int
    {
        return $this->whole + ($this->remainder * 2 >= InterestMath::MONTH_DENOMINATOR ? 1 : 0);
    }
}
