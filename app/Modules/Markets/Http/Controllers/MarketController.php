<?php

namespace App\Modules\Markets\Http\Controllers;

use App\Modules\Markets\Actions\MarketBoard;
use App\Modules\Markets\Models\FxRate;
use App\Modules\Markets\Models\GoldPrice;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketController
{
    public function fx(Request $request, MarketBoard $board): View
    {
        $batches = $board->fx();
        abort_if($batches === [], 404);

        // Đổi tiền nhanh (form GET, không lưu): số ngoại tệ theo bảng của nguồn mới nhất.
        $main = $batches[0];
        $currencies = collect($main['rows'])->mapWithKeys(fn (array $row) => [$row['quote']->currency => $row['quote']->currency.' – '.$row['quote']->currencyName()]);
        $code = is_string($code = $request->query('ma')) && $currencies->has($code) ? $code : ($currencies->has('USD') ? 'USD' : $currencies->keys()->first());
        $amount = is_string($value = $request->query('so_tien')) && preg_match('/^\d{1,9}([.,]\d{1,2})?$/', $value) ? (float) str_replace(',', '.', $value) : null;
        $amount = $amount > 0 ? $amount : null;
        $chartRow = collect($main['rows'])->first(fn (array $row) => $row['quote']->currency === $code)['quote'];

        return view('markets::fx', [
            'batches' => $batches,
            'currencies' => $currencies,
            'code' => $code,
            'amount' => $amount,
            'rate' => $chartRow,
            'history' => $board->fxHistory($main['source'], $code),
            'goldAvailable' => GoldPrice::query()->exists(),
        ]);
    }

    public function gold(MarketBoard $board): View
    {
        $batches = $board->gold();
        abort_if($batches === [], 404);
        $main = $batches[0]['rows'][0]['quote'];

        return view('markets::gold', [
            'batches' => $batches,
            'main' => $main,
            'history' => $board->goldHistory($main->source, $main->product),
            'fxAvailable' => FxRate::query()->exists(),
        ]);
    }
}
