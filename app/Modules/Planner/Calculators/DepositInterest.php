<?php

namespace App\Modules\Planner\Calculators;

use App\Modules\Planner\Enums\DepositPayout;
use InvalidArgumentException;

/**
 * Lãi tiền gửi có kỳ hạn theo lãi đơn: số tiền × lãi suất × số tháng / 12, làm tròn đến đồng.
 * Lĩnh lãi hằng tháng: mỗi tháng nhận số tiền × lãi suất / 12 (làm tròn), gốc nhận lại cuối kỳ.
 */
final class DepositInterest
{
    public static function calculate(int $amount, string|int $ratePercent, int $months, DepositPayout $payout): DepositInterestResult
    {
        if ($amount < 0 || $months < 1) {
            throw new InvalidArgumentException('Số tiền hoặc kỳ hạn không hợp lệ.');
        }

        $rate = InterestMath::rateUnits($ratePercent);
        $monthly = $payout === DepositPayout::Monthly ? InterestMath::accrue($amount, $rate)->rounded() : null;
        $rows = [];

        for ($month = 1; $month <= $months; $month++) {
            $rows[] = [
                'month' => $month,
                'interest' => $monthly ?? ($month === $months ? InterestMath::accrue($amount, $rate, $months)->rounded() : 0),
                // Lãi cuối kỳ: phần đã phát sinh nhưng chưa trả, để vẽ tiến độ.
                'accrued' => $monthly === null ? InterestMath::accrue($amount, $rate, $month)->rounded() : $monthly * $month,
            ];
        }

        $interest = $rows[$months - 1]['accrued'];

        return new DepositInterestResult($amount, $interest, $amount + $interest, $monthly, $rows);
    }
}
