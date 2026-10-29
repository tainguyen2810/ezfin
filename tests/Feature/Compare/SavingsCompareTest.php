<?php

use App\Modules\Compare\Actions\CompareSavingsRates;
use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\ProductStatus;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use App\Modules\Compare\Support\SavingsFilters;
use App\Modules\Compare\Support\SavingsInterest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

const SAVINGS_PAGE = 'http://ezfin.test/lai-suat-tiet-kiem';

/** Sản phẩm tiết kiệm đã xuất bản với các dòng lãi suất [kỳ hạn, kênh, lĩnh lãi, lãi suất, từ, đến, số ngày trước hôm nay]. */
function savingsProduct(string $name, array $rates, array $product = [], ?FinancialInstitution $institution = null): FinancialProduct
{
    $model = FinancialProduct::factory()->savings()->published()->create([
        'name' => $name,
        'institution_id' => ($institution ?? FinancialInstitution::factory()->create(['short_name' => 'NH '.$name]))->id,
        ...$product,
    ]);

    foreach ($rates as [$term, $channel, $payment, $rate, $min, $max, $daysAgo]) {
        $model->savingsRates()->forceCreate([
            'term_months' => $term, 'channel' => $channel, 'interest_payment' => $payment, 'rate' => $rate,
            'min_amount' => $min, 'max_amount' => $max, 'effective_from' => today()->subDays($daysAgo),
        ]);
    }

    return $model;
}

/** Kết quả so sánh dạng [tên sản phẩm => tiền lãi]. */
function compareSavings(array $query = []): array
{
    $filters = SavingsFilters::fromRequest(Request::create('/', 'GET', $query));

    return app(CompareSavingsRates::class)->handle($filters)
        ->mapWithKeys(fn (array $result) => [$result['rate']->product->name => $result['interest']])
        ->all();
}

it('computes interest that matches hand-calculated figures', function (int $amount, string $rate, int $months, int $expected) {
    expect(SavingsInterest::simple($amount, $rate, $months))->toBe($expected);
})->with([
    '100 triệu, 5,2%, 12 tháng' => [100_000_000, '5.2000', 12, 5_200_000],
    '100 triệu, 4,75%, 6 tháng' => [100_000_000, '4.7500', 6, 2_375_000],
    '50 triệu, 5,5%, 13 tháng (2.979.166,67)' => [50_000_000, '5.5000', 13, 2_979_167],
    '1.234.567 đ, 3,1%, 1 tháng (3.189,30)' => [1_234_567, '3.1000', 1, 3_189],
    '999.999.999 đ, 6,15%, 36 tháng (184.499.999,8)' => [999_999_999, '6.1500', 36, 184_500_000],
    '100 tỷ, 9,9999%, 120 tháng' => [100_000_000_000, '9.9999', 120, 99_999_000_000],
    'không kỳ hạn' => [100_000_000, '0.5000', 0, 0],
]);

beforeEach(function () {
    $online = SavingsChannel::Online->value;
    $counter = SavingsChannel::Counter->value;
    $end = InterestPayment::EndOfTerm->value;

    savingsProduct('Tiết kiệm A', [
        [12, $online, $end, 5.2, 0, null, 28],
        [12, $online, $end, 4.9, 0, null, 58],          // biểu cũ, đã bị thay
        [12, $online, $end, 5.5, 500_000_000, null, 58], // bậc chỉ có ở biểu cũ: không được dùng
        [12, $online, $end, 6.0, 0, null, -1],           // hiệu lực từ ngày mai
        [12, $counter, $end, 4.8, 0, null, 28],
        [6, $online, $end, 4.75, 0, null, 28],
    ]);
    savingsProduct('Tiết kiệm B', [
        [12, $counter, $end, 5.0, 0, 1_000_000_000, 10],
        [12, $counter, $end, 5.4, 1_000_000_000, null, 10],
        [12, $counter, InterestPayment::Monthly->value, 4.9, 0, null, 10],
    ]);
    savingsProduct('Tiết kiệm C', [[12, $online, $end, 5.2, 0, null, 5]]);
    savingsProduct('Tiết kiệm Nháp', [[12, $online, $end, 9.0, 0, null, 5]], ['status' => ProductStatus::Draft]);
    savingsProduct('Tiết kiệm Ngân Hàng Ngừng', [[12, $online, $end, 9.0, 0, null, 5]],
        institution: FinancialInstitution::factory()->create(['is_active' => false]));
});

it('ranks products by interest received, using the latest effective rate table', function () {
    expect(compareSavings())->toBe([
        'Tiết kiệm A' => 5_200_000,
        'Tiết kiệm C' => 5_200_000,
        'Tiết kiệm B' => 5_000_000,
    ]);
});

it('applies amount tiers, with the upper bound exclusive', function () {
    expect(compareSavings(['so_tien' => '2000000000']))->toBe([
        'Tiết kiệm B' => 108_000_000,
        'Tiết kiệm A' => 104_000_000,
        'Tiết kiệm C' => 104_000_000,
    ])->and(compareSavings(['so_tien' => '1000000000'])['Tiết kiệm B'])->toBe(54_000_000)
        ->and(compareSavings(['so_tien' => '999999999'])['Tiết kiệm B'])->toBe(50_000_000);
});

it('filters by term, channel and interest payment', function () {
    expect(compareSavings(['ky_han' => '6']))->toBe(['Tiết kiệm A' => 2_375_000])
        ->and(compareSavings(['kenh' => 'tai-quay']))->toBe(['Tiết kiệm B' => 5_000_000, 'Tiết kiệm A' => 4_800_000])
        ->and(compareSavings(['linh_lai' => 'hang-thang']))->toBe(['Tiết kiệm B' => 4_900_000])
        ->and(compareSavings(['linh_lai' => 'tat-ca', 'kenh' => 'tai-quay']))->toBe(['Tiết kiệm B' => 5_000_000, 'Tiết kiệm A' => 4_800_000])
        ->and(compareSavings(['ky_han' => '24']))->toBe([]);
});

it('normalizes the query string', function () {
    $filters = SavingsFilters::fromRequest(Request::create('/', 'GET', [
        'so_tien' => '200.000.000', 'ky_han' => '7', 'kenh' => 'online', 'linh_lai' => 'cuoi-ky',
    ]));

    expect($filters->toQuery())->toBe(['so_tien' => 200_000_000, 'kenh' => 'online'])
        ->and($filters->term)->toBe(12)
        ->and(SavingsFilters::fromRequest(Request::create('/', 'GET', ['so_tien' => '5']))->amount)->toBe(SavingsFilters::MIN_AMOUNT)
        ->and(SavingsFilters::fromRequest(Request::create('/', 'GET', ['so_tien' => '999999999999']))->amount)->toBe(SavingsInterest::MAX_AMOUNT);
});

it('renders the ranking with interest, total, source and the calculation note', function () {
    $this->get(SAVINGS_PAGE)
        ->assertOk()
        ->assertDontSee('noindex', false)
        ->assertSee('<link rel="canonical" href="'.SAVINGS_PAGE.'">', false)
        ->assertSee('<strong class="text-navy-900">3</strong> sản phẩm', false)
        ->assertSeeInOrder(['1. Tiết kiệm A', '5.200.000 đ', '5,2%/năm', '105.200.000 đ', '2. Tiết kiệm C', '3. Tiết kiệm B', '5.000.000 đ'])
        ->assertDontSee('Tiền lãi cao nhất')
        ->assertSee('Lãi suất hiệu lực từ')
        ->assertSee('Cách tính')
        ->assertDontSee('Tiết kiệm Nháp');

    $this->get(SAVINGS_PAGE.'?so_tien=2000000000')
        ->assertOk()
        ->assertSee('noindex', false)
        ->assertSeeInOrder(['1. Tiết kiệm B', 'Tiền lãi cao nhất', '108.000.000 đ', 'Từ 1.000.000.000 đ']);
});

it('redirects to a clean URL and keeps tracking parameters', function () {
    $this->get(SAVINGS_PAGE.'?so_tien=100.000.000&ky_han=12&kenh=&linh_lai=cuoi-ky&utm_source=zalo')
        ->assertRedirect(SAVINGS_PAGE.'?utm_source=zalo');
    $this->get(SAVINGS_PAGE.'?so_tien[]=1&ky_han[]=6')->assertRedirect(SAVINGS_PAGE);
    $this->get(SAVINGS_PAGE.'?ky_han=6&so_tien=50000000')->assertOk();
});

it('shows an empty state when nothing matches', function () {
    $this->get(SAVINGS_PAGE.'?ky_han=24')
        ->assertOk()
        ->assertSee('Chưa có lãi suất phù hợp')
        ->assertSee('Về mặc định');
});

it('is in the menu and hidden together with the compare module', function () {
    $this->get(SAVINGS_PAGE)->assertSee('href="'.SAVINGS_PAGE.'"', false);

    Feature::deactivate('compare');

    $this->get(SAVINGS_PAGE)->assertNotFound();
});
