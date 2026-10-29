<?php

namespace App\Modules\Core;

use App\Models\User;
use App\Modules\Core\Console\CreateAdminCommand;
use App\Modules\Core\Models\AuditLog;
use App\Modules\Core\Policies\AuditLogPolicy;
use App\Modules\Core\Policies\UserPolicy;
use App\Support\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;

class CoreServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);

        if ($this->app->runningInConsole()) {
            $this->commands([CreateAdminCommand::class]);
        }
    }
}
