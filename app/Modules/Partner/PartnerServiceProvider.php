<?php

namespace App\Modules\Partner;

use App\Modules\Partner\Models\OutboundLink;
use App\Modules\Partner\Policies\OutboundLinkPolicy;
use App\Support\Modules\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

class PartnerServiceProvider extends ModuleServiceProvider
{
    protected ?string $feature = 'partner';

    /** Số lần một IP được gọi /di/ mỗi phút, quá thì trả 429. */
    public const REDIRECTS_PER_MINUTE = 30;

    public function boot(): void
    {
        parent::boot();

        Gate::policy(OutboundLink::class, OutboundLinkPolicy::class);

        RateLimiter::for('partner-redirect', fn (Request $request) => Limit::perMinute(self::REDIRECTS_PER_MINUTE)->by($request->ip()));
    }
}
