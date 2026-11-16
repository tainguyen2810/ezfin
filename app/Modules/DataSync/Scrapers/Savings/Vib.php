<?php

namespace App\Modules\DataSync\Scrapers\Savings;

use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\SavingsParsing;
use App\Modules\DataSync\Support\ScrapeException;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * VIB: trang /vn/tiet-kiem tải bảng lãi từ API /PwsStaticData/api/saving-rate/core (mảng JSON, mỗi phần tử một mức lãi).
 * productType 531 = "Tiền gửi có kỳ hạn" tại quầy, 524 = "Tiền gửi trực tuyến". crIntFreq: rỗng = lĩnh lãi cuối kỳ,
 * 1T = hằng tháng, 3T = hằng quý. balance là mức trần của bậc số tiền; lấy bậc thấp nhất (dưới 300 triệu).
 * Mỗi mức có thể có nhiều effectDate: lấy ngày mới nhất đã hiệu lực.
 */
class Vib extends SavingsScraper
{
    private const PRODUCTS = ['531' => SavingsChannel::Counter, '524' => SavingsChannel::Online];

    private const PAYMENTS = ['' => InterestPayment::EndOfTerm, '1T' => InterestPayment::Monthly, '3T' => InterestPayment::Quarterly];

    protected function bank(): string
    {
        return 'vib';
    }

    public function url(): string
    {
        return 'https://www.vib.com.vn/vn/tiet-kiem';
    }

    protected function read(Fetcher $http): ?Carbon
    {
        $items = collect($http->json('https://www.vib.com.vn/PwsStaticData/api/saving-rate/core'))
            ->filter(fn ($item) => is_array($item) && ($item['ccy'] ?? '') === 'VND' && isset(self::PRODUCTS[(string) ($item['productType'] ?? '')]))
            ->map(fn ($item) => $item + ['date' => $this->date($item['effectDate'] ?? null)])
            ->filter(fn ($item) => $item['date'] !== null && $item['date']->lte(now()));

        if ($items->isEmpty()) {
            throw ScrapeException::layoutChanged('API lãi suất VIB không còn sản phẩm 531/524 bằng VND.');
        }

        $effective = null;
        foreach ($items->groupBy('productType') as $product => $rows) {
            $lowest = $rows->min(fn ($item) => (float) $item['balance']);
            $latest = $rows->filter(fn ($item) => (float) $item['balance'] === $lowest)
                ->sortBy(fn ($item) => $item['date']->timestamp)
                ->keyBy(fn ($item) => $item['term'].'|'.($item['crIntFreq'] ?? ''));

            foreach ($latest as $item) {
                $term = SavingsParsing::term((string) $item['term']);
                $payment = self::PAYMENTS[(string) ($item['crIntFreq'] ?? '')] ?? null;
                if ($term === null || $payment === null || ! is_numeric($item['actualRate'] ?? null)) {
                    continue;
                }
                $this->row($term, self::PRODUCTS[(string) $product], SavingsParsing::rate((string) $item['actualRate']), $payment);
                $effective = $effective === null || $item['date']->gt($effective) ? $item['date'] : $effective;
            }
        }

        return $effective;
    }

    private function date(mixed $value): ?Carbon
    {
        try {
            return is_string($value) ? Carbon::parse($value)->startOfDay() : null;
        } catch (Throwable) {
            return null;
        }
    }
}
