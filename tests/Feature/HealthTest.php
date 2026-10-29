<?php

it('reports healthy on the health check route', function () {
    $this->get('http://ezfin.test/up')->assertOk();
});
