<?php

namespace App\Modules\DataSync\Scrapers\Savings;

use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\HtmlTable;
use App\Modules\DataSync\Support\SavingsParsing;
use App\Modules\DataSync\Support\ScrapeException;
use Illuminate\Support\Carbon;

/**
 * TPBank: trang lãi suất nạp biểu lãi khách hàng cá nhân từ thành phần WCM (đường dẫn và cmpntid nằm trong script của trang).
 * Cột "Tiết kiệm điện tử" = online, "Tiết kiệm tính lãi cuối kỳ" = tại quầy.
 */
class Tpbank extends SavingsScraper
{
    public const BASE = 'https://tpb.vn';

    protected function bank(): string
    {
        return 'tpbank';
    }

    public function url(): string
    {
        return self::BASE.'/cong-cu-tinh-toan/lai-suat';
    }

    protected function read(Fetcher $http): ?Carbon
    {
        $page = $http->html($this->url());
        if (! preg_match("#'(/tpbank_data/[^']*/lai-xuat/danh-cho-khach-hang-ca-nhan)'.{0,400}?cmpntid=([0-9a-f\-]{36})#s", $page, $m)) {
            throw ScrapeException::layoutChanged('không thấy địa chỉ biểu lãi suất khách hàng cá nhân.');
        }

        $html = $http->html(self::BASE.'/wps/wcm/connect'.$m[1], ['source' => 'library', 'srv' => 'cmpnt', 'cmpntid' => $m[2]]);
        $table = HtmlTable::find($html, 'tiết kiệm điện tử');
        if ($table === null) {
            throw ScrapeException::layoutChanged('không thấy bảng có cột "Tiết kiệm điện tử".');
        }

        $grid = HtmlTable::grid($table);
        $columns = [];
        foreach ($grid[0] as $col => $label) {
            $label = mb_strtolower($label);
            if (str_contains($label, 'điện tử')) {
                $columns[$col] = SavingsChannel::Online;
            } elseif (str_contains($label, 'lãi cuối kỳ')) {
                $columns[$col] = SavingsChannel::Counter;
            }
        }

        foreach (array_slice($grid, 1) as $row) {
            $term = SavingsParsing::term($row[0]);
            foreach ($term === null ? [] : $columns as $col => $channel) {
                $this->row($term, $channel, SavingsParsing::rate($row[$col] ?? ''));
            }
        }

        return null;
    }
}
