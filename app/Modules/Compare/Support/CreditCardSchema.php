<?php

namespace App\Modules\Compare\Support;

use App\Modules\Compare\Enums\InstitutionType;
use App\Modules\Compare\Models\FinancialProduct;
use App\Support\Format;

/**
 * Dữ liệu có cấu trúc schema.org (JSON-LD) cho trang chi tiết thẻ.
 * Chỉ đưa thông tin đã công bố; không có đánh giá sao hay giá trị suy đoán.
 */
final class CreditCardSchema
{
    /** @return array<string, mixed> */
    public static function for(FinancialProduct $card): array
    {
        $detail = $card->creditCard;
        $institution = $card->institution;
        $url = route('compare.credit-cards.show', $card->slug);

        $fees = collect([
            'Phí thường niên' => $detail->annual_fee,
            'Phí phát hành' => $detail->issuance_fee,
        ])->reject(fn ($value) => $value === null)
            ->map(fn ($value, $label) => $label.': '.((float) $value === 0.0 ? 'Miễn phí' : Format::money($value)))
            ->when($detail->foreign_transaction_fee !== null, fn ($items) => $items->push('Phí giao dịch ngoại tệ: '.Format::percent($detail->foreign_transaction_fee)))
            ->implode('; ');

        $product = array_filter([
            '@type' => 'CreditCard',
            'name' => $card->name,
            'description' => $card->summary,
            'url' => $url,
            'image' => $card->imageUrl() ? url($card->imageUrl()) : null,
            'provider' => array_filter([
                '@type' => $institution->type === InstitutionType::Bank ? 'BankOrCreditUnion' : 'FinancialService',
                'name' => $institution->name,
                'url' => $institution->website,
            ]),
            'annualPercentageRate' => $detail->interest_rate !== null ? (float) $detail->interest_rate : null,
            'feesAndCommissionsSpecification' => $fees ?: null,
            'brand' => $detail->network?->getLabel(),
        ], fn ($value) => $value !== null);

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'url' => $url,
            'name' => $card->name,
            'inLanguage' => 'vi',
            'dateModified' => $card->verified_at?->toDateString(),
            'mainEntity' => $product,
            'breadcrumb' => [
                '@type' => 'BreadcrumbList',
                'itemListElement' => collect([
                    ['Trang chủ', route('home')],
                    ['Thẻ tín dụng', route('compare.credit-cards.index')],
                    [$card->name, $url],
                ])->map(fn (array $item, int $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $item[0], 'item' => $item[1]])->all(),
            ],
        ];
    }
}
