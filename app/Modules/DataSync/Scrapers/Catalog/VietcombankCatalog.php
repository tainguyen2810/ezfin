<?php

namespace App\Modules\DataSync\Scrapers\Catalog;

use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\HtmlTable;
use App\Modules\DataSync\Support\ScrapeException;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Danh mục sản phẩm trên vietcombank.com.vn: trang danh sách gọi API tìm kiếm của website
 * (/vi-VN/sxa/searchapi/customresults), mỗi kết quả là khung HTML (.card-item: ảnh, .card-name, .feature-name/.feature-value).
 * Trang chi tiết có các mục .information-detail-component .content-item (p.name + .label). robots.txt cho phép tất cả.
 */
abstract class VietcombankCatalog extends CatalogScraper
{
    public const BASE = 'https://www.vietcombank.com.vn';

    protected function bank(): string
    {
        return 'vietcombank';
    }

    /**
     * Tham số tìm kiếm của trang danh sách (s, itemid, v, type tùy chọn).
     *
     * @return array<string, string>
     */
    abstract protected function searchQuery(): array;

    /** @return list<array{url: string, name: string, image: ?string, features: array<string, string>}> */
    protected function listing(Fetcher $http): array
    {
        $json = $http->json(self::BASE.'/vi-VN/sxa/searchapi/customresults/', ['l' => 'vi-VN', 'p' => '50', 'o' => 'SortOrder,Ascending'] + $this->searchQuery());
        if (! isset($json['Results']) || ! is_array($json['Results'])) {
            throw ScrapeException::layoutChanged('API danh sách sản phẩm không trả về Results.');
        }

        $items = [];
        foreach ($json['Results'] as $result) {
            $xpath = new DOMXPath(HtmlTable::document((string) ($result['Html'] ?? '')));
            $name = HtmlTable::firstByClass($xpath, 'card-name');
            if ($name === null || blank($result['Url'] ?? null)) {
                continue;
            }
            $features = [];
            foreach (HtmlTable::byClass($xpath, 'feature') as $feature) {
                $label = HtmlTable::firstByClass($xpath, 'feature-name', $feature);
                $value = HtmlTable::firstByClass($xpath, 'feature-value', $feature);
                if ($label && $value) {
                    $features[mb_strtolower(HtmlTable::text($label))] = HtmlTable::text($value);
                }
            }
            $img = $xpath->query('//img')->item(0);
            $items[] = [
                'url' => self::BASE.$result['Url'],
                'name' => HtmlTable::text($name),
                'image' => $img instanceof DOMElement ? $img->getAttribute('src') : null,
                'features' => $features,
            ];
        }

        return $items;
    }

    /**
     * Các mục thông tin chi tiết của trang sản phẩm: tên mục => nội dung.
     *
     * @return array<string, string>
     */
    protected function sections(Fetcher $http, string $url): array
    {
        $xpath = new DOMXPath(HtmlTable::document($http->html($url)));
        $sections = [];
        foreach (HtmlTable::byClass($xpath, 'content-item') as $item) {
            $name = $xpath->query('.//*[contains(concat(" ", normalize-space(@class), " "), " name ")]', $item)->item(0);
            $label = HtmlTable::firstByClass($xpath, 'label', $item);
            if ($name && $label) {
                $sections[mb_strtolower(HtmlTable::text($name))] = $this->lines($label);
            }
        }

        return $sections;
    }

    /** Nội dung giữ ranh giới dòng (mỗi <li>/<p> một dòng) để tách "Thẻ chính" và "Thẻ phụ". */
    private function lines(DOMNode $node): string
    {
        $html = $node->ownerDocument instanceof DOMDocument ? $node->ownerDocument->saveHTML($node) : '';
        $text = html_entity_decode(strip_tags(preg_replace('#<(li|p|br|div)\b#i', "\n<$1", $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace(['/[ \t\x{00A0}]+/u', '/\s*\n\s*/u'], [' ', "\n"], $text));
    }
}
