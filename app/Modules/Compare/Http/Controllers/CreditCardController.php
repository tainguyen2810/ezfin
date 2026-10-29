<?php

namespace App\Modules\Compare\Http\Controllers;

use App\Modules\Compare\Actions\SearchCreditCards;
use App\Modules\Compare\Enums\CardBenefit;
use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Support\CreditCardFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class CreditCardController
{
    public function index(Request $request, SearchCreditCards $search): View|RedirectResponse
    {
        $filters = CreditCardFilters::fromRequest($request);
        $page = is_numeric($page = $request->query('page')) ? max(1, (int) $page) : 1;

        // Một bộ lọc = một URL: bỏ ô trống, giá trị lạ, giá trị mặc định (form GET gửi đủ mọi ô).
        // Tham số khác (utm_..., gclid) giữ nguyên để không mất dữ liệu đo lường.
        $canonical = $filters->toQuery() + ($page > 1 ? ['page' => $page] : []);
        $current = Arr::only($request->query(), [...CreditCardFilters::QUERY_KEYS, 'page']);
        if (array_map('strval', $canonical) != $current) {
            return redirect()->route('compare.credit-cards.index', [...Arr::except($request->query(), [...CreditCardFilters::QUERY_KEYS, 'page']), ...$canonical]);
        }

        return view('compare::credit-cards.index', [
            'filters' => $filters,
            'cards' => $search->handle($filters, $page),
            // Chỉ liệt kê ngân hàng đang có thẻ được xuất bản.
            'institutions' => FinancialInstitution::query()->active()
                ->whereHas('products', fn (Builder $query) => $query->ofType(ProductType::CreditCard)->published())
                ->orderBy('short_name')
                ->pluck('short_name', 'slug'),
            'needs' => collect(CardBenefit::cases())->mapWithKeys(fn (CardBenefit $benefit) => [$benefit->slug() => $benefit->getLabel()]),
            'fees' => collect(CreditCardFilters::FEES)->map(fn (array $fee) => $fee['label']),
        ]);
    }
}
