<?php

namespace App\Modules\DataSync\Scrapers\Catalog;

use App\Modules\DataSync\Scrapers\Savings\Techcombank;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\HtmlTable;
use App\Modules\DataSync\Support\PdfText;
use App\Modules\DataSync\Support\ScrapeException;
use DOMXPath;

/**
 * Thẻ tín dụng Techcombank: khối .pick-card-container (tên .label, ảnh .pick-card-top-image, link a.button-link).
 * Lãi suất lấy từ "Phụ lục lãi suất thẻ tín dụng" (PDF trên trang Biểu phí lãi suất), ghép theo tên thẻ; không đọc được
 * phụ lục thì giữ lãi suất ghi trên trang thẻ.
 */
class TechcombankCards extends HtmlCardCatalog
{
    protected function bank(): string
    {
        return 'techcombank';
    }

    public function url(): string
    {
        return 'https://techcombank.com/khach-hang-ca-nhan/chi-tieu/the/the-tin-dung';
    }

    protected function cards(DOMXPath $xpath): array
    {
        $cards = [];
        foreach (HtmlTable::byClass($xpath, 'pick-card-container') as $box) {
            $name = HtmlTable::firstByClass($xpath, 'label', $box);
            $link = HtmlTable::firstByClass($xpath, 'button-link', $box);
            if (! $name || ! $link || ! $link->getAttribute('href')) {
                continue;
            }
            $cards[] = [
                'name' => HtmlTable::text($name),
                'url' => $link->getAttribute('href'),
                'image' => HtmlTable::firstByClass($xpath, 'pick-card-top-image', $box)?->getAttribute('src'),
                'features' => array_map(fn ($node) => HtmlTable::text($node), HtmlTable::byClass($xpath, 'content-item-text', $box)),
            ];
        }

        return $cards;
    }

    protected function enrich(Fetcher $http, array $items): array
    {
        try {
            $rates = $this->appendixRates($http);
        } catch (ScrapeException) {
            return $items;
        }

        foreach ($items as &$item) {
            $rate = $this->matchRate($item['name'], $rates);
            if ($rate !== null) {
                $item['details']['interest_rate'] = $rate;
            }
        }

        return $items;
    }

    /** @return array<string, string> tên thẻ trong phụ lục => lãi suất %/năm */
    private function appendixRates(Fetcher $http): array
    {
        preg_match_all('#/content/dam/techcombank/public-site/documents/vn-phu-luc-lai-suat-the-tin-dung-(\d{2})-(\d{4})\.pdf#', $http->html((new Techcombank)->url()), $matches, PREG_SET_ORDER);
        if ($matches === []) {
            return [];
        }
        usort($matches, fn ($a, $b) => strcmp($b[2].$b[1], $a[2].$a[1]));

        $rates = [];
        foreach (array_merge(...$http->pdf(Techcombank::BASE.$matches[0][0])) as $line) {
            $cells = PdfText::cells($line);
            $name = collect($cells)->first(fn ($cell) => str_contains(mb_strtolower($cell), 'techcom'));
            $rate = collect($cells)->map(fn ($cell) => preg_match('/^(\d{1,2}(?:[.,]\d{1,2})?)\s*%\s*\/\s*năm$/u', $cell, $m) ? (float) str_replace(',', '.', $m[1]) : null)->filter()->first();
            if ($name !== null && $rate !== null && $rate >= 10 && $rate <= 45) {
                $rates[$name] = number_format($rate, 4, '.', '');
            }
        }

        return $rates;
    }

    /** Dòng phụ lục khớp nhất: mọi từ của tên thẻ đều có trong dòng, ít từ thừa nhất. */
    private function matchRate(string $name, array $rates): ?string
    {
        $words = $this->words($name);
        $best = null;
        foreach ($rates as $label => $rate) {
            $candidate = $this->words($label);
            if ($words !== [] && array_diff($words, $candidate) === []) {
                $extra = count(array_diff($candidate, $words));
                if ($best === null || $extra < $best[0]) {
                    $best = [$extra, $rate];
                }
            }
        }

        return $best[1] ?? null;
    }

    /** @return list<string> */
    private function words(string $name): array
    {
        $name = mb_strtolower(preg_replace('/\(\*+\)|[()]/u', ' ', $name));

        return array_values(array_diff(preg_split('/\s+/u', trim($name)) ?: [], ['techcombank', 'techcom', 'thẻ', 'tín', 'dụng', '']));
    }
}
