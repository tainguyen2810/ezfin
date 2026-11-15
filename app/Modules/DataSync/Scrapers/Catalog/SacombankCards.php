<?php

namespace App\Modules\DataSync\Scrapers\Catalog;

use App\Modules\DataSync\Enums\SyncKind;
use App\Modules\DataSync\Support\CatalogParsing;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\ScrapeException;

/**
 * Thẻ tín dụng Sacombank: dữ liệu JSON của khối danh sách thẻ trên website (cardlisting.sacom.listing.json): tên, ảnh,
 * link, tiêu chí so sánh (hạng thẻ, hạn mức, phí thường niên). robots.txt chỉ chặn trang tìm kiếm.
 */
class SacombankCards extends CatalogScraper
{
    public const BASE = 'https://www.sacombank.com.vn';

    protected function bank(): string
    {
        return 'sacombank';
    }

    public function kind(): SyncKind
    {
        return SyncKind::CreditCard;
    }

    public function url(): string
    {
        return self::BASE.'/ca-nhan/the/the-tin-dung.html';
    }

    protected function read(Fetcher $http): array
    {
        $json = $http->json(self::BASE.'/ca-nhan/the/the-tin-dung/_jcr_content/root/container/container/cardlisting.sacom.listing.json');
        if (! isset($json['cards']) || ! is_array($json['cards'])) {
            throw ScrapeException::layoutChanged('dữ liệu danh sách thẻ không có mục cards.');
        }

        $items = [];
        foreach ($json['cards'] as $card) {
            if (blank($card['url'] ?? null)) {
                continue;
            }
            $criteria = collect(array_merge($card['comparisonCriteriaList'] ?? [], $card['productCriteria'] ?? []))
                ->mapWithKeys(fn ($row) => [mb_strtolower(trim($row['comparisonCriteria'] ?? $row['title'] ?? '')) => CatalogParsing::pageText((string) ($row['value'] ?? ''))]);
            $name = CatalogParsing::cardName((string) ($card['alt'] ?: mb_convert_case((string) $card['title'], MB_CASE_TITLE)));
            $fee = $criteria['phí thường niên'] ?? null;
            $url = self::BASE.preg_replace('/#.*$/', '', $card['url']);
            $summary = collect(['Hạng thẻ' => $criteria['hạng thẻ'] ?? null, 'Hạn mức' => $criteria['hạn mức'] ?? null, 'Đặc quyền' => $criteria['đặc quyền'] ?? null])
                ->filter()->map(fn ($v, $k) => "{$k}: {$v}")->implode('; ');

            $items[] = $this->item(
                $this->slugFromUrl(preg_replace('/\.html$/', '', $url)),
                $name,
                $url,
                CatalogParsing::imageUrl($card['image'] ?? $card['thumbnail'] ?? null, self::BASE),
                $summary ?: null,
                [
                    'network' => CatalogParsing::network($name),
                    'annual_fee' => CatalogParsing::money($fee),
                    'annual_fee_note' => $fee ? mb_substr($fee, 0, 200) : null,
                    'benefits' => CatalogParsing::benefits($name.' '.($criteria['tiện ích nổi bật'] ?? '').' '.($criteria['đặc quyền'] ?? '')),
                ],
            );
        }

        return $items;
    }
}
