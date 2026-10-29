<?php

namespace App\Providers;

use App\Modules\Core\Listeners\RecordAuthenticationEvents;
use App\Support\Format;
use App\Support\Navigation\Navigation;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Navigation::class);

        // Dùng trong Blade: {{ Format::money($amount) }}
        AliasLoader::getInstance()->alias('Format', Format::class);

        foreach (config('ezfin.modules', []) as $module) {
            $this->app->register($module);
        }
    }

    public function boot(): void
    {
        // Feature flag ở đây là công tắc toàn hệ thống (bật/tắt module), không theo từng người dùng.
        Feature::resolveScopeUsing(fn () => null);

        foreach (config('ezfin.features', []) as $name => $default) {
            Feature::define($name, fn () => (bool) $default);
        }

        EnsureFeaturesAreActive::whenInactive(fn () => abort(404));

        Event::subscribe(RecordAuthenticationEvents::class);
    }
}
