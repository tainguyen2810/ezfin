<?php

namespace App\Modules\Personal\Http\Controllers;

use App\Modules\Personal\Actions\BudgetOverview;
use App\Modules\Personal\Actions\CreateDefaultCategories;
use App\Modules\Personal\Enums\CategoryType;
use App\Modules\Personal\Models\Budget;
use App\Modules\Personal\Models\Category;
use App\Modules\Personal\Support\Month;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BudgetController
{
    public function index(Request $request, BudgetOverview $overview): View
    {
        $month = Month::fromQuery($request->query('thang'));
        $rows = $overview->handle($month);

        return view('personal::budgets.index', [
            'month' => $month,
            'rows' => $rows,
            'limit' => $rows->sum('limit'),
            'spent' => $rows->sum('spent'),
            'hasPrevious' => Budget::where('month', $month->copy()->subMonth()->toDateString())->exists(),
        ]);
    }

    public function edit(Request $request, CreateDefaultCategories $defaults): View
    {
        $defaults->handle($request->user());
        $month = Month::fromQuery($request->query('thang'));

        return view('personal::budgets.edit', [
            'month' => $month,
            'categories' => Category::active()->ofType(CategoryType::Expense)->orderBy('sort')->orderBy('name')->get(),
            'amounts' => Budget::where('month', $month->toDateString())->pluck('amount', 'category_id'),
        ]);
    }

    /** Lưu hạn mức của cả tháng: ô để trống hoặc 0 là bỏ ngân sách danh mục đó. */
    public function update(Request $request): RedirectResponse
    {
        $month = Month::fromQuery($request->input('thang'));
        $amounts = collect($request->input('amounts', []))
            ->map(fn ($value) => (int) preg_replace('/\D/', '', (string) $value));

        $request->merge(['amounts' => $amounts->all()]);
        $request->validate([
            'amounts' => ['array'],
            'amounts.*' => ['integer', 'min:0', 'max:999999999999999'],
        ]);
        $categoryIds = Category::ofType(CategoryType::Expense)->pluck('id');
        abort_if($amounts->keys()->diff($categoryIds)->isNotEmpty(), 422);

        DB::transaction(function () use ($amounts, $month) {
            foreach ($amounts as $categoryId => $amount) {
                $amount > 0
                    ? Budget::updateOrCreate(['category_id' => $categoryId, 'month' => $month->toDateString()], ['amount' => $amount])
                    : Budget::where('category_id', $categoryId)->where('month', $month->toDateString())->delete();
            }
        });

        return redirect()->route('personal.budgets.index', ['thang' => $month->format('Y-m')])
            ->with('toast', ['message' => 'Đã lưu ngân sách tháng '.$month->format('m/Y').'.', 'type' => 'success']);
    }

    /** Dùng lại hạn mức tháng trước cho các danh mục chưa có ngân sách tháng này. */
    public function copy(Request $request): RedirectResponse
    {
        $month = Month::fromQuery($request->input('thang'));
        $existing = Budget::where('month', $month->toDateString())->pluck('category_id');

        Budget::where('month', $month->copy()->subMonth()->toDateString())
            ->whereNotIn('category_id', $existing)
            ->whereHas('category', fn ($q) => $q->whereNull('archived_at'))
            ->get()
            ->each(fn (Budget $previous) => Budget::create([
                'category_id' => $previous->category_id, 'month' => $month->toDateString(), 'amount' => $previous->amount,
            ]));

        return redirect()->route('personal.budgets.index', ['thang' => $month->format('Y-m')])
            ->with('toast', ['message' => 'Đã chép ngân sách từ tháng trước.', 'type' => 'success']);
    }
}
