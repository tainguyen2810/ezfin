<?php

use Laravel\Pennant\Feature;

it('turns a module off and on', function () {
    $this->artisan('ezfin:feature compare off')->assertSuccessful();
    expect(Feature::active('compare'))->toBeFalse();

    $this->artisan('ezfin:feature compare on')->assertSuccessful();
    expect(Feature::active('compare'))->toBeTrue();
});

it('rejects unknown features and states', function () {
    $this->artisan('ezfin:feature khong-co on')->assertFailed();
    $this->artisan('ezfin:feature compare maybe')->assertExitCode(2);
});

it('lists every feature with its state', function () {
    $this->artisan('ezfin:feature')
        ->expectsTable(['Feature', 'Trạng thái'], collect(config('ezfin.features'))
            ->map(fn ($on, $name) => [$name, $on ? 'bật' : 'tắt'])->values()->all())
        ->assertSuccessful();
});
