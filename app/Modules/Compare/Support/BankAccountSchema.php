<?php

namespace App\Modules\Compare\Support;

use App\Modules\Compare\Enums\InstitutionType;
use App\Modules\Compare\Models\FinancialProduct;
use App\Support\Format;

/**
 * Dữ liệu có cấu trúc schema.org (JSON-LD) cho trang chi tiết tài khoản thanh toán.
 * Chỉ đưa thông tin đã công bố; không có đánh giá sao hay giá trị suy đoán.
 */
final class BankAccountSchema
{
    /** @return array<string, mixed> */
    public static function for(FinancialProduct $account): array
    {
        $detail = $account->bankAccount;
        $institution = $account->institution;
        $url = route('compare.bank-accounts.show', $account->slug);

        $fees = collect([
            'Phí quản lý tài khoản/tháng' => $detail->monthly_fee,
            'Phí chuyển khoản liên ngân hàng online' => $detail->transfer_fee,
            'Phí rút tiền ATM nội mạng' => $detail->atm_withdrawal_fee,
        ])->reject(fn ($value) => $value === null)
            ->map(fn ($value, $label) => $label.': '.((float) $value === 0.0 ? 'Miễn phí' : Format::money($value)))
            ->implode('; ');

        $product = array_filter([
            '@type' => 'BankAccount',
            'name' => $account->name,
            'description' => $account->summary,
            'url' => $url,
            'provider' => array_filter([
                '@type' => $institution->type === InstitutionType::Bank ? 'BankOrCreditUnion' : 'FinancialService',
                'name' => $institution->name,
                'url' => $institution->website,
            ]),
            'accountMinimumInflow' => $detail->min_balance !== null
                ? ['@type' => 'MonetaryAmount', 'currency' => 'VND', 'value' => (float) $detail->min_balance]
                : null,
            'feesAndCommissionsSpecification' => $fees ?: null,
        ], fn ($value) => $value !== null);

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'url' => $url,
            'name' => $account->name,
            'inLanguage' => 'vi',
            'dateModified' => $account->verified_at?->toDateString(),
            'mainEntity' => $product,
            'breadcrumb' => [
                '@type' => 'BreadcrumbList',
                'itemListElement' => collect([
                    ['Trang chủ', route('home')],
                    ['Tài khoản ngân hàng', route('compare.bank-accounts.index')],
                    [$account->name, $url],
                ])->map(fn (array $item, int $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $item[0], 'item' => $item[1]])->all(),
            ],
        ];
    }
}
