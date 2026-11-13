<?php

namespace App\Modules\Compare\Support;

use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Compare\Models\LoanDetail;
use App\Modules\Planner\Calculators\Repayment;
use App\Modules\Planner\Enums\RepaymentMethod;
use App\Support\Format;

/**
 * Thông số hiển thị của khoản vay, dùng chung cho trang danh sách, trang chi tiết và trang ngân hàng.
 * "—" là thông tin ngân hàng chưa công bố.
 */
final class LoanFacts
{
    /** @return array<string, string> */
    public static function for(FinancialProduct $loan): array
    {
        $detail = $loan->loan;

        return [
            self::rateLabel($detail) => $detail?->promo_rate === null ? '—' : Format::rate($detail->promo_rate),
            'Sau ưu đãi' => self::afterPromo($detail),
            'Hạn mức tối đa' => self::maxAmount($detail),
            'Thời hạn tối đa' => self::months($detail?->max_term_months),
        ];
    }

    /** Thông số đầy đủ cho trang chi tiết. @return array<string, string> */
    public static function full(FinancialProduct $loan): array
    {
        $detail = $loan->loan;

        return [
            ...self::for($loan),
            'Số tiền vay tối thiểu' => $detail?->min_amount === null ? '—' : Format::money($detail->min_amount),
            'Cho vay tối đa' => $detail?->max_ltv === null ? '—' : Format::percent($detail->max_ltv).' giá trị tài sản',
            'Thu nhập tối thiểu' => $detail?->min_monthly_income === null ? '—' : Format::money($detail->min_monthly_income).'/tháng',
            'Phí hồ sơ' => self::percent($detail?->processing_fee, 'số tiền vay'),
            'Phí trả nợ trước hạn' => self::percent($detail?->prepayment_fee, 'số tiền trả trước'),
        ];
    }

    public static function rateLabel(?LoanDetail $detail): string
    {
        return match (true) {
            $detail?->promo_months !== null => 'Lãi ưu đãi '.$detail->promo_months.' tháng đầu',
            default => 'Lãi suất cố định',
        };
    }

    public static function afterPromo(?LoanDetail $detail): string
    {
        return match (true) {
            $detail === null => '—',
            ! $detail->isFloating() => 'Cố định cả kỳ hạn',
            $detail->floating_note !== null => $detail->floating_note,
            $detail->floating_margin !== null => 'Lãi cơ sở + '.Format::rate($detail->floating_margin),
            default => 'Thả nổi',
        };
    }

    /**
     * Tiền trả tháng đầu (dư nợ giảm dần, theo lãi ưu đãi). Null khi thiếu lãi suất.
     * Chỉ là ước tính: sau thời gian ưu đãi lãi suất thả nổi nên tiền trả sẽ khác.
     */
    public static function firstPayment(FinancialProduct $loan, int $amount, int $months): ?int
    {
        $rate = $loan->loan?->promo_rate;

        return $rate === null ? null
            : Repayment::schedule($amount, $rate, $months, RepaymentMethod::EqualPrincipal)->firstPayment();
    }

    /** Khoản vay đáp ứng số tiền và thời hạn người dùng nhập (thông số chưa công bố thì không loại). */
    public static function fits(FinancialProduct $loan, ?int $amount, ?int $months): bool
    {
        $detail = $loan->loan;

        return ($amount === null || (($detail->max_amount === null || $amount <= (float) $detail->max_amount)
                && ($detail->min_amount === null || $amount >= (float) $detail->min_amount)))
            && ($months === null || $detail->max_term_months === null || $months <= $detail->max_term_months);
    }

    private static function maxAmount(?LoanDetail $detail): string
    {
        return $detail?->max_amount === null ? '—' : Format::money($detail->max_amount);
    }

    private static function months(?int $months): string
    {
        return match (true) {
            $months === null => '—',
            $months % 12 === 0 && $months >= 24 => ($months / 12).' năm',
            default => "$months tháng",
        };
    }

    private static function percent(?string $value, string $of): string
    {
        return match (true) {
            $value === null => '—',
            (float) $value === 0.0 => 'Miễn phí',
            default => Format::percent($value).' '.$of,
        };
    }
}
