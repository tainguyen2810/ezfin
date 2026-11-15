<?php

namespace App\Modules\DataSync\Scrapers\Savings;

use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\SavingsParsing;
use App\Modules\DataSync\Support\ScrapeException;
use Illuminate\Support\Carbon;

/**
 * BIDV: trang tra cứu lãi suất gọi dịch vụ JSON InterestDetailServlet (POST), trả bảng lãi tiền gửi cá nhân
 * khu vực Hà Nội (hanoi) và TP.HCM (hcm). Lấy khu vực Hà Nội, cột VND, ngày hiệu lực ở day_vi.
 */
class Bidv extends SavingsScraper
{
    public const API = 'https://bidv.com.vn/ServicesBIDV/InterestDetailServlet';

    protected function bank(): string
    {
        return 'bidv';
    }

    public function url(): string
    {
        return 'https://bidv.com.vn/vn/tra-cuu-lai-suat';
    }

    protected function read(Fetcher $http): ?Carbon
    {
        $data = $http->decode($http->post(self::API), self::API);
        $region = $data['hanoi'] ?? null;

        if (! is_array($region['data'] ?? null)) {
            throw ScrapeException::layoutChanged('dịch vụ lãi suất không có dữ liệu khu vực Hà Nội.');
        }

        foreach ($region['data'] as $item) {
            $term = SavingsParsing::term((string) ($item['title_vi'] ?? ''));
            if ($term !== null) {
                $this->row($term, SavingsChannel::Counter, SavingsParsing::rate((string) ($item['VND'] ?? '')));
            }
        }

        $day = (string) ($region['day_vi'] ?? '');

        return preg_match('#^\d{2}/\d{2}/\d{4}$#', $day) ? Carbon::createFromFormat('d/m/Y', $day)->startOfDay() : null;
    }
}
