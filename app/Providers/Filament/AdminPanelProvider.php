<?php

namespace App\Providers\Filament;

use App\Http\Middleware\NoIndex;
use App\Modules\Core\Filament\Pages\Login;
use App\Support\Filament\InitialsAvatarProvider;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Trang quản trị: app.ezfin.vn/<EZFIN_ADMIN_PATH>, chỉ tài khoản có vai trò nội bộ,
 * bắt buộc xác thực 2 lớp bằng ứng dụng, có mã khôi phục.
 */
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->default()
            ->id('admin')
            ->domain(config('ezfin.domains.app'))
            ->path(config('ezfin.admin_path'))
            ->login(Login::class)
            ->profile(isSimple: false)
            ->multiFactorAuthentication(
                AppAuthentication::make()->recoverable()->brandName('EzFin'),
                isRequired: (bool) config('ezfin.admin_2fa'),
            )
            ->brandName('EzFin Quản trị')
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            ->colors(['primary' => Color::Emerald, 'gray' => Color::Slate])
            ->font('Plus Jakarta Sans', provider: LocalFontProvider::class)
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => Blade::render("@vite('resources/css/admin.css')"))
            ->sidebarCollapsibleOnDesktop()
            ->pages([Dashboard::class])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                NoIndex::class,
            ])
            ->authMiddleware([Authenticate::class]);

        // Mỗi module tự khai báo resource/page quản trị trong app/Modules/<Tên>/Filament.
        foreach (glob(app_path('Modules/*/Filament'), GLOB_ONLYDIR) as $directory) {
            $module = basename(dirname($directory));
            $namespace = "App\\Modules\\{$module}\\Filament";

            if (is_dir("$directory/Resources")) {
                $panel->discoverResources(in: "$directory/Resources", for: "$namespace\\Resources");
            }
            if (is_dir("$directory/Widgets")) {
                $panel->discoverWidgets(in: "$directory/Widgets", for: "$namespace\\Widgets");
            }
        }

        return $panel;
    }
}
