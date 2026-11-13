<?php

namespace App\Modules\Partner;

use App\Modules\Partner\Http\Controllers\PostbackController;
use App\Modules\Partner\Models\AffiliateConversion;
use App\Modules\Partner\Models\OutboundLink;
use App\Modules\Partner\Policies\AffiliateConversionPolicy;
use App\Modules\Partner\Policies\OutboundLinkPolicy;
use App\Support\Modules\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;

class PartnerServiceProvider extends ModuleServiceProvider
{
    protected ?string $feature = 'partner';

    /** Số lần một IP được gọi /di/ mỗi phút, quá thì trả 429. */
    public const REDIRECTS_PER_MINUTE = 30;

    /** Postback mỗi phút từ một IP (mạng affiliate có thể gửi dồn khi duyệt hàng loạt). */
    public const POSTBACKS_PER_MINUTE = 600;

    public function boot(): void
    {
        parent::boot();

        Gate::policy(OutboundLink::class, OutboundLinkPolicy::class);
        Gate::policy(AffiliateConversion::class, AffiliateConversionPolicy::class);

        $this->bootPostbackRoute();

        RateLimiter::for('partner-postback', fn (Request $request) => Limit::perMinute(self::POSTBACKS_PER_MINUTE)->by($request->ip()));
        RateLimiter::for('partner-redirect', fn (Request $request) => Limit::perMinute(self::REDIRECTS_PER_MINUTE)->by($request->ip()));
    }

    /**
     * Postback chuyển đổi từ mạng affiliate: chỉ GET, kiểm tra token bí mật. Không qua nhóm 'web'
     * (không cần phiên, cookie, CSRF) để mỗi lần gọi không tạo phiên mới trong database.
     */
    private function bootPostbackRoute(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::domain(config('ezfin.domains.public'))
            ->middleware([EnsureFeaturesAreActive::using('partner'), 'throttle:partner-postback'])
            ->get('/postback/{network}', PostbackController::class)
            ->where('network', '[a-z0-9][a-z0-9_-]{0,49}')
            ->name('partner.postback');
    }
}
