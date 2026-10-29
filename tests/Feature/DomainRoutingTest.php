<?php

it('redirects the app domain root to the public site until the app launches', function () {
    $this->get('http://app.ezfin.test/')
        ->assertRedirect('http://ezfin.test')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});
