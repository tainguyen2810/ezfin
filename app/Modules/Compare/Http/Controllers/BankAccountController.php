<?php

namespace App\Modules\Compare\Http\Controllers;

use App\Modules\Compare\Actions\FindSimilarBankAccounts;
use App\Modules\Compare\Actions\SearchBankAccounts;
use App\Modules\Compare\Enums\BankAccountFeature;
use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Compare\Support\BankAccountFacts;
use App\Modules\Compare\Support\BankAccountFilters;
use App\Modules\Compare\Support\BankAccountSchema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class BankAccountController
{
    public function index(Request $request, SearchBankAccounts $search): View|RedirectResponse
    {
        $filters = BankAccountFilters::fromRequest($request);
        $page = is_numeric($page = $request->query('page')) ? max(1, (int) $page) : 1;

        // Một bộ lọc = một URL; tham số khác (utm_..., gclid) giữ nguyên.
        $canonical = $filters->toQuery() + ($page > 1 ? ['page' => $page] : []);
        $current = Arr::only($request->query(), [...BankAccountFilters::QUERY_KEYS, 'page']);
        if (array_map('strval', $canonical) != $current) {
            return redirect()->route('compare.bank-accounts.index', [...Arr::except($request->query(), [...BankAccountFilters::QUERY_KEYS, 'page']), ...$canonical]);
        }

        return view('compare::bank-accounts.index', [
            'filters' => $filters,
            'accounts' => $search->handle($filters, $page),
            'institutions' => FinancialInstitution::query()->active()
                ->whereHas('products', fn (Builder $query) => $query->ofType(ProductType::BankAccount)->published())
                ->orderBy('short_name')
                ->pluck('short_name', 'slug'),
            'features' => collect(BankAccountFeature::cases())->mapWithKeys(fn (BankAccountFeature $feature) => [$feature->slug() => $feature->getLabel()]),
            'fees' => collect(BankAccountFilters::FEES)->map(fn (array $fee) => $fee['label']),
        ]);
    }

    public function show(string $slug, FindSimilarBankAccounts $similar): View
    {
        $account = FinancialProduct::query()
            ->ofType(ProductType::BankAccount)
            ->published()
            ->where('slug', $slug)
            ->whereHas('institution', fn (Builder $query) => $query->active())
            ->has('bankAccount')
            ->with(['institution', 'bankAccount'])
            ->firstOrFail();

        return view('compare::bank-accounts.show', [
            'account' => $account,
            'facts' => BankAccountFacts::for($account),
            'features' => BankAccountFacts::features($account),
            'similar' => $similar->handle($account),
            'schema' => BankAccountSchema::for($account),
        ]);
    }
}
