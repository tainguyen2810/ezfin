<?php

namespace App\Modules\DataSync\Scrapers\Catalog;

use App\Modules\DataSync\Enums\SyncKind;
use App\Modules\DataSync\Support\CatalogParsing;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\HtmlTable;
use DOMXPath;

/**
 * Thẻ tín dụng từ trang danh sách HTML của ngân hàng: bộ con chỉ cần đọc danh sách (tên, link, ảnh, ý chính);
 * phí thường niên, phí phát hành, số ngày miễn lãi, lãi suất, thu nhập tối thiểu đọc từ chữ trên trang chi tiết
 * (CatalogParsing::cardTerms). Chỉ đọc trang chi tiết trên tên miền của ngân hàng; thẻ ghi "dừng phát hành" bỏ qua.
 */
abstract class HtmlCardCatalog extends CatalogScraper
{
    /**
     * @return list<array{name: string, url: string, image: ?string, features: list<string>, key?: string}>
     */
    abstract protected function cards(DOMXPath $xpath): array;

    public function kind(): SyncKind
    {
        return SyncKind::CreditCard;
    }

    /** Gốc website để đổi link/ảnh tương đối thành tuyệt đối, ví dụ https://techcombank.com. */
    protected function base(): string
    {
        $parts = parse_url($this->url());

        return $parts['scheme'].'://'.$parts['host'];
    }

    protected function read(Fetcher $http): array
    {
        $items = [];
        $host = parse_url($this->base(), PHP_URL_HOST);

        foreach ($this->cards(new DOMXPath(HtmlTable::document($http->html($this->url())))) as $card) {
            $url = $this->absolute($card['url']);
            if (parse_url($url, PHP_URL_HOST) !== $host || preg_match('/(dừng|ngừng) phát hành/iu', $card['name'])) {
                continue;
            }
            $page = CatalogParsing::ownText(CatalogParsing::pageText($http->html($url)));
            $terms = CatalogParsing::cardTerms($page);
            $name = CatalogParsing::cardName($card['name']);
            $terms['cashback_rate'] = CatalogParsing::cashback($name.'. '.implode('. ', $card['features']).'. '.$page);

            $items[] = $this->item(
                $card['key'] ?? $this->slugFromUrl($url),
                $name,
                $url,
                CatalogParsing::imageUrl($card['image'], $this->base()),
                implode('; ', $card['features']) ?: null,
                ['network' => CatalogParsing::network($name)] + $terms + ['benefits' => CatalogParsing::benefits($name.' '.implode(' ', $card['features']))],
            );
        }

        return $this->enrich($http, $items);
    }

    /**
     * Bổ sung số liệu từ nguồn khác của ngân hàng (ví dụ phụ lục lãi suất thẻ dạng PDF). Mặc định không đổi gì.
     *
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    protected function enrich(Fetcher $http, array $items): array
    {
        return $items;
    }

    protected function absolute(string $url): string
    {
        $url = trim(html_entity_decode($url));

        return preg_match('#^https?://#', $url) ? $url : $this->base().'/'.ltrim($url, '/');
    }
}
