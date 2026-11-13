<?php

namespace App\Modules\Compare\Actions;

use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\SavingsRate;
use App\Modules\Compare\Support\SavingsFilters;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Lãi suất tiết kiệm theo ngân hàng: bảng tổng hợp ngân hàng × kỳ hạn và lịch sử thay đổi.
 * Mức lãi của một ngân hàng ở một kỳ hạn = mức cao nhất đang hiệu lực trong các sản phẩm đã xuất bản,
 * ở bậc số tiền thấp nhất (mức ai cũng gửi được).
 */
class SavingsRateBoard
{
    public const HISTORY_TERMS = [6, 12];

    /**
     * @return Collection<int, array{institution: FinancialInstitution, rates: array<int, ?float>}>
     *                                                                                              xếp theo lãi 12 tháng giảm dần
     */
    public function matrix(?SavingsChannel $channel, ?CarbonInterface $at = null): Collection
    {
        return $this->current(null, $channel, $at)
            ->groupBy(fn (SavingsRate $rate) => $rate->product->institution_id)
            ->map(fn (Collection $rates) => [
                'institution' => $rates->first()->product->institution,
                'rates' => collect(SavingsFilters::TERMS)->mapWithKeys(fn (int $term) => [
                    $term => $rates->where('term_months', $term)->max(fn (SavingsRate $rate) => (float) $rate->rate),
                ])->all(),
            ])
            ->sort(fn (array $a, array $b) => [$b['rates'][12] ?? -1, $a['institution']->short_name ?? $a['institution']->name]
                <=> [$a['rates'][12] ?? -1, $b['institution']->short_name ?? $b['institution']->name])
            ->values();
    }

    /**
     * Mức lãi đang hiệu lực của một ngân hàng theo kênh và kỳ hạn.
     *
     * @return array<string, array<int, ?float>> slug kênh => [kỳ hạn => lãi]
     */
    public function forInstitution(FinancialInstitution $institution): array
    {
        $rates = $this->current($institution, null);
        $terms = [0, ...SavingsFilters::TERMS];

        return collect(SavingsChannel::cases())
            ->mapWithKeys(fn (SavingsChannel $channel) => [$channel->slug() => collect($terms)->mapWithKeys(fn (int $term) => [
                $term => $rates->where('channel', $channel)->where('term_months', $term)->max(fn (SavingsRate $rate) => (float) $rate->rate),
            ])->all()])
            ->filter(fn (array $byTerm) => array_filter($byTerm, fn ($rate) => $rate !== null) !== [])
            ->all();
    }

    /**
     * Mức lãi online (hoặc tại quầy nếu không có online) cao nhất của ngân hàng ở mỗi ngày đổi biểu lãi.
     *
     * @return array{dates: list<string>, series: array<int, list<?float>>, channel: SavingsChannel}
     */
    public function history(FinancialInstitution $institution): array
    {
        $rates = $this->published($institution)
            ->whereIn('term_months', self::HISTORY_TERMS)
            ->whereDate('effective_from', '<=', today())
            ->get();
        $channel = $rates->contains('channel', SavingsChannel::Online) ? SavingsChannel::Online : SavingsChannel::Counter;
        $rates = $rates->where('channel', $channel)->where('min_amount', $rates->min('min_amount'));

        $dates = $rates->map(fn (SavingsRate $rate) => $rate->effective_from->toDateString())->unique()->sort()->values();
        $series = collect(self::HISTORY_TERMS)->mapWithKeys(fn (int $term) => [$term => $dates->map(function (string $date) use ($rates, $term) {
            // Mỗi sản phẩm lấy biểu lãi mới nhất đã có hiệu lực tại ngày đó.
            return $rates->where('term_months', $term)
                ->filter(fn (SavingsRate $rate) => $rate->effective_from->toDateString() <= $date)
                ->groupBy('product_id')
                ->map(fn (Collection $table) => (float) $table->sortByDesc(fn (SavingsRate $rate) => $rate->effective_from->toDateString())->first()->rate)
                ->max();
        })->all()])->all();

        return ['dates' => $dates->all(), 'series' => $series, 'channel' => $channel];
    }

    /**
     * Lãi suất toàn thị trường của một kỳ hạn qua từng tháng: tại cuối mỗi tháng (tháng này: hôm nay), mức cao nhất
     * và trung bình của các ngân hàng (mỗi ngân hàng lấy mức cao nhất mọi kênh, biểu lãi đang hiệu lực, bậc tiền thấp nhất).
     * Chỉ dùng các lần công bố đã lưu; tháng chưa có ngân hàng nào có dữ liệu thì bỏ, không nội suy.
     *
     * @return list<array{month: string, date: string, best: float, average: float, banks: int}>
     */
    public function marketHistory(int $term, int $months = 12): array
    {
        // Nạp mọi kỳ hạn: lần công bố mới hơn không có kỳ hạn này thì kỳ hạn này coi như không còn (giống current()).
        $rates = $this->published(null)
            ->whereDate('effective_from', '<=', today())
            ->with('product:id,institution_id')
            ->get();

        $points = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $at = $i === 0 ? today() : today()->startOfMonth()->subMonths($i)->endOfMonth()->startOfDay();
            $byBank = $this->effectiveAt($rates, $at)
                ->where('term_months', $term)
                ->groupBy(fn (SavingsRate $rate) => $rate->product->institution_id)
                ->map(fn (Collection $bank) => $bank->max(fn (SavingsRate $rate) => (float) $rate->rate));

            if ($byBank->isNotEmpty()) {
                $points[] = [
                    'month' => $at->format('m/Y'),
                    'date' => $at->toDateString(),
                    'best' => round($byBank->max(), 2),
                    'average' => round($byBank->avg(), 2),
                    'banks' => $byBank->count(),
                ];
            }
        }

        return $points;
    }

    /**
     * Ngày công bố biểu lãi gần nhất đã có hiệu lực (của mọi ngân hàng), để ghi "cập nhật ngày" và lastmod sitemap.
     */
    public function lastPublishedAt(?int $term = null): ?Carbon
    {
        $date = $this->published(null)
            ->when($term !== null, fn (Builder $query) => $query->where('term_months', $term))
            ->whereDate('effective_from', '<=', today())
            ->max('effective_from');

        return $date ? Carbon::parse($date) : null;
    }

    /** @return Collection<int, SavingsRate> lần công bố mới nhất đã hiệu lực tại ngày $at (mặc định hôm nay), bậc số tiền thấp nhất */
    private function current(?FinancialInstitution $institution, ?SavingsChannel $channel, ?CarbonInterface $at = null): Collection
    {
        $at ??= today();

        return $this->effectiveAt($this->published($institution)
            ->when($channel, fn (Builder $query) => $query->where('channel', $channel->value))
            ->whereDate('effective_from', '<=', $at->toDateString())
            ->with('product.institution')
            ->get(), $at);
    }

    /**
     * Trong các dòng lãi đã nạp: với mỗi sản phẩm, kênh, cách lĩnh lãi, giữ lần công bố mới nhất đã hiệu lực tại $at, bậc tiền thấp nhất.
     *
     * @param  Collection<int, SavingsRate>  $rates
     * @return Collection<int, SavingsRate>
     */
    private function effectiveAt(Collection $rates, CarbonInterface $at): Collection
    {
        $day = $at->toDateString();

        return $rates->filter(fn (SavingsRate $rate) => $rate->effective_from->toDateString() <= $day)
            ->groupBy(fn (SavingsRate $rate) => $rate->product_id.'|'.$rate->channel->value.'|'.$rate->interest_payment->value)
            ->flatMap(function (Collection $table) {
                $latest = $table->max(fn (SavingsRate $rate) => $rate->effective_from->toDateString());
                $table = $table->filter(fn (SavingsRate $rate) => $rate->effective_from->toDateString() === $latest);
                $lowest = $table->min(fn (SavingsRate $rate) => (float) $rate->min_amount);

                return $table->filter(fn (SavingsRate $rate) => (float) $rate->min_amount === $lowest);
            })
            ->values();
    }

    private function published(?FinancialInstitution $institution): Builder
    {
        return SavingsRate::query()->whereHas('product', fn (Builder $query) => $query->ofType(ProductType::Savings)->published()
            ->when($institution, fn (Builder $query) => $query->where('institution_id', $institution->id))
            ->whereHas('institution', fn (Builder $query) => $query->active()));
    }
}
