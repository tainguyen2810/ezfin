<?php

namespace App\Modules\DataSync\Scrapers\Catalog;

use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\ScrapeException;

/**
 * Khoản vay cá nhân ACB: API nội dung của website (/api/front/v1/posts, loại "vay-von"), bỏ nhóm vay kinh doanh;
 * thông số đọc từ chữ trên trang chi tiết (_seoPaths). robots.txt cho phép tất cả.
 */
class AcbLoans extends HtmlLoanCatalog
{
    protected function bank(): string
    {
        return 'acb';
    }

    public function url(): string
    {
        return AcbCards::BASE.'/vay-von';
    }

    protected function productPattern(): string
    {
        return '#^/vay-von/[a-z0-9-]+$#';
    }

    protected function productUrls(Fetcher $http): array
    {
        $json = $http->json(AcbCards::BASE.'/api/front/v1/posts', ['search[type:is]' => 'vay-von', 'limit' => '100']);
        if (! isset($json['data']) || ! is_array($json['data'])) {
            throw ScrapeException::layoutChanged('API khoản vay của ACB không trả về danh sách data.');
        }

        return collect($json['data'])
            ->reject(fn ($post) => in_array('vay-kinh-doanh', array_column($post['categories'] ?? [], 'slug'), true))
            ->map(fn ($post) => $post['_seoPaths']['paths']['vi'] ?? $post['_seoPaths']['path'] ?? null)
            ->filter()
            ->map(fn ($path) => AcbCards::BASE.'/'.preg_replace('#^vi/#', '', ltrim($path, '/')))
            ->values()->all();
    }
}
