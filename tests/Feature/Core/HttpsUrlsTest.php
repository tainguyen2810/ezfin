<?php

use App\Providers\AppServiceProvider;
use Illuminate\Support\Facades\URL;

afterEach(fn () => URL::forceScheme(null));

it('generates https URLs (including the Livewire update address) when APP_URL is https', function () {
    config(['app.url' => 'https://ezfin.vn']);
    (new AppServiceProvider(app()))->boot();

    expect(url('/livewire/update'))->toStartWith('https://');
});

it('treats requests forwarded by the HTTPS proxy as secure', function () {
    $this->get('http://ezfin.test/up', ['X-Forwarded-Proto' => 'https', 'X-Forwarded-For' => '203.0.113.9'])->assertOk();

    expect(request()->isSecure())->toBeTrue();
});
