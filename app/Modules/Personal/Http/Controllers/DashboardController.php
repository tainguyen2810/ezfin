<?php

namespace App\Modules\Personal\Http\Controllers;

use App\Modules\Personal\Actions\BudgetOverview;
use App\Modules\Personal\Actions\CashFlow;
use App\Modules\Personal\Actions\NetWorth;
use App\Modules\Personal\Models\Goal;
use App\Modules\Personal\Models\RecurringTransaction;
use App\Modules\Personal\Models\Transaction;
use App\Modules\Personal\Models\Wallet;
use Illuminate\View\View;

class DashboardController
{
    public function __invoke(NetWorth $netWorth, CashFlow $cashFlow, BudgetOverview $budgets): View
    {
        $month = today()->startOfMonth();
        $previous = $month->copy()->subMonth();

        return view('personal::dashboard', [
            'hasWallets' => Wallet::active()->exists(),
            'month' => $month,
            'netWorth' => $netWorth->handle(),
            'current' => $cashFlow->totals($month, $month->copy()->endOfMonth()),
            'previous' => $cashFlow->totals($previous, $previous->copy()->endOfMonth()),
            'monthly' => $cashFlow->monthly($month, 6),
            'spending' => $cashFlow->expenseByCategory($month, $month->copy()->endOfMonth()),
            'recent' => Transaction::with(['category', 'wallet'])->orderByDesc('occurred_on')->orderByDesc('id')->limit(5)->get(),
            'budgets' => $budgets->handle($month)->sortByDesc('percent')->take(3),
            // Khoản định kỳ đến hạn trong 7 ngày tới (kể cả khoản chỉ nhắc đã quá hạn chưa ghi nhận).
            'upcoming' => RecurringTransaction::with('category')->ongoing()
                ->whereDate('next_due_on', '<=', today()->addDays(7)->toDateString())
                ->orderBy('next_due_on')->orderBy('name')->limit(5)->get(),
            'today' => today(),
            'goals' => Goal::withSaved()->get()->reject->isCompleted()->sortBy(fn (Goal $goal) => $goal->deadline?->timestamp ?? PHP_INT_MAX)->take(2),
        ]);
    }
}
