<?php

namespace App\Modules\Planner\Calculators;

use App\Modules\Planner\Enums\RepaymentMethod;
use InvalidArgumentException;

/**
 * Lịch trả nợ hằng tháng cho khoản vay và trả góp. Lãi mỗi tháng = dư nợ × lãi suất / 12, làm tròn đến đồng
 * (ngân hàng thường tính theo số ngày thực tế / 365 nên số liệu thực tế lệch vài nghìn đồng).
 *
 * - Dư nợ giảm dần: gốc chia đều (tháng cuối trả phần lẻ), lãi trên dư nợ còn lại.
 * - Trả đều: số tiền trả mỗi tháng A = P × i / (1 − (1 + i)^−n), làm tròn đến đồng; tháng cuối trả hết dư nợ còn lại.
 * - Lãi phẳng: tổng lãi = số tiền vay × lãi suất × số tháng / 12, chia đều các tháng (tháng cuối trả phần lẻ).
 */
final class Repayment
{
    public static function schedule(int $amount, string|int $ratePercent, int $months, RepaymentMethod $method): RepaymentSchedule
    {
        if ($amount < 1 || $months < 1) {
            throw new InvalidArgumentException('Số tiền hoặc thời hạn không hợp lệ.');
        }

        InterestMath::guard($amount);
        $rate = InterestMath::rateUnits($ratePercent);

        $rows = match ($method) {
            RepaymentMethod::EqualPrincipal => self::equalPrincipal($amount, $rate, $months),
            RepaymentMethod::Annuity => self::annuity($amount, $rate, $months),
            RepaymentMethod::Flat => self::flat($amount, $rate, $months),
        };

        return new RepaymentSchedule(
            amount: $amount,
            method: $method,
            interest: array_sum(array_column($rows, 'interest')),
            total: array_sum(array_column($rows, 'payment')),
            months: $rows,
            effectiveRate: $method === RepaymentMethod::Flat && $rate > 0 ? self::effectiveRate($amount, array_column($rows, 'payment')) : null,
        );
    }

    /** @return list<array{month: int, payment: int, principal: int, interest: int, balance: int}> */
    private static function equalPrincipal(int $amount, int $rate, int $months): array
    {
        $principal = intdiv($amount, $months);
        $balance = $amount;
        $rows = [];

        for ($month = 1; $month <= $months; $month++) {
            $interest = InterestMath::accrue($balance, $rate)->rounded();
            $paid = $month === $months ? $balance : $principal;
            $balance -= $paid;
            $rows[] = ['month' => $month, 'payment' => $paid + $interest, 'principal' => $paid, 'interest' => $interest, 'balance' => $balance];
        }

        return $rows;
    }

    /** @return list<array{month: int, payment: int, principal: int, interest: int, balance: int}> */
    private static function annuity(int $amount, int $rate, int $months): array
    {
        // Lũy thừa chỉ dùng để tìm số tiền trả mỗi tháng; lịch trả nợ tính lại bằng số nguyên.
        $i = $rate / InterestMath::MONTH_DENOMINATOR;
        $payment = $rate === 0
            ? (int) ceil($amount / $months)
            : (int) round($amount * $i / (1 - (1 + $i) ** -$months));

        $balance = $amount;
        $rows = [];

        for ($month = 1; $month <= $months; $month++) {
            $interest = InterestMath::accrue($balance, $rate)->rounded();
            $paid = $month === $months ? $balance : min($balance, $payment - $interest);
            $balance -= $paid;
            $rows[] = ['month' => $month, 'payment' => $paid + $interest, 'principal' => $paid, 'interest' => $interest, 'balance' => $balance];
        }

        return $rows;
    }

    /** @return list<array{month: int, payment: int, principal: int, interest: int, balance: int}> */
    private static function flat(int $amount, int $rate, int $months): array
    {
        $totalInterest = InterestMath::accrue($amount, $rate, $months)->rounded();
        $principal = intdiv($amount, $months);
        $interest = intdiv($totalInterest, $months);
        $balance = $amount;
        $rows = [];

        for ($month = 1; $month <= $months; $month++) {
            $last = $month === $months;
            $paid = $last ? $balance : $principal;
            $charged = $last ? $totalInterest - $interest * ($months - 1) : $interest;
            $balance -= $paid;
            $rows[] = ['month' => $month, 'payment' => $paid + $charged, 'principal' => $paid, 'interest' => $charged, 'balance' => $balance];
        }

        return $rows;
    }

    /**
     * Lãi suất dư nợ giảm dần tương đương (%/năm, 2 chữ số thập phân) của lịch trả lãi phẳng:
     * lãi suất tháng i sao cho tổng các khoản trả chiết khấu về hiện tại bằng số tiền vay, nhân 12.
     *
     * @param  list<int>  $payments
     */
    private static function effectiveRate(int $amount, array $payments): string
    {
        $presentValue = function (float $i) use ($payments): float {
            $sum = 0.0;
            foreach ($payments as $k => $payment) {
                $sum += $payment / (1 + $i) ** ($k + 1);
            }

            return $sum;
        };

        // Giá trị hiện tại giảm khi lãi suất tăng: chia đôi khoảng tìm kiếm.
        [$low, $high] = [0.0, 1.0];
        for ($step = 0; $step < 100; $step++) {
            $mid = ($low + $high) / 2;
            $presentValue($mid) > $amount ? $low = $mid : $high = $mid;
        }

        return number_format(($low + $high) / 2 * 12 * 100, 2, '.', '');
    }
}
