<?php

namespace App\Modules\Core;

use App\Models\User;
use App\Modules\Core\Console\CheckDeploymentCommand;
use App\Modules\Core\Console\CreateAdminCommand;
use App\Modules\Core\Http\Controllers\InstallController;
use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Redirect;
use App\Modules\Core\Policies\AuditLogPolicy;
use App\Modules\Core\Policies\RedirectPolicy;
use App\Modules\Core\Policies\UserPolicy;
use App\Support\Modules\ModuleServiceProvider;
use App\Support\Seo\SitemapUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

class CoreServiceProvider extends ModuleServiceProvider
{
    /** Trang thông tin và pháp lý, theo thứ tự ở chân trang. */
    public const LEGAL_PAGES = [
        'legal.about' => 'Giới thiệu',
        'legal.methodology' => 'Phương pháp đánh giá',
        'legal.disclosure' => 'Công bố liên kết',
        'legal.disclaimer' => 'Miễn trừ trách nhiệm',
        'legal.terms' => 'Điều khoản sử dụng',
        'legal.privacy' => 'Quyền riêng tư',
        'legal.contact' => 'Liên hệ',
    ];

    public function boot(): void
    {
        parent::boot();

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(Redirect::class, RedirectPolicy::class);

        $this->bootInstaller();
        $this->bootLegacyAppDomain();

        if ($this->app->runningInConsole()) {
            $this->commands([CreateAdminCommand::class, CheckDeploymentCommand::class]);
        }
    }

    /** Đường dẫn trên tên miền phụ cũ giữ nguyên khi chuyển về tên miền chính. */
    public const LEGACY_SAME_PATHS = ['dang-nhap', 'dang-ky', 'dang-xuat', 'quen-mat-khau', 'dat-lai-mat-khau', 'xac-nhan-email', 'cai-dat'];

    /**
     * Tên miền phụ cũ (app.ezfin.vn): chuyển 301 mọi URL về tên miền chính. Trang quản trị và đăng nhập
     * giữ đường dẫn; màn hình ứng dụng thêm tiền tố /ung-dung.
     */
    private function bootLegacyAppDomain(): void
    {
        $legacy = config('ezfin.domains.legacy_app');

        if ($this->app->routesAreCached() || ! $legacy || $legacy === config('ezfin.domains.public')) {
            return;
        }

        Route::domain($legacy)->middleware('noindex')->group(function () {
            Route::get('/robots.txt', fn () => response("User-agent: *\nDisallow: /\n")->header('Content-Type', 'text/plain; charset=UTF-8'));
            Route::any('/{path?}', fn (Request $request, string $path = '') => redirect()->away(self::publicUrlFor($path, $request->getQueryString()), 301))
                ->where('path', '.*')->name('legacy-app.redirect');
        });
    }

    /** Địa chỉ trên tên miền chính tương ứng một đường dẫn của tên miền phụ cũ. */
    public static function publicUrlFor(string $path, ?string $query = null): string
    {
        $path = trim($path, '/');
        $first = strtok($path, '/') ?: '';
        $prefix = config('ezfin.app_prefix');

        $target = match (true) {
            $path === '' => $prefix,
            $first === config('ezfin.admin_path'), $first === $prefix, in_array($first, self::LEGACY_SAME_PATHS, true) => $path,
            default => "$prefix/$path",
        };

        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'https';

        return "$scheme://".config('ezfin.domains.public').'/'.$target.($query ? "?$query" : '');
    }

    /**
     * Trang cài đặt /cai-dat trên mọi tên miền. Không dùng nhóm 'web' vì session, cache đặt ở database
     * và APP_KEY có thể chưa có lúc cài lần đầu.
     */
    private function bootInstaller(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware('noindex')->controller(InstallController::class)->group(function () {
            Route::get('/cai-dat', 'show')->name('install.show');
            Route::post('/cai-dat/database', 'database')->name('install.database');
            Route::post('/cai-dat/tao-bang', 'migrate')->name('install.migrate');
            Route::post('/cai-dat/quan-tri', 'admin')->name('install.admin');
        });
    }

    protected function sitemap(): iterable
    {
        yield new SitemapUrl(route('home'));

        foreach (array_keys(self::LEGAL_PAGES) as $route) {
            yield new SitemapUrl(route($route));
        }
    }
}
