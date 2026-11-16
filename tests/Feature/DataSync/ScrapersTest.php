<?php

use App\Modules\DataSync\Scrapers\Savings;
use App\Modules\DataSync\Support\CatalogParsing;
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

it('reads Techcombank Phat Loc counter and online rates for normal customers under 1 billion from the newest PDF', function () {
    $result = app(Savings\Techcombank::class)->scrape(app(Fetcher::class));
    $rates = scrapedRates(Savings\Techcombank::class);

    expect($result->effectiveFrom?->format('Y-m-d'))->toBe('2026-11-01')
        ->and(SavingsParsing::term('12M'))->toBe(12)
        ->and($rates)->toBe([
            'counter|end_of_term|0|1' => '4.0500',
            'counter|end_of_term|0|3' => '4.3500',
            'counter|end_of_term|0|6' => '5.5500',
            'counter|end_of_term|0|12' => '5.7500',
            'counter|end_of_term|0|13' => '4.8500',
            'counter|end_of_term|0|36' => '4.8500',
            'online|end_of_term|0|0' => '0.0500',
            'online|end_of_term|0|1' => '4.2500',
            'online|end_of_term|0|6' => '6.0500',
            'online|end_of_term|0|12' => '6.2500',
            'online|end_of_term|0|36' => '5.3500',
        ]);
});

it('reads HDBank counter and online rates from the newest PDF, skipping the 500 billion tier and Vikki', function () {
    $result = app(Savings\Hdbank::class)->scrape(app(Fetcher::class));
    $rates = scrapedRates(Savings\Hdbank::class);

    expect($result->effectiveFrom?->format('Y-m-d'))->toBe('2026-10-19')
        ->and($rates)->toMatchArray([
            'counter|end_of_term|0|1' => '3.5000',
            'counter|upfront|0|1' => '3.4000',
            'counter|end_of_term|0|6' => '4.9000',
            'counter|monthly|0|6' => '4.8000',
            'counter|quarterly|0|6' => '4.8000',
            'counter|end_of_term|0|12' => '5.2000',
            'counter|monthly|0|12' => '5.0000',
            'counter|end_of_term|0|13' => '5.4000',
            'counter|end_of_term|0|36' => '4.9000',
            'online|end_of_term|0|1' => '4.2000',
            'online|end_of_term|0|12' => '5.3000',
            'online|monthly|0|12' => '5.1000',
            'online|end_of_term|0|36' => '5.0000',
        ])->not->toContain('7.2000')->not->toContain('7.1000')->not->toContain('5.9500')
        ->and(collect($rates)->keys()->filter(fn ($key) => str_ends_with($key, '|0|0')))->toBeEmpty();
});

it('reads Eximbank counter rates from the newest announcement PDF', function () {
    $result = app(Savings\Eximbank::class)->scrape(app(Fetcher::class));
    $rates = scrapedRates(Savings\Eximbank::class);

    expect($result->effectiveFrom?->format('Y-m-d'))->toBe('2026-10-31')
        ->and($rates)->toBe([
            'counter|end_of_term|0|1' => '3.5000',
            'counter|end_of_term|0|2' => '3.5000',
            'counter|end_of_term|0|6' => '4.2000',
            'counter|end_of_term|0|7' => '4.2000',
            'counter|end_of_term|0|12' => '4.5000',
            'counter|end_of_term|0|24' => '5.0000',
            'counter|end_of_term|0|60' => '5.0000',
            'counter|monthly|0|2' => '3.3000',
            'counter|monthly|0|6' => '4.0000',
            'counter|monthly|0|7' => '4.0000',
            'counter|monthly|0|12' => '4.3000',
            'counter|monthly|0|24' => '4.8000',
            'counter|monthly|0|60' => '4.8000',
            'counter|upfront|0|1' => '3.1000',
            'counter|upfront|0|2' => '3.1000',
            'counter|upfront|0|6' => '3.3000',
            'counter|upfront|0|7' => '3.3000',
            'counter|upfront|0|12' => '3.6000',
            'counter|upfront|0|24' => '4.1000',
            'counter|upfront|0|60' => '4.1000',
        ]);
});

it('reads the highest cashback rate of the card itself, not of suggested cards', function () {
    expect(CatalogParsing::cashback('Hoàn tiền lên đến 16.8% chi tiêu di chuyển; Giao dịch khác: hoàn 0,39%'))->toBe('16.8000')
        ->and(CatalogParsing::cashback('Hoàn tiền chi tiêu: Đến 1,5%'))->toBe('1.5000')
        ->and(CatalogParsing::cashback('Hoàn phí thường niên 699,000 VND. Trả góp lãi suất 0%'))->toBeNull()
        ->and(CatalogParsing::cashback('HOÀN TIỀN KHÔNG GIỚI HẠN'))->toBeNull()
        ->and(CatalogParsing::cardTerms('Lãi suất thẻ tín dụng: 2,45%/tháng.')['interest_rate'])->toBe('29.4000')
        ->and(CatalogParsing::cardTerms('Trả góp lãi suất 0%/tháng.')['interest_rate'])->toBeNull()
        ->and(CatalogParsing::ownText('Tiện ích thẻ này. Có thể bạn quan tâm Thẻ khác HOÀN TIỀN Đến 5%'))->toBe('Tiện ích thẻ này. ');
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

it('reads VIB counter and online rates from the saving-rate API, lowest balance tier, newest effective date', function () {
    $result = app(Savings\Vib::class)->scrape(app(Fetcher::class));
    $rates = scrapedRates(Savings\Vib::class);

    expect($result->effectiveFrom?->format('Y-m-d'))->toBe('2026-10-20')
        ->and($rates)->toMatchArray([
            'counter|end_of_term|0|1' => '4.2500',
            'counter|end_of_term|0|6' => '5.0000',
            'counter|end_of_term|0|12' => '6.5000',
            'counter|monthly|0|12' => '6.3100',
            'counter|quarterly|0|12' => '6.3500',
            'counter|end_of_term|0|36' => '5.3000',
            'online|end_of_term|0|1' => '4.3500',
            'online|end_of_term|0|12' => '7.0000',
            'online|monthly|0|6' => '5.5700',
            'online|quarterly|0|24' => '5.8500',
        ])->and(count($rates))->toBe(68)
        ->and($rates)->not->toContain('6.9000')->not->toContain('7.2000')->not->toContain('6.8000');
});

it('reads MSB counter and online rates from the embedded rate table config', function () {
    $result = app(Savings\Msb::class)->scrape(app(Fetcher::class));
    $rates = scrapedRates(Savings\Msb::class);

    expect($result->effectiveFrom?->format('Y-m-d'))->toBe('2026-10-20')
        ->and($rates)->toMatchArray([
            'counter|end_of_term|0|1' => '4.5000',
            'counter|end_of_term|0|12' => '6.0000',
            'counter|monthly|0|12' => '5.8000',
            'counter|upfront|0|12' => '4.0000',
            'counter|end_of_term|0|36' => '5.3000',
            'online|end_of_term|0|6' => '5.8000',
            'online|end_of_term|0|12' => '6.3000',
            'online|monthly|0|24' => '5.7000',
            'online|upfront|0|36' => '4.8000',
        ])->and($rates)->not->toHaveKey('counter|monthly|0|1')->not->toContain('6.5000')->not->toContain('0.5000');
});

it('reads Nam A Bank counter and online rates from the newest dated rate pages', function () {
    $result = app(Savings\NamA::class)->scrape(app(Fetcher::class));
    $rates = scrapedRates(Savings\NamA::class);

    expect($result->effectiveFrom?->format('Y-m-d'))->toBe('2026-10-20')
        ->and($rates)->toMatchArray([
            'counter|end_of_term|0|1' => '4.5000',
            'counter|end_of_term|0|12' => '5.7000',
            'counter|monthly|0|12' => '5.5500',
            'counter|upfront|0|1' => '4.4800',
            'counter|quarterly|0|6' => '5.0600',
            'counter|monthly|0|24' => '5.5000',
            'online|end_of_term|0|6' => '5.9000',
            'online|end_of_term|0|24' => '6.4000',
        ])->and($rates)->not->toHaveKey('counter|end_of_term|0|24')->not->toHaveKey('counter|end_of_term|0|0')
        ->and(collect($rates)->keys()->filter(fn ($key) => str_contains($key, 'online|monthly')))->toBeEmpty();
});

it('reads Bac A Bank VND rates by posting the currency selector back to the page', function () {
    $result = app(Savings\BacA::class)->scrape(app(Fetcher::class));
    $rates = scrapedRates(Savings\BacA::class);

    expect($result->effectiveFrom?->format('Y-m-d'))->toBe('2026-10-19')
        ->and($rates)->toMatchArray([
            'counter|end_of_term|0|1' => '4.7500',
            'counter|end_of_term|0|6' => '6.5500',
            'counter|end_of_term|0|12' => '6.6000',
            'counter|monthly|0|12' => '6.4500',
            'counter|quarterly|0|12' => '6.5000',
            'counter|end_of_term|0|36' => '6.4500',
        ])->and($rates)->not->toHaveKey('counter|monthly|0|1');

    Http::assertSent(fn ($request) => $request->method() === 'POST' && $request['__VIEWSTATE'] === '/wEPDwUKMTY3NzE5MjIzNmRk'
        && $request['__EVENTTARGET'] === 'ctl00$PlaceHolderMain$g_3d98$ctl00$DDL_LS');
});
