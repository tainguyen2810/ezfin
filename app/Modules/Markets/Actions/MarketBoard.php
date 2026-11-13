<?php

namespace App\Modules\Markets\Actions;

use App\Modules\Markets\Models\FxRate;
use App\Modules\Markets\Models\GoldPrice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Bảng tỷ giá, giá vàng mới nhất theo từng nguồn và lịch sử theo ngày. Chỉ dùng số liệu đã nhập, không nội suy.
 * "Thay đổi" so với lần công bố cuối cùng của ngày trước đó (cùng nguồn, cùng mã/loại vàng).
 */
class MarketBoard
{
    /** Sau số ngày này không có số liệu mới thì trang ghi rõ số liệu cũ. */
    public const STALE_DAYS = 3;

    public const HISTORY_DAYS = 30;

    /**
     * @return list<array{source: string, quoted_at: Carbon, source_url: ?string, stale: bool, rows: list<array{quote: FxRate, change: ?float}>}>
     */
    public function fx(): array
    {
        return $this->batches(FxRate::class, 'currency', fn (Collection $rows) => $rows
            ->sortBy(fn (FxRate $rate) => array_search($rate->currency, array_keys(FxRate::CURRENCIES), true))->values());
    }

    /**
     * @return list<array{source: string, quoted_at: Carbon, source_url: ?string, stale: bool, rows: list<array{quote: GoldPrice, change: ?float}>}>
     */
    public function gold(): array
    {
        return $this->batches(GoldPrice::class, 'product', fn (Collection $rows) => $rows->sortBy('product')->values());
    }

    /** @return list<array{date: string, value: float}> giá bán cuối mỗi ngày trong 30 ngày, ngày không có số liệu thì bỏ */
    public function fxHistory(string $source, string $currency): array
    {
        return $this->history(FxRate::query()->where('source', $source)->where('currency', $currency));
    }

    /** @return list<array{date: string, value: float}> */
    public function goldHistory(string $source, string $product): array
    {
        return $this->history(GoldPrice::query()->where('source', $source)->where('product', $product));
    }

    /**
     * Tóm tắt cho trang chủ: giá bán USD và loại vàng đầu tiên của nguồn mới nhất. Null khi chưa có dữ liệu.
     *
     * @return array{usd: ?array{quote: FxRate, change: ?float}, gold: ?array{quote: GoldPrice, change: ?float}}|null
     */
    public function summary(): ?array
    {
        $usd = collect($this->fx())->flatMap(fn (array $batch) => $batch['stale'] ? [] : $batch['rows'])->first(fn (array $row) => $row['quote']->currency === 'USD');
        $gold = collect($this->gold())->first(fn (array $batch) => ! $batch['stale'])['rows'][0] ?? null;

        return $usd || $gold ? ['usd' => $usd, 'gold' => $gold] : null;
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function batches(string $model, string $key, callable $sort): array
    {
        $latest = $model::query()->selectRaw('source, max(quoted_at) as latest')->groupBy('source')->get();

        return $latest->map(function ($row) use ($model, $key, $sort) {
            $at = Carbon::parse($row->latest);
            $quotes = $sort($model::query()->where('source', $row->source)->where('quoted_at', $at)->get());

            // Lần công bố cuối của ngày trước, theo từng mã: một truy vấn cho cả bảng.
            $previous = $model::query()->where('source', $row->source)->where('quoted_at', '<', $at->copy()->startOfDay())
                ->whereIn($key, $quotes->pluck($key))
                ->orderByDesc('quoted_at')->get()
                ->unique($key)->keyBy($key);

            return [
                'source' => $row->source,
                'quoted_at' => $at,
                // Bảng mới không ghi link thì dùng link gần nhất đã ghi của cùng nguồn.
                'source_url' => $quotes->pluck('source_url')->filter()->first()
                    ?? $model::query()->where('source', $row->source)->whereNotNull('source_url')->orderByDesc('quoted_at')->value('source_url'),
                'stale' => $at->lt(now()->subDays(self::STALE_DAYS)),
                'rows' => $quotes->map(fn (Model $quote) => [
                    'quote' => $quote,
                    'change' => ($before = $previous->get($quote->{$key})) ? round((float) $quote->sell - (float) $before->sell, 2) : null,
                ])->all(),
            ];
        })->sortByDesc(fn (array $batch) => $batch['quoted_at']->timestamp)->values()->all();
    }

    private function history($query): array
    {
        return $query->where('quoted_at', '>=', today()->subDays(self::HISTORY_DAYS - 1))
            ->orderBy('quoted_at')->get(['quoted_at', 'sell'])
            ->groupBy(fn (Model $quote) => $quote->quoted_at->toDateString())
            ->map(fn (Collection $day, string $date) => ['date' => $date, 'value' => (float) $day->last()->sell])
            ->values()->all();
    }
}
