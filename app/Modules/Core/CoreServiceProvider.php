<?php

namespace App\Modules\Core;

use App\Models\User;
use App\Modules\Core\Console\CheckDeploymentCommand;
use App\Modules\Core\Console\CreateAdminCommand;
use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Models\Redirect;
use App\Modules\Core\Policies\AuditLogPolicy;
use App\Modules\Core\Policies\RedirectPolicy;
use App\Modules\Core\Policies\UserPolicy;
use App\Support\Modules\ModuleServiceProvider;
use App\Support\Seo\SitemapUrl;
use Illuminate\Support\Facades\Gate;

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

        if ($this->app->runningInConsole()) {
            $this->commands([CreateAdminCommand::class, CheckDeploymentCommand::class]);
        }
    }

    protected function sitemap(): iterable
    {
        yield new SitemapUrl(route('home'));

        foreach (array_keys(self::LEGAL_PAGES) as $route) {
            yield new SitemapUrl(route($route));
        }
    }
}
