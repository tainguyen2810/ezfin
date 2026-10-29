<?php

namespace App\Modules\Planner\Calculators;

use InvalidArgumentException;

/**
 * Phép tính lãi bằng số nguyên. Tiền tính bằng đồng (int), lãi suất %/năm quy về phần vạn của 1%
 * (6,5% → 65000) nên không có sai số dấu phẩy động. Lãi theo tháng: số dư × lãi suất × số tháng / 12.
 */
final class InterestMath
{
    public const RATE_SCALE = 10_000;

    /** Số dư/kết quả tối đa: 1 triệu tỷ đồng. Vượt ngưỡng thì báo lỗi thay vì tràn số. */
    public const MAX_AMOUNT = 1_000_000_000_000_000;

    /** Mẫu số của lãi một tháng: 12 tháng × 100% × RATE_SCALE. */
    public const MONTH_DENOMINATOR = 12 * 100 * self::RATE_SCALE;

    /** "6,5" hoặc "6.5" → 65000. Tối đa 4 chữ số thập phân, không âm. */
    public static function rateUnits(string|int $percent): int
    {
        $percent = str_replace(',', '.', trim((string) $percent));

        if (! preg_match('/^(\d{1,3})(?:\.(\d{1,4}))?$/', $percent, $match)) {
            throw new InvalidArgumentException('Lãi suất không hợp lệ.');
        }

        return (int) $match[1] * self::RATE_SCALE + (int) str_pad($match[2] ?? '', 4, '0');
    }

    /** Lãi đơn của $balance trong $months tháng, giữ nguyên phần lẻ để làm tròn một lần. */
    public static function accrue(int $balance, int $rateUnits, int $months = 1): Accrual
    {
        if ($balance < 0 || $rateUnits < 0 || $months < 0) {
            throw new InvalidArgumentException('Số tiền, lãi suất hoặc số tháng không hợp lệ.');
        }

        self::guard($balance);

        // balance = a × D + b với b < D: a × lãi chia hết cho D, chỉ phần b mới có phần lẻ.
        $a = intdiv($balance, self::MONTH_DENOMINATOR);
        $b = $balance % self::MONTH_DENOMINATOR;
        $remainder = $b * $rateUnits * $months;

        return new Accrual($a * $rateUnits * $months + intdiv($remainder, self::MONTH_DENOMINATOR), $remainder % self::MONTH_DENOMINATOR);
    }

    public static function guard(int $amount): void
    {
        if ($amount > self::MAX_AMOUNT) {
            throw new CalculationLimitExceeded;
        }
    }
}
