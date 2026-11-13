<?php

namespace App\Modules\Compare\Http\Controllers;

use App\Modules\Compare\Actions\FindSimilarLoans;
use App\Modules\Compare\Actions\SearchLoans;
use App\Modules\Compare\Enums\LoanKind;
use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Compare\Support\LoanFacts;
use App\Modules\Compare\Support\LoanFilters;
use App\Modules\Compare\Support\LoanSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class LoanController
{
    public function index(Request $request, SearchLoans $search): View|RedirectResponse
    {
        $filters = LoanFilters::fromRequest($request);
        $page = is_numeric($page = $request->query('page')) ? max(1, (int) $page) : 1;

        // Một bộ lọc = một URL; tham số khác (utm_..., gclid) giữ nguyên.
        $canonical = $filters->toQuery() + ($page > 1 ? ['page' => $page] : []);
        $current = Arr::only($request->query(), [...LoanFilters::QUERY_KEYS, 'page']);
        if (array_map('strval', $canonical) != $current) {
            return redirect()->route('compare.loans.index', [...Arr::except($request->query(), [...LoanFilters::QUERY_KEYS, 'page']), ...$canonical]);
        }

        return view('compare::loans.index', [
            'filters' => $filters,
            'loans' => $search->handle($filters, $page),
            'institutions' => FinancialInstitution::query()->active()
                ->whereHas('products', fn (Builder $query) => $query->ofType(ProductType::Loan)->published())
                ->orderBy('short_name')
                ->pluck('short_name', 'slug'),
            'kinds' => collect(LoanKind::cases())->mapWithKeys(fn (LoanKind $kind) => [$kind->slug() => $kind->getLabel()]),
        ]);
    }

    public function show(string $slug, FindSimilarLoans $similar): View
    {
        $loan = FinancialProduct::query()
            ->ofType(ProductType::Loan)
            ->published()
            ->where('slug', $slug)
            ->whereHas('institution', fn (Builder $query) => $query->active())
            ->has('loan')
            ->with(['institution', 'loan'])
            ->firstOrFail();

        return view('compare::loans.show', [
            'loan' => $loan,
            'facts' => LoanFacts::full($loan),
            'similar' => $similar->handle($loan),
            'schema' => LoanSchema::for($loan),
        ]);
    }
}
