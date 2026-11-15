<?php

namespace App\Modules\DataSync\Scrapers\Markets;

use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\ScrapeException;
use App\Modules\DataSync\Support\ScrapeResult;
use Illuminate\Support\Carbon;

/**
 * Giá vàng PNJ: trang công khai giavang.pnj.com.vn đọc từ dịch vụ get-gold-price (JSON, đơn vị nghìn đồng/lượng).
 * Lấy giá vàng miếng SJC tại PNJ (khu vực TP.HCM) và bảng giá vàng nữ trang; bỏ bảng "nguyên liệu mua ngoài" (chỉ có giá mua).
 * Thay cho nguồn SJC khi sjc.com.vn chặn truy cập tự động. Không có robots.txt chặn; ghi rõ nguồn PNJ khi hiển thị.
 * Kiểm tra độ hợp lý, so sánh biến động và ghi dữ liệu dùng chung với SjcGold.
 */
class PnjGold extends SjcGold
{
    public const SOURCE = 'PNJ';

    public const API = 'https://edge-cf-api.pnj.io/ecom-frontend/v3/get-gold-price';

    public function key(): string
    {
        return 'gold.pnj';
    }

    public function name(): string
    {
        return self::SOURCE;
    }

    public function url(): string
    {
        return 'https://giavang.pnj.com.vn/';
    }

    protected function barProduct(): string
    {
        return 'vàng miếng sjc';
    }

    public function scrape(Fetcher $http): ScrapeResult
    {
        $data = $http->json(self::API);
        $locations = collect($data['locations'] ?? []);
        if ($locations->isEmpty()) {
            throw ScrapeException::layoutChanged('dịch vụ giá vàng PNJ không trả bảng giá.');
        }

        if (! preg_match('#(\d{1,2})/(\d{1,2})/(\d{4})\s+(\d{1,2}):(\d{2})#', (string) ($data['updated_text'] ?? ''), $m)) {
            throw ScrapeException::layoutChanged('không đọc được giờ cập nhật giá vàng PNJ.');
        }

        // Nghìn đồng/lượng ("136.900") → đồng/lượng.
        $amount = function ($value): ?string {
            $digits = preg_replace('/\D/', '', (string) $value);

            return $digits === '' || (int) $digits === 0 ? null : number_format((int) $digits * 1000, 2, '.', '');
        };
        $row = fn (string $product, array $item) => ['product' => $product, 'buy' => $amount($item['gia_mua'] ?? null), 'sell' => $amount($item['gia_ban'] ?? null)];

        $rows = [];
        $hcm = $locations->first(fn ($location) => str_contains(mb_strtolower((string) ($location['name'] ?? '')), 'hcm')) ?? $locations->first();
        foreach ($hcm['gold_type'] ?? [] as $item) {
            if (mb_strtoupper(trim((string) ($item['name'] ?? ''))) === 'SJC') {
                $rows[] = $row('Vàng miếng SJC', $item);
            }
        }
        $jewelry = $locations->first(fn ($location) => str_contains(mb_strtolower((string) ($location['name'] ?? '')), 'nữ trang'));
        foreach ($jewelry['gold_type'] ?? [] as $item) {
            $name = trim(preg_replace('/\s+/u', ' ', (string) ($item['name'] ?? '')));
            if ($name !== '') {
                $rows[] = $row($name, $item);
            }
        }

        return new ScrapeResult([
            'quoted_at' => Carbon::create((int) $m[3], (int) $m[2], (int) $m[1], (int) $m[4], (int) $m[5])->format('Y-m-d H:i'),
            'rows' => collect($rows)->unique('product')->values()->all(),
        ]);
    }
}
