<?php

namespace App\Modules\Compare\Support;

use App\Modules\Compare\Enums\InstitutionType;
use App\Modules\Compare\Models\FinancialProduct;

/**
 * Dữ liệu có cấu trúc schema.org (JSON-LD) cho trang chi tiết khoản vay.
 * Chỉ đưa thông tin đã công bố; không có đánh giá sao hay giá trị suy đoán.
 */
final class LoanSchema
{
    /** @return array<string, mixed> */
    public static function for(FinancialProduct $loan): array
    {
        $detail = $loan->loan;
        $institution = $loan->institution;
        $url = route('compare.loans.show', $loan->slug);

        $product = array_filter([
            '@type' => 'LoanOrCredit',
            'name' => $loan->name,
            'description' => $loan->summary,
            'url' => $url,
            'loanType' => $detail->kind->getLabel(),
            'provider' => array_filter([
                '@type' => $institution->type === InstitutionType::Bank ? 'BankOrCreditUnion' : 'FinancialService',
                'name' => $institution->name,
                'url' => $institution->website,
            ]),
            'annualPercentageRate' => $detail->promo_rate !== null ? (float) $detail->promo_rate : null,
            'amount' => $detail->max_amount !== null
                ? array_filter(['@type' => 'MonetaryAmount', 'currency' => 'VND', 'minValue' => $detail->min_amount !== null ? (float) $detail->min_amount : null, 'maxValue' => (float) $detail->max_amount], fn ($value) => $value !== null)
                : null,
            'loanTerm' => $detail->max_term_months !== null
                ? ['@type' => 'QuantitativeValue', 'maxValue' => $detail->max_term_months, 'unitCode' => 'MON']
                : null,
            'requiredCollateral' => $detail->kind->isSecured() ? 'Tài sản bảo đảm' : null,
        ], fn ($value) => $value !== null);

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'url' => $url,
            'name' => $loan->name,
            'inLanguage' => 'vi',
            'dateModified' => $loan->verified_at?->toDateString(),
            'mainEntity' => $product,
            'breadcrumb' => [
                '@type' => 'BreadcrumbList',
                'itemListElement' => collect([
                    ['Trang chủ', route('home')],
                    ['Vay', route('compare.loans.index')],
                    [$loan->name, $url],
                ])->map(fn (array $item, int $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $item[0], 'item' => $item[1]])->all(),
            ],
        ];
    }
}
