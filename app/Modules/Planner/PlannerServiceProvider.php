<?php

namespace App\Modules\Planner;

use App\Modules\Planner\Tools\CompoundInterestTool;
use App\Modules\Planner\Tools\DepositInterestTool;
use App\Modules\Planner\Tools\InstallmentTool;
use App\Modules\Planner\Tools\LoanTool;
use App\Modules\Planner\Tools\RecurringSavingsTool;
use App\Modules\Planner\Tools\ToolRegistry;
use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavItem;

class PlannerServiceProvider extends ModuleServiceProvider
{
    protected ?string $feature = 'planner';

    public function register(): void
    {
        $this->app->singleton(ToolRegistry::class, function () {
            $tools = new ToolRegistry;
            $tools->register(new CompoundInterestTool);
            $tools->register(new DepositInterestTool);
            $tools->register(new RecurringSavingsTool);
            $tools->register(new LoanTool);
            $tools->register(new InstallmentTool);

            return $tools;
        });
    }

    protected function publicNavigation(): array
    {
        return [
            new NavItem('Công cụ tính', 'planner.tools.index', order: 40),
        ];
    }
}
