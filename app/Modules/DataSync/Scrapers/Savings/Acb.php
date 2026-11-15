<?php

namespace App\Modules\DataSync\Scrapers\Savings;

use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\HtmlTable;
use App\Modules\DataSync\Support\SavingsParsing;
use App\Modules\DataSync\Support\ScrapeException;
use Illuminate\Support\Carbon;

/**
 * ACB: trang lãi suất tiền gửi có sẵn bảng HTML.
 * - Tại quầy: bảng "TK Truyền Thống/ Tiền Gửi Có Kỳ Hạn" (lãi cuối kỳ, quý, tháng, trả trước), kỳ hạn dạng "12T".
 * - Online: bảng "Tiền gửi Online (LCK)" theo mức gửi (triệu VND) × kỳ hạn, lãi cuối kỳ.
 */
class Acb extends SavingsScraper
{
    protected function bank(): string
    {
        return 'acb';
    }

    public function institution(): array
    {
        return ['slug' => 'acb', 'name' => 'Ngân hàng TMCP Á Châu', 'short_name' => 'ACB', 'website' => 'https://acb.com.vn'];
    }

    public function url(): string
    {
        return 'https://acb.com.vn/lai-suat-tien-gui';
    }

    protected function read(Fetcher $http): ?Carbon
    {
        $html = $http->html($this->url());

        $this->readCounter($html);
        $this->readOnline($html);

        return null;
    }

    private function readCounter(string $html): void
    {
        $table = HtmlTable::find($html, 'tk truyền thống', 'lãi cuối kỳ');
        if ($table === null) {
            throw ScrapeException::layoutChanged('không thấy bảng "TK Truyền Thống/ Tiền Gửi Có Kỳ Hạn".');
        }

        $grid = HtmlTable::grid($table);
        $payments = ['lãi cuối kỳ' => InterestPayment::EndOfTerm, 'lãi quý' => InterestPayment::Quarterly, 'lãi tháng' => InterestPayment::Monthly, 'lãi trả trước' => InterestPayment::Upfront];
        $columns = [];
        foreach (array_keys($grid[0]) as $col) {
            $group = mb_strtolower($grid[1][$col] ?? '');
            $label = mb_strtolower($grid[2][$col] ?? '');
            if (str_contains($group, 'truyền thống') && isset($payments[$label])) {
                $columns[$col] = $payments[$label];
            }
        }
        if (! in_array(InterestPayment::EndOfTerm, $columns, true)) {
            throw ScrapeException::layoutChanged('bảng tại quầy không còn cột "Lãi cuối kỳ" của TK Truyền Thống.');
        }

        foreach ($grid as $row) {
            $term = SavingsParsing::term($row[0]);
            if ($term === null) {
                continue;
            }
            foreach ($columns as $col => $payment) {
                $this->row($term, SavingsChannel::Counter, SavingsParsing::rate($row[$col] ?? ''), $payment);
            }
        }
    }

    private function readOnline(string $html): void
    {
        $table = collect(HtmlTable::tables($html))->first(function ($table) {
            $grid = HtmlTable::grid($table);

            return mb_strtolower($grid[0][1] ?? '') === 'tiền gửi online (lck)';
        });
        if ($table === null) {
            return;
        }

        $grid = HtmlTable::grid($table);
        $terms = array_map(fn ($label) => SavingsParsing::term($label), $grid[1] ?? []);
        $tiers = [];
        foreach (array_slice($grid, 2) as $row) {
            $tiers[] = [$this->tierMin($row[0]), $row];
        }

        foreach ($tiers as $index => [$min, $row]) {
            $max = $tiers[$index + 1][0] ?? null;
            foreach ($terms as $col => $term) {
                if ($col > 0 && $term !== null && $min !== null) {
                    $this->row($term, SavingsChannel::Online, SavingsParsing::rate($row[$col] ?? ''), InterestPayment::EndOfTerm, $min, $max);
                }
            }
        }
    }

    /** Mức gửi (triệu VND): "< 200" → 0, "200 – < 1.000" → 200.000.000, ">= 5.000" → 5.000.000.000. */
    private function tierMin(string $label): ?int
    {
        $label = trim(html_entity_decode($label));
        if (str_starts_with($label, '<')) {
            return 0;
        }

        return preg_match('/(\d{1,3}(?:\.\d{3})*)/', $label, $m) ? (int) str_replace('.', '', $m[1]) * 1_000_000 : null;
    }
}
