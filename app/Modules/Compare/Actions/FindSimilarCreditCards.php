<?php

namespace App\Modules\Compare\Actions;

use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Models\FinancialProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Thẻ tương tự theo tiêu chí khách quan: cùng nhóm lợi ích trước, rồi phí thường niên gần nhất.
 * Không xét đối tác hay hoa hồng.
 */
class FindSimilarCreditCards
{
    /** @return Collection<int, FinancialProduct> */
    public function handle(FinancialProduct $card, int $limit = 3): Collection
    {
        $benefits = $card->creditCard?->benefits ?? [];
        $fee = $card->creditCard?->annual_fee;

        return FinancialProduct::query()
            ->ofType(ProductType::CreditCard)
            ->published()
            ->whereKeyNot($card->getKey())
            ->whereHas('institution', fn (Builder $query) => $query->active())
            ->has('creditCard')
            ->with(['institution', 'creditCard'])
            ->get()
            ->map(fn (FinancialProduct $other) => [
                'card' => $other,
                'shared' => count(array_intersect($benefits, $other->creditCard->benefits ?? [])),
                'feeGap' => $fee === null || $other->creditCard->annual_fee === null
                    ? PHP_FLOAT_MAX
                    : abs((float) $fee - (float) $other->creditCard->annual_fee),
            ])
            ->filter(fn (array $item) => $item['shared'] > 0 || $item['feeGap'] < PHP_FLOAT_MAX)
            ->sort(fn (array $a, array $b) => [$b['shared'], $a['feeGap'], $a['card']->name] <=> [$a['shared'], $b['feeGap'], $b['card']->name])
            ->take($limit)
            ->pluck('card')
            ->values();
    }
}
