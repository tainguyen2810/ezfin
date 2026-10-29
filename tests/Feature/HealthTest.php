<?php

it('reports healthy on the health check route', function () {
    $this->get('http://ezfin.test/up')->assertOk();
});

it('serves the temporary home page without indexing', function () {
    $this->withoutVite()
        ->get('http://ezfin.test/')
        ->assertOk()
        ->assertSee('EzFin')
        ->assertSee('noindex, nofollow', false);
});
