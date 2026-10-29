<?php

use App\Support\Format;

it('formats money the Vietnamese way', function ($amount, $expected) {
    expect(Format::money($amount))->toBe($expected);
})->with([
    [520000000, '520.000.000 đ'],
    ['15000000.00', '15.000.000 đ'],
    [0, '0 đ'],
    [-120000, '-120.000 đ'],
    [999.6, '1.000 đ'],
    [null, '—'],
]);

it('prefixes the sign for income and expense', function () {
    expect(Format::signedMoney(15000000))->toBe('+15.000.000 đ')
        ->and(Format::signedMoney(-450000))->toBe('-450.000 đ')
        ->and(Format::signedMoney(0))->toBe('0 đ');
});

it('shortens large amounts for compact cards', function () {
    expect(Format::compactMoney(28500000))->toBe('28,5 tr')
        ->and(Format::compactMoney(1200000000))->toBe('1,2 tỷ')
        ->and(Format::compactMoney(18000000))->toBe('18 tr')
        ->and(Format::compactMoney(950000))->toBe('950.000 đ');
});

it('formats percentages and annual rates with a decimal comma', function () {
    expect(Format::percent(5.5))->toBe('5,5%')
        ->and(Format::percent('6.0000'))->toBe('6%')
        ->and(Format::percent(12.345))->toBe('12,35%')
        ->and(Format::rate('4.7500'))->toBe('4,75%/năm')
        ->and(Format::rate(null))->toBe('—');
});

it('formats dates as dd/mm/yyyy', function () {
    expect(Format::date('2026-10-29'))->toBe('29/10/2026')
        ->and(Format::date(null))->toBe('—');
});
