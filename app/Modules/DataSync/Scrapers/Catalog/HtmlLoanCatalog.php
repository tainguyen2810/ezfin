<?php

namespace App\Modules\DataSync\Scrapers\Catalog;

use App\Modules\DataSync\Enums\SyncKind;
use App\Modules\DataSync\Support\CatalogParsing;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\HtmlTable;
use DOMXPath;

/**
 * Khoản vay cá nhân từ các trang sản phẩm HTML của ngân hàng: tên lấy từ <h1>, ảnh từ og:image, thông số (lãi "từ",
 * số tháng ưu đãi, thời hạn, % giá trị tài sản, hạn mức) đọc từ chữ trên trang qua CatalogParsing::loanTerms.
 * Bộ con chỉ cần cho biết danh sách trang sản phẩm. Chỉ lấy loại EzFin so sánh (tiêu dùng, mua nhà, mua ô tô);
 * vay kinh doanh, cầm cố, thấu chi bỏ qua. Chỉ đọc trang trên tên miền của ngân hàng.
 */
abstract class HtmlLoanCatalog extends CatalogScraper
{
    public function kind(): SyncKind
    {
        return SyncKind::Loan;
    }

    /** Regex đường dẫn (path) của trang sản phẩm trong trang danh sách url(). */
    abstract protected function productPattern(): string;

    /** Ngân hàng có ghi lãi suất trên trang sản phẩm không (không ghi thì bỏ qua để khỏi đọc nhầm lãi ở tin tức). */
    protected function readsRate(): bool
    {
        return true;
    }

    protected function base(): string
    {
        $parts = parse_url($this->url());

        return $parts['scheme'].'://'.$parts['host'];
    }

    /**
     * Trang sản phẩm: liên kết trong trang danh sách khớp productPattern(); trang nhóm (có trang con cũng nằm trong
     * danh sách) bỏ qua.
     *
     * @return list<string>
     */
    protected function productUrls(Fetcher $http): array
    {
        $xpath = new DOMXPath(HtmlTable::document($http->html($this->url())));
        $paths = [];
        foreach ($xpath->query('//a[@href]') as $link) {
            $path = parse_url(html_entity_decode(trim($link->getAttribute('href'))), PHP_URL_PATH);
            $host = parse_url(trim($link->getAttribute('href')), PHP_URL_HOST);
            if (is_string($path) && ($host === null || $host === parse_url($this->base(), PHP_URL_HOST)) && preg_match($this->productPattern(), $path)) {
                $paths[rtrim($path, '/')] = true;
            }
        }
        $paths = array_keys($paths);
        $leaves = array_filter($paths, fn ($path) => ! collect($paths)->contains(fn ($other) => str_starts_with($other, $path.'/')));

        return array_values(array_map(fn ($path) => $this->base().$path, $leaves));
    }

    /** Ảnh đại diện của trang (og:image); ảnh mặc định chung của website ("default-thumbnail") không phải ảnh sản phẩm. */
    private function image(string $src): ?string
    {
        return $src === '' || str_contains(strtolower($src), 'default') ? null : CatalogParsing::imageUrl($src, $this->base());
    }

    protected function read(Fetcher $http): array
    {
        $items = [];
        foreach ($this->productUrls($http) as $url) {
            $html = $http->html($url);
            $xpath = new DOMXPath(HtmlTable::document($html));
            $heading = $xpath->query('//h1')->item(0);
            $name = $heading ? HtmlTable::text($heading) : (string) $xpath->evaluate('string(//meta[@property="og:title"]/@content)');
            $kind = CatalogParsing::loanKind($name);
            if ($name === '' || $kind === null || preg_match('/thấu chi|cầm cố/iu', $name)) {
                continue;
            }

            $text = CatalogParsing::loanText(CatalogParsing::pageText($html));
            $description = trim((string) $xpath->evaluate('string(//meta[@name="description"]/@content)'));

            $items[] = $this->item(
                $this->slugFromUrl($url),
                $name,
                $url,
                $this->image((string) $xpath->evaluate('string(//meta[@property="og:image"]/@content)')),
                $description !== '' ? $description : null,
                ['kind' => $kind] + CatalogParsing::loanTerms($text, $this->readsRate()),
            );
        }

        return $items;
    }
}
