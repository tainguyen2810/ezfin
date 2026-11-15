<?php

namespace App\Modules\DataSync\Scrapers\Savings;

use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\HtmlTable;
use App\Modules\DataSync\Support\SavingsParsing;
use App\Modules\DataSync\Support\ScrapeException;
use Illuminate\Support\Carbon;

/**
 * VietinBank: trang lãi suất khách hàng cá nhân có bảng HTML (kỳ hạn | VND | USD | EUR). Nhãn kỳ hạn dạng khoảng
 * ("Từ 6 tháng đến dưới 7 tháng") lấy mốc đầu. robots.txt chặn /api nên chỉ đọc trang HTML.
 */
class Vietinbank extends SavingsScraper
{
    protected function bank(): string
    {
        return 'vietinbank';
    }

    public function institution(): array
    {
        return ['slug' => 'vietinbank', 'name' => 'Ngân hàng TMCP Công Thương Việt Nam', 'short_name' => 'VietinBank', 'website' => 'https://www.vietinbank.vn'];
    }

    public function url(): string
    {
        return 'https://www.vietinbank.vn/ca-nhan/cong-cu-tien-ich/lai-suat-khcn';
    }

    protected function read(Fetcher $http): ?Carbon
    {
        $table = HtmlTable::find($http->html($this->url()), 'kỳ hạn', 'vnd');
        if ($table === null) {
            throw ScrapeException::layoutChanged('không thấy bảng "Kỳ hạn / VND".');
        }

        $grid = HtmlTable::grid($table);
        $column = collect($grid[0])->search(fn ($cell) => str_starts_with(mb_strtolower($cell), 'vnd'));
        if ($column === false) {
            throw ScrapeException::layoutChanged('bảng lãi suất không có cột VND.');
        }

        foreach (array_slice($grid, 1) as $row) {
            $term = SavingsParsing::term($row[0]);
            if ($term !== null) {
                $this->row($term, SavingsChannel::Counter, SavingsParsing::rate($row[$column] ?? ''));
            }
        }

        return null;
    }
}
