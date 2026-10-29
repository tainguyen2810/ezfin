<?php

namespace App\Modules\Compare\Actions;

use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Models\FinancialProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Tài khoản tương tự theo tiêu chí khách quan: nhiều tính năng chung trước, rồi phí quản lý gần nhất.
 * Không xét đối tác hay hoa hồng.
 */
class FindSimilarBankAccounts
{
    /** @return Collection<int, FinancialProduct> */
    public function handle(FinancialProduct $account, int $limit = 3): Collection
    {
        $features = $account->bankAccount?->features ?? [];
        $fee = $account->bankAccount?->monthly_fee;

        return FinancialProduct::query()
            ->ofType(ProductType::BankAccount)
            ->published()
            ->whereKeyNot($account->getKey())
            ->whereHas('institution', fn (Builder $query) => $query->active())
            ->has('bankAccount')
            ->with(['institution', 'bankAccount'])
            ->get()
            ->map(fn (FinancialProduct $other) => [
                'account' => $other,
                'shared' => count(array_intersect($features, $other->bankAccount->features ?? [])),
                'feeGap' => $fee === null || $other->bankAccount->monthly_fee === null
                    ? PHP_FLOAT_MAX
                    : abs((float) $fee - (float) $other->bankAccount->monthly_fee),
            ])
            ->filter(fn (array $item) => $item['shared'] > 0 || $item['feeGap'] < PHP_FLOAT_MAX)
            ->sort(fn (array $a, array $b) => [$b['shared'], $a['feeGap'], $a['account']->name] <=> [$a['shared'], $b['feeGap'], $b['account']->name])
            ->take($limit)
            ->pluck('account')
            ->values();
    }
}
