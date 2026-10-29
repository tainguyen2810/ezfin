<?php

namespace App\Modules\Compare\Actions;

use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Models\SavingsRate;
use App\Modules\Compare\Support\SavingsFilters;
use App\Modules\Compare\Support\SavingsInterest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * So sánh lãi suất tiết kiệm cho một số tiền và kỳ hạn: mỗi sản phẩm lấy mức lãi áp dụng được,
 * xếp theo tiền lãi nhận được (cao xuống thấp). Không xét đối tác hay hoa hồng.
 */
class CompareSavingsRates
{
    /** @return Collection<int, array{rate: SavingsRate, interest: int, total: int}> */
    public function handle(SavingsFilters $filters): Collection
    {
        $rates = SavingsRate::query()
            ->where('term_months', $filters->term)
            ->when($filters->channel, fn (Builder $query, $channel) => $query->where('channel', $channel->value))
            ->when($filters->payment, fn (Builder $query, $payment) => $query->where('interest_payment', $payment->value))
            ->whereDate('effective_from', '<=', today())
            ->whereHas('product', fn (Builder $query) => $query->ofType(ProductType::Savings)->published()
                ->whereHas('institution', fn (Builder $query) => $query->active()))
            ->with('product.institution')
            ->get();

        return $rates
            // Mỗi biểu lãi suất (sản phẩm, kênh, lĩnh lãi) chỉ dùng lần công bố mới nhất đã có hiệu lực.
            ->groupBy(fn (SavingsRate $rate) => $rate->product_id.'|'.$rate->channel->value.'|'.$rate->interest_payment->value)
            ->flatMap(function (Collection $table) {
                $latest = $table->max(fn (SavingsRate $rate) => $rate->effective_from->toDateString());

                return $table->filter(fn (SavingsRate $rate) => $rate->effective_from->toDateString() === $latest);
            })
            // Bậc số tiền áp dụng được; nhiều bậc khớp thì lấy bậc có mức "từ" cao nhất.
            ->filter(fn (SavingsRate $rate) => (float) $rate->min_amount <= $filters->amount
                && ($rate->max_amount === null || $filters->amount < (float) $rate->max_amount))
            ->groupBy(fn (SavingsRate $rate) => $rate->product_id.'|'.$rate->channel->value.'|'.$rate->interest_payment->value)
            ->map(fn (Collection $tiers) => $tiers->sortByDesc(fn (SavingsRate $rate) => (float) $rate->min_amount)->first())
            ->map(fn (SavingsRate $rate) => [
                'rate' => $rate,
                'interest' => $interest = SavingsInterest::simple($filters->amount, $rate->rate, $filters->term),
                'total' => $filters->amount + $interest,
            ])
            // Mỗi sản phẩm một dòng: lựa chọn cho tiền lãi cao nhất.
            ->groupBy(fn (array $result) => $result['rate']->product_id)
            ->map(fn (Collection $options) => $options->sortByDesc('interest')->first())
            ->sort(fn (array $a, array $b) => [$b['interest'], (float) $b['rate']->rate, $a['rate']->product->name]
                <=> [$a['interest'], (float) $a['rate']->rate, $b['rate']->product->name])
            ->values();
    }
}
