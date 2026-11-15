<?php

namespace App\Modules\DataSync\Scrapers\Catalog;

use App\Modules\DataSync\Support\HtmlTable;
use DOMElement;
use DOMXPath;

/**
 * Thẻ tín dụng quốc tế Agribank: khối .item2 (tên .name_the, ảnh .img_item img, ý chính .lead_the li). Link chi tiết
 * dạng /vn/ca-nhan/san-pham/the/dtl?urile=wcm:path:/agbank/.../<mã thẻ>, nên mã lấy từ đoạn cuối của urile.
 */
class AgribankCards extends HtmlCardCatalog
{
    protected function bank(): string
    {
        return 'agribank';
    }

    public function url(): string
    {
        return 'https://www.agribank.com.vn/vn/ca-nhan/san-pham/the/the-tin-dung-quoc-te';
    }

    protected function cards(DOMXPath $xpath): array
    {
        $cards = [];
        foreach (HtmlTable::byClass($xpath, 'item2') as $box) {
            $link = $xpath->query('.//*[contains(@class, "name_the")]//a[@href]', $box)->item(0);
            if (! $link instanceof DOMElement) {
                continue;
            }
            $href = html_entity_decode($link->getAttribute('href'));
            parse_str((string) parse_url($href, PHP_URL_QUERY), $query);
            $image = $xpath->query('.//*[contains(@class, "img_item")]//img', $box)->item(0);
            $cards[] = [
                'key' => mb_strtolower(basename((string) ($query['urile'] ?? $href))),
                'name' => HtmlTable::text($link),
                'url' => $href,
                'image' => $image instanceof DOMElement ? $image->getAttribute('src') : null,
                'features' => array_map(fn ($li) => HtmlTable::text($li), iterator_to_array($xpath->query('.//*[contains(@class, "lead_the")]//li', $box), false)),
            ];
        }

        return $cards;
    }
}
