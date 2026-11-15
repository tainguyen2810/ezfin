<?php

namespace App\Modules\Markets;

use App\Modules\Markets\Models\FxRate;
use App\Modules\Markets\Models\GoldPrice;
use App\Modules\Markets\Policies\MarketQuotePolicy;
use App\Support\Modules\ModuleServiceProvider;
use App\Support\Seo\SitemapUrl;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;

/**
 * Tỷ giá ngoại tệ và giá vàng. Số liệu nhập tay, nhập file trong trang quản trị, hoặc tự lấy từ trang chính thức
 * (Vietcombank, SJC) qua module DataSync. Trang chưa có dữ liệu thì 404.
 */
class MarketsServiceProvider extends ModuleServiceProvider
{
    protected ?string $feature = 'markets';

    protected function sitemap(): iterable
    {
        foreach ([[FxRate::class, 'markets.fx'], [GoldPrice::class, 'markets.gold']] as [$model, $route]) {
            if ($latest = $model::query()->max('quoted_at')) {
                yield new SitemapUrl(route($route), Carbon::parse($latest));
            }
        }
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(FxRate::class, MarketQuotePolicy::class);
        Gate::policy(GoldPrice::class, MarketQuotePolicy::class);
    }
}
