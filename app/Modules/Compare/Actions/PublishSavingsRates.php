<?php

namespace App\Modules\Compare\Actions;

use App\Modules\Compare\Enums\ProductStatus;
use App\Modules\Compare\Enums\ProductType;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Compare\Models\SavingsRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Ghi một biểu lãi suất tiết kiệm đã được duyệt (từ nguồn tự động) thành một lần công bố mới.
 *
 * Mỗi kênh (online, tại quầy) là một sản phẩm riêng của ngân hàng, slug cố định "<ngân hàng>-tiet-kiem-online"
 * / "<ngân hàng>-tiet-kiem-tai-quay"; chưa có thì tạo (đã xuất bản vì người duyệt đã kiểm tra, có nguồn và ngày xác minh).
 * Lần công bố cũ giữ nguyên làm lịch sử; cùng ngày hiệu lực thì thay bằng biểu mới.
 */
class PublishSavingsRates
{
    /**
     * @param  array{slug: string, name: string, short_name: string, website: string}  $institution
     * @param  list<array{term: int, channel: string, payment: string, min_amount: int, max_amount: ?int, rate: string}>  $rows
     * @return array{rates: int, products: int, effective_from: Carbon}
     */
    public function handle(array $institution, string $sourceUrl, array $rows, ?Carbon $effectiveFrom = null): array
    {
        return DB::transaction(function () use ($institution, $sourceUrl, $rows, $effectiveFrom) {
            $bank = FinancialInstitution::query()->where('slug', $institution['slug'])->first()
                ?? app(UpdateInstitutionProfile::class)->handle($institution);

            $count = 0;
            $date = null;
            $byChannel = collect($rows)->groupBy('channel');

            foreach ($byChannel as $channel => $channelRows) {
                $product = $this->product($bank, SavingsChannel::from($channel), $sourceUrl);
                $date = $this->effectiveDate($product, $effectiveFrom);

                SavingsRate::query()->where('product_id', $product->id)->whereDate('effective_from', $date)->get()->each->delete();

                foreach ($channelRows as $row) {
                    $rate = new SavingsRate([
                        'term_months' => $row['term'],
                        'channel' => $channel,
                        'interest_payment' => $row['payment'],
                        'min_amount' => number_format($row['min_amount'], 2, '.', ''),
                        'max_amount' => $row['max_amount'] === null ? null : number_format($row['max_amount'], 2, '.', ''),
                        'rate' => $row['rate'],
                        'effective_from' => $date,
                    ]);
                    $rate->product()->associate($product);
                    $rate->save();
                    $count++;
                }
            }

            return ['rates' => $count, 'products' => $byChannel->count(), 'effective_from' => $date ?? today()];
        });
    }

    private function product(FinancialInstitution $bank, SavingsChannel $channel, string $sourceUrl): FinancialProduct
    {
        $slug = $bank->slug.'-tiet-kiem-'.$channel->slug();
        $product = FinancialProduct::query()->where('slug', $slug)->first();

        if ($product === null) {
            $product = new FinancialProduct([
                'type' => ProductType::Savings,
                'name' => ($channel === SavingsChannel::Online ? 'Tiết kiệm online ' : 'Tiết kiệm tại quầy ').$bank->short_name,
                'slug' => Str::limit($slug, 150, ''),
                'status' => ProductStatus::Published,
            ]);
            $product->institution()->associate($bank);
        }

        $product->fill([
            'source_url' => $sourceUrl,
            'source_note' => 'Tự động lấy từ trang lãi suất chính thức của ngân hàng, đã được quản trị viên duyệt.',
            'verified_at' => today(),
            'next_review_at' => null,
        ])->save();

        return $product;
    }

    /**
     * Ngày hiệu lực ngân hàng công bố; nếu không có, ở tương lai, hoặc cũ hơn lần công bố đang có thì dùng hôm nay
     * để biểu vừa duyệt trở thành mức đang áp dụng.
     */
    private function effectiveDate(FinancialProduct $product, ?Carbon $effectiveFrom): Carbon
    {
        $date = ($effectiveFrom ?? today())->copy()->startOfDay();
        $latest = SavingsRate::query()->where('product_id', $product->id)->max('effective_from');

        if ($date->isFuture() || ($latest !== null && $date->lt(Carbon::parse($latest)->startOfDay()))) {
            return today();
        }

        return $date;
    }
}
