<?php

namespace App\Support\Seo;

use Closure;
use Illuminate\Support\Collection;
use Laravel\Pennant\Feature;

/**
 * Nguồn URL cho /sitemap.xml. Mỗi module khai báo trang công khai đã xuất bản của mình
 * (ModuleServiceProvider::sitemap()); module có feature flag tắt thì không đóng góp URL nào.
 */
class Sitemap
{
    /** @var list<array{feature: ?string, source: Closure(): iterable<SitemapUrl>}> */
    private array $sources = [];

    /** @param  Closure(): iterable<SitemapUrl>  $source */
    public function add(?string $feature, Closure $source): void
    {
        $this->sources[] = ['feature' => $feature, 'source' => $source];
    }

    /** @return Collection<int, SitemapUrl> */
    public function urls(): Collection
    {
        return collect($this->sources)
            ->filter(fn (array $source) => $source['feature'] === null || Feature::active($source['feature']))
            ->flatMap(fn (array $source) => collect(($source['source'])())->all())
            ->unique(fn (SitemapUrl $url) => $url->loc)
            ->values();
    }
}
