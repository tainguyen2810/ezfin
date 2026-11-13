<?php

namespace App\Modules\Compare\Actions;

use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Compare\Support\LoanFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tìm khoản vay đã xuất bản theo bộ lọc. Thứ tự chỉ theo tiêu chí người dùng chọn, không theo đối tác.
 * Thông số chưa công bố không loại khoản vay khỏi kết quả nhưng luôn xếp cuối.
 */
class SearchLoans
{
    public const PER_PAGE = 12;

    public function handle(LoanFilters $filters, int $page = 1, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        $query = FinancialProduct::query()
            ->select('financial_products.*')
            ->join('loan_details as loan', 'loan.product_id', '=', 'financial_products.id')
            ->ofType(ProductType::Loan)
            ->published()
            ->whereHas('institution', fn (Builder $query) => $query->active())
            ->with(['institution', 'loan']);

        if ($filters->kind !== null) {
            $query->where('loan.kind', $filters->kind->value);
        }

        if ($filters->institution !== null) {
            $query->whereHas('institution', fn (Builder $query) => $query->where('slug', $filters->institution));
        }

        if ($filters->amount !== null) {
            $query->where(fn (Builder $query) => $query->whereNull('loan.max_amount')->orWhere('loan.max_amount', '>=', $filters->amount))
                ->where(fn (Builder $query) => $query->whereNull('loan.min_amount')->orWhere('loan.min_amount', '<=', $filters->amount));
        }

        if ($filters->term !== null) {
            $query->where(fn (Builder $query) => $query->whereNull('loan.max_term_months')->orWhere('loan.max_term_months', '>=', $filters->term));
        }

        match ($filters->sort) {
            // Trống = lãi cố định cả kỳ hạn, tức "ưu đãi" dài nhất.
            'uu-dai-dai' => $query->orderByRaw('loan.promo_rate is null')->orderByRaw('loan.promo_months is not null')->orderByDesc('loan.promo_months')->orderBy('loan.promo_rate'),
            'han-muc-cao' => $query->orderByRaw('loan.max_amount is null')->orderByDesc('loan.max_amount'),
            'moi-xac-minh' => $query->orderByDesc('financial_products.verified_at'),
            default => $query->orderByRaw('loan.promo_rate is null')->orderBy('loan.promo_rate'),
        };

        // Trang, đường dẫn truyền tường minh thay vì đọc từ resolver tĩnh của Paginator.
        return $query->orderBy('financial_products.name')
            ->paginate($perPage, page: max(1, $page))
            ->withPath(route('compare.loans.index'))
            ->appends($filters->toQuery());
    }
}
