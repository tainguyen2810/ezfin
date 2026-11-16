<?php

namespace App\Modules\DataSync\Scrapers\Savings;

use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\HtmlTable;
use App\Modules\DataSync\Support\SavingsParsing;
use App\Modules\DataSync\Support\ScrapeException;
use DOMXPath;
use Illuminate\Support\Carbon;

/**
 * MSB: trang lãi suất tiết kiệm nhúng bảng lãi dạng JSON trong thuộc tính data-config của .msb-saving-rate-table.
 * ratesTableVND.tai_quay / truc_tuyen: mỗi dòng một kỳ hạn (ky_han "12M"), mỗi cột một sản phẩm. Lấy "Rút gốc từng phần"
 * (lĩnh lãi cuối kỳ), "Định kỳ sinh lời" (hằng tháng), "Trả lãi ngay" (đầu kỳ). Ngày hiệu lực theo dòng "Lãi suất cập nhật lúc".
 */
class Msb extends SavingsScraper
{
    private const CHANNELS = ['tai_quay' => SavingsChannel::Counter, 'truc_tuyen' => SavingsChannel::Online];

    private const PRODUCTS = [
        'ROT_GOC_TUNG_PHAN' => InterestPayment::EndOfTerm,
        'DINH_KY_SINH_LOI' => InterestPayment::Monthly,
        'TRA_LAI_NGAY' => InterestPayment::Upfront,
    ];

    protected function bank(): string
    {
        return 'msb';
    }

    public function url(): string
    {
        return 'https://www.msb.com.vn/khach-hang-ca-nhan/lai-suat-tiet-kiem/';
    }

    protected function read(Fetcher $http): ?Carbon
    {
        $xpath = new DOMXPath(HtmlTable::document($http->html($this->url())));
        $box = HtmlTable::firstByClass($xpath, 'msb-saving-rate-table');
        $config = $box ? json_decode($box->getAttribute('data-config'), true) : null;
        $tables = $config['ratesTableVND'] ?? null;

        if (! is_array($tables)) {
            throw ScrapeException::layoutChanged('không thấy bảng lãi suất VND (data-config của msb-saving-rate-table).');
        }

        foreach (self::CHANNELS as $key => $channel) {
            foreach ($tables[$key] ?? [] as $line) {
                $term = SavingsParsing::term((string) ($line['ky_han'] ?? ''));
                if ($term === null || $term === 0) {
                    continue;
                }
                foreach (self::PRODUCTS as $product => $payment) {
                    $value = $line[$product] ?? null;
                    if (is_numeric($value) && $value > 0) {
                        $this->row($term, $channel, SavingsParsing::rate((string) $value), $payment);
                    }
                }
            }
        }

        $updated = HtmlTable::firstByClass($xpath, 'update-time');
        if ($updated && preg_match('#(\d{1,2})/(\d{1,2})/(\d{4})#', HtmlTable::text($updated), $m)) {
            return Carbon::create((int) $m[3], (int) $m[2], (int) $m[1])->startOfDay();
        }

        return null;
    }
}
