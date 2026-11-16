<?php

namespace App\Modules\DataSync\Scrapers\Catalog;

use App\Modules\DataSync\Enums\SyncKind;
use App\Modules\DataSync\Support\CatalogParsing;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\ScrapeException;

/**
 * Thẻ tín dụng ACB: API nội dung của website (/api/front/v1/posts, loại "the", nhóm "the-tin-dung"), trang chi tiết
 * theo _seoPaths; phí đọc từ chữ trên trang chi tiết. Bỏ thẻ doanh nghiệp. robots.txt cho phép tất cả.
 */
class AcbCards extends CatalogScraper
{
    public const BASE = 'https://acb.com.vn';

    protected function bank(): string
    {
        return 'acb';
    }

    public function kind(): SyncKind
    {
        return SyncKind::CreditCard;
    }

    public function url(): string
    {
        return self::BASE.'/the';
    }

    protected function read(Fetcher $http): array
    {
        $json = $http->json(self::BASE.'/api/front/v1/posts', ['search[type:is]' => 'the', 'limit' => '100']);
        if (! isset($json['data']) || ! is_array($json['data'])) {
            throw ScrapeException::layoutChanged('API thẻ của ACB không trả về danh sách data.');
        }

        $items = [];
        foreach ($json['data'] as $post) {
            $categories = array_column($post['categories'] ?? [], 'slug');
            $name = CatalogParsing::cardName((string) ($post['title'] ?? ''));
            $path = $post['_seoPaths']['paths']['vi'] ?? $post['_seoPaths']['path'] ?? null;
            $isCredit = in_array('the-tin-dung', $categories, true) || str_contains((string) ($post['slug'] ?? ''), 'tin-dung');
            if (! $isCredit || $path === null || $name === '' || preg_match('/business|doanh nghiệp/iu', $name)) {
                continue;
            }

            $url = self::BASE.'/'.preg_replace('#^vi/#', '', ltrim($path, '/'));
            $summary = collect(preg_split('/\R/u', (string) ($post['short_description'] ?? '')))
                ->map(fn ($line) => trim($line, " -\u{200B}"))->filter()->implode('; ');

            $page = CatalogParsing::ownText(CatalogParsing::pageText($http->html($url)));

            $items[] = $this->item(
                $this->slugFromUrl($url),
                $name,
                $url,
                CatalogParsing::imageUrl($post['featured_image']['path'] ?? $post['featured_image_1']['path'] ?? null, self::BASE),
                $summary ?: null,
                ['network' => CatalogParsing::network($name)]
                    + ['cashback_rate' => CatalogParsing::cashback($name.'. '.$summary.'. '.$page)]
                    + CatalogParsing::cardTerms($page)
                    + ['benefits' => CatalogParsing::benefits($name.' '.$summary)],
            );
        }

        return $items;
    }
}
