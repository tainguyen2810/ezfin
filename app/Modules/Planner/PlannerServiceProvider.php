<?php

namespace App\Modules\Planner;

use App\Modules\Planner\Tools\CompoundInterestTool;
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
