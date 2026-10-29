<?php

namespace App\Support\Modules;

use App\Support\Navigation\Navigation;
use App\Support\Navigation\NavItem;
use App\Support\Seo\Sitemap;
use App\Support\Seo\SitemapUrl;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;
use ReflectionClass;

/**
 * Lớp nền cho mỗi module trong app/Modules/<Tên>.
 *
 * Tự nạp (nếu có): routes/public.php (tên miền công khai), routes/app.php
 * (tên miền ứng dụng, noindex), resources/views (namespace = tên module dạng kebab),
 * database/migrations. Khi module có feature flag và flag tắt, mọi route của
 * module trả 404, mục menu bị ẩn và URL của module không vào sitemap.
 */
abstract class ModuleServiceProvider extends ServiceProvider
{
    /** Feature flag điều khiển module; null = luôn bật. */
    protected ?string $feature = null;

    /**
     * Mục menu chính của website công khai.
     *
     * @return list<NavItem>
     */
    protected function publicNavigation(): array
    {
        return [];
    }

    /**
     * Trang công khai đã xuất bản của module cho /sitemap.xml. Chỉ trang được phép lập chỉ mục.
     *
     * @return iterable<SitemapUrl>
     */
    protected function sitemap(): iterable
    {
        return [];
    }

    public function boot(): void
    {
        $path = $this->modulePath();

        if (is_dir("$path/database/migrations")) {
            $this->loadMigrationsFrom("$path/database/migrations");
        }

        if (is_dir("$path/resources/views")) {
            $this->loadViewsFrom("$path/resources/views", Str::kebab($this->moduleName()));
        }

        $this->bootRoutes($path);

        $this->app->make(Sitemap::class)->add($this->feature, fn () => $this->sitemap());

        $navigation = $this->app->make(Navigation::class);

        foreach ($this->publicNavigation() as $item) {
            $navigation->add($item->feature === null && $this->feature !== null
                ? $item->withFeature($this->feature)
                : $item);
        }
    }

    protected function bootRoutes(string $path): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $middleware = ['web'];

        if ($this->feature !== null) {
            $middleware[] = EnsureFeaturesAreActive::using($this->feature);
        }

        if (is_file("$path/routes/public.php")) {
            Route::domain(config('ezfin.domains.public'))
                ->middleware($middleware)
                ->group("$path/routes/public.php");
        }

        if (is_file("$path/routes/app.php")) {
            Route::domain(config('ezfin.domains.app'))
                ->middleware([...$middleware, 'noindex'])
                ->group("$path/routes/app.php");
        }
    }

    protected function moduleName(): string
    {
        return basename($this->modulePath());
    }

    protected function modulePath(): string
    {
        return dirname((new ReflectionClass($this))->getFileName());
    }
}
