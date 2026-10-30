<?php

namespace App\Modules\Personal\Actions;

use App\Modules\Personal\Enums\CategoryType;
use App\Modules\Personal\Enums\TransactionType;
use App\Modules\Personal\Models\Category;
use App\Modules\Personal\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Thu chi của người đang đăng nhập (không tính chuyển tiền giữa các ví). Dùng cho Tổng quan và Báo cáo.
 */
class CashFlow
{
    /** Màu phân loại cố định (đã kiểm tra tương phản và mù màu), gán theo thứ tự danh mục để mỗi danh mục giữ màu qua các tháng. */
    public const PALETTE = ['#2a78d6', '#eb6834', '#1baf7a', '#eda100', '#e87ba4', '#008300', '#6250d6', '#e34948'];

    public const OTHER_COLOR = '#94a3b8';

    /** @return array{income: int, expense: int, net: int} */
    public function totals(Carbon $from, Carbon $to): array
    {
        $sums = Transaction::incomeOrExpense()
            ->whereBetween('occurred_on', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('type, sum(amount) as total')->groupBy('type')->pluck('total', 'type');

        $income = (int) round((float) ($sums[TransactionType::Income->value] ?? 0));
        $expense = (int) round((float) ($sums[TransactionType::Expense->value] ?? 0));

        return ['income' => $income, 'expense' => $expense, 'net' => $income - $expense];
    }

    /**
     * Thu, chi từng tháng, kết thúc ở $lastMonth.
     *
     * @return Collection<int, array{month: Carbon, income: int, expense: int, net: int}>
     */
    public function monthly(Carbon $lastMonth, int $months): Collection
    {
        $first = $lastMonth->copy()->startOfMonth()->subMonths($months - 1);
        $rows = Transaction::incomeOrExpense()
            ->whereBetween('occurred_on', [$first->toDateString(), $lastMonth->copy()->endOfMonth()->toDateString()])
            ->get(['type', 'occurred_on', 'amount']);

        return collect(range(0, $months - 1))->map(function (int $i) use ($first, $rows) {
            $month = $first->copy()->addMonths($i);
            $inMonth = $rows->filter(fn (Transaction $t) => $t->occurred_on->format('Y-m') === $month->format('Y-m'));
            $income = (int) round($inMonth->where('type', TransactionType::Income)->sum(fn (Transaction $t) => (float) $t->amount));
            $expense = (int) round($inMonth->where('type', TransactionType::Expense)->sum(fn (Transaction $t) => (float) $t->amount));

            return ['month' => $month, 'income' => $income, 'expense' => $expense, 'net' => $income - $expense];
        });
    }

    /**
     * Chi tiêu theo danh mục, lớn nhất trước; quá $limit danh mục thì gộp phần còn lại thành "Danh mục khác".
     *
     * @return Collection<int, array{category: ?Category, label: string, amount: int, percent: int, color: string}>
     */
    public function expenseByCategory(Carbon $from, Carbon $to, int $limit = 6): Collection
    {
        return $this->byCategory(CategoryType::Expense, $from, $to, $limit);
    }

    /** @return Collection<int, array{category: ?Category, label: string, amount: int, percent: int, color: string}> */
    public function byCategory(CategoryType $type, Carbon $from, Carbon $to, int $limit = 6): Collection
    {
        $sums = Transaction::where('type', $type === CategoryType::Expense ? TransactionType::Expense : TransactionType::Income)
            ->whereBetween('occurred_on', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('category_id, sum(amount) as total')->groupBy('category_id')->pluck('total', 'category_id')
            ->map(fn ($total) => (int) round((float) $total))->filter()->sortDesc();

        $categories = Category::ofType($type)->orderBy('sort')->orderBy('id')->get()->values();
        $total = max(1, $sums->sum());
        // Màu theo vị trí danh mục; nếu trùng màu với phần đã vẽ thì lấy màu kế tiếp còn trống.
        $used = [];
        $color = function (Category $category) use ($categories, &$used): string {
            $slot = $categories->search(fn (Category $c) => $c->is($category)) % count(self::PALETTE);
            $free = array_values(array_diff(array_keys(self::PALETTE), $used));
            $slot = in_array($slot, $free, true) ? $slot : ($free[0] ?? $slot);
            $used[] = $slot;

            return self::PALETTE[$slot];
        };

        $rows = $sums->take($limit)->map(function (int $amount, $categoryId) use ($categories, $color, $total) {
            $category = $categories->firstWhere('id', $categoryId);

            return [
                'category' => $category,
                'label' => $category?->name ?? 'Không rõ danh mục',
                'amount' => $amount,
                'percent' => (int) round($amount / $total * 100),
                'color' => $category ? $color($category) : self::OTHER_COLOR,
            ];
        })->values();

        $rest = $sums->skip($limit)->sum();
        if ($rest > 0) {
            $rows->push(['category' => null, 'label' => 'Danh mục khác', 'amount' => $rest, 'percent' => (int) round($rest / $total * 100), 'color' => self::OTHER_COLOR]);
        }

        return $rows;
    }
}
