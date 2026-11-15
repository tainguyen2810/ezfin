<?php

namespace App\Modules\DataSync\Scrapers\Catalog;

use App\Modules\DataSync\Support\HtmlTable;
use DOMXPath;

/** Thẻ tín dụng Techcombank: khối .pick-card-container (tên .label, ảnh .pick-card-top-image, link a.button-link). */
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
}
