<?php

use Laravel\Pennant\Feature;

it('redirects the app domain root to the public site while the app is off', function () {
    Feature::deactivate('personal');

    $this->get('http://app.ezfin.test/')
        ->assertRedirect('http://ezfin.test')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});
