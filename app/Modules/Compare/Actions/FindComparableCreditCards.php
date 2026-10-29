<?php

namespace App\Modules\Compare\Actions;

use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Models\FinancialProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Lấy tối đa 3 thẻ đã xuất bản theo slug, giữ đúng thứ tự người dùng chọn. */
class FindComparableCreditCards
{
    public const MAX = 3;

    /**
     * @param  list<string>  $slugs
     * @return Collection<int, FinancialProduct>
     */
    public function handle(array $slugs): Collection
    {
        // Giới hạn số slug đưa vào truy vấn; thẻ không còn công khai không chiếm chỗ trong 3 thẻ.
        $slugs = array_slice(array_values(array_unique(array_filter(
            $slugs,
            fn ($slug) => is_string($slug) && preg_match('/^[a-z0-9-]{1,150}$/', $slug),
        ))), 0, self::MAX * 3);

        if ($slugs === []) {
            return collect();
        }

        $cards = FinancialProduct::query()
            ->ofType(ProductType::CreditCard)
            ->published()
            ->whereIn('slug', $slugs)
            ->whereHas('institution', fn (Builder $query) => $query->active())
            ->has('creditCard')
            ->with(['institution', 'creditCard'])
            ->get()
            ->keyBy('slug');

        return collect($slugs)->map(fn (string $slug) => $cards->get($slug))->filter()->take(self::MAX)->values();
    }
}
