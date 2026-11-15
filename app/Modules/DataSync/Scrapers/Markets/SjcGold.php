<?php

namespace App\Modules\DataSync\Scrapers\Markets;

use App\Modules\DataSync\Contracts\Scraper;
use App\Modules\DataSync\Enums\SyncKind;
use App\Modules\DataSync\Models\SyncDraft;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\ScrapeException;
use App\Modules\DataSync\Support\ScrapeResult;
use App\Modules\Markets\Actions\RecordQuote;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Giá vàng SJC: trang "Giá vàng online" gọi dịch vụ PriceService.ashx (POST method=GetCurrentGoldPricesByBranch, chi nhánh
 * Hồ Chí Minh), trả giá mua/bán đồng/lượng và giờ cập nhật "HH:mm dd/mm/yyyy". robots.txt cho phép, tín hiệu use=reference.
 */
class SjcGold implements Scraper
{
    public const SOURCE = 'SJC';

    public const API = 'https://sjc.com.vn/GoldPrice/Services/PriceService.ashx';

    /** Giá bán vàng miếng hợp lý (đồng/lượng). */
    public const BAR_RANGE = [50_000_000, 500_000_000];

    public const SUSPICIOUS_CHANGE_PERCENT = 8;

    public function key(): string
    {
        return 'gold.sjc';
    }

    public function kind(): SyncKind
    {
        return SyncKind::Gold;
    }

    public function name(): string
    {
        return self::SOURCE;
    }

    public function url(): string
    {
        return 'https://sjc.com.vn/gia-vang-online';
    }

    public function scrape(Fetcher $http): ScrapeResult
    {
        $data = $http->decode($http->post(self::API, ['method' => 'GetCurrentGoldPricesByBranch', 'BranchId' => 1]), self::API);
        if (($data['success'] ?? false) !== true || ! is_array($data['data'] ?? null)) {
            throw ScrapeException::layoutChanged('dịch vụ giá vàng SJC không trả dữ liệu.');
        }

        $quotedAt = (string) ($data['latestDate'] ?? '');
        if (! preg_match('#^(\d{1,2}):(\d{2}) (\d{1,2})/(\d{1,2})/(\d{4})$#', $quotedAt, $m)) {
            throw ScrapeException::layoutChanged("không đọc được giờ cập nhật \"{$quotedAt}\".");
        }

        $amount = fn ($value) => is_numeric($value) && (float) $value > 0 ? number_format(round((float) $value), 2, '.', '') : null;
        $rows = collect($data['data'])->map(fn ($item) => [
            'product' => trim(preg_replace('/\s+/u', ' ', (string) ($item['TypeName'] ?? ''))),
            'buy' => $amount($item['BuyValue'] ?? null),
            'sell' => $amount($item['SellValue'] ?? null),
        ])->filter(fn ($row) => $row['product'] !== '')->unique('product')->values()->all();

        return new ScrapeResult([
            'quoted_at' => Carbon::create((int) $m[5], (int) $m[4], (int) $m[3], (int) $m[1], (int) $m[2])->format('Y-m-d H:i'),
            'rows' => $rows,
        ]);
    }

    public function validate(array $payload): array
    {
        $rows = collect($payload['rows'] ?? []);
        $errors = [];

        if ($rows->count() < 3) {
            $errors[] = "Chỉ đọc được {$rows->count()} loại vàng (cần ít nhất 3).";
        }
        $bar = $rows->first(fn ($row) => str_contains(mb_strtolower($row['product']), $this->barProduct()));
        if ($bar === null) {
            $errors[] = 'Không có giá vàng miếng SJC.';
        } elseif ($bar['sell'] === null || (float) $bar['sell'] < self::BAR_RANGE[0] || (float) $bar['sell'] > self::BAR_RANGE[1]) {
            $errors[] = "Giá bán {$bar['product']} = {$bar['sell']} nằm ngoài khoảng hợp lý.";
        }
        foreach ($rows as $row) {
            if ($row['sell'] === null) {
                $errors[] = "{$row['product']} không có giá bán.";
            } elseif ($row['buy'] !== null && (float) $row['buy'] > (float) $row['sell']) {
                $errors[] = "{$row['product']}: giá mua lớn hơn giá bán.";
            }
        }
        if (isset($payload['quoted_at']) && Carbon::parse($payload['quoted_at'])->gt(now()->addMinutes(5))) {
            $errors[] = "Giờ công bố {$payload['quoted_at']} ở tương lai.";
        }

        return $errors;
    }

    /** Chữ (thường) trong tên dòng giá vàng miếng SJC dùng để kiểm tra độ hợp lý. */
    protected function barProduct(): string
    {
        return 'vàng sjc 1l';
    }

    public function compare(array $payload, ?array $previous): array
    {
        $old = collect($previous['rows'] ?? [])->keyBy('product');

        return collect($payload['rows'] ?? [])->map(function ($row) use ($old) {
            $before = $old->get($row['product']);
            if (! $before || ! $before['sell'] || ! $row['sell']) {
                return null;
            }
            $change = abs((float) $row['sell'] / (float) $before['sell'] - 1) * 100;

            return $change > self::SUSPICIOUS_CHANGE_PERCENT
                ? "Giá bán {$row['product']} đổi ".number_format($change, 1, ',', '.').'% so với lần trước.'
                : null;
        })->filter()->values()->all();
    }

    public function apply(SyncDraft $draft): string
    {
        $record = app(RecordQuote::class);

        return DB::transaction(function () use ($draft, $record) {
            foreach ($draft->rows() as $row) {
                $record->gold([...$row, 'source' => static::SOURCE, 'quoted_at' => $draft->payload['quoted_at'], 'source_url' => $this->url()]);
            }

            return 'Đã ghi giá '.count($draft->rows()).' loại vàng, công bố lúc '.Carbon::parse($draft->payload['quoted_at'])->format('H:i d/m/Y').'.';
        });
    }
}
