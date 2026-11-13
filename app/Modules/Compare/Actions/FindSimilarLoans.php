<?php

namespace App\Modules\Compare\Actions;

use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Models\FinancialProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Khoản vay cùng loại có lãi ưu đãi gần nhất. Không xét đối tác hay hoa hồng. */
class FindSimilarLoans
{
    /** @return Collection<int, FinancialProduct> */
    public function handle(FinancialProduct $loan, int $limit = 3): Collection
    {
        $rate = $loan->loan?->promo_rate;

        return FinancialProduct::query()
            ->ofType(ProductType::Loan)
            ->published()
            ->whereKeyNot($loan->getKey())
            ->whereHas('institution', fn (Builder $query) => $query->active())
            ->whereHas('loan', fn (Builder $query) => $query->where('kind', $loan->loan?->kind))
            ->with(['institution', 'loan'])
            ->get()
            ->sortBy(fn (FinancialProduct $other) => [
                $rate === null || $other->loan->promo_rate === null ? PHP_FLOAT_MAX : abs((float) $rate - (float) $other->loan->promo_rate),
                $other->name,
            ])
            ->take($limit)
            ->values();
    }
}
