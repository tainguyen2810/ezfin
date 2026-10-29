<?php

it('reports healthy on the health check route', function () {
    $this->get('/up')->assertOk();
});

it('serves the temporary home page without indexing', function () {
    $this->withoutVite()
        ->get('/')
        ->assertOk()
        ->assertSee('EzFin')
        ->assertSee('noindex, nofollow', false);
});
