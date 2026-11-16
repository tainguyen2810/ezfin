<?php

use App\Modules\DataSync\Scrapers\Savings;
use App\Modules\DataSync\Support\Fetcher;
use App\Modules\DataSync\Support\HtmlTable;
use App\Modules\DataSync\Support\RobotsTxt;
use App\Modules\DataSync\Support\SavingsParsing;
use Illuminate\Support\Facades\Http;

require_once __DIR__.'/helpers.php';

beforeEach(function () {
    config(['ezfin.data_sync.delay_ms' => 0]);
    fakeBankSites();
});

/** @return array<string, string> "kênh|lĩnh lãi|từ|kỳ hạn" => lãi suất */
function scrapedRates(string $class): array
{
    $result = app($class)->scrape(app(Fetcher::class));
    expect(app($class)->validate($result->payload))->toBe([]);

    return collect($result->payload['rows'])->mapWithKeys(fn ($row) => [
        "{$row['channel']}|{$row['payment']}|{$row['min_amount']}|{$row['term']}" => $row['rate'],
    ])->all();
}

it('reads Vietcombank counter and online rates from the embedded JSON', function () {
    $rates = scrapedRates(Savings\Vietcombank::class);

    expect($rates)->toHaveKey('counter|end_of_term|0|12', '5.4000')
        ->toHaveKey('counter|end_of_term|0|0', '0.1000')
        ->toHaveKey('online|end_of_term|0|24', '5.5000')
        ->not->toHaveKey('online|end_of_term|0|36')
        ->and(collect(array_keys($rates))->every(fn ($key) => ! str_ends_with($key, '|7')))->toBeTrue();

    expect(app(Savings\Vietcombank::class)->scrape(app(Fetcher::class))->effectiveFrom->toDateString())->toBe('2026-11-15');
});

it('reads BIDV rates from the JSON service with its effective date', function () {
    $rates = scrapedRates(Savings\Bidv::class);
    $result = app(Savings\Bidv::class)->scrape(app(Fetcher::class));

    expect($rates)->toHaveCount(13)->toHaveKey('counter|end_of_term|0|12', '5.4000')->toHaveKey('counter|end_of_term|0|36', '5.5000')
        ->and($result->effectiveFrom->toDateString())->toBe('2026-10-21');
    Http::assertSent(fn ($request) => $request->method() === 'POST' && $request->url() === Savings\Bidv::API);
});

it('reads VietinBank range labels as the starting month', function () {
    $rates = scrapedRates(Savings\Vietinbank::class);

    expect($rates)->toMatchArray([
        'counter|end_of_term|0|0' => '0.1000',
        'counter|end_of_term|0|1' => '2.1000',
        'counter|end_of_term|0|6' => '3.0000',
        'counter|end_of_term|0|12' => '5.4000',
        'counter|end_of_term|0|13' => '5.4000',
        'counter|end_of_term|0|24' => '5.5000',
        'counter|end_of_term|0|36' => '5.5000',
    ])->toHaveKey('counter|end_of_term|0|18', '5.4000');
    expect(count($rates))->toBe(11); // bỏ "Dưới 1 tháng", "Trên 13 tháng đến dưới 18 tháng" (không rõ mốc), "Trên 36 tháng"
});

it('follows the Agribank component link and skips the payment account row', function () {
    $rates = scrapedRates(Savings\Agribank::class);

    expect($rates)->toHaveCount(13)->toHaveKey('counter|end_of_term|0|12', '5.4000')->toHaveKey('counter|end_of_term|0|0', '0.2000');
});

it('reads MB counter and online tables with amount tiers and payment options', function () {
    $rates = scrapedRates(Savings\MbBank::class);
    $result = app(Savings\MbBank::class)->scrape(app(Fetcher::class));

    expect($rates)->toMatchArray([
        'counter|end_of_term|0|12' => '5.7000',
        'counter|upfront|0|12' => '5.3900',
        'counter|monthly|0|12' => '5.5500',
        'counter|end_of_term|1000000000|12' => '5.8000',
        'counter|end_of_term|0|0' => '0.0500',
        'online|end_of_term|0|6' => '5.2000',
        'online|monthly|1000000000|24' => '6.1200',
    ])->not->toHaveKey('counter|monthly|0|1')
        ->and($result->effectiveFrom->toDateString())->toBe('2026-10-21');

    $tier = collect($result->payload['rows'])->firstWhere(fn ($row) => $row['min_amount'] === 0 && $row['term'] === 12);
    expect($tier['max_amount'])->toBe(1_000_000_000);
});

it('reads ACB traditional counter columns and online amount tiers', function () {
    $rates = scrapedRates(Savings\Acb::class);

    expect($rates)->toMatchArray([
        'counter|end_of_term|0|12' => '4.8000',
        'counter|quarterly|0|12' => '4.7000',
        'counter|monthly|0|12' => '4.6500',
        'counter|upfront|0|12' => '4.5500',
        'counter|end_of_term|0|13' => '4.9000',
        'counter|monthly|0|13' => '4.7500',
        'online|end_of_term|0|12' => '5.2000',
        'online|end_of_term|200000000|12' => '5.3000',
        'online|end_of_term|5000000000|1' => '4.6000',
    ])->not->toHaveKey('counter|end_of_term|0|0');
    // Cột TK Phúc An Lộc (5,00% kỳ hạn 12) không được lấy làm lãi tại quầy.
    expect(collect($rates)->filter(fn ($rate, $key) => str_starts_with($key, 'counter|') && $rate === '5.0000'))->toBeEmpty();
});

it('reads TPBank online and counter columns from the WCM component', function () {
    // Mẫu chụp từ trang thật: chữ có dấu viết bằng ký tự tổ hợp (NFD), phải chuẩn hóa mới khớp "điện tử".
    expect(file_get_contents(base_path('tests/Fixtures/DataSync/tpbank-component.html')))->not->toContain('ĐIỆN TỬ')
        ->and(Fetcher::nfc("TI\u{1EBE}T KI\u{1EC6}M \u{110}I\u{CA}\u{323}N T\u{1AF}\u{309}"))->toBe('TIẾT KIỆM ĐIỆN TỬ');

    $rates = scrapedRates(Savings\Tpbank::class);

    expect($rates)->toMatchArray([
        'online|end_of_term|0|1' => '4.7500',
        'online|end_of_term|0|36' => '5.8000',
        'counter|end_of_term|0|6' => '4.9000',
    ])->not->toHaveKey('counter|end_of_term|0|12');
});

it('reads OCB counter and online columns and the effective date', function () {
    $result = app(Savings\Ocb::class)->scrape(app(Fetcher::class));
    $rates = scrapedRates(Savings\Ocb::class);

    expect($result->effectiveFrom?->format('Y-m-d'))->toBe('2026-11-06')
        ->and($rates)->toMatchArray([
            'counter|end_of_term|0|0' => '0.0000',
            'counter|end_of_term|0|12' => '6.2000',
            'online|end_of_term|0|12' => '6.3000',
            'online|end_of_term|0|36' => '6.0000',
        ])->toHaveCount(24);
});

it('parses Vietnamese term labels, rates and multi-level tables', function () {
    expect(SavingsParsing::term('Không kỳ hạn'))->toBe(0)
        ->and(SavingsParsing::term('KKH'))->toBe(0)
        ->and(SavingsParsing::term('01 tháng'))->toBe(1)
        ->and(SavingsParsing::term('12T'))->toBe(12)
        ->and(SavingsParsing::term('24-months'))->toBe(24)
        ->and(SavingsParsing::term('Từ 6 tháng đến dưới 7 tháng'))->toBe(6)
        ->and(SavingsParsing::term('Trên 12 tháng đến 13 tháng'))->toBe(13)
        ->and(SavingsParsing::term('7 ngày'))->toBeNull()
        ->and(SavingsParsing::term('1 - 3 tuần'))->toBeNull()
        ->and(SavingsParsing::term('Trên 36 tháng'))->toBeNull()
        ->and(SavingsParsing::rate('5,4 %'))->toBe('5.4000')
        ->and(SavingsParsing::rate('4,90*'))->toBe('4.9000')
        ->and(SavingsParsing::rate('-'))->toBeNull()
        ->and(SavingsParsing::fraction(0.054))->toBe('5.4000');

    $table = HtmlTable::tables('<table><tr><td rowspan="2">A</td><td colspan="2">B</td></tr><tr><td>C</td><td>D</td></tr></table>')[0];
    expect(HtmlTable::grid($table))->toBe([['A', 'B', 'B'], ['A', 'C', 'D']]);
});

it('applies robots.txt rules for the EzFin bot or the wildcard group', function () {
    $rules = RobotsTxt::parse("User-agent: GPTBot\nDisallow: /\n\nUser-agent: *\nDisallow: /api\nDisallow: /wps/portal/*\nAllow: /api/public\n");

    expect(RobotsTxt::evaluate($rules, '/vn/tra-cuu-lai-suat'))->toBeTrue()
        ->and(RobotsTxt::evaluate($rules, '/api/rates'))->toBeFalse()
        ->and(RobotsTxt::evaluate($rules, '/api/public/rates'))->toBeTrue()
        ->and(RobotsTxt::evaluate($rules, '/wps/portal/x'))->toBeFalse()
        ->and(RobotsTxt::evaluate(RobotsTxt::parse("User-agent: EzFinBot\nDisallow: /\n\nUser-agent: *\nAllow: /\n"), '/a'))->toBeFalse();
});
