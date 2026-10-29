<?php

namespace App\Modules\Planner\Calculators;

use App\Modules\Planner\Enums\Compounding;
use InvalidArgumentException;

/**
 * Lãi kép có góp thêm hằng tháng.
 *
 * Giả định: lãi phát sinh theo tháng trên số dư đầu tháng, cộng vào gốc vào cuối mỗi kỳ ghép lãi
 * (làm tròn đến đồng mỗi kỳ); khoản góp thêm nộp cuối tháng nên sinh lãi từ tháng sau.
 * Không góp thêm thì kết quả là gốc × (1 + lãi suất / số kỳ trong năm) ^ số kỳ.
 */
final class CompoundInterest
{
    public static function calculate(int $principal, int $monthlyContribution, string|int $ratePercent, int $years, Compounding $compounding): CompoundInterestResult
    {
        if ($principal < 0 || $monthlyContribution < 0 || $years < 1) {
            throw new InvalidArgumentException('Số tiền hoặc số năm không hợp lệ.');
        }

        $rate = InterestMath::rateUnits($ratePercent);
        $balance = $principal;
        $contributed = $principal;
        $pending = new Accrual;
        $interestThisYear = 0;
        $rows = [];

        for ($month = 1; $month <= $years * 12; $month++) {
            $pending = $pending->add(InterestMath::accrue($balance, $rate));

            if ($month % $compounding->months() === 0) {
                $interest = $pending->rounded();
                $balance += $interest;
                $interestThisYear += $interest;
                $pending = new Accrual;
            }

            $balance += $monthlyContribution;
            $contributed += $monthlyContribution;
            InterestMath::guard($balance);

            if ($month % 12 === 0) {
                $rows[] = [
                    'year' => intdiv($month, 12),
                    'contributed' => $contributed,
                    'interest' => $interestThisYear,
                    'totalInterest' => $balance - $contributed,
                    'balance' => $balance,
                ];
                $interestThisYear = 0;
            }
        }

        return new CompoundInterestResult($balance, $contributed, $balance - $contributed, $rows);
    }
}
