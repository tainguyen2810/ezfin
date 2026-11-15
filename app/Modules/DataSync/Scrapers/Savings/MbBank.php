<?php

namespace App\Modules\DataSync\Scrapers\Savings;

use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\HtmlTable;
use App\Modules\DataSync\Support\SavingsParsing;
use App\Modules\DataSync\Support\ScrapeException;
use DOMElement;
use Illuminate\Support\Carbon;

/**
 * MB: trang "Biểu lãi suất" lấy văn bản từ API công khai của website (danh mục "Lãi suất huy động" → "Khách hàng cá nhân").
 * Văn bản mới nhất là HTML có nhiều bảng; dùng bảng "Lãi suất tiết kiệm khách hàng cá nhân bằng đồng Việt Nam" (tại quầy)
 * và "Tiền gửi số truyền thống (Khách hàng Mass)" (online). Mỗi bảng: dưới 1 tỷ / từ trên 1 tỷ × lãi trả sau, trả trước, hàng tháng.
 */
class MbBank extends SavingsScraper
{
    public const API = 'https://www.mbbank.com.vn/mb-website/v1/interest-rate';

    public const TIER = 1_000_000_000;

    protected function bank(): string
    {
        return 'mb';
    }

    public function url(): string
    {
        return 'https://www.mbbank.com.vn/ho-tro/bieu-lai-suat';
    }

    protected function read(Fetcher $http): ?Carbon
    {
        $categoryId = $this->categoryId($http->json(self::API.'/document-categories', ['languageCode' => 'vi']));
        $documents = $http->json(self::API.'/documents', [
            'categoryId' => $categoryId, 'page' => 1, 'size' => 20, 'languageCode' => 'vi', 'sort' => 'publishedDate:desc',
        ]);

        $document = $documents['content'][0] ?? null;
        $translation = collect($document['translations'] ?? [])->firstWhere('languageCode', 'vi');
        $html = (string) ($translation['description'] ?? '');
        if ($html === '') {
            throw ScrapeException::layoutChanged('văn bản biểu lãi suất huy động không có nội dung.');
        }

        $tables = $this->tablesWithHeadings($html);
        $counter = collect($tables)->first(fn ($item) => str_contains($item[0], 'tiết kiệm khách hàng cá nhân bằng đồng việt nam'));
        $online = collect($tables)->first(fn ($item) => str_contains($item[0], 'tiền gửi số truyền thống') && str_contains($item[0], 'mass'));
        if ($counter === null) {
            throw ScrapeException::layoutChanged('không thấy bảng "Lãi suất tiết kiệm khách hàng cá nhân bằng đồng Việt Nam".');
        }

        $this->readTable($counter[1], SavingsChannel::Counter);
        if ($online !== null) {
            $this->readTable($online[1], SavingsChannel::Online);
        }

        if (preg_match('/hiệu lực từ ngày (\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})/u', html_entity_decode(strip_tags($html)), $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return Carbon::create((int) $m[3], (int) $m[2], (int) $m[1])->startOfDay();
        }

        return isset($document['publishedDate']) ? Carbon::parse($document['publishedDate'])->startOfDay() : null;
    }

    private function categoryId(array $categories): string
    {
        $name = fn (array $node) => mb_strtolower((string) (collect($node['translations'] ?? [])->firstWhere('languageCode', 'vi')['name'] ?? ''));

        foreach ($categories['content'] ?? [] as $group) {
            if (str_contains($name($group), 'huy động')) {
                foreach ($group['children'] ?? [] as $child) {
                    if (str_contains($name($child), 'cá nhân')) {
                        return (string) $child['id'];
                    }
                }
            }
        }

        throw ScrapeException::layoutChanged('không thấy danh mục "Lãi suất huy động / Khách hàng cá nhân".');
    }

    /** @return list<array{string, DOMElement}> [chữ đứng ngay trước bảng (thường), bảng] */
    private function tablesWithHeadings(string $html): array
    {
        $result = [];
        foreach (HtmlTable::tables($html) as $table) {
            $heading = '';
            for ($node = $table->previousSibling; $node !== null && mb_strlen($heading) < 40; $node = $node->previousSibling) {
                $heading = HtmlTable::text($node).' '.$heading;
            }
            $result[] = [mb_strtolower(trim($heading)), $table];
        }

        return $result;
    }

    private function readTable(DOMElement $table, SavingsChannel $channel): void
    {
        $grid = HtmlTable::grid($table);
        $header = mb_strtolower(implode(' | ', array_map(fn ($row) => implode(' ', array_slice($row, 0, 7)), array_slice($grid, 0, 4))));
        if (count($grid[0] ?? []) < 7 || ! str_contains($header, 'dưới 1 tỷ') || ! str_contains($header, 'trả trước')) {
            throw ScrapeException::layoutChanged('bảng lãi suất MB không còn dạng "dưới 1 tỷ / trên 1 tỷ × trả sau, trả trước, hàng tháng".');
        }

        $columns = [
            1 => [InterestPayment::EndOfTerm, 0, self::TIER], 2 => [InterestPayment::Upfront, 0, self::TIER], 3 => [InterestPayment::Monthly, 0, self::TIER],
            4 => [InterestPayment::EndOfTerm, self::TIER, null], 5 => [InterestPayment::Upfront, self::TIER, null], 6 => [InterestPayment::Monthly, self::TIER, null],
        ];

        foreach ($grid as $row) {
            $term = SavingsParsing::term($row[0]);
            if ($term === null) {
                continue;
            }
            foreach ($columns as $index => [$payment, $min, $max]) {
                $this->row($term, $channel, SavingsParsing::rate($row[$index] ?? ''), $payment, $min, $max);
            }
        }
    }
}
