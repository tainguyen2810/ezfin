<?php

namespace App\Modules\Compare\Actions;

use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Compare\Support\BankAccountFilters;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Tìm tài khoản thanh toán đã xuất bản theo bộ lọc. Thứ tự chỉ theo tiêu chí người dùng chọn,
 * không theo đối tác. Trường chưa có thông tin luôn xếp cuối.
 */
class SearchBankAccounts
{
    public const PER_PAGE = 12;

    public function handle(BankAccountFilters $filters, int $page = 1, int $perPage = self::PER_PAGE): LengthAwarePaginator
    {
        $query = FinancialProduct::query()
            ->select('financial_products.*')
            ->join('bank_account_details as account', 'account.product_id', '=', 'financial_products.id')
            ->ofType(ProductType::BankAccount)
            ->published()
            ->whereHas('institution', fn (Builder $query) => $query->active())
            ->with(['institution', 'bankAccount']);

        if ($filters->institution !== null) {
            $query->whereHas('institution', fn (Builder $query) => $query->where('slug', $filters->institution));
        }

        if (($maxFee = $filters->maxMonthlyFee()) !== null) {
            $query->where('account.monthly_fee', '<=', $maxFee);
        }

        if ($filters->feature !== null) {
            $query->whereJsonContains('account.features', $filters->feature->value);
        }

        match ($filters->sort) {
            'so-du-thap' => $query->orderByRaw('account.min_balance is null')->orderBy('account.min_balance'),
            'chuyen-khoan-re' => $query->orderByRaw('account.transfer_fee is null')->orderBy('account.transfer_fee'),
            'moi-xac-minh' => $query->orderByDesc('financial_products.verified_at'),
            default => $query->orderByRaw('account.monthly_fee is null')->orderBy('account.monthly_fee'),
        };

        // Trang, đường dẫn truyền tường minh thay vì đọc từ resolver tĩnh của Paginator.
        return $query->orderBy('financial_products.name')
            ->paginate($perPage, page: max(1, $page))
            ->withPath(route('compare.bank-accounts.index'))
            ->appends($filters->toQuery());
    }
}
