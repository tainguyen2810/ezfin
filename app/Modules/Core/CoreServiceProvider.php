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
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;

class CoreServiceProvider extends ModuleServiceProvider
{
    /** Trang thông tin và pháp lý, theo thứ tự ở chân trang. */
    public const LEGAL_PAGES = [
        'legal.about' => 'Giới thiệu',
        'legal.methodology' => 'Phương pháp đánh giá',
        'legal.disclosure' => 'Công bố liên kết',
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

        if ($this->app->runningInConsole()) {
            $this->commands([CreateAdminCommand::class, CheckDeploymentCommand::class]);
        }
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
