<?php

use Illuminate\Routing\RouteCollection;

it('renders every style guide page outside production', function (string $path) {
    $this->withoutVite()
        ->get('http://ezfin.test'.$path)
        ->assertOk()
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertSee('dữ liệu minh họa');
})->with(['/_ui', '/_ui/so-sanh', '/_ui/ung-dung']);

it('formats amounts and pagination in Vietnamese on the style guide', function () {
    $this->withoutVite()
        ->get('http://ezfin.test/_ui')
        ->assertSee('520.000.000 đ')
        ->assertSee('Hiển thị')
        ->assertDontSee('Showing');
});

it('never registers the style guide in production', function () {
    expect(file_get_contents(base_path('app/Modules/Core/routes/public.php')))
        ->toContain('if (! app()->isProduction())');

    $this->app['env'] = 'production';
    app('router')->setRoutes(new RouteCollection);
    require base_path('app/Modules/Core/routes/public.php');
    app('router')->getRoutes()->refreshNameLookups();

    expect(Route::has('style-guide.components'))->toBeFalse()
        ->and(Route::has('home'))->toBeTrue();
});
