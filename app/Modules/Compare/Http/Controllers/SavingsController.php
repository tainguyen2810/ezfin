<?php

namespace App\Modules\Compare\Http\Controllers;

use App\Modules\Compare\Actions\CompareSavingsRates;
use App\Modules\Compare\Actions\SavingsRateBoard;
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

    /** Bảng tổng hợp: mỗi ngân hàng một dòng, mỗi kỳ hạn một cột; mặc định kênh online. */
    public function table(Request $request, SavingsRateBoard $board): View
    {
        $channel = SavingsChannel::fromSlug((string) $request->query('kenh')) ?? SavingsChannel::Online;
        $rows = $board->matrix($channel);
        // Mức cao nhất mỗi kỳ hạn; bỏ cột kỳ hạn không ngân hàng nào công bố.
        $best = array_filter(collect(SavingsFilters::TERMS)->mapWithKeys(fn (int $term) => [$term => $rows->max(fn (array $row) => $row['rates'][$term])])->all(),
            fn (?float $rate) => $rate !== null);

        return view('compare::savings.table', [
            'channel' => $channel,
            'rows' => $rows,
            'terms' => array_keys($best),
            'best' => $best,
            'channels' => collect(SavingsChannel::cases())->mapWithKeys(fn (SavingsChannel $c) => [$c->slug() => $c->getLabel()]),
        ]);
    }
}
