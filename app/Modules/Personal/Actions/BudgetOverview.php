<?php

namespace App\Modules\Personal\Actions;

use App\Modules\Personal\Enums\TransactionType;
use App\Modules\Personal\Models\Budget;
use App\Modules\Personal\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Ngân sách một tháng của người đăng nhập: từng danh mục có hạn mức, số đã chi, còn lại, tỷ lệ.
 * Dùng cho trang Ngân sách và Tổng quan.
 */
class BudgetOverview
{
    /** Ngưỡng cảnh báo sắp hết ngân sách (%). */
    public const WARNING_PERCENT = 80;

    /**
     * @return Collection<int, object{budget: Budget, spent: int, limit: int, remaining: int, percent: int, over: bool, warning: bool}>
     */
    public function handle(Carbon $month): Collection
    {
        $budgets = Budget::with('category')->where('month', $month->copy()->startOfMonth()->toDateString())->get();

        $spent = Transaction::query()->inMonth($month)
            ->where('type', TransactionType::Expense)
            ->whereIn('category_id', $budgets->pluck('category_id'))
            ->selectRaw('category_id, SUM(amount) as total')->groupBy('category_id')
            ->pluck('total', 'category_id');

        return $budgets
            ->map(function (Budget $budget) use ($spent) {
                $limit = (int) round((float) $budget->amount);
                $used = (int) round((float) ($spent[$budget->category_id] ?? 0));
                $percent = $limit > 0 ? (int) round($used / $limit * 100) : 0;

                return (object) [
                    'budget' => $budget,
                    'spent' => $used,
                    'limit' => $limit,
                    'remaining' => $limit - $used,
                    'percent' => $percent,
                    'over' => $used > $limit,
                    'warning' => $used <= $limit && $percent >= self::WARNING_PERCENT,
                ];
            })
            ->sortBy(fn ($row) => [$row->budget->category->sort, $row->budget->category->name])
            ->values();
    }
}
