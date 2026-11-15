<?php

namespace App\Modules\DataSync\Scrapers\Catalog;

use App\Modules\DataSync\Support\HtmlTable;
use DOMXPath;

/** Thẻ tín dụng quốc tế BIDV: khối .block-card-item (tên h4, ảnh .card-thumbnail img, ý chính .card-list li). */
class BidvCards extends HtmlCardCatalog
{
    protected function bank(): string
    {
        return 'bidv';
    }

    public function url(): string
    {
        return 'https://bidv.com.vn/vn/ca-nhan/san-pham-dich-vu/dich-vu-the/the-tin-dung-quoc-te';
    }

    protected function cards(DOMXPath $xpath): array
    {
        $cards = [];
        foreach (HtmlTable::byClass($xpath, 'block-card-item') as $box) {
            $title = HtmlTable::firstByClass($xpath, 'block-card-item-title', $box);
            $link = $title ? $xpath->query('.//a[@href]', $title)->item(0) : null;
            $name = $title ? $xpath->query('.//h4', $title)->item(0) : null;
            if (! $link instanceof \DOMElement || ! $name) {
                continue;
            }
            $image = $xpath->query('.//*[contains(@class, "card-thumbnail")]//img', $box)->item(0);
            $cards[] = [
                'name' => HtmlTable::text($name),
                'url' => $link->getAttribute('href'),
                'image' => $image instanceof \DOMElement ? $image->getAttribute('src') : null,
                'features' => array_map(fn ($node) => HtmlTable::text($node), iterator_to_array($xpath->query('.//*[contains(@class, "card-list")]//li', $box), false)),
            ];
        }

        return $cards;
    }
}
