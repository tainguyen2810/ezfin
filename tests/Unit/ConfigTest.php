<?php

it('pins the composer platform to the production PHP version', function () {
    $composer = json_decode(file_get_contents(__DIR__.'/../../composer.json'), true);

    expect($composer['config']['platform']['php'])->toBe('8.3.0');
});
