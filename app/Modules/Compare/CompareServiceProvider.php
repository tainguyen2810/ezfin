<?php

namespace App\Modules\Compare;

use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Policies\FinancialInstitutionPolicy;
use App\Support\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Gate;

class CompareServiceProvider extends ModuleServiceProvider
{
    protected ?string $feature = 'compare';

    public function boot(): void
    {
        parent::boot();

        Gate::policy(FinancialInstitution::class, FinancialInstitutionPolicy::class);
    }
}
