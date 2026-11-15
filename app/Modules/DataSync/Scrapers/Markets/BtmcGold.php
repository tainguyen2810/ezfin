<?php

namespace App\Modules\DataSync\Scrapers\Markets;

use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\HtmlTable;
use App\Modules\DataSync\Support\ScrapeException;
use App\Modules\DataSync\Support\ScrapeResult;
use Illuminate\Support\Carbon;

/**
 * Giá vàng Bảo Tín Minh Châu: bảng giá trên trang chủ btmc.vn (HTML dựng sẵn, robots.txt cho phép "/").
 * Giá ghi theo nghìn đồng/chỉ ("ĐVT 1 = 1.000 VNĐ"), đổi ra đồng/lượng (×10 chỉ ×1.000). Bỏ đồng xu 0.1 chỉ và dòng
 * chỉ có giá mua ("Liên hệ"). Kiểm tra độ hợp lý, so sánh biến động và ghi dữ liệu dùng chung với SjcGold.
 */
class BtmcGold extends SjcGold
{
    public const SOURCE = 'Bảo Tín Minh Châu';

    public function key(): string
    {
        return 'gold.btmc';
    }

    public function name(): string
    {
        return self::SOURCE;
    }

    public function url(): string
    {
        return 'https://btmc.vn/';
    }

    protected function barProduct(): string
    {
        return 'vàng miếng sjc';
    }

    public function scrape(Fetcher $http): ScrapeResult
    {
        $html = $http->html($this->url());
        $table = HtmlTable::find($html, 'loại vàng', 'mua vào', 'bán ra');
        if ($table === null) {
            throw ScrapeException::layoutChanged('không thấy bảng giá vàng.');
        }

        $text = HtmlTable::text(HtmlTable::document($html)->documentElement);
        if (! preg_match('#cập nhật lúc\s*(\d{1,2})/(\d{1,2})/(\d{4})\s+(\d{1,2}):(\d{2})#iu', $text, $m)) {
            throw ScrapeException::layoutChanged('không đọc được giờ cập nhật giá vàng.');
        }
        if (! preg_match('#ĐVT\s*:?\s*1\s*=\s*1[.,]000\s*VN#u', $text)) {
            throw ScrapeException::layoutChanged('đơn vị giá trên trang đã đổi (không còn "1 = 1.000 VNĐ").');
        }

        $grid = HtmlTable::grid($table);
        $header = array_map('mb_strtolower', $grid[0]);
        $col = fn (string $needle) => collect($header)->search(fn ($label) => str_contains($label, $needle));
        [$name, $buy, $sell] = [$col('loại vàng'), $col('mua vào'), $col('bán ra')];
        if ($name === false || $buy === false || $sell === false) {
            throw ScrapeException::layoutChanged('bảng giá vàng thiếu cột tên, mua vào hoặc bán ra.');
        }

        // Nghìn đồng/chỉ → đồng/lượng.
        $amount = function (string $value): ?string {
            $digits = preg_replace('/\D/', '', $value);

            return $digits === '' || (int) $digits === 0 ? null : number_format((int) $digits * 10_000, 2, '.', '');
        };

        $rows = [];
        foreach (array_slice($grid, 1) as $row) {
            $product = $this->productName($row[$name] ?? '');
            if ($product === '' || preg_match('/\bxu\b/iu', $product) || $amount($row[$sell] ?? '') === null) {
                continue;
            }
            $rows[] = ['product' => $product, 'buy' => $amount($row[$buy] ?? ''), 'sell' => $amount($row[$sell])];
        }

        return new ScrapeResult([
            'quoted_at' => Carbon::create((int) $m[3], (int) $m[2], (int) $m[1], (int) $m[4], (int) $m[5])->format('Y-m-d H:i'),
            'rows' => collect($rows)->unique('product')->values()->all(),
        ]);
    }

    /** "VÀNG MIẾNG SJC" → "Vàng Miếng SJC" (giữ chữ viết tắt). */
    private function productName(string $name): string
    {
        $name = mb_convert_case(trim(preg_replace('/\s+/u', ' ', $name)), MB_CASE_TITLE);

        return preg_replace_callback('/\b(Sjc|Vrtl|Btmc|Pnj)\b/u', fn ($m) => mb_strtoupper($m[1]), $name);
    }
}
