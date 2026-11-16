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
 * HDBank: trang Lãi suất liệt kê PDF "Biểu lãi suất tiền gửi khách hàng cá nhân dd-mm-yyyy" (lấy bản có ngày mới nhất,
 * ngày đó là ngày hiệu lực). Trong PDF:
 * - mục 1a "Tiết kiệm có kỳ hạn thông thường" (tại quầy): cột VND Cuối kỳ, Trả lãi trước, Hàng tháng, ...;
 *   kỳ 12 và 13 tháng có "loại 1" (từ 500 tỷ) và "loại 2" (thông thường): chỉ lấy loại 2.
 * - mục 2c "Tiết kiệm Online VNĐ": Cuối kỳ, Trả lãi trước, Hàng tháng, Hàng quý.
 */
class Hdbank extends SavingsScraper
{
    protected function bank(): string
    {
        return 'hdbank';
    }

    public function url(): string
    {
        return 'https://hdbank.com.vn/vi/personal/cong-cu/interest-rate';
    }

    protected function read(Fetcher $http): ?Carbon
    {
        [$pdf, $effective] = $this->latestPdf($http->html($this->url()));

        $channel = null;
        $pending = null;
        foreach (array_merge(...$http->pdf($pdf)) as $line) {
            $cells = PdfText::cells($line);
            $heading = mb_strtolower(implode(' ', $cells));
            if (preg_match('/^[a-e]\.\s/u', $heading) || preg_match('/^\d\.\s/u', $heading)) {
                $channel = match (true) {
                    str_contains($heading, 'tiết kiệm có kỳ') && str_contains($heading, 'thông thường') => SavingsChannel::Counter,
                    (bool) preg_match('/tiết kiệm\s*online\s*vnđ/u', $heading) => SavingsChannel::Online,
                    default => null,
                };

                continue;
            }
            if ($channel === null || $cells === []) {
                continue;
            }

            // "12 thángloại 1 | 7,20" hoặc "13 tháng | loại 1 | 7,10": mức từ 500 tỷ, bỏ qua; dòng "loại 2" kế tiếp là mức thường.
            if (preg_match('/^0*(\d{1,2})\s*tháng.*loại 1/u', $heading, $m)) {
                $pending = (int) $m[1];

                continue;
            }
            if (str_starts_with($heading, 'loại 2')) {
                [$term, $pending] = [$pending, null];
            } else {
                $term = SavingsParsing::term($cells[0]);
            }
            $values = array_slice($cells, 1);
            if ($term === null || count($values) < 2) {
                continue;
            }

            $this->row($term, $channel, SavingsParsing::rate($values[0]));
            $this->row($term, $channel, SavingsParsing::rate($values[1]), InterestPayment::Upfront);
            $this->row($term, $channel, SavingsParsing::rate($values[2] ?? ''), InterestPayment::Monthly);
            $this->row($term, $channel, SavingsParsing::rate($values[3] ?? ''), InterestPayment::Quarterly);
        }

        return $effective;
    }

    /** @return array{0: string, 1: ?Carbon} đường dẫn PDF biểu lãi tiền gửi cá nhân mới nhất và ngày ghi trên nhãn */
    private function latestPdf(string $html): array
    {
        preg_match_all('#href="(https://cdn\.hdbank\.com\.vn/[^"]*BIEULAISUATTIENGUIKHACHHANGCANHAN[^"]*\.pdf)"(.{0,600}?)</a>#su', $html, $matches, PREG_SET_ORDER);
        if ($matches === []) {
            throw ScrapeException::layoutChanged('không thấy tệp PDF biểu lãi suất tiền gửi khách hàng cá nhân.');
        }

        $links = array_map(function (array $match) {
            $date = preg_match('/(\d{2})-(\d{2})-(\d{4})/', strip_tags($match[2]), $d) && checkdate((int) $d[2], (int) $d[1], (int) $d[3])
                ? Carbon::create((int) $d[3], (int) $d[2], (int) $d[1])->startOfDay()
                : null;

            return [html_entity_decode($match[1]), $date];
        }, $matches);
        usort($links, fn ($a, $b) => ($b[1]?->timestamp ?? 0) <=> ($a[1]?->timestamp ?? 0));

        return $links[0];
    }
}
