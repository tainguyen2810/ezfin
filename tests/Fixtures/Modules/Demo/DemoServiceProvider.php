<?php

namespace Tests\Fixtures\Modules\Demo;

use App\Support\Modules\ModuleServiceProvider;
use App\Support\Navigation\NavItem;

class DemoServiceProvider extends ModuleServiceProvider
{
    protected ?string $feature = 'demo';

    protected function publicNavigation(): array
    {
        return [new NavItem('Demo', 'demo.index', 10)];
    }
}
