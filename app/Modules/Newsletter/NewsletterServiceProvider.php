<?php

namespace App\Modules\Newsletter;

use App\Modules\Newsletter\Console\RateAlertsCommand;
use App\Modules\Newsletter\Console\WeeklyDigestCommand;
use App\Modules\Newsletter\Models\Subscriber;
use App\Modules\Newsletter\Policies\SubscriberPolicy;
use App\Support\Modules\ModuleServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Nhận tin qua email: báo lãi suất tăng, bản tin tuần. Double opt-in, hủy một chạm.
 * Form đăng ký: @include('newsletter::signup', ['term' => 12]) ở trang khác (kiểm tra flag newsletter trước).
 */
class NewsletterServiceProvider extends ModuleServiceProvider
{
    protected ?string $feature = 'newsletter';

    public const SIGNUPS_PER_MINUTE = 10;

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Subscriber::class, SubscriberPolicy::class);

        RateLimiter::for('newsletter-signup', fn (Request $request) => Limit::perMinute(self::SIGNUPS_PER_MINUTE)->by($request->ip()));

        if ($this->app->runningInConsole()) {
            $this->commands([RateAlertsCommand::class, WeeklyDigestCommand::class]);
        }
    }
}
