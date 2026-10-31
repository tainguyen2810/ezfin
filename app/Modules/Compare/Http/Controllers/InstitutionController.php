<?php

namespace App\Modules\Compare\Http\Controllers;

use App\Modules\Compare\Actions\SavingsRateBoard;
use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

/** Trang ngân hàng, công ty tài chính: lãi suất tiết kiệm, lịch sử lãi suất, thẻ và tài khoản đang so sánh. */
class InstitutionController
{
    public function index(SavingsRateBoard $board): View
    {
        $institutions = FinancialInstitution::query()->active()
            ->whereHas('products', fn (Builder $query) => $query->published())
            ->withCount(['products' => fn (Builder $query) => $query->published()])
            ->orderBy('short_name')->orderBy('name')
            ->get();
        $best = $board->matrix(null)->mapWithKeys(fn (array $row) => [$row['institution']->id => $row['rates'][12]]);

        return view('compare::institutions.index', ['institutions' => $institutions, 'best' => $best]);
    }

    public function show(string $slug, SavingsRateBoard $board): View
    {
        $institution = FinancialInstitution::query()->active()->where('slug', $slug)->firstOrFail();
        $products = FinancialProduct::query()->published()->where('institution_id', $institution->id)
            ->with(['creditCard', 'bankAccount'])->orderBy('name')->get();
        abort_if($products->isEmpty(), 404);

        return view('compare::institutions.show', [
            'institution' => $institution,
            'rates' => $board->forInstitution($institution),
            'history' => $board->history($institution),
            'cards' => $products->where('type', ProductType::CreditCard)->filter->creditCard->values(),
            'accounts' => $products->where('type', ProductType::BankAccount)->filter->bankAccount->values(),
        ]);
    }
}
