<?php

namespace App\Modules\Compare\Support;

use App\Modules\Compare\Enums\BankAccountFeature;
use App\Modules\Compare\Models\FinancialProduct;
use App\Support\Format;

/**
 * Thông số hiển thị của tài khoản thanh toán, dùng chung cho trang danh sách và trang chi tiết.
 * "—" là thông tin ngân hàng chưa công bố, khác với "Miễn phí" (0 đ).
 */
final class BankAccountFacts
{
    /** @return array<string, string> */
    public static function for(FinancialProduct $account): array
    {
        $detail = $account->bankAccount;

        return [
            'Phí quản lý tài khoản' => self::fee($detail?->monthly_fee, '/tháng'),
            'Số dư tối thiểu' => $detail?->min_balance === null ? '—' : ((float) $detail->min_balance === 0.0 ? 'Không yêu cầu' : Format::money($detail->min_balance)),
            'Phí chuyển khoản' => self::fee($detail?->transfer_fee, '/giao dịch'),
            'Phí rút tiền ATM' => self::fee($detail?->atm_withdrawal_fee, '/giao dịch'),
        ];
    }

    /** @return list<string> */
    public static function features(FinancialProduct $account): array
    {
        return collect($account->bankAccount?->features ?? [])
            ->map(fn ($feature) => BankAccountFeature::tryFrom($feature)?->getLabel())
            ->filter()->values()->all();
    }

    private static function fee(?string $value, string $unit): string
    {
        return match (true) {
            $value === null => '—',
            (float) $value === 0.0 => 'Miễn phí',
            default => Format::money($value).$unit,
        };
    }
}
