<?php

namespace App\Modules\Compare\Http\Controllers;

use App\Modules\Compare\Actions\FindComparableCreditCards;
use App\Modules\Compare\Actions\FindSimilarCreditCards;
use App\Modules\Compare\Actions\SearchCreditCards;
use App\Modules\Compare\Enums\CardBenefit;
use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Compare\Support\CreditCardComparison;
use App\Modules\Compare\Support\CreditCardFilters;
use App\Modules\Compare\Support\CreditCardSchema;
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

    public function compare(Request $request, FindComparableCreditCards $find): View|RedirectResponse
    {
        $ids = $request->query('ids');
        $requested = is_string($ids) ? array_map('trim', explode(',', $ids)) : [];
        $cards = $find->handle($requested);

        // Bỏ slug không hợp lệ, trùng, quá 3 hoặc thẻ đã ngừng xuất bản khỏi URL.
        $canonical = $cards->pluck('slug')->implode(',');
        if ($canonical !== ($ids ?? '')) {
            return redirect()->route('compare.credit-cards.compare', [
                ...Arr::except($request->query(), 'ids'),
                ...($canonical !== '' ? ['ids' => $canonical] : []),
            ]);
        }

        return view('compare::credit-cards.compare', [
            'cards' => $cards,
            'rows' => CreditCardComparison::rows($cards),
            'products' => $cards->map(fn ($card) => [
                'name' => $card->name,
                'institution' => $card->institution->short_name,
                'logo' => $card->institution->logoUrl(),
                'url' => route('compare.credit-cards.show', $card->slug),
                'removeUrl' => route('compare.credit-cards.compare', array_filter([
                    'ids' => $cards->where('slug', '!=', $card->slug)->pluck('slug')->implode(','),
                ])),
            ])->all(),
            'max' => FindComparableCreditCards::MAX,
        ]);
    }

    public function show(string $slug, FindSimilarCreditCards $similar): View
    {
        $card = FinancialProduct::query()
            ->ofType(ProductType::CreditCard)
            ->published()
            ->where('slug', $slug)
            ->whereHas('institution', fn (Builder $query) => $query->active())
            ->has('creditCard')
            ->with(['institution', 'creditCard'])
            ->firstOrFail();

        $rows = collect(CreditCardComparison::rows(collect([$card])));

        return view('compare::credit-cards.show', [
            'card' => $card,
            'facts' => $rows->whereNotIn('label', ['Nhóm lợi ích', 'Ưu điểm', 'Hạn chế'])->mapWithKeys(fn (array $row) => [$row['label'] => $row['values'][0]]),
            'similar' => $similar->handle($card),
            'schema' => CreditCardSchema::for($card),
        ]);
    }
}
