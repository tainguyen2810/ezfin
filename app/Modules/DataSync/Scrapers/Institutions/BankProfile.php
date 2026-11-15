<?php

namespace App\Modules\DataSync\Scrapers\Institutions;

use App\Modules\Compare\Actions\UpdateInstitutionProfile;
use App\Modules\DataSync\Contracts\Scraper;
use App\Modules\DataSync\Enums\SyncKind;
use App\Modules\DataSync\Models\SyncDraft;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\HtmlTable;
use App\Modules\DataSync\Support\ScrapeException;
use App\Modules\DataSync\Support\ScrapeResult;
use DOMElement;

/**
 * Hồ sơ ngân hàng từ trang chủ chính thức: tên, website và logo. Logo chọn theo thứ tự: ảnh PNG/JPG/WebP có chữ "logo"
 * (ưu tiên alt là tên ngân hàng, bỏ logo ứng dụng/chứng nhận/bản trắng); nếu trang chỉ có logo SVG thì dùng bản PNG
 * chính ngân hàng đặt cạnh (cùng tên .png, hoặc bản PNG do hệ thống quản lý ảnh AEM sinh ra từ file SVG); rồi ảnh logo
 * gốc đã biết trên website ngân hàng (FALLBACK); cuối cùng apple-touch-icon / icon PNG. Ảnh dự phòng được tải thử, chỉ
 * nhận khi đúng là ảnh PNG/JPG/WebP. Không nhận SVG. Người duyệt xem ảnh trước khi ghi; logo được tải về ổ public lúc duyệt.
 */
class BankProfile implements Scraper
{
    /** Ảnh có các chữ này trong đường dẫn/alt không phải logo chính. */
    private const SKIP = ['google', 'apple', 'app-store', 'appstore', 'play', 'store', 'bhtg', 'bao-hiem', 'premier', 'private', 'priority', 'white', 'footer', 'partner', 'napas', 'visa', 'master', 'jcb', 'qr'];

    /**
     * Logo gốc trên chính website ngân hàng, dùng khi trang chủ chỉ có bản trắng (đặt trên nền màu) hoặc ảnh nền CSS.
     * Kiểm tra lại khi ngân hàng đổi giao diện.
     */
    public const FALLBACK = [
        'agribank' => 'https://www.agribank.com.vn/contenthandler/dav/themelist/custom.portal.theme.AGBank3/assets/images/logo-color.png',
    ];

    /** Bản PNG do AEM (Adobe Experience Manager) sinh từ ảnh SVG trong kho /content/dam/. */
    private const AEM_RENDITION = '/jcr:content/renditions/cq5dam.thumbnail.319.319.png';

    /** @param  array{slug: string, name: string, short_name: string, website: string}  $bank */
    public function __construct(private readonly array $bank) {}

    public function key(): string
    {
        return 'institution.'.$this->bank['slug'];
    }

    public function kind(): SyncKind
    {
        return SyncKind::Institution;
    }

    public function name(): string
    {
        return $this->bank['short_name'];
    }

    public function url(): string
    {
        return $this->bank['website'];
    }

    public function scrape(Fetcher $http): ScrapeResult
    {
        $html = $http->html($this->url());
        [$priority, $logo] = $this->bestCandidate($html) ?? [null, null];

        // Chỉ có icon (hoặc không có gì): thử bản PNG của logo SVG, rồi logo gốc đã biết, trước khi lấy icon.
        if ($priority === null || $priority >= 2) {
            $fallbacks = [...$this->svgLogoRasters($html), ...(isset(self::FALLBACK[$this->bank['slug']]) ? [self::FALLBACK[$this->bank['slug']]] : [])];
            foreach ($fallbacks as $url) {
                if ($this->isImage($http, $url)) {
                    $logo = $url;
                    break;
                }
            }
        }

        if ($logo === null) {
            throw ScrapeException::layoutChanged('không tìm thấy logo dạng PNG/JPG/WebP trên trang chủ.');
        }

        return new ScrapeResult([
            'slug' => $this->bank['slug'],
            'name' => $this->bank['name'],
            'short_name' => $this->bank['short_name'],
            'website' => $this->bank['website'],
            'logo_url' => $logo,
        ]);
    }

    public function findLogo(string $html): ?string
    {
        return $this->bestCandidate($html)[1] ?? null;
    }

    /**
     * Đường dẫn PNG ứng với các logo SVG trên trang (thẻ img hoặc chuỗi trong CSS/script), chỉ trên website ngân hàng.
     *
     * @return list<string>
     */
    public function svgLogoRasters(string $html): array
    {
        $sources = [];
        foreach (HtmlTable::document($html)->getElementsByTagName('img') as $img) {
            /** @var DOMElement $img */
            $src = trim($img->getAttribute('src') ?: $img->getAttribute('data-src'));
            $hint = mb_strtolower($src.' '.$img->getAttribute('alt').' '.$img->getAttribute('class').' '.$img->getAttribute('itemprop'));
            if (str_contains($hint, 'logo') || mb_strtolower(trim($img->getAttribute('alt'))) === mb_strtolower($this->bank['short_name'])) {
                $sources[] = $src;
            }
        }
        preg_match_all('#[^"\'()\s\\\\]*logo[^"\'()\s\\\\]*\.svg#i', $html, $matches);
        $sources = [...$sources, ...$matches[0]];

        $site = preg_replace('/^www\./', '', (string) parse_url($this->bank['website'], PHP_URL_HOST));

        return collect($sources)
            ->map(fn (string $src) => html_entity_decode(trim($src)))
            ->filter(fn (string $src) => (bool) preg_match('/\.svg$/i', (string) parse_url($src, PHP_URL_PATH)) && ! $this->skipped($src))
            ->map(fn (string $src) => $this->absolute(strtok($src, '?#')))
            ->filter(fn (string $url) => str_ends_with((string) parse_url($url, PHP_URL_HOST), $site))
            ->map(fn (string $url) => str_contains($url, '/content/dam/') ? $url.self::AEM_RENDITION : preg_replace('/\.svg$/i', '.png', $url))
            ->unique()->values()->all();
    }

    private function isImage(Fetcher $http, string $url): bool
    {
        try {
            $bytes = $http->get($url)->body();
        } catch (ScrapeException) {
            return false;
        }
        $info = @getimagesizefromstring($bytes);

        return $info !== false && in_array($info['mime'], ['image/png', 'image/jpeg', 'image/webp'], true) && strlen($bytes) <= 1024 * 1024;
    }

    /** @return array{0: int, 1: string}|null [độ ưu tiên (0-1 logo, 2-3 icon), đường dẫn tuyệt đối] */
    private function bestCandidate(string $html): ?array
    {
        $document = HtmlTable::document($html);
        $candidates = [];

        foreach ($document->getElementsByTagName('img') as $img) {
            /** @var DOMElement $img */
            $src = trim($img->getAttribute('src') ?: $img->getAttribute('data-src'));
            $alt = mb_strtolower(trim($img->getAttribute('alt')));
            $hint = mb_strtolower($src.' '.$alt.' '.$img->getAttribute('class').' '.($img->parentNode instanceof DOMElement ? $img->parentNode->getAttribute('class') : ''));
            if ($src === '' || ! str_contains($hint, 'logo') || ! $this->isRaster($src) || $this->skipped($src.' '.$alt)) {
                continue;
            }
            $candidates[] = [$alt === mb_strtolower($this->bank['short_name']) ? 0 : 1, $src];
        }

        foreach ($document->getElementsByTagName('link') as $link) {
            /** @var DOMElement $link */
            $rel = mb_strtolower($link->getAttribute('rel'));
            $href = trim($link->getAttribute('href'));
            if ($href !== '' && $this->isRaster($href) && str_contains($rel, 'icon')) {
                $candidates[] = [str_contains($rel, 'apple-touch-icon') ? 2 : 3, $href];
            }
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, fn ($a, $b) => $a[0] <=> $b[0]);

        return [$candidates[0][0], $this->absolute(html_entity_decode($candidates[0][1]))];
    }

    private function isRaster(string $url): bool
    {
        return (bool) preg_match('/\.(png|jpe?g|webp)(\?|#|$)/i', $url);
    }

    private function skipped(string $text): bool
    {
        $text = mb_strtolower($text);

        return collect(self::SKIP)->contains(fn ($word) => str_contains($text, $word));
    }

    private function absolute(string $url): string
    {
        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }
        if (preg_match('#^https?://#', $url)) {
            return $url;
        }

        return rtrim($this->bank['website'], '/').'/'.ltrim($url, '/');
    }

    public function validate(array $payload): array
    {
        return str_starts_with((string) ($payload['logo_url'] ?? ''), 'https://') ? [] : ['Logo phải lấy qua https.'];
    }

    public function compare(array $payload, ?array $previous): array
    {
        return $previous && $previous['logo_url'] !== $payload['logo_url']
            ? ['Logo trên trang chủ đã đổi so với lần duyệt trước, kiểm tra ảnh trước khi duyệt.']
            : [];
    }

    public function apply(SyncDraft $draft): string
    {
        $payload = $draft->payload;
        $response = app(Fetcher::class)->get($payload['logo_url']);
        $bytes = $response->body();
        $info = @getimagesizefromstring($bytes);

        if ($info === false || ! in_array($info['mime'], ['image/png', 'image/jpeg', 'image/webp'], true) || strlen($bytes) > 1024 * 1024) {
            throw new ScrapeException('Logo tải về không phải ảnh PNG/JPG/WebP hợp lệ (tối đa 1 MB).');
        }

        $institution = app(UpdateInstitutionProfile::class)->handle(
            ['slug' => $payload['slug'], 'name' => $payload['name'], 'short_name' => $payload['short_name'], 'website' => $payload['website']],
            $bytes,
            image_type_to_extension($info[2], false),
        );

        return "Đã cập nhật hồ sơ và logo {$institution->short_name}.";
    }
}
