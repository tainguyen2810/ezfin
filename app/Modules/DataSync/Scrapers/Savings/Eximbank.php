<?php

namespace App\Modules\DataSync\Scrapers\Savings;

use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\PdfText;
use App\Modules\DataSync\Support\SavingsParsing;
use App\Modules\DataSync\Support\ScrapeException;
use Illuminate\Support\Carbon;

/**
 * Eximbank: trang Lãi suất tiết kiệm liệt kê PDF "TB LSHD VND KHBL hieu luc tu ngay ddmmyyyy" (lấy ngày mới nhất).
 * Bảng A.1 "Tiết kiệm, tiền gửi cá nhân" (tại quầy): cột Lãnh lãi trước, hàng tháng, hàng quý, hàng 6 tháng, hàng năm,
 * cuối kỳ; ô trống không in ra nên chỉ chắc chắn: số cuối = cuối kỳ, số đầu = lãi trước, số thứ hai (khi có từ 3 số) = hàng tháng.
 */
class Eximbank extends SavingsScraper
{
    private const FILES = 'https://media.eximbank.com.vn/exim/files/';

    protected function bank(): string
    {
        return 'eximbank';
    }

    public function url(): string
    {
        return 'https://eximbank.com.vn/lai-suat-tiet-kiem';
    }

    protected function read(Fetcher $http): ?Carbon
    {
        $html = str_replace('\\/', '/', $http->html($this->url()));
        preg_match_all('#media\.eximbank\.com\.vn/exim/files/(TB(?:%20| )LSHD(?:%20| )VND(?:%20| )KHBL[^"\'<>]*?(\d{2})(\d{2})(\d{4})\.pdf)#i', $html, $matches, PREG_SET_ORDER);
        if ($matches === []) {
            throw ScrapeException::layoutChanged('không thấy tệp PDF thông báo lãi suất huy động VND khách hàng cá nhân.');
        }
        usort($matches, fn ($a, $b) => strcmp($b[4].$b[3].$b[2], $a[4].$a[3].$a[2]));
        $file = rawurlencode(rawurldecode($matches[0][1]));

        $lines = array_merge(...$http->pdf(self::FILES.$file));
        $inTable = false;
        foreach ($lines as $line) {
            $upper = mb_strtoupper($line);
            if (str_starts_with($upper, 'A.1')) {
                $inTable = true;

                continue;
            }
            if ($inTable && preg_match('/^A\.\s*\d|^A\.\d|^B\./u', $upper)) {
                break;
            }

            $cells = PdfText::cells($line);
            $term = $inTable && $cells !== [] ? SavingsParsing::term($cells[0]) : null;
            $values = array_values(array_filter(array_map(fn ($cell) => SavingsParsing::rate($cell), array_slice($cells, 1))));
            if ($term === null || $values === []) {
                continue;
            }

            $this->row($term, SavingsChannel::Counter, end($values));
            if (count($values) >= 2) {
                $this->row($term, SavingsChannel::Counter, $values[0], InterestPayment::Upfront);
            }
            if (count($values) >= 3) {
                $this->row($term, SavingsChannel::Counter, $values[1], InterestPayment::Monthly);
            }
        }

        $text = preg_replace('/\s+/u', ' ', implode(' ', $lines));

        return preg_match('/hiệu lực từ\s*ngày\s*(\d{1,2})\s*tháng\s*(\d{1,2})\s*năm\s*(\d{4})/iu', $text, $m)
            ? Carbon::create((int) $m[3], (int) $m[2], (int) $m[1])->startOfDay()
            : Carbon::create((int) $matches[0][4], (int) $matches[0][3], (int) $matches[0][2])->startOfDay();
    }
}
