<?php

use App\Modules\Compare\Actions\BestSavingsRates;
use App\Modules\Compare\Enums\InterestPayment;
use App\Modules\Compare\Enums\SavingsChannel;
use App\Modules\Compare\Models\FinancialInstitution;
use App\Modules\Compare\Models\FinancialProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;

uses(RefreshDatabase::class);

/** Sản phẩm tiết kiệm đã xuất bản với các dòng [kỳ hạn, kênh, lãi suất, số ngày trước hôm nay]. */
function landingRateProduct(FinancialInstitution $institution, array $rates): FinancialProduct
{
    $model = FinancialProduct::factory()->savings()->published()->create(['institution_id' => $institution->id]);
    foreach ($rates as [$term, $channel, $rate, $daysAgo]) {
        $model->savingsRates()->forceCreate([
            'term_months' => $term, 'channel' => $channel->value, 'interest_payment' => InterestPayment::EndOfTerm->value,
            'rate' => $rate, 'min_amount' => 0, 'effective_from' => today()->subDays($daysAgo),
        ]);
    }

    return $model;
}

beforeEach(function () {
    Feature::activate('compare');

    $alpha = FinancialInstitution::factory()->create(['short_name' => 'Alpha', 'slug' => 'alpha']);
    $beta = FinancialInstitution::factory()->create(['short_name' => 'Beta', 'slug' => 'beta']);

    landingRateProduct($alpha, [
        [12, SavingsChannel::Online, 5.2, 60],
        [12, SavingsChannel::Online, 5.6, 10],
        [12, SavingsChannel::Counter, 5.1, 10],
        [6, SavingsChannel::Online, 4.7, 10],
    ]);
    landingRateProduct($beta, [
        [12, SavingsChannel::Counter, 5.8, 60],
        [12, SavingsChannel::Online, 9.9, -3], // chưa hiệu lực
    ]);
});

it('ranks banks by their best rate for a term, with online, counter and the change over 30 days', function () {
    $rows = app(BestSavingsRates::class)->forTerm(12);

    expect($rows->map(fn ($row) => [$row['institution']->short_name, $row['online'], $row['counter'], $row['best'], $row['previous']])->all())
        ->toBe([
            ['Beta', null, 5.8, 5.8, 5.8],
            ['Alpha', 5.6, 5.1, 5.6, 5.2],
        ])
        ->and(app(BestSavingsRates::class)->availableTerms())->toBe([6, 12])
        ->and(app(BestSavingsRates::class)->topByTerm([12])[12]['rate'])->toBe(5.8);
});

it('shows the landing page titled with the current month, computed interest and FAQ schema', function () {
    $html = $this->get('http://ezfin.test/lai-suat-tiet-kiem/12-thang')
        ->assertOk()
        ->assertSee('Lãi suất tiết kiệm 12 tháng cao nhất tháng '.now()->format('m/Y'))
        ->assertSeeInOrder(['Beta', 'Alpha'])
        ->assertSee('5.800.000 đ')          // 100 triệu × 5,8% × 12/12
        ->assertSee('▲ 0,4 điểm', false)
        ->assertSee('"@type":"FAQPage"', false)
        ->assertSee('"@type":"ItemList"', false)
        ->assertSee('href="http://ezfin.test/lai-suat-tiet-kiem/6-thang"', false)
        ->getContent();

    // JSON-LD hợp lệ.
    preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $blocks);
    foreach ($blocks[1] as $json) {
        expect(json_decode($json, true, flags: JSON_THROW_ON_ERROR))->toHaveKey('@context');
    }
});

it('returns 404 for terms without a landing page or without data, and for non-canonical numbers', function (string $path) {
    $this->get('http://ezfin.test/lai-suat-tiet-kiem/'.$path)->assertNotFound();
})->with(['24-thang', '5-thang', '012-thang', '99999999999999999999-thang']);

it('lists only landing pages with data in the sitemap and links them from the compare pages', function () {
    $this->get('http://ezfin.test/sitemap.xml')
        ->assertSee('http://ezfin.test/lai-suat-tiet-kiem/12-thang')
        ->assertSee('http://ezfin.test/lai-suat-tiet-kiem/6-thang')
        ->assertDontSee('lai-suat-tiet-kiem/24-thang');

    $this->get('http://ezfin.test/lai-suat-tiet-kiem/bang-lai-suat')->assertSee('http://ezfin.test/lai-suat-tiet-kiem/12-thang', false);
});

it('hides landing pages when the compare feature is off', function () {
    Feature::deactivate('compare');

    $this->get('http://ezfin.test/lai-suat-tiet-kiem/12-thang')->assertNotFound();
});
