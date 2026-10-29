<?php

namespace App\Modules\Compare\Support;

use InvalidArgumentException;

/**
 * Tiền lãi tiết kiệm theo công thức lãi đơn ngân hàng công bố:
 * tiền lãi = số tiền × lãi suất (%/năm) × số tháng / 12, làm tròn đến đồng.
 * Tính bằng số nguyên (lãi suất quy về phần vạn phần trăm) để không sai số dấu phẩy động.
 */
final class SavingsInterest
{
    /** Giới hạn để phép nhân số nguyên không tràn: 100 tỷ đồng. */
    public const MAX_AMOUNT = 100_000_000_000;

    public static function simple(int $amount, string|int|float $ratePercent, int $months): int
    {
        if ($amount < 0 || $amount > self::MAX_AMOUNT || $months < 0) {
            throw new InvalidArgumentException('Số tiền hoặc kỳ hạn không hợp lệ.');
        }

        // 5,2%/năm → 52000 (phần vạn của 1%).
        $rateUnits = (int) round((float) $ratePercent * 10_000);
        $numerator = $amount * $rateUnits * $months;
        $denominator = 12 * 100 * 10_000;

        // Làm tròn nửa lên.
        return intdiv($numerator * 2 + $denominator, $denominator * 2);
    }
}
