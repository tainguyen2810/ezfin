<?php

namespace App\Modules\Markets\Actions;

use App\Modules\Markets\Models\FxRate;
use App\Modules\Markets\Models\GoldPrice;
use App\Modules\Markets\Support\QuoteParser;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Ghi một dòng tỷ giá hoặc giá vàng từ dữ liệu thô (form, file). Cùng nguồn, mã/loại vàng và thời điểm thì cập nhật.
 * Giá mua lớn hơn giá bán bị từ chối vì gần như chắc chắn là nhập nhầm cột.
 */
class RecordQuote
{
    /**
     * @param  array{source?: ?string, currency?: ?string, quoted_at?: ?string, buy_cash?: ?string, buy_transfer?: ?string, sell?: ?string, source_url?: ?string}  $data
     * @return array{quote: FxRate, created: bool}
     *
     * @throws InvalidArgumentException
     */
    public function fx(array $data): array
    {
        $currency = strtoupper(trim((string) ($data['currency'] ?? '')));
        if (! array_key_exists($currency, FxRate::CURRENCIES)) {
            throw new InvalidArgumentException("Mã ngoại tệ \"{$currency}\" chưa hỗ trợ (".implode(', ', array_keys(FxRate::CURRENCIES)).').');
        }

        $sell = QuoteParser::amount($data['sell'] ?? null, 'Giá bán') ?? throw new InvalidArgumentException('Thiếu giá bán.');
        $buyCash = QuoteParser::amount($data['buy_cash'] ?? null, 'Giá mua tiền mặt');
        $buyTransfer = QuoteParser::amount($data['buy_transfer'] ?? null, 'Giá mua chuyển khoản');
        foreach ([$buyCash, $buyTransfer] as $buy) {
            if ($buy !== null && (float) $buy > (float) $sell) {
                throw new InvalidArgumentException("Giá mua {$buy} lớn hơn giá bán {$sell} ({$currency}).");
            }
        }

        return $this->save(FxRate::class, ['source' => $this->source($data), 'currency' => $currency, 'quoted_at' => QuoteParser::time($data['quoted_at'] ?? null)],
            ['buy_cash' => $buyCash, 'buy_transfer' => $buyTransfer, 'sell' => $sell, 'source_url' => $this->url($data)]);
    }

    /**
     * @param  array{source?: ?string, product?: ?string, quoted_at?: ?string, buy?: ?string, sell?: ?string, source_url?: ?string}  $data
     * @return array{quote: GoldPrice, created: bool}
     *
     * @throws InvalidArgumentException
     */
    public function gold(array $data): array
    {
        $product = trim(strip_tags((string) ($data['product'] ?? '')));
        if ($product === '' || mb_strlen($product) > 120) {
            throw new InvalidArgumentException('Thiếu loại vàng hoặc tên quá dài (tối đa 120 ký tự).');
        }

        $sell = QuoteParser::amount($data['sell'] ?? null, 'Giá bán') ?? throw new InvalidArgumentException('Thiếu giá bán.');
        $buy = QuoteParser::amount($data['buy'] ?? null, 'Giá mua');
        if ($buy !== null && (float) $buy > (float) $sell) {
            throw new InvalidArgumentException("Giá mua {$buy} lớn hơn giá bán {$sell} ({$product}).");
        }

        return $this->save(GoldPrice::class, ['source' => $this->source($data), 'product' => $product, 'quoted_at' => QuoteParser::time($data['quoted_at'] ?? null)],
            ['buy' => $buy, 'sell' => $sell, 'source_url' => $this->url($data)]);
    }

    /**
     * @template T of Model
     *
     * @param  class-string<T>  $model
     * @return array{quote: T, created: bool}
     */
    private function save(string $model, array $key, array $values): array
    {
        $key['quoted_at'] = $key['quoted_at']->format('Y-m-d H:i:s');
        $quote = $model::query()->updateOrCreate($key, $values);

        return ['quote' => $quote, 'created' => $quote->wasRecentlyCreated];
    }

    private function source(array $data): string
    {
        $source = trim(strip_tags((string) ($data['source'] ?? '')));
        if ($source === '' || mb_strlen($source) > 60) {
            throw new InvalidArgumentException('Thiếu nguồn công bố hoặc tên nguồn quá dài (tối đa 60 ký tự).');
        }

        return $source;
    }

    private function url(array $data): ?string
    {
        $url = trim((string) ($data['source_url'] ?? ''));
        if ($url === '') {
            return null;
        }
        if (! preg_match('#^https://[^\s/]+\.[^\s]+$#', $url) || strlen($url) > 500) {
            throw new InvalidArgumentException('Đường dẫn nguồn phải bắt đầu bằng https://.');
        }

        return $url;
    }
}
