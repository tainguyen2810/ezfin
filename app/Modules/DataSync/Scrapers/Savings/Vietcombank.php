<?php

namespace App\Modules\DataSync\Scrapers\Savings;

use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\HtmlTable;
use App\Modules\DataSync\Support\SavingsParsing;
use App\Modules\DataSync\Support\ScrapeException;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Vietcombank: trang lãi suất nhúng sẵn bảng lãi dạng JSON trong ô <input id="currentDataInterestRate">.
 * tenorType: Savings = tiết kiệm tại quầy, Online = tiền gửi trực tuyến (FixedDeposit trùng tại quầy, bỏ qua). Lãi dạng tỷ lệ (0.054 = 5,4%).
 */
class Vietcombank extends SavingsScraper
{
    protected function bank(): string
    {
        return 'vietcombank';
    }

    public function institution(): array
    {
        return ['slug' => 'vietcombank', 'name' => 'Ngân hàng TMCP Ngoại thương Việt Nam', 'short_name' => 'Vietcombank', 'website' => 'https://www.vietcombank.com.vn'];
    }

    public function url(): string
    {
        return 'https://www.vietcombank.com.vn/vi-VN/KHCN/Cong-cu-tien-ich/KHCN---Lai-suat';
    }

    protected function read(Fetcher $http): ?Carbon
    {
        $input = HtmlTable::document($http->html($this->url()))->getElementById('currentDataInterestRate');
        $data = $input ? json_decode($input->getAttribute('value'), true) : null;

        if (! is_array($data) || ! is_array($data['Data'] ?? null)) {
            throw ScrapeException::layoutChanged('không thấy dữ liệu lãi suất (currentDataInterestRate).');
        }

        $channels = ['Savings' => SavingsChannel::Counter, 'Online' => SavingsChannel::Online];
        foreach ($data['Data'] as $item) {
            $channel = $channels[$item['tenorType'] ?? ''] ?? null;
            $term = SavingsParsing::term((string) ($item['tenor'] ?? ''));
            if ($channel && $term !== null && ($item['currencyCode'] ?? '') === 'VND') {
                $this->row($term, $channel, SavingsParsing::fraction($item['rates'] ?? null));
            }
        }

        try {
            return isset($data['UpdatedDate']) ? Carbon::parse($data['UpdatedDate'])->setTimezone(config('app.timezone'))->startOfDay() : null;
        } catch (Throwable) {
            return null;
        }
    }
}
