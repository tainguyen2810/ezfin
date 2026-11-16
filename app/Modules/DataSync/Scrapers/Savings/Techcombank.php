<?php

namespace App\Modules\DataSync\Scrapers\Savings;

use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\PdfText;
use App\Modules\DataSync\Support\SavingsParsing;
use App\Modules\DataSync\Support\ScrapeException;
use Illuminate\Support\Carbon;

/**
 * Techcombank: trang Biểu phí lãi suất liệt kê PDF "Biểu lãi suất tiền gửi tiết kiệm/tiền gửi có kỳ hạn" (tên tệp có ngày
 * yyyymmdd, lấy tệp mới nhất). Trong PDF, bảng "Tiết kiệm Phát Lộc tại quầy" = tại quầy, "Tiền gửi Phát Lộc Online" = online.
 * Mỗi dòng có 12 cột: 4 hạng khách (Private, Priority, Inspire, KH thường) × 3 bậc tiền (≥3 tỷ, 1–3 tỷ, <1 tỷ). Lấy cột
 * khách hàng thường, dưới 1 tỷ (cột cuối) là mức phổ biến nhất.
 */
class Techcombank extends SavingsScraper
{
    public const BASE = 'https://techcombank.com';

    private const PDF = '#/content/dam/techcombank/public-site/documents/techcombank-bieu-lai-suat-tien-gui-tiet-kiem-tien-gui-co-ky-han-(\d{8})\.pdf#';

    protected function bank(): string
    {
        return 'techcombank';
    }

    public function url(): string
    {
        return self::BASE.'/cong-cu-tien-ich/bieu-phi-lai-suat';
    }

    protected function read(Fetcher $http): ?Carbon
    {
        preg_match_all(self::PDF, $http->html($this->url()), $matches, PREG_SET_ORDER);
        if ($matches === []) {
            throw ScrapeException::layoutChanged('không thấy tệp PDF biểu lãi suất tiền gửi có kỳ hạn.');
        }
        usort($matches, fn ($a, $b) => strcmp($b[1], $a[1]));

        $lines = array_merge(...$http->pdf(self::BASE.$matches[0][0]));
        $channel = null;
        foreach ($lines as $line) {
            $upper = mb_strtoupper($line);
            if (str_contains($upper, 'PHÁT LỘC TẠI QUẦY')) {
                $channel = SavingsChannel::Counter;
            } elseif (str_contains($upper, 'PHÁT LỘC ONLINE') && ! str_contains($upper, 'ATM')) {
                $channel = SavingsChannel::Online;
            } elseif (preg_match('/^(TIẾT KIỆM|TIỀN GỬI) /u', $upper)) {
                $channel = null;
            }

            $cells = PdfText::cells($line);
            $term = $channel === null || count($cells) !== 13 ? null : SavingsParsing::term($cells[0]);
            if ($term !== null) {
                $this->row($term, $channel, SavingsParsing::rate($cells[12]));
            }
        }

        $text = implode(' ', $lines);
        if (preg_match('/áp dụng từ ngày (\d{1,2}) tháng (\d{1,2}) năm (\d{4})/iu', $text, $m)) {
            return Carbon::create((int) $m[3], (int) $m[2], (int) $m[1])->startOfDay();
        }

        return Carbon::createFromFormat('Ymd', $matches[0][1])->startOfDay();
    }
}
