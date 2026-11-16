<?php

namespace App\Modules\DataSync\Scrapers\Savings;

use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\HtmlTable;
use App\Modules\DataSync\Support\SavingsParsing;
use App\Modules\DataSync\Support\ScrapeException;
use Illuminate\Support\Carbon;

/**
 * OCB: trang Lãi suất (Angular, dựng sẵn ở máy chủ) có bảng VND khách hàng cá nhân, lãi cuối kỳ.
 * Cột "Tiết kiệm thông thường" = tại quầy, "Tiết kiệm Online" = online; cột "Tiền gửi có kỳ hạn" (hợp đồng tiền gửi) bỏ qua.
 * Ngày hiệu lực: dòng "Lãi suất tiền gửi tiết kiệm áp dụng từ: dd/mm/yyyy".
 */
class Ocb extends SavingsScraper
{
    protected function bank(): string
    {
        return 'ocb';
    }

    public function url(): string
    {
        return 'https://ocb.com.vn/vi/cong-cu/lai-suat';
    }

    protected function read(Fetcher $http): ?Carbon
    {
        $html = $http->html($this->url());
        $table = HtmlTable::find($html, 'tiết kiệm online');
        if ($table === null) {
            throw ScrapeException::layoutChanged('không thấy bảng có cột "Tiết kiệm Online".');
        }

        $grid = HtmlTable::grid($table);
        $columns = [];
        foreach ($grid[0] as $col => $label) {
            $label = mb_strtolower(trim($label));
            if ($label === 'tiết kiệm online') {
                $columns[$col] = SavingsChannel::Online;
            } elseif ($label === 'tiết kiệm thông thường') {
                $columns[$col] = SavingsChannel::Counter;
            }
        }
        if (count($columns) < 2) {
            throw ScrapeException::layoutChanged('bảng lãi suất không còn cột "Tiết kiệm thông thường" và "Tiết kiệm Online".');
        }

        foreach (array_slice($grid, 1) as $row) {
            $term = SavingsParsing::term($row[0]);
            foreach ($term === null ? [] : $columns as $col => $channel) {
                $this->row($term, $channel, SavingsParsing::rate($row[$col] ?? ''));
            }
        }

        return preg_match('#áp dụng từ:\s*(?:<[^>]+>\s*)*(\d{2})/(\d{2})/(\d{4})#u', $html, $m)
            ? Carbon::create((int) $m[3], (int) $m[2], (int) $m[1])->startOfDay()
            : null;
    }
}
