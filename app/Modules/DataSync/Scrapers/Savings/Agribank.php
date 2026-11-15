<?php

namespace App\Modules\DataSync\Scrapers\Savings;

use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\HtmlTable;
use App\Modules\DataSync\Support\SavingsParsing;
use App\Modules\DataSync\Support\ScrapeException;
use Illuminate\Support\Carbon;

/**
 * Agribank: trang lãi suất tiền gửi nạp bảng khách hàng cá nhân từ một thành phần WCM (lai-suat-cn). Địa chỉ thành phần
 * (có mã cmpntid) đọc từ trang, rồi lấy bảng đầu tiên có cột VND. Bảng online chỉ ghi "mức tối đa" theo khoảng kỳ hạn nên không dùng.
 */
class Agribank extends SavingsScraper
{
    public const BASE = 'https://www.agribank.com.vn';

    protected function bank(): string
    {
        return 'agribank';
    }

    public function institution(): array
    {
        return ['slug' => 'agribank', 'name' => 'Ngân hàng Nông nghiệp và Phát triển Nông thôn Việt Nam', 'short_name' => 'Agribank', 'website' => self::BASE];
    }

    public function url(): string
    {
        return self::BASE.'/vn/lai-suat-tien-gui';
    }

    protected function read(Fetcher $http): ?Carbon
    {
        $page = $http->html($this->url());
        if (! preg_match('#(/wcm/connect/ttkhac/lai-suat/lai-suat-cn\?[^\'"\s]+)#', $page, $m)) {
            throw ScrapeException::layoutChanged('không thấy địa chỉ bảng lãi suất cá nhân (lai-suat-cn).');
        }

        $table = HtmlTable::find($http->html(self::BASE.html_entity_decode($m[1])), 'vnd', 'tháng');
        if ($table === null) {
            throw ScrapeException::layoutChanged('không thấy bảng lãi suất có cột VND.');
        }

        $grid = HtmlTable::grid($table);
        $column = collect($grid[0])->search(fn ($cell) => mb_strtolower($cell) === 'vnd');
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
