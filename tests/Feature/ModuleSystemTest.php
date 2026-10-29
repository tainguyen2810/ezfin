<?php

use App\Support\Navigation\Navigation;
use App\Support\Navigation\NavItem;
use Laravel\Pennant\Feature;
use Tests\Fixtures\Modules\Demo\DemoServiceProvider;

beforeEach(function () {
    Feature::define('demo', true);
    $this->app->register(DemoServiceProvider::class);
    // Ứng dụng đã boot xong nên phải làm mới tra cứu tên route cho module đăng ký muộn.
    app('router')->getRoutes()->refreshNameLookups();
});

it('serves module routes on their own domain only', function () {
    $this->get('http://ezfin.test/demo')->assertOk()->assertSee('demo page');
    $this->get('http://app.ezfin.test/demo')->assertOk()->assertSee('demo app page');
    $this->get('http://ezfin.test/khong-ton-tai')->assertNotFound();
});

it('marks app domain routes as noindex', function () {
    $this->get('http://app.ezfin.test/demo')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    $this->get('http://ezfin.test/demo')->assertHeaderMissing('X-Robots-Tag');
});

it('returns 404 for every route of a disabled module', function () {
    Feature::deactivate('demo');

    $this->get('http://ezfin.test/demo')->assertNotFound();
    $this->get('http://app.ezfin.test/demo')->assertNotFound();
});

it('hides navigation items of a disabled module', function () {
    $labels = fn () => app(Navigation::class)->visible()->pluck('label')->all();

    expect($labels())->toContain('Demo');

    Feature::deactivate('demo');

    expect($labels())->not->toContain('Demo');
});

it('hides navigation items whose route does not exist', function () {
    app(Navigation::class)->add(new NavItem('Chưa có', 'route.chua-co'));

    expect(app(Navigation::class)->visible()->pluck('label'))->not->toContain('Chưa có');
});

it('keeps unreleased modules off by default', function (string $feature) {
    expect(Feature::active($feature))->toBeFalse();
})->with(['markets', 'stocks', 'funds', 'personal', 'pro']);
