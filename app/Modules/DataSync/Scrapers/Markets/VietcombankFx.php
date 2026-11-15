<?php

namespace App\Modules\DataSync\Scrapers\Markets;

use App\Modules\DataSync\Contracts\Scraper;
use App\Modules\DataSync\Enums\SyncKind;
use App\Modules\DataSync\Models\SyncDraft;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\HtmlTable;
use App\Modules\DataSync\Support\ScrapeException;
use App\Modules\DataSync\Support\ScrapeResult;
use App\Modules\Markets\Actions\RecordQuote;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Tỷ giá Vietcombank: trang Tỷ giá nhúng bảng dạng JSON trong <input id="currentDataExchange"> (mua tiền mặt, mua chuyển khoản, bán;
 * 0.00 = không mua tiền mặt). Chỉ lấy các ngoại tệ EzFin hiển thị.
 */
class VietcombankFx implements Scraper
{
    public const SOURCE = 'Vietcombank';

    /** Khoảng hợp lý của giá bán USD (đồng) để phát hiện đọc sai. */
    public const USD_RANGE = [20_000, 35_000];

    /** Đổi quá mức này (%) so với lần đã duyệt thì cảnh báo, không tự duyệt. */
    public const SUSPICIOUS_CHANGE_PERCENT = 3;

    public function key(): string
    {
        return 'fx.vietcombank';
    }

    public function kind(): SyncKind
    {
        return SyncKind::Fx;
    }

    public function name(): string
    {
        return self::SOURCE;
    }

    public function url(): string
    {
        return 'https://www.vietcombank.com.vn/vi-VN/KHCN/Cong-cu-tien-ich/Ty-gia';
    }

    /** @return array<string, string> */
    private function currencies(): array
    {
        return RecordQuote::fxCurrencies();
    }

    public function scrape(Fetcher $http): ScrapeResult
    {
        $input = HtmlTable::document($http->html($this->url()))->getElementById('currentDataExchange');
        $data = $input ? json_decode($input->getAttribute('value'), true) : null;
        if (! is_array($data['Data'] ?? null)) {
            throw ScrapeException::layoutChanged('không thấy bảng tỷ giá (currentDataExchange).');
        }

        try {
            $quotedAt = Carbon::parse((string) ($data['UpdatedDate'] ?? ''))->setTimezone(config('app.timezone'));
        } catch (Throwable) {
            throw ScrapeException::layoutChanged('không đọc được giờ cập nhật tỷ giá.');
        }

        $amount = fn ($value) => is_numeric($value) && (float) $value > 0 ? number_format((float) $value, 2, '.', '') : null;
        $rows = collect($data['Data'])
            ->filter(fn ($item) => array_key_exists($item['currencyCode'] ?? '', $this->currencies()))
            ->map(fn ($item) => [
                'currency' => $item['currencyCode'],
                'buy_cash' => $amount($item['cash'] ?? null),
                'buy_transfer' => $amount($item['transfer'] ?? null),
                'sell' => $amount($item['sell'] ?? null),
            ])
            ->values()->all();

        return new ScrapeResult(['quoted_at' => $quotedAt->format('Y-m-d H:i'), 'rows' => $rows]);
    }

    public function validate(array $payload): array
    {
        $rows = collect($payload['rows'] ?? []);
        $errors = [];

        if ($rows->count() < 5) {
            $errors[] = "Chỉ đọc được {$rows->count()} ngoại tệ (cần ít nhất 5).";
        }
        $usd = $rows->firstWhere('currency', 'USD');
        if ($usd === null) {
            $errors[] = 'Không có tỷ giá USD.';
        } elseif ($usd['sell'] === null || (float) $usd['sell'] < self::USD_RANGE[0] || (float) $usd['sell'] > self::USD_RANGE[1]) {
            $errors[] = "Giá bán USD = {$usd['sell']} nằm ngoài khoảng hợp lý.";
        }
        foreach ($rows as $row) {
            if ($row['sell'] === null) {
                $errors[] = "{$row['currency']} không có giá bán.";
            }
            foreach (['buy_cash', 'buy_transfer'] as $buy) {
                if ($row[$buy] !== null && $row['sell'] !== null && (float) $row[$buy] > (float) $row['sell']) {
                    $errors[] = "{$row['currency']}: giá mua {$row[$buy]} lớn hơn giá bán {$row['sell']}.";
                }
            }
        }
        if (isset($payload['quoted_at']) && Carbon::parse($payload['quoted_at'])->gt(now()->addMinutes(5))) {
            $errors[] = "Giờ công bố {$payload['quoted_at']} ở tương lai.";
        }

        return $errors;
    }

    public function compare(array $payload, ?array $previous): array
    {
        $old = collect($previous['rows'] ?? [])->keyBy('currency');

        return collect($payload['rows'] ?? [])->map(function ($row) use ($old) {
            $before = $old->get($row['currency']);
            if (! $before || ! $before['sell'] || ! $row['sell']) {
                return null;
            }
            $change = abs((float) $row['sell'] / (float) $before['sell'] - 1) * 100;

            return $change > self::SUSPICIOUS_CHANGE_PERCENT
                ? "Giá bán {$row['currency']} đổi ".number_format($change, 1, ',', '.')."% so với lần trước ({$before['sell']} → {$row['sell']})."
                : null;
        })->filter()->values()->all();
    }

    public function apply(SyncDraft $draft): string
    {
        $record = app(RecordQuote::class);

        return DB::transaction(function () use ($draft, $record) {
            foreach ($draft->rows() as $row) {
                $record->fx([...$row, 'source' => self::SOURCE, 'quoted_at' => $draft->payload['quoted_at'], 'source_url' => $this->url()]);
            }

            return 'Đã ghi tỷ giá '.count($draft->rows()).' ngoại tệ, công bố lúc '.Carbon::parse($draft->payload['quoted_at'])->format('H:i d/m/Y').'.';
        });
    }
}
