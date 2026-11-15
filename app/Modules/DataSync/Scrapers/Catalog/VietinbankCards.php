<?php

namespace App\Modules\DataSync\Scrapers\Catalog;

use App\Modules\DataSync\Support\HtmlTable;
use DOMElement;
use DOMXPath;

/**
 * Thẻ tín dụng VietinBank: trang danh sách lọc ?the=the_tin_dung (Next.js, nội dung có sẵn trong HTML), mỗi thẻ là
 * .swiper-slide > a (tên .truncate, ảnh qua /_next/image?url=<ảnh gốc>). Trang chi tiết thường không ghi số tiền phí
 * thường niên (chỉ có trong biểu phí PDF) nên trường đó có thể trống.
 */
class VietinbankCards extends HtmlCardCatalog
{
    protected function bank(): string
    {
        return 'vietinbank';
    }

    public function url(): string
    {
        return 'https://www.vietinbank.vn/vi/ca-nhan/san-pham-dich-vu/danh-sach-the?the=the_tin_dung';
    }

    protected function cards(DOMXPath $xpath): array
    {
        $cards = [];
        foreach ($xpath->query('//*[contains(@class, "swiper-slide")]//a[contains(@href, "/danh-sach-the/")]') as $link) {
            /** @var DOMElement $link */
            $name = HtmlTable::firstByClass($xpath, 'truncate', $link);
            if ($name === null) {
                continue;
            }
            $image = $xpath->query('.//img', $link)->item(0);
            $src = $image instanceof DOMElement ? $image->getAttribute('src') : null;
            if ($src && preg_match('/[?&]url=([^&]+)/', html_entity_decode($src), $m)) {
                $src = urldecode($m[1]);
            }
            $features = [];
            foreach ($xpath->query('.//*[contains(@class, "line-clamp")]', $link) as $line) {
                $features[] = HtmlTable::text($line);
            }
            $cards[] = ['name' => HtmlTable::text($name), 'url' => $link->getAttribute('href'), 'image' => $src, 'features' => array_values(array_filter($features))];
        }

        return $cards;
    }
}
