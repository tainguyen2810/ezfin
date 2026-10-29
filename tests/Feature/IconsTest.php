<?php

use App\Support\Icons;

it('inlines an icon with the given classes', function () {
    $svg = Icons::svg('plus', 'size-4 text-white');

    expect($svg)->toStartWith('<svg class="size-4 text-white" ')->toContain('aria-hidden="true"');
});

it('rejects icon names that could escape the icon folder', function () {
    Icons::svg('../../.env');
})->throws(InvalidArgumentException::class);
