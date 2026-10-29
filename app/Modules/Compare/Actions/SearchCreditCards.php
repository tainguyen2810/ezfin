<?php

namespace App\Modules\Compare\Actions;

use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Compare\Support\CreditCardFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tìm thẻ tín dụng đã xuất bản theo bộ lọc. Thứ tự chỉ theo tiêu chí người dùng chọn,
 * không theo đối tác hay hoa hồng. Trường chưa có thông tin luôn xếp cuối.
 */
class SearchCreditCards
{
    public const PER_PAGE = 12;

    public function handle(CreditCardFilters $filters, int $page = 1, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        $query = FinancialProduct::query()
            ->select('financial_products.*')
            ->join('credit_card_details as card', 'card.product_id', '=', 'financial_products.id')
            ->ofType(ProductType::CreditCard)
            ->published()
            ->whereHas('institution', fn (Builder $query) => $query->active())
            ->with(['institution', 'creditCard']);

        if ($filters->institution !== null) {
            $query->whereHas('institution', fn (Builder $query) => $query->where('slug', $filters->institution));
        }

        if (($maxFee = $filters->maxAnnualFee()) !== null) {
            $query->where('card.annual_fee', '<=', $maxFee);
        }

        if ($filters->need !== null) {
            $query->whereJsonContains('card.benefits', $filters->need->value);
        }

        // Chỉ giữ thẻ đã biết điều kiện thu nhập và người dùng đáp ứng được.
        if ($filters->income !== null) {
            $query->whereNotNull('card.min_monthly_income')->where('card.min_monthly_income', '<=', $filters->income);
        }

        match ($filters->sort) {
            'hoan-tien-cao' => $query->orderByRaw('card.cashback_rate is null')->orderByDesc('card.cashback_rate'),
            'thu-nhap-thap' => $query->orderByRaw('card.min_monthly_income is null')->orderBy('card.min_monthly_income'),
            'moi-xac-minh' => $query->orderByDesc('financial_products.verified_at'),
            default => $query->orderByRaw('card.annual_fee is null')->orderBy('card.annual_fee'),
        };

        // Trang, đường dẫn truyền tường minh thay vì đọc từ resolver tĩnh của Paginator.
        return $query->orderBy('financial_products.name')
            ->paginate($perPage, page: max(1, $page))
            ->withPath(route('compare.credit-cards.index'))
            ->appends($filters->toQuery());
    }
}
