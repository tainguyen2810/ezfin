<?php

namespace App\Modules\Compare\Http\Controllers;

use App\Modules\Compare\Actions\CompareSavingsRates;
use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\Compare\Models\SavingsRate;
use App\Modules\Compare\Support\SavingsFilters;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\View\View;

class SavingsController
{
    public function index(Request $request, CompareSavingsRates $compare): View|RedirectResponse
    {
        $filters = SavingsFilters::fromRequest($request);

        // Một điều kiện = một URL: bỏ giá trị mặc định, giá trị lạ; giữ tham số khác (utm_...).
        $canonical = $filters->toQuery();
        $current = Arr::only($request->query(), SavingsFilters::QUERY_KEYS);
        if (array_map('strval', $canonical) != $current) {
            return redirect()->route('compare.savings.index', [...Arr::except($request->query(), SavingsFilters::QUERY_KEYS), ...$canonical]);
        }

        return view('compare::savings.index', [
            'filters' => $filters,
            'results' => $compare->handle($filters),
            'terms' => collect(SavingsFilters::TERMS)->mapWithKeys(fn (int $months) => [$months => SavingsRate::termLabel($months)]),
            'channels' => collect(SavingsChannel::cases())->mapWithKeys(fn (SavingsChannel $channel) => [$channel->slug() => $channel->getLabel()]),
            'payments' => collect(InterestPayment::cases())->mapWithKeys(fn (InterestPayment $payment) => [$payment->slug() => $payment->getLabel()])
                ->put(SavingsFilters::ANY_PAYMENT, 'Mọi hình thức'),
        ]);
    }
}
