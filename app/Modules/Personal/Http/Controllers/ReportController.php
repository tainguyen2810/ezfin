<?php

namespace App\Modules\Personal\Http\Controllers;

use App\Modules\Personal\Actions\CashFlow;
use App\Modules\Personal\Enums\CategoryType;
use App\Modules\Personal\Support\Period;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController
{
    public function index(Request $request, CashFlow $cashFlow): View
    {
        $period = Period::fromQuery($request->query('ky'), $request->query('moc'));
        $previous = $period->previous();

        return view('personal::reports.index', [
            'period' => $period,
            'totals' => $cashFlow->totals($period->start, $period->end()),
            'previousTotals' => $cashFlow->totals($previous->start, $previous->end()),
            'monthly' => $cashFlow->monthly($period->end(), $period->chartMonths()),
            'spending' => $cashFlow->expenseByCategory($period->start, $period->end()),
            'expenseRows' => $cashFlow->byCategory(CategoryType::Expense, $period->start, $period->end(), PHP_INT_MAX),
            'incomeRows' => $cashFlow->byCategory(CategoryType::Income, $period->start, $period->end(), PHP_INT_MAX),
        ]);
    }
}
