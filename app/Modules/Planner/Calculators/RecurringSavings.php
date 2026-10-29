<?php

namespace App\Modules\Planner\Calculators;

use InvalidArgumentException;

/**
 * Tiết kiệm định kỳ (tích lũy): gửi thêm một số tiền cố định đầu mỗi tháng, mỗi khoản hưởng lãi đơn
 * từ ngày gửi đến cuối kỳ; lãi trả một lần cuối kỳ. Khoản gửi tháng k hưởng (n − k + 1) tháng, nên
 * tổng lãi = khoản gửi × lãi suất / 12 × n(n + 1)/2 (+ lãi của số tiền ban đầu trong n tháng).
 */
final class RecurringSavings
{
    public static function calculate(int $monthlyDeposit, string|int $ratePercent, int $months, int $initial = 0): RecurringSavingsResult
    {
        if ($monthlyDeposit < 0 || $initial < 0 || $months < 1) {
            throw new InvalidArgumentException('Số tiền hoặc số tháng không hợp lệ.');
        }

        $rate = InterestMath::rateUnits($ratePercent);
        $rows = [];

        for ($month = 1; $month <= $months; $month++) {
            $deposited = $initial + $monthlyDeposit * $month;
            InterestMath::guard($deposited);
            $interest = InterestMath::accrue($initial, $rate, $month)
                ->add(InterestMath::accrue($monthlyDeposit, $rate, intdiv($month * ($month + 1), 2)))
                ->rounded();

            $rows[] = ['month' => $month, 'deposited' => $deposited, 'interest' => $interest, 'balance' => $deposited + $interest];
        }

        $last = $rows[$months - 1];

        return new RecurringSavingsResult($last['deposited'], $last['interest'], $last['balance'], $rows);
    }
}
