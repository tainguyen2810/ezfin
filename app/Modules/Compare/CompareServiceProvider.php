<?php

namespace App\Modules\Compare;

use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Compare\Policies\FinancialInstitutionPolicy;
use App\Modules\Compare\Policies\FinancialProductPolicy;
use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavItem;
use Illuminate\Support\Facades\Gate;

class CompareServiceProvider extends ModuleServiceProvider
{
    protected ?string $feature = 'compare';

    protected function publicNavigation(): array
    {
        return [
            new NavItem('Lãi suất tiết kiệm', 'compare.savings.index', order: 10),
            new NavItem('Thẻ tín dụng', 'compare.credit-cards.index', order: 20),
            new NavItem('Tài khoản ngân hàng', 'compare.bank-accounts.index', order: 30),
        ];
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(FinancialInstitution::class, FinancialInstitutionPolicy::class);
        Gate::policy(FinancialProduct::class, FinancialProductPolicy::class);
    }
}
