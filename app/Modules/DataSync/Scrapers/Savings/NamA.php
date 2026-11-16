<?php

namespace App\Modules\DataSync\Scrapers\Savings;

use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\HtmlTable;
use App\Modules\DataSync\Support\SavingsParsing;
use App\Modules\DataSync\Support\ScrapeException;
use Illuminate\Support\Carbon;

/**
 * Nam A Bank: trang /lai-suat liệt kê các trang biểu lãi, đường dẫn mang ngày hiệu lực, ví dụ
 * /lai-suat-tien-gui-vnd-hieu-luc-20102026 (tại quầy: lãi cuối kỳ, hàng tháng, đầu kỳ, hàng quý) và
 * /lai-suat-tiet-kiem-truc-tuyen-online-nam-hieu-luc-ngay-20102026 (online, lãi cuối kỳ). Lấy trang mới nhất mỗi loại.
 * Kỳ hạn ghi "6 tháng, Từ 180 - 209 ngày" hoặc "24 tháng (**)": lấy phần trước dấu phẩy, bỏ chú thích.
 */
class NamA extends SavingsScraper
{
    private const BASE = 'https://www.namabank.com.vn';

    private const PAGES = [
        'counter' => '#href="(/lai-suat-tien-gui-vnd-hieu-luc-(?:ngay-)?(\d{2})(\d{2})(\d{4}))"#',
        'online' => '#href="(/lai-suat-tiet-kiem-truc-tuyen-online-nam-hieu-luc-(?:ngay-)?(\d{2})(\d{2})(\d{4}))"#',
    ];

    private const PAYMENTS = [
        'lãi cuối kỳ' => InterestPayment::EndOfTerm,
        'lãi hàng tháng' => InterestPayment::Monthly,
        'lãi đầu kỳ' => InterestPayment::Upfront,
        'lãi hàng quý' => InterestPayment::Quarterly,
    ];

    protected function bank(): string
    {
        return 'namabank';
    }

    public function url(): string
    {
        return self::BASE.'/lai-suat';
    }

    protected function read(Fetcher $http): ?Carbon
    {
        $index = $http->html($this->url());
        $effective = null;

        foreach (self::PAGES as $channel => $pattern) {
            preg_match_all($pattern, $index, $matches, PREG_SET_ORDER);
            if ($matches === []) {
                throw ScrapeException::layoutChanged('trang Lãi suất không còn liên kết biểu lãi '.($channel === 'counter' ? 'tiền gửi VND' : 'tiết kiệm trực tuyến').'.');
            }
            usort($matches, fn ($a, $b) => strcmp($b[4].$b[3].$b[2], $a[4].$a[3].$a[2]));
            [, $path, $day, $month, $year] = $matches[0];

            $this->readTable($http->html(self::BASE.$path), SavingsChannel::from($channel));
            $date = Carbon::create((int) $year, (int) $month, (int) $day)->startOfDay();
            $effective = $effective === null || $date->gt($effective) ? $date : $effective;
        }

        return $effective;
    }

    private function readTable(string $html, SavingsChannel $channel): void
    {
        $table = HtmlTable::find($html, 'kỳ hạn', 'lãi cuối kỳ');
        if ($table === null) {
            throw ScrapeException::layoutChanged('không thấy bảng có cột "Kỳ hạn" và "Lãi cuối kỳ".');
        }

        $grid = HtmlTable::grid($table);
        $columns = [];
        foreach ($grid[0] as $col => $label) {
            $payment = self::PAYMENTS[mb_strtolower(trim($label))] ?? null;
            if ($payment) {
                $columns[$col] = $payment;
            }
        }

        foreach (array_slice($grid, 1) as $row) {
            $label = trim(preg_replace('/\(\*+\)/u', '', explode(',', $row[0])[0]));
            $term = SavingsParsing::term($label);
            foreach ($term ? $columns : [] as $col => $payment) {
                $this->row($term, $channel, SavingsParsing::rate($row[$col] ?? ''), $payment);
            }
        }
    }
}
