<?php

use App\Modules\Compare\Actions\SavingsRateBoard;
use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\ProductStatus;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

/** Sản phẩm tiết kiệm với các dòng [kỳ hạn, kênh, lãi suất, số ngày trước hôm nay, số tiền tối thiểu]. */
function bankRateProduct(FinancialInstitution $institution, array $rates, array $product = []): FinancialProduct
{
    $model = FinancialProduct::factory()->savings()->published()->create(['institution_id' => $institution->id, ...$product]);
    foreach ($rates as [$term, $channel, $rate, $daysAgo, $min]) {
        $model->savingsRates()->forceCreate([
            'term_months' => $term, 'channel' => $channel->value, 'interest_payment' => InterestPayment::EndOfTerm->value,
            'rate' => $rate, 'min_amount' => $min ?? 0, 'effective_from' => today()->subDays($daysAgo),
        ]);
    }

    return $model;
}

beforeEach(function () {
    Feature::activate('compare');

    $this->alpha = FinancialInstitution::factory()->create(['short_name' => 'Alpha', 'slug' => 'alpha']);
    $this->beta = FinancialInstitution::factory()->create(['short_name' => 'Beta', 'slug' => 'beta']);

    bankRateProduct($this->alpha, [
        [12, SavingsChannel::Online, 5.0, 180, null],
        [12, SavingsChannel::Online, 5.3, 90, null],
        [12, SavingsChannel::Online, 5.6, 10, null],
        [12, SavingsChannel::Online, 6.5, 10, 1_000_000_000], // bậc tiền lớn: không dùng
        [12, SavingsChannel::Online, 9.9, -5, null],          // chưa hiệu lực
        [6, SavingsChannel::Online, 4.5, 180, null],
        [6, SavingsChannel::Online, 4.7, 10, null],
        [12, SavingsChannel::Counter, 5.1, 10, null],
    ]);
    bankRateProduct($this->alpha, [[12, SavingsChannel::Online, 5.4, 20, null]]);
    bankRateProduct($this->beta, [
        [12, SavingsChannel::Online, 5.8, 15, null],
        [1, SavingsChannel::Online, 3.9, 15, null],
    ]);
    bankRateProduct($this->beta, [[12, SavingsChannel::Online, 8.25, 15, null]], ['status' => ProductStatus::Draft]);
});

it('builds the bank × term matrix from the latest effective table at the lowest tier', function () {
    $rows = app(SavingsRateBoard::class)->matrix(SavingsChannel::Online);

    expect($rows->map(fn ($row) => $row['institution']->short_name)->all())->toBe(['Beta', 'Alpha'])
        ->and($rows[1]['rates'][12])->toBe(5.6)
        ->and($rows[1]['rates'][6])->toBe(4.7)
        ->and($rows[1]['rates'][1])->toBeNull()
        ->and($rows[0]['rates'][12])->toBe(5.8)
        ->and(app(SavingsRateBoard::class)->matrix(SavingsChannel::Counter)->pluck('rates.12')->all())->toBe([5.1]);
});

it('returns the rate history per change date, carrying forward each product\'s latest table', function () {
    $history = app(SavingsRateBoard::class)->history($this->alpha);

    expect($history['channel'])->toBe(SavingsChannel::Online)
        ->and($history['dates'])->toBe([
            today()->subDays(180)->toDateString(),
            today()->subDays(90)->toDateString(),
            today()->subDays(20)->toDateString(),
            today()->subDays(10)->toDateString(),
        ])
        ->and($history['series'][12])->toBe([5.0, 5.3, 5.4, 5.6])
        ->and($history['series'][6])->toBe([4.5, 4.5, 4.5, 4.7]);
});

it('shows the rate table page with links to each bank and the best rate per term', function () {
    $this->get('http://ezfin.test/lai-suat-tiet-kiem/bang-lai-suat')
        ->assertOk()
        ->assertSeeInOrder(['Beta', 'Alpha'])
        ->assertSee('http://ezfin.test/ngan-hang/alpha', false)
        ->assertSee('5,8%')
        ->assertDontSee('9,9%')
        ->assertDontSee('8,25%');

    $this->get('http://ezfin.test/lai-suat-tiet-kiem/bang-lai-suat?kenh=tai-quay')->assertOk()->assertSee('5,1%')->assertDontSee('5,8%');
});

it('shows a bank page with its rates by channel and the history chart', function () {
    $this->get('http://ezfin.test/ngan-hang/alpha')
        ->assertOk()
        ->assertSee('Lãi suất Alpha')
        ->assertSee('5,6%')
        ->assertSee('5,1%')
        ->assertSee('Lịch sử lãi suất')
        ->assertSee(Format::date(today()->subDays(180)));

    $this->get('http://ezfin.test/ngan-hang')->assertOk()->assertSee('Alpha')->assertSee('Beta');
});

it('hides banks that are inactive or have nothing published', function () {
    $closed = FinancialInstitution::factory()->create(['slug' => 'dong-cua', 'is_active' => false]);
    bankRateProduct($closed, [[12, SavingsChannel::Online, 7.15, 5, null]]);
    $empty = FinancialInstitution::factory()->create(['slug' => 'chua-co']);
    bankRateProduct($empty, [[12, SavingsChannel::Online, 7.15, 5, null]], ['status' => ProductStatus::Draft]);

    $this->get('http://ezfin.test/ngan-hang/dong-cua')->assertNotFound();
    $this->get('http://ezfin.test/ngan-hang/chua-co')->assertNotFound();
    $this->get('http://ezfin.test/ngan-hang/khong-ton-tai')->assertNotFound();
    $this->get('http://ezfin.test/lai-suat-tiet-kiem/bang-lai-suat')->assertDontSee('7,15%');
});

it('lists the new pages in the sitemap and hides them when the module is off', function () {
    $sitemap = $this->get('http://ezfin.test/sitemap.xml')->assertOk();
    $sitemap->assertSee('http://ezfin.test/ngan-hang/alpha', false)
        ->assertSee('http://ezfin.test/lai-suat-tiet-kiem/bang-lai-suat', false);

    Feature::deactivate('compare');
    $this->get('http://ezfin.test/ngan-hang/alpha')->assertNotFound();
    $this->get('http://ezfin.test/lai-suat-tiet-kiem/bang-lai-suat')->assertNotFound();
});

it('drops term columns that no bank publishes', function () {
    $this->get('http://ezfin.test/lai-suat-tiet-kiem/bang-lai-suat')
        ->assertOk()
        ->assertSee('>1 th', false)
        ->assertDontSee('>2 th', false);
});
